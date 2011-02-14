<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ZetaPrints_Attachments_Block_Attachments extends Mage_Core_Block_Template
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