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

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$timenow = vB::getRequest()->getTimeNow();
$data = array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_STORED,
	'timenow' => $timenow);
$assertor = vB::getDbAssertor();
$infractions = $assertor->assertQuery('getUserExpiredInfractions', $data);

if (defined('IN_CONTROL_PANEL'))
{
	// $vbphrase = vB_Api::instanceInternal('phrase')->fetch(array(
	// 	'task_infractions_title',
	// 	'done',
	// ));
}

if ($infractions->valid())
{
	$infractionid = array();

	$warningarray = array();
	$infractionarray = array();
	$ipointsarray = array();

	$userids = array();
	$usernames = array();
	$textnodeids = array();

	$clearCacheNodeIds = array();

	if (defined('IN_CONTROL_PANEL'))
	{
		echo '<h4>Expire Infractions:</h4>';
		echo '<ol>';
	}

	foreach ($infractions AS $infraction)
	{
		if (defined('IN_CONTROL_PANEL'))
		{
			echo '<li>Infraction NodeID: ' . $infraction['nodeid'];
		}

		$quantity = $assertor->update('infraction',
			array('action' => 1, 'actiondateline' => $timenow),
			array('nodeid' => $infraction['nodeid'], 'action' => 0)
		);

	// enforce atomic update so that related records are only updated at most one time, in the event this task is executed more than one time
		if ($quantity)
	{
			if (defined('IN_CONTROL_PANEL'))
			{
				echo ' Updated';
			}

			// clear cache for these infraction nodes
			$clearCacheNodeIds[] = $infraction['nodeid'];

			$userids["$infraction[infracteduserid]"] = $infraction['username'];
		if ($infraction['points'])
		{
				$infractionarray["$infraction[infracteduserid]"]++;
				$ipointsarray["$infraction[infracteduserid]"] += $infraction['points'];
		}
		else
		{
				$warningarray["$infraction[infracteduserid]"]++;
		}

			if ($infraction['infractednodeid'] > 0)
			{
				$textnodeids[] = $infraction['infractednodeid'];
	}
	}
		else
		{
			if (defined('IN_CONTROL_PANEL'))
			{
				echo ' Update not needed';
			}
		}

		if (defined('IN_CONTROL_PANEL'))
		{
			echo '</li>';
		}
	}

	if (defined('IN_CONTROL_PANEL'))
	{
		echo '</ol>';
	}

	// ############################ MAGIC(tm) ###################################
	if (!empty($userids))
	{
		$result = $assertor->assertquery('buildUserInfractions', array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_METHOD,
			'points' => $ipointsarray,
			'infractions' => $infractionarray,
			'warnings' => $warningarray
			)
		);

		if ($result)
		{
			vB_Api::instance('Content_Infraction')->buildInfractionGroupIds(array_keys($userids));
		}

		if (!empty($textnodeids))
		{
			// mark the infracted node's text record as not having an infraction any more
			// 1 = infraction, 2 = warning, 0 = no infraction or warning (or an expired/reversed infraction)
			$assertor->update('vBforum:text', array('infraction' => 0), array('nodeid' => $textnodeids));

			// clear cache for these text nodes
			$clearCacheNodeIds = array_merge($clearCacheNodeIds, $textnodeids);
	}

		if (defined('IN_CONTROL_PANEL'))
		{
			echo 'Updated user and text tables.';
		}
	}

	if (!empty($clearCacheNodeIds))
	{
		// invalidate cache
		vB_Api::instance('node')->clearCacheEvents($clearCacheNodeIds);
	}

	if (!empty($userids))
	{
	log_cron_action(implode(', ', $userids), $nextitem, 1);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
