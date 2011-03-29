<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/22/11
 * Time: 5:48 PM
 */

class ZetaPrints_DistributionMap_Model_Events_Observer
{
  protected $map_options;
  protected $map_collection;

  /**
   * Get any options that could be maps
   * @param Varien_Event_Observer $observer
   * @return void
   */
  public function getQuoteMaps(Varien_Event_Observer $observer)
  {
    $item = $observer->getEvent()->getItem();
    /** @var $item Mage_Sales_Model_Quote_Item */
    /** @var $product Mage_Catalog_Model_Product */
    $product = $item->getProduct();
    $options = $product->getOptions();
    $maps = $this->_addQuoteItemId($this->_getMaps($options), $item);
    $this->_saveMaps($maps);
    // $this->_updateOptions();
  }

  /**
   * @param array $options
   * @return array
   */
  protected function _getMaps(array $options)
  {
    $maps = array();
    foreach ($options as $option) {
      $map = $this->_getMap($option);
      if($map != false){
        $maps[] = $map;
      }
    }
    return $maps;
  }

  /**
   * @param  Mage_Catalog_Model_Product_Option $option
   * @return string | false
   */
  protected function _getMap($option)
  {
    $map = false;
    if ($option instanceof Mage_Catalog_Model_Product_Option && $this->_isMap($option)) {
      $custom_option = $option->getProduct()
          ->getCustomOption('option_' . $option->getId());
      if(!$custom_option) {
        return $map; // option not added, bail out
      }
      $map = array(
        'map' => $custom_option->getValue(),
        'option' => $option->getId()
      );
      $this->map_options[] = $custom_option;
    }
    return $map;
  }

  /**
   * @param  array $maps
   * @param  Mage_Sales_Model_Quote_Item $item
   * @return array
   */
  protected function _addQuoteItemId($maps, $item)
  {
    $result = array();
    foreach ($maps as $map) {
      $result[] = array(
        ZetaPrints_DistributionMap_Model_Map::COORDS => $map['map'],
        ZetaPrints_DistributionMap_Model_Map::QUOTID => $item->getId(),
        ZetaPrints_DistributionMap_Model_Map::OPTID  => $map['option'],
      );
    }
    return $result;
  }

  /**
   * @param array $maps
   * @return void
   */
  protected function _saveMaps(array $maps)
  {
    /** @var $collection ZetaPrints_DistributionMap_Model_Mysql4_Map_Collection */
    $collection = Mage::getModel('distro_map/map')->getCollection();
    /** @var $helper ZetaPrints_DistributionMap_Helper_Data */
    foreach ($maps as $map) {
      //      $data = $helper->parseRawMapData($map);
      $collection->addItem(Mage::getModel('distro_map/map')->setMapData($map));
    }
    $collection->save();
    $this->map_collection = $collection;
  }

  /**
   * Update quote options
   * Setting option value to be a static map image.
   * @return void
   */
  protected function _updateOptions()
  {
    $options = $this->map_options;
    //    $collection = $this->map_collection;
    foreach ($options as $option) {
      $value = $option->getValue();
      /** @var $option Mage_Sales_Model_Quote_Item_Option */
      $option->setData('custom_view', 1);
      $option->setData('value', Mage::helper('distro_map')->getStaticMap($value));
      $option->save();
    }
  }

  public function addOrderId(Varien_Event_Observer $observer)
  {
    $order = $observer->getEvent()->getDataObject();
    /** @var $order Mage_Sales_Model_Order  */
    $items = $order->getAllItems();
    foreach ($items as $item) {
      $this->_addOrderId($item);
    }
  }

  protected function _addOrderId(Mage_Sales_Model_Order_Item $item)
  {
    $product = $item->getProduct(); // get product
    $product_options = $product->getOptions();
    $order_id = $item->getOrder()->getRealOrderId(); // get order id as shown to user
    if (is_array($product_options)) {
      foreach ($product_options as $option) {
        /** @var $option Mage_Catalog_Model_Product_Option_Type_Default */
        if ($this->_isMap($option)) {
          $quote_item_id = $item->getQuoteItemId();
          /** @var $collection ZetaPrints_DistributionMap_Model_Mysql4_Map_Collection */
          $collection = Mage::getModel('distro_map/map')->getCollection();
          $collection->addFieldToFilter(ZetaPrints_DistributionMap_Model_Map::QUOTID, $quote_item_id)->load();
          foreach ($collection->getItems() as $map) {
            $map->setData(ZetaPrints_DistributionMap_Model_Map::ORDERID, $order_id);
          }
          $collection->save();
        }
      }
    }
  }

  /**
   * @param Mage_Catalog_Model_Product_Option $option
   */
  protected function _isMap($option)
  {
    return Mage::helper('distro_map')->isMap($option);
  }
}
