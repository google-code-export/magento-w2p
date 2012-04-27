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

$attributes = array(
  'openx_website_id' => array(
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
  ),

  'openx_zone_id' => array(
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
  ),

  'openx_pricing_model' => array(
    //Global settings
    'type' => 'int',
    'input' => 'select',
    'source' => 'moxi/entity_attribute_source_pricingmodels',
    'label' => 'Pricing Model',
    'required' => false,
    'user_defined' => true,
    'default' => ZetaPrints_Moxi_Helper_Data::MT_PRICING_MODEL,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Catalogue setting
    'visible' => false,
    'is_configurable' => false
  ),

  'openx_rate_price' => array(
    //Global settings
    'type' => 'decimal',
    'input' => 'text',
    'label' => 'Rate/Price',
    'required' => false,
    'user_defined' => true,
    'default' => 0,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Catalogue setting
    'visible' => false,
    'is_configurable' => false
  ),

  'openx_impressions' => array(
    //Global settings
    'type' => 'int',
    'input' => 'text',
    'label' => 'Impressions',
    'required' => false,
    'user_defined' => true,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Catalogue setting
    'visible' => false,
    'is_configurable' => false
  ),

  'openx_clicks' => array(
    //Global settings
    'type' => 'int',
    'input' => 'text',
    'label' => 'Clicks',
    'required' => false,
    'user_defined' => true,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Catalogue setting
    'visible' => false,
    'is_configurable' => false
  ),

  'openx_conversions' => array(
    //Global settings
    'type' => 'int',
    'input' => 'text',
    'label' => 'Conversions',
    'required' => false,
    'user_defined' => true,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Catalogue setting
    'visible' => false,
    'is_configurable' => false
  ),

  'openx_campaign_weight' => array(
    //Global settings
    'type' => 'int',
    'input' => 'text',
    'label' => 'Campaign weight',
    'required' => false,
    'user_defined' => true,
    'default' => 0,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Catalogue setting
    'visible' => false,
    'is_configurable' => false
  ),
);

foreach ($attributes as $name => $attribute) {
  $id = $this
          ->addAttribute($entityTypeId, $name, $attribute)
          ->getAttributeId($entityTypeId, $name);

  $this
    ->addAttributeToSet($entityTypeId, $setId, $groupId, $id)
    ->updateAttribute($entityTypeId, $id, 'is_user_defined', 0);
}
}

foreach ($attributes as $name => $attribute)
  $this->removeAttribute('catalog_product', $name);
