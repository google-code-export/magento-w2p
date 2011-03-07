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

  /**
   * Add appropriate condition to selection
   *
   * @param  integer $old - Old files period
   * @param  integer $orphaned - Orphaned files period
   * @param Varien_Db_Select $select - DB Select Object
   * @return Varien_Db_Select
   */
  public function setFileDeleteConditions($old, $orphaned, Varien_Db_Select $select)
  {
    $periodField = ZetaPrints_Attachments_Model_Attachments::ATT_CREATED; // table field 'created'
    $orderField = ZetaPrints_Attachments_Model_Attachments::ORD_ID; // table field 'order_id'
    $orphanedWhere = "DATE_SUB(CURDATE(),INTERVAL ? DAY) > `{$periodField}` AND `{$orderField}` IS NULL"; // orphaned condition
    $oldWhere = "DATE_SUB(CURDATE(),INTERVAL ? DAY) > `{$periodField}`"; // old condition

    if ($old && $orphaned) { // if both periods are greater than 0 - add both conditions

      if ($orphaned < $old) { // if $orphaned period is sooner then $old, add them both
        $select->where($orphanedWhere, $orphaned)
              ->orWhere($oldWhere, $old);
      } else { // else add just old
        $select->where($oldWhere, $old);
      }

    } else if ($orphaned) { // else add which ever is set
      $select->where($orphanedWhere, $orphaned);
    } else {
      $select->where($oldWhere, $old);
    }

    return $select;
  }

  /**
   * Delete file ID from order details
   *
   * @param  int $orderId
   * @param ZetaPrints_Attachments_Model_Attachments $att
   * @return bool
   */
  public function deleteFromOrder($orderId, ZetaPrints_Attachments_Model_Attachments $att)
  {
    try {
      $order = Mage::getModel('sales/order')->loadByIncrementId($orderId); // get order
      $items = $order->getAllItems(); // and order items
      $value = unserialize($att->getData(ZetaPrints_Attachments_Model_Attachments::ATT_VALUE));
      $secret = $value['secret_key'];
      $unset = array();
      if (count($items)) {
        foreach ($items as $item) {
          $found = false; // for each item we want to delete only one file
          $productOptions = unserialize($item->getData('product_options'));
          if (isset($productOptions)) {
            $option_id = $att->getData(ZetaPrints_Attachments_Model_Attachments::OPT_ID);
            if (isset($productOptions['options'])) { // if we have options added to  this order we check them
              foreach ($productOptions['options'] as $key => $option) {
                
                if ($option_id == $option['option_id']) { // if any of those is our option id, delete it.
                  $opt_value = unserialize($option['option_value']);
                  foreach($opt_value as $okey => $file){ // loop all files and delete the one that matches
                    if($file['secret_key'] == $secret && !$found){
                      $found = true;
                      unset($opt_value[$okey]);
                      $unset[] = $file['title'];
                    }
                  }

                  if(empty($opt_value)){ // if there are no more files, remove option
                    unset($productOptions['options'][$key]);
                    if (isset($productOptions['info_buyRequest']['options'][$option_id])) {
                      unset($productOptions['info_buyRequest']['options'][$option_id]);
                    }
                  }else{
                    // update product options
                    $productOptions['options'][$key]['option_value'] = serialize($opt_value);
                    if(isset($productOptions['info_buyRequest']['options'][$option_id])){ // update info_buyRequest
                      foreach($productOptions['info_buyRequest']['options'][$option_id] as $okey => $file){
                        if($file['secret_key'] == $secret){
                          unset($productOptions['info_buyRequest']['options'][$option_id][$okey]);
                        }
                      }
                    }
                  }

                } // end if any of those is our option id, delete it.

              } // end foreach $productOptions['options']

            } // end if $productOptions['options']
            $item->setData('product_options', serialize($productOptions))->save(); // set back data and save item
          } // end if isset $productOptions
        } // end foreach $items
        if(!empty($unset)){
          $msg = $this->__('Deleted files: ');
          $order->addStatusToHistory(false, $msg . implode(',', $unset))->save(); // save order just in case
        }
        return true;
      } // end if $items
    } catch (Exception $e){
      Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
    }
    return false;
  }
}
