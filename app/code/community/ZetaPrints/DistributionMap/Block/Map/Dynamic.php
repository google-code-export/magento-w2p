<?php
class ZetaPrints_DistributionMap_Block_Map_Dynamic
    extends ZetaPrints_DistributionMap_Block_Map_Abstract
{

  const API_VERSION = '3.4';
  public function escapeHint($hint)
  {
    $hint = $this->escapeHtml($hint);
    // add some breaks
    $hint = nl2br($hint);
    // explode to lines
    $lines = explode(PHP_EOL, $hint);
    array_walk($lines, array($this, 'trim'));
    $hint = implode('', $lines);
    return $hint;
  }

  public function trim(&$item)
  {
    $item = trim($item);
  }



  public function getMapApi()
  {
    return self::API_VERSION;
  }




  public function getMarkerIconUrl()
  {
    $url = $this->getSkinUrl('images/pencil_marker.png');
    return $url;
  }
}
