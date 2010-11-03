<?php

class ZetaPrints_Attachments_Model_Attachments extends Mage_Core_Model_Abstract
{

  const PR_ID     = 'product_id';
  const ORD_ID    = 'order_id';
  const OPT_ID    = 'option_id';
  const ATT_HASH  = 'attachment_hash';
  const ATT_VALUE = 'attachment_value';
  const ATT_CODE  = 'use_ajax_upload';

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
    if(isset($data[self::ATT_VALUE])){
      $value = $data[self::ATT_VALUE];
      unset($data[self::ATT_VALUE]);
    }
    $collection = $this->getAttachmentCollection($data);

    if ($collection->count() > 0 && $value != null) { // if there is already such product / hash combo
      //      $attachments->load($collection[0]);
      $atValues = array ();
      $check = md5($value);
      foreach ($collection as $att) {
        $atValue = $att->getAttachmentValue();
        $atId = $att->getId();
        $atValues[$atId] = md5($atValue);
      }
      $found = array_search($check, $atValues);
      if (false === $found) { // if no option value is set then we are behind the add to cart event, so add the value
        $firstEmptyAtt = array_search(null, $atValues);
        if (false !== $firstEmptyAtt) { // if there is attachments added from cart with empty value
          $this->load($firstEmptyAtt);
          $this->setAttachmentValue($value)->save();
        } else { // this is new attachments for this combo
          $this->setData($data)->setAttachmentValue($value)
                ->save();
        }
      }else {
        $this->load($found);
      }
    } else { // there is no attachments for this combo yet so we set our values and move on.
      $this->setData($data)->setAttachmentValue($value)
            ->save();
    }
    return $this;
  }

  /**
   * Get collection of attachmentss filtered
   * @see self::addAttachment for possibe filter keys
   * @param array $filters
   */
  public function getAttachmentCollection(array $filters = array())
  {
    $collection = $this->getCollection();
    $collection->addFieldToSelect('*');
    /* @var $collection ZetaPrints_Attachments_Model_Mysql4_Attachment_Collection */
    foreach ($filters as $field => $value) {
      $collection->addFieldToFilter($field, $value);
    }
    $collection->load();
    return $collection;
  }

  public function deleteFile($value)
  {
    $filePath = Mage::getBaseDir() . $value['order_path'];
    if (!is_file($filePath) || !is_readable($filePath)) {
      // try get file from quote
      $filePath = Mage::getBaseDir() . $value['quote_path'];
      if (!is_file($filePath) || !is_readable($filePath)) {
        return;
      }
    }
    @unlink($filePath);
    if(isset($value['attachment_id'])){
      try {
        $this->load($value['attachment_id'])->delete();
      } catch (Exception $e) {
      }
    }
  }
}
