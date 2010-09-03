<?php

class ZetaPrints_OrderApproval_Block_Widget_Grid_Column_Renderer_Image
  extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

  public function _getValue (Varien_Object $row) {
    $default_value = $this->getColumn()->getDefault();

    $data = parent::_getValue($row);

    return is_null($data) && !is_set($data['url']) && !is_set($data['alt'])
                ? $default_value : $data;
  }

  public function render (Varien_Object $row) {
    $data = $this->_getValue($row);

    $data['alt'] = htmlspecialchars($data['alt']);

    return "<img src=\"{$data['url']}\" alt=\"{$data['alt']}\" />";
  }
}

?>
