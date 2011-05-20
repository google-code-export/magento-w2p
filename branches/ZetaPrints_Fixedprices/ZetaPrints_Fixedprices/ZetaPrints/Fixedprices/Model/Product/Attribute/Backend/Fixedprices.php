<?php

/**
 * Catalog product fixed price backend attribute model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 * ZetaPrints_Fixedprices_Model_Product_Attribute_Backend_Fixedprices
 */
class ZetaPrints_Fixedprices_Model_Product_Attribute_Backend_Fixedprices
  extends Mage_Catalog_Model_Product_Attribute_Backend_Tierprice
{

  /**
   * Retrieve resource instance
   *
   * @return ZetaPrints_Fixedprices_Model_Mysql4_Product_Attribute_Backend_Fixedprices
   */
  protected function _getResource ()
  {
    return Mage::getResourceSingleton('fixedprices/product_attribute_backend_fixedprices');
  }

  /**
   * Validate fixed qtys price data
   *
   * @param Mage_Catalog_Model_Product $object
   * @throws Mage_Core_Exception
   * @return bool
   */
  public function validate ($object)
  {
    $attribute = $this->getAttribute();
    $fixes = $object->getData($attribute->getName());
    if (empty($fixes)) {
      return true;
    }

    // validate per website
    $duplicates = array();
    foreach ($fixes as $fixed) {
      if (!empty($fixed['delete'])) {
        continue;
      }
      $compare = join('-', array($fixed['website_id'], $fixed['units'], $fixed['price_qty'] * 1));
      if (isset($duplicates[$compare])) {
        Mage::throwException(
                        Mage::helper('catalog')->__('Duplicate website fixed price units and quantity.')
        );
      }
      $duplicates[$compare] = true;
    }

    // if attribute scope is website and edit in store view scope
    // add global tier prices for duplicates find
    if (!$attribute->isScopeGlobal() && $object->getStoreId()) {
      $origFixedPrices = $object->getOrigData($attribute->getName());
      foreach ($origFixedPrices as $fixed) {
        if ($fixed['website_id'] == 0) {
          $compare = join('-', array($fixed['website_id'], $fixed['units'], $fixed['price_qty'] * 1));
          $duplicates[$compare] = true;
        }
      }
    }

    // validate currency
    $baseCurrency = Mage::app()->getBaseCurrencyCode();
    $rates = $this->_getWebsiteRates();
    foreach ($fixes as $fixed) {
      if (!empty($fixed['delete'])) {
        continue;
      }
      if ($fixed['website_id'] == 0) {
        continue;
      }

      $compare = join('-', array($fixed['website_id'], $fixed['units'], $fixed['price_qty']));
      $globalCompare = join('-', array(0, $fixed['units'], $fixed['price_qty'] * 1));
      $websiteCurrency = $rates[$fixed['website_id']]['code'];

      if ($baseCurrency == $websiteCurrency && isset($duplicates[$globalCompare])) {
        Mage::throwException(
                        Mage::helper('catalog')->__('Duplicate website fixed price units and quantity.')
        );
      }
    }

    return true;
  }

  /**
   * Assign tier prices to product data
   *
   * @param Mage_Catalog_Model_Product $object
   * @return Mage_Catalog_Model_Product_Attribute_Backend_Fixedprices
   */
  public function afterLoad ($object)
  {
    $storeId = $object->getStoreId();
    $websiteId = null;
    if ($this->getAttribute()->isScopeGlobal()) {
      $websiteId = 0;
    } else if ($storeId) {
      $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
    }

    $data = $this->_getResource()->loadPriceData($object->getId(), $websiteId);
    foreach ($data as $k => $v) {
      $data[$k]['website_price'] = $v['price'];
    }

    if (!$object->getData('_edit_mode') && $websiteId) {
      $rates = $this->_getWebsiteRates();

      $full = $data;
      $data = array();
      foreach ($full as $v) {
        $key = join('-', array($v['units'], $v['price_qty']));
        if ($v['website_id'] == $websiteId) {
          $data[$key] = $v;
          $data[$key]['website_price'] = $v['price'];
        } else if ($v['website_id'] == 0 && !isset($data[$key])) {
          $data[$key] = $v;
          $data[$key]['website_id'] = $websiteId;
          if ($object->getPriceModel()->isFixedPriceFixed()) {
            $data[$key]['price'] = $v['price'] * $rates[$websiteId]['rate'];
            $data[$key]['website_price'] = $v['price'] * $rates[$websiteId]['rate'];
          }
        }
      }
    }

    $object->setData($this->getAttribute()->getName(), $data);
    $object->setOrigData($this->getAttribute()->getName(), $data);

    $valueChangedKey = $this->getAttribute()->getName() . '_changed';
    $object->setOrigData($valueChangedKey, 0);
    $object->setData($valueChangedKey, 0);

    $this->setProductOptions($object);

    return $this;
  }

  /**
   * After Save Attribute manipulation
   *
   * @param Mage_Catalog_Model_Product $object
   * @return Mage_Catalog_Model_Product_Attribute_Backend_Fixedprices
   */
  public function afterSave ($object)
  {
    $websiteId = Mage::app()->getStore($object->getStoreId())->getWebsiteId();
    $isGlobal = $this->getAttribute()->isScopeGlobal() || $websiteId == 0;

    $fixedPrices = $object->getData($this->getAttribute()->getName());
    if (empty($fixedPrices)) {
      if ($isGlobal) {
        $this->_getResource()->deletePriceData($object->getId());
      } else {
        $this->_getResource()->deletePriceData($object->getId(), $websiteId);
      }
      return $this;
    }

    $old = array();
    $new = array();

    // prepare original data for compare
    $origFixedPrices = $object->getOrigData($this->getAttribute()->getName());
    if (!is_array($origFixedPrices)) {
      $origFixedPrices = array();
    }
    foreach ($origFixedPrices as $data) {
      if ($data['website_id'] > 0 || ($data['website_id'] == '0' && $isGlobal)) {
        $key = join('-', array($data['website_id'], $data['units'], $data['price_qty'] * 1));
        $old[$key] = $data;
      }
    }
    $default_index = -1;                        // init default index to non possible value
    if (isset($fixedPrices['active'])) {          // if active is set in post
      $default_index = $fixedPrices['active'];  // grab the index
      unset($fixedPrices['active']);            // and unset it
    }
    // prepare data for save
    foreach ($fixedPrices as $k => $data) {
      if (empty($data['price_qty']) || !isset($data['units']) || !empty($data['delete'])) {
        continue;
      }
      if ($this->getAttribute()->isScopeGlobal() && $data['website_id'] > 0) {
        continue;
      }
      if (!$isGlobal && (int) $data['website_id'] == 0) {
        continue;
      }

      $key = join('-', array($data['website_id'], $data['units'], $data['price_qty'] * 1));

      $new[$key] = array(
          'website_id' => $data['website_id'],
          'units' => $data['units'],
          'qty' => $data['price_qty'],
          'value' => $data['price'],
          'is_active' => ($k == $default_index) ? 1 : 0,
          'order' => (isset($data['order']) && $data['order'] != 0) ? $data['order']: $k +1, // if no order is set use the order of adding (this will happen only on initial FQ adding
      );
    }

    $delete = array_diff_key($old, $new);
    $insert = array_diff_key($new, $old);
    $update = array_intersect_key($new, $old);

    $isChanged = false;
    $productId = $object->getId();

    $active = null;

    if (!empty($delete)) {
      foreach ($delete as $data) {
        $this->_getResource()->deletePriceData($productId, null, $data['price_id']);
        $isChanged = true;
      }
    }

    if (!empty($insert)) {
      foreach ($insert as $data) {
        $price = new Varien_Object($data);
        $price->setEntityId($productId);
        $this->_getResource()->savePriceData($price);

        $active = $this->_minPrice($active, $data['value']);
        $isChanged = true;
      }
    }

    if (!empty($update)) {
      foreach ($update as $k => $v) {
        if (   $old[$k]['price']  != $v['value']
            || $old[$k]['active'] != $v['is_active']
            || $old[$k]['order']  != $v['order']) {
          $price = new Varien_Object(array(
                      'value_id' => $old[$k]['price_id'],
                      'value' => $v['value'],
                      'is_active' => $v['is_active'],
                      'order' => $v['order'],
                  ));
          $this->_getResource()->savePriceData($price);
          $isChanged = true;
        }
        $active = $this->_minPrice($active, $v['value']);
      }
    }

    if ($isChanged) {
      $valueChangedKey = $this->getAttribute()->getName() . '_changed';
      $object->setData($valueChangedKey, 1);
      if ($active !== false) {
        $object->addAttributeUpdate($this->getAttribute()->getName(), $active, 0);
        $object->addAttributeUpdate('minimal_price', $active, 0);
      }
    }

    return $this;
  }

  public function beforeSave ($object)
  {
    parent::beforeSave($object);
    $this->setProductOptions($object);
  }

  protected function setProductOptions($product)
  {
    if(!Mage::helper('fixedprices')->isFixedPriceEnabled($product)){
      return;
    }
    if (!$product->getRequiredOptions()) {
      $product->setRequiredOptions(true);
    }
    if (!$product->hasOptions()) {
      $product->setHasOptions(true);
    }
  }

  protected function _minPrice ($currPrice, $newPrice)
  {
    if (null == $currPrice) {
      return $newPrice;
    }

    return min($currPrice, $newPrice);
  }
}
