<?php

class ZetaPrints_Fixedprices_Model_Status extends Varien_Object
{
    const ENABLED	= 1;
    const DISABLED	= 2;

    static public function getOptionArray()
    {
        return array(
            self::ENABLED    => Mage::helper('fixedprices')->__('Enabled'),
            self::DISABLED   => Mage::helper('fixedprices')->__('Disabled')
        );
    }
}
