<?php
/**
  * Project	zeta prints api
  * Author	Pham Tri Cong <phtcong@gmail.com>
  * ZetaPrints provides direct connection to its back-end via web-to-print API. 
  * Web to print API can only be accessed through your custom domain name, not through zetaprints.com. 
  * There must be a match between your domain name and your API key.
  * ZP API have :
  *	1. Catalogs API
  * 		1.1. Get list of catalogs for the domain from ZP
  *		1.2. Get list of templates of catalog
  *	2. Template API
  *		2.1. Get details of template
  *		2.2. Show previews of template in IFRAME
  *	3. User API
  *		3.1. Regiser new User
  *	4. Order API
  *		4.1. Get Order details
  *		4.2. Saved order completion
  *		4.3. Change Order status
  */
define("ZP_API_VER",'1.0');
global $zp_api_key;
global $zp_api_url;

/**
  * Web to print API can only be accessed through your custom domain name, not through zetaprints.com. 
  * There must be a match between your domain name and your API key.
  *
  * This function will set api key and api url for all call of zp api.
  * Init api key and api url
  */
function zp_api_init($key, $url){
	global $zp_api_key;
	global $zp_api_url;
	//set zp_api_key
	$zp_api_key = $key;
	//set zp_api_url
	$zp_api_url = $url;
}

/***************************
  *	catalog functions		*
  ***************************/
/**
  * 1.1. Get list of catalogs for the domain from ZP
  * @param	api_key
  * @param	api_url
  * @return	Array
  */
function zp_api_catalog_list($key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_catalog_list:start url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_catalog_list:error, null param url=[$zp_api_url],key=[$zp_api_key]");
		return null;
	}
	//Get Url 
	$url = "$zp_api_url/API.aspx?page=api-catalogs;ApiKey=$zp_api_key";
	//Get data from feed
	$ret = zp_api_common_feed2array($url);
	$list = null;
	//check have data	
	if (   isset($ret['channel']) 
		&& isset($ret['channel']['item']) 
	){
		$list = $ret['channel']['item'];
	}
	zp_api_log_debug("zp_api_catalog_list:end url=[$zp_api_url],key=[$zp_api_key],num cate=" . count($list));
	return $list;
}


/**
  * 1.2. Get list of templates of catalog
  * @param	cid	catalog Id
  * @param 	key	ApiKey
  * @param	url	Url of ZentaPrints site
  * @return  list templates of catalog
  */
function zp_api_catalog_detail($cid, $key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_catalog_detail:start cid=[$cid], url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_catalog_detail:error, null param cid=[$cid], url=[$zp_api_url],key=[$zp_api_key]");
		return null;
	}
	//Get Url 
	$url = "$zp_api_url/API.aspx?page=api-templates;CorporateID=$cid;ApiKey=$zp_api_key";
	//Get data from feed
	$ret = zp_api_common_feed2array($url);
	$list = null;
	//check have data	
	if (   isset($ret['channel']) 
		&& isset($ret['channel']['item']) 
	){
		$list = $ret['channel']['item'];
	}
	zp_api_log_debug("zp_api_catalog_detail:end cid=[$cid], url=[$zp_api_url],key=[$zp_api_key],num cate=" . count($list));
	return $list;
}
function zp_api_catalog_check_public($cate){
	if (!isset($cate['access']) || !$cate['access']) return false;
	$access = trim($cate['access']);
	if ($access == "public" || $access== "public-rego") return true;
	return false;
}
/***************************
  *	end catalog functions	*
  ***************************/
  
  
/***************************
  *	template functions	*
  ***************************/
/**
  * 2.1. Get details of template
  * @param	tid	Template Id
  * @param 	key	ApiKey
  * @param	url	Url of ZentaPrints site
  * @return  Template Detail
  */
