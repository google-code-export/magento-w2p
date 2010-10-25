<?php

class ZetaPrints_Attachment_Block_Adminhtml_Attachment_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('attachment_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('attachment')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('attachment')->__('Item Information'),
          'title'     => Mage::helper('attachment')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('attachment/adminhtml_attachment_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}