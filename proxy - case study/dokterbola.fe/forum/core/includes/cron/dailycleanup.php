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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
// if (!is_object($vbulletin->db))
// {
// 	exit;
// }

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

//  Clean up MAPI attachment helper table
$timenow = vB::getRequest()->getTimeNow();
$twodaysago = $timenow - (60*60*24*2);
$onemonthago = $timenow - (60 * 60 * 24 * 30);
$result = vB::getDbAssertor()->assertQuery('vBMAPI:cleanPosthash', array('cutoff' => $twodaysago));

// Clean the nodehash table
vB::getDbAssertor()->delete('vBForum:nodehash', array(array('field' => 'dateline', 'value' => $twodaysago, 'operator' => vB_dB_Query::OPERATOR_LT)));
// Clean all expired redirects
 vB_Library::instance('content_redirect')->deleteExpiredRedirects();

// SELECT announcements that are active, will be active in the future or were active in the last ten days
$anns = vB::getDbAssertor()->getRows('vBForum:announcement',
		array(vB_dB_Query::CONDITIONS_KEY=> array(
				array('field'=>'enddate', 'value' => $timenow -  864000, vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_LTE)
		)),
		false,
		'announcementid'
);

// Delete all read markers for announcements expired > 10 days
if (!empty($anns))
{
	vB::getDbAssertor()->delete('vBForum:announcementread',
		array(
				array('field'=>'announcementid', 'value' => array_keys($anns), vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_NE)
		)
	);
}

if (vB::getDatastore()->getOption('tagcloud_searchhistory'))
{
	vB::getDbAssertor()->delete('vBForum:tagsearch', array(
				array(
						'field'=>'dateline',
						'value' => $timenow - (vB::getDatastore()->getOption('tagcloud_searchhistory') * 60 * 60 * 24),
						vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_LT
				)
		)
	);
}

// Clean out autosave
vB::getDbAssertor()->delete('vBForum:autosavetext', array(array('field' => 'dateline', 'value' => $onemonthago, 'operator' => vB_dB_Query::OPERATOR_LT)));

// Legacy Hook 'cron_script_cleanup_daily' Removed //

log_cron_action('', $nextitem, 1);

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
