<?php

class ZetaPrints_Moxi_Helper_Data extends Mage_Core_Helper_Abstract {
  const API_PATH = 'www/api/v2/xmlrpc/';

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
}
