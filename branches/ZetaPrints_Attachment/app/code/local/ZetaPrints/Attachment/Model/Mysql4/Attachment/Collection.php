<?php

class ZetaPrints_Attachment_Model_Mysql4_Attachment_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('attachment/attachment');
    }
}