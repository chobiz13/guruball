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
 * vB_Api_Page
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Page extends vB_Api
{
	protected $disableWhiteList = array('getQryCount', 'preload', 'savePreCacheInfo', 'fetchPageById');

	/** array of info used for precaching
	***/
	protected $preCacheInfo = array();
	protected $lastCacheData = array();
	/**Last time we saved cache- useful to prevent thrashing **/
	protected $lastpreCache = false;
	/**Minimum time between precache list updates, in seconds **/
	const MIN_PRECACHELIFE = 300;

	protected function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get information for a page
	 * @param int $pageid
	 * @param array $routeData -- The needed to render this pages route.  Will vary by page
	 *
	 * @return array
	 *  	pageid int
	 * 		parentid int -- the parent page (currently unused)
	 *    pagetemplateid int
	 *    title string
   *    metadescription string -- the metadescription to display when page is rendered as html
	 *    routeid int -- route associated with this page
	 *    moderatorid int -- need to determine
	 *    displayorder int -- the order to display page when displaying lists of pages
   * 		pagetype string -- default or custom depending of if this is a page we install with the system
   *		product string -- product the page belongs to 'vbulletin' for pages created by the system and via the admincp
	 *		guid string -- globally unique identifier
	 *		screenlayoutid int -- layout for the page
	 * 		screenlayouttemplate string -- name of the layout template
	 *		templatetitle string -- need to determine
	 *		ishomepage boolean -- is this the homepage
   * 		makehomepagecheckattr string -- DEPRECATED 'checked=checked' if this is the homepage
   * 		isgeneric boolean -- DEPRECATED true if this is of type default
	 *		urlprefix string -- prefix for the route
   *		url string -- url generated from the route -- will be relative to the frontend base
	 *		urlscheme string -- DEPRECATED -- will be blank
	 *		urlhostname string -- DEPRECATED -- will be blank
	 *		noindex boolean -- should this page be indexed.
	 *		nofollow boolean -- should this page be followed.
	 */
	public function fetchPageById($pageid, $routeData = array())
	{
		$pageid = intval($pageid);

		$db = vB::getDbAssertor();

		$conditions = array(
			'pageid' => $pageid,
		);
		//$page = $db->getRow('fetch_page_pagetemplate_screenlayout', $conditions);
		$page = $db->assertQuery('fetch_page_pagetemplate_screenlayout', $conditions);
		$page = $page->current();

		if ($page)
		{
			// Fetch phrases
			$guidforphrase = vB_Library::instance('phrase')->cleanGuidForPhrase($page['guid']);
			$phrases = vB_Api::instanceInternal('phrase')->fetch(array('page_' . $guidforphrase . '_title', 'page_' . $guidforphrase . '_metadesc'));
			$page['title'] = !empty($phrases['page_' . $guidforphrase . '_title'])?$phrases['page_' . $guidforphrase . '_title']:$page['title'];
			$page['metadescription'] = !empty($phrases['page_' . $guidforphrase . '_metadesc'])?$phrases['page_' . $guidforphrase . '_metadesc']:$page['metadescription'];

			// check if this is currently the homepage
			$route = vB5_Route::getHomePageRouteInfo();

			if ($route AND $route['contentid'] == $page['pageid'])
			{
				$page['ishomepage'] = true;
				//todo shouldn't use html in the API.
				$page['makehomepagecheckattr'] = ' checked="checked"';
			}
			else
			{
				$page['ishomepage'] = false;
				$page['makehomepagecheckattr'] = '';
			}

			$page['isgeneric'] = ($page['pagetype'] == vB_Page::TYPE_DEFAULT);

			// get url scheme, hostname and path
			$route = vB5_Route::getRoute(intval($page['routeid']), $routeData);
			if ($route)
			{
				$page['urlprefix'] = $route->getCanonicalPrefix();
				$page['url'] = $route->getCanonicalUrl();

				$parsed = vB_String::parseUrl($page['url']);
				$page['urlscheme'] = isset($parsed['scheme']) ? $parsed['scheme'] : '';
				$page['urlhostname'] = isset($parsed['host']) ? $parsed['host'] : '';
				$page['urlpath'] = base64_encode($parsed['path']);
				$page['noindex'] = false;
				$page['nofollow'] = false;
				$arguments = $route->getArguments();
				if (!empty($arguments['noindex']))
				{
					$page['noindex'] = $arguments['noindex'];
				}
				if (!empty($arguments['nofollow']))
				{
					$page['nofollow'] = $arguments['nofollow'];
				}
			}
		}

		return $page;
	}

	/**
	 * Saves a (new or existing) page
	 *
	 * @param	array	Page data
	 * @param	array	Conditions - Must be specified if updating an existing record.
	 *
	 * @return	int|mixed	If it is a new page, the pageid will be returned
	 */
	public function save(array $data, array $conditions = array())
	{
		$this->checkHasAdminPermission('canusesitebuilder');

		$db = vB::getDbAssertor();

		// We should unset 'pageid' from data
		// 'pageid' should go to conditions parameter.
		unset($data['pageid']);

		// Get page table structure
		$structure = vB_dB_Assertor::fetchTableStructure('page');

		foreach ($data as $k => $v)
		{
			if (!in_array($k, $structure['structure']))
			{
				unset($data[$k]);
			}
		}

		if (!empty($conditions))
		{
			return $db->update('page', $data, $conditions);
		}
		else
		{
			return $db->insert('page', $data);
		}
	}



	/**
	 * Deletes a page
	 *
	 * @param	int		id of the page to be deleted
	 *
	 * @return	mixed	either success=>true or success=>false and an error array
	 */
	public function delete($pageid)
	{
		$this->checkHasAdminPermission('canusesitebuilder');
		$page = $this->fetchPageById($pageid);

		if (empty($page))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		if (!empty($page['errors']))
		{
			return array('success' => false, 'errors' => $page['errors']);
		}

		if ($page['pagetype'] != 'custom')
		{
			throw new vB_Exception_Api('cannot_delete_default_pages');
		}
		$assertor = vB::getDbAssertor();
		$routeInfo = $this->fetchRouteClass($pageid);

		if (empty($routeInfo['routeclass']))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		if (!empty($routeInfo['errors']))
		{
			return array('success' => false, 'errors' => $routeInfo['errors']);;
		}

		if ($routeInfo['routeclass'] != 'vB5_Route_Page')
		{
			throw new vB_Exception_Api('cannot_delete_default_pages');
		}
		$check = $assertor->assertQuery('page', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'pageid' => $pageid));

		if ($check AND empty($check['errors']))
		{
			//We delete the routenew record also.
			$assertor->assertQuery('routenew', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'routeid' => $page['routeid']));
			return array('success' => true);
		}
		else
		{
			return array('success' => false, 'errors' => $check['errors']);
		}
	}

	public function getPageNav($currentpage = 1, $totalpages = 1)
	{
		$cacheKey = 'pageNav_' . $currentpage . '_' . $totalpages;

		if ($pageNav = vB_Cache::instance()->read($cacheKey))
		{
			return $pageNav;
		}

		$options = vB::getDatastore()->getValue('options');
		// create array of possible relative links that we might have (eg. +10, +20, +50, etc.)
		if (!isset($options['pagenavsarr']))
		{
			$options['pagenavsarr'] = preg_split('#\s+#s', $options['pagenavs'], -1, PREG_SPLIT_NO_EMPTY);
		}

		$pages = array(1, $currentpage, $totalpages);

		for ($i = 1; $i <= $options['pagenavpages']; $i++)
		{
			$pages[] = $currentpage + $i;
			$pages[] = $currentpage - $i;
		}

		foreach ($options['pagenavsarr'] AS $relpage)
		{
			$pages[] = $currentpage + $relpage;
			$pages[] = $currentpage - $relpage;
		}

		$show_prior_elipsis = $show_after_elipsis = ($totalpages > $options['pagenavpages']) ? 1 : 0;

		$pages = array_unique($pages);
		sort($pages);

		$final_pages = array();
		foreach ($pages AS $foo => $curpage)
		{
			if ($curpage < 1)
			{
				continue;
			}
			else if ($curpage > $totalpages)
			{
				break;
			}

			$final_pages[] = $curpage;
		}
		vB_Cache::instance()->write($cacheKey, $final_pages, 0, "pageNavChg");
		return $final_pages;
	}

	/**
	 * Get pagination information for frontend use
	 *
	 * @param	int		Current page number
	 * @param	int		Total items number
	 * @param	int		Number of items per page
	 * @param	array	Route info data
	 * @param	String	forum base url
	 * @param	int		Maximum pages allowed
	 *
	 * @return	array	Number of pages, start/end count, next/previous URLs
	 */
	public function getPagingInfo($pageNum = 1, $totalCount = 0, $perPage = 0, array $routeInfo, $baseUrl, $maxpage = 0)
	{
		$perPage = (int) $perPage;
		$perPage = $perPage < 1 ? 25 : $perPage;
		$totalPages = ceil($totalCount / $perPage);
		if ($totalPages == 0)
		{
			$totalPages = 1;
		}

		if ($maxpage AND $totalPages > $maxpage)
		{
			$totalPages = $maxpage;
		}

		$pageNum = (int) $pageNum;
		if ($pageNum < 1)
		{
			$pageNum = 1;
		}
		else if ($pageNum > $totalPages)
		{
			$pageNum = ($totalPages > 0) ? $totalPages : 1;
		}

		$prevUrl = $nextUrl = '';

		if ($pageNum > 1)
		{
			$routeInfo['arguments']['pagenum'] = $pageNum - 1;
			$prevUrl = $baseUrl . vB5_Route::buildUrl($routeInfo['routeId'], $routeInfo['arguments'], $routeInfo['queryParameters']);
		}

		if ($pageNum < $totalPages)
		{
			$routeInfo['arguments']['pagenum'] = $pageNum + 1;
			$nextUrl = $baseUrl . vB5_Route::buildUrl($routeInfo['routeId'], $routeInfo['arguments'], isset($routeInfo['queryParameters']) ? $routeInfo['queryParameters'] : null);
		}

		if ($totalCount > 0)
		{
			$startCount = ($pageNum * $perPage) - $perPage + 1;
			$endCount = $pageNum * $perPage;
			if ($endCount > $totalCount)
			{
				$endCount = $totalCount;
			}
		}
		else
		{
			$startCount = $endCount = 0;
		}

		unset($routeInfo['arguments']['pagenum']);
		$pageBaseUrl = $baseUrl . vB5_Route::buildUrl($routeInfo['routeId'], $routeInfo['arguments']);

		//get pagenav data
		$pageNavData = array(
			'startcount' => $startCount,
			'endcount' => $endCount,
			'totalcount' => $totalCount,
			'currentpage' => $pageNum,
			'prevurl' => $prevUrl,
			'nexturl' => $nextUrl,
			'totalpages' => $totalPages,
			'perpage' => $perPage,
			'baseurl' => $pageBaseUrl,
			'routeInfo' => $routeInfo,
		);

		return $pageNavData;
	}

	// Removed fetchPageMapHierarchy() VBV-13508 Remove the sitebuilder "Page Map"

	/**
	 * Saves a page based on page editor info
	 * @param 	mixed	$input
	 * @return 	mixed	array
	 * 	success boolean
	 * 	url string -- DEPRECATED this will not always be correct due to the lack of complete route data.  See the action savePage
	 * 		in the front end controller for a way to generate the correct url for the updated page
	 * 	pageid int -- the pageid for the update or created page
	 */
	public function pageSave($input)
	{
		$this->checkHasAdminPermission('canusesitebuilder');

		/* Sample input
		Array
		(
			[pageid] => 1,
			[screenlayoutid] => 2,
			[displaysections[0 => [{"widgetId":"3","widgetInstanceId":"1"},{"widgetId":"4","widgetInstanceId":"2"}],
			[displaysections[1 => [{"widgetId":"1","widgetInstanceId":"3"},{"widgetId":"2","widgetInstanceId":"4"}],
			[pagetitle] => Forums,
			[resturl] => forums,
			[pagetemplateid] => 0,	// 0 if we are saving the page template as a new page template
			[templatetitle] => Name,
			[btnSaveEditPage] =>
		)
		*/
		$done = false;
		$i = 0;
		$displaysections = array();
		foreach ($input as $key => $value)
		{
			if (!empty($value) AND preg_match('/^displaysections\[([0-9]+)$/i', $key, $matches))
			{
				$displaysection_value = json_decode($value, true);
				if (!empty($displaysection_value))
				{
					$displaysections[$matches[1]] = $displaysection_value;
				}
			}
		}

		// TODO: apparently JQuery will send POST data using the UTF-8 charset,
		// so we don't convert the resturl. However, if the url can be edited from anywhere
		// else than Site Builder, we'll need to convert it properly to ensure that the
		// route table gets the proper UTF-8 characters saved.

		// cleaning input
		$input = array(
			'pagetitle' => trim(strval($input['pagetitle'])),
			'resturl' => trim(strval($input['resturl']), " \t\n\r\0\x0B/"), // subdirectory. Remove white space as well as any beginning/trailing forward slashes
			'pageid' => intval($input['pageid']),
			'nodeid' => intval($input['nodeid']),
			'userid' => intval($input['userid']),
			'pagetemplateid' => intval($input['pagetemplateid']),
			'templatetitle' => trim(strval($input['templatetitle'])),
			'screenlayoutid' => intval($input['screenlayoutid']),
			'displaysections' => $displaysections,
			'metadescription' => trim(strval($input['metadescription'])),
		);

		// we need to check that resturl does not contain any reserved characters.
		if (!$this->checkCustomUrl($input['resturl']))
		{
			throw new vB_Exception_Api('invalid_custom_url', vB_String::INVALID_CUSTOM_URL_CHAR);
		}

		if (empty($input['pagetitle']))
		{
			throw new vB_Exception_Api('page_title_cannot_be_empty');
		}
		if (empty($input['templatetitle']) AND $input['pagetemplateid'] < 1)
		{
			throw new vB_Exception_Api('page_template_title_cannot_be_empty');
		}
		if ($input['screenlayoutid'] < 1)
		{
			throw new vB_Exception_Api('you_must_specify_a_screen_layout');
		}

		$this->db = vB::getDbAssertor();

		// --- save the page template ----------------------------

		// get page info
		$forceNewPage = false; /* if prefix is modified, we need to create a new page, pagetemplate and widgets */
		$isPrefixUsed = false;
		if ($input['pageid'] > 0)
		{
			$page = $this->fetchPageById($input['pageid'], array('nodeid'=>$input['nodeid'], 'userid' => $input['userid']));
			if (!is_array($page))
			{
				$page = array();
			}
			else
			{
				$forceNewPage = ($page['isgeneric'] AND ($input['resturl'] != $page['urlprefix']));

				// if we are modifying a page url, we need to check the new url...
				if ($input['resturl'] != $page['urlprefix'])
				{
					$isPrefixUsed = vB5_Route::isPrefixUsed($input['resturl'], $page['routeid']);
				}
			}
		}
		else
		{
			// if it is a new page, we need to check the url
			$isPrefixUsed = vB5_Route::isPrefixUsed($input['resturl']);

			$page = array();
		}

		$routeApi = vB_Api::instanceInternal('route');

		// if the used prefix is a 301 to another route, then we can take it.
		if ($isPrefixUsed !== FALSE AND empty($isPrefixUsed['redirectRouteId']))
		{
			throw new vB_Exception_Api('this_url_is_already_used');
		}

		// page template
		$valuePairs = array(
			'title' => $input['templatetitle'],
			'screenlayoutid' => $input['screenlayoutid'],
		);

		$pagetemplateid = $input['pagetemplateid'];

		if ($pagetemplateid < 1 OR $forceNewPage)
		{
			$valuePairs['guid'] = vB_Xml_Export_PageTemplate::createGUID($valuePairs);
			// If no widgets were configured on the page template, we won't have a page template ID.
			$pagetemplateid = $this->db->insert('pagetemplate', $valuePairs);
			if (is_array($pagetemplateid))
			{
				$pagetemplateid = (int) array_pop($pagetemplateid);
			}
			$newTemplate = true;
		}
		else
		{
			$this->db->update('pagetemplate', $valuePairs, array('pagetemplateid' => $pagetemplateid));
			$newTemplate = false;
		}

		// widgets on page template

		$widgetApi = vB_Api::instanceInternal('widget');
		$currentWidgetInstances = $widgetApi->fetchWidgetInstancesByPageTemplateId($pagetemplateid);
		$currentWidgetInstanceIds = $this->getAllCurrentModuleInstances($currentWidgetInstances);

		$savedWidgetInstanceIds = array();

		$widgets = array();

		foreach ($input['displaysections'] AS $displaycolumn => $columnwidgets)
		{
			$displayorder = 0;
			foreach ($columnwidgets AS $columnwidget)
			{
				$columnwidgetid = intval($columnwidget['widgetId']);
				$columnwidgetinstanceid = intval($columnwidget['widgetInstanceId']);

				if (!$columnwidgetid)
				{
					continue;
				}

				if ($newTemplate)
				{
					$widgetInstanceId = 0;
				}
				else
				{
					$widgetInstanceId = $columnwidgetinstanceid;
					$savedWidgetInstanceIds[$widgetInstanceId] = $columnwidgetid;
				}

				$widget = array(
					'widgetinstanceid' => $widgetInstanceId,
					'pagetemplateid'   => $pagetemplateid,
					'widgetid'         => $columnwidgetid,
					'displaysection'   => $displaycolumn,
					'displayorder'     => $displayorder,
				);

				if (isset($columnwidget['subModules']))
				{
					$widget['subModules'] = $columnwidget['subModules'];
					$widget['displaySubModules'] = $columnwidget['displaySubModules'];

					if (!$newTemplate)
					{
						$savedWidgetInstanceIds += $this->getAllSubModulesInstances($columnwidget['subModules']);
					}
				}

				$widgets[] = $widget;

				++$displayorder;
			}
		}

		// check we are not adding a system widget
		$newWidgets = array_diff_key($savedWidgetInstanceIds, $currentWidgetInstanceIds);
		if ($newWidgets)
		{
			foreach($newWidgets AS $widgetId)
			{
				if ($widgetApi->isSystemWidget($widgetId))
				{
					throw new vB_Exception_Api('cannot_add_system_module');
				}
			}
		}

		// check we are not removing a system widget
		$deleteWidgets = array_diff_key($currentWidgetInstanceIds, $savedWidgetInstanceIds);
		if ($deleteWidgets)
		{
			foreach($deleteWidgets AS $widgetId)
			{
				if ($widgetApi->isSystemWidget($widgetId))
				{
					throw new vB_Exception_Api('cannot_remove_system_module');
				}
			}
		}
		// save widget placements on the page template
		foreach ($widgets as $widget)
		{
			$widgetinstanceid = $widget['widgetinstanceid'];
			unset($widget['widgetinstanceid']);

			$subModules = isset($widget['subModules']) ? $widget['subModules'] : array();
			unset($widget['subModules']);

			$displaySubModules = isset($widget['displaySubModules']) ? $widget['displaySubModules'] : array();
			unset($widget['displaySubModules']);

			if ($widgetinstanceid > 0 AND !$forceNewPage)
			{
				$this->db->update('widgetinstance', $widget, array('widgetinstanceid' => $widgetinstanceid));
			}
			else
			{
				$widgetinstanceid = $this->db->insert('widgetinstance', $widget);
				if (is_array($widgetinstanceid))
				{
					$widgetinstanceid = (int) array_pop($widgetinstanceid);
				}
			}

			// save submodules if available
			if (!empty($subModules))
			{
				$this->saveSubModules($pagetemplateid, $widgetinstanceid, $subModules, $displaySubModules, $forceNewPage);
			}
		}

		// remove any widgets that have been removed from the page template
		if (!empty($deleteWidgets))
		{
			$deleted = $widgetApi->deleteWidgetInstances(array_keys($deleteWidgets));
			if ($deleted != count($deleteWidgets))
			{
				throw new vB_Exception_Api('unable_to_delete_widget_instances');
			}
		}

		// --- save the page  ---------------------------------

		// permalink
		$urlprefix = $input['resturl'];

		$valuePairs = array(
//			'title' => $input['pagetitle'],
			'pagetemplateid' => $pagetemplateid,
//			'metadescription' => $input['metadescription'],
		);

		// save page
		if (!empty($page) AND !$forceNewPage)
		{
			// update page record
			$conditions = array(
				'pageid' => $page['pageid'],
			);
			$this->save($valuePairs, $conditions);
			$pageid = $page['pageid'];
			$guidforphrase = vB_Library::instance('phrase')->cleanGuidForPhrase($page['guid']);

			// update this page's current route if needed
			if ($input['resturl'] != $page['urlprefix'])
			{
				$data = array('prefix' => $urlprefix);
				if (isset($input['nodeid']) AND !empty($input['nodeid']))
				{
					$data['nodeid'] = $input['nodeid'];
				}
				vB5_Route::updateRoute($page['routeid'],$data);
			}
		}
		else
		{
			$valuePairs['guid'] = vB_Xml_Export_Page::createGUID($valuePairs);
			$guidforphrase = vB_Library::instance('phrase')->cleanGuidForPhrase($valuePairs['guid']);

			// insert a new page
			$pageid = $this->save($valuePairs);
			if (is_array($pageid))
			{
				$pageid = (int) array_pop($pageid);
			}

			// route
			if (isset($page['routeid']))
			{
				// update this page's current route
				$data = array(
					'pageid' => $pageid,
					'prefix' => $urlprefix,
					'nodeid' => $input['nodeid']
				);
				$routeid = vB5_Route::updateRoute($page['routeid'], $data);
			}
			else
			{
				$valuePairs = array(
					'prefix' => $urlprefix,
					'contentid' => $pageid,
				);
				$routeid = $routeApi->createRoute('vB5_Route_Page', $valuePairs);
			}
			if (is_array($routeid))
			{
				$routeid = (int) array_pop($routeid);
			}

			// update page with routeid (for deleting it when deleting a page)
			$this->updateNewPageRoute($pageid, $routeid);

			// VBV-13666. Since $forceNewPage is true, we have created a new
			// page record corresponding to $pageid. But it appears that the
			// actual page being used is still the old page record. Thus we need
			// to update the routeid in the old page record so that the redirect
			// works correctly (the page route class gets the cannonical route
			// record based on the routeid in the page record). When/if we fix/change
			// the behavior for creating a new page record when updating the page
			// route prefix, this code will be wrong-- instead, we will need code
			// in the if branch above (for updating a page as opposed to creating
			// a new one), to update the routeid in the page record.
			if (!empty($page['pageid']))
			{
				$this->updateNewPageRoute($page['pageid'], $routeid);
			}
		}

		// Insert/Update phrases for page title, meta description.
		// Only update phrases of current language. Keep other translations.
		$phraseLib = vB_Library::instance('phrase');
		$currentlanguageid = vB::getCurrentSession()->get('languageid');
		if (empty($currentlanguageid))
		{
			$currentlanguageid = vB::getDatastore()->getOption('languageid');
		}

		$translations = vB::getDbAssertor()->assertQuery('phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'varname', 'value' => array('page_' . $guidforphrase . '_title', 'page_' . $guidforphrase . '_metadesc'), 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'fieldname', 'value' => 'pagemeta', 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'languageid', 'value' => array(-1, $currentlanguageid), 'operator' => vB_dB_Query::OPERATOR_NE),
				)
			)
		);

		$pagetrans = array(
			'page_' . $guidforphrase . '_title' => array(),
			'page_' . $guidforphrase . '_metadesc' => array(),
		);
		$hasdefault = array();
		$productid = false;
		foreach ($translations as $translation)
		{
			if (!$productid)
			{
				$productid = $translation['product'];
			}

			$pagetrans[$translation['varname']][$translation['languageid']] = $translation['text'];

			if ($translation['languageid'] == 0)
			{
				$hasdefault[$translation['varname']] = true;
			}
		}

		// Add input text to translates
		$pagetrans['page_' . $guidforphrase . '_title'][$currentlanguageid] = $input['pagetitle'];
		$pagetrans['page_' . $guidforphrase . '_metadesc'][$currentlanguageid] = $input['metadescription'];

		foreach (array_keys($pagetrans) as $varname)
		{
			if (empty($hasdefault[$varname]) OR $currentlanguageid == vB::getDatastore()->getOption('languageid'))
			{
				// If the page phrase doesn't have a default one (languageid = 0) or current language is default language
				// We should update the phrase for default language (languageid = 0)
				$pagetrans[$varname][0] = $pagetrans[$varname][$currentlanguageid];
			}
		}
		if (!$productid)
		{
			$page = vB::getDbAssertor()->getColumn('page', 'product', array('pageid' => $pageid));
			$productid = array_pop($page);
		}

		$phraseLib->save('pagemeta',
			'page_' . $guidforphrase . '_title',
			array(
				'text' => $pagetrans['page_' . $guidforphrase . '_title'],
				'product' => $productid,
				'oldvarname' => 'page_' . $guidforphrase . '_title',
				'oldfieldname' => 'global',
				'skipdebug' => 1,
			)
		);

		$phraseLib->save('pagemeta',
			'page_' . $guidforphrase . '_metadesc',
			array(
				'text' => $pagetrans['page_' . $guidforphrase . '_metadesc'],
				'product' => $productid,
				'oldvarname' => 'page_' . $guidforphrase . '_metadesc',
				'oldfieldname' => 'global',
				'skipdebug' => 1,
			)
		);

		build_language();


		vB_Cache::instance()->event('pageChg_' . $pageid);

		$page = $this->fetchPageById($pageid, array('nodeid'=>$input['nodeid'],'userid' => $input['userid']));

		return array('success' => true, 'url' => $page['url'], 'pageid' => $pageid);
	}

	/**
	 * Sets the route for the specified page to the specified routeid. Also appears to
	 * update the route records for channels associated with this page.
	 *
	 * Note: Moved from the Route API and changed visibility to protected, see VBV-15361.
	 *
	 * @param int Page ID
	 * @param int New Route ID
	 */
	protected function updateNewPageRoute($pageId, $routeId)
	{
		$db = vB::getDbAssertor();

		$db->update('page', array('routeid' => $routeId), array('pageid' => $pageId));

		$pageRoute = $db->getRow('routenew', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::CONDITIONS_KEY => array(
				'routeid' => $routeId
			)
		));

		// update routes for included channels
		$pageWidgets = $db->getRows('getPageWidgetsByType', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'pageid' => $pageId,
			'widgetids' => 3, // TODO: replace with a class constant
		));

		// TODO: I moved this function from the route api to the page api
		// for VBV-15361. However, I'm a bit unsure what we're trying to
		// do with the following code which apparently tries to update
		// channel routes. Note the second of the two preg_replace
		// calls uses $pageRoute['prefix'] instead of $pageRoute['regex']
		// which would seem to make more sense. BUT since the scope of
		// VBV-15361 is only to fix the security hole, I'm not going to
		// make any changes to the behavior of this function other than
		// making it protected instead of public.
		if ($pageWidgets)
		{
			$channelIds = array();
			foreach ($pageWidgets AS $widget)
			{
				$adminConfig = unserialize($widget['adminconfig']);
				if (isset($adminConfig['channel_node_ids']))
				{
					$channelIds = array_merge($channelIds, $adminConfig['channel_node_ids']);
				}
			}

			if ($channelIds)
			{
				$routes = $db->getRows('getChannelRoutes', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
					'channelids' => $channelIds,
				));

				$defaultPrefix = 'home';
				foreach ($routes AS $route)
				{
					if (strpos($route['prefix'], $defaultPrefix) === 0)
					{
						$newprefix = preg_replace("#^$defaultPrefix#", $pageRoute['prefix'], $route['prefix']);
						// Should this use $pageRoute['regex'] instead of $pageRoute['prefix']? See comment above.
						$newregex = preg_replace("#^$defaultPrefix#", $pageRoute['prefix'], $route['regex']);

						$db->update('routenew', array('prefix' => $newprefix, 'regex' => $newregex), array('routeid' => $route['routeid']));
					}
				}
			}
		}
	}

	protected function getAllCurrentModuleInstances($modules)
	{
		if (empty($modules))
		{
			return array();
		}
		else
		{
			$result = array();
			foreach($modules AS $module)
			{
				$result[$module['widgetinstanceid']] = $module['widgetid'];

				if (isset($module['subModules']))
				{
					$result += $this->getAllCurrentModuleInstances($module['subModules']);
				}
			}

			return $result;
		}
	}

	protected function getAllSubModulesInstances($subModules)
	{
		if (empty($subModules))
		{
			return array();
		}
		else
		{
			$result = array();
			foreach($subModules AS $module)
			{
				$widgetInstanceId = intval($module['widgetInstanceId']);
				$widgetId = intval($module['widgetId']);

				$result[$widgetInstanceId] = $widgetId;

				if (isset($module['subModules']))
				{
					$result += $this->getAllSubModulesInstances($subModules);
				}
			}

			return $result;
		}
	}

	protected function saveSubModules($pageTemplateId, $widgetInstanceId, $subModules, $displaySubModules, $forceNewPage)
	{
		$subWidgetInstances = array();

		// save subwidget instances
		foreach ($subModules as $module)
		{
			$widgetinstanceid = intval($module['widgetInstanceId']);
			$widget['widgetid'] = intval($module['widgetId']);
			$widget['containerinstanceid'] = intval($widgetInstanceId);
			$widget['pagetemplateid'] = intval($pageTemplateId);

			if (empty($widget['widgetid']))
			{
				continue;
			}

			if ($widgetinstanceid > 0 AND !$forceNewPage)
			{
				$this->db->update('widgetinstance', $widget, array('widgetinstanceid' => $widgetinstanceid));
			}
			else
			{
				$widgetinstanceid = $this->db->insert('widgetinstance', $widget);
				if (is_array($widgetinstanceid))
				{
					$widgetinstanceid = (int) array_pop($widgetinstanceid);
				}
			}
			$subWidgetInstances[] = $widgetinstanceid;

			// update visible modules
			$widgetApi = vB_Api::instance('widget');
			if (!($adminConfig = $widgetApi->fetchAdminConfig($widget['containerinstanceid'])))
			{
				$adminConfig = array();
			}
			array_walk($displaySubModules, 'intval');
			$adminConfig['display_modules'] = $displaySubModules;
			$this->db->update('widgetinstance', array('adminconfig' => serialize($adminConfig)), array('widgetinstanceid' => $widget['containerinstanceid']));

			// save submodules if available
			if (isset($module['subModules']))
			{
				$this->saveSubModules($pageTemplateId, $widgetinstanceid, $module['subModules'], $module['displaySubModules'], $forceNewPage);
			}
		}
	}

	/** This returns the number and type of database asserts. This is similar to but a bit smaller than the number of queries executed.
	 *
	 *	@return 	mixed	array of 'queryCount', 'queries'. Integer and array of strings
	 * **/
	public function getQryCount()
	{
		$qryCount = vB::getDbAssertor()->getQryCount();

		if (!empty($_REQUEST) AND !empty($_REQUEST['querylist']))
		{
			$qryCount['showQueries'] = 1;
		}
		else
		{
			$qryCount['showQueries'] = 0;
			unset($qryCount['queries']);
		}
		return $qryCount;
	}

	/** This preloads information for the current page.
	*
	* 	@param	string	the identifier for this page, which comes from the route class.
	*
	***/
	public function preload($pageKey)
	{
		$this->lastCacheData = vB_Cache::instance(vB_Cache::CACHE_LARGE)->read("vbPre_$pageKey");

		//If we don't have anything, just return;
		if (!$this->lastCacheData)
		{
			return;
		}

		$this->lastpreCache = $this->lastCacheData['cachetime'];

		if (!empty($this->lastCacheData['data']))
		{
			foreach ($this->lastCacheData['data'] AS $class => $tasks)
			{
				try
				{
					$library = vB_Library::instance($class);
					foreach ($tasks AS $method => $params)
					{
						if (method_exists($library, $method))
						{
							$reflection = new ReflectionMethod($library, $method);
							$reflection->invokeArgs($library, $params);
						}
					}

				}
				catch(exception $e)
				{
					//nothing to do. Just try the other methods.
				}
			}
		}



	}

	/** This saves preload information for the current page.
	 *
	 * 	@param	string	the identifier for this page, which comes from the route class.
	 *
	 ***/
	public function savePreCacheInfo($pageKey)
	{
		$timenow = vB::getRequest()->getTimeNow();

		if (empty($this->preCacheInfo) OR
			(($timenow - intval($this->lastpreCache)) < self::MIN_PRECACHELIFE)
		)
		{
			return;
		}
		$data = array('cachetime' => $timenow, 'data' => $this->preCacheInfo);

		vB_Cache::instance(vB_Cache::CACHE_LARGE)->write("vbPre_$pageKey", $data, 300);
	}

	/** This saves preload information for the current page.
	 *
	 *	@param	string	name of the api class
	 * 	@param	string	name of the api method that should be called
	 *	@param	mixed	array of method parameters that should be passed
	 *
	 ***/
	public function registerPrecacheInfo($apiClass, $method, $params)
	{
		//if we have cached within the last five minutes do nothing.
		if ((vB::getRequest()->getTimeNow() - intval($this->lastpreCache)) < self::MIN_PRECACHELIFE)
		{
			return;
		}

		if (!isset($this->preCacheInfo[$apiClass]))
		{
			$this->preCacheInfo[$apiClass] = array();
		}

		$this->preCacheInfo[$apiClass][$method] = $params;
	}

	/** This is used for setting a custom url to make sure that the new url is valid as a prefix
	 *
	 *	@param	string	$prefixCandidate	the 'resturl' to be checked
	 *	@return	boolean						true if no reserved characters are used in the url AND it is unique
	 */
	public function checkCustomUrl($prefixCandidate)
	{
		// Remove white space as well as any beginning/trailing forward slashes
		$prefixCandidate = trim($prefixCandidate, " \t\n\r\0\x0B/");

		// See in vBString::INVALID_CUSTOM_URL_CHAR: !@#$%^&*()+?:;"\'\\,.<>= []
		// Most of these characters are reserved. The period . character can cause issues with certain
		// clients cutting off the URL prematurely.
		// Even though / is 'reserved,' (see vB_String::getUrlIdent()) a custom url might include it
		// legitimately / ex: forum/main-category/cats.
		// However, since the routing system can't handle multiple /'s in a row, we check for repeated /'s
		$regex = '#[' . preg_quote(vB_String::INVALID_CUSTOM_URL_CHAR, '#') . ']|//#';
		$hasReservedChars = preg_match($regex, $prefixCandidate);

		// Note, since we disallow % at the moment, this means users can't use it legitimately to encode characters.
		// Maybe we need to re-think which characters are allowed for Custom URLs, and how to check/clean it.

		// if it contains no reserved characters, it's okay
		return !$hasReservedChars;
	}

	/**
	 * Returns the pagetemplate record given a pageid
	 * @param 	int		$pageid
	 * @return mixed	array with success=>true/false and usually an error array or a route class.
	 */
	public function fetchRouteClass($pageid)
	{
		//Note that we
		$page = $this->fetchPageById($pageid);
		$route = vB::getDbAssertor()->getRow('routenew', array('routeid' => $page['routeid']));

		if (empty($route))
		{
			return array('success' => false);
		}

		if (!empty($route['errors']))
		{
			return array('success' => false, 'errors' => $route['errors']);
		}
		return array('success' => true, 'routeclass' => $route['class']);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86093 $
|| #######################################################################
\*=========================================================================*/
