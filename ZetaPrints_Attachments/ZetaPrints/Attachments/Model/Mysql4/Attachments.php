<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ZetaPrints_Attachments_Model_Mysql4_Attachments extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        // Note that the attachment_id refers to the key field in your database table.
        $this->_init('attachments/attachments', 'attachment_id');
    }
}