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
 * @package vBulletin
 * @subpackage Legacy
 * @author Kevin Sours, vBulletin Development Team
 * @version $Revision: 83435 $
 * @since $Date: 2014-12-10 10:32:27 -0800 (Wed, 10 Dec 2014) $
 * @copyright vBulletin Solutions Inc.
 */
require_once (DIR . "/vb/legacy/dataobject.php");

/**
 * Wrapper object for a user.
 *
 */
class vB_Legacy_User extends vB_Legacy_Dataobject
{

	/**
	 * Create from the user id
	 *
	 * @param int $id
	 * @return vB_Legacy_User
	 */
	public static function createFromId($id, $extra_flags = 0)
	{
		$user = new vB_Legacy_User();
		if ($id == 0)
		{
			$user->initGuest();
		}
		else
		{
			$user->record = fetch_userinfo($id, $extra_flags);
		}

		return $user;
	}

	/**
	 * Create from the user id.
	 *
	 * Guarentee's that the lookup is cached.
	 *
	 * @param int $id
	 * @return vB_Legacy_User
	 */
	public static function createFromIdCached($id, $extra_flag = 0)
	{
		return self::createFromId($id, $extra_flag);
	}

	/**
	 * Constructor -- protected to force the use of the factory method
	 */
	protected function __construct()
	{
		$this->registry = $GLOBALS['vbulletin'];
	}

	/**
	 * Init the guest user
	 *
	 * This was taken from the session init for guests, but it was simplified to
	 * avoid having to look stuff up from the session here (some of the logic may
	 * not be appropriate for the non logged in user).  It should be a good basis
	 * for dealing with code that tried to load user id 0 when it is not the
	 * logged in user.
	 *
	 * This needs some rethinking if and when we try to merge the current user
	 * class back into this one
	 *
	 */
	protected function initGuest()
	{
		$this->record = array(
			'userid' => 0,
			'usergroupid' => 1,
			'username' => '',
			'password' => '',
			'email' => '',
			'styleid' => -1,
			'languageid' => -1,
			'lastactivity' => null,
			'daysprune' => 0,
			'timezoneoffset' => $this->registry->options['timeoffset'],
			'dstonoff' => $this->registry->options['dstonoff'],
			'showsignatures' => 1,
			'showavatars' => 1,
			'showimages' => 1,
			'showusercss' => 1,
			'dstauto' => 0,
			'maxposts' => -1,
			'startofweek' => 1,
			'threadedmode' => 0,
			'securitytoken' => 'guest',
			'securitytoken_raw' => 'guest',
			'signature' => '',
			'subfolders' => '',
			'avatarpath' => '',
			'hascustomavatar' => false,
			'hascustom' => false
		);
	}

	//*********************************************************************************
	// Derived getters

	/**
	 * Is this the guest userr
	 *
	 * @return boolean
	 */
	public function isGuest()
	{
		return ($this->get_field('userid') == 0);
	}

	/**
	 * Get teh array of subfolders
	 *
	 * @return array
	 */
	public function getSubfolders()
	{
		if ($this->record['subfolders'] == '')
		{
			return unserialize($this->record['subfolders']);
		}
		else
		{
			return array();
		}
	}

	//*********************************************************************************
	// Data Manipulation Functions

	public function saveSearchPrefs($prefs)
	{
		if ($prefs)
		{
			$save_prefs = serialize($prefs);
		}
		else
		{
			$save_prefs = '';
		}

		// init user data manager
		$userdata = new vB_Datamanager_User($GLOBALS['vbulletin'], vB_DataManager_Constants::ERRTYPE_STANDARD);
		$userdata->set_existing($this->get_record());
		$userdata->set('searchprefs', $save_prefs);

		// Legacy Hook 'search_doprefs_process' Removed //

		$userdata->save();
	}

		/**
	 * @var vB_Registry
	 */
	protected $registry = null;

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
