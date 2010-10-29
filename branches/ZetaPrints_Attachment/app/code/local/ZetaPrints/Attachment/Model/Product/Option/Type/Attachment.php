<?php

class ZetaPrints_Attachment_Model_Product_Option_Type_Attachment extends Mage_Catalog_Model_Product_Option_Type_File
{

  public function getFormattedOptionValue($optionValue)
  {
    if ($this->_formattedOptionValue === null) {
      $formattedValue = array ();
      $fullValues = array();
      if (!is_array($optionValue)) {
        try {
          $optionValue = unserialize($optionValue);
        } catch (Exception $e) {
          return $optionValue;
        }
      }
      try {
        foreach ($optionValue as $value) {
            $value['url'] = array (	'route' => 'sales/download/downloadCustomOption',
                                    'params' => array (
                                      'id' => $this->getQuoteItemOption()->getId(),
                                      'key' => $value['secret_key']
                                    ));
            $formattedValue[] = $this->_getOptionHtml($value);
            $fullValues[] = $value;
        }
        $this->_formattedOptionValue = implode('<br/>', $formattedValue);
        $this->getQuoteItemOption()->setValue(serialize($fullValues));
      } catch (Exception $e) {
        return $optionValue;
      }
    }
    return $this->_formattedOptionValue;
  }

  protected function _getOptionHtml($optionValue)
  {
    return parent::_getOptionHtml($optionValue);
  }
}
