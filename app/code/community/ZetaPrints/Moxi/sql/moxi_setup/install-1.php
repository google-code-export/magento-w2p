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
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$setName = 'OpenX Advertising Plan';
$groupName = 'OpenX';

$entityTypeId = Mage::getModel('catalog/product')
                  ->getResource()
                  ->getTypeId();

$defaultSetId = $this->getDefaultAttributeSetId($entityTypeId);

$set = Mage::getModel('eav/entity_attribute_set')
         ->setEntityTypeId($entityTypeId)
         ->setAttributeSetName($setName);

$set->validate();

$set
  ->save()
  ->initFromSkeleton($defaultSetId)
  ->save();

$setId = $set->getId();

$groupId = $this->getDefaultAttributeGroupId($entityTypeId, $setId);

$siteAttr = array(
  //Global settings
  'type' => 'int',
  'input' => '',
  'label' => 'OpenX Website ID',
  'required' => false,
  'user_defined' => true,
  'default' => 0,
  'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

  //Catalogue setting
  'visible' => false,
  'is_configurable' => false
);

$this->addAttribute($entityTypeId, 'openx_website_id', $siteAttr);
$siteAttrId = $this->getAttributeId($entityTypeId, 'openx_website_id');

$zoneAttr = array(
  //Global settings
  'type' => 'int',
  'input' => '',
  'label' => 'OpenX Zone ID',
  'required' => false,
  'user_defined' => true,
  'default' => 0,
  'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

  //Catalogue setting
  'visible' => false,
  'is_configurable' => false
);

$this->addAttribute($entityTypeId, 'openx_zone_id', $zoneAttr);
$zoneAttrId = $this->getAttributeId($entityTypeId, 'openx_zone_id');

$this->addAttributeToSet($entityTypeId, $setId, $groupId, $siteAttrId);
$this->addAttributeToSet($entityTypeId, $setId, $groupId, $zoneAttrId);

$this->updateAttribute($entityTypeId, $siteAttrId, 'is_user_defined', 0);
$this->updateAttribute($entityTypeId, $zoneAttrId, 'is_user_defined', 0);
