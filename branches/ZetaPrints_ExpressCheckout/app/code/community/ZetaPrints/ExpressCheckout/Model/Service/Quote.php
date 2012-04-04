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
 * Quote submit service model
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_ExpressCheckout
 * @author     Anatoly A. Kazantsev <anatoly@zetaprints.com>
 */
class ZetaPrints_ExpressCheckout_Model_Service_Quote
  extends Mage_Sales_Model_Service_Quote {

  /**
   * Validate quote data before converting to order
   *
   * @return Mage_Sales_Model_Service_Quote
   */
  protected function _validate () {
    $helper = Mage::helper('sales');

    if (!$this->getQuote()->isVirtual()) {
      $address = $this->getQuote()->getShippingAddress();
      $addressValidation = $address->validate();

      if ($addressValidation !== true)
        Mage::throwException(
          $helper->__('Please check shipping address information. %s',
                      implode(' ', $addressValidation)) );

      $method= $address->getShippingMethod();
      $rate  = $address->getShippingRateByCode($method);

      if (!$this->getQuote()->isVirtual() && (!$method || !$rate))
        Mage::throwException($helper->__('Please specify a shipping method.'));
    }

    $addressValidation = $this->getQuote()->getBillingAddress()->validate();

    //Disable checking validation results
    //if ($addressValidation !== true)
    //  Mage::throwException(
    //    $helper->__('Please check billing address information. %s',
    //                implode(' ', $addressValidation)) );

    if (!($this->getQuote()->getPayment()->getMethod()))
      Mage::throwException($helper
                             ->__('Please select a valid payment method.'));

    return $this;
  }
}
