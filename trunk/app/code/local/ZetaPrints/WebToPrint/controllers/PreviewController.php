<?php
class Biinno_WebToPrint_PreviewController extends Mage_Core_Controller_Front_Action {

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

    $params['ApiKey'] = $w2p_user->key;

    $user_credentials = $w2p_user->get_credentials();
    $params['ID'] = $user_credentials['id'];
    $params['Hash'] = zetaprints_generate_user_password_hash($user_credentials['password']);

    //Sending image generating request to zetaprints
    list($header, $content) = zp_api_common_post_request(Mage::getStoreConfig('api/settings/w2p_url'), '/api.aspx?page=api-preview', $params);

    //BUG. Getting strange numbers in the content
    list(, $url) = explode("\r\n", $content);

    //Returning an url to generated image
    echo $url;
  }
}
?>