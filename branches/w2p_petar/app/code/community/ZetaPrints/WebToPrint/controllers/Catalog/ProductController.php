<?php

require_once 'Mage/Adminhtml/controllers/Catalog/ProductController.php';

class ZetaPrints_WebToPrint_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController {
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

    $radio_block= $this->getLayout()
      ->createBlock('webtoprint/catalog_product_edit_tab_templates_radiobutton');

    $grid_block = $this->getLayout()
      ->createBlock('webtoprint/catalog_product_edit_tab_templates')
      ->setGridUrl($this->getUrl('*/*/templatesGrid', array('_current' => true)));

    $this->_outputBlocks($radio_block, $grid_block);
  }

  public function updateProfileAction() {
    $profile = $this->getRequest()->getParam('profile', null);
    $src = $this->getRequest()->getParam('src');
    if(!$profile) {
      $this->_redirect('adminhtml/catalog_product/edit', array('id' => $src));
      return;
    }
    /* @var  Mage_Dataflow_Model_Profile */
    $profile = Mage::getModel('dataflow/profile')->load($profile);
    $actionXml = $profile->getData('actions_xml');
    $actionXml = simplexml_load_string('<data>' . $actionXml . '</data>');
    if($actionXml) {
      $actionXml->action[0]['src'] = $src;
      $resActionXml = $actionXml->action->asXml();
      $profile->setData('actions_xml', $resActionXml)->save();
    }
    $this->_redirect('adminhtml/system_convert_profile/edit', array('id' => $profile->getId()));
  }
}

?>
