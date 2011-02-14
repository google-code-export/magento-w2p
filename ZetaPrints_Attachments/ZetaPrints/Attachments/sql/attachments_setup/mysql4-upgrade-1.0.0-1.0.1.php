<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
$installer->startSetup();
$table = $installer->getTable('attachments/attachments');
$installer->run("
ALTER TABLE `{$table}`
 ADD `created` DATETIME NOT NULL ");
$installer->endSetup();
