<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/16/11
 * Time: 8:22 PM
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_DistributionMap_Block_Adminhtml_Edit_Tab_Map
  extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Options_Type_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('distromap/product/options/type/distromap.phtml');
    }
}
