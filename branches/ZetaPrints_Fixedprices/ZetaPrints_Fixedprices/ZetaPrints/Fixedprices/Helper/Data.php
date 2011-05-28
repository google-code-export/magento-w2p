<?php

class ZetaPrints_Fixedprices_Helper_Data extends Mage_Core_Helper_Abstract
{
  protected $_attributes = array(
    self::FIXED_PRICE,
    self::USE_FIXED_PRICE,
  );

  const FIXED_PRICE = 'fixed_price';
  const USE_FIXED_PRICE = 'use_fixed_price';
  const TAB_NAME = 'fixed_price_tab';

  public function getFixedPrice(Mage_Catalog_Model_Product $product, $qty)
  {
    if (!$this->isFixedPriceEnabled($product)) { // if product is not fixed price enabled
      return false;
    }

    $prices = $product->getData(self::FIXED_PRICE);
    if(!is_array($prices)) { // if no complete load is done, FQ will be decimal and not an array. So we have to load it.
      $backend = $product->getResource()
                         ->getAttribute(self::FIXED_PRICE)
                         ->getBackend();
      if($backend instanceof ZetaPrints_Fixedprices_Model_Product_Attribute_Backend_Fixedprices) {
        /* @var $backend ZetaPrints_Fixedprices_Model_Product_Attribute_Backend_Fixedprices */
        $backend->afterLoad($product);
      }
      $prices = $product->getData(self::FIXED_PRICE);
    }

    if(is_array($prices)){
      foreach ($prices as $price) {
        if ($price['price_qty'] == $qty) { // if price qty is matched, return that price, else return false
          return $price['price'] / $qty;
        }
      }
    }

    return false;
  }

  public function getFixedUnits(Mage_Catalog_Model_Product $product, $qty)
  {
    if (!$this->isFixedPriceEnabled($product)) { // if product is not fixed price enabled
      return false;
    }

    $prices = $product->getData(self::FIXED_PRICE);

    foreach ($prices as $price) {
      if ($price['price_qty'] == $qty) { // if price qty is matched, return that price, else return false
        return $price['units'];
      }
    }

    return false;
  }

  public function getAttributes(Mage_Catalog_Model_Product $product)
  {
    $attributes = array();
    try {
      foreach ($this->_attributes as $attr) {
        $attribute = $product->getResource()->getAttribute($attr);
        if ($attribute) {
          $attributes[$attr] = $attribute;
        }
      }
    } catch (Exception $e) {
      return $attributes;
    }
    return $attributes;
  }

  public function getAttributeCodes()
  {
    return $this->_attributes;
  }

  public function getTabName()
  {
    return self::TAB_NAME;
  }

  public function isFixedPriceEnabled(Mage_Catalog_Model_Product $product)
  {
    if (!$product->hasData(self::FIXED_PRICE) && $product->getId()) {
      $attribute = $product->getResource()->getAttributeRawValue($product->getId(), self::FIXED_PRICE, $product->getStore());
      $product->setData(self::FIXED_PRICE, $attribute);
    }
    $use = $product->getData(self::FIXED_PRICE);
    return !empty($use);
  }
}