function zp_api_template_detail($tid, $key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_template_detail:start tid=[$tid],url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_template_detail:error, null param tid=[$tid], url=[$zp_api_url],key=[$zp_api_key]");
		return null;
	}
	//Get Url 
	$url = "$zp_api_url/API.aspx?page=api-template;TemplateID=$tid;ApiKey=$zp_api_key";
	//Get data from feed
	$ret = zp_api_common_feed2array($url);
	$data = $ret;
	
	$thumbs = "";
	$previews = "";
	$comma = "";
	//check have data	
	if (   isset($ret['@attributes'])
	){
		$data = $ret['@attributes'];
		$pages = array();
		if (isset($ret['pages']['page'])){
			$ps =  $ret['pages']['page'];
			if (count($ps) > 1){
				$pages = $ps;
			}else{
				$pages[] = $ps;
			}
			foreach ($pages as $page){
				if (isset($page['@attributes']['thumbimage']) && isset($page['@attributes']['previewimage'])){
					$p = array();
					$p['thumbimage'] 	= "$zp_api_url/" . $page['@attributes']['thumbimage'];
					$p['previewimage'] 	= "$zp_api_url/" . $page['@attributes']['previewimage'];
					$data['pages'][] = $p;
					if (!$comma){
						$data['thumbimage'] 	= $p['thumbimage'];
						$data['previewimage'] 	= $p['previewimage'];
					}
					$thumbs .= $comma . $p['thumbimage'];
					$previews .= $comma . $p['previewimage'];
					$comma = ",";
				}
			}
		}else{
			$data['pages'] = null;
			$thumbs = "";
			$previews = "";
		}
		if (isset($ret['tags']['tag'])){
			$tags = $ret['tags']['tag'];
			if (count($tags) > 0){
				$data['tags'] = $tags;
			}else{
				//TODO:Not test yet
				$data['tags'][] = $tags;
			}
		}else{
			$data['tags'] = null;
		}
		$data['thumbs'] = $thumbs;
		$data['previews'] = $previews;
	}
	zp_api_log_debug("zp_api_template_detail:end tid=[$tid], url=[$zp_api_url],key=[$zp_api_key]");
	return $data;
}
/**
  * 2.2. Get iframe url of Template to Show previews of template in IFRAME
  *
  * @param	tid	Template Id
  * @param	uid	User Id
  * @param	pass	PassWord
  * @param 	key	ApiKey
  * @param	url	Url of ZentaPrints site
  * @return  Template Detail
  */
function zp_api_template_iframe_url($tid, $uid, $pass, $key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_template_detail:start tid=[$tid],url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_template_detail:error, null param tid=[$tid], url=[$zp_api_url],key=[$zp_api_key]");
		return null;
	}
	$ip 	= $_SERVER["REMOTE_ADDR"];
	if ((strpos($ip,"192") !== false)
		||(strpos($ip,"127") !== false)){
		$ip = "113.22.120.143" ;
	}
	$hash =  md5($pass . $ip);
	return "$zp_api_url/?page=template;TemplateID=$tid;RetT=id;RetO=Save;RetE=1;ID=$uid;Hash=$hash";;
}
/***************************
  *	end template functions	*
  ***************************/
  
  
/***************************
  *	user functions		*
  ***************************/
/**
  * 3.1. Regiser new User
  * @param 	user
  * @param 	pass
  * @param 	key	ApiKey
  * @param	url	Url of ZentaPrints site
  * @return 	1 : registe new ok
  *		0: user is registed
  *		-1: registe new error
  */
function zp_api_user_register($user, $pass, $key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_user_register:start url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_user_register:error, null param url=[$zp_api_url],key=[$zp_api_key]");
		return -1;
	}
	//Get path
	$path = "/API.aspx?page=api-user-new";
	$data = array();
	$data['UserID'] = $user;
	$data['Password'] = $pass;
	$data['ApiKey'] = $zp_api_key;
	zp_api_log_debug("zp_api_user_register:request");
	list($header, $content) = zp_api_common_post_request($zp_api_url, $path, $data);
	zp_api_log_debug("zp_api_user_register:request:end");
	return zp_api_common_xml_user_register_result($content);
}
/***************************
  *	end user functions	*
  ***************************/
  
  
  
/***************************
  *	order functions		*
  ***************************/
/**
  *  4.1. Get Order details
  * @param 	id	order id
  * @param 	key	ApiKey
  * @param	url	Url of ZentaPrints site
  * @return Order detail 
  */
function zp_api_order_detail($id, $key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_order_detail:start order id=[$id],url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_order_detail:error, null param url=[$zp_api_url],key=[$zp_api_key]");
		return null;
	}
	//Get Url 
	$url = "$zp_api_url/API.aspx?page=api-order;OrderID=$id;ApiKey=$zp_api_key";
	//Get data from feed
	$ret = zp_api_common_feed2array($url);
	
	//convert to data
	return zp_api_order_fetch($ret, $zp_api_url);
}
/**
  *
  * 4.2. Saved order completion
  * @param 	id	order id
  * @param 	key	ApiKey
  * @param	url	Url of ZentaPrints site
  * @return Order detail 
  */
