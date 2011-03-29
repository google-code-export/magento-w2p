<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/16/11
 * Time: 8:22 PM
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_DistributionMap_Block_Adminhtml_Edit_Tab_Option
  extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Options_Option
{

  const GROUPS = 'global/catalog/product/options/custom/groups';

  public function __construct()
  {
    parent::__construct();
    $this->setTemplate('distromap/product/options/option.phtml');
  }

  public function getTemplatesHtml()
  {
    $templates = '';
    foreach (Mage::getConfig()->getNode(self::GROUPS)->children() as $group) {
      $childName = $group->getName() . '_option_type';
      $templates .= $this->getChildHtml($childName) . "\n";
    }
    return $templates;
  }
}
