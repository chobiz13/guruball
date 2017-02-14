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
 * vB_Api_Vb4_private
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_private extends vB_Api
{
	public function movepm($messageids, $folderid)
	{
		$cleaner = vB::getCleaner();
		$messageids = $cleaner->clean($messageids, vB_Cleaner::TYPE_STR);
		$folderid = $cleaner->clean($folderid, vB_Cleaner::TYPE_UINT);

		$folders = vB_Api::instance('content_privatemessage')->listFolders();

		if ($folders === null OR !empty($folders['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($folders);
		}


		if($folderid == -1)
		{
			$folderid = array_search('Sent Items', $folders);
		}
		else if($folderid == 0)
		{
			$folderid = array_search('Inbox', $folders);
		}

		if (empty($messageids) || empty($folderid))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$pm = unserialize($messageids);

		if (empty($pm))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}
		foreach ($pm as $pmid => $nothing)
		{
			$result = vB_Api::instance('content_privatemessage')->moveMessage($pmid, $folderid);
			if ($result === null || isset($result['errors']))
			{
				return vB_Library::instance('vb4_functions')->getErrorResponse($result);
			}
		}
		return array('response' => array('errormessage' => array('pm_messagesmoved')));
	}

	public function managepm($pm, $dowhat, $folderid = null)
	{
		$cleaner = vB::getCleaner();
		$pm = $cleaner->clean($pm, vB_Cleaner::TYPE_ARRAY);
		$dowhat = $cleaner->clean($dowhat, vB_Cleaner::TYPE_STR);
		$folderid = $cleaner->clean($folderid, vB_Cleaner::TYPE_UINT);

		$folders = vB_Api::instance('content_privatemessage')->listFolders();

		if ($folders === null OR !empty($folders['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($folders);
		}


		if($folderid == -1)
		{
			$folderid = array_search('Sent Items', $folders);
		}
		else if($folderid == 0)
		{
			$folderid = array_search('Inbox', $folders);
		}

		if (empty($pm) ||
			empty($dowhat))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		if ($dowhat == 'move')
		{
			if (empty($folderid))
			{
				return array('response' => array('errormessage' => array('invalidid')));
			}
			foreach ($pm as $pmid => $nothing)
			{
				$result = vB_Api::instance('content_privatemessage')->moveMessage($pmid, $folderid);
				if ($result === null || isset($result['errors']))
				{
					return vB_Library::instance('vb4_functions')->getErrorResponse($result);
				}
			}
			return array('response' => array('HTML' => array('messageids' => serialize($pm))));
		}
		else if ($dowhat == 'unread')
		{
			foreach ($pm as $pmid => $nothing)
			{
				$result = vB_Api::instance('content_privatemessage')->setRead($pmid, 0);
				if ($result === null || isset($result['errors']))
				{
					return vB_Library::instance('vb4_functions')->getErrorResponse($result);
				}
			}
			return array('response' => array('errormessage' => array('pm_messagesmarkedas')));
		}
		else if ($dowhat == 'read')
		{
			foreach ($pm as $pmid => $nothing)
			{
				$result = vB_Api::instance('content_privatemessage')->setRead($pmid, 1);
				if ($result === null || isset($result['errors']))
				{
					return vB_Library::instance('vb4_functions')->getErrorResponse($result);
				}
			}
			return array('response' => array('errormessage' => array('pm_messagesmarkedas')));
		}
		else if ($dowhat == 'delete')
		{
			foreach ($pm as $pmid => $nothing)
			{
				$result = vB_Api::instance('content_privatemessage')->deleteMessage($pmid);
				if (isset($result['errors']))
				{
					return vB_Library::instance('vb4_functions')->getErrorResponse($result);
				}
			}

			return array('response' => array('errormessage' => array('pm_messagesdeleted')));
		}
		else
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}
	}

	public function insertpm($message, $title, $recipients)
	{
		$cleaner = vB::getCleaner();
		$message = $cleaner->clean($message, vB_Cleaner::TYPE_STR);
		$title = $cleaner->clean($title, vB_Cleaner::TYPE_STR);
		$recipients = $cleaner->clean($recipients, vB_Cleaner::TYPE_STR);

		if (empty($message) ||
			empty($title) ||
			empty($recipients))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$recipients = implode(',', array_map('trim', explode(';', $recipients)));

		$data = array(
			'msgRecipients' => $recipients,
			'title' => $title,
			'rawtext' => $message,
		);

		$result = vB_Api::instance('content_privatemessage')->add($data, array('wysiwyg' => false));

		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}
		return array('response' => array('errormessage' => 'pm_messagesent'));
	}

	//VBV-11007
	public function sendemail($pmid, $reason){
		$cleaner = vB::getCleaner();
		$postid = $cleaner->clean($pmid, vB_Cleaner::TYPE_UINT);
		$reason = $cleaner->clean($reason, vB_Cleaner::TYPE_STR);

		if (empty($pmid))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		if (empty($reason))
		{
			return array('response' => array('errormessage' => array('invalidreason')));
		}

		return vB_Api::instance('vb4_report')->sendemail($pmid, $reason);
	}

	public function showpm($pmid)
	{
		$pm = vB_Api::instanceInternal('content_privatemessage')->getMessage($pmid);

		if(empty($pm))
		{
			return array("response" => array("errormessage" => array("invalidid")));
		}

		$pm_response = array();

		$recipients = $this->parseRecipients($pm);

		$pm_response['response']['HTML']['pm'] = array(
			'pmid' => $pmid,
			'fromusername' => $pm['message']['authorname'],
			'title' => vB_String::unHtmlSpecialChars($pm['message']['title'] ? $pm['message']['title'] : $pm['message']['previewtext']),
			'recipients' => $recipients,
		);

		$pm_response['response']['HTML']['postbit']['post'] = array(
			'posttime' => $pm['message']['publishdate'],
			'username' => $pm['message']['authorname'],
			'title' => vB_String::unHtmlSpecialChars($pm['message']['title'] ? $pm['message']['title'] : $pm['message']['previewtext']),
			'avatarurl' => !empty($pm['message']['senderAvatar']) ? $pm['message']['senderAvatar']['avatarpath'] : '',
			'message' => $this->parseBBCodeMessage($pm['message']),
			'message_plain' => strip_bbcode($pm['message']['rawtext']),
			'message_bbcode' => $pm['message']['rawtext'],
		);

		return $pm_response;
	}

	protected function parseRecipients($pm)
	{
		$pm = $pm['message'];
		if (!empty($pm['recipients']))
		{
			$recipients = array();
			foreach ($pm['recipients'] as $recipient)
			{
				$rinfo = vB_Library::instance('user')->fetchUserinfo($recipient['userid']);
				$recipients[] = $rinfo['username'];
			}
			return implode(';', $recipients);
		}
		else
		{
			return $pm['username'];
		}
	}

	public function editfolders()
	{
		$folders = vB_Api::instanceInternal('content_privatemessage')->fetchSummary();

		$custom_folders = array('response' => array('HTML' => array('editfolderbits' => array())));
		foreach($folders['folders']['customFolders'] as $folder)
		{
			$custom_folders['response']['HTML']['editfolderbits'][] = array(
				'folderid' => $folder['folderid'],
				'foldername' => $folder['title'],
				'foldertotal' => $folder['qty']
			);
		}

		return $custom_folders;
	}

	public function messagelist($folderid = 0, $perpage = 10, $pagenumber = 1, $sort = 'date', $order = 'desc')
	{
		//
		//  vB4 folders are:
		//      0   = Inbox
		//      -1  = Sent
		//      N   = Custom
		//

		$folders = vB_Api::instance('content_privatemessage')->listFolders();

		if ($folders === null OR !empty($folders['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($folders);
		}


		if($folderid == -1)
		{
			$folderid = array_search('Sent Items', $folders);
		}
		else if($folderid == 0)
		{
			$inbox = true;
			$folderid = array_search('Inbox', $folders);
		}

		$messages = vB_Api::instance('content_privatemessage')->listMessages(array(
			'folderid' => $folderid,
			'pageNum' => $pagenumber,
			'perpage' => $perpage,
			'sortDir' => $order
		));

		if ($messages === null OR !empty($messages['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($messages);
		}

		$final_messages = array();
		foreach($messages as $key => $message)
		{
			$final_messages[] = $this->parseMessage($message);
		}

		$page_info = vB_Api::instance('content_privatemessage')->getFolderMsgCount($folderid, $pagenumber, $perpage);
		$page_nav = vB_Library::instance('vb4_functions')->pageNav($pagenumber, $perpage, $page_info['count']);

		$response = array();
		$response['response']['HTML']['folderid'] = $inbox? 0 : $folderid;
		$response['response']['HTML']['pagenav'] = $page_nav;
		$response['response']['HTML']['messagelist_periodgroups']['messagelistbits'] = $final_messages;

		return $response;
	}

	private function parseMessage($message)
	{
		return array(
			'pm' => array(
				'pmid' => $message['nodeid'],
				'sendtime' => $message['publishdate'],
				'title' => vB_String::unHtmlSpecialChars($message['title'] ? $message['title'] : $message['previewtext']),
				'statusicon' => $message['msgread'] ? 'old' : 'new'
			),
			'userbit' => array(
				'userinfo' => array(
					'userid' => $message['userid'],
					'username' => $message['username']
				),
			),
			'show' => array(
				'unread' => $message['msgread'] ? 0 : 1
			)
		);
	}

	private function parseBBCodeMessage($message)
	{
		$this->bbcode_parser = new vB_Library_BbCode(true, true);
		$this->bbcode_parser->setAttachments($message['attach']);
		$this->bbcode_parser->setParseUserinfo($message['userid']);

		$authorContext = vB::getUserContext($message['userid']);

		$canusehtml = $authorContext->getChannelPermission('forumpermissions2', 'canusehtml', $message['parentid']);
		require_once DIR . '/includes/functions.php';

		return fetch_censored_text($this->bbcode_parser->doParse(
			$message['rawtext'],
			$canusehtml,
			true,
			true,
			$authorContext->getChannelPermission('forumpermissions', 'cangetattachment', $message['parentid']),
			true
		));
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84898 $
|| #######################################################################
\*=========================================================================*/
