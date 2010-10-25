<?php

$installer = $this;
/* @var $installer ZetaPrints_Attachment_Model_Mysql4_Attachment */
$installer->startSetup();
$productEntityTypeId = $installer->getEntityTypeId('catalog_product');
$attributeSetId = $installer->getDefaultAttributeSetId($productEntityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($productEntityTypeId, $attributeSetId);
$installer->addAttribute('catalog_product', 'allow_attachements', array(
    'type' => 'int',
    'label' => 'Allow files to be attached to order.',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => false,
    'default' => 0
));

$installer->addAttributeToGroup(
        $productEntityTypeId,
        $attributeSetId,
        $attributeGroupId,
        'allow_attachements',
        '10'
);

$attributeId = $installer->getAttributeId($productEntityTypeId, 'allow_attachements');

$installer->run("
INSERT INTO `{$installer->getTable('catalog_product_entity_int')}`
(`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '0'
        FROM `{$installer->getTable('catalog_product_entity')}`;
");

$installer->endSetup();
