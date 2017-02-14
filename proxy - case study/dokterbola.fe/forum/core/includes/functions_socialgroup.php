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

/**#@+
* The maximum sizes for the "small" social group icons
*/
define('FIXED_SIZE_GROUP_ICON_WIDTH', 200);
define('FIXED_SIZE_GROUP_ICON_HEIGHT', 200);
define('FIXED_SIZE_GROUP_THUMB_WIDTH', 80);
define('FIXED_SIZE_GROUP_THUMB_HEIGHT', 80);
/**#@-*/



/**
 * Fetches information about the selected social discussion
 *
 * @param	integer				id of the discussion
 * @return 	array | false		Array of information about the discussion, or false
 */
function fetch_socialdiscussioninfo($discussionid)
{
	global $vbulletin;
	static $socialdiscussioncache;

	$discussionid = intval($discussionid);
	if (!isset($socialdiscussioncache[$discussionid]))
	{

		$socialdiscussioncache[$discussionid] = $vbulletin->db->query_first("
			SELECT discussion.discussionid, discussion.groupid, discussion.firstpostid, discussion.lastpostid, discussion.visible, discussion.subscribers,
					discussion.moderation, discussion.deleted " .
					($vbulletin->userinfo['userid'] ?
					', IF(subscribe.discussionid,1,0) AS subscribed, COALESCE(discussionread.readtime,0) AS readtime' : '') . "
				,firstpost.state, firstpost.postuserid, firstpost.title, firstpost.postusername
			FROM " . TABLE_PREFIX . "discussion AS discussion
			LEFT JOIN " . TABLE_PREFIX . "groupmessage AS firstpost
				ON (firstpost.gmid = discussion.firstpostid)" .
			($vbulletin->userinfo['userid'] ?
			 " LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS subscribe
			    ON (subscribe.userid = " . $vbulletin->userinfo['userid'] . "
			    AND subscribe.discussionid = discussion.discussionid)
			   LEFT JOIN " . TABLE_PREFIX . "discussionread AS discussionread
			    ON (discussionread.userid = " . $vbulletin->userinfo['userid'] . "
			    AND discussionread.discussionid = discussion.discussionid)" : '') . "
			WHERE discussion.discussionid = $discussionid
		");

		// check read marking
	 	if (!$socialdiscussioncache[$discussionid]['readtime'])
	 	{
	 		if (!($socialdiscussioncache[$discussionid]['readtime'] =
				fetch_bbarray_cookie('discussion_marking', $socialdiscussioncache[$discussionid]['discussionid'])))
			{
				$socialdiscussioncache[$discussionid]['readtime'] = $vbulletin->userinfo['lastvisit'];
			}
	 	}
	}

	if (!$socialdiscussioncache[$discussionid])
	{
		return false;
	}
	else
	{
		return $socialdiscussioncache[$discussionid];
	}
}

/**
 * Takes information regardign a group, and prepares the information within it
 * for display
 *
 * @param	array	Group Array
 * @param	bool	Whether to fetch group members and avatars
 *
 * @return	array	Group Array with prepared information
 *
 */
function prepare_socialgroup($group, $fetchmembers = false)
{
	global $vbulletin;

	if (!is_array($group))
	{
		return array();
	}

	if ($fetchmembers)
	{
		$membersinfo = cache_group_members();
		$group['membersinfo'] = $membersinfo[$group['groupid']];
	}

	$group['joindate'] = (!empty($group['joindate']) ?
		vbdate($vbulletin->options['dateformat'], $group['joindate'], true) : '');
	$group['createtime'] = (!empty($group['createdate']) ?
		vbdate($vbulletin->options['timeformat'], $group['createdate'], true) : '');
	$group['createdate'] = (!empty($group['createdate']) ?
		vbdate($vbulletin->options['dateformat'], $group['createdate'], true) : '');

	$group['lastupdatetime'] = (!empty($group['lastupdate']) ?
		vbdate($vbulletin->options['timeformat'], $group['lastupdate'], true) : '');
	$group['lastupdatedate'] = (!empty($group['lastupdate']) ?
		vbdate($vbulletin->options['dateformat'], $group['lastupdate'], true) : '');

	$group['visible'] = vb_number_format($group['visible']);
	$group['moderation'] = vb_number_format($group['moderation']);

	$group['members'] = vb_number_format($group['members']);
	$group['moderatedmembers'] = vb_number_format($group['moderatedmembers']);

	$group['categoryname'] = htmlspecialchars_uni($group['categoryname']);
	$group['discussions'] = vb_number_format($group['discussions']);
	$group['lastdiscussion'] = fetch_word_wrapped_string(fetch_censored_text($group['lastdiscussion']));
	$group['trimdiscussion'] = fetch_trimmed_title($group['lastdiscussion']);

	if (!($group['options'] & $vbulletin->bf_misc_socialgroupoptions['enable_group_albums']))
	{
		// albums disabled in this group - force 0 pictures
		$group['picturecount'] = 0;
	}
	$group['rawpicturecount'] = $group['picturecount'];
	$group['picturecount'] = vb_number_format($group['picturecount']);

	$group['rawname'] = $group['name'];
	$group['rawdescription'] = $group['description'];

	$group['name'] = fetch_word_wrapped_string(fetch_censored_text($group['name']));

	if ($group['description'])
	{
 		$group['shortdescription'] = fetch_word_wrapped_string(fetch_censored_text(fetch_trimmed_title($group['description'], 185)));
	}
	else
	{
		$group['shortdescription'] = $group['name'];
	}

 	$group['mediumdescription'] = fetch_word_wrapped_string(fetch_censored_text(fetch_trimmed_title($group['description'], 1000)));
	$group['description'] = nl2br(fetch_word_wrapped_string(fetch_censored_text($group['description'])));

	$group['is_owner'] = ($group['creatoruserid'] == $vbulletin->userinfo['userid']);

	$group['is_automoderated'] = (
		$group['options'] & $vbulletin->bf_misc_socialgroupoptions['owner_mod_queue']
		AND $vbulletin->options['sg_allow_owner_mod_queue']
		AND !$vbulletin->options['social_moderation']
	);

	$group['canviewcontent'] = (
		(
			(
				!($group['options'] & $vbulletin->bf_misc_socialgroupoptions['join_to_view'])
				OR !$vbulletin->options['sg_allow_join_to_view']
			) // The above means that you dont have to join to view
			OR $group['membertype'] == 'member'
			// Or can moderate comments
			OR can_moderate(0, 'canmoderategroupmessages')
			OR can_moderate(0, 'canremovegroupmessages')
			OR can_moderate(0, 'candeletegroupmessages')
			OR fetch_socialgroup_perm('canalwayspostmessage')
			OR fetch_socialgroup_perm('canalwascreatediscussion')
		)
	);

 	$group['lastpostdate'] = vbdate($vbulletin->options['dateformat'], $group['lastpost'], true);
 	$group['lastposttime'] = vbdate($vbulletin->options['timeformat'], $group['lastpost']);

 	$group['lastposterid'] = $group['canviewcontent'] ? $group['lastposterid'] : 0;
 	$group['lastposter'] = $group['canviewcontent'] ? $group['lastposter'] : '';

 	// check read marking
	//remove notice and make readtime determination a bit more clear
	if (!empty($group['readtime']))
	{
		$readtime = $group['readtime'];
	}
	else
	{
		$readtime = fetch_bbarray_cookie('group_marking', $group['groupid']);
		if (!$readtime)
		{
			$readtime = $vbulletin->userinfo['lastvisit'];
		}
	}

 	// get thumb url
 	$group['iconurl'] = fetch_socialgroupicon_url($group, true);

 	// check if social group is moderated to join
 	$group['membermoderated'] = ('moderated' == $group['type']);

 	// posts older than markinglimit days won't be highlighted as new
	$oldtime = (TIMENOW - ($vbulletin->options['markinglimit'] * 24 * 60 * 60));
	$readtime = max((int)$readtime, $oldtime);
	$group['readtime'] = $readtime;
	$group['is_read'] = ($readtime >= $group['lastpost']);

	// Legacy Hook 'group_prepareinfo' Removed //

	return $group;
}


/**
 * Rebuilds discussion counter info, including first and last post information.
 *
 * @param integer								The id of the discussion
 */
function build_discussion_counters($discussionid)
{
	global $vbulletin;

	if (!($discussionid = intval($discussionid)))
	{
		return;
	}

	// Get message counters
	$messages = $vbulletin->db->query_first("
		SELECT
			SUM(IF(state = 'visible', 1, 0)) AS visible,
			SUM(IF(state = 'deleted', 1, 0)) AS deleted,
			SUM(IF(state = 'moderation', 1, 0)) AS moderation
		FROM " . TABLE_PREFIX . "groupmessage
		WHERE discussionid = $discussionid
	");

	// Get last post info
	$lastpost = $vbulletin->db->query_first("
		SELECT user.username, gm.postuserid, gm.dateline, gm.gmid
		FROM " . TABLE_PREFIX . "groupmessage AS gm
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = gm.postuserid)
		WHERE gm.discussionid = $discussionid
		AND gm.state = 'visible'
		ORDER BY gm.dateline DESC
		LIMIT 1
	");

	$discussion = fetch_socialdiscussioninfo($discussionid);

	$dataman =& datamanager_init('Discussion', $vbulletin, vB_DataManager_Constants::ERRTYPE_ARRAY);
	$dataman->set_existing($discussion);

	if ($lastpost['gmid'])
	{
		$dataman->set('lastpost', $lastpost['dateline']);
		$dataman->set('lastposter', $lastpost['username']);
		$dataman->set('lastposterid', $lastpost['postuserid']);
		$dataman->set('lastpostid', $lastpost['gmid']);
	}

	$messages['visible'] = $messages['visible'] ? $messages['visible'] : 1;

	$dataman->set('visible', $messages['visible']);
	$dataman->set('deleted', $messages['deleted']);
	$dataman->set('moderation', $messages['moderation']);

	// Legacy Hook 'discussion_build_counters' Removed //

	$dataman->save();
	unset($dataman);
}

/**
* Updates the counter for the owner of the group that shows how many pending members
* they have awaiting them to deal with
*
* @param	integer	The userid of the owner of the group
*/
function update_owner_pending_gm_count($ownerid)
{
	global $vbulletin;

	list($pendingcountforowner) = $vbulletin->db->query_first("
		SELECT SUM(moderation) FROM " . TABLE_PREFIX . "socialgroup
		WHERE creatoruserid = " . intval($ownerid)
		, vB_Database::DBARRAY_NUM);

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "user
		SET gmmoderatedcount = " . intval($pendingcountforowner) . "
		WHERE userid = " . intval($ownerid)
	);
}


/**
 * Checks a single social group permission.
 *
 * @param	string	The permission to check
 *
 * @return	boolean	Whether or not the current user has the permission.
 */
function fetch_socialgroup_perm($perm)
{
	global $vbulletin;

	$userinfo = $vbulletin->userinfo;

	if (isset($vbulletin->bf_ugp_socialgrouppermissions["$perm"]))
	{
		return $userinfo['permissions']['socialgrouppermissions'] &
				$vbulletin->bf_ugp_socialgrouppermissions["$perm"];
	}

	return false;
}


/**
 * Prepares the appropriate url for a group icon.
 * The url is based on whether fileavatars are in use, and whether a thumb is required.
 *
 * @param array mixed $groupinfo				- GroupInfo array of the group to fetch the icon for
 * @param boolean $thumb						- Whether to return a thumb url
 * @param boolean $path							- Whether to fetch the path or the url
 * @param boolean $force_file					- Always get the file path as if it existed
 */
function fetch_socialgroupicon_url($groupinfo, $thumb = false, $path = false, $force_file = false)
{
	global $vbulletin;

	$iconurl = false;

	if ($vbulletin->options['sg_enablesocialgroupicons'])
	{
		if (!$groupinfo['icondateline'])
		{
			return vB_Template_Runtime::fetchStyleVar('unknownsgicon');
		}

		if ($vbulletin->options['usefilegroupicon'] OR $force_file)
		{
			$iconurl = ($path ? $vbulletin->options['groupiconpath'] : $vbulletin->options['groupiconurl']) . ($thumb ? '/thumbs' : '') . '/socialgroupicon' . '_' . $groupinfo['groupid'] . '_' . $groupinfo['icondateline'] . '.gif';
		}
		else
		{
			$iconurl = 'image.php?' . vB::getCurrentSession()->get('sessionurl') . 'groupid=' . $groupinfo['groupid'] . '&amp;dateline=' . $groupinfo['icondateline'] . ($thumb ? '&amp;type=groupthumb' : '');
		}
	}

	return $iconurl;
}

/**
 * Fetches newest groups from datastore or rebuilds the cache.
 *
 * @param boolean $force_rebuild				Force the cache to be rebuilt
 * @param boolean $without_icons				Fetch groups that have no icon
 * @return array								Array of groupinfos
 */
function fetch_socialgroup_newest_groups($force_rebuild = false, $without_icons = false, $listview = false)
{
	global $vbulletin;

	$groups = $vbulletin->sg_newest_groups;

	// Legacy Hook 'group_fetch_newest' Removed //

	if ($force_rebuild OR !is_array($groups))
	{
		$sql = "SELECT
					socialgroup.*, socialgroup.dateline AS createdate, sgc.title AS categoryname, sgc.socialgroupcategoryid AS categoryid,
					socialgroup.groupid, socialgroup.name, socialgroup.description, socialgroup.dateline, sgicon.dateline AS icondateline,
					socialgroupmember.type AS membertype, socialgroupmember.dateline AS joindate,
					sgicon.thumbnail_width AS iconthumb_width, sgicon.thumbnail_height AS iconthumb_height
				FROM " . TABLE_PREFIX . "socialgroup AS socialgroup
				LEFT JOIN " . TABLE_PREFIX ."socialgroupmember AS socialgroupmember
					ON (socialgroup.groupid = socialgroupmember.groupid AND socialgroupmember.userid = " . $vbulletin->userinfo['userid'] . ")
				LEFT JOIN " . TABLE_PREFIX . "socialgroupicon AS sgicon ON (sgicon.groupid = socialgroup.groupid)
				INNER JOIN " . TABLE_PREFIX . "socialgroupcategory AS sgc ON (sgc.socialgroupcategoryid = socialgroup.socialgroupcategoryid)
				ORDER BY socialgroup.dateline DESC
				LIMIT 0, " . ($vbulletin->options['sg_newgroups_count'] ? intval($vbulletin->options['sg_newgroups_count']) : 5) . "
		";

		$newgroups = $vbulletin->db->query_read_slave($sql);

		$groups = array();
		while ($group = $vbulletin->db->fetch_array($newgroups))
		{
			$groups[] = $group;
		}
		$vbulletin->db->free_result($newgroups);

		build_datastore('sg_newest_groups', serialize($groups), 1);
	}

	return $groups;
}

function cache_group_members($groupids = null, $force_rebuild = false)
{
	global $vbulletin;
	static $group_members;

	if ($group_members AND !$force_rebuild)
	{
		return $group_members;
	}

	if (!is_array($groupids) OR !$groupids)
	{
		return null;
	}

	/*
	 * Explain to this query:
	 * We only need to fetch at most 10 new joined member info for each group.
	 * Fetching the members for each group separately is faster than one query with a heavy subquery
	 */

	$group_members = array();
	foreach ($groupids AS $groupid) {
		$result = $vbulletin->db->query_read_slave("
			SELECT user.*, socialgroupmember.groupid
			" . ($vbulletin->options['avatarenabled'] ? ",avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight" : "") . "
			FROM " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (socialgroupmember.userid = user.userid)
			" . ($vbulletin->options['avatarenabled'] ? "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)" : "") . "
			WHERE socialgroupmember.groupid = $groupid
			ORDER BY socialgroupmember.dateline DESC
			LIMIT 10
				");
		while ($member = $vbulletin->db->fetch_array($result))
		{
			fetch_avatar_from_userinfo($member, true, true);
			$group_members[$member['groupid']][] = $member;
		}
	}

	return $group_members;

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
