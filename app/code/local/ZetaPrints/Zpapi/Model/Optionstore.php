<?php
class ZetaPrints_Api_Model_Optionstore
{
	protected $_options;
	protected $_arr;
    
    public function toOptionArray()
    {
        if (!$this->_options) {
            $stores = Mage::getResourceModel('core/store_collection')
                ->load();
			foreach ($stores as $store) {
                $id = $store->getCode();
                $name = $store->getName();
                $this->_options[] = array('value'=>$id, 'label'=>$name);
            }
			
        }
        return $this->_options;
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