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

class Api_Interface_Test extends Api_Interface_Collapsed
{
	public function __construct()
	{
		// in collapsed form, we want to be able to load API classes
		$core_path = vB5_Config::instance()->core_path;
		vB5_Autoloader::register($core_path);

		vB::init();
		$request = new vB_Request_Test(
			array(
				'userid' => 1,
				'ipAddress' => '127.0.0.1',
				'altIp' => '127.0.0.1',
				'userAgent' => 'CLI'
			)
		);
		vB::setRequest($request);
		$request->createSession();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
