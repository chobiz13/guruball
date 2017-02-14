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
 * vB_Api_Route
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Route extends vB_Api
{
	protected $whitelistPrefix = array('help', 'contact-us', 'lostpw', 'register', 'activateuser', 'activateemail', 'admincp');
	protected $whitelistRoute = array('admincp', 'auth/login');

	protected $disableWhiteList = array('getRoute', 'getUrls', 'preloadRoutes');

	protected function __construct()
	{
		parent::__construct();
	}

	public function GetSpecialRoutes()
	{
		/* Routes that should always give
		a no permission error if directly viewed
		They are mostly Top Level special channels */
		return array(
			'special',
			'special/reports'
		);
	}

	/**
	 * Returns the array of routes for the application
	 *
	 * @return 	array	The routes
	 */
	public function fetchAll()
	{
		$result = vB::getDbAssertor()->assertQuery(
			'routenew',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			)
		);

		$routes = array();
		if ($result->valid())
		{
			foreach ($result AS $route)
			{
				if (($unserialized = @unserialize($route['arguments'])) !== false)
				{
					$route['arguments'] = $unserialized;
				}
				else
				{
					$route['arguments'] = array();
				}
				$routes[$route['routeid']] = $route;
			}
		}

		//uasort($routes, array($this, '_sortRoutes'));

		return $routes;
	}

	/**
	 * Returns a matching route if available for $pathInfo
	 *
	 * @param string $pathInfo
	 * @param string $queryString
	 * @return vB_Frontend_Route
	 */
	public function getRoute($pathInfo, $queryString, $anchor = '')
	{
		static $closed;
		// clean the path if necessary
		$parsed = vB_String::parseUrl($pathInfo);
		$pathInfo = $parsed['path'];

		// check for any querystring to append
		if (!empty($parsed['query']))
		{
			if (!empty($queryString))
			{
				$queryString = $parsed['query'] . '&' . $queryString;
			}
			else
			{
				$queryString = $parsed['query'];
			}
		}

		if (empty($anchor) AND (!empty($parsed['anchor'])))
		{
			$anchor = $parsed['anchor'];
		}

		//Check for standard routes.
		if (is_string($pathInfo))
		{
			$common = vB5_Route::fetchCommonRoutes();

			if (isset($common[$pathInfo]))
			{
				//See if we have a match
				// pattern matching is case-insensitive
				$pattern = '#^' . $common[$pathInfo]['regex'] . '(?:/)?$#i';

				if (preg_match($pattern, $pathInfo, $matches))
				{
					$className = (isset($common[$pathInfo]['class']) AND !empty($common[$pathInfo]['class']) AND class_exists($common[$pathInfo]['class'])) ? $common[$pathInfo]['class'] : self::DEFAULT_CLASS;
					if ((!empty($common[$pathInfo]['arguments'])))
					{
						$common[$pathInfo]['arguments'] = unserialize($common[$pathInfo]['arguments']);
					}

					try
					{
						$route = new $className($common[$pathInfo], $matches, $queryString, $anchor);
					}
					catch (vB_Exception $ex)
					{
						return $this->handleRouteExceptions($ex);
					}
				}
			}

		}

		if ((!isset($route)))
		{
			// calculate prefixes set
			$prefixes = vB5_Route::getPrefixSet($pathInfo);

			// get matching routes
			$result = vB::getDbAssertor()->assertQuery('routenew', array('prefix' => $prefixes));

			if (in_array($result->db()->errno, $result->db()->getCriticalErrors()))
			{
				throw new Exception ('no_vb5_database');
			}

			$prefixMatches = array();

			foreach ($result AS $route)
			{
				if (($unserialized = @unserialize($route['arguments'])) !== false)
				{
					$route['arguments'] = $unserialized;
				}
				else
				{
					$route['arguments'] = array();
				}
				$prefixMatches[$route['routeid']] = $route;
			}
			unset($route);
		}

		// check for banned
		$bannedInfo = vB_Library::instance('user')->fetchBannedInfo(false);

		// get best route
		try
		{
			if (!isset($route))
			{
				$route = vB5_Route::selectBestRoute($pathInfo, $queryString, $anchor, $prefixMatches);
			}

			if ($route)
			{
				// Check if forum is closed
				$routeInfo = array(
					'routeguid' => $route->getRouteGuid(),
					'controller' => $route->getController(),
					'action' => $route->getAction(),
					'arguments' => $route->getArguments(),
				);

				$segments = $route->getRouteSegments();
				$cleanedRoute = implode('/', $segments);

				if(in_array($cleanedRoute, $this->GetSpecialRoutes()))
				{
					return array('no_permission' => 1);
				}


				//Always allow login and access to the admincp, even if closed.
				if (!in_array($cleanedRoute, $this->whitelistRoute))
				{
					if (!isset($closed))
					{
						if (vB_Cache::instance(vB_Cache::CACHE_FAST)->isLoaded('vB_State_checkBeforeView'))
						{
							$closed = vB_Cache::instance(vB_Cache::CACHE_FAST)->read('vB_State_checkBeforeView');
						}
						else
						{
							$closed = vB_Api::instanceInternal('state')->checkBeforeView($routeInfo);
						}
					}

					if ($closed !== false)
					{
						return array('forum_closed' => $closed['msg']);
					}
				}

				if ($bannedInfo['isbanned'])
				{
					return array('banned_info' => $bannedInfo);
				}

				$channelid = vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::MAIN_CHANNEL);
				if (! vB::getUserContext()->getChannelPermission('forumpermissions', 'canview', $channelid) )
				{
					$prefix = $route->getCanonicalPrefix();
					if (!in_array($prefix, $this->whitelistPrefix ))
					{
						if ($route->getPrefix() == 'admincp' OR $route->getPrefix() == 'modcp')
						{
							// do nothing really, just allow passage
						}
						else if ($route->getPrefix() == 'ajax')
						{
							$arguments = $route->getArguments();
							$allowedOptions = array(
								'/api/contactus/sendMail',
								'/api/hv/generateToken',
							);
							if (!isset($arguments['route']) OR !in_array($arguments['route'], $allowedOptions))
							{
								return array('no_permission' => 1);
							}
						}
						else
						{
							return array('no_permission' => 1);
						}
					}
				}

				if (is_array($route) AND (isset($route['no_permission']) OR isset($route['internal_error'])))
				{
					return $route;
				}
				$canonicalUrl = $route->getCanonicalUrl();
				$canonicalUrl = str_replace('&amp;', '&', $canonicalUrl);
				$canonicalPathInfo = ($canonicalUrl) ? vB_String::parseUrl($canonicalUrl, PHP_URL_PATH) : $pathInfo;
				$canonicalParam = $route->getCanonicalQueryParameters();
				if ($canonicalPathInfo AND $canonicalPathInfo{0} == '/')
				{
					$canonicalPathInfo = substr($canonicalPathInfo, 1);
				}

				$queryParams = $route->getQueryParameters();
				$routeId =  $route->getRouteId();
				// return routeid even for 301 redirects. Certain callers expect
				// this function to return the routeid in order to write a cache record
				if ($redirectId = $route->getRedirect301())
				{
					return array(
						'routeid' => $routeId,
						'redirect'	=> vB5_Route::buildUrl($redirectId, $route->getArguments(), $queryParams, $route->getAnchor()),
						'redirectRouteId' => $redirectId
					);
				}
				else if ($pathInfo != $canonicalPathInfo OR ($canonicalParam !== false AND $queryParams != $canonicalParam))
				{
					$hashtag = '';
					if (isset($queryParams['p']))
					{
						$hashtag = '#post' . $queryParams['p'];	// some browers do not preserve fragment during redirects, VBV-10255
					}
					return array(
						'routeid' => $routeId,
						'redirect' => $canonicalUrl . $hashtag,
						'redirectRouteId' => $routeId,
					);
				}
				else
				{
					return array(
						'routeid'         => $routeId,
						'routeguid'       => $route->getRouteGuid(),
						'controller'      => $route->getController(),
						'action'          => $route->getAction(),
						'template'        => $route->getTemplate(),
						'arguments'       => $route->getArguments(),
						'queryParameters' => $queryParams,
						'pageKey'         => $route->getPageKey(),
						'userAction'      => $route->getUserAction(),
						'breadcrumbs'     => $route->getBreadcrumbs(),
						'headlinks'		  => $route->getHeadLinks()
					);
				}
			}
			else
			{
				return false;
			}
		}

		//this is bad.  An API method should *not* be returning a php object of any sort.
		//Need to determine if this catch block is even appropriate.
		catch (vB_Exception $ex)
		{
			return $this->handleRouteExceptions($ex);
		}
	}

	/*
	 *	This function emulates the exception catch behavior of the old route generation
	 */
	protected function handleRouteExceptions(Exception $ex)
	{
		if ($ex instanceof vB_Exception_NodePermission)
		{
			// check for banned
			$bannedInfo = vB_Library::instance('user')->fetchBannedInfo(false);
			if (!$bannedInfo['isbanned'])
			{
				return array('no_permission' => 1);
			}
			else
			{
				return array('banned_info' => $bannedInfo);
			}
		}
		elseif ($ex instanceof vB_Exception_Api)
		{
			return array(
				'internal_error' => $ex
			);
		}
		elseif ($ex instanceof vB_Exception_404)
		{
			// we want to return a 404
			return false;
		}

		throw $ex;
	}

	/**
	 * Returns the route id for the generic conversation route
	 * @param int $channelId
	 * @return int
	 */
	public function getChannelConversationRoute($channelId)
	{
		if(empty($channelId))
		{
			return false;
		}

		$route = vB5_Route::getChannelConversationRouteInfo($channelId);

		if(empty($route))
		{
			return false;
		}

		return $route['routeid'];
	}

	/**
	 * Get fullURL of a node. It appends frontendurl to the relative node route.
	 *
	 * @param int $nodeid Node ID
	 * @param array $data Additional route data for the node
	 * @param array $extra Extra data for the route
	 *
	 * @return string Node's URL
	 */
	public function getAbsoluteNodeUrl($nodeid, $data = array(), $extra = array())
	{
		$node = vB_Api::instanceInternal('node')->getNode($nodeid);
		$data = array_merge($data, array('nodeid' => $node['nodeid']));

		return $this->getUrl($node['routeid'] . '|fullurl', $data, $extra);
	}

	/**
	 * Get URL of a node
	 *
	 * @param int $nodeid Node ID
	 * @param array $data Additional route data for the node
	 * @param array $extra Extra data for the route
	 *
	 * @return string Node's URL
	 */
	public function getNodeUrl($nodeid, $data = array(), $extra = array())
	{
		$node = vB_Api::instanceInternal('node')->getNode($nodeid);

		$data = array_merge($data, array('nodeid' => $node['nodeid']));

		return $this->getUrl($node['routeid'], $data, $extra);
	}

	/**
	 *
	 * @param mixed $route
	 * @param array $data
	 * @param array $extra
	 * @return string	Always in UTF-8. If vB_String::getCharset() is not utf-8, it's percent encoded.
	 */
	public function getUrl($route, array $data, array $extra)
	{
		return vB5_Route::buildUrl($route, $data, $extra);
	}

	/**
	 * get the urls in one batch
	 * @param array $URLInfoList has to contain the route, data and extra
	 * @return array URLs built based on the input
	 */
	public function getUrls($URLInfoList)
	{
		return vB5_Route::buildUrls($URLInfoList);
	}
	/**
	 *	get a unique hash
	 * @param mixed $route
	 * @param array $data
	 * @param array $extra
	 * @return string
	 */
	public function getHash($route, array $data, array $extra)
	{
		return vB5_Route::getHash($route, $data, $extra);
	}

	/**
	 * Saves a new route
	 *
	 * @param	string	Route class name
	 * @param	array	Route data
	 *(
	 * @return	mixed	The routeid will be returned
	 */
	public function createRoute($class, array $data)
	{
		return call_user_func(array($class, 'createRoute'), $class, $data);
	}

	/** Preloads a list of routes to reduce database traffic
	 *
	 * @param	mixed	array of route ids- can be integers or strings.
	 **/
	public function preloadRoutes($routeIds)
	{
		return vB5_Route::preloadRoutes($routeIds);
	}

	/**
	 * Writes debugging output to the filesystem for AJAX calls
	 *
	 * @param	mixed	Output to write
	 */
	protected function _writeDebugOutput($output)
	{
		$fname = dirname(__FILE__) . '/_debug_output.txt';
		file_put_contents($fname, $output);
	}

	/**
	 * Returns the URL for the legacy postid
	 * @param int $postId
	 * @return mixed
	 */
	public function fetchLegacyPostUrl($postId)
	{
		$nodeInfo = vB::getDbAssertor()->getRow('vBForum:fetchLegacyPostIds', array(
			'oldids' => $postId,
			'postContentTypeId' => vB_Types::instance()->getContentTypeID('vBForum_Post'),
		));

		if ($nodeInfo)
		{
			return vB5_Route::buildUrl('node|fullurl', $nodeInfo);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the URL for the legacy threadid
	 * @param int $threadId
	 * @return mixed
	 */
	public function fetchLegacyThreadUrl($threadId)
	{
		$nodeInfo = vB::getDbAssertor()->getRow('vBForum:node', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::COLUMNS_KEY => array('nodeid', 'starter', 'routeid'),
			vB_dB_Query::CONDITIONS_KEY => array(
				'oldid' => $threadId,
				'oldcontenttypeid' => vB_Types::instance()->getContentTypeID('vBForum_Thread')
			)
		));

		if ($nodeInfo)
		{
			return vB5_Route::buildUrl('node|fullurl', $nodeInfo);
		}
		else
		{
			return false;
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89551 $
|| #######################################################################
\*=========================================================================*/
