<<<<<<< .mine
<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
$attr_code = ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE;

$installer->startSetup();

$installer->addAttribute('catalog_product', $attr_code, array (
          'type' => 'decimal',
          'label' => 'Fixed Quantity Prices:',
      		'backend' => 'fixedprices/product_attribute_backend_fixedprices',
          'input' => 'text',
          'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
          'visible' => true,
          'required' => false,
          'user_defined' => false,
          'default' => '',
          'searchable' => false,
          'filterable' => false,
          'comparable' => false,
          'visible_on_front' => false,
          'visible_in_advanced_search' => false,
          'used_for_price_rules' => false,
          'unique' => false,
          'sort_order' => 20
));

  $installer->run("
DROP TABLE IF EXISTS {$this->getTable('zetaprints_product_entity_fixed_price')};
CREATE TABLE {$this->getTable('zetaprints_product_entity_fixed_price')} (
 `value_id` int(11) NOT NULL AUTO_INCREMENT,
 `entity_id` int(10) unsigned NOT NULL DEFAULT '0',
 `qty` int(11) NOT NULL DEFAULT '1',
 `units` varchar(255) NOT NULL DEFAULT '',
 `value` decimal(12,4) NOT NULL DEFAULT '0.0000',
 `website_id` smallint(5) unsigned NOT NULL,
 `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
 PRIMARY KEY (`value_id`),
 UNIQUE KEY `UNQ_CATALOG_PRODUCT_FIXED_PRICE` (`entity_id`,`qty`,`units`,`website_id`),
 KEY `FK_CATALOG_PRODUCT_ENTITY_FIXED_PRICE_PRODUCT_ENTITY` (`entity_id`),
 KEY `FK_CATALOG_PRODUCT_FIXED_WEBSITE` (`website_id`),
 CONSTRAINT `FK_CATALOG_PRODUCT_FIXED_WEBSITE` FOREIGN KEY (`website_id`) REFERENCES `core_website` (`website_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `FK_CATALOG_PRODUCT_ENTITY_FIXED_PRICE_PRODUCT_ENTITY` FOREIGN KEY (`entity_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8");

  $installer->endSetup();
=======
<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
$attr_code = ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE;

$installer->startSetup();

$installer->addAttribute('catalog_product', $attr_code, array (
          'type' => 'decimal',
          'label' => 'Fixed Quantity Prices:',
      		'backend' => 'fixedprices/product_attribute_backend_fixedprices',
          'input' => 'text',
          'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
          'visible' => true,
          'required' => false,
          'user_defined' => false,
          'default' => '',
          'searchable' => false,
          'filterable' => false,
          'comparable' => false,
          'visible_on_front' => false,
          'visible_in_advanced_search' => false,
          'used_for_price_rules' => false,
          'unique' => false,
          'sort_order' => 20
));

  $installer->run("
DROP TABLE IF EXISTS {$this->getTable('zetaprints_product_entity_fixed_price')};
CREATE TABLE {$this->getTable('zetaprints_product_entity_fixed_price')} (
 `value_id` int(11) NOT NULL AUTO_INCREMENT,
 `entity_id` int(10) unsigned NOT NULL DEFAULT '0',
 `qty` int(11) NOT NULL DEFAULT '1',
 `units` varchar(255) NOT NULL DEFAULT '',
 `value` decimal(12,4) NOT NULL DEFAULT '0.0000',
 `website_id` smallint(5) unsigned NOT NULL,
 `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
 PRIMARY KEY (`value_id`),
 UNIQUE KEY `UNQ_CATALOG_PRODUCT_FIXED_PRICE` (`entity_id`,`qty`,`units`,`website_id`),
 KEY `FK_CATALOG_PRODUCT_ENTITY_FIXED_PRICE_PRODUCT_ENTITY` (`entity_id`),
 KEY `FK_CATALOG_PRODUCT_FIXED_WEBSITE` (`website_id`),
 CONSTRAINT `FK_CATALOG_PRODUCT_FIXED_WEBSITE` FOREIGN KEY (`website_id`) REFERENCES `core_website` (`website_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `FK_CATALOG_PRODUCT_ENTITY_FIXED_PRICE_PRODUCT_ENTITY` FOREIGN KEY (`entity_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8");

  $installer->endSetup();
>>>>>>> .r1756
