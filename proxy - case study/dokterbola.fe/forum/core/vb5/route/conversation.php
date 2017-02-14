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

class vB5_Route_Conversation extends vB5_Route
{
	const REGEXP = '(?P<nodeid>[0-9]+)(?P<title>(-[^!@\\#\\$%\\^&\\*\\(\\)\\+\\?/:;"\'\\\\,\\.<>= \\[\\]]*)*)(?:/contentpage(?P<contentpagenum>[0-9]+))?(?:/page(?P<pagenum>[0-9]+))?';
	const CUSTOM_URL_REGEXP = '(?:/contentpage(?P<contentpagenum>[0-9]+))?(?:/page(?P<pagenum>[0-9]+))?'; // Note that the leading / means that a multi-page conversation can't be set as a home page

	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		parent::__construct($routeInfo, $matches, $queryString, $anchor);
		if (isset($this->arguments['channelid']))
		{
			$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
			$hashKey = 'vbRouteChannelInfo_'. $this->arguments['channelid'];
			$channelInfo = $cache->read($hashKey);
			if (empty($channelInfo))
			{
				// check if we need to force a styleid
				$channel = vB_Library::instance('Content_Channel')->getBareContent($this->arguments['channelid']);
				$channel = array_pop($channel);
				$channelInfo['styleid'] = $channel['styleid'];
				$channelInfo['options'] = $channel['options'];
				$channelInfo['routeid'] = $channel['routeid'];

				$channelInfo['rss_enabled'] = $channel['rss_enabled'];
				$channelInfo['rss_route'] = $channel['rss_route'];
				$channelInfo['title'] = $channel['title'];
				$cache->write($hashKey, $channelInfo, 1440, array('routeChg_' . $channelInfo['routeid'], 'nodeChg_' . $channelInfo['routeid']));
			}

			if (!empty($channelInfo['styleid']))
			{
				if($channelInfo['options']['styleoverride'])
				{
					// the channel must force the style
					$this->arguments['forceStyleId'] = $channelInfo['styleid'];
				}
				else
				{
					// the channel suggests to use this style
					$this->arguments['routeStyleId'] = $channelInfo['styleid'];
				}
			}

			// rss info
			$this->arguments['rss_enabled'] = $channelInfo['rss_enabled'];
			$this->arguments['rss_route'] = $channelInfo['rss_route'];
			$this->arguments['rss_title'] = $channelInfo['title'];

			$this->setPageKey('pageid', 'channelid', 'nodeid');
		}

