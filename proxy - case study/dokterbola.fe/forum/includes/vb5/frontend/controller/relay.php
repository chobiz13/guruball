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

class vB5_Frontend_Controller_Relay extends vB5_Frontend_Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function admincp($file)
	{
		if ($file)
		{
			//the sizeof is due to the fact that there is code in the download that can
			//alter the php extension for nonstandard environments.  This will work with
			//any string that might replace php
			if (substr($file, -1 * strlen('.php')) != '.php')
			{
				$file = "$file.php";
			}
		}
		else
		{
			$file = "index.php";
		}

		$api = Api_InterfaceAbstract::instance();
		$api->relay('admincp/' . $file);
	}

	public function modcp($file)
	{
		if ($file)
		{
			//the sizeof is due to the fact that there is code in the download that can
			//alter the php extension for nonstandard environments.  This will work with
			//any string that might replace php
			if (substr($file, -1 * strlen('.php')) != '.php')
			{
				$file = "$file.php";
			}
		}
		else
		{
			$file = "index.php";
		}

		$api = Api_InterfaceAbstract::instance();
		$api->relay('modcp/' . $file);
	}

	public function legacy($file)
	{
		$api = Api_InterfaceAbstract::instance();
		$api->relay($file);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
