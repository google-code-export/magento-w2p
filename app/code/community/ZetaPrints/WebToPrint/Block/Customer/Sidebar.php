<?php

class ZetaPrints_WebToPrint_Block_Customer_Sidebar
  extends Mage_Wishlist_Block_Customer_Sidebar {

  public function getProductUrl ($item, $additional = array()) {
    $product = $item instanceof Mage_Catalog_Model_Product
                 ? $item
                   : $item->getProduct();

    return $product->isVisibleInSiteVisibility()
            ? $this->getItemConfigureUrl($item)
              : parent::getProductUrl($item, $additional);
  }
}
