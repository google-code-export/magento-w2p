<?php

$installer = $this;
$installer->startSetup();

$installer->updateAttribute('catalog_category', 'accesscontrol_show_group',
                            'is_configurable', '0');
$installer->updateAttribute('catalog_category', 'accesscontrol_show_group',
                            'used_in_product_listing', '1');

$installer->endSetup();

?>
