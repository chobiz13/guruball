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
 * @package vBLibrary
 *
 * This class depends on the following
 *
 *
 * It does not and should not depend on the permission objects.  All permissions
 * should be handled outside of the class and passed to to the class in the form 
 * of override flags.
 * 
 */

/**
 * vB_Library_API
 *
 * @package vBLibrary
 * @access public
 */
class vB_Library_API extends vB_Library
{
	/**
	 * Generates an api key.
	 * @return string
	 */
	public function generateAPIKey()
	{
		require_once(DIR . '/includes/functions.php');
		$newapikey = fetch_random_password();
		$assertor = vB::getDbAssertor();
		$assertor->update('setting',
			array(
				'value' => $newapikey,
			),
			array(
				'varname' => 'apikey',
			)
		);
		$assertor->update('setting',
			array(
				'value' => '1',
			),
			array(
				'varname' => 'enableapi',
			)
		);
		vB::getDatastore()->build_options();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86469 $
|| #######################################################################
\*=========================================================================*/
