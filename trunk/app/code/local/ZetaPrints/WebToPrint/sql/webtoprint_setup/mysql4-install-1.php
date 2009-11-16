<?php

$installer = $this;
$installer->startSetup();

$installer->run("
  CREATE TABLE `{$installer->getTable('webtoprint/template')}` (
    `template_id` int(11) NOT NULL auto_increment,
    `guid` varchar(36),
    `catalog_guid` varchar(36),
    `title` text,
    `link` text,
    `description` text,
    `thumbnail_url` text,
    `image_url` text,
    `date` datetime,
    `xml` text,
    PRIMARY KEY  (`template_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

$installer->endSetup();


?>