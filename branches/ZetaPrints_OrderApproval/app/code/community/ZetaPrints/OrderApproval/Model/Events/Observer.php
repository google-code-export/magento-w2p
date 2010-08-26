<?php

class ZetaPrints_OrderApproval_Model_Events_Observer {

  public function mark_quote_item ($observer) {
    $customer_session = Mage::getSingleton('customer/session');

    if (!$customer_session->isLoggedIn())
      return;

    $customer = $customer_session->getCustomer();

    if (!$customer->getId())
      return;

    if (!($customer->hasApprover() && $customer->getApprover()))
      return;

    $item = $observer->getEvent()->getQuoteItem();

    //$option_model = $item->getOptionByCode('info_buyRequest');
    //$options = unserialize($option_model->getValue());

    //$options['orderapprove-need-approve'] = true;

    //$option_model->setValue(serialize($options));
    $item->setApproved(false)->save();
  }
}

?>
