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
 * vB_Api_Vb4_search
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_search extends vB_Api
{
	public function process(
		$query = null,
		$type = null,
		$sortby = null,
		$searchdate = null,
		$beforeafter = null,
		$order = null,
		$searchuser = null,
		$contenttypeid = null,
		$tag = null,
		$searchthreadid = null,
		$showposts = null)
	{
		$cleaner = vB::getCleaner();
		$query = $cleaner->clean($query, vB_Cleaner::TYPE_STR);
		$sortby = $cleaner->clean($sortby, vB_Cleaner::TYPE_STR);
		$searchdate = $cleaner->clean($searchdate, vB_Cleaner::TYPE_UINT);
		$beforeafter = $cleaner->clean($beforeafter, vB_Cleaner::TYPE_STR);
		$order = $cleaner->clean($order, vB_Cleaner::TYPE_STR);
		$tag = $cleaner->clean($tag, vB_Cleaner::TYPE_STR);
		$type = $cleaner->clean($type, vB_Cleaner::TYPE_ARRAY);
		$showposts = $cleaner->clean($showposts, vB_Cleaner::TYPE_UINT);
		$searchthreadid = $cleaner->clean($searchthreadid, vB_Cleaner::TYPE_UINT);
		$contenttypeid = $cleaner->clean($contenttypeid, vB_Cleaner::TYPE_UINT);
		$searchuser = $cleaner->clean($searchuser, vB_Cleaner::TYPE_STR);

		$searchJSON = array(
			'type' => 'vBForum_Text',
		);

		$sort = 'relevance';
		$ord = 'desc';

		if (!empty($order))
		{
			if ($order === 'ascending')
			{
				$ord = 'asc';
			}
			else if ($order === 'descending')
			{
				$ord = 'desc';
			}
		}

		if (!empty($sortby))
		{
			if ($sortby === 'title')
			{
				$sort = 'title';
			}
			else if ($sortby === 'user')
			{
				$sort = 'username';
			}
			else if ($sortby === 'dateline')
			{
				$sort = 'created';
			}
		}

		$searchJSON['sort'] = array(
			$sort => $ord,
		);

		if (!empty($beforeafter))
		{
			if ($beforeafter === 'before')
			{
				$fromto = 'to';
			}
			else if ($beforeafter === 'after')
			{
				$fromto = 'from';
			}
		}
		else
		{
			$fromto = 'from';
		}

		if (!empty($searchdate))
		{
			$searchJSON['date'] = array(
				$fromto => $searchdate,
			);
			if ($searchdate == 1)
			{
				$searchJSON['date'] = array(
					$fromto => 'lastDay',
				);
			}
		}

		if (!empty($tag))
		{
			$searchJSON['tag'] = $tag;
		}

		if (!empty($searchthreadid))
		{
			$searchJSON['channel'] = $searchthreadid;
		}

		if (!empty($query))
		{
			$searchJSON['keywords'] = $query;
		}

		if (!empty($searchuser))
		{
			$searchJSON['author'] = $searchuser;
		}

		if (!empty($type))
		{
			$searchJSON['custom']['type'] = $type;
		}

		if (!empty($contenttypeid))
		{
			$searchJSON['custom']['type'][] = $contenttypeid;
		}

		if (empty($searchJSON['custom']['type']))
		{
			$types = vB_Types::instance();

			$default = array();
			$default[] = $types->getContentTypeId('vBForum_Post');
			$default[] = $types->getContentTypeId('vBBlog_BlogEntry');
			$default[] = $types->getContentTypeId('vBBlog_BlogComment');

			$searchJSON['custom']['type'] = $default;
		}

		if ($showposts == 0)
		{
			$searchJSON['custom']['showposts'] = 0;
		}
		else if($showposts == 1)
		{
			$searchJSON['view'] = vB_Api_Search::FILTER_VIEW_CONVERSATION_THREAD;
			$searchJSON['include_starter'] = 1;
			$searchJSON['depth'] = 1;
			$searchJSON['custom']['showposts'] = 1;
		}

		$result = vB_Api::instance('search')->getSearchResult($searchJSON);

		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		return array(
			'response' => array('errormessage' => array('search')),
			'show' => array('searchid' => $result['resultId']),
		);
	}

	public function showresults($searchid, $pagenumber = 1, $perpage = 10)
	{
		$cleaner = vB::getCleaner();
		$searchid = $cleaner->clean($searchid, vB_Cleaner::TYPE_UINT);
		$pagenumber = $cleaner->clean($pagenumber, vB_Cleaner::TYPE_UINT);
		$perpage = $cleaner->clean($perpage, vB_Cleaner::TYPE_UINT);

		$result = vB_Api::instance('search')->getMoreNodes($searchid, 1000, 1);
		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		if ($result['totalRecords'] < 1)
		{
			return array('response' => array('errormessage' => array('searchnoresults')));
		}

		$searchbits = $this->parseSearchbits($result);
		$pagenav = vB_Library::instance('vb4_functions')->pageNav($pagenumber, $perpage, count($searchbits));

		$searchbits = array_slice($searchbits, ($pagenumber - 1) * $perpage, $perpage);

		$out = array(
			'response' => array(
				'pagenav' => $pagenav,
				'searchbits' => $searchbits,
			),
		);
		return $out;
	}

	private function parseSearchbits($search)
	{
		$top = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();

		$types = vB_Types::instance();
		$forumtypeid = $types->getContentTypeId('vBForum_Post');
		$blogtypeid = $types->getContentTypeId('vBBlog_BlogEntry');
		$blogcommenttypeid = $types->getContentTypeId('vBBlog_BlogComment');
		$articletypeid = $types->getContentTypeId('vBCms_Article');

		$selectedTypes = $search['searchJSONStructure']['custom']['type'];
		$forumitemsSelected = in_array($forumtypeid, $selectedTypes);
		$blogentriesSelected = in_array($blogtypeid, $selectedTypes);
		$blogcommentsSelected = in_array($blogcommenttypeid, $selectedTypes);
		$articlesSelected = in_array($articletypeid, $selectedTypes);

		$searchbits = array();
		$processed_nodes = array();

		$functions = vB_Library::instance('vb4_functions');

		foreach ($search['nodeIds'] as $nodeid => $nothing)
		{
			$result = vB_Api::instance('node')->getFullContentforNodes(array($nodeid));
			if ($result === null || isset($result['errors']))
			{
				continue;
			}
			$node = $result[0];


			$isstarter = ($node['nodeid'] == $node['starter']);

			if ($forumitemsSelected AND $this->isForumText($node, $top))
			{
				if ($search['searchJSONStructure']['custom']['showposts'])
				{
					list($searchbits[], ) = $functions->parsePost($node);
				}
				else
				{
					if ($isstarter)
					{
						$searchbits[] = $functions->parseThread($node);
					}
				}
			}
			else if (($blogentriesSelected OR $blogcommentsSelected) AND $this->isBlogText($node))
			{
				if(($isstarter AND $blogentriesSelected) OR (!$isstarter AND $blogcommentsSelected))
				{
					if (!isset($processed_nodes[$node['starter']]))
					{
						if (!$isstarter)
						{
							$result = vB_Api::instance('node')->getFullContentforNodes(array($node['starter']));
							if ($result === null || isset($result['errors']))
							{
								continue;
							}
							$node = $result[0];
						}
						$processed_nodes[$node['nodeid']] = true;
						$searchbits[] = $functions->parseBlogEntrySearch($node);
					}
				}

				// Note we send back Blog entry when matched a comment
			}
			else if ($articlesSelected AND $isstarter AND $this->isArticleText($node, $top))
			{
				$result = vB_Api::instance('node')->getFullContentforNodes(array($node['parentid']));
				if ($result === null || isset($result['errors']))
				{
					continue;
				}
				$searchbits[] = $functions->parseArticleSearch($node, $result[0]);
			}
		}

		return $searchbits;
	}

	private function isBlogText($node)
	{
		return vB_Api::instance('blog')->isBlogNode($node['nodeid'], $node);
	}

	private function isForumText($node, $top)
	{
		$forumRoot = $top['forum'];
		$node = vB_Api::instance('node')->getNode($node['nodeid'], true, false);

		return in_array($forumRoot, $node['parents']);
	}

	private function isArticleText($node, $top)
	{
		$articleRoot = $top['articles'];
		return in_array($articleRoot, $node['parents']);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85475 $
|| #######################################################################
\*=========================================================================*/
