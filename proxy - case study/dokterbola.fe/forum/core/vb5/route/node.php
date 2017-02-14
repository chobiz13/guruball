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
 * This class is used as a proxy for the actual node route. It enables us to hide
 * the node title and URL path until we verify permissions.
 */
class vB5_Route_Node extends vB5_Route
{
	public function getUrl()
	{
		return "/{$this->prefix}/{$this->arguments['nodeid']}";
	}

	public function getCanonicalRoute()
	{
		if (!isset($this->canonicalRoute))
		{
			if (empty($this->arguments['nodeid']))
			{
				throw new vB_Exception_NodePermission();
			}
			
			$nodeApi = vB_Api::instanceInternal('node');
			
			try 
			{
				// this method will return an error if the user does not have permission	
				$node = $nodeApi->getNode($this->arguments['nodeid']);
			}
			catch(vB_Exception_Api $ex)
			{
				// throw the proper NodePermission exception to return a 403 status instead of a 500 internal error
				if($ex->has_errors('no_permission'))
				{
					throw new vB_Exception_NodePermission($this->arguments['nodeid']);
				}
				else
				{
					// otherwise, just let the caller catch the exception
					throw $ex;
				}
			}
			
			$contentApi = vB_Api_Content::getContentApi($node['contenttypeid']);
			if (!$contentApi->validate($node, vB_Api_Content::ACTION_VIEW, $node['nodeid'], array($node['nodeid'] => $node)))
			{
				throw new vB_Exception_NodePermission($node['nodeid']);
			}
			
			$parent = $nodeApi->getNode($node['starter']);
			$parent['innerPost'] = $this->arguments['nodeid'];
			
			$this->canonicalRoute = self::getRoute($node['routeid'], $parent, $this->queryParameters);
		}

		return $this->canonicalRoute;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
