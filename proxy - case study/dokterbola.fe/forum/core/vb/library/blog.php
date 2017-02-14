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
 * vB_Library_Blog
 *
 * @package vBApi
 * @access public
 */
class vB_Library_Blog extends vB_Library
{
	protected $blogChannel = false;

	protected function __construct()
	{
		parent::__construct();
		$this->assertor = vB::getDbAssertor();
		$this->blogChannel = $this->getBlogChannel();
	}

	public function createBlog($input)
	{
		return $this->createChannel($input, $this->getBlogChannel(), vB_Page::getBlogConversPageTemplate(), vB_Page::getBlogChannelPageTemplate(), vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID);
	}

	/**
	 * Create a blog channel.
	 *
	 * @param array $input
	 * @param int $channelid
	 * @param int $channelConvTemplateid
	 * @param int $channelPgTemplateId
	 * @param int $ownerSystemGroupId
	 *
	 * @return int The nodeid of the new blog channel
	 */
	public function createChannel($input, $channelid, $channelConvTemplateid, $channelPgTemplateId, $ownerSystemGroupId)
	{
		$input['parentid'] = $channelid;

		$input['inlist'] = 1; // we don't want it to be shown in channel list, but we want to move them
		$input['protected'] = 0;

		if (empty($input['userid']))
		{
			$input['userid'] =  vB::getCurrentSession()->get('userid');
		}


		if (!isset($input['publishdate']))
		{
			$input['publishdate'] = vB::getRequest()->getTimeNow();
		}

		$input['templates']['vB5_Route_Channel'] = $channelPgTemplateId;
		$input['templates']['vB5_Route_Conversation'] = $channelConvTemplateid;

		// add channel node
		$channelLib = vB_Library::instance('content_channel');
		$input['page_parentid'] = 0;
		$result = $channelLib->add($input, array('skipFloodCheck' => true, 'skipDupCheck' => true));
		//Make the current user the channel owner.
		$userApi = vB_Api::instanceInternal('user');

		$usergroup = vB::getDbAssertor()->getRow('usergroup', array('systemgroupid' => $ownerSystemGroupId));

		if (empty($usergroup) OR !empty($usergroup['errors']))
		{
			//This should never happen. It would mean an invalid parameter was passed
			throw new vB_Exception_Api('invalid_request');
		}
		vB_User::setGroupInTopic($input['userid'], $result['nodeid'], $usergroup['usergroupid']);
		vB_Cache::allCacheEvent(array('nodeChg_' . $this->blogChannel, "nodeChg_$channelid"));
		vB::getUserContext()->rebuildGroupAccess();
		vB_Channel::rebuildChannelTypes();
		// clear follow cache
		vB_Api::instanceInternal('follow')->clearFollowCache(array($input['userid']));
		return $result['nodeid'];
	}

	/**
	 * @uses fetch the id of the global Blog Channel
	 * @return int nodeid of actual Main Blog Channel
	 */
	public function getBlogChannel()
	{
		if ($this->blogChannel)
		{
			return $this->blogChannel;
		}
		// use default pagetemplate for blogs
		$blogChannel = vB_Library::instance('content_channel')->fetchChannelByGUID(vB_Channel::DEFAULT_BLOG_PARENT);

		if (isset($blogChannel['nodeid']))
		{
			$this->blogChannel = $blogChannel['nodeid'];
		}
		return $this->blogChannel;
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