		if (isset($this->arguments['nodeid']))
		{
			if (!empty($this->arguments['userid'])
					&& !empty($this->arguments['contenttypeid'])
					&& !empty($this->arguments['title'])
					&& !empty($this->arguments['parentid'])
				)
			{
				$node['userid'] = $this->arguments['userid'];
				$node['contenttypeid'] = $this->arguments['contenttypeid'];
				$node['title'] = $this->arguments['title'];
				$node['parentid'] = $this->arguments['parentid'];
			}
			else
			{
				try
				{
					$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
				}
				catch (vB_Exception_Api $e)
				{
					if ($e->has_error('invalid_node_id'))
					{
						// the node does not exist, send a 404
						throw new vB_Exception_404('invalid_page_url');
					}
					else
					{
						// rethrow exception
						throw $e;
					}
				}
			}
			// privacy check
			$albumChannel = vB_Api::instance('node')->fetchAlbumChannel($node['nodeid']);
			if ($node['parentid'] == $albumChannel)
			{
				$userInfo = vB_Api::instance('user')->fetchProfileInfo($node['userid']);

				if ((($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Video')) AND !$userInfo['showVideos'])
					OR
					(($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Gallery'))
						AND (!$userInfo['showPhotos'] OR (!vB::getUserContext()->hasPermission('albumpermissions', 'canviewalbum'))
						)
					)
				)
				{
					throw new vB_Exception_NodePermission($node['nodeid']);
				}
			}
			$contentApi = vB_Api_Content::getContentApi($node['contenttypeid']);

			if (!$contentApi->validate($node, vB_Api_Content::ACTION_VIEW, $node['nodeid'], array($node['nodeid'] => $node)) AND empty($node['public_preview']))
			{

				throw new vB_Exception_NodePermission($node['nodeid']);
			}

			if(!empty($this->queryParameters))	// this probably has to be updated if we decide to use a querystring for contentPage instead of the path
			{
				$this->arguments['noindex'] = 1;
			}

			if(!empty($node['description']))
			{
				$this->arguments['metadescription'] = $node['description'];
			}

			// set user action
			$this->setUserAction('viewing_topic_x', $node['title'], $this->getFullUrl('fullurl'));

			// set last crumb
			$this->breadcrumbs[] = array(
				'title' => $node['title'],
				'url'	=> ''
			);

		}

		$this->arguments['pageSchema'] = 'http://schema.org/ItemPage';
	}

	protected function initRoute($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		parent::initRoute($routeInfo, $matches, $queryString, $anchor);

		// add querystring parameters for permalink (similar to vB5_Route_PrivateMessage constructor)
		if (isset($matches['innerPost']) AND ($innerPost = intval($matches['innerPost'])))
		{
			// TODO: make $innerPost a route argument in route record?
			if ($innerPost != $this->arguments['nodeid'])
			{
				// it's not the starter, either a reply or a comment
				$this->queryParameters['p'] = intval($matches['innerPost']);

				if (isset($matches['innerPostParent']) AND ($innerPostParent = intval($matches['innerPostParent']))
					AND $this->arguments['nodeid'] != $innerPostParent)
				{
					// it's a comment
					$this->queryParameters['pp'] = $innerPostParent;
				}
			}
		}

		// If the parent's initRoute didn't find a pagenum or contentpagenum, the route's arguments will just get the strings
		// '$pagenum' or '$contentpagenum'.
		// Let's set default values for pagenum and contentpagenum to be 1 if they didn't have an integer value.
		// Note that the isset on pagenum probably isn't necessary, since most* conversation routes will probably have it set.
		// (*Some older custom URLs may lack the pagenum in the regex)
		// contentpagenum, however, was added recently (mainly for articles) and some older routes may lack them. If we start
		// using it for conversations, we should add an upgrade step to refresh all old conversation routes, and/or put an else
		// block to define some default value for when it just lacks the contentpagenum argument.
		if (isset($this->arguments['pagenum']))
		{
			$this->arguments['pagenum'] = intval($this->arguments['pagenum']) > 0 ? intval($this->arguments['pagenum']) : 1;
		}
		if (isset($this->arguments['contentpagenum']))
		{
			$this->arguments['contentpagenum'] = intval($this->arguments['contentpagenum']) > 0 ? intval($this->arguments['contentpagenum']) : 1;
		}

	}

	private static function getChannelURL($channelid, $node = array())
	{
		$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);

		// looking up cache for the channel
		$hashKey = 'vbRouteChannelInfo_'. $channelid;
		$channelInfo = $cache->read($hashKey);
		if (!empty($channelInfo['channelUrl']))
		{
			$channelUrl = $channelInfo['channelUrl'];
		}
		elseif(!empty($channelInfo))
		{
			$channelInfo['channelUrl'] = $channelUrl = self::buildUrl($channelInfo['routeid']);
			$cache->write($hashKey, $channelInfo, 1440, array('routeChg_' . $channelInfo['routeid'], 'nodeChg_' . $channelInfo['routeid']));
		}
		/*
		//This case causes this function to return inconsistant data (in this case just the channelUrl value instead of the entire
		//Channel info array).  Even worse it *caches* inconsistant data, which causes notices in the constructor and potentially
		//problems with the code that reads the bad cache.  Trying to cache the url seperate from the doesn't seem to save us
		//much and making sure to load and cache the channelInfo basically collapses this case to the final case anyway.
		elseif(!empty($node['channelroute']))
		{
			$channelInfo['channelUrl'] = $channelUrl = self::buildUrl($node['channelroute']);
			$cache->write($hashKey, $channelInfo, 1440, array('routeChg_' . $node['channelroute'], 'nodeChg_' . $node['channelroute']));
		}
		 */
		else
		{
			$channel = vB_Library::instance('content_channel')->getBareContent($channelid);
			$channel = array_pop($channel);
			$channelInfo['styleid'] = $channel['styleid'];
			$channelInfo['options'] = $channel['options'];
			$channelInfo['routeid'] = $channel['routeid'];
			$channelInfo['channelUrl'] = $channelUrl = self::buildUrl($channel['routeid']);

			//not used here but is in other places where data saved to this hashKey is.
			$channelInfo['rss_enabled'] = $channel['rss_enabled'];
			$channelInfo['rss_route'] = $channel['rss_route'];
			$channelInfo['title'] = $channel['title'];

			$cache->write($hashKey, $channelInfo, 1440, array('routeChg_' . $channelInfo['routeid'], 'nodeChg_' . $channelInfo['routeid']));
		}

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			// We're gonna use this to build other urls. decode it to get encoded again later.
			$channelUrl = urldecode($channelUrl);
		}

		return $channelUrl;
	}

