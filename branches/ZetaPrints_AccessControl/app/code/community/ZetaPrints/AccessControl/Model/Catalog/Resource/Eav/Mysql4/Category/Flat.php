<?php

class ZetaPrints_AccessControl_Model_Catalog_Resource_Eav_Mysql4_Category_Flat
  extends Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat {

  /**
   * Because in Magento 1.3.1 events that ARE NOT TRIGGERED exist
   * I need to override this method here to filter out hidden categories...
   *
   * @param Mage_Catalog_Model_Category|int $parentNode
   * @param int $recursionLevel
   * @param int $storeId
   * @return array
   */
  protected function _loadNodes ($parentNode = null, $recursionLevel = 0, $storeId = 0) {
    $nodes = parent::_loadNodes($parentNode, $recursionLevel, $storeId);

    if (!Mage::helper('accesscontrol')->is_in_admin_panel()
          && Mage::helper('accesscontrol')->is_extension_enabled()) {

      foreach (array_keys($nodes) as $nodeId)
        if (!Mage::helper('accesscontrol')
              ->has_customer_group_access_to_category($nodes[$nodeId]))

          unset($nodes[$nodeId]);
    }

    return $nodes;
  }
}

?>
