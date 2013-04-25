<?php

class ZetaPrints_WebToPrint_Block_Catalog_Product_Edit_Tab_Templates_DynamicImaging extends Mage_Adminhtml_Block_Widget {
  public function __construct() {
    parent::__construct();
    $this->setTemplate('catalog/product/tab/templates/dynamic-imaging.phtml');
  }

  public function getProduct () {
    return Mage::registry('product');
  }
}

?>
