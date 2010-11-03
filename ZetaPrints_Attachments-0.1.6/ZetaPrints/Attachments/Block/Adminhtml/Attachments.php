<?php
class ZetaPrints_Attachments_Block_Adminhtml_Attachment extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_attachments';
    $this->_blockGroup = 'attachments';
    $this->_headerText = Mage::helper('attachments')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('attachments')->__('Add Item');
    parent::__construct();
  }
}