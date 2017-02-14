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

class vB_Xml_Import_Page extends vB_Xml_Import
{
	/**
	 * Widgets referenced by instances in the imported template
	 * @var array
	 */
	protected $referencedTemplates;

	/**
	 * Checks if all referenced widgets are already defined
	 * Also sets referencedWidgets class attribute to be used while importing
	 */
	protected function checkTemplates()
	{
		$requiredTemplates = array();

		$pages = is_array($this->parsedXML['page'][0]) ? $this->parsedXML['page'] : array($this->parsedXML['page']);
		foreach ($pages AS $page)
		{
			$requiredTemplates[] = $page['pageTemplateGuid'];
		}

		$existingPageTemplates = $this->db->getRows('pagetemplate', array('guid' => $requiredTemplates));
		foreach ($existingPageTemplates AS $pagetemplate)
		{
			$this->referencedTemplates[$pagetemplate['guid']] = $pagetemplate;
		}

		$missingTemplates = array_diff($requiredTemplates, array_keys($this->referencedTemplates));
		if (!empty($missingTemplates))
		{
			throw new Exception('Reference to undefined template(s): ' . implode(' ', $missingTemplates));
		}
	}

	protected function import($onlyGuid = false)
	{
		if (empty($this->parsedXML['page']))
		{
			$this->parsedXML['page'] = array();
		}

		$this->checkTemplates();

		// get all columns but the key
		$pageTable = $this->db->fetchTableStructure('page');
		$pageTableColumns = array_diff($pageTable['structure'], array($pageTable['key']));

		$pages = is_array($this->parsedXML['page'][0]) ? $this->parsedXML['page'] : array($this->parsedXML['page']);

		$phraseLib = vB_Library::instance('phrase');

		foreach ($pages AS $page)
		{
			if ($onlyGuid AND $onlyGuid != $page['guid'])
			{
				continue;
			}

			$values = array();
			foreach($pageTableColumns AS $col)
			{
				if (isset($page[$col]))
				{
					$values[$col] = $page[$col];
				}
			}
			$values['pagetemplateid'] = $this->referencedTemplates[$page['pageTemplateGuid']]['pagetemplateid'];

			if (isset($page['parentGuid']) AND !empty($page['parentGuid']))
			{
				$parent = $this->db->getRow('page', array('guid' => $page['parentGuid']));

				if ($parent)
				{
					$values['parentid'] = $parent['pageid'];
				}
				else if (!($this->options & vB_Xml_Import::OPTION_IGNOREMISSINGPARENTS))
				{
					throw new Exception('Couldn\'t find parent while attempting to import page ' . $page['guid']);
				}
			}

			$existingPage = $this->db->getRow('page', array('guid' => $page['guid']));
			if ($existingPage)
			{
				$pageId = $existingPage['pageid'];
				if ($this->options & self::OPTION_OVERWRITE)
				{
					$this->db->update('page', $values, array('pageid' => $pageId));
				}
			}
			else
			{
				$pageId = $this->db->insert('page', $values);
			}

			if (is_array($pageId))
			{
				$pageId = array_pop($pageId);
			}

			vB_Xml_Import::setImportedId(vB_Xml_Import::TYPE_PAGE, $page['guid'], $pageId);

			// Insert phrases for page title, meta description.
			$guidforphrase = vB_Library::instance('phrase')->cleanGuidForPhrase($page['guid']);
			$productid = (!empty($page['product']) ? $page['product'] : 'vbulletin');
			$phraseLib->save('pagemeta',
				'page_' . $guidforphrase . '_title',
				array(
					'text' => array($page['title']),
					'ismaster' => 1,
					'product' => $productid,
					't' => 0,
					'oldvarname' => 'page_' . $guidforphrase . '_title',
					'oldfieldname' => 'global',
				)
			);

			$phraseLib->save('pagemeta',
				'page_' . $guidforphrase . '_metadesc',
				array(
					'text' => array($page['metadescription']),
					'ismaster' => 1,
					'product' => $productid,
					't' => 0,
					'oldvarname' => 'page_' . $guidforphrase . '_metadesc',
					'oldfieldname' => 'global',
				)
			);
		}
	}

	public function updatePageRoutes($xml = false)
	{
		if ($xml)
		{
			$this->parsedXML = $xml;
		}

		$currentPages = $this->db->assertQuery('page', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));
		$existingPage = array();
		foreach($currentPages AS $pageInfo)
		{
			$existingPage[$pageInfo['guid']] = $pageInfo['pageid'];
		}

		$existingRoute = array();
		$currentRoutes = $this->db->assertQuery('routenew', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));
		foreach($currentRoutes AS $routeInfo)
		{
			$existingRoute[$routeInfo['guid']] = $routeInfo['routeid'];
		}

		$pages = is_array($this->parsedXML['page'][0]) ? $this->parsedXML['page'] : array($this->parsedXML['page']);

		foreach ($pages AS $page)
		{

			if (isset($existingPage[$page['guid']]) AND isset($existingRoute[$page['routeGuid']]))
			{
				$this->db->update(
					'page',
					array('routeid' => $existingRoute[$page['routeGuid']]),
					array('pageid'	=> $existingPage[$page['guid']])
				);
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86975 $
|| #######################################################################
\*=========================================================================*/
