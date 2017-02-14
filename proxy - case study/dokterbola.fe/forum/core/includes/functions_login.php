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

// ###################### Start replacesession #######################
function fetch_replaced_session_url($url)
{
	// replace the sessionhash in $url with the current one
	global $vbulletin;

	$url = addslashes($url);
	$url = fetch_removed_sessionhash($url);

	if (vB::getCurrentSession()->get('sessionurl') != '')
	{
		if (strpos($url, '?') !== false)
		{
			$url .= '&amp;' . vB::getCurrentSession()->get('sessionurl');
		}
		else
		{
			$url .= '?' . vB::getCurrentSession()->get('sessionurl');
		}
	}

	return $url;
}

// ###################### Start removesessionhash #######################
function fetch_removed_sessionhash($string)
{
	return preg_replace('/([^a-z0-9])(s|sessionhash)=[a-z0-9]{32}(&amp;|&)?/', '\\1', $string);
}

// ###################### Start do login redirect #######################
function do_login_redirect()
{
	global $vbulletin, $vbphrase;

	$vbulletin->input->fetch_basepath();

	//the clauses
	//url $vbulletin->url == 'login.php' and $vbulletin->url == $vbulletin->options['forumhome'] . '.php'
	//will never be true -- $vbulletin->url contains the full url path.
	//The second shouldn't be needed, the else clause seems to handle this just fine.
	//the first we'll change to match a partial url.
	if (
		preg_match('#login.php(?:\?|$)#', $vbulletin->url)
		OR strpos($vbulletin->url, 'do=logout') !== false
		OR (!$vbulletin->options['allowmultiregs'] AND strpos($vbulletin->url, $vbulletin->basepath . 'register.php') === 0)
	)
	{
		$forumHome = vB_Library::instance('content_channel')->getForumHomeChannel();
		$vbulletin->url = vB5_Route::buildUrl($forumHome['routeid'] . '|fullurl');
	}
	else
	{
		$vbulletin->url = fetch_replaced_session_url($vbulletin->url);
		$vbulletin->url = preg_replace('#^/+#', '/', $vbulletin->url); // bug 3654 don't ask why
	}

	$temp = strpos($vbulletin->url, '?');
	if ($temp)
	{
		$formfile = substr($vbulletin->url, 0, $temp);
	}
	else
	{
		$formfile =& $vbulletin->url;
	}

	$postvars = $vbulletin->GPC['postvars'];

	// Legacy Hook 'login_redirect' Removed //

	if (!VB_API)
	{
		// recache the global group to get the stuff from the new language
		$globalgroup = $vbulletin->db->query_first_slave("
			SELECT phrasegroup_global, languagecode, charset
			FROM " . TABLE_PREFIX . "language
			WHERE languageid = " . intval($vbulletin->userinfo['languageid'] ? $vbulletin->userinfo['languageid'] : $vbulletin->options['languageid'])
		);
		if ($globalgroup)
		{
			$vbphrase = array_merge($vbphrase, unserialize($globalgroup['phrasegroup_global']));

			if (vB_Template_Runtime::fetchStyleVar('charset') != $globalgroup['charset'])
			{
				// change the character set in a bunch of places - a total hack
				global $headinclude;

				$headinclude = str_replace(
					"content=\"text/html; charset=" . vB_Template_Runtime::fetchStyleVar('charset') . "\"",
					"content=\"text/html; charset=$globalgroup[charset]\"",
					$headinclude
				);

				vB_Template_Runtime::addStyleVar('charset', $globalgroup['charset'], 'imgdir');
				$vbulletin->userinfo['lang_charset'] = $globalgroup['charset'];

				exec_headers();
			}
			if ($vbulletin->GPC['postvars'])
			{
				$postvars = array();
				$client_string = verify_client_string($vbulletin->GPC['postvars']);
				if ($client_string)
				{
					$postvars = @json_decode($client_string, true);
				}

				if ($postvars['securitytoken'] == 'guest')
				{
					$vbulletin->userinfo['securitytoken_raw'] = sha1($vbulletin->userinfo['userid'] . sha1($vbulletin->userinfo['secret']) . sha1(vB_Request_Web::$COOKIE_SALT));
					$vbulletin->userinfo['securitytoken'] = TIMENOW . '-' . sha1(TIMENOW . $vbulletin->userinfo['securitytoken_raw']);
					$postvars['securitytoken'] = $vbulletin->userinfo['securitytoken'];
					$vbulletin->GPC['postvars'] = sign_client_string(json_encode($postvars));
				}
			}

			vB_Template_Runtime::addStyleVar('languagecode', $globalgroup['languagecode']);
		}
	}

	if ($vbulletin->GPC['logintype'] === 'cplogin' OR $vbulletin->GPC['logintype'] === 'modcplogin')
	{
		require_once(DIR . '/includes/adminfunctions.php');
		print_cp_redirect($vbulletin->url);
	}
	else
	{
		eval(print_standard_redirect('redirect_login_gfrontredirect', true, true, $vbulletin->userinfo['languageid']));
	}
}

// ###################### Start process logout #######################
function process_logout()
{
	global $vbulletin;

	// clear all cookies beginning with COOKIE_PREFIX
	$prefix_length = strlen(COOKIE_PREFIX);
	foreach ($_COOKIE AS $key => $val)
	{
		$index = strpos($key, COOKIE_PREFIX);
		if ($index == 0 AND $index !== false)
		{
			$key = substr($key, $prefix_length);
			if (trim($key) == '')
			{
				continue;
			}
			// vbsetcookie will add the cookie prefix
			vbsetcookie($key, '', 1);
		}
	}

	if ($vbulletin->userinfo['userid'] AND $vbulletin->userinfo['userid'] != -1)
	{
		// init user data manager
		$userdata = new vB_Datamanager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_SILENT);
		$userdata->set_existing($vbulletin->userinfo);
		$userdata->set('lastactivity', TIMENOW - $vbulletin->options['cookietimeout']);
		$userdata->set('lastvisit', TIMENOW);
		$userdata->save();

		// make sure any other of this user's sessions are deleted (in case they ended up with more than one)
		$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "session WHERE userid = " . $vbulletin->userinfo['userid']);
	}

	$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "session WHERE sessionhash = '" . $vbulletin->db->escape_string(vB::getCurrentSession()->get('dbsessionhash')) . "'");

	// Remove accesstoken from apiclient table so that a new one will be generated
	if (defined('VB_API') AND VB_API === true AND $vbulletin->apiclient['apiclientid'])
	{
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "apiclient SET apiaccesstoken = '', userid = 0
			WHERE apiclientid = " . intval($vbulletin->apiclient['apiclientid']));
		$vbulletin->apiclient['apiaccesstoken'] = '';
	}

	if ($vbulletin->session->created == true AND !VB_API)
	{
		// if we just created a session on this page, there's no reason not to use it
		$newsession = $vbulletin->session;
	}
	else
	{
		// API should always create a new session here to generate a new accesstoken
//		$newsession = new vB_Session($vbulletin, '', 0, '', vB::getCurrentSession()->get('styleid'));
		$newsession = vB_Session::getNewSession(vB::getDbAssertor(), vB::getDatastore(), vB::getConfig(), '', 0, '', vB::getCurrentSession()->get('styleid'));
	}
	$newsession->set('userid', 0);
	$newsession->set('loggedin', 0);
	$vbulletin->session =& $newsession;

	// Legacy Hook 'logout_process' Removed //
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
