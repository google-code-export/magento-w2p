<?php

require_once 'Mage/Checkout/controllers/CartController.php';

class ZetaPrints_OrderApproval_CartController
  extends Mage_Checkout_CartController {

  public function indexAction () {
    $cart = $this->_getCart();

    $cart->getQuote()->setIncludeApproved(true);

    if ($cart->getQuote()->getAllItemsCount()) {
      $cart->init();
      $cart->save();

      if (!$this->_getQuote()->validateMinimumAmount()) {
        $warning = Mage::getStoreConfig('sales/minimum_order/description');
        $cart->getCheckoutSession()->addNotice($warning);
      }
    }

    foreach ($cart->getQuote()->getMessages() as $message)
      if ($message)
        $cart->getCheckoutSession()->addMessage($message);

    /**
     * if customer enteres shopping cart we should mark quote
     * as modified bc he can has checkout page in another window.
     */
    $this->_getSession()->setCartWasUpdated(true);

    Varien_Profiler::start(__METHOD__ . 'cart_display');

    $this->loadLayout()
      ->_initLayoutMessages('checkout/session')
      ->_initLayoutMessages('catalog/session')
      ->getLayout()->getBlock('head')->setTitle($this->__('Shopping Cart'));

    $this->renderLayout();

    Varien_Profiler::stop(__METHOD__ . 'cart_display');
  }
}
