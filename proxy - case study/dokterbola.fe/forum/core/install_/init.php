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

// Force PHP 5.3.0+ to take time zone information from OS
/* The min requirement for vB5 is 5.3.0, so version checking isnt necessary */
@date_default_timezone_set(date_default_timezone_get());

// set the current unix timestamp
define('TIMENOW', time());
define('SAPI_NAME', php_sapi_name());
define('SAFEMODE', (@ini_get('safe_mode') == 1 OR strtolower(@ini_get('safe_mode')) == 'on') ? true : false);

// define current directory
if (!defined('CWD'))
{
	define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));
}

// #############################################################################
//Class Core needs a number of values, which are set by php in the web
// request
if (!isset($_SERVER['SERVER_PORT']))
{
	//we're being run from CLI
	$_SERVER['SERVER_PORT'] = 80;
}

if (!isset($_SERVER['SERVER_NAME']))
{
	//we're being run from CLI
	$_SERVER['SERVER_NAME'] = 80;
}

if (!isset($_SERVER['HTTP_HOST']))
{
	//we're being run from CLI
	$_SERVER['HTTP_HOST'] = '';
}

if (!isset($_SERVER['QUERY_STRING']))
{
	//we're being run from CLI
	$_SERVER['QUERY_STRING'] = '';
}

if (!isset($_SERVER['REQUEST_URI']))
{
	//we're being run from CLI
	$_SERVER['REQUEST_URI'] = '';
}

if (!isset($_SERVER['REQUEST_METHOD']))
{
	//we're being run from CLI
	$_SERVER['REQUEST_METHOD'] = '';
}

if (!isset($_SERVER['REMOTE_ADDR']))
{
	//we're being run from CLI
	$_SERVER['REMOTE_ADDR'] = '';
}

if (!isset($_SERVER['HTTP_USER_AGENT']))
{
	//we're being run from CLI
	$_SERVER['HTTP_USER_AGENT'] = 'vB CLI';
}

// fetch the core classes
require_once(CWD . '/includes/class_core.php');

if (!class_exists('vB')) {
	require_once(CWD . '/vb/vb.php');
}

vB::init();
vB::setRequest(new vB_Request_Web());

//don't want the slave server on the install
$override_config = array();
$override_config['SlaveServer']['servername'] = '';

vB::sensitiveConfigOverride($override_config);

// initialize the data registry
// We need to signal get_registry() to not try to fetch options from a possibly empty database (e.g. new install)

try
{
	$vbulletin = vB::get_registry(true);
}
catch(vB_Exception_Database $e)
{
	//if we wanted to skip the connection we'll handle a bad connection
	//later so long as we have a minimal object structure set up.
	if (defined('SKIPDB'))
	{
		//don't call through the vB object, it isn't set correctly there
		//due to the exception
		$vbulletin->db = vB_Db_Assertor::instance()->getDbConnection();
	}
	else
	{
		throw $e;
	}
}

$vb5_config =& vB::getConfig();

/* This option isnt in the core config
if ($vb5_config['report_all_php_errors'])
{
	// try to force display_errors on
	@ini_set('display_errors', true);
}
*/

// Load Phrases
$phrases = vB_Upgrade::fetch_language();

if (!defined('VB_AREA') AND !defined('THIS_SCRIPT'))
{
	echo $phrases['core']['VB_AREA_not_defined'];
	exit;
}

if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS']))
{
	echo $phrases['core']['request_tainting_attempted'];
	exit;
}

/*
	During vB::get_registry(), the vB class loads the config file, passes in sensitive DB data into the
	assertor creator, and scrubs the DB data from the global config.
	The assertor will die during construction if the config has an unsupported Database.dbtype value set,
	so the redundant check in this file has been removed.
 */
$db =& $vbulletin->db; // instance of vB_Database_MySQLi()

$db->appshortname = 'vBulletin (' . VB_AREA . ')';

// make $db a member of $vbulletin
//$vbulletin->db =& $db; done by get_registry()

if (!defined('SKIPDB'))
{
	//we're already connecting before now.
	// we do not want to use the slave server at all during this process
	// as latency problems may occur
//	$override_config = array();
//	$override_config['SlaveServer']['servername'] = '';
	// make database connection
//	$db->connect_using_dbconfig($override_config);

	//30443 Right now the product doesn't work in strict mode at all.  Its silly to make people have to edit their
	//config to handle what appears to be a very common case (though the mysql docs say that no mode is the default)
	//we no longer use the force_sql_mode parameter, though if the app is fixed to handle strict mode then we
	//may wish to change the default again, in which case we should honor the force_sql_mode option.
	//added the force parameter
	//The same logic is in includes/init.php and should stay in sync.
	//if (!empty($vb5_config['Database']['force_sql_mode']))

	$force_sql_mode = !(vB::getDbAssertor()->getNoForceSqlMode());
	if ($force_sql_mode)
	{
		$db->force_sql_mode('');
	}

	// #############################################################################
	// fetch options and other data from the datastore

	// grab the MySQL Version once and let every script use it.
	$mysqlversion = $db->query_first("SELECT version() AS version");
	define('MYSQL_VERSION', $mysqlversion['version']);

	if (VB_AREA == 'Upgrade')
	{
		$optionstemp = false;

		$db->hide_errors();
		$optionstemp = $db->query_first("SELECT template FROM  " . TABLE_PREFIX . "template WHERE title = 'options' AND styleid = -1");
		$db->show_errors();

		// ## Found vB2 Options so use them...
		if ($optionstemp)
		{
			eval($optionstemp['template']);
			$vbulletin->options =& $vboptions;
			$vbulletin->versionnumber = $templateversion;
		}
		else
		{
			// we need our datastore table to be updated properly to function
			$db->hide_errors();
			$db->query_write("ALTER TABLE " . TABLE_PREFIX . "datastore ADD unserialize SMALLINT NOT NULL DEFAULT '2'");
			$db->show_errors();

			//$datastore_class = (!empty($vb5_config['Datastore']['class']) AND !defined('STDIN')) ? $vb5_config['Datastore']['class'] : 'vB_Datastore';

			//$vbulletin->datastore = new $datastore_class($vb5_config, vB::getDbAssertor()); // datastore is set as part of vB::get_registry();
			vB::getDatastore()->build_options(); // Refresh options, see VBV-4277
		}
	}
	else if (VB_AREA == 'Install')
	{
		// datastore is set as part of vB::get_registry();
		// load it up but don't actually call fetch, we need the ability to overwrite fields.
		//$datastore_class = (!empty($vb5_config['Datastore']['class']) AND !defined('STDIN')) ? $vb5_config['Datastore']['class'] : 'vB_Datastore';

		//$vbulletin->datastore = new $datastore_class($vb5_config, vB::getDbAssertor());
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87129 $
|| #######################################################################
\*=========================================================================*/
