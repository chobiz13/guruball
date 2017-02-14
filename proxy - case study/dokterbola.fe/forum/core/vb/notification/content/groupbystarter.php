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

abstract class vB_Notification_Content_GroupByStarter extends vB_Notification_Content
{
	const GROUP_CHILDREN = true;

	const TYPENAME = 'GroupByStarter';

	/**
	 * Children of this class will be grouped by the starter of the sentbynodeid.
	 *
	 * @return	String[String]
	 *
	 * @access protected
	 */
	final protected static function defineUnique($notificationData, $skipValidation)
	{
		$nodeid = $notificationData['sentbynodeid'];
		if ($skipValidation)
		{
			$node = array();
			$node['starter'] = (int) $notificationData['starter'];
		}
		else
		{
			$node = vB_Library::instance('node')->getNodeBare($nodeid);
			if (!isset($node['starter']))
			{
				throw new Exception("Missing data! node.starter");
			}
		}

		return array('starter' => (int) $node['starter']);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
