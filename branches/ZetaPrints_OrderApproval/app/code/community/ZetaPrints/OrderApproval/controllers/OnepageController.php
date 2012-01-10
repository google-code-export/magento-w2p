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

      //If customer was loaded successfully and has approver field
      //and the field is not empty then...
      if ($customer->getId() && $customer->hasApprover()
          && $approver_id = $customer->getApprover()) {

        $approver = Mage::getModel('customer/customer')->load($approver_id);

        if ($approver->getId()) {
          $quote = $this->getOnepage()->getQuote();

          $items_to_approve = array();

          //Filter unapproved items which were not mentioned in previous e-mails
          //For every item in the quote...
          foreach ($quote->getAllItemsCollection() as $item)
            //... check that it is not approved alred then...
            if (!$item->getApproved()) {
              //... get info options model
              $option_model = $item->getOptionByCode('info_buyRequest');

              //Get option values from the model
              $options = unserialize($option_model->getValue());

              //Check if zetaprints-approval-email-was-sent option exists and
              //has true value then...
              if (isset($options['zetaprints-approval-email-was-sent'])
                  && $options['zetaprints-approval-email-was-sent'] == true)
                //... pass the item.
                continue;

              //Add unapproved item to the list for further processing
              $items_to_approve[] = $item;
            }

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
                //for the item
                Mage::getModel('sales/quote_item_option')
                  ->setData(array(
                              'code' => 'additional_options',
                              'value' => serialize(
                                array('approval_status' => $option) )) )
                  ->setItem($item)
                  ->save();
              }
            }

            $email_template  = Mage::getModel('core/email_template');

            $approver_fullname = "{$approver->getFirstname()} {$approver->getLastname()}";
            $cart_url = Mage::getUrl('orderapproval/customercart/edit',
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

            //If e-mail was sent sucessfully then...
            if ($email_template->getSentSuccess()) {
              //.. for every item from the list...
              foreach ($items_to_approve as $item) {
                //... get info options model
                $option_model = $item->getOptionByCode('info_buyRequest');

                //Get option values from the model
                $options = unserialize($option_model->getValue());

                //Set zetaprints-approval-email-was-sent option to true
                $options['zetaprints-approval-email-was-sent'] = true;

                //Save options model
                $option_model->setValue(serialize($options))->save();
              }

              //Show notice for customer about sending request to approver
              Mage::getSingleton('checkout/session')
                ->addNotice($this->__('Approval request sent to')
                                                . ' ' . $approver->getEmail() );

              //If there're both approved and not approved items in the cart
              //then...
              if (count($quote->getItemsCollection())
                  && $unapproved_items_number =
                      count($quote->getAllItemsCollection())
                        - count($quote->getItemsCollection()))
                //...display notice about number of unapproved items
                //in customer's cart
                Mage::getSingleton('checkout/session')->addNotice(
                    $unapproved_items_number . $this
                    ->__(' unapproved item(s) remain in your shopping cart.') );

              //If there's only unapproved items in the cart then...
              if (count($quote->getAllItemsCollection())
                                                  == count($items_to_approve)) {
                //... redirect to shopping cart page
                $this->_redirect('checkout/cart');

                return;
              }
            }
          } else
            //else if there's no items to approve without sent emails and
            //shopping cart is empty then...
            if (!count($quote->getItemsCollection()))
              //... show notice for customer
              Mage::getSingleton('checkout/session')
                ->addNotice($this
                       ->__('Please, wait for your purchase to be approved.') );
            else
              // else if there's unapproved items then...
              if ($unapproved_items_number =
                    count($quote->getAllItemsCollection())
                      - count($quote->getItemsCollection()))
                //...display notice about number of unapproved items
                //in customer's cart
                Mage::getSingleton('checkout/session')
                  ->addNotice($unapproved_items_number . $this
                    ->__(' unapproved item(s) remain in your shopping cart.') );
        }
      } else {
        //... else mark all items as approved and remove approval statuses
        //from them

        //Get customer's quote
        $quote = $this->getOnepage()->getQuote();

        //For every item from the quote check...
        foreach ($quote->getAllItemsCollection() as $item)
          //... if it's not approved then ...
          if (!$item->getApproved()) {
            //... if it has additional options...
            if ($option_model = $item->getOptionByCode('additional_options')) {
              //... get its value
              $options = unserialize($option_model->getValue());

              //Remove approval status
              unset($options['approval_status']);

              //and update value of additional options
              $option_model->setValue(serialize($options));
            }

            //Mark the item as approved
            $item->setApproved(true);
          }

        //Save items from the quote
        $quote->getAllItemsCollection()->save();
      }
    }

    parent::indexAction();
  }
}

?>
