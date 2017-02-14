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

// function to get a ban-end timestamp
function convert_date_to_timestamp($period)
{
	if ($period[0] == 'P')
	{
		return 0;
	}

	$p = explode('_', $period);
	$d = explode('-', date('H-i-n-j-Y'));
	$date = array(
		'h' => &$d[0],
		'm' => &$d[1],
		'M' => &$d[2],
		'D' => &$d[3],
		'Y' => &$d[4]
	);

	$date["$p[0]"] += $p[1];
	return mktime($date['h'], 0, 0, $date['M'], $date['D'], $date['Y']);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
