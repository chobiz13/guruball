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
 * vB_Library_Site
 *
 * @package vBLibrary
 */

class vB_Library_Site extends vB_Library
{
	// Assertor object
	protected $assertor;

	// required fields for site
	protected $fields = array(
		'title' => vB_Cleaner::TYPE_STR,
		'url' => vB_Cleaner::TYPE_STR,
		'usergroups' => vB_Cleaner::TYPE_ARRAY_UINT,
		'newWindow' => vB_Cleaner::TYPE_BOOL,
		'subnav' => vB_Cleaner::TYPE_ARRAY,
	);

	// cleaner instance
	protected $cleanerObj;

	protected $sitescache = array();

	/**
	 * Array of cached channelInfo, used by getChannelType
	 * @var	array
	 */
	protected $channelInfo = array();

	/**
	 * Phrases that need to be cached for the navbar/footer items
	 *
	 * @var array
	 */
	protected $requiredPhrases = array();

	/**
	 * Cached phrases used for navbar/footer items
	 *
	 * @var array
	 */
	protected $phraseCache = array();

	/**
	 * Initializes an Api Site object
	 */
	public function __construct()
	{
		parent::__construct();

		$this->assertor = vB::getDbAssertor();
		$this->cleanerObj = new vB_Cleaner();
	}

	/**
	 * Stores the header navbar data.
	 *
	 * @param	int			The storing data siteid (currently ignored).
	 * @param	mixed		Array of elements containing data to be stored for header navbar. Elements might contain:
	 * 			title		--	string		Site title. *required
	 * 			url			--	string		Site url. *required
	 * 			usergroups	--	array		Array of ints.
	 * 			newWindow	--	boolean		Flag used to display site in new window. *required
	 * 			subnav		--	mixed		Array of subnav sites (containing same site data structure).
	 * 				id			--	int		Id of subnav site.
	 * 				title		--	string	Title of subnav site.
	 * 				url			--	string	Url of subnav site.
	 * 				usergroups	--	array	Array of ints.
	 * 				newWindow	--	boolean	Flag used to display subnav site in new window.
	 * 				subnav		--	mixed	Array of subnav sites (containing same site data structure).
	 * @return	boolean		To indicate if save was succesfully done.
	 */
	public function saveHeaderNavbar($siteId, $data)
	{
		/** We expect an array of elements for cleaning */
		$cleanedData = array();
		foreach ($data AS $key => $element)
		{
			$cleanedData[$key] = $this->cleanData($element);
		}

		/** Required fields check */
		$this->hasEmptyData($cleanedData);
		$phrases = array();
		foreach ($cleanedData AS &$element)
		{
			$this->hasRequiredData($element);
			$this->saveNavbarPhrase($element, $phrases);
		}

		/** At this point we can store the data */
		$cleanedData = serialize($cleanedData);
		$response = $this->assertor->update('vBForum:site', array('headernavbar' => $cleanedData), vB_dB_Query::CONDITION_ALL);

		// reset cache
		unset($this->sitescache);

		return true;
	}

	/**
	 * Stores the footer navbar data.
	 *
	 * @param	int			The storing data siteid (currently ignored).
	 * @param	mixed		Array of data to be stored for footer navbar.
	 * 			title		--	string		Site title.
	 * 			url			--	string		Site url.
	 * 			usergroups	--	array		Array of ints.
	 * 			newWindow	--	boolean		Flag used to display site in new window.
	 * 			subnav		--	mixed		Array of subnav sites (containing same site data structure).
	 * 				id			--	int		Id of subnav site.
	 * 				title		--	string	Title of subnav site.
	 * 				url			--	string	Url of subnav site.
	 * 				usergroups	--	array	Array of ints.
	 * 				newWindow	--	boolean	Flag used to display subnav site in new window.
	 * 				subnav		--	mixed	Array of subnav sites (containing same site data structure).
	 * @return	boolean		To indicate if save was succesfully done.
	 */
	public function saveFooterNavbar($siteId, $data)
	{
		/** We expect an array of elements for cleaning */
		$cleanedData = array();
		foreach ($data AS $key => $element)
		{
			$cleanedData[$key] = $this->cleanData($element);
		}

		/** Required fields check */
		$this->hasEmptyData($cleanedData);
		$phrases = array();
		foreach ($cleanedData AS &$element)
		{
			$this->hasRequiredData($element);
			$this->saveNavbarPhrase($element, $phrases);
		}

		/** At this point we can store the data */
		$cleanedData = serialize($cleanedData);
		$response = $this->assertor->update('vBForum:site', array('footernavbar' => $cleanedData), vB_dB_Query::CONDITION_ALL);

		// reset cache
		unset($this->sitescache);

		return true;
	}

