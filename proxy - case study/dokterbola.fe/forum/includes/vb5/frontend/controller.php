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

class vB5_Frontend_Controller
{
	/** vboptions **/
	protected $vboptions = array();

	function __construct()
	{
		$vboptions = vB5_Template_Options::instance()->getOptions();
		$this->vboptions = $vboptions['options'];
	}

	/**
	 * Sends the response as a JSON encoded string
	 *
	 * @param	mixed	The data (usually an array) to send
	 */
	public function sendAsJson($data)
	{
		//This function needs to be kept in sync with the implmentation in applicationlight.php

		if (headers_sent($file, $line))
		{
			throw new Exception("Cannot send response, headers already sent. File: $file Line: $line");
		}

		// We need to convert $data charset if we're not using UTF-8
		if (vB5_String::getTempCharset() != 'UTF-8')
		{
			$data = vB5_String::toCharset($data, vB5_String::getTempCharset(), 'UTF-8');
		}

		//If this is IE9, IE10, or IE11 -- we also need to work around the deliberate attempt to break "is IE" logic by the
		//IE dev team -- we need to send type "text/plain". Yes, we know that's not the standard.
		if (
			isset($_SERVER['HTTP_USER_AGENT']) && (
				(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) OR
				(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)
			)
		)
		{
			header('Content-type: text/plain; charset=UTF-8');
		}
		else
		{
			header('Content-type: application/json; charset=UTF-8');
		}

		// IE will cache ajax requests, and we need to prevent this - VBV-148
		header('Cache-Control: max-age=0,no-cache,no-store,post-check=0,pre-check=0');
		header('Expires: Sat, 1 Jan 2000 01:00:00 GMT');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Pragma: no-cache");

		if (isset($data['template']) AND !empty($data['template']))
		{
			$data['template'] = $this->outputPage($data['template'], false);
		}

		$output = vB5_String::jsonEncode($data);

		$sapi_name = php_sapi_name();
		if (!(strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false AND strpos($sapi_name, 'cgi') !== false))
		{
			$length = strlen($output);
			header('Content-Length: ' . $length);
			header('Connection: Close');
		}

		echo $output;
	}

	/**
	 * Show a simple and clear message page which contains no widget
	 *
	 * @param string $title Page title. HTML will be escaped.
	 * @param string $msg Message to display. HTML is allowed and the caller must make sure it's valid.
	 * @deprecated
	 */
	public function showMsgPage($title, $msg)
	{
		// This function basically duplicates the more common function in vB5_ApplicationAbstract.  The latter
		// doesn't handle early flush, but frankly that's overkill for a simple message page.  Better to get
		// everything running the same code.
		vB5_ApplicationAbstract::showMsgPage($title, $msg);
	}

