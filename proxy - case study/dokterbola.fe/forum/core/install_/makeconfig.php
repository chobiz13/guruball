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

error_reporting(E_ALL & ~E_NOTICE);

define('VERSION', '5.2.3');
define('THIS_SCRIPT', 'makeconfig.php');
define('VB_AREA', 'tools');
define('VB_ENTRY', 1);

if (strlen('be8498c371158ece3c593f4c56fefc6b') == 32)
{
	/**
	* @ignore
	*/
	define('CUSTOMER_NUMBER', 'be8498c371158ece3c593f4c56fefc6b');
}
else
{
	/**
	* @ignore
	*/
	define('CUSTOMER_NUMBER', md5(strtoupper('be8498c371158ece3c593f4c56fefc6b')));
}

$core = realpath(dirname(__FILE__) . '/../');
if (file_exists($core . '/includes/init.php'))
{ // need to go up a single directory, we must be in includes / admincp / modcp / install
	chdir($core);
}
else
{
	die('Please place this file within the "core/admincp" / "core/install" folder');
}

// define current directory
if (!defined('CWD'))
{
	define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));
}
if (!class_exists('vB')) {
	require_once(CWD . '/vb/vb.php');
}
vB::init();

$type = vB::getCleaner()->clean($_REQUEST['type'], vB_Cleaner::TYPE_STR);
$customerid = vB::getCleaner()->clean($_POST['customerid'], vB_Cleaner::TYPE_STR);
$bbcustomerid = vB::getCleaner()->clean($_COOKIE['bbcustomerid'], vB_Cleaner::TYPE_STR);

// #############################################################################
if ($_POST['do'] == 'login')
{
	if (md5(strtoupper($customerid)) == CUSTOMER_NUMBER)
	{
		setcookie('bbcustomerid', md5(strtoupper($customerid)), 0, '/', '');
		$bbcustomerid = CUSTOMER_NUMBER;
		$_REQUEST['do'] = '';
	}
}

// #############################################################################
if ($bbcustomerid !== CUSTOMER_NUMBER)
{
	// set the style folder
	if (empty($options['cpstylefolder']))
	{
		$options['cpstylefolder'] = 'vBulletin_5_Default';
	}

	if (empty($options['cpstyleimageext']))
	{
		$options['cpstyleimageext'] = 'png';
	}

	// set the version
	$options['templateversion'] = VERSION;


	/*
		We're not guaranteed to be at a .../core/install URL, so the image links will
		break.

		For instance, they might just in the root index, and have been redirected to this.

		Having the "first page" show a broken image is bad mojo, so although some browsers
		may not render it, let's go with a base64 encoded data URI
	 */
	$options['cp_logo_img_src'] = '../cpstyles/' . $options['cpstylefolder'] . '/cp_logo.' . $options['cpstyleimageext'];

	$image =  dirname(__FILE__) . '/../cpstyles/' . $options['cpstylefolder'] . '/cp_logo.' . $options['cpstyleimageext'];
	$imageData = false;
	$imageData = base64_encode(file_get_contents($image));
	if (!empty($imageData) AND function_exists('mime_content_type'))
	{
		$options['cp_logo_img_src'] = 'data: ' . mime_content_type($image) . ';base64,'.$imageData;
	}

	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>VB5 - Build Configuration</title>
		<style type="text/css">
			body,td,th {
				color: #000;
			}
			body {
				background-color: #DDD;
			}
			a:link {
				color: #00F;
			}
			a:visited {
				color: #00F;
			}
			a:hover {
				color: #F00;
			}
			a:active {
				color: #F00;
			}
			.maindiv {
				width:800px;
				border-width:1px;
				border-color: white;
				background-color:white;
				margin-left:auto;
				margin-right:auto;
				padding:4px;
				font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
				font-size: 10pt;
			}
			.infobar {
				background-color:rgb(1,55,79);
				color:white;
				font-size:150%;
				margin-top:15px;
				margin-right:-2px;
				margin-left:-2px;
				padding:4px;
			}
			.maincontent {
				padding:4px;
			}
			.alt2 {
				background-color: #DFE6E6;
			}
			.maintable {
				border-collapse:collapse;
				border-spacing: 1px;
				width:100%;
				border: 2px rgb(1,55,79) inset;
			}
			td {
				padding:4px;
			}
			.settingname {
				font-size: 12px;
				font-weight: bold;
			}
			.settingdesc {
				font-size: 11px;
			}
			.filewarning {
				font-weight: bold;
				color: #F00;
			}
		</style>
	</head>
	<body>
		<form action="?do=login" method="post">
		<input type="hidden" name="do" value="login" />
		<p>&nbsp;</p><p>&nbsp;</p>
		<table class="tborder" cellpadding="0" cellspacing="0" border="0" width="450" align="center"><tr><td>

			<!-- header -->
			<div class="tcat" style="text-align:center"><b>Enter Customer Number</b></div>
			<!-- /header -->

			<!-- logo and version -->
			<table cellpadding="4" cellspacing="0" border="0" width="100%" class="login-logo navbody">
			<tr valign="bottom">
				<td><img src="<?php echo $options['cp_logo_img_src']; ?>" alt="" border="0" /></td>
				<td>
					<b><a href="../"><?php echo $options['bbtitle']; ?></a></b><br />
					<?php echo 'vBulletin ' . $options['templateversion'] . ' Tools'; ?><br />
					&nbsp;
				</td>
			</tr>
			</table>
			<!-- /logo and version -->

			<table cellpadding="4" cellspacing="0" border="0" width="100%" class="alt1 logincontrols">
			<col width="50%" style="text-align:right; white-space:nowrap"></col>
			<col></col>
			<col width="50%"></col>
			<!-- login fields -->
			<tr valign="top">
				<td>&nbsp;<br />Customer Number<br />&nbsp;</td>
				<td class="smallfont"><input type="text" style="padding-left:5px; font-weight:bold; width:250px" name="customerid" value="" tabindex="1" /><br />This is the number with which you log in to the vBulletin.com Members&#8217; Area</td>
				<td>&nbsp;</td>
			</tr>
			<!-- /login fields -->
			<!-- submit row -->
			<tr>
				<td colspan="3" align="center">
					<input type="submit" class="button" value="Enter System" accesskey="s" tabindex="3" />
				</td>
			</tr>
			<!-- /submit row -->
			</table>
		</td></tr></table>
		</form>
	</body>
</html>
	<?php

	unset($debug, $GLOBALS['DEVDEBUG']);
	exit;
}

