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
 * vB_Api_Content_Report
 *
 * @package vBApi
 * @author xiaoyu
 * @copyright Copyright (c) 2011
 * @version $Id: report.php 89714 2016-07-27 19:53:24Z ksours $
 * @access public
 */
class vB_Library_Content_Report extends vB_Library_Content_Text
{
	//override in client- the text name
	protected $contenttype = 'vBForum_Report';

	//The table for the type-specific data.
	protected $tablename = array('report', 'text');

	protected $ReportChannel;

	/**
	 * If true, then creating a node of this content type will increment
	 * the user's post count. If false, it will not. Generally, this should be
	 * true for topic starters and replies, and false for everything else.
	 *
	 * @var	bool
	 */
	protected $includeInUserPostCount = false;

	// Do not send a moderator notification when this contenttype is created
	protected $skipModNotification = true;

	protected function __construct()
	{
		parent::__construct();
		$this->ReportChannel = $this->nodeApi->fetchReportChannel();
	}

	/**
	 * 	Adds a new node.
	 *
	 *	@param	mixed		Array of field => value pairs which define the record.
	 *	@param	array		Array of options for the content being created.
	 *						Understands skipTransaction, skipFloodCheck, floodchecktime, skipDupCheck, skipNotification, nl2br, autoparselinks.
	 *							- nl2br: if TRUE, all \n will be converted to <br /> so that it's not removed by the html parser (e.g. comments).
	 *	@param	bool		Convert text to bbcode
	 *
	 * 	@return	mixed		array with nodeid (int), success (bool), cacheEvents (array of strings), nodeVals (array of field => value), attachments (array of attachment records).
	 */
	public function add($data, array $options = array(), $convertWysiwygTextToBbcode = true)
	{
		//Store this so we know whether we should call afterAdd()
		$skipTransaction = !empty($options['skipTransaction']);
		$vboptions = vB::getDatastore()->getValue('options');
		$reportemail = ($vboptions['enableemail'] AND $vboptions['rpemail']);

		$data['reportnodeid'] = intval($data['reportnodeid']);
		// Build node title based on reportnodeid
		if (!$data['reportnodeid'])
		{
			throw new vB_Exception_Api('invalid_report_node');
		}

		$data['parentid'] = $this->ReportChannel;

		if (empty($data['title']))
		{
			$reportnode = $this->nodeApi->getNodeFullContent($data['reportnodeid']);
			$reportnode = $reportnode[$data['reportnodeid']];

			$phraseapi = vB_Api::instanceInternal('phrase');

			if ($reportnode['nodeid'] == $reportnode['starter'])
			{
				// Thread starter
				$data['title'] = $reportnode['title'];
			}
			elseif ($reportnode['parentid'] == $reportnode['starter'])
			{
				$phrases = $phraseapi->fetch(array('reply_to'));
				$data['title'] = $phrases['reply_to'] . ' ' . $reportnode['startertitle'];
			}
			else
			{
				$phrases = $phraseapi->fetch(array('comment_in_a_topic'));
				$data['title'] = $phrases['comment_in_a_topic'] . ' ' . $reportnode['startertitle'];
			}
		}

		$result = parent::add($data, $options, $convertWysiwygTextToBbcode);

		//we don't even set skip transaction or create a transaction so the parent class will call beforeCommit
		//we don't need to call it here.  However I think this means that afterAdd gets called twice, which is
		//not good.
		//$this->beforeCommit($result['nodeid'], $data, $options, $result['cacheEvents'], $result['nodeVals']);

		if (!$skipTransaction)
		{
			//The child classes that have their own transactions all set this to true so afterAdd is always called just once.
			$this->afterAdd($result['nodeid'], $data, $options, $result['cacheEvents'], $result['nodeVals']);
		}

		// send an email
		if ($reportemail)
		{
			$reporterInfo = vB::getCurrentSession()->fetch_userinfo();
			$nodeLib = vB_Library::instance('node');

			$moderators = array();
			$moderatorUsernames = '';

			// Get moderators on the reported node
			$moderatorsArray = $nodeLib->getNodeModerators($reportnode['nodeid']);
			foreach ($moderatorsArray as $moderator)
			{
				$moderators[$moderator['userid']] = $moderator['userid'];
			}

			if ($vboptions['rpemail'] == 2)
			{
				// Fetch admins and super moderators
				$allmoderators = $nodeLib->getForumSupermoderatorsAdmins($moderators);
				foreach ($allmoderators as $moderator)
				{
					$moderators[$moderator['userid']] = $moderator['userid'];
				}
			}

			// get user info
			foreach ($moderators as $moderatorid => $moderator)
			{
				$moderators[$moderatorid] =  vB_Library::instance('user')->fetchUserinfo($moderatorid);
				$moderatorUsernames .= $moderators[$moderatorid]['username'] . ', ';
			}

			// Compose the email
			if ($reportnode['starter'] == $reportnode['nodeid'])
			{
				$maildata = vB_Api::instanceInternal('phrase')->
					fetchEmailPhrases('reportpost_newthread', array(
						vB5_Route::buildUrl('profile|fullurl', array('userid' => $reporterInfo['userid'])), // reported profile link
						$reporterInfo['username'], // reported username
						$data['rawtext'], // reason
						vB5_Route::buildUrl($reportnode['routeid'] . '|fullurl', array('nodeid' => $reportnode['nodeid'], 'title' => $reportnode['title'])),
						$reportnode['title'],
						vB::getDatastore()->getOption('bbtitle'), // forum title
						substr($moderatorUsernames, 0, -2),// moderator list???
						$reportnode['authorname'], // poster username
						vB5_Route::buildUrl('profile|fullurl', array('userid' => $reportnode['userid'])), // poster profile link
						vB_String::getPreviewText($reportnode['rawtext'])
						),
						array($reporterInfo['username'])
				);
			}
			else
			{
				$maildata = vB_Api::instanceInternal('phrase')->
					fetchEmailPhrases('reportpost', array(
						$reporterInfo['username'],
						$reporterInfo['email'],
						$reportnode['title'],
						vB5_Route::buildUrl($reportnode['routeid'] . '|fullurl',
							array(
								'nodeid' => $reportnode['starter'],
								'userid' => $reportnode['starteruserid'],
								'username' => $reportnode['starterauthorname'],
								'innerPost' => $reportnode['nodeid'],
								'innerPostParent' => $reportnode['parentid'])
						),
						$reportnode['startertitle'],
						vB5_Route::buildUrl($reportnode['routeid'] . '|fullurl', array('nodeid' => $reportnode['starter'], 'title' => $reportnode['startertitle'])),
						$data['rawtext'],
						),
						array(vB::getDatastore()->getOption('bbtitle'))
				);
			}
			// Send out the emails
			foreach ($moderators as $moderator)
			{
				if (!empty($moderator['email']))
				{
					vB_Mail::vbmail($moderator['email'], $maildata['subject'], $maildata['message'], false);
				}
			}
		}

		return $result;;
	}

