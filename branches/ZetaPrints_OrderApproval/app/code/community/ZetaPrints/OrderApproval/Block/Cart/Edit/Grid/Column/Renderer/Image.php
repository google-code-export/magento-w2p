<?php

class ZetaPrints_OrderApproval_Block_Cart_Edit_Grid_Column_Renderer_Image
  extends ZetaPrints_OrderApproval_Block_Cart_Edit_Grid_Column_Renderer_Abstract {

  public function __construct() {
    parent::__construct();

    $this->setTemplate('checkout/cart/edit/grid/column/renderer/image.phtml');
  }

  public function getProductThumbnail () {
    return $this->getItemRendererBlock()->getProductThumbnail();
  }

  public function getProductName () {
    return $this->getItemRendererBlock()->getProductName();
  }
}

?>