function zp_api_order_save($id, $key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_order_save:start order id=[$id],url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_order_save:error, null param url=[$zp_api_url],key=[$zp_api_key]");
		return null;
	}
	//Get Url 
	$url = "$zp_api_url/API.aspx?page=api-order-complete;OrderID=$id;ApiKey=$zp_api_key";
	//Get data from feed
	$ret = zp_api_common_feed2array($url);
	//convert to data
	return zp_api_order_fetch($ret, $zp_api_url);
}
/**
  * 4.3. Change Order status
  * @param 	id	order id
  * @param	fstatus	new status
  * @param	tstatus	old status
  * @param 	key	ApiKey
  * @param	url	Url of ZentaPrints site
  * @return  new Order Detail
  */
function zp_api_order_change($id, $fstatus, $tstatus, $key = null, $url = null){
	if ($key){
		zp_api_init($key,$url);
	}
	global $zp_api_key;
	global $zp_api_url;
	zp_api_log_debug("zp_api_order_change:start order id=[$id],status=[$fstatus], old=[$tstatus], url=[$zp_api_url],key=[$zp_api_key]");
	if (!$zp_api_key || !$zp_api_url){
		zp_api_log_error("zp_api_order_change:error, null param url=[$zp_api_url],key=[$zp_api_key]");
		return null;
	}
	$status = urlencode  ($tstatus);
	$statusOld = urlencode  ($fstatus);
	//Get Url 
	$url = "$zp_api_url/API.aspx?page=api-order-status;OrderID=$id;Status=$status;StatusOld=$statusOld;ApiKey=$zp_api_key";
	//Get data from feed
	$ret = zp_api_common_feed2array($url);
	//convert to data
	return zp_api_order_fetch($ret, $zp_api_url);
}
/**
  * Get Order Detail Array from Order Detail xml which got from ZP feed
  * @param 	ret	Order Detail xml 
  * @param	zp_api_url	Url of ZentaPrints site
  * @return  Order Detail Array
  */
function zp_api_order_fetch($ret, $zp_api_url){
	$data = array();
	if (!$ret) return $data;
	$thumbs = "";
	$previews = "";
	$comma = "";
	//check have data	
	if (   isset($ret['@attributes'])
	){
		$data = $ret['@attributes'];
		$fields = array("pdf"=>""
					,"jpeg"=>""
					,"gif"=>""
					,"png"=>""
					,"cdr"=>"");
		foreach ($fields as $key => $val){
			if (isset($data[$key])){
				$data[$key] = "$zp_api_url/" . $data[$key];
			}
		}
		$data['pages'] = null;
		$pages = array();
		if (isset($ret['pages']['page'])){
			$ps =  $ret['pages']['page'];
			if (count($ps) > 1){
				$pages = $ps;
			}else{
				$pages[] = $ps;
			}
			foreach ($pages as $page){
				if (isset($page['@attributes']['previewimage'])){
					$p = array();
					$p['previewimage'] 	= "$zp_api_url/" . $page['@attributes']['previewimage'];
					$data['pages'][] = $p;
					if (!$comma){
						$data['previewimage'] 	= $p['previewimage'];
					}
					$previews .= $comma . $p['previewimage'];
					$comma = ",";
				}
			}
		}else{
			$data['pages'] = null;
			$thumbs = "";
			$previews = "";
		}
		
		$data['thumbs'] = $thumbs;
		$data['previews'] = $previews;
	}
	return $data;
}

/***************************
  *	end order functions	*
  ***************************/
  
  
/***************************
  *	common functions		*
  ***************************/

/**
  * zeta prints log function
  */
function zp_api_log($mess){
	error_log( date('d.m.Y h:i:s') . "[zp_api_log] $mess \n", 3, "zp_api_log.log");
}
function zp_api_log_error($mess){
	zp_api_log("[error] $mess");
}
function zp_api_log_info($mess){
	zp_api_log("[info] $mess");
}
function zp_api_log_debug($mess){
	zp_api_log("[debug] $mess");
}

/**
  * Get content of url then parse to array
  * @param	url	Url of ZentaPrints site
  * @return	Array
  */
