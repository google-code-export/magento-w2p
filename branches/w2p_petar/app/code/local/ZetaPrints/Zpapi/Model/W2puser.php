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
          //connecting to DB
          $db = Mage::getSingleton('core/resource')->getConnection('core_write');
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
    //Get user ID from session or from customer object
    $id = $this->getW2pUserId();

    //If user has ZetaPrints ID in session or in customer object, then...
    if ($id) {

      //... return user's ID and its password
      return array('id' => $id, 'password' => $this->getW2pPass());
    }

    //If user doesn't have ZetaPrints ID in session or in customer object,
    //but has ZP_ID cookie, then extract ZetaPrints ID from it and
    //password from DB
    if (($credentials = $this->get_credentials_from_zp_cookie()) !== false) {
      //Update session if password for user exists in DB
      $this->update_session_with_credentials($credentials);

      return $credentials;
    }

    //We don't know the user, register him on ZetaPrints
    $this->autoRegister();

    return array('id' => $this->getW2pUserId(),
                 'password' => $this->getW2pPass());
  }

  function get_credentials_from_zp_cookie () {
    //Get ZetaPrints user id from cookie
    $id = Mage::getSingleton('core/cookie')->get('ZP_ID');

    if (!$id)
      return false;

    //connecting to DB
    $db = Mage::getSingleton('core/resource')->getConnection('core_write');

    //Get password for user from DB
    $password = $db
      ->fetchOne("select pass from zetaprints_cookies where user_id=?",
                 array($id));

    //If there's no password for user in DB then...
    if (strlen($password) != 6) {
      //... remove cookie
      Mage::getSingleton('core/cookie')->delete('ZP_ID');

      return false;
    }

    return array('id' => $id, 'password' => $password);
  }

  function update_session_with_credentials ($credentials) {
    $this->user = $credentials['id'];
    $this->pass = $credentials['password'];
    $this->state = "ok";

    Mage::getSingleton('core/session')->setW2puser($this->user);
    Mage::getSingleton('core/session')->setW2ppass($this->pass);
    Mage::getSingleton('core/session')->setState($this->state);
  }
}
