<?php

class ZetaPrints_Moxi_Helper_Data extends Mage_Core_Helper_Abstract {
  const API_PATH = 'www/api/v2/xmlrpc/';

  const CPM_PRICING_MODEL = 1;
  const CPC_PRICING_MODEL = 2;
  const CPA_PRICING_MODEL = 3;
  const MT_PRICING_MODEL = 4;

  private $_api = null;

  public function _getApi () {
    if (! $this->_api) {
      $url = Mage::getStoreConfig('moxi/settings/url');

      $position = strpos($url, '/', 8);

      $host = substr($url, 0, $position);
      $path = substr($url, $position) . self::API_PATH;

      $login = Mage::getStoreConfig('moxi/settings/login');
      $password = Mage::getStoreConfig('moxi/settings/password');

      $this->_api = new OpenX_Api($host, $path, $login, $password);
    }

    return $this->_api;
  }

  public function getManagers () {
    $api = $this->_getApi();

    $managers = new Varien_Data_Collection();

    foreach ($api->getAgencyList() as $agency)
      $managers
        ->addItem(new Varien_Object(array('id' => $agency->agencyId,
                                          'name' => $agency->agencyName,
                                          'contact' => $agency->contactName,
                                          'email' => $agency->emailAddress )));

    return $managers;
  }

  public function getSitesByManager ($manager) {
    $api = $this->_getApi();

    $id = $manager->getId();

    $sites = new Varien_Data_Collection();

    foreach ($api->getPublisherListByAgencyId($id) as $publisher)
      $sites
        ->addItem(new Varien_Object(
                    array('id' => $publisher->publisherId,
                          'manager' => $publisher->agencyId == $id
                                         ? $manager : $publisher->agencyId,
                          'name' => $publisher->publisherName,
                          'contact' => $publisher->contactName,
                          'email' => $publisher->emailAddress,
                          'url' => $publisher->website,
                          'comments' => $publisher->comments ) ) );

    return $sites;
  }

  public function getZone ($zoneId) {
    $api = $this->_getApi();

    $zone = $api->getZone($zoneId);

    return new Varien_Object(array('id' => $zone->zoneId,
                                   'site' => $zone->publisherId,
                                   'name' => $zone->zoneName,
                                   'type' => $zone->type,
                                   'width' => $zone->width,
                                   'height' => $zone->height,
                                   'capping' => $zone->capping,
                                   'session_capping' => $zone->sessionCapping,
                                   'block' => $zone->block,
                                   'comments' => $zone->comments,
                                   'append' => $zone->append,
                                   'prepend' => $zone->prepend ));
  }

  public function getZonesBySite ($site) {
    $api = $this->_getApi();

    $id = $site->getId();

    $zones = new Varien_Data_Collection();

    foreach ($api->getZoneListByPublisherId($id) as $zone)
      $zones
        ->addItem(new Varien_Object(
                    array('id' => $zone->zoneId,
                          'site' => $zone->publisherId == $id
                                      ? $site : $zone->publisherId,
                          'name' => $zone->zoneName,
                          'type' => $zone->type,
                          'width' => $zone->width,
                          'height' => $zone->height,
                          'capping' => $zone->capping,
                          'session_capping' => $zone->sessionCapping,
                          'block' => $zone->block,
                          'comments' => $zone->comments,
                          'append' => $zone->append,
                          'prepend' => $zone->prepend )));

    return $zones;
  }

  public function getCampaignsByAdvertiser ($advertiser) {
    $api = $this->_getApi();

    $id = $advertiser->getId();

    $campaigns = new Varien_Data_Collection();

    foreach ($api->getCampaignListByAdvertiserId($id) as $campaign)
      $campaigns
        ->addItem(
          new Varien_Object(
            array('id' => $campaign->campaignId,
                  'advertiser' => $campaign->advertiserId == $id
                                    ? $advertiser : $campaign->advertiserId,
                  'name' => $campaign->campaignName,
                  'begin' => $campaign->startDate,
                  'end' => $campaign->endDate,
                  'impressions' => $campaign->impressions,
                  'clicks' => $campaign->clicks,
                  'priority' => $campaign->priority,
                  'weight' => $campaign->weight,
                  'target_impressions' => $campaign->targetImpressions,
                  'target_clicks' => $campaign->targetClicks,
                  'target_conversions' => $campaign->targetConversions,
                  'revenue' => $campaign->revenue,
                  'revenue_type' => $campaign->revenueType,
                  'capping' => $campaign->capping,
                  'session_capping' => $campaign->sessionCapping,
                  'block' => $campaign->block,
                  'comments' => $campaign->comments )));

    return $campaigns;
  }

  public function addAdvertiser ($name, $email) {
    $api = $this->_getApi();

    $managerId = (int) Mage::getStoreConfig('moxi/settings/manager');

    $advertiser = new OA_Dll_AdvertiserInfo();

    $advertiser->advertiserName = $name;
    $advertiser->contactName = $name;
    $advertiser->emailAddress = $email;
    $advertiser->agencyId = $managerId;

    $result = $api->addAdvertiser($advertiser);

    if (!$result)
      return false;

    return new Varien_Object(array('id' => $result,
                                   'manager' => $managerId,
                                   'name' => $name,
                                   'contact_name' => $name,
                                   'email' => $email,
                                   'comments' => '' ));
  }

  public function addCampaign ($advertiser, $name, $begin, $end) {
    $api = $this->_getApi();

    $campaign = new OA_Dll_CampaignInfo();

    $name = "{$advertiser->getName()} - {$name}";
    $begin = new DateTime($begin);
    $end = new DateTime($end);

    $campaign->advertiserId = $advertiser->getId();
    $campaign->campaignName = $name;
    $campaign->startDate = $begin;
    $campaign->endDate = $end;

    $result = $api->addCampaign($campaign);

    if (!$result)
      return false;

    return new Varien_Object(array('id' => $result,
                                   'advertiser' => $advertiser,
                                   'name' => $name,
                                   'begin' => $begin,
                                   'end' => $end ));
  }

  public function addBanner ($campaign, $name, $url, $content) {
    $api = $this->_getApi();

    $banner = new OA_Dll_BannerInfo();

    $banner->campaignId = $campaign->getId();
    $banner->bannerName = $name;
    $banner->storageType = 'web';
    $banner->url = $url;
    $banner->aImage = array('filename' => $name, 'content' => $content);

    $result = $api->addBanner($banner);

    if (!$result)
      return false;

    return new Varien_Object(array('id' => $result,
                                   'campaign' => $campaign,
                                   'name' => $name,
                                   'storage_type' => 'web',
                                   'url' => $url ));
  }

  public function linkBannerToZone ($banner, $zone) {
    $api = $this->_getApi();

    return $api->linkBanner($zone->getId(), $banner->getId());
  }

  public function linkCampaignToZone ($campaign, $zone) {
    $api = $this->_getApi();

    return $api->linkCampaign($zone->getId(), $campaign->getId());
  }
}
