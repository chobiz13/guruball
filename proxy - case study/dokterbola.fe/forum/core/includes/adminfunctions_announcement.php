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

error_reporting(E_ALL & ~E_NOTICE);

// #############################################################################
/**
* Checks whether or not an administrator can post announcements
*
* @param	integer	Forum ID
*
* @return	integer	The return value of this function is really rubbish... who wrote this?
*/
function fetch_announcement_permission_error($forumid)
{
	global $vbulletin, $phrase;

	if ($forumid == -1 AND !($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator']))
	{
		return 1;
	}
	else if ($forumid != -1 AND !can_moderate($forumid, 'canannounce'))
	{
		return 2;
	}

	return 0;
}

// #############################################################################
/**
* Returns the phrase name for an error message
*
* @param	integer	Magic error number
*
* @return	string
*/
function fetch_announcement_permission_error_phrase($errno)
{
	global $vbphrase;
	switch($errno)
	{
		case 1:
			return 'you_do_not_have_permission_global';
			break;
		case 2:
			return 'you_do_not_have_permission_forum';
			break;
		default:
			return construct_phrase($vbphrase['unknown_error'], $errno);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
