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
 * Memcached.
 * Handler that caches and retrieves data from the memcache.
 * @see vB_Cache
 *
 * @package vBulletin
 * @author vBulletin Development Team
 * @version $Revision: 87129 $
 * @since $Date: 2016-02-25 11:18:15 -0800 (Thu, 25 Feb 2016) $
 * @copyright vBulletin Solutions Inc.
 */
class vB_Cache_Memcached extends vB_Cache
{
	const LOCK_PREFIX = 'lock_';
	const EVENT_PREFIX = '~ev_';

	/** There is a strangeness in the event handling of this class. We cannot guarantee a memcache record will be available
	 * when desired. If we have a cache record but not an event, that's not a problem.  But if we have a cache record but
	 * not an event, we have potentially bad data. So:
	 *
	 * We store both cache records and events in memcache.
	 *
	 * an event record is just an integer. A unix time value represents the last time the event was called. 0 means it has never been called.
	 *
	 * On write, add the current time and the events to the cache record. We also make sure there is an event record for all associated events.
	 *
	 * On read, if we get a value we check all the events. If any have been called more recently than the cache record, we remove the
	 *	record and return false. If we don't have an event record, we can't know if the cached value is valid, so we remove it and
	 *  return false
	 *
	 * For the in-memory copy of the data we treat as normal. We retain the values_read, no_values, and add an events array. So when
	 * an event is called we clear the values_read array and add to no_values;
	 */

	/*Properties====================================================================*/
	/**
	 * A reference to the singleton instance
	 *
	 * @var vB_Cache_Memcached
	 */
	protected static $instance;

	/**
	* The Memcached wrapper
	*
	* @var	vB_Memcache
	*/
	protected $memcached = null;

	protected $events = array();
	/*Construction==================================================================*/

	/**
	 * Constructor protected to enforce singleton use.
	 * @see instance()
	 */
	protected function __construct($cachetype)
	{
		parent::__construct($cachetype);

		$this->memcached = vB_Memcache::instance();
		$check = $this->memcached->connect();

		if ($check === 3)
		{
			trigger_error('Unable to connect to memcache server', E_USER_ERROR);
		}

		$this->expiration = 48*60*60; // two days
		$this->timeNow = vB::getRequest()->getTimeNow();
		//get the memcache prefix.
		$config = vB::getConfig();

		if (empty($config['Cache']['memcacheprefix']))
		{
			$this->prefix = TABLE_PREFIX;
		}
		else
		{
			$this->prefix = $config['Cache']['memcacheprefix'];
		}
	}

	/**
	 * Returns singleton instance of self.
	 * @todo This can be inherited once late static binding is available.  For now
	 * it has to be redefined in the child classes
	 *
	 * @return vB_Cache_Memcached						- Reference to singleton instance of cache handler
	 */
	public static function instance($type = NULL)
	{
		if (!isset(self::$instance))
		{
			$class = __CLASS__;
			self::$instance = new $class($type);
		}

		return self::$instance;
	}

	/**
	 * Returns the UNIX timestamp of the last ocurrence of $event, and FALSE if there isn't one
	 *
	 * @param string $event
	 * @return mixed
	 */
	protected function getEventTime($event)
	{
		if ($time = $this->memcached->get($this->prefix . self::EVENT_PREFIX . $event))
		{
			return $time;
		}

		return FALSE;
	}

	/**
	 * Returns the UNIX timestamp of the last ocurrence of $events. If there was none the return for that key will be missing.
	 *
	 * @param mixed		array of strings
	 * @return mixed	array of either FALSE or int
	 */
	protected function getEventTimes($events)
	{
		//We need to do some mapping, because we are passed the raw event strings,
		// but the keys  in memcache have a prefix.
		$keys = array();
		foreach($events AS $event)
		{
			$keys[$event] = $this->prefix . self::EVENT_PREFIX . $event ;
		}

		$found = $this->memcached->get($keys);

		if (empty($found))
		{
			return array();
		}

		$results = array();
		foreach($events AS $event)
		{
			if (isset($found[$keys[$event]]))
			{
				$results[$event] = $found[$keys[$event]];
			}
		}
		return $results;
	}

