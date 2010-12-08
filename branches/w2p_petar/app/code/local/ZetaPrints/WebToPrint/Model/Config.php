<?php
class ZetaPrints_WebToPrint_Model_Config extends Mage_Core_Model_Config_Base
{
  /**
   * Key name for storage of cache data
   *
   * @var string
   */
  const CACHE_KEY_NAME = 'W2P_CUSTOMCONFIG';

  /**
   * Tag name for cache type, used in mass cache cleaning
   *
   * @var string
   */
  const CACHE_TAG_NAME = 'W2P';

  /**
   * Filename that will be collected from different modules
   *
   * @var string
   */
  const CONFIGURATION_FILENAME = 'w2p.xml';
  /**
   * Initial configuration file template, then merged in one file
   *
   * @var string
   */
  const CONFIGURATION_TEMPLATE = '<?xml version="1.0"?><config></config>';

  /**
   * Constructor
   *
   * Loading of custom configuration files
   * from different modules
   *
   * @param string $sourceData
   */
  function __construct($sourceData = null)
  {
    $tags = array (self::CACHE_TAG_NAME);
    $useCache = Mage::app()->useCache(self::CACHE_TAG_NAME);
    $this->setCacheId(self::CACHE_KEY_NAME);
    $this->setCacheTags($tags);

    if ($useCache && ($cache = Mage::app()->loadCache(self::CACHE_KEY_NAME))) {
      parent::__construct($cache);
    } else {
      parent::__construct(self::CONFIGURATION_TEMPLATE);
      Mage::getConfig()->loadModulesConfiguration(self::CONFIGURATION_FILENAME, $this);
      if ($useCache) {
        $xmlString = $this->getXmlString();
        Mage::app()->saveCache($xmlString, self::CACHE_KEY_NAME, $tags);
      }
    }
  }
}