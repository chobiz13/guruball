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

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

require_once(DIR . '/includes/functions_misc.php');

// #############################################################################
/**
* Prints a row containing a <select> showing forums the user has permission to moderate
*
* @param	string	name for the <select>
* @param	mixed	selected <option>
* @param	string	text given to the -1 option
* @param	string	title for the row
* @param	boolean	Display the -1 option or not
* @param	boolean	Allow a multiple <select> or not
* @param	boolean	Display a 'select forum' option or not
* @param	string	If specified, check this permission for each forum
*/
function print_moderator_forum_chooser($name = 'forumid', $selectedid = -1, $topname = NULL, $title = NULL, $displaytop = true, $multiple = false, $displayselectforum = false, $permcheck = '')
{
	global $vbphrase;

	if ($title === NULL)
	{
		$title = $vbphrase['parent_forum'];
	}

	// stub this out.  The forum options function doesn't work and was removed, this function is used by a page that isn't displayed
	// but might be restored in some form, for now we'll just use a single "all forums" option (which is what was being displayed
	// by the broken code).
	//	$select_options = fetch_moderator_forum_options($topname, $displaytop, $displayselectforum, $permcheck);
	$select_options['-1'] = ($topname === NULL ? $vbphrase['no_one'] : $topname);
	print_select_row($title, $name, $select_options, $selectedid, 0, iif($multiple, 10, 0), $multiple);
}


/**
* Shows the form for inline mod authentication.
*/
function show_inline_mod_login($showerror = false)
{
	global $vbulletin, $vbphrase, $show;

	$show['inlinemod_form'] = true;
	$show['passworderror'] = $showerror;

	if (!$showerror)
	{
		$vbulletin->url = SCRIPTPATH;
	}
	$forumHome = vB_Library::instance('content_channel')->getForumHomeChannel();
	eval(standard_error(fetch_error('nopermission_loggedin',
		$vbulletin->userinfo['username'],
		vB_Template_Runtime::fetchStyleVar('right'),
		vB::getCurrentSession()->get('sessionurl'),
		$vbulletin->userinfo['securitytoken'],
		vB5_Route::buildUrl($forumHome['routeid'] . 'home|fullurl')
	)));

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
