<<<<<<< .mine
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

$attributes = array(
  $installer->getAttributeId('catalog_product', $attr_code)
);
if (!empty($attributes)) {
  $apply_to = 'simple,configurable,virtual,downloadable';

  $sql = $installer->getConnection()
      ->quoteInto("SELECT * FROM `{$installer->getTable('catalog/eav_attribute')}` WHERE attribute_id IN (?)", $attributes);
  $data = $installer->getConnection()->fetchAll($sql);
  foreach ($data as $row) {
    $row['apply_to'] = $apply_to;

    $installer->run("UPDATE `{$installer->getTable('catalog/eav_attribute')}`
                SET `apply_to` = '{$row['apply_to']}'
                WHERE `attribute_id` = {$row['attribute_id']}");
  }
}

$installer->endSetup();
=======
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

$attributes = array(
  $installer->getAttributeId('catalog_product', $attr_code)
);
if (!empty($attributes)) {
  $apply_to = 'simple,configurable,virtual,downloadable';

  $sql = $installer->getConnection()
      ->quoteInto("SELECT * FROM `{$installer->getTable('catalog/eav_attribute')}` WHERE attribute_id IN (?)", $attributes);
  $data = $installer->getConnection()->fetchAll($sql);
  foreach ($data as $row) {
    $row['apply_to'] = $apply_to;

    $installer->run("UPDATE `{$installer->getTable('catalog/eav_attribute')}`
                SET `apply_to` = '{$row['apply_to']}'
                WHERE `attribute_id` = {$row['attribute_id']}");
  }
}

$installer->endSetup();
>>>>>>> .r1756
