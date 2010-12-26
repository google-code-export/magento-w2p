<?php
/**
  * Project zeta prints api
  * Author  Pham Tri Cong <phtcong@gmail.com>
  *
  */
define("ZP_API_VER",'1.0');
//define("ZP_API_HTTP_CACHE",'zp_cache');
define("ZP_API_HTTP_CACHE",'');

require_once 'mage-logging.php';

global $zp_api_key;
global $zp_api_url;

/**
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
  * catalog functions   *
  ***************************/
/**
  * Get list of catalogs for the domain from ZP
  * @param  api_key
  * @param  api_url
  * @return Array
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
    if (isset($ret['channel']['item']['title'])){
      $list[] = $ret['channel']['item'];
    }else{
      $list = $ret['channel']['item'];
    }
  }
  zp_api_log_debug("zp_api_catalog_list:end url=[$zp_api_url],key=[$zp_api_key],num cate=" . count($list));
  return $list;
}


/**
  * Get List Template of Category's Feed URL Of User
  * @param  cid Category Id
  * @param  key ApiKey
  * @param  url Url of ZentaPrints site
  * @return  list template of category
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
    //$list = $ret['channel']['item'];
    if (isset($ret['channel']['item']['title'])){
      $list[] = $ret['channel']['item'];
    }else{
      $list = $ret['channel']['item'];
    }
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
  * end catalog functions *
  ***************************/


/***************************
  * template functions  *
  ***************************/

function zetaprints_get_template_details ($api_url, $template_id) {
  $url = "$api_url/?page=template-xml;TemplateID=$template_id";
  return zetaprints_get_xml_from_url($url);
}

/**
  * Get Template  detail from ZP
  * @param  tid Template Id
  * @param  key ApiKey
  * @param  url Url of ZentaPrints site
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
          $p['thumbimage']  = "$zp_api_url/" . $page['@attributes']['thumbimage'];
          $p['previewimage']  = "$zp_api_url/" . $page['@attributes']['previewimage'];
          $data['pages'][] = $p;
          if (!$comma){
            $data['thumbimage']   = $p['thumbimage'];
            $data['previewimage']   = $p['previewimage'];
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
  * Get iframe url of Template
  * @param  tid Template Id
  * @param  uid User Id
  * @param  pass  PassWord
  * @param  key ApiKey
  * @param  url Url of ZentaPrints site
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
  $ip   = $_SERVER["REMOTE_ADDR"];
  if ((strpos($ip,"192") !== false)
    ||(strpos($ip,"127") !== false)){
    $ip = "113.22.60.28" ;
  }
  $hash =  md5($pass . $ip);
  return "$zp_api_url/?page=template;TemplateID=$tid;RetT=id;RetO=Save;RetE=1;ID=$uid;Hash=$hash";;
}
/***************************
  * end template functions  *
  ***************************/


/***************************
  * user functions    *
  ***************************/
/**
  * register user to w2p
  * @param  user
  * @param  pass
  * @param  key ApiKey
  * @param  url Url of ZentaPrints site
  * @return   1 : registe new ok
  *   0: user is registed
  *   -1: registe new error
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
  * end user functions  *
  ***************************/



/***************************
  * order functions   *
  ***************************/
/**
  * Get Order Detail From ZP
  * @param  id  order id
  * @param  key ApiKey
  * @param  url Url of ZentaPrints site
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
  * Save Order To ZP
  * @param  id  order id
  * @param  key ApiKey
  * @param  url Url of ZentaPrints site
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
  * Change Order Status To ZP
  * @param  id  order id
  * @param  fstatus new status
  * @param  tstatus old status
  * @param  key ApiKey
  * @param  url Url of ZentaPrints site
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
  * @param  ret Order Detail xml
  * @param  zp_api_url  Url of ZentaPrints site
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
          $p['previewimage']  = "$zp_api_url/" . $page['@attributes']['previewimage'];
          $data['pages'][] = $p;
          if (!$comma){
            $data['previewimage']   = $p['previewimage'];
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
  * end order functions *
  ***************************/


