<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');
 
 
  function vbul_login() {
	  	require_once('forum/includes/vb5/autoloader.php');
		$vbpath = 'forum/';
		vB5_Autoloader::register($vbpath);
		vB5_Frontend_Application::init('config.php');

		$username = "alexine";
		$password = "asxz4521";
		$api = Api_InterfaceAbstract::instance();
	    $loginInfo = $api->callApi('user', 'login', array($username, $password));

	    if (empty($loginInfo['errors']))
	    {
	        // set cookies
	        vB5_Auth::setLoginCookies($loginInfo, '', !empty($_POST['remember']));

	        // redirect somewhere Also see: vB5_Auth::doLoginRedirect();
	        header('Location: forum');
	        //exit;
	       
	    }
	    else
	    {
	    	echo "failed";
	        // there was a problem logging in.
	        // redirect or display errors here
	    }
  }

  function vbul_user(){
  		
  		require_once('forum/includes/vb5/autoloader.php');
		$vbpath = 'forum/';
		vB5_Autoloader::register($vbpath);
		vB5_Frontend_Application::init('config.php');
		$uid=$vbulletin->userinfo['userid'];
		echo $uid;
  }
 
/* End of file dropdwon_helper.php */
/* Location: ./application/helper/dropdown_helper.php */
