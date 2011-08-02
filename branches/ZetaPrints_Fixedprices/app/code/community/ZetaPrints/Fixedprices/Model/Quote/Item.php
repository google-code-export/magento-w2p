<?php
class ZetaPrints_Fixedprices_Model_Quote_Item extends Mage_Sales_Model_Quote_Item
{
  const FIXED_NAME = 'fixed_name_set';

  public function representProduct($product)
  {
    // fixed price is not base for 2 separate products or product versions to be added to cart
    // so by default only QTY will be added to same item, which is not suitable for fixed prices
    $represent = parent::representProduct($product); // check representation
    if(true === $represent){// if this is identical product
      // we check if we use fixed prices, and we have some set
      if (Mage::helper('fixedprices')->isFixedPriceEnabled($product)
          && $product->getData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE)) {
        $represent = false; // if we do, then we want another item in quote
      }
    }

    return $represent;
  }

  public function setQty($qty)
  {
    parent::setQty($qty);
    $this->_setName($qty);
    return $this;
  }

  public function getProduct()
  {
    $product = parent::getProduct();
    if(Mage::helper('fixedprices')->isFixedPriceEnabled($product)){
      $this->_setName($this->getQty(), $product);
    }
    return $product;
  }


  protected function _setName($qty = null, $product = null)
  {
    if(null == $qty){
      $qty = $this->getQty();
    }

    if(null == $product){
      $product = $this->getProduct();
    }

    if($qty > 0 && $product instanceof Mage_Catalog_Model_Product) {
      $name = Mage::helper('fixedprices')->getFixedUnits($product, $this->getQty());
      if ($name !== false) {
        if(!$product->hasData('orig_name')) {
            $product->setData('orig_name', $product->getName());
        }
        $name = $product->getData('orig_name') . ' - ' . $name;
        $this->setName($name);
        $product->setName($name);
        return $this;
      }
    }
  }

}