<?php

class ZetaPrints_NoBillingAddress_Helper_Data
  extends Mage_Core_Helper_Abstract {

  protected $_quote;
  protected $_methods;
  protected $_address;
  protected $_customer;

  protected function _getQuote () {
    if (empty($this->_quote))
      $this->_quote = Mage::getSingleton('checkout/session')->getQuote();

    return $this->_quote;
  }

  protected function _isCustomerLoggedIn () {
    return Mage::getSingleton('customer/session')->isLoggedIn();
  }

  protected function _getCustomer () {
    if (empty($this->_customer))
      $this->_customer = Mage::getSingleton('customer/session')->getCustomer();

    return $this->_customer;
  }

  protected function _customerHasAddresses () {
    return count($this->_getCustomer()->getAddresses());
  }

  protected function _canUseMethod ($method) {
    if (!$method->canUseForCountry(
                         $this->_getQuote()->getBillingAddress()->getCountry()))
      return false;

    if (!$method->canUseForCurrency(
                                Mage::app()->getStore()->getBaseCurrencyCode()))
      return false;

    /**
     * Checking for min/max order total for assigned payment method
     */
    $total = $this->_getQuote()->getBaseGrandTotal();
    $minTotal = $method->getConfigData('min_order_total');
    $maxTotal = $method->getConfigData('max_order_total');

    if((!empty($minTotal) && ($total < $minTotal))
       || (!empty($maxTotal) && ($total > $maxTotal)))
      return false;

    return true;
  }

  protected function _getPaymentMethods () {
    if (empty($this->_methods)) {
      $quote = $this->_getQuote();
      $store = $quote ? $quote->getStoreId() : null;
      $methods = Mage::helper('payment')->getStoreMethods($store, $quote);
      $total = $quote->getBaseSubtotal();

      foreach ($methods as $key => $method)
        if (!($this->_canUseMethod($method)
            && ($total != 0 || $method->getCode() == 'free'
                || ($quote->hasRecurringItems()
                    && $method->canManageRecurringProfiles()))))
          unset($methods[$key]);

      $this->_methods = $methods;
    }

    return $this->_methods;
  }

  protected function _getBillingAddress () {
    if (is_null($this->_address))
      if ($this->_isCustomerLoggedIn())
        $this->_address = $this->_getQuote()->getBillingAddress();
      else
        $this->_address = Mage::getModel('sales/quote_address');

    return $this->_address;
  }

  public function getBillingAddressId () {
    $addressId = $this->_getBillingAddress()->getCustomerAddressId();

    if (empty($addressId)
        && $address = $this->_getCustomer()->getPrimaryBillingAddress())
      $addressId = $address->getId();

    return $addressId;
  }

  public function hasPaymentMethods () {
    $methods = $this->_getPaymentMethods();

    return !(count($methods) == 1 && array_pop($methods)->getCode() == 'free');
  }

  public function hasDefaultBillingAddress () {
    return $this->_customerHasAddresses() && $this->getBillingAddressId();
  }
}
