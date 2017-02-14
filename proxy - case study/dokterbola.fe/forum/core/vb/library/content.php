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
 * vB_Library_Content
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id: content.php 89714 2016-07-27 19:53:24Z ksours $
 * @access public
 */
abstract class vB_Library_Content extends vB_Library
{
	//override in client- the text name
	protected $contenttype = false;

	//set internally
	protected $contenttypeid;

	//The table for the type-specific data.
	protected $tablename;

	//list of fields that are included in the index
	protected $index_fields = array();

	//Whether we change the parent's text count- 1 or zero
	protected $textCountChange = 0;

	//Whether we inherit viewperms from parents
	protected $inheritViewPerms = 0;

	//Whether this shows on a section listing
	protected $inlist = 1;

	//Does this content show author signature?
	protected $showSignature = false;

	//We need a way to skip flood check for types like Photos, where we'll upload several together.
	protected $doFloodCheck = true;

	protected $assertor;
	protected $nodeApi;
	protected $nodeLibrary;

	/** Whether we are caching node content */
	protected static $cacheNodes;

	protected $channelTypeId;

	protected $cannotDelete = false;

	//To minimize deadlocks, we'll keep a list of queries we need to run, but can delay until after the commit.
	//It needs to be a class property so it can be used by descendants, but will only be used in the add() method.
	protected $qryAfterAdd = array();
	//This allows child classes to delay parent processing after an add until they have done their own add processing inside the transaction.

	//This, plus the node fields, are what somebody can see if they don't have canviewthreads in that channel
	//This should be kept consistent with node api $previewFields. Anything here should be there,
	// although not necessarily the reverse.
	protected $allCanview = array('title' => 'title','channelroute' => 'channelroute', 'channeltitle' =>  'channeltitle', 'channelid' =>  'channelid',
		'edit_reason' =>  'edit_reason', 'edit_userid' =>  'edit_userid', 'edit_username' =>  'edit_username',
		'edit_dateline' =>  'edit_dateline', 'hashistory' =>  'hashistory',  'starternodeoptions' =>  'starternodeoptions',
		'channelnodeoptions' =>  'channelnodeoptions', 'contenttypeclass' => 'contenttypeclass',
		 'hide_title' => 'hide_title', 'hide_author' => 'hide_author', 'hide_publishdate' => 'hide_publishdate',
		 'display_fullincategory' => 'display_fullincategory', 'display_pageviews' => 'display_pageviews',
		'channeltype'  => 'channeltype', 'startertitle' => 'startertitle', 'starterauthor' => 'startertitle');

	/**
	 * If true, then creating a node of this content type will increment
	 * the user's post count. If false, it will not. Generally, this should be
	 * true for topic starters and replies, and false for everything else.
	 *
	 * @var	bool
	 */
	protected $includeInUserPostCount = false;

	//Array of nodeoptions according the channeltype
	protected static $defaultNodeOptions = array(
		'forum' => 138,			//vB_Api_Node::OPTION_AUTOAPPROVE_SUBSCRIPTION + //128
								//vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP + // 8
								//vB_Api_Node::OPTION_ALLOW_POST //2

		'blog' => 522,			//vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP + // 8
								//vB_Api_Node::OPTION_AUTOSUBSCRIBE_ON_JOIN // 512;
								//vB_Api_Node::OPTION_ALLOW_POST //2
		'article' => 138,

		'group' => 10,			//vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP + // 8
								//vB_Api_Node::OPTION_ALLOW_POST //2

		'vm' => 138,
		'pm' => 138,
		'album' => 138,
		'report' => 138,
		'infraction' => 138,
		'default' => 138
	);

	/**
	 * @var	bool	Flag to indicate is we should do incomplete node cleanup. If false, cleanup is not done and the node is just skipped.
	 */
	protected $doIncompleteNodeCleanup = false;

	//This defines the cache levels.  If the user requests say node Data, and we only have
	//cached content data- pass the data anyway.
	const CACHELEVEL_NODE = 1;
	const CACHELEVEL_CONTENT = 2;
	const CACHELEVEL_FULLCONTENT = 3;

	protected function __construct()
	{
		parent::__construct();
		$this->contenttypeid = vB_Types::instance()->getContentTypeId($this->contenttype);
		//The table for the type-specific data.
		$this->assertor = vB::getDbAssertor();
		$this->nodeApi = vB_Api::instanceInternal('node');
		$this->nodeLibrary = vB_Library::instance('node');
		$this->nodeFields = $this->nodeApi->getNodeFields();
		$this->options = vB::getDatastore()->getValue('options');
		$this->channelTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		$config = vB::getConfig();
		$structure = $this->assertor->fetchTableStructure('vBForum:node');
		foreach($structure['structure'] AS $fieldName)
		{
			$this->allCanview[$fieldName] = $fieldName;
		}
		$structure = $this->assertor->fetchTableStructure('vBForum:channel');
		foreach($structure['structure'] AS $fieldName)
		{
			$this->allCanview[$fieldName] = $fieldName;
		}

		self::$cacheNodes = vB::getDatastore()->getOption('cache_node_data');
	}

	public function fetchContentType()
	{
		return $this->contenttype;
	}

	/**Returns the fields that all users can see if they don't have canviewthreads
	*
	*	@return	mixed	array of strings
	*/
	public function getAllCanView()
	{
		return $this->allCanview;
	}

	public function fetchContentTypeId()
	{
		return $this->contenttypeid;
	}

	/**
	 * Returns textCountChange property
	 * @return int
	 */
	public function getTextCountChange()
	{
		return $this->textCountChange;
	}

	/**
	 * Returns inlist property
	 * @return int
	 */
	public function getInlist()
	{
		return $this->inlist;
	}



	/**
	 * Adds a new node.
	 *
	 *	@todo	finish documenting this function
	 *
	 *	@param	mixed	$data		Array of field => value pairs which define the record. Data-types & key names:
	 *									int		'parentid'		Nodeid of the parent, i.e. node this attachment is being added under.
	 *									int		'userid'		Optional. Userid of the user who is adding this record. If not provided, it will use the
	 *															current session's user.
	 *									string	'authorname'	Optional. Name of the user who is adding this record. If not provided, it will use the username
	 *															of userid above.
	 *									string	'ipaddress'		Optional. IP of request. Will be fetched from current request if skipped.
	 *									???		'protected'		???
	 *									int		'starter'		???
	 *									int		'routeid'		???
	 *									???		'prefixid'		???
	 *									???		'iconid'		???
	 *									int		'created'		Optional. Unix timestamp of creation date. If skipped it will use the value returned by
	 *															vB::getRequest()->getTimeNow() (TIMENOW).
	 *									int		'publishdate'	Optional. Unix timestampe of publish date. If skipped, it will use TIMENOW. If
	 *															'publish_now' is provided, publishdate will be overwritten by TIMENOW
	 *									mixed	'publish_now'	Optional.  If not empty, publishdate will be overwritten by TIMENOW
	 *									???		'viewperms'		???
	 *									???		'featured'		Optional. ???(purpose/description)???
	 *															Will only be used if the CURRENT USER has the moderatorpermissions.cansetfeatured
	 *															channel permission.
	 *									string	'title'			???
	 *									string	'htmltitle'		???
	 *									string	'urlident'		???
	 *									int		'setfor'		Only used by visitor messages. Userid of intended visitor message recipient.
	 *									???		???				???
	 *								Can also contain node fields. @see vB_Api_Node::getNodeFields() or the node table structure for these fields
	 *	@param	array	$options	Array of options for the content being created.
	 * 								Understands skipTransaction, skipFloodCheck, floodchecktime, many subclasses have skipNotification.
	 *
	 * 	@return	mixed		array with nodeid (int), success (bool), cacheEvents (array of strings), nodeVals (array of field => value).
	 *
	 *	@throws	vB_Exception_Api('need_parent_node')			If required $data['parentid'] is empty.
	 *	@throws	vB_Exception_Api('invalid_request')				If parent is closed and CURRENT USER lacks the moderatorpermissions.canmoderateposts channel permission.
	 *	@throws	vB_Exception_Api('invalid_request')				If parent is a category channel and this is not a channel
	 *	@throws	vB_Exception_Api('cannot_reply_to_redirect')	If parent is a "Redirect" content type
	 *	@throws	vB_Exception_Api('invalid_route_contact_vbulletin_support')			If there is no parent routenew record for the ('vB5_Route_Conversation',
	 *															'vB5_Route_Article') classes.
	 *	@throws	vB_Exception_Api('postfloodcheck')				If CURRENT USER is not an administrator and the user failed the flood check.
	 *	@throws	vB_Exception_Api('invalid_data')				If not enough data was provided to add anything to the node table.
	 *	@throws	vB_Exception_Api('invalid_data')				If this is a visitor message and $data['setfor'] was invalid
	 *	@throws	vB_Exception_Api('invalid_data')				If adding a record to the node tabled failed
	 *
	 *	@access public
	 */
	public function add($data, array $options = array())
	{
		if (empty($data['parentid']))
		{
			throw new vB_Exception_Api('need_parent_node');
		}

		// *************************************
		// * Fill some default data if missing *
		// *************************************
		if (empty($data['userid']))
		{
			$user = vB::getCurrentSession()->fetch_userinfo();
			$data['authorname'] = $user['username'];
			$userid = $data['userid'] = $user['userid'];
		}
		else
		{
			$userid = $data['userid'];
			if (empty($data['authorname']))
			{
				$data['authorname'] = vB_Api::instanceInternal('user')->fetchUserName($userid);
			}
		}

		if (empty($data['ipaddress']))
		{
			$data['ipaddress'] = vB::getRequest()->getIpAddress();
		}

		$parentInfo = self::fetchFromCache($data['parentid'], self::CACHELEVEL_FULLCONTENT);

		if ($parentInfo AND $parentInfo['found'] AND $parentInfo['found'][$data['parentid']])
		{
			$parentInfo = $parentInfo['found'][$data['parentid']];
		}
		else
		{
			$parentInfo = vB_Library::instance('node')->getNodeContent($data['parentid']);
			$parentInfo = $parentInfo[$data['parentid']];
		}


		//We always inherit the parents "protected" value
		if (!isset($data['protected']) || $data['protected'] != 1)
		{
			$data['protected'] = $parentInfo['protected'];
		}

		$channelContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Channel');

		// we cannot add content to category channels
		if (
			$parentInfo['contenttypeid'] == $channelContentTypeId AND $parentInfo['category'] != 0
			AND
			$this->contenttypeid != $channelContentTypeId
		)
		{
			throw new vB_Exception_Api('invalid_request');
		}

		$redirectContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Redirect');

		if ($parentInfo['contenttypeid'] == $redirectContentTypeId)
		{
			throw new vB_Exception_Api('cannot_reply_to_redirect');
		}

		// the starter cannot be a channel, so if this is the case set the starter after adding the node
		//we also need to set the routeid
		if (($this->contenttypeid != $channelContentTypeId) AND ($parentInfo['contenttypeid'] != $channelContentTypeId))
		{
			//The parent already has a conversation route
			if (!empty($parentInfo['starter']))
			{
				$data['starter'] = $parentInfo['starter'];
			}
			else
			{
				//if the parent is a text type but the starter isn't set then we need to get the
				$data['starter'] = $this->getStarter($data['parentid']);
			}
			$data['routeid'] = $parentInfo['routeid'];
		}
		else if ($this->contenttypeid != $channelContentTypeId)
		{
			// TODO refactor this and create a vB5_Route method to get this info,
			// since the route info may be cached (in memory) internally in vB5_Route.
			$route = $this->assertor->getRow('routenew', array('contentid' => $data['parentid'], 'class' => array('vB5_Route_Conversation', 'vB5_Route_Article')));

			if (empty($route) OR !empty($route['errors']))
			{
				throw new vB_Exception_Api('invalid_route_contact_vbulletin_support');
			}

			$data['routeid'] = $route['routeid'];
		}

		// Verify prefixid
		if ($this->contenttypeid != $channelContentTypeId AND !empty($data['prefixid']))
		{
			$this->verifyPrefixid($data['prefixid']);
		}
		else
		{
			// Channel can't have a prefix
			unset($data['prefixid']);
		}

		// Verify post iconid
		if ($this->contenttypeid != $channelContentTypeId AND !empty($data['iconid']))
		{
			$this->verifyPostIconid($data['iconid']);
		}
		else
		{
			// Channels can't have a post icon
			unset($data['iconid']);
		}

		//we generally do a flood check- when was this user's last post?
		if (empty($options['skipFloodCheck']) AND ($this->options['floodchecktime'] > 0) AND ($this->doFloodCheck) AND !vB::getUserContext()->isAdministrator())
		{
			if ($lastPostElapsed = $this->isFlood($data))
			{
				throw new vB_Exception_Api('postfloodcheck', array($this->options['floodchecktime'],
						$this->options['floodchecktime'] - $lastPostElapsed));
			}
		}

		$data['lastupdate'] = vB::getRequest()->getTimeNow();

		if (empty($data['created']))
		{
			$data['created'] = vB::getRequest()->getTimeNow();
		}

		// set publishdate to now...
		if (!empty($data['publish_now']))
		{
			$data['publishdate'] = vB::getRequest()->getTimeNow();
		}
		else if (!isset($data['publishdate']))
		{
			$data['publishdate'] = vB::getRequest()->getTimeNow();
		}

		//It's possible we already have a nodeid.
		$nodevals = array();

		if ($this->isPublished($data))
		{
			$nodevals['showpublished'] = 1;
		}
		else
		{
			$nodevals['showpublished'] = 0;
		}

		// inherit showpublish from the parent
		if (($parentInfo['showpublished'] == 0) AND ($this->contenttype != 'vBForum_PrivateMessage'))
		{
			$nodevals['showpublished'] = 0;
		}

		// inherit viewperms from parent
		if ($this->inheritViewPerms OR !isset($data['viewperms']))
		{
			$nodevals['viewperms'] = $parentInfo['viewperms'];
			unset($data['viewperms']);
		}

		$nodevals['inlist'] = $this->inlist;

		//If this user doesn't have the featured permission and they are trying to set it,
		//Let's just quietly unset it.
		if (isset($data['featured']))
		{
			if (!vB::getUserContext()->getChannelPermission('moderatorpermissions', 'cansetfeatured', $data['parentid']))
			{
				unset($data['featured']);
			}
		}

		if (empty($data['htmltitle']) AND !empty($data['title']))
		{
			$data['htmltitle'] = vB_String::htmlSpecialCharsUni(vB_String::stripTags($data['title']), false);
		}

		if (empty($data['urlident']) AND !empty($data['title']))
		{
			$data['urlident'] = vB_String::getUrlIdent($data['title']);
		}

		// check for accidental duplicate post

		$crc32 = $this->duplicateCheck($data, $options);

		$parentid = $data['parentid'];
		//let's set the appropriate fields in the node table
		foreach ($data as $field => $value)
		{
			if (in_array($field, $this->nodeFields))
			{
				$nodevals[$field] = $value;
				if (!isset($this->contentDupFields) OR !in_array($field, $this->contentDupFields))
				{
					unset($data[$field]);
				}
			}
		}
		if (empty($nodevals))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		if (empty($nodevals['userid']))
		{
			$nodevals['userid'] = vB::getCurrentSession()->get('userid');
		}

		//default to open
		if(!isset($nodevals['open']))
		{
			$nodevals['open'] = 1;
		}

		//popagate show open from parent.  Some people can post to a closed node.
		$nodevals['showopen'] = ($nodevals['open'] AND $parentInfo['showopen']) ? 1 : 0;

		if (empty($this->contenttypeid))
		{
			$this->contenttypeid = vB_Types::instance()->getContentTypeId($this->contenttype);
		}

		$nodevals['contenttypeid'] = $this->contenttypeid;
		$nodevals[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;

		$timeNow = vB::getRequest()->getTimeNow();
		//check for next update needed
		if (isset($nodevals['publishdate']) AND ($nodevals['publishdate'] > $timeNow))
		{
			if (empty($nodevals['unpublishdate']) OR ($nodevals['unpublishdate'] > $nodevals['publishdate']))
			{
				$nodevals['nextupdate'] = $nodevals['publishdate'];
			}
		}
		else if (!empty($nodevals['unpublishdate']) AND ($nodevals['unpublishdate'] > $timeNow))
		{
			$nodevals['nextupdate'] = $nodevals['unpublishdate'];
		}

		//We need the correct nodeoptions setting. If this is not a channel we should inherit.
		$parentFullContent = vB_Library::instance('node')->getNodeFullContent($nodevals['parentid']);
		if ($this->contenttype == 'vBForum_Channel')
		{
			if (empty($nodevals['nodeoptions']) OR !is_numeric($nodevals['nodeoptions']))
			{
				if(!empty($parentFullContent[$nodevals['parentid']]['channeltype']))
				{
					$nodevals['nodeoptions'] = self::$defaultNodeOptions[$parentFullContent[$nodevals['parentid']]['channeltype']];
				}
				else
				{
					$nodevals['nodeoptions'] = self::$defaultNodeOptions['default'];
				}
			}
		}
		else //if is not blog, we inherit from the parent.
		{
			if($parentFullContent[$nodevals['parentid']]['channeltype'] == 'blog')
			{
				$nodevals['nodeoptions'] = self::$defaultNodeOptions[$parentFullContent[$nodevals['parentid']]['channeltype']];
			}
			else
			{
				$nodevals['nodeoptions'] = $parentFullContent[$nodevals['parentid']]['nodeoptions'];
			}
		}

		$vmParentid = vB_Api::instanceInternal('node')->fetchVMChannel();
		if ($vmParentid == $parentInfo['nodeid'])
		{
			if (
				!vB::getUserContext()->hasPermission('visitormessagepermissions', 'followforummoderation')
					AND
				$nodevals['setfor'] != $nodevals['userid']
			)
			{
				$nodevals['approved'] = 0;
				$nodevals['showapproved'] = 0;
			}

			if (!intval($nodevals['setfor']))
			{
				throw new vB_Exception_Api('invalid_data');
			}
		}

		try
		{
			if (empty($options['skipTransaction']))
			{
				$this->assertor->beginTransaction();
			}
			$this->qryAfterAdd = array();
			$nodeid = $this->assertor->assertQuery('vBForum:node', $nodevals);

			if (!$nodeid)
			{
				if (empty($options['skipTransaction']))
				{
					$this->assertor->rollbackTransaction();
				}
				throw new vB_Exception_Api('invalid_data');
			}

			if (is_array($nodeid))
			{
				$nodeid = $nodeid[0];
			}

			//Let's set the lastcontent and lastcontentid values
			if (($this->contenttype != 'vBForum_Channel') AND !empty($nodevals['publishdate']))
			{
				$this->qryAfterAdd[] = array('definition' => 'vBForum:node', 'data' => array(
						vB_dB_Query::TYPE_KEY=> vB_dB_Query::QUERY_UPDATE,
						'nodeid' => $nodeid,
						'lastcontent' => $nodevals['publishdate'],
						'lastcontentid' => $nodeid,
						'lastcontentauthor' => $nodevals['authorname'],
						'lastauthorid' => $nodevals['userid'],
					));
			}

			if (!empty($crc32))
			{
				$this->qryAfterAdd[] = array('definition' => 'vBForum:nodehash', 'data' => array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
					'nodeid' => $nodeid,
					'dupehash' => $crc32,
					'userid' => $userid,
					'dateline' => vB::getRequest()->getTimeNow()
				));
			}
			$cacheEvents = array("fUserContentChg_" . $nodevals['userid'], 'userChg_' . $userid);
			if ($this->contenttypeid == $channelContentTypeId)
			{
				$cacheEvents[] = "nodeChg_" . $nodevals['parentid'] ;
			}
			else
			{
				$channelNodeid = 0;
				if (!isset($nodevals['starter']))
				{
					//The only reason this would be unset is that THIS is the starter.
					$update = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						'starter' => $nodeid, 'nodeid' => $nodeid);
					$this->assertor->assertQuery('vBForum:node', $update);
					//Since this is the starter, the parent is the channel.
					$cacheEvents[] = "nodeChg_" . $parentid;
				}
				else
				{
					//we need cache events to for the starter, which we have, and the
					//channel, which we don't. But we know it's the parent of the starter.
					$starterNodeInfo = vB_Library::instance('node')->getNodeBare($nodevals['starter']);
					$cacheEvents[] = "nodeChg_" . $nodevals['starter'];
					$cacheEvents[] = "nodeChg_" . $starterNodeInfo['parentid'] ;
					if ($nodevals['starter'] != $parentid)
					{
						$cacheEvents[] = "nodeChg_" . $parentid;
					}
					$cacheEvents[] = "fUserContentChg_" . $starterNodeInfo['userid'];
					$channelNodeid = $starterNodeInfo['parentid'];
				}

				// Check "Moderate comments before displaying"
				// If !$channelNodeid, the node is a starter not a comment so we skip it.
				if ($channelNodeid)
				{
					$channel = vB_Library::instance('node')->getNode($channelNodeid);
					// If it's the owner of channel who posted the comment, we need to approve it
					// TODO: we may need to allow Admin and moderator to bypass the limit?
					if ($nodevals['userid'] != $channel['userid'] AND $channel['moderate_comments'])
					{
						$this->qryAfterAdd[] = array('definition' => 'vBForum:node', 'data' => array(
							vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
							'approved' => 0,
							'showapproved' => 0,
							vB_dB_Query::CONDITIONS_KEY => array(
								'nodeid' => $nodeid,
							)));
					}
				}
			}
			//Now update the closure table.
			$this->assertor->assertQuery('vBForum:closure', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'parent' => $nodeid,
				'child' => $nodeid,
				'depth' => 0,
				'publishdate' => $nodevals['publishdate'],
			));

