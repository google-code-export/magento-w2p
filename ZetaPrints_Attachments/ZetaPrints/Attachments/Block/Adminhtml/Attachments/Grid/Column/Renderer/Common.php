<?php
class ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Common
  extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
  protected function getLinkHtml($value, $href, $class = 'zp-att-link')
  {
    $link = '<a href="%s" title="%2$s" class="%3$s">%2$s</a>';

    return sprintf($link, $href, $value, $class);
  }
}
