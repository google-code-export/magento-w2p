<?php

$installer = $this;
$installer->startSetup();

$installer->run("CREATE TABLE `zetaprints_cookie`(`user_id` VARCHAR(200) NOT NULL , `pass` VARCHAR(7) , PRIMARY KEY (`user_id`));");

$installer->endSetup();

?>
