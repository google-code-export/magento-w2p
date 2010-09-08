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

    //Reset toatal collected flag
    $quote->setTotalsCollectedFlag(false);

    //Recalculate quote total and save the quote.
    $quote->collectTotals()->save();
  }
}

?>
