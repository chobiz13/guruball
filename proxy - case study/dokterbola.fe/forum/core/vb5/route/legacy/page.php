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
 * extends legacy_node with pagination parameter support
 */
abstract class vB5_Route_Legacy_Page extends vB5_Route_Legacy_Node
{
	public function __construct($routeInfo = array(), $matches = array(), $queryString = '')
	{
		if (!empty($routeInfo))
		{
			parent::__construct($routeInfo, $matches, $queryString);
		}
		else
		{
			$this->arguments = array('oldid' => '$oldid', 'pagenum' => '$pagenum');
		}
	}
	
	protected function captureOldId()
	{
		$argument = & $this->arguments;
		$param = & $this->queryParameters;
		$keys = array_keys($param);
		if (intval($argument['oldid']))
		{
			$oldid = $argument['oldid'];
		}
		else if (!empty($param) AND preg_match('#^(?P<oldid>[1-9]\d*)(?P<title>(?:-[^?&/]*)*)(?:/page(?P<pagenum>[1-9]\d*))?#', $keys[0], $matches))
		{
			$oldid = $matches['oldid'];
			$argument['pagenum'] = empty($matches['pagenum'])?:$matches['pagenum'];
		}
		else if ($set=array_intersect($keys, $this->idkey) AND $pid=intval($param[reset($set)]))
		{
			$oldid = $pid;
			$argument['pagenum'] = empty($param['page'])?:$param['page'];
		}
		else
		{
			throw new vB_Exception_404('invalid_page');
		}
		return $oldid;
	}
	
	public function getRegex()
	{
		return $this->prefix . '(?:/(?P<oldid>[1-9]\d*)(?P<title>(?:-[^?&/]*)*)(?:/page(?P<pagenum>[1-9]\d*))?)?';
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
