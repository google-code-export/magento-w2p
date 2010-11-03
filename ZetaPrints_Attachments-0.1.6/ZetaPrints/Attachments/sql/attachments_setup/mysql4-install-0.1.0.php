<?php

$installer = $this;
/* @var $installer ZetaPrints_Attachments_Model_Resource_Setup */
$installer->startSetup();
$productEntityTypeId = $installer->getEntityTypeId('catalog_product');
$attributeSetId = $installer->getDefaultAttributeSetId($productEntityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($productEntityTypeId, $attributeSetId);

$is_update = $installer->getAttributeId($productEntityTypeId, 'allow_attachements');

$installer->addAttribute('catalog_product', 'allow_attachements', array(
    'type' => 'int',
    'label' => 'Use AJAX upload:',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => false,
    'is_user_defined' => true,
    'sort_order' => 10,
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
if(!$is_update){ // if for some reason this runs again do not enter anything
  $installer->updateCatalogProductEntityTable($productEntityTypeId, $attributeId);
}
$installer->endSetup();
