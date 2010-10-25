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
        $action = $this->_getUrl('attachment/index/upload');
        $prId = $this->getProduct()->getId();
?>
<div class="zp-upload" id="zp-file-upload-<?php echo $id;?>">
    <input class="file" type="file" name="options_<?php echo $id;?>_file" id="option_<?php echo $id;?>_file"/>
    <input type="hidden" name="product_id" value="<?php echo $prId;?>"/>
    <input type="hidden" name="option_id" value="<?php echo $id;?>"/>
    <button class="submit inp-upload" disabled="disabled" type="submit" id="zp-btn-upload-<?php echo $id;?>">
        <span id="zp-btn-upload-<?php echo $id;?>-lbl">Upload</span></button>
    <div class="form-info">
        <iframe id="upload-target-<?php echo $id;?>" name="upload-target-<?php echo $id;?>" style="width:0; height:0; border:0;"></iframe>
    </div>
    <script type="text/javascript">
        Event.observe(window, 'load', function(e){
            var fileUpld = $('option_<?php echo $id;?>_file');
            fileUpld.observe('change', function(e){
                if(this.value.length > 0){
                    var submitBtn = $('zp-btn-upload-<?php echo $id;?>');
                    submitBtn.observe('click', function(ev){
                        var uploadLbl = $('zp-btn-upload-<?php echo $id;?>-lbl');
                        uploadLbl.innerHTML = 'Upload new file';
                        var theForm = this.form;
                        var olAction = this.form.action;
                        theForm.action = parseSidUrl('<?php echo $action;?>');
                        theForm.target = 'upload-target-<?php echo $id;?>';
                        theForm.submit();
                        theForm.target = '';
                        theForm.action = olAction;
                        ev.stop();
                        return false;
                    });

                    submitBtn.disabled = '';
                }
            });
        });
    </script>
</div>
<?php
    }

    public function getUploadJs()
    {
        return '';
//        return Mage::helper('core/js')->includeSkinScript('js/multiFileUpload.js');
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
}
