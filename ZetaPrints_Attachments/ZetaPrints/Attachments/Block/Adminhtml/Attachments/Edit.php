<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class ZetaPrints_Attachments_Block_Adminhtml_Attachment_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'attachments';
        $this->_controller = 'adminhtml_attachments';

        $this->_updateButton('save', 'label', Mage::helper('attachments')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('attachments')->__('Delete Item'));

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('attachments_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'attachments_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'attachments_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('attachments_data') && Mage::registry('attachments_data')->getId() ) {
            return Mage::helper('attachments')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('attachments_data')->getTitle()));
        } else {
            return Mage::helper('attachments')->__('Add Item');
        }
    }
}