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
 * vB_Api_Style
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Style extends vB_Api
{
	// TODO: some of these methods shouldn't be public. We should move them to vB_Library_Style instead to avoid exposing them in the API.

	protected $disableWhiteList = array('fetchStyles', 'fetchStyleVars');

	protected $library;

	protected $userContext;

	protected $cssFileLocation;

	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('Style');
	}

	/**
	 *	Get the style from the list of preferences -- will check that the
	 *	desired styles exist and are available for the user to
	 *
	 *	@param array $stylePreference -- various styles in the order we should check them
	 */
	public function getValidStyleFromPreference($stylePreference)
	{
		$styleid =  $this->library->getValidStyleFromPreference($stylePreference);
		return $styleid;
	}

	/**
	 *	Get Style Vars
	 *
	 *	@param array $stylePreference -- various styles in the order we should check them
	 */
	public function fetchStyleVars($stylePreference)
	{
		$styleId = $this->library->getValidStyleFromPreference($stylePreference);

		// fetch style from datastore
		$style = $this->library->fetchStyleByID($styleId);

		if (is_array($style) AND isset($style['newstylevars']))
		{
			return unserialize($style['newstylevars']);
		}
		else
		{
			return FALSE;
		}
	}


	/**
	 * Fetch All styles
	 *
	 * @param 	bool 	$withdepthmark If true, style title will be prepended with depth mark
	 * @param 	bool 	$userselectonly If true, this method returns only styles that allows user to select
	 * @param	mixed	array of options: currently only understands "themes"-includes themes
	 *
	 * @return 	array 	All styles' information
	 */
	public function fetchStyles($withdepthmark = false, $userselectonly = false, $nocache = false, $options = array())
	{
		// todo: if we don't need stylevars, set the second flag to false
		$stylecache = $this->library->fetchStyles($nocache, true, $options);
		$defaultStyleId = vB::getDatastore()->getOption('styleid');

		require_once(DIR . '/includes/adminfunctions.php');
		foreach ($stylecache as $k => $v)
		{
			if (
				$userselectonly AND !$v['userselect']
				AND ($v['styleid'] != $defaultStyleId) // always treat the default style as selectable even if it's not userselectable
			)
			{
				unset($stylecache[$k]);
			}

			if (isset($stylecache[$k]) && $withdepthmark)
			{
				$stylecache[$k]['title'] = construct_depth_mark($v['depth'], '-') . ' ' . $v['title'];
			}
		}

		// library->fetchStyles() removed read-protected styles.
		return $stylecache;
	}

	/**
	 * Insert style
	 *
	 * @param string $title Style title
	 * @param integer $parentid New parent style ID for the style.
	 * @param boolean $userselect Whether user is able to choose the style.
	 * @param integer $displayorder Display order.
	 * @param string $guid Theme GUID
	 * @param binary $icon Theme icon
	 * @param binary $previewImage Theme preview image
	 *
	 * @return array array('styleid' => newstyleid)
	 */
	public function insertStyle($title, $parentid, $userselect, $displayorder, $guid = '', $icon = '', $previewImage = '', $styleattributes = vB_Library_Style::ATTR_DEFAULT, $dateline = null)
	{
		if (!vB::getUserContext()->hasAdminPermission('canadminstyles'))
		{
			throw new vB_Exception_Api('no_permission');
		}

		/*
			Keep this code in sync with vB_Xml_Import_Theme::doInsertTheme()
		 */

		if (!$title)
		{
			throw new vB_Exception_Api('please_complete_required_fields');
		}

		if (is_null($dateline))
		{
			$dateline = vB::getRequest()->getTimeNow();
		}

		$result = vB::getDbAssertor()->insert('style', array(
			'title' => $title,
			'parentid' => $parentid,
			'userselect' => intval($userselect),
			'displayorder' => $displayorder,
			'styleattributes' => $styleattributes,
			'dateline' => $dateline,
		));

		if(is_array($result))
		{
			$result = array_pop($result);
		}

		require_once(DIR . '/includes/adminfunctions_template.php');
		build_template_parentlists();

		// add theme data
		$this->addThemeData($result, $guid, $icon, false, $previewImage, false);

		$this->library->buildStyle($result, $title, array(
				'docss' => 1,
				'dostylevars' => 1,
				'doreplacements' => 1,
				'doposteditor' => 1
		), false);

		$this->library->buildStyleDatastore();

		return array('styleid' => $result);
	}

	/**
	 * Update style
	 *
	 * @param integer $dostyleid Style ID to be updated.
	 * @param string $title Style title.
	 * @param integer $parentid New parent style ID for the style.
	 * @param boolean $userselect Whether user is able to choose the style.
	 * @param integer $displayorder Display order of the style.
	 * @param boolean $rebuild Whether to rebuild style
	 * @param string $guid Theme GUID
	 * @param binary $icon Theme icon
	 * @param boolean $iconRemove Whether to remove the current icon (if there is one, and we're not uploading a new one)
	 * @param binary $previewImage Theme preview image
	 * @param boolean $previewImageRemove Whether to remove the current preview image (if there is one, and we're not uploading a new one)
	 */
	public function updateStyle($dostyleid, $title, $parentid, $userselect, $displayorder, $rebuild = false, $guid = '', $icon = '', $iconRemove = false, $previewImage = '', $previewImageRemove = false)
	{
		if (!vB::getUserContext()->hasAdminPermission('canadminstyles'))
		{
			throw new vB_Exception_Api('no_permission');
		}

		$vboptions = vB::getDatastore()->getValue('options');

		if ($vboptions['styleid'] == $dostyleid)
		{
			// If a style is default style, we should always allow user to select it.
			$userselect = 1;
		}

		if (!$title)
		{
			throw new vB_Exception_Api('please_complete_required_fields');
		}

		// SANITY CHECK (prevent invalid nesting)
		if ($parentid == $dostyleid)
		{
			throw new vB_Exception_Api('cant_parent_style_to_self');
		}
		$parents = array();
		if ($parentid != -1)
		{
			$ts_info = $this->library->fetchStyleByID($parentid);
			$parents = explode(',', $ts_info['parentlist']);
		}

		foreach($parents AS $childid)
		{
			if ($childid == $dostyleid)
			{
				throw new vB_Exception_Api('cant_parent_x_to_child', array('style'));
			}
		}

		// end Sanity check

		vB::getDbAssertor()->update('style', array(
			'title' => $title,
			'parentid' => $parentid,
			'userselect' => intval($userselect),
			'displayorder' => $displayorder
		), array('styleid' => $dostyleid));

		// add theme data
		$this->addThemeData($dostyleid, $guid, $icon, $iconRemove, $previewImage, $previewImageRemove);

		if ($rebuild)
		{
			require_once(DIR . '/includes/adminfunctions_template.php');
			build_template_parentlists();
			$this->library->buildStyle($dostyleid, $title, array(
				'docss' => 1,
				'dostylevars' => 1,
				'doreplacements' => 1,
				'doposteditor' => 1
			), false);
		}

		$this->library->clearStyleCache();
		$this->library->buildStyleDatastore();

		return true;
	}

	/**
	 * Adds theme data (GUID, icon, preview image) to a style if in debug mode. (used by update & insert)
	 *
	 * @param	string	$guid Theme GUID
	 * @param	binary	$icon Theme icon
	 * @param	boolean	$iconRemove Whether to remove the current icon (if there is one, and we're not uploading a new one)
	 * @param	binary	$previewImage Theme preview image
	 * @param	boolean	$previewImageRemove Whether to remove the current preview image (if there is one, and we're not uploading a new one)
	 */
	protected function addThemeData($dostyleid, $guid, $icon, $iconRemove, $previewImage, $previewImageRemove)
	{
		$config = vB::getConfig();
		if (empty($config['Misc']['debug']))
		{
			// only modify theme information in debug mode.
			return;
		}

		$style = $this->library->fetchStyleByID($dostyleid);
		$themeImporter = new vB_Xml_Import_Theme();
		$updateValues = array();

		// ----- GUID -----
		if (!empty($guid))
		{
			$updateValues['guid'] = $guid;
		}
		else
		{
			$updateValues['guid'] = vB_dB_Query::VALUE_ISNULL;
		}

		// ----- Icon -----
		if (!empty($icon))
		{
			// upload it & get a filedataid
			$filedataid = $themeImporter->uploadThemeImageData($icon);
			if ($filedataid > 0 AND $filedataid != $style['filedataid'])
			{
				$updateValues['filedataid'] = $filedataid;
			}
		}

		if ($style['filedataid'] > 0 AND ($iconRemove OR !empty($updateValues['filedataid'])))
		{
			// remove previous icon (if there was one and they checked 'remove' or if there was one and we just uploaded a new one)
			vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $style['filedataid']));
			// set icon to blank if we don't have a new one
			if (empty($updateValues['filedataid']))
			{
				$updateValues['filedataid'] = 0;
			}
		}

		// ----- Preview Image -----
		if (!empty($previewImage))
		{
			// upload it & get a previewfiledataid
			$previewfiledataid = $themeImporter->uploadThemeImageData($previewImage);
			if ($previewfiledataid > 0 AND $previewfiledataid != $style['previewfiledataid'])
			{
				$updateValues['previewfiledataid'] = $previewfiledataid;
			}
		}

		if ($style['previewfiledataid'] > 0 AND ($previewImageRemove OR !empty($updateValues['previewfiledataid'])))
		{
			// remove previous preview image (if there was one and they checked 'remove' or if there was one and we just uploaded a new one)
			vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $style['previewfiledataid']));
			// set preview image to blank if we don't have a new one
			if (empty($updateValues['previewfiledataid']))
			{
				$updateValues['previewfiledataid'] = 0;
			}
		}

		// save
		if (!empty($updateValues))
		{
			vB::getDbAssertor()->update('style', $updateValues, array('styleid' => $dostyleid));
		}
	}

	/**
	 * Delete style
	 *
	 * @param integer $dostyleid Style ID to be deleted.
	 */
	public function deleteStyle($dostyleid, $skipRebuild = false)
	{
		if (!vB::getUserContext()->hasAdminPermission('canadminstyles'))
		{
			throw new vB_Exception_Api('no_permission');
		}
		$vboptions = vB::getDatastore()->get_value('options');

		if ($dostyleid == $vboptions['styleid'])
		{
			throw new vB_Exception_Api('cant_delete_default_style');
		}

		// look at how many styles are being deleted
		$count = vB::getDbAssertor()->getField('style_count',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED));

		// check that this isn't the last one that we're about to delete
		$last = vB::getDbAssertor()->getField('style_checklast',
			array(
				'styleid' => $dostyleid,
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED
			));

		if ($count == 1 AND $last == 1)
		{
			throw new vB_Exception_Api('cant_delete_last_style');
		}

		$style = $this->library->fetchStyleByID($dostyleid);

		// Delete css file
		if ($vboptions['storecssasfile'] AND $style)
		{
			$style['css'] .= "\n";
			$css = substr($style['css'], 0, strpos($style['css'], "\n"));

			// attempt to delete the old css file if it exists
			require_once(DIR . '/includes/adminfunctions_template.php');
			delete_css_file($dostyleid, $css);
			delete_style_css_directory($dostyleid, 'ltr');
			delete_style_css_directory($dostyleid, 'rtl');
		}

		vB::getDbAssertor()->assertQuery('template_deletefrom_templatemerge2', array('styleid' => $dostyleid));
		vB::getDbAssertor()->assertQuery('style_delete', array('styleid' => $dostyleid));
		vB::getDbAssertor()->assertQuery('template_deletehistory2', array('styleid' => $dostyleid));
		vB::getDbAssertor()->assertQuery('template_delete2', array('styleid' => $dostyleid));
		vB::getDbAssertor()->assertQuery('style_deletestylevar', array('styleid' => $dostyleid));

		// If style/theme has an icon or preview image, remove it (decrement refcount and let the cron job clean it up)
		// we don't modify publicview here, since this file may be used somewhere else that needs
		// publicview=1, for example as the site header image.
		if ($style['filedataid'] > 0)
		{
			vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $style['filedataid']));
		}
		if ($style['previewfiledataid'] > 0)
		{
			vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $style['previewfiledataid']));
		}

		// update parent info for child styles
		vB::getDbAssertor()->assertQuery('style_updateparent', array(
			'styleid' =>	$dostyleid,
			'parentid' =>	$style['parentid'],
			'parentlist' =>	$style['parentlist'],
		));
		/*
			Above does not seem correct.
			parentlist contains the currentstyleid + parent's styleid, while the query is
				UPDATE {TABLE_PREFIX}style
				SET parentid = {parentid},
				parentlist = {parentlist}
				WHERE parentid = {styleid}
			so it won't have the correct parentlist for any children, and probably
			relies on the full rebuild for correcting children's parentlists.
		 */

		/*
			Something about below will revert parentlist changes
			during upgrades if the parentlist actually changed.
			It's probably something to do with in-memory variables
			not being cleared properly, but I wasn't able to figure
			it out, so placing a workaround for now.
			Upgrade 520a3 step_7 will call buildAllStyles() on its own
			after it's done, which seems to preserve the parentlist
			changes.
		 */
		if (!$skipRebuild)
		{
			$this->library->buildAllStyles(0, 0);
		}

		return true;
	}

	/**
	* Builds all data from the template table into the fields in the style table
	*
	* @param	boolean	If true, will drop the template table and rebuild, so that template ids are renumbered from zero
	* @param	boolean	If true, will fix styles with no parent style specified
	* @param	boolean	If true, reset the master cache
	*/
	public function buildAllStyles($renumber = 0, $install = 0, $resetcache = false)
	{
		if (!vB::getUserContext()->hasAdminPermission('canadminstyles ')
			AND !vB::getUserContext()->hasAdminPermission('canadminads')
			AND !vB::getUserContext()->hasAdminPermission('canadmintemplates'))
		{
			throw new vB_Exception_Api('no_permission');
		}
		return $this->library->buildAllStyles($renumber, $install, $resetcache);
	}

	public function generateStyle($scheme, $type, $parentid, $title, $displayorder = 1, $userselect = false)
	{
		if (!vB::getUserContext()->hasAdminPermission('canadminstyles'))
		{
			throw new vB_Exception_Api('no_permission');
		}

		define('NO_IMPORT_DOTS', true);

		$merge = $scheme['primary'];

		if (!empty($scheme['secondary']))
		{
			$merge = array_merge($merge, $scheme['secondary']);
		}

		if (!empty($scheme['complement']))
		{
			$merge = array_merge($merge, $scheme['complement']);
		}

		foreach ($merge as $val)
		{
			$hex[] = $val['hex'];
		}

		switch ($type)
		{
			case 'lps': // Color : Primary and Secondary
				$sample_file = "style_generator_sample_light.xml";
				break;
			case 'lpt': // White : Similar to the current style
				$sample_file = "style_generator_sample_white.xml";
				break;
			case 'gry': // Grey :: Primary 3 and Primary 4 only
				$sample_file = "style_generator_sample_gray.xml";
				break;
			case 'drk': // Dark : Primary 3 and Primary 4 only
			default:// Dark : Default to Dark
				$sample_file = "style_generator_sample_dark.xml";
				break;
		}

		$xmlobj = new vB_XML_Parser(false, DIR . '/includes/xml/' . $sample_file);
		$styledata = $xmlobj->parse();

		if($title === '')
		{
			$title = 'Style ' . time();
		}

		$xml = new vB_XML_Builder();
		$xml->add_group('style', array(
			'name' => $title,
			'vbversion' => vB::getDatastore()->getOption('templateversion'),
			'product' => 'vbulletin',
			'type' => 'custom',
		));
		$xml->add_group('stylevars');

		foreach($styledata['stylevars']['stylevar'] AS $stylevars)
		{
			// The XML Parser outputs 2 values for the value field when one is set as an attribute.
			// The work around for now is to specify the first value (the attribute). In reality
			// the parser shouldn't add a blank 'value' if it exists as an attribute.
			if (!empty($stylevars['colCat']))
			{
				list($group, $nr) = explode('-', $stylevars['colCat']);
				$group = ($group == 'sec' ? 'secondary' : 'primary');
				$stylevars['value'] = '{"color":"#' . $scheme[$group][$nr]['hex'] . '"}';
			}

			$thisValue = json_decode($stylevars['value'], true);

			if (strpos($stylevars['name'], '_border') !== false)
			{
				// @todo, make this inherit the border style & width from the default style?
				$thisValue['width'] = 1;
				$thisValue['units'] = 'px';
				$thisValue['style'] = 'solid';
			}

			$xml->add_tag('stylevar', '', array(
				'name' => htmlspecialchars($stylevars['name']),
				'value' =>  base64_encode(serialize($thisValue)),
			));
		}

		// Close stylevar group
		$xml->close_group();
		// Close style group
		$xml->close_group();

		$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";
		$doc .= $xml->output();
		$xml = null;
		$imported = $this->library->importStyleFromXML($doc, $title, $parentid, -1, true, $displayorder, $userselect, true);
		$this->buildAllStyles();
		//xml_import_style($doc, -1, $parentid, $title, $anyversion, $displayorder, $userselect, null, null, true);
		return $imported;
	}

	/** gets the css File Location, which only means something if storecssasfiles is set to 2.
	 *
	 * @return	string.
	 */
	public function fetchCssLocation()
	{
		if (!isset($this->cssFileLocation))
		{
			$fileLocation = vB::getDatastore()->getOption('cssfilelocation');

			if (!empty($fileLocation))
			{
				$this->cssFileLocation = $fileLocation;
			}
			else
			{
				$this->cssFileLocation =	'/clientscript/vbulletin_css';
			}
		}
		return($this->cssFileLocation);
	}

	/**
	 * Returns an array of theme information if the user has permission.
	 * The theme parent style is skipped.
	 *
	 * @return	array 	array where each element contains an array of theme information, eg:
	 *			array(
	 *				"themes" => array(
	 *					0 => array(
	 *						"styleid" => {theme1's styleid}
	 *						"title" => {theme1's title},
	 *						"iconurl" => {URL to theme1's icon},
	 *						"previewurl" => {URL to theme1's preview image (empty if there is no preview image)},
	 *					),
	 *					1 => array(
	 *						"styleid" => {theme2's styleid}
	 *						"title" => {theme2's title},
	 *						"iconurl" => {URL to theme2's icon},
	 *						"previewurl" => {URL to theme2's preview image (empty if there is no preview image)},
	 *					), [...]
	 *				)
	 *			)
	 */
	public function getThemeInfo()
	{
		// must be able to administrate settings (the limited settings
		// perm is sufficient) or styles to use the themes tab in sb.
		if (
			!$this->hasAdminPermission('canadminsettings') AND
			!$this->hasAdminPermission('canadminsettingsall') AND
			!$this->hasAdminPermission('canadminstyles')
		)
		{
			throw new vB_Exception_Api('no_permission');
		}

		$themesQuery = vB::getDbAssertor()->getRows(
			'style',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('styleid', 'parentlist', 'guid', 'title', 'filedataid', 'previewfiledataid', 'styleattributes'),
			),
			array(
				'field' => array('title', 'displayorder'),
				'direction' => array(vB_dB_Query::SORT_ASC, vB_dB_Query::SORT_ASC),
			),
			'styleid'
		);

		// If any styles don't have an icon and previewimage, inherit from the nearest
		// parent that has one.
		foreach ($themesQuery AS $k => $v)
		{
			$parentids = explode(',', $v['parentlist']);

			// icon
			if (!$v['filedataid'])
			{
				foreach ($parentids AS $parentid)
				{
					if (isset($themesQuery[$parentid]) AND $themesQuery[$parentid]['filedataid'])
					{
						$themesQuery[$k]['filedataid'] = $themesQuery[$parentid]['filedataid'];
						break;
					}
				}
			}

			// preview image
			if (!$v['previewfiledataid'])
			{
				foreach ($parentids AS $parentid)
				{
					if (isset($themesQuery[$parentid]) AND $themesQuery[$parentid]['previewfiledataid'])
					{
						$themesQuery[$k]['previewfiledataid'] = $themesQuery[$parentid]['previewfiledataid'];
						break;
					}
				}
			}
		}

		$themesList = array();
		$defaultTheme = null;
		$urlPrefix = 'filedata/fetch?filedataid=';
		$defaultIcon = vB::getDatastore()->getOption('frontendurl') . '/images/misc/theme-default.png';
		foreach ($themesQuery AS $theme)
		{
			// skip the default theme parent, since it's only there for organizational
			// purposes and isn't really a theme (it would display the same as the default style)
			if (
				$theme['guid'] == vB_Xml_Import_Theme::DEFAULT_PARENT_GUID
				OR $theme['guid'] == vB_Xml_Import_Theme::DEFAULT_GRANDPARENT_GUID
			)
			{
				continue;
			}

			// Do not return any read-protected styles/themes.
			if (!$this->library->checkStyleReadProtection(null, $theme))
			{
				continue;
			}

			$themeInfo = array(
				'styleid' => $theme['styleid'],
				'title'   => $theme['title'],
				'iconurl' => (!empty($theme['filedataid']) ? $urlPrefix . $theme['filedataid'] : $defaultIcon),
				'previewurl' => (!empty($theme['previewfiledataid']) ? $urlPrefix . $theme['previewfiledataid'] : ''),
			);

			// @TODO - DEFAULT_THEME_GUID is deprecated, the style was removed in VBV-14913
			if ($theme['guid'] === vB_Xml_Import_Theme::DEFAULT_THEME_GUID)
			{
				$defaultTheme = $themeInfo;
			}
			else
			{
				$themesList[] = $themeInfo;
			}
		}

		// ensure the default theme is always at the beginning of the list.
		if ($defaultTheme !== null)
		{
			array_unshift($themesList, $defaultTheme);
		}

		return array('themes' => $themesList);
	}

	/**
	 * Sets the site default style
	 *
	 * @param	int	Styleid to set as default
	 *
	 * @return	array	Array containing the bool 'success' element
	 */
	public function setDefaultStyle($styleid)
	{
		// must be able to administrate settings (the limited settings
		// perm is sufficient) or styles to set default theme/style
		if (
			!$this->hasAdminPermission('canadminsettings') AND
			!$this->hasAdminPermission('canadminsettingsall') AND
			!$this->hasAdminPermission('canadminstyles')
		)
		{
			throw new vB_Exception_Api('no_permission');
		}

		return vB_Library::instance('Options')->updateValue('styleid', $styleid);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88727 $
|| #######################################################################
\*=========================================================================*/
