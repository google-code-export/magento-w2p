<?php
/**
  * Author	Pham Tri Cong <phtcong@gmail.com>
  * Data import from W2P 
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
class Biinno_Catalog_Model_Convert_Adapter_Category
    extends Mage_Eav_Model_Convert_Adapter_Entity
{
    protected $_categoryCache = array();
    protected $_stores;    
    protected $_displayModes = array( 'PRODUCTS', 'PAGE', 'PRODUCTS_AND_PAGE');
	protected $debug = 0;   
	protected $base = "";   
	protected $key = "";   
	
	/**
	  * Parser feed of all category and product
	  */
    public function parse()
    {		
        $batchModel = Mage::getSingleton('dataflow/batch');
        $this->base = $this->getVar('url', '');
		$this->key = $this->getVar('key', '');
		$this->debug = $this->getVar('debug', "0");
		$this->saveList($this->base, $this->key);        
    }
	/**
	  * Save List Category with product of user
	  * param	domain	url of site
	  * param 	key		ApiKey
	  */
    function saveList($domain, $key){
		$this->infoMess("**BEGIN**");
		$this->infoMess("**import data from url=[$domain]");
		$this->infoMess("**import data from key=[$key]");
		
		//Create url of categories
		$url = $this->getListCategoryUrl($domain, $key);
		$this->debugMess(sprintf("**List categories's url=[%s]",$url));
		//Get data of url
		$datas = $this->xml2array($url);
		if (
		!$datas 
		|| !isset($datas['rss'])
		|| !isset($datas['rss']['channel'])
		|| !isset($datas['rss']['channel']['item'])
		|| (count($datas['rss']['channel']['item']) < 1)
		){
			$this->infoMess("**No category found");
			return 0;
		}
		
		$items = $datas['rss']['channel']['item'];
		$this->infoMess(sprintf("**Number of categories:[%s]",count($items)));
		//Parser Categories
		$count = 0;
		foreach ($items as $item){
			$cate['store'] = "default";
			$cate['categories'] = str_replace('/','-',$item['title']);
			$cid = $this->saveCategoryData($cate);
			$data['list'] = $this->saveCategory($domain,$item['id'], $key, $cid);
			$data['title'] = $item['title'];
			$data['link'] = $item['link'];
			$ret[] = $data;
			$count += count($data['list']);
			$this->infoMess(sprintf("****Number of products:[%s]",count($data['list'])));
			//break;
		}
		//print_r($ret);
		$this->infoMess(sprintf("**Number of categories:[%s]",count($items)));
		$this->infoMess(sprintf("**Number of products:[%s]",$count));
		$this->infoMess("**END**");
		return $ret;
		
	}
	/**
	  * Save Product of category
	  * param	domain	url of site
	  * param	id		id of category
	  * param	key		ApiKey
	  * param	cid		cid of category in magento
	  **/
	function saveCategory($domain, $id,$key,$cid = 1){		
		$url = $this->getCategoryUrl($domain, $id,$key);
		$this->debugMess(sprintf("****BEGIN:Save Products Of Category:id=[%s]",$id));
		$datas = $this->xml2array($url);
		if (
		!$datas 
		|| !isset($datas['rss'])
		|| !isset($datas['rss']['channel'])
		|| !isset($datas['rss']['channel']['item'])
		|| (count($datas['rss']['channel']['item']) < 1)
		){
			return 0;
		}
		$products = $datas['rss']['channel']['item'];
		foreach ($products as $product){
			if (!isset($product['id'])) continue;
			$data = $this->getProduct($domain,$product['id'],$key);
			$data['title'] = $product['title'];
			$data['enclosure'] = $product['enclosure'];
			$data['description'] = $product['description'];
			$data['thumbnail'] = $product['thumbnail'];
			$data['image'] = $product['image'];
			$data['cids'] = $cid;
			$data['price'] = 0;
			$ret[] = $data;
			$this->saveProduct($data);			
			//break;
		}
		//print_r($ret);
		$this->debugMess(sprintf("****END:Save Products Of Category:id=[%s]",$id));		
		return $ret;
		
	}
	/**
	  * Get Product Information from feed
	  * param	domain	Url of site
	  * param	id		Template id
	  * param	key		ApiKey
	  */
	function getProduct($domain,$id,$key){
		$url = $this->getProductUrl($domain, $id,$key);
		$this->debugMess(sprintf("******Product URL=[%s]",$url));
		$datas = $this->xml2array($url);
		$ret = array();
		$ret['id'] = $id;
		if (isset($datas['TemplateDetails_attr'])){
			$ret['access_url'] = $datas['TemplateDetails_attr']['AccessURL'];
			$ret['reference'] = $datas['TemplateDetails_attr']['ProductReference'];
			$ret['created'] = $datas['TemplateDetails_attr']['Created'];
		}
		if (isset($datas['TemplateDetails']['Pages'])){
			foreach ($datas['TemplateDetails']['Pages'] as $page){
				foreach ($page as $item){
					if (isset($item['Name'])){
						$obj = array();
						$obj['name'] = $item['Name'];
						$obj['image'] = $item['PreviewImage'];
						$obj['thumb'] = $item['ThumbImage'];
						$ret['pages'][] = $obj;
					}
				}
			}
		}		
		return $ret;
	}
	/**
	  * Generate List Category'Feed URL Of User
	  * param	url	Url of ZentaPrints site
	  * param	key	ApiKey
	  */
	function getListCategoryUrl($url,$key){
		return "$url/API.aspx?page=api-catalogs;ApiKey=$key";
	}
	/**
	  * Generate Template Detail's Feed URL Of User
	  * param	url	Url of ZentaPrints site
	  * param 	id	Template Id
	  * param	key	ApiKey
	  */
	function getProductUrl($url,$id,$key){
		return "$url/API.aspx?page=api-template;TemplateID=$id;ApiKey=$key";
	}
	/**
	  * Generate List Template of Category's Feed URL Of User
	  * param	url	Url of ZentaPrints site
	  * param 	id	Category Id
	  * param	key	ApiKey
	  */
	function getCategoryUrl($url,$id,$key){
		return "$url/API.aspx?page=api-templates;CorporateID=$id;ApiKey=$key";
	}
	/**
	  * Get data by http protocal from url
	  * param	url	Url of ZentaPrints site
	  * return 	Text Content of url
	  */
	function getHttp($url){
		return file_get_contents($url);
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
		$this->debugMess(sprintf("******BEGIN:Save Product:title=[%s]",$data['title']));
		if (!$data['id'] || !$data['title'] || !$data['cids'] || !isset($data['created'])){
			return ;
		}
		$product = Mage::getModel('catalog/product');    
		
		$old = $product->getIdBySku($data['id']);
		if($old)
		{
			$product->load($old);
			//$product->setId($old);		
			//$product->delete();
			
			$product->setData("w2p_image",$data['image']);
			$product->setData("w2p_image_large",$data['image']);
			$product->setData("w2p_image_small",$data['thumbnail']);
			$product->setData("w2p_modified",$data['created']);
			if (count($data['pages']) < 1){
				$product->setData("w2p_image_links",$data['image']);			
			}else {
				$links = "";
				$comma = "";
				foreach($data['pages'] as $page){
					$links .= $this->base ."/". $comma . $page['image'];
					$comma = ",";
				}
				$product->setData("w2p_image_links",$links);
			}
			$product->setData("w2p_link", $data['access_url']);
			$product->save();
			return $old;
			
			
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
		if (count($data['pages']) < 1){
			$product->setData("w2p_image_links",$data['image']);			
		}else {
			$links = "";
			$comma = "";
			foreach($data['pages'] as $page){
				$links .= $comma . $page['image'];
				$comma = ",";
			}
			$product->setData("w2p_image_links",$links);
		}
		$product->setData("w2p_link", $data['access_url']);
		$product->setWeight(0);
		$product->setStatus(1);
		$product->setTaxClassId(2);
		$product->setCategoryIds(array($data['cids'] =>$data['cids']));
		$product->setVisibility(4);
		$product->save();
		$this->debugMess(sprintf("******End:Save Product:title=[%s]",$data['title']));
	}
	/**
	  * Save Category data to magento db. this function use core catalog category model class of magento
	  * param	data	category information which is get from Feed of ZentaPrints
	  * return	nothing
	  */
	public function saveCategoryData(array $importData)
    {
		$this->debugMess(sprintf("****BEGIN:Save Category:title=[%s]",$importData['categories']));
        $catId = 2;
        if (empty($importData['store'])) {
            if (!is_null($this->getBatchParams('store'))) {
                $store = $this->getStoreById($this->getBatchParams('store'));
            } else {
                $message = Mage::helper('catalog')->__('Skip import row, required field "%s" not defined', 'store');
                Mage::throwException($message);
            }
        } else {
            $store = $this->getStoreByCode($importData['store']);
        }

        if ($store === false) {
            $message = Mage::helper('catalog')->__('Skip import row, store "%s" field not exists', $importData['store']);
            Mage::throwException($message);
        }

        $rootId = $store->getRootCategoryId();
        if (!$rootId) {
            return array();
        }
        $rootPath = '1/'.$rootId;
        if (empty($this->_categoryCache[$store->getId()])) {
            $collection = Mage::getModel('catalog/category')->getCollection()
                ->setStore($store)
                ->addAttributeToSelect('name');
            $collection->getSelect()->where("path like '".$rootPath."/%'");

            foreach ($collection as $cat) {
                $pathArr = explode('/', $cat->getPath());
                $namePath = '';
                for ($i=2, $l=sizeof($pathArr); $i<$l; $i++) {
                    $name = $collection->getItemById($pathArr[$i])->getName();
                    $namePath .= (empty($namePath) ? '' : '/').trim($name);
                }
                $cat->setNamePath($namePath);
            }

            $cache = array();
            foreach ($collection as $cat) {
                $cache[strtolower($cat->getNamePath())] = $cat;
                $cat->unsNamePath();
            }
            $this->_categoryCache[$store->getId()] = $cache;
        }
        $cache =& $this->_categoryCache[$store->getId()];

        $importData['categories'] = preg_replace('#\s*/\s*#', '/', trim($importData['categories']));
        if (!empty($cache[$importData['categories']])) {
            return $cache[$importData['categories']]->getId();
        }

        $path = $rootPath;
        $namePath = '';

        $i = 1;
        $categories = explode('/', $importData['categories']);
        foreach ($categories as $catName) {
            $namePath .= (empty($namePath) ? '' : '/').strtolower($catName);
            if (empty($cache[$namePath])) {

                $dispMode = $this->_displayModes[2];

                $cat = Mage::getModel('catalog/category')
                    ->setStoreId($store->getId())
                    ->setPath($path)
                    ->setName($catName)
                    ->setIsActive(1)
                    ->setIsAnchor(1)
                    ->setDisplayMode($dispMode)
                    ->save();
                $cache[$namePath] = $cat;
            }
            $catId = $cache[$namePath]->getId();
            $path .= '/'.$catId;
            $i++;
        }
        return $catId ;
    }

	/**
	  * Retrieve store object by code
	  *
	  * @param string $store
	  * @return Mage_Core_Model_Store
	  */
    public function getStoreByCode($store)
    {
        $this->_initStores();
        if (isset($this->_stores[$store])) {
            return $this->_stores[$store];
        }
        return false;
    }

	/**
	  *  Init stores
	  *
	  *  @param    none
	  *  @return      void
	  */
    protected function _initStores ()
    {
        if (is_null($this->_stores)) {
            $this->_stores = Mage::app()->getStores(true, true);
            foreach ($this->_stores as $code => $store) {
                $this->_storesIdCode[$store->getId()] = $code;
            }
        }
    }
	
	/* Message function */
	protected function debugMess($mess){
		if ($this->debug){
			$message = Mage::helper('dataflow')->__($mess);
			$this->addException($message);
		}
	}
	protected function infoMess($mess){
		$message = Mage::helper('dataflow')->__($mess);
		$this->addException($message);
	}
	protected function errorMess($mess){
		$message = Mage::helper('dataflow')->__($mess);
		$this->addException($message);
	}
}

?>