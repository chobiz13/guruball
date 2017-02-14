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

class vB5_Frontend_Controller_Registration extends vB5_Frontend_Controller
{
	/** Responds to a request to create a new user.
	*
	**/
	public function actionRegistration()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		//We need at least a username, email, and password.

		if (empty($_REQUEST['username']) OR empty($_REQUEST['password']) OR empty($_REQUEST['email']))
		{
			$this->sendAsJson(array('error' => 'insufficient data'));
			return;
		}

		$username = trim($_REQUEST['username']);
		$password = trim($_REQUEST['password']);

		$postdata = array('username' => $username, 'email' => $_REQUEST['email']);

		if (isset($_REQUEST['month']) AND isset($_REQUEST['day']) AND !empty($_REQUEST['year']))
		{
			$postdata['birthday'] = $_REQUEST['year'] . '-' . str_pad($_REQUEST['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($_REQUEST['day'], 2, '0', STR_PAD_LEFT);
		}

		if (!empty($_REQUEST['guardian']))
		{
			$postdata['parentemail'] = $_REQUEST['guardian'];
		}

		$vboptions = vB5_Template_Options::instance()->getOptions();
		$vboptions = $vboptions['options'];

		// Coppa cookie check
		$coppaage = vB5_Cookie::get('coppaage', vB5_Cookie::TYPE_STRING);
		if ($vboptions['usecoppa'] AND $vboptions['checkcoppa'])
		{
			if ($coppaage)
			{
				$dob = explode('-', $coppaage);
				$month = $dob[0];
				$day = $dob[1];
				$year = $dob[2];
				$postdata['birthday'] = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
			}
			else
			{
				vB5_Cookie::set('coppaage', $_REQUEST['month'] . '-' . $_REQUEST['day'] . '-' . $_REQUEST['year'], 365, 0);
			}
		}

		// Fill in ReCaptcha data
		$recaptchaData = array();
		if (!empty($_REQUEST['recaptcha_challenge_field']))
		{
			$recaptchaData['recaptcha_challenge_field'] = $_REQUEST['recaptcha_challenge_field'];
		}
		if (!empty($_REQUEST['recaptcha_response_field']))
		{
			$recaptchaData['recaptcha_response_field'] = $_REQUEST['recaptcha_response_field'];
		}
		if (!empty($recaptchaData))
		{
			$_REQUEST['humanverify'] = $recaptchaData + (isset($_REQUEST['humanverify']) ? (array)$_REQUEST['humanverify'] : array());
		}

		$api = Api_InterfaceAbstract::instance();
		$data = array(
			'userid'   => 0,
			'password' => $password,
			'user'     => $postdata,
			array(),
			array(),
			'userfield' => (!empty($_REQUEST['userfield']) ? $_REQUEST['userfield'] : false),
			array(),
			isset($_REQUEST['humanverify']) ? $_REQUEST['humanverify'] : '',
			array('registration' => true),
		);


		// add facebook data
		if ($api->callApi('facebook', 'isFacebookEnabled') && $api->callApi('facebook', 'userIsLoggedIn'))
		{
			$fbUserInfo = $api->callApi('facebook', 'getFbUserInfo');

			$data['user']['fbuserid'] = $fbUserInfo['id'];
			$data['user']['fbname'] = $fbUserInfo['name'];
			$data['user']['timezoneoffset'] = $fbUserInfo['timezone'];
			$data['user']['fbjoindate'] = time();

			$fb_profilefield_info = $this->getFacebookProfileinfo($fbUserInfo);

			if(!empty($fb_profilefield_info['birthday']) AND empty($data['user']['birthday']))
			{
				$data['user']['birthday'] = $fb_profilefield_info['birthday'];
			}

			if (empty($data['userfield']))
			{
				$data['userfield'] = array();
			}
			if ($vboptions['fb_userfield_biography'])
			{
				$data['userfield'] += array(
					$vboptions['fb_userfield_biography'] => $fb_profilefield_info['biography'],
				);
			}
			if ($vboptions['fb_userfield_location'])
			{
				$data['userfield'] += array(
					$vboptions['fb_userfield_location'] => $fb_profilefield_info['location'],
				);
			}
			if ($vboptions['fb_userfield_occupation'])
			{
				$data['userfield'] += array(
					$vboptions['fb_userfield_occupation'] => $fb_profilefield_info['occupation'],
				);
			}
		}

		// save data
		$response = $api->callApi('user', 'save', $data);

		if (!empty($response) AND (!is_array($response) OR !isset($response['errors'])))
		{
			// try to login
			$loginInfo = $api->callApi('user', 'login', array($username, $password, '', '', ''));

			if (!isset($loginInfo['errors']) OR empty($loginInfo['errors']))
			{
				// browser session expiration
				vB5_Cookie::set('sessionhash', $loginInfo['sessionhash'], 0, true);
				vB5_Cookie::set('password', $loginInfo['password'], 0);
				vB5_Cookie::set('userid', $loginInfo['userid'], 0);

				$urlPath = '';
				if (!empty($_POST['urlpath']))
				{
					$urlPath = base64_decode(trim($_POST['urlpath']), true);
				}
				if (!$urlPath OR strpos($urlPath, '/auth/') !== false OR strpos($urlPath, '/register') !== false OR !vB5_Template_Runtime::allowRedirectToUrl($urlPath))
				{
					$urlPath = vB5_Template_Options::instance()->get('options.frontendurl');
				}
				$response = array('urlPath' => $urlPath);
			}
			else if (!empty($loginInfo['errors']))
			{
				$response = array(
					'errors' => $loginInfo['errors']
				);
			}

			$userinfo = $api->callApi('user', 'fetchUserInfo');
			if ($api->callApi('user', 'needsCoppa', array($userinfo['birthday'])))
			{
				$response['usecoppa'] = true;
				$response['urlPath'] = vB5_Route::buildUrl('coppa-form|bburl');
			}
			else if ($vboptions['verifyemail'])
			{
				$response['msg'] = 'registeremail';
				$response['msg_params'] = array(
					vB5_String::htmlSpecialCharsUni($postdata['username']),
					$postdata['email'],
					vB5_Template_Options::instance()->get('options.frontendurl')
				);
			}
			else if ($vboptions['moderatenewmembers'])
			{
				$response['msg'] = 'moderateuser';
				$response['msg_params'] = array(
					vB5_String::htmlSpecialCharsUni($postdata['username']),
					vB5_Template_Options::instance()->get('options.frontendurl')
				);
			}
			else
			{
				$frontendurl = vB5_Template_Options::instance()->get('options.frontendurl');
				$routeProfile = $api->callApi('route', 'getUrl', array('route' => 'profile', 'data' => array('userid' => $loginInfo['userid']), array()));
				$routeuserSettings = $api->callApi('route', 'getUrl', array('route' => 'settings', 'data' => array('tab' => 'profile'), array()));
				$routeAccount = $api->callApi('route', 'getUrl', array('route' => 'settings', 'data' => array('tab' => 'account'), array()));
				$response['msg'] = 'registration_complete';
				$response['msg_params'] = array(
					vB5_String::htmlSpecialCharsUni($postdata['username']),
					$frontendurl . $routeProfile,
					$frontendurl . $routeAccount,
					$frontendurl . $routeuserSettings,
					$frontendurl
				);
			}

			// Also provide a CSRF token that the current page can use from this point on.
			if (!empty($userinfo['securitytoken']))
			{
				$response['newtoken'] = $userinfo['securitytoken'];
			}
		}

		$this->sendAsJson(array('response' => $response));
	}

	protected function getFacebookProfileinfo($fb_info)
	{
		//our expected fields vs what facebook returns.  Null means we handle that specially.
		$profilefields = array (
			'biography'          => '',
			'location'           => '',
			'occupation'         => '',
			'birthday'           => '',
		);

		// occupation
		if (isset($fb_info['work']) AND isset($fb_info['work'][0]))
		{
			$history = $fb_info['work'][0];
			if (!empty($history->employer) AND !empty($history->employer->name))
			{
				$occupation[] = $history->employer->name;
			}

			if (!empty($history->position) AND !empty($history->position->name))
			{
					$occupation[] = $history->employer->name;
			}

			if (!empty($history->employer) AND !empty($history->employer->description))
			{
					$occupation[] = $history->employer->description;
			}

			$profilefields['occupation'] = implode(', ', $occupation);
		}

		// location
		if (isset($fb_info['location']))
		{
			if (!empty($fb_info['location']->name))
			{
					$profilefields['location'] = $fb_info['location']->name;
			}
		}

		if (!empty($fb_info['bio']))
		{
			$profilefields['biography'] = $fb_info['bio'];
		}

		if (!empty($fb_info['birthday']))
		{
			//should always be MM/DD/YYYY per the DB docs
			$birthday = explode('/', $fb_info['birthday']);
			if(count($birthday) == 3)
			{
				$profilefields['birthday']['month'] = $birthday[0];
				$profilefields['birthday']['day'] = $birthday[1];
				$profilefields['birthday']['year'] = $birthday[2];
			}
		}

		return $profilefields;
	}

	/**
	 *	Checks whether a user with a specific birthday is COPPA
	 **/
	public function actionIscoppa()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$vboptions = vB5_Template_Options::instance()->getOptions();
		$vboptions = $vboptions['options'];

		// Coppaage cookie
		if ($vboptions['usecoppa'] AND $vboptions['checkcoppa'])
		{
			vB5_Cookie::set('coppaage', $_REQUEST['month'] . '-' . $_REQUEST['day'] . '-' . $_REQUEST['year'], 365, 0);
		}

		//Note that 0 = wide open
		// 1 means COPPA users (under 13) can register but need approval before posting
		// 2 means COPPA users cannot register
		$api = Api_InterfaceAbstract::instance();
		$coppa = $api->callApi('user', 'needsCoppa', array('data' => $_REQUEST));

		$this->sendAsJson(array('needcoppa' => $coppa));
	}

