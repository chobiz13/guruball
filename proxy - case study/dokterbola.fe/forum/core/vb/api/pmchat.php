<?php

class vB_Api_Pmchat extends vB_Api
{
	protected $disableWhiteList = array('canUsePMChat');

	/**
	 * Checks if the current user is logged in, and is in a usergruop that is allowed
	 * to use the chat system, and that the chat is enabled globally.
	 *
	 * @return array(bool, [string])
	 *             bool    'canuse'	   true if they can use the chat system
	 *             string  'reason'	   if 'canuse' is false, an accompanying reason why user is not allowed. Not set if 'canuse' is true.
	 */
	public function canUsePMChat()
	{
		// Must be logged into send messages.
		$currentUser = vB::getCurrentSession()->get('userid');
		if (empty($currentUser))
		{
			return array(
				'canuse' => false,
				'reason' => 'not_logged_no_permission',
			);
		}

		// Can they use the PM system?
		$canUsePmSystem = vB_Api::instanceInternal('content_privatemessage')->canUsePmSystem();
		if (empty($canUsePmSystem))
		{
			return array(
				'canuse' => false,
				'reason' => 'not_logged_no_permission',
			);
		}


		// Can they use the PM Chat system?
		$vboptions = vB::getDatastore()->getValue('options');
		$systemOnline = ($vboptions['pmchat_enabled']);
		if (!$systemOnline)
		{
			// todo: phrase
			return array(
				'canuse' => false,
				'reason' => 'System Disabled. Please ask the site administrator to check the "Enable PM Chat" setting under "Private Messaging Options".',
			);
		}

		$userGroupAuthorized = vb::getUserContext()->hasPermission('pmpermissions', 'canusepmchat');
		if (!$userGroupAuthorized)
		{
			// todo: phrase
			return array(
				'canuse' => false,
				'reason' => 'System Disabled. Please ask the site administrator to check the "Enable PM Chat" permission for your usergroup.',
			);
		}

		return array(
			'canuse' => true,
		);
	}

	/**
	 * Checks if current user is a recipient of $nodeid
	 *
	 * @param	int     $nodeid
	 *
	 * @return array(bool)
	 *             bool    'result'	   true if they are a recipient
	 */
	public function isMessageParticipant($nodeid)
	{
		$userid = vB::getCurrentSession()->get('userid');

		// Authors or recipients will have a sentto record.
		$check = vB::getDbAssertor()->getRow('vBForum:sentto', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'nodeid' => $nodeid, 'userid' => $userid));
		if (!empty($check) AND $check['userid'] == $userid AND $check['nodeid'] == $nodeid)
		{
			return array('result' => true);
		}

		return array('result' => false);
	}
}
