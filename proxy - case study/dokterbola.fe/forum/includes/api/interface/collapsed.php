<?php if (!defined('VB_ENTRY')) die('Access denied.');
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

class Api_Interface_Collapsed extends Api_InterfaceAbstract
{
	protected $initialized = false;

	public function init()
	{
		if ($this->initialized)
		{
			return true;
		}

		//initialize core
		$config = vB5_Config::instance();

		//if this is AJAX, let's avoid showing warnings (notices etc)
		//nothing good will come of it.
		if (
			!$config->report_all_ajax_errors AND
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
			$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
		)
		{
			vB::silentWarnings();
		}

		$request = new vB_Request_WebApi();
		vB::setRequest($request);

		// When we reach here, there's no user information loaded. What we can do is trying to load language from cookies.
		// Shouldn't use vB5_User::getLanguageId() as it will try to load userinfo from session
		$languageid = vB5_Cookie::get('languageid', vB5_Cookie::TYPE_UINT);
		if ($languageid)
		{
			$request->setLanguageid($languageid);
		}

		$sessionhash = vB5_Cookie::get('sessionhash', vB5_Cookie::TYPE_STRING);
		$restoreSessionInfo['userid'] = vB5_Cookie::get('userid', vB5_Cookie::TYPE_STRING);
		$restoreSessionInfo['remembermetoken'] = vB5_Cookie::get('password', vB5_Cookie::TYPE_STRING);
		$remembermetokenOrig = $restoreSessionInfo['remembermetoken'];

		$retry = false;
		if ($restoreSessionInfo['remembermetoken'] == 'facebook-retry')
		{
			$restoreSessionInfo['remembermetoken'] = 'facebook';
			$retry = true;
		}

		//We normally don't allow the use of the backend classes in the front end, but the
		//rules are relaxed inside the api class and especially in the bootstrap dance of getting
		//things set up.  Right now getting at the options in the front end is nasty, but I don't
		//want the backend dealing with cookies if I can help it (among other things it makes
		//it nasty to handle callers of the backend that don't have cookies).  But we need
		//so information to determine what the cookie name is.  This is the least bad way
		//of handling things.
		$options = vB::getDatastore()->getValue('options');
		if($options['facebookactive'] AND $options['facebookappid'])
		{
			//this is not a vB cookie so it doesn't use our prefix -- which the cookie class adds automatically
			$cookie_name = 'fbsr_' .  $options['facebookappid'];
			$restoreSessionInfo['fb_signed_request'] = isset($_COOKIE[$cookie_name]) ? strval($_COOKIE[$cookie_name]) : '';
		}
		$session = $request->createSessionNew($sessionhash, $restoreSessionInfo);
		if ($session['sessionhash'] !== $sessionhash)
		{
			vB5_Cookie::set('sessionhash', $session['sessionhash'], 0, true);
		}

		//redirect to handle a stale FB cookie when doing a FB "remember me".
		//only do it once to prevent redirect loops -- don't try this with
		//posts since we'd lose the post data in that case
		//
		//Some notes on the JS code (don't want them in the JS inself to avoid
		//increasing what gets sent to the browser).
		//1) This code is deliberately designed to avoid using subsystems that
		//	would increase the processing time for something that doesn't need it
		//	(we even avoid initializing JQUERY here).  This is the reason it is
		//	inline and not in a template.
		//2) The code inits the FB system which will create update the cookie
		//	if it is able to validate the user.  The cookie is what we are after.
		//	We use getLoginStatus instead of setting status to true because
		//	the latter introduces a race condition were we can do the redirect
		//	before the we've fully initialized and updated the cookie.  The
		//	explicit call to getLoginStatus allows us to redirect when the
		//	status is obtained.
		//3) If we fail to update the cookie we catch that when we try to
		//	create the vb session (which is why we only allow one retry)
		//4) The JS here should *never* prompt the user, assuming the FB
		//	docs are correct.
		//5) If the FB version is changed it needs to changed in the
		//	FB library class and the facebook.js file
		if(
			strtolower($_SERVER['REQUEST_METHOD']) == 'get' AND
			vB::getCurrentSession()->get('userid') == 0 AND
			$options['facebookactive'] AND
			$options['facebookappid'] AND
			$restoreSessionInfo['remembermetoken'] == 'facebook'
		)
		{
			if (!$retry)
			{
				//if this isn't a retry, then do a redirect
				vB5_Auth::setRememberMeCookies('facebook-retry', $restoreSessionInfo['userid']);
				$fbredirect = "
					<!DOCTYPE html>
					<html>
					<head>
						<script type='text/javascript' src='//connect.facebook.net/en_US/sdk.js'></script>
						<script type='text/javascript'>
							FB.init({
								appId   : '$options[facebookappid]',
								version : 'v2.2',
								status  : false,
								cookie  : true,
								xfbml   : false
							});

							FB.getLoginStatus(function(response)
							{
								window.top.location.reload(true);
							});
						</script>
					</head>
					<body></body>
					</html>
				";
				echo $fbredirect;
				exit;
			}
			else
			{
				//we tried and failed to log in via FB.  That probably means that the user
				//is logged out of facebook.  Let's kill the autolog in so that we stop
				//trying to connect via FB
				vB5_Auth::setRememberMeCookies('', '');
			}
		}

		//if we have an existing token and if we got a token back from the session that is different then we
		//need to update the token in the browser.  We shouldn't get a token back if we didn't pass one in but
		//we shouldn't depend on that behavior.
		if ($session['remembermetoken'] AND  $session['remembermetoken'] != $remembermetokenOrig)
		{
			vB5_Auth::setRememberMeCookies($session['remembermetoken'], $restoreSessionInfo['userid']);
		}

		// Try to set cpsession hash to session object if exists
		vB::getCurrentSession()->setCpsessionHash(vB5_Cookie::get('cpsession', vB5_Cookie::TYPE_STRING));

		// Update lastvisit/lastactivity
		$info = vB::getCurrentSession()->doLastVisitUpdate(vB5_Cookie::get('lastvisit', vB5_Cookie::TYPE_UINT), vB5_Cookie::get('lastactivity', vB5_Cookie::TYPE_UINT));
		if (!empty($info))
		{
			// for guests we need to set some cookies
			if (isset($info['lastvisit']))
			{
				vB5_Cookie::set('lastvisit', $info['lastvisit']);
			}

			if (isset($info['lastactivity']))
			{
				vB5_Cookie::set('lastactivity', $info['lastactivity']);
			}
		}

		$this->initialized = true;
	}

