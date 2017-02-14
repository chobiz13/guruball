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

abstract class vB_Search_Core
{
	const OP_EQ = 'eq';
	const OP_NEQ = 'neq';
	const OP_LT = 'lt';
	const OP_GT = 'gt';
	const TYPE_COMMON = 'common';

	public static function instance()
	{
		$searchimplementation = vB::getDatastore()->getOption('searchimplementation');
		if (empty($searchimplementation) OR !class_exists($searchimplementation))
		{
			// falling back to vBDBSearch in case the searchimplementation does not exist - needed in certain cases during upgrade
			$searchimplementation = 'vBDBSearch_Core';
		}
		if (!class_exists($searchimplementation))
		{
			throw new vB_Exception_Api('invalid_search_implementation');
		}
		return new $searchimplementation();
	}

	abstract public function getResults(vB_Search_Criteria $criteria);

	abstract public function indexText($node, $title, $text, $skip_prev_index = false);

	abstract public function delete($nodeid, $node = false);

	public function getTwoPassResults(vB_Search_Criteria $criteria)
	{
		$cacheKey = $this->getTwoPassCacheKey($criteria);

		if ($cacheKey)
		{
			$cached = vB_Cache::instance(vB_Cache::CACHE_STD)->read($cacheKey);
			//We need to query the database.

			if ($cached !== false)
			{
				return $cached;
			}
			else
			{
				return $cacheKey;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Index a node
	 * @param int $node_id to index
	 * @param boolean $propagate flag to propagate the indexing to the nodes parents
	 */

	public function index($node_id, $propagate = true)
	{
		$node = $this->getNodeToIndex($node_id);

		if (empty($node))
		{
			return false;
		}
		$node_id = $node['nodeid'];
		list($title, $text) = $this->getTitleAndText($node, $propagate);
		//getting the content type for the node
		$contenttype = vB_Api::instanceInternal('ContentType')->fetchContentTypeClassFromId($node['contenttypeid']);

		//this should really be part of the Attach indexable content but doing it that way means having to look up the
		//parent for each Attachment we index, which gets expensive when doing the propogation where we already have
		//the parent information and may end up fetching it for each and every attachment.  So let's handle this
		//as a special case
		if (($contenttype == 'Attach' OR $contenttype == 'Photo') AND $propagate !== 0)
		{
			$parentid = $this->getParentNodeId($node['nodeid']);
			$parentnode = $this->getNodeToIndex($parentid);
			if (!empty($parentnode))
			{
				list($parentTitle, $parentText) = $this->getTitleAndText($parentnode);
				$title .= ' ' . $parentTitle;
				$text .= ' ' . $parentText;
			}

		}

		//need to add the captions from the attachments
		if ($contenttype != 'Attach' AND $contenttype != 'Photo')
		{
			$attachments = vB_Api::instanceInternal('Node')->getNodeAttachments($node_id);
			foreach ($attachments as $attach)
			{
				// attachment indexable content gets added to the text, not the title (it's less relevant)
				$text .= ' ' . $attach['caption'];
			}
		}

		$result = $this->indexText($node, $title, $text);
		// no need to go any furthere if the content didn't change
		if (!$result)
		{
			return false;
		}

		// need to index the parent when the attachment changes
		if(!empty($propagate) AND ($contenttype == 'Attach' OR $contenttype == 'Photo'))
		{
			$parentid = $this->getParentNodeId($node['nodeid']);
			$this->index($parentid, false);
		}


		//need to index the attachments when the parents change
		if (!empty($propagate) AND $contenttype != 'Attach' AND $contenttype != 'Photo')
		{
			if (!isset($attachments))
			{
				$attachments = vB_Api::instanceInternal('Node')->getNodeAttachments($node_id);
			}
			foreach ($attachments as $attach)
			{
				$this->indexChildAttachment($attach['nodeid'], $title, $text);
			}
		}
	}
	abstract public function reIndexAll();

	/**
	 * this function should be called whenever an attribute changes in the node table
	 *
	 * @param int|array $nodeid
	 */
	public function attributeChanged($nodeid)
	{
		return false;
	}

	public function emptyIndex()
	{
		$assertor = vB::getDbAssertor();
		$assertor->update(
				'vBForum:node',
				array('CRC32' => ''),
				vB_dB_Query::CONDITION_ALL
		);
	}

	protected function indexChildAttachment($node_id, $parentTitle, $parentText)
	{
		$node = $this->getNodeToIndex($node_id);
		if (empty($node))
		{
			return false;
		}

		list($title, $text) = $this->getTitleAndText($node);
		$title .= ' ' . $parentTitle;
		$text .= ' ' . $parentText;
		$result = $this->indexText($node, $title, $text);
		if (!$result)
		{
			return false;
		}

		return true;
	}

	protected function getNodeToIndex($node)
	{
		try
		{
			if (is_numeric($node))
			{
				return vB_Library::instance('node')->getNodeBare($node);
			}
			elseif (is_array($node) && !empty($node['nodeid']) && !empty($node['contenttypeid']))
			{
				return $node;
			}
		}
		catch (Exception $e)
		{}

		return null;
	}

	protected function getParentNodeId($nodeid)
	{
		//@todo -- We need to look at making this assertor call a node method.
		// propagate the indexing to the parents
		$queryinfo = vB::getDbAssertor()->assertQuery('vBForum:closure', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'child' => $nodeid, 'depth' => 1));
		if ($queryinfo AND $queryinfo->valid() AND $closure = $queryinfo->current())
		{
			return $closure['parent'];
		}

		return false;
	}

	protected function getTitleAndText($node, $propagate = true)
	{
		try
		{
			$indexableContent = vB_Library_Content::getContentLib($node['contenttypeid'])->getIndexableContent($node, $propagate);
		}
		catch (Exception $e)
		{
			//whatever the reason, just ignore and move on
			$indexableContent = array();
		}

		$title = "";
		if (!empty($indexableContent['title']))
		{
			$title = strtolower($indexableContent['title']);
			unset($indexableContent['title']);
		}

		//need to put a space between segments or we could concatenate words and distort our index
		$text = implode(' ', $indexableContent);
		$text = strtolower($text);

		return array($title, $text);
	}

	public function purgeCacheForUser($userid, $from = 301)
	{
		$userinfo = vB_User::fetchUserinfo($userid);

		if (!empty($userinfo['userid']))
		{
			vB::getDbAssertor()->update('vBDbSearch:searchlog',
					array('dateline' => vB::getRequest()->getTimeNow() - $from), array('userid' => $userinfo['userid']));
		}
	}

	public function clean()
	{
		vB::getDbAssertor()->delete('vBDbSearch:searchlog', array(
				array('field' => 'dateline', 'value' => vB::getRequest()->getTimeNow() - 86400, vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_LT)
		));
	}

	/**
	 * Caches the results
	 * @param array 				$nodeIds		Nodeids resulting from a successful search
	 * @param vB_Search_Criteria 	$criteria		Search criteria object
	 * @param int					$searchtime		Elapsed search time in seconds
	 *
	 * @return mixed		array of strings and integers- see details below.
	 */
	public function cacheResults($nodeIds, $criteria, $searchtime = 0, $searchType = 0)
	{
		$json = $criteria->getJSON();
		$fields['type'] = intval($searchType);
		$fields['userid'] = vB::getCurrentSession()->get('userid');
		$fields['ipaddress'] =  vB::getRequest()->getIpAddress();
		$fields['searchhash'] = $this->generateHash($json) ;
		$fields['sortby'] = $criteria->get_sort_field();
		$fields['sortorder'] = $criteria->get_sort_direction();
		$fields['searchtime'] = $searchtime;
		$fields['dateline'] = vB::getRequest()->getTimeNow();
		$fields['completed'] = 0;
		$fields['json'] = json_encode($json);
		$fields['results'] = implode(',', $nodeIds);
		$fields['results_count'] = count($nodeIds);
		$fields['resultId'] = vB::getDbAssertor()->assertQuery('vBDBSearch:cacheResults',array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,'fields'=>$fields));
		return $fields;
	}

	public function getFromCache(vB_Search_Criteria $criteria, $search_json)
	{
		$cacheTTL = vB_Api_Search::getCacheTTL();

		if ($cacheTTL == 0)
		{
			return false;
		}
		$hashkey = $this->generateHash($criteria->getJSON());
		//now see if we have a cached value.
		$hashes = array($hashkey);
		if (!empty($search_json['custom']))
		{
			$hashes[] = $customhashkey = $this->generateHash($search_json);
		}
		$conditions =
		array(
				array(
						'field' => 'searchhash',
						'value' => $hashes,
						'operator' => vB_dB_Query::OPERATOR_EQ
				),
				array(
						'field' => 'userid',
						'value' => vB::getCurrentSession()->get('userid'),
						'operator' => vB_dB_Query::OPERATOR_EQ
				),
				array(
						'field' => 'dateline', // cache life is in minutes.
						'value' => vB::getRequest()->getTimeNow() - (vB_Api_Search::getCacheTTL() * 60),
						'operator' => vB_dB_Query::OPERATOR_GTE
				)

		);
		$sort = $criteria->get_sort();
		if (!empty($sort))
		{
			$conditions[] = array(
					'field' => 'sortby',
					'value' => key($sort),
					'operator' => vB_dB_Query::OPERATOR_EQ
			);
			$conditions[] = array(
					'field' => 'sortorder',
					'value' => current($sort),
					'operator' => vB_dB_Query::OPERATOR_EQ
			);
		}
		$searchlogs = vB::getDbAssertor()->getRows('vBDbSearch:searchlog',
				array(vB_dB_Query::CONDITIONS_KEY => $conditions),
				array(
						'field' => array('dateline'),
						'direction' => vB_dB_Query::SORT_DESC,
				),
				'searchhash'
		);

		if (!empty($searchlogs[$hashkey]))
		{
			$searchlog = $searchlogs[$hashkey];

			//Check the age cacheLife is minutes but the other values are seconds
			if ($searchlog['dateline'] - (vB::getRequest()->getTimeNow() - ($cacheTTL * 60)))
			{
				return false;
			}
			$searchlog['resultId'] = $searchlog['searchlogid'];
			$searchlog['totalRecords'] = $searchlog['results_count'];
			$searchlog['from_cache'] = true;
			$ignored_words = $criteria->get_ignored_keywords();
			if (!empty($ignored_words))
			{
				$searchlog['ignored_keywords'] = $ignored_words;
			}
			// if there is a custom field we need to duplicate the search cache and save the custom field with it
			if (!empty($search_json['custom']))
			{
				if (empty($searchlogs[$customhashkey]))
				{
					$customCriteria = $criteria;
					$customCriteria->setJSON($search_json);
					$customsearchlog = $this->cacheResults(explode(',',$searchlog['results']), $customCriteria, $searchlog['searchtime'], $searchlog['type']);
					$customsearchlog['totalRecords'] = $customsearchlog['results_count'];
					$customsearchlog['from_cache'] = true;
					$ignored_words = $customCriteria->get_ignored_keywords();
					if (!empty($ignored_words))
					{
						$customsearchlog['ignored_keywords'] = $ignored_words;
					}
					return $customsearchlog;
				}
				else
				{
					$customsearchlog = $searchlogs[$customhashkey];
					$customsearchlog['resultId'] = $customsearchlog['searchlogid'];
					$customsearchlog['totalRecords'] = $customsearchlog['results_count'];
					$customsearchlog['from_cache'] = true;
					$ignored_words = $criteria->get_ignored_keywords();
					if (!empty($ignored_words))
					{
						$customsearchlog['ignored_keywords'] = $ignored_words;
					}
					return $customsearchlog;
				}
			}
			return $searchlog;
		}
		return false;
	}

	public function removeNodeFromResult($nodeid, $result, $perpage, $pagenumber, $getStarterInfo)
	{
		$nodeids = explode(',', $result['results']);
		if (($index = array_search($nodeid, $nodeids)) !== false)
		{
			unset($nodeids[$index]);
			$result['results'] = implode(',', $nodeids);
			vB::getDbAssertor()->update('vBDBSearch:searchlog', array('results' => $result['results']), array('searchlogid' => $result['searchlogid']));
			return $this->getMoreResults($result, $perpage, $pagenumber, $getStarterInfo);
		}
		else
		{
			return false;
		}
	}

	public function getCache($resultId)
	{
		return vB::getDbAssertor()->getRow('vBDbSearch:searchlog',array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'searchlogid' => $resultId));
	}

