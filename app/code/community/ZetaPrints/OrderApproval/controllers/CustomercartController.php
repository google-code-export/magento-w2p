<?php

class ZetaPrints_OrderApproval_CustomerCartController
  extends Mage_Core_Controller_Front_Action {

  public function allAction () {
    if (!Mage::helper('orderapproval')->getApprover()) {
      $this->_redirect('404');

      return;
    }
    
    $this->loadLayout();

    $this
      ->getLayout()
      ->getBlock('head')
      ->setTitle($this->__('Carts of customers'));

    if ($block = $this->getLayout()->getBlock('customer.account.link.back'))
      $block->setRefererUrl($this->_getRefererUrl());

    $this->renderLayout();
  }

  public function editAction () {
    $helper = Mage::helper('orderapproval');
    if (! $approver = $helper->getApprover()) {
      $this->_redirect('404');

      return;
    }

    if (! $customer_id = $this->getRequest()->getParam('customer')) {
      $this->_redirect('');
      return;
    }

    if (!$helper->getCustomerWithApprover($customer_id, $approver)) {
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
      ->getBlock('order-approval.cart.edit')
      ->setCustomerQuote($quote);

    if ($block = $this->getLayout()->getBlock('customer.account.link.back'))
      $block->setRefererUrl($this->_getRefererUrl());

    $this->renderLayout();
  }

  public function updateApprovalStateAction () {
    $helper = Mage::helper('orderapproval');

    if (!(($item_id = $this->getRequest()->getParam('item'))
          && ($approver = $helper->getApprover()))) {
      $this->_redirect('');
      return;
    }

    $state = (int) $this->getRequest()->getParam('state');

    if ($state != ZetaPrints_OrderApproval_Helper_Data::APPROVED
        && $state != ZetaPrints_OrderApproval_Helper_Data::DECLINED) {

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

    if (!$helper->getCustomerWithApprover($quote->getCustomerId(), $approver)) {
      $this->_redirect('');
      return;
    }

    //Update or add approval status notice to the item
    $helper->addNoticeToApprovedItem($item, $state);

    $item->setQuote($quote)->setApproved($state)->save();

    if ($state == ZetaPrints_OrderApproval_Helper_Data::APPROVED)
      Mage::getSingleton('core/session')
        ->addNotice($this->__('Product was succesfully approved'));
    else
      Mage::getSingleton('core/session')
        ->addNotice($this->__('Product was declined'));

    $this->_redirect('*/*/edit', array('customer' => $quote->getCustomerId()));
  }

  public function massUpdateApprovalStateAction () {
    if (! $item_ids = $this->getRequest()->getParam('items')) {
      $this->_redirect('');
      return;
    }

    $state = (int) $this->getRequest()->getParam('state');

    if ($state != ZetaPrints_OrderApproval_Helper_Data::APPROVED
        && $state != ZetaPrints_OrderApproval_Helper_Data::DECLINED) {

      $this->_redirect('');
      return;
    }

    if (! $approver = Mage::helper('orderapproval')->getApprover()) {
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

        if (!$helper->getCustomerWithApprover($quote->getCustomerId(),
                                                                   $approver)) {
          $this->_redirect('');
          return;
        }
      }

      //Update or add approval status notice to the item
      $helper->addNoticeToApprovedItem($item, $state);

      $item->setQuote($quote)->setApproved($state)->save();
    }

    if ($state == ZetaPrints_OrderApproval_Helper_Data::APPROVED)
      Mage::getSingleton('core/session')
        ->addNotice($this->__('Selected products were succesfully approved'));
    else
      Mage::getSingleton('core/session')
        ->addNotice($this->__('Selected products were declined'));

    $this->_redirect('*/*/edit', array('customer' => $quote->getCustomerId()));
  }
}

?>