	/**
	 * Gets the header navbar data
	 *
	 * @param	int		Site id requesting header data.
	 * @param	string		URL
	 * @param	int		Edit mode so allow all links if user can admin sitebuilder
	 * @param	int		Channel ID (optional, used to determine current header navbar tab)
	 *
	 * @return	mixed	Array of header navbar data (Described in save method).
	 */
	public function loadHeaderNavbar($siteId, $url = false, $edit = false, $channelId = 0)
	{
		return $this->getNavbar('header', $siteId, $url, $edit, $channelId);
	}

	/**
	 * Gets the footer navbar data
	 *
	 * @param	int		Site id requesting footer data.
	 * @param	string		URL
	 * @param	int		Edit mode so allow all links if user can admin sitebuilder
	 *
	 * @return	mixed	Array of footer navbar data (Described in save method).
	 */
	public function loadFooterNavbar($siteId, $url = false, $edit = false)
	{
		return $this->getNavbar('footer', $siteId, $url, $edit);
	}

	/**
	 * Gets the navbar data for the header or the footer
	 *
	 * @param	int		Site id requesting header/footer data. (currently ignored).
	 * @parma	string		URL
	 * @param	int		Edit mode so allow all links if user can admin sitebuilder
	 * @param	int		Channel ID (optional, used to determine current header navbar tab)
	 *
	 * @return	mixed	Array of header/footer navbar data (Described in save method).
	 */
	private function getNavbar($type, $siteId, $url = false, $edit = false, $channelId = 0)
	{
		if (empty($this->sitescache))
		{
			$queryParams = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT);
			$this->sitescache = $this->assertor->getRow('vBForum:site', $queryParams);

			if (!empty($url))
			{
				$array = explode('?', $url, 2);
				$url = array_shift($array);
			}

			$header = unserialize($this->sitescache['headernavbar']);
			$footer = unserialize($this->sitescache['footernavbar']);

			try
			{
				$this->prepareNavbarData($header, $url, $edit, $channelId);
				$this->prepareNavbarData($footer, $url, $edit);
			}
			catch (Exception $e)
			{
				// This only really happens in unit tests, but if we hit an exception during the preparation above,
				// it means never finished saving the _prepared data to memory. If multiple calls to loadHeaderNavbar()
				// are made, it can cause weird behavior.
				// On a related note, the fact that we don't cache by $channelId means that only the *first valid*
				// (no exception) loadHeaderNavbar() call will be guaranteed to be correct. . .
				unset($this->sitescache);
				throw $e;
			}

			// when editing, phrases need to be loaded from language 0 specifically
			// other language translations can be edited in the Admin CP
			// when not editing, phrases are pulled via the template tag vb:phrase
			if ($edit)
			{
				$this->cachePhrases($edit);
				$this->addPhrasesToData($header);
				$this->addPhrasesToData($footer);
			}

			$this->sitescache['headernavbar_prepared'] = $header;
			$this->sitescache['footernavbar_prepared'] = $footer;
		}

