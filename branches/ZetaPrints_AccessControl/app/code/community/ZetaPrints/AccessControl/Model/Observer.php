<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 * @copyright  Copyright (c) 2008 Vinai Kopp http://netzarbeiter.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * Observer for the groups catalog extension. Remove hidden items from the collections
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 * @author     Vinai Kopp <vinai@netzarbeiter.com>
 */
class Netzarbeiter_GroupsCatalog_Model_Observer extends Mage_Core_Model_Abstract
{
	/**
	 * Remove hidden products from the collection
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogProductCollectionLoadAfter($observer)
	{
		//Mage::helper('groupscatalog')->log('catalog_product_collection_load_after');
		if (! Mage::helper('groupscatalog')->moduleActive() || $this->_isApiRequest()) return;
        $collection = $observer->getCollection();
		Mage::helper('groupscatalog')->removeHiddenProducts($collection);
	}

	/**
	 * Remove hidden caegories from the collection
	 *
	 * Since Mageto 1.3.1 this is also used for flat category collections
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogCategoryCollectionLoadAfter($observer)
	{
		//Mage::helper('groupscatalog')->log('catalog_category_collection_load_after');
		if (! Mage::helper('groupscatalog')->moduleActive() || $this->_isApiRequest()) return;
		Mage::helper('groupscatalog')->removeHiddenCategories($observer->getCategoryCollection());
	}

	/**
	 * This observer method is obsolete except for Magento Version 1.3.0
	 * Since 1.3.1 catalogCategoryCollectionLoadAfter() is used (same as with the
	 * traditional eav category collections)
	 * 
	 * @param Varien_Event_Observer $observer
	 */
    public function catalogCategoryFlatLoadNodesAfter($observer)
    {
		//Mage::helper('groupscatalog')->log('catalog_category_flat_load_after');
		if (! Mage::helper('groupscatalog')->moduleActive() || $this->_isApiRequest()) return;
		Mage::helper('groupscatalog')->hideHiddenCategoryNodes($observer->getNodes());
    }

	/**
	 * Save the product visibility settings
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function adminhtmlCustomerSaveAfter($observer)
	{
		//Mage::helper('groupscatalog')->log(__METHOD__);
		if (! Mage::helper('groupscatalog')->moduleActive() || $this->_isApiRequest()) return;
		$visibleProducts = explode(',', Mage::app()->getRequest()->getPost('visible_products', ''));
		$customer = Mage::registry('current_customer');
		if ($visibleProducts && $customer && $customer->getId())
		{
			$product = Mage::getModel('catalog/product');
			foreach ($visibleProducts as $productState)
			{
				@list($productId, $isAccessible) = explode(':', $productState);
				$product->getResource()->load($product, $productId);
				if ($product->getId())
				{
					Mage::helper('groupscatalog')->setProductAccessibilityForGroup($product, $customer->getGroupId(), (bool) $isAccessible);
				}
			}
		}
	}

	/**
	 * Return true if the reqest is made via the api
	 *
	 * @return boolean
	 */
	protected function _isApiRequest()
	{
		return Mage::app()->getRequest()->getModuleName() === 'api';
	}
}

