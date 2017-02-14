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
 * vB_Utilities
 *
 * @package vBApi
 * @access public
 */
class vB_Utilities
{
	public static function vbmkdir($path, $mode = 0777)
	{
		if (is_dir($path))
		{
			if (!(is_writable($path)))
			{
				@chmod($path, $mode);
			}
			return true;
		}
		else
		{
			$oldmask = @umask(0);
			$partialpath = dirname($path);

			if (!self::vbmkdir($partialpath, $mode))
			{
				return false;
			}
			else
			{
				return @mkdir($path, $mode);
			}
		}
	}

	// #############################################################################
	/**
	 * Converts shorthand string version of a size to bytes, 8M = 8388608
	 *
	 * @param	string			The value from ini_get that needs converted to bytes
	 *
	 * @return	integer			Value expanded to bytes
	 */
	public static function ini_size_to_bytes($value)
	{
		$value = trim($value);
		$retval = intval($value);

		switch(strtolower($value[strlen($value) - 1]))
		{
			case 'g':
				$retval *= 1024;
			/* break missing intentionally */
			case 'm':
				$retval *= 1024;
			/* break missing intentionally */
			case 'k':
				$retval *= 1024;
				break;
		}

		return $retval;
	}

	/**
	 * Generates a valid path and filename for a temp file. In the case
	 * of safe upload, this generates the filename, but not the file. In
	 * the case of tempnam(), the temp file is actually created.
	 *
	 * @param	string|int	Optional extra "entropy" for the md5 call, this would typically be an ID such as userid or avatarid, etc *for the current record* of whatever is being processed. If empty, it uses the *current user's* userid.
	 * @param	string		An optional prefix for the file name. Depending on OS and if tempnam is used, only the first 3 chars of this will be used.
	 * @param	string		An optional suffix for the file name, can be used to add a file extension if needed.
	 *
	 * @return	string|false	The path and filename of the temp file, or bool false if it failed.
	 */
	public static function getTmpFileName($entropy = '', $prefix = 'vb_', $suffix = '')
	{
		$options = vB::getDatastore()->getValue('options');

		if ($options['safeupload'])
		{
			if (empty($entropy))
			{
				$entropy = vB::getCurrentSession()->get('userid');
			}

			//it *usually* doesn't matter if we use the slash instead of the local OS seperator, but
			//if we pass the value to exec things can't go a bit wierd.
			$filename = $options['tmppath'] . DIRECTORY_SEPARATOR . $prefix . md5(uniqid(microtime()) . $entropy) . $suffix;
		}
		else
		{
			if (vB::getUserContext()->hasPermission('adminpermissions', 'cancontrolpanel'))
			{
				$filename = tempnam(self::getTmpDir(), $prefix);
			}
			else
			{
				$filename = @tempnam(self::getTmpDir(), $prefix);
			}

			if ($filename AND $suffix)
			{
				// tempnam doesn't support specifying a suffix
				unlink($filename);
				$filename = $filename . $suffix;
				touch($filename);
			}
		}

		return $filename;
	}

	/**
	 * Returns the temp directory that vBulletin should use.
	 *
	 * @return	string|false	Path to the temp directory, or false if ini_get failed.
	 */
	public static function getTmpDir()
	{
		$options = vB::getDatastore()->getValue('options');

		if ($options['safeupload'])
		{
			$path = $options['tmppath'];
		}
		else
		{
			$path = ini_get('upload_tmp_dir');
			if (!$path OR !is_writable($path))
			{
				$path = sys_get_temp_dir();
			}
		}

		return $path;
	}

	/**
	 * Returns a stack trace as a string
	 *
	 * @return	string	Stack trace
	 */
	public static function getStackTrace()
	{
		$trace = debug_backtrace();
		$trace_item_blank = array(
			'type' => '',
			'file' => '',
			'line' => '',
			'class' => '',
		);

		// rm 'core' from the end of DIR, since the path could be in core or presentation
		$dir = trim(DIR, '/\\');
		$dir = substr($dir, -4) == 'core' ? substr($dir, 0, -4) : $dir;
		$dir = trim($dir, '/\\');

		$traceString = '';
		foreach ($trace AS $index => $trace_item)
		{
			$trace_item += $trace_item_blank;

			if (in_array($trace_item['function'], array('require', 'require_once', 'include', 'include_once')))
			{
				// included files
				$param = array();
				foreach ($trace_item['args'] AS $arg)
				{
					$param[] = str_replace($dir, '[path]', $arg);
				}
				$param = implode(', ', $param);
			}
			else
			{
				// include some limited, strategic data on args
				$param = array();

				if (is_array($trace_item['args']))
				{
					foreach ($trace_item['args'] AS $arg)
					{
						$argType = gettype($arg);
						switch ($argType)
						{
							case 'integer':
							case 'double':
								$argVal = $arg;
								break;
							case 'string':
								$len = strlen($arg);
								$argVal = "'" . ($len > 30 ? substr($arg, 0, 25) . '[len:' . $len . ']' : $arg) . "'";
								break;
							case 'array':
								$argVal = 'array[len:' . count($arg) . ']';
								break;
							case 'boolean':
								$argVal = $arg ? 'true' : 'false';
								break;
							case 'object':
								$argVal = get_class($arg);
								break;
							case 'resource':
								$argVal = 'resource[type:' . get_resource_type($arg) . ']';
								break;
							default:
								$argVal = $argType;
								break;
						}
						$param[] = $argVal;
					}
				}
				$param = implode(', ', $param);
			}

			$trace_item['file'] = str_replace($dir, '[path]', $trace_item['file']);

			$traceString .= "#$index: " . $trace_item['class'] . $trace_item['type'] . $trace_item['function'] . "($param)" . ($trace_item['file'] ? ' called in ' . $trace_item['file'] . ' on line ' . $trace_item['line'] : '') . "\n";
		}

		// args may contain chars that need escaping
		$traceString = htmlspecialchars($traceString);

		return $traceString;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
