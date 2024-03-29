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
 * vB_Api_Content_Privatemessage
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id: privatemessage.php 88967 2016-06-10 18:12:58Z jinsoojo.ib $
 * @access public
 */
class vB_Api_Content_Privatemessage extends vB_Api_Content_Text
{

	//override in client- the text name
	protected $contenttype = 'vBForum_PrivateMessage';
	//The table for the type-specific data.
	protected $tablename = array('text', 'privatemessage');
	protected $folders = array();
	protected $assertor;
	protected $pmChannel;
	//Cache our knowledge of records the current user can see, to streamline permission checking.
	protected $canSee = array();
	//these are the notification message types. Message and request are handled differently.
	//the parameter is whether they need an aboutid.
	protected $notificationTypes = array();
	protected $bbcodeOptions = array();

	protected $disableWhiteList = array('getUnreadInboxCount', 'canUsePmSystem', 'fetchSummary');

	const PARTICIPANTS_PM = 'PrivateMessage';
	const PARTICIPANTS_POLL = 'Poll';
	const PARTICIPANTS_CHANNEL = 'Channel';

	/**
	 * Constructor, no external instantiation.
	 */
	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('Content_Privatemessage');
		$userInfo = vB::getCurrentSession()->fetch_userinfo();
		if ($userInfo['userid'] > 0)
		{
			$this->library->checkFolders($userInfo['userid']);
			$this->pmChannel = $this->nodeApi->fetchPMChannel();
			$this->notificationTypes = $this->library->fetchNotificationTypes();
		}
	}

	/**
	 * Private messaging can be disabled either by pmquota or enablepms
	 *
	 * @return bool True if the current user can use the PM system, false otherwise
	 */
	public function canUsePmSystem()
	{
		$pmquota = vB::getUserContext()->getLimit('pmquota');
		$vboptions = vB::getDatastore()->getValue('options');
		$userid = intval(vB::getCurrentSession()->get('userid'));

		if (!$userid OR !$pmquota OR !$vboptions['enablepms'])
		{
			return false;
		}

		return true;
	}

	/**
	 * Adds a new private message
	 *
	 * @param  mixed must include 'sentto', 'contenttypeid', and the necessary data for that contenttype.
	 * @param  array Array of options for the content being created.
	 *               Understands skipTransaction, skipFloodCheck, floodchecktime, skipDupCheck, skipNotification,
	 *               nl2br, autoparselinks, skipNonExistentRecipients.
	 *               - nl2br: if TRUE, all \n will be converted to <br /> so that it's not removed by the html parser (e.g. comments).
	 *               - skipNonExistentRecipients (bool) skips recipients that don't exist instead of throwing an exception.
	 *               - wysiwyg: if true convert html to bbcode.  Defaults to true if not given.
	 *
	 * @return int   the new nodeid.
	 */
	public function add($data, $options = array())
	{
		$vboptions = vB::getDatastore()->getValue('options');
		if (!empty($data['title']))
		{
			$strlen = vB_String::vbStrlen(trim($data['title']), true);
			if ($strlen > $vboptions['titlemaxchars'])
			{
				throw new vB_Exception_Api('maxchars_exceeded_x_title_y', array($vboptions['titlemaxchars'], $strlen));
			}
		}

		//If this is a response, we have a "respondto" = nodeid
		//If it's a forward, we set "forward" = nodeid
		$userInfo = vB::getCurrentSession()->fetch_userinfo();
		$sender = intval($userInfo['userid']);

		if (!intval($sender) OR !$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		if (!$userInfo['receivepm'])
		{
			throw new vB_Exception_Api('pm_turnedoff');
		}

		$pmquota = vB::getUserContext()->getLimit('pmquota');

		if ($userInfo['pmtotal'] >= $pmquota)
		{
			throw new vB_Exception_Api('yourpmquotaexceeded', array($pmquota, $userInfo['pmtotal']));
		}

		$data['sender'] = $sender;
		$recipientNames = 0;
		//check if the user from the usergroup can send the pm to the number of recipients
		$pmsendmax = vB::getUserContext()->getLimit('pmsendmax');
		if (!empty($data['msgRecipients']))
		{
			$recipientNames = count(explode(',', $data['msgRecipients']));
		}
		else if (!empty($data['sentto']))
		{
			$recipientNames = count($data['sentto']);
		}
		if ($pmsendmax > 0 AND $recipientNames > $pmsendmax)
		{
			throw new vB_Exception_Api('pmtoomanyrecipients', array($recipientNames, $pmsendmax));
		}

		if (!empty($data['pagetext']))
		{
			$strlen = vB_String::vbStrlen($this->library->parseAndStrip($data['pagetext']), true);
			if ($strlen < $vboptions['postminchars'])
			{
				throw new vB_Exception_Api('please_enter_message_x_chars', $vboptions['postminchars']);
			}
			if ($vboptions['postmaxchars'] != 0 AND $strlen > $vboptions['postmaxchars'])
			{
				throw new vB_Exception_Api('maxchars_exceeded_x_y', array($vboptions['postmaxchars'], $strlen));
			}
		}
		else if (!empty($data['rawtext']))
		{
			$strlen = vB_String::vbStrlen($this->library->parseAndStrip($data['rawtext']), true);
			if ($strlen < $vboptions['postminchars'])
			{
				throw new vB_Exception_Api('please_enter_message_x_chars', $vboptions['postminchars']);
			}
			if ($vboptions['postmaxchars'] != 0 AND $strlen > $vboptions['postmaxchars'])
			{
				throw new vB_Exception_Api('maxchars_exceeded_x_y', array($vboptions['postmaxchars'], $strlen));
			}
		}
		else
		{
			throw new vB_Exception_Api('invalid_data');
		}

		if (empty($data['parentid']))
		{
			$data['parentid'] = $this->pmChannel;
		}

		if (!$this->validate($data, vB_Api_Content::ACTION_ADD))
		{
			throw new vB_Exception_Api('no_create_permissions');
		}

		if (isset($data['respondto']))
		{
			if (!empty($data['respondto']))
			{
				//if we don't have access to see a node we can't respond to it.
				//this call with throw an exception if we don't have permission to load it.
				$this->nodeApi->getNode($data['respondto']);
			}
			else
			{
				unset($data['respondto']);
			}
		}

		$this->cleanInput($data);
		$this->cleanOptions($options);

		$wysiwyg = true;
		if(isset($options['wysiwyg']))
		{
			$wysiwyg = (bool) $options['wysiwyg'];
		}

		//If this is a response, we have a "respondto" = nodeid
		$result = $this->library->add($data, $options, $wysiwyg);

		return $result['nodeid'];
	}

	/**
	 * Permanently deletes a message
	 *
	 * @param  int  nodeid of the entry to be deleted.
	 *
	 * @return bool did the deletion succeed?
	 */
	public function deleteMessage($nodeid)
	{
		//We need a copy of the existing node.
		$content = $this->nodeApi->getNode($nodeid);

		if (empty($content) OR !empty($content['error']))
		{
			throw new vB_Exception_Api('invalid_data');
		}
		$currentUser = vB::getCurrentSession()->get('userid');

		if (!intval($currentUser) OR !$this->validate($content, vB_Api_Content::ACTION_DELETE, $nodeid))
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		return $this->library->deleteMessage($nodeid, $currentUser);
	}

	/**
	 * Deletes all pms for a given user
	 *
	 * This will mark all "sentto" records for the given user as deleted.
	 * In addtion it will mark any PM records for deletion that no longer have
	 * any users attached to them.  The actual deletion is handled via cron script.
	 *
	 * The requested user must much the current user or the current use have the 'canadminusers' permission
	 *
	 * @param  int              $userid
	 *
	 * @return array
	 *                          -- int count number of items marked for delete (for the user, the pm itself might be referenced
	 *                          by another user and therefore still around)
	 *
	 * @throws vB_Exception_Api
	 *                          -- invalid_data_w_x_y_z when userid is not valid
	 *                          -- not_logged_no_permission user is not an admin and does not have permission to use pm system
	 */
	public function deleteMessagesForUser($userid)
	{
		$userid = intval($userid);
		if ($userid <= 0)
		{
			throw new vB_Exception_Api('invalid_data_w_x_y_z', array($userid, 'userid', __CLASS__, __FUNCTION__));
		}

		if (vB::getCurrentSession()->get('userid') != $userid)
		{
			$this->checkHasAdminPermission('canadminusers');
		}
		else
		{
			//don't use canUsePmSystem here because that validates against the current
			//logged in user and we want to allow admins to use this function for other users
			//we should eventually fix the entire library to work that way.
			$pmquota = vB::getUserContext()->getLimit('pmquota');
			$vboptions = vB::getDatastore()->getValue('options');

			if (!$pmquota OR !$vboptions['enablepms'])
			{
				throw new vB_Exception_Api('not_logged_no_permission');
			}
		}

		$count = $this->library->deleteMessagesForUser($userid);

		return array('deleted' => $count);
	}

	/**
	 * Moves a message to a different folder
	 *
	 * @param  int  the node to be moved
	 * @param  int  the new parent node.
	 *
	 * @return bool did it succeed?
	 */
	public function moveMessage($nodeid, $newFolderid = false)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		$userInfo = vB::getCurrentSession()->fetch_userinfo();
		$currentUser = $userInfo['userid'];

		if (!intval($currentUser))
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		$nodeids = explode(',', $nodeid);

		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}

		// if it's not message we can't move
		$pmRec = $this->assertor->getRows('vBForum:privatemessage', array(
			'nodeid' => $nodeids
		));

		foreach ($pmRec AS $node)
		{
			if ($node['msgtype'] != 'message')
			{
				throw new vB_Exception_Api('no_move_permission_x', array($node['nodeid']));
			}
		}

		//we can only move a record to which the user has access.
		$this->library->checkFolders($currentUser);
		$folders = $this->library->fetchFolders($currentUser);
		$sentFolder = $folders['systemfolders'][vB_Library_Content_Privatemessage::SENT_FOLDER];

		if (
			in_array($newFolderid, $folders['systemfolders'])
			AND !in_array($newFolderid, array(
				$folders['systemfolders'][vB_Library_Content_Privatemessage::MESSAGE_FOLDER],
				$folders['systemfolders'][vB_Library_Content_Privatemessage::TRASH_FOLDER]
			))
		)
		{
			throw new vB_Exception_Api('invalid_move_folder');
		}
		$conditions = array(
			array('field' => 'userid', 'value' => $currentUser),
			array('field' => 'nodeid', 'value' => $nodeids)
		);
		// allow deleting sent items
		if ($newFolderid != $folders['systemfolders'][vB_Library_Content_Privatemessage::TRASH_FOLDER])
		{
			$conditions[] = array('field' => 'folderid', 'value' => $sentFolder, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_NE);
		}

		$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, vB_dB_Query::CONDITIONS_KEY => $conditions);
		$existing = $this->assertor->getRows('vBForum:sentto', $data);

		if (empty($existing) OR !empty($existing['errors']))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		return $this->library->moveMessage($nodeid, $newFolderid, $existing);
	}

	/**
	 * Gets a message
	 *
	 * @param  int   The Node ID
	 *
	 * @return mixed Array of data
	 */
	public function getMessage($nodeid)
	{
		$content = $this->nodeApi->getNode($nodeid);

		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		//if this is the author we can return the value
		if ($content['userid'] == $userid)
		{
			return $this->library->getMessage($nodeid);
		}

		//Maybe this is a recipient.
		$recipients = $this->assertor->getRows('vBForum:sentto', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'nodeid' => $nodeid));
		foreach ($recipients as $recipient)
		{
			if ($recipient['userid'] == $userid)
			{
				return $this->library->getMessage($nodeid);
			}
		}

		//If we got here, this user isn't authorized to see this record. Well, it's also possible this may not exist.
		throw new vB_Exception_Api('no_permission');
	}

	/**
	 * Get a single request
	 *
	 * @param  int   the nodeid
	 *
	 * @return array The node data array for the request
	 */
	public function getRequest($nodeid)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		$content = $this->nodeApi->getNodeContent($nodeid);

		//getNodeContent returns a list.
		$content = $content[$nodeid];

		//if this is the author we can return the value
		if ($content['userid'] == $userid)
		{
			return $content;
		}
		else
		{
			//Maybe this is a recipient.
			$recipients = $this->assertor->getRows('vBForum:sentto', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'nodeid' => $nodeid));
			$canshow = false;
			foreach ($recipients as $recipient)
			{
				if ($recipient['userid'] == $userid)
				{
					return $content;
				}
			}
		}

		//If we got here, this user isn't authorized to see this record. Well, it's also possible this may not exist.
		throw new vB_Exception_Api('no_permission');
	}

	/**
	 * Lists the folders.
	 *
	 * @param  mixed array of system folders to be hidden. like vB_Library_Content_Privatemessage::MESSAGE_FOLDER
	 *
	 * @return mixed array of folderid => title
	 */
	public function listFolders($suppress = array())
	{
		return $this->library->listFolders($suppress);
	}

	/**
	 * Creates a new message folder. It returns false if the record already exists and the id if it is able to create the folder
	 *
	 * @return int
	 */
	public function createMessageFolder($folderName)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		return $this->library->createMessageFolder($folderName, $userid);
	}

	/**
	 * Moves a node to the trashcan. Wrapper for deleteMessage()
	 *
	 * @param int
	 */
	public function toTrashcan($nodeid)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		$this->library->checkFolders($userid);
		$folders = $this->library->fetchFolders($userid);

		return $this->moveMessage($nodeid, $folders['systemfolders'][vB_Library_Content_Privatemessage::TRASH_FOLDER]);
	}

	/**
	 * Returns a summary of messages for current user
	 *
	 * @return array Array of information including:
	 *               folderId, title, quantity not read.
	 */
	public function fetchSummary()
	{
		$userid = vB::getCurrentSession()->get('userid');

		if (!intval($userid))
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		return $this->library->fetchSummary($userid);
	}

	/**
	 * Lists messages for current user
	 *
	 *	@param array $data-
	 *		'sortDir'
	 *		'pageNum'
	 *		'perpage'
	 *		'folderid'
	 *		'showdeleted'
	 *		'ignoreRecipients'
	 *	@return	array - list of messages.
	 */
	public function listMessages($data = array())
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		return $this->library->listMessages($data, $userid);
	}

	/**
	 * Lists notifications for current user
	 *
	 * @deprecated 5.1.6  Only used by unit tests
	 *
	 * @param      mixed- can pass sort direction, type, page, perpage
	 *
	 * @return     Array  @see vB_Library_Notification::fetchNotificationsforCurrentUser()
	 */
	public function listNotifications($data = array())
	{
		// TODO: remove this function. Do not simply deprecate since
		// notification API returns different data and isn't compatible.
		// This stopgap is meant for internal calls only!!
		if (!isset($data['readFilter']))
		{
			// Really only used by unit tests nowadays, but the 'read' status is a new concept and wasn't around back when
			// notifications were part of the PM code. So the default when going through PM API would be "both" not "unread_only"
			$data['readFilter'] = 'both';
		}
		$notifications = vB_Api::instanceInternal('notification')->fetchNotificationsForCurrentUser($data);
		return $notifications;

	}

	/**
	 * Lists messages for current user
	 *
	 * @param  mixed- can pass sort direction, type, page, perpage, or folderid.
	 *
	 * @return mixed  - array-includes folderId, title, quantity not read. Also 'page' is array of node records for page 1.
	 */
	protected function listSpecialPrivateMessages($data = array())
	{
		$userid = vB::getCurrentSession()->get('userid');
		if (!intval($userid))
		{
			return false;
		}

		return $this->library->listSpecialPrivateMessages($data);
	}

	/**
	 * Lists messages for current user
	 *
	 * @param  mixed- can pass sort direction, type, page, perpage, or folderid.
	 *
	 * @return mixed  - array-includes folderId, title, quantity not read. Also 'page' is array of node records for page 1.
	 */
	public function listRequests($data = array())
	{
		$userid = vB::getCurrentSession()->get('userid');

		if (!intval($userid))
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$this->library->checkFolders($userid);

		$folders = $this->library->fetchFolders($userid);
		$data['folderid'] = $folders['systemfolders'][vB_Library_Content_Privatemessage::REQUEST_FOLDER];
		$data['userid'] = $userid;

		$requests = $this->listSpecialPrivateMessages($data);

		//We need blog info

		$channelRequests = $this->library->getChannelRequestTypes();

		$channels = array();

		if (!empty($requests))
		{
			foreach ($requests as $key => &$request)
			{
				//if it's a channel request we need the channel title
				if (in_array($request['about'], $channelRequests))
				{
					$channels[] = $request['aboutid'];
				}

				/* construct phrase name.  Be sure to create the new
				 * phrases when new requests are added! Also add any new channel requests to
				 * library\content\privatemessage's $channelRequests array.
				 * Channel requests: received_<about string>_request_from_x_link_y_<to/for>_channel_z
				 * Other requests: received_<about string>_request_from_x_link_y
				 * If the about string is equal to another request's about string after stripping sg_ and _to, the same phrase will be used.
				 * */
				$cleanAboutStr = preg_replace('/(^sg_)?|(_to$)?/', '', $request['about']);
				$request['phrasename'] = 'received_' . $cleanAboutStr . '_request_from_x_link_y';
			}
		}

		//If we have some channel info to get let's do it now.
		if (!empty($channels))
		{
			$channelInfo = vB_Api::instanceInternal('node')->getNodes($channels);

			foreach ($channelInfo AS $channel)
			{
				foreach ($requests as $key => &$request)
				{
					if ($request['aboutid'] == $channel['nodeid'])
					{
						$request['abouttitle'] = $channel['title'];
						$request['aboutrouteid'] = $channel['routeid'];

						// if it's a channel request, and has a title & url, the phrase name
						// should have a "_to_channel_z" (take request) or "_for_channel_z" (grant request) appended
						if(strpos($request['about'], '_to') !== false)
						{
							$request['phrasename'] .= '_to_channel_z';
						}
						else
						{
							$request['phrasename'] .= '_for_channel_z';
						}
					}
				}
			}
		}

		return $requests;
	}

	/**
	 * Returns an array with bbcode options for PMs
	 *
	 * @return array Options
	 */
	public function getBbcodeOptions($nodeid = 0)
	{
		if (!$this->bbcodeOptions)
		{
			// all pm nodes have the same options
			$response = Api_InterfaceAbstract::instance()->callApi('bbcode', 'initInfo');
			$this->bbcodeOptions = $response['defaultOptions']['privatemessage'];
		}
		return $this->bbcodeOptions;
	}

	/**
	 * Gets the count of undeleted messages in a folder
	 *
	 * @param  int    $folderid the folderid to search
	 * @param  int    $pageNum
	 * @param  int    $perpage
	 * @param  String $about Optional "about" string
	 * @param  Array  $filterParams Optional filter parameters, only used for notifications.
	 *                See vB_Library_Notification::fetchNotificationsForCurrentUser()
	 *                - 'sortDir'
	 *                - 'perpage'
	 *                - 'page'
	 *                - 'showdetail'
	 *                - 'about'
	 *
	 * @return Array  the count & page data, including: 'count', 'pages' (total pages), 'pagenum' (selected page #), 'nextpage', 'prevpage'
	 */
	public function getFolderMsgCount($folderid, $pageNum = 1, $perpage = 50, $about = false, $filterParams = false)
	{
		$userid = vB::getCurrentSession()->get('userid');

		if (!intval($userid))
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		$this->library->checkFolders($userid);
		$folders = $this->library->fetchFolders($userid);

		$notificationFolderid = $folders['systemfolders'][vB_Library_Content_Privatemessage::NOTIFICATION_FOLDER];

		if (!array_key_exists($folderid, $folders['folders']))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		if ($folderid == $notificationFolderid)
		{
			$qty = vB_Library::instance('notification')->fetchNotificationCountForUser($userid, $filterParams);

			if (isset($filterParams['page']))
			{
				$pageNum = $filterParams['page'];
			}

			if (isset($filterParams['perpage']))
			{
				$perpage = $filterParams['perpage'];
			}
		}
		else
		{
			// @TODO improve the queries to return the count already to avoid using count() from rows
			if (empty($about))
			{
				$result = $this->assertor->getRows('vBForum:getMsgCountInFolder', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
					'folderid' => $folderid));
			}
			else
			{
				$result = $this->assertor->getRows('vBForum:getMsgCountInFolderAbout', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
					'userid' => $userid, 'folderid' => $folderid, 'about' => $about));
			}


			if (empty($result) OR !empty($result['errors']))
			{
				$qty = 0;
			}
			else
			{
				$qty = count($result);
			}
		}

		if (empty($perpage))
		{
			$pagecount = ceil($qty / 50);
		}
		else
		{
			$pagecount = ceil($qty / $perpage);
		}

		if ($pageNum > 1)
		{
			$prevpage = $pageNum - 1;
		}
		else
		{
			$prevpage = false;
		}

		if ($pageNum < $pagecount)
		{
			$nextpage = $pageNum + 1;
		}
		else
		{
			$nextpage = false;
		}

		return array('count' => $qty, 'pages' => $pagecount, 'pagenum' => $pageNum, 'nextpage' => $nextpage, 'prevpage' => $prevpage);
	}

	/**
	 * Gets the count of undeleted messages & notifications
	 *
	 * @param  int the folderid to search
	 *
	 * @return int the count
	 */
	public function getUnreadInboxCount()
	{
		$userid = vB::getCurrentSession()->get('userid');

		if (!intval($userid))
		{
			return 0;
		}

		$this->library->checkFolders($userid);

		if ($this->canUsePmSystem())
		{
			$result = $this->assertor->getRow('vBForum:getUnreadMsgCount', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED, 'userid' => $userid));
		}
		else
		{
			$result = $this->assertor->getRow('vBForum:getUnreadSystemMsgCount', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED, 'userid' => $userid));
		}

		if (empty($result) OR !empty($result['errors']))
		{
			return 0;
		}

		$reportsCount = $this->getOpenReportsCount();
		$notificationsCount = vB_Library::instance('notification')->fetchNotificationCountForUser($userid, array('readFilter' => 'unread_only'));
		$total = $result['qty'] + $reportsCount + $notificationsCount;


		return $total;
	}

	/**
	 * Gets the count of undeleted privatemessages, requests, notifications & reports
	 *
	 * @return array
	 *                int  'messages'             private messages
	 *                int  'requests'
	 *                int  'notifications'
	 *                int  'pending_posts'
	 *                int  'reports'
	 *                bool 'canviewreports'        if the "reports" should be displayed.
	 *                int  'nonpms_sum'            sum of the int counts minus 'messages' count
	 *                int  'folderid_messages'
	 */
	public function getHeaderCounts()
	{
		/*
			TODO: How are 'pending_posts' sentto records created??
		 */
		$result = array('messages' => 0, 'requests' => 0, 'notifications' => 0, 'pending_posts' => 0, 'reports' => 0, 'canviewreports' => false,  'nonpms_sum' => 0, 'folderid_messages' => 0);

		$userid = vB::getCurrentSession()->get('userid');

		if (!intval($userid))
		{
			return $result;
		}

		$this->library->checkFolders($userid);


		$queryResult = $this->assertor->getRows('vBForum:getHeaderMsgCount', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED, 'userid' => $userid));
		foreach ($queryResult AS $row)
		{
			$result[$row['folder']] = $row['qty'];
			if ($row['folder'] == 'messages')
			{
				$result['folderid_' . $row['folder']] = $row['folderid'];
			}
		}

		if (!$this->canUsePmSystem())
		{
			$result['messages'] = 0;
		}

		$canViewReports = vB::getUserContext()->getChannelPermission('forumpermissions', 'canview', vB_Api::instanceInternal('node')->fetchReportChannel());
		if ($canViewReports)
		{
			$result['canviewreports'] = true;
			$result['reports'] = $this->getOpenReportsCount();
		}
		else
		{
			$result['canviewreports'] = false;
			$result['reports'] = 0;
		}

		$result['notifications'] = vB_Library::instance('notification')->fetchNotificationCountForUser($userid, array('readFilter' => 'unread_only'));
		$result['nonpms_sum'] = $result['requests'] + $result['notifications'] + $result['pending_posts'] + $result['reports'];

		// If there were no new messages, this may not be set correctly.
		if (empty($result['folderid_messages']))
		{
			// messagefolder AS f ON f.folderid = s.folderid AND f.titlephrase IN
			$qry = $this->assertor->getRow('vBForum:messagefolder', array('userid' => $userid, 'titlephrase' => array('messages')));
			if (!empty($qry['folderid']))
			{
				$result['folderid_messages'] = $qry['folderid'];
			}

		}

		return $result;
	}

	/**
	 * Gets the count of open reports
	 *
	 * @return int the count of open reports
	 */
	public function getOpenReportsCount()
	{
		if (vB::getUserContext()->getChannelPermission('forumpermissions', 'canview', vB_Api::instanceInternal('node')->fetchReportChannel()))
		{
			$result = $this->assertor->getRow('vBForum:getOpenReportsCount');
			return $result['qty'];
		}
		else
		{
			// they cannot view reports. return 0
			return 0;
		}
	}

	/**
	 * Gets the preview for the messages
	 *
	 * @return mixed array of record-up to five each messages, then requests, then notifications
	 */
	public function previewMessages()
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		$this->library->checkFolders($userid);
		$folders = $this->library->fetchFolders($userid);
		$exclude = array($folders['systemfolders'][vB_Library_Content_Privatemessage::TRASH_FOLDER],
			$folders['systemfolders'][vB_Library_Content_Privatemessage::NOTIFICATION_FOLDER]);
		$lastnodeidsQry = $this->assertor->getRows('vBForum:lastNodeids', array('userid' => $userid, 'excludeFolders' => $exclude));
		// since the above query might not return anything, if there are no privatemessages for the user, add a -1 to prevent
		// the qryResults query from breaking
		$lastnodeids = array(-1);
		foreach ($lastnodeidsQry AS $lastnode)
		{
			$lastnodeids[] = $lastnode['nodeid'];
		}
		$ignoreUsersQry = $this->assertor->getRows('vBForum:getIgnoredUserids', array('userid' => $userid));
		$ignoreUsers = array(-1);
		foreach ($ignoreUsersQry as $ignoreUser)
		{
			$ignoreUsers[] = $ignoreUser['userid'];
		}
		$qryResults = $this->assertor->assertQuery('vBForum:pmPreview', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'userid' => $userid,
			'ignoreUsers' => $ignoreUsers,
			'excludeFolders' => $exclude,
			'nodeids' => $lastnodeids,
		));
		/*
		TODO: the 'title' fields used to be used by privatemessage_foldersummary template. They were used in a way that made translations difficult,
		so that template no longer relies on it, but other callers might use it, so I haven't removed 'title' yet.
		At some point we should check for the dependency and remove 'title', as they'd be unnecessary fetchSinglePhrase() calls.
		 */
		$results = array(
			'message' => array(
				'count' => 0,
				'title'=> vB_Phrase::fetchSinglePhrase('messages'),
				'folderid' => 0,
				'messages' => array(),
				'phrase_titles' => array(
					'preview' => 'inbox_preview',
					'see_all' => 'see_all_inbox',
				),
			),
			'request' => array(
				'count' => 0,
				'title'=> vB_Phrase::fetchSinglePhrase('requests'),
				'folderid' => 0,
				'messages' => array(),
				'phrase_titles' => array(
					'preview' => 'requests_preview',
					'see_all' => 'see_all_requests',
				),
			),
			'notification' => array(
				'count' => 0,
				'title'=> vB_Phrase::fetchSinglePhrase('notifications'),
				'folderid' => 0,
				'messages' => array(),
				'phrase_titles' => array(
					'preview' => 'notifications_preview',
					'see_all' => 'see_all_notifications',
				),
			),
		);
		$messageIds = array();
		$nodeIds = array();
		$userIds = array();
		$userApi = vB_Api::instanceInternal('user');
		$receiptDetail = vB::getUserContext()->hasPermission('genericpermissions', 'canseewholiked');

		$needLast = array();
		if ($qryResults->valid())
		{
			foreach ($qryResults AS $result)
			{
				if (empty($result['previewtext']))
				{
					$result['previewtext'] = vB_String::getPreviewText($result['rawtext']);
				}

				if ($result['titlephrase'] == 'messages')
				{
					$messageIds[] = $result['nodeid'];
				}
				else
				{
					$nodeIds[] = $result['nodeid'];
				}

				// privatemessage_requestdetail template requires you to pass back the phrase name for requests.
				// See listRequests() for more details
				if($result['msgtype'] == 'request')
				{
					// remove starting sg_ and ending _to from the about string
					$cleanAboutStr = preg_replace('/(^sg_)?|(_to$)?/', '', $result['about']);
					$result['phrasename'] = 'received_' . $cleanAboutStr . '_request_from_x_link_y';

					// grab channel request types
					$channelRequests = $this->library->getChannelRequestTypes();

					// append correct suffix for channel requests
					if(in_array($result['about'], $channelRequests))
					{
						// should have a "_to_channel_z" (take request) or "_for_channel_z" (grant request) appended
						if(strpos($result['about'], '_to') !== false)
						{
							$result['phrasename'] .= '_to_channel_z';
						}
						else
						{
							$result['phrasename'] .= '_for_channel_z';
						}
					}
				}

				$result['senderAvatar'] = $userApi->fetchAvatar($result['userid']);
				$result['recipients'] = array();
				$result['otherRecipients'] = 0;
				$result['responded'] = 0;
				$results[$result['msgtype']]['messages'][$result['nodeid']] = $result;
				$results[$result['msgtype']]['count']++;
				$userIds[] = $result['userid'];


				if (intval($result['lastauthorid']))
				{
					$userIds[] = $result['lastauthorid'];
				}
				if (!$results[$result['msgtype']]['folderid'])
				{
					$results[$result['msgtype']]['folderid'] = $result['folderid'];
				}

				// set recipients needed
				if ($result['msgtype'] == 'message')
				{
					if (empty($result['lastauthorid']) OR $result['lastauthorid'] == $userid)
					{
						$needLast[] = $result['nodeid'];
					}
				}
			}

			// @TODO check for a way to implement a generic protected library method to fetch recipients instead of cloning code through methods.
			// drag the needed info
			if (!empty($needLast))
			{
				$needLast = array_unique($needLast);
				$neededUsernames = $this->assertor->assertQuery('vBForum:getPMLastAuthor', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED, 'nodeid' => $needLast, 'userid' => $userid));
				foreach ($neededUsernames AS $username)
				{
					if (isset($results['message']['messages'][$username['nodeid']]))
					{
						$results['message']['messages'][$username['nodeid']]['lastcontentauthor'] = $username['username'];
						$results['message']['messages'][$username['nodeid']]['lastauthorid'] = $username['userid'];
					}
				}
			}

			//Now we need to sort out the other recipients for this message.
			$recipients = array();
			if (!empty($nodeIds))
			{
				$recipientQry = $this->assertor->assertQuery('vBForum:getPMRecipients', array(
					'nodeid' => array_unique($nodeIds), 'userid' => $userid));
				foreach ($recipientQry as $recipient)
				{
					$recipients[$recipient['nodeid']][$recipient['userid']] = $recipient;
				}
			}

			$messageRecipients = array();
			if (!empty($messageIds))
			{
				$recipientsInfo = $this->assertor->assertQuery('vBForum:getPMRecipientsForMessage', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
					'nodeid' => $messageIds
				));

				$recipients = array();
				if (!empty($recipientsInfo))
				{
					foreach ($recipientsInfo AS $recipient)
					{
						if (isset($results['message']['messages'][$recipient['starter']]))
						{
							if (($recipient['userid'] == $userid))
							{
								if (empty($results['message']['messages'][$recipient['starter']]['included']))
								{
									$results['message']['messages'][$recipient['starter']]['included'] = true;
								}

								continue;
							}
							else if ($results['message']['messages'][$recipient['starter']]['lastcontentauthor'] == $recipient['username'])
							{
								continue;
							}

							if (!isset($results['message']['messages'][$recipient['starter']]['recipients'][$recipient['userid']]))
							{
								$results['message']['messages'][$recipient['starter']]['recipients'][$recipient['userid']] = $recipient;
							}
						}
					}
				}
			}

			//Collect the user info. Doing it this way we get a lot of info in one query.
			$userQuery = $this->assertor->assertQuery('user', array('userid' => array_unique($userIds)));
			$userInfo = array();
			$userApi = vB_Api::instanceInternal('user');
			foreach ($userQuery AS $userRecord)
			{
				//some information we shouldn't pass along.
				foreach (array('token', 'scheme', 'secret', 'coppauser', 'securitytoken_raw', 'securitytoken', 'logouthash', 'fbaccesstoken',
					'passworddate', 'parentemail', 'logintype', 'ipaddress', 'passworddate',
					'referrerid', 'ipoints', 'infractions', 'warnings', 'infractiongroupids', 'infractiongroupid',
				) AS $field)
				{
					unset($userRecord[$field]);
				}

				$userRecord['avatar'] = $userApi->fetchAvatar($userRecord['userid'], true, $userRecord);
				$userInfo[$userRecord['userid']] = $userRecord;
			}

			//Now we need to scan the results list and assign the other recipients.
			foreach ($results AS $key => $folder)
			{
				foreach ($folder['messages'] AS $msgkey => $message)
				{
					if ($message['titlephrase'] == 'messages')
					{
						// set the first recipient
						if (!empty($message['lastcontentauthor']) AND !empty($message['lastauthorid']) AND ($message['lastauthorid'] != $userid))
						{
							$results[$key]['messages'][$msgkey]['firstrecipient'] = array(
								'userid' => $message['lastauthorid'],
								'username' => $message['lastcontentauthor']
							);
						}
						else if (!empty($message['recipients']))
						{
							$firstrecip = reset($message['recipients']);
							$results[$key]['messages'][$msgkey]['firstrecipient'] = $firstrecip;
							unset($results[$key]['messages'][$msgkey]['recipients'][$firstrecip['userid']]);
						}

						$results[$key]['messages'][$msgkey]['otherRecipients'] = count($results[$key]['messages'][$msgkey]['recipients']);
					}
					else
					{
						if (!empty($recipients[$message['nodeid']]))
						{
							$results[$key]['messages'][$msgkey]['recipients'] = $recipients[$message['nodeid']];
							$results[$key]['messages'][$msgkey]['otherRecipients'] = count($recipients[$message['nodeid']]);
							$results[$key]['messages'][$msgkey]['userinfo'] = $userInfo[$message['userid']];
						}
					}

					if ($message['lastauthorid'])
					{
						$results[$key]['messages'][$msgkey]['lastauthor'] = $userInfo[$message['lastauthorid']]['username'];
						$results[$key]['messages'][$msgkey]['lastcontentauthorid'] = $message['lastauthorid'];
						$results[$key]['messages'][$msgkey]['lastcontentavatar'] = $userInfo[$message['lastauthorid']]['avatar'];
					}
				}

				if (empty($message['previewtext']))
				{
					$results[$key]['previewtext'] = vB_String::getPreviewText($message['rawtext']);
				}
			}
		}

		$channelRequests = $this->library->getChannelRequestTypes();

		$nodeIds = array();
		foreach ($results['request']['messages'] AS $message)
		{
			if (in_array($message['about'], $channelRequests))
			{
				$nodeIds[] = $message['aboutid'];
			}
		}

		if (!empty($nodeIds))
		{
			$nodesInfo = vB_Library::instance('node')->getNodes($nodeIds);

			$arrayNodeInfo = array();
			foreach ($nodesInfo as $node)
			{
				$arrayNodeInfo[$node['nodeid']] = array('title' => $node['title'], 'routeid' => $node['routeid']);
			}

			foreach ($results['request']['messages'] AS $key => &$val)
			{
				if (isset($arrayNodeInfo[$val['aboutid']]))
				{
					$val['abouttitle'] = $arrayNodeInfo[$val['aboutid']]['title'];
					$val['aboutrouteid'] = $arrayNodeInfo[$val['aboutid']]['routeid'];
				}
			}
		}


		// add notifications
		$params = array(
			'showdetail' => $receiptDetail,
			'perpage' => 5,
			'page' => 1,
			'sortDir' => "DESC",
		);
		$notifications = vB_Library::instance('notification')->fetchNotificationsForCurrentUser($params);
		$results['notification']['messages'] = $notifications;
		$results['notification']['count'] = count($notifications);
		$results['notification']['folderid'] = $folders['systemfolders'][vB_Library_Content_Privatemessage::NOTIFICATION_FOLDER];


		return $results;
	}

	/**
	 * Returns the text for a "reply" or "forward" message. Not implemented yet
	 */
	public function getReplyText($nodeid)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		throw new vB_Exception_Api('not_implemented');
	}

	/**
	 * Sets a message to read
	 *
	 * @param $nodeid
	 */
	public function setRead($nodeid, $read = 1)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		$this->library->setRead($nodeid, $read, vB::getCurrentSession()->get('userid'));
	}

	/**
	 * Checks that we have all the folders for the current user, and the set folders are there.
	 *
	 * @param int User ID of the current user
	 */
	public function checkFolders($userid = false)
	{
		if (empty($userid))
		{
			if (!$this->canUsePmSystem())
			{
				throw new vB_Exception_Api('not_logged_no_permission');
			}
		}
		return $this->library->checkFolders(vB::getCurrentSession()->get('userid'));
	}

	/**
	 * Updates the title
	 *
	 * @param  string The folder name
	 * @param  int    The folder ID
	 *
	 * @return array  The array of folder information for this folder.
	 */
	public function updateFolderTitle($folderName, $folderid)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		$this->library->checkFolders($userid);

		if (empty($folderid) OR empty($folderName))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		$cleaner = vB::get_cleaner();
		$foldername = $cleaner->clean($folderName, $vartype = vB_Cleaner::TYPE_NOHTML);
		$folderid = intval($folderid);
		$folders = $this->library->fetchFolders($userid);
		if (!array_key_exists($folderid, $folders['folders']) OR
			in_array($folderid, $folders['systemfolders']))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		if (empty($foldername) OR (strlen($foldername) > 512))
		{
			throw new vB_Exception_Api('invalid_msgfolder_name');
		}

		//If we got here we have valid data.
		return $this->assertor->assertQuery('vBForum:messagefolder', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array('folderid' => $folderid),
				'title' => $foldername));
	}

	/**
	 * Deletes a folder and moves its contents to trash
	 *
	 * @param string The new folder title.
	 */
	public function deleteFolder($folderid)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		$this->library->checkFolders($userid);
		$folders = $this->library->fetchFolders($userid);

		if (!array_key_exists($folderid, $folders['folders']) OR
			in_array($folderid, $folders['systemfolders']))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		//If we got here we have valid data. First move the existing messages to trash
		$this->assertor->assertQuery('vBForum:sentto', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'folderid' => $folders['systemfolders'][vB_Library_Content_Privatemessage::TRASH_FOLDER],
			vB_dB_Query::CONDITIONS_KEY => array('folderid' => $folderid)));
		//Then delete the folder
		$this->assertor->assertQuery('vBForum:messagefolder', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'folderid' => $folderid));

		return true;
	}

	/**
	 * Returns the node content as an associative array
	 *
	 * @param  integer The id in the primary table
	 * @param  array   permissions
	 * @param  bool    appends to the content the channel routeid and title, and starter route and title the as an associative array
	 *
	 * @return int
	 */
	public function getFullContent($nodeid, $permissions = false)
	{
		$results = $this->library->getFullContent($this->library->checkCanSee($nodeid), $permissions);

		if (empty($results))
		{
			throw new vB_Exception_Api('no_permission');
		}
		return $results;
	}

	/**
	 * Gets the title and forward
	 *
	 * @param  mixed will accept an array, but normall a comma-delimited string
	 *
	 * @return mixed array of first (single db record), messages- nodeid=> array(title, recipents(string), to (array of names), pagetext, date)
	 */
	public function getForward($nodeids)
	{
		if (!is_array($nodeids))
		{
			$nodeids = explode(',', $nodeids);
		}

		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		$valid = array();

		foreach ($nodeids as $nodeid)
		{
			$content = $this->nodeApi->getNode($nodeid);
			//if this is the author we can return the value
			if ($content['userid'] == $userid)
			{
				$valid[] = $nodeid;
			}
			else
			{
				//Maybe this is a recipient.
				$recipients = $this->assertor->getRows('vBForum:getPMRecipients', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
					'nodeid' => $nodeid, 'userid' => -1));
				foreach ($recipients as $recipient)
				{
					if ($recipient['userid'] == $userid)
					{
						$valid[] = $nodeid;
						break;
					}
				}
			}
		}

		if (empty($valid))
		{
			throw new vB_Exception_Api('invalid_data');
		}
		//Now build the response.
		$messageInfo = $this->assertor->assertQuery('vBForum:getPrivateMessageForward', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'nodeid' => $valid));

		if (!$messageInfo OR !$messageInfo->valid())
		{
			throw new vB_Exception_Api('invalid_data');
		}
		$results = array();
		$currentNode = false;
		$currentQuote = false;
		$currentAuthors = array();
		//We may have several messages, but normally all will be from one person to the same list.
		foreach ($messageInfo as $message)
		{
			if ($message['messageid'] != $currentNode['messageid'])
			{
				if ($currentNode)
				{
					$results[$currentNode['messageid']] = array('from' => $currentNode['messageauthor'], 'to' => $currentAuthors,
						'recipients' => implode(', ', $currentAuthors),
						'title' => $currentNode['title'],
						'date' => $currentNode['publishdate']);

					if (empty($currentNode['pagetext']))
					{
						$results[$currentNode['messageid']]['pagetext'] = $currentNode['rawtext'];
					}
					else
					{
						$results[$currentNode['messageid']]['pagetext'] = $currentNode['pagetext'];
					}
				}

				$currentNode = $message;
				$currentAuthors = array($message['username']);
			}
			else
			{
				$currentAuthors[] = $message['username'];
			}
		}

		//we'll have a last node that didn't get loaded.
		if ($currentNode)
		{
			$results[$currentNode['messageid']] = array('from' => $currentNode['messageauthor'], 'to' => $currentAuthors,
				'recipients' => implode(', ', $currentAuthors),
				'title' => $currentNode['title'],
				'date' => $currentNode['publishdate']);

			if (empty($currentNode['pagetext']))
			{
				$results[$currentNode['messageid']]['pagetext'] = $currentNode['rawtext'];
			}
			else
			{
				$results[$currentNode['messageid']]['pagetext'] = $currentNode['pagetext'];
			}
		}

		$firstMessage = reset($results);
		return array('first' => $firstMessage, 'messages' => $results);
	}

	/**
	 * Verifies that the request exists and its valid.
	 * Returns the message if no error is found.
	 * Throws vB_Exception_Api if an error is found.
	 *
	 * @param  int   $userid
	 * @param  int   $nodeid
	 *
	 * @return array - message info
	 */
	protected function validateRequest($userid, $nodeid)
	{
		return $this->library->validateRequest($userid, $nodeid);
	}

	/**
	 * Denies a user follow request
	 *
	 * @param  int  the nodeid of the request
	 * @param  int  (optional) the userid to whom the request was sent
	 *
	 * @return bool
	 */
	public function denyRequest($nodeid, $cancelRequestFor = 0)
	{
		return $this->library->denyRequest($nodeid, $cancelRequestFor);
	}

	/**
	 * Accepts a user follow request or a channel ownership/moderation/membership request
	 *
	 * @param  int  the nodeid of the request
	 *
	 * @return bool
	 */
	public function acceptRequest($nodeid)
	{
		return $this->library->acceptRequest($nodeid);
	}

	/**
	 * Clears the cached folder information
	 */
	public function resetFolders()
	{
		$this->library->resetFolders();
	}

	/**
	 * Validates the data according to the action to be taken
	 *
	 * @param  array  The standard node data array
	 * @param  string The "action" to be taken (one of the vB_Api_Content::ACTION_* constants)
	 * @param  int    (optional) Node ID
	 * @param  array  (optional) Array of Nodes.
	 *
	 * @return bool
	 */
	public function validate(&$data, $action = vB_Api_Content::ACTION_ADD, $nodeid = false, $nodes = false)
	{
		if (vB::getUserContext()->isSuperAdmin())
		{
			return true;
		}

		if (!$this->canUsePmSystem())
		{
			return false;
		}
		$currentUser = vB::getCurrentSession()->get('userid');

		if (!intval($currentUser))
		{
			return false;
		}

		//we need a nodeid (or parentid if we are adding) or we cannot answer the question.
		if ($action == vB_Api_Content::ACTION_ADD)
		{
			if (empty($data['parentid']) OR !intval($data['parentid']))
			{
				$data['parentid'] = $this->pmChannel;
			}
			$parentid = $data['parentid'];
		}
		else
		{
			if (!$nodeid)
			{
				if (empty($data['nodeid']) OR !intval($data['nodeid']))
				{
					throw new vB_Exception_Api('invalid_data');
				}
				else
				{
					$nodeid = $data['nodeid'];
				}
			}

			if (!is_array($nodeid))
			{
				$nodeid = array($nodeid);
			}

			$nodes = vB_Api::instanceInternal('node')->getNodes($nodeid);

			if (empty($nodes))
			{
				return false;
			}
		}

		switch ($action)
		{
			case vB_Api_Content::ACTION_ADD:
				// VBV-3512
				if (vB::getUserContext()->isGloballyIgnored())
				{
					throw new vB_Exception_Api('not_logged_no_permission');
				}

				// HV is not needed while sending requests or notifications.
				if (!isset($data['msgtype']) OR !in_array($data['msgtype'], array('request', 'notification')))
				{
					vB_Api::instanceInternal('hv')->verifyToken($data['hvinput'], 'post');
				}

				//parentid must be pmChannel or a descendant.
				if ($parentid != $this->pmChannel)
				{
					//this only returns the closure record that has the given ancestor (second param) so
					//if $parentid is isn't a decendant of pmChannel no records will be returned.
					//we only need to check if its empty not the specific contents
					$closure = vB_Library::instance('node')->fetchClosureParent($parentid, $this->pmChannel);
					if (empty($closure) OR !is_array($closure))
					{
						throw new vB_Exception_Api('invalid_data');
					}
				}

				return vB::getUserContext()->getChannelPermission('createpermissions', $this->contenttype, $parentid);
				break;

			case vB_Api_Content::ACTION_UPDATE:
				//They can only update if they are a moderator with permission to moderate messages.
				// As a moderator
				foreach ($nodes as $node)
				{
					if (!vB::getUserContext()->getChannelPermission('moderatorpermissions', 'caneditposts', $node['nodeid'], false, $node['parentid']))
					{
						return false;
					}
				}
				return true;
				break;

			case vB_Api_Content::ACTION_VIEW:
				//Maybe we already have a record.
				if (!isset($this->canSee[$currentUser]))
				{
					$this->canSee[$currentUser] = array();
				}

				$canSeeQry = $this->assertor->assertQuery('vBForum:sentto', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'nodeid' => $nodeid, 'userid' => $currentUser));

				//We scan the $canSeeQuery list. If there's a match then they can view this node.
				foreach ($canSeeQry as $sentto)
				{
					foreach ($nodes as $key => $node)
					{
						if ($node['nodeid'] == $sentto['nodeid'])
						{
							unset($nodes[$key]);
							if (count($nodes) == 0)
							{
								return true;
							}
							break;
						}
					}
				}

				//if we got here we have some unmatched nodes. That means no view permission
				throw new vB_Exception_NodePermission($node['nodeid']);
				break;

			case vB_Api_Content::ACTION_DELETE:
				foreach ($nodes as $node)
				{
					//If the user has a sentto record, it's O.K.
					$canSeeQry = $this->assertor->assertQuery('vBForum:sentto', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						'nodeid' => $node['nodeid'], 'userid' => $currentUser));

					if ($canSeeQry->valid())
					{
						//skip to the next record
						continue;
					}
					else if (!vB::getUserContext()->getChannelPermission('moderatorpermissions', 'canremoveposts', $node['nodeid'], false, $node['parentid']))
					{
						return false;
					}
				}
				return true;

				break;

			case vB_Api_Content::ACTION_APPROVE:

				return true;
				break;

			case vB_Api_Content::ACTION_PUBLISH:

				return true;
				break;
			default:
				;
		} // switch

		return false;
	}

	/**
	 * Returns a formatted json string appropriate for the search api interface
	 *
	 * @param  string the search query
	 *
	 * @return string the json string
	 */
	public function getSearchJSON($queryText)
	{
		return json_encode(array('keywords' => $queryText,
				/* 'contenttypeid' => vB_Types::instance()->getContentTypeId('vBForum_PrivateMessage' ) */
				'type' => 'vBForum_PrivateMessage'));
	}

	/**
	 * Gets the pending posts folder id
	 *
	 * @return int The pending posts folder id from messagefolder.
	 */
	public function getPendingPostFolderId()
	{
		return $this->library->getPendingPostFolderId();
	}

	/**
	 * Gets the infractions folder id
	 *
	 * @return int The infractions folder id from messagefolder.
	 */
	public function getInfractionFolderId()
	{
		return $this->library->getInfractionFolderId();
	}

	/**
	 * Gets the deleted_items folder id
	 *
	 * @return int The deleted_items folder id from messagefolder.
	 */
	public function getDeletedItemsFolderId()
	{
		return $this->library->getDeletedItemsFolderId();
	}


	/**
	 * Moves a message back to user inbox folder
	 *
	 * @params int  The nodeid we are undeleting.
	 *
	 * @return bool True if succesfully done.
	 */
	public function undeleteMessage($nodeid)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		$currentUser = vB::getCurrentSession()->get('userid');

		if (!intval($currentUser))
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}

		$nodeids = explode(',', $nodeid);

		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}

		//we can only move a record to which the user has access.
		$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::CONDITIONS_KEY => array(
				array('field' => 'userid', 'value' => $currentUser),
				array('field' => 'nodeid', 'value' => $nodeids)
			));
		$existing = $this->assertor->getRows('vBForum:sentto', $data);

		if (empty($existing) OR !empty($existing['errors']))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		return $this->library->undeleteMessage($nodeid, $existing);
	}

	/**
	 * Delete private messages. Once deleted user won't be able to retrieve them again.
	 *
	 * @params mixed Array or comma separated nodeids from messages to delete.
	 *
	 * @return array Whether delete action succeeded or not.
	 *               keys -- success
	 */
	public function deleteMessages($nodeid)
	{
		$nodeids = $nodeid;
		if (is_string($nodeid) AND strpos($nodeid, ',') !== false)
		{
			$nodeids = explode(',', $nodeid);
		}
		else if (!is_array($nodeid))
		{
			$nodeids = array($nodeid);
		}


		foreach ($nodeids as $nodeid)
		{
			if (!$this->deleteMessage($nodeid))
			{
				return array('success' => false);
			}
		}
		return array('success' => true);
	}

	/**
	 * Gets the folder information from a given folderid. The folderid requested should belong to the user who is requesting.
	 *
	 * @param  int   The folderid to fetch information for.
	 *
	 * @return array The folder information such as folder title, titlephrase and if is custom folder.
	 */
	public function getFolderInfoFromId($folderid)
	{
		if (!$this->canUsePmSystem())
		{
			throw new vB_Exception_Api('not_logged_no_permission');
		}
		$userid = vB::getCurrentSession()->get('userid');

		$folderid = intval($folderid);
		if (!$folderid)
		{
			throw new vB_Exception_Api('invalid_data');
		}

		// check that the folderid belongs to the user request.
		// @TODO we might want to let admin to fetch any requested folder
		$folders = $this->library->listFolders();
		if (!in_array($folderid, array_keys($folders)))
		{
			throw new vB_Exception_Api('no_permission');
		}

		return $this->library->getFolderFromId($folderid, $userid);
	}

	/**
	 * Returns the cached folder information
	 *
	 * @param  int   Userid we are fetching folders for.
	 *
	 * @return mixed Array containing user folders info.
	 */
	public function fetchFolders($userid)
	{
		$userid = intval($userid);
		if (!$userid)
		{
			throw new vB_Exception_Api('invalid_data');
		}

		return $this->library->fetchFolders($userid);
	}

	/**
	 * Returns an array of all users participating in a discussion
	 *
	 * @param  int   the nodeid of the discussion
	 *
	 * @return array of user information
	 *               * following -- is the participant a follower of the current user (may be NULL)
	 *               * userid -- ID of the participant
	 *               * username -- Name of the participant
	 *               * avatarurl -- Url for the participant's avatar
	 *               * starter -- ID of the starter for $nodeid
	 */
	public function fetchParticipants($nodeid)
	{
		if (!intval($nodeid))
		{
			throw new vB_Exception_Api('invalid_data');
		}
		$currentUser = vB::getCurrentSession()->get('userid');

		//We always should have something in $exclude.
		$exclude = array('-1');

		if (intval($currentUser))
		{
			$options = vB::getDatastore()->get_value('options');
			if (trim($options['globalignore']) != '')
			{
				$exclude = preg_split('#\s+#s', $options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
			}
		}

		$node = vB_Api::instanceInternal('node')->getNode($nodeid);
		$contentApi = vB_Api_Content::getContentApi($node['contenttypeid']);
		$valid = $contentApi->validate($node, vB_Api_Content::ACTION_VIEW, $node['nodeid'], array($node['nodeid'] => $node));

		//if the user can't see the node, then don't allow them to see the participants.
		if (!$valid)
		{
			throw new vB_Exception_Api('no_permission');
		}

		$nodeCTClass = vB_Types::instance()->getContentTypeClass($node['contenttypeid']);

		switch ($nodeCTClass)
		{
			case self::PARTICIPANTS_PM :
				$queryPart = 'vBForum:getPMRecipientsForMessageOverlay';
				$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED, 'nodeid' => $nodeid);
				break;
			case self::PARTICIPANTS_POLL :
				// this seems a bit sketchy. This works (I think) because polls are always the starter due to
				// frontend restrictions, and no current notification will expect anything different when
				// calling this on a poll post, but if we have poll replies, and a notification called this function
				// expecting to get the thread participants, NOT poll voters, this would be a bug...
				$queryPart = 'vBForum:getNotificationPollVoters';
				$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED, 'nodeid' => $nodeid);
				break;
			//for a channel we will quietly fail.  Trying to look up the participants is too expensive, is a potential DOS
			//and we don't really need it.
			case self::PARTICIPANTS_CHANNEL :
				return array();
				break;
			default :
				// private messages should've been caught by the first case. At this point, we should only be concerned with content
				// nodes (excluding polls)
				$queryPart = 'vBForum:fetchParticipants';
				$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED, 'nodeid' => $nodeid, 'currentuser' => $currentUser, 'exclude' => $exclude);
				break;
		}

		$members = vB::getDbAssertor()->getRows($queryPart, $params);

		$participants = array();
		foreach ($members AS $member)
		{
			if (isset($participants[$member['userid']]))
			{
				continue;
			}

			$participants[$member['userid']] = $member;
		}

		$userApi = vB_Api::instanceInternal('user');
		foreach ($participants as $uid => $participant)
		{
			$participants[$uid]['avatarurl'] = $userApi->fetchAvatar($uid, true, $participant);
		}

		return $participants;
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88967 $
|| #######################################################################
\*=========================================================================*/
