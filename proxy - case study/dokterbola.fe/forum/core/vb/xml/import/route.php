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

class vB_Xml_Import_Route extends vB_Xml_Import
{
	protected function import($onlyGuid = false)
	{
		// get all columns but the key
		$routeTable = $this->db->fetchTableStructure('routenew');
		$routeTableColumns = array_diff($routeTable['structure'], array('arguments', 'contentid', $routeTable['key']));

		if (empty($this->parsedXML['route']))
		{
			$this->parsedXML['route'] = array();
		}

		$routes = is_array($this->parsedXML['route'][0]) ? $this->parsedXML['route'] : array($this->parsedXML['route']);

		$redirects = array();
		foreach ($routes AS $route)
		{
			if ($onlyGuid AND $onlyGuid != $route['guid'])
			{
				continue;
			}

			$values = array();
			foreach($routeTableColumns AS $col)
			{
				if (isset($route[$col]))
				{
					$values[$col] = $route[$col];
				}
			}

			//this is a guid in the xml rather than an id which the db wants.
			//we can't look it up now because we might not have seen that route yet.
			if (isset($values['redirect301']))
			{
				$redirects[$route['guid']] = $values['redirect301'];
				unset($values['redirect301']);
			}

			if (!isset($route['class']))
			{
				$values['class'] = '';
			}
			$condition = array('guid' => $route['guid']);
			$existing = $this->db->getRow('routenew', $condition);

			if ($existing AND !empty($existing['routeid']))
			{
				//If we have a route with this guid we leave it alone. The customer may have intentionally changed it
				//see VBV-13586.
				$routeid = $existing['routeid'];
			}
			else
			{
				$class = (isset($route['class']) AND !empty($route['class']) AND class_exists($route['class'])) ? $route['class'] : vB5_Route::DEFAULT_CLASS;
				$values['arguments'] = call_user_func_array(array($class, 'importArguments'), array($route['arguments']));
				$values['contentid'] = call_user_func_array(array($class, 'importContentId'), array(unserialize($values['arguments'])));

				$routeid = $this->db->insertIgnore('routenew', $values);
				//We need to make sure the name is unique. Collisions should be very rare but not impossible.

				if (is_array($routeid))
				{
					$routeid = array_pop($routeid);
				}
			}

			vB_Xml_Import::setImportedId(vB_Xml_Import::TYPE_ROUTE, $route['guid'], $routeid);
		}

		if(count($redirects))
		{
			$map = array();
			$routes = $this->db->select('routenew', array('guid' => $redirects), array('routeid', 'guid'));
			foreach ($routes AS $route)
			{
				$map[$route['guid']] = $route['routeid'];
			}

			foreach($redirects AS $source => $dest)
			{
				if (isset($map[$dest]))
				{
				 	$this->db->update('routenew', array('redirect301' => $map[$dest]), array('guid' => $source));
				}
				else
				{
					throw new Exception("Could not find redirect route '$dest' for route '$source'");
				}
			}
		}
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87251 $
|| #######################################################################
\*=========================================================================*/
