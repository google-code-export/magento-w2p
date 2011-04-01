<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/28/11
 * Time: 7:16 PM
 */

class ZetaPrints_DistributionMap_Block_Map_Abstract
  extends Mage_Core_Block_Template
{
  /**
   * @var ZetaPrints_DistributionMap_Helper_Data
   */
  protected $helper;
  public function getMapUrl()
  {
    return $this->maphelper()->getBaseJsUrl();
  }

  public function getSensor()
  {
    return $this->maphelper()->getSensor();
  }

  public function getRegion()
  {
    return $this->maphelper()->getRegion();
  }

  public function getLanguage()
  {
    return $this->maphelper()->getLanguage();
  }

  public function getMapHeight($page)
  {
    return $this->maphelper()->getMapHeight($page);
  }

  public function getMapWidth($page)
  {
    return $this->maphelper()->getMapWidth($page);
  }


  /**
   * @return Mage_Sales_Model_Order
   */
  public function getOrder()
  {
    $order = Mage::registry('current_order');
    return $order;
  }

  /**
   * Get KML urls
   *
   * Parses all order items for product options.
   * If options found and are map options, a link is
   * build that will download KML file to user computer.
   * @return array|null
   */
  protected function getKmlUrls()
  {
    $order = $this->getOrder();
    if (!$order) {
      return array();
    }
    $order_id = $order->getRealOrderId();
    $params = array(
      'ordid' => $order_id,
      'optid' => ''
    );
    $route = 'distromap/index/kml';
    $option_ids = array();
    $items = $order->getAllVisibleItems();
    foreach ($items as $item) {
      /** @var $item Mage_Sales_Model_Order_Item
       * @var $product Mage_Catalog_Model_Product
       */
      $product = Mage::getModel('catalog/product')->load($item->getProductId());
      $options = $product->getOptions();
      foreach ($options as $opt) {
        /** @var $opt Mage_Catalog_Model_Product_Option */
        if ($this->maphelper()->isMap($opt)) {
          $id = $opt->getId();
          $proptions = $item->getProductOptions();
          $params['qiid'] = $item->getQuoteItemId();
          if (isset($proptions['options'])) {
            $proptions = $proptions['options'];
            foreach ($proptions as $custom) {
              if ($custom['option_id'] == $id) {
                $option_ids[] = array(
                  'id' => $id,
                  'value' => $custom['value']
                );
              }
            }
          }
        }
      }
    }
    $result = null;
    if (!empty($option_ids)) {
      foreach ($option_ids as $opt) {
        $params['optid'] = $opt['id'];
        $url = $this->getUrl($route, $params);
        $link = '<a href="' . $url . '" title="' . $this->__('Distribution map KML') . '">' .
                $this->__('Download KML file') . '</a>';
        $result[] = array('kml' => $link, 'option' => $opt['value']);
      }
    }
    return $result;
  }

  /**
   * Is block executed in head part of document, or loaded after page load
   * @return bool
   */
  public function isHeadScript()
  {
    $parent = $this->getParentBlock();
    if(!$parent){
      return false;
    }
    return $parent->getNameInLayout() == 'head';
  }

  /**
   * Prepare kml JS objects
   *
   * This method is fired only when viewing order details.
   * @return null|string
   */
  public function renderKml()
  {
    $request = $this->getRequest();
    $module = $request->getModuleName();
    $controller = $request->getControllerName();
    $action = $request->getActionName();
    $render = $module . '_' . $controller . '_' . $action;
    switch ($render) {
      case 'sales_order_view':
      case 'admin_sales_order_view':
        return Zend_Json_Encoder::encode($this->getKmlUrls());
        break;
    }
    return null;
  }

  public function getMapApi()
  {
    return $this->maphelper()->getMapApi();
  }

  public function getMarkerIconUrl()
  {
    $url = $this->getSkinUrl('images/pencil_marker.png');
    return $url;
  }

  /**
   * @return ZetaPrints_DistributionMap_Helper_Data
   */
  public function maphelper(){
    if(!isset($this->helper)) {
      $this->helper = parent::helper('distro_map');
    }
    return $this->helper;
  }
}
