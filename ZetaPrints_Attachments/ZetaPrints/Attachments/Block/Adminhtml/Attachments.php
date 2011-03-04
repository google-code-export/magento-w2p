<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ZetaPrints_Attachments_Block_Adminhtml_Attachments extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_attachments';
    $this->_blockGroup = 'attachments';
    $this->_headerText = Mage::helper('attachments')->__('Attachments Manager');
    parent::__construct();

    $this->removeButton('add');
    $delAll = $this->__('This will delete ALL old files! Do you want to do continue?');
    $delOrph = $this->__('This will delete orphaned files. Do you want to do continue?');
    $this->_addButton('deleteall', array ('label' => 'Delete All Old',
                                    'onclick' => 'if(confirm(\'' . $delAll . '\')){setLocation(\'' . $this->getUrl('*/*/deleteAll') . '\')}',
                                    'class' => 'delete'
    ));
    $this->_addButton('deleteorphaned', array ('label' => 'Delete Orphaned',
                                    'onclick' => 'if(confirm(\'' . $delOrph . '\')){setLocation(\'' . $this->getUrl('*/*/deleteOrphaned') . '\')}',
                                    'class' => 'delete'
    ));
    $this->_addButton('refreshashes', array('label' => 'Refresh File Hashes',
                                      'onclick' => 'setLocation(\'' . $this->getUrl('*/*/refresh') . '\')',
                                      )
    );
  }
}
