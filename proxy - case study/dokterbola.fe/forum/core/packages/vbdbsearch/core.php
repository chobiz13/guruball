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

class vBDBSearch_Core extends vB_Search_Core
{
	const asymptote = 5;
	const slope = 20;
	/**************INDEXING**************************/
	/**
	 *
	 * Index a node
	 * @param int $node_id to index
	 * @param boolean $propagate flag to propagate the indexing to the nodes parents
	 */


	public function indexText($node, $title, $text, $skip_prev_index = false)
	{
		$title_words = $this->break_words($title);
		if (!empty($text))
		{
			$words = array_merge($title_words, $this->break_words($text));
		}
		else
		{
			$words = $title_words;
		}
		// no meaningful words, no index
		$assertor = vB::getDbAssertor();
		if (empty($words))
		{
			// updating the CRC32 value so it would not be picked up by the indexer again
			$assertor->update('vBForum:node', array('CRC32' => 1), array('nodeid' => $node['nodeid']));
			return false;
		}

		//count the number of occurance of each word
		$word_occurances = array_count_values($words);
		$words = array_keys($word_occurances);
		$total_words = count($words);
		$crc = sprintf('%u', crc32(implode(' ', $words)));
		// check if the same content has already been indexed (update)
		// We reduce the check to checking the current CRC with the old(stored) CRC of the content
		if ($node['CRC32'] == $crc)
		{
			// no need to index if the content hasn't changed
			return false;
		}
		// fetch previously indexed word ids

		$prev_index = array();
		if (!$skip_prev_index)
		{
			$prev_index = $assertor->getRows('vBDBSearch:fetch_indexed_words', array('nodeid' => $node['nodeid']), false,'wordid');
		}
		// update the existing node with the new CRC
		$assertor->update('vBForum:node', array('CRC32' => $crc), array('nodeid' => $node['nodeid']));
		//fetch the ids of the words
		$existing_words = array();
		$remaining_words = array_flip($words);
		// see if all the words are in the words table
		$existing_words_res = $assertor->assertQuery('vBDBSearch:words', array('word' => $words));

		foreach($existing_words_res as $existing_word)
		{
			$existing_words[$existing_word['word']] = $existing_word['wordid'];
			unset($remaining_words[$existing_word['word']]);
		}
		//adding the remaining words
		if (!empty($remaining_words))
		{
			$existing_words += $this->addWords(array_keys($remaining_words));
		}

		$searchword_tables_unique = array();

		foreach ($words as $index => $word)
		{
			if (!isset($existing_words[$word]))
			{
				continue;
			}
			else
			{
				$wordid=$existing_words[$word];
			}
			// create a relation between the word and the searchword_table
			$suffix = $this->get_table_name($word);
			$score_params = array
			(
					'occurance_nr' => $word_occurances[$word],
					'total_words' => $total_words,
					'word' => $word,
					'position' => $index + 1, //index is 0 based
					'is_title' => 0
			);

			if (!empty($title_words) AND in_array($word, $title_words))
			{
				$score_params['is_title'] = 1;
			}
			$score = $this->get_score($score_params);
			//let's check if this word has previously been indexed
			if (array_key_exists($wordid, $prev_index))
			{
				//do we need to update it?
				if ($prev_index[$wordid]['score'] != $score OR $prev_index[$wordid]['is_title'] != $score_params['is_title'] OR $prev_index[$wordid]['position'] != ($index + 1) )
				{

					$assertor->assertQuery(
							'vBDBSearch:updateSearchtowords',
							array
							(
									'wordid' => $wordid,
									'nodeid' => $node['nodeid'],
									'suffix' => $prev_index[$wordid]['suffix'],
									'score' => $score,
									'is_title' => ($score_params['is_title']?1:0),
									'position' => ($index + 1)
							)
					);

				}
				//now that we know the previous index is accurate, we can remove it from the list of obsolete index
				unset($prev_index[$wordid]);
			}
			else // it looks like a new word that needs to be indexed
			{
				// creating a list of indexes partitioned by the searchtowords tables
				$searchword_tables_unique[$suffix][$wordid] = array
				(
						'is_title' => $score_params['is_title']?1:0,
						'score' => $score,
						'position' => $index + 1 //index is 0 based
				);
			}
		}

		foreach ($searchword_tables_unique as $suffix => $wordids)
		{
			if (empty($wordids))
			{
				continue;
			}
			$values = array();
			foreach ($wordids as $wordid => $info)
			{
				$values[] = array($node['nodeid'], $wordid, empty($info['is_title']) ? 0 : 1, $info['score'], $info['position']);
			}
			try
			{
				$assertor->insertMultiple('vBDBSearch:searchtowords_' . $suffix, array('nodeid', 'wordid', 'is_title', 'score', 'position'), $values);
			}
			catch (Exception $e)
			{
				//just ignore the error, if this query fails it means the node is already indexed
			}

		}

		// let's clean up the entries from the previous indexing that are not used anymore
		foreach ($prev_index as $wordid => $details)
		{
			$assertor->delete('vBDBSearch:searchtowords_' . $details['suffix'], array('wordid' => $wordid, 'nodeid' => $node['nodeid']));
		}

		return true;
	}

