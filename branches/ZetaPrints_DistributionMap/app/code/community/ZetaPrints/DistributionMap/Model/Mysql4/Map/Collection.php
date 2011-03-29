<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/16/11
 * Time: 7:45 PM
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_DistributionMap_Model_Mysql4_Map_Collection
  extends Mage_Core_Model_Mysql4_Collection_Abstract
{
  protected function _construct()
  {
    parent::_construct();
    $this->_init('distro_map/map');
  }

}
