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
 * vB_Api_Content
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id: content.php 88832 2016-05-26 15:52:03Z ksours $
 * @access public
 */
abstract class vB_Api_Content extends vB_Api
{
	/**#@+
	 * @var int Flags that describe the action to be taken.
	 */
	const ACTION_ADD = 1;
	const ACTION_UPDATE = 2;
	const ACTION_VIEW = 3;
	const ACTION_DELETE = 4;
	const ACTION_APPROVE = 5;
	const ACTION_PUBLISH = 6;
	/**#@-*/

	/**
	 * List of methods that which can still be called as normal even when the
	 * API is disabled due to forum being closed, password expired, IP ban, etc.
	 *
	 * @var array $disableWhiteList
	 */
	protected $disableWhiteList = array('getTimeNow');

	/**
	 * @var vB_UserContext Instance of vB_UserContext
	 */
	protected $usercontext;

	/**
	 * @var vB_dB_Assertor Instance of the database assertor
	 */
	protected $assertor;

	/**
	 * @var vB_Api_Node Instance of the Node API
	 */
	protected $nodeApi;

	/**
	 * @var array List of the fields on the node table
	 * @deprecated This is no longer being used and will be removed in a future version.
	 */
	protected $nodeFields;

	/**
	 * @var array vBulletin options
	 */
	protected $options;

	/**
	 * @var bool Flag that allows skipping the flood check (used for types like Photos, where we'll upload several together)
	 */
	protected $doFloodCheck = true;

	/**
	 * @var int Flag for whether we handle showapproved, approved fields internally or not
	 */
	protected $handleSpecialFields = 0;

	/**
	 * @deprecated This appears to not be used anywhere
	 */
	protected $notifications = array();

	/**
	 * @var vB_Library_Content Instance of the content library
	 */
	protected $library;

	/**
	 * @deprecated This appears to not be used anywhere
	 */
	protected $previewFields = array();

	/**
	 * Constructor
	 */
	protected function __construct()
	{
		parent::__construct();

		//The table for the type-specific data.
		$this->assertor = vB::getDbAssertor();
		$this->nodeApi = vB_Api::instanceInternal('node');
		// TODO remove this when the previewFields var is removed
		$this->previewFields = $this->nodeApi->fetchPreviewFields();

		// TODO remove this when the nodeFields var is removed
		$this->nodeFields = $this->nodeApi->getNodeFields();
		$this->options = vB::get_datastore()->get_value('options');
	}

	/**
	 * Returns textCountChange property
	 * @return int
	 */
	public function getTextCountChange()
	{
		return $this->library->getTextCountChange();
	}

	/**
	 * Adds a new node
	 *
	 * @param  mixed   Array of field => value pairs which define the record.
	 * @param  array   Array of options for the content being created.
	 *                 Understands skipTransaction, skipFloodCheck, floodchecktime, many subclasses have skipNotification. See subclasses for more info.
	 *
	 * @return integer the new nodeid
	 */
	public function add($data, $options = array())
	{
		if (!$this->validate($data, vB_Api_Content::ACTION_ADD))
		{
			throw new vB_Exception_Api('no_create_permissions');
		}

		//We shouldn't pass the open or show open fields
		unset($data['open']);
		unset($data['showopen']);

		//We shouldn't pass the approved or showapproved open fields
		if (!$this->handleSpecialFields)
		{
			unset($data['approved']);
			unset($data['showapproved']);
		}

		$this->cleanInput($data);
		$this->cleanOptions($options);
		$result = $this->library->add($data, $options);
		return $result['nodeid'];
	}

	/**
	 * Clean unallowed options from user request, only cleans 'skipFloodCheck' for now
	 *
	 * @param array $options Array of options, may be passed in from client
	 */
	public function cleanOptions(&$options)
	{
		if (isset($options['skipFloodCheck']))
		{
			unset($options['skipFloodCheck']);
		}
		//clients don't get to set skipTransaction
		unset ($options['skipTransaction']);
	}

