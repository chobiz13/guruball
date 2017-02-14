<?php if(!defined('VB_ENTRY')) die('Access denied.');
/*
 * Forum Runner
 *
 * Copyright (c) 2010-2011 to End of Time Studios, LLC
 *
 * This file may not be redistributed in whole or significant part.
 *
 * http://www.forumrunner.com
 */

require_once(DIR . '/includes/functions_forumlist.php');

function do_get_profile()
{
	global $vbulletin;

	$userinfo = vB_Api::instance('user')->fetchUserInfo();

	$cleaned = vB::getCleaner()->cleanArray($_REQUEST, array(
		'userid' => vB_Cleaner::TYPE_UINT,
	));

	if (!$userinfo['userid'] && !$cleaned['userid']) {
		return json_error(ERR_INVALID_LOGGEDIN, RV_NOT_LOGGED_IN);
	}

	if (!$cleaned['userid']) {
		$cleaned['userid'] = $userinfo['userid'];
	}

	$profile = vB_Api::instance('user')->fetchProfileInfo($cleaned['userid']);

	if (empty($profile)) {
		return json_error(ERR_NO_PERMISSION);
	}

	$values = array();

	foreach($profile['customFields']['default'] as $name => $value) {
		$value = $value['val'];
		if ($value === null) {
			$value = '';
		}
		$values[] = array(
			'name' => (string) new vB_Phrase('cprofilefield', $name),
			'value' => $value,
		);
	}

	$groups = array();
	$groups[] = array(
		'name' => 'about',
		'values' => $values,
	);

	$out = array(
		'username' => prepare_utf8_string($profile['username']),
		'joindate' => prepare_utf8_string(fr_date($profile['joindate'])),
		'posts' => $profile['posts'],
		'online' => fr_get_user_online($profile['lastactivity']),
		'avatar_upload' => $profile['canuseavatar'] ? true : false,
		'groups' => $groups,
	);

	$avatarurl = vB_Library::instance('vb4_functions')->avatarUrl($cleaned['userid']);
	if ($avatarurl) {
	    $out['avatarurl'] = $avatarurl;
	}

	cache_moderators();
	cache_permissions($vbulletin->userinfo);
	$canbanuser = (($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) OR can_moderate(0, 'canbanusers'));
	if ($canbanuser) {
	    $out['ban'] = true;
	}

	return $out;
}

function do_upload_avatar()
{
	$cleaned = vB::getCleaner()->cleanArray($_REQUEST, array(
		'upload' => vB_Cleaner::TYPE_FILE,
	));

	if (empty($cleaned['upload'])) {
		return json_error(ERR_NO_PERMISSION);
	}

	$upload_result = vB_Api::instance('profile')->upload($cleaned['upload']);

	if (!empty($upload_result['errors'])) {
		return json_error(ERR_NO_PERMISSION);
	}

	return true;
}
