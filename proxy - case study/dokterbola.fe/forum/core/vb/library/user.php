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
 * vB_Library_User
 *
 * @package vBApi
 * @access public
 */
class vB_Library_User extends vB_Library
{
	const PASSWORD_RESET_ATTEMPTS = 10;
	const PASSWORD_RESET_LOCK_MINUTES = 10;

	protected function __construct()
	{
		parent::__construct();
		require_once(DIR . '/includes/functions_bigthree.php');
	}

	/**
	 * Check whether a user is banned.
	 *
	 * @param integer $userid User ID.
	 * @return bool Whether the user is banned.
	 */
	public function isBanned($userid)
	{
		$usercontext = vB::getUserContext($userid);
		return !$usercontext->hasPermission('genericoptions', 'isnotbannedgroup');
	}

	/**
	 * Check whether a user is banned and returns info such as reason and liftdate if possible.
	 *
	 * @param	int		User id
	 *
	 * @retun	mixed	Array containing ban liftdate and reason or false is user is not banned.
	 */
	public function fetchBannedInfo($userid)
	{
		$userid = intval($userid);
		if (!$userid)
		{
			$userid = vB::getCurrentSession()->get('userid');
		}

		$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
		// looking up cache for the node
		$hashKey = 'vbUserBanned_'. $userid;
		$banned = $cache->read($hashKey);

		if (!empty($banned))
		{
			// a string false is received if the banning was already checked and the user is not banned
			if ($banned === 'false')
			{
				return false;
			}
			return $banned;
		}

		if ($this->isBanned($userid))
		{
			$info = array('isbanned' => 1);
			$banRecord = vB::getDbAssertor()->getRow('vBForum:userban', array('userid' => $userid));

			if ($banRecord AND empty($banRecord['errors']))
			{
				$info['liftdate'] = $banRecord['liftdate'];
				$info['reason'] = $banRecord['reason'];
				$info['admin'] = $this->fetchUserName($banRecord['adminid']);
			}
			else if (!vB::getUserContext()->hasPermission('genericoptions', 'isnotbannedgroup'))
			{
				$info['bannedGroup'] = true;
				$info['admin'] = vB_Phrase::fetchSinglePhrase('administrator');
			}
			$cache->write($hashKey, $info, 1440, 'userChg_' . $userid);
			return $info;
		}
		else
		{
			// false is intentionally passed as string so it can be identified as different from the boolean false returned by the cache if not cached
			$cache->write($hashKey, 'false', 1440, 'userChg_' . $userid);
			return false;
		}
	}

	/**
	 * Fetches the username for a userid
	 *
	 * @param integer $ User ID
	 * @return string
	 */
	public function fetchUserName($userid)
	{
		$userInfo = $this->fetchUserinfo($userid);

		if (empty($userInfo) OR empty($userInfo['userid']))
		{
			return false;
		}

		return $userInfo['username'];
	}
	/**
	 * Fetches the user names for the given user ids
	 * @param array $userIds
	 * @return array $usernames
	 */
	public function fetchUserNames($userIds)
	{
		$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
		$usernames = array();
		$remainingIds = array();
		foreach ($userIds as $userid)
		{
			$user = $cache->read('vbUserInfo_' . $userid);
			if (!empty($user))
			{
				$usernames[$userid] = $user['username'];
			}
			else
			{
				$remainingIds[] = $userid;
			}
		}
		if (!empty($remainingIds))
		{
			$usernames += vB::getDbAssertor()->getColumn('user', 'username', array('userid' => $remainingIds), false, 'userid');
		}
		return $usernames;
	}

	/**
	 * Fetches an array containing info for the specified user, or false if user is not found
	 * @param integer $userid
	 * @param integer $languageid -- If set to 0, it will use user-set languageid (if exists) or default languageid.
	 * @param boolean $nocache -- If true, the method won't use user cache but fetch information from DB.
	 * @param	boolean $lastactivity -- unused
	 * @return array The information for the requested user
	 */
	public function fetchUserWithPerms($userid, $languageid = 0, $nocache = false, $lastactivity = false)
	{
		//Try cached data.
		$fastCache = vB_Cache::instance(vB_Cache::CACHE_FAST);

		$userCacheKey = "vb_UserWPerms_$userid" . '_' . $languageid;
		$infoKey = "vb_UserInfo_$userid" . '_' . $languageid;
		$cached = vB_Cache::instance(vB_Cache::CACHE_LARGE)->read($userCacheKey);

		// This already uses FAST cache, do not encapsulate in LARGE
		$userInfo = $this->fetchUserinfo($userid, array(), $languageid);
		//This includes usergroups, groupintopic, moderator, and basic userinformation. Each should be cached in fastcache.
		if (($cached !== false) AND ($cached['groups'] !== false))
		{
			$usergroups = $cached['groups'];
			$groupintopic = $cached['git'];
			$moderators = $cached['moderators'];
		}
		else
		{
			// unsetting secondary groups depending on allowmembergroups UG option is done in
			// vB_User::fetchUserinfo()

			//Let's see if we have the raw data.
			$groupintopic = $this->getGroupInTopic($userid);
			$primary_group_id = $userInfo['usergroupid'];
			$display_group_id = $userInfo['displaygroupid'];
			$secondary_group_ids = (!empty($userInfo['membergroupids'])) ? explode(',', str_replace(' ', '', $userInfo['membergroupids'])) : array();
			$infraction_group_ids = (!empty($userInfo['infractiongroupids'])) ? explode(',', str_replace(' ', '', $userInfo['infractiongroupids'])) : array();

			$usergroups = array(
				'groupid' => $primary_group_id,
				'displaygroupid' => $display_group_id,
				'secondary' => $secondary_group_ids,
				'infraction' => $infraction_group_ids
			);

			$moderators = $this->fetchModerator($userid);

			vB_Cache::instance(vB_Cache::CACHE_LARGE)->write(
				$userCacheKey,
				array(
					'groups' => $usergroups,
					'git' => $groupintopic,
					'moderators' => $moderators
				),
				1440,
				array("userPerms_$userid", "userChg_$userid")
			);
		}

		$fastCache->write($infoKey, $userInfo, 5, "userChg_$userid");

		$this->groupInTopic[$userid] = $groupintopic;
		return $userInfo;
	}


