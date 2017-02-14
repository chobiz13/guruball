<?php
/*========================================================================*\
|| ###################################################################### ||
|| # vBulletin 5.2.3 - Licence Number LC451E80E8
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2016 vBulletin Solutions Inc. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

/**
 * Light version of the application, for fixed routes like getting phrases, options, etc. At the time of writing this, the
 * biggest improvement is skipping the route parsing. There's a lot of processing needed for handling forum-type, channel-type urls
 * that isn't needed for the static routes.
 *
 * @package		vBulletin presentation
 */

class vB5_Frontend_ApplicationLight extends vB5_ApplicationAbstract
{
	//This is just the array of routing-type information.  It defines how the request will be processed.
	protected $application = array();

	//This defines the routes that can be handled by this class.
	protected static $quickRoutes = array
	(
		'ajax/api/phrase/fetch' => array(
			'controller'  => 'phrase',
			'method'      => 'fetchStatic',
			'static'      => true,
			'handler'     => 'fetchPhrase',
			'requirePost' => true,
		),
		'ajax/api/options/fetchValues' => array(
			'controller'  => 'phrase',
			'method'      => 'fetchStatic',
			'static'      => true,
			'handler'     => 'fetchOptions',
			'requirePost' => true,
		),
		'filedata/fetch' => array(
			'static'      => true,
			'handler'     => 'fetchImage',
			'requirePost' => false,
		),
		'external' => array(
			'controller'     => 'external',
			'callcontroller' => true,
			'method'         => 'output',
			'static'         => false,
			'requirePost'    => false,
		),
	);

	/**
	 * @var array Quick routes that match the beginning of the route string
	 */
	protected static $quickRoutePrefixMatch = array(
		'ajax/apidetach' => array(
			'handler'     => 'handleAjaxApiDetached',
			'static'      => false,
			'requirePost' => true,
		), // note, keep this before ajax/api. More specific routes should come before
		// less specific ones, to allow the prefix check to work correctly, see constructor.
		'ajax/api' => array(
			'handler'     => 'handleAjaxApi',
			'static'      => false,
			'requirePost' => true,
		),
		'ajax/render' => array(
			'handler'     => 'callRender',
			'static'      => false,
			'requirePost' => true,
		),
	);

	protected $userid;
	protected $languageid;

	/** Tells whether this class can process this request
	 *
	 * @return bool
	 */
	public static function isQuickRoute()
	{
		if (empty($_REQUEST['routestring']))
		{
			return false;
		}

		if (isset(self::$quickRoutes[$_REQUEST['routestring']]))
		{
			return true;
		}

		foreach (self::$quickRoutePrefixMatch AS $prefix => $route)
		{
			if (substr($_REQUEST['routestring'], 0, strlen($prefix)) == $prefix)
			{
				return true;
			}
		}

		return false;
	}

	/**Standard constructor. We only access applications through init() **/
	protected function __construct()
	{
		if (empty($_REQUEST['routestring']))
		{
			return false;
		}

		if (isset(self::$quickRoutes[$_REQUEST['routestring']]))
		{
			$this->application = self::$quickRoutes[$_REQUEST['routestring']];
			return true;
		}

		foreach (self::$quickRoutePrefixMatch AS $prefix => $route)
		{
			if (substr($_REQUEST['routestring'], 0, strlen($prefix)) == $prefix)
			{
				$this->application = $route;
				return true;
			}
		}

		return false;
	}

	/**
	 * This is the standard way to initialize an application
	 *
	 * @param 	string	location of the configuration file
	 *
	 * @return this application object
	 */
	public static function init($configFile)
	{
		self::$instance = new vB5_Frontend_ApplicationLight();

		$config = vB5_Config::instance();
		$config->loadConfigFile($configFile);
		$corePath = vB5_Config::instance()->core_path;
		//this will be set by vb::init
		//define('CWD', $corePath);
		define('CSRF_PROTECTION', true);
		define('VB_AREA', 'Presentation');
		require_once ($corePath . "/vb/vb.php");
		vB::init();
		vB::setRequest(new vB_Request_WebApi());
		self::ajaxCharsetConvert();

		return self::$instance;
	}

