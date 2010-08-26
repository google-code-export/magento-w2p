<?php

class  ZetaPrints_OrderApproval_Model_Quote_Address
  extends Mage_Sales_Model_Quote_Address  {

  public function getAllItems ($incl_approved = false) {
    if (!$this->getQuote()->getIncludeApproved())
      return parent::getAllItems();

    $quoteItems = $this->getQuote()->getAllItemsCollection();
    $addressItems = $this->getItemsCollection();

    $items = array();

    if ($this->getQuote()->getIsMultiShipping() && $addressItems->count() > 0)
      foreach ($addressItems as $aItem) {
        if ($aItem->isDeleted() || !$this->_filterNominal($aItem))
          continue;

        if (!$aItem->getQuoteItemImported()) {
          $qItem = $this->getQuote()->getItemById($aItem->getQuoteItemId(), true);

          if ($qItem)
            $aItem->importQuoteItem($qItem);
        }

        $items[] = $aItem;
      }
    else {
      $isQuoteVirtual = $this->getQuote()->isVirtual();

      foreach ($quoteItems as $qItem) {
        if ($qItem->isDeleted() || !$this->_filterNominal($qItem))
          continue;

        //For virtual quote we assign all items to billing address
        if ($isQuoteVirtual) {
          if ($this->getAddressType() == self::TYPE_BILLING)
            $items[] = $qItem;
        } else
          if ($this->getAddressType() == self::TYPE_SHIPPING)
            $items[] = $qItem;
      }
    }

    return $items;
  }
}
