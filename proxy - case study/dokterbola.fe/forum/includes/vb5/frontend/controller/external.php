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

class vB5_Frontend_Controller_External extends vB5_Frontend_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function actionOutput()
	{
		// This is called via application light, see also vB_Library_External
		// Allow GET requests

		$api = Api_InterfaceAbstract::instance();
		$response = $api->callApi('session', 'getGuestSession');
		if (is_array($response) AND !empty($response['errors']))
		{
			return '';
		}

		$type = (!empty($_REQUEST['type']) ? $_REQUEST['type'] : '');

		// default rss2
		switch ($type)
		{
			case 'rss2':
			case 'rss1':
			case 'rss':
			case 'xml':
			case 'js':
				$type = $_REQUEST['type'];
				break;
			default:
				$type = 'rss2';
				break;
		}

		if((!empty($_SERVER['HTTP_IF_NONE_MATCH']))
			 AND
			(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		)
		{
			$response = $api->callApi('external', 'getCacheData', array('type' => $type, 'options' => $_REQUEST));
			if (is_array($response) AND !empty($response['errors']))
			{
				return '';
			}

			if ($_SERVER['HTTP_IF_NONE_MATCH'] == "\"$response[cachehash]\"")
			{
				$timediff = strtotime(gmdate('D, d M Y H:i:s') . ' GMT') - strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
				if ($timediff <= $response['cachetime'])
				{
					if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
					{
						header('Status: 304 Not Modified');
					}
					else
					{
						header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
					}
					exit;
				}
			}
		}

		// always disable nohtml
		$_REQUEST['nohtml'] = 0;
		$response = $api->callApi('external', 'createExternalOutput', array('type' => $type, 'options' => $_REQUEST));
		if (is_array($response) AND !empty($response['errors']))
		{
			return '';
		}

		$data = $_REQUEST + array('Pragma' => '', 'Content-Type' => vB5_String::getTempCharset());
		$headers = $api->callApi('external', 'getHeadersFromLastOutput', array('type' => $type, 'data' => $data));
		if (is_array($headers) AND !empty($headers['errors']))
		{
			return '';
		}

		foreach ($headers AS $name => $value)
		{
			header("$name: $value");
		}

		return $response;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85440 $
|| #######################################################################
\*=========================================================================*/
