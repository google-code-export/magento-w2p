<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/24/11
 * Time: 3:15 PM
 */

class ZetaPrints_Theme_Block_Switcher
  extends ZetaPrints_Theme_Block_Abstract
{


  public function _construct()
  {
    parent::_construct();
    $this->setTemplate('theme/switcher.phtml');
  }

  /**
   * @return array;
   */
  public function getThemes()
  {
    $themes =  $this->helper->getThemes();
    $allowed = Mage::getStoreConfig('design/theme_switcher/allowed_themes');
    if(!is_array($allowed)) {
      $allowed = explode(',', $allowed);
    }
    foreach ($themes as $key => $val) {
      if (!in_array($key, $allowed)) {
        unset($themes[$key]);
      }
    }
    return $themes;
  }

  public function getCurrentTheme()
  {
    return $this->helper->getCurrentTheme();
  }

  public function getCurrentUrl($theme)
  {
    return $this->helper->getCurrentUrl($theme);
  }
}