	public function getFullContent($nodeid, $permissions = false)
	{
		if (empty($nodeid))
		{
			return array();
		}

		$results = parent::getFullContent($nodeid, $permissions);
		$reportparentnode = array();

		foreach ($results as $key => $result)
		{
			try
			{
				$reportnode = $this->nodeApi->getNodeFullContent($results[$key]['reportnodeid']);
			}
			catch (vB_Exception_NodePermission $e)
			{
				$results[$key]['node_no_permission'] = true;
				continue;
			}
			catch (vB_Exception_Api $e)
			{
				// The node probably does not exist.
				$results[$key]['reportnodeid'] = NULL;
				$results[$key]['reportnodetype'] = NULL;
				$results[$key]['reportparentnode'] = NULL;
				$results[$key]['reportnodetitle'] = NULL;
				$results[$key]['reportnoderouteid'] = NULL;
				continue;
			}
			if ($reportnode[$results[$key]['reportnodeid']]['nodeid'] == $reportnode[$results[$key]['reportnodeid']]['starter'])
			{
				$results[$key]['reportnodetype'] = 'starter';
			}
			elseif ($reportnode[$results[$key]['reportnodeid']]['parentid'] == $reportnode[$results[$key]['reportnodeid']]['starter'])
			{
				$results[$key]['reportnodetype'] = 'reply';

				//fetch parent info of reply (starter)
				$parentid = $reportnode[$results[$key]['reportnodeid']]['parentid'];
				if (!isset($reportparentnode[$parentid]))
				{
					$reportparentnode[$parentid] = $this->nodeApi->getNodeFullContent($parentid);
					$reportparentnode[$parentid] = $reportparentnode[$parentid][$parentid];
				}
				$results[$key]['reportparentnode'] = $reportparentnode[$parentid];
			}
			else
			{
				$results[$key]['reportnodetype'] = 'comment';

				//fetch parent info of comment (reply)
				$parentid = $reportnode[$results[$key]['reportnodeid']]['parentid'];
				if (!isset($reportparentnode[$parentid]))
				{
					$reportparentnode[$parentid] = $this->nodeApi->getNodeFullContent($parentid);
					$reportparentnode[$parentid] = $reportparentnode[$parentid][$parentid];
				}
				$results[$key]['reportparentnode'] = $reportparentnode[$parentid];
			}
			$results[$key]['reportnodetitle'] = $reportnode[$results[$key]['reportnodeid']]['title'];
			$results[$key]['reportnoderouteid'] = $reportnode[$results[$key]['reportnodeid']]['routeid'];
		}

		return $results;
	}

