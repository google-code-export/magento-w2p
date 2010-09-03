<?php

class ZetaPrints_OrderApproval_Block_Cart_Edit_Grid
  extends Mage_Adminhtml_Block_Widget_Grid {

  public function __construct() {
    parent::__construct();

    $this->setId('cart_edit_grid');

    $this->_filterVisibility = false;
    $this->_pagerVisibility  = false;
  }

  protected function _prepareCollection () {
    $collection = new Varien_Data_Collection();

    $items = $this->getParentBlock() // I think there's a better way to pass
              ->getParentBlock()     // data down in a block hierarchy.
              ->getCustomerQuote()
              ->getAllVisibleItems(true);

    foreach ($items as $item) {
      if ($item->getApproved())
        continue;

      $data = array(
        'id' => $item->getId(),
        'thumbnail'=> array('url' => Mage::helper('catalog/image')
                              ->init($item->getProduct(), 'thumbnail')
                              ->resize(75),
                            'alt' => $item->getProduct()->getName(),
                            'item' => $item),
        'item' => $item);

      $collection->addItem(new Varien_Object($data));
    }

    $this->setCollection($collection);
    return parent::_prepareCollection();
  }

  protected function _prepareColumns() {
    $this->addColumn('thumbnail', array(
      'renderer' => 'orderapproval/cart_edit_grid_column_renderer_image',
      'width'     => '80',
      'align'     => 'center',
      'index'     => 'thumbnail',
      'sortable'  => false,
    ));

    $this->addColumn('product-name', array(
      'header' => $this->__('Product Name'),
      'renderer' => 'orderapproval/cart_edit_grid_column_renderer_productoptions',
      'align'     => 'left',
      'index'     => 'item',
      'sortable'  => false,
    ));

    $this->addColumn('price', array(
      'header' => $this->__('Unit Price'),
      'renderer' => 'orderapproval/cart_edit_grid_column_renderer_price',
      'width'     => '60',
      'align'     => 'right',
      'index'     => 'item',
      'sortable'  => false,
    ));

    $this->addColumn('qty', array(
      'header' => $this->__('Qty'),
      'renderer' => 'orderapproval/cart_edit_grid_column_renderer_qty',
      'width'     => '40',
      'align'     => 'center',
      'index'     => 'item',
      'sortable'  => false,
    ));

    $this->addColumn('subtotal', array(
      'header' => $this->__('Subtotal'),
      'renderer' => 'orderapproval/cart_edit_grid_column_renderer_subtotal',
      'width'     => '60',
      'align'     => 'right',
      'index'     => 'item',
      'sortable'  => false,
    ));

    $this->addColumn('approve_item', array(
      'header'    =>  $this->__('Action'),
      'width'     => '60',
      'align'     => 'center',
      'type'      => 'action',
      'getter'    => 'getId',
      'actions'   => array(
        array(
          'caption' => $this->__('Approve'),
          'url' => array('base' => '*/*/approve'),
          'field'     => 'item' ), ),
      'filter'    => false,
      'sortable'  => false,
      ));

    return parent::_prepareColumns();
  }

  protected function _prepareMassaction () {
    $this->setMassactionIdField('id');
    $this->getMassactionBlock()->setFormFieldName('items');

    $this->getMassactionBlock()->addItem('approve', array(
      'label'    => Mage::helper('index')->__('Approve'),
      'url'      => $this->getUrl('*/*/massApprove'),
      'selected' => true ));

    return $this;
  }
}

?>
