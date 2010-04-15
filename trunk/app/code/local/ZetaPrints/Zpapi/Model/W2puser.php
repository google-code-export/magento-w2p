<?php
/**
  * Project magento-w2p
  * Author  Pham Tri Cong <phtcong@gmail.com>
  * Issue 2: Register a new user
  *   + Update Magento DB: add field for UserID (GUID) and a generated password
  * + Update session parameters to store UserID and password for unregistered users
  * + Send an HTTP GET request to ZP prior to referring the user to any ZP UI.
  * + Store user ID and password in user profile or session.
  * Issue 3:
  * 1. Calculate MD5 hash of user password and current user IP address
  * 2. Craft the iframe src URL using TemplateID for the selected product,
  * UserID, the hash and URL of the page the user will be returned to from ZP
  * 3. Show the IFRAME
  */

//defining cookie life time in seconds
define('ZP_COOKIE_LIFETIME',15552000);

if (!defined('ZP_API_VER')) include('zp_api.php');

class ZetaPrints_Zpapi_Model_W2pUser extends Mage_Api_Model_User {

  protected function _construct () {
    parent::_construct();

    $this->base = Mage::getStoreConfig('zpapi/settings/w2p_url');
    $this->key = Mage::getStoreConfig('zpapi/settings/w2p_key');

    zp_api_init($this->key, $this->base);
  }

