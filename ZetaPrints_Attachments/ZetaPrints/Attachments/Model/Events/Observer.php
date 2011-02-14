<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ZetaPrints_Attachments_Model_Events_Observer
{

  /**
   * Add uploaded files to order
   *
   * Since we are going to handle file upload asynchronously
   * we need a way to attach files and orders.
   * @event checkout_cart_product_add_after
   * @param Varien_Event_Observer $observer
   */
  public function addAttachemntsToQuote($observer)
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

    $optKey = 'option_';
    foreach ($orderAtt as $prid => $options) {
      if ($product_id == $prid) {
        foreach ($options as $optId => $value) {
          $option['code'] = $optKey . $optId;
          $option['product_id'] = $prid;
          $option['value'] = serialize($value);
          $quote_item->addOption($option);
          $o = $quote_item->getOptionByCode($option['code']);
          $product->addCustomOption($optId, $option['value']);
          $optionIDs = $this->_getItemOptionIds($quote_item);
          if(!in_array($optId, $optionIDs)){
            $optionIDs[] = $optId;
            $this->_setItemOptionIds($quote_item, $optionIDs);
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
   * @event controller_action_predispatch_checkout_cart_add
   * @param Varien_Event_Observer $observer
   */
  public function storeAttachments($observer)
  {
    $request = $observer->getEvent()->getControllerAction()->getRequest();

    $prid = $request->getParam('product'); // get product id
    $hash = $request->getParam('attachment_hash');
    // to locate correct attachments we need all 3 keys
    if (isset($prid, $hash)) {
      $sess = Mage::getSingleton('core/session');
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
   * Update order id in attachment table
   * @event sales_order_save_after
   * @param Varien_Event_Observer $observer
   */
  public function updateAttachmentOrder($observer)
  {
    $order = $observer->getEvent()->getDataObject();
    /* @var $order Mage_Sales_Model_Order  */
    $items = $order->getAllItems();
    foreach ($items as $item) {
      $this->_addOrderId($item);
    }
  }

  protected function _addOrderId(Mage_Sales_Model_Order_Item $item)
  {
    $product_options = $item->getProductOptions(); // get product options
    $product = $item->getProduct(); // get product
    $order_id = $item->getOrder()->getRealOrderId(); // get order id as shown to user
    if ($product_options) {
      if (is_array($product_options) && isset($product_options['options'])) {
        foreach ($product_options['options'] as $option) {
          if ($this->_isOptionAttachment($option, $product)) {
            $atColl = Mage::getModel('attachments/attachments')->loadFromOptionArray($option);
            if($atColl && $atColl instanceof ZetaPrints_Attachments_Model_Mysql4_Attachments_Collection){
              foreach ($atColl as $atModel) {
                $atModel->addOrderId($order_id);
              }
            }
          }
        }
      }
    }
  }

  protected function _isOptionAttachment($option, $product)
  {
    if(!isset($option['option_type'])){
      return false;
    }
    $return = $option['option_type'] == ZetaPrints_Attachments_Model_Product_Option::OPTION_TYPE_ATTACHMENT
                                              && Mage::helper('attachments/upload')->getUseAjax($product);
    return $return;
  }
}

