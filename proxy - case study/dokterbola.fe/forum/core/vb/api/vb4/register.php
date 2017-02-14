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
 * vB_Api_Vb4_register
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_register extends vB_Api
{
	public function addmember(
		$agree,
		$username,
		$email,
		$emailconfirm,
		$fbname = null,
		$fbuserid = null,
		$month = null,
		$day = null,
		$year = null,
		$password = null,
		$password_md5 = null,
		$passwordconfirm = null,
		$passwordconfirm_md5 = null,
		$userfield = null)
	{
		$cleaner = vB::getCleaner();
		$agree = $cleaner->clean($agree, vB_Cleaner::TYPE_BOOL);
		$month = $cleaner->clean($month, vB_Cleaner::TYPE_UINT);
		$day = $cleaner->clean($day, vB_Cleaner::TYPE_UINT);
		$fbuserid = $cleaner->clean($fbuserid, vB_Cleaner::TYPE_UINT);
		$fbname = $cleaner->clean($fbname, vB_Cleaner::TYPE_STR);
		$year = $cleaner->clean($year, vB_Cleaner::TYPE_UINT);
		$username = $cleaner->clean($username, vB_Cleaner::TYPE_STR);
		$email = $cleaner->clean($email, vB_Cleaner::TYPE_STR);
		$emailconfirm = $cleaner->clean($emailconfirm, vB_Cleaner::TYPE_STR);
		$password = $cleaner->clean($password, vB_Cleaner::TYPE_STR);
		$password_md5 = $cleaner->clean($password_md5, vB_Cleaner::TYPE_STR);
		$passwordconfirm_md5 = $cleaner->clean($passwordconfirm_md5, vB_Cleaner::TYPE_STR);
		$passwordconfirm = $cleaner->clean($passwordconfirm, vB_Cleaner::TYPE_STR);
		$userfield = $cleaner->clean($userfield, vB_Cleaner::TYPE_ARRAY);

		if (empty($agree))
		{
			return array('response' => array('errormessage' => array('register_not_agreed')));
		}

		if (empty($username) ||
			empty($email) ||
			empty($emailconfirm) ||
			empty($agree))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$check = vB_Api::instance('user')->checkUsername($username);
		if (empty($check) || isset($check['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($check);
		}

		if ((empty($password) ||
			empty($passwordconfirm)) &&
			(empty($password_md5) ||
			empty($passwordconfirm_md5)))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		if (!empty($password) && $password != $passwordconfirm)
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}
		else
		{
			$password = $password;
		}

		if (!empty($password_md5) && $password_md5 != $passwordconfirm_md5)
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}
		else
		{
			$password = $password_md5;
		}

		if ($email != $emailconfirm)
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$userdata = array('username' => $username, 'email' => $email);
		if ($year > 0 AND $month > 0 AND $day > 0)
		{
			$userdata['birthday'] = date('m-d-Y', mktime(0, 0, 0, $month, $day, $year));
		}

		if (!empty($fbname) AND !empty($fbuserid))
		{
			$userdata['fbuserid'] = $fbuserid;
			$userdata['fbname'] = $fbname;
			$userdata['fbjoindate'] = time();
		}

		$hv = vB_Library::instance('vb4_functions')->getHVToken();
		$result = vB_Api::instance('user')->save(0, $password, $userdata, array(), array(), $userfield, array(), $hv);

		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		return array(
			'response' => array('errormessage' => array('registration_complete')),
			'session' => array('sessionhash' => $result['dbsessionhash']),
		);
	}

	public function call()
	{
		$result = vB_Api::instance('user')->fetchProfileFieldsForRegistration(array());
		if ($result === null || isset($result['errors']))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$custom_fields_profile = array();
		foreach ($result['profile'] as $field)
		{
			$custom_fields_profile[] = $this->parseCustomField($field);
		}

		$custom_fields_other = array();
		foreach ($result['other'] as $field)
		{
			$custom_fields_other[] = $this->parseCustomField($field);
		}

		$custom_fields_option = array();
		foreach ($result['option'] as $field)
		{
			$custom_fields_option[] = $this->parseCustomField($field);
		}

		$result = vB_Api::instance('phrase')->fetch(array('site_terms_and_rules', 'coppa_rules_description'));
		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}
		$forumRules = $result['site_terms_and_rules'];
		$coppaRules = $result['coppa_rules_description'];

		$options = vB::getDatastore()->getValue('options');

		$out = array(
			'vboptions' => array(
				'usecoppa' => $options['usecoppa'],
				'webmasteremail' => $options['webmasteremail'],
			),
			'vbphrase' => array(
				'forum_rules_description' => $forumRules,
				'coppa_rules_description' => $coppaRules,
			),
			'response' => array(
				'customfields_other' => $custom_fields_other,
				'customfields_profile' => $custom_fields_profile,
				'customfields_option' => $custom_fields_option,
			),
		);
		return $out;
	}

	private function parseCustomField($data)
	{
		$field = array(
			'custom_field_holder' => array(
				'profilefield' => array(
					'type' => $data['type'],
					'title' => $data['title'],
					'description' => $data['description'],
					'currentvalue' => $data['currentvalue'],
				),
				'profilefieldname' => $data['fieldname'],
			),
		);

		if ($data['type'] == 'select' || $data['type'] == 'select_multiple')
		{
			$selectbits = array();
			foreach ($data['bits'] as $key => $bit)
			{
				$selectbits[] = array(
					'key' => $key,
					'val' => $bit['val'],
					'selected' => '',
				);
			}
			$field['custom_field_holder']['selectbits'] = $selectbits;
		}

		if ($data['type'] == 'radio' || $data['type'] == 'checkbox')
		{
			$radiobits = array();
			foreach ($data['bits'] as $key => $bit)
			{
				$radiobits[] = array(
					'key' => $key,
					'val' => $bit['val'],
					'checked' => '',
				);
			}
			$field['custom_field_holder']['radiobits'] = $radiobits;
		}

		return $field;
	}

	/**
	 * Connect logged in user to facebook account
	 *
	 * @param  [int] 	$link
	 * @param  [int] 	$fbuserid       Facebook userid (unused)
	 * @param  [string] $fbname         Facebook username (unused)
	 * @param  [type] 	$signed_request Facebook response
	 * @return [array]
	 */
	public function fbconnect($link, $fbuserid=null, $fbname=null, $signed_request=null)
	{
		$cleaner = vB::getCleaner();

		// Clean the input params
		$link 			= $cleaner->clean($link, vB_Cleaner::TYPE_UINT);
//		$fbuserid 		= $cleaner->clean($fbuserid, vB_Cleaner::TYPE_UINT);
//		$fbname 		= $cleaner->clean($fbname, vB_Cleaner::TYPE_STR);
		$signed_request = $cleaner->clean($signed_request, vB_Cleaner::TYPE_STR);

		if (!$link)
		{
			return array('response' => array('errormessage' => array('missing_link')));
		}

		if (!$signed_request)
		{
			return array('response' => array('errormessage' => array('missing_signed_request')));
		}

 		$api_facebook = vB_Api::instance('facebook');
		if (!$api_facebook->isFacebookEnabled())
		{
			return array('response' => array('errormessage' => array('facebook_disabled')));
		}


		$check = $this->parse_signed_facebook_request($signed_request);
		if (isset($check['error']))
		{
			// This used to just return 'invalidid', which didn't help anyone.
			return array('response' => array('errormessage' => array('invalid_signed_request')));
		}

		$auth = isset($check['data']['oauth_token']) ? $check['data']['oauth_token'] : null;

		$result = $api_facebook->connectCurrentUser($auth);

		if (empty($result) || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		return array('response' => array('errormessage' => array('redirect_updatethanks')));
	}


	protected function parse_signed_facebook_request($signed_request)
	{
		/*
			Copied virtually verbatim from
				https://developers.facebook.com/docs/games/canvas/login
			Also see
				https://developers.facebook.com/docs/reference/login/signed-request
		 */
		list($encoded_sig, $payload) = explode('.', $signed_request, 2);
		// decode the data
		$sig = $this->base64_url_decode($encoded_sig);
		$data = json_decode($this->base64_url_decode($payload), true);

		// confirm the signature
		$options = vB::getDatastore()->getValue('options');
		$secret = $options['facebooksecret'];
		$expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
		if ($sig !== $expected_sig) {
			return array('error' => 'Bad Signed JSON signature!');
		}

		return array('data' => $data);
	}

	protected function base64_url_decode($input)
	{
		/*
			Copied verbatim from
				https://developers.facebook.com/docs/games/canvas/login
		 */
		return base64_decode(strtr($input, '-_', '+/'));
	}

	/**
	 * Disconnect fb account from the logged in user
	 *
	 * @param  [int] $confirm (unused)
	 * @return [array]
	 */
	public function fbdisconnect($confirm)
	{
		$userid = vB::getCurrentSession()->get('userid');
		$api_facebook = vB_Api::instance('facebook');

		if (!empty($userid))
		{
			//this no longer requires facebook to be enabled, so don't check that
			$result = $api_facebook->disconnectUser($userid);
			if (empty($result) || isset($result['errors']))
			{
				//this is the closest error we have in the API
				return array('response' => array('errormessage' => array('invalidid')));
			}

			return array('response' => array('errormessage' => array('header_redirect')));
		}

		return array('response' => array('errormessage' => array('nopermission_loggedout')));
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87992 $
|| #######################################################################
\*=========================================================================*/
