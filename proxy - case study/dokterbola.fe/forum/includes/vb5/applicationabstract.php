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

abstract class vB5_ApplicationAbstract {
	protected static $instance;
	protected $router = NULL;
	protected static $needCharset = false;

	const ON_FIRE_MESSAGE = <<<EOD
<head>
	<title>System Error</title>
</head>
<body>
	<h1> A System Error has occured.</h1>
	<p> The software is experiencing a systems error.</p>
	<p> You should attempt to repeat your last action. If this error occurs again, please contact the {{{site_administrator}}}.</p>
</body>
EOD;

	public static function instance()
	{
		if (!isset(self::$instance))
		{
			throw new vB5_Exception('Application hasn\'t been initialized!');
		}

		return self::$instance;
	}

	public static function init($configFile)
	{
		$config = vB5_Config::instance();
		$config->loadConfigFile($configFile);

		require_once($config->core_path . '/vb/vb.php');
		vB::init();


		if (!defined('VB_ENTRY'))
		{
			define('VB_ENTRY', 1);
		}

		set_exception_handler(array('vB5_ApplicationAbstract','handleException'));
	}

	/**
	 * Replacing meta tag from the header.xml with header in the requests. See VBV-6361
	 *
	 */
	protected static function setHeaders() {
		header('X-UA-Compatible: IE=edge,chrome=1');

		// add no cache directive if it's set in options
		// redirect to install page if there's no database
		try {
			if (headers_sent())
			{
				self::$needCharset = true;
			}
			else
			{
				header('Content-Type: text/html; charset=' . $charset = vB5_String::getCharset());
			}
			$api = Api_InterfaceAbstract::instance();
			$option = $api->callApi('options', 'fetchValues', array(array('nocacheheaders')));

			if (!empty($option['nocacheheaders']))
			{
				header("Expires: Fri, 01 Jan 1990 00:00:00 GMT");
				header("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");
				header("Pragma: no-cache");
			}

		} catch (Exception $e) {
			if ($e->getMessage() == 'no_vb5_database') {
				header('Location: ' . vB5_Template_Options::instance()->get('options.bburl') . '/install/index.php');
				exit;
			}
			else {
				vB5_ApplicationAbstract::handleException($e, true);
			}
		}
	}

	public function getRouter()
	{
		return $this->router;
	}

	/**
	 * @static
	 * When current lang charset isn't the one in http content_type header
	 * this method will convert All Ajax $_POST data into current language charset
	 */
	protected static function ajaxCharsetConvert() {
		$requestcharset = '';
		if (isset($_SERVER["CONTENT_TYPE"]) AND $_SERVER["CONTENT_TYPE"])
		{
			if (($pos = strpos(strtoupper($_SERVER["CONTENT_TYPE"]), 'CHARSET')) !== false)
			{
				$requestcharset = substr(strtoupper($_SERVER["CONTENT_TYPE"]), $pos);
				$temp = explode('=', $requestcharset);
				if (!empty($temp[1]))
				{
					$requestcharset = trim($temp[1]);
				}
			}
		}

		if ($requestcharset	AND vB5_String::getTempCharset() != $requestcharset)
		{
			$routestring = isset($_REQUEST['routestring']) ? $_REQUEST['routestring'] : null;
			$_COOKIE = vB5_String::toCharset($_COOKIE, $requestcharset);
			$_GET = vB5_String::toCharset($_GET, $requestcharset);
			$_POST = vB5_String::toCharset($_POST, $requestcharset);
			$_REQUEST = vB5_String::toCharset($_REQUEST, $requestcharset);

			if ($routestring !== null)
			{
				// Preserve the utf-8 encoded route string
				$_REQUEST['routestring'] = $routestring;

				if (isset($_GET['routestring']))
				{
					$_GET['routestring'] = $routestring;
				}

				if (isset($_POST['routestring']))
				{
					$_POST['routestring'] = $routestring;
				}
			}
			// Note we're not doing file names here because they're set via multipart form.
			// So $_SERVER["CONTENT_TYPE"] won't have CHARSET in it.
		}
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
			$message = $exception->getMessage();

			if (!$simple)
			{
				$api = Api_InterfaceAbstract::instance();
				$phrase = $api->callApi('phrase', 'fetch', array('phrase' => $message));

				if (!empty($phrase))
				{
					$message = array_pop($phrase);
				}
			}
			else
			{
				$message = $exception->getMessage();
			}

			$error = array('message' => $message,
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => $exception->getTrace()
			);
		}
		else
		{
			$error = false;
		}

