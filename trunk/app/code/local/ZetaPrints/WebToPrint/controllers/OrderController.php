<?php
require_once 'Mage/Catalog/controllers/ProductController.php';

class ZetaPrints_WebToPrint_OrderController extends Mage_Catalog_ProductController {

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

    //Saving order in zetaprints for futher processing
    list($header, $content) = zp_api_common_post_request(Mage::getStoreConfig('api/settings/w2p_url'), '/api.aspx?page=api-order-save', $params);

    list(, $order_id) = explode("\r\n", $content);

    //Validating order id
    if (preg_match('/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/', $order_id))
      if (parent::_initProduct())
        //Adding order to magento if its id is valid.
        echo $w2p_user->order($order_id);
    else
      echo '0';
  }
}
?>