	public function reIndexAll()
	{
		return false;
	}

	public function emptyIndex()
	{
		parent::emptyIndex();
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('truncateTable', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD, 'table' => 'words'));
		$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE);
		$names = $this->get_table_name_suffixes();
		foreach ($names as $name)
		{
			$assertor->assertQuery('truncateTable', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD, 'table' => 'searchtowords_' . $name));
		}
	}

	/**************DELETING**************************/

	public function delete($nodeid, $node = false)
	{
		$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'nodeid' => $nodeid);
		$assertor = vB::getDbAssertor();
		$names = $this->get_table_name_suffixes();
		foreach ($names as $name)
		{
			$assertor->assertQuery('vBDBSearch:searchtowords_' . $name, $data);
		}
	}

	public function deleteBulk($nodeids)
	{
		if (empty($nodeids))
		{
			return;
		}

		$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'nodeid' => $nodeids);
		$assertor = vB::getDbAssertor();
		$names = $this->get_table_name_suffixes();
		foreach ($names as $name)
		{
			$assertor->assertQuery('vBDBSearch:searchtowords_' . $name, $data);
		}
	}


	/**************SEARCHING**************************/

	public function getResults(vB_Search_Criteria $criteria)
	{
		$results = $this->getTwoPassResults($criteria);
		if (is_array($results))
		{
			$nodeids = array();
			foreach ($results AS $nodeid => $node)
			{
				$nodeids[$nodeid] = $nodeid;
			}

			return $nodeids;
		}

		return vB::getDbAssertor()->getColumn(
			'vBDBSearch:getSearchResults', 'nodeid', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
				'criteria' => $criteria,
				'cacheKey' => $results
			)
		);
	}

	/**
	 * breaks up the text into words
	 * @param string $text
	 * @return string
	 */
	protected static function break_words($text)
	{
		$text = strip_tags($text);
		$text = strip_bbcode($text,true, false, false, true);
		// multibyte
		$is_mb = preg_match('/[^\x00-\x7F]/', $text);
		if (!$is_mb)
		{
			$is_mb = (strlen($text) != vB_String::vbStrlen($text));
		}

		if ($is_mb)
		{
			$text = vB_String::toUtf8($text, vB_String::getCharSet());
			if (preg_match('/&#([0-9]+);|[^\x00-\x7F]/esiU', $text) AND function_exists('mb_decode_numericentity'))
			{
				$text = mb_decode_numericentity($text, array(0x0, 0x2FFFF, 0, 0xFFFF), 'UTF-8');
			}
			$pattern = '/[\s,.!?@#$%^&*\(\)\\/<>"\';:\[\]\{\}\+|-]/';
		}
		else
		{
			$pattern = '/[^a-z0-9_]+/i';
		}

		$words = preg_split($pattern, vB_String::vBStrToLower($text), -1,PREG_SPLIT_NO_EMPTY);

		foreach ($words as $index => $word) {
			if (!vB_Api_Search::is_index_word($word, true))
			{
				unset($words[$index]);
				continue;
			}
			//$words[$index] = $is_mb ? vB_String::toUtf8($word) : $word;
			if (empty($words[$index]))
			{
				unset($words[$index]);
				continue;
			}
		}
		return $words;
	}

	/**
	 * gets the suffixes that need to be appended to the searchtowords table names
	 * @return array
	 */
	public static function get_table_name_suffixes()
	{
		$suffixes = range('a', 'z');
		$suffixes[] = 'other';
		return $suffixes;
	}

	/**
	 * add one word into the words table
	 * @param string $word
	 */
	protected function addWord($word)
	{
		try
		{
			$wordid = vB::getDbAssertor()->assertQuery('vBDBSearch:words',
					array(vB_dB_Query::TYPE_KEY=>vB_dB_Query::QUERY_INSERTIGNORE, 'word' => $word));
		}
		catch (Exception $e)
		{
			//if we got here, the word might already exist so let's look it up
			$wordid =  vB::getDbAssertor()->getField('vBDBSearch:words', array('word' => $word));
			return $wordid;
		}

		if (is_array($wordid))
		{
			$wordid = array_pop($wordid);
		}

		if (empty($wordid))
		{
			// The INSERT IGNORE may fail to insert without throwing an exception if the row already
			// so we need to get the wordid to return.
			$wordid =  vB::getDbAssertor()->getField('vBDBSearch:words', array('word' => $word));
		}

		return $wordid;
	}

	/**
	 * add multiple words into the words table
	 * @param array $words
	 */
	protected function addWords($words)
	{
		// if it's only one word to be added, use the simple query instead of the multiple
		if (count($words) == 1)
		{
			$word = array_pop($words);
			return array($word => $this->addWord($word));
		}

		if (empty($words))
		{
			return array();
		}

		$wordIds = array();
		try
		{
			vB::getDbAssertor()->assertQuery('vBDBSearch:insertWords', array(
				'words' => $words,
			));
		}
		catch (Exception $e)
		{
			//if the multiple insert fails, let's try to add them one by one
			foreach ($words as $word)
			{
				$wordId = $this->addWord($word);
				if (!empty($wordId))
				{
					$wordIds[$word] = $wordId;
				}
			}
			return $wordIds;
		}

		//get the missing(just added) wordids
		return vB::getDbAssertor()->getColumn('vBDBSearch:words', 'wordid', array('word' => $words), false, 'word');
	}

	/**
	 * finds which searchtowords table a word belongs to
	 * @var string $word
	 * @return array
	 */
	public static function get_table_name($word)
	{
		if (empty($word))return false;
		$firstchar = $word[0];
		$suffixes = self::get_table_name_suffixes();
		// do we have a valid character?
		if (($index = array_search($firstchar, $suffixes)) !== false)
		{
			return $suffixes[$index];
		}
		// all numbers and non-valid characters are stored in the 'searchtowords_other' table
		return 'other';
	}

	/**
	 *
	 * The function for the word weight uses an asymptotic function
	 * @param array $word_info contains the information about the word
	 */
	protected function get_score($word_info)
	{
		$score = self::asymptote - ((self::asymptote-1) * exp(-1 * ($word_info['occurance_nr']-1)/self::slope));
		return round($score * 10000);

		//		$score = ceil($word_info['occurance_nr'] * 100 / $word_info['total_words']);
		//		//if the word is in the title, add more weight to it
		//		$score = min(100, $score + (empty($word_info['is_title'])?0:50));
		//		return $score;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85946 $
|| #######################################################################
\*=========================================================================*/
