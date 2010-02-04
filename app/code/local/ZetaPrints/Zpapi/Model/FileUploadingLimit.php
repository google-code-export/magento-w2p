<?php

class ZetaPrints_Zpapi_Model_FileUploadingLimit {
  const NONE = -1;
  const DELETE = -2;

  protected $_options;

  public function toOptionArray () {
    return array(ini_get('upload_max_filesize'));
  }
}

?>
