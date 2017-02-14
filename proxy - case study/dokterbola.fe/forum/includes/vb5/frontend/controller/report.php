<?php
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

class vB5_Frontend_Controller_Report extends vB5_Frontend_Controller
{

	function __construct()
	{
		parent::__construct();
	}

	function actionReport()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'reason' => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''),
			'reportnodeid' => (isset($_POST['reportnodeid']) ? trim(intval($_POST['reportnodeid'])) : 0),
		);

		if (!$input['reportnodeid'])
		{
			$results['error'] = 'invalid_nodeid';
			$this->sendAsJson($results);

			return;
		}

		$api = Api_InterfaceAbstract::instance();

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchCurrentUserinfo', array());

		$reportData = array(
			'rawtext' => $input['reason'],
			'reportnodeid' => $input['reportnodeid'],
			'parentid' => $input['reportnodeid'],
			'userid' => $user['userid'],
			'authorname' => $user['username'],
			'created' => time(),
		);

		$nodeId = $api->callApi('content_report', 'add', array($reportData));

		if (!empty($nodeId['errors']))
		{
			$results['error'] = $nodeId['errors'][0];
		}
		else
		{
			$results = $nodeId;
		}

		$this->sendAsJson($results);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85451 $
|| #######################################################################
\*=========================================================================*/
