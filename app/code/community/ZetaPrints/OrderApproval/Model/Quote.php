<?php

class ZetaPrints_OrderApproval_Model_Quote extends Mage_Sales_Model_Quote {

  protected $_approved_items = null;

  protected function _afterSave () {
    if ($this->_items !== null)
      $this->getAllItemsCollection()->save();
    else if ($this->_approved_items !== null)
      $this->getApprovedItemsCollection()->save();

    $_items = $this->_items;
    $this->_items = null;

    parent::_afterSave();

    $this->_items = $_items;
  }

  public function getAllItemsCollection () {
    return parent::getItemsCollection();
  }

  public function getApprovedItemsCollection () {
     if (is_null($this->_approved_items)) {
      $this->_approved_items = Mage::getModel('sales/quote_item')
        ->getCollection()
        ->addFieldToFilter('approved', 1)
        ->setQuote($this);
    }

    return $this->_approved_items;
  }

  public function getItemsCollection($use_cache = true) {
    return $this->getApprovedItemsCollection();
  }

  public function getAllItemsCount () {
    $number = 0;

    foreach ($this->getAllItemsCollection() as $item) {
      if ($item->isDeleted() || $item->getParentItemId()
          || $item->getParentItem())
        continue;

      $number++;
    }

    return $number;
  }

  public function getAllItems ($incl_unapproved = false) {
    if (!$incl_unapproved)
      return parent::getAllItems();

    $items = array();

    foreach ($this->getAllItemsCollection() as $item)
      if (!$item->isDeleted())
        $items[] =  $item;

    return $items;
  }

  public function hasItems ($incl_unapproved = false) {
    if (!$incl_unapproved)
      return parent::hasItems();

    return sizeof($this->getAllItems(true)) > 0;
  }

  public function getItemById ($itemId, $incl_unapproved = false) {
    if (!$incl_unapproved)
      return parent::getItemById($itemId);

    return $this->getAllItemsCollection()->getItemById($itemId);
  }

  public function removeItem ($itemId, $incl_unapproved = false) {
    if (!$incl_unapproved)
      return parent::removeItem($itemId);

    if ($item = $this->getItemById($itemId, true)) {
      //If we remove item from quote - we can't use multishipping mode
      $this->setIsMultiShipping(false);
      $item->isDeleted(true);

      if ($item->getHasChildren())
        foreach ($item->getChildren() as $child)
          $child->isDeleted(true);

      Mage::dispatchEvent('sales_quote_remove_item', array('quote_item' => $item));
    }

    return $this;
  }

  public function getAllVisibleItems ($incl_unapproved = false) {
    if (!$incl_unapproved)
      return parent::getAllVisibleItems();

    $items = array();

    foreach ($this->getAllItemsCollection() as $item)
      if (!$item->isDeleted() && !$item->getParentItemId())
        $items[] =  $item;

    return $items;
  }

  public function addItem (Mage_Sales_Model_Quote_Item $item) {
    if ($item->isNominal() && $this->hasItems() || $this->hasNominalItems())
      Mage::throwException(Mage::helper('sales')
        ->__('Nominal item can be purchased standalone only. To proceed please remove other items from the quote.'));

    $item->setQuote($this);

    if (!$item->getId()) {
      $this->getAllItemsCollection()->addItem($item);

      Mage::dispatchEvent('sales_quote_add_item', array('quote_item' => $item));
    }

    return $this;
  }

  public function getItemByProduct ($product) {
    foreach ($this->getAllItems(true) as $item)
      if ($item->representProduct($product))
        return $item;

    return false;
  }

  public function collectTotals() {
    if ($this->getTotalsCollectedFlag())
      return $this;

    Mage::dispatchEvent($this->_eventPrefix . '_collect_totals_before',
                                          array($this->_eventObject => $this));

    $this->setSubtotal(0);
    $this->setBaseSubtotal(0);

    $this->setSubtotalWithDiscount(0);
    $this->setBaseSubtotalWithDiscount(0);

    $this->setGrandTotal(0);
    $this->setBaseGrandTotal(0);

    foreach ($this->getAllAddresses() as $address) {
      $address->setSubtotal(0);
      $address->setBaseSubtotal(0);

      $address->setGrandTotal(0);
      $address->setBaseGrandTotal(0);

      $address->collectTotals();

      $this->setSubtotal((float) $this->getSubtotal()+$address->getSubtotal());
      $this->setBaseSubtotal((float) $this->getBaseSubtotal()
                                                + $address->getBaseSubtotal() );

      $this->setSubtotalWithDiscount((float) $this->getSubtotalWithDiscount()
                                        + $address->getSubtotalWithDiscount() );
      $this->setBaseSubtotalWithDiscount(
                                  (float) $this->getBaseSubtotalWithDiscount()
                                    + $address->getBaseSubtotalWithDiscount() );

      $this->setGrandTotal((float) $this->getGrandTotal()
                                                  + $address->getGrandTotal());
      $this->setBaseGrandTotal((float) $this->getBaseGrandTotal()
                                              + $address->getBaseGrandTotal());
    }

    Mage::helper('sales')->checkQuoteAmount($this, $this->getGrandTotal());
    Mage::helper('sales')->checkQuoteAmount($this, $this->getBaseGrandTotal());

    $this->setItemsCount(0);
    $this->setItemsQty(0);
    $this->setVirtualItemsQty(0);

    foreach ($this->getAllVisibleItems(true) as $item) {
      if ($item->getParentItem())
        continue;

      if (($children = $item->getChildren()) && $item->isShipSeparately())
        foreach ($children as $child)
          if ($child->getProduct()->getIsVirtual())
            $this->setVirtualItemsQty($this->getVirtualItemsQty()
                                          + $child->getQty()*$item->getQty() );

      if ($item->getProduct()->getIsVirtual())
        $this->setVirtualItemsQty($this->getVirtualItemsQty() + $item->getQty());

      $this->setItemsCount($this->getItemsCount()+1);
      $this->setItemsQty((float) $this->getItemsQty()+$item->getQty());
    }

    $this->setData('trigger_recollect', 0);
    $this->_validateCouponCode();

    Mage::dispatchEvent($this->_eventPrefix . '_collect_totals_after',
                                            array($this->_eventObject=>$this) );

    $this->setTotalsCollectedFlag(true);

    return $this;
  }
}

?>
