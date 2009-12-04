<?php
class ZetaPrints_Zpapi_Model_Optionproductexist
{
	protected $_arr;
    public function toOptionArray()
    {
        return array(
			array('value'=>'private_exist_ignore', 'label'=>Mage::helper('adminhtml')->__('Ignore')),
            array('value'=>'private_exist_invisible', 'label'=>Mage::helper('adminhtml')->__('Change to invisible')),
            array('value'=>'private_exist_visible', 'label'=>Mage::helper('adminhtml')->__('Change to visible')),			
			array('value'=>'private_exist_import', 'label'=>Mage::helper('adminhtml')->__('Import, do not change visibility')),
			array('value'=>'private_exist_delete', 'label'=>Mage::helper('adminhtml')->__('Delete from store'))
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