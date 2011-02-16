<?php

class ZetaPrints_WebToPrint_Model_Config extends Mage_Core_Model_Config_Base {

  /**
   * Key name for storage of cache data
   *
   * @var string
   */
  const CACHE_KEY = 'WEBTOPRINT';

  /**
   * Tag name for cache type, used in mass cache cleaning
   *
   * @var string
   */
  const CACHE_TAG = 'WEBTOPRINT_CUSTOM_OPTIONS';

  /**
   * Filename that will be collected from different modules
   *
   * @var string
   */
  const CONFIG_FILENAME = 'custom-options.xml';

  /**
   * Initial configuration file template, then merged in one file
   *
   * @var string
   */
  const CONFIG_TEMPLATE = '<?xml version="1.0"?><config></config>';

  /**
   * Constructor
   *
   * Loading of custom configuration files
   * from different modules
   *
   * @param string $sourceData
   */
  function __construct ($sourceData = null) {
    $tags = array (self::CACHE_TAG);
    $useCache = Mage::app()->useCache(self::CACHE_TAG);

    $this->setCacheId(self::CACHE_KEY);
    $this->setCacheTags($tags);

    if ($useCache && ($cache = Mage::app()->loadCache(self::CACHE_KEY)))
      parent::__construct($cache);
    else {
      parent::__construct(self::CONFIG_TEMPLATE);

      Mage::getConfig()
        ->loadModulesConfiguration(self::CONFIG_FILENAME, $this);

      if ($useCache) {
        $xmlString = $this->getXmlString();
        Mage::app()->saveCache($xmlString, self::CACHE_KEY, $tags);
      }
    }
  }
}