/***************************
  * common functions    *
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

function zetaprints_get_xml_from_url ($url) {
  zp_api_log_debug("zetaprints_get_xml_from_url: url=$url");
  $xml = zp_api_get_http_cache($url);

  if (!$xml) return null;

  return $xml;
}

/**
  * Get content of url then parse to array
  * @param  url Url of ZentaPrints site
  * @return Array
  */
function zp_api_common_feed2array($url)
{
  zp_api_log_debug("zp_api_common_feed2array:start url=[$url]");
  $obj = null;
  $str = zp_api_get_http_cache($url);
  if (!$str) return null;

  $obj = @simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA);
  $obj = zp_api_common_object2array($obj);
  zp_api_log_debug("zp_api_common_feed2array:end url=[$url]");
  return ($obj);
}
function zp_api_get_context_curl($_url){
  $ch = curl_init();
  $timeout = 10; // set to zero for no timeout
  curl_setopt ($ch, CURLOPT_URL, $_url);
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $file_contents = curl_exec($ch);
  curl_close($ch);
  return $file_contents ;
}
function zp_api_get_http($url){
  for($i = 0; $i < 3 ; $i++){
    try{
      if (extension_loaded('curl')) {
        $str = @zp_api_get_context_curl($url);
      }else{
        $str = @file_get_contents($url);
      }
      if ($str){
        return $str;
      }
    }catch(Exception $e){
      rss_api_log_error("zp_api_get_http:exception=[$e]");
    }
  }
  return $str;
}
function zp_api_get_http_cache($url){
  if (!ZP_API_HTTP_CACHE) return zp_api_get_http($url);
  zp_api_log_debug("zp_api_get_http_cache:start url=[$url]");
  global $zp_cache_time;
  if (!$zp_cache_time) $zp_cache_time = "NO";
  $fname = ZP_API_HTTP_CACHE . "/" . md5($url . $zp_cache_time);
  if (file_exists($fname)){
    return @file_get_contents($fname);
  }
  //get data from http
  $str = zp_api_get_http($url);
  //save to cache
  if (!file_exists(ZP_API_HTTP_CACHE)){
    mkdir(ZP_API_HTTP_CACHE,0755,true);
  }
  $fp = @fopen($fname, 'w');
  @fwrite($fp, $str);
  @fclose($fp);
  return $str;
}
/**
  * Convert  simplexml obj to array
  * @param  object
  * @return array of object
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
  * @param  val
  * @return date in format Y-m-d h:i:s
  */
function zp_api_common_str2date($val){
  if (!$val) return zp_api_common_date();
  return zp_api_common_date(strtotime($val));
}
/**
  * Convert  time format: Y-m-d h:i:s
  * @param  val
  * @return date in format Y-m-d h:i:s
  */
function zp_api_common_date($time = null){
  if (!$time) return date("Y-m-d h:i:s");
  return date("Y-m-d h:i:s", $time);

}

/**
  * Generate GUID - UUID
  * return  UUID
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
  * param   url   url of request
  * param path    path of request
  * param _data   request data
  * return  list(header, content)
  */
