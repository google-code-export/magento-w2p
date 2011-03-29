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
    return $this->store->getConfig(self::CONFIG_PATH . 'sensor')?'true':'false';
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
    if(!in_array($page, $this->pages)){
      return false;
    }
    return $this->store->getConfig(self::CONFIG_PATH . $page . '_height');
  }

  public function getMapWidth($page)
  {
    if(!in_array($page, $this->pages)){
      return false;
    }
    return $this->store->getConfig(self::CONFIG_PATH . $page . '_width');
  }
}
