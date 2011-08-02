<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer ZetaPrints_Fixedprices_Model_Resource_Setup */

$installer->addAttribute('catalog_product', ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE, array (
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
      'sort_order' => 20,
      'used_in_product_listing' => 1
));

$installer->addAttribute('catalog_product', ZetaPrints_Fixedprices_Helper_Data::USE_FIXED_PRICE, array(
      'type' => 'int',
      'label' => 'Use Fixed Quantity Prices:',
      'input' => 'select',
      'source' => 'eav/entity_attribute_source_boolean',
      'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
      'visible' => true,
      'required' => false,
      'user_defined' => false,
      'used_in_product_listing' => 1,
      'sort_order' => 10,
      'default' => 0
));
$installer->endSetup();

