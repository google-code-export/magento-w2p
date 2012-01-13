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

    //Check for M. versions <= 1.5.1
    if (method_exists($cart->getCheckoutSession(), 'addUniqueMessages')) {

      // Compose array of messages to add
      $messages = array();

      foreach ($cart->getQuote()->getMessages() as $message)
        if ($message)
          $messages[] = $message;

      $cart->getCheckoutSession()->addUniqueMessages($messages);
    } else {
      //Code for M <= 1.5.1

      foreach ($cart->getQuote()->getMessages() as $message)
        if ($message)
          $cart->getCheckoutSession()->addMessage($message);
    }

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

  public function configureAction () {
    // Extract item and product to configure
    $id = (int) $this->getRequest()->getParam('id');

    $quoteItem = null;

    $cart = $this->_getCart();

    if ($id)
      $quoteItem = $cart->getQuote()->getItemById($id, true);

    if (!$quoteItem) {
      $this->_getSession()->addError($this->__('Quote item is not found.'));
      $this->_redirect('checkout/cart');

      return;
    }

    try {
      $params = new Varien_Object();

      $params->setCategoryId(false);
      $params->setConfigureMode(true);
      $params->setBuyRequest($quoteItem->getBuyRequest());

      Mage::helper('catalog/product_view')
        ->prepareAndRender($quoteItem->getProduct()->getId(), $this, $params);
    } catch (Exception $e) {
      $this->_getSession()->addError($this->__('Cannot configure product.'));

      Mage::logException($e);

      $this->_goBack();

      return;
    }
  }

  public function updateItemOptionsAction () {
    $cart = $this->_getCart();

    $id = (int) $this->getRequest()->getParam('id');
    $params = $this->getRequest()->getParams();

    if (!isset($params['options']))
      $params['options'] = array();

    try {
      if (isset($params['qty'])) {
        $filter = new Zend_Filter_LocalizedToNormalized(
                  array('locale' => Mage::app()->getLocale()->getLocaleCode()));

        $params['qty'] = $filter->filter($params['qty']);
      }

      $quoteItem = $cart->getQuote()->getItemById($id, true);

      if (!$quoteItem)
        Mage::throwException($this->__('Quote item is not found.'));

      $item = $cart->updateItem($id, new Varien_Object($params));

      if (is_string($item))
        Mage::throwException($item);

      if ($item->getHasError())
        Mage::throwException($item->getMessage());

      $related = $this->getRequest()->getParam('related_product');

      if (!empty($related))
        $cart->addProductsByIds(explode(',', $related));

      $cart->save();

      $this->_getSession()->setCartWasUpdated(true);

      Mage::dispatchEvent('checkout_cart_update_item_complete',
        array('item' => $item,
              'request' => $this->getRequest(),
              'response' => $this->getResponse()) );

      if (!$this->_getSession()->getNoCartRedirect(true)) {
        if (!$cart->getQuote()->getHasError()){
          $message = $this->__('%s was updated in your shopping cart.',
              Mage::helper('core')->htmlEscape($item->getProduct()->getName()));

          $this->_getSession()->addSuccess($message);
        }

        $this->_goBack();
      }
    } catch (Mage_Core_Exception $e) {
      if ($this->_getSession()->getUseNotice(true))
        $this->_getSession()->addNotice($e->getMessage());
      else {
        $messages = array_unique(explode("\n", $e->getMessage()));

        foreach ($messages as $message)
          $this->_getSession()->addError($message);
      }

      $url = $this->_getSession()->getRedirectUrl(true);

      if ($url)
        $this->getResponse()->setRedirect($url);
      else
        $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
    } catch (Exception $e) {
      $this->_getSession()->addException($e,
                                         $this->__('Cannot update the item.'));

      Mage::logException($e);

      $this->_goBack();
    }

    $this->_redirect('*/*');
  }
}
