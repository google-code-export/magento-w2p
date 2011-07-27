<?php
class ZetaPrints_Fixedprices_Model_Convert_Adapter_Fixedprices extends Mage_Catalog_Model_Convert_Adapter_Product
{
  const QTY   = 'qty';
  const LBL   = 'label';
  const PRICE = 'price';
  const DEF   = 'default';

  protected $columnMap = array( self::QTY   => 'price_qty',
      													self::LBL   => 'units',
      													self::PRICE => 'price',
      													self::DEF   => 'is_active');
  /**
   * (non-PHPdoc)
   * @see Mage_Dataflow_Model_Convert_Adapter_Interface::save()
   */
  public function save()
  {
    parent::save();
  }

  public function load()
  {
    $conn = Mage::getResourceModel('fixedprices/product_attribute_backend_fixedprices');

    /*@var $conn ZetaPrints_Fixedprices_Model_Mysql4_Product_Attribute_Backend_Fixedprices */

    $adapter = $conn->getReadConnection();
    $select = $adapter->select()->from($conn->getMainTable(), array('id' => 'entity_id'))->order('id');
    $ids = $adapter->fetchAll($select);
    $result = array();
    foreach ($ids as $id) {
      $result[] = $id['id'];
    }
    $result = array_unique($result);
    $message = Mage::helper('eav')->__("Loaded %d records", count($result));
    $this->addException($message);
    $this->setData($result);
    return $this;
  }

  public function saveRow(array $importData)
  {
    $product = $this->getProductModel()->reset();

    if (empty($importData['sku'])) {
      $message = Mage::helper('catalog')->__('Skipping import result, required field "%s" is not defined.', 'sku');
      Mage::thresultException($message);
    }
    $store = Mage::app()->getStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
    $product->setStoreId($store->getId());
    $productId = $product->getIdBySku($importData['sku']);

    if ($productId) {
      $product->load($productId);
    } else { // product with that SKU does not exist, skip it.
      $message = Mage::helper('fixedprices')
                          ->__('Skipping import result, product with SKU "%s" does not exist. Create or import it first.',
                                $importData['sku']);
      Mage::thresultException($message);
    }

    $this->setProductTypeInstance($product);

    foreach ($this->_ignoreFields as $field) {
      if (isset($importData[$field])) {
        unset($importData[$field]);
      }
    }

    unset($importData['sku']); // got the product, do not need sku

    $data = $this->_parseFq($importData);

    $product->setData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE, $data);

    $product->setIsMassupdate(true);
    $product->setExcludeUrlRewrite(true);
    $product->save();

    return true;
  }

  protected function _parseFq($importData)
  {
    $result = array ();
    foreach ($importData as $key => $value) {
      $field = $this->_getFqField($key);
      switch ($field['name']) {
        case self::QTY:
          $result[$field['offset']][$this->columnMap[self::QTY]] = $value;
          break;
        case self::LBL:
          $result[$field['offset']][$this->columnMap[self::LBL]] = $value;
          break;
        case self::PRICE:
          $result[$field['offset']][$this->columnMap[self::PRICE]] = $value;
          break;
        case self::DEF:
          if($value == 1){
            $result['active'] = $field['offset'];
          }
          break;
      }
      $result[$field['offset']]['website_id'] = 0;
    }
    return $result;
  }

  protected function _getFqField($value)
  {
    $result = array();
    preg_match('/^(?P<field>\D+)(?P<idx>\d*)/', $value, $result);
    if(!isset($result['field'])){
      return array();
    }

    if(!isset($result['idx'])){
      $result['idx'] = 0;
    }
    return array('name' => $result['field'], 'offset' => $result['idx']);
  }
}