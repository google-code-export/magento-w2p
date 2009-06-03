<?php 
	include ("api/zp_api.php");
	//begin all
	zp_api_init("key" , "url");
	//test catalog functions
	/*$list = zp_api_catalog_list();
	print_r($list);
	$list_template = zp_api_catalog_detail("0187C932-6EB2-4DFF-B90E-3B3BB33E9279");
	print_r($list_template);
	*/
	//test template functions
	$template = zp_api_template_detail("7AFB079C-BB16-4856-89FF-2E69B5BE303E");
	print_r($template);
	
	$template = zp_api_template_detail("423BC9F1-BCAF-4FD9-AEAD-C0310DA19594");
	print_r($template);
	
	//user register
	/*
	$ret = zp_api_user_register("423BC9F1-BCAF-4FD9-AEAD-C0310DA19594","pass");
	echo "regiter=[$ret]";
	*/
	/*
	//Order 
	$id = "FB70B451-A132-461A-A859-C936A3846724";
	//$id = "7278EDC8-FA79-43D1-B2B2-B70845E857D4";
	$order = zp_api_order_detail($id);
	print_r($order);
	
	
	$id = "FB70B451-A132-461A-A859-C936A3846724";
	$id = "7278EDC8-FA79-43D1-B2B2-B70845E857D4";
	$order = zp_api_order_save($id);
	print_r($order);*/
	/*
	$id = "FB70B451-A132-461A-A859-C936A3846724";
	//$id = "7278EDC8-FA79-43D1-B2B2-B70845E857D4";
	$order = zp_api_order_change($id, "deleted", "saved");
	print_r($order);*/
?>