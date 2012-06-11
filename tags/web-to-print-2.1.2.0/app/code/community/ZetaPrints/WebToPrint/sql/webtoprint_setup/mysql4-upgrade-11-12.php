<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', 'dynamic_imaging',
  array(
    'type'              => 'int',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Dynamic imaging',
    'input'             => '',
    'class'             => '',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'           => false,
    'required'          => false,
    'user_defined'      => false,
    'default'           => 0,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'visible_in_advanced_search' => false,
    'unique'            => false
  )
);

$this->addAttribute('catalog_category', 'dynamic_imaging',
  array(
    'type'          => 'int',
    'label'         => 'Dynamic imaging',
    'input'         => 'select',
    'source'        => 'eav/entity_attribute_source_boolean',
    'backend'       => '',
    'backend_model' => '',
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required'      => 0,
    'default'       => 0,
    'user_defined'  => false,
    'is_configurable' => 0
  )
);

$category_entity_id = $installer->getEntityTypeId('catalog_category');
$category_attribute_set_id = $installer
                               ->getDefaultAttributeSetId($category_entity_id);

$installer->addAttributeToGroup(
  $category_entity_id,
  $category_attribute_set_id,
  $installer->getAttributeGroupId($category_entity_id,
                                  $category_attribute_set_id,
                                  'Display Settings'),
  'dynamic_imaging',
  55
);

$installer->endSetup();

?>
