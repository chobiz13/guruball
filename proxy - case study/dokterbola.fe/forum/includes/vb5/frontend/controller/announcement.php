<?php
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

/** This handles rendering and caching of announcements
 * */
class vB5_Frontend_Controller_Announcement extends vB5_Frontend_Controller
{
	public static function getText($channelid)
	{
		// called from widget_announcement using the {vb:action} tag
		// This can't be called externally, since the method name
		// doesn't start with 'action'

		if (empty($channelid))
		{
			return false;
		}
		$cacheKey = "vB_Announcements_$channelid";

		// first try with cache
		$api = Api_InterfaceAbstract::instance();
		$cache = $api->cacheInstance(0);
		$found = $cache->read($cacheKey);

		if ($found !== false)
		{
			return $found;
		}

		$announcements = $api->callApi('announcement', 'fetch', array($channelid));
		$parser = new vB5_Template_BbCode();
		$bbCodeOptions = array('allowimages', 'allowimagebbcode', 'allowbbcode', 'allowhtml', 'allowsmilies');
		foreach ($announcements as $key => $announcement)
		{
			$announcements[$key]['pagetext'] = $parser->doParse($announcement['pagetext'], $announcement['dohtml'], $announcement['dosmilies'],
				$announcement['dobbcode'], $announcement['dobbimagecode']);
		}

		$events = array('nodeChg_' . $channelid, 'vB_AnnouncementChg');
		$cache->write($cacheKey, $announcements, 10080, $events);

		return $announcements;
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85421 $
|| #######################################################################
\*=========================================================================*/
