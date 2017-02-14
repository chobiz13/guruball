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

class vB_Notification_Content_GroupByStarter_Subscription extends vB_Notification_Content_GroupByStarter
{

	protected static $triggers = array(
		'new-content'	=> 10,
		//'updated-content'	=> 10,

	);

	const TYPENAME = 'Subscription';

	protected function addAdditionalRecipients()
	{
		$skipUsers = array();
		/*
			TODO: if we get ignore list caching in memory, add them to the skip list.
		 */
		if (!empty($this->notificationData['sender']))
		{
			$skipUsers[$this->notificationData['sender']] = (int) $this->notificationData['sender'];
		}
		$apiResult = vB_Api::instanceInternal('follow')->getSubscribersForNotifications(
			$this->notificationData['sentbynodeid'],
			$skipUsers
		);
		$subscribers = $apiResult['subscribers'];

		// Subscribers should be an array keyed by userids.
		return array_keys($subscribers);
	}

	protected function typeEnabledForUser($user)
	{
		// subscription doesn't have an option, because if they didn't want notifications they probably wouldn't
		// have subscribed in the first place...
		return true;
	}


	/**
	 * @see vB_Notification::fetchPhraseArray()
	 */
	public static function fetchPhraseArray($notificationData)
	{
		$nodelink = vB5_Route::buildUrl('node|fullurl', array('nodeid' => $notificationData['sentbynodeid']));

		$phraseTitle = "missing phrase for " . __CLASS__;
		$phraseData = array();
		if (empty($notificationData['sender']) OR is_null($notificationData['sender_username']))
		{
			switch ($notificationData['otherParticipantsCount'])
			{
				case 0:
					$phraseTitle = 'guest_posted_on_subscription';
					$phraseData = array(
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				case 1:
					$phraseTitle = 'guest_and_one_other_posted_on_subscription';
					$phraseData = array(
						$notificationData['sentbynodeid'],
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				default:
					$phraseTitle = 'guest_and_y_others_posted_on_subscription';
					$phraseData = array(
						$notificationData['sentbynodeid'],
						$notificationData['otherParticipantsCount'],
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
			}
		}
		else
		{
			$userid = $notificationData['sender'];
			$username = $notificationData['sender_username'];
			$userInfo = array('userid' => $userid, 'username' => $username);
			try
			{
				$userProfileUrl = vB5_Route::buildUrl('profile|fullurl', $userInfo);
			}
			catch (Exception $e)
			{
				$userProfileUrl = "#";
			}
			switch ($notificationData['otherParticipantsCount'])
			{
				case 0:
					$phraseTitle = 'x_posted_on_subscription';
					$phraseData = array(
						$userProfileUrl,
						$username,
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				case 1:
					$phraseTitle = 'x_and_one_other_posted_on_subscription';
					$phraseData = array(
						$userProfileUrl,
						$username,
						$notificationData['sentbynodeid'],
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				default:
					$phraseTitle = 'x_and_y_others_posted_on_subscription';
					$phraseData = array(
						$userProfileUrl,
						$username,
						$notificationData['sentbynodeid'],
						$notificationData['otherParticipantsCount'],
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
			}
		}

		return array($phraseTitle, $phraseData);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
