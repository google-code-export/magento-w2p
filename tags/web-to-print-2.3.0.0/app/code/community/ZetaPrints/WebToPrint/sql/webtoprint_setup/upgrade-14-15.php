<?php

$profiles = array(
  array(
    'name' => 'ZetaPrints templates synchronization',
    'xml' => '<action '
           .   'type="webtoprint/templates-synchronization" '
           .   'method="parse"'
           . '/>'
           . "\n"
           . '<action '
           .   'type="webtoprint/products-updating" '
           .   'method="map" '
           .   'process-quantities="yes" '
           . '/>'
  ),
  array(
    'name' => 'ZetaPrints simple products creation',
    'xml' => '<action '
           .   'type="webtoprint/products-creation" '
           .   'method="map" '
           .   'product-type="simple" '
           .   'process-quantities="yes" '
           . '/>'
  ),
  array(
    'name' => 'ZetaPrints virtual products creation',
    'xml' => '<action '
           .   'type="webtoprint/products-creation" '
           .   'method="map" '
           .   'product-type="virtual" '
           .   'process-quantities="yes" '
           . '/>'
  )
);

foreach ($profiles as $profile) {
  $profile_model = Mage::getModel('dataflow/profile');

  if ($profile_model->getResource()->isProfileExists($profile['name'])) {
    $collection = $profile_model->getCollection();
    $collection->getSelect()->where('name = ?', $profile['name']);

    if ($collection->count() == 1)
      $profile_model = $collection->getFirstItem();
  }

  $profile_model
    ->setName($profile['name'])
    ->setActionsXml($profile['xml'])
    ->setGuiData(false)
    ->setDataTransfer('interactive')
    ->save();
}

?>
