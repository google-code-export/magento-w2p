<?php

class ZetaPrints_WebToPrint_Model_Template extends Mage_Core_Model_Abstract {
  protected function _construct() {
    $this->_init('webtoprint/template');
  }

  public function loadById ($template_id) {
    return parent::load($template_id);
  }

  public function load ($id, $field = 'guid') {
    return parent::load($id, $field);
  }
}

?>
