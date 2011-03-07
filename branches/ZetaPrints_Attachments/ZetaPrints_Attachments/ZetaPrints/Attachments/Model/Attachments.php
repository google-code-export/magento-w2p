<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ZetaPrints_Attachments_Model_Attachments
  extends Mage_Core_Model_Abstract
{

  const PR_ID     = 'product_id';
  const ORD_ID    = 'order_id';
  const OPT_ID    = 'option_id';
  const ATT_ID    = 'attachment_id';
  const ATT_HASH  = 'attachment_hash';
  const FILE_HASH = 'file_hash';
  const ATT_VALUE = 'attachment_value';
  const ATT_CODE  = 'use_ajax_upload';
  const ATT_CREATED = 'created';

  const ATT_SESS  = 'zp_att_'; // session variable prefix

  public function _construct()
  {
    parent::_construct();
    $this->_init('attachments/attachments');
  }

  /**
   * Reset model
   *
   * @return ZetaPrints_Attachments_Model_Attachments
   */
  public function reset()
  {
    $this->setData(array ());
    $this->setOrigData();
    return $this;
  }

  /**
   * Add attachments
   *
   * Since the idea is to have all this happen asynchronously
   * we need a way to add attachments prior order creation and
   * post order creation. When method is invoked, we first check
   * if product id / hash combo is already present - if yes then
   * some attachments are added, then we check if these have values
   * (which are the file upload in serialised form), collect the values
   * and compare them to what we have passed as value. If there is
   * a match then we are uploading the same file for same order, so
   * we do nothing. If there is no match and we have passed value
   * we try to update first attachment that has empty value, if there
   * isn't one, then we just create new attachment.
   * If there is no product id / hash combo that matches, we too
   * create new attachment.
   *
   * Possible fields in $data:
   *
   * - product_id - required
   * - order_id
   * - option_id - required
   * - attachments_value
   * - attachments_hash - required
   *
   * All required fields have to be passed
   *
   * @param array $data - table fields and values
   * @return ZetaPrints_Attachments_Model_Attachments
   */
  public function addAtachment($data)
  {
    /*
    if(isset($data[self::ATT_VALUE])){
      $value = $data[self::ATT_VALUE];
      $check = md5($value); // hash file info, for same files it should always be the same
      $data[self::FILE_HASH] = $check;
      unset($data[self::ATT_VALUE]);
    }
    $collection = $this->getAttachmentCollection($data);
    $data[self::ATT_VALUE] = $value;
    if ($collection->count() > 0 && $value != null) { // if there is already such product / hash combo
      //      $attachments->load($collection[0]);
      $atValues = array ();
      foreach ($collection as $att) {
        $atId = $att->getId();
        $atValues[$atId] = $att->getData(self::FILE_HASH);
      }
      $found = array_search($check, $atValues);
      if (false === $found) { // if no option value is set then we are behind the add to cart event, so add the value
        $firstEmptyAtt = array_search(null, $atValues);
        if (false !== $firstEmptyAtt) { // if there is attachments added from cart with empty value
          $this->load($firstEmptyAtt);
          $this->setData($data)->save();
        } else { // this is new attachments for this combo
          $this->setData($data)->save();
        }
      }else {
        $this->load($found);
      }
    } else { // there is no attachments for this combo yet so we set our values and move on.
      $this->setData($data)->save();
    }
     */
    if(!isset($data[self::PR_ID], $data[self::ATT_VALUE], $data[self::ATT_HASH], $data[self::OPT_ID])){
      throw new InvalidArgumentException('Data provided misses mandatory fields.');
    }
    $this->setData($data)->save();
    return $this;
  }

  public function save()
  {
    if(!$this->hasData(self::ATT_CREATED)){
      $this->setData(self::ATT_CREATED, new Zend_Db_Expr('NOW()'));
    }
    if(!$this->getData(self::FILE_HASH)){ // precaution to make sure that hash value is set
      $file_hash = $this->getData(self::ATT_VALUE);
      $this->setData(self::FILE_HASH, md5($file_hash));
    }
    parent::save();
  }

  /**
   * Get collection of attachmentss filtered
   * @see self::addAttachment for possibe filter keys
   * @param array $filters
   * @return ZetaPrints_Attachments_Model_Mysql4_Attachments_Collection
   */
  public function getAttachmentCollection(array $filters = array())
  {
    $collection = $this->getCollection();
    /* @var $collection ZetaPrints_Attachments_Model_Mysql4_Attachments_Collection */
    foreach ($filters as $field => $value) {
      $collection->addFieldToFilter($field, $value);
    }
    $collection->load();
    return $collection;
  }

  /**
   * Delete uploaded file
   * Use this function to delete uploaded file.
   * @param array $value
   * @return ZetaPrints_Attachments_Model_Attachments
   */
  public function deleteFile($value = null)
  {
//    $this->rehashAttachments();
    if($value == null){
      $value = unserialize($this->getAttachmentValue());
    }

    if($this->getData(self::ORD_ID)){ // if this is part of an order and we fail to update it
      if(!Mage::helper('attachments')->deleteFromOrder($this->getData(self::ORD_ID), $this)){
        return $this; // don't delete
      }
    }

    if(!$this->isLastReference()){
      return $this->delete();
    }

    $filePath = Mage::getBaseDir() . $value['order_path'];
    if (is_file($filePath) && is_writable($filePath)) {
      @unlink($filePath);
    }
    $filePath = Mage::getBaseDir() . $value['quote_path'];
    if (is_file($filePath) && is_writable($filePath)) {
      @unlink($filePath);
    }
    $this->delete();
    return $this;
  }

  public function addOrderId($order_id)
  {
    if($this->hasData(self::ORD_ID) && $this->getData(self::ORD_ID))
    {
      return true;
    }
    $this->setData(self::ORD_ID, (int)$order_id)->save();
  }

  public function loadFromOptionArray($option)
  {
    if(!is_array($option)){
      try {
        $option = unserialize($option);
      } catch (Exception $e) {
        return false;
      }
    }
    $value = null;
    if(is_array($option) && isset($option['option_value'])){
      $value = unserialize($option['option_value']);
    }
    if(is_array($value)){
      $filters = array();
      foreach ($value as $att) {
        $id = isset($att['attachment_id'])? $att['attachment_id']: null;
        if($id !== null){
          $filters['attachment_id'][] = $id;
        }
      }
      $collection = $this->getAttachmentCollection($filters);
      return $collection;
    }
    return false;
  }

  /**
   * Rehash file hash
   *
   * Get all attachments and make sure their file hash is up to date.
   * If $emptyOnly is false all attachments will be rechashed, else
   * only ones that have no hashes will be rehashed.
   *
   * @param bool $emptyOnly
   * @return void
   */
  protected function rehashAttachments($emptyOnly = true)
  {
    $filters = array();
    if($emptyOnly){
      // only get fields with null
      $filters[self::FILE_HASH] = array('null' => true);
    }
    $collection = $this->getAttachmentCollection($filters)->rehashFiles();
  }

  /**
   * Is this attachment last reference to file
   * @return bool
   */
  public function isLastReference()
  {
    $this->save(); // make sure current file is written to DB
    $filters = array(self::FILE_HASH => $this->getData(self::FILE_HASH));
    $count = $this->getAttachmentCollection($filters)->count();
    $return = ($count == 1); // if collection has 1 item, then this is last one;
    return $return;
  }

  /**
   * Detach file from session
   *
   * Purpose of this is prevent adding to order files
   * that are marked as deleted from frontend user.
   * @return void
   */
  public function detachFromSession()
  {
    $this->setData(self::ATT_HASH, '');
    return $this->save();
  }
}
