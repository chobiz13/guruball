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

/**
 *	@package vBUtility
 */

/**
 *	@package vBUtility
 */
class vB_Utility_Password_Algorithm_Blowfish extends vB_Utility_Password_Algorithm
{

	private static $initialized = false;

	private $cost;

	protected function __construct($scheme)
	{
		//handle some stuff that only needs to happen the first time we access this algorithm.
		//
		if (!self::$initialized)
		{
			//if password hash doesn't exists (php version 5.5 or lower) then include compatibility library
			if (!function_exists('password_hash'))
			{
				//library also does this check but will trigger an error instead of throwing an exception
				if(!function_exists('crypt'))
				{
					throw new vB_Utility_Password_Exception_SchemeNotSupported();
				}

				//the library requires a security fix that was made in 5.3.7.  Apparently there exist specific
				//distributions of prior versions that have the patched that fix, but the specific check for
				//fix is more involved and we need to keep people from causing themselves problems.
				if (version_compare(PHP_VERSION, '5.3.7', '<')) 
				{
					throw new vB_Utility_Password_Exception_SchemeNotSupported();
				}
				require_once(DIR . '/libraries/password_compat/lib/password.php');
			}
			self::$initialized = true;
		}

		//scheme specific init, need to do this every time because it could change.
		//this algorithm expects exactly one parameter
		$params = explode(':', $scheme);
		if (count($params) != 2)
		{
			throw new vB_Utility_Password_Exception_SchemeNotSupported();
		}

		$this->cost = (int) $params[1];
		parent::__construct($scheme);	
	}

	public function generateToken($password)
	{
		$options['cost'] = $this->cost;
		$hash = password_hash($password, PASSWORD_BCRYPT, $options);
		return $hash;
	}

	public function verifyPassword($password, $token)
	{
		//if the cost part of the token does not match what we expect then don't validate
		//this shouldn't happen under ordinary circumstances, but we need to make sure we
		//don't provide any avenues for attack. 
		list (,,$cost,) = explode('$', $token, 4);
		if($cost != $this->cost)
		{
			return false;
		}	

		return password_verify($password, $token);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
