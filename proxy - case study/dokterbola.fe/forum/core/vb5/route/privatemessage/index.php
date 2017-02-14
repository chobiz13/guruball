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

class vB5_Route_PrivateMessage_Index
{
	protected $subtemplate = 'privatemessage_foldersummary';

	public function __construct(&$routeInfo, &$matches, &$queryString = '')
	{
		// just modify routeInfo, no internal settings
		$routeInfo['arguments']['subtemplate'] = $this->subtemplate;
		}

	public function validInput(&$data)
	{
		$data['arguments'] = '';

		return true;
	}

	public function getUrlParameters()
	{
		return '';
	}

	public function getParameters()
	{
		// TODO: remove the dummy variable, this was just a demo
		return array('dummyIndex' => "I'm a dummy value!");
	}

	public function getBreadcrumbs()
	{
		return array(
			array(
				'phrase' => 'inbox',
				'url'	=> ''
			)
		);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
