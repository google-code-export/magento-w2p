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
  const DELETE_LINK_TPL = '<a class="zp-delete-file" href="%s">Delete</a>';
  const ADMIN_AREA_TPL = '<a class="zp-download-file" href="%s" target="_blank">%s</a> %s';
  const FRONTEND_AREA_TPL = '<span class="zp-attachment-file">%s</span> %s';

  public function getFormattedOptionValue($optionValue)
  {
    if ($this->_formattedOptionValue === null) {
      $formattedValue = array ();
      $fullValues = array();
      if (!is_array($optionValue)) {
        try {
          $optionValue = $this->_unserialize($optionValue);
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
  /**
   * Unserialise value
   * Since the code relies on exception being thrown if unserialisation fails,
   * and this is not 100% guaranteed in production mode in magento,
   * we are implementing this here.
   *
   * @param mixed $value - value that may or may not be in serialized form
   * @return mixed - unserialised value
   * @throws Exception - if userializing fails
   */
  protected function _unserialize($value)
  {
    $_value = unserialize($value);
    if (!$_value) {
      throw new Exception(print_r($value, true));
    }
    return $_value;
  }

  protected function _getOptionHtml($optionValue)
  {
    $area = Mage::getDesign()->getArea();
    if ($area == Mage_Core_Model_Design_Package::DEFAULT_AREA) {
      return $this->_getAreaHtml($optionValue, self::FRONTEND_AREA_TPL);
    }
    return $this->_getAreaHtml($optionValue, self::ADMIN_AREA_TPL);
  }

  /**
   * Format option html
   *
   * Format option html so that it allows file download.
   * @param string|array $optionValue Serialized string of option data or its data array
   * @return string
   */
  protected function _getAreaHtml($optionValue, $format = self::FRONTEND_AREA_TPL)
  {
    try {
      $value = $this->_unserialize($optionValue);
    } catch (Exception $e) {
      $value = $optionValue;
    }
    try {
      if(!is_array($value)){
        throw new Exception();
      }
      $sizes = $this->_sizes($value);
      switch ($format) {
        case self::ADMIN_AREA_TPL:
          return sprintf($format, $this->_getOptionDownloadUrl($value['url']['route'], $value['url']['params']), Mage::helper('core')->htmlEscape($value['title']), $sizes);
        break;
        default:
          return sprintf($format, Mage::helper('core')->htmlEscape($value['title']), $sizes);
        break;
      }
    } catch (Exception $e) {
      Mage::throwException(Mage::helper('catalog')->__("File options format is not valid."));
    }
  }

  /**
   * Format image sizes
   * @param array $value
   * @return string
   */
  protected function _sizes($value)
  {
    if ($value['width'] > 0 && $value['height'] > 0) {
      $sizes = $value['width'] . ' x ' . $value['height'] . ' ' . Mage::helper('catalog')->__('px.');
    } else {
      $sizes = '';
    }
    return $sizes;
  }

  protected function _getDeleteLinkHtml($value, $format)
  {
    try {
      if(!is_array($value)){
        throw new Exception();
      }
      $url = $this->_getOptionDownloadUrl($value['delete_url']['route'], $value['delete_url']['params']);
      return sprintf($format, $url);
    } catch (Exception $e) {
      Mage::throwException(Mage::helper('catalog')->__("File options format is not valid."));
    }
  }

  public function getCustomizedView($optionInfo)
  {
    try {
      if (isset($optionInfo['option_value'])) {
        $result = $this->getFormattedOptionValue($optionInfo['option_value']);
        return $result;
      }
    } catch (Exception $e) {}
    if(isset($optionInfo['value'])){
      return $optionInfo['value'];
    }
    Mage::throwException(Mage::helper('catalog')->__("File options values are not valid."));
  }
}
