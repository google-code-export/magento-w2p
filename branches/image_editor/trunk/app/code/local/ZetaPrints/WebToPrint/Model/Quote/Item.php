<?php

class ZetaPrints_WebToPrint_Model_Quote_Item extends Mage_Sales_Model_Quote_Item {
  public function representProduct ($product) {
    if ($product->getWebtoprintTemplate())
      return false;

    return parent::representProduct($product);
  }
}

?>
