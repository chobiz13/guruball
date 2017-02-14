<?php



header('Access-Control-Allow-Origin: *');  





// get the HTTP method, path and body of the request

$method = $_SERVER['REQUEST_METHOD'];

$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));





	require_once('./forum/includes/vb5/autoloader.php');

	$vbpath = 'forum/';

	vB5_Autoloader::register($vbpath);

	vB5_Frontend_Application::init('config.php');

	$api = Api_InterfaceAbstract::instance();

	if(!$request[0]){ die("false"); }

	switch ($request[0]) {

		case 'login':



			if(isset($_POST['redirurl'])) 

			   $url = "http://".$_SERVER['HTTP_HOST'].$_POST['redirurl']; // holds url for last page visited.

			else 

			   $url = "http://".$_SERVER['HTTP_HOST']; // default page for 



			



			$username = $_POST['username'];

			$password = $_POST['password'];

		

			$loginInfo = $api->callApi('user', 'login', array($username, $password));

			if (empty($loginInfo['errors']))

			{

			    // set cookies

			    vB5_Auth::setLoginCookies($loginInfo, '', !empty($_POST['remember']));

			    // redirect somewhere Also see: vB5_Auth::doLoginRedirect();

			    $userid = vB5_Cookie::get('userid', vB5_Cookie::TYPE_UINT);

		    	$hash = vB5_Cookie::get('password', vB5_Cookie::TYPE_STRING);

			    $userInfo = $api->callApi('user', 'fetchProfileInfo', array($userid)); //echo "<pre>"; var_dump($userInfo);

			   	//$userInfo['username'] . ' is logged in.';

			    

			    header("Location: $url");

			    

			    //exit;	   

			}

			else

			{
				$false_url = $url."/login/failed";
				header("Location: $false_url");

			    // there was a problem logging in.

			    // redirect or display errors here

			}

			break;

		case 'check':

			$userid = vB5_Cookie::get('userid', vB5_Cookie::TYPE_UINT);

		    $hash = vB5_Cookie::get('password', vB5_Cookie::TYPE_STRING);

			$userInfo = $api->callApi('user', 'fetchProfileInfo', array($userid));



			

			echo "<pre>";

			print_r($userInfo);

			$_SESSION['username'] = $userInfo['username'];

			$_SESSION['profilepicturepath'] = (isset($userInfo['profilepicturepath']) ? $userInfo['profilepicturepath'] : "");



			//echo $userInfo['username'] . ' is logged in.';

			# code...

			break;



		case 'logout':





			$hash = vB5_Cookie::get('password', vB5_Cookie::TYPE_STRING);

			vB5_Cookie::deleteAll();



			$url =  $_SERVER["HTTP_REFERER"];

			header("Location: $url");

			//header('Location : $_SERVER["HTTP_REFERER"]');

			break;

		default:

			echo 'false';

			break;

	}



	