		if (!headers_sent())
		{
			// Set HTTP Headers
			if ($exception instanceof vB5_Exception_404)
			{
				$redirect_404_to_root = vB5_Template_Options::instance()->get('options.redirect_404_to_root');
				if ($redirect_404_to_root == '1')
				{
					header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl'), true, 301);
				}
				else
				{
					header("HTTP/1.0 404 Not Found");
					header("Status: 404 Not Found");

					// if it's a 404, let's have a slightly more appropriate
					// error message than pm_ajax_error_desc
					if (!$error)
					{
						$error = array('message' => $exception->getMessage());
					}
				}
			}
			else
			{
				header('HTTP/1.1 500 Internal Server Error');
				header("Status: 500 Internal Server Error");
			}
		}

		self::showErrorPage($error, $simple);
		die();
	}

	/*** Displays a vB page for no_permission exception
	*
	*	@param	mixed 	exception
	*
	*
	***/
	public static function handleNoPermission()
	{
		header('HTTP/1.1 403 Forbidden');
		header("Status: 403 Forbidden");
		self::showNoPermissionPage();
		die();
	}

	/*** Displays a vB page for banned users
	*
	*	@param	mixed 	exception
	*
	*
	***/
	public static function handleBannedUsers($bannedInfo)
	{
		header('HTTP/1.1 403 Forbidden');
		header("Status: 403 Forbidden");
		self::showBannedPage($bannedInfo);
		die();
	}

	/*** Displays a vB page for errors
	 *
	 *	@param	string	error number
	 *	@param	string	error message
	 *	@param	string	filename
	 *	@param	string	line number
	 *
	 *
	 ***/
	public static function handleError($errno, $errstr, $errfile, $errline, $errcontext)
	{
		// Explain to (strpos($errfile, 'includes\vb5\template\runtime.php') === false) condition:
		//   Now the eval() in vB5_Template::render() method won't throw any errors because of the prepended @ operator
		//   To catch the error of the template, we check if the error is thrown by runtime.php
		//   If so, regardless the error_reporting() value, we report the error.
		if (!(error_reporting() & $errno) AND strpos($errfile, 'includes\vb5\template\runtime.php') === false) {
			// This error code is not included in error_reporting
			return;
		}
		//for warning, notices, etc
		$config = vB5_Config::instance();
		if ($config->debug)
		{
			$error = array('message' => $errstr,
				'file' => $errfile,
				'line' => $errline,
			'trace' => debug_backtrace());
		}
		else
		{
			$error = false;
		}

		if (!headers_sent())
		{
			header('HTTP/1.1 500 Internal Server Error');
			header("Status: 500 Internal Server Error");
		}

		self::showErrorPage($error);
		die();
	}

	protected static function minErrorPage($error, $exception = null, $trace = null)
	{
		self::setCharset();

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

	private static function echoOnFireMessage()
	{
		/*
			If things are on fire (ex. DB is down), and debug mode is not enabled, this gets called.
		*/

		$siteadmin = 'site administrator';
		/*
			TODO: Do we want a new frontend config value for this instead of the DB technicalemail?
		 */
		try
		{
			$backendConfig = vB::getConfig();
			if ($backendConfig AND !empty($backendConfig['Database']['technicalemail']))
			{
				$siteadmin = "<a href=\"mailto:" . $backendConfig['Database']['technicalemail'] . "\">site administrator</a>";
			}
		}
		catch(Exception $e)
		{
			// if fetching the config failed because things are really going down, let's just output the message without fuss.
		}

		echo str_replace('{{{site_administrator}}}', $siteadmin, vB5_ApplicationAbstract::ON_FIRE_MESSAGE);
	}

	protected static function setCharset()
	{
		if (headers_sent())
		{
			// This only works if the preheader template is rendered after this call.
			self::$needCharset = true;
		}
		else
		{
			// ATM I don't see a need to support any other content types, as this is
			// used in a myriad of error handler pages which just displays a message
			// or trace at most. But it wouldn't be too difficult to just add a param to
			// this function to set the content-type appropriately here.
			header('Content-Type: text/html; charset=' . $charset = vB5_String::getCharset());
		}
	}

	protected static function showErrorPage($error, $simple = false)
	{
		//We want the simplest possible page.
		static $inHandler = false;

		//This block is to prevent error loops. If an error occurs while rendering the page we'll wind up here.
		if ($inHandler OR $simple)
		{
			self::minErrorPage($error);
		}

		$inHandler = true;
		$trace = debug_backtrace(false);

		try
		{
			$page = array();
			$page['routeInfo'] = self::getRouteInfo();
			$page['ignore_np_notices'] = self::getIgnoreNPNotices();

			$templater = new vB5_Template('error_page');
			$templater->registerGlobal('page', $page);
			$templater->register('error', $error);

			$output = self::getPreheader() . $templater->render();

			//not sure what the purpose is here -- output will always have some kind of value, even if its
			if ($output)
			{
				echo $output;
			}
			else
			{
				self::minErrorPage($error, null, $error['trace']);
			}
		}
		catch(Error $e)
		{
			self::minErrorPage($error, $e, $trace);
		}
		catch(Exception $e)
		{
			self::minErrorPage($error, $e, $trace);
		}
	}

	public static function handleFormError($error, $url)
	{
		self::showErrorForm($error, $url);
		die();
	}

	protected static function showErrorForm($error, $url)
	{
		$page = array();
		$page['routeInfo'] = self::getRouteInfo();
		$page['ignore_np_notices'] = self::getIgnoreNPNotices();

		//We want the simplest possible page.
		$templater = new vB5_Template('error_page_form');

		$templater->registerGlobal('page', $page);
		// check to see if any arguments were passed in
		$args = array();
		if(is_array($error))
		{
			$args = is_array($error[1]) ? $error[1] : array($error[1]);
			$error = $error[0];
		}
		$templater->register('error', $error);
		$templater->register('args', $args);
		$templater->register('url', $url);
		echo self::getPreheader() . $templater->render();
	}

	public static function checkState($route = array())
	{
		if ($response = Api_InterfaceAbstract::instance()->callApi('state', 'checkBeforeView', array('route' => $route)))
		{
			self::showMsgPage($response['title'], $response['msg'], $response['state']);
			die();
		}
	}

	/**
	 * Show a simple and clear message page which contains no widget
	 *
	 * @param string $title Page title. HTML will be escaped.
	 * @param string $msg Message to display. HTML is allowed and the caller must make sure it's valid.
	 * @param string $state The state of the site
	 */
	public static function showMsgPage($title = '', $msg = '', $state = '')
	{
		$page = array();
		$page['routeInfo'] = self::getRouteInfo();
		$page['ignore_np_notices'] = self::getIgnoreNPNotices();

		$page['title'] = $title;
		$page['state'] = $state;

		//We want the simplest possible page.
		$templater = new vB5_Template('message_page');
		$templater->register('page', $page);
		$templater->register('message', $msg);

		echo self::getPreheader() . $templater->render();
	}


	protected static function showNoPermissionPage($message = '')
	{
		$page = array();
		$page['routeInfo'] = self::getRouteInfo();
		$page['ignore_np_notices'] = self::getIgnoreNPNotices();

		//We want the simplest possible page.
		$templater = new vB5_Template('no_permission_page');
		$templater->register('message', $message);
		$templater->registerGlobal('page', $page);
		echo self::getPreheader() . $templater->render();
	}

	protected static function showBannedPage($bannedInfo)
	{
		//We want the simplest possible page.
		$page = array();
		$page['routeInfo'] = self::getRouteInfo();
		$page['ignore_np_notices'] = self::getIgnoreNPNotices();

		$templater = new vB5_Template('banned_page');
		$templater->registerGlobal('page', $page);
		$templater->register('bannedInfo', $bannedInfo);
		echo self::getPreheader() . $templater->render();
	}


	/*
	 *	We currently need these is a lot of places so let's keep it DRY
	 */

	/*
	 *	Public because we need this outside of this file.
	 */
	public static function getPreheader()
	{
		// This is called here in case any of the many handleXError()
		// functions forgot to set the charset in the header.
		self::setCharset();

		$templater = new vB5_Template('preheader');

		if (self::$needCharset)
		{
			$templater->register('charset', vB5_String::getTempCharset());
		}
		else
		{
			$templater->register('charset', false);
		}

		$html = $templater->render();
		Api_InterfaceAbstract::instance()->invokeHook('hookFrontendPreheader', array('preheaderHtml' => &$html));
		return $html;
	}

	protected static function getRouteInfo()
	{
			$router = vB5_ApplicationAbstract::instance()->getRouter();
			if (!empty($router))
			{
				$arguments = $router->getArguments();
				return array(
					'routeId' => $router->getRouteId(),
					'arguments' => $arguments,
					'queryParameters' => $router->getQueryParameters()
				);
			}
			return array();
	}

	public static function getIgnoreNPNotices()
	{
		$cookiekey = vB5_Config::instance()->cookie_prefix . 'np_notices_displayed';
		if(isset($_COOKIE[$cookiekey]))
		{
			return explode(',', $_COOKIE[$cookiekey]);
		}
		else
		{
			return array();
		}
	}

	final public static function checkCSRF()
	{
		/*
			Keep this function in sync with vB_Api_State::checkCSRF()
		 */

		$response = Api_InterfaceAbstract::instance()->callApi('state', 'checkCSRF');
		if (!empty($response))
		{
			throw new vB5_Exception($response['error']);
		}

		return true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87832 $
|| #######################################################################
\*=========================================================================*/
