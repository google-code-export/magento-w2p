<?php

class ZetaPrints_WebToPrint_Model_Resource_Template
  extends Mage_Core_Model_Resource_Db_Abstract {

  protected function _construct() {
    $this->_init('webtoprint/template', 'template_id');
  }

  public function getIdByGuid($guid) {
    return $this
             ->getReadConnection()
             ->fetchOne('select template_id from '
                        . $this->getMainTable()
                        . ' where guid = ?', $guid);
  }
}
