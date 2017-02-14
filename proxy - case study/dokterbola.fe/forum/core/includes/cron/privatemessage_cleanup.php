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

//This removes private messages with no activity for 30 days.

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

//First we get a list up to 500 records.

$assertor = vB::getDbAssertor();
$records = $assertor->assertQuery('vBForum:getDeletedMsgs', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
	'deleteLimit' => vB::getRequest()->getTimeNow() - (30 * 86400),
	vB_dB_Query::PARAM_LIMIT => 200));

if ($records AND $records->valid())
{
	$nodeids = array();
	foreach ($records as $record)
	{
		$nodeids[] = $record['nodeid'];
	}

	vB_Library::instance('content_privatemessage')->delete($nodeids);
}

log_cron_action('', $nextitem, 1);

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85997 $
|| #######################################################################
\*=========================================================================*/
