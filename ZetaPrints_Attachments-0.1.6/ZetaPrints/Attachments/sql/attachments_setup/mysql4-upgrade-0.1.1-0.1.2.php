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
ALTER TABLE `{$table}` DROP `path`, DROP `url`;
");
$installer->run("
ALTER TABLE `{$table}`
ADD `product_id` INT( 10 ) UNSIGNED NOT NULL ,
ADD `order_id` INT( 10 ) UNSIGNED NULL ,
ADD `option_id` INT( 10 ) NOT NULL,
ADD `attachment_value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL
");
$installer->endSetup();

