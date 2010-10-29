<?php

$installer = $this;
$installer->startSetup();
$table = $installer->getTable('attachment/attachment');
$installer->run("
ALTER TABLE `{$table}`
ADD `attachment_hash` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
");
$installer->endSetup();

