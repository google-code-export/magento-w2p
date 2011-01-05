<?php

class ZetaPrints_Fixedprices_Model_Fixedprices extends Mage_Catalog_Model_Product_Type_Price
{
  /**
   * @see Mage_Catalog_Model_Product_Type_Price::getPrice()
   * @param $product Mage_Catalog_Model_Product
   */
  public function getPrice(Mage_Catalog_Model_Product $product)
  {
    $price = parent::getPrice($product);
    if(Mage::helper('fixedprices')->isFixedPriceEnabled($product)){
      $fixedPrices = $product->getData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE);
      // get fixed prices, if we have an array with at least one member we set its price
      if(!empty($fixedPrices)){
        if(is_array($fixedPrices) && isset($fixedPrices[0])){
          $temp = false; // no default price
          foreach ($fixedPrices as $idx => $fPrice) {
            if($fPrice['active'] == 1){
              $temp = $idx;
            }
          }
          $price = ($temp !== false) ? $fixedPrices[$temp]['price'] : $fixedPrices[0]['price'];
        }else{
          $price = $fixedPrices;
        }
      }
    }

    return $price;
  }

  public function getFinalPrice($qty=null, $product)
  {
    /* @var $product Mage_Catalog_Model_Product */
    $finalPrice = parent::getFinalPrice($qty, $product);
    if (Mage::helper('fixedprices')->isFixedPriceEnabled($product)) {
      if (is_null($qty)) {
        return $finalPrice;
      }

      $fixedPrice = Mage::helper('fixedprices')->getFixedPrice($product, $qty);
      if($fixedPrice !== false){
        $finalPrice = $fixedPrice;
      }
    }
    return max(0, $finalPrice);
  }
}