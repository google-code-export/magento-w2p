<?php

require_once 'Mage/Adminhtml/controllers/Catalog/ProductController.php';

class ZetaPrints_Moxi_Catalog_Product_CampaignController
  extends Mage_Adminhtml_Catalog_ProductController {

  public function campaignAction () {
    $this->_initProduct();

    $this->getResponse()->setBody(
        $this->getLayout()
          ->createBlock('moxi/catalog_product_edit_tab_campaign')
          ->toHtml() );
  }

  public function templatesGridAction() {
    $this->_initProduct();
    $this->loadLayout();

    $this->getResponse()->setBody(
        $this->getLayout()
          ->createBlock('webtoprint/catalog_product_edit_tab_templates')
          ->toHtml() );
  }

  public function templatesAction () {
    $this->_initProduct();

    $dynamicImaging = $this->getLayout()
      ->createBlock('webtoprint/catalog_product_edit_tab_templates_dynamicimaging');

    $radio_block= $this->getLayout()
      ->createBlock('webtoprint/catalog_product_edit_tab_templates_radiobutton');

    $grid_block = $this->getLayout()
      ->createBlock('webtoprint/catalog_product_edit_tab_templates')
      ->setGridUrl($this->getUrl('*/*/templatesGrid', array('_current' => true)));

    $this->_outputBlocks($dynamicImaging, $radio_block, $grid_block);
  }
}

?>