$basePath = dirname(__FILE__) . str_repeat(DIRECTORY_SEPARATOR . '..', 2);
$makeConfig = array(
	'frontend' => array(
		'source' => realpath(implode(DIRECTORY_SEPARATOR, array($basePath, 'config.php.bkp'))),
		'dest' => realpath($basePath) . '/config.php',
		'fields' => array(
			'cookie_prefix' => array('name' => 'Cookie Prefix:', 'description' => 'Default: bb'),
		),
	),
	'backend' => array(
		'source' => realpath(implode(DIRECTORY_SEPARATOR, array($basePath, 'core', 'includes', 'config.php.new'))),
		'dest' => realpath(implode(DIRECTORY_SEPARATOR, array($basePath, 'core', 'includes'))) . '/config.php',
		'fields' => array(
			'Database|dbtype' => array('name' => 'Database Type:', 'description' => 'Default: mysqli'),
			'Database|dbname' => array('name' => 'Database Name:', 'description' => 'Enter your database name'),
			'Database|tableprefix' => array('name' => 'Table Prefix:', 'description' => 'Optional Table Prefix (OK to leave blank.)'),
			'Database|technicalemail' => array('name' => 'Technical Email:', 'description' => 'Database errors will be emailed to this address'),
			'MasterServer|servername' => array('name' => 'Database Server Name:', 'description' => 'The server name of your database server'),
			'MasterServer|port' => array('name' => 'Database Port #:', 'description' => 'Port of database server'),
			'MasterServer|username' => array('name' => 'Database Username:', 'description' => 'Username to log into database server'),
			'MasterServer|password' => array('type' => 'password', 'name' => 'Database Password:', 'description' => 'Password for database username (no single-quotes allowed)'),
			'Misc|modcpdir' => array('name' => 'Mod CP Directory:', 'description' => 'Default: modcp'),
			'cookie_prefix' => array('path' => 'Misc|cookieprefix', 'name' => 'Cookie Prefix:', 'description' => 'Default: bb'),
		)
	),
);


