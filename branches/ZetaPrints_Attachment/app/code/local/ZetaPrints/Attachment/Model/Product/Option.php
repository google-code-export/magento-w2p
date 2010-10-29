<?php

class ZetaPrints_Attachment_Model_Product_Option extends Mage_Catalog_Model_Product_Option
{
  const OPTION_TYPE_ATTACHMENT = 'file';
  const OPTION_GROUP_ATTACHMENT   = 'attachment';
  public function getGroupByType($type = null)
  {
    if ($type == self::OPTION_TYPE_ATTACHMENT) {
      return self::OPTION_GROUP_ATTACHMENT;
    }
    return parent::getGroupByType($type);
  }
}

