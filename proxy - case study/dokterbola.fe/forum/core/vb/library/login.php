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
 * @package vBLibrary
 *
 * This class depends on the following
 *
 * * vB_Utility_Password_Algorithm
 * * vB Environment including the datastore and request objects
 * * Datastore value 'pwschemes'.  Note that this 
 *
 * It does not and should not depend on the permission objects.  All permissions
 * should be handled outside of the class and passed to to the class in the form 
 * of override flags.
 * 
 */

/**
 * vB_Library_Login
 *
 * @package vBLibrary
 * @access public
 */
class vB_Library_Login extends vB_Library
{
	/**
	 *	Verify a login value
	 *
	 *  In addition to the user's password, we'll verify do a couple of additional things
	 *  * If the password hash scheme is disabled, we'll reject the login entirely
	 *  * If the scheme is not current, we will attempt to quietly rehash
	 *  * If the scheme has been deprecated and we cannot rehash, then we'll expire the password. 
	 *
	 *  @param array $login  The login info of the user to verify containg
	 *  	* token -- the password hash to verify against
	 *  	* scheme -- the scheme used to generate the hash
	 *  @param $passwords array.  Array of password variants in the form
	 *  	array('password' => $password, 'encoding' => $encoding)
	 *  valid values for encoding are 'text' and 'md5'.  This is required
	 *  to handle various legacy logic that encodes the password using md5
	 *  on the front end.  We may wish to expand that to include better 
	 *  front end encodings in the future.	 
	 *  @return array
	 *  	* auth bool true if the login succeeded, false otherwise
	 *  	* remembermetoken string token to use for remember me logic (blank if not authenticated)
	 */
	public function verifyPasswordFromInfo($login, $passwords)
	{
		$datastore = vB::getDatastore();
		$schemes = $datastore->getValue('pwschemes');

		if (!isset($schemes[$login['scheme']]))
		{
			throw new vB_Exception_Api('invalid_password_scheme');
		}

		foreach($passwords AS $password)
		{
			$string = $password['password'];
			$encoding = $password['encoding'];

			if ($encoding == 'text')
			{
				//if text we need to encode before passing to the verfication system
				$string = md5($string);
			}
			else if ($encoding == 'md5')
			{
				//deliberate fallthrough
			}
			else
			{
				//if we don't recognize the scheme, then ignore.
				continue;
			}
			
			$result =  vB_Utility_Password_Algorithm::instance($login['scheme'])->verifyPassword($string, $login['token']);
			if ($result)
			{
				$schemes = $this->getSchemesByPriority($schemes);
				reset($schemes);
				$top = current($schemes);

				if ($login['scheme'] != $top)
				{
					$this->setPassword($login['userid'], $string, array('passwordhistorylength' => 0), 
						array('passwordhistory' => true));
				}

				return array (
					'auth' => true,
					'remembermetoken' => $this->generateRememberMeToken($login['token'], vB_Request_Web::$COOKIE_SALT)
				);
			}
		}

		return array (
			'auth' => false,
			'remembermetoken' => ''
		);
	}

	/**
	 * Verify the remember token.
	 *
	 * This verifies if the "rememberme" token returned by the password verification
	 * function is valid for the given user
	 *
	 * @param array $login login information
	 * 	* token -- the user's password token
	 * 	* scheme -- the user's password scheme
	 * @param string $remembermetoken -- The token to checka
	 * @return array
	 * 	* auth (boolean) -- true if the rememberme token matches, false otherwise
	 * 	* remembermetoken (string) -- the "current" rememberme token.  This will be the same as the rememberme token 
	 * 			passed in unless we validated based on a legacy value.  This should be used to update the rememberme value
	 * 			stored with the client.  If the auth failed, this will be blank.
	 */
	public function verifyRememberMeFromInfo($login, $remembermetoken)
	{
		$newtoken = $this->generateRememberMeToken($login['token'], vB_Request_Web::$COOKIE_SALT);
		$result = ($newtoken == $remembermetoken);

		//complete hack which requires the kind of understanding of the legacy hash 
		//that totally breaks the enscapulation of the new password system.  However
		//explaining good software engineering practices to customers irate because 
		//their remember me cookie broke after upgrade isn't not really high on 
		//my to do list.  This can be removed once those cookies become extinct in the 
		//wild.
		if (!$result AND $login['scheme'] == 'legacy')
		{
			list($hash, $salt) = explode(' ', $login['token']);
			$result = (md5($hash . vB_Request_Web::$COOKIE_SALT) == $remembermetoken);
		}

		return array (
			'auth' => $result,
			'remembermetoken' => ($result ? $newtoken : '') 
		);		
	}

