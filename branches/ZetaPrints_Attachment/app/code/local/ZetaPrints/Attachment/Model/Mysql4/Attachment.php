<?php

class ZetaPrints_Attachment_Model_Mysql4_Attachment extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the attachment_id refers to the key field in your database table.
        $this->_init('attachment/attachment', 'attachment_id');
    }
}