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

class vB5_Frontend_Controller_Bbcode extends vB5_Frontend_Controller
{

	protected static $needDebug = false;

	function __construct()
	{
		parent::__construct();
	}

	public static function parse($text, $options = array(), $attachments = array(), $cacheInfo = array())
	{
		//if we have a nodeid, let's try to cache this.
		if (!empty($cacheInfo))
		{
			//TODO- Find a caching method that doesn't break collapsed mode.
			if (!empty($cacheInfo['nodeid']))
			{
				$cacheKey = 'vbNodeText' . $cacheInfo['nodeid'];
			}
			else if (!empty($cacheInfo['signatureid']))
			{
				$cacheKey = 'vbSig' . $cacheInfo['signatureid'];
			}
			if (!empty($cacheKey))
			{
				$cacheKey .= strval($options);
				$parsed = vB_Cache::instance()->read($cacheKey);

				if ($parsed)
				{
					return $parsed;
				}
			}
		}
		$result = self::parseInternal(new vB5_Template_BbCode(), $text, $options, $attachments);

		if (!empty($cacheKey))
		{
			if (!empty($cacheInfo['nodeid']))
			{
				$cacheEvent = 'nodeChg_' . $cacheInfo['nodeid'];
			}
			else if (!empty($cacheInfo['signatureid']))
			{
				$cacheEvent = 'userChg_' . $cacheInfo['signatureid'];
			}
			vB_Cache::instance()->write($cacheKey, $result, 86400, $cacheEvent);
		}
		return $result;
	}

	public static function parseWysiwyg($text, $options = array(), $attachments = array())
	{
		return self::parseInternal(new vB5_Template_BbCode_Wysiwyg(), $text, $options, $attachments);
	}

	public static function verifyImgCheck($text, $options = array())
	{
		$parsed = self::parseWysiwygForImages($text, $options);
		$vboptions = vB5_Template_Options::instance()->getOptions();
		if ($vboptions['options']['maximages'])
		{
			$imagecount = substr_count(strtolower($parsed), '<img');
			if ($imagecount > $vboptions['options']['maximages'])
			{
				return array('toomanyimages', $imagecount, $vboptions['options']['maximages']);
			}
		}
		return true;
	}

	public static function parseWysiwygForImages($text, $options = array())
	{
		$api = Api_InterfaceAbstract::instance();
		$text = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($text, array('autoparselinks' => false)));
		$parser = new vB5_Template_BbCode_Imgcheck();

		if (!isset($options['allowhtml']))
		{
			$options['allowhtml'] = false;
		}
		if (!isset($options['allowsmilies']))
		{
			$options['allowsmilies'] = true;
		}
		if (!isset($options['allowbbcode']))
		{
			$options['allowbbcode'] = true;
		}
		if (!isset($options['allowimagebbcode']))
		{
			$options['allowimagebbcode'] = true;
		}