if (isset($_REQUEST['submit']) AND $_REQUEST['submit'] == 'Create Files')
{
	$errors = array();
	foreach ($makeConfig AS $component => $componentInfo)
	{
		$configContent = file_get_contents($componentInfo['source']);
		if ($configContent == false)
		{
			die("Error - Could not open $component config file.");
		}

		foreach ($componentInfo['fields'] AS $field => $fieldInfo)
		{
			$fieldPath = isset($fieldInfo['path']) ? $fieldInfo['path'] : $field;

			$find = '/^\$config\[\'' . implode("'\]\['", explode('|', $field)) . "'\].*$/m";
			$replace = '$config[\'' . implode("']['", explode('|', $field)) . "'] = '{$_POST[$field]}';" . PHP_EOL;

			$configContent = preg_replace($find, $replace, $configContent);
		}

		if (!file_put_contents($componentInfo['dest'], $configContent))
		{
			$errors[] = 'Was not able to write to the file ' . $componentInfo['dest'] . '. Please check your write permissions and try again.';
		}
	}

	if (empty($errors))
	{
		$selfdelete = '<br /><br />Click this <a href="makeconfig.php?submit=self_delete">link</a> to automatically delete makeconfig.php and begin the install script. Otherwise close this page. <br />';

		die("<br />File Creation Complete.<br /><br />Delete <strong>makeconfig.php</strong> file now, then begin installation.$selfdelete");
	}
	else
	{
		die('<br />There was an error:<ul><li>' . implode('</li><li>', $errors) . '</li></ul><br />Please <a href="makeconfig.php">go back</a> and try again.');
	}
}
elseif (isset($_REQUEST['submit']) AND $_REQUEST['submit'] == 'self_delete')
{
	$current_path=realpath(dirname(__FILE__));
	if (unlink( $current_path . '/makeconfig.php'))
	{
		if (file_exists(($makeConfig['frontend']['dest'])))
		{
			require_once($makeConfig['frontend']['dest']);
			header("location: {$config['baseurl_core']}/install/install.php");
		}
		else
		{
			$install_path = (empty($_SERVER['HTTPS']) ? "http://" : "https://") . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			$install_path = str_replace('makeconfig.php', 'install.php', $install_path);
			header("location: {$install_path}");
		}
		exit;
	}
	else
	{
		$url = (empty($_SERVER['HTTPS']) ? "http://" : "https://") . $_SERVER['SERVER_NAME'];
		die('Self-delete failed. Please delete makeconfig.php manually.<br/ >It is not recommended to continue with installation without removing makeconfig.php first. <br />If you plan on removing the script manually later, you can continue the installation by <a href="' . $url . '/install/install.php">clicking here.</a><br />');
	}
}

// Prepare to load and display config info
$config = $fields = array();
$caution = '';
foreach ($makeConfig AS $component => $componentInfo)
{
	if (file_exists($componentInfo['source']))
	{
		// load default values
		require_once($componentInfo['source']);
	}

	if (file_exists($componentInfo['dest']))
	{
		$caution .= "Warning $component already exists. <br />";

		// load existing values
		require_once($componentInfo['dest']);
	}

	foreach ($componentInfo['fields'] AS $field => $info)
	{
		$info['class'] = (count($fields) % 2) ? '' : 'alt2';

		if (!isset($info['type']))
		{
			$info['type'] = 'text';
		}

		$info['value'] = '';
		$fields[$field] = isset($fields[$field]) ? array_merge($info, $fields[$field]) : $info;
	}
}

function fetchCurrentConfigValue($fieldName, $config)
{
	if (empty($config))
	{
		return '';
	}
	else
	{
		$tmp = $config;
		$field_path = explode('|', $fieldName);
		foreach ($field_path as $p)
		{
			if (isset($tmp[$p]))
				if (is_array($tmp[$p]))
				{
					$tmp = $tmp[$p];
				}
				else
				{
					return $tmp[$p];
				}
			else
			{
				return '';
			}
		}
	}
}


/*
	See note above for $options['cp_logo_img_src']
 */
$options['vb5_logo_img_src'] = '../../images/misc/vbulletin5_logo.png';

