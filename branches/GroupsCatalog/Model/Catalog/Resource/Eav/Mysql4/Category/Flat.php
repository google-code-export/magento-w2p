<?php


class Netzarbeiter_GroupsCatalog_Model_Catalog_Resource_Eav_Mysql4_Category_Flat
	extends Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat
{
	/**
	 * Because in Magento 1.3.1 events that ARE NOT TRIGGERED exist
	 * I need to override this method here to filter out hidden categories...
	 *
	 * @param Mage_Catalog_Model_Category|int $parentNode
	 * @param int $recursionLevel
	 * @param int $storeId
	 * @return array
	 */
	protected function _loadNodes($parentNode = null, $recursionLevel = 0, $storeId = 0)
	{
		$nodes = parent::_loadNodes($parentNode, $recursionLevel, $storeId);

		if (! Mage::helper('groupscatalog')->inAdmin() && Mage::helper('groupscatalog')->moduleActive())
		{
			foreach (array_keys($nodes) as $nodeId)
			{
				if (Mage::helper('groupscatalog')->isCategoryHidden($nodes[$nodeId])) unset($nodes[$nodeId]);
			}
		}

		return $nodes;
	}
}