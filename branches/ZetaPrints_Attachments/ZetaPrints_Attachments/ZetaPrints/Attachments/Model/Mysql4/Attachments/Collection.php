<?php
/**
 * @author       Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ZetaPrints_Attachments_Model_Mysql4_Attachments_Collection
  extends Mage_Core_Model_Mysql4_Collection_Abstract
{
  public function _construct()
  {
    parent::_construct();
    $this->_init('attachments/attachments');
  }

  public function rehashFiles()
  {
    if (!$this->isLoaded()) {
      $this->load();
    }
    foreach ($this->_items as $att) {
      $att->setData(ZetaPrints_Attachments_Model_Attachments::FILE_HASH, md5($att->getData(ZetaPrints_Attachments_Model_Attachments::ATT_VALUE)));
      $att->save();
    }
    return $this;
  }
}
