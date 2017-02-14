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
 * vB_Api_Request
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Request extends vB_Api
{

	protected $disableWhiteList = array('getRequestInfo');

	protected function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns an array of request information
	 *
	 * @return 	array	The request info
	 */
	public function getRequestInfo()
	{
		$request = vB::getRequest();

		$items = array(
			'sessionClass'  => 'getSessionClass',
			'timeNow'       => 'getTimeNow',
			'ipAddress'     => 'getIpAddress',
			'altIp'         => 'getAltIp',
			'sessionHost'   => 'getSessionHost',
			'userAgent'     => 'getUserAgent',
			'useEarlyFlush' => 'getUseEarlyFlush',
			'cachePageForGuestTime' => 'getCachePageForGuestTime',
			'referrer'      => 'getReferrer',
			'vBHttpHost'    => 'getVbHttpHost',
			'vBUrlScheme'   => 'getVbUrlScheme',
			'vBUrlPath'     => 'getVbUrlPath',
			'vBUrlQuery'    => 'getVbUrlQuery',
			'vBUrlQueryRaw' => 'getVbUrlQueryRaw',
			'vBUrlClean'    => 'getVbUrlClean',
			'vBUrlWebroot'  => 'getVbUrlWebroot',
			'scriptPath'    => 'getScriptPath',
		);

		$values = array();

		foreach ($items AS $varName => $methodName)
		{
			$values[$varName] = $request->$methodName();
		}

		return $values;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
