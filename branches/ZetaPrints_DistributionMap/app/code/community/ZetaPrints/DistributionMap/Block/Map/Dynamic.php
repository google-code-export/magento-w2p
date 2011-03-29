<?php
class ZetaPrints_DistributionMap_Block_Map_Dynamic
    extends ZetaPrints_DistributionMap_Block_Map_Abstract
{
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
}
