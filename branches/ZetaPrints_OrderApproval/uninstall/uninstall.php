<?php

//Initialize Magento
require_once 'app/Mage.php';
umask(0);

//Run Mage app
Mage::app('default');

//Get setup model
$setup = Mage::getModel('eav/entity_setup',  'core_setup');

$setup->startSetup();

echo 'Removing attributes ';

//Remove installed attributes
$setup->removeAttribute('customer', 'is_approver');
$setup->removeAttribute('customer', 'approver');

echo '[OK]<br />';

echo 'Cleaning tables ';

//Remove approved column from quote items
$setup->run("
  ALTER TABLE {$setup->getTable('sales/quote_item')} DROP COLUMN `approved`");

//Remove record about extension from resource table
$setup->run("
  DELETE FROM {$setup->getTable('core/resource')}
    WHERE code = 'orderapproval_setup'");

echo '[OK]<br />';

$setup->endSetup();

echo 'ZetaPrints OrderApproval extension was completely removed';

?>
