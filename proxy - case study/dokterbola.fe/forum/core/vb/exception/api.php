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
 * Api Exception
 * Exception thrown by API methods
 */
class vB_Exception_Api extends vB_Exception
{
	protected $errors = array();

	public function __construct($phrase_id = '', $args = array(), $message = '', $code = false, $file = false, $line = false)
	{
		$fullmessage = '<b>API Error</b><br>';

		if ($message)
		{
			$fullmessage .= '<b>Message:</b>: ' . htmlspecialchars($message) . '<br>';
		}

		if (!empty($line))
		{
			$fullmessage .= '<b>Line:</b> ' . htmlspecialchars($line) . '<br>';
		}

		if ($phrase_id)
		{
			//handle the case where the error is passed in as an array of phrase + params
			if (is_array($phrase_id))
			{
				$temp = array_shift($phrase_id);
				if (!$args)
				{
					$args = $phrase_id;
				}
				$phrase_id = $temp;
			}

			if (!is_array($args))
			{
				$args = array($args);
			}
			$this->add_error($phrase_id, $args);
			$fullmessage .= '<b>Error:</b> ' . htmlspecialchars($phrase_id) . '<br>';
			if ($args)
			{
				$fullmessage .= '<b>Args:</b><br><pre style="font-family:Lucida Console,Monaco5,monospace;font-size:small;overflow:auto;border:1px solid #CCC;">';
				$fullmessage .= htmlspecialchars(var_export($args, true));
				$fullmessage .= '</pre>';
			}
		}

		parent::__construct($fullmessage, $code, $file, $line);
	}

	public function add_error($phrase_id, array $args = array())
	{
		$error = $args;
		array_unshift($error, $phrase_id);
		$this->errors[] = $error;
	}

	public function has_errors()
	{
		return !empty($this->errors);
	}

	public function get_errors()
	{
		return $this->errors;
	}

	public function has_error($phrase_id)
	{
		if (!$this->has_errors())
		{
			return false;
		}
		foreach ($this->errors as $error)
		{
			if (in_array($phrase_id, $error))
			{
				return true;
			}
		}
		return false;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85111 $
|| #######################################################################
\*=========================================================================*/
