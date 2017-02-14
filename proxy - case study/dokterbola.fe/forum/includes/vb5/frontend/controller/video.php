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

class vB5_Frontend_Controller_Video extends vB5_Frontend_Controller
{

	function __construct()
	{
		parent::__construct();
	}

	function actionGetvideodata()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'url' => trim($_POST['url']),
		);

		$api = Api_InterfaceAbstract::instance();
		$video = $api->callApi('content_video', 'getVideoFromUrl', array($input['url']));

		if ($video)
		{
			$templater = new vB5_Template('video_edit');
			$templater->register('video', $video);
			$templater->register('existing', 0);
			$templater->register('editMode', 1);
			$results['template'] = $templater->render();
		}
		else
		{
			$results['error'] = 'Invalid URL.';
		}

		$this->sendAsJson($results);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85457 $
|| #######################################################################
\*=========================================================================*/