	/**
	 * Store event in memcache
	 *
	 * @param string $event - Event identifier
	 * @param int $time - If 0, the event won't overwrite any memcache entry
	 */
	protected function setEventTime($event, $time)
	{
		// if $time = 0, this is a dummy event which should not overwrite real events in memcache
		if ($time == 0 )
		{
			$method =  'add';
			$time = $this->timeNow - 1;
		}
		else
		{
			$method = 'set';
		}

		$this->memcached->$method($this->prefix . self::EVENT_PREFIX . $event, $time);
	}

	/*Initialisation================================================================*/

	/**
	 * Writes the cache data to storage.
	 *
	 * @param array	includes key, data, expires
	 */
	protected function writeCache($cache)
	{
		$ptitle = $this->prefix . $cache['key'];

		try
		{
			$this->lock($cache['key']);
			$this->memcached->set($ptitle, $cache, $cache['expires']);

			if (!empty($cache['events']))
			{
				foreach ($cache['events'] AS $event)
				{
					// store the cache information in memory so we can clear them.
					if (empty ($this->events[$event]))
					{
						$this->events[$event] = array();
					}
					$this->events[$event][$cache['key']] = $cache['key'];

					if (!$this->getEventTime($event))
					{
						// no events in memcached, set event time to 0
						$this->setEventTime($event, 0);
					}
				}
			}
			$this->unlock($cache['key']);
		}
		catch(Exception $e)
		{
			$this->unlock($cache['key']);
		}
	}

	/**
	 * Reads the cache object from storage.
	 *
	 * @param string $key						- Id of the cache entry to read
	 * @return array	includes key, data, expires
	 */
	protected function readCache($key)
	{
		$ptitle = $this->prefix . $key;
		$entry = $this->memcached->get($ptitle);

		if (!$entry)
		{
			return false;
		}

		// check if it is still valid
		if (!empty($entry['events'])AND is_array($entry['events']))
		{
			foreach ($entry['events'] AS $event)
			{
				// We have to store the retrieved record's events in memory so that calling event() will work correctly even if
				// we read from cache but did not write to cache within the same session. VBV-12944
				if (empty ($this->events[$event]))
				{
					$this->events[$event] = array();
				}
				$this->events[$event][$key] = $key;

				$time = $this->getEventTime($event);

				if (($time == false) OR ($time >= $entry['created']))
				{
					$this->expireCache($key);
					return false;
				}
			}
		}
		return $entry;
	}


	/**
	 * Reads an array of cache objects from storage.
	 *
	 * @param  mixed	array of Ids of the cache entry to read
	 * @return array of array	includes key, data, expires
	 */
	/**
	 * Reads an array of cache objects from storage.
	 *
	 * @param  mixed	array of Ids of the cache entry to read
	 * @return array of array	includes key, data, expires
	 */
	protected function readCacheArray($keys, $writeLock = false)
	{
		if (empty($keys))
		{
			return array();
		}

		if (count($keys) == 1)
		{
			//faster to just call the method for single key. Saves overhead
			$key = array_pop($keys);
			return array($key => $this->readCache($key));
		}

		//There's some bookkeeping here. We need to map keys from the app with
		//prefixed keys to get data, and then map back.
		$memKeys = array();
		foreach($keys AS $key)
		{
			$memKeys[] = $this->prefix . $key;
		}
		$cached = $this->memcached->get($memKeys);
		//if we didn't get anything, we're done.
		if (empty($cached))
		{
			return array();
		}
		//now map the result back to the original keys
		$found = array();
		foreach($cached AS $cacheRecord)
		{
			$found[$cacheRecord['key']] = $cacheRecord;
		}
		$allEvents = array();
		foreach($found AS $key => $record)
		{
			try
			{
				if (isset($record['data']))
				{
					//we need to check events. Let's get all the events so we can
					//make one call.
					if (!empty($record['events']))
					{
						foreach ((array)$record['events'] AS $event)
						{
							// We have to store the retrieved record's events in memory so that calling event() will work correctly even if
							// we read from cache but did not write to cache within the same session. VBV-12944
							if (!isset($this->events[$event]))
							{
								$this->events[$event] = array();
							}
							$this->events[$event][$key] = $key;	// this key is the original, non-prefixed key. See "now map the result back to the original keys" above.
							$allEvents[] = $event;
						}
					}

				}
				else
				{
					unset($found[$key]);
				}
			}
			catch (exception $e)
			{
				//If we got here, something was improperly serialized
				unset($found[$key]);
			}
		}

		//Possible we've eliminated everything, or there are no events. If so we're done.
		if (empty($found) OR empty($allEvents))
		{
			return $found;
		}

		$eventTimes = $this->getEventTimes($allEvents);
		foreach($found AS $key => $record)
		{
			//now check the events.
			if (!empty($record['events'])AND is_array($record['events']))
			{
				foreach ($record['events'] AS $event)
				{
					if (!isset($eventTimes[$event]) OR ($eventTimes[$event] >= $record['created']))
					{
						unset($found[$key]);
						$this->purgeCache($key);
						continue;
					}
				}
			}
		}

		return $found;
	}



