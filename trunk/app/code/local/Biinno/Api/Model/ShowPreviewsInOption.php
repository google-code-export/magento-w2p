<?php
class Biinno_Api_Model_ShowPreviewsInOption
{
  protected $_arr;

  public function toOptionArray () {
    return array(
             array('value'=>'magento-form', 'label'=>Mage::helper('adminhtml')->__('Magento form')),
             array('value'=> 'zetaprints-iframe', 'label'=>Mage::helper('adminhtml')->__('ZetaPrints IFrame')) );
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
