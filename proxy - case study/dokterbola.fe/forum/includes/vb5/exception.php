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

class vB5_Exception extends Exception
{
	/**
	 * Constructor.
	 * Checks whether the error should be logged, mailed and/or debugged.
	 *
	 * @TODO:
	 *
	 * $code, $line and $file should only be specified if the exception was thrown
	 * because of a PHP error.  The code should be the PHP error level of the error.
	 * $file and $line will override where the exception was thrown from and instead
	 * be set to where the PHP error occured.
	 *
	 * @param string $message				- A description of the error
	 * @param int $code						- The PHP code of the error
	 * @param string $file					- The file the exception was thrown from
	 * @param int $line						- The line the exception was thrown from
	 */
	public function __construct($message, $code = false, $file = false, $line = false)
	{
		parent::__construct($message, $code);

		// Set code
		if ($code)
		{
			$this->code = $code;
		}

		// Set file
		if ($file)
		{
			$this->file = $file;
		}

		// Set line
		if ($line)
		{
			$this->line = $line;
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
