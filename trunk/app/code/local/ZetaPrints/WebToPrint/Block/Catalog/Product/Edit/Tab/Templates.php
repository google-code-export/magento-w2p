<?php

class ZetaPrints_WebToPrint_Block_Catalog_Product_Edit_Tab_Templates extends Mage_Adminhtml_Block_Widget_Grid {
  public function __construct() {
    parent::__construct();
    $this->setId('webtoprint_templates_grid');
    $this->setDefaultSort('guid');
    $this->setUseAjax(true);
  }

  protected function _prepareCollection () {
    $this->setCollection(Mage::getModel('webtoprint/template')->getCollection());
    return parent::_prepareCollection();
  }

  protected function _prepareColumns () {
    $this->addColumn('selected', array(
      'header_css_class' => 'a-center',
      'type'      => 'radio',
      'html_name'      => 'product[webtoprint_template]',
      'values'    => array($this->get_template_guid ()),
      'align'     => 'center',
      'index'     => 'guid',
      'sortable'  => false,
    ));

    $this->addColumn('title', array(
      'header'    => Mage::helper('catalog')->__('Name'),
      'sortable'  => true,
      'index'     => 'title' ));

    $this->addColumn('created', array(
      'type'      => 'datetime',
      'header'    => Mage::helper('catalog')->__('Created'),
      'sortable'  => true,
      'index'     => 'date' ));
  }

  private function get_template_guid () {
    return Mage::registry('product')->getWebtoprintTemplate();
  }

  public function getGridUrl() {
    return $this->getData('grid_url') ? $this->getData('grid_url') : $this->getUrl('*/*/templates', array('_current' => true));
  }
}

?>