	/**
	 * This returns a user's additional permissions from the groupintopic table
	 *
	 *	@param	int
	 *	@param	int	optional nodeid
	 *
	 *	@return	mixed	Associated array of array(nodeid, groupid);
	 ***/
	public function getGroupInTopic($userid , $nodeid = false, $forceReload = false)
	{
		if (!isset($this->groupInTopic[$userid]) OR $forceReload)
		{
			// Only call getUserContext if we already have it, as we don't need all of the queries that it does
			if (vB::isUserContextSet($userid) AND !$forceReload)
			{
				$groupInTopic = vB::getUserContext($userid)->fetchGroupInTopic();
				$perms = array();
				foreach ($groupInTopic AS $_nodeid => $groups)
				{
					foreach($groups AS $group)
					{
						$perms[] = array('nodeid' => $_nodeid, 'groupid' => $group);
					}
				}
			}
			else
			{
				$params = array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'userid' => $userid
				);
				$permQry = vB::getDbAssertor()->assertQuery('vBForum:groupintopic', $params);
				$perms = array();
				foreach ($permQry AS $permission)
				{
					$perms[] = array('nodeid' => $permission['nodeid'], 'groupid' => $permission['groupid']);
				}
			}
			$this->groupInTopic[$userid] = $perms;
		}

