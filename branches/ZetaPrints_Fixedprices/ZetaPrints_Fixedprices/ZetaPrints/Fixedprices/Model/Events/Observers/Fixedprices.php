<?php

class ZetaPrints_Fixedprices_Model_Events_Observers_Fixedprices
{
  const COOKIE_NAME = 'fp_items';
  public function getItemId($observer){
    $quote_items = $observer->getEvent()->getCart()->getItems(); // get all items
    $cookie = '';
    $ids = array();

    foreach ($quote_items as $quote_item){ // loop them
      /*@var $quote_item Mage_Sales_Model_Quote_Item */
      $product = $quote_item->getProduct();
      if ($product->getData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE)){ // if item product uses FQ
        $id = $quote_item->getId(); // get quote item id - it should be unique for each product you add


        if(!in_array($id, $ids)){
          $ids[] = $id;
        }
      }
    } // end foreach

    $cookie = implode(',', $ids);
    $path = rtrim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK), '/');
    $fullpath = Mage::getUrl('checkout/cart');
    $path = str_ireplace($path, '', $fullpath);
    setcookie(self::COOKIE_NAME, $cookie, 0, $path);
  }
}

