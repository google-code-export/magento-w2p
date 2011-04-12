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

    $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($prid);
    $reqOptions = array ();
    foreach ($product->getOptions() as $option) {                  // loop all product options
      if ($option->getIsRequire() && $optId != $option->getId()) { // if the option is not the upload one,
        $reqOptions[] = $option;                                   // and is required, make sure it will not
        $option->setIsRequire(0);                                  // prevent processing our upload
      }
    }
    /* @var $product Mage_Catalog_Model_Product */
    $buyRequest = new Varien_Object(array ('qty' => 0,  // try not to add product to cart yet
                                          'product' => $product->getId(),
    ));
    try {
      /** @var $type Mage_Catalog_Model_Product_Type_Abstract */
      $type = $product->getTypeInstance(true);
      if(method_exists($type, 'prepareForCartAdvanced')){
        $result = $type->prepareForCartAdvanced($buyRequest, $product, Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_LITE);
      }else{
        $result = $type->prepareForCart($buyRequest, $product);
      }
    } catch (Exception $e) {
      $response->setBody(($this->_jsonError($e->getMessage())));
      return;
    }
    /*
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
    $return['delete_url'] = Mage::getUrl('attachments/index/delete', array('id' => $return['attachment_id']));

    $response->setBody($this->_jsonEncode($return));
  }

  public function deleteAction()
  {
    $this->setFlag('', 'no-renderLayout', TRUE);
    $id = $this->getRequest()->getParam('id');

    $attachments = Mage::getModel('attachments/attachments');
    /*@var $attachments ZetaPrints_Attachments_Model_Attachments */
    $attachments->load($id);
    if($attachments->getId()){
      try{
        $attachments->detachFromSession();
      }catch(Exception $e){
        $this->getResponse()->setHttpResponseCode(403)->setBody('File not found.');
        return;
      }
    }

    $this->getResponse()->setBody('File deleted.')->setHttpResponseCode(200);
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
