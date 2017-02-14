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
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @version	$Revision: 88680 $
* @date		$Date: 2016-05-17 08:56:28 -0700 (Tue, 17 May 2016) $
*
* @abstract
*
*/
abstract class vB_PaidSubscriptionMethod
{

	/**
	 * The vBulletin Registry
	 *
	 * @var vB_Registry
	 *
	 */
	var $registry = null;

	/**
	 * Settings for this Subscription Method
	 *
	 * @var array
	 *
	 */
	var $settings = array();

	/**
	 * Does this Subscription Method support recurring Payments?
	 *
	 * @var boolean
	 *
	 */
	var $supports_recurring = false;

	/**
	 * Should we display the feedback from this Subscription Gateway?
	 *
	 * @var	boolean
	 *
	 */
	var $display_feedback = false;

	/**
	 * An array of information regarding the payment
	 *
	 * @var array
	 *
	 */
	var $paymentinfo = array();

	/**
	 * The transaction ID
	 *
	 * @var	mixed
	 *
	 */
	var $transaction_id = '';

	/**
	 * The payment Type
	 *
	 * @var integer
	 *
	 */
	var $type = 0;

	/**
	 * The error String (if any)
	 *
	 * @var	string
	 *
	 */
	var $error = '';

	/**
	 * The error code (if any)
	 *
	 * @var string
	 *
	 */
	var $error_code = '';

	/**
	 * Constructor
	 *
	 * @param	vB_Registry	The vBulletin Registry
	 *
	 */
	function __construct(&$registry)
	{
		if (is_object($registry))
		{
			$this->registry =& $registry;
			if (!is_object($registry->db))
			{
				trigger_error('Database object is not an object', E_USER_ERROR);
			}
		}
		else
		{
			trigger_error('Registry object is not an object', E_USER_ERROR);
		}
	}
	/**
	 * Perform verification of the payment, this is called from the payment gateway
	 *
	 * @return	bool	Whether the payment is valid
	 *
	 */
	abstract function verify_payment();

	/**
	* Generates HTML for the subscription form page
	*
	* @param	string		Hash used to indicate the transaction within vBulletin
	* @param	string		The cost of this payment
	* @param	string		The currency of this payment
	* @param	array		Information regarding the subscription that is being purchased
	* @param	array		Information about the user who is purchasing this subscription
	* @param	array		Array containing specific data about the cost and time for the specific subscription period
	*
	* @return	array		Compiled form information
	*
	*/
	function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo)
	{
		$form = array();
		// Legacy Hook 'paidsub_construct_payment' Removed //
		return $form;
	}
}


/**
 * Class to handle Paid Subscriptions
 *
 * @package	vBulletin
 * @license http://www.vbulletin.com/licence.html
 *
 */
class vB_PaidSubscription
{
	/**
	* The vBulletin registry object
	*
	* @var	vB_Registry
	*/
	var $registry = null;

	/**
	* The HTML currency symbols
	*
	* @var	_CURRENCYSYMBOLS
	*/
	var $_CURRENCYSYMBOLS = array(
		'usd' => 'US$',
		'gbp' => '&pound;',
		'eur' => '&euro;',
		'cad' => 'CA$',
		'aud' => 'AU$',
	);

	/**
	* The extra paypal option bitfields
	*
	* @var	_SUBSCRIPTIONS
	*/
	var $_SUBSCRIPTIONOPTIONS = array(
		'tax'       => 1,
		'shipping1' => 2,
		'shipping2' => 4,
	);

	/**
	* The subscription cache array, indexed by subscriptionid
	*
	* @var	subscriptioncache
	*/
	var $subscriptioncache = array();

	/**
	* Constructor
	*
	* @param	vB_Registry	Reference to registry object
	*/
	function __construct()
	{
		$this->registry = vB::get_registry();
		if (!is_object($this->registry))
		{
			trigger_error("vB_PaidSubscription::Registry object is not an object", E_USER_ERROR);
		}
	}

