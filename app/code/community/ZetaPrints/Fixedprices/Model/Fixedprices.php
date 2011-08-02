<?php
class ZetaPrints_Fixedprices_Model_Fixedprices
 extends Mage_Catalog_Model_Product_Type_Price
{
  /**
   * @see Mage_Catalog_Model_Product_Type_Price::getPrice()
   * @param $product Mage_Catalog_Model_Product
   */
  public function getPrice($product)
  {
    $price = parent::getPrice($product);
    if(Mage::helper('fixedprices')->isFixedPriceEnabled($product)){
      $fixedPrices = $product->getData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE);
      // get fixed prices, if we have an array with at least one member we set its price
      if(!empty($fixedPrices)){
        if(is_array($fixedPrices) && isset($fixedPrices[0])){
          $temp = false; // no default price
          $qty = $fixedPrices[0]['price_qty'];
          foreach ($fixedPrices as $idx => $fPrice) {
            if($fPrice['active'] == 1){
              $temp = $idx;
              $qty = $fPrice['price_qty'];
            }
          }
          $price = ($temp !== false) ? $fixedPrices[$temp]['price']/$qty : $fixedPrices[0]['price']/$qty;
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
        $finalPrice = $this->_applySpecialPrice($product, $finalPrice);
        $product->setFinalPrice($finalPrice);

        Mage::dispatchEvent('catalog_product_get_final_price', array('product'=>$product, 'qty' => $qty));

        $finalPrice = $product->getData('final_price');
        $finalPrice = $this->_applyOptionsPrice($product, $qty, $finalPrice);
      }
    }
      return max(0, $finalPrice);
  }

  /**
   * When using fixed prices, disable tier prices.
   * @see Mage_Catalog_Model_Product_Type_Price::getFormatedTierPrice()
   */
  public function getFormatedTierPrice($qty = null, $product){
    if (Mage::helper('fixedprices')->isFixedPriceEnabled($product)) {
      return array();
    }
    return parent::getFormatedTierPrice($qty, $product);
  }

  public function getTierPrice($qty = null, $product)
  {
    if (Mage::helper('fixedprices')->isFixedPriceEnabled($product)) {
      return array ();
    }
    return parent::getTierPrice($qty, $product);
  }

  public function getTierPriceCount($product)
  {
    if (Mage::helper('fixedprices')->isFixedPriceEnabled($product)) {
      return 0;
    }
    return parent::getTierPriceCount($product);
  }
}