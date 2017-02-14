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

class vB_Notification_Content_GroupByParentid_ThreadComment extends vB_Notification_Content_GroupByParentid
{

	protected static $triggers = array(
		'new-content'	=> 10,
		//'updated-content'	=> 10,

	);

	const TYPENAME = 'ThreadComment';

	protected function addAdditionalRecipients()
	{
		$nodeid = $this->notificationData['sentbynodeid'];
		$node = vB_Library::instance('node')->getNodeBare($nodeid);

		// If this is not a topic starter, and is not a reply, then it's a comment
		if (($node['nodeid'] != $node['starter']) AND ($node['parentid'] != $node['starter']))
		{
			$starter = vB_Library::instance('node')->getNodeBare($node['starter']);
			if (!empty($starter['userid']) AND $starter['userid'] != $this->notificationData['sender'])
			{
				return array($starter['userid']);
			}
		}

		return array();
	}

	protected function typeEnabledForUser($user)
	{
		static $bf_masks;
		if (empty($bf_masks))
		{
			$bf_masks = vB::getDatastore()->getValue('bf_misc_usernotificationoptions');
		}

		// The original mapping was taken from vB_Library_Privatemessage->userReceivesNotification()
		return ((bool) ($user['notification_options'] & $bf_masks['discussions_on']));
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
					$phraseTitle = 'guest_threadcommented_at_y';
					$phraseData = array(
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				case 1:
					$phraseTitle = 'guest_and_one_other_threadcommented_at_y';
					$phraseData = array(
						$notificationData['sentbynodeid'],
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				default:
					$phraseTitle = 'guest_and_y_others_threadcommented_at_y';
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
					$phraseTitle = 'x_threadcommented_at_y';
					$phraseData = array(
						$userProfileUrl,
						$username,
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				case 1:
					$phraseTitle = 'x_and_one_other_threadcommented_at_y';
					$phraseData = array(
						$userProfileUrl,
						$username,
						$notificationData['sentbynodeid'],
						$nodelink,
						$notificationData['aboutstartertitle']
					);
					break;
				default:
					$phraseTitle = 'x_and_y_others_threadcommented_at_y';
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