	/**
	* Adds a unix timestamp and an english date together
	*
	* @param	int		Unix timestamp
	* @param	int		Number of units to add to timestamp
	* @param	string	The units of the number parameter
	*
	* @return	int		Unix timestamp
	*/
	function fetch_proper_expirydate($regdate, $length, $units)
	{
		// conver the string to an integer by adding 0
		$length = $length + 0;
		$regdate = $regdate + 0;
		if (!is_int($regdate) OR !is_int($length) OR !is_string($units))
		{ // its not a valid date
			return false;
		}

		$units_full = array(
			'D' => 'day',
			'W' => 'week',
			'M' => 'month',
			'Y' => 'year'
		);
		// lets get a formatted string that strtotime will understand
		$formatted = date('d F Y H:i', $regdate);

		// if we extend for years, we need to make sure we're not going into 2038 - #23115
		if ($units == 'Y')
		{
			$start_year = date('Y', $regdate);
			if ($start_year + $length >= 2038)
			{
				// too long, return a time for the beginning of 2038
				return mktime(0, 0, 0, 1, 2, 2038);
			}
		}

		// now lets add the appropriate terms
		$time = strtotime("$formatted + $length " . $units_full["$units"]);

		// Protect against possible errors with PHP 5.1.x
		if ($time <= 0)
		{
			trigger_error('strtotime returned an invalid value, upgrade PHP to at least 5.1.2', E_USER_ERROR);
		}

		return $time;
	}

	/**
	* Creates user subscription
	*
	* @param	int		The id of the subscription
	* @param	int		The subid of the subscription, this indicates the length
	* @param	int		The userid the subscription is to be applied to
	* @param	int		The start timestamp of the subscription
	* @param	int		The expiry timestamp of the subscription
	* @param	boolean	Whether to perform permission checks to determin if this user can have this subscription
	*
	*/
	function build_user_subscription($subscriptionid, $subid, $userid, $regdate = 0, $expirydate = 0, $checkperms = true)
	{

		//first three variables are pretty self explanitory
		//the 4thrd is used to decide if the user is subscribing to the subscription for the first time or rejoining

		$vb5_config = vB::getConfig();

		$subscriptionid = intval($subscriptionid);
		$subid = intval($subid);
		$userid = intval($userid);

		$this->cache_user_subscriptions();
		$sub =& $this->subscriptioncache["$subscriptionid"];
		$tmp = unserialize($sub['cost']);
		if (is_array($tmp["$subid"]) AND $subid != -1)
		{
			$sub = array_merge($sub, $tmp["$subid"]);
		}
		unset($tmp);
		$user = vB::getDbAssertor()->getRow('user', array('userid' => $userid));
		$currentsubscription = vB::getDbAssertor()->getRow('vBForum:subscriptionlog', array('userid' => $userid, 'subscriptionid' => $subscriptionid));

		if ($checkperms AND !empty($sub['deniedgroups']) AND !count(array_diff(fetch_membergroupids_array($user), $sub['deniedgroups'])))
		{
				return false;
		}

		// no value passed in for regdate and we have a currently active subscription
		if ($regdate <= 0 AND $currentsubscription['regdate'] AND $currentsubscription['status'])
		{
			$regdate = $currentsubscription['regdate'];
		}
		// no value passed and no active subscription
		else if ($regdate <= 0)
		{
			$regdate = TIMENOW;
		}

		if ($expirydate <= 0 AND $currentsubscription['expirydate'] AND $currentsubscription['status'])
		{
			$expirydate_basis = $currentsubscription['expirydate'];
		}
		else if ($expirydate <= 0 OR $expirydate <= $regdate)
		{
			$expirydate_basis = $regdate;
		}

		if ($expirydate_basis)
		{ // active subscription base the value on our current expirydate
			$expirydate = $this->fetch_proper_expirydate($expirydate_basis, $sub['length'], $sub['units']);
		}

		if ($user['userid'] AND $sub['subscriptionid'])
		{
			$userdm = new vB_Datamanager_User($this->registry, vB_DataManager_Constants::ERRTYPE_SILENT);
			$userdm->set_existing($user);

			//access masks
			$subscription_forums = preg_split('#,#', $sub['forums'], -1, PREG_SPLIT_NO_EMPTY);

			if (is_array($subscription_forums) AND !empty($subscription_forums))
			{
				// double check since we might not have fetched this -- this might not be necessary
				require_once(DIR . '/includes/functions.php');
				$origsize = sizeof($subscription_forums);

				//require_once(DIR . '/includes/functions_databuild.php');
				//cache_forums();
				$forumlist = "0";

				foreach ($subscription_forums AS $key => $forumid)
				{
						//@TODO: Originally there was a check here for forumcache values.  We may want do do some additional checking where
						// at some future time
						$forumlist .= ",$forumid";
						$forumsql[] = array($userid, $forumid, 1);
				}
				vB::getDbAssertor()->delete('access', array('forumid' => $forumlist, 'userid' => $userid));

				if ($origsize != sizeof($subscription_forums))
				{
					vB::getDbAssertor()->update('vBForum:subscription', array('forums' => implode(',', $subscription_forums)), array('subscriptionid' => $subscriptionid));
				}

				if (!empty($forumsql))
				{
					/*insert query*/
					vB::getDbAssertor()->insertMultiple('access', array('userid', 'forumid', 'accessmask'), $forumsql);
					$userdm->set_bitfield('options', 'hasaccessmask', true);
				}
			}

			$noalter = explode(',', $vb5_config['SpecialUsers']['undeletableusers']);
			if (empty($noalter[0]) OR !in_array($userid, $noalter))
			{
				//membergroupids and usergroupid
				if (!empty($sub['membergroupids']))
				{
					$membergroupids = array_merge(fetch_membergroupids_array($user, false), array_diff(fetch_membergroupids_array($sub, false), fetch_membergroupids_array($user, false)));
				}
				else
				{
					$membergroupids = fetch_membergroupids_array($user, false);
				}

				if ($sub['nusergroupid'] > 0)
				{
					$userdm->set('usergroupid', $sub['nusergroupid']);
					$userdm->set('displaygroupid', 0);

					if ($user['customtitle'] == 0)
					{
						$usergroup = vB::getDbAssertor()->getRow('usergroup', array('usergroupid' => $sub['nusergroupid']));
						if (!empty($usergroup['usertitle']))
						{
							$userdm->set('usertitle', $usergroup['usertitle']);
						}
					}
				}
				$userdm->set('membergroupids', implode($membergroupids, ','));
			}

			$userdm->save();
			unset($userdm);

			if (!$currentsubscription['subscriptionlogid'])
			{
				/*insert query*/
				vB::getDbAssertor()->insert('vBForum:subscriptionlog', array(
					'subscriptionid' => $subscriptionid,
					'userid' => $userid,
					'pusergroupid' => $user['usergroupid'],
					'status' => 1,
					'regdate' => $regdate,
					'expirydate' => $expirydate,
				));
			}
			else
			{
				$updatedata = array(
					'status' => 1,
					'regdate' => $regdate,
					'expirydate' => $expirydate,
				);

				if (!$currentsubscription['status'])
				{
					$updatedata['pusergroupid'] = $user['usergroupid'];
				}

				vB::getDbAssertor()->update('vBForum:subscriptionlog',
					$updatedata,
					array(
						'userid' => $userid,
						'subscriptionid' => $subscriptionid,
					)
				);
			}

			// Legacy Hook 'paidsub_build' Removed //
		}
	}

