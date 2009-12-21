<?php

require_once 'Mage/Adminhtml/controllers/Catalog/ProductController.php';

class ZetaPrints_WebToPrint_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController {

  public function templatesAction () {
    $this->_initProduct();

    $radio_block= $this->getLayout()
      ->createBlock('webtoprint/catalog_product_edit_tab_templates_radiobutton');

    $grid_block = $this->getLayout()
      ->createBlock('webtoprint/catalog_product_edit_tab_templates');

    $this->_outputBlocks($radio_block, $grid_block);
  }

}

?>
