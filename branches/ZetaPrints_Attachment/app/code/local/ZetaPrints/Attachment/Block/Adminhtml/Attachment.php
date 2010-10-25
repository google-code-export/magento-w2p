<?php
class ZetaPrints_Attachment_Block_Adminhtml_Attachment extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_attachment';
    $this->_blockGroup = 'attachment';
    $this->_headerText = Mage::helper('attachment')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('attachment')->__('Add Item');
    parent::__construct();
  }
}