	public function floodCheck()
	{
		$searchFloodTime = vB::getDatastore()->getOption('searchfloodtime');

		//if we don't have a search limit then skip check
		if ($searchFloodTime == 0)
		{
			return true;
		}

		$userContext = vB::getUserContext();

		//if the user is an admin or a moderater, skip the check
		if ($userContext->hasPermission('adminpermissions', 'cancontrolpanel') /* OR $user->isModerator()*/)
		{
			return true;
		}

		$request = vB::getRequest();
		$userId = vB::getCurrentSession()->get('userid');

		// get last search for this user and check floodcheck
		$filter = array(
				'type' => vB_Api_Search::SEARCH_TYPE_USER
		);
		if ($userId == 0)
		{
			// it's a guest
			$filter['ipaddress'] = $request->getIpAddress();
		}
		else
		{
			$filter['userid'] = $userId;
		}

		$prevsearch = vB::getDbAssertor()->getRow('vBDbSearch:searchlog', $filter, array('field' => 'dateline', 'direction' => vB_dB_Query::SORT_DESC));
		if ($prevsearch)
		{
			$timepassed = $request->getTimeNow() - $prevsearch['dateline'];
			if ($timepassed < $searchFloodTime)
			{
				throw new vB_Exception_Api('searchfloodcheck', array($searchFloodTime, ($searchFloodTime - $timepassed)));
			}
		}

		return true;
	}

