<?php
/**
 * Description of Fixedprices
 *
 * @author pp
 */
class ZetaPrints_Fixedprices_Block_Fixedprices
  extends Mage_Core_Block_Template
{
  /**
   * Array of disabled ids
   * @var array
   */
  protected $fqids;

  /**
   * Get checkout session
   * @return Mage_Checkout_Model_Session
   */
  protected function _getCartSession ()
  {
    return Mage::getSingleton('checkout/session');
  }

  public function getDisabledQtysText ()
  {
    $ids = $this->getDisabledQtys();
    $result = '';
    if($ids){
      $result = '"';
      $result .= implode('","', $ids);
      $result .= '"';
    }
    return $result;
  }

  public function shouldDisableQty ()
  {
    $ids = $this->getDisabledQtys();
    return !empty ($ids);
  }

  public function getDisabledQtys()
  {
    if(!isset ($this->fqids)){
      $session = $this->_getCartSession();
      $ids = $session->getData(ZetaPrints_Fixedprices_Model_Events_Observers_Fixedprices::COOKIE_NAME);
      if($ids){
        $this->fqids = explode(',', $ids);
      }
    }
    return $this->fqids;
  }
}