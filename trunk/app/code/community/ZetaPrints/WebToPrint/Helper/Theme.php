<?php

class ZetaPrints_WebToPrint_Helper_Theme
  extends ZetaPrints_WebToPrint_Helper_Data
{

  public function getAddtocartButtonId () {
    return version_compare(Mage::getVersion(), '1.9', '>=')
             ? 'product-addtocart-button'
               : 'zetaprints-add-to-cart-button';
  }

}