	protected function getSessionData($needUserId)
	{
		/** We need userid and languageid */
		$config = vB5_Config::instance();
		//first see if we have a php session
		if (!empty($config->php_sessions))
		{
			session_start();
			if (isset($_SESSION['userid']) AND isset($_SESSION['languageid']))
			{
				return $_SESSION;
			}
		}
		//Check cookies
		if ($config->cookie_enabled !== false)
		{
			$cookiePrefix = $config->cookie_prefix;

			if (isset($_COOKIE[$cookiePrefix . 'languageid']) AND !$needUserId)
			{
				return array('languageid' => $_COOKIE[$cookiePrefix . 'languageid']);
			}

			if (!empty($_COOKIE[$cookiePrefix . 'sessionhash']))
			{
				return  Api_Interface_Collapsed::callApiStatic(
					'session',
					'getInfoFromHash',
					array($_COOKIE[$cookiePrefix . 'sessionhash']),
					false
				);
			}

		}

	}

	/**
	 * Executes the application. Normally this means to get some data. We usually return in json format.
	 *
	 * @return bool
	 * @throws vB_Exception_Api
	 */
	public function execute()
	{
		if (empty($this->application))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		// These handlers must require POST request method, but POST requests can accept parameters passed in via
		// both the post body ($_POST) and querystring in the url ($_GET)
		if ($this->application['requirePost'])
		{
			if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')
			{
				throw new vB5_Exception('Incorrect HTTP Method. Please use a POST request.');
			}

			// Also require a CSRF token check.
			static::checkCSRF();
		}

		$serverData = array_merge($_GET, $_POST);

		if (!empty($this->application['handler']) AND method_exists($this, $this->application['handler']))
		{
			$app = $this->application['handler'];
			call_user_func(array($this, $app), $serverData);

			return true;
		}
		else if ($this->application['static'])
		{
			//BEWARE- NOT YET TESTED
			$result = Api_InterfaceAbstract::instance()->callApiStatic(
				$this->application['controller'],
				$this->application['method'],
				$serverData,
				true
			);
		}
		else if ($this->application['callcontroller'])
		{
			$response = $this->callController(
				$this->application['controller'],
				$this->application['method']
			);

			// using an array will let us have more control on the response.
			// we can easily extend to support printing different kind of outputs.
			echo $response['response'];

			return true;
		}
		else
		{
			//We need to create a session
			$result = Api_InterfaceAbstract::instance()->callApi(
				$this->application['controller'],
				$this->application['method'],
				$serverData,
				true
			);
		}

		$controller = new vB5_Frontend_Controller();
		$controller->sendAsJson($result);

		return true;
	}

	/**
	 * Calls a controller action and returns the response.
	 *
	 * @param 	string 	Controller name.
	 * @param 	string 	Controller action.
	 *
	 * @return 	array 	Information of controller call:
	 *					- Response => the result from calling the controller action.
	 *
	 */
	private function callController($controller, $action)
	{
		$controller = ucfirst(strtolower($controller));
		$action = ucfirst(strtolower($action));
		$controllerClass = 'vB5_Frontend_Controller_' . $controller;
		$controllerMethod = 'action' . $action;

		if (class_exists($controllerClass) AND method_exists($controllerClass, $controllerMethod))
		{
			$controller = new $controllerClass();
			return array('response' => $controller->$controllerMethod());
		}

		return array('response' => '');
	}

	/**
	 * This gets phrase data from an ajax request.
	 *
	 * @param array Array of server data (from $_POST and/or $_GET, see execute())
	 */
	protected function fetchPhrase($serverData)
	{
		$sessionData = $this->getSessionData(true);
		$phrases = Api_Interface_Collapsed::callApiStatic(
			'phrase',
			'fetchStatic',
			array(
				'phrases' => $serverData['phrases'],
				'languageid' => $sessionData['languageid'],
				'userid' => $sessionData['userid']
			),
			true
		);

		$this->sendAsJson($phrases);
	}


	/**
	 * This gets phrase data from an ajax request.
	 *
	 * @param array Array of server data (from $_POST and/or $_GET, see execute())
	 */
	protected function fetchOptions($serverData)
	{
		$options = Api_Interface_Collapsed::callApiStatic(
			'options',
			'fetchStatic',
			array(
				'options' => $serverData['options'],
			),
			true
		);

		$this->sendAsJson($options);
	}

	/**
	 * Renders a template from an ajax call
	 *
	 * @param array Array of server data (from $_POST and/or $_GET, see execute())
	 */
	protected function callRender($serverData)
	{
		$routeInfo = explode('/', $serverData['routestring']);

		if (count($routeInfo) < 3)
		{
			throw new vB5_Exception_Api('ajax', 'render', array(), 'invalid_request');
		}

		$this->router = new vB5_Frontend_Routing();
		$this->router->setRouteInfo(array(
			'action'          => 'actionRender',
			'arguments'       => $serverData,
			'template'        => $routeInfo[2],
			// this use of $_GET appears to be fine,
			// since it's setting the route query params
			// not sending the data to the template
			// render
			'queryParameters' => $_GET,
		));
		Api_InterfaceAbstract::setLight();

		$this->sendAsJson(vB5_Template::staticRenderAjax($routeInfo[2], $serverData));
	}

