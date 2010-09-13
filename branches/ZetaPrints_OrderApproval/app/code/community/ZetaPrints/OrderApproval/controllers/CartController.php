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

      //If shopping cart contains items that were not mentioned in approval
      //e-mails then...
      if ($this->_has_not_sent_items($cart->getQuote()->getAllItemsCollection()))
        //show notice to cautomer
        $cart->getCheckoutSession()->addNotice(
          $this->__('Approval request for all added items will be sent out when you proceed to checkout.') );

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
