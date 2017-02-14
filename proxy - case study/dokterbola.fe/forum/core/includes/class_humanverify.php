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
* Abstracted human verification class
*
* @package 		vBulletin
* @version		$Revision: 86409 $
* @date 		$Date: 2015-12-29 10:32:25 -0800 (Tue, 29 Dec 2015) $
*
*/
class vB_HumanVerify
{
	/**
	* Constructor
	* Does nothing :p
	*
	* @return	void
	*/
	function __construct() {}

	/**
	* Singleton emulation: Select library
	*
	* @return	object
	*/
	public static function &fetch_library(&$registry, $library = '')
	{
		global $show;
		static $instance;

		if (!$instance)
		{
			if ($library)
			{
				// Override the defined vboption
				$chosenlib = $library;
			}
			else
			{
				$vboptions = vB::getDatastore()->getValue('options');
				$chosenlib = ($vboptions['hv_type'] ? $vboptions['hv_type'] : 'Disabled');
			}

			$selectclass = 'vB_HumanVerify_' . $chosenlib;
			$chosenlib = strtolower($chosenlib);
			require_once(DIR . '/includes/class_humanverify_' . $chosenlib . '.php');
			$instance = new $selectclass($registry);
		}

		return $instance;
	}
}


/**
* Abstracted human verification class
*
* @package 		vBulletin
* @version		$Revision: 86409 $
* @date 		$Date: 2015-12-29 10:32:25 -0800 (Tue, 29 Dec 2015) $
*
* @abstract
*/
abstract class vB_HumanVerify_Abstract
{
	/**
	* Main data registry
	*
	* @var	vB_Registry
	*/
	var $registry = null;

	/**
	* Error string
	*
	* @var	string
	*/
	var $error = '';

	/**
	* Last generated hash
	*
	* @var	string
	*/
	var $hash = '';

	/**
	* Constructor
	* Don't allow direct construction of this abstract class
	* Sets registry
	*
	* @return	void
	*/
	function __construct(&$registry)
	{
		$this->registry =& $registry;
	}

	/**
	 * Deleted a Human Verification Token
	 *
	 * @param	string	The hash to delete
	 * @param	string	The Corresponding Option
	 * @param	integer	Whether the token has been viewd
	 *
	 * @return	boolean	Was anything deleted?
	 *
	*/
	function delete_token($hash, $answer = NULL, $viewed = NULL)
	{
		$data = array(
			'hash' => $hash,
		);

		if ($answer !== NULL)
		{
			$data['answer'] = $answer;
		}
		if ($viewed !== NULL)
		{
			$data['viewed'] = intval($viewed);
		}

		if ($this->hash == $hash)
		{
			$this->hash = '';
		}

		vB::getDbAssertor()->assertQuery('humanverify', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
			vB_dB_Query::CONDITIONS_KEY => $data,
		));

		return vB::getDbAssertor()->affected_rows() ? true : false;
	}

	/**
	 * Returns the HTML to be displayed to the user for Human Verification
	 *
	 * @param	string	Passed to template
	 *
	 * @return 	string	HTML to output
	 *
	 */
	function output_token($var_prefix = 'humanverify') {}

	/**
	 * Generates a Random Token and stores it in the database
	 *
	 * @param	boolean	Delete the previous hash generated
	 *
	 * @return	array	an array consisting of the hash, and the answer
	 *
	*/
	function generate_token($deletehash = true)
	{
		$verify = array(
			'hash'   => md5(uniqid(vbrand(), true)),
			'answer' => $this->fetch_answer(),
		);

		if ($deletehash AND $this->hash)
		{
			$this->delete_token($this->hash);
		}
		$this->hash = $verify['hash'];

		vB::getDbAssertor()->assertQuery('humanverify', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'hash' => $verify['hash'],
			'answer' => $verify['answer'],
			'dateline' => vB::getRequest()->getTimeNow()
		));

		return $verify;
	}

	/**
	 * Verifies whether the HV entry was correct
	 *
	 * @param	array	An array consisting of the hash, and the inputted answer
	 *
	 * @return	boolean
	 *
	*/
	function verify_token($input)
	{
		return true;
	}

	/**
	 * Returns any errors that occurred within the class
	 *
	 * @return	mixed
	 *
	*/
	function fetch_error()
	{
		return $this->error;
	}

	/**
	 * Generates an expected answer
	 *
	 * @return	mixed
	 *
	*/
	function fetch_answer() {}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86409 $
|| #######################################################################
\*=========================================================================*/
