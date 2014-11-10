<?php

class ZetaPrints_WebToPrint_Model_Item extends Mage_Wishlist_Model_Item {

  public function representProduct ($product) {
    return $product->getWebtoprintTemplate()
             ? false
               : parent::representProduct($product);
  }
}
