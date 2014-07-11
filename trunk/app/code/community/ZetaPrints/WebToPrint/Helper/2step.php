<?php

class ZetaPrints_WebToPrint_Helper_2step
  extends ZetaPrints_WebToPrint_Helper_Data
{
  public function isPersonalisationStep () {
    return Mage::registry('webtoprint_is_personalisation_step');
  }
}
