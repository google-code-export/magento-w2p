<?php

class ZetaPrints_WebToPrint_Model_Mysql4_Template extends Mage_Core_Model_Mysql4_Abstract {
  protected function _construct() {
    $this->_init('webtoprint/template', 'template_id');
  }
}

?>
