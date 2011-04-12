<?php
class ZetaPrints_Fixedprices_Model_Configurable_Fixedprices
  extends ZetaPrints_Fixedprices_Model_Fixedprices
{
  /**
   * Get product final price
   *
   * @param   double $qty
   * @param   Mage_Catalog_Model_Product $product
   * @return  double
   */
  public function getFinalPrice($qty = null, $product)
  {
    if (is_null($qty) && !is_null($product->getCalculatedFinalPrice())) {
      return $product->getCalculatedFinalPrice();
    }

    $finalPrice = parent::getFinalPrice($qty, $product);
    $product->getTypeInstance(true)
        ->setStoreFilter($product->getStore(), $product);
    $attributes = $product->getTypeInstance(true)
        ->getConfigurableAttributes($product);

    $selectedAttributes = array();
    if ($product->getCustomOption('attributes')) {
      $selectedAttributes = unserialize($product->getCustomOption('attributes')->getValue());
    }

    $hasFq = Mage::helper('fixedprices')->isFixedPriceEnabled($product);

    $basePrice = $finalPrice;
    foreach ($attributes as $attribute) {
      $attributeId = $attribute->getProductAttribute()->getId();
      $value = $this->_getValueByIndex(
        $attribute->getPrices() ? $attribute->getPrices() : array(),
        isset($selectedAttributes[$attributeId]) ? $selectedAttributes[$attributeId] : null
      );
      if ($value) {
        if ($value['pricing_value'] != 0) {
          $product->setConfigurablePrice($this->_calcSelectionPrice($value, $basePrice, $hasFq, $qty));
          Mage::dispatchEvent(
            'catalog_product_type_configurable_price',
            array('product' => $product)
          );
          $finalPrice += $product->getConfigurablePrice();
        }
      }
    }
    $product->setFinalPrice($finalPrice);
    return max(0, $product->getData('final_price'));
  }

  /**
   * Calculate configurable product selection price
   *
   * @param   array $priceInfo
   * @param   decimal $productPrice
   * @return  decimal
   */
  protected function _calcSelectionPrice($priceInfo, $productPrice, $hasFq = false, $qty = 1)
  {
    if ($priceInfo['is_percent']) {
      $ratio = $priceInfo['pricing_value'] / 100;
      $price = $productPrice * $ratio;
    } else {
      $price = $priceInfo['pricing_value'];
    }
    return $price;
  }

  protected function _getValueByIndex($values, $index)
  {
    foreach ($values as $value) {
      if ($value['value_index'] == $index) {
        return $value;
      }
    }
    return false;
  }
}
