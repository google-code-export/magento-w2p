<?php

$installer = $this;
$installer->startSetup();

$installer->run("
  ALTER TABLE {$installer->getTable('webtoprint/template')}
    CHANGE `xml` `xml` MEDIUMTEXT; ");

$installer->endSetup();

?>
