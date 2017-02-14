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
 * Null Cache.
 * Handler that just does nothing. Mostly needed for installer/upgrader
 * @see vB_Cache
 *
 * @package vBulletin
 * @author vBulletin Development Team
 * @version $Revision: 83435 $
 * @since $Date: 2014-12-10 10:32:27 -0800 (Wed, 10 Dec 2014) $
 * @copyright vBulletin Solutions Inc.
 */
class vB_Cache_Null extends vB_Cache
{
	/*Properties====================================================================*/

	/**
	 *
	 * @var vB_dB_Assertor
	 */
	protected $assertor;

	/**
	 *
	 * @var requestStart
	 */
	protected $requestStart;

	/*
	 * Cache
	 */
	protected $cache = array();


	/*Construction==================================================================*/
	public function __construct($cachetype)
	{
		$this->cachetype = $cachetype;
	}
	
	protected function writeCache($cache){}

	protected function readCache($key){}

	protected function purgeCache($key){}

	protected function expireCache($key){}
	

	public function write($key, $data, $lifetime_mins = false, $events = false)
	{
		return false;
	}

	public function saveCacheInfo($cacheid)
	{
		return false;
	}

	public function restoreCacheInfo($cacheid)
	{
		return false;
	}

	public function isLoaded($key)
	{
		return false;
	}

	public function read($keys, $writeLock = false, $save_meta = false)
	{
		return false;
	}

	public function purge($cache_id)
	{
		return $this;
	}

	public function expire($cache_id)
	{
		return $this;
	}

	public function event($events)
	{
		return $this;
	}

	public function lock($key)
	{
		return false;
	}

	public function unlock($key)
	{
		return false;
	}

	/*Clean=========================================================================*/

	public function clean($only_expired = true){}

	/*Observers=====================================================================*/
	public function attachObserver(vB_Cache_Observer $observer){}

	public function removeObserver(vB_Cache_Observer $observer){}
	
	/*Shutdown=====================================================================*/

	public function shutdown(){}

	public function cleanNow(){}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
