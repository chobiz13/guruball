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

/**
 * vB_Api_Vb4_notification
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_notification extends vB_Api
{
	/**
	 * Dismisses a notification by triggering the "read_topic" event on the $threadid.
	 * If threadid is not provided, it will attempt to dismiss the provided $dismissid
	 * notification directly.
	 *
	 * @param  int		$threadid	Optional integer threadid that is the
	 *								subject of a content-related notification
	 * @param  int		$dismissid	Optional numeric id of specific notification
	 *								to be dismissed. Only used if threadid is empty.
	 *
	 * @return [array]
	 */
	public function dismiss($threadid = 0, $dismissid = 0)
	{
		$cleaner = vB::getCleaner();

		// Clean the input params
		$threadid = $cleaner->clean($threadid, vB_Cleaner::TYPE_UINT);
		$dismissid = $cleaner->clean($dismissid, vB_Cleaner::TYPE_UINT);

		if (!empty($threadid))
		{
			$lib = vB_Library::instance('notification');
			// Copied from vB_Library_Node::markRead()
			$lib->triggerNotificationEvent('read_topic', array('nodeid' => $threadid));
			return array('response' => array('dismissed' => true));
		}

		if (!empty($dismissid))
		{
			$api = vB_Api::instance('notification');
			$result = $api->dismissNotification($dismissid);

			if (empty($result['error']) AND empty($result['errors']))
			{
				// If we care about checking the validity of dismissid, you could try
				// checking for (bool)$result['affected_rows']
				return array('response' => array('dismissed' => true));
			}
			else
			{
				// The client can't really handle all of the api errors that could
				// happen. Let's just dumb it down
				return array('response' => array('errormessage' => array('dismiss_failed')));
			}
		}

		return array('response' => array('errormessage' => array('no_threadid_or_dismissid')));

	}

	/**
	 * Fetch unread notifications of the specified type, descending by date
	 *
	 * @param  String	$notificationid	Subscription|Reply
	 * @param  int		$perpage
	 * @param  int		$pagenumber
	 *
	 * @return [array]
	 */
	public function get($notificationid, $perpage, $pagenumber)
	{
		/*
			NOTE 1:
			vB_Input_Cleaner automagically translates a "page" GET/POST/GLOBAL parameter into
			"pagenumber". So although the initial API spec requested "page", not "pagenumber",
			the function definition must be "pagenumber", otherwise the api.php will just
			keep throwing "invalid_api_signature", because it "stuffs" a "pagenumber"
			param into the GET/POST array (whichever had the original "page" param).
			See convert_shortvars() & vB_Input_Cleaner() in core/includes/class_core.php
			for where this happens.

			NOTE 2:
			"notificationid" IS NOT the numeric auto_increment `notification`.notificationid
			used in the backend. It's just a string that maps to the `notificationtypes`.typename,
			but because of legacy code, and for the sake of not confusing the hell out of each other
			when talking to the mobile devs, we had to stick w/ using the term 'notificationid'.
		 */
		$cleaner = vB::getCleaner();

		// Clean the input params
		$notificationid = $cleaner->clean($notificationid, vB_Cleaner::TYPE_STR);
		$perpage = $cleaner->clean($perpage, vB_Cleaner::TYPE_UINT);
		$pagenumber = $cleaner->clean($pagenumber, vB_Cleaner::TYPE_UINT);

		/*
			Note, this function DOES NOT HANDLE 'vmunreadcount' for $notificationid, because
			that's a legacy thing w/ its OWN handling in the app (opens the user's profile/wall page)
			If we want this function to handle that, we should update the spec so it returns the
			NEW typename string, so we don't have to add a stupid amount of code maintenance to
			"map legacy to new string" handling.
		 */
		// Keep this in sync with vB_Api_Vb4_forum::call()
		$supportedTypes = array(
			vB_Notification_Content_GroupByStarter_Subscription::TYPENAME
				=> true,
			vB_Notification_Content_GroupByStarter_Reply::TYPENAME
				=> true,
		);

		if (!isset($supportedTypes[$notificationid]))
		{
			return array('response' => array('errormessage' => array('unsupported_notification_type')));
		}

		if ($perpage < 1)
		{
			return array('response' => array('errormessage' => array('perpage_too_small')));
		}

		if ($pagenumber < 1)
		{
			return array('response' => array('errormessage' => array('page_too_small')));
		}

		/*
			We can't pass in pagenumber & perpage here, because we need to return "totalpages" &
			"total" counts
		*/

		$data = array(
			'typename' => $notificationid,
			'readFilter' => 'unread_only',
		);
		$library = vB_Library::instance('notification');
		$notifications = $library->fetchNotificationsForCurrentUser($data);

		// return bits
		$total = count($notifications);
		$totalpages = ceil($total / $perpage);

		$notificationsOnPage = array_slice(
			$notifications,
			($pagenumber - 1) * $perpage,// offset
			$perpage,	// length
			true	// preserve keys
		);

		// First, cluster the node fetches into 1 call so we can bulk fetch from DB
		$nodesToFetch = array();
		foreach ($notificationsOnPage AS $nid => $notification)
		{
			if (!empty($notification['sentbynodeid']))
			{
				$nodesToFetch[$notification['sentbynodeid']] = $notification['sentbynodeid'];
			}
		}
		// According to the doc block, this function is supposed to maintain the original keys.
		$nodes = vB_Api::instance('node')->getFullContentforNodes($nodesToFetch);

		$func_lib = vB_Library::instance('vb4_functions');
		$notificationbits = array();
		foreach ($notificationsOnPage AS $nid => $notification)
		{
			if (!empty($notification['sentbynodeid']))
			{
				$node = $nodes[$notification['sentbynodeid']];
				if (empty($node) OR $node['nodeid'] !== $notification['sentbynodeid'])
				{
					// dev exception only. I've never hit this in testing, so leaving it out for now.
					//throw new vB_Exception_Api('getFullContentforNodes preserving keys was a lie. Like the cake.');
				}
				$users = array();
				if (!empty($notification['others']))
				{
					foreach ($notification['others'] AS $user)
					{
						$users[]['user'] = array(
							"userid"    => $user['userid'],
							"username"  => $user['username'],
							"avatarurl" => $this->getAbsoluteAvatarUrl($user['avatarurl']),
						);
					}
				}

				$notificationbits[]['notification'] = array(
					"threadid"          => $node['starter'],
					"threadtitle"       => $node['content']['startertitle'],
					"preview"           => $func_lib->getPreview($node['content']['rawtext']),
					"posttime"          => $node['publishdate'],
					"forumid"           => $node['content']['channelid'],
					"forumtitle"        => $node['content']['channeltitle'],
					"notificationtime"  => $notification['lastsenttime'],
					"notificationid"    => $notificationid, // the "Type" string passed in from the app
					// This is confusing, but we can't get around this naming conflict
					// due to legacy code on the app using "notificationid".
					"dismissid"         => $notification['notificationid'],
					"users"             => $users,
				);
				unset($users);
			}
		}

		$response['response'] = array(
			'pagenumber'    => $pagenumber,
			'perpage'       => $perpage,
			'totalpages'    => $totalpages,
			'total'         => $total,
		);

		$response['response']['notificationbits'] = $notificationbits;
		return $response;
	}


	protected function getAbsoluteAvatarUrl($url)
	{
		/*
			So far, all of the avatar URLs were relative without a starting '/',
			so this is mostly to prepend it w/ the frontendurl + '/', with some
			"just in case" checks.
		 */
		if (substr($url, 0, 7) === 'http://' OR substr($url, 0, 8) === 'https://')
		{
			return $url;
		}
		else
		{
			$options = vB::getDatastore()->getValue('options');
			if (substr($url, 0, 1) !== '/' AND substr($options['frontendurl'], -1, 1) !== '/')
			{
				return $options['frontendurl'] . '/' . $url;
			}
			return $options['frontendurl'] . $url;;
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85370 $
|| #######################################################################
\*=========================================================================*/
