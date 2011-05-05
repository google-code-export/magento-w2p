<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ZetaPrints_Attachments_Model_Product_Option extends Mage_Catalog_Model_Product_Option
{
  const OPTION_TYPE_ATTACHMENT = 'file';
  const OPTION_GROUP_ATTACHMENT   = 'attachments';

  /**
   * (non-PHPdoc)
   * @see Mage_Catalog_Model_Product_Option::getGroupByType()
   */
  public function getGroupByType($type = null)
  {
    if ($type == self::OPTION_TYPE_ATTACHMENT) {
//      $product_id = $this; // this is debug help only

      // currently we haven't applied our custom option type
      // so we try to get what should we use from product
      // if no product can be found we continue with
      // ajax attachments, if product is found and has
      //  use_ajax_upload set to anything but 'Yes'
      // we load standard file upload

      $product = null;
      if($this->getProduct()){
        $product = $this->getProduct();
      }elseif ($this->hasData('product_id')) {
        $product = Mage::getModel('catalog/product')->load($this->getData('product_id'));
      }elseif (Mage::registry('product')) {
        $product = Mage::registry('product');
      }
      if ($product instanceof Mage_Catalog_Model_Product ){
        if(!Mage::helper('attachments/upload')->getUseAjax($product)) {
          return parent::getGroupByType($type);
        }
      }
    return self::OPTION_GROUP_ATTACHMENT;
    }
    return parent::getGroupByType($type);
  }
}

