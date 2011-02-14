<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 2/14/11
 * Time: 9:14 PM
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Product
 extends ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Common
{
  public function render(Varien_Object $row)
  {
    $prId = $row->getData($this->getColumn()->getIndex());
    if($prId){
      $product = Mage::getModel('catalog/product')->load($prId);
      if($product->getId()){
        /* @var $product Mage_Catalog_Model_Product */
        $name = $product->getName();
        $link = Mage::getUrl('*/catalog_product/edit', array('id' => $product->getId()));
        return $this->getLinkHtml($name, $link);
      }else{
        return $prId;
      }
    }
    return $row->getData($this->getColumn()->getIndex());
  }
}
