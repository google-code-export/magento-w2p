<?php
class ZetaPrints_Attachment_Block_Attachment extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getAttachment()     
     { 
        if (!$this->hasData('attachment')) {
            $this->setData('attachment', Mage::registry('attachment'));
        }
        return $this->getData('attachment');
        
    }
}