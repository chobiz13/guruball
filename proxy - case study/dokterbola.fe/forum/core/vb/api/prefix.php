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
 * vB_Api_Prefix
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Prefix extends vB_Api
{
	/**
	 * @var vB_dB_Assertor
	 */
	protected $assertor;

	/*
	 * Cache for prefix sets. Stored as array[nodeid][$permcheck]
	 *
	 * @var array
	 */
	protected $cache = array();


	public function __construct()
	{
		parent::__construct();

		$this->assertor = vB::getDbAssertor();
	}

	/**
	 * Fetch available prefixes of a Channel. It has permission check,
	 * So if an user doesn't have permission to use a prefix, the prefix
	 * won't be returned.
	 *
	 * @param int $nodeid Channel node ID
	 * @param bool $permcheck If set to true, it will return only the prefixes that
	 *        a user can use
	 *
	 * @return array Prefixes in format [PrefixsetID][PrefixID] => array(prefixid, usergroupids)
	 */
	public function fetch($nodeid, $permcheck = true)
	{
		if (!$nodeid)
		{
			return array();
		}

		if (!isset($this->cache[$nodeid][$permcheck]))
		{
			require_once(DIR . '/includes/functions_prefix.php');
			$prefixsets = array();
			if ($prefixsets = fetch_prefix_array($nodeid))
			{
				if ($permcheck)
				{
					foreach ($prefixsets AS $prefixsetid => $prefixes)
					{
						foreach ($prefixes AS $prefixid => $prefix)
						{
							if (!can_use_prefix($prefixid, $prefix['restrictions']))
							{
								unset($prefixsets[$prefixsetid][$prefixid]);
							}
						}
					}
				}
			}
			$this->cache[$nodeid][$permcheck] = $prefixsets;
		}

		return $this->cache[$nodeid][$permcheck];
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
