<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ZetaPrints_Attachments_Model_Product_Option_Type_Attachments
 extends Mage_Catalog_Model_Product_Option_Type_File
{

  protected $_setFullValues = false;

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
        $s = $this;
        foreach ($optionValue as $value) {
          $value = $this->_setDeleteUrl($value);
          $value = $this->_setDownloadUrl($value);
          $formattedValue[] = $this->_getOptionHtml($value);
          $fullValues[] = $value;
        }
        $this->_formattedOptionValue = implode('<br/>', $formattedValue);
        $this->_setFullValues($fullValues);
      } catch (Exception $e) {
        return $optionValue;
      }
    }
    return $this->_formattedOptionValue;
  }

  protected function _setFullValues($value)
  {
    if ($this->_setFullValues && $this->getQuoteItemOption()) {
      $this->getQuoteItemOption()->setValue(serialize($value));
    }
    return $this;
  }

  protected function _setUrl($value, $key, $route)
  {
    if(!isset($value[$key]) && $this->getQuoteItemOption()){
      $value[$key] = array ('route' => $route,
                            'params' => array (
                            'id' => $this->getQuoteItemOption()->getId(),
                            'key' => $value['secret_key']
                           ));
      $this->_setFullValues = true;
    }
    return $value;
  }
  protected function _setDeleteUrl($value, $route = 'attachments/index/delete')
  {
    return $this->_setUrl($value, 'delete_url', $route);
  }

  protected function _setDownloadUrl($value, $route = 'sales/download/downloadCustomOption')
  {
    return $this->_setUrl($value, 'url', $route);
  }

  protected function _getOptionHtml($optionValue)
  {
    $area = Mage::getDesign()->getArea();
    if ($area == Mage_Core_Model_Design_Package::DEFAULT_AREA) {
      return $this->_getFrontendHtml($optionValue);
    }
    return parent::_getOptionHtml($optionValue);
  }

  protected function _getFrontendHtml($optionValue)
  {
    try {
      $value = unserialize($optionValue);
    } catch (Exception $e) {
      $value = $optionValue;
    }
    try {
      if ($value['width'] > 0 && $value['height'] > 0) {
        $sizes = $value['width'] . ' x ' . $value['height'] . ' ' . Mage::helper('catalog')->__('px.');
      } else {
        $sizes = '';
      }
      $return = sprintf('<span class="zp-attachment-file">%s</span> %s ', Mage::helper('core')->htmlEscape($value['title']), $sizes);
      return $return; // . $this->_getDeleteLink($optionValue); // add this to have delete link
    } catch (Exception $e) {
      Mage::throwException(Mage::helper('catalog')->__("File options format is not valid."));
    }
  }

  protected function _getDeleteLink($value)
  {
    try {
      $url = $this->_getOptionDownloadUrl($value['delete_url']['route'], $value['delete_url']['params']);
      $format = '<a class="zp-delete-file" href="%s">Delete</a>';
      return sprintf($format, $url);
    } catch (Exception $e) {
      Mage::throwException(Mage::helper('catalog')->__("File options format is not valid."));
    }
  }

  public function getCustomizedView($optionInfo)
  {
    try {
      $result = $this->getFormattedOptionValue($optionInfo['option_value']);
      return $result;
    } catch (Exception $e) {
      return $optionInfo['value'];
    }
  }
}
