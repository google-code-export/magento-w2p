<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Zpapi/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

Mage::getConfig()->saveConfig('zetaprints/webtoprint/uploading/dir', zetaprints_generate_guid());

?>
