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

class vB_Array
{

	protected static function arrayReplaceRecurse($array, $array1)
	{
		foreach ($array1 as $key => $value)
		{
			// create new key in $array, if it is empty or not an array
			if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
			{
				$array[$key] = array();
			}

			// overwrite the value in the base array
			if (is_array($value))
			{
				$value = self::arrayReplaceRecurse($array[$key], $value);
			}
			$array[$key] = $value;
		}
		return $array;
	}

	public static function arrayReplaceRecursive(array &$array, array &$array1)
	{
		// todo get rid of this. We don't support PHP 5.2- anymore.
		if (function_exists('array_replace_recursive'))
		{
			// For 5.3+
			return call_user_func_array('array_replace_recursive', func_get_args());
		}
		else
		{
			// Prior to 5.3
			// handle the arguments, merge one by one
			$args = func_get_args();
			$array = $args[0];
			if (!is_array($array))
			{
				return $array;
			}
			for ($i = 1; $i < count($args); $i++)
			{
				if (is_array($args[$i]))
				{
					$array = self::arrayReplaceRecurse($array, $args[$i]);
				}
			}
			return $array;
		}
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87129 $
|| #######################################################################
\*=========================================================================*/
