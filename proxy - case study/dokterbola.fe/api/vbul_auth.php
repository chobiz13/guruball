<?php

header('Access-Control-Allow-Origin: *');  
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
if($method == "POST"){
	require_once('../forum/includes/vb5/autoloader.php');
	$vbpath = '../forum/';
	vB5_Autoloader::register($vbpath);
	vB5_Frontend_Application::init('config.php');
	//$uid=$vbulletin->userinfo['userid'];
	//echo $uid;


	$username = 'alexine';
	$password = 'password';
	$api = Api_InterfaceAbstract::instance();
	$loginInfo = $api->callApi('user', 'login', array($username, $password));

	if (empty($loginInfo['errors']))
	{
	    // set cookies
	    vB5_Auth::setLoginCookies($loginInfo, '', !empty($_POST['remember']));

	    // redirect somewhere Also see: vB5_Auth::doLoginRedirect();
	    echo "true";
	    //header('Location: forum');
	    //exit;
	   
	}
	else
	{
		echo "failed";
	    // there was a problem logging in.
	    // redirect or display errors here
	}
}
