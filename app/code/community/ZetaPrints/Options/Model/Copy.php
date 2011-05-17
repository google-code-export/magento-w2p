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
    $db = Mage::getSingleton('core/resource')->getConnection('core_read');
    $this->_startProfiler($db);
    // get options associated to src product
    $options = $this->getOptions($srcId);

    // if no options come out, return with message
    if (!count($options)) {
      $this->_getSession()->addError('Incorrect source product! Product has no custom options or does not exist.');
      return false;
    }
    $start = microtime(true);
    $processed = 0;
    $this->addNotice('Started at ' . date('c'));
    $productOptions = $this->getOptionsAsArray($options);

    if (!empty($productOptions)) {
      $this->addNotice('Found ' . count($productOptions) . ' options.');
      try {
        foreach ($productIds as $id) {
          if ($srcId == $id) {
            continue;
          }
          $this->copyOptionsToProduct($id, $productOptions);
          $processed++;
        }
      } catch (Exception $err) {
        $this->_getSession()->addError($err->getMessage());
        $this->_getSession()->addError('<pre>' . $err->getTraceAsString() . '</pre>');
        $this->_stopProfiler($db);
        return false;
      }
    }

    $end = microtime(true) - $start;
    $this->addNotice('Ended at ' . date('c') . ', taking ' . $end . ' seconds for ' . $processed . ' product(s), ' . $end / $processed . ' sec per product.');
    $this->_stopProfiler($db);
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
    $product = Mage::getModel('catalog/product');
    /* @var Mage_Catalog_Model_Product */
    $product->reset()->load($id);
    $product->setIsMassupdate(true);
    $product->setExcludeUrlRewrite(true);

    $product->setProductOptions($productOptions); // set product options data
    $product->setCanSaveCustomOptions(!$product->getOptionsReadonly()); // make sure product knows that options have to be saved
    $this->deleteCurrentOptions($product);
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

  protected function addNotice($notice)
  {
    if (Mage::getIsDeveloperMode())
      $this->_getSession()->addNotice($notice);

    return $this;
  }

  protected function addError($error)
  {
    if (Mage::getIsDeveloperMode())
      $this->_getSession()->addError($error);

    return $this;
  }

  /**
   * @param  Mage_Catalog_Model_Product $product
   * @return void
   */
  protected function deleteCurrentOptions($product)
  {
    $option = $product->getOptionInstance();
    $optionsCollection = $option->getProductOptionCollection($product);
    $optionsCollection->walk('delete');
    $option->unsetOptions();
  }

  /**
   * @param  Zend_Db_Adapter_Abstract $db
   * @return void
   */
  protected function _startProfiler($db)
  {
    if ($db instanceof Zend_Db_Adapter_Abstract && Mage::getIsDeveloperMode()) {
      $profiler = $db->getProfiler();
      $profiler->setEnabled(true)
          ->setFilterQueryType(null);

    }
  }

  protected function _stopProfiler($db)
  {
    if ($db instanceof Zend_Db_Adapter_Abstract && Mage::getIsDeveloperMode()) {
      /** @var $profiler Zend_Db_Profiler */
      $profiler = $db->getProfiler();
      if ($profiler && $profiler->getEnabled()) {
        $totalTime = $profiler->getTotalElapsedSecs();
        $queryCount = $profiler->getTotalNumQueries();
        $insertCount = $profiler->getTotalNumQueries(Zend_Db_Profiler::INSERT);
        $updateCount = $profiler->getTotalNumQueries(Zend_Db_Profiler::UPDATE);
        $insertTime = $profiler->getTotalElapsedSecs(Zend_Db_Profiler::INSERT);
        $updateTime = $profiler->getTotalElapsedSecs(Zend_Db_Profiler::UPDATE);
        $longestTime = 0;
        $longestQuery = null;

        foreach ($profiler->getQueryProfiles() as $query) {
          if ($query->getElapsedSecs() > $longestTime) {
            $longestTime = $query->getElapsedSecs();
            $longestQuery = $query->getQuery();
          }
        }

        $msg = 'Executed ' . $queryCount . ' queries in ' . $totalTime . ' seconds' . "<br/>\n";
        $msg .= 'Executed INSERTs ' . $insertCount . ' queries in ' . $insertTime . ' seconds' . "<br/>\n";
        $msg .= 'Executed UPDATEs ' . $updateCount . ' queries in ' . $updateTime . ' seconds' . "<br/>\n";
        $msg .= 'Average query length: ' . $totalTime / $queryCount . ' seconds' . "<br/>\n";
        $msg .= 'Queries per second: ' . $queryCount / $totalTime . "<br/>\n";
        $msg .= 'Longest query length: ' . $longestTime . "<br/>\n";
        $msg .= "Longest query: \n" . $longestQuery . "\n";

        $this->_getSession()->addNotice($msg);
        $profiler->setEnabled(false);
      }
    }
  }
}