	/**
	 * Replaces special characters in a given string with dashes to make the string SEO friendly
	 * Note: This is really restrictive. If it can be helped, leave it to core's vB_String::getUrlIdent.
	 *
	 * @param	string	The string to be converted
	 */
	protected function toSeoFriendly($str)
	{
		if (!empty($str))
		{
			return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($str)), '-');
		}
		return $str;
	}

	/**
	 * Handle errors that are returned by API for use in JSON AJAX responses.
	 *
	 * @param	mixed	The result array to populate errors into. It will contain error phrase ids.
	 * @param	mixed	The returned object by the API call.
	 *
	 * @return	boolean	true errors are found, false, otherwise.
	 */
	protected function handleErrorsForAjax(&$result, $return)
	{
		if ($return AND !empty($return['errors']))
		{
			if (isset($return['errors'][0][1]))
			{
				// it is a phraseid with variables
				$errorList = array($return['errors'][0]);
			}
			else
			{
				$errorList = array($return['errors'][0][0]);
			}

			if (!empty($result['error']))
			{
				//merge and remove duplicate error ids
				$errorList = array_merge($errorList, $result['error']);
				$errorList = array_unique($errorList);
			}

			$result['error'] = $errorList;
			return true;
		}
		return false;
	}

	/**
	 * Checks if this is a POST request
	 */
	protected function verifyPostRequest()
	{
		// Require a POST request for certain controller methods
		// to avoid CSRF issues. See VBV-15018 for more details.
		if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')
		{
			// show exception and stack trace in debug mode
			throw new Exception('This action only available via POST');
		}

		// Also verify CSRF token.
		vB5_ApplicationAbstract::checkCSRF();

	}

	/**
	 * Any final processing, and then output the page
	 */
	protected function outputPage($html, $exit = true)
	{
		$styleid = vB5_Template_Stylevar::instance()->getPreferredStyleId();

		if (!$styleid)
		{
			$styleid = $this->vboptions['styleid'];
		}

		$api = Api_InterfaceAbstract::instance();
		$fullPage = $api->callApi('template', 'processReplacementVars', array($html, $styleid));

		if (vB5_Config::instance()->debug)
		{
			$fullPage = str_replace('<!-- VB-DEBUG-PAGE-TIME-PLACEHOLDER -->', round(microtime(true) - VB_REQUEST_START_TIME, 4), $fullPage);
		}

		$api->invokeHook('hookFrontendBeforeOutput', array('styleid' => $styleid, 'pageHtml' => &$fullPage));

		if ($exit)
		{
			echo $fullPage;
			exit;
		}

		return $fullPage;
	}

	protected function parseBbCodeForPreview($rawText, $options = array())
	{
		$results = array();

		if (empty($rawText))
		{
			$results['parsedText'] = $rawText;
			return $results;
		}

		// parse bbcode in text
		try
		{
			$results['parsedText'] = vB5_Frontend_Controller_Bbcode::parseWysiwygForPreview($rawText, $options);
		}
		catch (Exception $e)
		{
			$results['error'] = 'error_parsing_bbcode_for_preview';

			if (vB5_Config::instance()->debug)
			{
				$results['error_trace'] = (string) $e;
			}
		}

		return $results;
	}


	/**
	 *	Adds attachment information so attachments can be created in one call
	 *
	 *	This will modify the $data array to add data under the keys
	 *	'attachments' for added attachments & 'removeattachments' for
	 *	attachments requested for removal.
	 *
	 * @param 	mixed	array of node data for insert
	 */
	protected function addAttachments(&$data)
	{
		if (isset($_POST['filedataids']) AND !empty($data['parentid']))
		{
			$api = Api_InterfaceAbstract::instance();
			$availableSettings =  $api->callApi('content_attach', 'getAvailableSettings', array());
			$availableSettings = (isset($availableSettings['settings'])? $availableSettings['settings'] : array());

			$data['attachments'] = array();
			/*
			 *	For inline inserts, the key is the temporary id that will be replaced by the nodeid by
			 *	vB_Library_Content_Text->fixAttachBBCode(), so maintaining the key $k is important.
			 */
			foreach ($_POST['filedataids'] AS $k => $filedataid)
			{
				$filedataid = (int) $filedataid;

				if ($filedataid < 1)
				{
					continue;
				}

				// We only use $availableSettings so we know which values to extract
				// from the $_POST variable. This is not here for cleaning,
				// which happens in the API. See the text and attach API cleanInput
				// methods.
				$settings = array();
				foreach ($availableSettings AS $settingkey)
				{
					if (!empty($_POST['setting'][$k][$settingkey]))
					{
						$settings[$settingkey] = $_POST['setting'][$k][$settingkey];
					}
				}

				$data['attachments'][$k] = array(
					'filedataid' => $filedataid,
					'filename' => (isset($_POST['filenames'][$k]) ? strval($_POST['filenames'][$k]) : ''),
					'settings' => $settings,
				);

			}
		}

		// if it's an update, we might have some attachment removals.
		// Let's also add removeattachments for an update, so the attachment limit
		// checks can take them into account.
		if (!empty($_POST['removeattachnodeids']))
		{
			// This list is used in 2 places.
			// First, it's used for permission checking purposes in vB_Api_Content_Text->checkAttachmentPermissions()
			// Later, it is used to delete attachments after the main node update in vB_Library_Content_Text->update().
			foreach ($_POST['removeattachnodeids'] AS $removeattachnodeid)
			{
				$removeattachnodeid = (int) $removeattachnodeid;
				if ($removeattachnodeid > 0)
				{
					$data['removeattachments'][$removeattachnodeid] = $removeattachnodeid;
				}
			}
		}
	}

	/*
		Copied from vB5_Frontend_ApplicationLight::handleAjaxApiDetached()
	*/
	protected function sendAsJsonAndCloseConnection($responseToSendAsJson)
	{
		ignore_user_abort(true);
		@set_time_limit(0);

		// browser will think there is no more data if content-length is what is returned
		// regardless of how long the script continues to execute, apart from IIS + CGI
		if (is_array($responseToSendAsJson) AND !isset($responseToSendAsJson['note']))
		{
			$responseToSendAsJson['note'] = 'Returned before processing';
		}

		$this->sendAsJson($responseToSendAsJson);

		// ob_end_flush and flush are needed for the browser to think the request is complete
		if (ob_get_level())
		{
			ob_end_flush();
		}
		flush();

		//this is intended to make the detach funcion work for people running php-fpm.
		if (function_exists('fastcgi_finish_request'))
		{
			fastcgi_finish_request();
		}

		// The caller will likely have more processing. Why else would they call this?
		return true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87289 $
|| #######################################################################
\*=========================================================================*/