function zp_api_common_post_request ($url, $path, $_data) {
  zp_api_log_debug("zp_api_common_post_request:start url=[$url], path=[$path]");

  $referer = $url;
  $data = array();

  while (list($n,$v) = each($_data)) {
    $n = urlencode($n);
    $v = urlencode($v);

    $data[] = ("$n=$v");
  }

  $data = implode('&', $data);
  $url = parse_url($url);

  if ($url['scheme'] != 'http')
    die('Only HTTP request are supported !');

  $host = $url['host'];

  zp_api_log_debug("zp_api_common_post_request:data=[$data]");

  try {
    $fp = fsockopen($host, 80);
    fputs($fp, "POST $path HTTP/1.1\r\n");
    fputs($fp, "Host: $host\r\n");
    fputs($fp, "Referer: $referer\r\n");
    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
    fputs($fp, "Content-length: ". strlen($data) ."\r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    fputs($fp, $data);

    $result = '';

    while (!feof($fp))
      $result .= fgets($fp, 1024);

    fclose($fp);

    zp_api_log_debug("Post request response: $result");

    $result = explode("\r\n\r\n", $result, 2);

    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    zp_api_log_debug("zp_api_common_post_request: content: $content");
    zp_api_log_debug("zp_api_common_post_request:end url=[$url]");

    return array($header, $content);
  } catch (Exception $e) {
    zp_api_log_error("zp_api_common_post_request:end, error url=[$url]");
    return array("ERROR", "<error/>");
  }
}

/**
  * Parser Register User Result ' s XML
  * param   content XML data
  * return  1   if xml is <ok/>
  *   -1    if xml is <error/>
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

function zetaprints_generate_guid () {
  return strtoupper(sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
    mt_rand(0, 65535), mt_rand(0, 65535),
    mt_rand(0, 65535),
    mt_rand(0, 4095),
    bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
    mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) ));
}

/**
 * Generate md5 hash from user's password and server ip address.
 *
 * Param password - user's password
 * Returns string contains hash
 */
function zetaprints_generate_user_password_hash ($password) {
  $ip = $_SERVER["SERVER_ADDR"];

  //Enter here your outside ip address
  //if it doesn't match your server address
  //$ip = 'a.b.c.d';

  return md5($password.$ip);
}

/**
 * Transform template details xml to html form.
 *
 * Param template_xml - string contains template details xml
 * Returns string contains html form
 */
function zetaprints_get_html_from_xml ($xml, $xslt, $params) {
  if (is_string($xml)) {
    $xml_dom = new DOMDocument();
    $xml_dom->loadXML($xml);
  } else
    $xml_dom = $xml;

  $xslt_dom = new DOMDocument();
  $xslt_dom->load(dirname(__FILE__).'/' . $xslt . '-html.xslt');

  $proc = new XSLTProcessor();
  $proc->importStylesheet($xslt_dom);

  $proc->setParameter('', $params);
  return $proc->transformToXML($xml_dom);
}

function zetaprints_get_list_of_catalogs ($url, $key) {
  zetaprints_debug();
  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-catalogs;ApiKey=$key");

  if (zetaprints_has_error($response))
    return $response['content'];

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }


  $catalogs = array();

  foreach ($xml->channel[0]->item as $item)
    $catalogs[] = array('title' => (string)$item->title,
                        'link' => (string)$item->link,
                        'guid' => (string)$item->id,
                        'domain' => (string)$item->domain,
                        'templates' => (int)$item->templates,
                        'users' => (int)$item->users,
                        'orders' => (int)$item->orders,
                        'created' => strtotime($item->created),
                        'public' => (string)$item->access == 'public' ? true : false,
                        'keywords' => (string)$item->keywords);

  zetaprints_debug(array('catalogs' => $catalogs));

  return $catalogs;
}

function zetaprints_get_templates_from_catalog ($url, $key, $catalog_guid) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-templates;CorporateID=$catalog_guid;ApiKey=$key");

  if (zetaprints_has_error($response))
    return $response['content'];

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  $templates = array();

  foreach ($xml->channel[0]->item as $item)
    $templates[] = array('title' => (string)$item->title,
                         'link' => (string)$item->link,
                         'guid' => (string)$item->id,
                         'catalog_guid' => (string)$item->cid,
                         'description' => (string)$item->description,
                         'date' => zp_api_common_str2date($item->lastModified),
                         'thumbnail' => (string)$item->thumbnail,
                         'image' => (string)$item->image);

  zetaprints_debug(array('templates' => $templates));

  return $templates;
}

