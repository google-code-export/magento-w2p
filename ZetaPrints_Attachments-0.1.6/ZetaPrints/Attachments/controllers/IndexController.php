<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ZetaPrints_Attachments_IndexController extends Mage_Core_Controller_Front_Action
{
  protected $errorSpan = '<span class=\'error\'>%s</span>';

  protected function _construct()
  {
    $this->setFlag('', 'no-renderLayout', TRUE);
  }

  public function uploadAction()
  {
    $this->setFlag('', 'no-renderLayout', TRUE);
    $request = $this->getRequest();
    $prid = $request->getParam('product'); // get product id
    $optId = $request->getParam('option_id');
    $hash = $request->getParam('attachment_hash');

    $response = $this->getResponse();

    if (!isset($prid, $optId, $hash)) { // no product id - can't load custom options, return
      $response->setBody($this->_jsonError('No product id or hash received'));
      return;
    }

    $hash = $hash[$optId];

    $product = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->load($prid);
    $reqOptions = array ();
    foreach ($product->getOptions() as $option) {                  // loop all product options
      if ($option->getIsRequire() && $optId != $option->getId()) { // if the option is not the upload one,
        $reqOptions[] = $option;                                   // and is required, make sure it will not
        $option->setIsRequire(0);                                  // prevent processing our upload
      }
    }
    /*$var Mage_Catalog_Model_Product $product*/
    $buyRequest = new Varien_Object(array ('qty' => 0,  // try not to add product to cart yet
                                          'product' => $product->getId()
    ));
    try {
      $result = $product->getTypeInstance(true)->prepareForCart($buyRequest, $product);
    } catch (Exception $e) {
      $response->setBody(($this->_jsonError($e->getMessage())));
      return;
    }
    /**
     * Error message
     */

    if (is_string($result)) {
      $response->setBody(($this->_jsonError($result)));
      return;
    }
    $value = $result[0]->getCustomOption('option_' . $optId)->getValue();

    $attachments = Mage::getModel('attachments/attachments');

    $data = array (ZetaPrints_Attachments_Model_Attachments::PR_ID => $prid,
                  ZetaPrints_Attachments_Model_Attachments::OPT_ID => $optId,
                  ZetaPrints_Attachments_Model_Attachments::ATT_HASH => $hash,
                  ZetaPrints_Attachments_Model_Attachments::ATT_VALUE => $value
    );
    /* @var $attachments ZetaPrints_Attachments_Model_Attachments */
    $attachments->addAtachment($data);

    $return = unserialize($value);
    $return['attachment_id'] = $attachments->getId();

    $response->setBody($this->_jsonEncode($return));
  }

  public function deleteAction()
  {
    $optionId = $this->getRequest()->getParam('id');
    $fileKey = $this->getRequest()->getParam('key');

    $attachments = Mage::getModel('attachments/attachments');
    $option = Mage::getModel('sales/quote_item_option')->load($optionId);
    $files = unserialize($option->getValue());
    $result = array ();
    foreach ($files as $key => $value) {
      if ($value['secret_key'] == $fileKey) {
        $attachments->deleteFile($value);
        unset($files[$key]);
        $result[] = $value['title'];
      }
    }
    if (empty($files)) {
      //      $product = $option->getProduct();
    //      $option->delete(); // get product and remove option
    //      if($product->getCustomOption('option_ids')){
    //        $ids = explode(',', $product->getCustomOption('option_ids'));
    //        if (in_array($optionId, $ids)) {
    //          unset($ids[array_search($optionId, $ids)]);
    //        }
    //        $prOpt = empty($ids)?null:implode(',', $ids);
    //        $product->addCustomOption('option_ids', $prOpt);
    //      }
    } else {
      $option->setValue(serialize($files))->save();
    }
    if (empty($result)) {
      $result[] = 'No files deleted.';
    }
    $this->getResponse()->setBody($this->_jsonEncode($result));
  }

  /**
   * Encode error message as json string
   * @param string $msg - message to send
   * @param string $template - format template that can be used with sprintf
   */
  protected function _jsonError($msg, $template = null)
  {
    if (null == $template) {
      $template = $this->errorSpan;
    }
    $error = new stdClass();
    $error->error = $msg;
    return $this->_jsonEncode($error);
  }

  protected function _jsonEncode($valueToEncode, $cycleCheck = false, $options = array())
  {
    return Mage::helper('Core')->jsonEncode($valueToEncode, $cycleCheck = false, $options = array ());
  }
}