	/**
	 *	Checks whether a user is valid
	 **/
	public function actionCheckUsername()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (empty($_REQUEST['username']))
		{
			return false;
		}

		$api = Api_InterfaceAbstract::instance();

		$result = $api->callApi('user', 'checkUsername', array('candidate' => $_REQUEST['username']));

		$this->sendAsJson($result);
	}

	/**
	 * Activate an user who is in "Users Awaiting Email Confirmation" usergroup
	 */
	public function actionActivateUser()
	{
		// Given to users as a link with query params, so we need to accept GET requests
		// even though technically this does change something server-side

		$get = array(
			'u' => !empty($_GET['u']) ? intval($_GET['u']) : 0, // Userid
			'i' => !empty($_GET['i']) ? trim($_GET['i']) : '', // Activate ID
		);

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi('user', 'activateUser', array('userid' => $get['u'], 'activateid' => $get['i']));

		$phraseController = vB5_Template_Phrase::instance();
		$phraseController->register('registration');

		if (!empty($result['errors']) AND is_array($result['errors']))
		{
			$phraseArgs = is_array($result['errors'][0]) ? $result['errors'][0] : array($result['errors'][0]);
		}
		else
		{
			$phraseArgs = is_array($result) ? $result : array($result);
		}
		$messagevar = call_user_func_array(array($phraseController, 'getPhrase'), $phraseArgs);

		vB5_ApplicationAbstract::showMsgPage($phraseController->getPhrase('registration'), $messagevar);

	}

	/**
	 * Activate an user who is in "Users Awaiting Email Confirmation" usergroup
	 * This action is for Activate form submission
	 */
	public function actionActivateForm()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$post = array(
			'username' => !empty($_POST['username']) ? trim($_POST['username']) : '', // username
			'activateid' => !empty($_POST['activateid']) ? trim($_POST['activateid']) : '', // Activate ID
		);

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi('user', 'activateUserByUsername', array('username' => $post['username'], 'activateid' => $post['activateid']));

		if (empty($result['errors']))
		{
			$response['msg'] = $result;
			if ($response['msg'] == 'registration_complete')
			{
				$userinfo = $api->callApi('user', 'fetchByUsername', array('username' => $post['username']));
				$routeProfile = $api->callApi('route', 'getUrl', array('route' => 'profile', 'data' => array('userid' => $userinfo['userid']), array()));
				$routeuserSettings = $api->callApi('route', 'getUrl', array('route' => 'settings', 'data' => array('tab' => 'profile'), array()));
				$routeAccount = $api->callApi('route', 'getUrl', array('route' => 'settings', 'data' => array('tab' => 'account'), array()));
				$response['msg_params'] = array(
					$post['username'],
					vB5_Template_Options::instance()->get('options.frontendurl') . $routeProfile,
					vB5_Template_Options::instance()->get('options.frontendurl') . $routeAccount,
					vB5_Template_Options::instance()->get('options.frontendurl') . $routeuserSettings,
					vB5_Template_Options::instance()->get('options.frontendurl')
				);
			}
			else
			{
				$response['msg_params'] = array();
			}
		}
		else
		{
			$response = $result;
		}

		$this->sendAsJson(array('response' => $response));
	}

	/**
	 * Send activate email
	 */
	public function actionActivateEmail()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'email' => (isset($_POST['email']) ? trim(strval($_POST['email'])) : ''),
		);

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi('user', 'sendActivateEmail', array('email' => $input['email']));


		if (empty($result['errors']))
		{
			$response['msg'] = 'lostactivatecode';
			$response['msg_params'] = array();
		}
		else
		{
			$response = $result;
		}

		$this->sendAsJson(array('response' => $response));
	}

	// @TODO -- remove this function
	// it appears to not be used anywere, and it's almost identical
	// to killActivation, which is used.
	// When removing this, also remove the deleteActivation function
	// in the user api.
	public function actionDeleteActivation()
	{
		$data = array(
			'u' => !empty($_GET['u']) ? intval($_GET['u']) : 0, // Userid
			'i' => !empty($_GET['i']) ? trim($_GET['i']) : '', // Activate ID
		);

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi('user', 'deleteActivation', array('userid' => $data['u'], 'activateid' => $data['i']));

		$phraseController = vB5_Template_Phrase::instance();
		$phraseController->register('registration');

		if (!empty($result['errors']) AND is_array($result['errors']))
		{
			$phraseArgs = is_array($result['errors'][0]) ? $result['errors'][0] : array($result['errors'][0]);
		}
		else
		{
			$phraseArgs = is_array($result) ? $result : array($result);
		}
		$messagevar = call_user_func_array(array($phraseController, 'getPhrase'), $phraseArgs);
		vB5_ApplicationAbstract::showMsgPage($phraseController->getPhrase('registration'), $messagevar);
	}

	public function actionKillActivation()
	{
		// Given to users as a link with query params, so we need to accept GET requests
		// even though technically this does change something server-side

		$data = array(
			'u' => !empty($_GET['u']) ? intval($_GET['u']) : 0, // Userid
			'i' => !empty($_GET['i']) ? trim($_GET['i']) : '', // Activate ID
		);

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi('user', 'killActivation', array('userid' => $data['u'], 'activateid' => $data['i']));

		$phraseController = vB5_Template_Phrase::instance();
		$phraseController->register('registration');

		if (!empty($result['errors']) AND is_array($result['errors']))
		{
			$phraseArgs = is_array($result['errors'][0]) ? $result['errors'][0] : array($result['errors'][0]);
		}
		else
		{
			$phraseArgs = is_array($result) ? $result : array($result);
		}
		$messagevar = call_user_func_array(array($phraseController, 'getPhrase'), $phraseArgs);

		vB5_ApplicationAbstract::showMsgPage($phraseController->getPhrase('registration'), $messagevar);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88923 $
|| #######################################################################
\*=========================================================================*/
