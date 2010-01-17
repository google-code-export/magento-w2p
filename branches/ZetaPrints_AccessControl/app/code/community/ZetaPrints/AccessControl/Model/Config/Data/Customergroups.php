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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 * @copyright  Copyright (c) 2008 Vinai Kopp http://netzarbeiter.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Config data backend model for customer groups selection
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 */
class Netzarbeiter_GroupsCatalog_Model_Config_Data_Customergroups extends Mage_Core_Model_Config_Data
{
	/**
	 * Input validation for the backend data entry
	 *
	 * @return Netzarbeiter_GroupsCatalog_Model_Config_Data_Customergroups
	 */
    protected function _beforeSave()
    {
        $data = $this->getValue();
        
        /**
         * Default to using the default - don't let the customer select nothing
         */
        if (empty($data)) {
        	$this->setValue(array(Netzarbeiter_GroupsCatalog_Helper_Data::NONE));
        }
        elseif (count($data) > 1) {
        	if (in_array(Netzarbeiter_GroupsCatalog_Helper_Data::NONE, $data))
       		{
        		/**
        		 * remove all groups but the "none" value
        		 */
        		$data = array(Netzarbeiter_GroupsCatalog_Helper_Data::NONE);
        		Mage::getSingleton('adminhtml/session')->addNotice(
        			Mage::helper('adminhtml')->__('Customer groups besides NONE where removed from the selection.')
        		);
        	}
        	$this->setValue($data);
        }
        

	//Renaming label of groups selector on catalog/product settings page.
	//Looks ugly.
	$categoryAttribute = Mage::getModel('catalog/category')->getResource()->getAttribute('groupscatalog_hide_group');
	$categoryData = $categoryAttribute->getData();

	$productAttribute = Mage::getModel('catalog/product')->getResource()->getAttribute('groupscatalog_hide_group');
	$productData = $productAttribute->getData();

	if (Mage::helper('groupscatalog/data')->getAccessLogic() == Netzarbeiter_GroupsCatalog_Helper_Data::ACCESS_DENIED) {
		$categoryData['frontend_label'] = Mage::helper('groupscatalog')->__('Show to customer groups');
		$productData['frontend_label'] = Mage::helper('groupscatalog')->__('Show to customer groups');
	} else {
		$categoryData['frontend_label'] = Mage::helper('groupscatalog')->__('Hide from customer groups');
		$productData['frontend_label'] = Mage::helper('groupscatalog')->__('Hide from customer groups');
	}

	$categoryAttribute->setData($categoryData);
	$categoryAttribute->save();

	$productAttribute->setData($productData);
	$productAttribute->save();

        return parent::_beforeSave();
    }
    
}
