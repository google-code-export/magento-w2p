<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kris
 * Date: 11-5-6
 * Time: 20:31
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_Options_Model_Copy
{
  /**
   * @var ZetaPrints_Options_Helper_Data
   */
  protected $helper;

  /**
   * Copy options
   *
   * Copy options from product with ID $srcId to all products
   * whose IDs are in $productIds
   *
   * @param  int $srcId
   * @param array $productIds
   * @return boolean
   */
  public function copy($srcId, $productIds = array())
  {
    // get options associated to src product

    $options = $this->getOptions($srcId);

    // if no options come out, return with message
    if (!count($options)) {
      $this->_getSession()->addError('Incorrect source product!');
      return false;
    }

    $productOptions = $this->getOptionsAsArray($options);

    if (!empty($productOptions)) {
      try {
        foreach ($productIds as $id) {
          $this->copyOptionsToProduct($id, $productOptions);
        }
      } catch (Exception $err) {
        $this->_getSession()->addError($err->getMessage());
        $this->_getSession()->addError('<pre>' . $err->getTraceAsString() . '</pre>');
        return false;
      }
    }

    return true;
  }

  /**
   * @param  Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Option_Collection $options
   * @return array
   */
  public function getOptionsAsArray($options)
  {
    $productOptions = array();

    foreach ($options as $option) { // loop options, remove src product data and add them to options array
      $productOptions[] = $this->helper()->getOptionArray($option);
    }
    return $productOptions;
  }

  /**
   * Get product options
   *
   * Given product ID return its associated options collection.
   *
   * @param  int $srcId
   * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Option_Collection
   */
  public function getOptions($srcId)
  {
    $options = Mage::getModel('catalog/product_option')
        ->getCollection()
        ->addTitleToResult(Mage::app()->getStore()->getId())
        ->addPriceToResult(Mage::app()->getStore()->getId())
        ->addProductToFilter($srcId)
        ->addValuesToResult();
    /* @var $options Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Option_Collection */
    return $options;
  }

  /**
   * Perform actual copy procedure
   *
   * @param  int $id - product ID to copy to
   * @param  array $productOptions - array representation of options
   * @return void
   */
  protected function copyOptionsToProduct($id, $productOptions)
  {
    /* @var $product Mage_Catalog_Model_Product */
    $product = Mage::getModel('catalog/product')->load($id);
    $product->setIsMassupdate(true);
    $product->setExcludeUrlRewrite(true);

    /* @var $product Mage_Catalog_Model_Product */
    $product->setProductOptions($productOptions); // set product options data
    $product->setCanSaveCustomOptions(!$product->getOptionsReadonly()); // make sure product knows that options have to be saved
    $product->save();
  }

  /**
   * Retrieve adminhtml session model object
   *
   * @return Mage_Adminhtml_Model_Session
   */
  protected function _getSession()
  {
    return Mage::getSingleton('adminhtml/session');
  }

  /**
   * @return ZetaPrints_Options_Helper_Data
   */
  protected function helper()
  {
    if (!$this->helper || !$this->helper instanceof ZetaPrints_Options_Helper_Data) {
      $this->helper = Mage::helper('zpoptions');
    }
    return $this->helper;
  }
}
