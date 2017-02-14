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
 * vB_Api_Options
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Options extends vB_Api
{
	protected $disableWhiteList = array('fetch', 'fetchValues');
	protected $library;


	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('options');
	}

	public function checkApiState($method)
	{
		if (in_array($method, $this->disableFalseReturnOnly))
		{
			return false;
		}
		else if (!in_array($method, $this->disableWhiteList))
		{
			// we need to fetch options even without a session to verify
			parent::checkApiState($method);
		}

		return true;
	}

	/**
	 * This function returns the options data from the specified option groups,
	 * in a multi dimensional array having the group name as key and the options
	 * as values.
	 *
	 * This function is misnamed and/or placed in the wrong API class. It acually
	 * returns any of the DATASTORE items, ONE of which is the vBulletin Options array.
	 *
	 * @param mixed $options The array of the DATASTORE items that you want to return.
	 *                       If empty, it will return a default set of datastore entries.
	 * @return array
	 */
	public function fetch($options = null)
	{
		if (!isset($options) OR empty($options))
		{
			$options = array(
				'options',
				'bitfields',
				'attachmentcache',
				'usergroupcache',
				'stylecache',
				'languagecache',
				'products',
				'cron',
				'profilefield',
				'loadcache',
				'miscoptions',
				'noticecache',
			);
		}
		else if (!is_array($options))
		{
			$options = array($options);
		}

		foreach ($options AS $key => $option)
		{
			// if they requested the "options" group, we want to pull the
			// "publicoptions" from the datastore instead
			if ($option == 'options')
			{
				$options[$key] = 'publicoptions';
				break;
			}
		}

		$datastore = vB::getDatastore();
		$datastore->pre_load($options);

		$response = array();
		foreach($options AS $option)
		{
			// return the "publicoptions" datastore item as "options"
			$responseKey = ($option == 'publicoptions' ? 'options' : $option);
			$response[$responseKey] = $datastore->getValue($option);
		}

		return $response;
	}

	/**
	 * Returns the requested vBulletin options as specified by the $options parameter.
	 * Only returns public options. If a private option is requested, the returned
	 * value for it will be null.
	 *
	 * @param  string|array Specify one option (as a string), or multiple options (as an array)
	 * @return array        Array of $option name => $value
	 */
	public static function fetchStatic($options = null)
	{
		if (!isset($options) OR empty($options))
		{
			return null;
		}
		else if (!is_array($options))
		{
			$options = array($options);
		}

		$dsOptions =  vB::getDatastore()->getValue('publicoptions');
		$response = array();
		foreach($options AS $option)
		{
			if (isset($dsOptions[$option]))
			{
				$response[$option] = $dsOptions[$option];
			}
			else
			{
				$response[$option] = null;
			}
		}

		return $response;
	}

	/**
	 * This function gets the settings for given product or vbulletin if not specified
	 * @param string $product
	 * @return array
	 */
	public function getSettingsXML($product = 'vbulletin')
	{
		$this->checkHasAdminPermission('canadminsettings');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		$response = array();

		//Evaluate if product is valid
		if (array_key_exists($product, vB::getDatastore()->getValue('products')))
		{
			$settings = get_settings_export_xml($product);
			if (!$settings)
			{
				throw new vB_Exception_Api('settings_not_found');
			}
			$response['settings'] = $settings;
		}
		else
		{
			throw new vB_Exception_Api('invalid_product_specified');
		}
		return $response;
	}

	/**
	 * This function gets a product or set vbulletin as default and prints
	 * the XML file for it's options..
	 * @param boolean $ignore_blacklist -- ignore the settings with blacklist =1
	 * @param string $product
	 * @return array response
	 */
	public function getGroupSettingsXML($ignore_blacklist, $product = 'vbulletin')
	{
		$this->checkHasAdminPermission('canadminsettingsall');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		$response = array();
		//Evaluate if product is valid
		if (array_key_exists($product, vB::getDatastore()->getValue('products')))
		{
			$xml = new vB_XML_Builder();
			$xml->add_group('settings', array('product' => $product));

			$conditions = array('product' => $product);
			if($product == 'vbulletin')
			{
				$conditions['product'] = array('vbulletin', '');
			}

			if($ignore_blacklist)
			{
				$conditions['blacklist'] = 0;
			}

			$sets = vB::getDbAssertor()->select('setting', $conditions, array('field' => array('displayorder', 'varname')));

			if ($sets AND $sets->valid())
			{
				foreach ($sets AS $set)
				{
					$arr = array('varname' => $set['varname']);
					$xml->add_group('setting', $arr);

					if ($set['value'] != '')
					{
						$xml->add_tag('value', $set['value']);
					}
					$xml->close_group();
				}
			}

			$xml->close_group();
			$response['settings'] = $xml->output();
			$xml = null;
		}
		else
		{
			throw new vB_Exception_Api('invalid_product_specified');
		}
		return $response;
	}

	/**
	 * This function gets the settings for given product or vbulletin if not specified
	 * @param string $settingsFile url
	 * @param string $serverFile url
	 * @param string $restore
	 * @param boolean $blacklist
	 * @return array
	 */
	public function importSettingsXML($settingsFile, $serverFile, $restore, $blacklist)
	{
		$this->checkHasAdminPermission('canadminsettings');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		$response = array();
		$xml = null;

		// got an uploaded file?
		// do not use file_exists here, under IIS it will return false in some cases
		if ($settingsFile)
		{
			if (is_uploaded_file($settingsFile['tmp_name']))
			{
				$xml = file_read($settingsFile['tmp_name']);
			}
		}
		// no uploaded file - got a local file?
		else if ($serverFile)
		{
			if (file_exists($serverFile))
			{
				$xml = file_read($serverFile);
			}
		}
		// no uploaded file and no local file - ERROR
		else
		{
			throw new vB_Exception_Api('no_file_uploaded_and_no_local_file_found_gerror');
		}

		if ($xml)
		{
			if ($restore)
			{
				xml_restore_settings($xml, $blacklist);
			}
			else
			{
				xml_import_settings($xml);
			}
		}

		$response['import'] = true;
		return $response;
	}

	/**
	 * Fetch option values
	 *
	 * @param array $options An array of option names to be fetched
	 *
	 * @return array Options' values
	 */
	public function fetchValues($options)
	{
		$allOptions = $this->fetch('options');
		return array_intersect_key($allOptions['options'], array_flip($options));
	}

	/**
	 * This function inserts a Settings value
	 * @param array $setting ( varname, defaultvalue, product, volatile, title, description, username )
	 * @return array $response
	 */
	public function insertSetting($setting)
	{
		$this->checkHasAdminPermission('canadminsettingsall');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		require_once(DIR . '/includes/adminfunctions.php');
		$response = array();

		if (vB::getDbAssertor()->getRow('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'varname' => $setting['varname'])
		))
		{
			throw new vB_Exception_Api('there_is_already_setting_named_x', $setting['varname']);
		}

		if (!preg_match('#^[a-z0-9_]+$#i', $setting['varname'])) // match a-z, A-Z, 0-9, _ only
		{
			throw new vB_Exception_Api('invalid_phrase_varname');
		}
		// insert setting place-holder
		$insertSetting = vB::getDbAssertor()->assertQuery('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'varname' => $setting['varname'],
				'grouptitle' => $setting['grouptitle'],
				'defaultvalue' => $setting['defaultvalue'],
				'optioncode' => $setting['optioncode'],
				'displayorder' => $setting['displayorder'],
				'volatile' => $setting['volatile'],
				'datatype' => $setting['datatype'],
				'product' => $setting['product'],
				'validationcode' => $setting['validationcode'],
				'blacklist' => $setting['blacklist'],
				'ispublic' => $setting['ispublic'],
				'adminperm' => $setting['adminperm'],
		)
		);
		if ($insertSetting['errors'])
		{
			$response['errors'] = $insertSetting['errors'];
		}

		$full_product_info = fetch_product_list(true);
		$product_version = $full_product_info[$setting['product']]['version'];

		// insert associated phrases
		// TODO: User phrase API to insert phrases
		$languageid = iif($setting['volatile'], -1, 0);

		$insertPhrase = vB::getDbAssertor()->assertQuery('vBForum:phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'languageid' => $languageid,
				'fieldname' => 'vbsettings',
				'varname' => "setting_" . $setting['varname'] . "_title",
				'text' => $setting['title'],
				'product' => $setting['product'],
				'username' => $setting['username'],
				'dateline' => TIMENOW,
				'version' => $product_version
			)
		);
		if ($insertPhrase['errors'])
		{
			$response['errors'] = $insertPhrase['errors'];
		}

		$insertPhrase = vB::getDbAssertor()->assertQuery('vBForum:phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'languageid' => $languageid,
				'fieldname' => 'vbsettings',
				'varname' => "setting_" . $setting['varname'] . "_desc",
				'text' => $setting['description'],
				'product' => $setting['product'],
				'username' => $setting['username'],
				'dateline' => TIMENOW,
				'version' => $product_version
			)
		);
		if ($insertPhrase['errors'])
		{
			$response['errors'] = $insertPhrase['errors'];
		}

		vB::getDatastore()->build_options();
		$response['insert'] = true;
		return $response;
	}

	/**
	 * This function updates specified settings
	 * @param array $values
	 *	'varname' => $vbulletin->GPC['varname'],
	 *	'grouptitle' => $vbulletin->GPC['grouptitle'],
	 *	'optioncode' => $vbulletin->GPC['optioncode'],
	 *	'defaultvalue' => $vbulletin->GPC['defaultvalue'],
	 *	'displayorder' => $vbulletin->GPC['displayorder'],
	 *	'volatile' => $vbulletin->GPC['volatile'],
	 *	'datatype' => $vbulletin->GPC['datatype'],
	 *	'validationcode' => $vbulletin->GPC['validationcode'],
	 *	'product' => $vbulletin->GPC['product'],
	 *	'blacklist' => $vbulletin->GPC['blacklist'],
	 *	'title' => $vbulletin->GPC['title'],
	 *	'username' => $vbulletin->userinfo['username'],
	 *	'description' => $vbulletin->GPC['description']
	 * @return array, $response
	 */
	public function updateSetting($values)
	{
		$this->checkHasAdminPermission('canadminsettingsall');
		return $this->library->updateSetting($values);
	}

	/** This updates a value in datastore settings
	*
	*	@param	string	the name of the settings value
	* 	@param	mixed	the settings value
	*	@param	bool	whether to rebuild the datastore. Normally true
	*
	* 	@return	mixed	normally array ('update' => 'true')
	**/
	public function updateValue($varname, $value, $rebuild = true)
	{
		$this->checkHasAdminPermission('canadminsettings');
		return $this->library->updateValue($varname, $value, $rebuild);
	}

	/** This updates the attachpath value in datastore settings
	 *
	 * 	@param	mixed	the settings value
	 *	@param	bool	whether to rebuild the datastore. Normally true
	 *
	 * 	@return	mixed	normally array ('update' => 'true')
	 **/
	public function updateAttachPath($value)
	{
		//This is a separate function because it checks a different permission
		//The user needs both cansetserverconfig and canadminthreads, but not canadminsettings
		$this->checkHasAdminPermission('cansetserverconfig');
		$this->checkHasAdminPermission('canadminthreads');
		return $this->library->updateValue('attachpath', $value);
	}


	/** This updates the attachpath value in datastore settings
	 *
	 * 	@param	mixed	the settings value
	 *	@param	bool	whether to rebuild the datastore. Normally true
	 *
	 * 	@return	mixed	normally array ('update' => 'true')
	 **/
	public function updateAttachSetting($value)
	{
		//This is a separate function because it checks a different permission
		//The user needs both cansetserverconfig and canadminthreads, but not canadminsettings
		$this->checkHasAdminPermission('cansetserverconfig');
		$this->checkHasAdminPermission('canadminthreads');
		return $this->library->updateValue('attachfile', $value);
	}

	/**
	 * This function deletes specified settings
	 * @param string $title
	 * @return array
	 */
	public function killSetting($varname)
	{
		$this->checkHasAdminPermission('canadminsettings');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		$response = array();
		// get some info
		$setting = vB::getDbAssertor()->getRow('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'varname' => $varname)
		);
		if (!$setting)
		{
			$response['error'] = "invalid_setting";
		}
		else
		{
			$response['setting'] = $setting;
		}

		// delete phrases
		vB::getDbAssertor()->assertQuery('vBForum:phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'languageid' => array(-1, 0),
				'fieldname' => 'vbsettings',
				'varname' => array("setting_" . $setting['varname'] . "_title", "setting_" . $setting['varname'] . "_desc")
			)
		);

		// delete setting
		/*$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "setting
			WHERE varname = '" . $vbulletin->db->escape_string($setting['varname']) . "'"
		);*/
		vB::getDbAssertor()->assertQuery('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'varname' => $setting['varname'])
		);
		build_options();

		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');
			autoexport_write_settings_and_language(-1, $setting['product']);
		}

		$response['delete'] = true;
		return $response;
	}

	/**
	 * Delete group of settings
	 * @param string $groupTitle
	 * @return mixed response
	 */
	public function deleteGroupSettings($groupTitle)
	{
		$this->checkHasAdminPermission('canadminsettings');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		$response = array();
		// get some info
		/*$group = $vbulletin->db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "settinggroup
			WHERE grouptitle = '" . $vbulletin->db->escape_string($vbulletin->GPC['title']) . "'"
		);*/
		$group = vB::getDbAssertor()->getRow('settinggroup',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'grouptitle' => $groupTitle)
		);

		//check if the settings have different products from the group.
		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			$products_to_export = array();
			$products_to_export[$group['product']] = 1;

			// query settings from this group
			$settings = array();
			/*$sets = $vbulletin->db->query_read("
				SELECT product
				FROM " . TABLE_PREFIX . "setting
				WHERE grouptitle = '$group[grouptitle]'
			");*/
			$sets = vB::getDbAssertor()->assertQuery('setting',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'grouptitle' => $group['grouptitle'])
			);
			//while ($set = $vbulletin->db->fetch_array($sets))
			if ($sets AND $sets->valid())
			{
				foreach ($sets AS $set)
				{
					$products_to_export[$set['product']] = 1;
				}
			}
		}

		// query settings from this group
		$settings = array();
		/*$sets = $vbulletin->db->query_read("
			SELECT varname
			FROM " . TABLE_PREFIX . "setting
			WHERE grouptitle = '$group[grouptitle]'
		");*/
		$sets = vB::getDbAssertor()->assertQuery('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'grouptitle' => $group['grouptitle'])
		);
		//while ($set = $vbulletin->db->fetch_array($sets))
		if ($sets AND $sets->valid())
		{
			foreach ($sets AS $set)
			{
				$settings[] = $set['varname'];
			}
		}

		// build list of phrases to be deleted
		$phrases = array("settinggroup_$group[grouptitle]");
		foreach($settings AS $varname)
		{
			$phrases[] = 'setting_' . $varname . '_title';
			$phrases[] = 'setting_' . $varname . '_desc';
		}
		// delete phrases
		/*$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE languageid IN (-1,0) AND
				fieldname = 'vbsettings' AND
				varname IN ('" . implode("', '", $phrases) . "')
		");*/
		$deletePhrases = vB::getDbAssertor()->assertQuery('vBForum:phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'languageid' => array(-1,0),
				'fieldname' => 'vbsettings',
				'varname' => $phrases,
			)
		);
		if ($deletePhrases['errors'])
		{
			$response['errors'] = $deletePhrases['errors'];
		}

		// delete settings
		/*$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "setting
			WHERE varname IN ('" . implode("', '", $settings) . "')
		");*/
		if (count($settings) >= 1)
		{
			$deleteSettings = vB::getDbAssertor()->assertQuery('setting',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
					'varname' => $settings,
				)
			);
			if ($deleteSettings['errors'])
			{
				$response['errors'] = $deleteSettings['errors'];
			}
		}

		// delete group
		/*$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "settinggroup
			WHERE grouptitle = '" . $vbulletin->db->escape_string($group['grouptitle']) . "'
		");*/
		$deleteGroupSettings = vB::getDbAssertor()->assertQuery('settinggroup',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'grouptitle' => $group['grouptitle'],
			)
		);
		if ($deleteGroupSettings['errors'])
		{
			$response['errors'] = $deleteGroupSettings['errors'];
		}

		build_options();

		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');
			foreach (array_keys($products_to_export) as $product)
			{
				autoexport_write_settings_and_language(-1, $product);
			}
		}

		$response['delete'] = true;
		return $response;
	}

	/**
	 * Insert group settings
	 * @param array $group ( [grouptitle] , [title] , [product] , [displayorder] , [volatile] )
	 * @return array response
	 */
	public function addGroupSettings($group)
	{
		$this->checkHasAdminPermission('canadminsettings');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		require_once(DIR . '/includes/adminfunctions.php');
		$response = array();
		// insert setting place-holder
		$full_product_info = fetch_product_list(true);
		$product_version = $full_product_info[$group['product']]['version'];


		// insert associated phrases
		$languageid = iif($group['volatile'], -1, 0);

		/*$vbulletin->db->query_write("
			INSERT INTO " . TABLE_PREFIX . "settinggroup
				(grouptitle, product)
			VALUES
				('" . $vbulletin->db->escape_string($vbulletin->GPC['group']['grouptitle']) . "',
				'" . $vbulletin->db->escape_string($vbulletin->GPC['group']['product']) . "')
		");*/
		$insertSetting = vB::getDbAssertor()->assertQuery('settinggroup',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'grouptitle' => $group['grouptitle'],
				'product' => $group['product'],
			)
		);
		if ($insertSetting['errors'])
		{
			$response['errors'] = $insertSetting['errors'];
		}
		/*$vbulletin->db->query_write("
				INSERT INTO " . TABLE_PREFIX . "phrase
					(languageid, fieldname, varname, text, product, username, dateline, version)
				VALUES
					($languageid,
					'vbsettings',
					'settinggroup_" . $vbulletin->db->escape_string($vbulletin->GPC['group']['grouptitle']) . "',
					'" . $vbulletin->db->escape_string($vbulletin->GPC['group']['title']) . "',
					'" . $vbulletin->db->escape_string($vbulletin->GPC['group']['product']) . "',
					'" . $vbulletin->db->escape_string($vbulletin->userinfo['username']) . "',
					" . TIMENOW . ",
					'" . $vbulletin->db->escape_string($product_version) . "')
		");*/
		$insertPhrase = vB::getDbAssertor()->assertQuery('vBForum:phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'languageid' => $group['languageid'],
				'fieldname' => 'vbsettings',
				'varname' => "settinggroup_" . $group['grouptitle'],
				'text' => $group['title'],
				'product' => $group['product'],
				'username' => $group['username'],
				'dateline' => TIMENOW,
				'version' => $group['version']
			)
		);
		if ($insertPhrase['errors'])
		{
			$response['errors'] = $insertPhrase['errors'];
		}

		$response['insert'] = true;
		return $response;
	}

	/**
	 * This function updates group settings.
	 * @param array $group Group values
	 * @return array, $response
	 */
	public function updateGroupSettings($group, $username, $oldproduct = '', $adminperm = false)
	{
		$this->checkHasAdminPermission('canadminsettings');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		require_once(DIR . '/includes/adminfunctions.php');
		$response = array();

		$existing =  vB::getDbAssertor()->assertQuery('settinggroup', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'grouptitle' => $group['grouptitle']));

		if ($existing->valid())
		{
			$existing = $existing->current();

			if (!empty($existing['adminperm']) AND (!vB::getUserContext()->hasAdminPermission($existing['adminperm'])))
			{
				throw new vB_Exception_AccessDenied('no_permission');
			}
		}
		$updates = 	array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'displayorder' => $group['displayorder'],
				'volatile' => $group['volatile'],
				'product' => $group['product'],
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'grouptitle', 'value' => $group['grouptitle'], 'operator' => vB_dB_Query::OPERATOR_EQ)
				));

		if (($adminperms !== false) AND vB::getUserContext()->hasAdminPermission('canadminsettingsall'))
		{
			$adminperm = vB::getCleaner()->clean($adminperm, vB_Cleaner::TYPE_STR);

			if (empty($adminperm))
			{
				$updates['adminperm'] = '';
			}
			else
			{
				$updates['adminperm'] = substr($adminperm, 0, 32);
			}
		}

		$updateSetting = vB::getDbAssertor()->assertQuery('settinggroup',$updates);

		if ($updateSetting['errors'])
		{
			$response['errors'] = $updateSetting['errors'];
		}

		$full_product_info = fetch_product_list(true);
		$product_version = $full_product_info[$group['product']]['version'];

		$updatePhrase = vB::getDbAssertor()->assertQuery('vBForum:phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'text' => $group['title'],
				'product' => $group['product'],
				'username' => $username,
				'dateline' => TIMENOW,
				'version' => $product_version,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'varname', 'value' => "settinggroup_" . $group['grouptitle'], 'operator' => vB_dB_Query::OPERATOR_EQ)
				)
			)
		);
		if ($updatePhrase['errors'])
		{
			$response['errors'] = $updatePhrase['errors'];
		}

		$settingnames = array();
		$phrasenames = array();
		$settings = vB::getDbAssertor()->assertQuery('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'grouptitle' => $group['grouptitle'],
				'product' => $oldproduct
			)
		);

		if ($settings AND $settings->valid())
		{
			foreach ($settings AS $setting)
			{
				$settingnames[] = $setting['varname'];
				$phrasenames[] = 'setting_' . $setting['varname'] . '_desc';
				$phrasenames[] = 'setting_' . $setting['varname'] . '_title';
			}
			$full_product_info = fetch_product_list(true);
			$product_version = $full_product_info[$group['product']]['version'];

			vB::getDbAssertor()->assertQuery('setting',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'product' => $group['product'],
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'varname', 'value' => $settingnames, 'operator' => vB_dB_Query::OPERATOR_EQ)
					)
				)
			);

			vB::getDbAssertor()->assertQuery('vBForum:phrase',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'product' => $group['product'],
					'username' => $username,
					'dateline' => TIMENOW,
					'version' => $product_version,
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'varname', 'value' => $phrasenames, 'operator' => vB_dB_Query::OPERATOR_EQ),
						array('field' => 'fieldname', 'value' => 'vbsettings', 'operator' => vB_dB_Query::OPERATOR_EQ)
					)
				)
			);
		}
		vB::getDatastore()->build_options();

		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');
			autoexport_write_settings_and_language(-1,
				array($oldproduct, $group['product']));
		}
		$response['update'] = true;
		return $response;
	}

	/**
	 * This function changes the search type for settings
	 * @param string $implementation
	 * @param string $options
	 * @return array, response
	 */
	public function changeSearchType($implementation, $options)
	{
		$this->checkHasAdminPermission('canadminsettingsall');

		$response = array();

		if (!array_key_exists($implementation, $options))
		{
			throw new vB_Exception_Api('invalid_search_implementation');
		}

		vB::getDbAssertor()->assertQuery('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'value' => $implementation,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'varname', 'value' => 'searchimplementation', 'operator' => vB_dB_Query::OPERATOR_EQ)
				)
			)
		);
		vB::getDatastore()->build_options();

		$response['change'] = true;
		return $response;
	}

	/**
	 * This function changes the search type for settings
	 * @param string $varname
	 * @param array $setting
	 * @return array, response
	 */
	public function validateSettings($varname, $setting)
	{
		$this->checkHasAdminPermission('canadminsettings');

		require_once(DIR . '/includes/class_xml.php');
		require_once(DIR . '/includes/functions_file.php');
		require_once(DIR . '/includes/adminfunctions_options.php');
		$response = array();

		$varname = convert_urlencoded_unicode($varname);
		$value = convert_urlencoded_unicode($setting["$varname"]);

		require_once(DIR . '/includes/class_xml.php');

		$xml = new vB_XML_Builder_Ajax('text/xml');
		$xml->add_group('setting');
		$xml->add_tag('varname', $varname);

		$setting = vB::getDbAssertor()->getRow('setting',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'varname' => $varname)
		);
		if ($setting)
		{
			$raw_value = $value;

			$value = validate_setting_value($value, $setting['datatype']);

			$valid = exec_setting_validation_code($setting['varname'], $value, $setting['validationcode'], $raw_value);
		}
		else
		{
			$valid = 1;
		}

		$xml->add_tag('valid', $valid);
		$xml->close_group();
		$response['xml'] = $xml;

		$response['validate'] = true;
		return $response;
	}

	function setOptionsDate()
	{
		$options = vB::getDatastore()->getValue('miscoptions');
		$options['optionsdate'] = vB::getRequest()->getTimeNow();
		vB::getDatastore()->build('miscoptions', serialize($options), 1);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88826 $
|| #######################################################################
\*=========================================================================*/
