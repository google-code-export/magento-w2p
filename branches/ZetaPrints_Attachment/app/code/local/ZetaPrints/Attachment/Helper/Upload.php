<?php

/**
 * Description of Upload
 *
 * @author pp
 */
class ZetaPrints_Attachment_Helper_Upload extends
        ZetaPrints_Attachment_Helper_Data
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
        $action = $this->_getUrl("attachment/index/upload/option_id/$id");
?>
<div class="zp-upload" id="zp-file-upload-<?php echo $id;?>">
    <input type="hidden" name="attachment_hash[<?php echo $id;?>]" value="<?php echo $this->_getHash($prId, $id);?>" />
    <span id="upload_group_<?php echo $id;?>">
<!--        <input class="file" type="file" name="options_<?php echo $id;?>_file" id="option_<?php echo $id;?>_file"/>-->
        <button class="submit btn-upload" disabled="disabled" id="zp-btn-upload-<?php echo $id;?>">
            <span>Upload file</span>
        </button>
    </span>
    <div class="form-info">
<!--        <iframe id="upload-target-<?php echo $id;?>" name="upload-target-<?php echo $id;?>" style="width:0; height:0; border:0;"></iframe>-->
    </div>
    <script type="text/javascript">
        Event.observe(window, 'load', function(e){
            var fileUpld = $('zp-btn-upload-<?php echo $id;?>');
            var the_action = '<?php echo $action;?>';
            var the_form = 'product_addtocart_form';
            addAttachment(fileUpld, the_action, the_form);
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

    public function getUseAjax()
    {
        if($this->_useAjax === null) {
            $product = $this->getProduct();
            $_useAjax = $product->getAttributeText('allow_attachements');
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
      $hash .= $product_id . $id;
      return md5($hash);
    }
}
