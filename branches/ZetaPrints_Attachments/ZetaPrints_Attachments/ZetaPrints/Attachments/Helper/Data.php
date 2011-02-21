<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ZetaPrints_Attachments_Helper_Data extends Mage_Core_Helper_Abstract
{
  public function getSessionAttachments($product_id, $clear_session = true)
  {
    $attachments = array();
    foreach (Mage::getSingleton('core/session')->getData() as $key => $value) {
      if (strpos($key, ZetaPrints_Attachments_Model_Attachments::ATT_SESS) !== false) { // this is attachment data
        if ($value[ZetaPrints_Attachments_Model_Attachments::PR_ID] == $product_id) { // and is for current product
          $attachments[] = $value;
          if($clear_session){
            Mage::getSingleton('core/session')->unsetData($key);
          }
        }
      }
    }

    return $attachments;
  }

  public function setSessionAttachments($product_id, $hash)
  {
    $sess = Mage::getSingleton('core/session');
    foreach ($hash as $opt_id => $hash_value) {
      $key = ZetaPrints_Attachments_Model_Attachments::ATT_SESS . '_' . $hash_value; // make unique option key
      $sess->setData($key, array( // save it in session
                                ZetaPrints_Attachments_Model_Attachments::PR_ID => $product_id,
                                ZetaPrints_Attachments_Model_Attachments::OPT_ID => $opt_id,
                                ZetaPrints_Attachments_Model_Attachments::ATT_HASH => $hash_value
                           ));
    }
  }
}
