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

// #############################################################################
/**
* Essentially a wrapper for the ternary operator.
*
* @deprecated	Deprecated as of 3.5. Use the ternary operator.
*
* @param	string	Expression to be evaluated
* @param	mixed	Return this if the expression evaluates to true
* @param	mixed	Return this if the expression evaluates to false
*
* @return	mixed	Either the second or third parameter of this function
*/
function iif($expression, $returntrue, $returnfalse = '')
{
	return ($expression ? $returntrue : $returnfalse);
}

// #############################################################################
/**
* Converts shorthand string version of a size to bytes, 8M = 8388608
*
* @param	string			The value from ini_get that needs converted to bytes
*
* @return	integer			Value expanded to bytes
*/
function ini_size_to_bytes($value)
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

// #############################################################################
/**
* Class factory. This is used for instantiating the extended classes.
*
* @param	string			The type of the class to be called (user, forum etc.)
* @param	vB_Registry	Unused.  Pass as NULL
* @param	integer			One of the ERRTYPE_x constants
* @param	string			Option to force loading a class from a specific file; no extension
*
* @return	vB_DataManager	An instance of the desired class
*/
function &datamanager_init($classtype, &$registry, $errtype = vB_DataManager_Constants::ERRTYPE_STANDARD, $forcefile = '')
{
	if (preg_match('#^\w+$#', $classtype))
	{
		$classtype = strtolower($classtype);
		if ($forcefile)
		{
			$classfile = preg_replace('#[^a-z0-9_]#i', '', $forcefile);
		}
		else
		{
			$classfile = str_replace('_multiple', '', $classtype);
		}
		$classname = 'vB_DataManager_' . $classtype;
		$object = new $classname($errtype);
		return $object;
	}
}

// #############################################################################
/**
* Converts A-Z to a-z, doesn't change any other characters
*
* @deprecated
* @see vB_String::vBStrToLower()
* @param	string	String to convert to lowercase
*
* @return	string	Lowercase string
*/
function vbstrtolower($string)
{
	return vB_String::vBStrToLower($string, false);
}

// #############################################################################
/**
* Splits a string into individual words for use in the search index
*
* @param	string	String to be seperated into individual words
*
* @return	array	Array of words based on the input string
*/
function split_string($string)
{
	switch (vB_Template_Runtime::fetchStyleVar('charset'))
	{
		case 'big5':
			preg_match_all('#((?:[A-Za-z]+)|(?:[\xa1-\xfe][\x40-\x7e]|[\xa1-\xfe]))#xs', $string, $matches);
			if (!$matches)
			{
				return array();
			}
			return $matches[0];
		break;
		default:
			return explode(' ', $string);
	}
}

// #############################################################################
/**
* Use vB_String::vbStrlen()
* Attempts to do a character-based strlen on data that might contain HTML entities.
* By default, it only converts numeric entities but can optional convert &quot;,
* &lt;, etc. Uses a multi-byte aware function to do the counting if available.
*
* @deprecated
* @see vB_String::vbStrlen()
* @param	string	String to be measured
* @param	boolean	If true, run unhtmlspecialchars on string to count &quot; as one, etc.
*
* @return	integer	Length of string
*/
function vbstrlen($string, $unhtmlspecialchars = false)
{
	return vB_String::vbStrlen($string, $unhtmlspecialchars);
}

/**
* Chops off a string at a specific length, counting entities as once character
* and using multibyte-safe functions if available.
*
* @deprecated
* @see vB_String::vbChop()
* @param	string	String to chop
* @param	integer	Number of characters to chop at
*
* @return	string	Chopped string
*/
function vbchop($string, $length)
{
	return vB_String::vbChop($string, $length);
}

// #############################################################################
/**
 * Transliterates non ASCII chars to ASCII.
 * This is an approximation.
 *
 * Note: Performance and accuracy is gained if the pecl translit extension is available.
 * @see http://pecl.php.net/package/translit
 *
 * @param	string String to transliterate
 * @return	string
 */
function to_ascii($str)
{
	if (!$str)
    {
    	return;
    }

    if (function_exists('transliterate'))
    {
    	return transliterate($str, array('normalize_ligature'), 'ISO-8859-1', 'ISO-8859-1');
    }

	static $lookup = array(
		'&Agrave;' => 'A',
		'&Aacute;' => 'A',
		'&Acirc;' => 'A',
		'&Atilde;' => 'A',
		'&Auml;' => 'AE',
		'&Aring;' => 'A',
		'&AElig;' => 'AE',
		'&Ccedil;' => 'C',
		'&Egrave;' => 'E',
		'&Eacute;' => 'E',
		'&Ecirc;' => 'E',
		'&Euml;' => 'E',
		'&Igrave;' => 'I',
		'&Iacute;' => 'I',
		'&Icirc;' => 'I',
		'&Iuml;' => 'I',
		'&ETH;' => 'Dj',
		'&Ntilde;' => 'N',
		'&Ograve;' => 'O',
		'&Oacute;' => 'O',
		'&Ocirc;' => 'O',
		'&Otilde;' => 'O',
		'&Ouml;' => 'OE',
		'&Oslash;' => 'U',
		'&Ugrave;' => 'U',
		'&Uacute;' => 'U',
		'&Ucirc;' => 'U',
		'&Uuml;' => 'UE',
		'&Yacute;' => 'Y',
		'&THORN;' => 'Th',
		'&szlig;' => 'ss',
		'&agrave;' => 'a',
		'&aacute;' => 'a',
		'&acirc;' => 'a',
		'&atilde;' => 'a',
		'&auml;' => 'ae',
		'&aring;' => 'a',
		'&aelig;' => 'ae',
		'&ccedil;' => 'c',
		'&egrave;' => 'e',
		'&eacute;' => 'e',
		'&ecirc;' => 'e',
		'&euml;' => 'e',
		'&igrave;' => 'i',
		'&iacute;' => 'i',
		'&icirc;' => 'i',
		'&iuml;' => 'i',
		'&eth;' => 'dj',
		'&ntilde;' => 'n',
		'&ograve;' => 'o',
		'&oacute;' => 'o',
		'&ocirc;' => 'o',
		'&otilde;' => 'o',
		'&ouml;' => 'oe',
		'&oslash;' => 'o',
		'&ugrave;' => 'u',
		'&uacute;' => 'u',
		'&ucirc;' => 'u',
		'&uuml;' => 'ue',
		'&yacute;' => 'y',
		'&thorn;' => 'th',
		'&yuml;' => 'y'
	);

    $str = htmlentities($str);
    $str = str_replace(array_keys($lookup), array_values($lookup), $str);
    $str = html_entity_decode($str);
    $str = preg_replace('#[^a-z0-9]+#i', '-', $str);

    return $str;
}

// #############################################################################
/**
* Formats a number with user's own decimal and thousands chars
*
* @param	mixed	Number to be formatted: integer / 8MB / 16 GB / 6.0 KB / 3M / 5K / ETC
* @param	integer	Number of decimal places to display
* @param	boolean	Special case for byte-based numbers
*
* @return	mixed	The formatted number
*/
function vb_number_format($number, $decimals = 0, $bytesize = false, $decimalsep = null, $thousandsep = null)
{
	global $vbulletin;

	if (defined('VB_API') AND VB_API === true)
	{
		// The number format of API should always be standard
		$decimalsep = '.';
		$thousandsep = '';
	}

	$type = '';

	if (empty($number))
	{
		return 0;
	}
	else if (preg_match('#^(\d+(?:\.\d+)?)(?>\s*)([mkg])b?$#i', trim($number), $matches))
	{
		switch(strtolower($matches[2]))
		{
			case 'g':
				$number = $matches[1] * 1073741824;
				break;
			case 'm':
				$number = $matches[1] * 1048576;
				break;
			case 'k':
				$number = $matches[1] * 1024;
				break;
			default:
				$number = $matches[1] * 1;
		}
	}

	if ($bytesize)
	{
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch(array('gigabytes', 'megabytes', 'kilobytes', 'bytes'));

		if ($number >= 1073741824)
		{
			$number = $number / 1073741824;
			$decimals = 2;
			$type = " $vbphrase[gigabytes]";
		}
		else if ($number >= 1048576)
		{
			$number = $number / 1048576;
			$decimals = 2;
			$type = " $vbphrase[megabytes]";
		}
		else if ($number >= 1024)
		{
			$number = $number / 1024;
			$decimals = 1;
			$type = " $vbphrase[kilobytes]";
		}
		else
		{
			$decimals = 0;
			$type = " $vbphrase[bytes]";
		}
	}

	if ($decimalsep === null)
	{
		$decimalsep = $vbulletin->userinfo['lang_decimalsep'];
	}
	if ($thousandsep === null)
	{
		$thousandsep = $vbulletin->userinfo['lang_thousandsep'];
	}

	return str_replace('_', '&nbsp;', number_format($number, $decimals, $decimalsep, $thousandsep)) . $type;
}

// #############################################################################
/**
* Generates a random password that is much stronger than what we currently use.
*
* @param	integer	Length of desired password
* @deprecated.  Use vB_Library_Login::resetPassword
*/
function fetch_random_password($length = 8)
{
	$password_characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
	$total_password_characters = strlen($password_characters) - 1;

	$digit = vbrand(0, $length - 1);

	$newpassword = '';
	for ($i = 0; $i < $length; $i++)
	{
		if ($i == $digit)
		{
			$newpassword .= chr(vbrand(48, 57));
			continue;
		}

		$newpassword .= $password_characters{vbrand(0, $total_password_characters)};
	}
	return $newpassword;
}

// #############################################################################
/**
* Generates a random string of alphanumeric characters
*
* @param	integer	Length of desired string
*/
function fetch_random_alphanumeric($length = 8)
{
	if ($length <= 0 OR !is_int($length))
	{
		throw new Exception("Length must be a positive integer.");
	}
	require_once(DIR . '/libraries/random_compat/lib/random.php');
	$characters =  'ABCDEFGHIJKLMNOPQRSTUVWXYZ' .
				   'abcdefghijklmnopqrstuvwxyz' .
				   '0123456789';
	$min = 0;
	$max = strlen($characters) - 1;
	/*
		62 possible characters, 62^$length possible permutations.
		Compared to a substr(sha1(), 0, $length), which would be  (16 possible characters) ^ $lenth permutations,
		this is a larger pool. But in the context of a brute force attack, this isn't that much better.
		Be sure to have ways to limit the rate of attack if the result is used for security purposes.
		Usage for nonces should be fine.
	 */

	$output = '';
	for ($i = 0; $i < $length; $i++)
	{
		$output .= $characters{random_int($min, $max)};
	}
	return $output;
}

// #############################################################################
/**
* Approximation of old fetch_random_string() function in terms of output set, not distribution.
*
* @param	integer	Length of desired hash
*/
function fetch_random_hex($length = 32)
{
	if ($length <= 0 OR !is_int($length))
	{
		throw new Exception("Length must be a positive integer.");
	}
	/*
		If we just want to use a hash function like sha1 or md5, we can use
		random_bytes({some length}) to generate a seed for the hash.
		Old function returned a substr of a sha1() of a weak seed.
		I do not want to return a substr of a hash function result, and we want
		to use a stronger seed. Since sha1() returns a hex, the output should be
		a $length character hexadecimal number.
		Each byte will give us 2 characters of hex. We still need to substr
		if the desired length is odd.

		BEWARE THAT random_bytes() COULD GIVE YOU UNPRINTABLE CHARACTERS IN WHATEVER
		ENCODING YOU'RE HOPING TO USE. If you need to use the string as more than a hash seed,
		convert it to something safe via bin2hex(), base64encode(), etc.
		For further reading: http://haacked.com/archive/2012/01/30/hazards-of-converting-binary-data-to-a-string.aspx/
	 */
	require_once(DIR . '/libraries/random_compat/lib/random.php');
	$bytes = ceil($length/2);
	$printable_hex = bin2hex(random_bytes($bytes));
	$digits = strlen($printable_hex);
	if ($digits < $length)
	{
		// I don't think this will ever happen unless for some reason bin2hex goes batshit and starts
		// returning single digits instead of 0x0[0-f] for bytes starting with 0b0000
		throw new Exception('Unexpected error: Generated hex was shorter than expected');
	}
	else if ($digits > $length)
	{
		// This happens every time $length is odd.
		$printable_hex = substr($printable_hex, 0, $length);
	}

	return $printable_hex;
}

// #############################################################################
/**
* vBulletin's hash fetcher, note this may change from a-f0-9 to a-z0-9 in future.
*
* @param	integer	Length of desired hash
*/
function fetch_random_string($length = 32)
{
	return fetch_random_hex($length);
}

// #############################################################################
/**
* vBulletin's own random number generator
*
* @param	integer	Minimum desired value
* @param	integer	Maximum desired value
* @param	mixed Param is not used.
*/
function vbrand($min = 0, $max = 0, $seed = -1)
{
	mt_srand(crc32(microtime()));

	if ($max AND $max <= mt_getrandmax())
	{
		$number = mt_rand($min, $max);
	}
	else
	{
		$number = mt_rand();
	}
	// reseed so any calls outside this function don't get the second number
	mt_srand();

	return $number;
}

// #############################################################################
/**
* Returns an array of usergroupids from all the usergroups to which a user belongs
*
* @param	array	User info array - must contain usergroupid and membergroupid fields
* @param	boolean	Whether or not to fetch the user's primary group as part of the returned array
*
* @return	array	Usergroup IDs to which the user belongs
*/
function fetch_membergroupids_array($user, $getprimary = true)
{
	if (!empty($user['membergroupids']))
	{
		$membergroups = explode(',', str_replace(' ', '', $user['membergroupids']));
	}
	else
	{
		$membergroups = array();
	}

	if ($getprimary)
	{
		$membergroups[] = $user['usergroupid'];
	}

	return array_unique($membergroups);
}

// #############################################################################
/**
* Works out if a user is a member of the specified usergroup(s)
*
* This function can be overloaded to test multiple usergroups: is_member_of($user, 1, 3, 4, 6...)
*
* @param	array	User info array - must contain userid, usergroupid and membergroupids fields
* @param	integer	Usergroup ID to test
* @param	boolean	Pull result from cache
*
* @return	boolean
*/
function is_member_of($userinfo, $usergroupid, $cache = true)
{
	static $user_memberships;

	switch (func_num_args())
	{
		// 1 can't happen

		case 2: // note: func_num_args doesn't count args with default values unless they're overridden
			$groups = is_array($usergroupid) ? $usergroupid : array($usergroupid);
		break;

		case 3:
			if (is_array($usergroupid))
			{
				$groups = $usergroupid;
				$cache = (bool)$cache;
			}
			else if (is_bool($cache))
			{
				// passed in 1 group and a cache state
				$groups = array($usergroupid);
			}
			else
			{
				// passed in 2 groups
				$groups = array($usergroupid, $cache);
				$cache = true;
			}
		break;

		default:
			// passed in 4+ args, which means it has to be in the 1,2,3 method
			$groups = func_get_args();
			unset($groups[0]);

			$cache = true;
	}

	if (!isset($user_memberships["$userinfo[userid]"]) OR !is_array($user_memberships["$userinfo[userid]"]) OR !$cache)
	{
		// fetch membergroup ids for this user
		$user_memberships["$userinfo[userid]"] = fetch_membergroupids_array($userinfo);
	}

	foreach ($groups AS $usergroupid)
	{
		// is current group user's primary usergroup, or one of their membergroups?
		if ($userinfo['usergroupid'] == $usergroupid OR in_array($usergroupid, $user_memberships["$userinfo[userid]"]))
		{
			// yes - return true
			return true;
		}
	}

	// if we get here then the user doesn't belong to any of the groups.
	return false;
}

// #############################################################################
/**
* Works out if the specified user is 'in Coventry'
*
* @param	integer	User ID
* @param	boolean	Whether or not to confirm that the visiting user is himself in Coventry or not
*
* @return	boolean
*/
function in_coventry($userid, $includeself = false)
{
	global $vbulletin;
	static $Coventry;

	// if user is guest, or user is bbuser, user is NOT in Coventry.
	if ($userid == 0 OR ($userid == $vbulletin->userinfo['userid'] AND $includeself == false))
	{
		return false;
	}

	if (!is_array($Coventry))
	{
		$options = vB::getDatastore()->get_value('options');
		if (trim($options['globalignore']) != '')
		{
			$Coventry = preg_split('#\s+#s', $options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$Coventry = array();
		}
	}

	// if Coventry is empty, user is not in Coventry
	if (empty($Coventry))
	{
		return false;
	}

	// return whether or not user's id is in Coventry
	return in_array($userid, $Coventry);
}


// #############################################################################
/**
* Replaces any non-printing ASCII characters with the specified string.
* This also supports removing Unicode characters automatically when
* the entered value is >255 or starts with a 'u'.
*
* @deprecated
* @see vB_String::stripBlankAscii()
* @param	string	Text to be processed
* @param	string	String with which to replace non-printing characters
*
* @return	string
*/
function strip_blank_ascii($text, $replace)
{
	return vB_String::stripBlankAscii($text, $replace);
}

// #############################################################################
/**
* Replaces any instances of words censored in $options['censorwords'] with $options['censorchar']
*
* @param	string	Text to be censored
*
* @return	string
*/
function fetch_censored_text($text)
{
	static $censorwords;
	$options = vB::getDatastore()->get_value('options');

	if (!$text)
	{
		// return $text rather than nothing, since this could be '' or 0
		return $text;
	}
	$options = vB::getDatastore()->get_value('options');

	if ($options['enablecensor'] AND !empty($options['censorwords']))
	{
		if (empty($censorwords))
		{
			$options['censorwords'] = preg_quote($options['censorwords'], '#');
			$censorwords = preg_split('#[ \r\n\t]+#', $options['censorwords'], -1, PREG_SPLIT_NO_EMPTY);
		}

		foreach ($censorwords AS $censorword)
		{
			if (substr($censorword, 0, 2) == '\\{')
			{
				if (substr($censorword, -2, 2) == '\\}')
				{
					// prevents errors from the replace if the { and } are mismatched
					$censorword = substr($censorword, 2, -2);
				}

				// ASCII character search 0-47, 58-64, 91-96, 123-127
				$nonword_chars = '\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f';

				// words are delimited by ASCII characters outside of A-Z, a-z and 0-9
				$text = preg_replace(
					'#(?<=[' . $nonword_chars . ']|^)' . $censorword . '(?=[' . $nonword_chars . ']|$)#si',
					str_repeat($options['censorchar'], vbstrlen($censorword)),
					$text
				);
			}
			else
			{
				$text = preg_replace("#$censorword#si", str_repeat($options['censorchar'], vbstrlen($censorword)), $text);
			}
		}
	}

	// strip any admin-specified blank ascii chars
	$text = strip_blank_ascii($text, $options['censorchar']);

	return $text;
}

// #############################################################################
/**
* Attempts to intelligently wrap excessively long strings onto multiple lines
*
* @param	string	Text to be wrapped
* @param	integer	If specified, max word wrap length
* @param	string	Text to insert at the wrap point
*
* @return	string
*/
function fetch_word_wrapped_string($text, $limit = false, $wraptext = ' ')
{
	global $vbulletin;

	if ($limit === false)
	{
		$options = vB::getDatastore()->get_value('options');
		$limit = $options['wordwrap'];
	}

	$limit = intval($limit);

	if ($limit > 0 AND !empty($text))
	{
		return preg_replace('
			#((?>[^\s&/<>"\\-\[\]]|&[\#a-z0-9]{1,7};){' . $limit . '})(?=[^\s&/<>"\\-\[\]]|&[\#a-z0-9]{1,7};)#i',
			'$0' . $wraptext,
			$text
		);
	}
	else
	{
		return $text;
	}
}

// #############################################################################
/**
* @deprecated Should use vB_String::fetchTrimmedTitle() [VBV-11107]
* Trims a string to the specified length while keeping whole words
*
* @param	string	String to be trimmed
* @param	integer	Number of characters to aim for in the trimmed string
* @param  boolean Append "..." to shortened text
*
* @return	string
*/
function fetch_trimmed_title($title, $chars = -1, $append = true)
{
	global $vbulletin;

	$options = vB::getDatastore()->get_value('options');
	if ($chars == -1)
	{
		$chars = $options['lastthreadchars'];
	}

	if ($chars)
	{
		// limit to 10 lines (\n{240}1234567890 does weird things to the thread preview)
		$titlearr = preg_split('#(\r\n|\n|\r)#', $title);
		$title = '';
		$i = 0;
		foreach ($titlearr AS $key)
		{
			$title .= "$key \n";
			$i++;
			if ($i >= 10)
			{
				break;
			}
		}
		$title = trim($title);
		unset($titlearr);

		if (vbstrlen($title) > $chars)
		{
			$title = vbchop($title, $chars);
			if (($pos = strrpos($title, ' ')) !== false)
			{
				$title = substr($title, 0, $pos);
			}
			if ($append)
			{
				$title .= '...';
			}
		}

		//$title = fetch_soft_break_string($title);
	}

	return $title;
}

// #############################################################################
/**
* Breaks up strings (typically URLs) semi-invisibly to avoid word-wrapping issues
*
* @param	string	Text to be broken
*
* @return	string
*/
function fetch_soft_break_string($string)
{
	// replace forward slashes and question marks not followed by digits or slashes with soft hyphens (&shy;)
	return preg_replace('#(/|\?)([^/\d])#s', '\1&shy;\2', $string);
}

// #############################################################################
/**
* Checks to see if the IP address of the visiting user is banned from visiting
*
* This function will show an error and halt execution if the IP is banned.
*/
function verify_ip_ban()
{
	/* Make sure we can login as the admin
	This old code is only called by ACP logins now */
	if (defined('THIS_SCRIPT') AND THIS_SCRIPT == 'login')
	{
		return;
	}

	global $vbulletin;

	$user_ipaddress = IPADDRESS . '.';
	$options = vB::getDatastore()->get_value('options');

	if ($options['enablebanning'] == 1 AND $options['banip'] = trim($options['banip']))
	{
		$addresses = preg_split('#\s+#', $options['banip'], -1, PREG_SPLIT_NO_EMPTY);
		foreach ($addresses AS $banned_ip)
		{
			if (strpos($banned_ip, '*') === false AND $banned_ip{strlen($banned_ip) - 1} != '.')
			{
				$banned_ip .= '.';
			}

			$banned_ip_regex = str_replace('\*', '(.*)', preg_quote($banned_ip, '#'));
			if (preg_match('#^' . $banned_ip_regex . '#U', $user_ipaddress))
			{
				eval(standard_error(fetch_error('banip')));
			}
		}
	}
}

// #############################################################################
/**
* Fetches the remaining characters in a filename after the final dot
*
* @param	string	The filename to test
*
* @return	string	The extension of the provided file
*/
function file_extension($filename)
{
	return substr(strrchr($filename, '.'), 1);
}

// #############################################################################
/**
* Tests a string to see if it's a valid email address
*
* @param	string	Email address
*
* @return	boolean
* @deprecated
*/
function is_valid_email($email)
{
	return vB_String::isValidEmail($email);
}

// #############################################################################
/**
* Starts the process of sending an email - either immediately or by adding it to the mail queue.
*
* @param	string	Destination email address
* @param	string	Email message subject
* @param	string	Email message body
* @param	boolean	If true, do not use the mail queue and send immediately
* @param	string	Optional name/email to use in 'From' header
* @param	string	Additional headers
* @param	string	Username of person sending the email
*/
function vbmail($toemail, $subject, $message, $sendnow = false, $from = '', $uheaders = '', $username = '')
{
	return vB_Mail::vbmail($toemail, $subject, $message, $sendnow = false, $from, $uheaders, $username, $skipFloodCheck);
}

// #############################################################################
/**
* Returns a portion of an SQL query to select language fields from the database
*
* @param	boolean	If true, select 'language.fieldname' otherwise 'fieldname'
*
* @return	string
*/
function fetch_language_fields_sql($addtable = true)
{
	global $phrasegroups;

	if (!is_array($phrasegroups))
	{
		$phrasegroups = array();
	}
	if (!in_array('global', $phrasegroups))
	{
		array_unshift($phrasegroups, 'global');
	}

	if ($addtable)
	{
		$prefix = 'language.';
	}
	else
	{
		$prefix = '';
	}

	$sql = '';
	$options = vB::getDatastore()->get_value('options');

	foreach ($phrasegroups AS $group)
	{
		$group = preg_replace('#[^a-z0-9_]#i', '', $group); // just to be safe...
		if ($group == 'reputationlevel' AND VB_AREA == 'Forum')
		{	// Don't load reputation phrases if reputation is disabled
			continue;
		}
		$sql .= ",
			{$prefix}phrasegroup_$group AS phrasegroup_$group";
	}

	$sql .= ",
			{$prefix}options AS lang_options,
			{$prefix}languagecode AS lang_code,
			{$prefix}charset AS lang_charset,
			{$prefix}locale AS lang_locale,
			{$prefix}imagesoverride AS lang_imagesoverride,
			{$prefix}dateoverride AS lang_dateoverride,
			{$prefix}timeoverride AS lang_timeoverride,
			{$prefix}registereddateoverride AS lang_registereddateoverride,
			{$prefix}calformat1override AS lang_calformat1override,
			{$prefix}calformat2override AS lang_calformat2override,
			{$prefix}logdateoverride AS lang_logdateoverride,
			{$prefix}decimalsep AS lang_decimalsep,
			{$prefix}thousandsep AS lang_thousandsep";

	return $sql;
}

// #############################################################################
/**
* Returns an UPDATE or INSERT query string for use in big queries with loads of fields...
*
* @param	array	Array of fieldname = value pairs - array('userid' => 21, 'username' => 'John Doe')
* @param	string	Name of the table into which the data should be saved
* @param	string	SQL condition to add to the query string
* @param	array	Array of field names that should be ignored from the $queryvalues array
*
* @return	string
*/
function fetch_query_sql($queryvalues, $table, $condition = '', $exclusions = '')
{
	global $vbulletin;

	if (empty($exclusions))
	{
		$exclusions = array();
	}

	$numfields = sizeof($queryvalues);
	$i = 1;

	if (!empty($condition))
	{
		$querystring = "\n### UPDATE QUERY GENERATED BY fetch_query_sql() ###\n";
		foreach($queryvalues AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}
			$querystring .= "\t`$fieldname` = " . iif(is_numeric($value) OR in_array($fieldname, $exclusions), "'$value'", "'" . $vbulletin->db->escape_string($value) . "'") . iif($i++ == $numfields, "\n", ",\n");
		}
		return "UPDATE " . TABLE_PREFIX . "$table SET\n$querystring$condition";
	}
	else
	{
		#$fieldlist = $table . 'id, ';
		#$valuelist = 'NULL, ';
		$fieldlist = '';
		$valuelist = '';
		foreach($queryvalues AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}
			$endbit = iif($i++ == $numfields, '', ', ');
			$fieldlist .= "`" . $fieldname . "`" . $endbit;
			$valuelist .= iif(is_numeric($value) OR in_array($fieldname, $exclusions), "'$value'", "'" . $vbulletin->db->escape_string($value) . "'") . $endbit;
		}
		return "\n### INSERT QUERY GENERATED BY fetch_query_sql() ###\nINSERT INTO " . TABLE_PREFIX . "$table\n\t($fieldlist)\nVALUES\n\t($valuelist)";
	}
}

