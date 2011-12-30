<?php

require_once 'Mage/Checkout/controllers/CartController.php';

class ZetaPrints_OrderApproval_CartController
  extends Mage_Checkout_CartController {

  public function _get_approver () {
    $customer_session = Mage::getSingleton('customer/session');

    if (!$customer_session->isLoggedIn())
      return false;

    $approver = $customer_session->getCustomer();

    if ($approver->getId() && $approver->getIsApprover())
      return $approver;

    return false;
  }

  public function _get_customer_with_approver ($customer_id, $approver) {
    $customer = Mage::getModel('customer/customer')->load($customer_id);

    if ($customer->getId() && $customer->hasApprover()
        && $customer->getApprover() == $approver->getId())
      return $customer;

    return false;
  }

  public function _add_notice_to_approved_item ($item) {
    //Load options for the item
    $option_models = Mage::getModel('sales/quote_item_option')
      ->getCollection()
      ->addItemFilter($item);

    $option_model = null;

    //Find additional options
    foreach ($option_models as $_option_model)
      if ($_option_model['code'] == 'additional_options') {
        $option_model = $_option_model;

        break;
      }

    //Declare option for item
    $option = array(
      'label' => $this->__('Order approval status:'),
      'value' => $this->__('Approved') );

    //If additional options exist...
    if ($option_model) {
      //... then get its value
      $options = unserialize($option_model->getValue());

      //Update approval status option
      $options['approval_status'] = $option;

      //and save additional options
      $option_model->setValue(serialize($options))->save();
    } else {
      //... else create additional options with approval status option
      //in the item
      $item->addOption(array(
        'code' => 'additional_options',
        'value' => serialize(
          array('approval_status' => $option) )) );
    }
  }

  public function _has_not_sent_items ($items) {
    //For every item in a list...
    foreach ($items as $item) {
      //... get info options model
      $option_model = $item->getOptionByCode('info_buyRequest');

      //Get option values from the model
      $options = unserialize($option_model->getValue());

      //Check if zetaprints-approval-email-was-sent option doesn't exist or
      //its value is not true
      if (!(isset($options['zetaprints-approval-email-was-sent'])
            && $options['zetaprints-approval-email-was-sent'] == true))
        return true;
    }

    return false;
  }

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

    // Compose array of messages to add
    $messages = array();

    foreach ($cart->getQuote()->getMessages() as $message) {
      if ($message) {
        $messages[] = $message;
      }
    }

    $cart->getCheckoutSession()->addUniqueMessages($messages);

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

  public function editAction () {
    if (! $approver = $this->_get_approver()) {
      $this->_redirect('');
      return;
    }

    if (! $customer_id = $this->getRequest()->getParam('customer')) {
      $this->_redirect('');
      return;
    }

    if (!$this->_get_customer_with_approver($customer_id, $approver)) {
      $this->_redirect('');
      return;
    }

    $quote = Mage::getModel('sales/quote')
              ->setStoreId(Mage::app()->getStore()->getId())
              ->loadByCustomer($customer_id);

    if (!$quote->getId()) {
      $this->_redirect('');

      return;
    }

    $this->loadLayout()
      ->getLayout()
      ->getBlock('cart-edit')
      ->setCustomerQuote($quote);

    $this->renderLayout();
  }

  public function massApproveAction () {
    if (! $item_ids = $this->getRequest()->getParam('items')) {
      $this->_redirect('');
      return;
    }

    if (! $approver = $this->_get_approver()) {
      $this->_redirect('');
      return;
    }

    $item_ids = explode(',', $item_ids);

    // We can not retrieve several quote items without setting quote
    // with its collection
    // Looks like a bug in the M.
    //$items = Mage::getModel('sales/quote_item')->getCollection();
    //$items->getSelect()->where('item_id in (?)', $item_ids);
    //Mage::log(count($items));
    //$this->_redirect('*/*/edit');

    $quote = null;

    foreach ($item_ids as $item_id) {
      $item = Mage::getModel('sales/quote_item')->load($item_id);

      if(!$item->getId())
        continue;

      if (!$quote) {
        $quote = Mage::getModel('sales/quote')->load($item->getQuoteId());

        if (!$quote->getId())
          break;

        if (!$this->_get_customer_with_approver($quote->getCustomerId(),
                                                                  $approver)) {
          $this->_redirect('');
          return;
        }
      }

      //Update or add approval status notice to the item
      $this->_add_notice_to_approved_item($item);

      $item->setQuote($quote)->setApproved(true)->save();
    }

    Mage::getSingleton('core/session')
        ->addNotice($this->__('Selected products were succesfully approved'));

    $this->_redirect('*/*/edit', array('customer' => $quote->getCustomerId()));
  }

  public function approveAction () {
    if (!(($item_id = $this->getRequest()->getParam('item'))
          && ($approver = $this->_get_approver()))) {
      $this->_redirect('');
      return;
    }

    $item = Mage::getModel('sales/quote_item')->load($item_id);

    if(!$item->getId()) {
      $this->_redirect('');
      return;
    }

    $quote = Mage::getModel('sales/quote')->load($item->getQuoteId());

    if (!$quote->getId()) {
      $this->_redirect('');
      return;
    }

    if (!$this->_get_customer_with_approver($quote->getCustomerId(),
                                                                  $approver)) {
      $this->_redirect('');
      return;
    }

    //Update or add approval status notice to the item
    $this->_add_notice_to_approved_item($item);

    $item->setQuote($quote)->setApproved(true)->save();

    Mage::getSingleton('core/session')
        ->addNotice($this->__('Product was succesfully approved'));

    $this->_redirect('*/*/edit', array('customer' => $quote->getCustomerId()));
  }
}
