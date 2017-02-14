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
 * vB_Api_Session
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Session extends vB_Api
{
	/**
	 * Get basic information from a stored session
	 *
	 * @param string    session hash
     *
	 * @return mixed    array of permissions,
	 */
	public static function getInfoFromHash($sessionHash = false)
	{
        if (!empty($sessionHash))
        {
            $session = vB::getDbAssertor()->getRow('session', array('sessionhash' => $sessionHash));
        }

        if (empty($session) OR !empty($session['errors']))
        {
            //guest user
            return array('userid' => 0, 'languageid' =>  vB::getDatastore()->getOption('languageid') );
        }
        //This has userid and language.
        return array('userid' => $session['userid'], 'languageid' => $session['languageid']);
	}

	/**
	 * starts a new lightweight (no shutdown) session
	 *
	 * @param string    session hash
	 *
	 * @return mixed    array of permissions,
	 */
	public static function startSessionLight($sessionHash = false, $cphash = false, $languageid = 0, $checkTimeout = false)
	{
		if (!empty($sessionHash))
		{
			if ($checkTimeout)
			{
				$timenow = vB::getRequest()->getTimeNow();
				$timeout = vB::getDatastore()->getOption('cookietimeout');
				if ($timenow > $timeout)
				{
					$cutoff = ($timenow - $timeout);
				}
				else
				{
					$cutoff = 0;
				}
				$sessionInfo = vB::getDbAssertor()->getRow('session',
					array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						vB_dB_Query::CONDITIONS_KEY => array(
							array(
								'field' => 'sessionhash',
								'value'	=> $sessionHash,
								'operator' => vB_dB_Query::OPERATOR_EQ
							),
							array(
								'field' => 'lastactivity',
								'value' => $cutoff,
								'operator' => vB_dB_Query::OPERATOR_GT
							),
						)
					)
				);
			}
			else
			{
				$sessionInfo = vB::getDbAssertor()->getRow('session', array('sessionhash' => $sessionHash));
			}

			if (!empty($sessionInfo) AND empty($sessionInfo['errors']))
			{
				$session = vB_Session_Web::getSession($sessionInfo['userid'], $sessionHash );

				if (!empty($cphash))
				{
					$session->setCpsessionHash($cphash);
				}
			}

		}

		if (empty($session))
		{
			//constructor is now private
			$session = vB_Session_Web::getSession(0, '');
		}

		$session->set('languageid', $languageid);

		vB::skipShutdown(true);
		vB::setCurrentSession($session);
		return $session;
	}

	/**
	 * starts a new lightweight (no shutdown) guest session and returns the session object.
	 *
	 * @return 	vB_Session 	session data.
	 */
	public function getGuestSession()
	{
		$session = vB_Session_Web::getSession(0, '');
		$languageid = vB::getDatastore()->getOption('languageid');
		$session->set('languageid', $languageid);

		vB::skipShutdown(true);
		vB::setCurrentSession($session);
		return $session;
	}

	public function disableShutdownQueries()
	{
		vB::getDbAssertor()->skipShutdown();
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84898 $
|| #######################################################################
\*=========================================================================*/
