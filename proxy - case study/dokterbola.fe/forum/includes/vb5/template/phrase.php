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

class vB5_Template_Phrase
{
	const PLACEHOLDER_PREFIX = '<!-- ##phrase_';
	const PLACEHOLDER_SUFIX = '## -->';

	protected static $instance;
	protected $cache = array();
	protected $pending = array();
	protected $stack = array();

	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function register($args)
	{
		$phraseName = $args[0];
		$pos = isset($this->pending[$phraseName]) ? count($this->pending[$phraseName]) : 0;

		if (count($args) < 2)
		{
			// If it doesn't have other arguments, assume that this is a phrase with no variables.
			// There's no reason to re-construct this phrase, so let's not bother adding it to the stack.
			// TODO: Maybe we should deep-cache argument-less phrases for each language so we don't try
			// to construct it/call sprintf on it every time? We'll need to check how much more performant
			// it'd be, and how many unique phrase:arguments pairs there are in a typical page load.
			$pos = 0;
		}

		$placeHolder = $this->getPlaceholder($phraseName, $pos);

		$this->pending[$phraseName][$placeHolder] = $args;
		$this->stack[$placeHolder] = $phraseName;

		return $placeHolder;
	}

	/**
	 * The use of this function should be avoided when possible because it forces the controller to fetch all missing phrases immediately.
	 *
	 * @var string phraseName
	 * @var mixed parameter1
	 * @var mixed parameter2
	 * @return type
	 */
	public function getPhrase()
	{
		$args = func_get_args();
		$phraseName = $args[0];

		// first check if we already have the phrase, if not force fetching
		if (!isset($this->cache[$phraseName]))
		{
			// note: the placeholder won't be used in this case
			$this->pending[$phraseName][] = $args;
			$this->fetchPhrases();
		}

		$args[0] = isset($this->cache[$phraseName]) ? $this->cache[$phraseName] : $args[0];
		return $this->constructPhraseFromArray($args);
	}

	public function resetPending() {
		$this->pending = array();
		$this->stack = array();
	}

	public function replacePlaceholders(&$content)
	{
		$this->fetchPhrases();
		$placeholders = array();
		end($this->stack);
		while (!is_null($placeholder_id = key($this->stack)))
		{
			$phraseName = current($this->stack);
			$phraseInfo = $this->pending[$phraseName][$placeholder_id];
			$phraseInfo[0] = isset($this->cache[$phraseName]) ? $this->cache[$phraseName] : $phraseInfo[0];

			// do parameter replacements in phrases for notices, since we don't want
			// the extra overhead of pulling these phrases in the api method
			if (strpos($phraseName, 'notice_') === 0 AND preg_match('/^notice_[0-9]+_html$/', $phraseName))
			{
				$phraseInfo[0] = str_replace(
					array(
						'{musername}',
						'{username}',
						'{userid}',
						'{sessionurl}',
						'{sessionurl_q}',
						'{register_page}',
						'{help_page}'
					),
					array(
						vB5_User::get('musername'),
						vB5_User::get('username'),
						vB5_User::get('userid'), vB::getCurrentSession()->get('sessionurl'),
						vB::getCurrentSession()->get('sessionurl_q'),
						vB5_Template_Runtime::buildUrl('register'),
						vB5_Template_Runtime::buildUrl('help')
					),
					$phraseInfo[0]
				);
			}

			$replace = $this->constructPhraseFromArray($phraseInfo);
			$placeholders[$placeholder_id] = $replace;
			//$content = str_replace($placeholder_id, $replace, $content);

			prev($this->stack);
		}

		// If we passed any phrases as parameters to other phrases, we will
		// still have those placeholders in the "replace" content, for example:
		//   {vb:phrase have_x_posts_in_topic_last_y, {vb:var topic.dot_postcount}, {vb:date {vb:var topic.dot_lastpostdate}}}
		// since the date call can return phrases (today, yesterday, etc.).
		// This only goes one level deep (e.g., it's not recursive), since that's
		// all we need at this time.
		// This searches the replace text to see if there are any placeholders
		// left in them, and if so, replaces those placeholders with the phrase text.
		foreach ($placeholders AS $k => $replace)
		{
			if (strpos($replace, '<!-- ##phrase_') !== false OR strpos($replace, '&lt;!-- ##phrase_') !== false)
			{
				if (preg_match_all('/(?:<|&lt;)!-- ##phrase_([a-z0-9_]+)_[0-9]+## --(?:>|&gt;)/siU', $replace, $matches, PREG_SET_ORDER))
				{
					foreach ($matches AS $match)
					{
						$placeholder_id = $match[0];
						$phrase_varname = $match[1];

						$placeholder_id_lookup = str_replace(array('&lt;', '&gt;'), array('<', '>'), $placeholder_id);

						$phraseInfo = $this->pending[$phrase_varname][$placeholder_id_lookup];
						$phraseInfo[0] = isset($this->cache[$phrase_varname]) ? $this->cache[$phrase_varname] : $phraseInfo[0];

						$phraseText = $this->constructPhraseFromArray($phraseInfo);

						$placeholders[$k] = str_replace($placeholder_id, $phraseText, $placeholders[$k]);
					}
				}
			}
		}

		if (!empty($placeholders))
		{
			$content = str_replace(array_keys($placeholders), $placeholders, $content);
		}
	}

	protected function getPlaceholder($phraseName, $pos)
	{
		return self::PLACEHOLDER_PREFIX . $phraseName . '_' . $pos . self::PLACEHOLDER_SUFIX;
	}

	protected function fetchPhrases()
	{

		$missing = array_diff(array_keys($this->pending), array_keys($this->cache));

		if (!empty($missing))
		{
			$response = Api_InterfaceAbstract::instance()->callApi('phrase', 'fetch', array('phrases' => $missing));
			foreach ($response as $key => $value)
			{
				$this->cache[$key] = $value;
			}
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
	protected function constructPhraseFromArray($phrase_array)
	{
		$numargs = sizeof($phrase_array);

		// if we have only one argument then its a phrase
		// with no variables, so just return it
		if ($numargs < 2)
		{
			return $phrase_array[0];
		}

		// if the second argument is an array, use their values as variables
		if (is_array($phrase_array[1]))
		{
			array_unshift($phrase_array[1], $phrase_array[0]);
			$phrase_array = $phrase_array[1];
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

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89111 $
|| #######################################################################
\*=========================================================================*/
