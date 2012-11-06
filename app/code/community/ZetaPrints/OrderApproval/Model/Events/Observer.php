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

    $item->setApproved(false);
  }

  public function remove_approved_items_from_quote ($observer) {
    //Get quote
    $quote = $observer->getEvent()->getQuote();

    //If the quote is not active then it means that shopping cart contained
    //only approved items and all of them were checked out.
    if (!$quote->getIsActive())
      return;

    //If shopping cart contained both approved and not approved items,
    //then remove all approved items from the cart. After it cart will contain
    //only not approved items for futher processing.

    //For every item from the quote check...
    foreach ($quote->getAllVisibleItems(true) as $item)
      //... if it's approved then...
      if ($item->getApproved())
        //...remove it from the cart
        $quote->removeItem($item->getId(), true);

    //Reset total collected flag
    $quote->setTotalsCollectedFlag(false);

    //Recalculate quote total and save the quote.
    $quote->collectTotals()->save();
  }

  public function check_for_not_sent_items ($observer) {
    //Get customer session
    $customer_session = Mage::getSingleton('customer/session');

    //Check if customer is logged
    if (!$customer_session->isLoggedIn())
      return;

    //Get customer object from the session
    $customer = $customer_session->getCustomer();

    //Check if customer object was loaded already
    if (!$customer->getId())
      return;

    //Check if customer has approver
    if (!($customer->hasApprover() && $customer->getApprover()))
      return;

    $helper = Mage::helper('orderapproval');

    //Get cart object
    $cart = Mage::getSingleton('checkout/cart');

    //If shopping cart contains items that were not sent to approver then ...
    if ($helper->hasNotSentItems($cart->getQuote()->getAllItemsCollection())) {
      $msg = 'Approval request for all added items will be sent out when you '
             . 'proceed to checkout.';

      //... show notice to the customer
      $cart
        ->getCheckoutSession()
        ->addNotice($helper->__($msg));
    }
  }

  public function addCartsMenuItem ($observer) {
    //Get customer session
    $customer_session = Mage::getSingleton('customer/session');

    //Check if customer is logged
    if (!$customer_session->isLoggedIn())
      return;

    //Get customer object from the session
    $customer = $customer_session->getCustomer();

    //Check if customer object was loaded already
    if (!$customer->getId())
      return;

    //Check if customer is approver
    if (!$customer->getIsApprover())
      return;

    //Get current controller
    $controller = Mage::app()
                    ->getFrontController()
                    ->getAction();

    //Add link to the list of carts into navigation block
    $controller
      ->getLayout()
      ->getBlock('customer_account_navigation')
      ->addLink('order-approval', 'order-approval/carts/all',
                                             $controller->__('Order Approval'));
  }
}

?>
