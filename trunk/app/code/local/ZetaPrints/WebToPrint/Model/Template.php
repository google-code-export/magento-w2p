<?php

class ZetaPrints_WebToPrint_Model_Template extends Mage_Core_Model_Abstract {
  protected function _construct() {
    $this->_init('webtoprint/template');
  }

  public function loadByGuid ($guid) {
    return $this->load($this->_getResource()->getIdByGuid($guid));
  }
}

?>