	protected static function validInput(array &$data)
	{
		// ignore page number
		unset($data['pagenum']);

		if (
				!isset($data['channelid']) OR !is_numeric($data['channelid']) OR
				!isset($data['pageid']) OR !is_numeric($data['pageid']) // this is used for rendering the page
			)
		{
			return FALSE;
		}
		$data['channelid'] = intval($data['channelid']);
		$data['pageid'] = intval($data['pageid']);

		if (!isset($data['prefix']))
		{
			$channelUrl = self::getChannelURL($data['channelid']);

			if ($channelUrl AND $channelUrl{0} == '/')
			{
				$channelUrl = substr($channelUrl, 1);
			}

			$data['prefix'] = $channelUrl;
			$data['regex'] = preg_quote($channelUrl) . '/' . self::REGEXP;
			$data['arguments'] = serialize(array(
				'nodeid'	=> '$nodeid',
				'pagenum'	=> '$pagenum',
				'contentpagenum'	=> '$contentpagenum',
				'channelid' => $data['channelid'],
				'pageid'	=> $data['pageid']
			));
		}
		else
		{
			if (!isset($data['nodeid']))
			{
				// when modifying the url for a conversation page, we need to store the conversation node id
				return FALSE;
			}

			// We have conversation route that's getting a special URL. Ex: /mySpecialTopic instead of /channelX/nodeId-topicTitle
			$data['regex'] = preg_quote($data['prefix']) . self::CUSTOM_URL_REGEXP;	// omit appending  '/' since the regex begins with it
			$data['arguments'] = serialize(array(
				'nodeid'	=> $data['nodeid'],
				'customUrl'	=> true,
				'pagenum'	=> '$pagenum',
				'contentpagenum'	=> '$contentpagenum',
				'channelid' => $data['channelid'],
				'pageid'	=> $data['pageid']
			));
			unset($data['nodeid']);
		}

		$data['class'] = __CLASS__;
		$data['controller']	= 'page';
		$data['action']		= 'index';
		// this field will be used to delete the route when deleting the channel (contains channel id)
		$data['contentid'] = $data['channelid'];

		unset($data['channelid']);
		unset($data['pageid']);

		return parent::validInput($data);
	}

	protected static function updateContentRoute($oldRouteInfo, $newRouteInfo)
	{
		$db = vB::getDbAssertor();
		$events = array();

		$arguments = unserialize($newRouteInfo['arguments']);

		// update routeid in nodes table
		$db->update('vBForum:node', array('routeid' => $newRouteInfo['routeid']), array('nodeid' => $arguments['nodeid']));
		$events[] = "nodeChg_{$arguments['nodeid']}";

		// don't modify the routeid for default pages, as it will still be used
		$updateIds = $db->assertQuery('page', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::COLUMNS_KEY => array('pageid'),
			vB_dB_Query::CONDITIONS_KEY => array('routeid' => $oldRouteInfo['routeid'], 'pagetype' => vB_Page::TYPE_CUSTOM),
		));

		if (!empty($updateIds))
		{
			$pageIds = array();
			foreach($updateIds AS $page)
			{
				$pageid = $page['pageid'];
				$events[] = "pageChg_$pageid";
				$pageIds[] = $pageid;
			}
			$db->update('page', array('routeid' => $newRouteInfo['routeid']), array('pageid' => $pageIds));
		}

