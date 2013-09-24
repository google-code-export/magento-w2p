<?php

class ZetaPrints_WebToPrint_Block_Customer_Wishlist_Item_Column_Comment
  extends Mage_Wishlist_Block_Customer_Wishlist_Item_Column_Comment {

  public function getProductUrl ($item, $additional = array()) {
    $product = $item instanceof Mage_Catalog_Model_Product
                 ? $item
                   : $item->getProduct();

    return $product->isVisibleInSiteVisibility()
            ? $this->getItemConfigureUrl($item)
              : parent::getProductUrl($item, $additional);
  }
}
