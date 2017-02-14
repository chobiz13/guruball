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
* Fetches the list of coventry user IDs.
*
* @param	string	Type of data to return ('array' returns array of users, otherwise comma-delimited string)
* @param	boolean	True if you want to include the browsing user
*
* @return	string|array	List of coventry users in the specified format
*/
function fetch_coventry($returntype = 'array', $withself = false)
{
	global $vbulletin;
	static $Coventry;
	static $Coventry_with;

	if (!isset($Coventry))
	{
		if (trim($vbulletin->options['globalignore']) != '')
		{
			$Coventry = preg_split('#\s+#s', $vbulletin->options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
			$Coventry_with = $Coventry;
			$bbuserkey = array_search($vbulletin->userinfo['userid'], $Coventry);
			if ($bbuserkey !== FALSE AND $bbuserkey !== NULL)
			{
				unset($Coventry["$bbuserkey"]);
			}
		}
		else
		{
			$Coventry = $Coventry_with = array();
		}
	}

	if ($withself)
	{
		if ($returntype === 'array')
		{
			// return array
			return $Coventry_with;
		}
		else
		{
			// return comma-separated string
			return implode(',', $Coventry_with);
		}
	}
	else
	{
		if ($returntype === 'array')
		{
			// return array
			return $Coventry;
		}
		else
		{
			// return comma-separated string
			return implode(',', $Coventry);
		}
	}
}

/**
* Fetches the online states for the user, taking into account the browsing
* user's viewing permissions. Also modifies the user to include [buddymark]
* and [invisiblemark]
*
* @param	array	Array of userinfo to fetch online status for
* @param	boolean	True if you want to set $user[onlinestatus] with template results
*
* @return	integer	0 = offline, 1 = online, 2 = online but invisible (if permissions allow)
*/
function fetch_online_status(&$user)
{
	static $buddylist, $datecut;
	$session = vB::getCurrentSession();

	if (empty($session))
	{
		$currentUserId = 0;
	}
	else
	{
		$currentUserId = vB::getCurrentSession()->get('userid');
	}

	// get variables used by this function
	if (!isset($buddylist) AND !empty($currentUserId))
	{
		$buddylist = array();

		//If we are asking for the current user's status we can skip the fetch
		if ($currentUserId == $user['userid'])
		{
			$currentUser = &$user;
		}
		else
		{
			$currentUser = vB_Api::instanceInternal('user')->fetchCurrentUserInfo();
		}

		if (isset($currentUser['buddylist']) AND $currentUser['buddylist'] = trim($currentUser['buddylist']))
		{
			$buddylist = preg_split('/\s+/', $currentUser['buddylist'], -1, PREG_SPLIT_NO_EMPTY);
		}
	}

	if (!isset($datecut))
	{
		$datecut = vB::getRequest()->getTimeNow() - vB::getDatastore()->getOption('cookietimeout');
	}

	// is the user on bbuser's buddylist?
	if (isset($buddylist) AND is_array($buddylist) AND in_array($user['userid'], $buddylist))
	{
		$user['buddymark'] = '+';
	}
	else
	{
		$user['buddymark'] = '';
	}

	// set the invisible mark to nothing by default
	$user['invisiblemark'] = '';

	$onlinestatus = 0;
	$user['online'] = 'offline';

	// now decide if we can see the user or not
	if ($user['lastactivity'] > $datecut AND $user['lastvisit'] != $user['lastactivity'])
	{
		$bf_misc_useroptions = vB::getDatastore()->getValue('bf_misc_useroptions');
		if ($user['options'] & $bf_misc_useroptions['invisible'])
		{
			if (!isset($userContext))
			{
				$userContext = vB::getUserContext();
			}

			if (
					$currentUserId == $user['userid'] OR
					($userContext AND $userContext->hasPermission('genericpermissions','canseehidden'))
				)
			{
				// user is online and invisible BUT bbuser can see them
				$user['invisiblemark'] = '*';
				$user['online'] = 'invisible';
				$onlinestatus = 2;
			}
		}
		else
		{
			// user is online and visible
			$onlinestatus = 1;
			$user['online'] = 'online';
		}
	}

	return $onlinestatus;
}

/**
* Constructs a forum rules template for the specified forum, with selected permissions.
* Does not return a value, instead putting the results in the global $forumrules.
*
* @param	array	Array of forum info
* @param	integer	Bitfield of permissions for the specified forum
*/
function construct_forum_rules($foruminfo, $permissions)
{
	// array of foruminfo and permissions for this forum
	global $forumrules, $vbphrase, $vbcollapse, $show, $vbulletin;

	$bbcodeon = iif($foruminfo['allowbbcode'], $vbphrase['on'], $vbphrase['off']);
	$imgcodeon = iif($foruminfo['allowimages'], $vbphrase['on'], $vbphrase['off']);
	$htmlcodeon = iif($foruminfo['allowhtml'], $vbphrase['on'], $vbphrase['off']);
	$smilieson = iif($foruminfo['allowsmilies'], $vbphrase['on'], $vbphrase['off']);

	$can['postnew'] = (($permissions & $vbulletin->bf_ugp_forumpermissions['canpostnew']) AND $foruminfo['allowposting']);
	$can['reply'] = (($permissions & $vbulletin->bf_ugp_forumpermissions['canreply']) AND $foruminfo['allowposting']);
	$can['reply'] = ($can['replyown'] OR $can['replyothers']);
	$can['editpost'] = $permissions & $vbulletin->bf_ugp_forumpermissions['caneditpost'];
	$can['postattachment'] = (($permissions & $vbulletin->bf_ugp_forumpermissions['canpostattachment']) AND $foruminfo['allowposting'] AND !empty($vbulletin->userinfo['attachmentextensions']));
	$can['attachment'] = ($can['postattachment'] AND ($can['postnew'] OR $can['replyown'] OR $can['replyothers']));

	// Legacy Hook 'forumrules' Removed //

	$templater = vB_Template::create('forumrules');
		$templater->register('bbcodeon', $bbcodeon);
		$templater->register('can', $can);
		$templater->register('htmlcodeon', $htmlcodeon);
		$templater->register('imgcodeon', $imgcodeon);
		$templater->register('smilieson', $smilieson);
	$forumrules = $templater->render();
}

/**
* Fetches the tagbits for display in a thread.
*
* @param	array	Tags
*
* @return	string	Tag bits, including a none word and progress image
*/
function fetch_tagbits($tags)
{
	global $vbulletin, $vbphrase, $show;


	if ($tags)
	{
		$tag_array = explode(',', $tags);

		$tag_list = '';
		foreach ($tag_array AS $tag)
		{
			$tag = trim($tag);
			if ($tag === '')
			{
				continue;
			}
			$tag_url = urlencode(unhtmlspecialchars($tag));
			$tag = fetch_word_wrapped_string($tag);

			// Legacy Hook 'tag_fetchbit' Removed //

//			$tag_list .= ($tag_list != '' ? ', ' : '');
			$templater = vB_Template::create('tagbit');
				$templater->register('tag', $tag);
				$templater->register('tag_url', $tag_url);
			$tag_list .= $templater->render();
		}
	}
	else
	{
		$tag_list = '';
	}

	// Legacy Hook 'tag_fetchbit_complete' Removed //

	$templater = vB_Template::create('tagbit_wrapper');
		$templater->register('tag_list', $tag_list);
	$wrapped = $templater->render();
	return $wrapped;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84935 $
|| #######################################################################
\*=========================================================================*/