	/**
	 *	Change the password for a user
	 *
	 *	@param int $userid -- the id of the user to change the passwordor
	 *	@param string $password -- the passsword to use for the new hash.  May be md5 encoded.
	 *	@param array $checkOptions -- values for permission checks.  These are all required (though they might be ignored if 
	 *		the specific check is skipped).
	 *		* passwordhistorylength -- The number of days to look back for duplicate passwords
	 *	@param array $checkOverrides -- checks to skip.  This will usually be based on user permissions, but we shouldn't
	 *		check those in the library.  All of these fields are optional. If not set or set to false, the check
	 *		will be performed.  If set to true, then the check will be skipped.
	 *		* passwordhistory -- skip the check for the password history for this user.  Will will still store the 
	 *				password set in the history
	 *	@return no return.  Will throw an exception if setting the password fails.
	 *	@throws vB_Exception_Api with the following possible errors 
	 *		* usernotfound -- The userid does not exist.
	 *		* invalidpassword -- The password does not meet the configured standards for the site.
	 *				Currently this only checks that the password is not the same as the username, but the caller 
	 *				should not assume that this is the only reason because this is likely to change in the future
	 *		
	 */
	public function setPassword($userid, $password, $checkOptions, $checkOverrides = array())
	{
		/*
		 * Get the user info and handle front end encoding of the password
		 */

		//get the user info.  Will be used to check password against 
		$db = vB::getDBAssertor();
		$login = $db->getRow('user', array(
			vB_Db_Query::COLUMNS_KEY => array('userid', 'username', 'token'),
			'userid' => $userid
		));

		if(!$login)
		{
			throw new vB_Exception_Api('invalid_user_specified');
		}

		//if the password isn't encoded, encode it.  This is the "magic" that makes the 
		//md5 front end encoding work.  Should eventually replace with an flexible and 
		//explicit encoding scheme
		$md5_password = $password;
		if (!$this->verifyMd5($password))
		{
			$md5_password = md5($password); 
		}


		/*
		 *	validate the (encoded) password
		 */

		//check that the password is not on the list of passwords we don't like.  Right now its a short list,
		//but we're aiming for expansion.
		$bad_passwords = array(); //if we want to prohibit common passwords, set this array to something 
		$bad_passwords[] = $login['username'];
		foreach($bad_passwords as $bad_password)
		{
			if ($md5_password == md5($bad_password))
			{
				throw new vB_Exception_Api('invalid_password_specified');
			}
		}

		//check the history
		if (empty($checkOverrides['passwordhistory']))
		{
			$lookback = (vB::getRequest()->getTimeNow() - ($checkOptions['passwordhistorylength'] * 86400));
			if(!$this->checkPasswordHistory($userid, $md5_password, $lookback))
			{
				throw new vB_Exception_Api(array('passwordhistory', $checkOptions['passwordhistorylength']));
			}
		}
		
		/*
		 *	Actually set the password
		 */
		$datastore = vB::getDatastore();
		$data = array();
		$schemes = $this->getSchemesByPriority($datastore->getValue('pwschemes'));
		foreach($schemes as $scheme)
		{
			try
			{
				$token = vB_Utility_Password_Algorithm::instance($scheme)->generateToken($md5_password);
				$data['token'] = $token;
				$data['scheme'] = $scheme;
				break;
			}
			catch (Exception $e)
			{
				//if something goes wrong, let's go to the next algorithm and try that. 
				continue;
			}
		}
		
		if (!$data)
		{
			throw new vB_Exception_Api("no_avaialble_schemes");
		}

		$data['passworddate'] = date('Y-m-d', vB::getRequest()->getTimeNow());
		$db->update('user', $data, array('userid' => $userid));

		//save the password history
		$data['userid'] = $userid;
		$data['passworddate'] = vB::getRequest()->getTimeNow();
		$db->insert('passwordhistory', $data);
	}


