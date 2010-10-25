<?php

class ZetaPrints_Attachment_Block_Adminhtml_Attachment_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'attachment';
        $this->_controller = 'adminhtml_attachment';
        
        $this->_updateButton('save', 'label', Mage::helper('attachment')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('attachment')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('attachment_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'attachment_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'attachment_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('attachment_data') && Mage::registry('attachment_data')->getId() ) {
            return Mage::helper('attachment')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('attachment_data')->getTitle()));
        } else {
            return Mage::helper('attachment')->__('Add Item');
        }
    }
}