	/**
	* Removes user subscription
	*
	* @param	int		The id of the subscription
	* @param	int		The userid the subscription is to be removed from
	* @param int		The id of the sub-subscriptionid
	* @param bool		Update user.adminoptions from subscription.adminoption (keep avatars)
	*
	*/
	function delete_user_subscription($subscriptionid, $userid, $subid = -1, $adminoption = false)
	{
		$subscriptionid = intval($subscriptionid);
		$userid = intval($userid);

		$this->cache_user_subscriptions();
		$sub =& $this->subscriptioncache["$subscriptionid"];

		$user = vB::getDbAssertor()->getRow('fetchUsersSubscriptions', array(
			'userid' => $userid,
			'subscriptionid' => $subscriptionid,
			'adminoption' => $adminoption
		));

		if ($user['userid'] AND $sub['subscriptionid'])
		{
			$this->cache_user_subscriptions();
			$sub =& $this->subscriptioncache["$subscriptionid"];
			$tmp = unserialize($sub['cost']);
			if ($subid != -1 AND is_array($tmp["$subid"]))
			{
				$sub = array_merge($sub, $tmp["$subid"]);
				$units_full = array(
					'D' => 'day',
					'W' => 'week',
					'M' => 'month',
					'Y' => 'year'
				);

				switch ($sub['units'])
				{
					case 'D':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']), date('j', $user['expirydate']) - $sub['length'], date('Y', $user['expirydate']));
						break;
					case 'W':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']), date('j', $user['expirydate']) - ($sub['length'] * 7), date('Y', $user['expirydate']));
						break;
					case 'M':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']) - $sub['length'], date('j', $user['expirydate']), date('Y', $user['expirydate']));
						break;
					case 'Y':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']), date('j', $user['expirydate']), date('Y', $user['expirydate']) - $sub['length']);
						break;
				}

				if ($new_expires > TIMENOW)
				{	// new expiration is still after today so just decremement and return
					vB::getDbAssertor()->update('vBForum:subscriptionlog', array('expirydate' => $new_expires), array('subscriptionid' => $subscriptionid, 'userid' => $userid));
					return;
				}
			}
			unset($tmp);

			$userdm = new vB_Datamanager_User($this->registry, vB_DataManager_Constants::ERRTYPE_SILENT);
			$userdm->set_existing($user);

			if ($adminoption)
			{
				if ($user['hascustomavatar'] AND $sub['adminavatar'])
				{
					$userdm->set_bitfield('adminoptions', 'adminavatar', 1);
				}
			}

			//access masks
			if (!empty($sub['forums']))
			{
				if ($old_sub_masks = @unserialize($sub['forums']) AND is_array($old_sub_masks))
				{
					// old format is serialized array with forumids for keys
					$access_forums = array_keys($old_sub_masks);
				}
				else
				{
					// new format is comma-delimited string
					$access_forums = explode(',', $sub['forums']);
				}

				if ($access_forums)
				{
					vB::getDbAssertor()->delete('access', array('nodeid' => $access_forums, 'userid' => $userid));
				}
			}

			// TODO: Restore the line when Access Masks is implemented
//			$countaccess = vB::getDbAssertor()->getRow('masks', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT, 'userid' => $userid));

			$membergroupids = array_diff(fetch_membergroupids_array($user, false), fetch_membergroupids_array($sub, false));
			$update_userban = false;

			if($sub['nusergroupid'] == $user['usergroupid'] AND $user['usergroupid'] != $user['pusergroupid'])
			{
				// check if there are other active subscriptions that set the same primary usergroup
				$subids = array(0);
				foreach ($this->subscriptioncache AS $subcheck)
				{
					if ($subcheck['nusergroupid'] == $user['usergroupid'] AND $subcheck['subscriptionid'] != $subscriptionid)
					{
						$subids[] = $subcheck['subscriptionid'];
					}
				}
				if (!empty($subids))
				{
					$activesub = vB::getDbAssertor()->getRow('vBForum:subscriptionlog', array('userid' => $userid, 'subscriptionid' => $subids), array('field' => 'expirydate', 'direction' => vB_dB_Query::SORT_DESC));
				}
				if ($activesub)
				{
					// there is at least one active subscription with the same primary usergroup, so alter its resetgroup
					vB::getDbAssertor()->update('vBForum:subscriptionlog', array('pusergroupid' => $user['pusergroupid']), array('subscriptionlogid' => $activesub['subscriptionlogid']));
					// don't touch usertitle/displaygroup
					$user['pusergroupid'] = $user['usergroupid'];
					$sub['nusergroupid'] = 0;
				}
				else
				{
					$userdm->set('usergroupid', $user['pusergroupid']);
				}
			}
			else if ($user['isbanned'] AND $user['busergroupid'] == $sub['nusergroupid'])
			{
				$update_userban = true;
				$userbansql['usergroupid'] = $user['pusergroupid'];
			}
			$groups = iif(!empty($sub['membergroupids']), $sub['membergroupids'] . ',') . $sub['nusergroupid'];

			if (in_array ($user['displaygroupid'], explode(',', $groups)))
			{ // they're displaying as one of the usergroups in the subscription
				$user['displaygroupid'] = 0;
			}
			else if ($user['isbanned'] AND in_array ($user['bandisplaygroupid'], explode(',', $groups)))
			{
				$update_userban = true;
				$userbansql['displaygroupid'] = 0;
			}

			// do their old groups still allow custom titles?
			$reset_title = false;
			if ($user['customtitle'] == 2)
			{
				$groups = empty($membergroupids) ? array() : $membergroupids;
				$groups[] = $user['pusergroupid'];
				$bf_ugp_genericpermissions = vB::get_datastore()->get_value('bf_ugp_genericpermissions');
				$usergroup = vB::getDbAssertor()->getRow('usergroup',
						array(vB_dB_Query::CONDITIONS_KEY=> array(
								array('field'=> 'usergroupid', 'value' => $groups, vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_EQ),
								array('field'=> 'genericpermissions', 'value' => $bf_ugp_genericpermissions['canusecustomtitle'], vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_AND)
						))
				);

				if (empty($usergroup['usergroupid']))
				{
					// no custom group any more lets set it back to the default
					$reset_title = true;
				}
			}

			if (($sub['nusergroupid'] > 0 AND $user['customtitle'] == 0) OR $reset_title)
			{ // they need a default title
				$usergroup = vB::getDbAssertor()->getRow('usergroup',array('usergroupid' => $user['pusergroupid']));
				if (empty($usergroup['usertitle']))
				{ // should be a title based on minposts it seems then
					$usergroup = vB::getDbAssertor()->getRow('usertitle', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
							vB_dB_Query::CONDITIONS_KEY => array(array('field'=> 'minposts', 'value' => $user[posts], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LTE))
					), array('field' => 'minposts', 'direction' => vB_dB_Query::SORT_DESC));
				}

				if ($user['isbanned'])
				{
					$update_userban = true;
					$userbansql['customtitle'] = 0;
					$userbansql['usertitle'] = $usergroup['usertitle'];
				}
				else
				{
					$userdm->set('customtitle', 0);
					$userdm->set('usertitle', $usergroup['usertitle']);
				}
			}

			$userdm->set('membergroupids', implode($membergroupids, ','));
//			$userdm->set_bitfield('options', 'hasaccessmask', ($countaccess['count'] ? true : false));
			$userdm->set('displaygroupid', $user['displaygroupid']);

			$userdm->save();
			unset($userdm);
			vB::getDbAssertor()->update('vBForum:subscriptionlog', array('status' => 0), array('subscriptionid' => $subscriptionid, 'userid' => $userid));

			if ($update_userban)
			{
				vB::getDbAssertor()->update('userban', $userbansql, array('subscriptionid' => $subscriptionid, 'userid' => $user['userid']));
			}

			$mysubs = vB::getDbAssertor()->assertQuery('vBForum:subscriptionlog', array('status' => 1, 'userid' => $userid));
			foreach ($mysubs as $mysub)
			{
				$this->build_user_subscription($mysub['subscriptionid'], -1, $userid, $mysub['regdate'], $mysub['expirydate']);
			}

			// Legacy Hook 'paidsub_delete' Removed //
		}
	}

	/**
	* Caches the subscriptions from the database into an array
	*/
	function cache_user_subscriptions()
	{
		if (empty($this->subscriptioncache))
		{
			$permissions = vB::getDbAssertor()->assertQuery('vBForum:subscriptionpermission');
			$permcache = array();
			foreach ($permissions as $perm)
			{
				$permcache["$perm[subscriptionid]"]["$perm[usergroupid]"] = $perm['usergroupid'];
			}

			$subscriptions = vB::getDbAssertor()->assertQuery('vBForum:subscription', array(), array('field' => 'displayorder', 'direction' => vB_dB_Query::SORT_ASC));
			//$subscriptions = $this->registry->db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "subscription ORDER BY displayorder");
			foreach ($subscriptions as $subscription)
			{
				$subscription = array_merge($subscription, convert_bits_to_array($subscription['adminoptions'], $this->registry->bf_misc_adminoptions));
				if (!empty($permcache["$subscription[subscriptionid]"]))
				{
					$subscription['deniedgroups'] = 	$permcache["$subscription[subscriptionid]"];
				}
				$this->subscriptioncache["$subscription[subscriptionid]"] = $subscription;
			}
			unset($permcache);
		}
	}

	/**
	* Constructs the payment form
	*
	* @param	string	A 32 character hash corresponding to the entry in the paymentinfo table
	* @param	array	Array containing the API information for the form to be constructed for
	* @param	array	Array containing specific data about the cost and time for the specific subscription period
	* @param	string	The currency of the cost
	* @param	array	Array containing the entry from the subscription table
	* @param	array	Array containing the userinfo of the user purchasing the subscription
	*
	* @return	array|bool	The array containing the form data or false on error
	*/
	function construct_payment($hash, $methodinfo, $timeinfo, $currency, $subinfo, $userinfo)
	{
		if (file_exists(DIR . '/includes/paymentapi/class_' . $methodinfo['classname'] . '.php'))
		{
			require_once(DIR . '/includes/paymentapi/class_' . $methodinfo['classname'] . '.php');
			$api_class = 'vB_PaidSubscriptionMethod_' . $methodinfo['classname'];
			$obj = new $api_class($this->registry);
			if (!empty($methodinfo['settings']))
			{ // need to convert this from a serialized array with types to a single value
				$obj->settings = $this->construct_payment_settings($methodinfo['settings']);
			}
			return $obj->generate_form_html($hash, $timeinfo['cost']["$currency"], $currency, $subinfo, $userinfo, $timeinfo);
		}
		// maybe throw an error about the lack of a class?
		return false;
	}

	/**
	* Prepares the API settings array
	*
	* @param	string	Serialized string
	*
	* @return	array	Array containing the settings after being converted to the correct index format
	*/
	function construct_payment_settings($serialized_settings)
	{
		$methodsettings = unserialize($serialized_settings);
		$settings = array();
		// could probably do with finding a nicer solution to the following
		$settings['_SUBSCRIPTIONOPTIONS'] =& $this->_SUBSCRIPTIONOPTIONS;
		if (is_array($methodsettings))
		{
			foreach ($methodsettings AS $key => $info)
			{
				$settings["$key"] = $info['value'];
			}
		}
		return $settings;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88680 $
|| #######################################################################
\*=========================================================================*/