	/**
	 * This handles an ajax api call.
	 *
	 * @param array Array of server data (from $_POST and/or $_GET, see execute())
	 */
	protected function handleAjaxApi($serverData)
	{
		$routeInfo = explode('/', $serverData['routestring']);

		if (count($routeInfo) < 4)
		{
			throw new vB5_Exception_Api('ajax', 'api', array(), 'invalid_request');
		}

		//we use : to delineate packages in controller names, but that's a reserved
		//character in the url structure so we use periods in URLs.
		$controller = str_replace('.', ':', $routeInfo[2]);

		$this->sendAsJson(Api_InterfaceAbstract::instance(Api_InterfaceAbstract::API_LIGHT)->callApi(
			$controller,
			$routeInfo[3],
			$serverData,
			true
		));
	}

	/**
	 * This handles an ajax api call, detatched from the current request
	 *
	 * @param array Array of server data (from $_POST and/or $_GET, see execute())
	 */
	protected function handleAjaxApiDetached($serverData)
	{
		// Keep this function in sync with vB5_Frontend_Controller::sendAsJsonAndCloseConnection()
		// TODO: Make the controller function public and have this call it.
		// The main reason I didn't do this now is because there are some differences between this class's
		// sendAsJson() & the controller, and the changes were starting to get a bit too big for this particular
		// JIRA than I was comfortable with.

		//make sure this is a valid request before detaching.
		$routeInfo = explode('/', $serverData['routestring']);
		if (count($routeInfo) < 4)
		{
			throw new vB5_Exception_Api('ajax', 'apidetach', array(), 'invalid_request');
		}

		ignore_user_abort(true);
		@set_time_limit(0);

		// browser will think there is no more data if content-length is what is returned
		// regardless of how long the script continues to execute, apart from IIS + CGI
		$output = array('success' => true, 'note' => 'Returned before processing');

		$this->sendAsJson($output);

		// ob_end_flush and flush are needed for the browser to think the request is complete
		if (ob_get_level())
		{
			ob_end_flush();
		}
		flush();

		//this is intended to make the detach funcion work for people running php-fpm.
		if (function_exists('fastcgi_finish_request'))
		{
			fastcgi_finish_request();
		}


		//we use : to delineate packages in controller names, but that's a reserved
		//character in the url structure so we use periods in URLs.
		$controller = str_replace('.', ':', $routeInfo[2]);

		//don't do anything with the return, we've already let the broswer go.
		Api_InterfaceAbstract::instance(Api_InterfaceAbstract::API_LIGHT)->callApi(
			$controller,
			$routeInfo[3],
			$serverData,
			true
		);
	}

