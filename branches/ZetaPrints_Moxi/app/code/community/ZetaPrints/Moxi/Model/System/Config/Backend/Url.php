<?php

class ZetaPrints_Moxi_Model_System_Config_Backend_Url
  extends Mage_Core_Model_Config_Data {

  protected function _beforeSave () {
    $this->setValue($this->getValue() . '/');

    return $this;
  }
}

?>
