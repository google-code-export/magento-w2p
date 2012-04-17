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
        'url' => $block->getUrl('moxi-admin/catalog_product_campaign/campaign',
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

      if (! $item->getProductId())
        continue;

      $zoneId = Mage::getResourceModel('catalog/product')
                  ->getAttributeRawValue($item->getProductId(),
                                         'openx_zone_id',
                                         $item->getStoreId());

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

      $campaign = $helper
                    ->addCampaign($advertiser, $zone->getName(), $begin, $end);

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
