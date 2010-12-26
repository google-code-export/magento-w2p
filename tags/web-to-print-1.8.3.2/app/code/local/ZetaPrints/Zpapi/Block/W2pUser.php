<?php
/**
  * Project	magento-w2p
  * Author	Pham Tri Cong <phtcong@gmail.com>
  *  Issue 2: Register a new user
  * 	+ Update Magento DB: add field for UserID (GUID) and a generated password
  *	+ Update session parameters to store UserID and password for unregistered users
  *	+ Send an HTTP GET request to ZP prior to referring the user to any ZP UI.
  *	+ Store user ID and password in user profile or session.
  * Issue 3:
  *	1. Calculate MD5 hash of user password and current user IP address
  *	2. Craft the iframe src URL using TemplateID for the selected product,
  *	UserID, the hash and URL of the page the user will be returned to from ZP
  *	3. Show the IFRAME
  */
class ZetaPrints_Zpapi_Block_W2pUser extends Mage_Catalog_Block_Product_Abstract
{
	function init(){
		$this->logic = Mage::getModel('zpapi/w2puser'); 
		$ret = $this->logic->autoRegister() ;
		return "<br/>ret[$ret],IP=[" . $_SERVER["REMOTE_ADDR"] . "],UserId=[" . $this->getW2pUserId() ."],Pass=[" . $this->getW2pPass() ."],Role=[" . $this->getRole() . "],Register=[" . $this->getW2pState() . "]";
	}
	function order($id){
		$this->logic = Mage::getModel('zpapi/w2puser'); 
		return $this->logic->order($id);
	}
	function getW2pUserId(){
		return Mage::getModel('zpapi/w2puser')->getW2pUserId();
	}
	function getW2pPass(){
		return Mage::getModel('zpapi/w2puser')->getW2pPass();
	}
	function getW2pState(){
		return Mage::getModel('zpapi/w2puser')->getW2pState();
	}
	function getRole(){
		return Mage::getModel('zpapi/w2puser')->getRole();
	}
	function getHash(){
		if ((strpos($_SERVER["REMOTE_ADDR"],"192") !== false)
		||(strpos($_SERVER["REMOTE_ADDR"],"127") !== false)){
			return md5($this->getW2pPass() . "113.22.17.21");
		}
		return md5($this->getW2pPass() . $_SERVER["REMOTE_ADDR"]);
		//return md5($this->getW2pPass() . "118.71.147.202");
	}
	
}
?>