		return $parser->doParse($text, $options['allowhtml'], $options['allowsmilies'], $options['allowbbcode'], $options['allowimagebbcode']);
	}

	public static function parseWysiwygForPreview($text, $options = array(), $attachments = array())
	{
		$api = Api_InterfaceAbstract::instance();
		$text = $api->callApi('bbcode', 'parseWysiwygHtmlToBbcode', array($text));
		$parser = new vB5_Template_BbCode();

		if (!isset($options['allowhtml']))
		{
			$options['allowhtml'] = false;
		}
		if (!isset($options['allowsmilies']))
		{
			$options['allowsmilies'] = true;
		}
		if (!isset($options['allowbbcode']))
		{
			$options['allowbbcode'] = true;
		}
		if (!isset($options['allowimagebbcode']))
		{
			$options['allowimagebbcode'] = true;
		}
		if (isset($options['userid']))
		{
			$parser->setParseUserinfo($options['userid']);
		}

		$parser->setAttachments($attachments);

		$templateCache = vB5_Template_Cache::instance();
		$phraseCache = vB5_Template_Phrase::instance();

		// In BBCode parser, the templates of inner BBCode are registered first,
		// so they should be replaced after the outer BBCode templates. See VBV-4834.
		$templateCache->setRenderTemplatesInReverseOrder(true);

		// Parse the bbcode
		$result = $parser->doParse($text, $options['allowhtml'], $options['allowsmilies'], $options['allowbbcode'], $options['allowimagebbcode'], true, false, $options['htmlstate']);

		$templateCache->replacePlaceholders($result);
		$phraseCache->replacePlaceholders($result);
		$templateCache->setRenderTemplatesInReverseOrder(false);

		return $result;
	}

	private static function parseInternal(vB5_Template_BbCode $parser, $text, $options = array(), $attachments = array())
	{
		if (!isset($options['allowhtml']))
		{
			$options['allowhtml'] = false;
		}

		if (!isset($options['allowsmilies']))
		{
			$options['allowsmilies'] = true;
		}

		if (!isset($options['allowbbcode']))
		{
			$options['allowbbcode'] = true;
		}

		if (!isset($options['allowimagebbcode']))
		{
			$options['allowimagebbcode'] = true;
		}

		if (isset($options['userid']))
		{
			$parser->setParseUserinfo($options['userid']);
		}

		$parser->setAttachments($attachments);
		/*
		 * If we have new attachments, we need to know whether it's an image or not so we can choose the correct
		 * tag (img or a). We need to grab & check the file extension for that, which is saved in the filedata table.
		 * Let's prefetch all of them so we don't have to hit the DB one at a time.
		 */
		preg_match_all('#\[attach(?:=(right|left|config))?\]temp_(\d+)_(\d+)_(\d+)\[/attach\]#i', $text, $matches);
		if (!empty($matches[2]))
		{
			$filedataids = array();
			foreach($matches[2] AS $filedataid)
			{
				$filedataids[$filedataid] = $filedataid;
			}
			$parser->prefetchFiledata($filedataids);
		}

		// Parse the bbcode
		$result = $parser->doParse($text, $options['allowhtml'], $options['allowsmilies'], $options['allowbbcode'], $options['allowimagebbcode']);

		return $result;
	}

	function evalCode($code)
	{
		ob_start();
		eval($code);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public function actionResolveIp($ip)
	{
		return @gethostbyaddr($ip);
	}

	/** parse the text table's rawtext field. At this point we just register. We do the parse and replace later in a block

	 	@param	int		the nodeid
	 * @param	mixed	array of bbcode options
	 *
	 * @return	string
	 */
	public function parseNodeText($nodeid, $bbCodeOptions = array(), $contentPage = 1)
	{
		if (!is_array($bbCodeOptions))
		{
			$bbCodeOptions = array();
		}

		if (empty($nodeid) OR !is_numeric($nodeid))
		{
			return '';
		}

		if (empty($contentPage) OR !is_numeric($contentPage))
		{
			$contentPage = 1;
		}

		return vB5_Template_NodeText::instance()->register($nodeid, $bbCodeOptions, $contentPage);
	}


	/** inserts the Google Analytics code, if set
	*
	* 	@return	string	Either empty string to the GA javascript code
	**/
	public static function getGACode()
	{
		$vboptions = vB5_Template_Options::instance()->getOptions();

		if (empty($vboptions['options']['ga_enabled']) OR empty($vboptions['options']['ga_code']))
		{

			return '';
		}
		return $vboptions['options']['ga_code'];
	}

	/** inserts the Google Ownership Verification Meta Tag, if set
	 *
	 * 	@return	string	Either empty string to the Google Ownership Verification Meta tag
	 **/
	public static function getGoogleVerificationTag()
	{
		$vboptions = vB5_Template_Options::instance()->getOptions();

		if (empty($vboptions['options']['google_ownership_verification_enable']) OR empty($vboptions['options']['google_ownership_verification_tag']))
		{

			return '';
		}
		return $vboptions['options']['google_ownership_verification_tag'];
	}

	/** inserts the Bing Ownership Verification Meta Tag, if set
	 *
	 * 	@return	string	Either empty string to the Bing Ownership Verification Meta tag
	 **/
	public static function getBingVerificationTag()
	{
		$vboptions = vB5_Template_Options::instance()->getOptions();

		if (empty($vboptions['options']['bing_ownership_verification_enable']) OR empty($vboptions['options']['bing_ownership_verification_tag']))
		{

			return '';
		}
		return $vboptions['options']['bing_ownership_verification_tag'];
	}

	/** parse the text table's rawtext field. At this point we just register. We do the parse and replace later in a block

	@param	int		the nodeid
	 * @param	mixed	array of bbcode options
	 *
	 * @return	string
	 */
	public function parseNodePreview($nodeid, $bbCodeOptions  = array())
	{
		if (empty($nodeid))
		{
			return '';
		}

		return vB5_Template_NodeText::instance()->registerPreview($nodeid, $bbCodeOptions);
	}

	/** gets a single page title.
	*
	*	@param	int		the nodeid
	*	@param	int		the content page. Defaults to one
	*/
	public function fetchPageTitle($nodeid, $contentPageId = 1)
	{
		return vB5_Template_NodeText::instance()->fetchPageTitle($nodeid, $contentPageId);
	}

	/** gets a single page title.
	 */
	public function fetchArticlePaging($nodeid)
	{
		return vB5_Template_NodeText::instance()->fetchArticlePaging($nodeid);
	}
	/** returns a placeholder for the debug information.
	 *
	 * @return	string
	 */
	public static function debugInfo()
	{
		self::$needDebug = true;
		return '<!-DebugInfo-->';
	}

	/** Returns the flag saying whether we should add debug information
	 *
	 *	@return		bool
	 **/
	public static function needDebug()
	{
		return self::$needDebug;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84668 $
|| #######################################################################
\*=========================================================================*/