	/**
	 * This gets an image
	 *
	 * @param array Array of server data (from $_POST and/or $_GET, see execute())
	 */
	protected function fetchImage($serverData)
	{
		$api = Api_InterfaceAbstract::instance('light');

		$request = array(
			'id'          => 0,
			'type'        => '',
			'includeData' => true,
		);

		if (isset($serverData['type']) AND !empty($serverData['type']))
		{
			$request['type'] = $serverData['type'];
		}
		else if (!empty($serverData['thumb']) AND intval($serverData['thumb']))
		{
			$request['type'] = 'thumb';
		}

		if (!empty($serverData['id']) AND intval($serverData['id']))
		{
			// Don't put an intval() call in an if condition and then subsequently
			// *use* the non-intval'ed value. Normally, you'd use intval to
			// typecast *before* the if condition.
			$serverData['id'] = intval($serverData['id']);

			$request['id'] = $serverData['id'];
			try
			{
				set_error_handler(array($this, 'handleImageError'), E_ALL | E_STRICT ) ;
				// we can have type photo nodes coming in via the id parameter
				// when text.previewimage is used in article listings or the
				// content slider module.
				$nodeInfo = $api->callApi('node', 'getNode', array('nodeid' => $request['id']));
				$contentType = $api->callApi('contenttype', 'fetchContentTypeClassFromId', array('contenttypeid' => $nodeInfo['contenttypeid']));
				if ($contentType == 'Photo')
				{
					$fileInfo = $api->callApi('content_photo', 'fetchImageByPhotoid', $request);
				}
				else
				{
					$fileInfo = $api->callApi('content_attach', 'fetchImage', $request);
				}
			}
			catch(Exception $e)
			{
				//just end quietly
				return '';
			}
		}
		else if (!empty($serverData['filedataid']) AND intval($serverData['filedataid']))
		{
			// Don't put an intval() call in an if condition and then subsequently
			// *use* the non-intval'ed value. Normally, you'd use intval to
			// typecast *before* the if condition.
			$serverData['filedataid'] = intval($serverData['filedataid']);

			$request['id'] = $serverData['filedataid'];
			try
			{
				set_error_handler(array($this, 'handleImageError'), E_ALL | E_STRICT ) ;
				$fileInfo = $api->callApi('filedata', 'fetchImageByFiledataid', $request);
			}
			catch(Exception $e)
			{
				//just end quietly
				return '';
			}
		}
		else if (!empty($serverData['photoid']) AND intval($serverData['photoid']))
		{
			// Don't put an intval() call in an if condition and then subsequently
			// *use* the non-intval'ed value. Normally, you'd use intval to
			// typecast *before* the if condition.
			$serverData['photoid'] = intval($serverData['photoid']);

			$request['id'] = $serverData['photoid'];
			$fileInfo = $api->callApi('content_photo', 'fetchImageByPhotoid', $request);
		}
		else if (!empty($serverData['linkid']) AND intval($serverData['linkid']))
		{
			// Don't put an intval() call in an if condition and then subsequently
			// *use* the non-intval'ed value. Normally, you'd use intval to
			// typecast *before* the if condition.
			$serverData['linkid'] = intval($serverData['linkid']);

			$request['id'] = $serverData['linkid'];
			$request['includeData'] = false;
			try
			{
				set_error_handler(array($this, 'handleImageError'), E_ALL | E_STRICT ) ;
				$fileInfo = $api->callApi('content_link', 'fetchImageByLinkId', $request);
			}
			catch(Exception $e)
			{
				//just end quietly
				return '';
			}
		}
		else if (!empty($serverData['attachid']) AND intval($serverData['attachid']))
		{
			// Don't put an intval() call in an if condition and then subsequently
			// *use* the non-intval'ed value. Normally, you'd use intval to
			// typecast *before* the if condition.
			$serverData['attachid'] = intval($serverData['attachid']);

			$request['id'] = $serverData['attachid'];
			try
			{
				set_error_handler(array($this, 'handleImageError'), E_ALL | E_STRICT ) ;
				$fileInfo = $api->callApi('content_attach', 'fetchImage', $request);
			}
			catch(Exception $e)
			{
				//just end quietly
				return '';
			}
		}
		else if (!empty($serverData['channelid']) AND intval($serverData['channelid']))
		{
			// Don't put an intval() call in an if condition and then subsequently
			// *use* the non-intval'ed value. Normally, you'd use intval to
			// typecast *before* the if condition.
			$serverData['channelid'] = intval($serverData['channelid']);

			$request['id'] = $serverData['channelid'];
			try
			{
				set_error_handler(array($this, 'handleImageError'), E_ALL | E_STRICT ) ;
				$fileInfo = $api->callApi('content_channel', 'fetchChannelIcon', $request);
			}
			catch(Exception $e)
			{
				//just end quietly
				return '';
			}
		}
		else
		{
			return '';
		}

		if (!empty($fileInfo['filedata']))
		{
			header('ETag: "' . $fileInfo['filedataid'] . '"');
			header('Accept-Ranges: bytes');
			header('Content-transfer-encoding: binary');
			header("Content-Length: " . $fileInfo['filesize'] );

			$fileInfo['extension'] = strtolower($fileInfo['extension']);
			if (in_array($fileInfo['extension'], array('jpg', 'jpe', 'jpeg', 'gif', 'png')))
			{
				header("Content-Disposition: inline; filename=\"image_" . $fileInfo['filedataid'] .  "." . $fileInfo['extension'] . "\"");
				header('Content-transfer-encoding: binary');
			}
			else
			{
				$attachInfo = $api->callApi('content_attach', 'fetchAttachByFiledataids', array('filedataids' => array($fileInfo['filedataid'])));

				// force files to be downloaded because of a possible XSS issue in IE
				header("Content-disposition: attachment; filename=\"" . $attachInfo[$fileInfo['filedataid']]['filename']. "\"");
			}
			header('Cache-control: max-age=31536000, private');
			header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 31536000) . ' GMT');
			header('Pragma:');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileInfo['dateline']) . ' GMT');
			foreach ($fileInfo['headers'] as $header)
			{
				header($header);
			}

			echo $fileInfo['filedata'];
		}
	}

	/**
	 * Sends the response as a JSON encoded string
	 *
	 * @param	mixed	The data (usually an array) to send
	 */
	protected function sendAsJson($data)
	{
		//This function needs to be kept in sync with the implmentation in controller.php
		//(Some items are specific to the controller implemenation and are not copied here)

		if (headers_sent($file, $line))
		{
			throw new Exception("Cannot send response, headers already sent. File: $file Line: $line");
		}

		// We need to convert $data charset if we're not using UTF-8
		if (vB5_String::getTempCharset() != 'UTF-8')
		{
			$data = vB5_String::toCharset($data, vB5_String::getTempCharset(), 'UTF-8');
		}

		//If this is IE9, IE10, or IE11 -- we also need to work around the deliberate attempt to break "is IE" logic by the
		//IE dev team -- we need to send type "text/plain". Yes, we know that's not the standard.
		if (
			isset($_SERVER['HTTP_USER_AGENT']) && (
				(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) OR
				(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)
			)
		)
		{
			header('Content-type: text/plain; charset=UTF-8');
		}
		else
		{
			header('Content-type: application/json; charset=UTF-8');
		}

		// IE will cache ajax requests, and we need to prevent this - VBV-148
		header('Cache-Control: max-age=0,no-cache,no-store,post-check=0,pre-check=0');
		header('Expires: Sat, 1 Jan 2000 01:00:00 GMT');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Pragma: no-cache");

		$output = vB5_String::jsonEncode($data);
		$sapi_name = php_sapi_name();
		if (!(strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false AND strpos($sapi_name, 'cgi') !== false))
		{
			$outputLength = strlen($output);
			header('Content-Length: ' . $outputLength);
			header('Connection: Close');
		}

		echo $output;
	}

	/***
	 * Displays a vB page for errors
	 *
	 *	@param	string	error number
	 *	@param	string	error message
	 *	@param	string	filename
	 *	@param	string	line number
	 */
	public static function handleError($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$config = vB5_Config::instance();

		if ($config->debug)
		{
			if (!empty($error) AND is_array($error))
			{
				echo "Error :" . $error['message'] . ' on line ' . $error['line'] . ' in ' . $error['file'] . "<br />\n";
			}

			if (!empty($trace))
			{
				foreach ($trace as $key => $step)
				{
					$line = "Step $key: " . $step['function'] . '() called' ;

					if (!empty($step['line']))
					{
						$line .= ' on line ' . $step['line'];
					}

					if (!empty($step['file']))
					{
						$line .= ' in ' . $step['file'];
					}

					echo "$line <br />\n";
				}

			}
			if (!empty($exception))
			{
				echo "Exception " . $exception->getMessage() . " on line " . $exception->getLine() . " in " . $exception->getFile() . "<br />\n";
			}
		}
		else
		{
			static::echoOnFireMessage();
		}
		die();
	}


	/**If there is an error, there's little we can do. We have a 1px file. Let's return that with a header so the
	 * client won't request it again soon;
	 **/
	public function handleImageError($error)
	{

		$location = pathinfo(__FILE__, PATHINFO_DIRNAME);

		if (file_exists($location . '/../../../../images/1px.png'))
		{
			$contents = file_get_contents($location . '/../../../../images/1px.png');
		}
		else
		{
			die('');
		}
		header('Content-Type: image/png');
		header('Accept-Ranges: bytes');
		header('Content-transfer-encoding: binary');
		header("Content-Length: " . strlen($contents) );
		header("Content-Disposition: inline; filename=\"1px.png\"");
		header('Cache-control: max-age=31536000, private');
		header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 31536000) . ' GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
		die($contents);
	}

	/*** Displays a vB page for exceptions
	 *
	 *	@param	mixed 	exception
	 *	@param	bool 	Bypass API and display simple error message
	 *
	 *
	 ***/
	public static function handleException($exception, $simple = false)
	{
		$config = vB5_Config::instance();

		if ($config->debug)
		{
			echo "Exception ". $exception->getMessage() . ' in file ' . $exception->getFile() . ", line " . $exception->getLine() .
				"<br />\n". $exception->getTrace();
		}

		if (!headers_sent())
		{
			// Set HTTP Headers
			if ($exception instanceof vB5_Exception_404)
			{
				header("HTTP/1.0 404 Not Found");
				header("Status: 404 Not Found");
			}
			else
			{
				header('HTTP/1.1 500 Internal Server Error');
				header("Status: 500 Internal Server Error");
			}
		}
		die();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87400 $
|| #######################################################################
\*=========================================================================*/