function zetaprints_parse_template_details ($xml) {
  $download = false;

  if (isset($xml['Download']) && ((string)$xml['Download'] == 'allow'
      || (string)$xml['Download'] == 'only'))
    $download = true;

  $template = array('guid' => (string) $xml['TemplateID'],
                     'corporate-guid' => (string) $xml['CorporateID'],
                     'created' => zp_api_common_str2date($xml['Created']),
                     'comments' => (string) $xml['Comments'],
                     'url' => (string) $xml['AccessURL'],
                     'product-reference' => (string) $xml['ProductReference'],
                     'download' => $download,
                     'pdf' => isset($xml['GeneratePdf'])
                                  ? (bool) $xml['GeneratePdf'] : false,
                     'jpeg' => isset($xml['GenerateJpg'])
                                  ? (bool) $xml['GenerateJpg'] : false,
                     'png' => isset($xml['GenerateGifPng'])
                                  ? (bool) $xml['GenerateGifPng'] : false );

  if (!$xml->Pages->Page) {
    zetaprints_debug("No pages in tempalate [$template_guid]");

    return $template;
  }

  $template['pages'] = array();

  $page_number = 1;

  foreach ($xml->Pages->Page as $page) {
    $template['pages'][$page_number] = array(
      'name' => (string) $page['Name'],
      'preview-image' => (string) $page['PreviewImage'],
      'thumb-image' => (string) $page['ThumbImage'],
      'updated-preview-image' => (string) $page['PreviewImageUpdated'] );

    if ($page->Shapes) {
      $template['pages'][$page_number]['shapes'] = array();

      foreach ($page->Shapes->Shape as $shape) {
        $name = (string) $shape['Name'];
        $template['pages'][$page_number]['shapes'][$name] = array(
          'x1' => (float) $shape['X1'],
          'y1' => (float) $shape['Y1'],
          'x2' => (float) $shape['X2'],
          'y2' => (float) $shape['Y2'],
        );
      }
    }

    $page_number++;
  }

  foreach ($xml->Images->Image as $image) {
    $image_array = array(
      'name' => (string) $image['Name'],
      'width' => (string) $image['Width'],
      'height' => (string) $image['Height'],
      'color-picker' => isset($image['ColourPicker'])
                            ? (string) $image['ColourPicker'] : null,
      'allow-upload' => isset($image['AllowUpload'])
                            ? (bool) $image['AllowUpload'] : false,
      'allow-url' => isset($image['AllowUrl'])
                            ? (bool) $image['AllowUrl'] : false,
      //We get lowercase GUID in value for user images.
      //Convert to uppercase while the issue will be fixed in ZP side
      'value' => strtoupper((string) $image['Value']) );

    if ($image->StockImage) {
      $image_array['stock-images'] = array();

      foreach ($image->StockImage as $stock_image)
        $image_array['stock-images'][] = array(
          'guid' => (string) $stock_image['FileID'],
          'mime' => (string) $stock_image['MIME'],
          'thumb' => (string) $stock_image['Thumb']
        );
    }

    $page_number = (int) $image['Page'];

    if (!isset($template['pages'][$page_number]['images']))
      $template['pages'][$page_number]['images'] = array();

    $template['pages'][$page_number]['images'][(string) $image['Name']]
                                                                = $image_array;
  }

  foreach ($xml->Fields->Field as $field) {
    $field_array = array(
      'name' => (string) $field['FieldName'],
      'hint' => (string) $field['Hint'],
      'min-length' => isset($field['MinLen']) ? (int) $field['MinLen'] : null,
      'max-length' => isset($field['MaxLen']) ? (int) $field['MaxLen'] : null,
      'multiline' => isset($field['Multiline'])
                        ? (bool) $field['Multiline'] : false,
      'value' => (string) $field['FieldValue'] );

    if ($field->Value) {
      $field_array['values'] = array();

      foreach ($field->Value as $value)
        $field_array['values'][] = (string) $value;
    }

    $page_number = (int) $field['Page'];

    if (!isset($template['pages'][$page_number]['fields']))
      $template['pages'][$page_number]['fields'] = array();

    $template['pages'][$page_number]['fields'][(string) $field['FieldName']]
                                                                = $field_array;
  }

  zetaprints_debug(array('template' => $template));

  return $template;
}

function zetaprints_get_template_detailes ($url, $key, $template_guid) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-template;TemplateID=$template_guid;ApiKey=$key");

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_template_details($xml);
}

