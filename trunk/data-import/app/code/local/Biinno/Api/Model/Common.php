<?php
/**
  * Project	magento-w2p
  * Author	Pham Tri Cong <phtcong@gmail.com>
  * Issue 1 : Data import from W2P 
  * To run this batch, you have to add the properties to the Product.
  *	w2p_link			Template Url
  *	w2p_image			image link ** to view in Category page
  *	w2p_image_links		links to images
  *	w2p_created		created date time
  *	w2p_modified		modified date time
  *	w2p_image_large		links to large product images
  *	w2p_image_small		links to small product images 
  * Below Mapping is default field
  *	name				product name (only when a new product is created)
  *	description			product description (only when a new product is created)
  *	sku				template ID
  */
class Biinno_Api_Model_Common
    extends Mage_Core_Model_Abstract
{
	function strToDate($val){
		if (!$val) return $this->getDate();
		return $this->getDate(strtotime($val));
	}
	function getDate($time = null){
		if (!$time) return date("Y-m-d h:i:s");
		return date("Y-m-d h:i:s", $time);
	}
	/**
	  * Get data by http protocal from url
	  * param	url	Url of ZentaPrints site
	  * return 	Text Content of url
	  */
	function getHttp($url){
		try{
			$ret = file_get_contents($url);
			return $ret;
		}catch(Exception $e){
			return "";
		}
	}
	/**
	  * Get content of url then parse to array
	  * param	url	Url of ZentaPrints site
	  * return	Array
	  */
	function xml2Obj($url)
	{
		$obj = null;
		try{
			$obj = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA);
			if (!$obj) {
				//echo "can not get data from [$url] ";
				//exit ();
				return null;
			}			
		}catch(Exception $e){
			//echo "can not get data from [$url] e=" . $e;
			//	exit ();
			return null;
		}
		$obj = $this->object2array($obj);
		//print_r($obj);exit();  
	    return ($obj);
	}
	function object2array($object)
	{
		$return = NULL;

		if(is_array($object))
		{
			foreach($object as $key => $value)
				$return[strtolower($key)] = $this->object2array($value);
		}
		else
		{
			$var = get_object_vars($object);
			if($var)
			{
				foreach($var as $key => $value)
				$return[strtolower($key)] = ($key && !$value) ? NULL : $this->object2array($value);
			}
			else return $object;
		}
		return $return;
	} 
	/**
	  * Save Product data to magento db. this function use core catalog product model class of magento
	  * param	data	product information which is get from Template Detail Feed of ZentaPrints
	  * return	nothing
	  */
	function saveProduct($data){	
		if (!$data['id'] || !$data['title'] || !isset($data['cids']) || !isset($data['created'])){
			echo sprintf("******DATA ERROR:Product:id=[%s],title=[%s],cids=[%s],created=[%s]",$data['id'],$data['title'],$data['cids'],isset($data['created'])?$data['created'] : '0');
			return null;
		}
		$baseProduct = Mage::registry('product');
		//echo $base->getSku();exit();
		
		//if (!$base) return 0;
		$product = Mage::getModel('catalog/product');    
		$old = $product->getIdBySku($data['id']);
		if($old)
		{
			$product->load($old);
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 
			$product->setData("w2p_image",$data['image']);
			$product->setData("w2p_image_large",$data['image']);
			$product->setData("w2p_image_small",$data['thumbnail']);
			$product->setData("w2p_modified",$data['created']);
			$product->setData("w2p_link", $data['access_url']);
			$product->setData("w2p_isorder", $data['w2p_isorder']);
			$product->setData("w2p_image_links",$this->getImageLinks($data));
			$product->save();
			return $product;
		}
		/*$product->setWebsiteIds(array('1'));
		$product->setAttributeSetId(4);
		$product->setSku($data['id']);
		$product->setTypeId('simple'); 
		$product->setName($data['title']);
		$product->setDescription($data['description']);
		$product->setShortDescription(" ");
		$product->setPrice($data['price']);
		$product->setData("w2p_image",$data['image']);
		$product->setData("w2p_image_large",$data['image']);
		$product->setData("w2p_image_small",$data['thumbnail']);
		$product->setData("w2p_created",$data['created']);
		$product->setData("w2p_modified",$data['created']);
		$product->setData("w2p_image_links",$this->getImageLinks($data));
		$product->setData("w2p_isorder", $data['w2p_isorder']);
		$product->setData("w2p_link", $data['access_url']);
		$product->setWeight(0);
		//$product->setQty(1);
		//$product->setData("inventory_manage_stock_default",1);
		$product->setData("inventory_qty",1);
		$product->setStatus(1);
		$product->setTaxClassId(2);
		$product->setCategoryIds(array($data['cids'] =>$data['cids']));
		$product->setVisibility(4);
		*/
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 
		$product  = $baseProduct;
		$product->setId(null);
		$product->setSku($data['id']);
		$product->setData("w2p_image",$data['image']);
		$product->setData("w2p_image_large",$data['image']);
		$product->setData("w2p_image_small",$data['thumbnail']);
		$product->setData("w2p_created",$data['created']);
		$product->setData("w2p_modified",$data['created']);
		$product->setData("w2p_image_links",$this->getImageLinks($data));
		$product->setData("w2p_isorder", $data['w2p_isorder']);
		$product->setData("w2p_link", $data['access_url']);
		
		$product->setData("inventory_manage_stock_default",1);
		$product->setData("inventory_qty",10000);
		$product->setStatus(1);
		$product->setVisibility(4);
		$product->setCategoryIds(array($data['cids'] =>$data['cids']));
		$product->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID);
		$product->save();
		/* Stock Item */
		
		$stockItem = Mage::getModel('cataloginventory/stock_item');
		$stockItem->setData('use_config_manage_stock', 1);
		$stockItem->setData('is_in_stock', 1);		
		$stockItem->setData('stock_id', 1);
		$stockItem->setData('qty', 10000);
		$stockItem->setProduct($product);
		$stockItem->save();
		
		/* End Stock Item */
		
		return $product;
	}
	function getImageLinks($data){
		if (isset($data['w2p_image_links'])) return $data['w2p_image_links'];
		if (count($data['pages']) < 1){
			return $data['image'];
		}else {
			$links = "";
			$comma = "";
			foreach($data['pages'] as $page){
				$links .= $comma . $this->base ."/" . $page['image'];
				$comma = ",";
			}
			return $links;
		}
	}
}

?>