	/**
	 *	Reset the user's password to a randomly generated value.
	 *
	 *	Will throw an exception on failure.
	 *
	 *	@param int $userid -- the id of the user to change the passwordor
	 *	@return string -- the new password (to notify the user what it is, usually via email).
	 */
	public function resetPassword($userid)
	{
		//skip the history check.  This prevents us from having to look up the actual lookback period
		//or deleting records incorrectly if we set an artificially short lookback.  Chances are we
		//will not match any previous passwords randomly.
		$newpass = $this->fetchRandomPassword(10);
		$this->setPassword($userid, $newpass, array('passwordhistorylength' => 0), array('passwordhistory' => true));
		return $newpass;
	}


	/**
	 *	Load the scheme files from xml files 
	 */
	public function importPasswordSchemes()
	{
		$xmldir = DIR . '/includes/xml/';
		$xmlarrays = $this->readPasswordSchemes($xmldir);
		$schemes = $this->processPasswordSchemes($xmlarrays);
		vB::getDatastore()->build('pwschemes', serialize($schemes), 1);
	}

	/* ######################################################################################################
	 *	Some helper functions.  These should *not* have any dependancies on anything other than the 
	 *	utility layer and the DB object.  The entry point function should handle grabbing all other 
	 *	information from the environment and pass it through.  
	 * ######################################################################################################*/


	protected function getSchemesByPriority($schemeArray)
	{
		if (!is_array($schemeArray))
		{
			return array();
		}

		$candidates = array();
		foreach($schemeArray AS $key => $data)
		{
			//a scheme with a null priority should never be considered for encoding a 
			//new password.  These schemes exist solely to decode existing passwords
			if(is_null($data['priority']))
			{
				unset($schemeArray[$key]);
			}
			else
			{
				$candidates[$key] = $data['priority'];
			}
		}

		arsort($candidates);
		return array_keys($candidates);
	}

	/**
	 *	Fetch the scheme files from disk and returned the parsed arrays
	 *
	 *	@param string $xmldir the directory the xml files are located in
	 *	@return array the password scheme data in the form
	 *	array ('scheme' => array('priority' => $n))  
	 *	Note that the scheme is an array for potential future expansion.
	 */
	protected function readPasswordSchemes($xmldir)
	{
		if(!is_readable($xmldir))
		{
			throw new vB_Exception_Api(array('error_x', 'Could not read the includes/xml directory'));
		}

		$handle =  opendir($xmldir);
		if (!$handle)
		{
			throw new vB_Exception_Api(array('error_x', 'Could not read the includes/xml directory'));
		}

		$schemeArrays = array();
		while (($file = readdir($handle)) !== false)
		{
			if (!preg_match('#^pwschemes_(.*).xml$#i', $file, $matches))
			{
				continue;
			}

			$xmlobj = new vB_XML_Parser(false, $xmldir . $file);
			if (!($xml = $xmlobj->parse()))
			{	
				throw new vB_Exception_Api(array('error_x', 'Failed to parse password scheme file ' . $file));
			}

			$schemeArrays[] = $xml;
		}

		if(count($schemeArrays) == 0)
		{
			throw new vB_Exception_Api(array('error_x', 'No password scheme files found.'));
		}

		return $schemeArrays;
		closedir($handle);
	}
	
