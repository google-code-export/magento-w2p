<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Zpapi/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

class ZetaPrints_WebToPrint_ThumbnailController extends Mage_Core_Controller_Front_Action {

  public function getAction () {
    if (!$this->getRequest()->has('guid'))
        return;

    $guid = $this->getRequest()->get('guid');

    $type = explode('.', $guid);

    if (count($type) == 2)
      $type = $type[1];

    if ($type == 'jpg')
      $type = 'jpeg';

    $width = 0;
    if ($this->getRequest()->has('width'))
      $width = (int) $this->getRequest()->get('width');

    $height = 0;
    if ($this->getRequest()->has('height'))
      $height = (int) $this->getRequest()->get('height');

    //Check if width or height is setted
    if (($width + $height) != 0)
      $guid = str_replace('.', "_{$width}x{$height}.", $guid);

    $url = Mage::getStoreConfig('zpapi/settings/w2p_url') . '/thumb/' . $guid;

    $response = zetaprints_get_content_from_url($url);

    if (!zetaprints_has_error($response))
      $this->getResponse()
        ->setHeader('Content-Type', 'image/' . $type)
        ->setBody($response['content']['body']);
  }
}
?>
