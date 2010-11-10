<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @author Petar Dzhambazov
 */
class ZetaPrints_Attachments_Model_Events_Observer
{

  /**
   * Add uploaded files to order
   *
   * Since we are going to handle file upload asynchronously
   * we need a way to attach files and orders.
   */
  public function addAttachemntsToOrder($observer)
  {
    $quote_item = $observer->getEvent()->getQuoteItem();
    /*@var $quote_item Mage_Sales_Model_Quote_Item */
    $product_id = $quote_item->getProductId();
    $product = $quote_item->getProduct();

    $attachments = array ();
    foreach (Mage::getSingleton('core/session')->getData() as $key => $value) {
      if (strpos($key, ZetaPrints_Attachments_Model_Attachments::ATT_SESS) !== false) { // this is attachment data
        if ($value[ZetaPrints_Attachments_Model_Attachments::PR_ID] == $product_id) { // and is for current product
          $attachments[] = $value;
          Mage::getSingleton('core/session')->unsetData($key);
        }
      }
    }

    $orderAtt = array ();
    foreach ($attachments as $data) {
      $atCollection = Mage::getModel('attachments/attachments')->getAttachmentCollection($data); // get all uploads for give option
      foreach ($atCollection as $att) { // collect all uploads in common array
        $orderAttValue = unserialize($att->getAttachmentValue());
        $orderAttValue['attachment_id'] = $att->getId();
        $orderAtt[$att->getProductId()][$att->getOptionId()][] = $orderAttValue;
      }
    }

    foreach ($orderAtt as $prid => $options) {
      $optKey = 'option_';
      if ($product_id == $prid) {
        foreach ($options as $optId => $value) {
          $option['code'] = $optKey . $optId;
          $option['product_id'] = $prid;
          $option['value'] = serialize($value);
          $product->addCustomOption($optId, $option['value']);
          $quote_item->addOption($option);
          $options = $this->_getItemOptionIds($quote_item);
          if(!isset($options[$optId])){
            $options[] = $optId;
            $this->_setItemOptionIds($quote_item, $options);
          }
        }
      }
    }
  }

  protected function _getItemOptionIds(Mage_Sales_Model_Quote_Item $item)
  {
    $options = array ();
    if ($optionIds = $item->getOptionByCode('option_ids')) {
      $options = explode(',', $optionIds->getValue());
    }
    return $options;
  }

  protected function _setItemOptionIds(Mage_Sales_Model_Quote_Item $item, array $ids)
  {
    $optionIds = $item->getOptionByCode('option_ids');
    if(null == $optionIds){
      $opt = array('code' => 'option_ids', 'product_id' => $item->getProductId(), 'value' => implode(',', $ids));
      $item->addOption($opt);
      $product = $item->getProduct();
      if(!$product->getCustomOption('option_ids')){
        $product->addCustomOption('option_ids', implode(',', $ids));
      }
    }else{
      $optionIds->setValue(implode(',', $ids));
    }
  }

  /**
   * Store attachment keys into session
   */
  public function storeAttachments($observer)
  {
    $request = $observer->getEvent()->getControllerAction()->getRequest();

    $prid = $request->getParam('product'); // get product id
    $hash = $request->getParam('attachment_hash');
    $files = $request->getParam('attached_files');
    // to locate correct attachments we need all 3 keys
    if (isset($prid, $hash)) {
      $sess = Mage::getSingleton('core/session');
      $sess->setData('attached_files', $files);
      foreach ($hash as $opt_id => $hash_value) {
        $key = ZetaPrints_Attachments_Model_Attachments::ATT_SESS . '_' . $hash_value; // make unique option key
        $sess->setData($key, array ( // save it in session
            ZetaPrints_Attachments_Model_Attachments::PR_ID => $prid,
            ZetaPrints_Attachments_Model_Attachments::OPT_ID => $opt_id,
            ZetaPrints_Attachments_Model_Attachments::ATT_HASH => $hash_value
        ));
      }
    }
  }

  /**
   * Handle case of cancelled orders
   *
   * In case that order has been cancelled or discarded
   * for any reason, we could make sure that we delete all
   * attached files. Some design files can get quite large
   * so having housekeeping function like this might be good
   * idea.
   */
  public function deleteAttachemnts($observer)
  {
    $order = $observer->getEvent()->getDataObject();
    // for now we deal deleting manually via controller action
    return;
  }
}

