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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL);
ignore_user_abort(true);
chdir('./../');

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('NO_IMPORT_DOTS', true);
define('NOZIP', 1);
if (!defined('VB_AREA')) { define('VB_AREA', 'Upgrade'); }
if (!defined('VB_ENTRY')) { define('VB_ENTRY', 'upgrade.php'); }

require_once('./install/includes/language.php');

if (!function_exists('version_compare')
OR version_compare(PHP_VERSION, '5.0.0', '<'))
{
/* ## This check is here on purpose, do not remove it ##
This is because on PHP4 [or below] the code will apparently
die before it reaches the standard check on minimum version */
	echo PHP4_ERROR;
	exit;
}

// Save for later CLI Processing //
if (VB_AREA == 'Upgrade')
{
	if (!isset($cli))
	{
		$cli = array();
	}

	if (!empty($argv) AND count($argv) > 1)
	{
		$options = getopt('', array('version::', 'only::'));
		if (!empty($options['version']))
		{
			$cli['cliver'] = $options['version'];
			$cli['clionly'] = (isset($options['only']) AND (($options['only'] == 'y') OR ($options['only'] == 1))) ? 1 : 0;
		}
		else
		{
			$cli['cliver'] = (isset($argv[1])) ? trim($argv[1]) : 'xxx';
			$cli['clionly'] = (isset($argv[2]) AND ($argv[2] == 'y')) ? 1 : 0;
		}
	}
	else
	{
		$cli['cliver'] = 'xxx';
		$cli['clionly'] = 0;
	}
}

if(is_link(dirname($_SERVER["SCRIPT_FILENAME"])))
{
	$frontendConfigPath = dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"]))) . '/config.php';
	$backendConfigPath = dirname(dirname($_SERVER["SCRIPT_FILENAME"])) . '/includes/config.php';
}
else
{
	$frontendConfigPath = dirname(__FILE__) . '/../../config.php';
	$backendConfigPath = dirname(__FILE__) . '/../includes/config.php';
}

$makeConfigPath = dirname(__FILE__) . '/makeconfig.php';
// Only if we don't have one of the files
if (file_exists($makeConfigPath) AND (!file_exists($frontendConfigPath) OR !file_exists($backendConfigPath)))
{
	require_once('./install/makeconfig.php');
	exit;
}

// ########################## REQUIRE BACK-END ############################
require_once('./install/includes/class_upgrade.php');
require_once('./install/init.php');
require_once(DIR . '/includes/functions.php');
require_once(DIR . '/includes/functions_misc.php');

if (VB_AREA == 'Upgrade')
{
	$db->hide_errors();
	$db->query_first("SELECT * FROM " . TABLE_PREFIX . "user LIMIT 1");
	if ($db->errno())
	{
		if (!vB_Upgrade::isCLI())
		{
			exec_header_redirect('install.php');
		}
		else
		{
			echo $phrases['upgrade']['no_database_found'] . "\n";
			exit;
		}
	}
}

$vbulletin->cli =& $cli;
if (function_exists('set_time_limit') AND !SAFEMODE)
{
	@set_time_limit(0);
}

// install/upgrader need vB_Cache_Null implementation
$vb5_config =& vB::getConfig();
if (!isset($vb5_config['Cache']) OR !isset($vb5_config['Cache']['class']) OR !is_array($vb5_config['Cache']['class']))
{
	$vb5_config['Cache']['class'] = array('vB_Cache_Null', 'vB_Cache_Null', 'vB_Cache_Null');
}

$cache = $vb5_config['Cache']['class'];
foreach ($cache AS $key => $class)
{
	// backup the original class so we can revert this change when required (see class_upgrade_final)
	$vb5_config['Backup']['Cache']['class'][$key] = $class;
	$vb5_config['Cache']['class'][$key] = 'vB_Cache_Null';
}

// Reset all cache types
vB_Cache::resetAllCache();

$verify =& vB_Upgrade::fetch_library($vbulletin, $phrases, '', !defined('VBINSTALL'));

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86842 $
|| #######################################################################
\*=========================================================================*/
