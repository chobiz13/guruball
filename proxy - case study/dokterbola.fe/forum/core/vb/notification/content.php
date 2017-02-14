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

abstract class vB_Notification_Content extends vB_Notification
{

	/*
	 * Generally you want to specify triggers at the lowest level so you can specify the priorities
	 * without conflicts at the subclass siblings. In contrast, you may want specify updateEvents
	 * at the highest level, as each subclass might not have special handling, and the logic for
	 * dismissing & delete might not differ at all.
	 */
	//protected static $triggers = array();

	/*
	 * String[] $updateEvents
	 *
	 * String array of events that should dismiss or delete the notification based on
	 * lookupid. Only valid for things that have a non-empty/non-null lookupid!
	 */
	protected static $updateEvents = array('read_topic', 'read_channel',
								'soft_deleted_node', 'physically_deleted_node',
								'deleted_user');

	/*
	 * Unique, string identifier of this notification subclass.
	 * Must be composed of alphanumeric or underscore characters: [A-Za-z0-9_]+
	 */
	const TYPENAME = 'Content';

	/**
	 * Validates the notification data, checks the context to see if we should send this
	 * notification type, and throws exceptions if we should not or cannot send this notification
	 * type. If all is okay, it may set additional notification data specific to this notification type.
	 *
	 * @param	Array	$notificationData
	 *
	 * @throws Exception()	If for some reason this notification type cannot be sent given
	 *						the context data in $notificationData
	 *
	 * @access protected
	 */
	protected function validateAndCleanNotificationData($notificationData)
	{
		$newData = parent::validateAndCleanNotificationData($notificationData);
		unset($notificationData);

		if (!isset($newData['sentbynodeid']))
		{
			throw new Exception("Missing Notification Data: sentbynodeid");
		}

		$nodeid = $newData['sentbynodeid'];
		$node = vB_Library::instance('node')->getNode($nodeid, false, true);	// we need to get the full content, to ensure 'channeltype' is there.
		if (!isset($node['nodeid']))
		{
			throw new Exception("Invalid Notification Data: sentbynodeid");
		}

		// Don't send notification if it's not visible to a "regular" user.
		if (!($node['showpublished'] AND $node['showapproved']))
		{
			throw new Exception("Invalid Notification Data: showpublished or showapproved");
		}


		// only explictly specified content types are allowed to send notifications
		$topLevelContentTypes = array(
			'Gallery' => 1,
			'Link' => 1,
			'Poll' => 1,
			'Text' => 1,
			'Video' => 1
		);
		$contenttypeclass = vB_Types::instance()->getContentTypeClass($node['contenttypeid']);
		if (!isset($topLevelContentTypes[$contenttypeclass]))
		{
			throw new Exception("Cannot send this notification for the node's content type.");
		}

		// Similar to above, but for channeltypes. Keep this in sync with vB_Channel::$channelTypes
		$allowedChannelTypes = array(
			'forum' => 1,
			'blog' => 1,
			'article' => 1,
			'group' => 1,
		);
		if (!isset($allowedChannelTypes[$node['channeltype']]))
		{
			throw new Exception("Cannot send this notification for the node's channel type.");
		}

		// We're good if we got to this point.
		$newData['sentbynodeid'] = (int) $node['nodeid'];

		if (!isset($node['userid']))
		{
			throw new Exception("Invalid Notification Data: sentbynodeid");
		}
		$newData['sender'] = (int) $node['userid'];

		return $newData;
	}

	/**
	 * If there is an existing notification for a recipient (judged by comparing the lookupid), this function
	 * guides whether the new notification should overwrite the old one. Coder may want to consider checking for
	 * $this->notificationData['trigger'] for a more complex handling for different triggers.
	 *
	 * @return	String		Must be 'always'|'if_read'|'never'. These values indicate, respectively, that the
	 *						new notification should always overwrite, only overwrite if the old one is read, or
	 *						never overwrite while the old one exists.
	 *
	 * @access protected
	 */
	protected function overwriteRule()
	{
		return 'if_read';
	}

	protected static function defineUnique($notificationData, $skipValidation)
	{
		return array();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
