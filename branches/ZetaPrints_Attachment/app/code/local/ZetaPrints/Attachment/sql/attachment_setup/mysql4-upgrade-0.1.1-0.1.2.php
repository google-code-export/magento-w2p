<?php

$installer = $this;
$installer->startSetup();
$table = $installer->getTable('attachment/attachment');
$installer->run("
ALTER TABLE `{$table}` DROP `path`, DROP `url`;
");
$installer->run("
ALTER TABLE `{$table}`
ADD `product_id` INT( 10 ) UNSIGNED NOT NULL ,
ADD `order_id` INT( 10 ) UNSIGNED NULL ,
ADD `quote_id` INT( 10 ) UNSIGNED NULL ,
ADD `attachment_value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
");
$installer->endSetup();

