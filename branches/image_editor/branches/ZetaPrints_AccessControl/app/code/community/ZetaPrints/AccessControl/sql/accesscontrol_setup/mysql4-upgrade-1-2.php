<?php

$installer = $this;
$installer->startSetup();

$installer->updateAttribute('catalog_category', 'accesscontrol_show_group',
  'note', 'Hold Ctrl to select multiple categories');

$installer->endSetup();

?>
