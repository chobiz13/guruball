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
 * vB_Library_Language
 *
 * @package vBApi
 * @access public
 */

class vB_Library_Language extends vB_Library
{
	protected $languages = array();

	/**
	 * Clears language whole cache or cache for a specific languageid
	 * @param type $languageId
	 */
	public function clearLanguageCache($languageId = FALSE)
	{
		$languageId = intval($languageId);
		if ($languageId !== FALSE)
		{
			unset($this->languages[$languageId]);
		}
		else
		{
			$this->languages = array();
		}
	}
	
	// TODO: add required fields as key?
	/**
	 * 
	 * @param mixed $languageIds - Language id or array of language ids
	 * @return array - Array of languages including:
	 *					- languageid
	 *					- dateoverride
	 *					- timeoverride
	 *					- locale
	 *					- charset
	 */
	public function fetchLanguages($languageIds)
	{
		$result = array();
		
		if (empty($languageIds))
		{
			return $result;
		}
		else if (is_array($languageIds))
		{
			array_walk($languageIds, 'intval');
		}
		else
		{
			$languageIds = array(intval($languageIds));
		}
		
		$missing = array();
		foreach ($languageIds AS $languageId)
		{
			if (isset($this->languages[$languageId]))
			{
				$result[$languageId] = $this->languages[$languageId];
			}
			else
			{
				$missing[$languageId] = $languageId;
			}
		}
		
		if (!empty($missing))
		{
			$query = array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('languageid', 'dateoverride', 'timeoverride', 'locale', 'charset'),
				vB_dB_Query::CONDITIONS_KEY => array('languageid' => $missing),
			);
			$dbLanguages = vB::getDbAssertor()->assertQuery('language', $query);
			foreach ($dbLanguages AS $lang)
			{
				$this->languages[$lang['languageid']] = $lang;
				$result[$lang['languageid']] = $lang;
			}
		}
		
		return $result;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
