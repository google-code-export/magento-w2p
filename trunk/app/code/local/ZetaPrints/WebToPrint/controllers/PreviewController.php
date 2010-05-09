<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Zpapi/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

class ZetaPrints_WebToPrint_PreviewController extends Mage_Core_Controller_Front_Action {

  public function indexAction () {
    $params = array();

     //Preparing params for image generating request to zetaprints
    foreach ($this->getRequest()->getParams() as $key => $value) {
      if (strpos($key, 'zetaprints-') !== false) {
        $_key = substr($key, 11);
        $_key = substr($_key, 0, 1).str_replace('_', ' ', substr($_key, 1));
        $params[$_key] = str_replace("\n", "\r\n", $value);
      }
    }

    if(count($params) == 0)
      return;

    //$session = Mage::getSingleton('customer/session');

    //$text_cache = $session->getTextFieldsCache();
    //if (!$text_cache)
    //  $text_cache = array();

    //$image_cache = $session->getImageFieldsCache();
    //if (!$image_cache)
    //  $image_cache = array();

    //foreach ($params as $key => $value)
    //  if (strpos($key, '_') !== false) {
    //    $_key = substr($key, 1);

    //    if (array_key_exists($_key, $text_cache))
    //      unset($text_cache[$_key]);

    //    if ($value)
    //      $text_cache[$_key] = $value;

    //    if ($length = count($text_cache) > 150)
    //      $text_cache = array_slice($text_cache, $length - 150);
    //  } elseif (strpos($key, '#') !== false) {
    //    $_key = substr($key, 1);

    //    if (array_key_exists($_key, $image_cache))
    //      unset($image_cache[$_key]);

    //    if ($value)
    //      $image_cache[$_key] = $value;

    //    if ($length = count($image_cache) > 50)
    //      $image_cache = array_slice($image_cache, $length - 50);
    //  }

    //$session->setTextFieldsCache($text_cache);
    //$session->setImageFieldsCache($image_cache);

    //reset($params);

    $w2p_user = Mage::getModel('zpapi/w2puser');

    $user_credentials = $w2p_user->get_credentials();
    $params['ID'] = $user_credentials['id'];
    $params['Hash'] = zetaprints_generate_user_password_hash($user_credentials['password']);

    $url = zetaprints_get_preview_image_url(
      Mage::getStoreConfig('zpapi/settings/w2p_url'), $w2p_user->key, $params);

    $guid = explode('/preview/', $url);

    if (count($guid) != 2)
      return;

    $urls = array(
      'filename' => $guid[1],
      'preview_url' => Mage::helper('webtoprint')->get_preview_url($guid[1]),
      'thumbnail_url' => Mage::helper('webtoprint')->get_thumbnail_url($guid[1],
        100, 100) );

    echo json_encode($urls);
  }

  public function getAction () {
    if (!$this->getRequest()->has('guid'))
        return;

    $guid = $this->getRequest()->get('guid');

    $type = explode('.', $guid);

    if (count($type) == 2)
      $type = $type[1];

    if ($type == 'jpg')
      $type = 'jpeg';

    $url = Mage::getStoreConfig('zpapi/settings/w2p_url') . '/preview/' . $guid;

    $response = zetaprints_get_content_from_url($url);

    if (!zetaprints_has_error($response))
      $this->getResponse()
        ->setHeader('Content-Type', 'image/' . $type)
        ->setBody($response['content']['body']);
  }
}
?>
