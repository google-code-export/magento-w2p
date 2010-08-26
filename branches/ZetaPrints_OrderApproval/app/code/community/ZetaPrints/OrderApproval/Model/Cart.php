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

    //If user try do checkout, reset shipiing and payment data
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


  public function addProduct($product, $info = null) {
    $product = $this->_getProduct($product);
    $request = $this->_getProductRequest($info);

    //Check if current product already exists in cart
    $productId = $product->getId();

    $items = $this->getQuote()->getAllItems(true);

    $quoteProduct = null;

    foreach ($items as $item)
      if ($item->getProductId() == $productId) {
        $quoteProduct = $item;
        break;
      }

    if ($product->getStockItem()) {
      $minimumQty = $product->getStockItem()->getMinSaleQty();

      //If product was not found in cart and there is set minimal qty for it
      if ($minimumQty && $minimumQty > 0 && $request->getQty() < $minimumQty
          && $quoteProduct === null)
        $request->setQty($minimumQty);
    }

    if ($product->getId()) {
      $result = $this->getQuote()->addProduct($product, $request);

      //String we can get if prepare process has error
      if (is_string($result)) {
        $this->getCheckoutSession()->setRedirectUrl($product->getProductUrl());

        if ($this->getCheckoutSession()->getUseNotice() === null)
          $this->getCheckoutSession()->setUseNotice(true);

        Mage::throwException($result);
      }
    } else
      Mage::throwException(Mage::helper('checkout')
        ->__('The product does not exist.'));

    Mage::dispatchEvent('checkout_cart_product_add_after',
                            array('quote_item'=>$result, 'product'=>$product));

    $this->getCheckoutSession()->setLastAddedProductId($product->getId());

    return $this;
  }

  public function updateItems ($data) {
    Mage::dispatchEvent('checkout_cart_update_items_before',
                                          array('cart'=>$this, 'info'=>$data));

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

      if ($qty > 0)
        $item->setQty($qty);
    }

    Mage::dispatchEvent('checkout_cart_update_items_after',
                                          array('cart'=>$this, 'info'=>$data));

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

      if ($this->getSummaryQty()>0)
        foreach ($this->getQuote()->getAllItems(true) as $item)
          $this->_productIds[] = $item->getProductId();

      $this->_productIds = array_unique($this->_productIds);
    }

    return $this->_productIds;
  }
}
