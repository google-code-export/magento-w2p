<?php

class ZetaPrints_Zpapi_Model_FileUploadingLimit extends
  Mage_Core_Model_Config_Data {

  public function getValue () {

    return ini_get('upload_max_filesize') . 'B';
  }
}

?>