  /**
   * Process saved order to magento db
   * param id order id
   */
  function order ($id) {
    $data = zp_api_order_detail($id);

    if (!$data || !isset($data['orderid']) || !isset($data['productname'])
        || !isset($data['created']) || !isset($data['productprice'])
        || !isset($data['previewimage']) || !isset($data['previews'])) return -1;

    $data['created'] = zp_api_common_str2date($data['created']);
    $product = Mage::getModel('catalog/product');
    $old = $product->getIdBySku($id);

    if($old) {
      $product->load($old);

      Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

      $product->setData("w2p_image", $data['previewimage']);
      $product->setData("w2p_modified", $data['created']);
      $product->setData("w2p_isorder", 1);
      $product->setData("w2p_image_links", $data['previews']);
      $product->save();

      return $old;
    }

    $baseProduct = Mage::registry('product');
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $product = $baseProduct;

    //custom option
    $newOptionsArray = array();
    $product->setCanSaveCustomOptions(true);

    foreach ($product->getOptions() as $_option) {
      /* @var $_option Mage_Catalog_Model_Product_Option */
      $newOptionsArray[] = $_option->prepareOptionForDuplicate();
    }

    $product->setProductOptions($newOptionsArray);
    //end custom option

    $product->setId(null);

    $product->setSku($id);
    $product->setData("w2p_image", $data['previewimage']);
    $product->setData("w2p_image_large", $data['previewimage']);
    $product->setData("w2p_image_small", $data['previewimage']);
    $product->setData("w2p_created", $data['created']);
    $product->setData("w2p_modified", $data['created']);
    $product->setData("w2p_image_links", $data['previews']);
    $product->setData("w2p_isorder", 1);
    $product->setData("w2p_link", "");
    $product->setData("url_key", $id);
    $product->setData("inventory_manage_stock_default", 1);
    $product->setData("inventory_qty", 10000);

    $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
    $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG);
    $product->setCategoryIds(array(0 => 0));
    $product->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);

    $product->save();

    //stock item
    $stockItem = Mage::getModel('cataloginventory/stock_item');
    $stockItem->setData('use_config_manage_stock', 1);
    $stockItem->setData('is_in_stock', 1);
    $stockItem->setData('stock_id', 1);
    $stockItem->setData('qty', 10000);
    $stockItem->setProduct($product);
    $stockItem->save();

    return $product->getId();
  }

  /**
   * Save order  to ZP
   * param id order id
   * return array
   * ret['pdf'] pdf url
   * ret['jpg'] jpg url
   * ret['gif'] gif url
   * ret['png'] png url
   * ret['cdr'] cdr url
   */
  function saveOrder ($id) {
    $product = Mage::getModel('catalog/product');
    $old = $product->getIdBySku($id);

    if ($old) {
      $product->load($old);

      if (!$product->getData('w2p_isorder')) return 0;
    } else {
      return 0;
    }

    $data = array();
    for ($i = 0; $i < 2; $i++) {
      $data = zp_api_order_save($id);

      if (isset($data['orderid'])) break;
    }

    if (!$data || !isset($data['orderid']) || !isset($data['productname'])
        || !isset($data['created']) || !isset($data['productprice'])
        || !isset($data['previewimage']) || !isset($data['previews'])) {

      Mage::getSingleton('checkout/session')->addError("SAVE STATUS OF $id :ERROR!!!" );

      return -1;
    }

    $ret = array("pdf" => "",
                 "jpeg" => "",
                 "gif" => "",
                 "png" => "",
                 "cdr" => "");

    foreach ($ret as $key => $val)
      if (isset($data[$key]))
        $ret[$key] = $data[$key];

    foreach ($ret as $key => $val)
      if ($val) {
        if ($key == "jpeg") $key = "jpg";
        $product->setData("w2p_".$key, $val);
      }

    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $product->setStatus(2);
    $product->save();

    Mage::getSingleton('checkout/session')->addError("CHANGE STATUS OF $id :DONE!!!");

    return 1;
  }

  function getPersonalizeUrl ($tid) {
    $ip = $_SERVER["REMOTE_ADDR"];
    $uid = $this->getW2pUserId();

    if (!$uid) {
      $this->autoRegister();
      $uid = $this->getW2pUserId();
    }

    $pass = $this->getW2pPass();

    return zp_api_template_iframe_url($tid, $uid, $pass);
  }

  /**
   * auto registe user
   * check if is not registed, this will create new GUID and Pas then registe new user
   *
   */
  function autoRegister() {
    $login = 0;

    //Not have UserId
    if (!$this->key || !$this->base) return;

    if (!$this->getW2pUserId()) {
      $cus = Mage::getSingleton('customer/session')->getCustomer();

      //Check if is created as unregister user
      if (Mage::getSingleton('core/session')->getW2puser() && $cus->getData('entity_id')) {
        //Save to Magento DB
        $cus->setData('zetaprints_user', Mage::getSingleton('core/session')->getW2puser());
        $cus->setData('zetaprints_password', Mage::getSingleton('core/session')->getW2ppass());
        $cus->save();

        $this->state = "registed-u->M";

        //Clear session
        Mage::getSingleton('core/session')->unsW2puser();
        Mage::getSingleton('core/session')->unsW2ppass();

        return 0;
      }

      //connecting to DB
      $db = Mage::getSingleton('core/resource')->getConnection('core_write');
      //check if ZP_ID cookie exists
      $c_user=Mage::getSingleton('core/cookie')->get('ZP_ID');
      if ($c_user){
      //found cookie, fetching password from DB
          zp_api_log_debug('Found cookie, fetching password from DB');
      $c_pass=$db->fetchOne("select pass from zetaprints_cookies where user_id=?",array($c_user));
      if (strlen($c_pass)==6)
          {
            //found password in DB, assigning creditenials and return
            zp_api_log_debug('Restoring session from cookie and DB');
            $this->pass=$c_pass;
            $this->user=$c_user;
            $this->state="ok";
            //Save to session
            Mage::getSingleton('core/session')->setW2puser($this->user);
            Mage::getSingleton('core/session')->setW2ppass($this->pass);
            return 1;
          }else{
            //password not found in DB, cookie is wrong?
            unset($c_user);
            unset($c_pass);

            zp_api_log_debug('Wrong cookie on client side. Deleting...');
            Mage::getSingleton('core/cookie')->delete('ZP_ID');
          }
      }

      //Not created, will create new account on ZP
      $this->user = zp_api_common_uuid();
      $this->pass = zp_api_common_pass();

      if (zetaprints_register_user($this->base, $this->key, $this->user, $this->pass)) {
        //Save SESSION
        $this->state = "ok";
        $login = 1;

        if ($cus->getData('entity_id')) {
          //Save to db
          $cus->setData('zetaprints_user', $this->user);
          $cus->setData('zetaprints_password', $this->pass);
          $cus->save();
          $this->state = "ok-m";
        } else {
          //Save to session
          Mage::getSingleton('core/session')->setW2puser($this->user);
          Mage::getSingleton('core/session')->setW2ppass($this->pass);

          //registered, creating cookie
          Mage::getSingleton('core/cookie')->set('ZP_ID',$this->user,ZP_COOKIE_LIFETIME);
          //adding password to DB
          $db->insert("zetaprints_cookies",array("user_id"=>$this->user,"pass"=>$this->pass));
        }

      } else {
        //Login Error
        $this->user = "";
        $this->state = "error";
        $login = -1;
      }
    } else
      $this->state = "registed";

    Mage::getSingleton('core/session')->setState($this->state);
    //Mage::getSingleton('core/session')->unsW2puser();
    //Mage::getSingleton('core/session')->unsW2ppass();

    return $login;
  }
  /**
   * get magneto's role of the user from session
   */
  function getRole () {
    $cus = Mage::getSingleton('customer/session')->getCustomer();
    return $cus->getData('email') ? $cus->getData('email') : "guest";
  }

  /**
   * get w2p's state of the user from session
   */
  function getW2pState () {
    return Mage::getSingleton('core/session')->getState();
  }

  /**
   * get w2p's userId of the user from session
   */
  function getW2pUserId () {
    $cus = Mage::getSingleton('customer/session')->getCustomer();
    return $cus->getData('entity_id') ? $cus->getData('zetaprints_user') : Mage::getSingleton('core/session')->getW2puser();
  }

  /**
   * get w2p's pass of the user from session
   */
  function getW2pPass () {
    $cus = Mage::getSingleton('customer/session')->getCustomer();
    return $cus->getData('entity_id') ? $cus->getData('zetaprints_password') : Mage::getSingleton('core/session')->getW2ppass();
  }

  function get_credentials () {
    $id = $this->getW2pUserId();

    if (!$id) {
      $this->autoRegister();
      $id = $this->getW2pUserId();
    }

    $password = $this->getW2pPass();

    return array('id' => $id, 'password' => $password);
  }
}
