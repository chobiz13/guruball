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
* Class to do data save/delete operations for ANNOUNCEMENTS
*
* @package	vBulletin
* @version	$Revision: 83981 $
* @date		$Date: 2015-02-02 11:02:20 -0800 (Mon, 02 Feb 2015) $
*/
class vB_DataManager_Announcement extends vB_DataManager
{
	/**
	* Array of recognised and required fields for announcements, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'announcementid'      => array(vB_Cleaner::TYPE_UINT,     vB_DataManager_Constants::REQ_INCR, 'return ($data > 0);'),
		'nodeid'              => array(vB_Cleaner::TYPE_INT,      vB_DataManager_Constants::REQ_YES, vB_DataManager_Constants::VF_METHOD),
		'userid'              => array(vB_Cleaner::TYPE_UINT,     vB_DataManager_Constants::REQ_YES, vB_DataManager_Constants::VF_METHOD),
		'title'               => array(vB_Cleaner::TYPE_STR,      vB_DataManager_Constants::REQ_YES, 'return ($data != \'\');'),
		'pagetext'            => array(vB_Cleaner::TYPE_STR,      vB_DataManager_Constants::REQ_YES, 'return ($data != \'\');'),
		'startdate'           => array(vB_Cleaner::TYPE_UNIXTIME, vB_DataManager_Constants::REQ_YES),
		'enddate'             => array(vB_Cleaner::TYPE_UNIXTIME, vB_DataManager_Constants::REQ_YES),
		'views'               => array(vB_Cleaner::TYPE_UINT,     vB_DataManager_Constants::REQ_NO),
		'allowhtml'           => array(vB_Cleaner::TYPE_BOOL,     vB_DataManager_Constants::REQ_NO),
		'allowbbcode'         => array(vB_Cleaner::TYPE_BOOL,     vB_DataManager_Constants::REQ_NO),
		'allowsmilies'        => array(vB_Cleaner::TYPE_BOOL,     vB_DataManager_Constants::REQ_NO),
		'announcementoptions' => array(vB_Cleaner::TYPE_UINT,     vB_DataManager_Constants::REQ_NO)
	);

	/**
	* Array of field names that are bitfields, together with the name of the variable in the registry with the definitions.
	*
	* @var	array
	*/
	var $bitfields = array('announcementoptions' => 'bf_misc_announcementoptions');

	/**
	* The main table this class deals with
	*
	* @var	string
	*/
	var $table = 'vBForum:announcement';

	//Primary Key
	protected $keyField = 'announcementid';

	/**
	* Verifies that the specified nodeid is valid
	*
	* @param	integer	Forum ID (allow -1 = all forums)
	*
	* @return	boolean
	*/
	function verify_nodeid(&$forumid)
	{
		$channels = vB_Api::instanceInternal('search')->getChannels(true);
		if ($forumid == -1 OR isset($channels["$forumid"]))
		{
			return true;
		}
		else
		{
			$this->error('invalid_forum_specified');
			return false;
		}
	}

	/**
	* Any checks to run immediately before saving. If returning false, the save will not take place.
	*
	* @param	boolean	Do the query?
	*
	* @return	boolean	True on success; false if an error occurred
	*/
	function pre_save($doquery = true)
	{
		if ($this->presave_called !== null)
		{
			return $this->presave_called;
		}

		if ($this->fetch_field('startdate') >= $this->fetch_field('enddate'))
		{
			$this->error('begin_date_after_end_date');
		}

		$return_value = true;
		// Legacy Hook 'announcementdata_presave' Removed //

		$this->presave_called = $return_value;
		return $return_value;
	}

	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed for each record updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_each($doquery = true)
	{
		// Legacy Hook 'announcementdata_postsave' Removed //

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		$announcementid = intval($this->existing['announcementid']);
		//$db =& $this->registry->db;

		$this->assertor->delete('vBForum:announcementread', array('announcementid' => $announcementid));

		// Legacy Hook 'announcementdata_delete' Removed //
		return true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83981 $
|| #######################################################################
\*=========================================================================*/
