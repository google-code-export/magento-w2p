<?php

class ZetaPrints_Moxi_Model_Events_Observer {

  public function addCampaignSettingTab ($observer) {
    $block =  $observer->getEvent()->getBlock();

    if (! $block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs)
      return;

    if ($block->getProduct()->getAttributeSetId()
        || $block->getRequest()->getParam('set', null))
      $block->addTab('campaign', array(
        'label' => Mage::helper('moxi')->__('Advertising campaign settings'),
        'url' => $block->getUrl('moxi-admin/catalog_product_campaign/',
                                array('_current' => true) ),
        'class' => 'ajax' ) );
  }

  public function createCampaigns ($observer) {
    $order = $observer->getEvent()->getOrder();

    $helper = Mage::helper('moxi');

    $customer = Mage::getSingleton('customer/session')
                  ->getCustomer();

    $name = $customer->getName();
    $email = $customer->getEmail();

    $advertiser = $helper
                    ->addAdvertiser($name, $email);

    if (!$advertiser)
      return;

    foreach ($order->getAllItems() as $item) {
      if (! $productId = $item->getProductId())
        continue;

      $resource = Mage::getResourceModel('catalog/product');

      $storeId = $item->getStoreId();

      $zoneId = $resource
                  ->getAttributeRawValue($productId, 'openx_zone_id', $storeId);

      $zone = $helper->getZone($zoneId);

      if (! $zone->getId())
        continue;

      $options = $item->getProductOptionByCode('options');

      $begin = null;
      $end = null;

      $name = '';
      $image = null;
      $url = '';

      foreach ($options as $option) {
        switch ($option['label']) {
          case 'Banner image':
            $value = unserialize($option['option_value']);

            $name = $value['title'];
            $image = @file_get_contents($value['fullpath']);

            break;
          case 'Destination URL (incl. http://)':
            $url = $option['option_value'];

            break;
          case 'Start date':
            $begin = $option['option_value'];

            break;
          case 'End date':
            $end = $option['option_value'];

            break;
        }
      }

      if (!$image)
        continue;

      $qty = $item->getQtyOrdered();

      $pricingModel = $resource->getAttributeRawValue($productId,
                                                      'openx_pricing_model',
                                                      $storeId);

      $rate = $resource->getAttributeRawValue($productId,
                                              'openx_rate_price',
                                              $storeId);

      $impressions = $resource->getAttributeRawValue($productId,
                                                     'openx_impressions',
                                                     $storeId);

      $clicks = $resource->getAttributeRawValue($productId,
                                                'openx_clicks',
                                                $storeId);

      $conversions = $resource->getAttributeRawValue($productId,
                                                     'openx_conversions',
                                                     $storeId);

      $weight = $resource->getAttributeRawValue($productId,
                                                'openx_campaign_weight',
                                                $storeId);

      $name = "{$advertiser->getName()} - {$name}";
      $begin = new DateTime($begin);
      $end = new DateTime($end);

      $campaign = new Varien_Object();

      $campaign
        ->setName($name)
        ->setBegin($begin)
        ->setEnd($end)
        ->setAdvertiser($advertiser)
        ->setPricingModel((int) $pricingModel)
        ->setRate((float) $rate)
        ->setImpressions($impressions * $qty)
        ->setClicks($clicks * $qty)
        ->setConversions($conversions * $qty)
        ->setWeight((int) $weight);

      $campaign = $helper->addCampaign($campaign);

      if (! $campaign->getId())
        continue;

      $helper->linkCampaignToZone($campaign, $zone);

      $banner = $helper
                  ->addBanner($campaign, $name, $url, $image);

      if (! $banner->getId())
        continue;

      $helper->linkBannerToZone($banner, $zone);
    }
  }
}

?>
