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

class vB5_Route_Search extends vB5_Route
{
	public function getUrl()
	{
		// the regex contains the url
		$url = '/' . $this->regex;
		
		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}
		
		return $url;
	}

	public function getCanonicalRoute()
	{
		if (!isset($this->canonicalRoute))
		{
			if (!empty($this->arguments['pageid']))
			{
				$page = vB::getDbAssertor()->getRow('page', array('pageid'=>$this->arguments['pageid']));
			}
			if (!empty($page['routeid']))
			{
				$this->canonicalRoute = self::getRoute($page['routeid'], array(), $this->queryParameters);
			}
			else
			{
				return $this;
			}
		}

		return $this->canonicalRoute;
	}

	protected static function validInput(array &$data)
	{
		if (
				!isset($data['contentid']) OR !is_numeric($data['contentid']) OR
				!isset($data['prefix']) OR
				!isset($data['action'])
			)
		{
			return FALSE;
		}

		$data['regex'] = $data['prefix'];
		$data['class'] = __CLASS__;
		$data['controller']	= 'search';
		$data['arguments']	= '';//serialize(array('pageid' => $data['contentid']));

		return parent::validInput($data);
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
