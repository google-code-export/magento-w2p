<?php

class ZetaPrints_Moxi_Block_Catalog_Product_Edit_Tab_Campaign
  extends Mage_Adminhtml_Block_Widget_Grid {

  public function __construct() {
    parent::__construct();
    $this->setId('moxi_campaign_grid');
    $this->setDefaultSort('id');
    $this->setUseAjax(true);
  }

  protected function _prepareCollection () {
    $this->setCollection(Mage::helper('moxi')->getManagers());

    return parent::_prepareCollection();
  }

  protected function _prepareColumns () {
    $this->addColumn('selected', array(
      'header_css_class' => 'a-center',
      'type'      => 'radio',
      'html_name'      => 'product[moxi_manager]',
      //'values'    => array($this->get_template_guid ()),
      'align'     => 'center',
      'index'     => 'id',
      'sortable'  => false,
    ));

    $this->addColumn('name', array(
      'header'    => Mage::helper('moxi')->__('Name'),
      'sortable'  => true,
      'index'     => 'name' ));

    $this->addColumn('contact', array(
      'header'    => Mage::helper('moxi')->__('Contact'),
      'sortable'  => true,
      'index'     => 'contact' ));

    $this->addColumn('email', array(
      'header'    => Mage::helper('moxi')->__('E-mail'),
      'sortable'  => true,
      'index'     => 'email' ));
  }
}

?>
