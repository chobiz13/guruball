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
 * vB_Api_Facebook
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Facebook extends vB_Api
{
	protected $disableFalseReturnOnly = array('isFacebookEnabled', 'userIsLoggedIn', 'getLoggedInFbUserId');

	public function isFacebookEnabled()
	{
		return vB_Library::instance('facebook')->isFacebookEnabled();
	}

	public function userIsLoggedIn($ping = false)
	{
		return vB_Library::instance('facebook')->userIsLoggedIn($ping);
	}

	public function getLoggedInFbUserId()
	{
		return vB_Library::instance('facebook')->getLoggedInFbUserId();
	}

	public function getVbUseridFromFbUserid()
	{
		return vB_Library::instance('facebook')->getVbUseridFromFbUserid();
	}

	public function getFbProfileUrl()
	{
		return vB_Library::instance('facebook')->getFbProfileUrl();
	}

	public function getFbProfilePicUrl()
	{
		return vB_Library::instance('facebook')->getFbProfilePicUrl();
	}

	public function getFbUserInfo()
	{
		return vB_Library::instance('facebook')->getFbUserInfo();
	}


	/**
	 *	Publish the node to facebook
	 *	@param array $node -- standard node array
	 *	@param boolean $explicit -- is this message explicitly shared? See
	 *		https://developers.facebook.com/docs/opengraph/using-actions/v2.2#explicitsharing
	 *
	 *	@return array standard success array
	 */
	public function publishNode($node, $explicit)
	{
		$value = vB_Library::instance('facebook')->publishNode($node, $explicit);
		return array('success' => $value);
	}


	/**
	 *	Get the results from several functions in one call.
	 *
	 *	This is a cover function to make it easier to access all of the fb related information
	 *	for the current Facebook user in a single call.  This is an inefficient way of getting the
	 *	information if you aren't going to use most of it, but convenient if you are.
	 *
	 *	@return array
	 *		'profileurl' => result of getFbProfileUrl
	 *		'profilepicurl' => result of getFbProfilePicUrl
	 *		'vbuserid' => result of getVbUseridFromFbUserid
	 *		'user' = > result of getFbUserInfo
	 */
	public function getAllUserInfo()
	{
		$fblib = vB_Library::instance('facebook');
		$result = array();

		$result['profileurl'] = $fblib->getFbProfileUrl();
		$result['profilepicurl'] = $fblib->getFbProfilePicUrl();
		$result['vbuserid'] = $fblib->getVbUseridFromFbUserid();
		$result['user'] = $fblib->getFbUserInfo();
		return $result;
	}

	/**
	 *	Disconnects the current user from facebook
	 *
	 *	User must either be the current user or an administrator with permissions to
	 *	manage users.
	 *
	 *	@param int $userid -- The id of the user to disconnect
	 *	@return -- standard success array when successful, otherwise will throw an exception
	 */
	public function disconnectUser($userid)
	{
		$userid = intval($userid);

		//check permissions
		if (($userid != vB::getCurrentSession()->get('userid')) AND
			!vB::getUserContext()->hasAdminPermission('canadminpermissions'))
		{
			//this requires admin canadminpermissions or that it be for the current user.
			throw new vB_Exception_Api('no_permission');
		}
		vB_Library::instance('facebook')->disconnectUser($userid);

		//if we get this far without an exception we're good.
		return array('success' => true);
	}

	/**
	 *	Connects the currently logged in user to the currently logged in Facebook user
	 *
	 *	Note that we don't allow connection of a non logged in account because
	 *	we need to validate the FB login.  Connecting somebody else's account
	 *	to a FB just doesn't make sense as an action.
	 *
	 *	@param string $accessToken.  The facebook access token to verify the FB login.
	 *			if not given use the internal stored session.
	 *	@return -- standard success array when successful, otherwise will throw an exception
	 */
	public function connectCurrentUser($accessToken=null)
	{
		vB_Library::instance('facebook')->connectCurrentUser($accessToken);
		//if we get this far without an exception we're good.
		return array('success' => true);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84740 $
|| #######################################################################
\*=========================================================================*/
