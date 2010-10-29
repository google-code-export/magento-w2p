<?php

$installer = $this;
$installer->startSetup();

$installer->run("
  DROP TABLE IF EXISTS `{$installer->getTable('attachment/attachment')}`;
  CREATE TABLE `{$installer->getTable('attachment/attachment')}` (
    `attachment_id` int(11) NOT NULL auto_increment,
    `path` text NOT NULL,
    `url` text NOT NULL,
    PRIMARY KEY  (`attachment_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

$installer->endSetup();

