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
   * @var Mage_Core_Model_Store
   */
  protected $store;

  const API_VERSION = '3.4';
  protected $map_src = array(
    'secure' => 'https://maps-api-ssl.google.com/maps/api/js',
    'base' => 'http://maps.google.com/maps/api/js'
  );

  protected $pages = array(
    'product',
    'cart',
    'admin'
  );

  const CONFIG_PATH = 'google/distro_map/';

  public function _construct()
  {
    $this->store = Mage::app()->getStore();
  }

  public function getMapUrl()
  {
    $_secure = $this->getRequest()->isSecure();
    $url = $_secure ? $this->map_src['secure'] : $this->map_src['base'];
    return $url;
  }

  public function getSensor()
  {
    return $this->store->getConfig(self::CONFIG_PATH . 'sensor') ? 'true' : 'false';
  }

  public function getRegion()
  {
    return $this->store->getConfig('general/country/default');
  }

  public function getLanguage()
  {
    return $this->store->getConfig('general/locale/code');
  }

  public function getMapHeight($page)
  {
    if (!in_array($page, $this->pages)) {
      return false;
    }
    return $this->store->getConfig(self::CONFIG_PATH . $page . '_height');
  }

  public function getMapWidth($page)
  {
    if (!in_array($page, $this->pages)) {
      return false;
    }
    return $this->store->getConfig(self::CONFIG_PATH . $page . '_width');
  }


  /**
   * @return Mage_Sales_Model_Order
   */
  public function getOrder()
  {
    $order = Mage::registry('current_order');
    return $order;
  }

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
      /** @var $item Mage_Sales_Model_Order_Item */
      $product = Mage::getModel('catalog/product')->load($item->getProductId());
      $options = $product->getOptions();
      foreach ($options as $opt) {
        if (Mage::helper('distro_map')->isMap($opt)) {
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

  public function isHeadScript()
  {
    $parent = $this->getParentBlock();
    if(!$parent){
      return false;
    }
    return $parent->getNameInLayout() == 'head';
  }

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
    return self::API_VERSION;
  }

  public function getMarkerIconUrl()
  {
    $url = $this->getSkinUrl('images/pencil_marker.png');
    return $url;
  }
}
