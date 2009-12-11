<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', 'webtoprint_template',
  array(
    'type'              => 'varchar',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Web-to-print Template',
    'input'             => 'text',
    'class'             => '',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'default'           => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'visible_in_advanced_search' => false,
    'unique'            => true ));

//$installer->removeAttribute('catalog_product', 'webtoprint_template');

$installer->endSetup();

?>
