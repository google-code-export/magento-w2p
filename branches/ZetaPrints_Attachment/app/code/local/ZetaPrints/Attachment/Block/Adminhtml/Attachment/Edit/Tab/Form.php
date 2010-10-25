<?php

class ZetaPrints_Attachment_Block_Adminhtml_Attachment_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('attachment_form', array('legend'=>Mage::helper('attachment')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('attachment')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('attachment')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('attachment')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('attachment')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('attachment')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('attachment')->__('Content'),
          'title'     => Mage::helper('attachment')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getAttachmentData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getAttachmentData());
          Mage::getSingleton('adminhtml/session')->setAttachmentData(null);
      } elseif ( Mage::registry('attachment_data') ) {
          $form->setValues(Mage::registry('attachment_data')->getData());
      }
      return parent::_prepareForm();
  }
}