			$this->assertor->assertQuery('vBForum:addClosure', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'nodeid' => $nodeid,
			));

			// Clear autosave table of this items entry
			if (vB::getCurrentSession()->get('userid')
				AND
				!empty($data['rawtext'])
			)
			{
				$this->qryAfterAdd[] = array('definition' => 'vBForum:autosavetext',
					'data' => array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
						'userid'   => vB::getCurrentSession()->get('userid'),
						'nodeid'   => 0,
						'parentid' => $parentid
					));
			}

			// Insert the content-type specific data
			if (!is_array($this->tablename))
			{
				$tables = array($this->tablename);
			}
			else
			{
				$tables = $this->tablename;
			}

			foreach ($tables as $table)
			{
				$structure = $this->assertor->fetchTableStructure('vBForum:' . $table);
				$queryData = array();
				$queryData[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;
				$queryData['nodeid'] = $nodeid;
				foreach ($structure['structure'] AS $fieldname)
				{
					if (isset($data[$fieldname]))
					{
						$queryData[$fieldname] = $data[$fieldname];
					}
				}
				$this->assertor->assertQuery('vBForum:' . $table, $queryData);
			}

			if (empty($options['skipTransaction']))
			{
				$this->beforeCommit($nodeid, $data, $options, $cacheEvents, $nodevals);
				$this->assertor->commitTransaction();
			}
		}
		catch (exception $e)
		{
			//Catch the transaction.
			if (empty($options['skipTransaction']))
			{
				$this->assertor->rollbackTransaction();
			}
			throw $e;
		}

		if (empty($options['skipTransaction']))
		{
			//The child classes that have their own transactions all set this to true.  So it's always called just once.
			$this->afterAdd($nodeid, $data, $options, $cacheEvents, $nodevals);
		}

		//For the children we need to pass extra data, so they can pass to afterAdd()
		return array(
			'nodeid'      => $nodeid,
			'success'     => true,
			'cacheEvents' => $cacheEvents,
			'nodeVals'    => $nodevals,
		);
	}


	/**
	 *	Called by the first child class just before the commit happens.
	 *	This is for logic that needs to be in the transaction, but
	 *	relies on the child class having finished all of its processing
	 *	(the flow is that content::add gets called first and then the
	 *	child does it's thing, primary because the child needs the nodeid for
	 *	the created node record).
	 *
	 *	Takes the same parameters as afterAdd
	 */
	protected function beforeCommit($nodeid, $data, $options, $cacheEvents, $nodevals)
	{
		// Handle Attachments
		// The text library (and most derivatives) can have attachments,
		// for the others, this is a no-op. This is called here from the
		// parent content class (instead of the text library) so that it
		// runs before notifications are sent (here in afterAdd() below)
		// (see VBV-14022) but after photos are added in the gallery
		// library add() method (see VBV-13883).  Moved inside the transaction
		// for VBV-16313 (but after child processing due to VBV-13883.

		$this->handleAttachments('add', $nodeid, $data, $options);
	}

	/**
	 * Operations to be done after adding a node.  Putting actions here allow child classes to minimize time keeping transaction open.
	 *
	 * @param 	int		$nodeid
	 * @param 	mixed	$data			Array of data being added
	 * @param	mixed	$options		Array of options for the content being created- passed from  add()
	 * 									Understands skipNotification(s), skipUpdateLastContent.
	 * @param	mixed	$cacheEvents	Array of strings- cache events to be called
	 * @param	mixed	$nodevals		Array of field => value pairs representing the node table field values passed to create the record.
	 *									Currently most are not used but all are passed.
	 */
	protected function afterAdd($nodeid, $data, $options, $cacheEvents, $nodevals)
	{
		$userid = vB::getCurrentSession()->get('userid');

		// We have singular & plural scattered throughout the code. Going to make the actual check use plural (look for
		//  empty($options['skipNotifications']) below) and catch/convert any singular to plural so that both work.
		// VBV-13609
		if (isset($options['skipNotification']))
		{
			$options['skipNotifications'] = $options['skipNotification'];
			unset($options['skipNotification']);
		}

		$this->updateNodeOptions($nodeid, $data);
		vB_Cache::instance()->allCacheEvent($cacheEvents);

		//If published and if this is a text class we should update the text counts.
		if ($textCountChange = $this->textCountChange)
		{
			// apparently showapproved isn't always set. I guess since the default value for approved & showapproved is 1, we only set it to 0 if
			// necessary. As such, if it's not set, let's assume it's been approved
			$approved = (
				(isset($nodevals['showapproved'])? $nodevals['showapproved'] : true)
				AND (isset($nodevals['approved'])? $nodevals['approved'] : true)
			);	// parentheses, parentheses everywhere

			if ($nodevals['showpublished'] AND $approved)
			{
					vB_Library::instance('node')->updateParentCounts($nodeid, $textCountChange, 0, $textCountChange, 0, 1, (!isset($options['skipUpdateLastContent']) OR !$options['skipUpdateLastContent']));
			}
			else
			{
				$published = $nodevals['showpublished'];
				// if it's not showpublished & showapproved, we shouldn't update the parents' last content data
				$updatelastcontent = false;
				vB_Library::instance('node')->updateParentCounts($nodeid, 0, $textCountChange, 0, $textCountChange, $published, $updatelastcontent);
			}
		}


		foreach ($this->qryAfterAdd AS $qry)
		{
			$this->assertor->assertQuery($qry['definition'], $qry['data']);
		}
		//handle the 'index' setting;
		$index = empty($data['noIndex']);

		if ($index)
		{
			vB_Library::instance('search')->index($nodeid);
		}
		vB_Library::instance('search')->purgeCacheForCurrentUser();
		$this->nodeLibrary->clearCacheEvents($nodeid);
		$node = vB_Library::instance('node')->getNode($nodeid);

		if ($this->isVisitorMessage($nodeid))
		{
			if (!empty($node['setfor']))
			{
				vB_Search_Core::instance()->purgeCacheForUser($node['setfor']);
			}
		}
		vB_Cache::instance()->allCacheEvent('fContentChg_' . $data['parentid']);

		// update tags
		if (!empty($data['tags']))
		{
			$tagRet = vB_Api::instanceInternal('tags')->addTags($nodeid, $data['tags']);
		}

		//Let's see if we need to send notifications
		//Private messages are different. Let the subclass handle them.
		// (Do PMs even have notifications? Why?)
		if (($this->contenttype != 'vBForum_PrivateMessage') AND empty($options['skipNotifications']))
		{
			$node = vB_Library::instance('node')->getNode($nodeid, false, true);	// we need to get the full content, to ensure 'channelid' is there.
			$notificationLib = vB_Library::instance('notification');
			//If this is a visitor message we always send a message
			// we have the $node from above
			if ($this->isVisitorMessage($nodeid) AND !empty($node['setfor']))
			{
				$recipients = array($node['setfor']);
				$notificationLib->triggerNotificationEvent('new-visitormessage', array('sentbynodeid' => $nodeid), $recipients);
			}
			else if ($node['starter'] > 0)
			{
				/*
				 * Warning: Currently the content library doesn't have a defined set of rules on whether
				 * this particular node should generate notifications or not. For example, if this node is
				 * a grand-child of a thread starter, it could be a comment or a photo in a gallery reply
				 * but only comments should send out notifications.
				 * At the moment, each photo added to a gallery doesn't generate individual notifications
				 * because the front-end createcontent controller sets the skipNotifications option when
				 * calling content_photo API's add().
				 *
				 * Note, each subclass of vB_Notification's validateAndCleanNotificationData() needs to check
				 * the context data and prevent attachments etc from sending notifications.
				*/
				$notificationLib->triggerNotificationEvent('new-content', array('sentbynodeid' => $nodeid));
			}

			// Don't forget about calling insert!
			$notificationLib->insertNotificationsToDB();
		}

		// @TODO VBV-2303 we can't send notifications as guest... private message library do checkFolders() which won't be valid for guests.
		if (empty($options['skipNotifications']) AND intval(vB::getCurrentSession()->get('userid')))
		{
			// is VBV-2303 still an issue?
		}

		// Subscribe this user to this topic if appropriate
		$userinfo = vB::getCurrentSession()->fetch_userinfo();
		if ($userinfo['autosubscribe'])
		{
			$starterid = 0;

			if (!empty($nodevals['starter']))
			{
				$starterid = $nodevals['starter'];
			}

			if (empty($starterid))
			{
				$node = vB_Library::instance('node')->getNode($nodeid);
				$starterid = $node['starter'];
			}

			// subscribe to topic
			vB_Api::instance('follow')->add($starterid, vB_Api_Follow::FOLLOWTYPE_CONTENT);
		}

		// Update the post count for this user (content add)
		$this->incrementUserPostCount($node);

		//send moderator notification emails.
		if (($this->textCountChange > 0) AND empty($this->skipModNotification))
		{
			$this->sendModeratorNotification($nodeid);
		}

	}

	/**
	 * Sends emails to moderators configured in admincp
	 */
	protected function sendModeratorNotification($nodeid)
	{
		// This is the list of ALL mods who have either the newpostemail or the newthreademail option enabled
		// We'll go through this list and figure out which ones are moderators of the ancestor channels of $nodeid

		$notify = vB_Cache::instance(vB_Cache::CACHE_STD)->read('vB_Mod_PostNotify');

		if ($notify === false)
		{
			$notify = array();
			$bitFields = vb::getDatastore()->getValue('bf_misc_moderatorpermissions');
			$modQry = $this->assertor->assertQuery('vBForum:modPostNotify',
				array(
					'bfPost' => $bitFields['newpostemail'],
					'bfTopic' => $bitFields['newthreademail']
				)
			);

			$events = array();
			if ($modQry->valid())
			{
				foreach ($modQry as $mod)
				{
					//Every time a moderator changes emails, is deleted, etc, we have to invalidate it.
					$events[$mod['userid']] = 'userChg_' . $mod['userid'];
					if (!isset($notify[$mod['nodeid']]))
					{
						$notify[$mod['nodeid']] = array(
							"posts" => array(),
							"topics" => array()
						);
					}

					//this used to be (sort of) handled in the modPostNotify query.  However it
					//only accounted for primary groups, not secondary groups.  It shouldn't happen
					//anyway, given that the global moderator record (nodeid 0) shouldn't be there
					//if the user isn't a supermod
					if($mod['nodeid'] == 0)
					{
						$userContext = vB::getUserContext($mod['userid']);
						if(!$userContext->isSuperMod())
						{
							continue;
						}
					}

					if ($mod['notifypost'] > 0)
					{
						$notify[$mod['nodeid']]['posts'][] = array($mod['email'], $mod['languageid'], $mod['userid']);
					}

					if ($mod['notifytopic'] > 0)
					{
						$notify[$mod['nodeid']]['topics'][] = array($mod['email'], $mod['languageid'], $mod['userid']);
					}
				}
			}

			// all these user change events could be a lot...
			$events['vB_ModPostNotifyChg'] = 'vB_ModPostNotifyChg';
			vB_Cache::instance(vB_Cache::CACHE_STD)->write('vB_Mod_PostNotify', $notify, 1440, $events);
		}

		// grab parents of the added node, and see if we have any moderators on the channel
		$parents = vB::getDbAssertor()->getRows('vBForum:closure',
			array('child' => $nodeid)
		);
		$notifyList = array();	// the actual list of emails that are associated with this node.

		$node = vB_Library::instance('node')->getNodeFullContent($nodeid);
		$node = $node[$nodeid];
		if ($node['starter'] == $node['nodeid'])
		{
			$notifyKey = "topics";
		}
		else
		{
			$notifyKey = "posts";
		}

		foreach ($parents AS $closure)
		{
			$parentid = $closure['parent'];
			if (array_key_exists($parentid, $notify))
			{
				// each found list is an array of emails, so we have to merge
				$notifyList = array_merge($notifyList, $notify[$parentid][$notifyKey]);
			}
		}

		// Global moderators case. At the moment, the global mods in the moderator table has nodeid = 0 so the
		// closure check above leaves them out.
		if (!empty($notify[0]))
		{
			foreach($notify[0][$notifyKey] AS $item)
			{
				//check that the user can see the forum before we send the email based on a global setting
				if(isset($item[2]))
				{
					$userContext = vB::getUserContext($item[2]);
					$canViewChannel = $userContext->getChannelPermission('forumpermissions', 'canview', $node['nodeid'], false, $node['parentid']);
					if ($canViewChannel)
					{
						$notifyList[] = $item;
					}
				}
				//replicate old behavior with old cache value -- can be removed after a release
				else
				{
					$notifyList[] = $item;
				}
			}
		}

		if (empty($notifyList))
		{
			return;
		}

		//its vaguely possible that we'll have the same email with different languages
		//since multiple users can, in rare cases, have the same email.  This is
		//enough of an edge case that we'll ignore it and just use whichever
		//language the dedup logic ends up with
		$tempList = array();
		foreach($notifyList AS $item)
		{
			$tempList[$item[0]] = $item;
		}
		$notifyList = array_values($tempList);

		// grab some data for the message
		$userinfo = vB::getCurrentsession()->fetch_userinfo();
		$forumName = vB::getDatastore()->getOption('bbtitle');
		$starter = vB_Library::instance('node')->getNodeFullContent($node['starter']);
		$starter = $starter[$node['starter']];
		$threadTitle = $starter['title'];
		// Below is the call to fetch the url to the thread starter
		// $threadLink = vB5_Route::buildUrl($starter['routeid'] . '|fullurl', $starter);
		// If we want the direct link to the post, below is what's needed
		$routeInfo = array('nodeid' => $nodeid);
		// Using the normal vB5_Route::buildURL can throw an exception, because it'll likely use the
		// conversation route which checks permissions on the *current* user in the constructor and
		// throw an exception.
		// So we'll use vB5_Route_Node
		$nodeLink = vB5_Route::buildUrl('node|fullurl', $routeInfo);

		$phrases = array(
			'text' => array(
				'new_post_notification_a_b_c_d_e_f',
				$userinfo['username'],
				$node['channeltitle'],
				$forumName,
				$threadTitle,
				$nodeLink,
				$node['rawtext']
			),
			'subject' => array(
				'new_post_in_forum_x',
				$node['channeltitle']
			)
		);

		//really don't like calling an API from a library, but can't
		//refacter the phrase API properly right now.
		$phraseApi = vB_Api::instanceInternal('phrase');

		// send emails
		foreach($notifyList AS $info)
		{
			//temporary in case we have old cached data
			//should be able to remove a version after release.
			if(count($info) == 2)
			{
				list($email, $languageid) = $info;
			}
			else
			{
				list($email, $languageid, ) = $info;
			}

			if ($email != $userinfo['email'])
			{
				//we could try to cache the rendered strings but I don't think its
				//worth the extra trouble.  We won't do more than one DB lookup per language
				$strings = $phraseApi->renderPhrases($phrases, $languageid);
				vB_Mail::vbmail($email, $strings['phrases']['subject'], $strings['phrases']['text'], true, '', '', '', TRUE);
			}
		}
	}

	protected function isFlood($data)
	{
		$isFlood = false;

		$node = vB::getDbAssertor()->getRow('vBForum:node',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'userid', 'value' => $data['userid'], 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'created', 'value' =>  vB::getRequest()->getTimeNow() - $this->options['floodchecktime'], 'operator' => vB_dB_Query::OPERATOR_GT),
					array('field' => 'contenttypeid', 'value' => $this->contenttypeid, 'operator' => vB_dB_Query::OPERATOR_EQ),
				)
			),
			array('field' => array('created'), 'direction' => array(vB_dB_Query::SORT_DESC))
		);

		if (!empty($node))
		{
			$lastPostElapsed = vB::getRequest()->getTimeNow() - $node['created'];
			if ($lastPostElapsed < $this->options['floodchecktime'])
			{
				$isFlood = true;
			}
			vB_Library_Content::writeToCache(array($node), vB_Library_Content::CACHELEVEL_NODE);
		}

		if ($isFlood)
		{
			return $lastPostElapsed;
		}
		return false;
	}

	/**
	 * Checks accidental duplicate posting
	 * @param array $data
	 * @param array $options - optional
	 * @return string CRC32 or boolean
	 */
	protected function duplicateCheck($data, $options = array())
	{
		if (!empty($options['skipDupCheck']))
		{
			return false;
		}
		$crc32 = $this->getCRC32($data);

		if ((!defined('VB_AREA') OR VB_AREA != 'Upgrade')
			AND (!defined('VB_TEST'))
			AND !empty($crc32) AND $this->assertor->getRow('vBForum:nodehash', array(vB_dB_Query::CONDITIONS_KEY => array(
				array('field' => 'dupehash', 'value' => $crc32, 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'userid', 'value' => $data['userid'], 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'dateline', 'value' => vB::getRequest()->getTimeNow() - 300, 'operator' => vB_dB_Query::OPERATOR_GT) // less than 5 minutes
		))))
		{
			throw new vB_Exception_Api('duplicate_post');
		}
		return $crc32;
	}

	/**
	 * Sets node options from individual fields.
	 *
	 * 	@param	int
	 * 	@param	mixed	array of field => value pairs. Both must be strings, but non-matching keys will be ignored.
	 *
	 */
	protected function updateNodeOptions($nodeid, $data)
	{
		//set noteoptions if we got them.
		if (!isset($data['nodeoptions']))
		{
			$nodeOptions = vB_Api::instanceInternal('node')->getOptions();
			$options = array();
			foreach ($nodeOptions AS $optionKey => $optionVal)
			{

				if (isset($data[$optionKey]))
				{
					$options[$optionKey] = $data[$optionKey];
				}
			}

			if (!empty($options))
			{
				vB_Library::instance('node')->setNodeOptions($nodeid, $options);
			}
		}
		else
		{
			vB_Library::instance('node')->setNodeOptions($nodeid, $data['nodeoptions']);
		}

	}

	/**
	 * Increments the number of posts for a user. This function will also update user's "lastpost" field
	 *
	 * When creating items, this is called from the content library,
	 * For all other state changes (approve, undelete, etc) it is called from the node library
	 *
	 * @param	array	Array of node information for the affected node
	 */
	public function incrementUserPostCount(array $node)
	{
		//be a bit careful, this query doesn't get all of the columns of the node table.  I want to avoid pulling down
		//the entire content of at thread because we undeleted it.  However it means that we need to makes sure that the
		//functions we call below have all of the fields of the node table that they need.
		$children = vB::getDbAssertor()->assertQuery('vBForum:selectPostsForPostCount', array('rootid' => $node['nodeid']));

		$parentShouldChange = array();
		$userInfo = array();

		foreach($children as $child)
		{
			//if our parent didn't want to change its children we don't want to do anything or change any
			//of *our* children
			if (isset($parentShouldChange[$child['parentid']]) AND !$parentShouldChange[$child['parentid']])
			{
				$parentShouldChange[$child['nodeid']] = false;
				continue;
			}

			$parentShouldChange[$child['nodeid']] = $this->shouldChangeUserPostCountForChildNodes($child);

			//this function, unfortunately, has problems.  However that needs to be a later fix.
			if (!$this->countInUserPostCount($child))
			{
				continue;
			}

			$userid = $child['userid'];
			if (!isset($userInfo[$userid]))
			{
				$userInfo[$userid] = array('posts' => 0, 'lastposttime' => 0, 'lastpostid' => 0);
			}

			//we want to find the last post that we've "reactivated".  We'll check against the actual
			//user's last post date before we set that information in the user table.
			if ($child['publishdate'] > $userInfo[$userid]['lastposttime'])
			{
				$userInfo[$userid]['lastposttime'] = $child['publishdate'];
				$userInfo[$userid]['lastpostid'] = $child['nodeid'];
			}

			$userInfo[$userid]['posts']++;
		}

		vB_Library::instance('user')->incrementPostCountForUsers($userInfo);
	}

	/**
	 * Decrements the number of posts for a user
	 *
	 * When hard-deleting items, this is called from the content library,
	 * For all other state changes (unapprove, soft-delete, etc) it is called from the node library
	 *
	 * @param	array	Array of node information for the affected node
	 * @param	(unpublish|unapprove)	Pass one of these strings when decrementUserPostCount is called *after* unpublishing or unapproving a post
	 */
	public function decrementUserPostCount(array $node, $action = '')
	{
		//be a bit careful, this query doesn't get all of the columns of the node table.  I want to avoid pulling down
		//the entire content of at thread because we undeleted it.  However it means that we need to makes sure that the
		//functions we call below have all of the fields of the node table that they need.
		$children = vB::getDbAssertor()->assertQuery('vBForum:selectPostsForPostCount', array('rootid' => $node['nodeid']));

		$parentShouldChange = array();
		$userInfo = array();

		//despite comments indicating that
		foreach($children as $child)
		{
			//mimics the original behavior where the action wasn't passed into the recursive functions.  Despite the
			//comments doing it that way caused more failures on the simple unit test.  Leaving it the old way for
			//now to prevent changes from the status quo.  It's not clear that either approach actually works.
			$thisaction = ($child['nodeid'] == $node['nodeid'] ? $action : '');

			//if our parent didn't want to change its children we don't want to do anything or change any
			//of *our* children
			if (isset($parentShouldChange[$child['parentid']]) AND !$parentShouldChange[$child['parentid']])
			{
				$parentShouldChange[$child['nodeid']] = false;
				continue;
			}

			$parentShouldChange[$child['nodeid']] = $this->shouldChangeUserPostCountForChildNodes($child, $action);

			//this function, unfortunately, has problems.  However that needs to be a later fix.
			if (!$this->countInUserPostCount($child, $action))
			{
				continue;
			}

			$userid = $child['userid'];
			if (!isset($userInfo[$userid]))
			{
				$userInfo[$userid] = array('posts' => 0);
			}

			$userInfo[$userid]['posts']++;
		}

		vB_Library::instance('user')->decrementPostCountForUsers($userInfo);
	}

	/** Reset the called array- this makes sure we only increment/decrement user post counts once for a given node
	 * This is only needed by the unit tests.
	 */
	public function resetCountCalled()
	{
		$this->nodeAddedToPostCount = array();
		$this->nodeRemovedFromPostCount = array();
	}
	/**
	 * Checks if the current node should be counted in the user post count for the author.
	 *
	 * @param	array	The node
	 * @param	(unpublish|unapprove)	The action that was just carried out on the node
	 *
	 * @return	boolean	Whether or not the node should be counted in user post count.
	 */
	protected function countInUserPostCount(array $node, $action = '')
	{
		// NOTICE: How we determine if a post counts in user post count here needs to
		// match the criteria used in admincp/misc.php?do=updateposts
		// If you update in one place, please update in the other


		// check if this content type counts as a "post"
		// We have to jump through a bunch of hoops to not count VMs, PMs,
		// reports, and other items in user post count
		// @todo - VMs and comments should probably be their own content types
		// extended from the Text content type.

		if (!$this->includeInUserPostCount OR $this->isVisitorMessage($node['nodeid']))
		{
			return false;
		}

		if (!isset($node['starter']))
		{
			//force a reload
			vB_Cache::allCacheEvent('nodeChg_'. $node['nodeid']);
			$node = vB_Library::instance('node')->getNodeFullContent($node['nodeid'], $node['contenttypeid']);
		}
		return (!$this->isComment($node['nodeid'], $node)
			AND
			(
				($action == 'unapprove')
					OR
					$node['approved']
			)
			AND
			(
				($action == 'unpublish')
					OR
					(
						$node['showpublished']
							AND
							!$node['deleteuserid']
					)
			)
		);
	}
	/**
	 * Checks whether or not we should adjust user post count for descendant nodes
	 *
	 * @param	array	The node
	 * @param	(unpublish|unapprove)	The action that was just carried out on the node
	 *
	 * @return	boolean	Whether or not the child nodes should be handled
	 */
	protected function shouldChangeUserPostCountForChildNodes($node, $action = '')
	{
		// We don't want to do anything for child nodes if the parent node
		// is currently soft-deleted or unapproved because the child nodes
		// are already not counted in user post count

/*
	=====================================\n
	shouldChangeUserPostCountForChildNodes\n
	\$node[nodeid]: $node[nodeid]\n
	\$node[approved]: $node[approved]\n
	\$node[showapproved]: $node[showapproved]\n
	\$node[showpublished]: $node[showpublished]\n
	\$node[deleteuserid]: $node[deleteuserid]\n
";*/

		return (
			// if the node is approved (or we are unapproving it)
			(
				($action == 'unapprove')
				OR
				(
					$node['approved']
					/*AND
					$node['showapproved']*/
				)
			)
			AND
			// and the node is not soft-deleted (or we are soft-deleting it)
			(
				($action == 'unpublish')
				OR
				(
					$node['showpublished']
					AND
					!$node['deleteuserid']
				)
			)
		);
	}

	/**
	 * Permanently deletes a node
	 *	@param	mixed	The nodeid of the record to be deleted, or an array of nodeids
	 *
	 *	@return	boolean
	 */
	public function delete($nodeids)
	{
		/*
		 *  Should move this logic back to the node library.
		 */

		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}

		$nodeLib = vB_Library::instance('node');
		$prior = $nodeLib->getNodes($nodeids);
		//Confirm we can delete. This is not a permissions check but a data integrity check, which is why it's in the library.
		foreach ($prior AS $node)
		{
			if ($node['protected'])
			{
				//O.K. if it's not a channel.
				if ($node['contenttypeid'] == vB_Types::instance()->getContentTypeId('vBForum_Channel'))
				{
					throw new vB_Exception_Api('no_delete_permissions');
				}
			}
		}

		$cachedUserIds = array();
		$textChangeTypes = vB_Library::instance('node')->getTextChangeTypes();
		//We need to loop and do these one at a time. If an early node is a child of a later one, the counts will change
		//during the loop. If the early node is a parent of a later node, the node will have been  deleted.

		$searchLIB = vB_Library::instance('search');
		$events = array();
		foreach ($nodeids AS $nodeid)
		{
			try
			{
				$existing = $nodeLib->getNodeBare($nodeid);
			}
			catch(exception $e)
			{
				continue;
			}

			//This can happen if either the node is invalid or has been deleted
			if (empty($existing) OR !empty($existing['errors']))
			{
				continue;
			}

			// Update user post count (content hard-delete)
			try
			{
				$this->decrementUserPostCount($existing);
			}
			catch(exception $e)
			{
				//nothing to do here- just continue;
			}

			//update parent counts.
			$nodeLib->updateAddRemovedNodeParentCounts($existing, $existing, true, false);


			//update the last content of the parents
			$parents = $nodeLib->getParents($nodeid);
			$parentids = array();
			foreach($parents as $parent)
			{
				//the first entry is always this node.
				if ($parent['nodeid'] != $nodeid)
				{
					$parentids[] = $parent['nodeid'];
				}
			}

			$searchLIB->delete($nodeid);
			$childTables = array('node');
			$childTypes = $this->assertor->assertQuery('vBForum:getChildContentTypes', array('nodeid' => $nodeid));

			foreach ($childTypes as $childType)
			{
				$childApi = self::getContentApi($childType['contenttypeid']);
				foreach ($childApi->fetchTableName() AS $table)
				{
					if (!in_array($table, $childTables))
					{
						$childTables[] = $table;
					}
				}
			}
			foreach ($childTables AS $childTable)
			{
				$this->assertor->assertQuery('vBForum:deleteChildContentTableRecords', array('nodeid' => $nodeid, 'tablename' => $childTable));
			}

			$events = array_merge($events, array($existing['nodeid'], $existing['parentid'], $existing['starter']));

			$children = $nodeLib->fetchClosurechildren($nodeid);

			if (!empty($children) AND !empty($children[$nodeid]))
			{
				foreach ($children[$nodeid] AS $child)
				{
					$events[] = $child['child'];
				}
			}

			$this->assertor->assertQuery('vBForum:closure',
				array(vB_dB_Query::TYPE_KEY =>  vB_dB_Query::QUERY_DELETE, 'parent' => $nodeid));
			$this->assertor->assertQuery('vBForum:closure',
				array(vB_dB_Query::TYPE_KEY =>  vB_dB_Query::QUERY_DELETE, 'child' => $nodeid));

			//redo the lastcontent *after* we've deleted the ndoe.
			foreach($parentids as $parentid)
			{
				$nodeLib->fixNodeLast($parentid);
			}

			$cachedUserIds[] =  $existing['userid'];
		}

		$nodeLib->clearCacheEvents(array_unique($events));
		$cacheEvents = array();
		foreach (array_unique($cachedUserIds) AS $userid)
		{
			$cacheEvents[] = 'userChg_' . $userid;
		}
		vB_Cache::instance()->allCacheEvent($cacheEvents);
		return true;
	}

	/**
	 * Delete the records without updating the parent info. It is used when deleting a whole channel and it's children need to be removed
	 * @param array $childrenIds - list of node ids
	 */
	public function deleteChildren($childrenIds)
	{
		$specific_tables = $this->fetchTableName();
		$specific_tables[] = 'node';
		foreach ($specific_tables as $table)
		{
			$this->assertor->delete('vBForum:' . $table, array('nodeid' => $childrenIds));
		}
	}

	/**
	 * Is this record in a published state based on the times?
	 *
	 *	@param	mixed
	 *
	 *	@return	bool
	 */
	public function isPublished($data)
	{
		if (empty($data['publishdate']))
		{
			return false;
		}

		$timeNow = vB::getRequest()->getTimeNow();

		return
			($data['publishdate'] > 0) AND
			($data['publishdate'] <= $timeNow) AND
			(
				empty($data['unpublishdate']) OR
				($data['unpublishdate'] <= 0) OR
				($data['unpublishdate'] >= $timeNow) OR
				($data['unpublishdate'] < $data['publishdate'])
			);
	}

	/**
	 * Is this record in approved state?
	 *	A node record is considered unapproved either directly (approved field) or indirectly (show approved field).
	 *
	 *	@param	mixed 	Node record information.
	 *
	 *	@return	bool 	Whether record is approved or not.
	 */
	public function isApproved($data)
	{
		return (!empty($data['showapproved']) ? true : false);
	}


	/**
	 * updates a record
	 *
	 *	@param	mixed		array of nodeid's
	 *	@param	mixed		array of permissions that should be checked.
	 *
	 * 	@return	boolean
	 */
	public function update($nodeid, $data)
	{
		$channelContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Channel');

		// Verify prefixid
		if ($this->contenttypeid != $channelContentTypeId AND isset($data['prefixid']))
		{
			$this->verifyPrefixid($data['prefixid']);
		}
		else
		{
			// Channel can't have a prefix
			unset($data['prefixid']);
		}

		// Verify post iconid
		if ($this->contenttypeid != $channelContentTypeId AND isset($data['iconid']))
		{
			$this->verifyPostIconid($data['iconid']);
		}
		else
		{
			// Channels can't have a post icon
			unset($data['iconid']);
		}

		$timeNow = vB::getRequest()->getTimeNow();
		$userContext = vB::getUserContext();
		//If this user doesn't have the featured permission and they are trying to set it,
		//Let's just quietly unset it.
		if (isset($data['featured']))
		{
			if (!$userContext->getChannelPermission('moderatorpermissions', 'cansetfeatured', $data['parentid']))
			{
				unset($data['featured']);
			}
		}

		//We can't allow directly setting parentid. That should only happen through the node api move function
		//And there are number of other fields that shouldn't be changed here. We have methods for the ones that can be changed at all.
		foreach (array('open', 'showopen', 'approved', 'showapproved', 'protected') as $field)
		{
			if (isset($data[$field]))
			{
				unset($data[$field]);
			}
		}

		if (isset($data['parentid']))
		{
			//Only allow for articles.
			$content = $this->nodeApi->getNodeFullContent($nodeid);
			$content = array_pop($content);

			// you can't move it to the category it's already in
			if ($data['parentid'] != $content['parentid'])
			{
				// only allow this for articles (currently)
				if ($content['channeltype'] == 'article')
				{
					if (!$userContext->getChannelPermission('forumpermissions', 'canmove', $data['parentid'])
						AND
						!$userContext->getChannelPermission('moderatorpermissions', 'canmassmove', $data['parentid'])
					)
					{
						throw new vB_Exception_Api('no_permission');
					}

					//If we got here, we're O.K. to move. let's do that now.
					vB_Library::instance('node')->moveNodes($nodeid, $data['parentid']);
				}
			}
			unset($data['parentid']);
		}

		//We need to see if we need to update.
		$prior = vB_Library::instance('node')->getNodeBare($nodeid);
		if ($this->contenttypeid != $channelContentTypeId)
		{
			$content = $this->getFullContent($nodeid);
		}

		if (isset($data['publish_now']) AND !empty($data['publish_now']))
		{
			$data['publishdate'] = vB::getRequest()->getTimeNow();
		}

		if (empty($data['htmltitle']) AND !empty($data['title']))
		{
			$data['htmltitle'] = vB_String::htmlSpecialCharsUni(vB_String::stripTags($data['title']), false);
		}

		if (empty($data['urlident']) AND !empty($data['title']))
		{
			$data['urlident'] = vB_String::getUrlIdent($data['title']);
		}

		// Do not change publishdate or showpublished status unless it was explicitly set while calling update().
		if (
			(!isset($data['publishdate']) OR (empty($data['publishdate']) AND ($data['publishdate'] !== 0)))
			AND !empty($prior['publishdate'])
		)
		{
			$data['publishdate'] = $prior['publishdate'];
		}

		if ($this->isPublished($data))
		{
			$published = 1;
		}
		else
		{
			$published = 0;
		}

		$nodevals = array();
		if ($published <> $prior['showpublished'])
		{
			$nodevals['showpublished'] = $published;
		}

		// set default node options
		if ((empty($data['nodeoptions']) OR !is_numeric($data['nodeoptions'])) AND $prior['contenttypeid'] != $channelContentTypeId)
		{
			$parentFullContent = vB_Library::instance('node')->getNodeFullContent($prior['parentid']);
			if(!empty($parentFullContent[$prior['parentid']]['channeltype']))
			{
				$data['nodeoptions'] = self::$defaultNodeOptions[$parentFullContent[$prior['parentid']]['channeltype']];
			}
			else
			{
				$data['nodeoptions'] = self::$defaultNodeOptions['default'];
			}

			// Add or remove any nodeoptions that have been explicitly passed in.
			// This would have otherwise happened in updateNodeOptions/setNodeOptions
			// (which is where it happens when adding a node as opposed to updating
			// a node), but since $data['nodeoptions'] is now defined, setNodeOptions
			// won't take care of setting these (it will just apply the int
			// nodeoptions value).
			$baseNodeOptions = vB_Api::instanceInternal('node')->getOptions();
			foreach ($baseNodeOptions AS $baseOptionKey => $baseOptionVal)
			{
				if (isset($data[$baseOptionKey]))
				{
					if (intval($data[$baseOptionKey]))
					{
						$data['nodeoptions'] = $data['nodeoptions'] | intval($baseOptionVal);
					}
					else
					{
						$data['nodeoptions'] = $data['nodeoptions'] & ~intval($baseOptionVal);
					}
				}
			}
		}

		//node table data.
		$data[vB_dB_Query::TYPE_KEY] =  vB_dB_Query::QUERY_UPDATE;
		$data['nodeid'] = $nodeid;
		$data['lastupdate'] =  $timeNow;
		//If the field passed is in the $nodeFields array then we update the node table.
		foreach ($data as $field => $value)
		{
			if (in_array($field, $this->nodeFields))
			{
				$nodevals[$field] = $value;
			}
		}
		$index = empty($data['noIndex']);
		unset($data['noIndex']);

		// Update the content-type specific data
		if (!is_array($this->tablename))
		{
			$tables = array($this->tablename);
		}
		else
		{
			$tables = $this->tablename;
		}

		$success = true;

		foreach ($tables as $table)
		{
			$structure = $this->assertor->fetchTableStructure('vBForum:' . $table);
			if (empty($structure) OR empty($structure['structure']))
			{
				throw new vB_Exception_Api('invalid_query_parameters');
			}
			$queryData = array();
			$queryData[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_UPDATE;
			$queryData['nodeid'] = $nodeid;
			foreach ($structure['structure'] as $fieldname)
			{
				if (isset($data[$fieldname]))
				{
					$queryData[$fieldname] = $data[$fieldname];
				}
			}
			//Now we have at least a query type and a nodeid. We put those in above. So if we don't
			//have at least one other value there's no reason to try an update.
			if (count($queryData)> 2)
			{
				$success = $success AND $this->assertor->assertQuery('vBForum:' . $table, $queryData);
			}
		}

		if ($success)
		{
			// Handle Attachments
			// The text library (and most derivatives) can have attachments,
			// for the others, this is a no-op.
			$this->handleAttachments('update', $nodeid, $data);

			//Clear cached query info that would be significantly impacted
			$events = array('fUserContentChg_' . $prior['userid']);
			if ($prior['starter'])
			{
				$starterNodeInfo = vB_Library::instance('node')->getNodeBare($prior['starter']);
				$events[] = 'fUserContentChg_' . $starterNodeInfo['userid'];
			}
			else if ($prior['parentid'])
			{
				$starterNodeInfo = vB_Library::instance('node')->getNodeBare($prior['parentid']);
				$events[] = 'fUserContentChg_' . $starterNodeInfo['userid'];
			}

			$this->nodeApi->clearCacheEvents($nodeid);
			vB_Cache::instance()->allCacheEvent($events);

			if (isset($nodevals['publishdate']) AND ($nodevals['publishdate'] > $timeNow))
			{
				if (empty($nodevals['unpublishdate']) OR ($nodevals['unpublishdate'] > $nodevals['publishdate']))
				{
					$nodevals['nextupdate'] = $nodevals['publishdate'];
				}
			}
			else if (isset($nodevals['unpublishdate']) AND ($nodevals['unpublishdate'] > $timeNow))
			{
				$nodevals['nextupdate'] = $nodevals['unpublishdate'];
			}

			// handle approved
			if (isset($nodevals['approved']))
			{
				if ($nodevals['approved'])
				{
					$approved = 1;
					$queryName = 'approveNode';
				}
				else
				{
					$approved = 0;
					$queryName = 'unapproveNode';
				}

				// set approved to parent...
				$this->assertor->assertQuery('vBForum:node',
					array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,'nodeid' => $nodeid, 'approved' => $approved
					)
				);

				// and handle showapproved
				$this->assertor->assertQuery('vBForum:' . $queryName, array('nodeid' => $nodeid));
				unset($nodevals['approved']);
			}

			if (isset($nodevals))
			{
				$nodevals[vB_dB_Query::TYPE_KEY] =  vB_dB_Query::QUERY_UPDATE;
				$nodevals['nodeid'] = $nodeid;
				$success = $this->assertor->assertQuery('vBForum:node', $nodevals);
			}

			//We need to compare the current publishdate and unpublishdate values against the
			// parent.
			//But we can skip this if neither publish or unpublishdate is set

			$updateParents = false;
			if ($published <> $prior['showpublished'])
			{
				$updateParents = true;
				//We are concerned about two possibilities. It could have gone from published to unpublished.
				//In either case we change by totalcount +1 (for ourselves.
				//Remember that published is always unpublished.

				//From unpublished to published.
				if ($published)
				{
					$nodeUpdates = $this->nodeLibrary->publishChildren($nodeid);

					// if $nodeUpdates is empty, that means no change was made to this node or its descendants,
					// and no parent count changes are necessary. If it's not empty but doesn't have totalcount set,
					// that means it possibly failed with a DB error. In such a case, we will just not update the
					// counts but continue updating the node.
					if (!empty($nodeUpdates) AND isset($nodeUpdates['totalcount']))
					{
						// text-counts only change by 1 (or 0 for non-text types), because it only affects the immediate parent
						$textChange = $this->textCountChange;
						$textUnPubChange = -1 * $textChange;
						// Note, below assumes that a DB had been diligent about
						// keeping track of the count fields correctly.
						$totalPubChange = $nodeUpdates['totalcount'] - $prior['totalcount'] + $textChange; // we add the text change because self counts for ancestors' total counts
						$totalUnPubChange = -1 * $totalPubChange;
					}
					else
					{
						$updateParents = false;
					}
				}
				//or from published to unpublished.
				else
				{
					$nodeUpdates = $this->nodeLibrary->unpublishChildren($nodeid);

					if (!empty($nodeUpdates) AND isset($nodeUpdates['totalunpubcount']))
					{
						$textUnPubChange = $this->textCountChange;
						$textChange = -1 * $textUnPubChange;
						$totalUnPubChange = $nodeUpdates['totalunpubcount'] - $prior['totalunpubcount']  + $textUnPubChange;
						$totalPubChange = -1 * $totalUnPubChange;
					}
					else
					{
						$updateParents = false;
					}
				}

				vB_Library::instance('node')->clearChildCache($nodeid);

			}


			//update the parent count if necessary
			if ($updateParents)
			{
				vB_Library::instance('node')->updateParentCounts($nodeid, $textChange, $textUnPubChange, $totalPubChange, $totalUnPubChange, $published);
			}

			//update viewperms from childs if needed, do we want this channel specific?
			if (isset($nodevals['viewperms']) AND isset($prior['viewperms']) AND ($nodevals['viewperms'] != $prior['viewperms']))
			{
				vB_Api::instanceInternal('node')->setNodePerms($nodeid, array('viewperms' => $nodevals['viewperms']));
			}

			if ($index)
			{
				vB_Library::instance('search')->index($nodeid);
			}

			// update user tags
			$tags = !empty($data['tags']) ? explode(',', $data['tags']) : array();
			$tagRet = vB_Api::instanceInternal('tags')->updateUserTags($nodeid, $tags);

			$this->updateNodeOptions($nodeid, $data);

			// Update childs nodeoptions
			$this->assertor->assertQuery('vBForum:updateChildsNodeoptions', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'parentid' => $nodeid
			));

			$this->nodeApi->clearCacheEvents(array($nodeid, $prior['parentid']));

			$loginfo = array(
				'nodeid'       => $prior['nodeid'],
				'nodetitle'    => $prior['title'],
				'nodeusername' => $prior['authorname'],
				'nodeuserid'   => $prior['userid']
			);

			$extra = array();
			if ($nodevals !== null && isset($nodevals['title']))
			{
				if($prior['title'] != $nodevals['title'])
				{
					$extra = array('newtitle' => $nodevals['title']);
				}
			}

			vB_Library_Admin::logModeratorAction($loginfo, 'node_edited_by_x', $extra);

			$updateEditLog = true;
			if (
				!vB::getUserContext()->hasPermission('genericoptions', 'showeditedby')
					AND
				(	(
						isset($content[$nodeid]['edit_reason'])
							AND
						$data['reason'] == $content[$nodeid]['edit_reason']
					)
					OR
					(
						!isset($content[$nodeid]['edit_reason'])
							AND
						empty($data['reason'])
					)
				)
			)
			{
				$updateEditLog = false;
			}

			// Clear autosave table of this items entry
			if (vB::getCurrentSession()->get('userid')
					AND
				!empty($data['rawtext'])
			)
			{
				$this->assertor->delete('vBForum:autosavetext', array(
					'userid'   => vB::getCurrentSession()->get('userid'),
					'nodeid'   => $nodeid,
					'parentid' => $content[$nodeid]['parentid']
				));
			}

			// Log edit by info
			if (
				$updateEditLog
					AND
				$this->contenttypeid != $channelContentTypeId
					AND
				isset($content[$nodeid]['rawtext'])
					AND
				isset($data['rawtext'])
					AND
				$content[$nodeid]['rawtext'] != $data['rawtext']
					AND
				!empty($data['publishdate'])	// Is this still published?
					AND
				$prior['publishdate']	// Was this already published?
					AND
				(
					!empty($data['reason'])
						OR
					$data['publishdate'] < (vB::getRequest()->getTimeNow() - ($this->options['noeditedbytime'] * 60))
				)
			)
			{
				$userinfo = vB::getCurrentSession()->fetch_userinfo();
				// save the postedithistory
				if ($this->options['postedithistory'])
				{
					$record = $this->assertor->getRow('vBForum:postedithistory',
						array(
							vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
							'original' => 1,
							'nodeid'   => $nodeid
					));
					// insert original post on first edit
					if (empty($record))
					{
						$this->assertor->assertQuery('vBForum:postedithistory', array(
							vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
							'nodeid'   => $nodeid,
							'userid'   => $content[$nodeid]['userid'],
							'username' => $content[$nodeid]['authorname'],
							'dateline' => $data['publishdate'],
							'pagetext' => $content[$nodeid]['rawtext'],
							'original' => 1,
						));
					}
					// insert the new version
					$this->assertor->assertQuery('vBForum:postedithistory', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
						'nodeid'   => $nodeid,
						'userid'   => $userinfo['userid'],
						'username' => $userinfo['username'],
						'dateline' => vB::getRequest()->getTimeNow(),
						'reason'   => isset($data['reason']) ? vB5_String::htmlSpecialCharsUni($data['reason']) : '',
						'pagetext' => isset($data['rawtext']) ? $data['rawtext'] : ''
					));
				}

				$this->assertor->assertQuery('editlog_replacerecord', array(
					'nodeid'     => $nodeid,
					'userid'     => $userinfo['userid'],
					'username'   => $userinfo['username'],
					'timenow'    => vB::getRequest()->getTimeNow(),
					'reason'     => isset($data['reason']) ? vB5_String::htmlSpecialCharsUni($data['reason']) : '',
					'hashistory' => intval($this->options['postedithistory'])
				));
			}

			return true;
		}

		$this->nodeApi->clearCacheEvents(array($nodeid, $prior['parentid']));
		return false;
	}

	/**
	 * Returns a content api of the appropriate type
	 *
	 *	@param int the content type id
	 *	@return	mixed	content api object
	 */
	public static function getContentApi($contenttypeid)
	{
		return vB_Api::instanceInternal('Content_' . vB_Types::instance()->getContentTypeClass($contenttypeid));
	}

	/**
	 * Returns a content api of the appropriate type
	 *
	 *	@param int the content type id
	 *	@return	mixed	content api object
	 */
	public static function getContentLib($contenttypeid)
	{
		$contentClass = vB_Types::instance()->getContentTypeClass($contenttypeid);

		if (class_exists('vB_Library_Content_' . $contentClass))
		{
			return vB_Library::instance('Content_' . $contentClass);
		}
		return false;
	}

	/**
	 * Returns the node content as an associative array
	 *	@param	integer	The id in the primary table
	 *	@param	integer	The id in the primary table
	 *	@param	mixed	array of permissions request- (array group, permission)
	 *	@return	int
	 */
	public function getContent($nodeids)
	{
		//With the performance enhancements including search api caching, optimized queries, and content caching
		//it is just as fast to always return the channel id's. That allow code optimization for permission handling
		return $this->getFullContent($nodeids);
	}

	/**
	 * Returns the node content plus the channel routeid and title, and starter route and title the as an associative array
	 * Caller MUST ensure that $nodeids are of the correct content type.
	 *
	 * @param	integer	The id in the primary table.
	 *
	 * @return	Array[Int]	Requested data, keyed by the nodeid.
	 */
	public function getBareContent($nodeids)
	{
		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}

		$nodesContent = $this->getRawContent($nodeids);
		$result = array();

		foreach ($nodeids AS $nodeid)
		{
			if (array_key_exists($nodeid, $nodesContent))
			{
				$result[$nodeid] = $nodesContent[$nodeid];
			}
		}
		return $result;
	}


	/**
	 * Returns the node content plus the channel routeid and title, and starter route and title the as an associative array
	 *	@param	integer	The id in the primary table
	 *	@param	mixed	array of permissions request- (array group, permission)
	 *
	 *	@return	array	Nodeid-keyed array of node content, including node.*, channel & starter id, title, route and other
	 *					common data, and content-specific data. @see $this->getRawContent()
	 */
	public function getFullContent($nodeids)
	{
		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}
		$nodesContent = $this->getRawContent($nodeids);
		//@TODO need to sort out how to improve the content being cached. Not possible now cause we do some perm checks and add flags for the current user.
		$nodesContent = $this->assembleContent($nodesContent);

		$result = array();

		foreach ($nodeids AS $nodeid)
		{
			if (array_key_exists($nodeid, $nodesContent))
			{
				$result[$nodeid] = $nodesContent[$nodeid];
			}
		}

		return $result;
	}

	/**
	 * Prepares basic content.  Used by both getBareContent and getFullContent.
	 *
	 * @return	array	Nodeid-keyed array of node & content data, including node table data. Keys depend on the
	 *					particular node's contenttype, but a few common ones include: node.*, channelroute,
	 *					channeltitle, channelid, starterroute, startertitle, etc. See vBForum:getFullContent.
	 *
	 * @throws throw new vB_Exception_Api('incorrect_content_class_called') 	If any node in $nodeids is not of $this's contenttype.
	 *
	 * @protected
	 */
	protected function getRawContent($nodeids)
	{
		//First see what we can load from cache.
		$cached = self::fetchFromCache($nodeids, self::CACHELEVEL_FULLCONTENT);
		$nodesContent = array();
		$timeNow = vB::getRequest()->getTimeNow();
		foreach($cached['found'] AS $key => $cachedNode)
		{
			if ($cachedNode['contenttypeid'] != $this->contenttypeid)
			{
				/*
				 * The reason we throw an exception for an already cached data is because
				 * the follow-up functions called by the upstream function also require
				 * that all nodes are of the correct contenttype, particularly assembleContent()
				 * and all of its incomplete node checks. As such, we should always consider it
				 * a fatal error if someone tries to fetch data via the wrong contenttype,
				 * whether it's been cached correctly or not.
				 */
				$currentContentClassName = get_class($this);
				$nodeContentClassObject = self::getContentLib($cachedNode['contenttypeid']);
				$nodeContentClassName = get_class($nodeContentClassObject);
				$exceptionPhraseData = array(
					$currentContentClassName,
					$cachedNode['nodeid'],
					$nodeContentClassName
				);
				throw new vB_Exception_Api('incorrect_content_class_called', $exceptionPhraseData);
			}

			$nodesContent[$cachedNode['nodeid']] = $cachedNode;
		}

		//Now do we need to query?
		if (!empty($cached['notfound']))
		{
			$content = $this->assertor->getRows('vBForum:getFullContent', array(
				'tablename' => $this->tablename,
				'nodeid' => $cached['notfound'],
			));

			//Now we merge these plus, if necessary, the permissions. First let's make an associative array of the core data.
			if (!$content OR !empty($content['errors']))
			{
				throw new vB_Exception_Api('invalid_data_requested') ;
			}

			// Check contenttypes & Get prefix phrases
			$phrasevars = array();
			foreach ($content as $node)
			{
				if ($node['contenttypeid'] != $this->contenttypeid)
				{
					/*
					 * Prevent caching incomplete content data. If we cache this, we'll have problems
					 * down the line of incorrect 'incomplete_node' exceptions causing issues.
					 *
					 * Phrase:
					 * Incorrect content class ({1}) was called for node {2} which is of type {3}.
					 */
					$currentContentClassName = get_class($this);
					$nodeContentClassObject = self::getContentLib($node['contenttypeid']);
					$nodeContentClassName = get_class($nodeContentClassObject);
					$exceptionPhraseData = array(
						$currentContentClassName,
						$node['nodeid'],
						$nodeContentClassName
					);
					throw new vB_Exception_Api('incorrect_content_class_called', $exceptionPhraseData);
				}

				if (!empty($node['prefixid']))
				{
					$phrasevars[] = 'prefix_' .  $node['prefixid'] . '_title_plain';
					$phrasevars[] = 'prefix_' .  $node['prefixid'] . '_title_rich';
				}
				if (!empty($node['starterprefixid']))
				{
					$phrasevars[] = 'prefix_' .  $node['starterprefixid'] . '_title_plain';
					$phrasevars[] = 'prefix_' .  $node['starterprefixid'] . '_title_rich';
				}
			}

			$phrases = array();
			if ($phrasevars)
			{
				$phrases = vB_Api::instanceInternal('phrase')->fetch($phrasevars);
			}

			//Check to see what needs updates.
			foreach ($content as $key => $node)
			{
				if ($phrases AND !empty($node['prefixid']))
				{
					$content[$key]['prefix_plain'] = $phrases['prefix_' .  $node['prefixid'] . '_title_plain'];
					$content[$key]['prefix_rich'] = $phrases['prefix_' .  $node['prefixid'] . '_title_rich'];
				}

				if ($phrases AND !empty($node['starterprefixid']))
				{
					$content[$key]['starterprefix_plain'] = $phrases['prefix_' .  $node['starterprefixid'] . '_title_plain'];
					$content[$key]['starterprefix_rich'] = $phrases['prefix_' .  $node['starterprefixid'] . '_title_rich'];
				}
			}

			self::writeToCache($content, self::CACHELEVEL_FULLCONTENT);

			foreach($content AS $node)
			{
				$nodesContent[$node['nodeid']] = $node;
			}
		}
		return $nodesContent;

	}


	/**
	 * Determines whether the current user can edit a node
	 *
	 * @param	int		The nodeid
	 * @param	int		optional usercontext
	 * @param	mixed	optional array of channel permissions data, which if available prevents a userContext Call
	 *
	 * @return	bool
	 */
	public function getCanEdit($node, $userContext = null, $channelPerms = array())
	{
		if (($userContext === null) AND empty($channelPerms))
		{
			$userContext = vB::getUserContext();
		}

		if ($userContext->isSuperAdmin())
		{
			return true;
		}

		if (empty($node['channelid']))
		{
			if ($node['contenttypeid'] == $this->channelTypeId)
			{
				$node['channelid'] = $node['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($node['starter']);
				$node['channelid'] = $starter['parentid'];
			}
		}

		if (empty($channelPerms))
		{
			$channelPerms = $userContext->fetchPermsForChannels(array($node['channelid']));

			// the 'global' array isn't part of the channel-specific-array, so we have to pull it out
			// and re-save it because the VM-specific code a bit below can use it.
			if (isset($channelPerms['global']))
			{
				$globalPerms = $channelPerms['global'];
			}

			$channelPerms = $channelPerms[$node['channelid']];

			if (isset($globalPerms))
			{
				$channelPerms['global'] = $globalPerms;
			}
		}

		//If it's a channel then users need a higher permission.
		if (empty($node['starter']))
		{
			if (!isset($channelPerms['canconfigchannel']))
			{
				$channelPerms['canconfigchannel'] = $userContext->getChannelPermission('forumpermission2', 'canconfigchannel', $node['nodeid'], false, $node['channelid']);
			}
			return $channelPerms['canconfigchannel'];
		}

		if (!isset($channelPerms['moderate']))
		{
			if ($userContext->getChannelPermission('moderatorpermissions', 'caneditposts', $node['nodeid'], false, $node['channelid']))
			{
				return true;
			}
		}

		if (isset($channelPerms['moderate']['caneditposts']) AND $channelPerms['moderate']['caneditposts'])
		{
			return true;
		}

		// allow the users to edit VMs sent to themselves if they have permission
		if (!empty($node['setfor']) AND ($node['setfor'] == vB::getCurrentSession()->get('userid')) AND
			($node['setfor'] == $node['userid']) )
		{
			if (!isset($channelPerms['global']))
			{
				$channelPerms['global']['caneditownmessages'] = $userContext->hasPermission('visitormessagepermissions', 'caneditownmessages');
			}
			return $channelPerms['global']['caneditownmessages'];
		}

		//if the user is not the author, we're done.
		if ($node['userid'] != vB::getCurrentSession()->get('userid'))
		{
 			return $channelPerms['caneditothers'];
		}

		if (!isset($channelPerms['caneditown']))
		{
			$channelPerms['caneditown'] = $userContext->getChannelPermission('forumpermissions', 'caneditpost', $node['nodeid'], false, $node['channelid']);
		}

		//If the user doesn't have permission to edit their own post we're done.
		if (!$channelPerms['caneditown'])
		{
			return false;
		}

		if (!isset($channelPerms['limits']))
		{
			$channelPerms['limits'] = vB::getUserContext()->getChannelLimits($node['nodeid']);
		}

		if (empty($channelPerms['limits']['edit_time']))
		{
			//There is no edit timeout set;
			return true;
		}

		if ($node['publishdate'] + ($channelPerms['limits']['edit_time'] * 3600) <= vB::getRequest()->getTimeNow())
		{
			return false;
		}
		return true;
	}


	/**
	 * Determines whether the current user can soft-delete or hard-delete
	 *
	 * @param	mixed	The node
	 * @param	int		Optional usercontext
	 * @param	mixed	Optional array of channel permissions data, which if available prevents a userContext Call
	 * @param	bool	Optional flag - false get can soft delete, true get can hard delete
	 *
	 * @return	bool
	 */
	public function getCanDelete($node, $userContext = null, $channelPerms = array(), $hard = false)
	{
		if (!empty($channelPerms) AND $channelPerms['global']['is_superadmin'])
		{
			return true; // can hard/soft delete
		}

		if (empty($userContext))
		{
			$userContext = vB::getUserContext();
		}

		if ($userContext->isSuperAdmin())
		{
			return true; // can hard/soft delete
		}

		if (empty($node['channelid']))
		{
			if ($node['contenttypeid'] == $this->channelTypeId)
			{
				$node['channelid'] = $node['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($node['starter']);
				$node['channelid'] = $starter['parentid'];
			}
		}

		if (!isset($channelPerms['canremoveposts']))
		{
			$channelPerms['canremoveposts'] = $userContext->getChannelPermission('moderatorpermissions', 'canremoveposts', $node['nodeid'], false, $node['channelid']);
		}

		if ($channelPerms['canremoveposts'] AND $hard)
		{
			return true; // can hard delete
		}
		/*
			VBV-12086 -- Commenting out this entire else branch
			If the user is a channel owner, they should have the correct channel permissions
			for moderation and deleting and we don't have to jump through these hoops
			to determin "fake" permissions.

			@TODO -- remove this commented code if it doesn't cause problems

		else
		{
			// The user may be the owner of the channel
			if ($userContext->getChannelPermission('forumpermissions2', 'canmanageownchannels', $node['nodeid'], false, $node['channelid']))
			{
				$channel = vB_Api::instanceInternal('node')->getNode($node['channelid']);

				if (($channel['userid'] == vB::getCurrentSession()->get('userid')) AND ($node['nodeid'] != $channel['nodeid']))
				{
					return true;
				}
			}

			//If this is the current user's post or thread we might be able to delete.
			if (($node['userid'] != vB::getCurrentSession()->get('userid')))
			{
				return $channelPerms['candeleteothers'];
			}

			if (!isset($channelPerms['candeleteownpost']))
			{
				$channelPerms['candeleteownpost'] =
					$userContext->getChannelPermission('forumpermissions', 'candeletepost', $node['nodeid'], false, $node['channelid']);
			}
			return !empty($channelPerms['candeleteownpost']);
		}*/

		if (!isset($channelPerms['candeleteposts']))
		{
			$channelPerms['candeleteposts'] = $userContext->getChannelPermission('moderatorpermissions', 'candeleteposts', $node['nodeid'], false, $node['channelid']);
		}

		if ($channelPerms['candeleteposts'] AND !$hard)
		{
			return true; // can soft delete
		}

		// END OF MODERATOR CHECKS

		// at this point, the user can only soft delete, depending on permissions. Skip this if it's a channel
		if (!$hard AND ($node['channelid'] != $node['nodeid']))
		{
			if ($node['userid'] == vB::getCurrentSession()->get('userid'))
			{
				if ($node['nodeid'] == $node['starter'])	// removing a starter requires candeletethread
				{
					if (!isset($channelPerms['candeleteownthread']))
					{
						$channelPerms['candeleteownthread'] = $userContext->getChannelPermission('forumpermissions', 'candeletethread', $node['nodeid'], false, $node['channelid']);
					}

					if ($channelPerms['candeleteownthread'])
					{
						return true;
					}
				}
				else	// while removing a non-starter requires candeleteposts
				{
					if (!isset($channelPerms['candeleteownpost']))
					{
						$channelPerms['candeleteownpost'] = $userContext->getChannelPermission('forumpermissions', 'candeletepost', $node['nodeid'], false, $node['channelid']);
					}

					if ($channelPerms['candeleteownpost'])
					{
						return true;
					}
				}
			}
			else
			{
				// candeleteothers lets them soft-delete starters OR replies (or comments). See vB_Api_Node->deleteNodes()
				if (!isset($channelPerms['candeleteothers']))
				{
					$channelPerms['candeleteothers'] = $userContext->getChannelPermission('forumpermissions2', 'candeleteothers', $node['nodeid'], false, $node['channelid']);
				}

				if ($channelPerms['candeleteothers'])
				{
					return true;
				}
			}
		}

		// You may be wondering where the canmanageownchannels check went. Well, previously it was blindly giving channel-owners all delete permissions, which is wrong.
		// usercontext should be *limiting* moderator permissions taken from the CHANNEL OWNERS groupintopic record depending on canmanageownchannels, and we shouldn't
		// be doing anything with it here. I don't know if the limiting thing works, but this is not the place to poke it.

		// TODO: VM CHECKS. VM's got their own thang going on, and we should make sure that the backend & front-end match. And that the different parts of the rear match
		// each other. However, this is becoming a rabbit hole dug by a rabbit on crack, so I'm trying to leave the VM permissions as alone as possible.
		// Note that this might already be taken care of in the usercontext getChannelLimitPermission()
		// I know I said "end of moderator checks" above, but there might be a few more moderator checks specific for VMs. Please forgive me.


		// The user doesn' have permission to delete
		return false;
	}


	/**
	 * Determines whether the current user can moderate
	 *
	 * @param  mixed  The node
	 * @param  object optional usercontext
	 * @param  mixed  optional array of channel permissions data, which if available prevents a userContext Call
	 * @param  int    optional nodeid,
	 *
	 * @return bool
	 */
	public function getCanModerate($node, $userContext = null, $channelPerms = array(), $nodeid = 0)
	{
		if (!empty($channelPerms) AND $channelPerms['global']['is_superadmin'])
		{
			return true;
		}

		if (empty($userContext))
		{
			$userContext = vB::getUserContext();
		}

		if ($userContext->isSuperAdmin())
		{
			return true;
		}

		if (!is_array($node) and intval($nodeid))
		{
			$node = vB_Library::instance('node')->getNodeBare($nodeid);
		}

		if (empty($node['channelid']))
		{
			if ($node['contenttypeid'] == $this->channelTypeId)
			{
				$node['channelid'] = $node['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($node['starter']);
				$node['channelid'] = $starter['parentid'];
			}
		}

		// can someone explain why we're setting this based on the canmoderateposts moderator permission instead of the canmanageownchannels moderator permission?
		if (!isset($channelPerms['canmanageownchannels']))
		{
			$channelPerms['canmanageownchannels'] =
				$userContext->getChannelPermission('moderatorpermissions', 'canmoderateposts', $node['nodeid'], false, $node['channelid']);
		}

		if ($channelPerms['canmanageownchannels'])
		{
			return true;
		}

		$channel = vB_Library::instance('node')->getNodeBare($node['channelid']);

		if ($channel['userid'] == vB::getCurrentSession()->get('userid'))
		{
			$channel = vB_Library::instance('node')->getNodeBare($node['channelid']);

			if ($channel['userid'] == vB::getCurrentSession()->get('userid'))
			{
				$channelPerms['canmanageownchannels'] =
					$userContext->getChannelPermission('forumpermissions2', 'canmanageownchannels', $node['nodeid'], false, $node['channelid']);
			}

			return !empty($channelPerms['canmanageownchannels']);
		}

		return false;
	}

	/**
	 * Assembles the response for detailed content
	 *
	 *	@param	Array	$content	getRawContent() response array. Each element is a nodeid-keyed array. Each
	 *								subarray must have the following data at minimum: nodeid, channelid,
	 *								contenttypeid, starter, showopen, userid, setfor (if VM), nodeoptions
	 *
	 *	@return	Array	Nodeid-keyed array of the $content data, plus additional data such as contenttypeclass,
	 *					createpermissions, moderatorperms, channeltype, permissions, etc, @TODO: complete this list
	 *					Also the expanded nodeoptions of:
	 *						allow_post, moderate_comments, approve_membership, invite_only, autoparselinks,
	 *						disablesmilies, disable_bbcode, hide_title, hide_author, hide_publishdate,
	 *						display_fullincategory, display_pageviews, hide_comment_count
	 */
	public function assembleContent(&$content)
	{
		// get the class name for this content type to add to results
		$contentTypeClass = vB_Types::instance()->getContentTypeClass($this->contenttypeid);
		$userContext = vB::getUserContext();
		$languageid = vB::getCurrentSession()->get('languageid');
		//We can save some time by saving, for a page load, the list of
		// channels we already know the current user can't post.

		static $noComment = array();

		$results = array();
		$needUserRep = array();
		$cache = vB_Cache::instance(vB_Cache::CACHE_STD);
		$fastCache = vB_Cache::instance(vB_Cache::CACHE_FAST);
		$thisUserid = vB::getCurrentSession()->get('userid');
		//pre-cache channels
		$channelids = $nodeids = array();
		$canUseRep = $userContext->hasPermission('genericpermissions', 'canuserep');

		foreach($content AS $key => $record)
		{
			if (!$this->checkComplete($record))
			{
				unset($content[$key]);
			}

			if (!empty($record['channelid']))
			{
				$channelids[$record['channelid']] = $record['channelid'];
			}
			else if ($record['contenttypeid'] == $this->channelTypeId)
			{
				$content[$key]['channelid'] = $record['nodeid'];
				$channelids[$record['nodeid']] = $record['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($record['starter']);
				$content[$key]['channelid'] = $starter['parentid'];
				$channelids[$starter['parentid']] = $starter['parentid'];
			}
			$nodeids[] = $record['nodeid'];
		}

		// preload closure
		vB_Library::instance('node')->fetchClosureParent($nodeids);
		$channels = vB_Library::instance('node')->getNodes($channelids);
		//and preload channel permission data
		$channelPerms = vB::getUserContext()->fetchPermsForChannels($channelids);
		$needOnlineStatus = array();
		$needReputation = array();
		$contentUserids = array();
		$channelTypes = vB::getDatastore()->getValue('vBChannelTypes');

		//we can comment if there is at least one content type we can create, and the channel (in case this is a blog or
		//social group) doesn't have comments disabled, and either it's your and you have canreply
		$userid = vB::getCurrentSession()->get('userid');
		$commentsEnabled =  vB::getDatastore()->getOption('postcommentthreads');
		$canmanageownprofile = $channelPerms['global']['canmanageownprofile'];
		foreach($content AS $key => $record)
		{
			$record['contenttypeclass'] = $contentTypeClass;
			$channelid = $record['channelid'];
			$thisChannelPerms = $channelPerms[$channelid];
			//$record['createpermissions'] = $thisChannelPerms['cancreate'];
			/*
			 *	I'm making the assumption that the 'createpermissions' here is only used in the templates to check
			 *	whether a REPLY to this specific node can be created or not. For an example, refer to their use in
			 *	the contententry template. Note that if what we need is NOT supposed to be permissions to reply to
			 *	this node, we need to fetch the correct permissions at that time. For an example, refer to the
			 *	createcontent controller's loadEditor(), where it calls getCreatepermissionsForEdit().
			 *	Since the permissions for replying to this node might be different from replying to the channel,
			 *	we don't want to rely on the channel perm's 'cancreate'.
			 *	PS. Never use these for "real" permission checking ("real" permission checking is in content api's validate())
			 */
			$record['createpermissions'] = vB::getUserContext()->getCanCreate($record['nodeid']);
			$record['moderatorperms'] = $thisChannelPerms['moderate'];

			//channeltype
			if (isset($channelTypes[$channelid]))
			{
				$record['channeltype'] = $channelTypes[$channelid];
			}
			else
			{
				$record['channeltype'] = '';
			}

			$thisChannelPerms['global'] = $channelPerms['global'];

			if ($this->getCanEdit($record, $userContext, $thisChannelPerms))
			{
				$record['canedit'] = 1;
			}
			else
			{
				$record['canedit'] = 0;
			}

			$record['canview'] = $thisChannelPerms['canview'];
			//There are four moderator-like permissions. Let's start setting them to zero. If the user has that
			// permissions we'll enable it soon.
			foreach (array('canmove', 'candeleteposts', 'candeletethread', 'canopenclose') AS $permName)
			{
				$record[$permName] = 0;
			}

			if ($record['contenttypeid'] == $this->channelTypeId)
			{
				$record['canundeleteposts'] = $record['canremove'] = false; // TODO: GET RID OF $record['canremove']
			}
			else
			{
				$record['canremove'] = $thisChannelPerms['canremoveposts']; // TODO: GET RID OF $record['canremove']
				// Only the soft-delete mod permission grants undelete permission.
				// A user could get soft-delete permissions *without* undelete, which is why we leave
				// candeleteposts to be set by getCanDelete() below
				$record['canundeleteposts'] = $thisChannelPerms['moderate']['candeleteposts'];
				// TODO, update above with the new, real moderator permission when we work on VBV-12234
			}
			// showUnpublishedNotice is used by display_Topics_item template to show the X Unpublished notice
			// in channel view. Because we muck around with the "moderator-like" permissions, including
			// candeleteposts, we can't rely on just that in the templates, so we have to do this.
			// For reference, VBV-12177
			$record['showUnpublishedNotice'] = $thisChannelPerms['moderate']['candeleteposts'];

			// Hard delete & soft delete permissions can be independent.
			// For ex. canremoveposts doesn't give you soft-delete permissions, but does allow you to physically delete
			// The following values may be determined here in addition to 'candeletethread' & 'candeleteposts':
			$record['canharddeleteposts'] = 0;
			$record['moderatorperms']['canremoveposts'] =  0;

			// hard delete
			if ($this->getCanDelete($record, $userContext, $thisChannelPerms, true))
			{
				// can hard delete
				$record['canharddeleteposts'] = 1;
				$record['moderatorperms']['canremoveposts'] =  1;
				// is this node a starter? That means they can delete the thread.
				if ($record['starter'] == $record['nodeid'])
				{
					$record['candeletethread'] = 1;
				}
			}

			// soft delete
			if ($this->getCanDelete($record, $userContext, $thisChannelPerms, false))
			{
				// can soft delete
				$record['candeleteposts'] = 1;
				// 'cansoftdeleteposts' is used just to ensure $record['canmoderate'] will be set to 1 below
				// so that topics will have a checkbox in channel view, VBV-12183. It's not used for anything else.
				// we can't just set $record['moderatorperms']['canremoveposts'], because that value is used for
				// physical-delete specific things in the templates.
				$record['moderatorperms']['cansoftdeleteposts'] =  1;
				// is this node a starter? That means they can delete the thread.
				if ($record['starter'] == $record['nodeid'])
				{
					$record['candeletethread'] = 1;
				}
			}

			// TODO: figure out what this does and remove if it does nothing
			$record['moderate']['candeleteposts'] = $thisChannelPerms['moderate']['canundeleteposts'] = 0;
			$thisChannelPerms['canmoderateposts'] = $this->getCanModerate($record, $userContext, $thisChannelPerms);

			if ($thisChannelPerms['canmoderateposts'])
			{
				$record['canmoderate'] = $record['canmoderateposts'] = 1;
				// TODO: figure out what this does and remove if it does nothing
				$record['moderate']['canmoderateposts'] = $thisChannelPerms['moderate']['canmoderateposts'] = 1;
			}
			else
			{
				$record['canmoderate'] = 0;
			}

			//check the four 'my own' moderator-like permissions
			if ($record['userid'] == $thisUserid)
			{
				//and the four moderator-like permissions for node owners.
				//move is for the topic, not replies & comments
				if (($record['nodeid'] == $record['starter']) AND $thisChannelPerms['canmove'])
				{
					$record['moderatorperms']['canmove'] = 1;
					$record['moderatorperms']['canmovethread'] = 1;
				}

				if (($record['nodeid'] == $record['starter']) AND $thisChannelPerms['canopenclose'])
				{
					$record['moderatorperms']['canopenclose'] = 1;
				}

				// TODO: figure out a way to combine this & the following if blocks into the
				// getCanDelete() checks we do above.
				// Note, this is probably redundant due to 'cansoftdeleteposts' above.
				if (($record['nodeid'] == $record['starter']) AND $thisChannelPerms['candeleteownthread'])
				{
					// I don't think user_candeleteownpost is actually used in any templates. It seems to be used
					// just so that $record['canmoderate'] will be set to 1 below, so that inline mod will be enabled.
					// I'm leaving this alone for now, but there's probably a better way to do this.
					$record['moderatorperms']['user_candeleteownpost'] = 1;
				}

				if (($record['nodeid'] != $record['starter']) AND $thisChannelPerms['candeleteownpost'])
				{
					$record['moderatorperms']['candeleteposts'] = 1;
				}

				if ($thisChannelPerms['caneditown'])
				{
					$record['moderatorperms']['caneditpost'] = 1;
				}
			}

			// allow the receiver to manage their VMs if they have permission
			if (!empty($record['setfor']) AND ($record['setfor'] == $userid) AND $canmanageownprofile)
			{
				$record['canmoderateposts'] = 1;
				$record['candeleteposts'] = 1;
				$thisChannelPerms['moderate']['canmoderateposts'] = 1; // TODO: figure out what this does and remove if it does nothing
			}

			if (($this->textCountChange > 0) AND $this->isPublished($record))
			{
				$record['textcount_1'] = $record['textcount'] + 1 ;
				$record['totalcount_1'] = $record['totalcount'] + 1 ;

				if($record['canmoderate'])
				{
					$record['textcount_1'] += $record['textunpubcount'];
					$record['totalcount_1'] += $record['totalunpubcount'];
				}
			}
			else
			{
				$record['textcount_1'] = $record['textcount'];
				$record['totalcount_1'] = $record['totalcount'] ;
			}

			// Add userinfo for reputation - is there a cached version?
			if (($record['userid'] > 0) AND ($record['contenttypeid'] != $this->channelTypeId))
			{
				$needReputation['vBUserRep_' . $record['userid']] = 'vBUserRep_' . $record['userid'];
			}
			$record['allow_post'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST) ? 1 : 0;
			$record['moderate_comments'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_MODERATE_COMMENTS) ? 1 : 0;
			$record['approve_membership'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP) ? 1 : 0;
			$record['invite_only'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_INVITEONLY) ? 1 : 0;
			$record['autoparselinks'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_PARSELINKS) ? 1 : 0;
			$record['disablesmilies'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_DISABLE_SMILIES) ? 1 : 0;
			$record['disable_bbcode'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_DISABLE_BBCODE) ? 1 : 0;
			$record['hide_title'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_HIDE_TITLE) ? 1 : 0;
			$record['hide_author'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_HIDE_AUTHOR) ? 1 : 0;
			$record['hide_publishdate'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_HIDE_PUBLISHDATE) ? 1 : 0;
			$record['display_fullincategory'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_DISPLAY_FULL_IN_CATEGORY) ? 1 : 0;
			$record['display_pageviews'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_DISPLAY_PAGEVIEWS) ? 1 : 0;
			$record['hide_comment_count'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_HIDE_COMMENT_COUNT) ? 1 : 0;


			$record['can_flag'] = intval($userid) ? 1 : 0;

			//If this is not a channel, we need the user information to check reputation.
			if ($record['contenttypeid'] != $this->channelTypeId)
			{
				$contentUserids[] = $record['userid'];
				// these cache keys are set by vB_Library_User::preloadUserInfo
				$needOnlineStatus[$record['userid']] = "vb_UserInfo_{$record['userid']}_$languageid";
				//Now the moderator-type permissions

				if (!empty($record['moderatorperms']))
				{
					foreach ($record['moderatorperms'] AS $key => $perm)
					{
						if (($perm > 0) AND ($key != 'caneditpost'))
						{
							$record['canmoderate'] = 1;
							break;
						}
					}
				}
			}

			$infractionLibrary = vB_Library::instance('content_infraction');
			$record['caninfract'] = $infractionLibrary->canInfractNode($record['nodeid'], $record) ? 1 : 0;
			$record['canviewnodeinfraction'] = $infractionLibrary->canViewNodeInfraction($record['nodeid'], $record) ? 1 : 0;

			$record['canseewholiked'] = $userContext->hasPermission('genericpermissions', 'canseewholiked') ? 1 : 0;

			// Let's make sure that these two are always set, so we don't run into undefine indices downstream somewhere.
			// Note that 'createpermissions' is instantiated above, near the beginning of this foreach body
			$record['can_comment'] = 0;
			$record['canreply'] = 0;

			//the channel permissions don't seem to use  vB_Api_Node::OPTION_ALLOW_POST and it appears to have
			//some special meaning for channels regarding comments so we'll handle channels seperately
			//If this is a channel we need to check canpostnew is the proper permission for channels
			if ($record['contenttypeid'] == $this->channelTypeId)
			{
				if (!$thisChannelPerms['canpostnew'])
				{
					$record['createpermissions'] = false;
				}

				if ((($record['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST) > 0))
				{
					$record['can_comment'] = (int)($thisChannelPerms['cancomment'] AND $commentsEnabled);
					$record['canreply'] = $thisChannelPerms['canreply'];
				}
			}
			//if we are a starter we can't comment, but we may be able to replay -- need to check "canreply" on
			//user perms
			else if ($record['starter'] == $record['nodeid'])
			{
				$record['canreply'] = 0;
				if (!$thisChannelPerms['canreply'])
				{
					$record['createpermissions'] = false;
				}
				else
				{
					$record['canreply'] = ((($record['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST) > 0) ? 1 : 0);
				}
			}
			//this is a reply or comment.  We can't reply and may or may not be able to comment
			else
			{
				// per VBV-4523, commenting requires BOTH cancomment AND canreply
				if (! ($thisChannelPerms['cancomment'] AND $thisChannelPerms['canreply'] ))
				{
					$record['createpermissions'] = false;
				}

				//if comments are disabled, then ... don't allow comments.
				if ((($record['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST) > 0) AND $commentsEnabled)
				{
					$record['can_comment'] = ($thisChannelPerms['cancomment'] AND $thisChannelPerms['canreply'] ) ? 1 : 0;

					if ($thisChannelPerms['cancomment'] AND !empty($record['starter']))
					{
						//If we were called with fullcontent then we already have the channel.
						if (empty($record['channelid']))
						{
							$thisParent = $this->nodeApi->getNode($record['starter']);
							$record['channelid'] = $thisParent['parentid'];
						}

						if (empty($channels[$record['channelid']]))
						{
							$channels[$record['channelid']] = $this->nodeApi->getNode($record['channelid']);
						}

						//special interpretation of vB_Api_Node::OPTION_ALLOW_POST) for channels if that's not
						//set then replies in this channel shouldn't allow comments.
						//We need to check the nodeoptions field.
						if (($channels[$record['channelid']]['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST) == 0)
						{
							$record['can_comment'] = 0;
						}
					}
				}
			}

			//regardless of how we set them above, if the node is closed
			//then don't allow replies unless the user has the rights to open it
			if (
				!$record['showopen'] AND
				!$thisChannelPerms['canopenclose'] AND
				!$userContext->getChannelPermission('moderatorpermissions', 'canopenclose', $channelid)
			)
			{
				$record['canreply'] = 0;
				$record['can_comment'] = 0;
				$record['canedit'] = 0;
			}

			/*
			 * Note 2014-02-19: We should remove 'canremove'. It doesn't really do anything outside of unit tests
			 *	currently. Not to confuse this with the 'canremove' checked in editor_contenttype_Text_comment template.
			 *	That's set in the createcontent controller.
			 *	I haven't removed it yet because it would cause a bunch of unit test failures, since they still check for
			 *	this even though it's not used anymore.
			 */

			$record['permissions'] = array(
				'canedit' => $record['canedit'],
				'canmoderate' => $record['canmoderate'],
				'canvote' => ($thisChannelPerms['canvote'] ? 1 : 0),
				'canuserep' => ($canUseRep ? 1 : 0),
				'canremove' => $record['canremove'],		// TODO: GET RID OF $record['canremove']
				'can_flag' => $record['can_flag'],
				'canviewthreads' => $thisChannelPerms['canviewthreads'],
				'canviewothers' => $thisChannelPerms['canviewothers'],
				'caninfract' => $record['caninfract'],
				'canviewnodeinfraction' => $record['canviewnodeinfraction'],
				'canseewholiked' => $record['canseewholiked'],
				'can_comment' => $record['can_comment'],
			);




			// can't like an infraction
			if ($record['permissions']['canuserep'] AND $this->contenttype == 'vBForum_Infraction')
			{
				$record['permissions']['canuserep'] = 0;
			}

			$record['moderatorperms']['canharddeleteposts'] = (int)$record['canharddeleteposts'];
			$record['moderatorperms']['candeleteposts'] = (int)$record['candeleteposts'];
			$record['moderatorperms']['canundeleteposts'] = (int)$record['canundeleteposts'];
			$record['moderatorperms']['candeletethread'] = empty($record['candeletethread']) ? 0 : 1;
			$record['moderatorperms']['canmoderateposts'] = empty($record['canmoderateposts']) ? 0 : 1;

			$results[$record['nodeid']] = $record;
		}

		if (!empty($contentUserids))
		{
			vB_Library::instance('user')->preloadUserInfo($contentUserids);
			// Add online status
			require_once(DIR . '/includes/functions_bigthree.php');
			// we just preloaded this info, so there must be a cache hit
			$cached = $fastCache->read($needOnlineStatus);
			$loadedSigPerm = array();
			foreach($results AS $key => $record)
			{
				if ($record['userid'] == 0)
				{
					continue;
				}
				$cache_key = "vb_UserInfo_{$record['userid']}_$languageid";
				$authorInfo = $cached[$cache_key];

				$results[$key]['signature'] = $authorInfo['signature'];
				if (!empty($authorInfo['signature']))
				{
					if (empty($loadedSigPerm[$record['userid']]))
					{
						$loadedSigPerm[$record['userid']] = vB::getUserContext($record['userid'])->hasPermission('genericpermissions', 'canusesignature');
					}
					$results[$key]['canSign'] = $loadedSigPerm[$record['userid']] ? 1 : 0;
				}

				$results[$key]['musername'] = vB_Api::instanceInternal("user")->fetchMusername($authorInfo);

				if (!isset($authorInfo['online']))
				{
					fetch_online_status($authorInfo);
				}
				$results[$key]['online'] = $authorInfo['online'];
				$options = vB::getDatastore()->getValue('options');

				if(
					!empty($options['postelements'])
					//postelements = 4 = showinfractions setting
					AND $options['postelements'] == 4
					//Check that we have at least one value
					AND ( $authorInfo['ipoints']
						OR $authorInfo['warnings']
						OR $authorInfo['infractions']
					)
					//Check permissions and the user is the same logged in
					AND ( $userContext->hasPermission('genericpermissions', 'canreverseinfraction')
						OR $userContext->hasPermission('genericpermissions', 'canseeinfraction')
						OR $userContext->hasPermission('genericpermissions', 'cangiveinfraction')
						OR vB::getCurrentSession()->get('userid') == $authorInfo['userid']
					)
				)
				{
					$results[$key]['postelements'] = $options['postelements'];
					$results[$key]['ipoints'] = $authorInfo['ipoints'];
					$results[$key]['warnings'] = $authorInfo['warnings'];
					$results[$key]['infractions'] = $authorInfo['infractions'];
				}

			}
		}

		if (!empty($needReputation))
		{
			$cached = $cache->read($needReputation);
			foreach($content AS $record)
			{
				// Add userinfo for reputation - is there a cached version?
				if (($record['userid'] > 0) AND ($record['contenttypeid'] != $this->channelTypeId))
				{

					if ($cached['vBUserRep_' . $record['userid']] !== false)
					{
						$cacheitem = $cached['vBUserRep_' . $record['userid']];
						$results[$record['nodeid']]['reputation'] = $cacheitem['reputation'];
						$results[$record['nodeid']]['showreputation'] = $cacheitem['showreputation'];
						$results[$record['nodeid']]['reputationlevelid'] = $cacheitem['reputationlevelid'];
						$results[$record['nodeid']]['reputationpower'] = $cacheitem['reputationpower'];
						$results[$record['nodeid']]['reputationimg'] = $cacheitem['reputationimg'];
					}
					else
					{
						$needUserRep[$record['nodeid']] = $record['userid'];
					}
				}
			}
		}

		//Now add reputation for any users for which we didn't have a cached value.
		if (!empty($needUserRep))
		{
			$reputationLib = vB_Library::instance('reputation');
			$userInfo = $this->assertor->assertQuery('user', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'userid' => $needUserRep));
			$bf_misc_useroptions = vB::getDatastore()->getValue('bf_misc_useroptions');

			$userReps = array();
			//build the reputation information
			foreach($userInfo AS $authorInfo)
			{
				$userid = $authorInfo['userid'];
				$userReps[$userid] = array();
				$userReps[$userid]['reputation'] = $authorInfo['reputation'];
				$userReps[$userid]['showreputation'] = $authorInfo['options'] & $bf_misc_useroptions['showreputation'];
				$userReps[$userid]['reputationlevelid'] = $authorInfo['reputationlevelid'];
				$userReps[$userid]['reputationpower'] = $reputationLib->fetchReppower($authorInfo);
				$userReps[$userid]['reputationimg'] = $reputationLib->fetchReputationImageInfo($authorInfo);
				//cache this for a day
				$cache->write('vBUserRep_' . $userid, $userReps[$userid], 1440, array("fUserContentChg_$userid", "userChg_$userid"));

			}
			foreach($needUserRep AS $nodeid => $userid)
			{
				if (!empty($userReps[$userid]))
				{
					foreach ($userReps[$userid] AS $field => $val)
					{
						$results[$nodeid][$field] = $val;
					}
				}
			}
		}

		// censor textual node items
		vB_Library_Node::censorNodes($results);

		return $results;
	}

	public function getIndexableFromNode($node, $include_attachments = true)
	{
		//merge in the attachments if any
		if ($include_attachments)
		{
			$indexableContent = $this->getIndexableContentForAttachments($node['nodeid']);
		}
		else
		{
			$indexableContent = array();
		}
		$indexableContent['title'] = isset($node['title']) ? $node['title'] : '';
		return $indexableContent;

	}

	/**
	 * The classes  that inherit this should implement this function
	 * It should return the content that should be indexed
	 * If there is a title field, the array key for that field should be 'title',
	 * the rest of the text can have any key
	 * @param int $nodeId  - it might be the node (assiciative array)
	 * @return array $indexableContent
	 */
	public function getIndexableContent($nodeId, $include_attachments = true)
	{
		// we might already have the content
		if (is_array($nodeId) AND array_key_exists('nodeid', $nodeId))
		{
			$node = $nodeId;
			$nodeId = $node['nodeid'];
		}
		else
		{
			$node = vB_Library::instance('node')->getNodeBare($nodeId);
		}
		if (empty($node))
		{
			return false;
		}
		if(!array_intersect($this->index_fields, array_keys($node)))
		{
			$this->fillContentTableData($node);
		}

		return $this->getIndexableFromNode($node, $include_attachments);
	}

	/**
	 * Finds the correct conversation starter for a node
	 *
	 *	@param	int		nodeid of the item being checked
	 *
	 *	@return	int		the conversation starter's nodeid
	 */
	public function getStarter($nodeid)
	{
		$parents = vB_Library::instance('node')->getParents($nodeid);
		$channelContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Channel');

		foreach ($parents as $sequence => $parent)
		{
			if ($parent['contenttypeid'] == $channelContentTypeId)
			{
				if ($sequence == 0)
				{
					return $nodeid;
				}
				else
				{
					return $parents[$sequence - 1]['nodeid'];
				}
			}
		}
	}

	/**
	 * Gets the main conversation node.
	 *
	 * 	@param	int		the nodeid
	 * 	@return	mixed	the main conversation node
	 */
	public function getConversationParent($nodeid)
	{
		if (empty($nodeid))
		{
			return false;
		}

		$parentId = $this->getStarter($nodeid);
		$parentNode = vB_Library::instance('node')->getNodeBare($parentId);

		return $parentNode;
	}


	/**
	 * determines whether a specific node is a visitor message
	 *
	 *	@param	int
	 *
	 *	@return bool
	 */
	public function isVisitorMessage($nodeid)
	{
		//Something is a visitor message if it's a descendant in the closure table of the protected channel
		// for visitor messages
		$test = vB_Library::instance('node') -> fetchClosureParent($nodeid, $this->nodeApi->fetchVMChannel());
		reset($test);
		$test = current($test);
		return ($test AND !empty($test['child']));
	}

	/**
	 * Determines whether a specific node is a comment on a thread reply or not
	 *
	 * @param	int	Node ID
	 * @param	array	Node information
	 * @return	bool
	 */
	public function isComment($nodeid, array $node = null)
	{
		if ($node === null)
		{
			$node = vB_Library::instance('node')->getNodeBare($nodeid);
		}

		return (
			// not a channel
			($this->contenttype != 'vBForum_Channel')
			AND
			// not a topic starter
			($node['starter'] != $node['nodeid'])
			AND
			// not a topic reply
			($node['parentid'] != $node['starter'])
		);
	}


	/**
	 * Get the indexable content for attachments.  Separate this so that child classes
	 * can rewrite getIndexableContent entirely without having to copy as much code
	 */
	protected function getIndexableContentForAttachments($nodeId)
	{
		$attachments = vB_Api::instanceInternal('Node')->getNodeAttachments($nodeId);
		$indexableContent = array();
		foreach ($attachments as $attach)
		{
			if ($attach['nodeid'] == $nodeId)
			{
				continue;
			}
			$indexableContent[$attach['nodeid']] = $attach['caption'] . ' ' . $attach['filename'];
		}
		return $indexableContent;
	}

	/**
	 * returns the tables used by this content type.
	 *
	 *	@return	Array
	 *
	 */
	public function fetchTableName()
	{
		if (is_array($this->tablename))
		{
			return $this->tablename;
		}
		return array($this->tablename);
	}

	/**
	* This attempts to get the cached data for nodes
	*
	*	@param	mixed integer or array of integers
	* @param	integer one of the constants for level of data
	*
	*	@return	mixed array('found' => array of node values per the constant, 'notfound' => array of nodeids);
	*
	*/
	public static function fetchFromCache($nodeids, $level)
	{
		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}

		//if we aren't caching then skip.
		if (!self::$cacheNodes)
		{
			return array('found' => array(), 'notfound' => $nodeids);
		}

		if ($level == self::CACHELEVEL_NODE)
		{
			$levelstr = "_lvl1data";
		}
		//implicitly on of self::CACHELEVEL_CONTENT or self::CACHELEVEL_FULLCONTENT.
		//if the caller sends something else they deserve what they get.
		else
		{
			$levelstr = "_lvl3data";
		}

		$notfound = array_combine($nodeids, $nodeids);
		$keys = array();
		foreach($nodeids AS $nodeid)
		{
			//not sure why we need this, but it was in the code that I refactored.
			//on futher review it appears to be an attempt to skip processing for
			//nodes that are obviously invalid.  Due to the vagaries of php processing
			//this will catch 0, false, null, and string that don't start with a number.
			//This causes some odd behavior -- we get a different error result for node 0
			//and node -1 requests -- and the situation where the found and notfound arrays
			//don't necesarily contain everything in $nodeids.  But that's the behavior we're
			//used to and I don't want to change it at this point.
			if (!intval($nodeid))
			{
				unset($notfound[$nodeid]);
				continue;
			}

			$keys[] =  'node_' . $nodeid . $levelstr;
		}

		$cached = vB_Cache::instance(vB_Cache::CACHE_LARGE)->read($keys);
		if (!is_array($cached))
		{
			//some kind of cache error, not much we can do other than report that
			//we didn't find anything.
			return array('found' => array(), 'notfound' => $nodeids);
		}

		//create the "not found" map.  We'll remove nodes from it as we read them from the cache.
		$found = array();
		foreach ($cached AS $cacheid => $cacheRecord)
		{
			if (!empty($cacheRecord))
			{
				$found[$cacheRecord['nodeid']] = $cacheRecord;
				unset($notfound[$cacheRecord['nodeid']]);
			}
		}
		return array('found' => $found, 'notfound' => array_values($notfound));
	}

	/**
	 * 	writes new cached data for nodes
	 *
	 * 	Note that this is affected by the 'cache_node_data' admin option.
	 * 	If that is not true then this function does nothing.
	 *
	 *	@param	mixed		array of node values
	 * 	@param	integer		one of the constants for level of data
	 *
	 *	@return void
	 */
	public static function writeToCache($nodes, $level)
	{
		//if we aren't caching then skip.
		if (!self::$cacheNodes)
		{
			return;
		}

		$largeCache = vB_Cache::instance(vB_Cache::CACHE_LARGE);
		foreach ($nodes AS $node)
		{
			//make sure data was passed correctly
			if (!empty($node['nodeid']))
			{
				$hashKey = 'node_' . $node['nodeid'] . "_lvl" . $level .  "data";
				$largeCache->write($hashKey, $node, 1440, 'nodeChg_' . $node['nodeid']);;
			}
		}
	}

	protected function verifyPrefixid($prefixid)
	{
		if ($prefixid)
		{
			$prefix = $this->assertor->getRow('vBForum:prefix', array('prefixid' => $prefixid));

			if (!$prefix)
			{
				throw new vB_Exception_Api('invalid_prefixid');
			}

			require_once(DIR . '/includes/functions_prefix.php');

			if (!can_use_prefix($prefixid))
			{
				throw new vB_Exception_Api('invalid_prefixid');
			}
		}
	}

	/**
	 * Verifies that the post iconid is valid
	 *
	 * @param	int	post icon ID
	 */
	protected function verifyPostIconid($posticonid)
	{
		if ($posticonid)
		{
			$posticons = vB_Api::instanceInternal('Icon')->fetchAll();

			if (empty($posticons[$posticonid]))
			{
				throw new vB_Exception_Api('invalid_posticonid');
			}
		}
	}


	protected function changeContentType($nodeid, $oldcontentType, $newcontentType)
	{
		$newLibrary = $this->getContentLib($newcontentType);
		$oldLibrary = $this->getContentLib($oldcontentType);

		$oldTables = $oldLibrary->fetchTableName();
		$newTables = $newLibrary->fetchTableName();

		$deleteTables = array_diff($oldTables, $newTables);
		$addTables = array_diff($newTables, $oldTables);

		foreach ($deleteTables as $table)
		{
			$this->assertor->delete('vBForum:' . $table, array(
				'nodeid' => $nodeid,
			));
		}

		foreach ($addTables as $table)
		{
			$this->assertor->insert('vBForum:' . $table, array(
				'nodeid' => $nodeid,
			));
		}
		vB_Library::instance('search')->attributeChanged($nodeid);
	}

	/**
	 * Calculates the CRC based on the indexable content
	 * @param array $data
	 * @return string
	 */
	protected function getCRC32($data)
	{
		try
		{
			$indexableContent = $this->getIndexableFromNode($data, false);
		}
		catch (Exception $e)
		{
			$indexableContent = array();
		}
		return sprintf('%u', crc32(implode(' ', $indexableContent)));
	}

	protected function fillContentTableData(&$node)
	{
		$contentData = $this->assertor->getRow('vBForum:getContentTablesData', array('tablename' => $this->tablename, 'nodeid' => $node['nodeid']));
		if (!empty($contentData))
		{
			$node += $contentData;
		}
		return $contentData;
	}

	/**
	 *	Examines the node record returned from the node library and determines if any information
	 *	needs to be removed before passing beyond the API layer.
	 *
	 *	This is part of the library layer because its needed for a number of API classes that
	 *	return node data in various forms.
	 *
	 * 	@param array $node the node array to be cleaned.
	 *	@return none
	 */
	public function removePrivateDataFromNode(&$node)
	{
		if (empty($node['moderatorperms']['canviewips']) AND empty($node['content']['moderatorperms']['canviewips']))
		{
			//this field is either not set or not always set.  Let's not add it if
			//doesn't already exist.
			if (isset($node['ipaddress']))
			{
				$node['ipaddress'] = '';
			}
			$node['content']['ipaddress'] = '';
		}
	}

	/** This function checks to see if a node is valid, and if not it deletes or fixes it.*/
	public function checkComplete($node)
	{
		if ($this->cannotDelete)
		{
			return true;
		}
		$clean = $this->getNodeClean($node);

		if (!$clean)
		{
			$this->incompleteNodeCleanup($node);
		}
		return $clean;
	}

	/** Whether this type can be deleted. Infractions, for example, cannot be.
	 *
	 *	@return 	boolean
	 */
	public function getCannotDelete()
	{
		return $this->cannotDelete;
	}

	/** Checks to see if the node has all the required data.
	 *
	 * 	@param integer	the nodeid to be checked
	 *
	 * @return	bool
	 */
	protected function getNodeClean($node)
	{
		if ($this->contenttypeid != $node['contenttypeid'])
		{
			// Skip the check if the node doesn't match current API's type (VBV-10659)
			return true;
		}

		//Each table should have a tablename_node field. If missing, we have a problem.
		foreach((array)$this->tablename AS $table)
		{
			if (empty($node[$table . "_nodeid"]))
			{
				return false;
				break;
			}
		}
		return true;
	}

	/** This cleans up for a node that was found to be incomplete by deleting the child nodes and subsidiary table records.  It is often overridden in child classes.
	 *
	 *	@param	mixed	node record, which may have missing child table data.
	 * @return bool     Whether the node has been cleaned up
	 */
	protected function incompleteNodeCleanup($node)
	{
		if ($this->contenttypeid != $node['contenttypeid'])
		{
			// Skip the check if the node doesn't match current API's type (VBV-10659)
			return false;
		}

		if (!$this->doIncompleteNodeCleanup)
		{
			// Skip the cleanup for normal pageviews.
			// Cleanup can be a damaging action, especially
			// if the nodes deemed incomplete are not truely
			// incomplete. See VBV-12708.
			// Currently, doIncompleteNodeCleanup is only true
			// when this is called via tools.php to clean
			// up the node table.
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']))
			{
				// in debug mode, throw an error to make the problem more apparent.
				// if this happens for a seemingly good node, it might be that the
				// cached data for it is incorrect (VBV-12708)
				throw new vB_Exception_Api('incomplete_node');
			}
			else
			{
				// silently skip this node
				return false;
			}
		}

		$children = $this->assertor->assertQuery('vBForum:node', array('parentid' => $node['nodeid']));

		if ($children->valid())
		{
			return false;
		}

		//we can't use the normal delete functions for the damaged node because they might fail
		$params = array(vB_db_Query::TYPE_KEY =>vB_dB_Query::QUERY_DELETE, 'nodeid' => $node['nodeid']);
		foreach((array)$this->tablename AS $table)
		{
			$this->assertor->assertQuery('vBForum:' . $table, $params);
		}
		vB_Library::instance('node')->deleteNode($node['nodeid']);
		vB_Library::instance('search')->purgeCacheForCurrentUser();
		//These records should be gone already, but just to be sure.
		$this->assertor->assertQuery('vBForum:closure', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'child' => $node['nodeid']));
		vB_Cache::instance()->allCacheEvent(array('nodeChg_' . $node['parentid'], 'nodeChg_' . $node['nodeid']));
		vB_Library::instance('node')->clearCacheEvents($node['nodeid']);

		return true;
	}

	/**
	 * Sets the $doIncompleteNodeCleanup flag
	 *
	 * @param	bool Flag value
	 */
	public function setDoIncompleteNodeCleanup($value)
	{
		$this->doIncompleteNodeCleanup = (bool) $value;
	}

	/**
	 * This function needs to be implemented by sub classes that
	 * want to handle attachments. See the text library implementation
	 * for the full description.
	 */
	protected function handleAttachments($type, $nodeid, $data, $options = array()){}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89714 $
|| #######################################################################
\*=========================================================================*/
