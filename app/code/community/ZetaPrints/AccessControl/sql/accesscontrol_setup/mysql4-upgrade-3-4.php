<?php

$installer = $this;
$installer->startSetup();

$installer->updateAttribute('catalog_category', 'accesscontrol_show_group',
                            'is_configurable', '0');
$installer->updateAttribute('catalog_category', 'accesscontrol_show_group',
                            'used_in_product_listing', '1');

if (version_compare(Mage::getVersion(), '1.4.0', '>='))
  try {
    Mage::getModel('index/indexer')
      ->getProcessByCode('catalog_category_flat')
      ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
  } catch (Exception $e) { }

$installer->updateAttribute('catalog_category', 'accesscontrol_show_group',
                            'frontend_input_renderer',
                            'accesscontrol/catalog_product_helper_customergroups');

$installer->endSetup();

?>