$image =  dirname(__FILE__) . '/../../images/misc/vbulletin5_logo.png';
$imageData = false;
$imageData = base64_encode(file_get_contents($image));
if (!empty($imageData) AND function_exists('mime_content_type'))
{
	$options['vb5_logo_img_src'] = 'data: ' . mime_content_type($image) . ';base64,'.$imageData;
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>VB5 - Build Configuration</title>
		<style type="text/css">
			body,td,th {
				color: #000;
			}
			body {
				background-color: #DDD;
			}
			a:link {
				color: #00F;
			}
			a:visited {
				color: #00F;
			}
			a:hover {
				color: #F00;
			}
			a:active {
				color: #F00;
			}
			.maindiv {
				width:800px;
				border-width:1px;
				border-color: white;
				background-color:white;
				margin-left:auto;
				margin-right:auto;
				padding:4px;
				font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
				font-size: 10pt;
			}
			.infobar {
				background-color:rgb(1,55,79);
				color:white;
				font-size:150%;
				margin-top:15px;
				margin-right:-2px;
				margin-left:-2px;
				padding:4px;
			}
			.maincontent {
				padding:4px;
			}
			.alt2 {
				background-color: #DFE6E6;
			}
			.maintable {
				border-collapse:collapse;
				border-spacing: 1px;
				width:100%;
				border: 2px rgb(1,55,79) inset;
			}
			td {
				padding:4px;
			}
			.settingname {
				font-size: 12px;
				font-weight: bold;
			}
			.settingdesc {
				font-size: 11px;
			}
			.filewarning {
				font-weight: bold;
				color: #F00;
			}
		</style>
		<script language="javascript">
			function valForm()
			{
				if (document.forms['buildform'].admincp.value != 'admincp')
					alert("Reminder: You must manually rename the admincp directories to match your custom value.");
				if (document.forms['buildform'].modcp.value != 'modcp')
					alert("Reminder: You must manually rename the modcp directory to match your custom value.");
				if (!document.forms['buildform'].dbname.value)
				{
					alert ("You must enter a Database Name");
					return false;
				}
				if (!document.forms['buildform'].technicalemail.value)
				{
					var echeck = confirm("If you do not enter an email address you will not be notified of database errors. Support will require a copy of any database error if you run into trouble. Press cancel if you want to enter an email address, otherwise press OK to continue.");
					if (echeck == false)
						return false;
				}
				if (!document.forms['buildform'].servername.value)
				{
					alert ("You must enter a Database Server Name");
					return false;
				}
				if (!document.forms['buildform'].username.value)
				{
					alert ("You must enter a Database Username");
					return false;
				}
				if (document.forms['buildform'].password.value.indexOf("'") != -1)
				{
					alert ("Passwords cannot contain the single-quote character (')");
					return false;
				}
			}
		</script>
	</head>
	<body onload="updatecore()">

		<div class="maindiv">
			<img src="<?php echo $options['vb5_logo_img_src']; ?>" width="171" height="42" alt="vBulletin 5" /> <br />
			<div class="infobar">
				vBulletin 5 Configuration Builder
			</div>
			<br />
			<div class="maincontent">
				<form action="" method="post" name="buildform" onsubmit="return valForm()" >
					<p>Please fill out the following fields to build your configuration data. This utility will auto-create a config.php file in the base directory and in your core/includes/ directory.</p>
					<p>If you require any advanced settings you must manually edit the config.php files yourself. See the install instructions for help.<br />
						<br />
						<span class="filewarning"><?php echo $caution ?></span> </p>
					<table border="1" class="maintable">
						<?php foreach ($fields AS $fieldName => $info): ?>
							<tr class="<?php echo $info['class'] ?>">
								<td width="50%">
									<span class="settingname"><?php echo $info['name'] ?></span><br />
									<span class="settingdesc"><?php echo $info['description'] ?></span>
								</td>
								<td><input type="<?php echo $info['type'] ?>" name="<?php echo $fieldName ?>" id="<?php echo $fieldName ?>" size="50" value="<?php echo $info['value'] ?>" /></td>
							</tr>
						<?php endforeach; ?>
					</table>
					<br />
					<div align="center">
						<input name="submit" type="submit" value="Create Files" /> &nbsp; <input name="reset" type="reset" value="Reset" />
					</div>

				</form>
			</div>
		</div>

	</body>
</html><?php

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87269 $
|| #######################################################################
\*=========================================================================*/
