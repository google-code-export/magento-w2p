<?php

class ZetaPrints_WebToPrint_Model_Mysql4_Template extends Mage_Core_Model_Mysql4_Abstract {
  protected function _construct() {
    $this->_init('webtoprint/template', 'guid');
  }

  public function getIdByGuid($guid) {
    return $this->getReadConnection()->fetchOne('select template_id from '.$this->getMainTable().' where guid = ?', $guid);
  }
}

?>
