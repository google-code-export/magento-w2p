<?php

class ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Option
        extends ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Common
{

  public function render (Varien_Object $row)
  {
    $incId = $row->getData($this->getColumn()->getIndex());
    if ($incId) {

      $product = Mage::getModel('catalog/product')->load($row->getData('product_id'));
      /* @var $product Mage_Catalog_Model_Product */
      $option = $product->getOptionById($incId);
      /* @var $option Mage_Catalog_Model_Product_Option */
      try {
        
        $value = $option->getStoreTitle();
        return $value;
      } catch (Exception $e) {
        return $incId;
      }
    }
    return 'N/A';
  }
}