	protected function generateHash($json)
	{
		if (isset($json['ignored_words']))
		{
			unset($json['ignored_words']);
		}
		if (isset($json['original_keywords']))
		{
			unset($json['original_keywords']);
		}
		return md5(serialize($json));
	}

	public static function saveSecondPassResults($results, $cacheKey, $parentid = false)
	{
		$res = array();
		foreach ($results AS $item)
		{
			if (!empty($item['parentid']))
			{
				$res[$item['nodeid']] = array(
					'nodeid'    => $item['nodeid'],
					'parentid'  => $item['parentid'],
					'userid'    => $item['userid']
				);
			}
			else
			{
				// guest
				$res[$item['nodeid']] = array('nodeid' => $item['nodeid']);
			}
		}

		if ($life = vB::getDatastore()->getOption('search_cache_ttl'))
		{
			$events = array(
				'perms_changed',
				'vB_ChannelStructure_chg',
				'vB_SearchResults_chg_' . vB::getUserContext()->fetchUserId()
			);

			if ($parentid AND is_numeric($parentid) AND (intval($parentid) > 1))
			{
				$events[] = 'nodeChg_' . $parentid;
			}

			vB_Cache::instance(vB_Cache::CACHE_STD)->write(
				$cacheKey,
				$res,
				$life,
				$events
			);
		}

		return $res;
	}

