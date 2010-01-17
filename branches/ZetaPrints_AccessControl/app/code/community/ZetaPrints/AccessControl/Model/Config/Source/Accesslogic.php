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
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Used for switching access mode in the extension.
 *
 * Available modes are: denied access and granted access.
 * Denied access: products/categories are visible to all and hidden from list of selected groups.
 * Granted access: products/categories are hidden from all, except the list of selected groups.
 */
class Netzarbeiter_GroupsCatalog_Model_Config_Source_Accesslogic
{

    public function toOptionArray()
    {
        return array(
            array('value'=>Netzarbeiter_GroupsCatalog_Helper_Data::ACCESS_DENIED,
                  'label'=>Mage::helper('groupscatalog')->__('Selected groups denied access')),

            array('value'=>Netzarbeiter_GroupsCatalog_Helper_Data::ACCESS_GRANTED,
                  'label'=>Mage::helper('groupscatalog')->__('Selected groups granted access')),
        );
    }

}
