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
        $_key = substr($_key, 0, 1)
          . str_replace(array('_', "\x0A"), array(' ', '.'), substr($_key, 1));
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

    $templates_details = zetaprints_update_preview(
      Mage::getStoreConfig('zpapi/settings/w2p_url'), $w2p_user->key, $params);

    if (!$templates_details)
      return;

    $helper = Mage::helper('webtoprint');

    //Generate URLs for preview and thumbnail images
    foreach ($templates_details['pages'] as &$page)
      if (isset($page['updated-preview-image'])) {
        $page['updated-preview-url'] = $helper
                  ->get_preview_url(substr($page['updated-preview-image'], 8));
        $page['updated-thumb-url'] = $helper
                  ->get_thumbnail_url(substr($page['updated-preview-image'], 8),
                                      100, 100);
      }

    echo json_encode($templates_details);
  }

  public function getAction () {
    if (!$this->getRequest()->has('guid'))
        return;

    $guid = $this->getRequest()->get('guid');

    $url = Mage::getStoreConfig('zpapi/settings/w2p_url') . '/preview/' . $guid;

    $response = zetaprints_get_content_from_url($url);

    if (!zetaprints_has_error($response)) {
      $headers = $response['content']['header'];

      if (is_array($headers))
        $this->getResponse()
          ->setHeader('Last-Modified', $headers['Last-Modified'], true)
          ->setHeader('ETag', $headers['ETag'], true)
          ->setHeader('Pragma', '', true)
          ->setHeader('Cache-Control', 'public', true)
          ->setHeader('Cache-Control', $headers['Cache-Control'])
          ->setHeader('Expires', '', true)
          ->setHeader('Content-Type', $headers['Content-Type'] , true)
          ->setHeader('Content-Length', $headers['Content-Length'], true);
      else {
        $type = explode('.', $guid);

        if (count($type) == 2)
          $type = $type[1];

        if ($type == 'jpg')
          $type = 'jpeg';

        $this->getResponse()
          ->setHeader('Content-Type', 'image/' . $type);
        }

      $this->getResponse()->setBody($response['content']['body']);
    }
  }

  public function downloadAction () {
    if (!$this->getRequest()->has('guid'))
        return;

    $guid = $this->getRequest()->get('guid');

    $media_config = Mage::getModel('catalog/product_media_config');

    $file_path = $media_config->getTmpMediaPath("previews/{$guid}");

    //Check that preview was already downloaded
    //to prevent subsequent downloads
    if (file_exists($file_path)) {
      echo json_encode('OK');
      return;
    }

    $url = Mage::getStoreConfig('zpapi/settings/w2p_url') . '/preview/' . $guid;

    //Download preview image from ZetaPrinrs
    $response = zetaprints_get_content_from_url($url);

    if (zetaprints_has_error($response)) {
      echo json_encode($this->__('Error was occurred while preparing preview image'));
      return;
    }

    //Save preview image on M. server
    if (file_put_contents($file_path, $response['content']['body']) === false) {
      echo json_encode($this->__('Error was occurred while preparing preview image'));
      return;
    }

    echo json_encode('OK');
  }
}
?>
