<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ZetaPrints_Attachments_Model_Resource_Setup extends Mage_Catalog_Model_Resource_Eav_Mysql4_Setup
{
    public function addAttribute($entityTypeId, $code, array $attr)
    {
      return parent::addAttribute($entityTypeId, $code, $attr);
    }
    /**
     * Update products with default value
     * Update product attributes for specific
     * attribute id
     * @param int $entityTypeId
     * @param int $attribute_id
     */
    protected function updateCatalogProductEntityTable($entityTypeId, $attribute_id)
    {
        $this->run("
          INSERT INTO `{$this->getTable('catalog_product_entity_int')}`
          (`entity_type_id`, `attribute_id`, `entity_id`, `value`)
          SELECT '{$entityTypeId}', '{$attribute_id}', `entity_id`, '0'
          FROM `{$this->getTable('catalog_product_entity')}`;");
    }

    /**
     *
     * Delete product attributes for specific
     * attribute id
     *
     * @param int $entityTypeId
     * @param int $attribute_id
     */
    protected function clearCatalogProductEntityTable($entityTypeId, $attribute_id)
    {
        $this->run("DELETE FROM `{$this->getTable('catalog_product_entity_int')}`
          WHERE `entity_type_id`='{$entityTypeId}' AND `attribute_id`='{$attribute_id}';");
    }

}
