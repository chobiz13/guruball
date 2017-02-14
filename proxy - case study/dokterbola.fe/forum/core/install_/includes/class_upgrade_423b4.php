<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.2.3 - Licence Number LC451E80E8
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2016 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

/*
if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}
*/

class vB_Upgrade_423b4 extends vB_Upgrade_Version
{
	/*Constants=====================================================================*/

	/*Properties====================================================================*/

	/**
	* The short version of the script
	*
	* @var	string
	*/
	public $SHORT_VERSION = '423b4';

	/**
	* The long version of the script
	*
	* @var	string
	*/
	public $LONG_VERSION  = '4.2.3 Beta 4';

	/**
	* Versions that can upgrade to this script
	*
	* @var	string
	*/
	public $PREV_VERSION = '4.2.3 Beta 3';

	/**
	* Beginning version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_STARTS = '';

	/**
	* Ending version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_ENDS   = '';

	/*

	Step 1 Removed, There is no 'postlog' table in vB5.

	*/

	/*

	Step 2 Removed, This is performed in 5.1.1 Alpha 7, Step 2
		   Note that vB5 uses CHAR for this field, not VARCHAR.
		   MEMORY tables use fixed length storage regardless, so either is ok.
		   If the table type for 'session' is changed, VARCHAR may be better.

	*/

	/*

	Step 3 Removed, This is not required as it simply converted CHAR to VARCHAR.
		   vB5 uses CHAR for this field anyway, so no update is required.
		   See note above for step 2 regarding MEMORY tables.

	*/
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 35750 $
|| ####################################################################
\*======================================================================*/
