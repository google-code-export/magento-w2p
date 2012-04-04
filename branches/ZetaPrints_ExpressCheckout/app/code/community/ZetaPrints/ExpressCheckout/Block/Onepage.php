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
 * Onepage checkout block
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_ExpressCheckout
 * @author     Anatoly A. Kazantsev <anatoly@zetaprints.com>
 */
class ZetaPrints_ExpressCheckout_Block_Onepage
  extends Mage_Checkout_Block_Onepage {

  public function getActiveStep () {
    $step = 'billing';

    if (Mage::helper('expresscheckout')->hasDefaultBillingAddress())
      $step = 'shipping';

    if ($this->getQuote()->isVirtual()) {
      $step = 'review';

      if (Mage::helper('expresscheckout')->hasPaymentMethods())
        $step = 'payment';
    }

    return $this->isCustomerLoggedIn() ? $step : 'login';
  }
}
