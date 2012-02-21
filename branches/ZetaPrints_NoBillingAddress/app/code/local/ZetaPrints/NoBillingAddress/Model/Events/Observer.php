<?php

class ZetaPrints_NoBillingAddress_Model_Events_Observer {

  public function saveBillingAction ($observer) {
    $controller = $observer->getEvent()->getControllerAction();

    $result = Mage::helper('core')
                ->jsonDecode($controller->getResponse()->getBody());

    if (isset($result['error']))
      return;

    if (!$controller->getOnepage()->getQuote()->isVirtual())
      return;

    if (Mage::helper('nobillingaddress')->hasPaymentMethods())
      return;

    $controller->getOnepage()->savePayment(array('method' => 'free'));

    $controller->loadLayout('checkout_onepage_review');

    $result['goto_section'] = 'review';
    $result['update_section'] = array('name' => 'review',
                                      'html' => $controller
                                                  ->getLayout()
                                                  ->getBlock('root')
                                                  ->toHtml() );

    $controller
      ->getResponse()
      ->setBody(Mage::helper('core')->jsonEncode($result));
  }
}
?>
