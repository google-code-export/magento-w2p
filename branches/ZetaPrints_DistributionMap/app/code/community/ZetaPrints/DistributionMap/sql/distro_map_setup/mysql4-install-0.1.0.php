<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/16/11
 * Time: 7:55 PM
 * To change this template use File | Settings | File Templates.
 */

$installer = $this;
$installer->startSetup();
$map = $installer->getTable('distro_map/distro_maps');
$installer->run("
  DROP TABLE IF EXISTS `{$map}`;
  CREATE TABLE `{$map}` (
    `entity_id` int(11) NOT NULL auto_increment,
    `coords` text NOT NULL,
    `kml` mediumtext NULL,
    `order_id` int(10) NULL,
    `created` DATETIME  NOT NULL,
    PRIMARY KEY  (`entity_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

$installer->endSetup();
