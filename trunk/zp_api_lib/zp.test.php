<?php 
/**
  * zp api user guide
  * ZP API Functions :
  *	0. set api key and api url 
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
?>
<?php 

	include ("api/zp_api.php");
	/* api key and url */
	/* to use api function, we have init api key and api url by call zp_api_init as below */
	zp_api_init("612eca11-48fd-4df7-bff5-3d493919283e" , "http://realestate.zetaprints.com");
	
	/*************** 1. Catalogs API ***********/
	//1.1. Get list of catalogs for the domain from ZP sample
	$list = zp_api_catalog_list();
	print_r($list);//process result here
	/*
	 foreach ($list as $cdata){
		//process here
	 }
	  */
	//1.2. Get list of templates of catalog
	$list_template = zp_api_catalog_detail("0187C932-6EB2-4DFF-B90E-3B3BB33E9279");
	print_r($list_template);
	/*
	 foreach ($list_template as $template){
		//process here
	 }
	  */
		
	/*************** 2. Template API ***********/
	//2.1. Get details of template
	$template = zp_api_template_detail("7AFB079C-BB16-4856-89FF-2E69B5BE303E");
	print_r($template);
	/*
	 //process here
	  */
	
	//3.1. Regiser new User and  2.2. Show previews of template in IFRAME
	//create user and pass if not have
	$user = zp_api_common_uuid();
	$pass = zp_api_common_pass();
	//3.1. Regiser new User 
	$ret = zp_api_user_register($user, $pass);
	echo "regiter=[$ret]";
	//2.2. Show previews of template in IFRAME
	$iframe = zp_api_template_iframe_url ("7AFB079C-BB16-4856-89FF-2E69B5BE303E",$user,$pass);
	echo "iframe=[$iframe]";
	
	
	/*************** 4. Order API ***********/
	
	//4.1. Get Order details
	$id = "FB70B451-A132-461A-A859-C936A3846724";
	$order = zp_api_order_detail($id);
	print_r($order);
	/*
	 //process here
	  */
		
	//4.2. Saved order completion
	$id = "FB70B451-A132-461A-A859-C936A3846724";
	$order = zp_api_order_save($id);
	print_r($order);
	/*
	 //process here
	  */
	
	//4.3. Change Order status
	$id = "FB70B451-A132-461A-A859-C936A3846724";
	$order = zp_api_order_change($id, "deleted", "saved");
	print_r($order);
	/*
	 //process here
	  */
?>