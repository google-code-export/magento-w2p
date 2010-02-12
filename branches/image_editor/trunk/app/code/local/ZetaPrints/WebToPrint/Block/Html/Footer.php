<?php

class ZetaPrints_WebToPrint_Block_Html_Footer extends Mage_Page_Block_Html_Footer {
  public function getCopyright() {
    return parent::getCopyright() . '<br /><a href="http://www.zetaprints.com/">Web-to-print and image generation, v. 1.7.0.0</a>';
  }
}

?>
