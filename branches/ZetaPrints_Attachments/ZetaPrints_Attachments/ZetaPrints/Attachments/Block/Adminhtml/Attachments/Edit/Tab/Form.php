<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ZetaPrints_Attachments_Block_Adminhtml_Attachment_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('attachmentss_form', array('legend'=>Mage::helper('attachmentss')->__('Item information')));

      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('attachments')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('attachments')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));

      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('attachments')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('attachments')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('attachments')->__('Disabled'),
              ),
          ),
      ));

      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('attachments')->__('Content'),
          'title'     => Mage::helper('attachments')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));

      if ( Mage::getSingleton('adminhtml/session')->getAttachmentData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getAttachmentData());
          Mage::getSingleton('adminhtml/session')->setAttachmentData(null);
      } elseif ( Mage::registry('attachments_data') ) {
          $form->setValues(Mage::registry('attachments_data')->getData());
      }
      return parent::_prepareForm();
  }
}