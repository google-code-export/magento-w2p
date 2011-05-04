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

  protected $attachments;

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

  /**
   * @param array $value
   * @return ZetaPrints_Attachments_Model_Product_Option_Type_Attachments
   */
  protected function _setFullValues($value)
  {
    if ($this->_setFullValues && $this->getConfigurationItemOption()) {
      $this->getConfigurationItemOption()->setValue(serialize($value));
    }
    return $this;
  }

  /**
   * @param array $value
   * @param string $key
   * @param string $route
   * @return array
   */
  protected function _setUrl($value, $key, $route)
  {
    if(!isset($value[$key]) && $this->getConfigurationItemOption()){
      $value[$key] = array ('route' => $route,
                            'params' => array (
                              'id' => $this->getConfigurationItemOption()->getId(),
                              'name'=> rawurlencode($value['title']),
                              'key' => $value['secret_key'],
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
    if(is_array($value)){
      return $value;
    }
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
          return sprintf($format, $this->_getOptionDownloadUrl($value['url']['route'], $value['url']['params']), Mage::helper('core')->escapeHtml($value['title']), $sizes);
        break;
        default:
          return sprintf($format, Mage::helper('core')->escapeHtml($value['title']), $sizes);
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

  protected function _prepareAttachments($request)
  {
    if ($request->getData('attachment_hash')) { // if this is main form submit we have this key in request
      if(!isset($this->attachments)){
        $product_id = $this->getProduct()->getId();
        $attachments = Mage::helper('attachments')->getSessionAttachments($product_id, false);
        $hash = $request->getData('attachment_hash');
        foreach ($attachments as $att_value) {
          // if error has occurred before there might be some hashes left,
          // so we filter to get only current files
          $h = $att_value[ZetaPrints_Attachments_Model_Attachments::ATT_HASH];
          if (in_array($h, $hash)) {
            $this->attachments[] = $att_value; // store attachments data in instance
          }
        }
      }
    }
  }

  /**
   * Set process mode
   *
   * Process mode is new flag added for 1.5 series.
   * It has 2 values currently - these are 'full' and 'lite'.
   * When full mode is used and we have AJAX attachment files,
   * we have a problem - product does not get added to cart.
   * To overcome the problem we need to set this mode to lite
   * so that Magento ignores options for which no values are passed
   * at moment of adding product to cart.
   * @param  $processMode
   * @return ZetaPrints_Attachments_Model_Product_Option_Type_Attachments
   */
  public function setProcessMode($processMode)
  {
    $request = $this->getRequest();
    $this->_prepareAttachments($request);
    if($this->attachments){
      $processMode = Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_LITE;
    }
    return parent::setProcessMode($processMode);
  }

  /**
   * Override parent method
   *
   * Since we are dealing with multiple files per option, we need to do
   * some data preparation before the option data is added to product/quote/order
   *
   * @return mixed|null|string
   */
  public function prepareForCart()
  {
    $request = $this->getRequest(); // get buy info request
    $this->_prepareAttachments($request);
    if ($this->attachments) { // if we have found any attachments in this request, prepare data
      $option = $this->getOption();
      $optionId = $option->getId();
      $buyRequest = $this->getRequest();
      // Prepare value and fill buyRequest with option
      $requestOptions = $buyRequest->getOptions();
      $orderAtt = array();
      foreach ($this->attachments as $data) {
        if($data['option_id'] != $optionId){
          continue;
        }
        $atCollection = Mage::getModel('attachments/attachments')
              ->getAttachmentCollection($data); // get all uploads for give option
        foreach ($atCollection as $att) { // collect all uploads in common array
          $orderAttValue = unserialize($att->getAttachmentValue());
          $orderAttValue['attachment_id'] = $att->getId();
          $orderAtt[] = $orderAttValue;
        }
      }
      if($orderAtt){
        $result = serialize($orderAtt);
        $requestOptions[$optionId] = $orderAtt;
      } else {
        /*
        * Clear option info from request, so it won't be stored in our db upon
        * unsuccessful validation. Otherwise some bad file data can happen in buyRequest
        * and be used later in reorders and reconfigurations.
        */
        if (is_array($requestOptions)) {
          unset($requestOptions[$optionId]);
        }
        $result = null;
      }
      $buyRequest->setOptions($requestOptions);

      // Clear action key from buy request - we won't need it anymore
      $optionActionKey = 'options_' . $optionId . '_file_action';
      $buyRequest->unsetData($optionActionKey);

      return $result;
    }

    // else pass control to parent
    return parent::prepareForCart();
  }

  /**
     * Quote item to order item copy process
     *
     * @return ZetaPrints_Attachments_Model_Product_Option_Type_Attachments
     */
    public function copyQuoteToOrder()
    {
      $quoteOption = $this->getConfigurationItemOption();
//      $quoteOption = $this->getConfigurationItemOption();
      try {
        $files = unserialize($quoteOption->getValue());
        foreach($files as $value){
          try{
            if (!isset($value['quote_path'])) {
              throw new Exception();
            }
            $quoteFileFullPath = Mage::getBaseDir() . $value['quote_path'];
            if (!is_file($quoteFileFullPath) || !is_readable($quoteFileFullPath)) {
              throw new Exception();
            }
            $orderFileFullPath = Mage::getBaseDir() . $value['order_path'];
            $dir = pathinfo($orderFileFullPath, PATHINFO_DIRNAME);
            $this->_createWriteableDir($dir);
            Mage::helper('core/file_storage_database')->copyFile($quoteFileFullPath, $orderFileFullPath);
            @copy($quoteFileFullPath, $orderFileFullPath);
          }catch(Exception $e){
            continue;
          }
        }
      } catch (Exception $e) {
        return $this;
      }
      return $this;
    }
}
