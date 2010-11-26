<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer ZetaPrints_Attachments_Model_Resource_Setup */

$installer->startSetup();

$productEntityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetId = $installer->getDefaultAttributeSetId($productEntityTypeId);

$attributeGroupId = $installer->getDefaultAttributeGroupId($productEntityTypeId, $attributeSetId);
$oldAtId = $installer->getAttributeId($productEntityTypeId, 'allow_attachements');
if($oldAtId){
  $installer->removeAttribute('catalog_product', 'allow_attachements');
  $installer->clearCatalogProductEntityTable($productEntityTypeId, $oldAtId);
}
$installer->addAttribute('catalog_product', ZetaPrints_Attachments_Model_Attachments::ATT_CODE, array(
    'type' => 'int',
    'label' => 'Use AJAX upload:',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => false,
    'default' => 0,
    'sort_order' => 10,
    'is_user_defined' => true,
));

$installer->addAttributeToGroup(
        $productEntityTypeId,
        $attributeSetId,
        $attributeGroupId,
        'allow_attachements',
        '10'
);
$attributeId = $installer->getAttributeId($productEntityTypeId, ZetaPrints_Attachments_Model_Attachments::ATT_CODE);

try {
  $installer->updateCatalogProductEntityTable($productEntityTypeId, $attributeId);
} catch (Exception $e) {
}

$installer->endSetup();
