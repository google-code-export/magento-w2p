<?php

class ZetaPrints_Attachments_Block_Adminhtml_Attachment_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('attachments_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('attachments')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('attachments')->__('Item Information'),
          'title'     => Mage::helper('attachments')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('attachments/adminhtml_attachments_edit_tab_form')->toHtml(),
      ));

      return parent::_beforeToHtml();
  }
}