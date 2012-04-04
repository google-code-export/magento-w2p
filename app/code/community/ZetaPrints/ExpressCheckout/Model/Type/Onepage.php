<?php
/**
 * ExpressCheckout
 *
 * Copyright (c) 2011-2012 ZetaPrints Ltd.
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category  ZetaPrints
 * @package   ZetaPrints_ExpressCheckout
 * @copyright Copyright (c) 2011-2012 ZetaPrints Ltd. (http://www.zetaprints.com)
 * @license   http://www.opensource.org/licenses/mit-license.php  The MIT License (MIT)
 */

/**
 * One page checkout processing model
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_ExpressCheckout
 * @author     Anatoly A. Kazantsev <anatoly@zetaprints.com>
 */
class ZetaPrints_ExpressCheckout_Model_Type_Onepage
  extends Mage_Checkout_Model_Type_Onepage {

  /**
    * Save billing address information to quote
    * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
    *
    * @param   array $data
    * @param   int $customerAddressId
    * @return  Mage_Checkout_Model_Type_Onepage
    */
  public function saveBilling($data, $customerAddressId) {
    if (empty($data))
      return array('error' => -1,
                   'message' => $this->_helper->__('Invalid data.'));

    $address = $this->getQuote()->getBillingAddress();

    /* @var $addressForm Mage_Customer_Model_Form */
    $addressForm = Mage::getModel('customer/form');

    $addressForm
      ->setFormCode('customer_address_edit')
      ->setEntityType('customer_address')
      ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

    if (!empty($customerAddressId)) {
      $customerAddress = Mage::getModel('customer/address')
                           ->load($customerAddressId);

      if ($customerAddress->getId()) {
        if ($customerAddress->getCustomerId()
                                          != $this->getQuote()->getCustomerId())
          return array('error' => 1,
                       'message' =>
                          $this->_helper->__('Customer Address is not valid.'));

        $address
          ->importCustomerAddress($customerAddress)
          ->setSaveInAddressBook(0);

        $addressForm->setEntity($address);
        $addressErrors  = $addressForm->validateData($address->getData());

        //Disable checking validation results
        //if ($addressErrors !== true)
        //  return array('error' => 1, 'message' => $addressErrors);
      }
    } else {
      $addressForm->setEntity($address);

      //emulate request object
      $addressData = $addressForm
                       ->extractData($addressForm->prepareRequest($data));
      $addressErrors  = $addressForm->validateData($addressData);

      //Disable checking validation results
      //if ($addressErrors !== true)
      //  return array('error' => 1, 'message' => $addressErrors);

      $addressForm->compactData($addressData);

      //unset billing address attributes which were not shown in form
      foreach ($addressForm->getAttributes() as $attribute)
        if (!isset($data[$attribute->getAttributeCode()]))
          $address->setData($attribute->getAttributeCode(), NULL);

      //Additional form data,
      //not fetched by extractData (as it fetches only attributes)
      $address
        ->setSaveInAddressBook(empty($data['save_in_address_book']) ? 0 : 1);
    }

    //validate billing address
    $address->validate();

    //Disable checking validation results
    //if (($validateRes = $address->validate()) !== true)
    //  return array('error' => 1, 'message' => $validateRes);

    $address->implodeStreetAddress();

    if (true !== ($result = $this->_validateCustomerData($data)))
      return $result;

    if (!$this->getQuote()->getCustomerId()
        && self::METHOD_REGISTER == $this->getQuote()->getCheckoutMethod())
      if ($this->_customerEmailExists($address->getEmail(),
                                      Mage::app()->getWebsite()->getId()))
        return array('error' => 1,
                     'message' => $this->_customerEmailExistsMessage);

    if (!$this->getQuote()->isVirtual()) {

      //Billing address using otions
      $usingCase = isset($data['use_for_shipping'])
                     ? (int)$data['use_for_shipping'] : 0;

      switch($usingCase) {
        case 0:
          $shipping = $this->getQuote()->getShippingAddress();
          $shipping->setSameAsBilling(0);
          break;
        case 1:
          $billing = clone $address;
          $billing->unsAddressId()->unsAddressType();
          $shipping = $this->getQuote()->getShippingAddress();
          $shippingMethod = $shipping->getShippingMethod();

          //don't reset original shipping data,
          //if it was not changed by customer
          foreach ($shipping->getData() as $shippingKey => $shippingValue)
            if (!is_null($shippingValue)
                && !is_null($billing->getData($shippingKey))
                && !isset($data[$shippingKey]))
              $billing->unsetData($shippingKey);

          $shipping
            ->addData($billing->getData())
            ->setSameAsBilling(1)
            ->setSaveInAddressBook(0)
            ->setShippingMethod($shippingMethod)
            ->setCollectShippingRates(true);

          $this->getCheckout()->setStepData('shipping', 'complete', true);

          break;
      }
    }

    $this->getQuote()->collectTotals();
    $this->getQuote()->save();

    if (!$this->getQuote()->isVirtual()
        && $this->getCheckout()->getStepData('shipping', 'complete') == true)
      //Recollect Shipping rates for shipping methods
      $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);

    $this
      ->getCheckout()
      ->setStepData('billing', 'allow', true)
      ->setStepData('billing', 'complete', true)
      ->setStepData('shipping', 'allow', true);

    if (!Mage::helper('expresscheckout')->hasPaymentMethods())
      $this
        ->getCheckout()
        ->setStepData('payment', 'allow', true)
        ->setStepData('payment', 'complete', true)
        ->setStepData('review', 'allow', true);

    return array();
  }
}