	/**
	 * Cleans the input in the $data array, directly updating $data.
	 *
	 * @param mixed     Array of fieldname => data pairs, passed by reference.
	 * @param int|false Nodeid of the node being edited, false if creating new
	 */
	public function cleanInput(&$data, $nodeid = false)
	{
		if (isset($data['userid']))
		{
			unset($data['userid']);
		}

		if (isset($data['authorname']))
		{
			unset($data['authorname']);
		}

		// These fields should be cleaned regardless of the user's canusehtml permission.
		$cleaner = vB::getCleaner();
		foreach(array('title', 'htmltitle', 'description', 'prefixid', 'caption') AS $fieldname)
		{
			if (isset($data[$fieldname]))
			{
				$data[$fieldname] = $cleaner->clean($data[$fieldname], vB_Cleaner::TYPE_NOHTML);
			}
		}

		foreach(array('open', 'showopen', 'approved', 'showapproved') AS $fieldname)
		{
			if (isset($data[$fieldname]))
			{
				$data[$fieldname] = $cleaner->clean($data[$fieldname], vB_Cleaner::TYPE_INT);
			}
		}

		if (isset($data['urlident']))
		{
			// Let's make sure it's a valid identifier. No spaces, UTF-8 encoded, etc.
			$data['urlident'] = vB_String::getUrlIdent($data['urlident']);
		}

		// These fields are cleaned for people who cannot use html
		foreach(array('pagetext') AS $fieldname)
		{
			if (isset($data[$fieldname]))
			{
				$data[$fieldname] = $cleaner->clean($data[$fieldname], vB_Cleaner::TYPE_NOHTML);
			}
		}

		if (!empty($data['nodeid']))
		{
			$checkNodeid = $data['nodeid'];
		}
		else if (!empty($data['parentid']))
		{
			$checkNodeid = $data['parentid'];
		}
		else if ($nodeid)
		{
			$checkNodeid = $nodeid;
		}

		//Channels are handled a bit differently.
		if (isset($this->contenttype))
		{
			$isChannel = ($this->contenttype == 'vBForum_Channel');
		}
		//Note that contenttype should always be set. The next three checks should never be necessary.
		//But just in case, any of these will give a valid check.
		else if (isset($data['contenttypeid']))
		{
			$isChannel = ($data['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Channel'));
		}
		else if (!empty($nodeid))
		{
			$node = $this->nodeApi->getNode($nodeid);
			$isChannel = ($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Channel'));
		}
		else if (!empty($data['nodeid']))
		{
			$node = $this->nodeApi->getNode($data['nodeid']);
			$isChannel = ($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Channel'));
		}

		if (!empty($isChannel))
		{
			//if this is an update we do nothing. If publishdate is already set we do nothing.
			if (empty($nodeid) AND !isset($data['publishdate']))
			{
				$data['publishdate'] = vB::getRequest()->getTimeNow();
			}
		}
		else if (!empty($checkNodeid))
		{
			$checkNode = $this->nodeApi->getNodeFullContent($checkNodeid);
			$checkNode = array_pop($checkNode);
			$limits = vB::getUserContext()->getChannelLimits($checkNode['channelid']);

			// VBV-12342 - If this is a new node & the usergroup requires moderation on this channel, we need to
			// set approved & showapproved to 0. Do not mess with publishdate. If it's not an article, it'll be
			// published immediately in the publishdate handling below.
			if (!empty($limits['require_moderate']) AND empty($node))
			{
				$data['approved'] = 0;
				$data['showapproved'] = 0;
			}

			/* PUBLISHDATE HANDLING */
			// For articles the handling is more complex
			if (($checkNode['channeltype'] == 'article'))
			{
				$publish = true;

				if (!empty($nodeid))
				{
					$node = $this->nodeApi->getNode($nodeid);
					$starter = ($node['nodeid'] == $node['starter']);
				}
				else if (!empty($data['nodeid']))
				{
					$node = $this->nodeApi->getNode($data['nodeid']);
					$starter = ($node['nodeid'] == $node['starter']);
				}
				else
				{
					$node = $this->nodeApi->getNode($data['parentid']);
					$starter = ($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Channel'));
				}

				if ($starter)
				{
					$canpublish = vB::getUserContext()->getChannelPermission('forumpermissions2', 'canpublish', $checkNode['channelid']);

					//if this is a add (we don't have a nodeid) AND the user can't publish, we force publishdate to zero
					if (!$canpublish OR (isset($data['publish_now']) AND $data['publish_now'] === false))
					{
						// if the user can't publish, but can create, then we save as a draft.
						$data['publish_now'] = false;
						$publish = false;

						if ($nodeid)
						{
							unset($data['publishdate']);
						}
						else
						{
							$data['publishdate'] = 0;
						}
					}
				}

				if ($publish)
				{
					if (empty($data['publishdate']) OR !vB::getUserContext()->getChannelPermission('forumpermissions2' , 'canpublish',
							$checkNode['channelid']))
					{
						$data['publishdate'] = vB::getRequest()->getTimeNow();
					}
				}

			}
			else if (!$nodeid)
			//New non-articles are published immediately.
			{
				if (empty($data['publishdate']) OR !vB::getUserContext()->getChannelPermission('forumpermissions2' , 'canpublish',
						$checkNode['channelid']))
				{
					$data['publishdate'] = vB::getRequest()->getTimeNow();
				}
			}
		}
		else if (!$nodeid)
		{
			/*
			 *	I'm not certain what condition this code branch is reached. If this is a new node,
			 *	then $checkNodeid will probably be the parentid. If we don't have the parentid, we
			 *	can't even check the channel limits for require_moderate. As such I'm not making
			 *	any code changes in this area for VBV-12342.
			 */
			$data['publishdate'] = vB::getRequest()->getTimeNow();
		}
	}

	/**
	 * Validates that the current can create a node with these values
	 *
	 * @param  array     $data Array of field => value pairs which define the record.
	 * @param  int       $action The action const, used to be checked for permission
	 * @param  int       $nodeid
	 * @param  array|int nodeid or array of nodeids.  If not passed $data must contain a nodeid
	 * @param  array     $nodes -- $node records corresponding to the $nodeid variable.  If not passed will be fetched from the DB
	 *
	 * @return bool
	 */
	public function validate(&$data, $action = self::ACTION_ADD, $nodeid = false, $nodes = false)
	{
		// Each descendant should override this function and add their own
		// check of individual required fields.
		if ((defined('VB_AREA') AND VB_AREA == 'Upgrade'))
		{
			return true;
		}

		if (vB::getUserContext()->isSuperAdmin())
		{
			//The only reason we would return false is if comments are globally disabled  AND the content type is neither attachment nor photo, this
			// would be a comment, and the reply would be a comment.
			if (
				($action != self::ACTION_ADD) OR
				vB::getDatastore()->getOption('postcommentthreads') OR
				($data['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Photo')) OR
				($data['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Attach'))
			)
			{
				return true;
			}

			if (empty($data['parentid']) OR !intval($data['parentid']))
			{
				throw new vB_Exception_Api('invalid_data');
			}
			$parent = vB_Library::instance('node')->getNodeBare($data['parentid']);

			//If the parent is not a starter. this would be a comment.
			if (($parent['starter'] > 0) AND ($parent['nodeid'] != $parent['starter']))
			{
				return false;
			}
			return true;
		}

		//we need a nodeid (or parentid if we are adding) or we cannot answer the question.
		if ($action == vB_Api_Content::ACTION_ADD)
		{
			if (empty($data['parentid']) OR !intval($data['parentid']))
			{
				throw new vB_Exception_Api('invalid_data');
			}
			$parentid = $data['parentid'];
		}
		else
		{
			if (!$nodeid)
			{
				if (empty($data['nodeid']))
				{
					throw new Exception('invalid_data');
				}
				$nodeid = $data['nodeid'];
			}

			if (!is_array($nodeid))
			{
				$nodeid = array($nodeid);
			}

			if (!$nodes)
			{
				// We need to go through the library. If current user doesn't have permission to view the node,
				// the API wouldn't return that node, and if the caller didn't pass in the specific array of nodes,
				// this function would incorrectly return "true".
				$nodes = vB_Library::instance('node')->getNodes($nodeid);
			}
		}

		$userContext = vB::getUserContext();
		$userid = vB::getCurrentSession()->get('userid');
		switch ($action)
		{
			case vB_Api_Content::ACTION_ADD:
				//Check the node-specific permissions first.
				$parent = vB_Library::instance('node')->getNode($parentid);
				if (in_array($this->contenttype, array('vBForum_Text', 'vBForum_Poll', 'vBForum_PrivateMessage'))
					AND ($parent['parentid'] != $parent['starter'])
				)
				{
					// Only validate HV for specific content types.
					// To skip HV, please call library methods instead.
					vB_Api::instanceInternal('hv')->verifyToken($data['hvinput'], 'post');
				}

				//confirm that the user can create this content type
				if (!$userContext->getChannelPermission('createpermissions', $this->contenttype, $parent['nodeid']))
				{
					return false;
				}

				//check the showPublished.
				if (($parent['showpublished'] == 0) )
				{
					if (!$userContext->getChannelPermission('moderatorpermissions', 'canmoderateposts', $parentid))
					{
						return false;
					}
				}

				//use show open here rather than open because it may be a more distant ancestor that is closed and not just the parent.
				//regardless if the parent is effectively closed we don't want to allow posting.
				if ($parent['showopen'] == 0 AND !$userContext->getChannelPermission('moderatorpermissions', 'canopenclose', $parentid))
				{
					//if the topic is owned by the poster and they can open their own topics then they can post
					$starter = vB_Library::instance('node')->getNode($parent['starter']);

					if(!($starter['userid'] == $userid AND $userContext->getChannelPermission('forumpermissions', 'canopenclose', $parent['starter'])))
					{
						return false;
					}
				}

				//Data consistency for a VM.
				if ($parentid == vB_Api::instanceInternal('node')->fetchVMChannel())
				{
					if (!isset($data['setfor']) OR
						(isset($data['setfor']) AND (!is_numeric($data['setfor']) OR $data['setfor'] <= 0)))
					{
						throw new vB_Exception_Api('invalid_data');
					}
					$vm_user = vB_User::fetchUserinfo($data['setfor']);

					if (($vm_user == false) OR !$vm_user['vm_enable'])
					{
						throw new vB_Exception_Api('invalid_data');
					}

					if ($data['setfor'] == $userid)
					{
						// Do we have add permission to write on our own wall?
						if (!vB::getUserContext()->hasPermission('visitormessagepermissions', 'canmessageownprofile'))
						{
							return false;
						}
					}
					else if (!vB::getUserContext()->hasPermission('visitormessagepermissions', 'canmessageothersprofile'))
					{
						// Do we have permission to write on others' walls?
						return false;
					}

				}

				return true;
				break;

			case vB_Api_Content::ACTION_UPDATE:
				//There are a couple of ways this user could be allowed to edit this record.
				// As a moderator
				$channelType = vB_Types::instance()->getContentTypeID('vBForum_Channel');

				foreach ($nodes AS $node)
				{
					// Can configure channel goes first, otherwise it is ignored due to the moderator perms
					if ($node['contenttypeid'] == $channelType)
					{
						$canEditChannels =
							(
								$userContext->getChannelPermission('forumpermissions2', 'canconfigchannel', $node['nodeid'], false, $node['parentid'])
								OR $userContext->hasAdminPermission('canadminforums')
							);
						if (!$canEditChannels)
						{
							return false;
						}

						continue;
					}

					if ($userContext->getChannelPermission('moderatorpermissions', 'caneditposts', $node['nodeid'], false, $node['parentid']))
					{
						continue;
					}



					$vmChannel = vB_Api::instanceInternal('node')->fetchVMChannel();

					if ($node['parentid'] == $vmChannel)
					{
						$vm_user = vB_User::fetchUserinfo($node['setfor']);
						if (
							!$vm_user['vm_enable']
								OR
							$node['userid'] != $userid
								OR
							!vB::getUserContext()->hasPermission('visitormessagepermissions', 'caneditownmessages')
								OR
							(!vB::getUserContext($vm_user['userid'])->hasPermission('genericpermissions', 'canviewmembers') AND $userid == $vm_user['userid'])
						)
						{
							return false;
						}
					}

					// It's a VM for the user from himself
					if (!empty($node['setfor']) AND $node['setfor'] == $userid AND $node['setfor'] == $node['userid'] AND $userContext->hasPermission('visitormessagepermissions', 'caneditownmessages'))
					{
						continue;
					}


					//Now  check the user permissions
					if ($node['userid'] == vB::getCurrentSession()->get('userid') )
					{
						//Check the editing time limit

						$limits = $userContext->getChannelLimits($node['nodeid']);

						if ($limits AND !empty($limits['edit_time']))
						{
							if ($node['publishdate'] + ($limits['edit_time'] * 3600) < vB::getRequest()->getTimeNow())
							{
								return false;
							};
						}

						if ($userContext->getChannelPermission('forumpermissions', 'caneditpost', $node['nodeid'], false, $node['parentid']))
						{
							continue;
						}
					}
					else
					{
						if ($userContext->getChannelPermission('forumpermissions2', 'caneditothers', $node['nodeid'], false, $node['parentid']))
						{
							continue;
						}
					}

					//if we got here the user isn't authorized to update this record.
					return false;
				}
				return true;
				break;


			case vB_Api_Content::ACTION_VIEW:
				$channelType = vB_Types::instance()->getContentTypeID('vBForum_Channel');
				foreach ($nodes as $key => $node)
				{
					if (empty($node['nodeid']) OR !is_numeric($node['nodeid']))
					{
						$check = current($node);

						if (!empty($check['nodeid']) AND is_numeric($check['nodeid']) )
						{
							$node = $check;
						}
					}

					$canViewChannel = $userContext->getChannelPermission('forumpermissions', 'canview', $node['nodeid'], false, $node['parentid']);
					if (!$canViewChannel)
					{
						return false;
					}

					if ($node['contenttypeid'] != $channelType)
					{
						// grab the starter's userid for the canviewothers check
						if (isset($node['starteruserid']))
						{
							// 'starteruserid' is not always set, for ex when the $node
							// provided originated from node lib's getNodeBare()
							$starterUserid = $node['starteruserid'];
						}
						else if ($node['nodeid'] == $node['starter'])
						{
							$starterUserid = $node['userid'];
						}
						else
						{
							$starter = vB_Api::instanceInternal('node')->getNode($node['starter']);
							$starterUserid = $starter['userid'];
						}

						// They can't view this non-channel node if they just cannot view *any* threads, or if the thread is not theirs and they don't have canviewothers
						if (!$userContext->getChannelPermission('forumpermissions', 'canviewthreads', $node['nodeid'], false, $node['parentid']) OR
							(($starterUserid <> $userid) AND !$userContext->getChannelPermission('forumpermissions', 'canviewothers', $node['nodeid'], false, $node['parentid'])))
						{
							return false;
						}
					}

					//If the node is published, we just need to check viewperms.
					if (($userid > 0) AND ($node['viewperms'] > 0) AND ($node['showpublished'] > 0) AND ($node['showapproved'] > 0))
					{
						continue;
					}

					//if the user has canalwaysview here, then ... they can view.
					if ($userContext->getChannelPermission('forumpermissions2', 'canalwaysview', $node['nodeid'], false, $node['parentid']))
					{
						continue;
					}

					if (($node['viewperms'] > 1) AND ($node['showpublished'] > 0) AND ($node['showapproved'] > 0))
					{
						continue;
					}

					if (!$node['showapproved'] AND !$userContext->getChannelPermission('moderatorpermissions', 'canmoderateposts', $node['nodeid'], false, $node['parentid']))
					{
						return false;
					}

					// those with candeleteposts or canremoveposts moderator permissions should be able to view soft-deleted posts
					if (!$node['showpublished'] AND
						!(
							$userContext->getChannelPermission('moderatorpermissions', 'candeleteposts', $node['nodeid'], false, $node['parentid']) OR
							$userContext->getChannelPermission('moderatorpermissions', 'canremoveposts', $node['nodeid'], false, $node['parentid']) OR
							($node['nodeid'] == $node['starter'] AND $userContext->getChannelPermission('forumpermissions2', 'canpublish', $node['nodeid'], false, $node['parentid']))
						)
					)
					{
						return false;
					}

					if ($userContext->getChannelPermission('moderatorpermissions', 'caneditposts', $node['nodeid'], false, $node['parentid']))
					{
						continue;
					}

					if ($node['viewperms'] == 0)
					{
						//Only blog members can view.  We need to find the channel
						if ($node['contenttypeid'] == vB_Types::instance()->getContentTypeId('vBForum_Channel'))
						{
							$checkNodeId = $node['nodeid'];
						}
						else if ($node['starter'] == $node['nodeid'])
						{
							//Check for the the parent's parent
							$checkNodeId = $node['parentid'];
						}
						else
						{
							// The channel is of course the starter's parent.
							$starter = vB_Api::instanceInternal('node')->getNode($node['starter']);
							$checkNodeId = $starter['parentid'];
						}

						$groupInTopic = vB_Api::instanceInternal('user')->getGroupInTopic($userid, $checkNodeId);

						if (!$groupInTopic OR empty($groupInTopic) OR !empty($groupInTopic['errors']))
						{
							//someone with moderator permission can view
							if ($userContext->getChannelPermission('moderatorpermissions', 'caneditposts', $node['nodeid'], false, $node['parentid']))
							{
								continue;
							}
							if ($userContext->getChannelPermission('forumpermissions2', 'canalwaysview', $node['nodeid'], false, $node['parentid']))
							{
								continue;
							}
							//Not O.K.
							return false;
						}

						$validGroups = vB_Api::instanceInternal('usergroup')->fetchPrivateGroups();
						$found = false;
						foreach($groupInTopic as $pair)
						{
							if (in_array($pair['groupid'], $validGroups))
							{
								$found = true;
								break;
							}
						}
						if (!$found)
						{
							return false;
						}
					}
					else if (($node['viewperms'] == 1) AND ($userid < 1))
					{
						return false;
					}


					if (!$userContext->getChannelPermission('forumpermissions', 'canview', $node['nodeid'], false, $node['parentid']))
					{
						return false;
					}
				}

				return true;

				break;

			case vB_Api_Content::ACTION_DELETE:
				foreach ($nodes AS $node)
				{
					if ($node['contenttypeid'] !== vB_Types::instance()->getContentTypeID($this->contenttype))
					{
						throw new vB_Exception_Api('invalid_request');
					}

					// Check for Blog Channel - only allow owner to delete the blog
					// This part should just check for any channel, not a blog but see VBV-7931
					if ($node['contenttypeid'] === vB_Types::instance()->getContentTypeId('vBForum_Channel'))
					{
						if ($userContext->getChannelPermission('forumpermissions2', 'candeletechannel', $node['nodeid']) OR
							$userContext->hasAdminPermission('canadminforums'))
						{
							continue;
						}
						// Check if it's a SG
						else if (vB_Api::instanceInternal('socialgroup')->isSGNode($node['nodeid'])
							AND ($node['userid'] === vB::getCurrentSession()->get('userid'))
							)
						{
							if (!$userContext->hasPermission('socialgrouppermissions', 'candeleteowngroups'))
							{
								return false;
							}
							continue;
						}
						else
						{
							return false;
						}
					}

					if (!$userContext->getChannelPermission('moderatorpermissions', 'canremoveposts', $node['nodeid']))
					{
						return false;
					}
				}

				return true;
				break;
			case vB_Api_Content::ACTION_APPROVE:
				foreach ($nodes AS $node)
				{
					return $userContext->getChannelPermission('moderatorpermissions', 'canmoderateposts', $node['nodeid']);
				}
				break;
			case vB_Api_Content::ACTION_PUBLISH:
				foreach ($nodes AS $node)
				{
					return $userContext->getChannelPermission('forumpermissions2', 'canpublish', $node['nodeid']);
				}
				break;
			default:
			;
		} // switch
	}

	/**
	 * Permanently deletes a node
	 *
	 * @param  integer The nodeid of the record to be deleted
	 *
	 * @return boolean
	 */
	public function delete($nodeid)
	{
		$data = false;

		if (!$this->validate($data, self::ACTION_DELETE, $nodeid))
		{
			throw new vB_Exception_Api('no_delete_permissions');
		}

		return $this->library->delete($nodeid);
	}

	/**
	 * Returns a content api of the appropriate type
	 *
	 * @param  int   The content type id
	 *
	 * @return mixed Content api object
	 */
	public static function getContentApi($contenttypeid)
	{
		return vB_Api::instanceInternal('Content_' . vB_Types::instance()->getContentTypeClass($contenttypeid));
	}

	/**
	 * Determines if this record is in a published state
	 *
	 * @param  array The standard array of data sent to the add() method
	 *
	 * @return bool
	 */
	public function isPublished($data)
	{
		return $this->library->isPublished($data);
	}

	/**
	 * Updates a record
	 *
	 * @param  mixed array of nodeids
	 * @param  mixed array of permissions that should be checked.
	 *
	 * @return bool
	 */
	public function update($nodeid, $data)
	{
		if (!$this->validate($data, self::ACTION_UPDATE, $nodeid))
		{
			throw new vB_Exception_Api('no_update_permissions');
		}

		$this->cleanInput($data, $nodeid);

		$content = $this->nodeApi->getNodeFullContent($nodeid);
		$content = array_pop($content);

		if (($content['channeltype'] != 'article') AND !vB::getUserContext()->getChannelPermission('forumpermissions2', 'canpublish', $content['parentid']))
		{
			unset($data['publishdate']);
			unset($data['publishnow']);
		}

		if (empty($data['title']))
		{
			unset ($data['title']);
		}

		$nodeInfo = vB_Api::instanceInternal('node')->getNode($nodeid);

		//check time limit on editing of thread title
		if(isset($data['title']) AND ($data['title'] != $nodeInfo['title']) AND !vB_Library::instance('node')->canEditThreadTitle($nodeid))
		{
			throw new vB_Exception_Api('exceeded_timelimit_editing_thread_title');
		}

		return $this->library->update($nodeid, $data);
	}

	/**
	 * Alias for @getFullContent
	 */
	public function getContent($nodeid, $permissions = false)
	{
		return $this->getFullContent($nodeid, $permissions);
	}

	/**
	 * Returns the node content plus the channel routeid and title, and starter route and title, and permissions and other data
	 *
	 * @param  integer The node id
	 * @param  array   Permissions
	 *
	 * @return array   The standard array of node data
	 */
	public function getFullContent($nodeid, $permissions = false)
	{
		$temporary = $this->library->getFullContent($nodeid, $permissions);
		$data = array();

		if (!$this->validate($data, self::ACTION_VIEW, $nodeid, $temporary))
		{
			throw new vB_Exception_Api('no_permission');
		}

		foreach($temporary AS $key => $node)
		{
			if (empty($node['moderatorperms']['canviewips']))
			{
				$temporary[$key]['ipaddress'] = '';
			}
		}

		return $temporary;
	}

	/**
	 * Takes a node record and removes the data cannot be viewed based on public_preview.
	 * It's called from the search Api, which avoids using the content APIs
	 *
	 * @param mixed The node record, normally from getNodeFullContent, by reference
	 */
	public function cleanPreviewContent(&$record)
	{
		static $channelTypeId;
		static $allCanView;

		if (!isset($channelTypeId))
		{
			$channelTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');
			$allCanView = $this->library->getAllCanView();
		}
		$thisUserid = vB::getCurrentSession()->get('userid');

		if (empty($record['content']['moderatorperms']['canviewips']))
		{
			unset($record['ipaddress']);
			unset($record['content']['ipaddress']);
		}

		//if the current user can't view thread content here we have to unset a number of fields.
		if (empty($record['content']['permissions']['canviewthreads'])	OR
			(empty($record['content']['permissions']['canviewothers']) AND ($record['userid'] != $thisUserid))	OR
			empty($record['content']['canview'])
			)
		{
			if (!empty($record['public_preview']))
			{
				$this->nodeApi->getPreviewOnly($record['content']);
			}
			else
			{
				foreach ($record as $field => $value)
				{
					if (($field != 'content') AND !array_key_exists($field, $allCanView))
					{
						unset($record[$field]);
					}
				}

				if (isset($record['content']))
				{
					foreach ($record['content'] as $field => $value)
					{
						if (!array_key_exists($field, $allCanView))
						{
							unset($record['content']['field']);
						}
					}
				}
				$record['lastcontent'] = $record['content']['lastcontent'] = $record['publishdate'];
				$record['lastcontentid'] = $record['content']['lastcontent'] = $record['nodeid'];
				$record['lastcontentauthor'] = $record['content']['lastcontent'] = $record['authorname'];
				$record['lastauthorid'] = $record['content']['lastcontent'] = $record['userid'];
			}
			$record['content']['permissions']['canvote'] = 0;
			$record['content']['permissions']['canuserep'] = 0;
			$record['content']['permissions']['can_flag']= $record['can_flag']= 0;
			$record['content']['permissions']['can_comment'] = $record['can_comment'] = 0;
			$record['content']['hidepostcount'] = 1;
			$record['content']['canreply'] = 0;
		}
	}

	/**
	 * Returns the node content, channel routeid and title, and starter route
	 * and title, but no permissions or other subsidiary data
	 *
	 * @param  int   The Node ID
	 * @param  array Permissions
	 *
	 * @return mixed
	 */
	public function getBareContent($nodeid, $permissions = false)
	{
		$temporary = $this->library->getBareContent($nodeid, $permissions);
		$data = array();

		if (!$this->validate($data, self::ACTION_VIEW, $nodeid, $temporary))
		{
			foreach ($temporary as $key => $node)
			{
				if ($node['public_preview'])
				{
					$temporary[$key] = $this->nodeApi->getPreviewOnly($node);
				}
				else
				{
					throw new vB_Exception_Api('no_permission');
				}

			}
		}

		foreach($temporary AS $key => $node)
		{
			if (empty($node['moderatorperms']['canviewips']))
			{
				$temporary[$key]['ipaddress'] = '';
			}
		}

		return $temporary;
	}

	/**
	 * Gets the main conversation node
	 *
	 * @param  int   The nodeid
	 *
	 * @return array The main conversation node
	 */
	public function getConversationParent($nodeid)
	{
		return $this->library->getConversationParent($nodeid);
	}

	/**
	 * Finds the correct conversation starter for a node
	 *
	 * @param  int Nodeid of the item being checked
	 *
	 * @return int The conversation starter's nodeid
	 */
	protected function getStarter($nodeid)
	{
		return $this->library->getStarter($nodeid);
	}

	/**
	 * The classes  that inherit this should implement this function
	 * It should return the content that should be indexed
	 * If there is a title field, the array key for that field should be 'title',
	 * the rest of the text can have any key
	 *
	 * @param  int   $nodeId - it might be the node (assiciative array)
	 *
	 * @return array $indexableContent
	 */
	public function getIndexableContent($nodeId, $include_attachments = true)
	{
		return $this->library->getIndexableContent($nodeId, $include_attachments);
	}

	/**
	 * Returns an array with bbcode options for the node.
	 *
	 * @param int $nodeId
	 */
	public function getBbcodeOptions($nodeId)
	{
		// This method needs to be overwritten for each relevant contenttype
		return array();
	}

	/**
	 * Gives the current board time- needed to set publishdate.
	 *
	 * @return int
	 */
	public function getTimeNow()
	{
		return vB::getRequest()->getTimeNow();
	}

	/**
	 * This returns the text to quote a node. Used initially for private messaging.
	 *
	 * @param  int    The nodeid of the quoted item
	 *
	 * @return string Quote text
	 */
	public function getQuoteText($nodeid)
	{
		//This must be implemented in the child class
		throw new vB_Exception_Api('feature_not_implemented');
	}


	/**
	 * This returns the text to quote a node. Used initially for private messaging.
	 *
	 * @param  int    The nodeid of the quoted item
	 *
	 * @return string Quote text.
	 */
	public function createQuoteText($nodeid, $pageText)
	{
		//This must be implemented in the child class
		throw new vB_Exception_Api('feature_not_implemented');
	}


	/**
	 * Returns the tables used by this content type.
	 *
	 * @return array Array of table names
	 */
	public function fetchTableName()
	{
		return $this->library->fetchTableName();
	}


	/**
	 * Determines whether a specific node is a visitor message
	 *
	 * @param  int  NodeID
	 *
	 * @return bool
	 */
	public function isVisitorMessage($nodeid)
	{
		return $this->library->isVisitorMessage($nodeid);
	}

	/**
	 * Extracts the video and photo content from text.
	 *
	 * @param  string
	 *
	 * @return mixed  Array of "photo", "video". Each is an array of images.
	 */
	public function extractMedia($rawtext)
	{
		$filter = '~\[video.*\[\/video~i';
		$matches = array();

		preg_match_all($filter, $rawtext, $matches);

		return $matches;
	}

	/**
	 * Adds content info to $result so that merged content can be edited.
	 * This method needs to be implemented in the content subclasses that support merging.
	 *
	 * @param array $result
	 * @param array $content
	 */
	public function mergeContentInfo(&$result, $content)
	{
		// content cannot be merged unless this method is implemented
		throw new vB_Exception_Api('merge_invalid_contenttypes');
	}

	/**
	 * Checks the "limit" permissions for this content item
	 *
	 * @param  array Info about the content that needs to be added
	 *
	 * @return bool  Either true if all the tests passed or thrown exception
	 */
	protected function verify_limits($data)
	{
		// This is where conent general checks should go
		return true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88832 $
|| #######################################################################
\*=========================================================================*/
