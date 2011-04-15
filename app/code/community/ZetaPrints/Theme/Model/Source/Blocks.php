<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 4/7/11
 * Time: 4:20 PM
 */

class ZetaPrints_Theme_Model_Source_Blocks
{
  /**
   * Possible places to stick our block in.
   * @var array
   */
  protected $blocks = array(
    'right' => 'Right Sidebar',
    'left' => 'Left Sidebar',
    'after_body_start' => 'Above all content including header',
    'before_body_end' => 'Bellow all content including footer',
  );

  public function toOptionArray()
  {
    $blocks = array();
    foreach ($this->blocks as $code => $name) {
      $blocks[$code] = array('value' => $code, 'label' => $name);
    }
    return $blocks;
  }
}
