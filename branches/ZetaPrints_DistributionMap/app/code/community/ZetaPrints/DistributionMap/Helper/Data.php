<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/16/11
 * Time: 7:46 PM
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_DistributionMap_Helper_Data
  extends Mage_Core_Helper_Abstract
{

  protected $googleStaticUrls = array(
    'base' => 'http://maps.google.com/maps/api/staticmap?',
    'secure' => 'https://maps.googleapis.com/maps/api/staticmap?'
  );

  protected $googleJsUrls = array(
    'base' => 'http://maps.google.com/maps/api/js',
    'secure' => 'https://maps-api-ssl.google.com/maps/api/js',
  );

  protected $pages = array(
    'product',
    'cart',
    'admin'
  );

  /**
   * @var Mage_Core_Model_Store
   */
  protected $store;

  const CONFIG_PATH = 'google/distro_map/';

  protected $mapsize = '300x200';
  protected $maptype = 'roadmap';
  protected $sensor = 'false';
  protected $pathstyle = 'color:0xff0000ff|weight:3|fillcolor:0xff00007f|';

  public function getStaticMap($value)
  {
    $value = Zend_Json::decode($value);
    $secure = Mage::app()->getRequest()->isSecure();
    $baseurl = $secure ? $this->googleStaticUrls['secure'] : $this->googleStaticUrls['base'];
    $language = Mage::getStoreConfig('general/locale/code');
    $sensor = Mage::getStoreConfig('google/distro_map/sensor') ? 'true' : 'false';
    $pathStyle = 'color:0xff0000ff|weight:3|fillcolor:0xff00007f';
    $path = array();
    /*
     * on static maps polygons are not auto closed
     * so we add first coordinate once more to get closed
     * outline.
     */
    $lastCoord = $value[0]['lat'] . ',' . $value[0]['lng'];
    foreach ($value as $coord) {
      $path[] = $coord['lat'] . ',' . $coord['lng'];
    }
    $path[] = $lastCoord;

    $path = rawurlencode($pathStyle . '|' . implode('|', $path));

    $url = $baseurl . 'size=' . $this->getMapSize() . '&sensor=' . $sensor . '&language=' . $language . '&path=' .
           $path;
    return '<img alt="' . $this->__('Static map') . '" src="' . $url . '" />';
  }

  /**
   * @param Mage_Catalog_Model_Product_Option $option
   */
  public function isMap($option)
  {
    return strpos($option->getTitle(), '***kml***') !== false;
  }

  protected function getMapSize()
  {
    $width = Mage::getStoreConfig(ZetaPrints_DistributionMap_Block_Map_Abstract::CONFIG_PATH . 'cart_width');
    $height = Mage::getStoreConfig(ZetaPrints_DistributionMap_Block_Map_Abstract::CONFIG_PATH . 'cart_height');

    if ((int)$width && (int)$height) {
      return $width . 'x' . $height;
    }

    return $this->mapsize;
  }

  public function getBaseStaticGoogleUrl()
  {
    $secure = Mage::app()->getRequest()->isSecure();
    $baseurl = $secure ? $this->googleStaticUrls['secure'] : $this->googleStaticUrls['base'];
    return $baseurl;
  }

  public function getStaticGoogleUrl($options)
  {
    $baseurl = $this->getBaseStaticGoogleUrl();
    $size = $this->getMapSize();
    if (isset($options['width'], $options['height'])) {
      $size = $options['width'] . 'x' . $options['height'];
    }
    $sensor = isset($options['sensor']) ? $options['sensor'] : 'false';
    $lang = isset($options['language']) ? $options['language'] : false;
    $style = $this->pathstyle;

    $url = $this->makeStaticUrl($baseurl, $size, $sensor, $lang, $style);
    return $url;
  }

  protected function makeStaticUrl($baseurl, $size, $sensor, $lang, $style)
  {
    $path = rawurlencode($style);
    $url = $baseurl . 'size=' . $size . '&sensor=' . $sensor . '&language=' . $lang . '&path=' . $path;
    return $url;
  }

  /**
   * @return Mage_Core_Model_Store
   */
  protected function getStore()
  {
    if (!isset($this->store)) {
      $this->store = Mage::app()->getStore();
    }
    return $this->store;
  }

  public function getBaseJsUrl()
  {
    $url = Mage::app()->getRequest()->isSecure() ? $this->googleJsUrls['secure'] : $this->googleJsUrls['base'];
    return $url;
  }

  public function getSensor()
  {
    return $this->getStore()->getConfig(self::CONFIG_PATH . 'sensor') ? 'true' : 'false';
  }

  /**
   * Current API version used
   * @return string
   */
  public function getMapApi()
  {
    return ZetaPrints_DistributionMap_Model_Map::API_VERSION;
  }

  /**
   * Get map width
   * Returns map width for passed page, if page is valid else returns product page width
   * @param  string $page
   * @return string
   */
  public function getMapWidth($page)
  {
    if (!in_array($page, $this->pages)) {
      return $this->getStore()->getConfig(self::CONFIG_PATH . 'product_width');
    }
    return $this->getStore()->getConfig(self::CONFIG_PATH . $page . '_width');
  }

  /**
   * Get map height
   * Returns map height for passed page, if page is valid else returns product page height
   * @param  string $page
   * @return string
   */
  public function getMapHeight($page)
  {
    Mage::log($page);
    if (!in_array($page, $this->pages)) {
      return $this->getStore()->getConfig(self::CONFIG_PATH . 'product_height');
    }
    return $this->getStore()->getConfig(self::CONFIG_PATH . $page . '_height');
  }

  public function getLanguage()
  {
    return $this->getStore()->getConfig('general/locale/code');
  }

  public function getRegion()
  {
    return $this->getStore()->getConfig('general/country/default');
  }

  /**
   * Product page hint
   * @return string
   */
  public function getProductPageHint()
  {
    return $this->getStore()->getConfig(self::CONFIG_PATH . 'hint_text');
  }

  /**
   * Initial map zoom
   *
   * Make sure it is in allowed range
   * @return int
   */
  public function getInitialZoom()
  {
    $zoom = (int)$this->getStore()->getConfig(self::CONFIG_PATH . 'map_zoom');
    if($zoom < 0) {
      $zoom = 0;
    }elseif($zoom > 20) {
      $zoom = 20;
    }
    return $zoom;
  }

  /**
   * Initial latitude
   * @return float
   */
  public function getInitialLat()
  {
    return (float)$this->getStore()->getConfig(self::CONFIG_PATH . 'map_lat');
  }

  /**
   * Initial longitude
   * @return float
   */
  public function getInitialLng()
  {
    return (float)$this->getStore()->getConfig(self::CONFIG_PATH . 'map_lng');
  }
}
