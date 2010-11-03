<?php

/**
 * Description of Upload
 *
 * @author pp
 */
class ZetaPrints_Attachments_Helper_Upload extends
        ZetaPrints_Attachments_Helper_Data
{
    const YES = 'yes';
    const NO = 'no';
    /**
     * Use ajax or normal form
     * @var boolean
     */
    protected $_useAjax = null;
    /**
     * Current product
     * @var Mage_Catalog_Model_Product
     */
    protected $_product;
    /**
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if(!isset($this->_product)) {
            $_product = Mage::registry('product');
            if(!$_product || !$_product instanceof Mage_Catalog_Model_Product) {
                throw new Exception(__CLASS__ . ' should be used only on product page.');
            }

            $this->_product = $_product;
        }
        return $this->_product;
    }

    /**
     *
     * @return string
     */
    public function uploadControl($option)
    {
        $id = $option->getId();
        $prId = $this->getProduct()->getId();
        $action = $this->_getUrl("attachments/index/upload/option_id/$id");
        $spinner = $this->getAjaxLoadImage('opc-ajax-loader.gif');
?>
<div class="zp-upload" id="zp-file-upload-<?php echo $id;?>">
    <input type="hidden" name="attachment_hash[<?php echo $id;?>]" value="<?php echo $this->_getHash($prId, $id);?>" />
    <span id="upload_group_<?php echo $id;?>">
        <button class="submit btn-upload" disabled="disabled" id="zp-btn-upload-<?php echo $id;?>">
            <span>Upload file</span>
        </button>
    </span>
    <div id="attachmentss" style="display:none;">
        <h4><?php echo $this->__('Files attached:');?></h4>
        <div id="attachments-list"></div>
    </div>
    <script type="text/javascript">
        Event.observe(window, 'load', function(e){
            var fileUpld = $('zp-btn-upload-<?php echo $id;?>');
            var the_action = '<?php echo $action;?>';
            var the_form = 'product_addtocart_form';
            var the_spinner = '<?php echo $spinner;?>'
            addAttachment(fileUpld, the_action, the_form, the_spinner);
        });
    </script>
</div>
<?php
    }

    public function getUploadJs($file)
    {
        return Mage::helper('core/js')->includeSkinScript($file);
    }
    public function getUploadJsUrl($file)
    {
        return Mage::helper('core/js')->getJsSkinUrl($file);
    }

    public function getAjaxLoadImage($image)
    {
      $spinnerUrl = Mage::getDesign()->getSkinUrl('images/' . $image, array());
      return $spinnerUrl;
    }

    public function getUseAjax()
    {
        if($this->_useAjax === null) {
            $product = $this->getProduct();
            $_useAjax = $product->getAttributeText(ZetaPrints_Attachments_Model_Attachments::ATT_CODE);
            if(self::YES == strtolower($_useAjax)) {
                $this->_useAjax = true;
            }else {
                $this->_useAjax = false;
            }
        }

        return $this->_useAjax;
    }

    /**
     * Get hash key
     * @param string|int $id
     */
    protected function _getHash($product_id, $option_id)
    {
      $hash = uniqid(__CLASS__, true);
      $hash .= microtime(true);
      $hash .= $product_id . $option_id;
      return md5($hash);
    }
}
