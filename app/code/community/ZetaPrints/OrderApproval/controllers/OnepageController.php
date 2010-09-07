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
            foreach ($items_to_approve as $item) {
              //Declare option for item
              $option = array(
                'label' => $this->__('Order approval status:'),
                'value' => $this->__('Pending approval') );

              //Check if additional options exist...
              if ($option_model = $item->getOptionByCode('additional_options')) {
                //... then get its value
                $options = unserialize($option_model->getValue());

                //Check if approval status option doesn't exist
                if (!isset($options['approval_status'])) {
                  //... then add approval status option to additional options
                  $options['approval_status'] = $option;

                  //and save additional options
                  $option_model->setValue(serialize($options))->save();
                }
              } else {
                //... else create additional options with approval status option
                //in the item
                $item->addOption(array(
                        'code' => 'additional_options',
                        'value' => serialize(
                          array('approval_status' => $option) )) )
                  ->save();
              }
            }

            $email_template  = Mage::getModel('core/email_template');

            $approver_fullname = "{$approver->getFirstname()} {$approver->getLastname()}";
            $cart_url = Mage::getUrl('checkout/cart/edit',
                                      array('customer' => $customer->getId()));

            $template = Mage::getStoreConfig(self::XML_PATH_NEW_ITEMS_TEMPLATE);


            $email_template->sendTransactional(
              $template,
              'sales',
              $approver->getEmail(),
              $approver_fullname,
              array(
                'number_of_items' => count($items_to_approve),
                'approver_fullname' => $approver_fullname,
                'customers_shopping_cart_url' => $cart_url ));
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
