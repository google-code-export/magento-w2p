<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/16/11
 * Time: 7:46 PM
 * To change this template use File | Settings | File Templates.
 */
 
class ZetaPrints_DistributionMap_Model_Mysql4_Map
  extends Mage_Core_Model_Mysql4_Abstract
{

  /**
   * Resource initialization
   */
  protected function _construct()
  {
    $this->_init('distro_map/distro_maps', 'entity_id');
  }
}