		vB_Cache::allCacheEvent($events);
	}

	public function getUrl($node = false)
	{
		if (isset($this->arguments['customUrl']) AND $this->arguments['customUrl'])
		{
			$result = '/' . $this->prefix;
		}
		else
		{
			$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
			// looking up cache for the node
			$hashKey = 'vbRouteURLIndent_'. $this->arguments['nodeid'];
			$urlident = $cache->read($hashKey);
			if (empty($urlident) OR !is_array($urlident))
			{
				$urlident = array();
				if (!empty($this->matches['starter']) && isset($node['urlident']))
				{
					$node['starter'] = $this->matches['starter'];
					$node['urlident'] = $this->matches['urlident'];
				}
				else
				{
					$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
				}
				$mainConversationId = $node['starter'];
				if ($mainConversationId AND $mainConversationId != $this->arguments['nodeid'])
				{
					$mainConversation = vB_Library::instance('node')->getNodeBare($mainConversationId);
					$urlident['urlident'] = $mainConversation['urlident'];
					$urlident['nodeid'] = $mainConversationId;
				}
				else
				{
					$urlident['urlident'] = $node['urlident'];
					$urlident['nodeid'] = $this->arguments['nodeid'];
				}

				$cache->write($hashKey, $urlident, 1440, array('routeChg_' . $this->arguments['nodeid'], 'nodeChg_' . $this->arguments['nodeid']));
			}

			$channelUrl = $this->getChannelURL($this->arguments['channelid'], $this->matches);
			// remove trailing / (special case, non-default home page with a conversation route)
			$channelUrl = rtrim($channelUrl, '/');
			$result = $channelUrl . '/' . $urlident['nodeid'] . '-' . $urlident['urlident'];
		}

		// putting /contentpageX before /pageX
		if (isset($this->arguments['contentpagenum']) AND is_numeric($this->arguments['contentpagenum']) AND $this->arguments['contentpagenum'] > 1)
		{
			$result .= '/contentpage' . intval($this->arguments['contentpagenum']);
		}

		if (isset($this->arguments['pagenum']) AND is_numeric($this->arguments['pagenum']) AND $this->arguments['pagenum'] > 1)
		{
			$result .= '/page' . intval($this->arguments['pagenum']);
		}

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$result = vB_String::encodeUtf8Url($result);
		}

		return $result;
	}

	public function  getCanonicalRoute()
	{
		if (!isset($this->canonicalRoute))
		{
			$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
			$data = array(
				'channelid'=>$this->arguments['channelid'],
				'nodeid'=>$this->arguments['nodeid']
			);
			$queryParameters = $this->queryParameters;

			try {
				// calculate page number for canonical URL e.g. /16-awsome-thread/page3
				if (isset($queryParameters['p']))
				{
					// Use user API isntead of vB5_User (avoid frontend usage in core)
					$usersettings = vB_Api::instanceInternal('user')->fetchCurrentUserinfo();
					// get result per page from user preference, default to widget config
					if (isset($usersettings['maxposts']) && $usersettings['maxposts'] != -1)
					{
						$resultsPerPage = $usersettings['maxposts'];
					}
					else
					{
						$page = vB_Api::instanceInternal('page')->fetchPageById($this->arguments['pageid'], $this->arguments);
						$displaySections = vB_Api::instanceInternal('widget')->fetchHierarchicalWidgetInstancesByPageTemplateId($page['pagetemplateid'], $this->arguments['channelid']);
						foreach ($displaySections as $section)
						{
							foreach ($section as $widget)
							{
								//this is not a great way of doing business, but the perPage is defined by the widget config so we
								//need to get it.  That's a problem that will require considerable thought to resolve.

								//widget_conversationdisplay
								if ($widget['guid'] == 'vbulletin-widget_6-4eb423cfd69f41.95279534')
								{
									$widgetConfig = vB_Api::instanceInternal('widget')->fetchConfig($widget['widgetinstanceid']);
									$resultsPerPage = $widgetConfig['resultsPerPage'];
									break 2;
								}
								else if ($widget['guid'] == 'vbulletin-widget_articledisplay-4eb423cfd6dea7.34930877')
								{
									$widgetConfig = vB_Api::instanceInternal('widget')->fetchConfig($widget['widgetinstanceid']);
									$resultsPerPage = $widgetConfig['commentsPerPage'];
									break 2;
								}
							}
						}
					}

					// grab queryParam 'filter_show' & pass it into getPageNumber(), and add to search_json
					if (isset($queryParameters['filter_show']))
					{
						$type = $queryParameters['filter_show'];
					}
					else
					{
						$type = '';
					}
					// get & set the page number
					$pagenumber = vB_Api::instanceInternal('search')->getPageNumber($queryParameters['p'], $node, $resultsPerPage, 1, 'ASC', 1, $type);
					$this->arguments['pagenum'] = $pagenumber;
					unset($queryParameters['p']);
				}
			}
			catch (vB_Exception_Api $e)
			{
				// if the page number was not found, then it doesn't exist and the url will redirect to the first page
				// of the thread... so we unset the 'p' query parameter
				if ($e->has_error('invalid_node_id'))
				{
					unset($queryParameters['p']);
				}
				else
				{
					// if it wasn't an invalid node, rethrow exception
					throw $e;
				}
			}


			// we may want to do some sort of validation on contentpagenum. We should be very careful to NOT create dupe content issues
			// ex, .../contentpagenum5 on an article with less than 5 pages would probably cause that URL to have content duplicate to contentpagenum=1.
			// So, we should never ever create a URL that would point to a nonexistent page. Without validation, we can't do anything about users or external
			// links that point to dupe content.
			// Need to check if it's set first, since we could have old conversation routes that do not have the updated regex.
			if (isset($this->arguments['contentpagenum']) AND $this->arguments['contentpagenum'] > 1)
			{
				$data['contentpagenum'] = $this->arguments['contentpagenum'];
			}

			if ($this->arguments['pagenum'] > 1)
			{
				$data['pagenum'] = $this->arguments['pagenum'];
			}
			$this->canonicalRoute = self::getRoute($node['routeid'], $data, $queryParameters);
		}

		return $this->canonicalRoute;
	}

	public function getCanonicalQueryParameters()
	{
		return $this->getCanonicalRoute()->getQueryParameters();
	}

	public static function exportArguments($arguments)
	{
		$data = unserialize($arguments);

		$channel = vB::getDbAssertor()->getRow('vBForum:channel', array('nodeid' => $data['channelid']));
		if (empty($channel))
		{
			throw new Exception('Couldn\'t find channel');
		}
		$data['channelGuid'] = $channel['guid'];
		unset($data['channelid']);


		$page = vB::getDbAssertor()->getRow('page', array('pageid' => $data['pageid']));
		if (empty($page))
		{
			throw new Exception('Couldn\'t find page');
		}
		$data['pageGuid'] = $page['guid'];
		unset($data['pageid']);

		return serialize($data);
	}

	public static function importArguments($arguments)
	{
		$data = unserialize($arguments);

		$channel = vB::getDbAssertor()->getRow('vBForum:channel', array('guid' => $data['channelGuid']));
		if (empty($channel))
		{
			throw new Exception('Couldn\'t find channel');
		}
		$data['channelid'] = $channel['nodeid'];
		unset($data['channelGuid']);

		$page = vB::getDbAssertor()->getRow('page', array('guid' => $data['pageGuid']));
		if (empty($page))
		{
			throw new Exception('Couldn\'t find page');
		}
		$data['pageid'] = $page['pageid'];
		unset($data['pageGuid']);

		return serialize($data);
	}

	public static function importContentId($arguments)
	{
		return $arguments['channelid'];
	}

	protected static function getHashKey($options = array(), $data = array(), $extra = array())
	{
		$routeId = $options[0];
		unset($options[0]);

		$hashKey = 'vbRouteURL_'. $routeId;
		if (!empty($data['nodeid']))
		{
			$hashKey .= '_' . $data['nodeid'];
		}
		$hash_add = (empty($options) ? '' : serialize($options)) . (empty($data) ? '' : serialize($data)) . (empty($extra) ? '' : serialize($extra));
		if (!empty($hash_add))
		{
			$hashKey .= '_' . md5($hash_add);
		}
		return $hashKey;
	}

	public function setHeadLinks()
	{
		$this->headlinks = array();

		if (vB::getDatastore()->getOption('externalrss'))
		{
			// adding headlinks
			$bbtitle = vB::getDatastore()->getOption('bbtitle');
			$routedata = vB_Api::instance('external')->buildExternalRoute(vB_Api_External::TYPE_RSS2);
			$this->headlinks[] = array('rel' => 'alternate', 'title' => $bbtitle, 'type' => 'application/rss+xml', 'href' => $routedata['route'], 'rsslink' => 1);

			// Conversations don't have their own rss files. Only channels do (refer to external API's validateOptions()).
			// This is the parent channel's rss link, not the conversation. See the constructor for where these arguments are added.
			// I'm guessing having the channel's rss link for every content page was part of the specs. If not, we
			// should get rid of this & the arguments-setting in the constructor.
			if ($this->arguments['rss_enabled'])
			{
				$this->headlinks[] = array('rel' => 'alternate', 'title' => $bbtitle . ' -- ' . $this->arguments['rss_title'], 'type' => 'application/rss+xml', 'href' => $this->arguments['rss_route'], 'rsslink' => 1);
			}
		}

		// not needed on page
		unset($this->arguments['rss_enabled']);
		unset($this->arguments['rss_title']);
		unset($this->arguments['rss_route']);
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84735 $
|| #######################################################################
\*=========================================================================*/
