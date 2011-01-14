<?php
/**
 * @author      Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Attachments helper
 *
 * Responsible for rendering file upload widgets
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
                throw new Exception(__CLASS__
                                     . ' should be used only on product page.');
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
        $maxLimit = ini_get('upload_max_filesize');
?>
<div class="zp-upload" id="zp-file-upload-<?php echo $id;?>">
    <input type="hidden" name="attachment_hash[<?php echo $id;?>]"
           value="<?php echo $this->_getHash($prId, $id);?>" />
    <div class="attachments" style="display:none;">
        <h4><?php echo $this->__('Files attached:');?></h4>
        <div id="zp-attachments-list-<?php echo $id;?>"></div>
    </div>
    <input type="file" id="option_<?php echo $id;?>_file"
        name="options_<?php echo $id;?>_file"
        class="product-custom-option
               <?php echo $option->getIsRequire() ? ' required-entry' : '' ?>"
        onchange="opConfig.reloadPrice()" />
</div>
<?php if($maxLimit):?>
<div class="upload-max">
  <?php echo $this->__('Max file size: %dMB. You can upload multiple files.',
                       $maxLimit); ?>
</div>
<?php endif;?>
    <script type="text/javascript">
        var fileUpld = $('zp-btn-upload-<?php echo $id;?>');
        var the_action = '<?php echo $action;?>';
        var the_form = 'product_addtocart_form';
        var the_spinner = '<?php echo $spinner;?>'
        var attachment<?php echo $id;?> = new attachments(<?php echo $id;?>,
                                                          the_action,
                                                          the_form);

        attachment<?php echo $id;?>.setOptions({spinner: the_spinner});
        Event.observe(window, 'load', function(e){
            attachment<?php echo $id;?>.addFirstUpload();
            var timer;

            // handle positioning on resize
            Event.observe(window, 'resize', function(e){
              if(timer){
                clearTimeout(timer);
              }
              timer = setTimeout(function(){
                var contClass = '.' + this.settings.containerDivClass;
                var containers = $$(contClass);
                containers.each(function(c){
                  $(c).fire(this.settings.updateListEvent);
                }.bind(this));
              }.bind(this), 100);
            }.bindAsEventListener(attachment<?php echo $id;?>));
        });

    </script>
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

    public function getUseAjax(Mage_Catalog_Model_Product $product)
    {
        if($this->_useAjax === null) {
            $_useAjax = $product
                  ->getData(ZetaPrints_Attachments_Model_Attachments::ATT_CODE);

            if($_useAjax) {
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