/**
 * fetch_query_sql() refactoring
 *
 * @return	array	Returns an array of values to be used by the assertor object.
 * 					array(
 * 						'set' => If updating array of fields to be set
 * 						'insert' => if inserting array of insertions
 * 						'conditions' => condition in assertor format
 * 					)
 */
function fetchQuerySql($queryvalues, $table, $condition = array(), $exclusions = array())
{
	$setValues = array();
	$insertValues = array();
	$sqlCond = array();
	$structure = vB::getDbAssertor()->fetchTableStructure($table);
	// try to fetch it from vBForum package
	if (empty($structure))
	{
		$structure = vB::getDbAssertor()->fetchTableStructure('vBForum:' . $table);
	}

	// undefined table...
	if (empty($structure))
	{
		return false;
	}
	$structure = $structure['structure'];

	if (!empty($condition))
	{
		foreach($queryvalues AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}

			if (!in_array($fieldname, $exclusions) AND in_array($fieldname, $structure))
			{
				$setValues[$fieldname] = $value;
			}
		}

		foreach($condition AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}

			if (!in_array($fieldname, $exclusions) AND in_array($fieldname, $structure))
			{
				$sqlCond[$fieldname] = $value;
			}
		}
	}
	else
	{
		$fieldlist = '';
		$valuelist = '';
		foreach($queryvalues AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}

			if (!in_array($fieldname, $exclusions) AND in_array($fieldname, $structure))
			{
				$insertValues[$fieldname] = $value;
			}
		}
	}

	return array('set' => $setValues, 'insert' => $insertValues, 'conditions' => $sqlCond);
}

