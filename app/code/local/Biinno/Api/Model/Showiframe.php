<?php
class Biinno_Api_Model_Showiframe
{
  protected $_arr;

  public function toOptionArray () {
    return array(
             array('value'=>'show_iframe_always', 'label'=>Mage::helper('adminhtml')->__('Always')),
             array('value'=> 'show_iframe_on_multipage', 'label'=>Mage::helper('adminhtml')->__('Only on multi-page templates')) );
  }

  public function toArray () {
    if (!$this->_arr){
      $options = $this->toOptionArray();

      foreach ($options as $option) {
        $this->_arr[$option['value']] = $option['label'];
      }
    }

    return $this->_arr;
  }
}