function zp_api_common_feed2array($url)
{
	zp_api_log_debug("zp_api_common_feed2array:start url=[$url]");
	$obj = null;
	try{
		try{
			$obj = @simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA);
			if (!$obj) {
				zp_api_log_debug("zp_api_common_feed2array:feed null");
				return null;
			}	
		}catch(Warning $e){
			zp_api_log_error("zp_api_common_feed2array:exception=[$e]");
		}
	}catch(Exception $e){
		//error
		zp_api_log_error("zp_api_common_feed2array:exception=[$e]");
		return null;
	}
	$obj = zp_api_common_object2array($obj);
	zp_api_log_debug("zp_api_common_feed2array:end url=[$url]");
	return ($obj);
}
/**
  * Convert  simplexml obj to array
  * @param 	object
  * @return	array of object
  */
function zp_api_common_object2array($object)
{
	//zp_api_log_debug("zp_api_common_object2array:start");
	$return = NULL;
	if(is_array($object))
	{
		foreach($object as $key => $value){
			$return[strtolower($key)] = zp_api_common_object2array($value);
		}
	}
	else
	{
		$var = get_object_vars($object);
		if($var)
		{
			foreach($var as $key => $value){
				$return[strtolower($key)] = ($key && !$value) ? NULL : zp_api_common_object2array($value);
			}
		}
		else {
			//zp_api_log_debug("zp_api_common_object2array:end");
			return $object;
		}
	}
	//zp_api_log_debug("zp_api_common_object2array:end");
	return $return;
}
/**
  * Convert  string of date format: Y-m-d h:i:s
  * @param 	val
  * @return	date in format Y-m-d h:i:s
  */
function zp_api_common_str2date($val){
	if (!$val) return zp_api_common_date();
	return zp_api_common_date(strtotime($val));
}
/**
  * Convert  time format: Y-m-d h:i:s
  * @param 	val
  * @return	date in format Y-m-d h:i:s
  */
function zp_api_common_date($time = null){
	if (!$time) return date("Y-m-d h:i:s");
	return date("Y-m-d h:i:s", $time);

}

/**
  * Generate GUID - UUID
  * return	UUID
  */
function zp_api_common_uuid() {   
	return strtoupper(sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
		mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
		mt_rand(0, 65535), // 16 bits for "time_mid"
		mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
		bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
		mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node" 
	)); 
}

function zp_api_common_pass () {
	return substr(md5(time()),0,6);
}
/**
  * Send Post request
  * param 	url		url of request
  * param	path		path of request
  * param	_data		request data
  * return	list(header, content)
  */
function zp_api_common_post_request($url, $path, $_data) {

	zp_api_log_debug("zp_api_common_post_request:start url=[$url]");

	$referer = $url;
	$data = array();	
	
	while(list($n,$v) = each($_data)){
		$data[] = "$n=$v";
	}	
	$data = implode('&', $data);
	$url = parse_url($url);
	if ($url['scheme'] != 'http') { 
		die('Only HTTP request are supported !');
	} 
	$host = $url['host'];
	zp_api_log_debug("zp_api_common_post_request:data=[$data]");
	try{
		$fp = fsockopen($host, 80);			
		fputs($fp, "POST $path HTTP/1.1\r\n");
		fputs($fp, "Host: $host\r\n");
		fputs($fp, "Referer: $referer\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: ". strlen($data) ."\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);		 
		$result = ''; 
		while(!feof($fp)) {
			$result .= fgets($fp, 128);
		}

		fclose($fp);

		$result = explode("\r\n\r\n", $result, 2);

		$header = isset($result[0]) ? $result[0] : '';
		$content = isset($result[1]) ? $result[1] : '';
		zp_api_log_debug("zp_api_common_post_request:end url=[$url]");
		return array($header, $content);
	}catch(Exception $e){
		zp_api_log_error("zp_api_common_post_request:end, error url=[$url]");
		return array("ERROR", "<error/>");
	}
}
/**
  * Parser Register User Result ' s XML
  * param 	content	XML data
  * return 	1		if xml is <ok/>
  *		-1		if xml is <error/>
  */
function zp_api_common_xml_user_register_result($content){
	$ret = "";
	$start = strpos ($content, "<");
	$end = strpos ($content, "/>");
	if (($start !== false) && ($start < $end)){
		$ret = trim(substr($content, $start + 1, $end - $start - 1));
	}
	if ($ret == "ok" ) return 1;
	return -1;
}
/***************************
  *	end common functions	*
  ***************************/
?>