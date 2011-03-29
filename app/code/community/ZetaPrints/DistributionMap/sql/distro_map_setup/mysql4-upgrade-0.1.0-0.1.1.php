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
$newField = ZetaPrints_DistributionMap_Model_Map::QUOTID;
$installer->run("ALTER TABLE `{$map}` ADD `{$newField}` INT( 10 ) NOT NULL");

$installer->endSetup();


