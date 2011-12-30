<?php

class ZetaPrints_OrderApproval_Model_Cart extends Mage_Checkout_Model_Cart {

  public function getItems () {
    if (!$this->getQuote()->getId())
      return array();

    return $this->getQuote()->getAllItemsCollection();
  }

  public function getQuoteProductIds () {
    $products = $this->getData('product_ids');

    if (is_null($products)) {
      $products = array();

      foreach ($this->getQuote()->getAllItems(true) as $item)
        $products[$item->getProductId()] = $item->getProductId();

      $this->setData('product_ids', $products);
    }

    return $products;
  }

  public function init () {
    $this->getQuote()->setCheckoutMethod('');

    //If user try do checkout, reset shipping and payment data
    if ($this->getCheckoutSession()->getCheckoutState()
          !== Mage_Checkout_Model_Session::CHECKOUT_STATE_BEGIN) {

      $this->getQuote()
        ->removeAllAddresses()
        ->removePayment();

      $this->getCheckoutSession()->resetCheckout();
    }

    if (!$this->getQuote()->hasItems(true))
      $this->getQuote()->getShippingAddress()
        ->setCollectShippingRates(false)
        ->removeAllShippingRates();

    return $this;
  }

  public function updateItems ($data) {
    Mage::dispatchEvent('checkout_cart_update_items_before',
                                          array('cart'=>$this, 'info'=>$data));

    /* @var $messageFactory Mage_Core_Model_Message */
    $messageFactory = Mage::getSingleton('core/message');
    $session = $this->getCheckoutSession();
    $qtyRecalculatedFlag = false;

    foreach ($data as $itemId => $itemInfo) {
      $item = $this->getQuote()->getItemById($itemId, true);

      if (!$item)
        continue;

      if (!empty($itemInfo['remove']) || (isset($itemInfo['qty'])
          && $itemInfo['qty']=='0')) {
        $this->removeItem($itemId);
        continue;
      }

      $qty = isset($itemInfo['qty']) ? (float) $itemInfo['qty'] : false;

      if ($qty > 0) {
        $item->setQty($qty);

        if ($item->getHasError())
          Mage::throwException($item->getMessage());

        if (isset($itemInfo['before_suggest_qty'])
            && ($itemInfo['before_suggest_qty'] != $qty)) {
          $qtyRecalculatedFlag = true;
          $message = $messageFactory->notice(
                               Mage::helper('checkout')
                                 ->__('Quantity was recalculated from %d to %d',
                                      $itemInfo['before_suggest_qty'],
                                      $qty) );

          $session->addQuoteItemMessage($item->getId(), $message);
        }
      }
    }

    if ($qtyRecalculatedFlag)
      $session->addNotice(
        Mage::helper('checkout')
          ->__('Some products quantities were recalculated because of quantity increment mismatch') );

    Mage::dispatchEvent('checkout_cart_update_items_after',
                                       array('cart' => $this, 'info' => $data));

    return $this;
  }

  public function removeItem ($itemId) {
    $this->getQuote()->removeItem($itemId, true);

    return $this;
  }

  public function truncate () {
    foreach ($this->getQuote()->getAllItemsCollection() as $item)
      $item->isDeleted(true);
  }

  public function getProductIds () {
    $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();

    if (null === $this->_productIds) {
      $this->_productIds = array();

      if ($this->getSummaryQty() > 0)
        foreach ($this->getQuote()->getAllItems(true) as $item)
          $this->_productIds[] = $item->getProductId();

      $this->_productIds = array_unique($this->_productIds);
    }

    return $this->_productIds;
  }

  /**
    * Returns suggested quantities for items.
    * Can be used to automatically fix user entered quantities before updating cart
    * so that cart contains valid qty values
    *
    * $data is an array of ($quoteItemId => (item info array with 'qty' key), ...)
    *
    * @param   array $data
    * @return  array
    */
  public function suggestItemsQty ($data) {
    foreach ($data as $itemId => $itemInfo) {
      if (!isset($itemInfo['qty']))
        continue;

      $qty = (float) $itemInfo['qty'];

      if ($qty <= 0)
        continue;

      $quoteItem = $this->getQuote()->getItemById($itemId, true);

      if (!$quoteItem)
        continue;

      $product = $quoteItem->getProduct();

      if (!$product)
        continue;

      /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
      $stockItem = $product->getStockItem();

      if (!$stockItem)
         continue;

      $data[$itemId]['before_suggest_qty'] = $qty;
      $data[$itemId]['qty'] = $stockItem->suggestQty($qty);
    }

    return $data;
  }

  public function updateItem ($itemId, $requestInfo = null,
                              $updatingParams = null) {
    try {
      $item = $this->getQuote()->getItemById($itemId, true);

      if (!$item)
        Mage::throwException(Mage::helper('checkout')
                                            ->__('Quote item does not exist.'));

      $productId = $item->getProduct()->getId();
      $product = $this->_getProduct($productId);
      $request = $this->_getProductRequest($requestInfo);

      if ($product->getStockItem()) {
        $minimumQty = $product->getStockItem()->getMinSaleQty();

        // If product was not found in cart and there is set minimal qty for it
        if ($minimumQty && ($minimumQty > 0)
            && ($request->getQty() < $minimumQty)
            && !$this->getQuote()->hasProductId($productId))
          $request->setQty($minimumQty);
      }

      $result = $this
                  ->getQuote()
                  ->updateItem($itemId, $request, $updatingParams);
    } catch (Mage_Core_Exception $e) {
      $this->getCheckoutSession()->setUseNotice(false);

      $result = $e->getMessage();
    }

    //We can get string if updating process had some errors
    if (is_string($result)) {
      if ($this->getCheckoutSession()->getUseNotice() === null)
        $this->getCheckoutSession()->setUseNotice(true);

      Mage::throwException($result);
    }

    Mage::dispatchEvent('checkout_cart_product_update_after',
                        array('quote_item' => $result, 'product' => $product));

    $this->getCheckoutSession()->setLastAddedProductId($productId);

    return $result;
  }
}
