<?php

class ZetaPrints_WebToPrint_Model_System_Config_Backend_ZetaPrintsUrl
  extends Mage_Core_Model_Config_Data {

  protected function _beforeSave () {
    $value = $this->getValue();

    //Add slash at the end of URL of there's no one,
    //otherwise remove everything after domain name
    $value = ($position = strpos($value, '/', 9)) === false
               ? $value .= '/'
                 : substr($value, 0, $position + 1);

    $this->setValue(trim($value));

    return parent::_beforeSave();;
  }
}

?>
