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

class vB5_Route_Subscription extends vB5_Route
{
	const REGEXP = '(?P<userid>[0-9]+)(?P<username>(-[^\?]*)*)/(?P<tab>subscriptions|subscribers|groups)(?:/page(?P<pagenum>[0-9]+))?';

	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		parent::__construct($routeInfo, $matches, $queryString, $anchor);
		$this->arguments['userid'] = intval($this->arguments['userid']);
		$userInfo = vB_Api::instanceInternal('user')->fetchProfileInfo($this->arguments['userid']);
		if (empty($this->arguments['userid']))
		{
			$this->arguments['userid'] = $userInfo['userid'];
			$this->arguments['username'] = $userInfo['username'];
		}
		else if (empty($this->arguments['username']))
		{
			$this->arguments['username'] = $userInfo['username'];
		}

		if ($this->arguments['tab'] == 'subscriptions' AND !$userInfo['showSubscriptions'])
		{
			throw new vB_Exception_NodePermission('subscriptions');
		}
		else if($this->arguments['tab'] == 'subscribers' AND !$userInfo['showSubscribers'])
		{
			throw new vB_Exception_NodePermission('subscribers');
		}

		$this->breadcrumbs = array(
			0 => array(
				'title' => $this->arguments['username'],
				'url' => vB5_Route::buildUrl('profile', array('userid' => $this->arguments['userid'], 'username' => vB_String::getUrlIdent($this->arguments['username'])))
			),
			1 => array(
				'phrase' => $this->arguments['tab'],
				'url' => ''
			)
		);

	}

	public function getUrl()
	{
		if (empty($this->arguments['username']))
		{
			$userInfo = vB_Api::instanceInternal('user')->fetchProfileInfo($this->arguments['userid']);
			$this->arguments['username'] = $userInfo['username'];
		}

		// the regex contains the url
		$url = '/' . $this->prefix . '/' . $this->arguments['userid'] . '-' . vB_String::getUrlIdent($this->arguments['username']) . '/' . $this->arguments['tab'];

		if (isset($this->arguments['pagenum']) AND is_numeric($this->arguments['pagenum']) AND $this->arguments['pagenum'] > 1)
		{
			$url .= '/page' . intval($this->arguments['pagenum']);
		}

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}

		return $url;
	}

	public function getCanonicalRoute()
	{
		if (!isset($this->canonicalRoute))
		{
			$page = vB::getDbAssertor()->getRow('page', array('pageid'=>$this->arguments['pageid']));
			$this->canonicalRoute = self::getRoute($page['routeid'], $this->arguments, $this->queryParameters);
		}

		return $this->canonicalRoute;
	}

	protected static function validInput(array &$data)
	{
		if (
				!isset($data['pageid']) OR !is_numeric($data['pageid']) OR
				!isset($data['prefix'])
			)
		{
			return false;
		}

		$data['regex'] = $data['prefix'] . '/' . self::REGEXP;
		$data['class'] = __CLASS__;
		$data['controller']	= 'page';
		$data['action']		= 'index';

		$arguments = unserialize($data['arguments']);
		if (!$arguments)
		{
			return false;
		}

		$arguments['pageid'] = $data['pageid'];
		$data['arguments']	= serialize($arguments);

		$result = parent::validInput($data);
		return $result;
	}

	protected static function updateContentRoute($oldRouteInfo, $newRouteInfo)
	{
		$db = vB::getDbAssertor();
		$events = array();

		// update redirect301 fields
		$updateIds = $db->assertQuery('get_update_route_301', array('oldrouteid' => $oldRouteInfo['routeid']));

		if (!empty($updateIds))
		{
			$routeIds = array();
			foreach($updateIds AS $route)
			{
				$routeid = $route['routeid'];
				$events[] = "routeChg_$routeid";
				$routeIds[] = $routeid;
			}

			$db->update('routenew', array('redirect301' => $newRouteInfo['routeid'], 'name' => vB_dB_Query::VALUE_ISNULL), array('routeid' => $routeIds));
		}

		// don't modify the routeid for default pages, as it will still be used
		$updateIds = $db->assertQuery('page', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::COLUMNS_KEY => array('pageid'),
			vB_dB_Query::CONDITIONS_KEY => array('routeid' => $oldRouteInfo['routeid'], 'pagetype' => vB_Page::TYPE_CUSTOM),
		));

		if (!empty($updateIds))
		{
			$pageIds = array();
			foreach($updateIds AS $page)
			{
				$pageid = $page['pageid'];
				$events[] = "pageChg_$pageid";
				$pageIds[] = $pageid;
			}
			$db->update('page', array('routeid' => $newRouteInfo['routeid']), array('pageid' => $pageIds));
		}

		vB_Cache::allCacheEvent($events);
	}

	public static function exportArguments($arguments)
	{
		$data = unserialize($arguments);

		$page = vB::getDbAssertor()->getRow('page', array('pageid' => $data['pageid']));
		if (empty($page))
		{
			throw new Exception('Couldn\'t find page');
		}
		$data['pageGuid'] = $page['guid'];
		unset($data['pageid']);

		return serialize($data);
	}

	public static function importArguments($arguments)
	{
		$data = unserialize($arguments);

		$page = vB::getDbAssertor()->getRow('page', array('guid' => $data['pageGuid']));
		if (empty($page))
		{
			throw new Exception('Couldn\'t find page');
		}
		$data['pageid'] = $page['pageid'];
		unset($data['pageGuid']);

		return serialize($data);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83644 $
|| ####################################################################
\*======================================================================*/
