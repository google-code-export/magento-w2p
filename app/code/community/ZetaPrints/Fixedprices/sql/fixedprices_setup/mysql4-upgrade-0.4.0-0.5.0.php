<?php
/**
 * @author       Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @var ZetaPrints_Fixedprices_Model_Resource_Setup $installer
 */
$installer = $this;
$attr_code = ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE;
$zptable = $installer->getTable('zetaprints_product_entity_fixed_price');

$installer->startSetup();

$installer->run("ALTER TABLE `{$zptable}` ADD `order` SMALLINT( 3 ) UNSIGNED NOT NULL DEFAULT '0'");

$installer->endSetup();