	/**
	 * Removes a cache object from storage.
	 *
	 * @param int $key							- Key of the cache entry to purge
	 * @return bool								- Whether anything was purged
	 */
	protected function purgeCache($key)
	{
		$ptitle = $this->prefix . $key;
		$this->memcached->delete($ptitle);

		return true;
	}

	/**
	 * Sets a cache entry as expired in storage.
	 *
	 * @param string/array $key						- Key of the cache entry to expire
	 *
	 * @return	array of killed items
	 */
	protected function expireCache($keys)
	{
		if (empty($keys))
		{
			return;
		}

		if (!is_array($keys))
		{
			$keys = array($keys);
		}

		foreach ($keys AS $key)
		{
			$this->memcached->delete($this->prefix . $key);
		}
	}

	/**
	 * Expires cache objects based on a triggered event.
	 *
	 * An event handling vB_CacheObserver must be attached to handle cache events.
	 * Generally the CacheObservers would respond by calling vB_Cache::expire() with
	 * the cache_id's of the objects to expire.
	 *
	 * @param string | array $event				- The name of the event
	 */
	public function event($events)
	{
		// set to an array of strings
		$events = (array)$events;

		foreach ($events AS $key => $event)
		{
			$strEvent = strval($event);
			$events[$key] = $strEvent;
			$this->setEventTime($strEvent, $this->timeNow);

			// This means that every setting of $this->values_read[KEY] should be accompanied by
			// setting of $this->events[EVENTSTR][KEY] = KEY for all associated events
			if (!empty($this->events[$strEvent]))
			{
				foreach ($this->events[$strEvent] AS $cacheKey)
				{
					unset($this->values_read[$cacheKey]);
				}
			}
		}
		return $this;
	}

	/**
	 * Locks a cache entry.
	 *
	 * @param string $key						- Key of the cache entry to lock
	 */
	public function lock($key)
	{
		// For some weird reason, storing a simple timestamp does not work, so use prefix.
		$lock_expiration = max(array(ini_get('max_execution_time'),30));
		return ($this->memcached->add($this->prefix . self::LOCK_PREFIX . $key, self::LOCK_PREFIX . $this->timeNow, $lock_expiration));
	}

	public function unlock($key)
	{
		return ($this->memcached->delete($this->prefix . self::LOCK_PREFIX . $key));
	}


	/*Clean=========================================================================*/

	/**
	 * Cleans cache.
	 *
	 * @param bool $only_expired				- Only clean expired entries
	 */
	public function clean($only_expired = true)
	{
		if (!$only_expired)
		{
			$this->memcached->flush();
			$this->cleanNow();
			$this->events = array();

			if (self::$cacheLogging)
			{
				$this->logCacheAction(0, self::CACHE_LOG_CLEAR, $this->cachetype);
			}
		}
	}

	/**
	 * Cleans things stored in memory. Used for unit tests ONLY.
	 */
	public function cleanMemoryOnly()
	{
		if (defined('VB_UNITTEST'))
		{
			$this->cleanNow();
			$this->events = array();

			if (self::$cacheLogging)
			{
				$this->logCacheAction(0, self::CACHE_LOG_CLEAR, $this->cachetype);
			}
		}
	}



	/*Shutdown======================================================================*\

	/**
	 * Perform any finalisation on shutdown.
	 */
	public function shutdown()
	{
		parent::shutdown();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87129 $
|| #######################################################################
\*=========================================================================*/