function zetaprints_get_template_details_as_xml ($url, $key, $template_guid,
                                                 $data = null) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-template;TemplateID=$template_guid;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_parse_order_details ($xml) {
  $order = array(
    'guid' => (string) $xml['OrderID'],
    'created-by' => (string) $xml['CreatedBy'],
    'created' => zp_api_common_str2date($xml['Created']),
    'status' => (string) $xml['Status'],
    'billed-by-zp' => zp_api_common_str2date($xml['BilledByZP']),
    'status-history' => (string) $xml['StatusHistory'],
    'product-price' => (float) $xml['ProductPrice'],
    'product-name' => (string) $xml['ProductName'],
    'pdf' => (string) $xml['PDF'],
    'cdr' => (string) $xml['CDR'],
    'gif' => (string) $xml['GIF'],
    'png' => (string) $xml['PNG'],
    'jpeg' => (string) $xml['JPEG'],
    'approval-email' => (string) $xml['ApprovalEmail'],
    'note' => (string) $xml['Note'],
    'cost-centre' => (string) $xml['CostCentre'],
    'delivery-address' => (string) $xml['DeliveryAddress'],
    'quantity-price-choice' => (string) $xml['QuantityPriceChoice'],
    'optional-choice' => (string) $xml['OptionalChoice'],
    'user-reference' => (string) $xml['UserReference'],
    'paid-date-time' => (string) $xml['PaidDateTime'],
    'currency' => (string) $xml['Currency'],
    'delivery-street-1' => (string) $xml['DeliveryStreet1'],
    'delivery-street-2' => (string) $xml['DeliveryStreet2'],
    'delivery-town' => (string) $xml['DeliveryTown'],
    'delivery-state' => (string) $xml['DeliveryState'],
    'delivery-zip' => (string) $xml['DeliveryZip'],
    'delivery-country' => (string) $xml['DeliveryCountry'] );

  $order['template-details'] =
                      zetaprints_parse_template_details($xml->TemplateDetails);

  zetaprints_debug(array('order' => $order));

  return $order;
}

function zetaprints_get_order_details ($url, $key, $order_id) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order;ApiKey=$key;OrderID=$order_id");

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_change_order_status ($url, $key, $order_id, $old_status, $new_status) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-order-status;ApiKey=$key;OrderID=$order_id",
                                              array('Status' => $new_status,
                                                    'StatusOld' => $old_status) );

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_update_preview ($url, $key, $data) {
  zetaprints_debug();

  $data['Xml'] = 1;

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-preview;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_template_details($xml);
}

function zetaprints_get_preview_image_url ($url, $key, $data) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-preview;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_get_user_images ($url, $key, $data) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-imgs;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  $images = array();

  foreach ($xml->Image as $image)
    $images[] = array('folder' => (string)$image['Folder'],
                      'guid' => (string)$image['ImageID'],
                      'created' => zp_api_common_str2date($image['Created']),
                      'used' => zp_api_common_str2date($image['Used']),
                      'updated' => zp_api_common_str2date($image['Updated']),
                      'file_guid' => (string)$image['FileID'],
                      'mime' => (string)$image['MIME'],
                      'thumbnail' => (string)$image['Thumb'],
                      'thumbnail_width' => (int)$image['ThumbWidth'],
                      'thumbnail_height' => (int)$image['ThumbHeight'],
                      'width' => (int)$image['ImageWidth'],
                      'height' => (int)$image['ImageHeight'],
                      'description' => (string)$image['Description'],
                      'length' => (int)$image['Length'] );

  zetaprints_debug(array('images' => $images));

  return $images;
}

function zetaprints_download_customer_image ($url, $key, $data) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-img-new;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  if (count($xml->Image) != 1) {
    zetaprints_debug('Number of uploaded customer images is ' . count($xml->Image));
    return null;
  }

  $images = array();

  foreach ($xml->Image as $image)
    $images[] = array('folder' => (string)$image['Folder'],
                      'guid' => (string)$image['ImageID'],
                      'created' => zp_api_common_str2date($image['Created']),
                      'used' => zp_api_common_str2date($image['Used']),
                      'updated' => zp_api_common_str2date($image['Updated']),
                      'file_guid' => (string)$image['FileID'],
                      'mime' => (string)$image['MIME'],
                      'thumbnail' => (string)$image['Thumb'],
                      'thumbnail_width' => (int)$image['ThumbWidth'],
                      'thumbnail_height' => (int)$image['ThumbHeight'],
                      'width' => (int)$image['ImageWidth'],
                      'height' => (int)$image['ImageHeight'],
                      'description' => (string)$image['Description'],
                      'length' => (int)$image['Length'] );

  zetaprints_debug(array('images' => $images));

  return $images;
}

