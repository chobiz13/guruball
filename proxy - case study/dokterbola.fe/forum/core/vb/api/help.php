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
 * vB_Api_FAQ
 *
 * @package vBApi
 * @access public
 */

class vB_Api_Help extends vB_Api
{
	// cleaner instance
	protected $cleanerObj;

	/**
	 * Initializes an Api Help object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->cleanerObj = new vB_Cleaner();
	}

	/**
	 * fetches the help hierarchy, title phrases are included but text phrases are not
	 * @return array
	 *  * titles contains the hierarchy which sub-help items under the 'children' key
	 *  * firstItem contains the first help item to display
	 */
	public function getTitles()
	{
		$cache = vB_Cache::instance(vB_Cache::CACHE_LARGE);
		$titles = $cache->read('vb_FAQ_Titles');
		if (empty($titles))
		{
			$assertor = vB::getDbAssertor();

			$phrases = $assertor->getColumn('vBForum:phrase','text',
					array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
							'fieldname' => array('faqtext', 'faqtitle'),
							'languageid' => array(-1, 0, vB::getCurrentSession()->get('languageid')),
					), false, 'varname'
			);
			$faqs = $assertor->getRows('vBForum:faq', array(), 'displayorder', 'faqname');
			foreach($faqs AS $faqname => &$faq)
			{
				if ($faqname == 'faqroot')
				{
					continue;
				}
				$faq['title_phrase'] = $faq['faqname']. '_gfaqtitle';
				$faq['text_phrase'] = $faq['faqname']. '_gfaqtext';
				$faq['title'] = $phrases[$faq['title_phrase']];
				$faq['text'] = $phrases[$faq['text_phrase']];
				$parentPath = '';
				$parent = $faq['faqparent'];
				while ($parent != 'faqroot' AND isset($faqs[$parent]))
				{
					$parentPath = $faqs[$parent]['faqname'] . '/' . $parentPath;
					$parent=$faqs[$parent]['faqparent'];
				}
				$faq['path'] = $parentPath . $faq['faqname'];
				$faqs[$faq['faqparent']]['children'][$faq['faqname']] = &$faq;
			}
			$titles = $faqs['faqroot']['children'];
			$cache->write('vb_FAQ_Titles', $titles, 300, 'vB_FAQ_chg');
		}

		return array('titles' => $titles, 'firstItem' => $this->findFirst($titles));
	}

	/**
	 * fetches the FAQs item belonging to the given group under the "answers" key
	 * title and test phrases are included
	 * @param string $group
	 * @throws vB_Exception_Api
	 * @return array
	 */
	public function getAnswers($group)
	{
		if (empty($group))
		{
			throw new vB_Exception_Api("invalid_help_group", array($group));
		}

		$group = $this->getAnswer($group);
		$group['answers'] = $group['answer'];
		if (!empty($group['answer']['children']))
		{
			$group['answers'] = $group['answer']['children'];
		}
		return $group;
	}
	/**
	 * fetches one FAQ with the title and text phrases filled in
	 * @param string $title
	 * @param string $group - optional. provide if available to take advantage of the cache
	 * @throws vB_Exception_Api
	 */
	public function getAnswer($title)
	{
		if (empty($title))
		{
			throw new vB_Exception_Api("invalid_help_title", array($title));
		}

		$item = $this->findItem($title);
		if (empty($item))
		{
			throw new vB_Exception_Api("invalid_help_title", array($title));
		}

		$firstItem = $item;
		if (!empty($firstItem['children']))
		{
			$firstItem = $this->findFirst($firstItem['children']);
		}
		return array('answer' => $item, 'firstItem' => $firstItem);
	}

	/**
	 * Searches for keywords in the FAQ title and/or text
	 * @param string $keywords
	 * @param bool $titleandtext
	 * @param string $match (can be any/all/phr)
	 * @return array
	 * 	*titles - contains the list of matching FAQ items - title phrases are included
	 * 	*keywords - contains the list of keywords used for the search
	 */
	public function searchTitles($keywords, $titleandtext = true, $match = 'any')
	{
		$assertor = vB::getDbAssertor();
		$languages = array(-1, 0, vB::getCurrentSession()->get('languageid'));
		$fields = $titleandtext ? array('faqtitle', 'faqtext') : 'faqtitle';
		if ($match == 'any' OR !in_array($match, array('all' , 'phr')))
		{
			$search = preg_split('#[ \r\n\t]+#', $keywords);
			$phraseListRes = $assertor->assertQuery('vBForum:searchHelp', array(
					'search' => $search,
					'fields' => $fields,
					'languages' => $languages
				));
		}
		else
		{
			$conditions = array(
					array('field' => 'languageid', 'value' => $languages, 'operator' => vB_dB_Query::OPERATOR_EQ)
			);

			$conditions[] = array('field' => 'fieldname', 'value' => $fields, 'operator' => vB_dB_Query::OPERATOR_EQ);

			switch($match)
			{
				case 'all':
					$search = preg_split('#[ \r\n\t]+#', $keywords);
					foreach ($search as $word)
					{
						if (strlen($word) == 1)
						{
						// searches happen anywhere within a word, so 1 letter searches are useless
							continue;
						}
						$conditions[] = array('field' => 'text', 'value' => $word, 'operator' => vB_dB_Query::OPERATOR_INCLUDES);
					}
				break;
				case 'phr':
					$search = array($keywords);
					$conditions[] = array('field' => 'text', 'value' => $keywords, 'operator' => vB_dB_Query::OPERATOR_INCLUDES);
				break;
			}

			$phraseListRes = $assertor->assertQuery('vBForum:phrase', array(vB_dB_Query::CONDITIONS_KEY => $conditions, vB_dB_Query::COLUMNS_KEY => array('fieldname', 'varname'),));
		}
		$titles = $title_phrases =array();
		foreach ($phraseListRes as $phrase)
		{
			//$phrases[$phrase['varname']] = $phrase;
			$phrasename = str_replace('_g' . $phrase['fieldname'], '', $phrase['varname']);
			$titles[] = $phrasename;
			$title_phrases[] = $phrasename . '_gfaqtitle';
		}

		$phrases = vB_Api::instanceInternal('phrase')->fetch($title_phrases);

		$faqcache = array();

		$faqs = $assertor->assertQuery('vBForum:faq', array('faqname' => $titles));

		foreach($faqs AS $faq)
		{
			$faq['title_phrase'] = $faq['faqname']. '_gfaqtitle';
			$faq['text_phrase'] = $faq['faqname']. '_gfaqtext';
			$faq['title'] = $phrases[$faq['title_phrase']];
			$faq['path'] = $faq['faqparent'] . '/' . $faq['faqname'];
			$faqcache["$faq[faqname]"] = $faq;
		}
		return array('titles' => $faqcache, 'keywords' => $search);
	}

	protected function findItem($title, $tree = false)
	{
		if ($tree === false)
		{
			$tree = $this->getTitles();
			$tree = $tree['titles'];
		}
		if ($title == 'faqroot')
		{
			return array('children' => $tree);
		}

		foreach ($tree as $name => $value)
		{
			if ($name == $title)
			{
				return $value;
			}
			if (!empty($value['children']))
			{
				$item = $this->findItem($title, $value['children']);
				if (!empty($item))
				{
					return $item;
				}
			}
		}
		return false;
	}
	protected function findFirst($tree)
	{
		$item = current($tree);
		while (!empty($item['children']))
		{
			$item = current($item['children']);
		}
		return $item;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
