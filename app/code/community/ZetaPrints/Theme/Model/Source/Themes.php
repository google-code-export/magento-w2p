<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 4/7/11
 * Time: 4:20 PM
 */

class ZetaPrints_Theme_Model_Source_Themes
{
  public function toOptionArray()
  {
    $themes = Mage::helper('themeswitch')->getThemes();
    foreach ($themes as $code => $name) {
      $themes[$code] = array('value' => $code, 'label' => $name);
    }
    return $themes;
  }
}