	public function callApi($controller, $method, array $arguments = array(), $useNamedParams = false, $byTemplate = false)
	{
		try
		{
			$c = vB_Api::instance($controller);
		}
		catch (vB_Exception_Api $e)
		{
			throw new vB5_Exception_Api($controller, $method, $arguments, array('Failed to create API controller.'));
		}

		if ($useNamedParams)
		{
			$result = $c->callNamed($method, $arguments);
		}
		else
		{
			$result = call_user_func_array(array(&$c, $method), $arguments);
		}

		// The core error handler has been rewritten and can be used here (by default)
		// The api call sets error/exception handlers appropriate to core. We need to reset.
		// But if the API is called by template ({vb:data}), we should use the core exception handler.
		// Otherwise we will have endless loop. See VBV-1682.
		if (!$byTemplate)
		{
			set_exception_handler(array('vB5_ApplicationAbstract', 'handleException'));
		}
		return $result;

	}


	public static function callApiStatic($controller, $method, array $arguments = array())
	{
		if (is_callable('vB_Api_'  . $controller, $method))
		{
			return call_user_func_array(array('vB_Api_'  . $controller, $method), $arguments);
		}
		throw new vB5_Exception_Api($controller, $method, $arguments, 'invalid_request');
	}


	public function relay($file)
	{
		$filePath = vB5_Config::instance()->core_path . '/' . $file;

		if ($file AND file_exists($filePath))
		{
			require_once($filePath);
		}
		else
		{
			// todo: redirect to 404 page instead
			throw new vB5_Exception_404("invalid_page_url");
		}
	}

	/*
	 *	Play nice and handle backend communication through the api class even though noncollapsed
	 *	mode is completely dead.  These are systems that don't really belong as part of the API, but
	 *	we really don't want to implement seperately for frontend/backend use.  By indirecting through
	 *	this class we maintain our goal of keeping the front end reasonable separate (hopefully ensuring
	 *	that backend functionality stands on its own for integration/extension purposes).
	 */
	public function cacheInstance($type)
	{
		return vB_Cache::instance($type);
	}

	public function invokeHook($hook_name, $params)
	{
		vB::getHooks()->invoke($hook_name, $params);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87152 $
|| #######################################################################
\*=========================================================================*/