function zetaprints_get_edited_image_url ($url, $key, $data) {
  zetaprints_debug();

  if (!isset($data['action']) || strlen($data['action']) == 0) {
    zp_api_log_error("No picture edit action specified");
    return null;
  }

  $action = $data['action'];
  unset($data['action']);

  $response = zetaprints_get_content_from_url("{$url}/API.aspx?page=api-{$action};ApiKey={$key}", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_create_order ($url, $key, $data) {
  zetaprints_debug();

  $data['Xml'] = 1;

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order-save;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_get_order_id ($url, $key, $data) {
  zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order-save;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_complete_order ($url, $key, $order_guid, $new_guid = null) {
  zetaprints_debug();

  if ($new_guid)
    $new_guid_parameter = ";IDs={$new_guid}";
  else
    $new_guid_parameter = '';

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order-complete;ApiKey=$key;OrderID=$order_guid{$new_guid_parameter}");

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_register_user ($url, $key, $user_id, $password, $corporate_id = null) {
  zetaprints_debug();

  $request_url = "$url/api.aspx?page=api-user-new;ApiKey=$key;UserID=$user_id;Password=$password";

  if ($corporate_id && is_string($corporate_id) && count($corporate_id))
    $request_url .= ";CorporateID=$corporate_id";

  $response = zetaprints_get_content_from_url($request_url);

  if (zetaprints_has_error($response))
    return null;

  return strpos($response['content']['body'], '<ok />') !== false ? true : false;
}

function _parse_http_headers ($headers_string) {
  $lines = explode("\r\n", $headers_string);

  $headers = array();

  foreach ($lines as $line) {
    $key_value = explode(': ', $line);

    if (count($key_value) == 2)
      $headers[$key_value[0]] = $key_value[1];
    else
      $headers[] = $key_value[0];
  }

  return $headers;
}

function _return ($content, $error = false) {
  return array('error' => $error, 'content' => $content);
}

function ok ($content) {
  return _return($content);
}

function error ($message) {
  return _return($message, true);
}

function zetaprints_has_error ($response) {
  return !is_array($response) || !isset($response['error']) || !isset($response['content']) || $response['error'];
}

function zetaprints_get_content_from_url ($url, $data = null) {
  zetaprints_debug();

  $options = array(CURLOPT_URL => $url,
                   CURLOPT_HEADER => true,
                   CURLOPT_CRLF => true,
                   CURLOPT_RETURNTRANSFER => true,
                   CURLOPT_HTTPHEADER => array('Expect:') );

  if ($data) {
    $data_encoded = array();

    while (list($key, $value) = each($data))
      $data_encoded[] = urlencode($key).'='.urlencode($value);

    $options[CURLOPT_POSTFIELDS] = implode('&', $data_encoded);
  }

  zetaprints_debug(array('curl options' => $options));

  $curl = curl_init();

  if (!curl_setopt_array($curl, $options)) {
    zetaprints_debug("Can't set options for curl");
    return error("Can't set options for curl");
  }

  $output = curl_exec($curl);
  $info = curl_getinfo($curl);

  if ($output === false || $info['http_code'] != 200) {
    $zetaprins_message = '';

    if ($output !== false) {
      $output = explode("\r\n\r\n", $output);

      if (function_exists('http_parse_headers'))
        $headers = http_parse_headers($output[0]);
      else
        $headers = _parse_http_headers($output[0]);

      $zetaprins_message = (is_array($headers) && isset($headers['X-ZP-API-Error-Msg'])) ? $headers['X-ZP-API-Error-Msg'] : '';
    }

    $curl_error_message = curl_error($curl);
    curl_close($curl);

    zetaprints_debug(array('Error' => $curl_error_message, 'Curl info' => $info, 'Data' => $output));
    return error('Zetaprints error: ' . $zetaprins_message . '; Curl error: ' . $curl_error_message);
  }

  curl_close($curl);

  list($headers, $content) = explode("\r\n\r\n", $output, 2);

  if (function_exists('http_parse_headers'))
    $headers = http_parse_headers($headers);
  else
    $headers = _parse_http_headers($headers);

  if (isset($info['content_type'])) {
    $type = explode('/', $info['content_type']);

    if ($type[0] == 'image')
      zetaprints_debug(array('header' => $headers, 'body' => 'Image'));
    else
      zetaprints_debug(array('header' => $headers, 'body' => $content));
  } else
    zetaprints_debug(array('header' => $headers, 'body' => $content));

  return ok(array('header' => $headers, 'body' => $content));
}

?>
