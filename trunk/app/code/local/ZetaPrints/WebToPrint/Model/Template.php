<?php

class ZetaPrints_WebToPrint_Model_Template extends Mage_Core_Model_Abstract {
  protected function _construct() {
    $this->_init('webtoprint/template');
    $this->setIdFieldName('guid');
  }

  public function loadByGuid ($guid) {
    return $this->load($this->_getResource()->getIdByGuid($guid));
  }

  public function load ($id, $field = 'guid') {
    return parent::load($id, $field);
  }

  public function save () {
    $this->setIdFieldName('template_id');
    parent::save();
    $this->setIdFieldName('guid');

    return $this;
  }
}

?>
