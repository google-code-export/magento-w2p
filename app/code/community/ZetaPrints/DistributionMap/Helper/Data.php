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

  protected $googleUrls = array(
    'base' => 'http://maps.google.com/maps/api/staticmap?',
    'secure' => 'https://maps.googleapis.com/maps/api/staticmap?'
  );

  protected $mapsize = '300x200';
  protected $maptype = 'roadmap';
  protected $sensor = 'false';
  protected $pathstyle = 'color:0xff0000ff|weight:3|fillcolor:0xff00007f|';

  public function getStaticMap($value) {
    $value = Zend_Json::decode($value);
    $secure = Mage::app()->getRequest()->isSecure();
    $baseurl = $secure ? $this->googleUrls['secure']: $this->googleUrls['base'];
    $language = Mage::getStoreConfig('general/locale/code');
    $sensor = Mage::getStoreConfig('google/distro_map/sensor')?'true':'false';
    $pathStyle = 'color:0xff0000ff|weight:3|fillcolor:0xff00007f';
    $path = array();
    /*
     * on static maps polygons are not auto closed
     * so we add first coordinate once more to get closed
     * outline.
     */
    $lastCoord = $value[0]['lat'] . ',' . $value[0]['lng'];
    foreach($value as $coord) {
      $path[] = $coord['lat'] . ',' . $coord['lng'];
    }
    $path[] = $lastCoord;

    $path = rawurlencode($pathStyle . '|' . implode('|', $path));

    $url = $baseurl . 'size=' . $this->getMapSize() . '&sensor=' . $sensor . '&language=' . $language . '&path=' .
           $path;
    return '<img alt="Static map" src="' . $url . '" />';
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
    $height  = Mage::getStoreConfig(ZetaPrints_DistributionMap_Block_Map_Abstract::CONFIG_PATH . 'cart_height');

    if((int)$width && (int)$height) {
      return $width . 'x' . $height;
    }

    return $this->mapsize;
  }

  public function getBaseStaticGoogleUrl()
  {
    $secure = Mage::app()->getRequest()->isSecure();
    $baseurl = $secure ? $this->googleUrls['secure']: $this->googleUrls['base'];
    return $baseurl;
  }

  public function getStaticGoogleUrl($options)
  {
    $baseurl = $this->getBaseStaticGoogleUrl();
    $size = $this->getMapSize();
    if(isset($options['width'], $options['height'])) {
      $size = $options['width'] . 'x' . $options['height'];
    }
    $sensor = isset($options['sensor'])?$options['sensor']:'false';
    $lang = isset($options['language'])?$options['language']:false;
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
}
