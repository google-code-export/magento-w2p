<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    ZetaPrints
 * @package     ZetaPrints_NoBillingAddress
 * @copyright   Copyright (c) 2011 ZetaPrints Ltd. http://www.zetaprints.com/
 * @author      Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 * @attribution Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Quote submit service model
 */
class ZetaPrints_NoBillingAddress_Model_Service_Quote
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
