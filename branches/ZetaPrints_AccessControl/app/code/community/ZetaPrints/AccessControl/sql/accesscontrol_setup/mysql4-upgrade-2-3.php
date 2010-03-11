<?php

$installer = $this;
$installer->startSetup();

$category_entity_id = $installer->getEntityTypeId('catalog_category');
$category_attribute_set_id = $installer
                               ->getDefaultAttributeSetId($category_entity_id);

$installer->addAttributeToGroup(
  $category_entity_id,
  $category_attribute_set_id,
  $installer->getAttributeGroupId($category_entity_id,
                                  $category_attribute_set_id,
                                  'Display Settings'),
  'accesscontrol_show_group',
  60
);

$installer->endSetup();

?>
