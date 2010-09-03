<?php

class ZetaPrints_OrderApproval_Block_Cart_Edit_Grid_Column_Renderer_Image
  extends ZetaPrints_OrderApproval_Block_Widget_Grid_Column_Renderer_Image {

  protected $_image_data = null;

  public function __construct() {
    parent::__construct();

    $this->setTemplate('checkout/cart/edit/grid/column/renderer/image.phtml');
  }

  public function render (Varien_Object $row) {
    if (is_null($this->_image_data))
      $this->_image_data = parent::_getValue($row);

    if (!isset($this->_image_data['item'])) {
      return '';
    }

    return $this->_toHtml();
  }

  public function getImageUrl () {
    return $this->_image_data['url'];
  }

  public function getAltText () {
    return $this->_image_data['alt'];
  }

  public function getItem () {
    return $this->_image_data['item'];
  }
}

?>
