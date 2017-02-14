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
 * vB_Api_Posthistory
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Posthistory extends vB_Api
{
	protected function __construct()
	{
		parent::__construct();
	}

	/* Can the post history for this node be viewed? It is required that the user can edit the post in order to view the history.
	 *
	 * @param	int		Nodeid
	 *
	 * @return	bool	Success
	 */
	public function canViewPostHistory($nodeid)
	{
		$postedithistory = vB::getDatastore()->getOption('postedithistory');
		$node = vB_Library::instance('node')->getNodeBare($nodeid);
		return (((bool)$postedithistory) AND vB_Library_Content::getContentLib($node['contenttypeid'])->getCanEdit($node));
	}

	/* Retrieves the posthistory for this node.
	 *
	 */
	public function fetchHistory($nodeid, $id1 = 0, $id2 = 0)
	{
		if (!$this->canViewPostHistory($nodeid))
		{
			throw new Exception('no_permission');
		}

		if ($id1 AND $id2)
		{
			$conditions = array(
				'nodeid' => $nodeid,
				'postedithistoryid' => array($id1, $id2)
			);
		}
		else
		{
			$conditions = array('nodeid' => $nodeid);
		}

		$_posthistory = vB::getDbAssertor()->assertQuery('vBForum:postedithistory', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('postedithistoryid', 'reason', 'dateline', 'username', 'original'),
				vB_dB_Query::CONDITIONS_KEY => $conditions),
			array(
				'field' => array('dateline'),
				'direction' => array(vB_dB_Query::SORT_DESC)
		));
		$posthistory = array();
		foreach ($_posthistory AS $info)
		{
			$info['reason'] = vB_String::fetchWordWrappedString($info['reason']);
			$posthistory[] = $info;
		}
		return $posthistory;
	}

	/*	Retrieves the comparison of two post history items
	 *
	 */
	public function fetchHistoryComparison($nodeid, $oldpost, $newpost)
	{
		if (!$this->canViewPostHistory($nodeid))
		{
			throw new Exception('no_permission');
		}

		$_posthistory = vB::getDbAssertor()->getRows('vBForum:postedithistory',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					vB_dB_Query::CONDITIONS_KEY => array('nodeid' => $nodeid, 'postedithistoryid' => array($oldpost, $newpost)),
					vB_dB_Query::COLUMNS_KEY => array('postedithistoryid', 'reason', 'dateline', 'username', 'original', 'pagetext')
				),
				array('field' => 'postedithistoryid', 'direction' => vB_dB_Query::SORT_ASC)
		);

		if (!$_posthistory)
		{
			throw new Exception('no_permission');
		}

		$posthistory = array();
		$key = 1;
		foreach ($_posthistory AS $info)
		{
			$posthistory['post' . $key] = $info;
			$key++;
		}

		require_once(DIR . '/includes/class_diff.php');

		if ($posthistory['post2'])
		{
			$textdiff_obj = new vB_Text_Diff($posthistory['post1']['pagetext'], $posthistory['post2']['pagetext']);
		}
		else
		{
			$textdiff_obj = new vB_Text_Diff($posthistory['post1']['pagetext'], $posthistory['post1']['pagetext']);
		}

		$results = array();
		$diff = $textdiff_obj->fetch_diff();
		foreach ($diff AS $diffrow)
		{
			$compare_show = array();

			if ($diffrow->old_class == 'unchanged' AND $diffrow->new_class == 'unchanged')
			{ // no change
				$results[] = array(
					'unchanged_olddata' => vB_String::fetchWordWrappedString(nl2br(vB_String::htmlSpecialCharsUni(implode("\n", $diffrow->fetch_data_old()))))
				);
			}
			else
			{ // something has changed
				$results[] = array(
					'changed_olddata' => vB_String::fetchWordWrappedString(nl2br(vB_String::htmlSpecialCharsUni(implode("\n", $diffrow->fetch_data_old())))),
					'changed_newdata' => vB_String::fetchWordWrappedString(nl2br(vB_String::htmlSpecialCharsUni(implode("\n", $diffrow->fetch_data_new()))))
				);
			}
		}

		return $results;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
