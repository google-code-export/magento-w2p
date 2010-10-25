<?php

class ZetaPrints_Attachment_Model_Attachment extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('attachment/attachment');
    }
}