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
