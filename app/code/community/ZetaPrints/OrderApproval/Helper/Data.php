<?php

class ZetaPrints_OrderApproval_Helper_Data extends Mage_Core_Helper_Abstract {
  const DEFAULT_APPROVER = -1;

  const APPROVED = 1;
  const DECLINED = 2;

  public function getApprover () {
    $customerSession = Mage::getSingleton('customer/session');

    if (!$customerSession->isLoggedIn())
      return false;

    $approver = $customerSession->getCustomer();

    if ($approver->getId() && $approver->getIsApprover())
      return $approver;

    return false;
  }

  public function getCustomerWithApprover ($customerId, $approver) {
    $customer = Mage::getModel('customer/customer')->load($customerId);

    if ($customer->getId() && $customer->hasApprover()
        && $customer->getApprover() == $approver->getId())
      return $customer;

    return false;
  }

  public function hasNotSentItems ($items) {
    //For every item in the collection...
    foreach ($items as $item) {
      //... get info options model
      $optionModel = $item->getOptionByCode('info_buyRequest');

      //Get option values from the model
      $options = unserialize($optionModel->getValue());

      //Check if zetaprints-approval-email-was-sent option doesn't exist or
      //its value is not true
      if (!(isset($options['zetaprints-approval-email-was-sent'])
            && $options['zetaprints-approval-email-was-sent'] == true))
        return true;
    }

    return false;
  }

  public function addNoticeToApprovedItem ($item, $state, $message = null) {
    //Load options for the item
    $optionModels = Mage::getModel('sales/quote_item_option')
      ->getCollection()
      ->addItemFilter($item);

    $optionModel = null;

    //Find additional options
    foreach ($optionModels as $_optionModel)
      if ($_optionModel['code'] == 'additional_options') {
        $optionModel = $_optionModel;

        break;
      }

    //Declare option for item
    $option = array('label' => $this->__('Order approval status:'));

    if ($state == self::APPROVED)
      $option['value'] = $this->__('Approved');
    else
      $option['value'] = $this->__('Declined')
                         . ($message ? ' (' . $message . ')' : '');

    //If additional options exist...
    if ($optionModel) {
      //... then get its value
      $options = unserialize($optionModel->getValue());

      //Update approval status option
      $options['approval_status'] = $option;

      //and save additional options
      $optionModel
        ->setValue(serialize($options))
        ->save();
    } else {
      //... else create additional options with approval status option
      //in the item
      $item->addOption(array(
        'code' => 'additional_options',
        'value' => serialize(
          array('approval_status' => $option) )) );
    }
  }

  public function getNoticeFromItem ($item) {
    $option = $item->getOptionByCode('additional_options');

    if (!$option)
       return '';

    $value = unserialize($option->getValue());

    return isset($value['approval_status'])
             ? $value['approval_status']['value']
               : '';
  }

  public function isWebToPrintInstalled () {
    return Mage::getConfig()->getHelperClassName('webtoprint')
             === 'ZetaPrints_WebToPrint_Helper_Data';
  }
}

?>
