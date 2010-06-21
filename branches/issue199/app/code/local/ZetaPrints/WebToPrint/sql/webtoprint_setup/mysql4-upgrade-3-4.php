<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Zpapi/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

$dir_name = zetaprints_generate_guid();

Mage::getConfig()->saveConfig('zetaprints/webtoprint/uploading/dir', $dir_name);
mkdir(Mage::getModel('catalog/product_media_config')->getTmpMediaPath($dir_name), 0777, true);

?>
