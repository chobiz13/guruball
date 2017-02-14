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
 * Authentication/login related methods
 */
class vB5_Auth
{
	/**
	 * Sets cookies needed for authentication
	 *
	 * @param	array	$loginInfo - array of information returned from
	 *			the user::login api method
	 */
	public static function setLoginCookies(array $loginInfo, $loginType, $remember)
	{
		// remember me option keeps you logged in for 30 days
		$expire = (isset($_POST['rememberme']) AND $_POST['rememberme']) ? 30 : 0;

		vB5_Cookie::set('sessionhash', $loginInfo['sessionhash'], $expire, true);

		if ($loginType === 'cplogin')
		{
			vB5_Cookie::set('cpsession', $loginInfo['cpsession']);
		}

		// in frontend we set these cookies only if rememberme is on
		if ($remember)
		{
			self::setRememberMeCookies($loginInfo['password'], $loginInfo['userid']);
		}
	}

	public static function setRememberMeCookies($rememberMeToken, $userid)
	{
		vB5_Cookie::set('password', $rememberMeToken, 30);
		vB5_Cookie::set('userid', $userid, 30);
	}

	/**
	 * Redirects the user back to where they were after logging in
	 */
	public static function doLoginRedirect()
	{
		$url = '';
		if (isset($_POST['url']) && $_POST['url'])
		{
			$url = base64_decode(trim($_POST['url']));
		}

		if ($url)
		{
			$parse = parse_url($url);
			if(
				!$parse OR
				empty($parse['scheme']) OR
				($parse['scheme'] != 'http' AND $parse['scheme'] != 'https')
			)
			{
				$url = NULL;
			}
		}

		if (!$url OR strpos($url, '/auth/') !== false OR strpos($url, '/register') !== false)
		{
			$url = vB5_Template_Options::instance()->get('options.frontendurl');
		}

		if (isset($_POST['associatefb']))
		{
			$joinchar = (strpos($url, '?') !== false ? '&' : '?');
			$url = $url . $joinchar . 'dofbredirect=1';
		}

		$templater = new vB5_Template('login_redirect');
		$templater->register('url', filter_var($url, FILTER_SANITIZE_STRING));
		echo $templater->render();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
