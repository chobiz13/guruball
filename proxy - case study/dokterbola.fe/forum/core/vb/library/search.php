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
 * vB_Library_Search
 *
 * @package vBLibrary
 */

class vB_Library_Search extends vB_Library
{

	/**
	 * Re-indexes the whole database
	 * Returns true if the full indexing is implemented and successful for the selected search implementation
	 * Returns false if the full indexing is not implemented
	 * @var bool $silent whther to print the progress to the output
	 * @return boolean
	 */
	public function reIndexAll($silent = false)
	{
		return vB_Search_Core::instance()->reIndexAll($silent);
	}

	/**
	 *
	 * Index a node
	 * @param int $node_id to index
	 * @param boolean $propagate flag to propagate the indexing to the nodes parents
	 */
	public function index($node_id, $propagate = true)
	{
		vB_Search_Core::instance()->index($node_id, $propagate);
	}

	public function indexText($node, $title, $text, $skip_prev_index = false)
	{
		vB_Search_Core::instance()->indexText($node, $title, $text, $skip_prev_index);
	}

	public function attributeChanged($nodeid)
	{
		vB_Search_Core::instance()->attributeChanged($nodeid);
	}

	public function emptyIndex()
	{
		vB_Search_Core::instance()->emptyIndex();
	}

	/**
	 *
	 * indexes a range of nodes
	 * @param int $start - where to start indexing from - used for pagination
	 * @param int $perpage - nr of nodes to index
	 * @param array $filter - only index this type of nodes
	 * @return bool - returns true if there are more nodes to index
	 */
	public function indexRange($start = 0, $perpage = false, $filter = false)
	{
		$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD);
		if (!empty($start))
		{
			$params[vB_dB_Query::PARAM_LIMITSTART] = intval($start);
		}
		if (!empty($perpage))
		{
			$params[vB_dB_Query::PARAM_LIMIT] = intval($perpage);
		}
		if (!empty($filter) AND !empty($filter['type']))
		{
			$params['contenttypeid'] = vB_Types::instance()->getContentTypeID($filter['type']);
		}

		if (!empty($filter) AND !empty($filter['channel']))
		{
			$params['channel'] = $filter['channel'];
		}
		else
		{
			$params['excludecontenttypeids'] = array(
					vB_Types::instance()->getContentTypeID('vBForum_Channel'),
					vB_Types::instance()->getContentTypeID('vBForum_Report'),

				);
		}
		$query = vB::getDbAssertor()->assertQuery('vBForum:getNodes',$params);
		$nr = 0;
		foreach ($query as $node)
		{
			try
			{
				$this->index($node, 0);
			}
			catch (Exception $e)
			{
				// we are going to ignore the exception and keep indexing the rest
				unset($e);
			}
			$nr ++;
		}
		if (empty($perpage))
		{
			return false;
		}
		return $nr == $perpage;
	}

	public function delete($nodeid, $node = false)
	{
		vB_Search_Core::instance()->delete($nodeid, $node);
	}

	/**
	 * Purge search log cache for current logged-in user
	 */
	public function purgeCacheForCurrentUser($from = 301)
	{
		/*
			Even though the comment above says "logged-in user", this could be a guest, and guests might have
			legitimate cached values (see vB_Search_Core::saveSecondPassResults()).

			It seems a bit wrong to allow any guest to blow the cache for *all* guests, but I'm not going to add
			a guest check here at this point because of regression risk.
		 */
		$userid = vB::getUserContext()->fetchUserId();
		vB_Cache::instance(vB_Cache::CACHE_STD)->event(array('vB_SearchResults_chg_' . $userid));
		vB_Search_Core::instance()->purgeCacheForUser($userid, $from);
	}

	public function clean()
	{
		vB_Search_Core::instance()->clean();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85823 $
|| #######################################################################
\*=========================================================================*/
