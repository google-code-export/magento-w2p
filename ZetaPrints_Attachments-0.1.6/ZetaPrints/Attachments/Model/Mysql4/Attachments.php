<?php

class ZetaPrints_Attachments_Model_Mysql4_Attachments extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        // Note that the attachment_id refers to the key field in your database table.
        $this->_init('attachments/attachments', 'attachment_id');
    }
}