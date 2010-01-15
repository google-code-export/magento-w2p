<?php


$this->startSetup();

/**
 * I don't know if I forgot that or what happend, but we don't want the array backend model, we want our own!
 */
$this->updateAttribute('catalog_product', 'groupscatalog_hide_group', 'backend_model', 'groupscatalog/entity_attribute_backend_customergroups');

/**
 * To be on the safe side, also do it for the category attribute, even though it was okay on don's installation
 */
$this->updateAttribute('catalog_category', 'groupscatalog_hide_group', 'backend_model', 'groupscatalog/entity_attribute_backend_customergroups');

$this->endSetup();
