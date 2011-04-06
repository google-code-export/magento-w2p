<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 4/6/11
 * Time: 7:36 PM
 */

class ZetaPrints_DistributionMap_Model_Source_Zoom
{
  /**
   * Return formatted zoom levels
   *      
   * @return array
   */
  public function toOptionArray()
  {
    $zoom_levels = range(0, 20, 1);
    $labels = array(
      1 => 'Entire earth',
      4 => 'Big country - US, China, Russia, Australia',
      8 => 'State',
      12 => 'Town, suburb',
      15 => 'Neighbourhood',
      18 => 'Street block',
      20 => 'Individual building',
    );
    foreach($zoom_levels as $key => $val) {
      if(isset($labels[$key])) {
        $zoom_levels[$key] = $val . ' - ' . $labels[$key];
      }else {
        $zoom_levels[$key] = (string)$val;
      }
    }
    return $zoom_levels;
  }
}
