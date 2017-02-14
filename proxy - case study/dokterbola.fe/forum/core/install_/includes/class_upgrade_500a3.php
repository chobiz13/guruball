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

/*
if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}
*/

class vB_Upgrade_500a3 extends vB_Upgrade_Version
{
	/*Constants=====================================================================*/

	/*Properties====================================================================*/

	/**
	* The short version of the script
	*
	* @var	string
	*/
	public $SHORT_VERSION = '500a3';

	/**
	* The long version of the script
	*
	* @var	string
	*/
	public $LONG_VERSION  = '5.0.0 Alpha 3';

	/**
	* Versions that can upgrade to this script
	*
	* @var	string
	*/
	public $PREV_VERSION = '5.0.0 Alpha 2';

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



	/***	Adding initial widgets*/
	function step_1()
	{
		$widgetRecords = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget WHERE `title` = 'PHP'
		");

		if (empty($widgetRecords) OR empty($widgetRecords['widgetid']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'));
			$this->db->query_write("
				INSERT INTO `" . TABLE_PREFIX . "widget`
				(`title`, `template`, `admintemplate`, `icon`, `isthirdparty`, `category`, `cloneable`)
				VALUES
				('PHP', 'widget_15', '', 'module-icon-default.png', 0, 'uncategorized', 0);
				"
			);
			$widgetid = $this->db->insert_id();

			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"
				INSERT INTO `" . TABLE_PREFIX . "widgetdefinition`
				(`widgetid`, `field`, `name`, `label`, `defaultvalue`, `isusereditable`, `isrequired`, `displayorder`, `validationtype`, `validationmethod`, `data`)
				VALUES
				($widgetid, 'Text', 'title', 'Title', 'Unconfigured PHP Widget', 1, 1, 1, '', '', ''),
				($widgetid, 'LongText', 'code', 'PHP Code', 'PHP Widget Content', 1, 0, 2, '', '', '')
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	function step_2()
	{
		$widgetRecords = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget WHERE `template` = 'widget_top_active_users'
		");

		if (empty($widgetRecords) OR empty($widgetRecords['widgetid']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'));
			$this->db->query_write("
				INSERT INTO `" . TABLE_PREFIX . "widget`
				(`title`, `template`, `admintemplate`, `icon`, `isthirdparty`, `category`, `cloneable`)
				VALUES
				('Top Active Users', 'widget_top_active_users', '', 'module-icon-default.png', 0, 'uncategorized', 0);
				"
			);
			$widgetid = $this->db->insert_id();

			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"
				INSERT INTO `" . TABLE_PREFIX . "widgetdefinition`
				(`widgetid`, `field`, `name`, `label`, `defaultvalue`, `isusereditable`, `isrequired`, `displayorder`, `validationtype`, `validationmethod`, `data`)
				VALUES
				($widgetid, 'Text', 'maxUsers', 'Max top online users to show:', '20', 1, 0, 1, '', '', '')
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	function step_3()
	{
		$activityWidget = $this->db->query_first("SELECT widgetid FROM " . TABLE_PREFIX . "widget WHERE title='Activity Stream';");

		if (empty($activityWidget) OR empty($activityWidget['widgetid']))
		{
			$this->skip_message();
			return;
		}

		$activityWidgetId = $activityWidget['widgetid'];

		$widgetDefRecords = $this->db->query_first("SELECT widgetid FROM " . TABLE_PREFIX . "widgetdefinition WHERE `widgetid` = " . $activityWidgetId);

		if (empty($widgetDefRecords) OR empty($widgetDefRecords['widgetid']))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"
				INSERT INTO `" . TABLE_PREFIX . "widgetdefinition`
				(`widgetid`, `field`, `name`, `label`, `defaultvalue`, `isusereditable`, `isrequired`, `displayorder`, `validationtype`, `validationmethod`, `data`)
				VALUES
				($activityWidgetId, 'Select', 'filter_sort', 'Sort By', 'sort_recent', 1, 1, 1, '', '', 'a:2:{s:11:\"sort_recent\";s:11:\"Most Recent\";s:13:\"sort_featured\";s:13:\"Sort Featured\";}'),
				($activityWidgetId, 'Select', 'filter_time', 'Time', 'time_all', 1, 1, 2, '', '', 'a:4:{s:10:\"time_today\";s:5:\"Today\";s:13:\"time_lastweek\";s:9:\"Last Week\";s:14:\"time_lastmonth\";s:10:\"Last Month\";s:8:\"time_all\";s:8:\"All time\";}'),
				($activityWidgetId, 'Select', 'filter_show', 'Show', 'show_all', 1, 1, 3, '', '', 'a:3:{s:8:\"show_all\";s:3:\"All\";s:11:\"show_photos\";s:11:\"Photos only\";s:10:\"show_polls\";s:10:\"Polls only\";}'),
				($activityWidgetId, 'YesNo', 'filter_conversations', 'Show new conversations?', 1, 1, 0, 4, '', '', '')
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/***	adding relationship 'follow' to userlist table*/
	function step_4()
	{

		$this->run_query(sprintf($this->phrase['core']['altering_x_table'], 'userlist', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "userlist CHANGE type type ENUM('buddy', 'ignore', 'follow') NOT NULL DEFAULT 'buddy';");
	}

	/** Add the route for the profile pages**/
	function step_5()
	{
		$this->skip_message();
	}

	/***	Setting default adminConfig for activity stream widget */
	function step_6()
	{

		$this->run_query(
		sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'),
		"
		UPDATE " . TABLE_PREFIX . "widgetinstance
			SET adminconfig = 'a:4:{s:11:\"filter_sort\";s:11:\"sort_recent\";s:11:\"filter_time\";s:8:\"time_all\";s:11:\"filter_show\";s:8:\"show_all\";s:20:\"filter_conversations\";s:1:\"1\";}'
			WHERE widgetid = (SELECT widgetid FROM " . TABLE_PREFIX . "widget WHERE title = 'Activity Stream') AND adminconfig = ''
			"
		);
	}

	/**
	 * Add default header navbar items for Blogs
	 */
	function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'),
			"
				UPDATE " . TABLE_PREFIX . "site
				SET headernavbar = 'a:1:{i:0;a:2:{s:5:\"title\";s:5:\"Blogs\";s:3:\"url\";s:1:\"#\";}}'
				WHERE
					siteid = 1
						AND
					headernavbar = ''
			"
		);
	}

	function step_8()
	{
		$widgetRecords = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget WHERE `title` = 'Today\\'s Birthday'
		");

		if (empty($widgetRecords) OR empty($widgetRecords['widgetid']))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'),
				"
				INSERT INTO `" . TABLE_PREFIX . "widget`
				(`title`, `template`, `admintemplate`, `icon`, `isthirdparty`, `category`, `cloneable`)
				VALUES
				('Today\\'s Birthday', 'widget_birthday', '', 'module-icon-default.png', 0, 'uncategorized', 0);
				"
			);
		}
		else
		{
			$this->skip_message();
		}

	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
