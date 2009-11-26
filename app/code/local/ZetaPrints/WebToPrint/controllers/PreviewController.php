<?php
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

    $w2p_user = Mage::getModel('api/w2puser');

    $user_credentials = $w2p_user->get_credentials();
    $params['ID'] = $user_credentials['id'];
    $params['Hash'] = zetaprints_generate_user_password_hash($user_credentials['password']);

    echo zetaprints_get_preview_image_url(Mage::getStoreConfig('api/settings/w2p_url'), $w2p_user->key, $params);
  }
}
?>