	/**
	 * Report is not allowed to be updated.
	 *
	 * @throws vB_Exception_Api
	 * @param $nodeid
	 * @param $data
	 * @return void
	 */
	public function update($nodeid, $data, $convertWysiwygTextToBbcode = true)
	{
		throw new vB_Exception_Api('not_implemented');
	}

	/**
	 * Open or close reports
	 *
	 * @param array $nodeids Array of node IDs
	 * @param string $op 'open' or 'close'
	 * @return void
	 */
	public function openClose($nodeids, $op)
	{
		if (is_numeric($nodeids))
		{
			$nodeids = array($nodeids);
		}

		if (is_string($nodeids) AND strpos($nodeids, ','))
		{
			$nodeids = explode(',', $nodeids);
		}

		if (!$nodeids OR !is_array($nodeids))
		{
			throw new vB_Exception_Api('invalid_param', array('nodeid'));
		}

		// Not sure why it doesn't work
//		foreach ($nodeids as &$nodeid)
//		{
//			$nodeid = intval($nodeid);
//		}
//
//		$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
//			vB_dB_Query::CONDITIONS_KEY =>array('nodeid' => $nodeids),
//			'closed' => ($op == 'open'? 0 : 1));
//
//		$this->assertor->assertQuery('vBForum:report', $data);

		foreach ($nodeids as $nodeid)
		{
			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY =>array('nodeid' => intval($nodeid)),
				'closed' => ($op == 'open'? 0 : 1));

			$this->assertor->assertQuery('vBForum:report', $data);
		}

		$this->nodeApi->clearCacheEvents($nodeids);
	}

	/**
	 * Delete one or more reports
	 *
	 * @throws vB_Exception_Api
	 * @param $nodeids
	 * @return void
	 */
	public function bulkdelete($nodeids)
	{
		if (is_numeric($nodeids))
		{
			$nodeids = array($nodeids);
		}

		if (is_string($nodeids) AND strpos($nodeids, ','))
		{
			$nodeids = explode(',', $nodeids);
		}

		if (!$nodeids OR !is_array($nodeids))
		{
			throw new vB_Exception_Api('invalid_param', array('nodeid'));
		}

		foreach ($nodeids as $nodeid)
		{
			$this->delete($nodeid);
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89714 $
|| #######################################################################
\*=========================================================================*/