	protected function processPasswordSchemes($schemeArrays)
	{
		$disabledSchemes = array();
		$processedSchemes = array();
		foreach($schemeArrays AS $schemeArray)
		{
			$schemeArray = vB_XML_Parser::getList($schemeArray['scheme']);
			foreach($schemeArray AS $scheme)
			{
				//if its disbabled, just mark it and move on
				if (!empty($scheme['disabled']))
				{
					$disabledSchemes[] = $scheme['name'];
					continue;
				}
				
				//makes sure we don't pick up any stray attributes from the xml file.
				$values['priority'] = isset($scheme['priority']) ? $scheme['priority'] : null;

				//duplicate schemes are not allowed, wether in one file or in many
				if (isset($processedSchemes[$scheme['name']]))
				{
					throw new vB_Exception_Api(array('error_x', 'Duplicate scheme ' . $scheme['name']));
				}
				$processedSchemes[$scheme['name']] = $values;
			}
		}

		//anything that disabled should be treated as if we never saw it.
		foreach($disabledSchemes AS $disabled)
		{
			unset($processedSchemes[$disabled]);
		}
		return $processedSchemes;
	}

	/**
	 *	Verify that a string value is an md5 hash
	 *
	 *	@param string $md5 -- string to check for an md5 hash.
	 */
	protected function verifyMd5(&$md5)
	{
		//copied from datamanager.  
		return ((bool) preg_match('#^[a-f0-9]{32}$#', $md5));
	}

	/**
	 * Checks to see if a password is in the user's password history
	 *
	 * Will also delete any expired records in the password history.
	 *
	 * @param	integer	$userid User ID
	 * @param string $fe_password -- the frontend encoded password
	 * @param	integer	$lookback The time period to look back for passwords in seconds
	 *
	 * @return boolean Returns true if password is in the history
	 */
	protected function checkPasswordHistory($userid, $fe_password, $lookback)
	{
		$db = vB::getDBAssertor();
		
		// first delete old password history
		$db->delete('passwordhistory', array(
				'userid' => $userid,
				array('field' =>'passworddate', 'value' => $lookback, 'operator' =>  vB_dB_Query::OPERATOR_LTE)
		));

		$old_passwords = $db->select('passwordhistory', array('userid' => $userid));
		foreach($old_passwords as $old_password)
		{
			//need to use the same scheme as when the history hash was created.  If the front end scheme has changed
			//then we'll be unable to check -- we'll just have to pass it along.  When we implement front end schemes
			//other than plain md5 we'll need to do something here to check if its changed.
			try
			{
				$verify = vB_Utility_Password_Algorithm::instance($old_password['scheme'])->verifyPassword($fe_password, $old_password['token']);
			}
			catch(Exception $e)
			{
				//if we fail to hash the password we'll just ignore that history record.  Better than failing because of an old
				//record that has a now invalid scheme or something else equally silly.
				continue;
			}

			if ($verify)
			{
				return false;
			}
		}

		return true;
	}


	/**
	* Inserts a record into the password history table if the user's password has changed
	*
	* @param	integer	User ID
	*/
	protected function updatePasswordHistory($userid, $data)
	{
		if (isset($this->user['password']) AND
			(empty($this->existing['password']) OR ($this->user['password'] != $this->existing['password'])))
		{
			/*insert query*/
			$this->assertor->assertQuery('insPasswordHistory', array(
					'userid' => $userid,
					'password' => $this->user['password'],
					'passworddate' => vB::getRequest()->getTimeNow()
			));
		}
	}

	protected function generateRememberMeToken($passwordtoken, $salt)
	{
		return hash('sha224', $passwordtoken . $salt);
	}

	private function fetchRandomPassword($length)
	{
		$password_characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
		$total_password_characters = strlen($password_characters) - 1;

		$digit = mt_rand(0, $length - 1);

		$newpassword = '';
		for ($i = 0; $i < $length; $i++)
		{
			if ($i == $digit)
			{
				$newpassword .= chr(mt_rand(48, 57));
				continue;
			}

			$newpassword .= $password_characters{mt_rand(0, $total_password_characters)};
		}
		return $newpassword;
	}	
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86469 $
|| #######################################################################
\*=========================================================================*/
