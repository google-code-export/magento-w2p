<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer ZetaPrints_Fixedprices_Model_Resource_Setup */

$attr_code = 'use_fixed_price';
$installer->startSetup();
$productEntityTypeId = $installer->getEntityTypeId('catalog_product');
$attributeSetId = $installer->getDefaultAttributeSetId($productEntityTypeId);

$installer->addAttribute('catalog_product', $attr_code, array(
    'type' => 'int',
    'label' => 'Use Fixed Quantity Prices:',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'sort_order' => 10,
    'default' => 0
));

$installer->endSetup();
