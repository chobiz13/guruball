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

/**
 * vB_Api_Vb4_register
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_login extends vB_Api
{
	/**
	 * Login with fabecook logged user
	 *
	 * @param  [string] $signed_request [fb info]
	 * @return [array]                  [response -> errormessage and session params]
	 */
	public function facebook($signed_request)
	{
		$cleaner = vB::getCleaner();
		$signed_request = $cleaner->clean($signed_request, vB_Cleaner::TYPE_STR);

		$user_api = vB_Api::instance('user');
		$loginInfo = $user_api->loginExternal('facebook', array('signedrequest' => $signed_request));

		if (empty($loginInfo) || isset($loginInfo['errors']))
		{
			//the api doesn't allow us to be that specific about our errors here.
			//and the app gets very cranky if the login returns an unexpected error code
			return array('response' => array('errormessage' => array('badlogin_facebook')));
		}

		$result = array(
			'session' => array(
				'dbsessionhash' => $loginInfo['login']['sessionhash'],
				'userid' => $loginInfo['login']['userid'],
			),
			'response' => array(
				'errormessage' => array('redirect_login')
			),
		);

		return $result;
	}
}
/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84800 $
|| #######################################################################
\*=========================================================================*/
