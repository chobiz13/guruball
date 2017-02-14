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

class vB_Notification_Content_Quote extends vB_Notification_Content
{
	/*
	 * We use late static bindings in this class, and absolutely require PHP 5.3+
	 */

	/*
	 * Int[String] $triggers
	 *
	 * Array of  [key => value]  pairs of  [(string) {trigger} => (int) {priority}]
	 * Where {trigger} is the trigger string that should generate this type of notification,
	 * and {priority} is the lookupid conflict resolver: When multiple notification types
	 * generate the same lookupid on the same trigger, the type with the highest priority
	 * will overwrite the others for insertion.
	 * If any there are priority conflicts, behavior is undefined. Good luck.
	 */
	protected static $triggers = array(
		'new-content'	=> 20,
		//'updated-content'	=> 5,
	);

	/*
	 * Unique, string identifier of this notification subclass.
	 * Must be composed of alphanumeric or underscore characters: [A-Za-z0-9_]+
	 */
	const TYPENAME = 'Quote';

	protected function validateProvidedRecipients($recipients)
	{
		// Recipients for this type will always be set by analyzing the rawtext in addAdditionalRecipients()
		return array();
	}

	final protected static function defineUnique($notificationData, $skipValidation)
	{
		$nodeid = $notificationData['sentbynodeid'];

		// Similar to usermentions, each post will send out its own quote notification
		return array('nodeid' => (int) $nodeid);
	}

	protected function addAdditionalRecipients()
	{
		$nodeid = $this->notificationData['sentbynodeid'];
		$node = vB_Library::instance('node')->getNode($nodeid, false, true);	// we need the rawtext.
		$quotedUsers = array();
		if (isset($node['rawtext']))
		{
			// don't send a notification if the user mention is inside a [NOPARSE] tag.
			$find = array(
				#'#\[QUOTE[^\]]*\].*\[/QUOTE\]#siU',
				'#\[NOPARSE\].*\[/NOPARSE\]#siU',
			);
			$replace = '';
			$rawtext = preg_replace($find, $replace, $node['rawtext']);
			/*
			 *	Note, this currently has issues with names with closing brackets ] in them, VBV-14607
			 */
			if (preg_match_all('#\[QUOTE=(?<username>[^\]]+)(;(?<node>(n?\d+)))?\].*\[/QUOTE\]#siU', $rawtext, $matches))
			{
				/*
				 *	Fetch userids based on found usernames.
				 */
				if (!empty($matches['username']))
				{
					$fetchUsers = array();
					foreach ($matches['username'] AS $username)
					{
						/*
						 *	Testing indicates that ajax/fetch-quotes will escape the names for us.
						 *	If we expect the usernames to be raw unescaped names, similar to a username that
						 *	would come in through the user API login, use the the "raw" to "DB-expected"
						 *	conversion below, taken from vB_Api_User::login() & vB_User::verifyAuthentication()
						 *	respectively :
						#$username = vB_String::htmlSpecialCharsUni($username);
						#$username = vB_String::convertStringToCurrentCharset($username);
						 */
						$fetchUsers[$username] = $username;
					}
					$useridsQuery = vB::getDbAssertor()->getRows('user', array(
						vB_Db_Query::COLUMNS_KEY => array('userid', 'username'),
						'username' => $fetchUsers
					));

					foreach($useridsQuery AS $row)
					{
						$quotedUsers[$row['userid']] = $row['userid'];
					}
				}
			}
			unset($rawtext, $find, $replace, $matches);
		}
		return $quotedUsers;
	}

	protected function typeEnabledForUser($user)
	{
		static $bf_masks;
		if (empty($bf_masks))
		{
			$bf_masks = vB::getDatastore()->getValue('bf_misc_usernotificationoptions');
		}

		return ((bool) ($user['notification_options'] & $bf_masks['general_quote']));
	}

	/**
	 * @see vB_Notification::fetchPhraseArray()
	 */
	public static function fetchPhraseArray($notificationData)
	{
		$nodelink = vB5_Route::buildUrl('node|fullurl', array('nodeid' => $notificationData['sentbynodeid']));

		if (empty($notificationData['sender']) OR is_null($notificationData['sender_username']))
		{
			$phraseTitle = 'guest_quoted_you_in_post';
			$phraseData = array(
				$nodelink,
				$notificationData['aboutstartertitle']
			);
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

			$phraseTitle = 'x_quoted_you_in_post';
			$phraseData = array(
				$userProfileUrl,
				$username,
				$nodelink,
				$notificationData['aboutstartertitle']
			);
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
