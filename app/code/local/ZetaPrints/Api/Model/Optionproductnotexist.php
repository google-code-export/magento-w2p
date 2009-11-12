<?php
class Biinno_Api_Model_Optionproductnotexist
{
	protected $_arr;
    public function toOptionArray()
    {
        return array(
			array('value'=>'private_not_exist_ignore', 'label'=>Mage::helper('adminhtml')->__('Ignore - Do not import')),
            array('value'=>'private_not_exist_invisible', 'label'=>Mage::helper('adminhtml')->__('Make invisible')),
            array('value'=>'private_not_exist_visible', 'label'=>Mage::helper('adminhtml')->__('Make visible'))			
        );
    }
	public function toArray(){
		if (!$this->_arr){
			$options = $this->toOptionArray();
			
			foreach ($options as $option){
				$this->_arr[$option['value']] = $option['label'];
			}
		}
		return $this->_arr;
	}

}