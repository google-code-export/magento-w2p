<?php
class ZetaPrints_DistributionMap_Block_Map_Static
  extends ZetaPrints_DistributionMap_Block_Map_Abstract
{
  public function getBaseGoogleUrl()
  {
    $area = Mage::getDesign()->getArea() == Mage_Core_Model_Design_Package::DEFAULT_AREA ? 'cart' : 'admin';
    $options = array();
    $options['width'] = $this->getMapWidth($area);
    $options['height'] = $this->getMapHeight($area);
    $options['sensor'] = $this->getSensor();
    $options['language'] = $this->getLanguage();

    $url = Mage::helper('distro_map')->getStaticGoogleUrl($options);
    return $url;
  }
}
