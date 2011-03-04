<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid
  extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('attachmentsGrid');
      $this->setDefaultSort('attachment_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('attachments/attachments')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('attachment_id', array(
          'header'    => Mage::helper('attachments')->__('Attachment ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'attachment_id',
      ));

      $this->addColumn('option_id', array(
          'header'    => Mage::helper('attachments')->__('Option Title'),
          'align'     =>'right',
          'width'     => '150px',
          'index'     => 'option_id',
          'renderer'  => 'ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Option'
      ));

      $this->addColumn('product_id', array(
          'header'    => Mage::helper('attachments')->__('Product'),
          'align'     =>'left',
          'index'     => 'product_id',
          'width'     => '150px',
          'renderer'  => 'ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Product'
      ));

      $this->addColumn('order_id', array(
          'header'    => Mage::helper('attachments')->__('Used in order'),
          'align'     => 'center',
          'width'     => '100px',
          'index'     => 'order_id',
          'default'		=> 'N/A',
          'renderer'  => 'ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Order'
      ));

      $this->addColumn('att_value', array(
        'header'  => Mage::helper('attachments')->__('File'),
        'align'		=> 'left',
        'index'		=> 'attachment_value',
        'renderer'=> 'ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Attachment'
      ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('attachments')->__('Action'),
                'width'     => '100px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('attachments')->__('Delete'),
                        'url'       => array('base'=> '*/*/delete'),
                        'field'     => 'attachment_id',
                        'confirm'  => Mage::helper('attachments')->__('Are you sure you want to delete this file?'),
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

//		$this->addExportType('*/*/exportCsv', Mage::helper('attachments')->__('CSV'));
//		$this->addExportType('*/*/exportXml', Mage::helper('attachments')->__('XML'));

      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('attachment_id');
        $this->getMassactionBlock()->setFormFieldName('attachments');
        $_helper = Mage::helper('attachments');

        $this->getMassactionBlock()
             ->addItem('delete', array(
               'label'    => $_helper->__('Delete'),
               'url'      => $this->getUrl('*/*/massDelete'),
               'confirm'  => $_helper->__('Are you sure you want to delete selected files?'),
            	 'selected' => true,
               ));
        return $this;
    }

  public function getRowUrl($row)
  {
      return '';
//      return $this->getUrl('*/*/delete', array('attachment_id' => $row->getId()));
  }

}
