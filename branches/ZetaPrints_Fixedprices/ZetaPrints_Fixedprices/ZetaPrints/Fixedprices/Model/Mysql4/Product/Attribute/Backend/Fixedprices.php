<?php
/**
 * Catalog product tier price backend attribute model
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_Fixedprices
 */
class ZetaPrints_Fixedprices_Model_Mysql4_Product_Attribute_Backend_Fixedprices
 extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Attribute_Backend_Tierprice
{

  public function _construct()
  {
    $this->_init('fixedprices/product_attribute_fixed_price', 'value_id');
  }

  /**
   * Load Fixed Prices for product
   *
   * @param int $productId
   * @return ZetaPrints_Fixedprices_Model_Mysql4_fixedprices
   */
  public function loadPriceData($productId, $websiteId = null)
  {
    $adapter = $this->_getReadAdapter();
    $columns = array ('price_id' => $this->getIdFieldName(),
                      'website_id' => 'website_id',
                      'units' => 'units',
                      'price_qty' => 'qty',
                      'price' => 'value',
                      'active' => 'is_active',
                      'order' => 'order'
    );
    $select = $adapter->select()
                      ->from($this->getMainTable(), $columns)
                      ->where('entity_id=?', $productId)
                      ->order('order');

    if (!is_null($websiteId)) {
      if ($websiteId == '0') {
        $select->where('website_id=?', $websiteId);
      } else {
        $select->where('website_id IN(?)', array ('0', $websiteId
        ));
      }
    }

    Mage::log((string)$select);

    return $adapter->fetchAll($select);
  }
}