		if ($nodeid)
		{
			$results = array();
			foreach ($this->groupInTopic[$userid] AS $perm)
			{
				if ($perm['nodeid'] == $nodeid)
				{
					$results[] = $perm;
				}
			}
			return $results;
		}
		return $this->groupInTopic[$userid];
	}


	/**
	 * Fetches an array containing all the moderator permission informationd
	 * @param integer 	User ID
	 * @param mixed 	array of $nodeids where the user in a moveratoe
	 * @return mixed	the permission array
	 */

	public function fetchModerator($userid, $moderators = false)
	{
		$parentnodeids = array();
		$moderatorPerms = array();

		if ($moderators === false)
		{
			$moderators = vB::getDbAssertor()->assertQuery('vBForum:moderator', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'userid' => $userid));
			if (!$moderators->valid())
			{
				return array();
			}
		}

		if (empty($moderators))
		{
			return array();
		}
		foreach ($moderators AS $modPerm)
		{
			if (isset($modPerm['nodeid']))
			{
				if ($modPerm['nodeid'] >= 1)
				{
					$parentnodeids[] = $modPerm['nodeid'];
				}

				$moderatorPerms[$modPerm['nodeid']] = $modPerm;
			}
		}

		if (!empty($parentnodeids))
		{
			foreach ($parentnodeids as $parentnodeid)
			{
				if ($parentnodeid < 1)
				{
					continue;
				}

				$closurerecords = vB::getDbAssertor()->assertQuery('vBForum:getDescendantChannelNodeIds', array(
					'parentnodeid' => $parentnodeid, 'channelType' => vB_Types::instance()->getContentTypeID('vBForum_Channel')
				));
				foreach ($closurerecords as $closurerecord)
				{
					$childnodeid = $closurerecord['child'];
					if (!isset($moderatorPerms[$childnodeid]) AND isset($moderatorPerms[$parentnodeid]))
					{
						// descendant channels inherit moderator permissions from parent channels
						// so we copy the parent channel's permissions and change the nodeid in it
						$moderatorPerms[$childnodeid] = $moderatorPerms[$parentnodeid];
						$moderatorPerms[$childnodeid]['nodeid'] = $childnodeid;
					}
				}
			}
		}

		return $moderatorPerms;
	}
	/**
	* Fetches an array containing info for the specified user, or false if user is not found
	*
	* Values for Option parameter:
	* avatar - Get avatar
	* location - Process user's online location
	* profilepic - Join the customprofilpic table to get the userid just to check if we have a picture
	* admin - Join the administrator table to get various admin options
	* signpic - Join the sigpic table to get the userid just to check if we have a picture
	* usercss - Get user's custom CSS
	* isfriend - Is the logged in User a friend of this person?
	* Therefore: array('avatar', 'location') means 'Get avatar' and 'Process online location'
	*
	 * @param integer $ User ID
	 * @param array $ Fetch Option (see description)
	 * @param integer $ Language ID. If set to 0, it will use user-set languageid (if exists) or default languageid
	 * @param boolean $ If true, the method won't use user cache but fetch information from DB.
	* @return array The information for the requested user
	*/
	public function fetchUserinfo($userid = false, $option = array(), $languageid = false, $nocache = false)
	{
		if ($languageid === false)
		{
			$session = vB::getCurrentSession();
			if ($session)
			{
				$languageid = vB::getCurrentSession()->get('languageid');
			}
		}

		$result = vB_User::fetchUserinfo($userid, $option, $languageid, $nocache);

		if (empty($result) OR !isset($result['userid']))
		{
			return false;
		}

		if(!empty($result['lang_options']))
		{
			//convert bitfields to arrays for external clients.
			$bitfields = vB::getDatastore()->getValue('bf_misc_languageoptions');
			$lang_options = $result['lang_options'];
			$result['lang_options'] = array();
			foreach ($bitfields as $key => $value)
			{
				$result['lang_options'][$key] = (bool) ($lang_options & $value);
			}
		}
		$userContext = vB::getUserContext($userid);

		//use the default style instead of the user style in some cases
		//1) The user style isn't set (value 0)
		//2) Style choosing isn't allowed and the user is not an admin
		if ($session = vB::getCurrentSession())
		{
			$sessionstyleid = $session->get('styleid');
			if ($sessionstyleid)
			{
				$result['styleid'] = $sessionstyleid;
			}
		}
		// adding some extra info
		if ($userid)
		{
			$result['is_admin'] = $userContext->isAdministrator();
			$result['is_supermod'] = (vB_UserContext::USERLEVEL_SUPERMODERATOR == $userContext->getUserLevel() ? true : false);
			$result['is_moderator'] = ($userContext->getUserLevel() >= vB_UserContext::USERLEVEL_MODERATOR);
			$result['can_use_sitebuilder'] = $userContext->hasAdminPermission('canusesitebuilder');
			$result['can_admin_ads'] = $userContext->hasAdminPermission('canadminads');
			$result['is_globally_ignored'] = $userContext->isGloballyIgnored();
		}

		$vboptions = vB::getDatastore()->getValue('options');
		$canChangeStyle =  ($vboptions['allowchangestyles'] == 1 OR $userContext->hasAdminPermission('cancontrolpanel'));

		if ( ($result['styleid'] == 0) OR !$canChangeStyle)
		{
			$result['styleid'] = $vboptions['styleid'];
		}

		//get the online status
		fetch_online_status($result);
		return $result;
	}

	/**
	 * Gets the usergroup information. Returns the secondary groups even if allowmembergroups usergroup option is set to No.
	 *
	 * @param	int		userid
	 *
	 * @return array with
	 * 	* groupid integer primary group id
	 * 	* displaygroupid integer display group id
	 * 	* secondary array list of secondary user groups
	 * 	* infraction array list of infraction groups.
	 *
	 * @throws vB_Exception_Api invalid_user_specified
	 */
	public function fetchUserGroups($userid)
	{
		/*
		 * Anything that calls this should take care of discarding the secondary groups
		 * based on the allowmembergroups option as appropriate. For example refer to
		 * usercontext's reloadUserPerms().
		 * Do not change this function to check for the option. AdminCP's user.php relies on this function
		 * to get the secondary groups when vB_User::fetchUserinfo() doesn't return the membergroups
		 * when the option is set to "No."
		 */

		$session = vB::getCurrentSession();
		if ($session)
		{
			$languageid = $session->get('languageid');
			$cached = vB_Cache::instance(vB_Cache::CACHE_LARGE)->read("vb_UserWPerms_$userid" . '_' . $languageid);

			//This includes usergroups, groupintopic, moderator, and basic userinformation. Each should be cached in fastcache.
			if (($cached !== false) AND ($cached['groups'] !== false))
			{
				return $cached['groups'];
			}
		}

		//Now- we can't use fetchUserinfo here. It would put us in a loop.
		$userInfo = vB::getDbAssertor()->getRow('user',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('usergroupid', 'displaygroupid', 'membergroupids', 'infractiongroupids'),
				'userid' => $userid
			)
		);

		if(!$userInfo)
		{
			throw new vB_Exception_Api('invalid_user_specified');
		}
		$primary_group_id = $userInfo['usergroupid'];
		$display_group_id = $userInfo['displaygroupid'];
		$secondary_group_ids = (!empty($userInfo['membergroupids'])) ? explode(',', str_replace(' ', '', $userInfo['membergroupids'])) : array();
		$infraction_group_ids = (!empty($userInfo['infractiongroupids'])) ? explode(',', str_replace(' ', '', $userInfo['infractiongroupids'])) : array();

		return array(
			'groupid' => $primary_group_id,
			'displaygroupid' => $display_group_id,
			'secondary' => $secondary_group_ids,
			'infraction' => $infraction_group_ids
		);
	}

	/**
	 *	Adds groups to a user
	 *
	 *	Will not add a group if it matches the user's primary group is set to that group
	 *	Will add groups even if allowmembergroups is set to "no".  There will be cases where
	 *	we want to track secondary group changes even if we aren't doing anything with them
	 *
	 *	Does not validate that the usergroupids are valid
	 *
	 *	@param integer $userid
	 *	@param array $groups list of integer ids for usergroups to add
	 *
	 *	@return none
	 */
	public function addSecondaryUserGroups($userid, $groups)
	{
		$usergroups = $this->fetchUserGroups($userid);
		$membergroups = $usergroups['secondary'];

		//PHP 5.3 doesn't like combining empty arrays.
		$membergroupmap = array();
		if ($usergroups['secondary'])
		{
			$membergroupmap = array_combine($usergroups['secondary'], $usergroups['secondary']);
		}

		$change = false;
		foreach($groups AS $group)
		{
			if ($group != $usergroups['groupid'] AND !isset($membergroupmap[$group]))
			{
				$change = true;
				$membergroups[] = $group;
			}
		}
		sort($membergroups);
		vB::getDbAssertor()->update('user', array('membergroupids' => implode(',', $membergroups)), array('userid' => $userid));

		if($change)
		{
			$this->clearUserInfo(array($userid));
		}
	}

	/**
	 *	Remove groups from a user
	 *
	 *	Will not affect the user's primary group
	 *	Will unset (set to 0) the display groupid if its being removed
	 *	Will remove groups even if allowmembergroups is set to "no".  There will be cases where
	 *	we want to track secondary group changes even if we aren't doing anything with them
	 *
	 *	@param integer $userid
	 *	@param array $groups list of integer ids for usergroups to remove
	 *
	 *	@return none
	 */
	public function removeSecondaryUserGroups($userid, $groups)
	{
		$usergroups = $this->fetchUserGroups($userid);

		//Now- we can't use fetchUserinfo here. It would put us in a loop.
		$userInfo = vB::getDbAssertor()->getRow('user',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('usergroupid', 'displaygroupid', 'membergroupids', 'infractiongroupids'),
				'userid' => $userid
			)
		);

		//PHP 5.3 doesn't like combining empty arrays.
		$membergroups = array();
		if ($usergroups['secondary'])
		{
			$membergroups = array_combine($usergroups['secondary'], $usergroups['secondary']);
		}

		$change = false;
		foreach ($groups AS $group)
		{
			if (isset($membergroups[$group]))
			{
				$change = true;
				unset($membergroups[$group]);
			}
		}

		sort($membergroups);
		$updates['membergroupids'] = implode(',', $membergroups);

		//if the display group is not one of the user's groups, set it to 0
		$displaygroupid = $usergroups['displaygroupid'];
		if ($displaygroupid AND ($usergroups['groupid'] != $displaygroupid) AND !in_array($displaygroupid, $membergroups))
		{
			$updates['displaygroupid'] = 0;
		}

		vB::getDbAssertor()->update('user', $updates, array('userid' => $userid));

		if($change)
		{
			$this->clearUserInfo(array($userid));
		}
	}

	/*
	 * @param	array	$useractivation		Record to check. Must have 'reset_locked_since'
	 */
	public function checkPasswordResetLock($useractivation)
	{
		$attemptsLimit = self::PASSWORD_RESET_ATTEMPTS;
		$lockDurationMinutes = self::PASSWORD_RESET_LOCK_MINUTES;
		$lockDurationSeconds = $lockDurationMinutes * 60;

		// data validation. Meant for devs/unit testing really, if these values aren't present than some code changed
		// unintentionally.
		if (!isset($useractivation['reset_locked_since']) OR !is_numeric($useractivation['reset_locked_since']))
		{
			throw new vB_Exception_Api('incorrect_data');
		}

		if (empty($attemptsLimit) OR empty($lockDurationSeconds))
		{
			throw new vB_Exception_Api('incorrect_data');
		}

		$timeNow = vB::getRequest()->getTimeNow();
		$locked = ($timeNow <= ($useractivation['reset_locked_since'] + $lockDurationSeconds));
		$lostPWLink = vB5_Route::buildUrl('lostpw|fullurl');
		$exceptionArgs = array($lockDurationMinutes, $lostPWLink);
		/*
			If they try to reset their password or generate a new reset activationid before the end
			of their timeout, throw an exception
		 */
		if ($locked)
		{
			throw new vB_Exception_Api('reset_password_lockout', $exceptionArgs);
		}

		// Caller must check if this activation record is invalid before using it.
		// We don't do that here as this is used by both new activationid generation &
		// password reset validation.
	}


	public function sendPasswordEmail($userid, $email)
	{
		if (!$email)
		{
			throw new vB_Exception_Api('invalidemail', array(vB5_Route::buildUrl('contact-us|fullurl')));
		}

		$vboptions = vB::getDatastore()->getValue('options');

		require_once(DIR . '/includes/functions_user.php');
		$users = vB::getDbAssertor()->select('user', array('email' => $email), array('userid', 'username', 'email', 'languageid'));

		$count = 0;
		foreach ($users as $user)
		{
			$count++;
			if ($userid AND $userid != $user['userid'])
			{
				continue;
			}
			$user['username'] = unhtmlspecialchars($user['username']);

			// build_user_activation_id() will throw an exception downstream if an existing activation record is locked.
			$user['activationid'] = build_user_activation_id($user['userid'], 2, 1);

			$maildata = vB_Api::instanceInternal('phrase')->fetchEmailPhrases(
				'lostpw',
				array(
					$user['username'],
					$vboptions['bbtitle'],
					$vboptions['frontendurl'],
					vB5_Route::buildUrl('reset-password|fullurl', array(), array('userid' => $user['userid'], 'activationid' => $user['activationid'])),
				),
				array($vboptions['bbtitle']),
				$user['languageid']
			);
			vB_Mail::vbmail($user['email'], $maildata['subject'], $maildata['message'], true);
		}

		if($count)
		{
			return true;
		}
		else
		{
			throw new vB_Exception_Api('invalidemail', array(vB5_Route::buildUrl('contact-us|fullurl')));
		}
	}

	public function sendActivateEmail($userid)
	{
		$userinfo = vB_User::fetchUserinfo($userid);

		if (empty($userinfo))
		{
			throw new vB_Exception_Api('invaliduserid');
		}

		if ($userinfo['usergroupid'] != 3)
		{
			// Already activated
			throw new vB_Exception_Api('activate_wrongusergroup');
		}

		$vboptions = vB::getDatastore()->getValue('options');
		$coppauser = false;

		if (!empty($userinfo['birthdaysearch']))
		{
			$birthday = $userinfo['birthdaysearch'];
		}
		else
		{
			//we want YYYY-MM-DD for the coppa check but normally we store MM-DD-YYYY
			$birthday = $userinfo['birthday'];
			if (strlen($birthday) >=6 AND $birthday[2] == '-' AND $birthday[5] == '-')
			{
				$birthday = substr($birthday, 6) . '-' . substr($birthday, 0, 2) . '-' . substr($birthday, 3, 2);
			}
		}

		if ($vboptions['usecoppa'] == 1 AND $this->needsCoppa($birthday))
		{
			$coppauser = true;
		}


		$username = trim(unhtmlspecialchars($userinfo['username']));
		require_once(DIR . '/includes/functions_user.php');

		// Try to get existing activateid from useractivation table
		$useractivation = vB::getDbAssertor()->getRow('useractivation', array(
			'userid' => $userinfo['userid'],
		));
		if ($useractivation)
		{
			$activateid = fetch_random_string(40);
			vB::getDbAssertor()->update('useractivation',
				array(
					'dateline' => vB::getRequest()->getTimeNow(),
					'activationid' => $activateid,
				),
				array(
					'userid' => $userinfo['userid'],
					'type' => 0,
				)
			);
		}
		else
		{
			$activateid = build_user_activation_id($userinfo['userid'], (($vboptions['moderatenewmembers'] OR $coppauser) ? 4 : 2), 0);
		}
		$maildata = vB_Api::instanceInternal('phrase')
			->fetchEmailPhrases('activateaccount', array($username, $vboptions['bbtitle'], $vboptions['frontendurl'], $userinfo['userid'], $activateid, $vboptions['webmasteremail']), array($username), $userinfo['languageid']);
		vB_Mail::vbmail($userinfo['email'], $maildata['subject'], $maildata['message'], true);

	}


	/**
	 * This checks whether a user needs COPPA approval based on birthdate. Responds to Ajax call
	 *
	 * @param mixed $dateInfo array of month/day/year.
	 * @return int 0 - no COPPA needed, 1- Approve but require adult validation, 2- Deny
	 */
	public function needsCoppa($dateInfo)
	{
		$options = vB::getDatastore()->get_value('options');
		$cleaner = vB::get_cleaner();

		if ((bool) $options['usecoppa']) {
			// date can come as a unix timestamp, or an array, or 'YYYY-MM-DD'
			if (is_array($dateInfo))
			{
				$dateInfo = $cleaner->cleanArray($dateInfo, array(
					'day' => vB_Cleaner::TYPE_UINT,
					'month' => vB_Cleaner::TYPE_UINT,
					'year' => vB_Cleaner::TYPE_UINT)
				);
				$birthdate = mktime(0, 0, 0, $dateInfo['month'], $dateInfo['day'], $dateInfo['year']);
			}
			else if (strlen($dateInfo) == 10)
			{
				$birthdate = strtotime($dateInfo);
			}
			else if (intval($dateInfo))
			{
				$birthdate = intval($dateInfo);
			}
			else
			{
				return true;
			}

			if (empty($dateInfo)) {
				return $options['usecoppa'];
			}

			$request = vB::getRequest();

			if (empty($request)) {
				// mainly happens in test- should never happen in production.
				$cutoff = strtotime(date("Y-m-d", time()) . '- 13 years');
			}else {
				$cutoff = strtotime(date("Y-m-d", vB::getRequest()->getTimeNow()) . '- 13 years');
			}

			if ($birthdate > $cutoff) {
				return $options['usecoppa'];
			}
		}
		return 0;
	}

	/** This preloads information for a list of userids, so it will be available for userContext and other data loading

	@param 	mixed	array of integers

	 */
	public function preloadUserInfo($userids)
	{
		if (empty($userids) OR !is_array($userids))
		{
			//no harm here. Just nothing to do.
			return;
		}
		$userids = array_unique($userids);

		//first we can remove anything that already has been loaded.
		$fastCache = vB_Cache::instance(vB_Cache::CACHE_FAST);
		$languageid = vB::getCurrentSession()->get('languageid');
		$cacheKeys = array();
		foreach ($userids AS $key => $userid)
		{
			//If we already have userinfo in cache we'll have the others
			$infoKey = "vb_UserInfo_$userid" . '_' . $languageid;

			if ($fastCache->read($infoKey))
			{
				unset($userids[$key]);
				continue;
			}
			//See if we have a cached version we can use.
			$cacheKeys[$userid] = "vb_UserWPerms_$userid" . '_' . $languageid;
		}

		//Now let's see what we can get from large cache
		if (!empty($cacheKeys))
		{
			$cached = vB_Cache::instance(vB_Cache::CACHE_LARGE)->read($cacheKeys);
			$needLast = array();
			foreach($cacheKeys AS $userid => $cacheKey)
			{
				if (!empty($cached[$cacheKey]))
				{
					$needLast[] = $userid;
				}
			}

			if (!empty($needLast))
			{
				$lastData = array();
				$lastActivityQuery = vB::getDbAssertor()->assertQuery('user', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					vB_dB_Query::COLUMNS_KEY => array('userid', 'lastactivity'),
					vB_dB_Query::CONDITIONS_KEY => array('userid' => $needLast)
				));

				foreach($lastActivityQuery AS $lastRecord)
				{
					$lastData[$lastRecord['userid']] = $lastRecord['lastactivity'];
				}

				foreach($cacheKeys AS $userid => $cacheKey)
				{
					if (!empty($cached[$cacheKey]))
					{
						/* VBV-10856: fetchUserWithPerms() expects true/false as its third parameter.
						   $lastData[$userid] was being passed to it below, which triggered a PHP notice
						   (Undefined offset) if it wasnt set. I have altered it to send true/false instead. */
						$this->fetchUserWithPerms($userid, $languageid, isset($lastData[$userid]));
						unset($cacheKeys[$userid]);
					}
				}
			}

		}

		//Now let's see what's left
		if (!empty($cacheKeys))
		{
			$assertor = vB::getDbAssertor();
			//First get userinfo. We cannot use a table query since we also need signature
			$userQry = $assertor->assertQuery('fetchUserinfo', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
				'userid' => array_keys($cacheKeys)));

			if (!$userQry->valid())
			{
				return;
			}
			foreach($userQry AS $userInfo)
			{
				$userid = $userInfo['userid'];
				fetch_online_status($userInfo);
				$primary_group_id = $userInfo['usergroupid'];
				$secondary_group_ids = (!empty($userInfo['membergroupids'])) ? explode(',', str_replace(' ', '', $userInfo['membergroupids'])) : array();
				$infraction_group_ids = (!empty($userInfo['infractiongroupids'])) ? explode(',', str_replace(' ', '', $userInfo['infractiongroupids'])) : array();
				$usergroups = array('groupid' => $primary_group_id, 'secondary' => $secondary_group_ids, 'infraction' => $infraction_group_ids);
				$fastCache->write("vb_UserInfo_$userid" . '_' . $languageid, $userInfo, 5, "userChg_$userid");
			}
		}
	}

	/** This method clears remembered channel permission
	*
	*	@param	int		the userid to be cleared
	*
	***/
	public function clearChannelPerms($userid)
	{
		unset($this->groupInTopic[$userid]);
	}

	public function updateEmailFloodTime()
	{
		$usercontext = vB::getCurrentSession()->fetch_userinfo();
		if ($usercontext['userid'])
		{
			vB::getDbAssertor()->update('user', array("emailstamp" => vB::getRequest()->getTimeNow()), array("userid" => $usercontext['userid']));
			vB_Cache::instance(vB_CACHE::CACHE_LARGE)->event(array('userChg_' . $usercontext['userid']));
		}
		else
		{
			// Guest. Update the field for its session
			vB::getCurrentSession()->set('emailstamp', vB::getRequest()->getTimeNow());
			vB::getCurrentSession()->save();
		}
	}

	public function uploadAvatar($filename, $crop = array(), $userid = false)
	{
		$imageHandler = vB_Image::instance();
		$fileInfo = $imageHandler->fetchImageInfo($filename);
		if (!$fileInfo)
		{
			throw new vB_Exception_Api('upload_invalid_image');
		}
		if ($userid === false)
		{
			$userid = vB::getCurrentSession()->get('userid');
		}
		$usercontext = vB::getUserContext($userid);
		$pathinfo = empty($crop['org_file_info']) ? pathinfo($filename) : $crop['org_file_info'];
		$dimensions['src_width'] = $fileInfo[0];
		$dimensions['src_height'] = $fileInfo[1];
		if (empty($crop['width']) AND empty($crop['height']))
		{
			$crop['width'] = $dimensions['src_width'];
			$crop['height'] = $dimensions['src_height'];
		}
		$crop['width'] = min($crop['width'], $dimensions['src_width']);
		$crop['height'] = min($crop['height'], $dimensions['src_height']);
		// the crop area should be square
		$crop['width'] = $crop['height'] = min($crop['width'], $crop['height']);

		$maxwidth = $usercontext->getLimit('avatarmaxwidth');
		$maxheight = $usercontext->getLimit('avatarmaxheight');
		//see if we need to resize the cropped image (if the crop happened on a resized image)
		$resize_ratio = 1;
		if (!empty($crop['resized_width']) AND $crop['resized_width'] < $dimensions['src_width'])
		{
			$resize_ratio = $dimensions['src_height'] / $crop['resized_height'];
		}
		$dimensions['x1'] = round(empty($crop['x1']) ? 0 : ($crop['x1'] * $resize_ratio));
		$dimensions['y1'] = round(empty($crop['y1']) ? 0 : ($crop['y1'] * $resize_ratio));
		$dimensions['width'] = round((empty($crop['width']) ? $maxwidth : $crop['width']) * $resize_ratio);
		$dimensions['height'] = round((empty($crop['height']) ? $maxheight : $crop['height']) * $resize_ratio);

		$isCropped = ($dimensions['src_width'] > $dimensions['width'] OR $dimensions['src_height'] > $dimensions['height']);

		$ext = strtolower($fileInfo[2]);

		$dimensions['extension'] = empty($ext) ? $pathinfo['extension'] : $ext;
		$dimensions['filename'] = $filename;
		$dimensions['filedata'] = file_get_contents($filename);
		// Check max height and max weight from the usergroup's permissions
		$forceResize = false;

		// force a resize if the uploaded file has the right dimensions but the file size exceeds the limits
		if ($resize_ratio == 1 AND !$isCropped AND strlen($dimensions['filedata']) > $usercontext->getLimit('avatarmaxsize'))
		{
			$new_dimensions = $imageHandler->bestResize($dimensions['src_width'], $dimensions['src_height']);
			$crop['width'] = $new_dimensions['width'];
			$crop['height'] = $new_dimensions['height'];
			$forceResize = true;
		}

		$extension_map = $imageHandler->getExtensionMap();

		if ($forceResize OR $maxwidth < $fileInfo[0] OR $maxheight < $fileInfo[1])
		{
			$fileArray_cropped = $imageHandler->cropImg(
				$dimensions,
				min(empty($crop['width']) ? $maxwidth : $crop['width'], $maxwidth),
				min(empty($crop['height']) ? $maxheight : $crop['height'], $maxheight),
				$forceResize
			);

			//want to get the thumbnail based on the cropped image
			$fh = fopen($filename, 'w');
			fwrite($fh, $fileArray_cropped['filedata']);
			fclose($fh);

			$fileArray_thumb = $imageHandler->fetchThumbnail($pathinfo['basename'], $filename);
			$filearray = array(
					'size' => $fileArray_cropped['filesize'],
					'filename' => $filename,
					'name' => $pathinfo['filename'],
					'location' => $pathinfo['dirname'],
					'type' => 'image/' . $extension_map[strtolower($dimensions['extension'])],
					'filesize' => $fileArray_cropped['filesize'],
					'height' => $fileArray_cropped['height'],
					'width' => $fileArray_cropped['width'],
					'filedata_thumb' => $fileArray_thumb['filedata'],
					'filesize_thumb' => $fileArray_thumb['filesize'],
					'height_thumb' => $fileArray_thumb['height'],
					'width_thumb' => $fileArray_thumb['width'],
					'extension' => $dimensions['extension'],
					'filedata' => $fileArray_cropped['filedata']
			);
		}
		else
		{
			$fileArray_thumb = $imageHandler->fetchThumbnail($pathinfo['basename'], $filename);
			$filearray = array(
					'size' => strlen($dimensions['filedata']),
					'filename' => $filename,
					'name' => $pathinfo['filename'],
					'location' => $pathinfo['dirname'],
					'type' => 'image/' . $extension_map[strtolower($dimensions['extension'])],
					'filesize' => strlen($dimensions['filedata']),
					'height' => $fileInfo[1],
					'width' => $fileInfo[0],
					'filedata_thumb' => $fileArray_thumb['filedata'],
					'filesize_thumb' => $fileArray_thumb['filesize'],
					'height_thumb' => $fileArray_thumb['source_height'],
					'width_thumb' => $fileArray_thumb['source_width'],
					'extension' => $dimensions['extension'],
					'filedata' => $dimensions['filedata']
			);
		}
		$api = vB_Api::instanceInternal('user');
		$result = $api->updateAvatar($userid, false, $filearray,true);
		if (empty($result['errors']))
		{
			return $api->fetchAvatar($userid);
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Transfers all ownerships (blogs and groups) from given user to another one.
	 *
	 * 	@param 		int 	Userid to transfer ownerships from.
	 * 	@param 		int 	Userid to transfer ownerships to.
	 *
	 * 	@return 	bool 	Indicates if transfer where properly done, throws exception if needed.
	 */
	public function transferOwnership($fromuser, $touser)
	{
		$fromuser = intval($fromuser);
		$touser = intval($touser);
		if (!$touser OR !$fromuser)
		{
			throw new vB_Exception_Api('invalid_data');
		}

		$usergroupInfo = vB_Api::instanceInternal('usergroup')->fetchUsergroupBySystemID(vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID);
		$update = vB::getDbAssertor()->update('vBForum:groupintopic', array('userid' => $touser), array('userid' => $fromuser, 'groupid' => $usergroupInfo['usergroupid']));

		// update cache if needed
		if (is_numeric($update) AND ($update > 0))
		{
			$events = array();
			foreach (array($fromuser, $touser) AS $uid)
			{
				vB::getUserContext($uid)->clearChannelPermissions();
				$events[] = 'userPerms_' . $uid;
			}

			vB_Cache::allCacheEvent($events);
		}

		return true;
	}

	/**
	 * Generates a totally random string
	 *
	 * Intended to populate the user secret field.  Exposed as a function
	 * because the installer doesn't use the normal user save code and will
	 * need access.
	 *
	 * @return	string	Generated String
	 */
	public function generateUserSecret()
	{
		$length = 30;
		$secret = '';
		for ($i = 0; $i < $length; $i++) {
			$secret .= chr(vbrand(33, 126));
		}
		return $secret;
	}


	/**
	 *	Update the post count for a list of users.
	 *
	 *	The simple case is one user and one post, but if we
	 *	do a large operation -- for example undeleting a topic -- we can cause a number of posts to be
	 *	"counted" for a number of users (and have more than one "new" post per user).  We batch
	 *	the call for all affected users because it allows us to avoid
	 *
	 *	We also update the lastpost information for the user (conditionally).  These are linked
	 *	primary to save queries to the database because they tend to change together rather
	 *	than because the are conceptually the same thing.
	 *
	 *	@param array of $userid => $info array with the following fields
	 *	* posts -- the number of new posts for the user
	 *	* lastposttime -- the publish time of the most recent "activated" post.  This will
	 *		become the user's last post IF it's more recent than the one we have on record
	 *	* lastpostid -- the id of the last post
	 *
	 *	@return none
	 */
	public function incrementPostCountForUsers($userInfo)
	{
		$assertor = vB::getDbAssertor();
		$events = array();
		foreach($userInfo AS $userid => $info)
		{
			$assertor->assertQuery('vBForum:incrementUserPostCount', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'userid' => $userid,
				'lastposttime' => $info['lastposttime'],
				'lastnodeid' => $info['lastpostid'],
				'count' => $info['posts']
			));

			$events[] = 'usrChg_' . $userid;
		}

		//efficiency hack.  If we have only one user and its the current user we
		//can skip loading the user's information because we should already (more
		//or less) have it.
		//This probably the most common case we will encounter.
		if (count($userInfo) == 1)
		{
			$currentuser = vB::getCurrentSession()->fetch_userinfo();
			reset($userInfo);
			list($userid, $user) = each($userInfo);

			if ($userid == $currentuser['userid'])
			{
				//the post count won't reflect the changes in this function but our calculations
				//based on post count should.  Fix the user array.
				$currentuser['posts'] += $user['posts'];
				$this->updateCurrentUserRank($currentuser);
			}
			else
			{
				$this->updateUserRanks(array_keys($userInfo));
			}
		}
		else
		{
				$this->updateUserRanks(array_keys($userInfo));
		}

		vB_Cache::allCacheEvent($events);
	}

	/**
	 *	Update the post count for a list of users.
	 *
	 *	The simple case is one user and one post, but if we
	 *	do a large operation -- for example deleting a topic -- we can cause a number of posts to be
	 *	"uncounted" for a number of users (and have more than one "new" post per user).  We batch
	 *	the call for all affected users because it allows us to avoid
	 *
	 *	@param array of $userid => $info array with the following fields.  This is structured this
	 *		way to be consistant with the data for incrementPostCountForUsers
	 *	* posts -- the number of removed posts for the user
	 *
	 *	@return none
	 */
	public function decrementPostCountForUsers($userInfo)
	{
		$assertor = vB::getDbAssertor();
		$events = array();
		foreach($userInfo AS $userid => $info)
		{
			$assertor->assertQuery('vBForum:decrementUserPostCount', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'userid' => $userid,
				'count' => $info['posts']
			));

			$events[] = 'usrChg_' . $userid;
		}

		//efficiency hack.  If we have only one user and its the current user we
		//can skip loading the user's information because we should already (more
		//or less) have it.
		//This probably the most common case we will encounter.
		//This is almost but not quite identical to the increment code (mind the change in sign)
		//But trying to isolate it to its own function is more trouble than its worth
		if (count($userInfo) == 1)
		{
			$currentuser = vB::getCurrentSession()->fetch_userinfo();
			reset($userInfo);
			list($userid, $user) = each($userInfo);

			if ($userid == $currentuser['userid'])
			{
				//the post count won't reflect the changes in this function but our calculations
				//based on post count should.  Fix the user array.
				$currentuser['posts'] -= $user['posts'];
				$this->updateCurrentUserRank($currentuser);
			}
			else
			{
				$this->updateUserRanks(array_keys($userInfo));
			}
		}
		else
		{
				$this->updateUserRanks(array_keys($userInfo));
		}

		vB_Cache::allCacheEvent($events);
	}

	/**
	 *	Update the user ranks for the users identified.
	 *
	 * 	Note that the user cache should be cleared after calling this function
	 * 	but it does not do so (to avoid clearing the cache repeatedly).  If
	 * 	this is needed to be made public a new public version should be created
	 * 	that calls this function and then clears the cash
	 *
	 *	Note also that this function will only update the ranks if there are ranks
	 *	defined.  This is intended to avoid querying user data that we don't need
	 *	if we can't match any ranks -- not everybody uses this feature.  However it
	 *	also means that if we create ranks and delete them all then then this
	 *	function will not clear the rank data.  There is a maintanince tool to
	 *	update this data that should be run whenever the rank structure is changed
	 *	that will fix this problem.
	 *
	 * 	@param $users an array of user ids
	 * 	@return none
	 */
	protected function updateUserRanks($users)
	{
		//if this is empty, we'll likely get an DB error.  Shouldn't happen but its good to check.
		if (empty($users))
		{
			return;
		}

		$ranklib = vB_Library::instance('userrank');
		if (!$ranklib->haveRanks())
		{
			return;
		}

		$db = vB::getDbAssertor();
		$userinfo = $db->select('user', array('userid' => $users), false, array('userid', 'posts', 'usergroupid', 'displaygroupid', 'membergroupids'));

		foreach($userinfo AS $info)
		{
			$rankHtml = $ranklib->getRankHtml($info);
			$db->update('vBForum:usertextfield', array('rank' => $rankHtml), array('userid' => $info['userid']));
		}
	}

	/**
	 *	Updates the rank for the current user.
	 *
	 *	This is going to be the common case.  We could use updateUserRanks for this, but we
	 *	already have all of the information we need for the user here so we can save
	 *	a query.
	 *
	 *	@param $currentUser The current user array.  We have to pass this in because the cached
	 *		value is likely to be outdated when get here and the caller may need to alter it before
	 *		we use it.  This prevents an unnecesary cache clear/reload.
	 */
	protected function updateCurrentUserRank($currentUser)
	{
		$ranklib = vB_Library::instance('userrank');
		if (!$ranklib->haveRanks())
		{
			return;
		}

		$db = vB::getDbAssertor();
		$rankHtml = $ranklib->getRankHtml($currentUser);
		$db->update('vBForum:usertextfield', array('rank' => $rankHtml), array('userid' => $currentUser['userid']));
	}

	/**
	 * Clear user cached info for given userids.
	 * There's currently cached info in several places  (vB_Session, vB_User and vB_Cache implementations)
	 * this makes sure they all properly cleared.
	 *
	 *	@param 	array 	List of users to clear cache.
	 *
	 *	@param 	bool 	Cache was cleared or not.
	 **/
	public function clearUserInfo($userids)
	{
		if (empty($userids) OR !is_array($userids))
		{
			return false;
		}

		$events = array();
		$userids = array_unique($userids);

		$session = vB::getCurrentSession();
		$currentuser = $session->get('userid');
		$updatecurrent = false;
		foreach ($userids AS $userid)
		{
			// update current user?
			if ($currentuser == $userid)
			{
				$updatecurrent = true;
			}

			vB_User::clearUsersCache($userid);
			$events[] = 'userChg_' . $userid;
		}

		vB_Cache::allCacheEvent($events);

		if ($updatecurrent)
		{
			$session->clearUserInfo();
		}

		return true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88904 $
|| #######################################################################
\*=========================================================================*/
