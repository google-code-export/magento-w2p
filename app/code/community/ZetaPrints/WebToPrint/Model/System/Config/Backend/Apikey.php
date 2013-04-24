<?php

class ZetaPrints_WebToPrint_Model_System_Config_Backend_Apikey
  extends Mage_Core_Model_Config_Data {

  protected function _beforeSave () {
    $this->setValue(trim($this->getValue()));

    return parent::_beforeSave();
  }
}

?>
