<<<<<<< .mine
<?php
/**
 * @author 			Petar Dzhambazov
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
$prtable = $installer->getTable('catalog_product_entity');
$wbtable = $installer->getTable('core_website');

$installer->startSetup();

  $installer->run("ALTER TABLE `{$zptable}` DROP FOREIGN KEY `FK_CATALOG_PRODUCT_ENTITY_FIXED_PRICE_PRODUCT_ENTITY`");
  $installer->run("ALTER TABLE `{$zptable}` ADD FOREIGN KEY
( `entity_id` ) REFERENCES `{$prtable}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE ;");
  $installer->run("ALTER TABLE `{$zptable}` DROP FOREIGN KEY `FK_CATALOG_PRODUCT_FIXED_WEBSITE` ;");
  $installer->run("ALTER TABLE `{$zptable}` ADD FOREIGN KEY
( `website_id` ) REFERENCES `{$wbtable}` (`website_id`) ON DELETE CASCADE ON UPDATE CASCADE ;");

  $installer->endSetup();
=======
<?php
/**
 * @author 			Petar Dzhambazov
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
$prtable = $installer->getTable('catalog_product_entity');
$wbtable = $installer->getTable('core_website');

$installer->startSetup();

  $installer->run("ALTER TABLE `{$zptable}` DROP FOREIGN KEY `FK_CATALOG_PRODUCT_ENTITY_FIXED_PRICE_PRODUCT_ENTITY`");
  $installer->run("ALTER TABLE `{$zptable}` ADD FOREIGN KEY
( `entity_id` ) REFERENCES `{$prtable}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE ;");
  $installer->run("ALTER TABLE `{$zptable}` DROP FOREIGN KEY `FK_CATALOG_PRODUCT_FIXED_WEBSITE` ;");
  $installer->run("ALTER TABLE `{$zptable}` ADD FOREIGN KEY
( `website_id` ) REFERENCES `{$wbtable}` (`website_id`) ON DELETE CASCADE ON UPDATE CASCADE ;");

  $installer->endSetup();
>>>>>>> .r1756
