<?php
class ZetaPrints_Fixedprices_Model_Events_Observers_Product_Collection {

  /**
   * Update some of the prices in product collection
   *
   * When product collection is fetched some prices need to
   * be updated with fixed price.
   *
   * @param Varien_Event_Observer $observer
   */
  public function updatePrices (Varien_Event_Observer $observer) {
    $collection = $observer->getCollection();

    /*@var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
    foreach ($collection as $item) {
      /* @var $item Mage_Catalog_Model_Product */
      if (!Mage::helper('fixedprices')->isFixedPriceEnabled($item))
        continue;

      $price = $item->getPrice();

      // if price returned from method is different than raw 'price' data
      // and we have fixed price
      if ($price != $item->getData('price') && $item->getFixedPrice())
        $item->setMinPrice($price)
          ->setMinimalPrice($price)
          ->setMaxPrice($price)
          ->setFinalPrice($price);
    }
  }
}
