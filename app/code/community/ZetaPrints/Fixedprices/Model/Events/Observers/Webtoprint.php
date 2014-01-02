<?php

class ZetaPrints_Fixedprices_Model_Events_Observers_Webtoprint {
  public function processQuantities ($observer) {
    $params = $observer->getData('params');

    if (!$params['process-quantities'])
      return;

    $product = $observer->getData('product');
    $template = $observer->getData('template');

    if (!(isset($template['quantities']) && $template['quantities']))
      return $product
               ->setData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE, null);

    foreach ($template['quantities'] as $quantity)
      $quantities[] = array(
        'website_id' => 0,
        'units' => $quantity['title'],
        'price_qty' => 1,
        'price' => $quantity['price'],
      );

    $product
      ->setData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE, $quantities);
  }
}
