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
 * vB_Library_Usergroup
 *
 * @package vBLibrary
 * @access public
 */
class vB_Library_Usergroup extends vB_Library
{
	/**
	 *  Returns all of the user groups with ismoderator set
	 *
	 *  @return array usergroupids for each usergroup 
	 */
	public function getSuperModGroups()
	{
		$datastore = vB::getDatastore();
		$groups = $datastore->getValue('usergroupcache');
		$bf_ugp_adminpermissions = $datastore->getValue('bf_ugp_adminpermissions');
		$perm = $bf_ugp_adminpermissions['ismoderator'];

		$smod_groups = array();
		foreach($groups as $ugid=> $groupinfo)
		{
			if ($groupinfo['adminpermissions'] & $perm)
			{
				// super mod group
				$smod_groups[] = $ugid;
			}
		}

		return $smod_groups;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
