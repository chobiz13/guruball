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
/*
if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}
*/

class vB_Upgrade_521a2 extends vB_Upgrade_Version
{
	/*Constants=====================================================================*/

	/*Properties====================================================================*/

	/**
	* The short version of the script
	*
	* @var	string
	*/
	public $SHORT_VERSION = '521a2';

	/**
	* The long version of the script
	*
	* @var	string
	*/
	public $LONG_VERSION  = '5.2.1 Alpha 2';

	/**
	* Versions that can upgrade to this script
	*
	* @var	string
	*/
	public $PREV_VERSION = '5.2.1 Alpha 1';

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

	public function step_1()
	{
		$this->drop_table('reminder');
	}

	public function step_2()
	{
		$this->drop_table('pm');
	}

	public function step_3()
	{
		$this->drop_table('pmreceipt');
	}

	public function step_4()
	{
		$this->drop_table('pmtext');
	}

	public function step_5()
	{
		$this->drop_table('pmthrottle');
	}

	public function step_6()
	{
		$this->drop_table('nodevote');
		$this->long_next_step();
	}

	public function step_7()
	{
		$this->drop_table('searchcore');
		$this->long_next_step();
	}

	public function step_8()
	{
		$this->drop_table('searchcore_text');
		$this->long_next_step();
	}

	public function step_9()
	{
		$this->drop_table('searchgroup');
		$this->long_next_step();
	}

	public function step_10()
	{
		$this->drop_table('searchgroup_text');
	}

	public function step_11()
	{
		//if we've already removed the forumid, this doesn't work.
		if ($this->field_exists('access', 'forumid'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'access'));
			vB::getDbAssertor()->assertQuery('vBInstall:setAccessNodeid',
				array('forumTypeId' => vB_Types::instance()->getContentTypeID('vBForum_Forum')));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_12()
	{
		/*
		 *	If, after the update, the nodeids are 0 then we just don't have the data to fix them
		 *	and they are going to break the index.  So we'll remove them.
		 */
		$this->show_message($this->phrase['version']['521a2']['delete_access_records']);
		vB::getDbAssertor()->delete('access', array('nodeid' => 0));
	}

	public function step_13()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'access', 1, 4),
			'access',
			'PRIMARY'
		);
	}

	public function step_14()
	{
		$this->drop_table('access_temp');
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'access_temp'),
			"
			CREATE TABLE " . TABLE_PREFIX . "access_temp (
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				nodeid SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				accessmask SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX user_node(userid, nodeid)
			)
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_15()
	{
		$this->show_message($this->phrase['version']['521a2']['copy_access_records']);
		$db = vB::getDbAssertor();
		$db->assertQuery('vBInstall:insertDedupTempAccess');
		$db->assertQuery('truncateTable', array('table' => 'access'));
	}

	public function step_16()
	{
		$this->show_message($this->phrase['version']['521a2']['restore_access_records']);
		$db = vB::getDbAssertor();
		$db->assertQuery('vBInstall:restoreDedupAccess');
		$this->drop_table('access_temp');
	}

	public function step_17()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'access', 2, 4),
			'access',
			'PRIMARY',
			array('userid, nodeid'),
			'primary'
		);
	}

	public function step_18()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'access', 3, 4),
			'access',
			'forumid'
		);
	}

	public function step_19()
	{
		//this might be a good idea, but it doesn't match the install and the table
		//isn't currently used.
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'access', 4, 4),
			'access',
			'nodeid'
		);
	}

	public function step_20()
	{
		$this->drop_table('visitormessage');
	}

	public function step_21()
	{
		$this->drop_table('visitormessage_hash');
	}

	public function step_22()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'hook', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "hook MODIFY COLUMN template varchar(100) NOT NULL DEFAULT ''"
		);
	}

	/**
	 * Add useractivation.reset_attempts
	 */
	public function step_23()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'useractivation', 1, 2),
			'useractivation',
			'reset_attempts',
			'int',
			array('null' => false, 'default' => '0')
		);
	}

	/**
	 * Add useractivation.reset_locked_since
	 */
	public function step_24()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'useractivation', 2, 2),
			'useractivation',
			'reset_locked_since',
			'int',
			array('null' => false, 'default' => '0')
		);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision$
|| ####################################################################
\*======================================================================*/
