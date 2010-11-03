<?php
class ZetaPrints_Attachments_Block_Attachment extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }

     public function getAttachment()
     {
        if (!$this->hasData('attachments')) {
            $this->setData('attachments', Mage::registry('attachments'));
        }
        return $this->getData('attachments');

    }
}