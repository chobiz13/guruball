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

// TODO: this class needs to be refactored to use the db assertor. We are creating it now as a way to move some
// functions required by the rest of the code without doing the required refactory here
class vB_Language
{
	protected static $phraseGroups = array();
	protected static $languageCache = array();

	/** Stores phrasegroups for later loading
	 *
	 * @param	mixes	string or array of string;
	 *
	 */
	public static function preloadPhraseGroups($phraseGroups)
	{
		if (!is_array($phraseGroups))
		{
			self::$phraseGroups[] = $phraseGroups;
		}
		else
		{
			self::$phraseGroups = array_merge(self::$phraseGroups, $phraseGroups);
		}
	}

	public static function getPhraseInfo($languageId, $phraseGroups = array())
	{
		self::$phraseGroups = array_merge(self::$phraseGroups, $phraseGroups);

		$params[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_METHOD;
		$params['languageid'] = $languageId;
		$params['phrasegroups'] = self::$phraseGroups;
		self::$phraseGroups = array();

		ksort($params);
		$cacheKey = md5(json_encode($params));

		$ret = self::getPhraseCache($cacheKey);
		if (!$ret)
		{
			$result = vB::getDbAssertor()->assertQuery('fetchLanguage', $params);

			if ($result AND $result->valid())
			{
				$current = $result->current();
				if (isset($current['phrasegroup_global']))
				{
					vB_Phrase::addPhrases(array('global' => unserialize($current['phrasegroup_global'])));
				}

				self::setPhraseCache($cacheKey, $current);
				$ret = $current;
			}
		}
		return $ret;
	}

	private static function getPhraseCache($cacheKey)
	{
		if (empty(self::$languageCache[$cacheKey]))
		{
			$result = vB_Cache::instance()->read($cacheKey);
			if (!empty($result))
			{
				self::$languageCache[$cacheKey] = $result;
				return $result;
			}
			else
			{
				return null;
			}
		}
		else
		{
			return self::$languageCache[$cacheKey];
		}
	}

	private static function setPhraseCache($cacheKey, $data)
	{
		self::$languageCache[$cacheKey] = $data;
		vB_Cache::instance()->write($cacheKey, $data, false, "vB_Language_languageCache");
	}

	/**
	 * Returns a portion of an SQL query to select language fields from the database
	 *
	 * @param	boolean	If true, select 'language.fieldname' otherwise 'fieldname'
	 *
	 * @return	string
	 */
	protected static function fetchLanguageFieldsSql($addtable = true)
	{
		global $phrasegroups;

		$options = vB::getDatastore()->get_value('options');

		if (!is_array($phrasegroups))
		{
			$phrasegroups = array();
		}
		array_unshift($phrasegroups, 'global');

		if ($addtable)
		{
			$prefix = 'language.';
		}
		else
		{
			$prefix = '';
		}

		$sql = '';

		foreach ($phrasegroups AS $group)
		{
			$group = preg_replace('#[^a-z0-9_]#i', '', $group); // just to be safe...
			if ($group == 'reputationlevel' AND VB_AREA == 'Forum')
			{ // Don't load reputation phrases if reputation is disabled
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

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
