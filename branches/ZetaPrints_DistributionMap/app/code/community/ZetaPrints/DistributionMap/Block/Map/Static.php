<?php
class ZetaPrints_DistributionMap_Block_Map_Static
  extends ZetaPrints_DistributionMap_Block_Map_Abstract
{
  public function isHeadScript()
  {
    $parent = $this->getParentBlock();
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
        $link = '<a href="' . $url . '" title="Distribution map KML">Option KML file</a>';
        $result[] = array('kml' => $link, 'option' => $opt['value']);
      }
    }
    return $result;
  }

  public function getBaseGoogleUrl()
  {
    $area = Mage::getDesign()->getArea() == Mage_Core_Model_Design_Package::DEFAULT_AREA ? 'cart' : 'admin';
    $options = array();
    $options['width'] = $this->getMapWidth($area);
    $options['height'] = $this->getMapHeight($area);
    $options['sensor'] = $this->getSensor();
    $options['language'] = $this->getLanguage();

    $url = Mage::helper('distro_map')->getStaticGoogleUrl($options);
    return $url;
  }
}
