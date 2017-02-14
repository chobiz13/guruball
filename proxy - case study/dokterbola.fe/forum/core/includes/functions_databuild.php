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

function build_bbcode_video($checktable = false)
{
	if ($checktable)
	{
		try
		{
			vB::getDbAssertor()->assertQuery('bbcode_video', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT
			));
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	require_once(DIR . '/includes/class_xml.php');
	$xmlobj = new vB_XML_Parser(false, DIR . '/includes/xml/bbcode_video_vbulletin.xml');
	$data = $xmlobj->parse();

	if (is_array($data['provider']))
	{
		$insert = array();
		foreach ($data['provider'] AS $provider)
		{
			$items = array();
			$items['tagoption'] = $provider['tagoption'];
			$items['provider'] = $provider['title'];
			$items['url'] = $provider['url'];
			$items['regex_url'] = $provider['regex_url'];
			$items['regex_scrape'] = $provider['regex_scrape'];
			$items['embed'] = $provider['embed'];

			$insert[] = $items;
		}

		if (!empty($insert))
		{
			vB::getDbAssertor()->assertQuery('truncateTable', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
				'table' => 'bbcode_video'
			));
			vB::getDbAssertor()->assertQuery('bbcode_video', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_MULTIPLEINSERT,
				vB_dB_Query::FIELDS_KEY => array('tagoption', 'provider', 'url', 'regex_url', 'regex_scrape', 'embed'),
				vB_dB_Query::VALUES_KEY => $insert));
		}
	}

	$firsttag = '<vb:if condition="$provider == \'%1$s\'">';
	$secondtag = '<vb:elseif condition="$provider == \'%1$s\'" />';

	$template = array();
	$bbcodes = vB::getDbAssertor()->assertQuery('bbcode_video', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT
		),
		array('field' => array('priority'), 'direction' => array(vB_dB_Query::SORT_ASC))
	);

	foreach ($bbcodes as $bbcode)
	{
		if (empty($template))
		{
			$template[] = sprintf($firsttag, $bbcode['tagoption']);
		}
		else
		{
			$template[] = sprintf($secondtag, $bbcode['tagoption']);
		}
		$template[] = $bbcode['embed'];
	}
	$template[] = "</vb:if>";

	$final = implode("\r\n", $template);

	$exists = vB::getDbAssertor()->getRow('template', array(
			vB_dB_Query::CONDITIONS_KEY =>array(
				array('field' => 'title', 'value' => 'bbcode_video', 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'product', 'value' => array('', 'vbulletin'), 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'styleid', 'value' => -1, 'operator' => vB_dB_Query::OPERATOR_EQ)
			)
		));

	if ($exists)
	{
		try
		{
			vB_Api::instanceInternal('template')->update($exists['templateid'],'bbcode_video',$final,'vbulletin',false,false,'');
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	else
	{
		vB_Api::instanceInternal('template')->insert(-1, 'bbcode_video', $final, 'vbulletin');
	}
	return true;
}

// ###################### Start updateusertextfields #######################
// takes the field type pmfolders/buddylist/ignorelist/signature in 'field'
// takes the value to insert in $value
function build_usertextfields($field, $value, $userid = 0)
{
	global $vbulletin;

	$userdata = new vB_Datamanager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_STANDARD);

	if ($userid == 0)
	{
		$userdata->set_existing($vbulletin->userinfo);
	}
	else
	{
		$userinfo = array('userid' => $userid);
		$userdata->set_existing($userinfo);
	}

	$userdata->set($field, $value);
	$userdata->save();

	return 0;
}

// ###################### Start build_userlist #######################
// This forces the cache for X list to be rebuilt, only generally needed for modifications.
function build_userlist($userid, $lists = array())
{
	global $vbulletin;
	$userid = intval($userid);
	if ($userid == 0)
	{
		return false;
	}

	if (empty($lists))
	{
		$userlists = vB::getDbAssertor()->assertQuery('vBForum:fetchuserlists', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'userid' => $userid,
		));

		foreach ($userlists as $userlist)
		{
			$lists["$userlist[type]"][] = $userlist['userid'];
		}
	}

	$userdata = new vB_Datamanager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_STANDARD);
	$existing = array('userid' => $userid);
	$userdata->set_existing($existing);

	foreach ($lists AS $listtype => $values)
	{
		$key = $listtype . 'list';
		if (isset($userdata->validfields["$key"]))
		{
			$userdata->set($key, implode(',', $values));
		}
	}

	/* Now to set the ones that weren't set. */
	foreach ($userdata->list_types AS $listtype)
	{
		$key = $listtype . 'list';
		if ($userdata->is_field_set($key))
		{
			$userdata->set($key, '');
		}
	}

	$userdata->save();

	return true;
}

// ###################### Start saveuserstats #######################
// Save user count & newest user into template
function build_user_statistics()
{
	$members = vB::getDbAssertor()->getRow('vBForum:fetchUserStats');

	// get newest member
	$newuser = vB::getDbAssertor()->getRow('vBForum:fetchnewuserstats',
		array('userid' => $members['maxid']));

	// make a little array with the data
	$values = array(
		'numbermembers' => $members['users'],
		'activemembers' => isset($members['active']) ? $members['active'] : 0,
		'newusername'   => $newuser['username'],
		'newuserid'     => $newuser['userid']
	);

	// update the special template
	vB::getDatastore()->build('userstats', serialize($values), 1);

	return $values;
}

// ###################### Start getbirthdays #######################
function build_birthdays()
{
	$storebirthdays = array();

	$serveroffset = date('Z', vB::getRequest()->getTimeNow()) / 3600;

	$fromdatestamp = vB::getRequest()->getTimeNow() + (-11 - $serveroffset) * 3600;
	$fromdate = getdate($fromdatestamp);
	$storebirthdays['day1'] = date('Y-m-d', $fromdatestamp);

	$todatestamp = vB::getRequest()->getTimeNow() + (13 - $serveroffset) * 3600;
	$todate = getdate($todatestamp);
	$storebirthdays['day2'] = date('Y-m-d', $todatestamp);

	$todayneggmt = date('m-d', $fromdatestamp);
	$todayposgmt = date('m-d', $todatestamp);

	$bdays = vB::getDbAssertor()->getRows('vBForum:fetchBirthdays', array(
		vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
		'todayneggmt' => $todayneggmt,
		'todayposgmt' => $todayposgmt,
	));


	$year = date('Y');
	$day1 = $day2 = array();

	foreach ($bdays as $birthday)
	{
		$username = $birthday['username'];
		$userid = $birthday['userid'];
		$day = explode('-', $birthday['birthday']);
		if ($year > $day[2] AND $day[2] != '0000' AND $birthday['showbirthday'] == 2)
		{
			$age = $year - $day[2];
		}
		else
		{
			unset($age);
		}
		if ($todayneggmt == $day[0] . '-' . $day[1])
		{
			$day1[] = array(
				'userid'   => $userid,
				'username' => $username,
				'age'      => $age
			);
		}
		else
		{
			$day2[] = array(
				'userid'   => $userid,
				'username' => $username,
				'age'      => $age
			);
		}
	}
	$storebirthdays['users1'] = $day1;
	$storebirthdays['users2'] = $day2;

	vB::getDatastore()->build('birthdaycache', serialize($storebirthdays), 1);

	return $storebirthdays;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87198 $
|| #######################################################################
\*=========================================================================*/
