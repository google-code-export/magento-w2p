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
    if(!$this->isFixedPriceEnabled($product)){ // if product is not fixed price enabled
      return false;
    }

    $prices = $product->getData(self::FIXED_PRICE);

    foreach ($prices as $price) {
      if ($price['price_qty'] == $qty){ // if price qty is matched, return that price, else return false
        return $price['price']/$qty;
      }
    }

    return false;
  }

  public function getFixedUnits(Mage_Catalog_Model_Product $product, $qty)
  {
    if(!$this->isFixedPriceEnabled($product)){ // if product is not fixed price enabled
      return false;
    }

    $prices = $product->getData(self::FIXED_PRICE);

    foreach ($prices as $price) {
      if ($price['price_qty'] == $qty){ // if price qty is matched, return that price, else return false
        return $price['units'];
      }
    }

    return false;
  }

  public function getAttributes(Mage_Catalog_Model_Product $product)
  {
    $attributes = array();
    $productAttributes = $product->getAttributes();
    foreach ($productAttributes as $attr) {
      $code = $attr->getAttributeCode();
      if(in_array($code, $this->_attributes)){
        $attributes[$code] = $attr;
      }
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

  public function isFixedPriceEnabled(Mage_Catalog_Model_Product $product){
    if(!$product->hasData(self::FIXED_PRICE) && $product->getId()){
      $product->load($product->getId());
    }
    $use = $product->getData(self::FIXED_PRICE);
    return !empty($use);
  }
}