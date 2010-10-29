<?php
class ZetaPrints_Attachment_IndexController
  extends Mage_Core_Controller_Front_Action
{
  protected $errorSpan = '<span class="error">%s</span>';

  public function indexAction()
  {

    /*
        * Load an object by id
        * Request looking like:
        * http://site.com/attachment?id=15
        *  or
        * http://site.com/attachment/id/15
        */
    /*
        $attachment_id = $this->getRequest()->getParam('id');

        if($attachment_id != null && $attachment_id != '')	{
            $attachment = Mage::getModel('attachment/attachment')->load($attachment_id)->getData();
        } else {
            $attachment = null;
        }
        */

    /*
        * If no param we load a the last created item
        */
    /*
        if($attachment == null) {
            $resource = Mage::getSingleton('core/resource');
            $read= $resource->getConnection('core_read');
            $attachmentTable = $resource->getTableName('attachment');

            $select = $read->select()
            ->from($attachmentTable,array('attachment_id','title','content','status'))
            ->where('status',1)
            ->order('created_time DESC') ;

            $attachment = $read->fetchRow($select);
        }
        Mage::register('attachment', $attachment);
        */

    $this->loadLayout();
    $this->renderLayout();
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
    }

    $hash = $hash[$optId];

    $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($prid);
    /*$var Mage_Catalog_Model_Product $product*/
    $buyRequest = new Varien_Object(array ('qty' => 0, // try not to add product to cart yet
                                  'product' => $product->getId()
                                    ));
      try{
        $result = $product->getTypeInstance(true)->prepareForCart($buyRequest, $product);
      }catch(Exception $e){
        $response->setBody(($this->_jsonError($e->getMessage())));
      }
    /**
     * Error message
     */

    if (is_string($result)) {
      $response->setBody(($this->_jsonError($result)));
    }
    $value = $result[0]->getCustomOption('option_' . $optId)->getValue();

    $cart = Mage::getSingleton('checkout/cart');
    /*@var Mage_Checkout_Model_Cart $cart*/
    $quote = $cart->getQuote();
    /*@var Mage_Sales_Model_Quote $quote */
    $attachment = Mage::getModel('attachment/attachment');

    $data = array(
      ZetaPrints_Attachment_Model_Attachment::PR_ID => $prid,
      ZetaPrints_Attachment_Model_Attachment::OPT_ID => $optId,
      ZetaPrints_Attachment_Model_Attachment::ATT_HASH => $hash,
      ZetaPrints_Attachment_Model_Attachment::ATT_VALUE => $value
    );
    /* @var $attachment ZetaPrints_Attachment_Model_Attachment */
    $attachment->addAtachment($data);

    $return = unserialize($value);
    $return['attachment_id'] = $attachment->getId();
    $response->setBody($this->_jsonEncode($return));
  }

  /**
   * Encode error message as json string
   * @param string $msg - message to send
   * @param string $template - format template that can be used with sprintf
   */
  protected function _jsonError($msg, $template = null)
  {
    if(null == $template){
      $template = $this->errorSpan;
    }
    return $this->_jsonEncode(array('title' => sprintf($template, $msg)));
  }

  protected function _jsonEncode($valueToEncode, $cycleCheck = false, $options = array()){
    return Mage::helper('Core')->jsonEncode($valueToEncode, $cycleCheck = false, $options = array());
  }
}
