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
* Converts URLs in text to bbcode links
*
* @param	string	message text
*
* @return	string
*/
function convert_url_to_bbcode($messagetext)
{
	$datastore = vB::getDatastore();
	$bbcodecache = $datastore->getValue('bbcodecache');
	$bbcodeoptions = $datastore->getValue('bf_misc_bbcodeoptions');

	// areas we should attempt to skip auto-parse in
	$skiptaglist = 'url|email|code|php|html|noparse';

	if (!isset($bbcodecache))
	{
		$bbcodecache = array();

		$bbcodes = vB_Library::instance('bbcode')->fetchBBCodes();
		foreach ($bbcodes as $customtag)
		{
			$bbcodecache["$customtag[bbcodeid]"] = $customtag;
		}
	}

	foreach ($bbcodecache AS $customtag)
	{
		if (intval($customtag['options']) & $bbcodeoptions['stop_parse'] OR intval($customtag['options']) & $bbcodeoptions['disable_urlconversion'])
		{
			$skiptaglist .= '|' . preg_quote($customtag['bbcodetag'], '#');
		}
	}

	// Legacy Hook 'url_to_bbcode' Removed //

	return preg_replace_callback('#(^|\[/(' . $skiptaglist . ')\])(.*(\[(' . $skiptaglist . ')\]|$))#siU',
		function($matches)
		{
			return convert_url_to_bbcode_callback($matches[3], $matches[1]);
		}, $messagetext
	);
}

/**
* Callback function for convert_url_to_bbcode
*
*	Should only be called from convert_url_to_bbcode
*
* @param	string	Message text
* @param	string	Text to prepend
*
* @return	string
*/
function convert_url_to_bbcode_callback($messagetext, $prepend)
{
	$datastore = vB::getDatastore();
	$bbcodecache = $datastore->getValue('bbcodecache');
	$bbcodeoptions = $datastore->getValue('bf_misc_bbcodeoptions');

	// the auto parser - adds [url] tags around neccessary things
	$messagetext = str_replace('\"', '"', $messagetext);
	$prepend = str_replace('\"', '"', $prepend);

	static $urlSearchArray, $urlReplaceArray, $emailSearchArray, $emailReplaceArray;
	if (empty($urlSearchArray))
	{
		$taglist = '\[b|\[i|\[u|\[left|\[center|\[right|\[indent|\[quote|\[highlight|\[\*' .
			'|\[/b|\[/i|\[/u|\[/left|\[/center|\[/right|\[/indent|\[/quote|\[/highlight';

		foreach ($bbcodecache AS $customtag)
		{
			if (!(intval($customtag['options']) & $bbcodeoptions['disable_urlconversion']))
			{
				$customtag_quoted = preg_quote($customtag['bbcodetag'], '#');
				$taglist .= '|\[' . $customtag_quoted . '|\[/' . $customtag_quoted;
			}
		}

		// Legacy Hook 'url_to_bbcode_callback' Removed //

		$urlSearchArray = array(
			'#(^|(?<=[^_a-z0-9-=\]"\'/@]|(?<=' . $taglist . ')\]))((https?|ftp|gopher|news|telnet)://|www\.)((\[(?!/)|[^\s[^$`"{}<>])+)(?!\[/url|\[/img)(?=[,.!\')]*(\)\s|\)$|[\s[]|$))#siU'
		);

		$urlReplaceArray = array(
			"[url]\\2\\4[/url]"
		);

		$emailSearchArray = array(
			'/([ \n\r\t])([_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[^\s]+(\.[a-z0-9-]+)*(\.[a-z]{2,6}))/si',
			'/^([_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[^\s]+(\.[a-z0-9-]+)*(\.[a-z]{2,6}))/si'
		);

		$emailReplaceArray = array(
			"\\1[email]\\2[/email]",
			"[email]\\0[/email]"
		);
	}

	$text = preg_replace($urlSearchArray, $urlReplaceArray, $messagetext);
	if (strpos($text, "@"))
	{
		$text = preg_replace($emailSearchArray, $emailReplaceArray, $text);
	}

	return $prepend . $text;
}

// ###################### Start stopshouting #######################

/**
 * Stops text being all UPPER CASE
 *
 * @param	string	The text to apply 'anti-shouting' to
 *
 * @return	string The text with 'anti-shouting' applied
 *
 */
function fetch_no_shouting_text($text)
{
	global $vbulletin;

	/*
		strip_bbcode param notes:
			$message 		: NA
			$stripquotes 	: true, legacy
			$fast_and_dirty : false, legacy
			$showlinks 		: false, was true/default previously, but I don't see why having links (which will usually
							have lowercase characters) should allow them to shout in the rest of their text
			$stripimg 		: true, [attach] bbcodes in vB5 have string prefices (ex 'temp1234_5678' or 'n90'), so we
							must strip them for the check to work when user added inline attachments
			$keepquotetags	: not provided, default(false), legacy
	 */
	$effective_string = preg_replace('#[^a-z0-9\s]#i', '\2', strip_bbcode($text, true, false, false, true));

	if ($vbulletin->options['stopshouting'] AND vbstrlen($effective_string) >= $vbulletin->options['stopshouting'] AND $effective_string == strtoupper($effective_string) AND !preg_match('#^[\x20-\x40\x5B-\x60\x7B-\x7E\s]+$#', $effective_string)/* string does not consist entirely of non-alpha ascii chars #32591 */)
	{
		return fetch_sentence_case($text);
	}
	else
	{
		return $text;
	}
}

/**
 * Capitalizes the first letter of each sentence, provided it is within a-z. Lower-cases the entire string first
 * Ignores locales
 *
 * @param	string	Text to capitalize
 *
 * @return	string
 */
function fetch_sentence_case($text)
{
	return preg_replace_callback(
		'#(^|\.\s+|\:\s+|\!\s+|\?\s+)[a-z]#',
		create_function('$matches', 'return strtoupper($matches[0]);'),
		vbstrtolower($text)
	);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85390 $
|| #######################################################################
\*=========================================================================*/
