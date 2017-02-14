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
* Class to handle shutdown
*
* @package	vBulletin
* @version	$Revision: 83435 $
* @author	vBulletin Development Team
* @date		$Date: 2014-12-10 10:32:27 -0800 (Wed, 10 Dec 2014) $
*/
class vB_Shutdown
{
	/**
	 * A reference to the singleton instance
	 *
	 * @var vB_Cache_Observer
	 */
	protected static $instance;

	/**
	 * An array of shutdown callbacks to call on shutdown
	 */
	protected $callbacks;

	protected $called = false;
	/**
	 * Constructor protected to enforce singleton use.
	 * @see instance()
	 */
	protected function __construct(){}

	/**
	 * Returns singleton instance of self.
	 *
	 * @return vB_Shutdown
	 */
	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$class = __CLASS__;
			self::$instance = new $class();
		}

		return self::$instance;
	}

	/**
	* Add callback to be executed at shutdown
	*
	* @param array $callback					- Call back to call on shutdown
	*/
	public function add($callback)
	{
		if (!isset($this->callbacks) OR !is_array($this->callbacks))
		{
			$this->callbacks = array();
		}

		$this->callbacks[] = $callback;
	}

	// only called when an object is destroyed, so $this is appropriate
	public function shutdown()
	{
		if ($this->called)
		{
			return; // Already called once.
		}

		$session = vB::getCurrentSession();
		if (is_object($session))
		{
			$session->save();
		}

		if (sizeof($this->callbacks))
		{
			foreach ($this->callbacks AS $callback)
			{
				call_user_func($callback);
			}

			unset($this->callbacks);
		}

		$this->setCalled();
	}

	public function __wakeup()
	{
		unset($this->callbacks);
	}

	public function setCalled()
	{
		$this->called = true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