		return $this->sitescache[$type . 'navbar_prepared'];
	}

	/**
	 * Prepares data for generating the navbar display, decides which navbar tab to
	 * highlight. The passed $data array is modified.
	 *
	 * @param	array	Array of navigation items, for the header or the footer
	 * @param	string	The current URL
	 * @param	bool	True if editing the page, false if not
	 * @param	int	Channel Node ID
	 *
	 * @return	bool	Whether the current navbar item was found or not
	 */
	protected function prepareNavbarData(array &$data, $url = false, $edit = false, $channelId = 0)
	{
		$baseurl_short = vB_String::parseUrl(vB::getDatastore()->getOption('frontendurl'), PHP_URL_PATH);
		$found_current = false;
		$found_sub_parent = false;
		$possibleCurrentItems = array();
		$removed_element = false;
		$userinfo = vB_Api::instanceInternal('user')->fetchCurrentUserInfo();
		$phraseApi = vB_Api::instance('phrase');
		foreach ($data AS $k => &$item)
		{
			if (is_array($item) AND isset($item['url']))
			{
				$item['phrase'] = $item['title'];
				$this->requiredPhrases[] = $item['title'];
				$additionalGrp = false;

				if ($userinfo['membergroupids'] AND !empty($item['usergroups']))
				{
					$memberGroups = explode(',', $userinfo['membergroupids']);
					foreach ($memberGroups as $memberGroup)
					{
						if (in_array($memberGroup, $item['usergroups']))
						{
							$additionalGrp = true;
							break;
						}
					}
				}

				if (
					(!$edit OR !vB::getUserContext()->hasAdminPermission('canusesitebuilder'))
						AND
					(!empty($item['usergroups']) AND (!in_array($userinfo['usergroupid'], $item['usergroups']) AND !$additionalGrp))
				)
				{
					unset($data[$k]);
					$removed_element = true;
					continue;
				}
				$item['isAbsoluteUrl'] = (bool) preg_match('#^https?://#i', $item['url']);
				$item['normalizedUrl'] = ltrim($item['url'], '/');
				$item['newWindow'] = ($item['newWindow'] ? 1 : 0);
				if (!empty($item['subnav']) AND is_array($item['subnav']))
				{
					$found_sub = $this->prepareNavbarData($item['subnav'], $url, $edit, $channelId);
					if (!$found_current AND $found_sub)
					{
						$found_sub_parent = &$item;
						$item['current_sub'] = true;
					}
				}
				if (!$found_current AND !empty($url))
				{
					if ($item['isAbsoluteUrl'])
					{
						$itemUrl = vB_String::parseUrl($item['normalizedUrl'], PHP_URL_PATH);
					}
					else
					{
						$itemUrl = $baseurl_short . '/' . $item['normalizedUrl'];
					}

					if(strtolower($url) == strtolower($itemUrl) || (strlen($url) > strlen($itemUrl) && strtolower(substr($url, 0, -(strlen($url) - strlen($itemUrl)))) == strtolower($itemUrl)))
					{
						// found an item that might be the current item
						$possibleCurrentItems[] = array(
							'length' => strlen($itemUrl),
							'item' => &$item,
						);
					}
				}
			}
		}

		// Reset the keys of the array, because in js it will be considered as an object
		if ($removed_element)
		{
			$data = array_values($data);
		}

		// test some special cases where we have non-conforming routes (routes
		// which don't begin with the same text as the navbar tab they are
		// supposed to be in.
		// @TODO consider renaming the /blogadmin route to /blogs/admin
		// and the /sgadmin route to /social-groups/admin
		if (!$found_current)
		{
			$setCurrentTab = '';

			// special case: the create content pages
			$channelId = (int) $channelId;
			if (strpos($url, $baseurl_short . '/new-content') === 0 AND $channelId > 0)
			{
				switch($this->getChannelType($channelId))
				{
					case 'blog':
						$setCurrentTab = 'blogs';
						break;
					case 'group':
						$setCurrentTab = 'social-groups';
						break;
					case 'article':
						$setCurrentTab = 'articles';
						break;
					default:
						break;
				}
			}
			// special case: the blogadmin pages
			else if (strpos($url, $baseurl_short . '/blogadmin') === 0)
			{
				$setCurrentTab = 'blogs';
			}
			// special case: the sgadmin pages
			else if (strpos($url, $baseurl_short . '/sgadmin') === 0)
			{
				$setCurrentTab = 'social-groups';
			}
			else if ($channelId > 0)
			{
				// special case: social groups, categories & topics
				// social group routes do not maintain the 'social-groups' bit in the URL
				if ($this->getChannelType($channelId) == 'group')
				{
					$setCurrentTab = 'social-groups';
				}
			}

			// set the special-cased tab to current
			if ($setCurrentTab)
			{
				foreach ($data AS $k => $v)
				{
					if ($v['normalizedUrl'] == $setCurrentTab)
					{
						$data[$k]['current'] = true;
						$found_current = true;
						break;
					}
				}
			}
		}


		// test the possible current items-- the longest URL is the best match
		if (!$found_current AND !empty($possibleCurrentItems))
		{
			$longestKey = 0;
			foreach ($possibleCurrentItems AS $k => $possibleCurrentItem)
			{
				if ($possibleCurrentItem['length'] > $possibleCurrentItems[$longestKey]['length'])
				{
					$longestKey = $k;
				}
			}

			$possibleCurrentItems[$longestKey]['item']['current'] = true;
			$found_current = true;
		}
		unset($possibleCurrentItems);

		if (!$found_current AND !empty($found_sub_parent))
		{
			$found_sub_parent['current'] = true;
		}

		return $found_current;
	}

	/**
	 * Returns the channel type for the given channel ID
	 *
	 * @param  int          The channel associated with the page.  If a non channel node is provided
	 * 											we will use that nodes channel instead.
	 * @return string|false The channel type, or an false if there was a problem,
	 *                      for example the user doesn't have access to the channel.
	 */
	protected function getChannelType($channelId)
	{
		if (!isset($this->channelInfo[$channelId]))
		{
			try
			{
				//this is supposed to be a channel id but it isn't always.  However all we actually care about is the
				//channel type, which is set for any node based on its ancestor channel.  If we ever need more
				//information about the channel than that we can explicitly look up the node's channel if it isn't
				//one already.
				$info = vB_Library::instance('node')->getNodeFullContent($channelId);
				$this->channelInfo[$channelId]['channeltype'] = $info[$channelId]['channeltype'];
			}
			catch (vB_Exception_Api $e)
			{
				if ($e->has_error('no_permission'))
				{
					return false;
				}
				else
				{
					throw $e;
				}
			}
		}

		if (isset($this->channelInfo[$channelId]) AND isset($this->channelInfo[$channelId]['channeltype']))
		{
			return $this->channelInfo[$channelId]['channeltype'];
		}

		return false;
	}

	protected function cachePhrases($edit = false)
	{
		if (!empty($this->requiredPhrases))
		{
			// when editing, use the default language phrase
			// translations can be made in the Admin CP.
			// instanceinternal?
			$this->phraseCache = vB_Api::instance('phrase')->fetch($this->requiredPhrases, ($edit ? 0 : null));
			$this->requiredPhrases = array();
		}
	}

	protected function addPhrasesToData(&$data)
	{
		foreach ($data as $k => &$item)
		{
			$item['phrase'] = $item['title'];
			$item['title'] = (isset($this->phraseCache[$item['phrase']]) AND !empty($this->phraseCache[$item['phrase']]))
				? $this->phraseCache[$item['phrase']] : $item['phrase'];

			if (!empty($item['subnav']) AND is_array($item['subnav']))
			{
				$this->addPhrasesToData($item['subnav']);
			}
		}
	}

	/**
	 * Check if data array is empty
	 *
	 * @param	mixed		Array of site data (described in save methods) to check.
	 *
	 * @throws 	Exception	missing_required_field if there's an empty field in site data.
	 */
	protected function hasEmptyData($data)
	{
		if (empty($data) OR !is_array($data))
		{
			throw new vB_Exception_Api('missing_required_field');
		}

		foreach ($data AS $field => $value)
		{
			//it's O.K. to have empty subnav
			if ((($field === 'subnav') OR ($field === 'usergroups') OR ($field === 'phrase') OR ($field == 'isAbsoluteUrl')) OR ($field === 'attr') AND (empty($value)))
			{
				continue;
			}

			if (is_array($value))
			{
				$this->hasEmptyData($value);
			}
			else
			{
				//if it's a boolean then empty is O.K.
				if (array_key_exists($field, $this->fields) AND ($this->fields[$field] == vB_Cleaner::TYPE_BOOL))
				{
					continue;
				}

				if (empty($value))
				{
					throw new vB_Exception_Api('missing_required_field');
				}
			}
		}
	}

	/**
	 * Check if data array is empty
	 *
	 * @param	mixed		Array of site data (described in save methods) to check.
	 *
	 * @throws 	Exception	missing_required_field if there's an empty field in site data.
	 */
	protected function hasRequiredData($data)
	{
		foreach ($this->fields as $field => $cleaner)
		{
			//it's O.K. to have empty subnav, usergroups or newWindow
			if (($field != 'subnav') AND ($field != 'usergroups') AND ($field != 'newWindow') AND empty($data[$field]))
			{
				throw new vB_Exception_Api('missing_required_field' );
			}
		}
	}

	protected function cleanData($data)
	{
		/** should be an array data */
		if (!is_array($data))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		foreach ($this->fields as $fieldKey => $fieldVal)
		{
			if (isset($data[$fieldKey]))
			{
				// clean array of subnav items properly
				if ($fieldKey == 'subnav')
				{
					foreach ($data[$fieldKey] AS $idx => $val)
					{
						$data[$fieldKey][$idx] = $this->cleanData($data[$fieldKey][$idx]);
					}
				}
				else
				{
					$data[$fieldKey] = $this->cleanerObj->clean($data[$fieldKey], $fieldVal);
				}
			}
		}

		return $data;
	}

	protected function saveNavbarPhrase(&$element, &$phrases)
	{
		if (!isset($element['phrase']) OR empty($element['phrase'])
					OR strpos($element['phrase'], 'navbar_') !==0
					/* we cannot have two different values for the same phrase */
					OR (isset($phrases[$element['phrase']]) AND $phrases[$element['phrase']] != $element['title']))
		{
			$words = explode(' ', $element['title']);
			array_walk($words, 'trim');
			$phrase = strtolower(implode('_', $words));

			//translating some special characters to their latin form
			$phrase = vB_String::latinise($phrase);

			// remove any invalid chars
			$phrase = preg_replace('#[^' . vB_Library_Phrase::VALID_CLASS . ']+#', '', $phrase);

			$phrase = 'navbar_' . $phrase;

			$suffix = 0;
			$tmpPhrase = $phrase;
			while (isset($phrases[$tmpPhrase]) AND $phrases[$tmpPhrase] != $element['title'])
			{
				$tmpPhrase = $phrase . (++$suffix);
			}

			$element['phrase'] = $tmpPhrase;
		}

		// Store the phrase-value so that we can check
		$phrases[$element['phrase']] = $element['title'];

		$existingPhrases = vB::getDbAssertor()->getRows('phrase', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'varname' => $element['phrase'],
		));

		// don't destroy translations
		$text = array();
		foreach ($existingPhrases as $existingPhrase)
		{
			$text[$existingPhrase['languageid']] = $existingPhrase['text'];
		}
		// the edited phrase
		$text[0] = $element['title'];

		vB_Api::instance('phrase')->save('navbarlinks', $element['phrase'], array(
				'text' => $text,
				'oldvarname' => $element['phrase'],
				'oldfieldname' => 'navbarlinks',
				't' => 0,
				'ismaster' => 0,
				'product' => 'vbulletin'
		));

		// store phrase name instead of title
		$element['title'] = $element['phrase'];
		unset($element['phrase']);

		// do the same for subnavigation
		if (isset($element['subnav']) AND !empty($element['subnav']))
		{
			foreach($element['subnav'] AS &$subnav)
			{
				$this->saveNavbarPhrase($subnav, $phrases);
			}
		}
	}

	/**
	 * Returns an array of general statistics for the site
	 *
	 * @return	array	Statistics.
	 */
	public function getSiteStatistics()
	{
		$statistics = array();

		// topics & posts
		$topChannels = vB_Api::instanceInternal('Content_Channel')->fetchTopLevelChannelIds();
		$parentid = $topChannels['forum'];
		$forumStats = vB_Api::instanceInternal('Node')->getChannelStatistics($topChannels['forum']);
		$statistics['topics'] = $forumStats['topics'];
		$statistics['posts'] = $forumStats['posts'];

		// members
		$userstats = vB::getDatastore()->getValue('userstats');
		$statistics['members'] = $userstats['numbermembers'];
		$statistics['activeMembers'] = $userstats['activemembers'];

		// latest member
		$statistics['newuser'] = array(
			'username' => $userstats['newusername'],
			'userid' => $userstats['newuserid'],
		);

		// @TODO: blogs, groups, articles

		return array(
			'statistics' => $statistics,
		);
	}

	/**
	 * Clears the internal site cache.
	 *
	 * WARNING: Only intended for use by unit tests. Do not use in
	 * any other context
	 */
	public function clearSiteCache()
	{
		if (!defined('VB_UNITTEST'))
		{
			throw new Exception('This method should be called only from unit tests');
		}
		else
		{
			$this->sitescache = array();
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88190 $
|| #######################################################################
\*=========================================================================*/