	/**
	 * Generate a cache key for the first pass of getSearchResults. Build key based on:
	 * usergroupids, infractiongroupids, search json, search sort order, search sort by,
	 * moderated channels
	 *
	 * @param vB_Search_Criteria
	 *
	 * @staticvar array	$hashResult	Array to build hash from
	 * @return string
	 */
	public static function getTwoPassCacheKey(vB_Search_Criteria $criteria)
	{
		$cacheTTL = vB_Api_Search::getCacheTTL();

		if ($cacheTTL < 1)
		{
			return false;
		}
		$channelAccess = vB::getUserContext()->getAllChannelAccess();
		$currentUserId = vB::getCurrentSession()->get('userid');

		// Don't use cache if we have a GIT record
		if (!empty($channelAccess['member']))
		{
			return false;
		}

		// Not 100% set on this but it seems adding further nodes to the result set
		// could introduce nodes that were grabbed based on nodes that might
		// end up removed on the second pass
		if ($criteria->get_post_processors())
		{
			return false;
		}

		$json = $criteria->getJSON();
		//$json['disable_two_pass'] = true;

		if (
			// Two pass caching has been explicitly disabled for this search
			!empty($json['disable_two_pass'])
				OR
			(!empty($json['my_following']) AND !empty($currentUserId))
				OR
			!empty($json[vB_Api_Search::FILTER_FOLLOW])
				OR
			!empty($json['private_messages_only'])
				OR
			!empty($json['include_private_messages'])
				OR
			(!empty($json['date']) AND $json['date'] == vB_Api_Search::FILTER_LASTVISIT)
				OR
			!empty($json['unread_only'])
				OR
			(
				!empty($json['author'])
					AND
				!empty($json['exactname'])
					AND
				(
					$json['author'] == 'myFriends'
						OR
					$json['author'] == 'iFollow'
				)
			)
		)
		{
			return false;
		}

		// Don't cache for globally ignored users
		if (!empty($currentUserId))
		{
			$globalignore = trim(vB::getDatastore()->getOption('globalignore'));
			if (!empty($globalignore))
			{
				$blocked = preg_split('#\s+#s', $globalignore, -1, PREG_SPLIT_NO_EMPTY);
				$bbuserkey = array_search($currentUserId , $blocked);
				if ($bbuserkey !== FALSE AND $bbuserkey !== NULL)
				{
					return false;
				}
			}
		}

		if (isset($json['ignored_words']))
		{
			unset($json['ignored_words']);
		}
		if (isset($json['original_keywords']))
		{
			unset($json['original_keywords']);
		}

		// Make sure ugids and ifids are in a consistent order
		$ugids = $ifids = $mod = '';
		$userinfo = vB_User::fetchUserinfo();
		if (!empty($userinfo['membergroupids']) AND trim($userinfo['membergroupids']) != '' AND $ugids = explode(',', str_replace(' ', '', $userinfo['membergroupids'])))
		{
			$ugids[] = $userinfo['usergroupid'];
			sort($ugids, SORT_NUMERIC);
			$ugids = array_unique($ugids, SORT_NUMERIC);
			$ugids = implode(',', $ugids);
		}
		else
		{
			$ugids = $userinfo['usergroupid'];
		}

		$ifid = !empty($userinfo['infractiongroupid']) ? intval($userinfo['infractiongroupid']) : 0;
		if (!empty($userinfo['infractiongroupids']) AND trim($userinfo['infractiongroupids']) != '' AND $ifids = explode(',', str_replace(' ', '', $userinfo['infractiongroupids'])))
		{
			if ($ifid)
			{
				$ifids[] = $ifid;
			}
			sort($ifids, SORT_NUMERIC);
			$ifids = array_unique($ifids, SORT_NUMERIC);
			$ifids = implode(',', $ifids);
		}
		else if ($ifid)
		{
			$ifids = $ifid;
		}

		if (!empty($channelAccess['canmoderate']))
		{
			$mod = $channelAccess['canmoderate'];
			sort($mod, SORT_NUMERIC);
			$mod = array_unique($mod, SORT_NUMERIC);
			$mod = implode(',', $mod);
		}

		$hashResult = array(
			'json' => $json,
			'so'   => $criteria->get_sort_field(),
			'sb'   => $criteria->get_sort_direction(),
			'ul'   => vB::getUserContext()->getUserLevel(),
			'ug'   => $ugids
		);

		if (!empty($ifids))
		{
			$hashResult['if'] = $ifids;
		}

		if (!empty($mod))
		{
			$hashResult['mod'] = $mod;
		}

		return 'getSearchResults_' . md5(serialize($hashResult));
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85017 $
|| #######################################################################
\*=========================================================================*/
