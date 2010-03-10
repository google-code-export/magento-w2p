<?php

$installer = $this;
$installer->startSetup();

$entityTypeId     = $installer->getEntityTypeId('catalog_category');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'Display Settings'),
	'accesscontrol_show_group',
	60
);

$installer->endSetup();

?>
