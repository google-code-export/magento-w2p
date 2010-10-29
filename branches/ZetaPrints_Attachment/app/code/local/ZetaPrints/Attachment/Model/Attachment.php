<?php

class ZetaPrints_Attachment_Model_Attachment extends Mage_Core_Model_Abstract
{

  const PR_ID     = 'product_id';
  const ORD_ID    = 'order_id';
  const OPT_ID    = 'option_id';
  const ATT_HASH  = 'attachment_hash';
  const ATT_VALUE = 'attachment_value';

  const ATT_SESS  = 'zp_att_'; // session variable prefix

  public function _construct()
  {
    parent::_construct();
    $this->_init('attachment/attachment');
  }

  /**
   * Reset model
   *
   * @return ZetaPrints_Attachment_Model_Attachment
   */
  public function reset()
  {
    $this->setData(array ());
    $this->setOrigData();
    return $this;
  }

  /**
   * Add attachment
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
   * - attachment_value
   * - attachment_hash - required
   *
   * All required fields have to be passed
   *
   * @param array $data - table fields and values
   * @return ZetaPrints_Attachment_Model_Attachment
   */
  public function addAtachment($data)
  {
    if(isset($data[self::ATT_VALUE])){
      $value = $data[self::ATT_VALUE];
      unset($data[self::ATT_VALUE]);
    }
    $collection = $this->getAttachmentCollection($data);

    if (!empty($collection) && $value != null) { // if there is already such product / hash combo
      //      $attachment->load($collection[0]);
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
        if (false !== $firstEmptyAtt) { // if there is attachment added from cart with empty value
          $this->load($firstEmptyAtt);
          $this->setAttachmentValue($value)->save();
        } else { // this is new attachment for this combo
          $this->setData($data)->setAttachmentValue($value)
                ->save();
        }
      }else {
        $this->load($found);
      }
    } else { // there is no attachment for this combo yet so we set our values and move on.
      $this->setData($data)->setAttachmentValue($value)
            ->save();
    }
    return $this;
  }

  /**
   * Get collection of attachments filtered
   * @see self::addAttachment for possibe filter keys
   * @param array $filters
   */
  public function getAttachmentCollection(array $filters = array())
  {
    $collection = $this->getCollection();
    $collection->addFieldToSelect('*');
    foreach ($filters as $field => $value) {
      $collection->addFieldToSelect($field, $value);
    }
    $collection->load();
    return $collection;
  }
}
