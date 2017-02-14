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
 * vB_Api_Api
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Api extends vB_Api
{
	/**
	 * @var	vB_dB_Assertor Instance of the database assertor
	 * @todo Remove this and have an $assertor instance set in the parent class vB_Api for all APIs
	 */
	protected $dbassertor;

	/**
	 * Constructor
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->dbassertor = vB::getDbAssertor();
	}

	/**
	 * Initializes an API client
	 *
	 * @param  int              $api_c API Client ID
	 * @param  array            $apiclientdata 'clientname', 'clientversion', 'platformname', 'platformversion', 'uniqueid'
	 *
	 * @throws vB_Exception_Api Throws 'apiclientinfomissing' if any of clientname, clientversion, platformname, platformversion, or uniqueid are missing.
	 *
	 * @return array            Api information, format:
	 *                          array(
	 *                              apiversion => string
	 *                              apiaccesstoken => string
	 *                              bbtitle => string
	 *                              bburl => string
	 *                              bbactive => int
	 *                              bbclosedreason => string (only set if bbactive = 0)
	 *                              forumhome => string
	 *                              vbulletinversion => string
	 *                              contenttypes => array(
	 *                                  content type class => content type id
	 *                                  [...]
	 *                              )
	 *                              features => array(
	 *                                  blogenabled => 1
	 *                                  cmsenabled => 0
	 *                                  pmsenabled => int
	 *                                  searchesenabled => tin
	 *                                  groupsenabled => 1
	 *                                  albumsenabled => 0
	 *                                  multitypesearch => 1
	 *                                  visitor_messagingenabled => 1
	 *                                  taggingenabled => int
	 *                                  visitor_trackingenabled => 0
	 *                                  paidsubs => int
	 *                                  friendsenabled => 0
	 *                                  activitystream => 1
	 *                              )
	 *                              permissions => empty array
	 *                              show => array(
	 *                                  registerbutton => 1
	 *                              )
	 *                              apiclientid => int
	 *                              secret => string (only if API Client ID was specified in the call)
	 *                          )
	 */
	public function init($clientname, $clientversion, $platformname, $platformversion, $uniqueid, $api_c = 0)
	{
		$clientname = strip_tags($clientname);
		$clientversion = strip_tags($clientversion);
		$platformname = strip_tags($platformname);
		$platformversion = strip_tags($platformversion);
		$uniqueid = strip_tags($uniqueid);
		$api_c = intval($api_c);

		$oldclientid = $api_c;
		if (!$api_c)
		{
			// The client doesn't have an ID yet. So we need to generate a new one.

			// All params are required.
			// uniqueid is the best to be a permanent unique id such as hardware ID (CPU ID,
			// Harddisk ID or Mobile IMIE). Some client can not get a such a uniqueid,
			// so it needs to generate an unique ID and save it in its local storage. If it
			// requires the client ID and Secret again, pass the same unique ID.
			if (!$clientname OR !$clientversion OR !$platformname OR !$platformversion OR !$uniqueid)
			{
				throw new vB_Exception_Api('apiclientinfomissing');
			}

			// Gererate clienthash.
			$clienthash = md5($clientname . $platformname . $uniqueid);

			// Generate a new secret
			$secret = fetch_random_password(32);

			// If the same clienthash exists, return secret back to the client.
			$client = $this->dbassertor->getRow('apiclient', array('clienthash' => $clienthash));

			$api_c = $client['apiclientid'];

			if ($api_c)
			{
				// Update secret
				// Also remove userid so it will logout previous loggedin and remembered user. (VBM-553)
				$this->dbassertor->update('apiclient',
					array(
						'secret' => $secret,
						'apiaccesstoken' => vB::getCurrentSession()->get('apiaccesstoken'),
						'lastactivity' => vB::getRequest()->getTimeNow(),
						'clientversion' => $clientversion,
						'platformversion' => $platformversion,
						'userid' => 0
					),
					array(
						'apiclientid' => $api_c,
					)
				);
			}
			else
			{
				$api_c = $this->dbassertor->insert('apiclient', array(
					'secret' => $secret,
					'clienthash' => $clienthash,
					'clientname' => $clientname,
					'clientversion' => $clientversion,
					'platformname' => $platformname,
					'platformversion' => $platformversion,
					'initialipaddress' => vB::getRequest()->getAltIp(),
					'apiaccesstoken' => vB::getCurrentSession()->get('apiaccesstoken'),
					'dateline' => vB::getRequest()->getTimeNow(),
					'lastactivity' => vB::getRequest()->getTimeNow(),
				));

				if (is_array($api_c))
				{
					$api_c = array_pop($api_c);
				}
				$api_c = (int) $api_c;
			}

			// Set session client ID
			vB::getCurrentSession()->set('apiclientid', $api_c);
		}
		else
		{
			// api_c and api_sig are verified in init.php so we don't need to verify here again.
			$api_c = intval($api_c);

			// Update lastactivity
			$this->dbassertor->update('apiclient',
				array(
					'lastactivity' => vB::getRequest()->getTimeNow(),
				),
				array(
					'apiclientid' => $api_c,
				)
			);
		}

		$contenttypescache = vB_Types::instance()->getContentTypes();

		$contenttypes = array();
		foreach ($contenttypescache as $contenttype)
		{
			$contenttypes[$contenttype['class']] = $contenttype['id'];
		}

		$products = vB::getDatastore()->getValue('products');
		$vboptions = vB::getDatastore()->getValue('options');
		$userinfo = vB::getCurrentSession()->fetch_userinfo();

		// Check the status of CMS and Blog
		$blogenabled = true;
		$cmsenabled = false;
		try
		{
		 	vB_Api::instanceInternal('paidsubscription')->checkStatus();
		 	$paidsubs = 1;
		}
		catch (Exception $e)
		{
		 	$paidsubs = 0;
		}

		$forumHome = vB_Library::instance('content_channel')->getForumHomeChannel();
		$forumhomeUrl = vB5_Route::buildUrl($forumHome['routeid'] . '|fullurl');

		$data = array(
			'apiversion' => VB_API_VERSION,
			'apiaccesstoken' => vB::getCurrentSession()->get('apiaccesstoken'),
			'bbtitle' => $vboptions['bbtitle'],
			'bburl' => $vboptions['bburl'],
			'bbactive' => $vboptions['bbactive'],
			'forumhome' => $forumhomeUrl,
			'vbulletinversion' => $vboptions['templateversion'],
			'contenttypes' => $contenttypes,
			'features' => array(
				'blogenabled' => 1,
				'cmsenabled' => 0,
				'pmsenabled' => $vboptions['enablepms'] ? 1 : 0,
				'searchesenabled' => $vboptions['enablesearches'] ? 1 : 0,
				'groupsenabled' => 1,
				'albumsenabled' => 0,
				'multitypesearch' => 1,
				'visitor_messagingenabled' => 1,
				'taggingenabled' => $vboptions['threadtagging'] ? 1 : 0,
				'visitor_trackingenabled' => 0,
				'paidsubs' => $paidsubs,
				'friendsenabled' => 0,
				'activitystream' => 1,
			),
			'permissions' => array(),
			'show' => array(
				'registerbutton' => 1,
			),

		);

		if (!$vboptions['bbactive'])
		{
			$data['bbclosedreason'] = $vboptions['bbclosedreason'];
		}

		$data['apiclientid'] = $api_c;
		if (!$oldclientid)
		{
			$data['secret'] = $secret;
		}

		return $data;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83538 $
|| #######################################################################
\*=========================================================================*/
