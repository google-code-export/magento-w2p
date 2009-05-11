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
	function xml2array($url, $get_attributes = 1, $priority = 'tag')
	{
	    $contents = "";
	    if (!function_exists('xml_parser_create'))
	    {
	        return array ();
	    }
	    $parser = xml_parser_create('');
	    
	    $contents = $this->getHttp($url);
	    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	    xml_parse_into_struct($parser, trim($contents), $xml_values);
	    xml_parser_free($parser);
	    if (!$xml_values)
	        return; //Hmm...
	    $xml_array = array ();
	    $parents = array ();
	    $opened_tags = array ();
	    $arr = array ();
	    $current = & $xml_array;
	    $repeated_tag_index = array ();
	    foreach ($xml_values as $data)
	    {
	        unset ($attributes, $value);
	        extract($data);
	        $result = array ();
	        $attributes_data = array ();
	        if (isset ($value))
	        {
	            if ($priority == 'tag')
	                $result = $value;
	            else
	                $result['value'] = $value;
	        }
	        if (isset ($attributes) and $get_attributes)
	        {
	            foreach ($attributes as $attr => $val)
	            {
	                if ($priority == 'tag')
	                    $attributes_data[$attr] = $val;
	                else
	                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
	            }
	        }
	        if ($type == "open")
	        {
	            $parent[$level -1] = & $current;
	            if (!is_array($current) or (!in_array($tag, array_keys($current))))
	            {
	                $current[$tag] = $result;
	                if ($attributes_data)
	                    $current[$tag . '_attr'] = $attributes_data;
	                $repeated_tag_index[$tag . '_' . $level] = 1;
	                $current = & $current[$tag];
	            }
	            else
	            {
	                if (isset ($current[$tag][0]))
	                {
	                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
	                    $repeated_tag_index[$tag . '_' . $level]++;
	                }
	                else
	                {
	                    $current[$tag] = array (
	                        $current[$tag],
	                        $result
	                    );
	                    $repeated_tag_index[$tag . '_' . $level] = 2;
	                    if (isset ($current[$tag . '_attr']))
	                    {
	                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
	                        unset ($current[$tag . '_attr']);
	                    }
	                }
	                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
	                $current = & $current[$tag][$last_item_index];
	            }
	        }
	        elseif ($type == "complete")
	        {
	            if (!isset ($current[$tag]))
	            {
	                $current[$tag] = $result;
	                $repeated_tag_index[$tag . '_' . $level] = 1;
	                if ($priority == 'tag' and $attributes_data)
	                    $current[$tag . '_attr'] = $attributes_data;
	            }
	            else
	            {
	                if (isset ($current[$tag][0]) and is_array($current[$tag]))
	                {
	                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
	                    if ($priority == 'tag' and $get_attributes and $attributes_data)
	                    {
	                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
	                    }
	                    $repeated_tag_index[$tag . '_' . $level]++;
	                }
	                else
	                {
	                    $current[$tag] = array (
	                        $current[$tag],
	                        $result
	                    );
	                    $repeated_tag_index[$tag . '_' . $level] = 1;
	                    if ($priority == 'tag' and $get_attributes)
	                    {
	                        if (isset ($current[$tag . '_attr']))
	                        {
	                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
	                            unset ($current[$tag . '_attr']);
	                        }
	                        if ($attributes_data)
	                        {
	                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
	                        }
	                    }
	                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
	                }
	            }
	        }
	        elseif ($type == 'close')
	        {
	            $current = & $parent[$level -1];
	        }
	    }
	    return ($xml_array);
	}
	/**
	  * Save Product data to magento db. this function use core catalog product model class of magento
	  * param	data	product information which is get from Template Detail Feed of ZentaPrints
	  * return	nothing
	  */
	function saveProduct($data){	
		if (!$data['id'] || !$data['title'] || !isset($data['cids']) || !isset($data['created'])){
			return null;
		}
		$product = Mage::getModel('catalog/product');    
		$old = $product->getIdBySku($data['id']);
		if($old)
		{
			$product->load($old);
			$product->setData("w2p_image",$data['image']);
			$product->setData("w2p_image_large",$data['image']);
			$product->setData("w2p_image_small",$data['thumbnail']);
			$product->setData("w2p_modified",$data['created']);
			$product->setData("w2p_link", $data['access_url']);
			$product->setData("w2p_image_links",$this->getImageLinks($data));
			$product->save();
			return $product;
			
			
		}
		$product->setWebsiteIds(array('1'));
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
		$product->setData("w2p_link", $data['access_url']);
		$product->setWeight(0);
		//$product->setQty(1);
		//$product->setData("inventory_manage_stock_default",1);
		$product->setData("inventory_qty",1);
		$product->setStatus(1);
		$product->setTaxClassId(2);
		$product->setCategoryIds(array($data['cids'] =>$data['cids']));
		$product->setVisibility(4);		
		$product->save();
		/* Stock Item */
		
		$stockItem = Mage::getModel('cataloginventory/stock_item');
		$stockItem->setData('use_config_manage_stock', 1);
		$stockItem->setData('is_in_stock', 1);		
		$stockItem->setData('stock_id', 1);
		$stockItem->setData('qty', 1);
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