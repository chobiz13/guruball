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
 * The vB core class.
 * Everything required at the core level should be accessible through this.
 *
 * The core class performs initialisation for error handling, exception handling,
 * application instatiation and optionally debug handling.
 *
 * @TODO: Much of what goes on in global.php and init.php will be handled, or at
 * least called here during the initialisation process.  This will be moved over as
 * global.php is refactored.
 *
 * @package vBulletin
 * @version $Revision: 87129 $
 * @since $Date: 2016-02-25 11:18:15 -0800 (Thu, 25 Feb 2016) $
 * @copyright vBulletin Solutions Inc.
 */
class vB_dB_MYSQL_Assertor extends vB_dB_Assertor
{
	/*Properties====================================================================*/

	protected static $db_type = 'MYSQL';

	protected function __construct(&$dbconfig, &$config)
	{
		parent::__construct($dbconfig, $config);
		self::$dbSlave = (!empty($dbconfig['SlaveServer']['servername'])) AND (!empty($dbconfig['SlaveServer']['port'])) AND
			(!empty($dbconfig['SlaveServer']['username']));

	}

	protected function load_database(&$dbconfig, &$config)
	{
		// load database class
		switch (strtolower($dbconfig['Database']['dbtype']))
		{
			// alwyas use load MySQLi class, the mysql interface is obsolete
			// mysql[i]_slave checks taken from core/install/init.php
			case 'mysql':
			case 'mysql_slave':
			case 'mysqli':
			case 'mysqli_slave':
			{
				$db = new vB_Database_MySQLi($dbconfig, $config);
				break;
			}
			default:
			{
				die('Fatal error: Database class not found');
			}
		}

		//even if the connection fails its useful to have a valid
		//connection object.  Particularly for the installer.
		self::$db = $db;

		// get core functions
		if (!$db->isExplainEmpty())
		{
			$db->timer_start('Including Functions.php');
			require_once(DIR . '/includes/functions.php');
			$db->timer_stop(false);
		}
		else
		{
			require_once(DIR . '/includes/functions.php');
		}

// make database connection
		$db->connect(
				$dbconfig['Database']['dbname'],
				$dbconfig['MasterServer']['servername'],
				$dbconfig['MasterServer']['port'],
				$dbconfig['MasterServer']['username'],
				$dbconfig['MasterServer']['password'],
				$dbconfig['MasterServer']['usepconnect'],
				$dbconfig['SlaveServer']['servername'],
				$dbconfig['SlaveServer']['port'],
				$dbconfig['SlaveServer']['username'],
				$dbconfig['SlaveServer']['password'],
				$dbconfig['SlaveServer']['usepconnect'],
				$dbconfig['Mysqli']['ini_file'],
				(isset($dbconfig['Mysqli']['charset']) ? $dbconfig['Mysqli']['charset'] : '')
		);
//if (!empty($vb5_config['Database']['force_sql_mode']))
//{
		$db->force_sql_mode('');
//}
//30443 Right now the product doesn't work in strict mode at all.  Its silly to make people have to edit their
//config to handle what appears to be a very common case (though the mysql docs say that no mode is the default)
//we no longer use the force_sql_mode parameter, though if the app is fixed to handle strict mode then we
//may wish to change the default again, in which case we should honor the force_sql_mode option.
//added the force parameter
//if (!empty($vbulletin->config['Database']['force_sql_mode']))
//if (empty($vbulletin->config['Database']['no_force_sql_mode']))
//{
//	$db->force_sql_mode('');
//}

		if (defined('DEMO_MODE') AND DEMO_MODE AND function_exists('vbulletin_demo_init_db'))
		{
			vbulletin_demo_init_db();
		}

		return $db;
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87129 $
|| #######################################################################
\*=========================================================================*/
