<?php
class ZetaPrints_Attachment_IndexController extends Mage_Core_Controller_Front_Action
{

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
        $request = $this->getRequest()->getPost(); // get all form values;
        $prid = isset($request['product_id']) ? $request['product_id'] : null; // get product id
        $optId = isset($request['option_id']) ? $request['option_id'] : null;
        if (null === $prid) { // no product id - can't load custom options, return
            return null;
        }

        $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()
                    ->getStore()
                    ->getId())
                    ->load($prid);
        /*$var Mage_Catalog_Model_Product $product*/
        $buyRequest = new Varien_Object(array(
            'qty' => 0, // try not to add
            'product' => $product->getId()
        ));
        $result = $product->getTypeInstance(true)
                          ->prepareForCart($buyRequest, $product);
        /**
         * Error message
         */

        if (is_string($result)) {
            die('<script type="text/javascript">alert("' . $result . '")</script>');
        }
        $value = $result[0]->getCustomOption('option_'.$optId)->getValue();

        $option = $product->getOptionById($optId);

        $cart = Mage::getSingleton('checkout/cart');
        /*@var Mage_Checkout_Model_Cart $cart*/
        $quote = $cart->getQuote();
        /*@var Mage_Sales_Model_Quote $quote */
?>
        <script type="text/javascript">
        var result = <?php echo Zend_Json::encode(unserialize($value));?>;
        </script>
<?php
        echo '<div id="result">', Zend_Json::encode(unserialize($value)), '</div>';
        die('<script type="text/javascript">alert("Done")</script>');
    }

    protected function _prepareOptionsForCart(Mage_Catalog_Model_Product $product)
    {
        $buyRequest = new Varien_Object(array(
            'qty' => 1,
            'product' => $product->getId()
        ));

        $transport = new StdClass();
        $transport->options = array();
        foreach ($product->getOptions() as $_option) {
            /* @var $_option Mage_Catalog_Model_Product_Option */
            $group = $_option->groupFactory($_option->getType())
                             ->setOption($_option)
                             ->setProduct($this->getProduct($product))
                             ->setRequest($buyRequest)
                             ->validateUserValue($buyRequest->getOptions());

            $preparedValue = $group->prepareForCart();
            if ($preparedValue !== null) {
                $transport->options[$_option->getId()] = $preparedValue;
            }
        }
        Mage::dispatchEvent('catalog_product_type_prepare_cart_options', array(
            'transport' => $transport,
            'buy_request' => $buyRequest,
            'product' => $product
        ));
        return $transport->options;
    }
}
