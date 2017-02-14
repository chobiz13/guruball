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

class vB5_Frontend_Controller_Admin extends vB5_Frontend_Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function actionSavepage()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = $_POST['input'];
		$url = $_POST['url'];

		//parse_url doesn't work on relative urls and I don't want to assume that
		//we have an absolute url.  We probably don't have a query string, but bad assumptions
		//about the url are what got us into this problem to begin with.
		$parts = explode('?', $url, 2);
		$url = $parts[0];

		$query = '';
		if (sizeof($parts) == 2)
		{
			$query = $parts[1];
		}

		if (preg_match('#^http#', $url))
		{
			$base = vB5_Template_Options::instance()->get('options.frontendurl');
			if (preg_match('#^' . preg_quote($base, '#') . '#', $url))
			{
				$url = substr($url, strlen($base)+1);
			}
		}

		$api = Api_InterfaceAbstract::instance();
		$route = $api->callApi('route', 'getRoute', array('pathInfo' => $url, 'queryString' => $query));

		//if we have a redirect try to find the real route -- this should only need to handle one layer
		//and if that also gets a redirect things are broken somehow.
		if (!empty($route['redirect']))
		{
			$route = $api->callApi('route', 'getRoute', array('pathInfo' => ltrim($route['redirect'], '/'), 'queryString' => $query));
		}

		$result = $api->callApi('page', 'pageSave', array($input));
		if (empty($result['errors']))
		{
			$page = $api->callApi('page', 'fetchPageById', array('pageid' => $result['pageid'], 'routeData' => $route['arguments']));
			$result['url'] = $page['url'];
		}
		$this->sendAsJson($result);
	}

	protected function getApi()
	{
		return Api_InterfaceAbstract::instance();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87047 $
|| #######################################################################
\*=========================================================================*/
