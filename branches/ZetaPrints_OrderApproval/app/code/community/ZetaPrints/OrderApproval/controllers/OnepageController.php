<?php

require_once 'Mage/Checkout/controllers/OnepageController.php';

class ZetaPrints_OrderApproval_OnepageController
  extends Mage_Checkout_OnepageController {

  const XML_PATH_NEW_ITEMS_TEMPLATE
                                = 'sales_email/orderapproval/new_items_template';

  public function indexAction () {
    $customer_session = Mage::getSingleton('customer/session');

    if ($customer_session->isLoggedIn()) {
      $customer = $customer_session->getCustomer();

      if ($customer->getId() && $customer->hasApprover()
          && $approver_id = $customer->getApprover()) {

        $approver = Mage::getModel('customer/customer')->load($approver_id);

        if ($approver->getId()) {
          $quote = $this->getOnepage()->getQuote();

          $items_to_approve = array();

          foreach ($quote->getAllItemsCollection() as $item)
            if (!$item->getApproved())
              $items_to_approve[] = $item;

          if (count($items_to_approve)) {
            $email_template  = Mage::getModel('core/email_template');

            $approver_fullname = "{$approver->getFirstname()} {$approver->getLastname()}";

            $template = Mage::getStoreConfig(self::XML_PATH_NEW_ITEMS_TEMPLATE);

            $email_template->sendTransactional(
              $template,
              'sales',
              $approver->getEmail(),
              $approver_fullname,
              array(
                'number_of_items' => count($items_to_approve),
                'approver_fullname' => $approver_fullname ));
          }

          if (count($quote->getAllItemsCollection()) != 0
              && count($quote->getAllItemsCollection())
                                                  == count($items_to_approve)) {
            Mage::getSingleton('checkout/session')
              ->addNotice($this
                            ->__('All items in the cart need to be approved'));

            $this->_redirect('checkout/cart');

            return;
          }
        }
      }
    }

    parent::indexAction();
  }
}

?>