// #############################################################################
/**
* fetches the proper username markup and title
*
* @param	array	(ref) User info array
* @param	string	Name of the field representing displaygroupid in the User info array
* @param	string	Name of the field representing username in the User info array
*
* @return	string
*/
function fetch_musername(&$user, $displaygroupfield = 'displaygroupid', $usernamefield = 'username')
{
	global $vbulletin;

	if (!empty($user['musername']))
	{
		// function already been called
		return $user['musername'];
	}

	$username = $user["$usernamefield"];

	if (!empty($user['infractiongroupid']) AND $vbulletin->usergroupcache["$user[usergroupid]"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup'])
	{
		$displaygroupfield = 'infractiongroupid';
	}

	if (isset($user["$displaygroupfield"], $vbulletin->usergroupcache["$user[$displaygroupfield]"]) AND $user["$displaygroupfield"] > 0)
	{
		// use $displaygroupid
		$displaygroupid = $user["$displaygroupfield"];
	}
	else if (isset($vbulletin->usergroupcache["$user[usergroupid]"]) AND $user['usergroupid'] > 0)
	{
		// use primary usergroupid
		$displaygroupid = $user['usergroupid'];
	}
	else
	{
		// use guest usergroup
		$displaygroupid = 1;
	}

	$user['musername'] = $vbulletin->usergroupcache["$displaygroupid"]['opentag'] . $username . $vbulletin->usergroupcache["$displaygroupid"]['closetag'];
	$user['displaygrouptitle'] = $vbulletin->usergroupcache["$displaygroupid"]['title'];
	$user['displayusertitle'] = $vbulletin->usergroupcache["$displaygroupid"]['usertitle'];

	if ($displaygroupfield == 'infractiongroupid' AND $usertitle = $vbulletin->usergroupcache["$user[$displaygroupfield]"]['usertitle'])
	{
		$user['usertitle'] = $usertitle;
	}
	else if (isset($user['customtitle']) AND $user['customtitle'] == 2)
	{
		$user['usertitle'] = htmlspecialchars_uni($user['usertitle']);
	}

	return $user['musername'];
}

// #############################################################################
/**
* Returns an array containing info for the specified forum, or false if forum is not found
*
* @param	integer	(ref) Forum ID
* @param	boolean	Whether or not to return the result from the forumcache if it exists
*
* @deprecated
* @return	mixed
*/
function fetch_foruminfo(&$forumid, $usecache = true)
{
	//this function previously queried a table that may not even exist
	//nothign good can come of this, but it's called from a number of places that
	//need to be tracked down.
	return false;
}

// #############################################################################
define('FETCH_USERINFO_AVATAR',     0x02);
define('FETCH_USERINFO_LOCATION',   0x04);
define('FETCH_USERINFO_PROFILEPIC', 0x08);
define('FETCH_USERINFO_ADMIN',      0x10);
define('FETCH_USERINFO_SIGPIC',     0x20);
define('FETCH_USERINFO_USERCSS',    0x40);
define('FETCH_USERINFO_ISFRIEND',   0x80);

/**
* Fetches an array containing info for the specified user, or false if user is not found
*
* Values for Option parameter:
* 1 - Nothing ...
* 2 - Get avatar
* 4 - Process user's online location
* 8 - Join the customprofilpic table to get the userid just to check if we have a picture
* 16 - Join the administrator table to get various admin options
* 32 - Join the sigpic table to get the userid just to check if we have a picture
* 64 - Get user's custom CSS
* 128 - Is the logged in User a friend of this person?
* Therefore: Option = 6 means 'Get avatar' and 'Process online location'
* See fetch_userinfo() in the do=getinfo section of member.php if you are still confused
*
* @deprecated -- use the fetchUserinfo method of vB_User
* @param	integer	(ref) User ID
* @param	integer	Bitfield Option (see description)
*
* @return	array	The information for the requested user
*/
function fetch_userinfo(&$userid, $option = 0, $languageid = false, $nocache = false)
{
	$optionMap = array(
		'2' => 'avatar',
		'4' => 'location',
		'8' => 'profilepic',
		'16' => 'admin',
		'32' => 'signpic',
		'64' => 'usercss',
		'128' => 'isfriend'
	);

	$options = array();
	foreach($optionMap as $bit => $value)
	{
		if ($option & $bit)
		{
			$options[] = $value;
		}
	}

	return vB_User::fetchUserinfo($userid, $options, $languageid, $nocache);
}

// #############################################################################
/**
* Converts the database value of a profilefield and prepares the displayable value as $profilefield['value']
*
* @param	array	Profilefield data (SELECT * FROM profilefield WHERE profilefieldid = $profilefieldid)
* @param	string	Database value of profilefield
*
* @return	array	Profilefield data including 'value' key
*/
function fetch_profilefield_display(&$profilefield, $profilefield_value)
{
	global $vbphrase;

	$profilefield['title'] = $vbphrase["field$profilefield[profilefieldid]_title"];

	if ($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple')
	{
		$data = unserialize($profilefield['data']);
		foreach ($data AS $key => $val)
		{
			if ($profilefield_value & pow(2, $key))
			{
				$profilefield['value'] .= iif($profilefield['value'], ', ') . $val;
			}
		}
	}
	else if ($profilefield['type'] == 'textarea')
	{
		// Convert newlines to <br /> and replace 3+ <br /> with two <br />
		$profilefield['value'] = preg_replace('#(<br />){3,}#', '<br /><br />', nl2br(trim($profilefield_value)));
	}
	else
	{
		$profilefield['value'] = $profilefield_value;
	}

	// Legacy Hook 'member_customfields' Removed //

	return $profilefield;
}

// #############################################################################
/**
* Returns an SQL condition like (forumid = 1 OR forumid = 2 OR forumid = 3) for each of a forum's parents
*
* @param	integer	Forum ID
* @param	string	The name of the field to be used in the clause
* @param	string	The 'joiner' word - could be 'OR' or 'AND' etc.
* @param	string	The parentlist of the specified forum (comma separated string)
*
* @return	string
*/
function fetch_forum_clause_sql($forumid, $field = 'forumid', $joiner = 'OR', $parentlist = '')
{
	global $vbulletin;

	if (empty($parentlist))
	{
		// prevents an error, and is at least somewhat correct
		$parentlist = '-1,' . intval($forumid);
	}

	if (strtoupper($joiner) == 'OR')
	{
		return "$field IN ($parentlist)";
	}
	else
	{
		return "($field = '" . implode(explode(',', $parentlist), "' $joiner $field = '") . '\')';
	}

}

// #############################################################################
/**
* Multi-purpose function to verify that an item exists and fetch data at the same time
*
* This function works with threads, forums, posts, users and other tables that obey the {item}id, title convention.
* If the data is not found and execution is not halted, a false value will be returned
*
* @param	string	Name of the ID field to be fetched (forumid, threadid etc.)
* @param	integer	(ref) ID of the item to be fetched
* @param	boolean	If true, halt and show error when data is not found
* @param	boolean	If true, 'SELECT *' instead of selecting just the ID field
* @param	integer	Bitfield options to be passed to fetch_userinfo()
*
* @return	mixed
* @deprecated
*/
function verify_id($idname, &$id, $alert = true, $selall = false, $options = 0)
{
	// verifies an id number and returns a correct one if it can be found
	// returns 0 if none found
	global $vbulletin, $vbphrase;

	$options = vB::getDatastore()->get_value('options');

	if (empty($vbphrase["$idname"]))
	{
		$vbphrase["$idname"] = $idname;
	}
	$id = intval($id);
	if (empty($id))
	{
		if ($alert)
		{
			eval(standard_error(fetch_error('noid', $vbphrase["$idname"])));
		}
		else
		{
			return 0;
		}
	}

	$selid = ($selall ? '*' : $idname . 'id');


	switch ($idname)
	{
		case 'user':
			$tempcache = fetch_userinfo($id, $options);
			if (!$tempcache AND $alert)
			{
				eval(standard_error(fetch_error('invalidid', $vbphrase["$idname"])));
			}
			return ($selall ? $tempcache : $tempcache[$idname . 'id']);

		default:
			if (!$check = $vbulletin->db->query_first("SELECT $selid FROM " . TABLE_PREFIX . "$idname WHERE $idname" . "id = $id"))
			{
				if ($alert)
				{
					eval(standard_error(fetch_error('invalidid', $vbphrase["$idname"])));
				}

				return ($selall ? array() : 0);
			}
			else
			{
				return ($selall ? $check : $check["$selid"]);
			}
	}
}

// #############################################################################
/**
* Strips away [quote] tags and their contents from the specified string
*
* @param	string	Text to be stripped of quote tags
*
* @return	string
*/
function strip_quotes($text)
{
	$lowertext = strtolower($text);

	// find all [quote tags
	$start_pos = array();
	$curpos = 0;
	do
	{
		$pos = strpos($lowertext, '[quote', $curpos);
		if ($pos !== false AND ($lowertext[$pos + 6] == '=' OR $lowertext[$pos + 6] == ']'))
		{
			$start_pos["$pos"] = 'start';
		}

		$curpos = $pos + 6;
	}
	while ($pos !== false);

	if (sizeof($start_pos) == 0)
	{
		return $text;
	}

	// find all [/quote] tags
	$end_pos = array();
	$curpos = 0;
	do
	{
		$pos = strpos($lowertext, '[/quote]', $curpos);
		if ($pos !== false)
		{
			$end_pos["$pos"] = 'end';
			$curpos = $pos + 8;
		}
	}
	while ($pos !== false);

	if (sizeof($end_pos) == 0)
	{
		return $text;
	}

	// merge them together and sort based on position in string
	$pos_list = $start_pos + $end_pos;
	ksort($pos_list);

	do
	{
		// build a stack that represents when a quote tag is opened
		// and add non-quote text to the new string
		$stack = array();
		$newtext = '';
		$substr_pos = 0;
		foreach ($pos_list AS $pos => $type)
		{
			$stacksize = sizeof($stack);
			if ($type == 'start')
			{
				// empty stack, so add from the last close tag or the beginning of the string
				if ($stacksize == 0)
				{
					$newtext .= substr($text, $substr_pos, $pos - $substr_pos);
				}
				array_push($stack, $pos);
			}
			else
			{
				// pop off the latest opened tag
				if ($stacksize)
				{
					array_pop($stack);
					$substr_pos = $pos + 8;
				}
			}
		}

		// add any trailing text
		$newtext .= substr($text, $substr_pos);

		// check to see if there's a stack remaining, remove those points
		// as key points, and repeat. Allows emulation of a non-greedy-type
		// recursion.
		if ($stack)
		{
			foreach ($stack AS $pos)
			{
				unset($pos_list["$pos"]);
			}
		}
	}
	while ($stack);

	return $newtext;
}

// #############################################################################
/**
* Strips away bbcode from a given string, leaving plain text
*
* @deprecated
* @see vB_String::stripBbcode()
* @param	string	Text to be stripped of bbcode tags
* @param	boolean	If true, strip away quote tags AND their contents
* @param	boolean	If true, use the fast-and-dirty method rather than the shiny and nice method
* @param	boolean	If true, display the url of the link in parenthesis after the link text
* @param	boolean	If true, strip away img/video tags and their contents
* @param	boolean	If true, keep [quote] tags. Useful for API.
*
* @return	string
*/
function strip_bbcode($message, $stripquotes = false, $fast_and_dirty = false, $showlinks = true, $stripimg = false, $keepquotetags = false)
{
	return vB_String::stripBbcode($message, $stripquotes, $fast_and_dirty, $showlinks, $stripimg, $keepquotetags);
}

// #############################################################################
/**
* Returns a gzip-compressed version of the specified string
*
* @param	string	Text to be gzipped
* @param	integer	Level of Gzip compression (1-10)
*
* @return	string
*/
function fetch_gzipped_text($text, $level = 1)
{
	global $vbulletin;

	$returntext = $text;

	if (function_exists('crc32') AND function_exists('gzcompress') AND !$vbulletin->nozip)
	{
		if (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)
		{
			$encoding = 'x-gzip';
		}
		if (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
		{
			$encoding = 'gzip';
		}

		if ($encoding)
		{
			$vbulletin->donegzip = true;
			header('Content-Encoding: ' . $encoding);

			if (false AND function_exists('gzencode'))
			{
				$returntext = gzencode($text, $level);
			}
			else
			{
				$size = strlen($text);
				$crc = crc32($text);

				$returntext = "\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\xff";
				$returntext .= substr(gzcompress($text, $level), 2, -4);
				$returntext .= pack('V', $crc);
				$returntext .= pack('V', $size);
			}
		}
	}
	return $returntext;
}

// #############################################################################
/**
* Checks whether or not any headers have been sent to the browser yet
*
* @param	string	(ref) File name (> PHP 4.3.x)
* @param	integer	(ref) Line number (> PHP 4.3.x)
*
* @return	boolean	True if headers have been sent
*/
function vbheaders_sent(&$filename, &$linenum)
{
	return headers_sent($filename, $linenum);
}

// #############################################################################
/**
* Sets a cookie based on vBulletin environmental settings
*
* @param	string	Cookie name
* @param	mixed	Value to store in the cookie
* @param	boolean	If true, do not set an expiry date for the cookie
* @param	boolean	Allow secure cookies (SSL)
* @param	boolean	Set 'httponly' for cookies in supported browsers
*/
function vbsetcookie($name, $value = '', $permanent = true, $allowsecure = true, $httponly = false)
{
	if (defined('NOCOOKIES'))
	{
		return;
	}

	global $vbulletin;

	$vb5_config =& vB::getConfig();

	if ($permanent)
	{
		$expire = vB::getRequest()->getTimeNow() + 60 * 60 * 24 * 365;
	}
	else
	{
		$expire = 0;
	}

	// IE for Mac doesn't support httponly
	$httponly = (($httponly AND (is_browser('ie') AND is_browser('mac'))) ? false : $httponly);

	// check for SSL
	$secure = ((vB::getRequest()->getVbUrlScheme() === 'https') AND $allowsecure ? true : false);

	$name = COOKIE_PREFIX . $name;

	$filename = 'N/A';
	$linenum = 0;
	$options = vB::getDatastore()->get_value('options');

	if (!headers_sent($filename, $linenum))
	{ // consider showing an error message if they're not sent using above variables?

		if ($value === '' OR $value === false)
		{
			// this will attempt to unset the cookie at each directory up the path.
			// ie, path to file = /test/vb3/. These will be unset: /, /test, /test/, /test/vb3, /test/vb3/
			// This should hopefully prevent cookie conflicts when the cookie path is changed.

			if (!empty($_SERVER['PATH_INFO']) OR !empty($_ENV['PATH_INFO']))
			{
				$scriptpath = $_SERVER['PATH_INFO'] ? $_SERVER['PATH_INFO'] : $_ENV['PATH_INFO'];
			}
			else if ($_SERVER['REDIRECT_URL'] OR $_ENV['REDIRECT_URL'])
			{
				$scriptpath = $_SERVER['REDIRECT_URL'] ? $_SERVER['REDIRECT_URL'] : $_ENV['REDIRECT_URL'];
			}
			else
			{
				$scriptpath = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_ENV['PHP_SELF'];
			}

			$scriptpath = preg_replace(
				array(
					'#/[^/]+\.php$#i',
					'#/(' . preg_quote('admincp', '#') . '|' . preg_quote($vb5_config['Misc']['modcpdir'], '#') . ')(/|$)#i'
				),
				'',
				$scriptpath
			);

			$dirarray = explode('/', preg_replace('#/+$#', '', $scriptpath));

			$alldirs = '';
			$havepath = false;
			if (!defined('SKIP_AGGRESSIVE_LOGOUT'))
			{
				// sending this many headers has caused problems with a few
				// servers, especially with IIS. Defining SKIP_AGGRESSIVE_LOGOUT
				// reduces the number of cookie headers returned.
				foreach ($dirarray AS $thisdir)
				{
					$alldirs .= "$thisdir";

					if ($alldirs == $options['cookiepath'] OR "$alldirs/" == $options['cookiepath'])
					{
						$havepath = true;
					}

					if (!empty($thisdir))
					{
						// try unsetting without the / at the end
						exec_vbsetcookie($name, $value, $expire, $alldirs, $options['cookiedomain'], $secure, $httponly);
					}

					$alldirs .= "/";
					exec_vbsetcookie($name, $value, $expire, $alldirs, $options['cookiedomain'], $secure, $httponly);
				}
			}

			if ($havepath == false)
			{
				exec_vbsetcookie($name, $value, $expire, $options['cookiepath'], $options['cookiedomain'], $secure, $httponly);
			}
		}
		else
		{
			exec_vbsetcookie($name, $value, $expire, $options['cookiepath'], $options['cookiedomain'], $secure, $httponly);
		}
	}	else if (!($vbulletin->db->isExplainEmpty()) AND !VB_API)
	{ //show some sort of error message
		global $templateassoc, $vbulletin;
		if (empty($templateassoc))
		{
			// this is being called before templates have been cached, so just get the default one
			$template = vB::getDbAssertor()->getRow('template', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'title' => 'STANDARD_ERROR', 'styleid' => -1));
			$templateassoc = array('STANDARD_ERROR' => $template['templateid']);
		}
		eval(standard_error(fetch_error('cant_set_cookies', $filename, $linenum)));
	}
}

// #############################################################################
/**
* Calls PHP's setcookie() or sends raw headers if 'httponly' is required.
* Should really only be called through vbsetcookie()
*
* @param	string	Name
* @param	string	Value
* @param	int		Expire
* @param	string	Path
* @param	string	Domain
* @param	boolean	Secure
* @param	boolean	HTTP only - see http://msdn.microsoft.com/workshop/author/dhtml/httponly_cookies.asp
*
* @return	boolean	True on success
*/
function exec_vbsetcookie($name, $value, $expires, $path = '', $domain = '', $secure = false, $httponly = false)
{
	if ($httponly AND $value)
	{
		// cookie names and values may not contain any of the characters listed
		foreach (array(",", ";", " ", "\t", "\r", "\n", "\013", "\014") AS $bad_char)
		{
			if (strpos($name, $bad_char) !== false OR strpos($value, $bad_char) !== false)
			{
				return false;
			}
		}

		// name and value
		$cookie = "Set-Cookie: $name=" . urlencode($value);

		// expiry
		$cookie .= ($expires > 0 ? '; expires=' . gmdate('D, d-M-Y H:i:s', $expires) . ' GMT' : '');

		// path
		$cookie .= ($path ? "; path=$path" : '');

		// domain
		$cookie .= ($domain ? "; domain=$domain" : '');

		// secure
		$cookie .= ($secure ? '; secure' : '');

		// httponly
		$cookie .= ($httponly ? '; HttpOnly' : '');

		header($cookie, false);
		return true;
	}
	else
	{
		return setcookie($name, $value, $expires, $path, $domain, $secure);
	}
}

// #############################################################################
/**
* Returns the value for an array stored in a cookie
*
* @param	string	Name of the cookie
* @param	mixed	ID of the data within the cookie
*
* @return	mixed
*/
function fetch_bbarray_cookie($cookiename, $id)
{
	global $vbulletin;

	$cookie_name = COOKIE_PREFIX . $cookiename; // name of cookie variable
	$cache_name = 'bb_cache_' . $cookiename; // name of cache variable
	global $$cache_name; // internal array for cacheing purposes

	$cookie =& $vbulletin->input->clean_gpc('c', $cookie_name, vB_Cleaner::TYPE_STR);
	$cache =  &$$cache_name;
	if ($cookie != '' AND !isset($cache))
	{
		$cache = @unserialize(convert_bbarray_cookie($cookie));
	}

	if (isset($cache))
	{
		return empty($cache["$id"]) ? null : $cache["$id"];
	}

}

// #############################################################################
/**
* Sets the value for data stored in an array-cookie
*
* @param	string	Name of the cookie
* @param	mixed	ID of the data within the cookie
* @param	mixed	Value for the data
* @param	boolean	If true, make this a permanent cookie
*/
function set_bbarray_cookie($cookiename, $id, $value, $permanent = false)
{
	// sets the value for a array and sets the cookie
	global $vbulletin;

	$cookie_name = COOKIE_PREFIX . $cookiename; // name of cookie variable
	$cache_name = 'bb_cache_' . $cookiename; // name of cache variable
	global $$cache_name; // internal array for cacheing purposes

	$cookie =& $vbulletin->input->clean_gpc('c', $cookie_name, vB_Cleaner::TYPE_STR);
	$cache =& $$cache_name;
	if ($cookie != '' AND !isset($cache))
	{
		$cache = @unserialize(convert_bbarray_cookie($cookie));
	}

	$cache["$id"] = $value;

	vbsetcookie($cookiename, convert_bbarray_cookie(serialize($cache), 'set'), $permanent);

}

// #############################################################################
/**
* Replaces all those none safe characters so we dont waste space in array cookie values with URL entities
*
* @param	string	Cookie array
* @param	string	Direction ('get' or 'set')
*
* @return	array
*/
function convert_bbarray_cookie($cookie, $dir = 'get')
{
	if ($dir == 'set')
	{
		$cookie = str_replace(array('"', ':', ';'), array('.', '-', '_'), $cookie);
		// prefix cookie with 32 character hash
		$cookie = sign_client_string($cookie);
	}
	else
	{
		if (($cookie = verify_client_string($cookie)) !== false)
		{
			$cookie = str_replace(array('.', '-', '_'), array('"', ':', ';'), $cookie);
		}
		else
		{
			$cookie = '';
		}
	}
	return $cookie;
}

// #############################################################################
/**
* Signs a string we intend to pass to the client but don't want them to alter
*
* @param	string	String to be signed
*
* @return	string	MD5 hash followed immediately by the string
*/
function sign_client_string($string, $extra_entropy = '')
{
	if (preg_match('#[\x00-\x1F\x80-\xFF]#s', $string))
	{
		$string = base64_encode($string);
		$prefix = 'B64:';
	}
	else
	{
		$prefix = '';
	}

	return $prefix . sha1($string . sha1(vB_Request_Web::$COOKIE_SALT) . $extra_entropy) . $string;
}

// #############################################################################
/**
* Verifies a string return from a client that it has been unaltered
*
* @param	string	String from the client to be verified
*
* @return	string|boolean	String without the verification hash or false on failure
*/
function verify_client_string($string, $extra_entropy = '')
{
	if (substr($string, 0, 4) == 'B64:')
	{
		$firstpart = substr($string, 4, 40);
		$return = substr($string, 44);
		$decode = true;
	}
	else
	{
		$firstpart = substr($string, 0, 40);
		$return = substr($string, 40);
		$decode = false;
	}

	if (sha1($return . sha1(vB_Request_Web::$COOKIE_SALT) . $extra_entropy) === $firstpart)
	{
		return ($decode ? base64_decode($return) : $return);
	}

	return false;
}

// #############################################################################
/**
* Verifies a security token is valid
*
* @param	string	Security token from the REQUEST data
* @param	string	Security token used in the hash
*
* @return	boolean	True if the hash matches and is within the correct TTL
*/
function verify_security_token($request_token, $user_token)
{
	global $vbulletin;

	// This is for backwards compatability before tokens had TIMENOW prefixed
	if (strpos($request_token, '-') === false)
	{
		return ($request_token === $user_token);
	}

	list($time, $token) = explode('-', $request_token);

	if ($token !== sha1($time . $user_token))
	{
		return false;
	}

	// A token is only valid for 3 hours
	if ($time <= TIMENOW - 10800)
	{
		$vbulletin->GPC['securitytoken'] = 'timeout';
		return false;
	}

	return true;
}

// #############################################################################
/**
* Reads $bgclass and returns the alternate table class
*
* @param	integer	If > 0, allows us to have multiple classes on one page without them overwriting each other
*
* @return	string	CSS class name
*/
function exec_switch_bg($alternate = 0)
{
	global $bgclass, $altbgclass;
	static $tempclass;

	if ($tempclass != '')
	{
		$bgclass = $tempclass;
		$tempclass = '';
	}

	if ($alternate > 0)
	{
		$varname = 'bgclass' . $alternate;
		global $$varname;

		if ($$varname == 'alt1')
		{
			$$varname = 'alt2';
			$altbgclass = 'alt1';
		}
		else
		{
			$$varname = 'alt1';
			$altbgclass = 'alt2';
		}
		$tempclass = $bgclass;
		$bgclass = $$varname;
	}
	else
	{
		if ($bgclass == 'alt1')
		{
			$bgclass = 'alt2';
			$altbgclass = 'alt1';
		}
		else
		{
			$bgclass = 'alt1';
			$altbgclass = 'alt2';
		}
	}

	return $bgclass;
}

// #############################################################################
/**
* Ensures that the variables for a multi-page display are sane
*
* @return	integer	Maximum posts perpage that a user can see
*/
function sanitize_maxposts($perpage = 0)
{
	global $vbulletin;
	$options = vB::getDatastore()->get_value('options');
	$max = intval(max(explode(',', $options['usermaxposts'])));

	if ($max AND $vbulletin->userinfo['maxposts'])
	{
		if (!$perpage)
		{
			return $vbulletin->userinfo['maxposts'] == -1 ? $options['maxposts'] : $vbulletin->userinfo['maxposts'];
		}
		else if ($perpage == -1)
		{
			return $max;
		}
		else
		{
			return ($perpage > $max ? $max : $perpage);
		}
	}
	else if (!empty($options['maxposts']))
	{
		return $options['maxposts'];
	}
	else
	{
		return 10;
	}
}

// #############################################################################
/**
* Ensures that the variables for a multi-page display are sane
*
* @param	integer	Total number of items to be displayed
* @param	integer	(ref) Current page number
* @param	integer	(ref) Desired number of results to show per-page
* @param	integer	Maximum allowable results to show per-page
* @param	integer	Default number of results to show per-page
*/
function sanitize_pageresults($numresults, &$page, &$perpage, $maxperpage = 20, $defaultperpage = 20)
{
	$perpage = fetch_perpage($perpage, $maxperpage, $defaultperpage);
	$numpages = ceil($numresults / $perpage);
	if ($numpages == 0)
	{
		$numpages = 1;
	}

	if ($page < 1)
	{
		$page = 1;
	}
	else if ($page > $numpages)
	{
		$page = $numpages;
	}
}

/**
* Returns the number of items to display on a page based on a desired value and
* constraints.
*
* If the desired value is not given use the default.  Under no circumstances allow
* a value greater than maxperpage.
*
* @param	integer	Desired number of results to show per-page
* @param	integer	Maximum allowable results to show per-page
* @param	integer	Default number of results to show per-page
* @return actual per page results
*/
function fetch_perpage($perpage, $maxperpage = 20, $defaultperpage = 20)
{
	$perpage = intval($perpage);
	if ($perpage < 1)
	{
		$perpage = $defaultperpage;
	}

	if ($perpage > $maxperpage)
	{
		$perpage = $maxperpage;
	}
	return $perpage;
}

/**
* Returns the HTML for multi-page navigation without a fully know result set
*
* This handles multipage navigation when we don't have a count for the
* resultset.  The follow things must be true for this logic to work correctly
*
* 1) $confirmedcount is less than or equal to the total number of results
* 2) if $confirmedcount is not the total number of results, it must be at
* 	least equal to the "count" of the last result displayed in the window.
*
* These assumptions allow us to display the window links knowing that they will
* be valid without knowing the full extent of the result set.
*
* @see fetch_seo_url
* @param	integer	Page number being displayed
* @param	integer Number of pages to show before and after current page
* @param	integer	Number of items to be displayed per page
* @param 	integer	Number of items confirmed in the results
* @param	string	Base address for links eg: showthread.php?t=99{&page=4}
* @param	string	Ending portion of address for links
* @param 	string 	The base link for seo urls (if this is used address will not be)
* @param 	array 	Additonal object info for generating the seo urls
* @param	array 	Additonal page info for generating the seo urls
*
* @todo is it correct to include the pagenav hooks here?  Do we need other hooks
* 	to replace them?
* @return	string	Page navigation HTML
*/
function construct_window_page_nav (
	$pagenumber,
	$window,
	$perpage,
	$confirmedcount,
	$address,
	$address2 = '',
	$anchor = '',
	$seolink = '',
	$objectinfo = '',
	$pageinfo = ''
	)
{
	global $vbulletin, $vbphrase, $show;

	$curpage = 0;
	$pagenavarr = array();
	$firstlink = '';
	$prevlink = '';
	$lastlink = '';
	$nextlink = '';

	if ($confirmedcount <= $perpage)
	{
		$show['pagenav'] = false;
		return '';
	}

	$show['pagenav'] = true;

	$confirmedpages = ceil($confirmedcount / $perpage);

	//window style page navs don't permit "jump to end" logic
	$show['jumppage'] = false;
	$show['last'] = false;

	$show['prev'] = false;
	$show['next'] = false;
	$show['first'] = false;

	$bits = vB_String::parseUrl($address);
	$jumpaddress = $bits['path'];
	$querybits = explode('&amp;', $bits['query'] . $address2);
	$hiddenfields = '';
	if (!empty($querybits))
	{
		foreach ($querybits AS $bit)
		{
			if ($bit)
			{
				$bitinfo = explode('=', $bit);
				$hiddenfields .= "<input type=\"hidden\" name=\"$bitinfo[0]\" value=\"$bitinfo[1]\" />";
			}
		}
	}
	$hiddenfields .= "<input type=\"hidden\" name=\"s\" value=\"" . vB::$vbulletin->session->fetch_sessionhash() . "\" />
			<input type=\"hidden\" name=\"securitytoken\" value=\"" . vB::$vbulletin->userinfo['securitytoken'] .
		"\" />";

	if ($seolink)
	{
		$show['pagelinks'] = false;
		$use_qmark =  0;
	}
	else
	{
		$firstaddress = $prevaddress = $nextaddress = $lastaddress = $address;
		$show['pagelinks'] = true;
		$use_qmark =  strpos($address, '?') ? 0 : 1;
	}

	if ($pagenumber > 1)
	{
		$prevpage = $pagenumber - 1;
		$prevnumbers = fetch_start_end_total_array($prevpage, $perpage, $confirmedcount);
		if ($seolink)
		{
			$pageinfo['page'] = $prevpage;
			$prevaddress = vB5_Route::buildUrl($seolink, $objectinfo, $pageinfo);
		}
		$show['prev'] = true;
	}

	if ($pagenumber < $confirmedpages)
	{
		$nextpage = $pagenumber + 1;
		if ($seolink)
		{
			$pageinfo['page'] = $nextpage;
			$nextaddress = vB5_Route::buildUrl($seolink, $objectinfo, $pageinfo);
		}
		$nextnumbers = fetch_start_end_total_array($nextpage, $perpage, $confirmedcount);
		$show['next'] = true;
	}

	if (($pagenumber - $window) > 1)
	{
		$firstnumbers = fetch_start_end_total_array(1, $perpage, $confirmedcount);
		if ($seolink)
		{
			unset($pageinfo['page']);
			$firstaddress = vB5_Route::buildUrl($seolink, $objectinfo, $pageinfo);
		}
		$show['first'] = true;
	}


	for ($curpage = ($pagenumber - $window); $curpage <= $pagenumber+$window AND $curpage <= $confirmedpages; $curpage++)
	{
		if ($curpage < 1)
		{
			continue;
		}

		else if ($curpage == $pagenumber)
		{
			$numbers = fetch_start_end_total_array($curpage, $perpage, $confirmedcount);

			$templater = vB_Template::create('pagenav_curpage_window');
			$templater->register('curpage', $curpage);
			$templater->register('numbers', $numbers);
			$templater->register('use_qmark', $use_qmark);
			$templater->register('total', $total);
			$pagenavarr[] = $templater->render();
		}

		else
		{
			if ($seolink)
			{
				$pageinfo['page'] = $curpage;
				$address = vB5_Route::buildUrl($seolink, $objectinfo, $pageinfo);
				$show['curpage'] = false;
			}
			else
			{
				$show['curpage'] = ($curpage != 1);
			}
			$pagenumbers = fetch_start_end_total_array($curpage, $perpage, $confirmedcount);

			$templater = vB_Template::create('pagenav_pagelink_window');
			$templater->register('address', $address);
			$templater->register('address2', $address2);
			$templater->register('anchor', $anchor);
			$templater->register('curpage', $curpage);
			$templater->register('pagenumbers', $pagenumbers);
			$templater->register('total', $total);
			$templater->register('use_qmark', $use_qmark);
			$pagenavarr[] = $templater->render();
		}
	}

	if (LANGUAGE_DIRECTION == 'rtl' AND (is_browser('ie') AND is_browser('ie') < 8))
	{
		$pagenavarr = array_reverse($pagenavarr);
	}

	$pagenav = implode('', $pagenavarr);

	$templater = vB_Template::create('pagenav_window');
	$templater->register('address2', $address2);
	$templater->register('anchor', $anchor);
	$templater->register('firstaddress', $firstaddress);
	$templater->register('firstnumbers', $firstnumbers);
	$templater->register('jumpaddress', $address);
	$templater->register('lastaddress', $lastaddress);
	$templater->register('lastnumbers', $lastnumbers);
	$templater->register('nextaddress', $nextaddress);
	$templater->register('nextnumbers', $nextnumbers);
	$templater->register('nextpage', $nextpage);
	$templater->register('pagenav', $pagenav);
	$templater->register('pagenumber', $pagenumber);
	$templater->register('prevaddress', $prevaddress);
	$templater->register('prevnumbers', $prevnumbers);
	$templater->register('prevpage', $prevpage);
	$templater->register('total', $total);
	$templater->register('totalpages', $confirmedpages);
	$templater->register('use_qmark', $use_qmark);
	$templater->register('hiddenfields', $hiddenfields);

	$pagenav = $templater->render();
	return $pagenav;
}

// #############################################################################
/**
* Returns an array so you can print 'Showing results $arr[first] to $arr[last] of $totalresults'
*
* @param	integer	Current page number
* @param	integer	Results to show per-page
* @param	integer	Total results found
*
* @return	array	In the format of - array('first' => x, 'last' => y)
*/
function fetch_start_end_total_array($pagenumber, $perpage, $total)
{
	$first = $perpage * ($pagenumber - 1);
	$last = $first + $perpage;

	if ($last > $total)
	{
		$last = $total;
	}
	$first++;

	return array('first' => vb_number_format($first), 'last' => vb_number_format($last));
}

// #############################################################################
/**
* Returns the HTML for the navigation breadcrumb in the navbar
*
* This function will also set the GLOBAL $pagetitle to equal whatever is the last item in the navbits
*
* @param	array	Array of link => title pairs from which to build the link chain
* @param 	boolean Whether to include the forum breadcrumb
* @return	string
*/
function construct_navbits($nav_array)
{
	global $pagetitle, $vbulletin, $vbphrase, $show;

	// VB API doesn't require rendering navbar.
	if (defined('VB_API') AND VB_API === true)
	{
		return array();
	}

	$code = array(
		'breadcrumb' => '',
		'lastelement' => ''
	);

	$lastelement = sizeof($nav_array);
	$counter = 0;

	if (is_array($nav_array))
	{
		foreach($nav_array AS $nav_url => $nav_title)
		{
			$pagetitle = $nav_title;

			$elementtype = (++$counter == $lastelement) ? 'lastelement' : 'breadcrumb';
			$show['breadcrumb'] = ($elementtype == 'breadcrumb');

			if (empty($nav_title))
			{
				continue;
			}

			$skip_nav_entry = false;
			// Legacy Hook 'navbits' Removed //
			if ($skip_nav_entry)
			{
				continue;
			}

			$templater = vB_Template::create('navbar_link');
				$templater->register('nav_title', $nav_title);
				$templater->register('nav_url', $nav_url);
			$code["$elementtype"] .= $templater->render();
		}
	}

	// Legacy Hook 'navbits_complete' Removed //

	return $code;
}

/**
* Renders the navbar template with the specified navbits
*
* @param	array	Array of navbit information
*
* @return	string	Navbar HTML
*/
function render_navbar_template($navbits)
{
	global $vbulletin;

	// VB API doesn't require rendering navbar.
	if (defined('VB_API') AND VB_API === true)
	{
		return true;
	}

	if (!class_exists('vB_Template', true))
	{
		//We're coming from vB5 and we don't need a vb3/4 navbar.
		return true;
	}

	$options = vB::getDatastore()->get_value('options');
	$templater = vB_Template::create('navbar');
	// Resolve the root segment

	$templater->register('ad_location', $GLOBALS['ad_location']);
	$templater->register('foruminfo', $GLOBALS['foruminfo']);
	$templater->register('navbar_reloadurl', $GLOBALS['navbar_reloadurl']);
	$templater->register('navbits', $navbits);
	$templater->register('notices', $GLOBALS['notices']);
	$templater->register('notifications_menubits', $GLOBALS['notifications_menubits']);
	$templater->register('notifications_total', $GLOBALS['notifications_total']);
	$templater->register('pmbox', $GLOBALS['pmbox']);
	$templater->register('return_link', $GLOBALS['return_link']);

	return $templater->render();
}

/**
* Renders the option template. Simply a helper method to shorten the length of code to do this.
*
* @param	string	Title of the option
* @param	string	Value of the option (sent to the server on submission)
* @param	string	If selected, should be string: selected="selected"
* @param	string	A class to apply to the option
*
* @return	string	Option HTML
*/
function render_option_template($optiontitle, $optionvalue, $optionselected = '', $optionclass = '')
{
	$templater = vB_Template::create('option');

	$templater->register('optionclass', $optionclass);
	$templater->register('optionselected', $optionselected);
	$templater->register('optiontitle', $optiontitle);
	$templater->register('optionvalue', $optionvalue);

	return $templater->render();
}

/**
* Extracts member information for the action drop-down from the postinfo array
*
* @param	array	post information
*
* @return	array	member information
*/
function fetch_lastposter_userinfo($lastpostinfo)
{
	global $show, $vbulletin;

	// use all the information thats already in lastpostinfo
	$memberinfo = $lastpostinfo;

	// calculate any pertinent missing info for member action drop-down
	$useroptions = $lastpostinfo['useroptions'];
	$memberinfo['receivepm'] = $useroptions & $vbulletin->bf_misc_regoptions['enablepm'];
	$memberinfo['userid'] = $lastpostinfo['lastposterid'];
	$memberinfo['username'] = $lastpostinfo['lastposter'];
	$memberinfo['showemail'] = (isset($memberinfo['showemail']) ? $memberinfo['showemail'] : false);
	$memberinfo['online'] = (isset($memberinfo['online']) ? $memberinfo['online'] : 'offline');

	return $memberinfo;
}

/**
* Returns the HTML for the member dwop-down pop-up menu
*
* @param	array	user information for the drop-down context
* @param	array	*UNUSED* (was template hook, if we dont want to use the global one (like in postbit))
* @param	string	class name to apply to the div for context specific stylings
*
* @return	string	Member Drop-Down HTML
*/
function construct_memberaction_dropdown($memberinfo, $dummy = array(), $page_class = null)
{
	global $show, $vbulletin;

	$memberperm = cache_permissions($memberinfo, false);
	$options = vB::getDatastore()->get_value('options');
	// display the private messgage link?
	$show['pmlink']=
	(
		$options['enablepms']
			AND
		$vbulletin->userinfo['permissions']['pmquota']
			AND
		(
			$vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']
				OR
			($memberinfo['receivepm'] AND $memberperm['pmquota'])
		)
	);

	// display the user's homepage link?
	$show['homepage'] = ($memberinfo['homepage'] != '' AND $memberinfo['homepage'] != 'http://');

	// display the add as friend link?
	$show['addfriend']=
	(
		$options['socnet'] & $vbulletin->bf_misc_socnet['enable_friends']
		AND $vbulletin->userinfo['userid']
		AND $memberinfo['userid'] != $vbulletin->userinfo['userid']
		AND $vbulletin->userinfo['permissions']['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']
		AND $memberperm['genericpermissions2'] & $vbulletin->bf_ugp_genericpermissions2['canusefriends']
		AND !$memberinfo['isfriend']
	);

	// Check if blog is installed, and show link if so
	$show['viewblog'] = $vbulletin->products['vbblog'];

	// Check if CMS is installed, and show link if so
	$show['viewarticles'] = $vbulletin->products['vbcms'];

	// display the email link?
	$show['emaillink'] = (
		$memberinfo['showemail'] AND $options['displayemails'] AND
		(
			!$options['secureemail']
				OR
			($options['secureemail'] AND $options['enableemail'])
		)
		AND $vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canemailmember']
		AND $vbulletin->userinfo['userid']
	);

	if (!$memberinfo['onlinestatusphrase'])
	{
		require_once(DIR . '/includes/functions_bigthree.php');
		fetch_online_status($memberinfo);
	}

	// execute memberaction hook
	// Legacy Hook 'memberaction_dropdown' Removed //

	$templater = vB_Template::create('memberaction_dropdown');
	$templater->register('memberinfo', $memberinfo);
	if (!empty($page_class))
	{
		$templater->register('page_class', $page_class);
	}

	return $templater->render();
}

// #############################################################################
/**
* Construct Phrase
*
* this function is actually just a wrapper for sprintf but makes identification of phrase code easier
* and will not error if there are no additional arguments. The first parameter is the phrase text, and
* the (unlimited number of) following parameters are the variables to be parsed into that phrase.
*
* @param	string	Text of the phrase
* @param	mixed	First variable to be inserted
* ..		..		..
* @param	mixed	Nth variable to be inserted
*
* @return	string	The parsed phrase
*/
function construct_phrase()
{
	$args = func_get_args();
	$numargs = sizeof($args);

	// FAIL SAFE: check if only parameter is an array,
	// if so we should have called construct_phrase_from_array instead
	if ($numargs == 1 AND is_array($args[0]))
	{
		return construct_phrase_from_array($args[0]);
	}

	// if this function was called with the phrase as the first argument, and an array
	// of paramters as the second, combine into single array for construct_phrase_from_array
	else if ($numargs == 2 AND is_string($args[0]) AND is_array($args[1]))
	{
		array_unshift($args[1], $args[0]);
		return construct_phrase_from_array($args[1]);
	}

	// otherwise just package arguments up as an array
	// and call the array version of this func
	else
	{
		return construct_phrase_from_array($args);
	}
}

/**
* Construct Phrase from Array
*
* this function is actually just a wrapper for sprintf but makes identification of phrase code easier
* and will not error if there are no additional arguments. The first element of the array is the phrase text, and
* the (unlimited number of) following elements are the variables to be parsed into that phrase.
*
* @param	array	array containing phrase and arguments
*
* @return	string	The parsed phrase
*/
function construct_phrase_from_array($phrase_array)
{
	$numargs = sizeof($phrase_array);

	// if we have only one argument then its a phrase
	// with no variables, so just return it
	if ($numargs < 2)
	{
		return $phrase_array[0];
	}

	// call sprintf() on the first argument of this function
	$phrase = @call_user_func_array('sprintf', $phrase_array);
	if ($phrase !== false)
	{
		return $phrase;
	}
	else
	{
		// if that failed, add some extra arguments for debugging
		for ($i = $numargs; $i < 10; $i++)
		{
			$phrase_array["$i"] = "[ARG:$i UNDEFINED]";
		}
		if ($phrase = @call_user_func_array('sprintf', $phrase_array))
		{
			return $phrase;
		}
		// if it still doesn't work, just return the un-parsed text
		else
		{
			return $phrase_array[0];
		}
	}
}

// #############################################################################
/**
* Converts an array of error values to error strings by calling the fetch_error
* function.
*
* @param	Array(mixed) Errors to compile.  Values can either be a string, which is taken
* 	be be the error varname or it can be an array in which case it is viewed as
*		a list of parameters to pass to fetch_error
*
* @return	Array(string)	The array of compiled error messages.  Keys are preserved.
*/
function fetch_error_array($errors)
{
	$compiled_errors = array();

	if (!is_array($errors))
	{
		$compiled_errors[] = $errors;
		return $compiled_errors;
	}

	foreach ($errors as $key => $value)
	{
		if (is_string($value))
		{
			$compiled_errors[$key] = fetch_error($value);
		}
		else if (is_array($value))
		{
			$compiled_errors[$key] = call_user_func_array('fetch_error', $value);
		}
	}

	return $compiled_errors;
}

// #############################################################################
/**
* Fetches an error phrase from the database and inserts values for its embedded variables
*
* @param	string	Varname of error phrase
* @param	mixed	Value of 1st variable
* @param	mixed	Value of 2nd variable
* @param	mixed	Value of Nth variable
*
* @return	string	The parsed phrase text
*/
function fetch_error()
{
	$vbulletin = vB::get_registry();

	$args = func_get_args();

	// Allow an array of phrase and variables to be passed in as arg0 (for some internal functions)
	if (is_array($args[0]))
	{
		$args = $args[0];
	}

	if (isset($vbulletin->GPC) AND !empty($vbulletin->GPC['ajax']))
	{
		switch ($args[0])
		{
			case 'invalidid':
			case 'nopermission_loggedin':
			case 'forumpasswordmissing':
				$args[0] = $args[0] . '_ajax';
		}
	}

	// API only needs error phrase name and args.
	if (defined('VB_API') AND VB_API === true)
	{
		return $args;
	}

	$phraseAux = vB_Api::instanceInternal('phrase')->fetch(array($args[0]));
	$args[0] = $phraseAux[$args[0]];

	if (sizeof($args) > 1)
	{
		return call_user_func_array('construct_phrase', $args);
	}
	else
	{
		return $args[0];
	}
}

// #############################################################################
/**
* Halts execution and shows an error message stating that the visitor does not have permission to view the page
*/
function print_no_permission()
{
	global $vbulletin, $vbphrase;

	require_once(DIR . '/includes/functions_misc.php');

	$vbulletin->userinfo['badlocation'] = 1; // Used by exec_shut_down();

	// Legacy Hook 'error_nopermission' Removed //

	$usergroupid = $vbulletin->userinfo['usergroupid'];
	$options = vB::getDatastore()->get_value('options');

	if (!($vbulletin->usergroupcache["$usergroupid"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
	{
		$reason = $vbulletin->db->query_first_slave("
			SELECT reason, liftdate
			FROM " . TABLE_PREFIX . "userban
			WHERE userid = " . $vbulletin->userinfo['userid']
		);

		// Check for a date or a perm ban
		if ($reason['liftdate'])
		{
			$date = vbdate($options['dateformat'] . ', ' . $options['timeformat'], $reason['liftdate']);
		}
		else
		{
			$date = $vbphrase['never'];
		}

		if (!$reason['reason'])
		{
			$phraseAux = vB_Api::instanceInternal('phrase')->fetch(array('no_reason_specified'));
			$reason['reason'] = $phraseAux['no_reason_specified'];
		}

		eval(standard_error(fetch_error('nopermission_banned', $reason['reason'], $date)));
	}
	else if ($vbulletin->userinfo['userid'] AND !empty($vbulletin->userinfo['infractiongroupids']))
	{
		$date = $vbphrase['never'];

		$infractiongroupids = explode(',', str_replace(' ', '', $vbulletin->userinfo['infractiongroupids']));
		$bannedgroups = array();
		foreach ($infractiongroupids AS $usergroupid)
		{
			if (!($vbulletin->usergroupcache["$usergroupid"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
			{
				$bannedgroups["$usergroupid"] = $usergroupid;
			}
		}

		if (!empty($bannedgroups))
		{
			$points = $vbulletin->userinfo['ipoints'];
			$infractions = $vbulletin->db->query_read("
				SELECT points, expires
				FROM " . TABLE_PREFIX . "infraction
				WHERE userid = " . $vbulletin->userinfo['userid'] . "
					AND action = 0
					AND expires <> 0
					AND points <> 0
				ORDER BY expires ASC
			");

			if ($vbulletin->db->num_rows($infractions))
			{
				$infractiongroups = array();
				$groups = $vbulletin->db->query_read("
					SELECT orusergroupid, pointlevel
					FROM " . TABLE_PREFIX . "infractiongroup
					WHERE usergroupid IN (-1, " . $vbulletin->userinfo['usergroupid'] . ")
						AND pointlevel <= " . $vbulletin->userinfo['ipoints'] . "
					ORDER BY pointlevel
				");
				while ($group = $vbulletin->db->fetch_array($groups))
				{
					$infractiongroups[] = $group;
				}

				$foundbanned = true;
				while ($foundbanned AND $infraction = $vbulletin->db->fetch_array($infractions))
				{
					// Decremement user points as they would be when this infraction expires
					$foundbanned = false;
					$points -= $infraction['points'];
					foreach($infractiongroups AS $key => $group)
					{
						if ($points < $group['pointlevel'])
						{
							continue;
						}
						else if (!($vbulletin->usergroupcache["$group[orusergroupid]"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
						{
							$foundbanned = true;
						}
					}
				}

				if (!$foundbanned)
				{	// This is when we will be "unbanned"
					$date = vbdate($options['dateformat'] . ', ' . $options['timeformat'], $infraction['expires']);
				}

				eval(standard_error(fetch_error('nopermission_banned_infractions', $date)));
			}
		}
	}

	if ($vbulletin->userinfo['userid'])
	{
		$forumHome = vB_Library::instance('content_channel')->getForumHomeChannel();
		eval(standard_error(fetch_error('nopermission_loggedin',
			$vbulletin->userinfo['username'],
			vB_Template_Runtime::fetchStyleVar('right'),
			vB::getCurrentSession()->get('sessionurl'),
			$vbulletin->userinfo['securitytoken'],
			vB5_Route::buildUrl($forumHome['routeid'] . '|fullurl')
		)));
	}
	else
	{
		define('VB_ERROR_PERMISSION', true);
		eval(standard_error(fetch_error('not_logged_no_permission')));
	}
}

// #############################################################################
/**
* Returns eval()-able code to initiate a standard error
*
* @deprecated	Deprecated since 3.5. Use standard_error(fetch_error(...)) instead.
*
* @param	string	Name of error phrase
* @param	boolean	If false, use the name of error phrase as the phrase text itself
* @param	boolean	If true, set the visitor's status on WOL to error page
*
* @return	string
*/
function print_standard_error($err_phrase, $doquery = true, $savebadlocation = true)
{
	die("<h1><em>print_standard_error(...)</em><br />is now redundant. Instead, use<br /><em>standard_error(fetch_error(...))</em></h1>");

	if ($doquery)
	{
		if (!function_exists('fetch_phrase'))
		{
			require_once(DIR . '/includes/functions_misc.php');
		}
		$phraseAux = vB_Api::instanceInternal('phrase')->fetch(array($err_phrase));
		return 'standard_error("' . $phraseAux[$err_phrase] . "\", '', " . intval($savebadlocation) . ");";
	}
	else
	{
		return 'standard_error("' . $err_phrase . "\", '', " . intval($savebadlocation) . ");";
	}
}

// #############################################################################
/**
* Halts execution and shows the specified error message
*
* @param	string	Error message
* @param	string	Optional HTML code to insert in the <head> of the error page
* @param	boolean	If true, set the visitor's status on WOL to error page
* @param	string	Optional template to force the display to use. Ignored if showing a lite error
*/
function standard_error($error = '', $headinsert = '', $savebadlocation = true, $override_template = '')
{
	global $header, $footer, $headinclude, $timezone, $gobutton;
	global $vbulletin, $vbphrase;
	global $pmbox, $show, $ad_location, $notifications_menubits, $notifications_total;

	$show['notices'] = false;

	$options = vB::getDatastore()->get_value('options');
	$title = $options['bbtitle'];
	$pagetitle =& $title;
	$errormessage = $error;

	if (empty($vbulletin->userinfo['badlocation']) OR (!$vbulletin->userinfo['badlocation'] AND $savebadlocation))
	{
		$vbulletin->userinfo['badlocation'] = 3;
	}
	require_once(DIR . '/includes/functions_misc.php');
	if (!empty($_POST['securitytoken']) OR !empty($vbulletin->GPC['postvars']))
	{
		$postvars = construct_post_vars_html();
		if ($vbulletin->GPC['postvars'])
		{
			$_postvars = array();
			$client_string = verify_client_string($vbulletin->GPC['postvars']);
			if ($client_string)
			{
				$_postvars = @json_decode($client_string, true);
			}

			if ($_postvars['securitytoken'] == 'guest')
			{
				unset($_postvars);
			}
		}
		else if ($_POST['securitytoken'] == 'guest')
		{
			unset($postvars);
		}
	}
	else
	{
		$postvars = '';
	}

	if (defined('VB_ERROR_PERMISSION') AND VB_ERROR_PERMISSION == true)
	{
		$show['permission_error'] = true;
	}
	else
	{
		$show['permission_error'] = false;
	}

	if (!empty($vbulletin->userinfo['permissions']) AND ($vbulletin->userinfo['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		$show['search_noindex'] = true;

	}
	else
	{
		$show['search_noindex'] = false;
	}

	$navbits = $navbar = '';
	if (defined('VB_ERROR_LITE') AND VB_ERROR_LITE == true)
	{
		$templatename = 'STANDARD_ERROR_LITE';
		define('NOPMPOPUP', 1); // No Footer here
	}
	else
	{
		// bug 33454: we used to not register the navbar when users did not have general forum permissions (banned)
		// but that was causing display issues with the vb4 style, so now we always render navbar
		$navbits = construct_navbits(array('' => $vbphrase['vbulletin_message']));
		$navbar = render_navbar_template($navbits);

		$templatename = ($override_template ? preg_replace('#[^a-z0-9_]#i', '', $override_template) : 'STANDARD_ERROR');
	}

	// Legacy Hook 'error_generic' Removed //

	if (isset($vbulletin->GPC) AND $vbulletin->GPC['ajax'])
	{
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_XML_Builder_Ajax('text/xml');
		$xml->add_tag('error', $errormessage);
		$xml->print_xml();
		exit;
	}
	else
	{
		if (isset($vbulletin->noheader) AND $vbulletin->noheader)
		{
			@header('Content-Type: text/html' . ($vbulletin->userinfo['lang_charset'] != '' ? '; charset=' . $vbulletin->userinfo['lang_charset'] : ''));
		}

		if (!class_exists('vB_Template', false))
		{
			//This is a vB5 error message. Let's just return is.
			return "error: $errormessage";
		}
		$templater = vB_Template::create($templatename);
			$templater->register_page_templates();
			$templater->register('errormessage', $errormessage);
			$templater->register('headinsert', $headinsert);
			$templater->register('navbar', $navbar);
			$templater->register('pagetitle', $pagetitle);
			$templater->register('postvars', $postvars);
			$templater->register('scriptpath', SCRIPTPATH);
			$templater->register('url', $vbulletin->url);
		print_output($templater->render());
	}
}

// #############################################################################
/**
* Returns eval()-able code to initiate a standard redirect
*
* The global variable $url should contain the URL target for the redirect
*
* @param	string	Name of redirect phrase
* @param	boolean	If false, use the name of redirect phrase as the phrase text itself
* @param	boolean	Whether or not to force a redirect message to be shown
* @param	integer	Language ID to fetch the phrase from (-1 uses the page-wide default)
*
* @return	string
*/
function print_standard_redirect($redir_phrase, $doquery = true, $forceredirect = false, $languageid = -1)
{
	if (!VB_API)
	{
		if ($doquery)
		{
			if (!function_exists('fetch_phrase'))
			{
				require_once(DIR . '/includes/functions_misc.php');
			}

			$phraseAux = vB_Api::instanceInternal('phrase')->fetch(array($redir_phrase));
			$phrase = $phraseAux[$redir_phrase];
			// addslashes run in fetch_phrase
		}
		else
		{
			$phrase = addslashes($redir_phrase);
		}
	}
	else
	{
		$phrase = $redir_phrase;
	}

	return 'standard_redirect("' . $phrase . '", ' . intval($forceredirect) . ');';
}

// #############################################################################
/**
* Halts execution and redirects to the address specified
*
* If the 'useheaderredirect' option is on, the system will attempt to redirect invisibly using header('Location...
* However, 'useheaderredirect' is overridden by setting $forceredirect to a true value.
*
* @param	string	Redirect message
* @param	string	URL to which to redirect the browser
*/
function standard_redirect($message = '', $forceredirect = false)
{
	global $header, $footer, $headinclude, $headinclude_bottom;
	global $timezone, $vbulletin, $vbphrase;

	static
		$str_find     = array('"',      '<',    '>'),
		$str_replace  = array('&quot;', '&lt;', '&gt;');
	$options = vB::getDatastore()->get_value('options');

	if (!$forceredirect AND !headers_sent() AND !$vbulletin->GPC['postvars'] AND !VB_API)
	{
		exec_header_redirect(unhtmlspecialchars($vbulletin->url, true));
	}

	$title = $options['bbtitle'];

	$pagetitle = $title;
	$errormessage = $message;

	$url = unhtmlspecialchars($vbulletin->url, true);
	$url = str_replace(chr(0), '', $url);
	$url = create_full_url($url);
	$url = str_replace($str_find, $str_replace, $url);
	$js_url = addslashes_js($url, '"'); // " has been replaced by &quot;

	$url = preg_replace(
		array('/&#0*59;?/', '/&#x0*3B;?/i', '#;#'),
		'%3B',
		$url
	);
	$url = preg_replace('#&amp%3B#i', '&amp;', $url);

	define('NOPMPOPUP', 1); // No footer here

	require_once(DIR . '/includes/functions_misc.php');
	$postvars = construct_hidden_var_fields(verify_client_string($vbulletin->GPC['postvars']));
	$formfile =& $url;

	// Legacy Hook 'redirect_generic' Removed //

	$templater = vB_Template::create('STANDARD_REDIRECT');
		$templater->register('errormessage', $errormessage);
		$templater->register('formfile', $formfile);
		$templater->register('headinclude', $headinclude);
		$templater->register('headinclude_bottom', $headinclude_bottom);
		$templater->register('js_url', $js_url);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('postvars', $postvars);
		$templater->register('url', $url);
	print_output($templater->render());
	exit;
}

// #############################################################################
/**
* Halts execution and redirects to the specified URL invisibly
*
* @param	string	Destination URL
*/
function exec_header_redirect($url, $redirectcode = 302)
{
	global $vbulletin;

	/* On Install, we cannot call the datastore any
	more, so just set a blank options array instead */
	if (VB_AREA == 'Install')
	{
		$options = array();
	}
	else
	{
		$options = vB::getDatastore()->get_value('options');
	}

	// VB API modification
	if (defined('VB_API') AND VB_API === true)
	{
		eval(print_standard_redirect('header_redirect', true, true));
	}

	$url = create_full_url($url);

	$url = str_replace('&amp;', '&', $url); // prevent possible oddity

	if (strpos($url, "\r\n") !== false)
	{
		trigger_error("Header may not contain more than a single header, new line detected.", E_USER_ERROR);
	}

	header("Location: $url", 0, $redirectcode);

	if ($options['addheaders'] AND (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi'))
	{
		// see #24779
		switch($redirectcode)
		{
			case 301:
				header('Status: 301 Moved Permanently');
			case 302:
				header('Status: 302 Found');
				break;
		}
	}

	define('NOPMPOPUP', 1);
	if (defined('NOSHUTDOWNFUNC'))
	{
		exec_shut_down();
	}
	exit;
}


function exec_header_redirect2($file, $extra = array(), $redirectcode = 302)
{
	exec_header_redirect(get_redirect_url($file, $extra, 'admincp'), $redirectcode);
}

// #############################################################################
/**
* Translates a relative URL to a fully-qualified URL. URLs not beginning with
* a / are assumed to be within the main vB-directory
*
* @param	string	Relative URL
* @param  string  Always use the bburl setting as the base path regardless of admin options.
* 	(Unless already an absolute path).  Primarily used in the archives where its currently
* 	hardcoded.
*
* @param	string	Fully-qualified URL
*/
function create_full_url($url = '', $force_bburl = false)
{
	global $vbulletin;

	// enforces HTTP 1.1 compliance
	if (!preg_match('#^[a-z]+(?<!about|javascript|vbscript|data)://#i', $url))
	{
		if ('/' == $url{0})
		{
			$url = vB::getRequest()->getVbUrlWebroot() . $url;
		}
		else
		{
			$url = $vbulletin->input->fetch_relpath($url);

			if ($force_bburl)
			{
				$base = vB::getDatastore()->getOption('bburl') . "/";
			}
			//these areas depend on the redirection being done against the VB_URL_BASE_PATH path explicitly.
			else if (defined('VB_AREA') AND in_array(VB_AREA, array('Install', 'Upgrade', 'AdminCP', 'ModCP', 'Archive', 'Unit Test', 'tools')))
			{
				$base = VB_URL_BASE_PATH;
			}
			else
			{
				$base = $vbulletin->input->fetch_basepath();
			}

			if (strtolower(substr($base, 0, 4)) != 'http')
			{
				$base = vB::getRequest()->getVbUrlScheme() . $base;
			}
			$url = $base . ltrim($url, ':/\\');
		}
	}
	else
	{
		$url = $vbulletin->cleaner->xssCleanUrl($url);
	}

	// Collapse ../ and ./
	$url = normalize_path($url);

	return $url;
}


/**
 * Adds a query string to a path, fixing the query characters.
 *
 * @param 	string		The path to add the query to
 * @return	string		The resulting string
 */
function add_query($path, $query = false)
{
	if (false === $query)
	{
		$query = VB_URL_QUERY;
	}

	if (!$query OR !($query = trim($query, '?&')))
	{
		return $path;
	}

	return $path . '?' . $query;
}


/**
 * Collapses ../ and ./ in a path.
 *
 * @param	string		The path to normalize
 * @return	string		The nromalized path
 */
function normalize_path($path)
{
	// Collapse ../
	$path = preg_replace('#\w+\/\.\.\/#', '', $path);

	// Collapse ./
	$path = preg_replace('#\/\.\/#', '/', $path);

	return $path;
}


// #############################################################################
/**
* Fetches a number of templates from the database and puts them into the templatecache
*
* @param	array	List of template names to be fetched
* @param	string	Serialized array of template name => template id pairs
* @param	bool	Whether to skip adding the bbcode style refs
*/
function cache_templates($templates, $templateidlist, $skip_bbcode_style = false)
{
	vB_Api::instanceInternal('template')->cacheTemplates($templates, $templateidlist, $skip_bbcode_style);
}

// #############################################################################
/**
* Sets various time and date related variables according to visitor's preferences
*
* Sets $timediff, $datenow, $timenow, $copyrightyear
*/
function fetch_time_data()
{
	global $vbulletin, $timediff, $datenow, $timenow, $copyrightyear;
        $options = vB::getDatastore()->get_value('options');
	$vbulletin->userinfo['tzoffset'] = $vbulletin->userinfo['timezoneoffset']; // preserve timzoneoffset for profile editing and proper event display

	if ($vbulletin->userinfo['dstonoff'])
	{
		// DST is on, add an hour
		$vbulletin->userinfo['tzoffset']++;

		if (substr($vbulletin->userinfo['tzoffset'], 0, 1) != '-')
		{
			// recorrect so that it has + sign, if necessary
			$vbulletin->userinfo['tzoffset'] = '+' . $vbulletin->userinfo['tzoffset'];
		}
	}

	// some stuff for the gmdate bug
	$options['hourdiff'] = (date('Z', vB::getRequest()->getTimeNow()) / 3600 - $vbulletin->userinfo['tzoffset']) * 3600;

	if ($vbulletin->userinfo['tzoffset'])
	{
		if ($vbulletin->userinfo['tzoffset'] > 0 AND strpos($vbulletin->userinfo['tzoffset'], '+') === false)
		{
			$vbulletin->userinfo['tzoffset'] = '+' . $vbulletin->userinfo['tzoffset'];
		}
		if (abs($vbulletin->userinfo['tzoffset']) == 1)
		{
			$timediff = ' ' . $vbulletin->userinfo['tzoffset'] . ' hour';
		}
		else
		{
			$timediff = ' ' . $vbulletin->userinfo['tzoffset'] . ' hours';
		}
	}
	else
	{
		$timediff = '';
	}

	$datenow       = vbdate($options['dateformat'], vB::getRequest()->getTimeNow());
	$timenow       = vbdate($options['timeformat'], vB::getRequest()->getTimeNow());
	$copyrightyear = vbdate('Y', vB::getRequest()->getTimeNow(), false, false);
}

// #############################################################################
/**
* Formats a UNIX timestamp into a human-readable string according to vBulletin prefs
*
* Note: Ifvbdate() is called with a date format other than than one in $vbulletin->options[],
* set $locale to false unless you dynamically set the date() and strftime() formats in the vbdate() call.
*
* @param	string	Date format string (same syntax as PHP's date() function)
* @param	integer	Unix time stamp. Note, if this value is 0, it will use the current time from vB::getRequest()->getTimeNow()
* @param	boolean	If true, attempt to show strings like "Yesterday, 12pm" instead of full date string
* @param	boolean	If true, and user has a language locale, use strftime() to generate language specific dates
* @param	boolean	If true, don't adjust time to user's adjusted time .. (think gmdate instead of date!)
* @param	boolean	If true, uses gmstrftime() and gmdate() instead of strftime() and date()
* @param array    If set, use specified info instead of $vbulletin->userinfo
*
* @return	string	Formatted date string
*/
function vbdate($format, $timestamp = 0, $doyestoday = false, $locale = true, $adjust = true, $gmdate = false, $userinfo = '')
{
	global $vbulletin, $vbphrase;

	if (!$timestamp)
	{
		$timestamp = vB::getRequest()->getTimeNow();
	}

	$uselocale = false;
        $options = vB::getDatastore()->getValue('options');
	if (defined('VB_API') AND VB_API === true)
	{
		$doyestoday = false;
	}

	if (!is_array($userinfo) OR empty($userinfo))
	{
		$userinfo = vB::getCurrentSession()->fetch_userinfo();
	}

	if ($userinfo['lang_locale'])
	{
		$uselocale = true;
		$currentlocale = setlocale(LC_TIME, 0);
		setlocale(LC_TIME, $userinfo['lang_locale']);
		if (substr($userinfo['lang_locale'], 0, 5) != 'tr_TR')
		{
			setlocale(LC_CTYPE, $userinfo['lang_locale']);
		}
	}
	if ($userinfo['dstonoff'] OR ($userinfo['dstauto'] AND $options['dstonoff']))
	{
		// DST is on, add an hour
		$userinfo['timezoneoffset']++;
		if (substr($userinfo['timezoneoffset'], 0, 1) != '-')
		{
			// recorrect so that it has a + sign, if necessary
			$userinfo['timezoneoffset'] = '+' . $userinfo['timezoneoffset'];
		}
	}

	$hourdiff = (date('Z', vB::getRequest()->getTimeNow()) / 3600 - $userinfo['timezoneoffset']) * 3600;

	if ($uselocale AND $locale AND !(defined('VB_API') AND VB_API === true))
	{
		if ($gmdate)
		{
			$datefunc = 'gmstrftime';
		}
		else
		{
			$datefunc = 'strftime';
		}
	}
	else
	{
		if ($gmdate)
		{
			$datefunc = 'gmdate';
		}
		else
		{
			$datefunc = 'date';
		}
	}
	if (!$adjust)
	{
		$hourdiff = 0;
	}
	$timestamp_adjusted = max(0, $timestamp - $hourdiff);

	if ($format == $options['dateformat'] AND $doyestoday AND $options['yestoday'])
	{
		if ($options['yestoday'] == 1)
		{
			if (!defined('TODAYDATE'))
			{
				define ('TODAYDATE', vbdate('n-j-Y', vB::getRequest()->getTimeNow(), false, false));
				define ('YESTDATE', vbdate('n-j-Y', vB::getRequest()->getTimeNow() - 86400, false, false));
				define ('TOMDATE', vbdate('n-j-Y', vB::getRequest()->getTimeNow() + 86400, false, false));
			}

			$datetest = @date('n-j-Y', $timestamp - $hourdiff);

			if ($datetest == TODAYDATE)
			{
				$returndate = $vbphrase['today'];
			}
			else if ($datetest == YESTDATE)
			{
				$returndate = $vbphrase['yesterday'];
			}
			else
			{
				$returndate = $datefunc($format, $timestamp_adjusted);
			}
		}
		else
		{
			$timediff = vB::getRequest()->getTimeNow() - $timestamp;

			if ($timediff >= 0)
			{
				if ($timediff < 120)
				{
					$returndate = $vbphrase['1_minute_ago'];
				}
				else if ($timediff < 3600)
				{
					$returndate = construct_phrase($vbphrase['x_minutes_ago'], intval($timediff / 60));
				}
				else if ($timediff < 7200)
				{
					$returndate = $vbphrase['1_hour_ago'];
				}
				else if ($timediff < 86400)
				{
					$returndate = construct_phrase($vbphrase['x_hours_ago'], intval($timediff / 3600));
				}
				else if ($timediff < 172800)
				{
					$returndate = $vbphrase['1_day_ago'];
				}
				else if ($timediff < 604800)
				{
					$returndate = construct_phrase($vbphrase['x_days_ago'], intval($timediff / 86400));
				}
				else if ($timediff < 1209600)
				{
					$returndate = $vbphrase['1_week_ago'];
				}
				else if ($timediff < 3024000)
				{
					$returndate = construct_phrase($vbphrase['x_weeks_ago'], intval($timediff / 604900));
				}
				else
				{
					$returndate = $datefunc($format, $timestamp_adjusted);
				}
			}
			else
			{
				$returndate = $datefunc($format, $timestamp_adjusted);
			}
		}
	}
	else
	{
		$returndate = $datefunc($format, $timestamp_adjusted);
	}

	if (!empty($userinfo['lang_locale']))
	{
		setlocale(LC_TIME, $currentlocale);
		if (substr($currentlocale, 0, 5) != 'tr_TR')
		{
			setlocale(LC_CTYPE, $currentlocale);
		}
	}
	return $returndate;
}

// #############################################################################
/**
* Returns a string where HTML entities have been converted back to their original characters
*
* @deprecated
* @see vB_String::unHtmlSpecialChars()
* @param	string	String to be parsed
* @param	boolean	Convert unicode characters back from HTML entities?
*
* @return	string
*/
function unhtmlspecialchars($text, $doUniCode = false)
{
	return vB_String::unHtmlSpecialChars($text, $doUniCode);
}

// #############################################################################
/** PHP 5.5
 * Callback function for preg_replace callbacks of convert_int_to_utf8()
 *
 * @return bool
 */
function convert_int_to_utf8_callback($matches)
{
	return convert_int_to_utf8($matches[1]);
}

// #############################################################################
/**
 * Checks if PCRE supports unicode
 *
 * @return bool
 */
function is_pcre_unicode()
{
	static $enabled;

	if (NULL !== $enabled)
	{
		return $enabled;
	}

	return $enabled = @preg_match('#\pN#u', '1');
}

/**
* Converts an integer into a UTF-8 character string
*
* @deprecated
* @see vB_String::convertIntToUtf8()
* @param	integer	Integer to be converted
*
* @return	string
*/
function convert_int_to_utf8($intval)
{
	return vB_String::convertIntToUtf8($intval);
}

// #############################################################################
/**
* Converts Unicode entities of the format %uHHHH where each H is a hexadecimal
* character to &#DDDD; or the appropriate UTF-8 character based on current charset.
*
* @param	Mixed		array or text
*
* @return	string	Decoded text
*/
function convert_urlencoded_unicode($text)
{
	if (is_array($text))
	{
		foreach ($text AS $key => $value)
		{
			$text["$key"] = convert_urlencoded_unicode($value);
		}
		return $text;
	}

	if (!($charset = vB_Template_Runtime::fetchStyleVar('charset')))
	{
		$session = vB::getCurrentSession();
		if ($session)
		{
			$userInfo = $session->fetch_userinfo();
			$charset = $userInfo['lang_charset'];
		}
		else
		{
			$charset = 'utf-8';
		}
	}

	$return = preg_replace_callback('#%u([0-9A-F]{1,4})#i',
		function($matches) use ($charset)
		{
			return vB_String::convertUnicodeCharToCharset(hexdec($matches[1]), $charset);
		},
		$text
	);

	$lower_charset = strtolower($charset);

	if ($lower_charset != 'utf-8' AND function_exists('html_entity_decode'))
	{
		// this converts certain &#123; entities to their actual character
		// set values; don't do this if using UTF-8 as it's already done above.
		// note: we don't want to convert &gt;, etc as that undoes the effects of STR_NOHTML
		$return = preg_replace('#&([a-z]+);#i', '&amp;$1;', $return);

		if ($lower_charset == 'windows-1251')
		{
			// there's a bug in PHP5 html_entity_decode that decodes some entities that
			// it shouldn't. So double encode them to ensure they don't get decoded.
			$return = preg_replace('/&#(128|129|1[3-9][0-9]|2[0-4][0-9]|25[0-5]);/', '&amp;#$1;', $return);
		}

		$return = @html_entity_decode($return, ENT_NOQUOTES, $charset);
	}

	return $return;
}

/**
* Converts a single unicode character to the desired character set if possible.
* Attempts to use iconv if it's available.
* Callback function for the regular expression in convert_urlencoded_unicode.
*
* @deprecated
* @see vB_String::convertUnicodeCharToCharset()
* @param	integer	Unicode code point value
* @param	string	Character to convert to
*
* @return	string	Character in desired character set or as an HTML entity
*/
function convert_unicode_char_to_charset($unicode_int, $charset)
{
	return vB_String::convertUnicodeCharToCharset($unicode_int, $charset);
}

/**
* Poor man's urlencode that only encodes specific characters and preserves unicode.
* Use urldecode() to decode.
*
* @param	string	String to encode
* @return	string	Encoded string
*/
function urlencode_uni($str)
{
	return preg_replace_callback(
		'`([\s/\\\?:@=+$,<>\%"\'\.\r\n\t\x00-\x1f\x7f]|(?(?<!&)#|#(?![0-9]+;))|&(?!#[0-9]+;)|(?<!&#\d|&#\d{2}|&#\d{3}|&#\d{4}|&#\d{5});)`',
		function($matches)
		{
			return urlencode($matches[1]);
		},
		$str
	);
}

/**
 * Converts a string to utf8
 *
 * @deprecated
 * @see vB_String::toUtf8()
 * @param	string	The variable to clean
 * @param	string	The source charset
 * @param	bool	Whether to strip invalid utf8 if we couldn't convert
 * @return	string	The reencoded string
 */
function to_utf8($in, $charset = false, $strip = true)
{
	return vB_String::toUtf8($in, $charset, $strip);
}

/**
 * Converts a string from one character encoding to another.
 * If the target encoding is not specified then it will be resolved from the current
 * language settings.
 *
 * @deprecated
 * @see vB_String::toCharset()
 * @param	string	The string to convert
 * @param	string	The source encoding
 * @return	string	The target encoding
 */
function to_charset($in, $in_encoding, $target_encoding = false)
{
	return vB_String::toCharset($in, $in_encoding, $target_encoding);
}

/**
 * Strips NCRs from a string.
 *
 * @deprecated
 * @see vB_String::stripNcrs()
 * @param	string	The string to strip from
 * @return	string	The result
 */
function stripncrs($str)
{
	return vB_String::stripNcrs($str);
}

/**
 * Converts a UTF-8 string into unicode NCR equivelants.
 *
 * @param	string	String to encode
 * @param	bool	Only ncrencode unicode bytes
 * @param	bool	If true and $skip_ascii is true, it will skip windows-1252 extended chars
 * @return	string	Encoded string
 */
function ncrencode($str, $skip_ascii = false, $skip_win = false)
{
	if (!$str)
	{
		return $str;
	}

	if (function_exists('mb_encode_numericentity'))
	{
		if ($skip_ascii)
		{
			if ($skip_win)
			{
				$start = 0xFE;
			}
			else
			{
				$start = 0x80;
			}
		}
		else
		{
			$start = 0x0;
		}
		return mb_encode_numericentity($str, array($start, 0xffff, 0, 0xffff), 'UTF-8');
	}

	if (is_pcre_unicode())
	{
		// @todo replace create_function with an anonymous function
		return preg_replace_callback(
			'#\X#u',
			create_function('$matches', 'return ncrencode_matches($matches, ' . (int)$skip_ascii . ', ' . (int)$skip_win . ');'),
			$str
		);
	}

	return $str;
}

/**
 * NCR encodes matches from a preg_replace.
 * Single byte characters are preserved.
 *
 * @param	string	The character to encode
 * @return	string	The encoded character
 */
function ncrencode_matches($matches, $skip_ascii = false, $skip_win = false)
{
	$ord = ord_uni($matches[0]);

	if ($skip_win)
	{
		$start = 254;
	}
	else
	{
		$start = 128;
	}

	if ($skip_ascii AND $ord < $start)
	{
		return $matches[0];
	}

	return '&#' . ord_uni($matches[0]) . ';';
}

/**
 * Gets the Unicode Ordinal for a UTF-8 character.
 *
 * @param	string	Character to convert
 * @return	int		Ordinal value or false if invalid
 */
function ord_uni($chr)
{
	// Valid lengths and first byte ranges
	static $check_len = array(
		1 => array(0, 127),
		2 => array(192, 223),
		3 => array(224, 239),
		4 => array(240, 247),
		5 => array(248, 251),
		6 => array(252, 253)
	);

	// Get length
	$blen = strlen($chr);

	// Get single byte ordinals
	$b = array();
	for ($i = 0; $i < $blen; $i++)
	{
		$b[$i] = ord($chr[$i]);
	}

	// Check expected length
	foreach ($check_len AS $len => $range)
	{
		if (($b[0] >= $range[0]) AND ($b[0] <= $range[1]))
		{
			$elen = $len;
		}
	}

	// If no range found, or chr is too short then it's invalid
	if (!isset($elen) OR ($blen < $elen))
	{
		return false;
	}

	// Normalise based on octet-sequence length
	switch ($elen)
	{
		case (1):
			return $b[0];
		case (2):
			return ($b[0] - 192) * 64 + ($b[1] - 128);
		case (3):
			return ($b[0] - 224) * 4096 + ($b[1] - 128) * 64 + ($b[2] - 128);
		case (4):
			return ($b[0] - 240) * 262144 + ($b[1] - 128) * 4096 + ($b[2] - 128) * 64 + ($b[3] - 128);
		case (5):
			return ($b[0] - 248) * 16777216 + ($b[1] - 128) * 262144 + ($b[2] - 128) * 4096 + ($b[3] - 128) * 64 + ($b[4] - 128);
		case (6):
			return ($b[0] - 252) * 1073741824 + ($b[1] - 128) * 16777216 + ($b[2] - 128) * 262144 + ($b[3] - 128) * 4096 + ($b[4] - 128) * 64 + ($b[5] - 128);
	}
}

// #############################################################################
/**
* Stuffs a message into the $DEVDEBUG array
*
* @param	string	Message to store
*/
function devdebug($text = '')
{
	/* TODO, Disabled for now.
	Confirm this isnt needed, if so, remove all usage from vb5 */

	return;
//	global $vbulletin;

//	$vb5_config =& vB::getConfig();

//	if ($vb5_config['Misc']['debug'])
//	{
//		$GLOBALS['DEVDEBUG'][] = $text;
//	}
}

// #############################################################################
/**
* Sends the appropriate HTTP headers for the page that is being displayed
*
* @param	boolean	If true, send HTTP 200
* @param	boolean	If true, send no-cache headers
*/
function exec_headers($headers = true, $nocache = true)
{
	global $vbulletin;
        $options = vB::getDatastore()->getValue('options');
	$contenttype = $vbulletin->contenttype ? $vbulletin->contenttype : 'text/html';

	$sendcontent = true;
	if ($options['addheaders'] AND !$vbulletin->noheader AND $headers)
	{
		// default headers
		if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
		{
			header('Status: 200 OK');
		}
		else
		{
			header('HTTP/1.1 200 OK');
		}
		@header('Content-Type: ' . $contenttype . iif($vbulletin->userinfo['lang_charset'] != '', '; charset=' . $vbulletin->userinfo['lang_charset']));
		$sendcontent = false;
	}

	if ($options['nocacheheaders'] AND !$vbulletin->noheader AND $nocache)
	{
		// no caching
		exec_nocache_headers($sendcontent);
	}
	else if (!$vbulletin->noheader)
	{
		@header("Cache-Control: private");
		@header("Pragma: private");
		if ($sendcontent)
		{
			$charset = $vbulletin->userinfo['lang_charset'] ? $vbulletin->userinfo['lang_charset'] : vB_Template_Runtime::fetchStyleVar('charset');
			@header('Content-Type: ' . $contenttype . '; charset=' . $charset);
		}
	}
}

// #############################################################################
/**
* Sends no-cache HTTP headers
*
* @param	boolean	If true, send content-type header
*/
function exec_nocache_headers($sendcontent = true)
{
	global $vbulletin;
	static $sentheaders;

	if (!$sentheaders)
	{
		@header("Expires: 0"); // Date in the past
		#@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		#@header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
		@header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", false);
		@header("Pragma: no-cache"); // HTTP/1.0
		if ($sendcontent)
		{
			@header('Content-Type: text/html' . iif($vbulletin->userinfo['lang_charset'] != '', '; charset=' . $vbulletin->userinfo['lang_charset']));
		}
	}

	$sentheaders = true;
}

// #############################################################################
/**
* Returns whether or not the visiting user can view the specified password-protected forum
*
* @param	integer	Forum ID
* @param	string	Provided password
* @param	boolean	If true, show error when access is denied
*
* @return	boolean
*/
function verify_forum_password($forumid, $password, $showerror = true)
{
	global $vbulletin;

	if (!$password OR ($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']) OR ($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['ismoderator']) OR can_moderate($forumid))
	{
		return true;
	}

	$foruminfo = fetch_foruminfo($forumid);
	$parents = explode(',', $foruminfo['parentlist']);
	if (!VB_API)
	{
		foreach ($parents AS $fid)
			{
				// get the pwd from any parent forums -- allows pwd cookies to cascade down
			if ($temp = fetch_bbarray_cookie('forumpwd', $fid) AND $temp === md5($vbulletin->userinfo['userid'] . $password))
			{
				return true;
			}
		}
	}
	else
	{
		$forumpwdmd5 = $vbulletin->input->clean_gpc('r', 'forumpwdmd5', vB_Cleaner::TYPE_STR);
		if ($forumpwdmd5 === md5($vbulletin->userinfo['userid'] . $password))
		{
			return true;
		}
	}

	// didn't match the password in any cookie
	if ($showerror)
	{
		require_once(DIR . '/includes/functions_misc.php');

		$security_token_html = '<input type="hidden" name="securitytoken" value="' . $vbulletin->userinfo['securitytoken'] . '" />';

		// forum password is bad - show error

		//use the basic link here.  I'm not sure how the advanced link will play with the postvars in the form.
		require_once(DIR . '/includes/class_friendly_url.php');
		$forumlink = vB_Friendly_Url::fetchLibrary($vbulletin, 'forum|nosession',
			$foruminfo, array('do' => 'doenterpwd'));
		$forumlink = $forumlink->get_url(FRIENDLY_URL_OFF);
		// TODO convert the 'forumpasswordmissoing' phrase to vB4
		eval(standard_error(fetch_error('forumpasswordmissing',
			vB::getCurrentSession()->get('sessionhash'),
			$vbulletin->scriptpath,
			$forumid,
			construct_post_vars_html() . $security_token_html,
			10,
			1,
			$forumlink
		)));
	}
	else
	{
		// forum password is bad - return false
		return false;
	}
}

// #############################################################################
/**
* Returns whether or not the user requires a human verification test to complete the specified action
*
* @param string $action						The name of the action to check
* @return boolean							Whether a hv check is required
*/
function fetch_require_hvcheck($action)
{
    $options = vB::getDatastore()->getValue('options');
	$bf_misc_hvcheck = vB::getDatastore()->getValue('bf_misc_hvcheck');

	if (!$options['hv_type']
		OR !($options['hvcheck'] & $bf_misc_hvcheck[$action]))
	{
		return false;
	}

	$usercontext = vB::getUserContext();

	switch ($action)
	{
		case 'register':
		{
			$guestuser = array(
				'userid'      => 0,
				'usergroupid' => 0,
			);
			cache_permissions($guestuser);

			return $usercontext->hasPermission('genericoptions', 'requirehvcheck');
		}

		case 'lostpw':
		{
			if ($usercontext->isAdministrator())
			{
				return false;
			}
			break;
		}
	}

	return $usercontext->hasPermission('genericoptions', 'requirehvcheck');
}

// #############################################################################
/**
* Converts a bitfield into an array of 1 / 0 values based on the array describing the resulting fields
*
* @param	integer	(ref) Bitfield
* @param	array	Array containing field definitions - array('canx' => 1, 'cany' => 2, 'canz' => 4) etc
*
* @return	array
*/
function convert_bits_to_array(&$bitfield, $_FIELDNAMES)
{
	$bitfield = intval($bitfield);
	$arry = array();
	foreach ($_FIELDNAMES AS $field => $bitvalue)
	{
		if ($bitfield & $bitvalue)
		{
			$arry["$field"] = 1;
		}
		else

		{
			$arry["$field"] = 0;
		}
	}
	return $arry;
}

// #############################################################################
/**
* Returns the full set of permissions for the specified user (called by global or init)
*
* @param	array	(ref) User info array
* @param	boolean	If true, returns combined usergroup permissions, individual forum permissions, individual calendar permissions and attachment permissions
* @param boolean        Reset the accesscache array for permissions following access mask update. Only allows one reset.
*
* @return	array	Permissions component of user info array
*/
function cache_permissions(&$user, $getforumpermissions = true, $resetaccess = false)
{
	global $vbulletin, $forumpermissioncache;
        $options = vB::getDatastore()->getValue('options');
	// these are the arrays created by this function

	//this is only set if we load the calendar perms, which have been moved to another function
	//global $calendarcache;

	static $accesscache = array(), $reset= false;

	if ($resetaccess AND !$reset)
	{	// Reset the accesscache array for permissions following access mask update. Only allows one reset.
		$accesscache = array();
		$reset = true;
	}

	$intperms = array();

	// set the usergroupid of the user's primary usergroup
	$USERGROUPID = $user['usergroupid'];

	if ($USERGROUPID == 0)
	{ // set a default usergroupid if none is set
		$USERGROUPID = 1;
	}

	// initialise $membergroups - make an array of the usergroups to which this user belongs
	$membergroupids = fetch_membergroupids_array($user);

	// build usergroup permissions
	if (sizeof($membergroupids) == 1 OR !($vbulletin->usergroupcache["$USERGROUPID"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['allowmembergroups']))
	{
		// if primary usergroup doesn't allow member groups then get rid of them!
		$membergroupids = array($USERGROUPID);

		// just return the permissions for the user's primary group (user is only a member of a single group)
		$user['permissions'] = $vbulletin->usergroupcache["$USERGROUPID"];
	}
	else
	{
		// initialise fields to 0
		foreach ($vbulletin->bf_ugp AS $dbfield => $permfields)
		{
			$user['permissions']["$dbfield"] = 0;
		}

		// return the merged array of all user's membergroup permissions (user has additional member groups)
		foreach ($membergroupids AS $usergroupid)
		{
			foreach ($vbulletin->bf_ugp AS $dbfield => $permfields)
			{
				//'createpermissions is new in vB5 and won't be available.
				if ($dbfield != 'createpermissions')
				{
					$user['permissions']["$dbfield"] |= $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
				}
			}
			foreach ($vbulletin->bf_misc_intperms AS $dbfield => $precedence)
			{
				// put in some logic to handle $precedence
				if (!isset($intperms["$dbfield"]))
				{
					$intperms["$dbfield"] = $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
				}
				else if (!$precedence)
				{
					if ($vbulletin->usergroupcache["$usergroupid"]["$dbfield"] > $intperms["$dbfield"])
					{
						$intperms["$dbfield"] = $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
					}
				}
				else if ($vbulletin->usergroupcache["$usergroupid"]["$dbfield"] == 0 OR (isset($intperms["$dbfield"]) AND $intperms["$dbfield"] == 0)) // Set value to 0 as it overrides all
				{
					$intperms["$dbfield"] = 0;
				}
				else if ($vbulletin->usergroupcache["$usergroupid"]["$dbfield"] > $intperms["$dbfield"])
				{
					$intperms["$dbfield"] = $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
				}
			}
		}
		$user['permissions'] = array_merge($vbulletin->usergroupcache["$USERGROUPID"], $user['permissions'], $intperms);
	}

	if (!empty($user['infractiongroupids']))
	{
		$infractiongroupids = explode(',', str_replace(' ', '', $user['infractiongroupids']));
	}
	else
	{
		$infractiongroupids = array();
	}

	foreach ($infractiongroupids AS $usergroupid)
	{
		foreach ($vbulletin->bf_ugp AS $dbfield => $permfields)
		{
			$user['permissions']["$dbfield"] &= $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
		}
		foreach ($vbulletin->bf_misc_intperms AS $dbfield => $precedence)
		{
			if (!$precedence)
			{
				if ($vbulletin->usergroupcache["$usergroupid"]["$dbfield"] < $user['permissions']["$dbfield"])
				{
					$user['permissions']["$dbfield"] = $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
				}
			}
			else if ($vbulletin->usergroupcache["$usergroupid"]["$dbfield"] < $user['permissions']["$dbfield"] AND $vbulletin->usergroupcache["$usergroupid"]["$dbfield"] != 0)
			{
				$user['permissions']["$dbfield"] = $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
			}
		}
	}

	if (defined('SKIP_SESSIONCREATE') AND $user['userid'] == $vbulletin->userinfo['userid'] AND !($user['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
	{	// grant canview for usergroup if session skipping is defined.
		$user['permissions']['forumpermissions'] += $vbulletin->bf_ugp_forumpermissions['canview'];
	}

	// Legacy Hook 'cache_permissions' Removed //

	// if we do not need to grab the forum/calendar permissions
	// then just return what we have so far
	if ($getforumpermissions == false)
	{
		return $user['permissions'];
	}

	if (!isset($user['channelpermissions']) OR !is_array($user['channelpermissions']))
	{
		$user['channelpermissions'] = array();
	}

	$channels = vB_Cache::instance(vB_Cache::CACHE_STD)->read('vB_ChannelStructure');
	if (empty($channels))
	{
		$channels = vB_Api::instanceInternal('search')->getChannels(true);

		// remove unrequired info to reduce cache size
		$requiredFields = array('nodeid', 'title');
		foreach ($channels as $nodeid => $channel)
		{
			$newValue = array();
			foreach($requiredFields AS $field)
			{
				if(isset($channel[$field]))
				{
					$newValue[$field] = $channel[$field];
				}
			}
			$channels[$nodeid] = $newValue;
		}

		vB_Cache::instance(vB_Cache::CACHE_STD)->write('vB_ChannelStructure', $channels, 1440, 'vB_ChannelStructure_chg');
	}

	$permission_context = new vB_PermissionContext(vB::getDatastore(), $user['usergroupid']);

	foreach ($channels AS $nodeid => $channel)
	{
		if (!isset($user['channelpermissions']["$nodeid"]))
		{
			$user['channelpermissions']["$nodeid"] = 0;
		}
		foreach ($membergroupids AS $usergroupid)
		{

			$user['channelpermissions']["$nodeid"] |= $permission_context->getChannelPermSet($usergroupid, $nodeid);
		}
		foreach ($infractiongroupids AS $usergroupid)
		{
			$user['channelpermissions']["$nodeid"] &= $permission_context->getChannelPermSet($usergroupid, $nodeid);
		}
	}

	// do access mask stuff if required
	if ($options['enableaccess'] AND isset($user['hasaccessmask']) AND $user['hasaccessmask'] == 1)
	{
		if (empty($accesscache["$user[userid]"]))
		{
			// query access masks
			$query = vB::getDbAssertor()->assertQuery('fetchAccessMaskForUser', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'userid' => $user['userid']));
			$accesscache["$user[userid]"] = array();

			while ($query AND $query->valid())
			{
				$access = $query->current();
				$accesscache["$user[userid]"]["$access[nodeid]"] = $access['accessmask'];
				$query->next();
			}
		}

		// if an access mask is set for a channel, set the permissions accordingly
		// If this is empty then the user really has no access masks but the switch is turned on?!?
		if (!empty($accesscache["$user[userid]"]))
		{
			foreach ($accesscache["$user[userid]"] AS $nodeid => $accessmask)
			{
				if ($accessmask == 0) // disable access
				{
					$user['channelpermissions']["$nodeid"] = 0;
				}
				else // use combined permissions
				{
					$user['channelpermissions']["$nodeid"] = $user['permissions']['forumpermissions'];
				}
			}
		}
		else
		{
			// says the user has access masks, but doesn't actually
			// so turn them off
			$userdm = new vB_Datamanager_User(vB::getDatastore(), vB_DataManager_Constants::ERRTYPE_SILENT);
			$userdm->set_existing($user);
			$userdm->set_bitfield('options', 'hasaccessmask', false);
			$userdm->save();
			unset($userdm);
		}

	} // end if access masks enabled and is logged in user

	$calfiles = array(
		'online'   => true,
		'calendar' => true,
		'index'    => false,
	);

	// query calendar permissions
	if (defined('THIS_SCRIPT') AND !empty($calfiles[THIS_SCRIPT]))
	{
		// Only query calendar permissions when accessing the calendar or subscriptions or index.php
		cache_calendar_permissions($user);
	}

	if (!empty($vbulletin->attachmentcache) AND empty($vbulletin->attachmentcache['extensions']))
	{
		$fields = array(
			'size'   => true,
			'width'  => true,
			'height' => true,
		);
		$user['attachmentextensions'] = '';

		// Combine the attachment permissions for all member groups
		foreach($vbulletin->attachmentcache AS $extension => $attachment)
		{
			$need_default = false;
			foreach($membergroupids AS $usergroupid)
			{
				if (!empty($attachment['custom']["$usergroupid"]))
				{
					$perm = $attachment['custom']["$usergroupid"];
					$user['attachmentpermissions']["$extension"]['permissions'] |= $perm['permissions'];

					foreach ($fields AS $dbfield => $precedence)
					{
						// put in some logic to handle $precedence
						if (!isset($user['attachmentpermissions']["$extension"]["$dbfield"]))
						{
							$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
						}
						else if (!$precedence)
						{
							if ($perm["$dbfield"] > $user['attachmentpermissions']["$extension"]["$dbfield"])
							{
								$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
							}
						}
						else if ($perm["$dbfield"] == 0 OR (isset($user['attachmentpermissions']["$extension"]["$dbfield"]) AND $user['attachmentpermissions']["$extension"]["$dbfield"] == 0))
						{
							$user['attachmentpermissions']["$extension"]["$dbfield"] = 0;
						}
						else if ($perm["$dbfield"] > $user['attachmentpermissions']["$extension"]["$dbfield"])
						{
							$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
						}
					}
				}
				else
				{
						$need_default = true;
				}
			}

			if (empty($user['attachmentpermissions']["$extension"]))
			{
				$user['attachmentpermissions']["$extension"] = array(
					'permissions'  => 1,
					'size'         => $vbulletin->attachmentcache["$extension"]['size'],
					'height'       => $vbulletin->attachmentcache["$extension"]['height'],
					'width'        => $vbulletin->attachmentcache["$extension"]['width'],
					'contenttypes' => isset($vbulletin->attachmentcache["$extension"]['contenttypes']) ?
						$vbulletin->attachmentcache["$extension"]['contenttypes'] : null,
				);
			}
			else if ($need_default)
			{
				$user['attachmentpermissions']["$extension"]['permissions'] = 1;
				$perm = $vbulletin->attachmentcache["$extension"];
				foreach ($fields AS $dbfield => $precedence)
				{
					// put in some logic to handle $precedence
					if (!isset($user['attachmentpermissions']["$extension"]["$dbfield"]))
					{
						$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
					}
					else if (!$precedence)
					{
						if ($perm["$dbfield"] > $user['attachmentpermissions']["$extension"]["$dbfield"])
						{
							$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
						}
					}
					else if ($perm["$dbfield"] == 0 OR (isset($user['attachmentpermissions']["$extension"]["$dbfield"]) AND $user['attachmentpermissions']["$extension"]["$dbfield"] == 0))
					{
 						$user['attachmentpermissions']["$extension"]["$dbfield"] = 0;
					}
					else if ($perm["$dbfield"] > $user['attachmentpermissions']["$extension"]["$dbfield"])
					{
						$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
					}
				}
			}

			foreach($infractiongroupids AS $usergroupid)
			{
				if (!empty($attachment['custom']["$usergroupid"]))
				{
					$perm = $attachment['custom']["$usergroupid"];
					$user['attachmentpermissions']["$extension"]['permissions'] &= $perm['permissions'];

					foreach ($fields AS $dbfield => $precedence)
					{
						if (!$precedence)
						{
							if ($perm["$dbfield"] < $user['attachmentpermissions']["$extension"]["$dbfield"])
							{
								$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
							}
						}
						else if ($perm["$dbfield"] < $user['attachmentpermissions']["$extension"]["$dbfield"] AND $perm["$dbfield"] != 0)
						{
							$user['attachmentpermissions']["$extension"]["$dbfield"] = $perm["$dbfield"];
						}
					}
				}
			}
		}

		foreach ($user['attachmentpermissions'] AS $extension => $foo)
		{
			if ($user['attachmentpermissions']["$extension"]['permissions'])
			{
				$user['attachmentextensions'] .= (!empty($user['attachmentextensions']) ? ' ' : '') . $extension;
			}
		}
	}

	return $user['permissions'];
}

/**
* Sets the calendar permissions to the passed user info array
*
* @param	array	(ref) User info array
*
* @return	array	Calendar permissions component of user info array
*/
function cache_calendar_permissions(&$user)
{
	global $calendarcache;
	global $vbulletin;

	$cpermscache = array();
	$calendarcache = array();
	$displayorder = array();

	//we should move this stuff to a user object.
	if (!empty($user['infractiongroupids']))
	{
		$infractiongroupids = explode(',', str_replace(' ', '', $user['infractiongroupids']));
	}
	else
	{
		$infractiongroupids = array();
	}

	// initialise $membergroups - make an array of the usergroups to which this user belongs
	$membergroupids = fetch_membergroupids_array($user);

	// build usergroup permissions
	if (sizeof($membergroupids) == 1 OR
		!($vbulletin->usergroupcache["$user[usergroupid]"]['genericoptions'] &
		$vbulletin->bf_ugp_genericoptions['allowmembergroups'])
	)
	{
		// if primary usergroup doesn't allow member groups then get rid of them!
		$membergroupids = array($user['usergroupid']);
	}

	$calendarpermissions = $vbulletin->db->query_read_slave("
		SELECT calendarpermission.usergroupid, calendarpermission.calendarpermissions,
			calendar.calendarid,calendar.title, displayorder
		FROM " . TABLE_PREFIX . "calendar AS calendar
		LEFT JOIN " . TABLE_PREFIX . "calendarpermission AS calendarpermission ON
			(calendarpermission.calendarid = calendar.calendarid AND
				usergroupid IN (" . implode(', ', $membergroupids) . "))
		ORDER BY displayorder ASC
	");
	while ($cp = $vbulletin->db->fetch_array($calendarpermissions))
	{
		$cpermscache["$cp[calendarid]"]["$cp[usergroupid]"] = intval($cp['calendarpermissions']);
		$calendarcache["$cp[calendarid]"] = $cp['title'];
		$displayorder["$cp[calendarid]"] = $cp['displayorder'];
	}
	$vbulletin->db->free_result($calendarpermissions);

	// Combine the calendar permissions for all member groups
	foreach ($cpermscache AS $calendarid => $cpermissions)
	{
		$user['calendarpermissions']["$calendarid"] = 0;

		if (empty($displayorder["$calendarid"]))
		{
			// leave permissions at 0 for calendars that aren't being displayed
			continue;
		}

		foreach ($membergroupids AS $usergroupid)
		{
			if (isset($cpermissions["$usergroupid"]))
			{
				$user['calendarpermissions']["$calendarid"] |= $cpermissions["$usergroupid"];
			}
			else
			{
				$user['calendarpermissions']["$calendarid"] |= $vbulletin->usergroupcache["$usergroupid"]['calendarpermissions'];
			}
		}
		foreach ($infractiongroupids AS $usergroupid)
		{
			if (isset($cpermissions["$usergroupid"]))
			{
				$user['calendarpermissions']["$calendarid"] &= $cpermissions["$usergroupid"];
			}
			else
			{
				$user['calendarpermissions']["$calendarid"] &= $vbulletin->usergroupcache["$usergroupid"]['calendarpermissions'];
			}
		}
	}
	return $user['calendarpermissions'];
}

// #############################################################################
/**
* Returns permissions for given forum and user
*
* @param	integer	Forum ID
* @param	integer	User ID
* @param	array	User info array
*
* @return	mixed
*/
function fetch_permissions($forumid = 0, $userid = -1, $userinfo = false)
{
	// gets permissions, depending on given userid and forumid
	global $vbulletin, $usercache, $permscache;

	$userid = intval($userid);
	if ($userid == -1)
	{
		$userid = $vbulletin->userinfo['userid'];
		$usergroupid = $vbulletin->userinfo['usergroupid'];
	}

	// ########## #DEBUG# CODE ##############
	$DEBUG_MESSAGE = (isset($GLOBALS['_permsgetter_']) ? "($GLOBALS[_permsgetter_])" : '(unspecified)') .
		" fetch_permissions($forumid, $userid, $usergroupid); ";
	unset($GLOBALS['_permsgetter_']);
	// ########## END #DEBUG# CODE ##############

	if ($userid == $vbulletin->userinfo['userid'])
	{
		// we are getting permissions for $vbulletin->userinfo
		// so return permissions built in querypermissions
		if ($forumid)
		{
			DEVDEBUG($DEBUG_MESSAGE."-> cached fperms for forum $forumid");
			return $vbulletin->userinfo['forumpermissions']["$forumid"];
		}
		else
		{
			DEVDEBUG($DEBUG_MESSAGE.'-> cached combined permissions');
			return $vbulletin->userinfo['permissions'];
		}
	}
	else
	{
	// we are getting permissions for another user...
		if (!is_array($userinfo))
		{
			return 0;
		}
		if ($forumid)
		{
			DEVDEBUG($DEBUG_MESSAGE."-> trying to get forumpermissions for non \$bbuserinfo");
			cache_permissions($userinfo);
			return $userinfo['forumpermissions']["$forumid"];
		}
		else
		{
			DEVDEBUG($DEBUG_MESSAGE."-> trying to get combined permissions for non \$bbuserinfo");
			return cache_permissions($userinfo, false);
		}
	}

}

// #############################################################################
/**
* Returns whether or not the given user can perform a specific moderation action in the specified forum
*
* @param	integer	Forum ID
* @param	string	If you want to check a particular moderation permission, name it here
* @param	integer	User ID
* @param	string	Comma separated list of usergroups to which the user belongs.  We don't need this, but legacy code passes it.
*
* @return	boolean
*/
function can_moderate($forumid = 0, $do = '', $userid = -1, $usergroupids = '')
{
	if ($userid == -1) {
		$userid = vB::getCurrentSession()->get('userid');
	}

	if (empty($forumid)) {
		return vB::getUserContext()->isModerator();
	}
	$node = vB_Library::instance('node')->getNodeBare($forumid);
	$modPerms = vB::getUserContext()->getModeratorPerms($node);

	if (!empty($do)) {
		return (!empty($modPerms[$do]));
	}
	//in this case if the user has any moderator perms we return true;
	foreach ($modPerms AS $key => $perm) {
		if ($perm) {
			return true;
		}
	}
	return false;
}

// #############################################################################
/**
* Returns whether or not vBulletin is running in demo mode
*
* if DEMO_MODE is defined and set to true in config.php this function will return false,
* the main purpose of which is to disable parsing of stuff that is undesirable for a
* board running with a publicly accessible admin control panel
*
* @return	boolean
*/
function is_demo_mode()
{
	return (defined('DEMO_MODE') AND DEMO_MODE == true) ? true : false;
}

// #############################################################################
/**
* Browser detection system - returns whether or not the visiting browser is the one specified
*
* @param	string	Browser name (opera, ie, mozilla, firebord, firefox... etc. - see $is array)
* @param	float	Minimum acceptable version for true result (optional)
*
* @return	boolean
*/
function is_browser($browser, $version = 0)
{
	static $is;
	if (!is_array($is))
	{
		$useragent = strtolower(vB::getRequest()->getUserAgent()); //strtolower($_SERVER['HTTP_USER_AGENT']);
		$is = array(
			'opera'     => 0,
			'ie'        => 0,
			'mozilla'   => 0,
			'firebird'  => 0,
			'firefox'   => 0,
			'camino'    => 0,
			'konqueror' => 0,
			'safari'    => 0,
			'webkit'    => 0,
			'webtv'     => 0,
			'netscape'  => 0,
			'mac'       => 0
		);

		// detect opera
			# Opera/7.11 (Windows NT 5.1; U) [en]
			# Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 5.0) Opera 7.02 Bork-edition [en]
			# Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 4.0) Opera 7.0 [en]
			# Mozilla/4.0 (compatible; MSIE 5.0; Windows 2000) Opera 6.0 [en]
			# Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC) Opera 5.0 [en]
		if (strpos($useragent, 'opera') !== false)
		{
			preg_match('#opera(/| )([0-9\.]+)#', $useragent, $regs);
			$is['opera'] = $regs[2];
		}

		// detect internet explorer
			# Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Q312461)
			# Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.0.3705)
			# Mozilla/4.0 (compatible; MSIE 5.22; Mac_PowerPC)
			# Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC; e504460WanadooNL)
		if (strpos($useragent, 'msie ') !== false AND !$is['opera'])
		{
			preg_match('#msie ([0-9\.]+)#', $useragent, $regs);
			$is['ie'] = $regs[1];
		}

		// detect macintosh
		if (strpos($useragent, 'mac') !== false)
		{
			$is['mac'] = 1;
		}

		// detect safari
			# Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-us) AppleWebKit/74 (KHTML, like Gecko) Safari/74
			# Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/51 (like Gecko) Safari/51
			# Mozilla/5.0 (Windows; U; Windows NT 6.0; en) AppleWebKit/522.11.3 (KHTML, like Gecko) Version/3.0 Safari/522.11.3
			# Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1C28 Safari/419.3
			# Mozilla/5.0 (iPod; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3A100a Safari/419.3
		if (strpos($useragent, 'applewebkit') !== false)
		{
			preg_match('#applewebkit/([0-9\.]+)#', $useragent, $regs);
			$is['webkit'] = $regs[1];

			if (strpos($useragent, 'safari') !== false)
			{
				preg_match('#safari/([0-9\.]+)#', $useragent, $regs);
				$is['safari'] = $regs[1];
			}
		}

		// detect konqueror
			# Mozilla/5.0 (compatible; Konqueror/3.1; Linux; X11; i686)
			# Mozilla/5.0 (compatible; Konqueror/3.1; Linux 2.4.19-32mdkenterprise; X11; i686; ar, en_US)
			# Mozilla/5.0 (compatible; Konqueror/2.1.1; X11)
		if (strpos($useragent, 'konqueror') !== false)
		{
			preg_match('#konqueror/([0-9\.-]+)#', $useragent, $regs);
			$is['konqueror'] = $regs[1];
		}

		// detect mozilla
			# Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.4b) Gecko/20030504 Mozilla
			# Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.2a) Gecko/20020910
			# Mozilla/5.0 (X11; U; Linux 2.4.3-20mdk i586; en-US; rv:0.9.1) Gecko/20010611
		if (strpos($useragent, 'gecko') !== false AND !$is['safari'] AND !$is['konqueror'])
		{
			// See bug #26926, this is for Gecko based products without a build
			$is['mozilla'] = 20090105;
			if (preg_match('#gecko/(\d+)#', $useragent, $regs))
			{
				$is['mozilla'] = $regs[1];
			}

			// detect firebird / firefox
				# Mozilla/5.0 (Windows; U; WinNT4.0; en-US; rv:1.3a) Gecko/20021207 Phoenix/0.5
				# Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4b) Gecko/20030516 Mozilla Firebird/0.6
				# Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4a) Gecko/20030423 Firebird Browser/0.6
				# Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.6) Gecko/20040206 Firefox/0.8
			if (strpos($useragent, 'firefox') !== false OR strpos($useragent, 'firebird') !== false OR strpos($useragent, 'phoenix') !== false)
			{
				preg_match('#(phoenix|firebird|firefox)( browser)?/([0-9\.]+)#', $useragent, $regs);
				$is['firebird'] = $regs[3];

				if ($regs[1] == 'firefox')
				{
					$is['firefox'] = $regs[3];
				}
			}

			// detect camino
				# Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-US; rv:1.0.1) Gecko/20021104 Chimera/0.6
			if (strpos($useragent, 'chimera') !== false OR strpos($useragent, 'camino') !== false)
			{
				preg_match('#(chimera|camino)/([0-9\.]+)#', $useragent, $regs);
				$is['camino'] = $regs[2];
			}
		}

		// detect web tv
		if (strpos($useragent, 'webtv') !== false)
		{
			preg_match('#webtv/([0-9\.]+)#', $useragent, $regs);
			$is['webtv'] = $regs[1];
		}

		// detect pre-gecko netscape
		if (preg_match('#mozilla/([1-4]{1})\.([0-9]{2}|[1-8]{1})#', $useragent, $regs))
		{
			$is['netscape'] = "$regs[1].$regs[2]";
		}
	}

	// sanitize the incoming browser name
	$browser = strtolower($browser);
	if (substr($browser, 0, 3) == 'is_')
	{
		$browser = substr($browser, 3);
	}

	// return the version number of the detected browser if it is the same as $browser
	if ($is["$browser"])
	{
		// $version was specified - only return version number if detected version is >= to specified $version
		if ($version)
		{
			if ($is["$browser"] >= $version)
			{
				return $is["$browser"];
			}
		}
		else
		{
			return $is["$browser"];
		}
	}

	// if we got this far, we are not the specified browser, or the version number is too low
	return 0;
}

// #############################################################################
/**
* Check webserver's make and model
*
* @param	string	Browser name (apache, iis, samber, nginx... etc. - see $is array)
* @param	float	Minimum acceptable version for true result (optional)
*
* @return	boolean
*/
function is_server($server_name, $version = 0)
{
	static $server;

	// Resolve server
	if (!is_array($server))
	{
		$server_name = preg_quote(strtolower($server_name), '#');
		$server = strtolower($_SERVER['SERVER_SOFTWARE']);
		$matches = array();

		if (preg_match("#(.*)(?:/| )([0-9\.]*)#i", $server, $matches))
		{
			$server = array('name' => $matches[1]);
			$server['version'] = (isset($matches[2]) AND $matches[2]) ? $matches[2] : true;
		}
	}

	if (strpos($server['name'], $server_name))
	{
		if (!$version OR (true === $server['version']) OR ($server['version'] >= $version))
		{
			return true;
		}
	}

	return false;
}

// #############################################################################
/**
* Sets up the Fakey stylevars
*
* @param	array	(ref) Style info array
* @param	array	User info array
*
* @return	array
*/
function fetch_stylevars(&$style, $userinfo)
{
	if (is_array($style))
	{
		// if we have a buttons directory override, use it
		if ($userinfo['lang_imagesoverride'])
		{
			vB_Template_Runtime::addStyleVar('imgdir_button', str_replace('<#>', $style['styleid'], $userinfo['lang_imagesoverride']), 'imagedir');
		}
	}

	// get text direction, left/right, and pos/neg values
	if (is_array($userinfo['lang_options']))
	{
		$ltr = (bool) $userinfo['lang_options']['direction'];
		$dirmark = (bool) $userinfo['lang_options']['dirmark'];
	}
	else
	{
		$bitfields = vB::getDatastore()->getValue('bf_misc_languageoptions');
		$ltr = (bool) ($userinfo['lang_options'] & $bitfields['direction']);
		$dirmark = (bool) ($userinfo['lang_options'] & $bitfields['dirmark']);
	}

	set_stylevar_ltr($ltr);

	if ($dirmark)
	{
		vB_Template_Runtime::addStyleVar('dirmark', $ltr ? '&lrm;' : '&rlm;');
	}

	// get the 'lang' attribute for <html> tags
	vB_Template_Runtime::addStyleVar('languagecode', $userinfo['lang_code']);

	// get the 'charset' attribute
	vB_Template_Runtime::addStyleVar('charset', $userinfo['lang_charset']);

}

/**
 *	Sets the ltr/rtl stylevars
 *
 *	@param $ltr bool do we want to render styles as ltr
 */
function set_stylevar_ltr($ltr)
{
	if ($ltr)
	{
		vB_Template_Runtime::addStyleVar('left', 'left');
		vB_Template_Runtime::addStyleVar('right', 'right');
		vB_Template_Runtime::addStyleVar('textdirection', 'ltr');
		vB_Template_Runtime::addStyleVar('pos', '');
		vB_Template_Runtime::addStyleVar('neg', '-');
	}
	else
	{
		vB_Template_Runtime::addStyleVar('left', 'right');
		vB_Template_Runtime::addStyleVar('right', 'left');
		vB_Template_Runtime::addStyleVar('textdirection', 'rtl');
		vB_Template_Runtime::addStyleVar('pos', '-');
		vB_Template_Runtime::addStyleVar('neg', '');
	}
}

// #############################################################################
/**
* Function to override various settings in $vbulletin->options depending on user preferences
*
* @param	array	User info array
*/
function fetch_options_overrides($userinfo)
{
	global $vbulletin;

	$vbulletin->options['default_dateformat'] = $vbulletin->options['dateformat'];
	$vbulletin->options['default_timeformat'] = $vbulletin->options['timeformat'];

	if ($userinfo['lang_dateoverride'] != '')
	{
		$vbulletin->options['dateformat'] = $userinfo['lang_dateoverride'];
	}
	if ($userinfo['lang_timeoverride'] != '')
	{
		$vbulletin->options['timeformat'] = $userinfo['lang_timeoverride'];
	}
	if ($userinfo['lang_registereddateoverride'] != '')
	{
		$vbulletin->options['registereddateformat'] = $userinfo['lang_registereddateoverride'];
	}
	if ($userinfo['lang_calformat1override'] != '')
	{
		$vbulletin->options['calformat1'] = $userinfo['lang_calformat1override'];
	}
	if ($userinfo['lang_calformat2override'] != '')
	{
		$vbulletin->options['calformat2'] = $userinfo['lang_calformat2override'];
	}
	if ($userinfo['lang_logdateoverride'] != '')
	{
		$vbulletin->options['logdateformat'] = $userinfo['lang_logdateoverride'];
	}
	if ($userinfo['lang_locale'] != '')
	{
		$locale1 = setlocale(LC_TIME, $userinfo['lang_locale']);
		if (substr($userinfo['lang_locale'], 0, 5) != 'tr_TR')
		{
			$locale2 = setlocale(LC_CTYPE, $userinfo['lang_locale']);
		}
	}

	if (defined('VB_API') AND VB_API === true)
	{
		// vboptions overwrite for API
		$vbulletin->options['dateformat'] = 'm-d-Y';
		$vbulletin->options['timeformat'] = 'h:i A';
		$vbulletin->options['registereddateformat'] = 'm-d-Y';
	}

}

// #############################################################################
/**
* Returns the initial $vbphrase array
*
* @return	array
*/
function init_language()
{
	global $vbulletin, $phrasegroups;
	global $copyrightyear, $timediff, $timenow, $datenow;
        $options = vB::getDatastore()->getValue('options');
	// define languageid
	define('LANGUAGEID', iif(empty($vbulletin->userinfo['languageid']), $options['languageid'], $vbulletin->userinfo['languageid']));

	// define language direction
	define('LANGUAGE_DIRECTION', iif(($vbulletin->userinfo['lang_options'] & $vbulletin->bf_misc_languageoptions['direction']), 'ltr', 'rtl'));

	// define html language code (lang="xyz")
	define('LANGUAGE_CODE', $vbulletin->userinfo['lang_code']);

	$userInfo = vB::getCurrentSession()->fetch_userinfo();
	// initialize the $vbphrase array
	$vbphrase = array();

	// populate the $vbphrase array with phrase groups
	if (empty($phrasegroups))
	{
		$phrasegroups = array('global');
	}

	foreach ($phrasegroups AS $phrasegroup)
	{
		$tmp = unserialize($userInfo["phrasegroup_$phrasegroup"]);
		if (is_array($tmp))
		{
			$vbphrase = array_merge($vbphrase, $tmp);
		}
	}

	// prepare phrases for construct_phrase / sprintf use
	//$vbphrase = preg_replace('/\{([0-9]+)\}/siU', '%\\1$s', $vbphrase);

	// pre-parse some global phrases
	$tzoffset = iif($vbulletin->userinfo['tzoffset'], ' ' . $vbulletin->userinfo['tzoffset']);
	$vbphrase['all_times_are_gmt_x_time_now_is_y'] = construct_phrase($vbphrase['all_times_are_gmt_x_time_now_is_y'], $tzoffset, $timenow, $datenow);
	$vbphrase['vbulletin_copyright_orig'] = $vbphrase['vbulletin_copyright'];
	$vbphrase['vbulletin_copyright'] = construct_phrase($vbphrase['vbulletin_copyright'], $options['templateversion'], $copyrightyear);
	$vbphrase['powered_by_vbulletin'] = construct_phrase($vbphrase['powered_by_vbulletin'], $options['templateversion'], $copyrightyear);
	$vbphrase['timezone'] = construct_phrase($vbphrase['timezone'], $timediff, $timenow, $datenow);

	// all done
	return $vbphrase;
}

// #############################################################################
/**
* Constructs a language chooser HTML menu
*
* @param	string	Marker to prepend each language name with
* @param	boolean	Whether or not this will build the quick chooser menu
* @param	integer	Reference to the total number of languages available in the chooser
*
* @return	string
*/
function construct_language_options($depthmark = '', $quickchooser = false, &$languagecount = 0)
{
	global $vbulletin, $vbphrase;
        $options = vB::getDatastore()->getValue('options');
	$thislanguageid = ($quickchooser ? $vbulletin->userinfo['languageid'] : $vbulletin->userinfo['reallanguageid']);
	if ($thislanguageid == 0 AND $quickchooser)
	{
		$thislanguageid = $options['languageid'];
	}

	$languagelist = '';
	// set the user's 'real language id'
	if (!isset($vbulletin->userinfo['reallanguageid']))
	{
		$vbulletin->userinfo['reallanguageid'] = $vbulletin->userinfo['languageid'];
	}

	if (!$quickchooser)
	{
		if ($thislanguageid == 0)
		{
			$optionselected = 'selected="selected"';
		}
		$optionvalue = 0;
		$optiontitle = $vbphrase['use_forum_default'];
		$languagelist .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
	}

	if ($vbulletin->languagecache === null)
	{
		$vbulletin->languagecache = array();
	}

	foreach ($vbulletin->languagecache AS $language)
	{
		if ($language['userselect']) # OR $vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
		{
			++$languagecount;
			if ($thislanguageid == $language['languageid'])
			{
				$optionselected = 'selected="selected"';
			}
			else
			{
				$optionselected = '';
			}
			$optionvalue = $language['languageid'];
			$optiontitle = $depthmark . ' ' . $language['title'];
			$optionclass = '';
			$languagelist .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		}
	}

	return $languagelist;
}

// #############################################################################
/**
* Constructs a style chooser HTML menu
*
* @param	integer	Style ID
* @param	string	String repeated before style name to indicate nesting
* @param	boolean	Whether or not to initialize this function (this function is recursive)
* @param	boolean	Whether or not this will build the quick chooser menu
* @param	integer Reference to the total number of styles available in the chooser
*
* @return	string
* @deprecated
*
* I think this can be removed, but I'm not going to do that on this branch.
*/
function construct_style_options($styleid = -1, $depthmark = '', $init = true, $quickchooser = false, &$stylecount = 0)
{
	global $vbulletin, $vbphrase;
	$userInfo = vB::getCurrentSession()->fetch_userinfo();
        $options = vB::getDatastore()->getValue('options');
	$thisstyleid = ($quickchooser ? $userInfo['styleid'] : $userInfo['realstyleid']);
	if ($thisstyleid == 0)
	{
		$thisstyleid = ($quickchooser ? $userInfo['realstyleid'] :$userInfo['styleid']);
	}
	if ($thisstyleid == 0)
	{
		$thisstyleid = $options['styleid'];
	}

	// initialize various vars
	if ($init)
	{
		$stylesetlist = '';
		// set the user's 'real style id'
		if (!isset($userInfo['realstyleid']))
		{
			$userInfo['realstyleid'] = $userInfo['styleid'];
		}

		if (!$quickchooser)
		{
			if ($thisstyleid == 0)
			{
				$optionselected = 'selected="selected"';
			}
			$optionvalue = 0;
			$optiontitle = $vbphrase['use_forum_default'];
			$stylesetlist .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
		}
	}

	// check to see that the current styleid exists
	// and workaround a very very odd bug (#2079)
	try
	{
		$cache = vB_Library::instance('Style')->fetchStyleByID($styleid);
	}
	catch (vB_Exception_Api $e)
	{
		return;
	}

	if ((empty($cache)))
	{
		return;
	}

	// loop through the stylecache to get results
	foreach ($cache AS $x)
	{
		foreach ($x AS $style)
		{
			if ($style['userselect'] OR $vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel'])
			{
				$stylecount++;
				if ($thisstyleid == $style['styleid'])
				{
					$optionselected = 'selected="selected"';
				}
				else
				{
					$optionselected = '';
				}
				$optionvalue = $style['styleid'];
				$optiontitle = $depthmark . ' ' . $style['title'];
				$optionclass = '';
				$stylesetlist .= render_option_template($optiontitle, $optionvalue, $optionselected, $optionclass);
				$stylesetlist .= construct_style_options($style['styleid'], $depthmark . '--', false, $quickchooser, $stylecount);
			}
			else
			{
				$stylesetlist .= construct_style_options($style['styleid'], $depthmark, false, $quickchooser, $stylecount);
			}
		}
	}

	return $stylesetlist;
}

// #############################################################################
/**
* Saves the specified data into the datastore
*
* @param	string	The name of the datastore item to save
* @param	mixed	The data to be saved
* @param	integer 1 or 0 as to whether this value is to be automatically unserialised on retrieval
* @deprecated	use the datastore class directly.
*/
function build_datastore($title = '', $data = '', $unserialize = 0)
{
	vB::getDatastore()->build($title, $data, $unserialize);
}

// #############################################################################
/**
* Checks whether or not user came from search engine
*/
function is_came_from_search_engine()
{
	global $vbulletin;

	static $is_came_from_search_engine;	// hey, you, user, you came from search engine?
        $options = vB::getDatastore()->getValue('options');
	if (!isset($is_came_from_search_engine))
	{
		$user_referrer = $_SERVER['HTTP_REFERER'] . '.';		// we're trusting wherever the user claims they're from, if they lie, we can't do anything about it

		if ($options['searchenginereferrers'] = trim($options['searchenginereferrers']))
		{

			$searchengines = preg_split('#\s+#', $options['searchenginereferrers'], -1, PREG_SPLIT_NO_EMPTY);
			foreach ($searchengines AS $searchengine)
			{
				if (strpos($searchengine, '*') === false AND $searchengine{strlen($searchengine) - 1} != '.')
				{
					$searchengine .= '.';
				}

				$searchengine_regex = str_replace('\*', '(.*)', preg_quote($searchengine, '#'));
				if (preg_match('#' . $searchengine_regex . '#U', $user_referrer))
				{
					$is_came_from_search_engine = true;
					return $is_came_from_search_engine;
				}
			}
		}

		// if nothing to match against (or we didn't return earlier)
		$is_came_from_search_engine = false;
	}

	return $is_came_from_search_engine;
}

// #############################################################################
/**
* Updates the LoadAverage DataStore
*/

function update_loadavg()
{
	global $vbulletin;

	if (!isset($vbulletin->loadcache))
	{
		$vbulletin->loadcache = array();
	}

	if (function_exists('exec') AND $stats = @exec('uptime 2>&1') AND trim($stats) != '' AND preg_match('#: ([\d.,]+),?\s+([\d.,]+),?\s+([\d.,]+)$#', $stats, $regs))
	{
		$vbulletin->loadcache['loadavg'] = $regs[2];
	}
	else if (@file_exists('/proc/loadavg') AND $filestuff = @file_get_contents('/proc/loadavg'))
	{
		$loadavg = explode(' ', $filestuff);

		$vbulletin->loadcache['loadavg'] = $loadavg[1];
	}
	else
	{
 		$vbulletin->loadcache['loadavg'] = 0;
	}

	$vbulletin->loadcache['lastcheck'] = TIMENOW;
	build_datastore('loadcache', serialize($vbulletin->loadcache), 1);
}

// #############################################################################
/**
* Escapes quotes in strings destined for Javascript
*
* @param	string	String to be prepared for Javascript
* @param	string	Type of quote (single or double quote)
*
* @return	string
*/
function addslashes_js($text, $quotetype = "'")
{
	if ($quotetype == "'")
	{
		// single quotes
		$replaced = str_replace(array('\\', '\'', "\n", "\r"), array('\\\\', "\\'","\\n", "\\r"), $text);
	}
	else
	{
		// double quotes
		$replaced = str_replace(array('\\', '"', "\n", "\r"), array('\\\\', "\\\"","\\n", "\\r"), $text);
	}

	$replaced = preg_replace('#(-(?=-))#', "-$quotetype + $quotetype", $replaced);
	$replaced = preg_replace('#</script#i', "<\\/scr$quotetype + {$quotetype}ipt", $replaced);

	return $replaced;
}

// #############################################################################
/**
* Returns the provided string with occurences of replacement variables replaced with their appropriate replacement values
*
* @param	string	Text containing replacement variables
* @param 	array	Override global $style if specified
*
* @return	string
*/
function process_replacement_vars($newtext, $paramstyle = false)
{
	global $vbulletin;
	static $replacementvars;

	if (connection_status())
	{
		exit;
	}

	if (is_array($paramstyle))
	{
		$style =& $paramstyle;
	}
	else
	{
		$style =& $GLOBALS['style'];
	}

	// Legacy Hook 'replacement_vars' Removed //

	// do vBulletin 3 replacement variables
	if (!empty($style['replacements']))
	{
		if (!isset($replacementvars["$style[styleid]"]))
		{
			$replacementvars["$style[styleid]"] = unserialize($style['replacements']);
		}

		if (is_array($replacementvars["$style[styleid]"]) AND !empty($replacementvars["$style[styleid]"]))
		{
			$newtext = preg_replace(array_keys($replacementvars["$style[styleid]"]), $replacementvars["$style[styleid]"], $newtext);
		}
	}

	return $newtext;
}

// #############################################################################
/**
* Finishes off the current page (using templates), prints it out to the browser and halts execution
*
* @param	string	The HTML of the page to be printed
* @param	boolean	Send the content length header?
*/
function print_output($vartext, $sendheader = true)
{
	global $querytime, $vbulletin, $show, $vbphrase;
        $options = vB::getDatastore()->getValue('options');
	$vb5_config =& vB::getConfig();

	if (VB_API)
	{
		$template = vB_Template::create('response');
		$template->register('response', $vartext);
		$vartext = $template->render(true, true);
	}

	if (!VB_API AND $options['addtemplatename'])
	{
		if ($doctypepos = @strpos($vartext, vB_Template_Runtime::fetchStyleVar('htmldoctype')))
		{
			$comment = substr($vartext, 0, $doctypepos);
			$vartext = substr($vartext, $doctypepos + strlen(vB_Template_Runtime::fetchStyleVar('htmldoctype')));
			$vartext = vB_Template_Runtime::fetchStyleVar('htmldoctype') . "\n" . $comment . $vartext;
		}
	}

	if (!VB_API AND (!($vbulletin->db->isExplainEmpty()) OR $vb5_config['Misc']['debug']))
	{
		$totaltime = microtime(true) - TIMESTART;

		$vartext .= "<!-- Page generated in " . vb_number_format($totaltime, 5) . " seconds with " . $vbulletin->db->querycount . " queries -->";
	}

	// set cookies for displayed notices
	if ($show['notices'] AND !defined('NOPMPOPUP') AND !empty($vbulletin->np_notices_displayed) AND is_array($vbulletin->np_notices_displayed))
	{
		$np_notices_cookie = $_COOKIE[COOKIE_PREFIX . 'np_notices_displayed'];
		vbsetcookie('np_notices_displayed',
			($np_notices_cookie ? "$np_notices_cookie," : '') . implode(',', $vbulletin->np_notices_displayed),
			false
		);
	}

	// debug code
	global $DEVDEBUG, $vbcollapse;
	if (!VB_API AND $vb5_config['Misc']['debug'])
	{
		devdebug('php_sapi_name(): ' . SAPI_NAME);

		$messages = '';
		if (is_array($DEVDEBUG))
		{
			foreach($DEVDEBUG AS $debugmessage)
			{
				$messages .= "\t<option>" . htmlspecialchars_uni($debugmessage) . "</option>\n";
			}
		}

		if (!empty(vB_Template::$template_usage))
		{
			$tempusagecache = vB_Template::$template_usage;
			$_TEMPLATEQUERIES = vB_Template::$template_queries;

			unset($tempusagecache['board_inactive_warning'], $_TEMPLATEQUERIES['board_inactive_warning']);

			ksort($tempusagecache);
			foreach ($tempusagecache AS $template_name => $times)
			{
				$tempusagecache["$template_name"] =
					"<span class=\"shade\" style=\"float:right\">($times)</span>" .
						((isset($_TEMPLATEQUERIES["$template_name"]) AND $_TEMPLATEQUERIES["$template_name"]) ?
							"<span style=\"color:red; font-weight:bold\">$template_name</span>" : $template_name);
			}
		}
		else
		{
			$tempusagecache = array();
		}

		$phrase_groups = '';
		sort($GLOBALS['phrasegroups']);
		foreach ($GLOBALS['phrasegroups'] AS $phrase_group)
		{
			$phrase_groups .= '<li class="smallfont">' . $phrase_group . '</li>';
		}
		if (!$phrase_groups)
		{
			$phrase_groups = '<li class="smallfont">&nbsp;</li>';
		}

		$vbcollapse['collapseimg_debuginfo'] = (!empty($vbcollapse['collapseimg_debuginfo']) ? $vbcollapse['collapseimg_debuginfo'] : '');
		$vbcollapse['collapseobj_debuginfo'] = (!empty($vbcollapse['collapseobj_debuginfo']) ? $vbcollapse['collapseobj_debuginfo'] : '');

		$debughtml = "
			<div class=\"block\" id=\"debuginfo\" style=\"width:800px; margin:4px auto;\">
				<h2 class=\"blockhead collapse\">
					<a style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . ";\" href=\"" . htmlspecialchars_uni($vbulletin->input->fetch_relpath()) . "#\" title=\"Close Debug Information\" onclick=\"document.getElementById('debuginfo').parentNode.removeChild(document.getElementById('debuginfo')); return false;\">X</a>
						vBulletin {$options['templateversion']} Debug Information
				</h2>
				<div style=\"border:" . vB_Template_Runtime::fetchStyleVar('blockhead_border') . "; border-top:0;\">
					<div class=\"blockbody\">
						<div class=\"blockrow\">
						<ul style=\"list-style:none; margin:0px; padding:0px\">
							<li class=\"smallfont\" style=\"display:inline; margin-right:8px\"><span class=\"shade\">Page Generation</span> " . vb_number_format($totaltime, 5) . " seconds</li>
							" . (function_exists('memory_get_usage') ? "<li class=\"smallfont\" style=\"display:inline; margin-right:8px\"><span class=\"shade\">Memory Usage</span> " . number_format(memory_get_usage() / 1024) . 'KB</li>' : '') . "
							<li class=\"smallfont\" style=\"display:inline; margin-right:8px\"><span class=\"shade\">Queries Executed</span> " . (empty($_TEMPLATEQUERIES) ? $vbulletin->db->querycount : "<span title=\"Uncached Templates!\" style=\"color:red; font-weight:bold\">{$vbulletin->db->querycount}</span>") . " <a href=\"" . (htmlspecialchars($vbulletin->scriptpath)) . (strpos($vbulletin->scriptpath, '?') === false ? '?' : '&amp;') . "explain=1\" target=\"_blank\" title=\"Explain Queries\">(?)</a></li>
						</ul>
						</div>
					</div>
					<div class=\"blocksubhead collapse\">
						<a style=\"top:5px;\" class=\"collapse\" id=\"collapse_debuginfo_body\" href=\"#top\"><img src=\"" . vB_Template_Runtime::fetchStyleVar('imgdir_button') . "/collapse_40b.png\" alt=\"\" title=\"Collapse Debug Information\" /></a>
						More Information
					</div>
					<div class=\"blockbody\" id=\"debuginfo_body\">
						<div class=\"blockrow\">
							<div style=\"width:48%; float:left;\">
								<div style=\"margin-bottom:6px; font-weight:bold;\">Template Usage (" . sizeof($tempusagecache) . "):</div>
						<ul style=\"list-style:none; margin:0px; padding:0px\"><li class=\"smallfont\">" . implode('</li><li class="smallfont">', $tempusagecache) . "&nbsp;</li></ul>
						<hr style=\"margin:10px 0px 10px 0px\" />

								<div style=\"margin-bottom:6px; font-weight:bold;\">Phrase Groups Available (" . sizeof($GLOBALS['phrasegroups']) . "):</div>
						<ul style=\"list-style:none; margin:0px; padding:0px\">$phrase_groups</ul>
							</div>
							<div style=\"width:48%; float:right;\">
								<div style=\"margin-bottom:6px; font-weight:bold;\">Included Files (" . sizeof($included_files = get_included_files()) . "):</div>
						<ul style=\"list-style:none; margin:0px; padding:0px\"><li class=\"smallfont\">" . implode('</li><li class="smallfont">', str_replace(str_replace('\\', '/', DIR) . '/', '', preg_replace('#^(.*/)#si', '<span class="shade">./\1</span>', str_replace('\\', '/', $included_files)))) . "&nbsp;</li></ul>
						<hr style=\"margin:10px 0px 10px 0px\" />

								<div style=\"margin-bottom:6px; font-weight:bold;\">Hooks Called ($hook_total):</div>
						<ul style=\"list-style:none; margin:0px; padding:0px\">$hook_usage</ul>
							</div>
							<br style=\"clear:both;\" />
						</div>
					</div>
					<div class=\"blockbody\">
						<div class=\"blockrow\">
							<label>Messages:<select style=\"display:block; width:100%\">$messages</select></label>
						</div>
					</div>
				</div>
			</div>
		";

		$vartext = str_replace('</body>', "<!--start debug html-->$debughtml<!--end debug html-->\n</body>", $vartext);
	}
	// end debug code

	$output = process_replacement_vars($vartext);

	if (!VB_API AND ($vb5_config['Misc']['debug'] AND function_exists('memory_get_usage')))
	{
		$output = preg_replace('#(<!--querycount-->Executed <b>\d+</b> queries<!--/querycount-->)#siU', 'Memory Usage: <strong>' . number_format((memory_get_usage() / 1024)) . 'KB</strong>, \1', $output);
	}

	// parse PHP include ##################
	// Legacy Hook 'global_complete' Removed //

	// make sure headers sent returns correctly
	if (ob_get_level() AND ob_get_length())
	{
		ob_end_flush();
	}

	if (defined('VB_API') AND VB_API === true AND !headers_sent() AND $sendheader)
	{
		// API Verification
		global $VB_API_REQUESTS;
		if (!in_array($VB_API_REQUESTS['api_m'], array('login_login', 'login_logout')))
		{
			$sign = md5($output . $vbulletin->apiclient['apiaccesstoken'] . $vbulletin->apiclient['apiclientid'] . $vbulletin->apiclient['secret'] . $options['apikey']);
			@header('Authorization: ' . $sign);
		}

		if (!$vb5_config['Misc']['debug'] OR !$vbulletin->GPC['debug'])
		{
			//  JSON header
			@header('Content-Type: application/json');
		}

	}

	if (!headers_sent())
	{
		if ($options['gzipoutput'])
		{
			$output = fetch_gzipped_text($output, $options['gziplevel']);
		}

		if ($sendheader)
		{
			@header('Content-Length: ' . strlen($output));
		}
	}

	// Trigger shutdown event
	$vbulletin->shutdown->shutdown();

	if (defined('NOSHUTDOWNFUNC'))
	{
		exec_shut_down();
	}

	// show regular page
	if ($vbulletin->db->isExplainEmpty() OR (defined('VB_API') AND VB_API === true))
	{
		echo $output;
	}
	// show explain
	elseif (!VB_API)
	{
		$querytime = $vbulletin->db->time_total;
		echo "\n<b>Page generated in $totaltime seconds with " . $vbulletin->db->querycount . " queries,\nspending $querytime doing MySQL queries and " . ($totaltime - $querytime) . " doing PHP things.\n\n<hr />Shutdown Queries:</b>" . (defined('NOSHUTDOWNFUNC') ? " <b>DISABLED</b>" : '') . "<hr />\n\n";
	}

	// broken if zlib.output_compression is on with Apache 2
	if (SAPI_NAME != 'apache2handler' AND SAPI_NAME != 'apache2filter')
	{
		flush();
	}

	exit;
}

// #############################################################################
/**
* Converts raw link information into an appropriate URL
* Verifies that the requested linktype can be handled
*
* @param	string	Type of link, 'thread', etc
* @param	array		Specific information relevant to the page being linked to, $threadinfo, etc
* @param	array		Other information relevant to the page being linked to
* @param	string	Override the default $linkinfo[userid] with $linkinfo[$primaryid]
* @param	string	Override the default $linkinfo[title] with $linkinfo[$primarytitle]
* @param	bool	Get the raw, canonical url.  Set to true if the url is for a redirect.
*/
function fetch_seo_url($link, $linkinfo, $pageinfo = null, $primaryid = null, $primarytitle = null, $canonical = false)
{
	global $vbulletin;
	require_once(DIR . '/includes/class_friendly_url.php');
	$friendlyurl = vB_Friendly_Url::fetchLibrary($vbulletin, $link, $linkinfo, $pageinfo, $primaryid, $primarytitle);

	return $friendlyurl->get_url(false, $canonical);
}

/**
* Verifies that we are at the proper canonical seo url based on admin settings
*
* @param	string	Type of link, 'thread', etc
* @param	array		Specific information relevant to the page being linked to, $threadinfo, etc
* @param	array		Other information relevant to the page being linked to
* @param	string	Override the default $linkinfo[userid] with $linkinfo[$primaryid]
* @param	string	Override the default $linkinfo[title] with $linkinfo[$primarytitle]
*/
function verify_seo_url($link, $linkinfo, $pageinfo = null, $primaryid = null, $primarytitle = null)
{
	global $vbulletin;

	require_once(DIR . '/includes/class_friendly_url.php');

	// Check we have something to compare
	if (empty($linkinfo) OR !isset($vbulletin->input->friendly_uri))
	{
		return;
	}

	// Redirect if the current request is not canonical
	$canonical = vB_Friendly_Url::fetchLibrary($vbulletin, $link . '|nosession', $linkinfo, $pageinfo, $primaryid, $primarytitle);
	$canonical->redirect_canonical_url($vbulletin->input->friendly_uri);


	// Set the WOLPATH
	$url = $canonical->get_url(FRIENDLY_URL_OFF);
	$vbulletin->session->set('location', $url);
	define('WOLPATH', $vbulletin->input->strip_sessionhash($url));
}


function verify_subdirectory_url($prefix, $scriptid = null)
{
	global $vbulletin;
	if (!$prefix)
	{
		return;
	}

	// Never redirect a post
	// unless we're in debug mode and we wan't to error out on the bad urls
	// unless again its ajax... we're relying on the old urls for ajax because
	// a) it doesn't matter, users and search engines one see them and b) getting the
	// url logic into javascript is difficult.
	if (('GET' != $_SERVER['REQUEST_METHOD']) AND
		(!(defined('DEV_REDIRECT_404') AND DEV_REDIRECT_404) OR (isset($vbulletin->GPC) AND $vbulletin->GPC['ajax'])) )
	{
		return;
	}

	if (defined('FRIENDLY_URL_LINK'))
	{
		$friendly = vB_Friendly_Url::fetchLibrary($vbulletin, FRIENDLY_URL_LINK . '|nosession');
		if (vB_Friendly_Url::getMethodUsed() == FRIENDLY_URL_REWRITE)
		{
			$scriptid = $friendly->getRewriteSegment();
		}
		else
		{
			$scriptid = $friendly->getScript();
		}
	}
	else
	{
		if (is_null($scriptid))
		{
			$scriptid = THIS_SCRIPT;
		}
	}

	$pos = strrpos(VB_URL_CLEAN, '/' . $scriptid);
	if ($pos === false)
	{
		//if we don't know what this url is, then lets not try to mess with it.
		//this came up with the situation where the main index.php is set to forum
		//but the forums have been moved to a subdirectory.  Doesn't make much sense
		//but we should handle it gracefully.
		return;
	}

	$base_part = substr(VB_URL_CLEAN, 0, $pos);
	$script_part = substr(VB_URL_CLEAN, $pos+1);

	if (!preg_match('#' . preg_quote($prefix) . '$#', $base_part))
	{
		//force the old urls to 404 for testing purposes
		if (defined('DEV_REDIRECT_404') AND DEV_REDIRECT_404)
		{
			//dev only, no need to phrase the error.
			header("HTTP/1.0 404 Not Found");
			standard_error("old style url found, shouldn't get here");
		}
		else
		{
			$url = $prefix . '/' . $script_part;
			// redirect to the correct url
			exec_header_redirect($url, 301);
		}
	}
}


/**
 * Implodes an array using both values and keys
 *
 * @param string $glue1							- Glue between key and value
 * @param string $glue2							- Glue between value and key
 * @param mixed $array							- Arr to implode
 * @param boolean $skip_empty					- Whether to skip empty elements
 * @return string								- The imploded result
 */
function implode_both($glue1 = '', $glue2 = '', $array, $skip_empty = false)
{
	if (!is_array($array))
	{
		return '';
	}

	$newarray = array();
	foreach ($array as $key => $val)
	{
		if (!$skip_empty OR !empty($val))
		{
			$newarray[$key] = $key . $glue1 . $val;
		}
	}

	return implode($glue2, $newarray);
}

/**
 * Implodes an assoc array into a partial url query string
 *
 * @param mixed $array							- Array to parse
 * @param boolean $skip_empty					- Whether to skip empty elements
 * @return string								- The parsed result
 */
function urlimplode($array, $skip_empty = true, $skip_urlencode = false, $preserve_uni = false)
{
	if (!$skip_urlencode)
	{
		foreach ($array AS $key => $value)
		{
			$array[$key] = ($preserve_uni ? urlencode_uni($value) : urlencode($value));
		}
	}

	return implode_both('=', ($skip_urlencode ? '&' : '&amp;'), $array, $skip_empty);
}

// #############################################################################
/**
* Performs general clean-up after the system exits, such as running shutdown queries
*/
function exec_shut_down()
{
	global $vbulletin;
	global $foruminfo, $threadinfo, $calendarinfo;
	$options = vB::getDatastore()->getValue('options');
	if (defined('VB_AREA') AND (VB_AREA == 'Install' OR VB_AREA == 'Upgrade'))
	{
		return;
	}
	if ($vbulletin->db)
	{
		$vbulletin->db->unlock_tables();
	}
	if (!empty($vbulletin->userinfo['badlocation']))
	{
		$threadinfo = array('threadid' => 0);
		$foruminfo = array('forumid' => 0);
		$calendarinfo = array('calendarid' => 0);
	}

	if (!$options['bbactive'] AND !($vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']))
	{ // Forum is disabled and this is not someone with admin access
		$vbulletin->userinfo['badlocation'] = 2;
	}

	if (is_object($vbulletin->session))
	{
		if (!defined('LOCATION_BYPASS'))
		{
			$vbulletin->session->set('inforum', (!empty($foruminfo['forumid']) ? $foruminfo['forumid'] : 0));
			$vbulletin->session->set('inthread', (!empty($threadinfo['threadid']) ? $threadinfo['threadid'] : 0));
			$vbulletin->session->set('incalendar', (!empty($calendarinfo['calendarid']) ? $calendarinfo['calendarid'] : 0));
		}
		$vbulletin->session->set('badlocation', (!empty($vbulletin->userinfo['badlocation']) ? $vbulletin->userinfo['badlocation'] : ''));
		if (vB::getCurrentSession()->get('loggedin') == 1 AND !$vbulletin->session->created)
		{
			// If loggedin = 1, this is out first page view after a login so change value to 2 to signify we are past the first page view
			// We do a DST update check if loggedin = 1
			$vbulletin->session->set('loggedin', 2);
			if (!empty($vbulletin->profilefield['required']))
			{
				foreach ($vbulletin->profilefield['required'] AS $fieldname => $value)
				{
					if (!isset($vbulletin->userinfo["$fieldname"]) OR $vbulletin->userinfo["$fieldname"] === '')
					{
						$vbulletin->session->set('profileupdate', 1);
						break;
					}
				}
			}
		}
		$vbulletin->session->save();
	}

	if (is_array($vbulletin->db->shutdownqueries))
	{
		$vbulletin->db->hide_errors();
		foreach($vbulletin->db->shutdownqueries AS $name => $query)
		{
			if (!empty($query) AND ($name !== 'pmpopup' OR !defined('NOPMPOPUP')))
			{
				$vbulletin->db->query_write($query);
			}
		}
		$vbulletin->db->show_errors();
	}

	// execute the queries that have been registered in the assertor
	vB::getDbAssertor()->executeShutdownQueries();

	// Make sure the database connection is closed since it can get hung up for a long time on php4 do to the mysterious echo() lagging issue
	// If NOSHUTDOWNFUNC is defined then this function should always be the last one called, before echoing of data
	if (defined('NOSHUTDOWNFUNC') AND !empty($vbulletin->db))
	{
		$vbulletin->db->close();
		vB_Shutdown::instance()->setCalled(); // Stop this running as DB connection is closed.
	}

	$vbulletin->db->shutdownqueries = array();
	// bye bye!
}

/**
 * Spreads an array of values across the given number of stepped levels based on
 * their standard deviation from the mean value.
 *
 * The function accepts an array of $id => $value and returns $id => $level.
 *
 * @param array $values							- Array of id => values
 * @param integer $levels						- Number of levels to assign
 */
function fetch_standard_deviated_levels($values, $levels=5)
{
	if (!$count = sizeof($values))
	{
		return array();
	}

	$total = $summation = 0;
	$results = array();

	// calculate the total
	foreach ($values AS $value)
	{
		$total += $value;
	}

	// calculate the mean
	$mean = $total / $count;

	// calculate the summation
	foreach ($values AS $id => $value)
	{
		$summation += pow(($value - $mean), 2);
	}

	$sd = sqrt($summation / $count);

	if ($sd)
	{
		$sdvalues = array();
		$lowestsds = 0;
		$highestsds = 0;

		// find the max and min standard deviations
		foreach ($values AS $id => $value)
		{
			$value = (($value - $mean) / $sd);
			$values[$id] = $value;

			$lowestsds = min($value, $lowestsds);
			$highestsds = max($value, $highestsds);
		}

		foreach ($values AS $id => $value)
		{
			// normalize the std devs to 0 - 1, then map back to 1 - #levls
			$values[$id] = round((($value - $lowestsds) / ($highestsds - $lowestsds)) * ($levels - 1)) + 1;
		}
	}
	else
	{
		foreach ($values AS $id => $value)
		{
			$values[$id] = round($levels / 2);
		}
	}

	return $values;
}

/**
 * Checks if Facebook is enabled, and applicable to the current request
 *
 * @return bool, true if Facebook code should be run for the current request
 */
function is_facebookenabled()
{
	$options = vB::getDatastore()->getValue('options');
	// on top of facebook being enabled, make sure we are not skipping session for this request
	if ($options['facebookactive'] AND !defined('SKIP_SESSIONCREATE'))
	{
		return true;
	}
	else
	{
		return false;
	}
}

/** Checks to see if the current user has at least read access to the CMS root node.
*
* @return	boolean
**/

function can_see_cms()
{
	global $vbulletin;

	if (!$vbulletin->products['vbcms'])
	{
		return false;
	}

	if (class_exists('vBCMS_Permissions', false))
	{
		return vBCMS_Permissions::canView(1);
	}

	$ids = array();
	$rawids = explode(',', $vbulletin->userinfo['usergroupid'] . ',' . $vbulletin->userinfo['membergroupids']);
	foreach ($rawids AS $id)
	{
		if (($id = intval($id)) > 0)
		{
			$ids[] = $id;
		}
	}

	if (!empty($ids))
	{
		$perms = $vbulletin->db->query_first("
			SELECT MAX(permissions & 1) AS perm
			FROM " . TABLE_PREFIX . "cms_permissions
			WHERE nodeid = 1 AND usergroupid IN (" . implode(',', $ids) . ")
		");

		return (intval($perms['perm']) > 0);
	}

	return false;
}

/**
 * This is a temporary function used to get the stylevar 'charset' (added for presentation).
 *
 * @deprecated
 * @see vB_String::getTempCharset()
 * @return string, stylevar charset value
 */
function getTempCharset()
{
	return vB_String::getTempCharset();
}

/*
Simple error handler for the template eval's.
Add $config['render_debug'] = true; to your vB5 frontend config.php to use this.
*/
function vberror($errno = -1, $errmsg = 'Unknown vB Error', $errfile = 'Unknown File', $errline = 'Unknown', $errcontext = array())
{
	echo("Error ($errno): $errmsg in $errfile line $errline. <br />");
}

/*
From vB4 : Stop execution at any point and print a trace & variable.
*/
function vbstop($variable = null, $exit = 1, $showtrace = -1, $showvar = 1)
{
	$count = 0;
	if ($showtrace)
	{
		echo'<pre><br />';
		$trace = debug_backtrace();
		$trace_item_blank = array(
			'type' => '',
			'file' => '',
			'line' => '',
			'class' => '',
		);

		foreach ($trace AS $index => $trace_item)
		{
			$index++;
			$count++;
			if ($showtrace > 0 AND $count > $showtrace)
			{
				break;
			}
			$trace_item += $trace_item_blank;
			$param = (in_array($trace_item['function'], array('require', 'require_once', 'include', 'include_once')) ? $trace_item['args'][0] : '');
			$param = str_replace(DIR, '[path]', $param);
			$trace_item['file'] = str_replace(DIR, '[path]', $trace_item['file']);
			echo "#$index : ".$trace_item['class'].$trace_item['type'].$trace_item['function']."($param) called in ".$trace_item['file']." on line ".$trace_item['line']."<br />";
		}
		echo'<br /></pre>';
	}

	if ($showvar AND $variable !== null)
	{
		echo'<pre>';
		if ($variable === false)
		{
			echo'false';
		}
		else
		{
			print_r($variable);
		}
		echo'</pre>';
	}

	if ($exit)
	{
		exit;
	}

	return;
}

/**
 *	Resolve paths that appear in the admincp as "server" paths.
 *
 *	Rather than copy various logic all around, we want to centralize it
 *	in case it changes.  This permits us to change things all at once.
 *	The current logic is that absolute paths are left alone and relative
 *	paths are resolved assuming they are in the core directory.
 *
 *	@param string $path -- the path to resolve
 *	@return the fully qualified path if it exists, false otherwise.
 */
function resolve_server_path($path)
{
	//this is funky, but it turns out there isn't a standard way of
	//detecting a fully qualified path (and it gets *complicated* on
	//windows machines).  However realpath will handle relative paths
	//according to the cwd.
	$currentDir = getcwd();
	chdir(DIR);
	$path = realpath($path);
	chdir($currentDir);
	return $path;
}


/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88679 $
|| #######################################################################
\*=========================================================================*/
