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

class vB_Xml_Import_PageTemplate extends vB_Xml_Import
{
	/**
	 * Widgets referenced by instances in the imported template
	 * @var array
	 */
	protected $referencedWidgets = array();

	/**
	 * Checks if all referenced widgets are already defined
	 * Also sets referencedWidgets class attribute to be used while importing
	 */
	protected function checkWidgets()
	{
		$requiredWidgets = array();

		$pageTemplates = is_array($this->parsedXML['pagetemplate'][0]) ? $this->parsedXML['pagetemplate'] : array($this->parsedXML['pagetemplate']);
		foreach ($pageTemplates AS $pagetemplate)
		{
			if (isset($pagetemplate['widgets']))
			{

				//We can get a single widget definition, or nothing, or an array of widget definitions.
				if (empty($pagetemplate['widgets']['widgetinstance']) OR !is_array($pagetemplate['widgets']['widgetinstance']))
				{
					continue;
				}
				else if (!empty($pagetemplate['widgets']['widgetinstance']['widgetguid']))
				{
					$widgetInstances = array($pagetemplate['widgets']['widgetinstance']);
				}
				else if (empty($pagetemplate['widgets']['widgetinstance'][0]))
				{
					continue;
				}
				else
				{
					$widgetInstances = $pagetemplate['widgets']['widgetinstance'];
				}

				foreach ($widgetInstances AS $instance)
				{
					$requiredWidgets[] = $instance['widgetguid'];

					if (isset($instance['subModules']))
					{
						if (empty($instance['subModules']['widgetinstance']) OR !is_array($instance['subModules']['widgetinstance']))
						{
							continue;
						}
						else if (!empty($instance['subModules']['widgetinstance']['widgetguid']))
						{
							$subModules = array($instance['subModules']['widgetinstance']);
						}
						else if (empty($instance['subModules']['widgetinstance'][0]))
						{
							continue;
						}
						else
						{
							$subModules = $instance['subModules']['widgetinstance'];
						}

						foreach ($subModules as $subModule)
						{
							$requiredWidgets[] = $subModule['widgetguid'];
						}
					}
				}
			}
		}

		$existingWidgets = $this->db->getRows('widget', array('guid' => $requiredWidgets));

		foreach ($existingWidgets AS $widget)
		{
			$this->referencedWidgets[$widget['guid']] = $widget;
		}

		$missingWidgets = array_diff($requiredWidgets, array_keys($this->referencedWidgets));

		if (!empty($missingWidgets))
		{
			throw new Exception('Reference to undefined widget(s): ' . implode(' ', $missingWidgets));
		}
	}

	protected function import($onlyGuid = false)
	{
		if (empty($this->parsedXML['pagetemplate']))
		{
			$this->parsedXML['pagetemplate'] = array();
		}

		$this->checkWidgets();

		// get all columns but the key
		$pageTemplateTable = $this->db->fetchTableStructure('pagetemplate');
		$pageTemplateTableColumns = array_diff($pageTemplateTable['structure'], array($pageTemplateTable['key']));

		$widgetInstanceTable = $this->db->fetchTableStructure('widgetinstance');
		$widgetInstanceTableColumns = array_diff($widgetInstanceTable['structure'], array($pageTemplateTable['key'], $widgetInstanceTable['key']));

		$pageTemplates = is_array($this->parsedXML['pagetemplate'][0]) ? $this->parsedXML['pagetemplate'] : array($this->parsedXML['pagetemplate']);

		// get the config items defined for each widget
		$widgetDefinitionCache = array();
		$widgetRows = vB::getDbAssertor()->getRows('widget');
		$widgetGuids = array();
		foreach ($widgetRows AS $widgetRow)
		{
			$widgetGuids[$widgetRow['widgetid']] = $widgetRow['guid'];
		}
		$widgetDefRows = vB::getDbAssertor()->getRows('widgetdefinition');
		foreach ($widgetDefRows AS $widgetDefRow)
		{
			$widgetGuid = $widgetGuids[$widgetDefRow['widgetid']];
			if (!isset($widgetDefinitionCache[$widgetGuid]))
			{
				$widgetDefinitionCache[$widgetGuid] = array();
			}

			// replace any "phrase:" placeholders in widget definitions
			$widgetDefRow = $this->replacePhrasePlaceholdersInWidgetConfig($widgetDefRow);

			$widgetDefinitionCache[$widgetGuid][] = $widgetDefRow;
		}
		unset($widgetDefRows, $widgetDefRow, $widgetRows, $widgetRow, $widgetRows, $widgetGuids, $widgetGuid, $phrases);

		// get screenlayoutguid => screenlayoutid lookup table
		$screenLayoutLookup = array();
		$screenLayouts = $this->db->assertQuery('screenlayout', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));
		foreach ($screenLayouts AS $screenLayout)
		{
			$screenLayoutLookup[$screenLayout['guid']] = $screenLayout['screenlayoutid'];
		}

		foreach ($pageTemplates AS $pageTemplate)
		{
			if ($onlyGuid AND $onlyGuid != $pageTemplate['guid'])
			{
				continue;
			}

			$values = array();
			foreach($pageTemplateTableColumns AS $col)
			{
				if (isset($pageTemplate[$col]))
				{
					$values[$col] = $pageTemplate[$col];
				}
			}

			/*
			 * Note, it wasn't trivial to just add finalUpgrade->step_5() before every instance of
			 * finalUpgrade->step_6(), which is what's needed for the below check to NOT block upgrades.
			 * I'm leaving this commented instead of just removing so that if people run into the issue
			 * of pagetemplate.screenlayoutid = 0 in the future (VBV-13771), they have a starting point
			 * into looking at what happened/how we solved the issue in the past. Also see
			 * class_upgrade_514b3->step_2()
			// Ensure screenlayoutid exists. This may happen if upgrade final step_6 (import pagetemplates XML)
			// is called before step_5 (import screenlayouts XML).
			if (!isset($screenLayoutLookup[$pageTemplate['screenlayoutguid']]))
			{
				throw new vB_Exception_Api(
					'missing_screenlayoutid_for_guid_x_pagetemplate_y',
					array(
						$pageTemplate['screenlayoutguid'],
						$pageTemplate['title']
					)
				);
			}
			*/
			// add screenlayoutid, use guid if available
			if (!empty($pageTemplate['screenlayoutguid']) AND !empty($screenLayoutLookup[$pageTemplate['screenlayoutguid']]))
			{
				$values['screenlayoutid'] = $screenLayoutLookup[$pageTemplate['screenlayoutguid']];
			}
			else if (!empty($pageTemplate['screenlayoutid']))
			{
				$values['screenlayoutid'] = $pageTemplate['screenlayoutid'];
			}
			else
			{
				$values['screenlayoutid'] = 0;
			}

			$pageTemplateId = $oldTemplateId = 0;
			$condition = array('guid' => $pageTemplate['guid']);
			if ($oldPageTemplate = $this->db->getRow('pagetemplate', $condition))
			{
				if ($this->options & self::OPTION_OVERWRITE)
				{
					$oldTemplateId = $oldPageTemplate['pagetemplateid'];

					// overwrite preexisting record
					$this->db->delete('pagetemplate', $condition);
				}
				else
				{
					$pageTemplateId = $oldPageTemplate['pagetemplateid'];
				}
			}

			if (empty($pageTemplateId))
			{
				$pageTemplateId = $this->db->insertIgnore('pagetemplate', $values);
			}

			if (is_array($pageTemplateId))
			{
				$pageTemplateId = array_pop($pageTemplateId);
			}

			// continue only if the widget could be inserted
			if ($pageTemplateId)
			{
				if ($oldTemplateId AND ($pageTemplateId != $oldTemplateId))
				{
					// update pages that point to the old templateid
					$this->db->update('page', array('pagetemplateid' => $pageTemplateId), array('pagetemplateid' => $oldTemplateId));
				}

				if ($this->options & self::OPTION_OVERWRITE)
				{
					// if we are overwriting the template with the same templateid, remove associated widget instances
					$this->db->delete('widgetinstance', array('pagetemplateid' => $pageTemplateId));
				}

				if (isset($pageTemplate['widgets']) AND
						(
							/* page template is new */
							(!$oldPageTemplate) OR
							/* we set the addwidgets flag */
							($this->options & self::OPTION_ADDWIDGETS)
						)
				   )
				{
					$widgets = is_array($pageTemplate['widgets']['widgetinstance'][0]) ? $pageTemplate['widgets']['widgetinstance'] : array($pageTemplate['widgets']['widgetinstance']);

					foreach ($widgets AS $widget)
					{
						$values = array();
						foreach($widgetInstanceTable['structure'] AS $col)
						{
							if (isset($widget[$col]))
							{
								if ($col == 'adminconfig' AND $widget[$col] != '')
								{
									// default admin config values are defined for this widget instance
									// in the vbulletin-pagetemplates.xml file. When setting these, make
									// sure we also pull in any additional default config items
									// for this widget
									$adminConfig = $widget[$col];
									if (($temp = unserialize($adminConfig)) !== false)
									{
										$adminConfig = $temp;
										unset($temp);

										// There are channel GUIDs in the serialized admin configs
										// these will be replaced with channel IDs in
										// replaceChannelGuidsInWidgetConfigs after channels have been
										// imported.

										// Replace "phrase:" placeholders in admin config
										$adminConfig = $this->replacePhrasePlaceholdersInWidgetConfig($adminConfig);
									}
									$defaultConfig = array();
									$configItems = $widgetDefinitionCache[$widget['widgetguid']];

									if (!empty($configItems))
									{
										foreach ($configItems AS $configItem)
										{
											if (!empty($configItem['name']))
											{
												// Set default value
												// Keep this in sync with the corresponding code
												// in vB_Xml_Import_Widget::updateInstanceAdminConfigs
												if (!empty($configItem['defaultvalue']))
												{
													// defaultvalue can be a serialized string
													$temp = @unserialize($configItem['defaultvalue']);
													if ($temp !== false)
													{
														$configItem['defaultvalue'] = $temp;
													}
												}

												$defaultConfig[$configItem['name']] = $configItem['defaultvalue'];
											}
										}
									}
									unset($configItems, $configItem, $temp);

									$values[$col] = serialize($adminConfig + $defaultConfig);
								}
								else
								{
									$values[$col] = $widget[$col];
								}
							}
						}
						$values['widgetid'] = $this->referencedWidgets[$widget['widgetguid']]['widgetid'];
						$values['pagetemplateid'] = $pageTemplateId;
						$widgetInstanceId = $this->db->insert('widgetinstance', $values);
						if (is_array($widgetInstanceId))
						{
							$widgetInstanceId = array_pop($widgetInstanceId);
						}

						if (isset($widget['subModules']))
						{
							$subModules = is_array($widget['subModules']['widgetinstance'][0]) ? $widget['subModules']['widgetinstance'] : array($widget['subModules']['widgetinstance']);

							foreach($subModules AS $widget)
							{
								$values = array();
								foreach($widgetInstanceTable['structure'] AS $col)
								{
									if (isset($widget[$col]))
									{
										$values[$col] = $widget[$col];
									}
								}
								$values['containerinstanceid'] = $widgetInstanceId;
								$values['widgetid'] = $this->referencedWidgets[$widget['widgetguid']]['widgetid'];
								$values['pagetemplateid'] = $pageTemplateId;
								$this->db->insert('widgetinstance', $values);
							}
						}
					}
				}
			}

			vB_Xml_Import::setImportedId(vB_Xml_Import::TYPE_PAGETEMPLATE, $pageTemplate['guid'], $pageTemplateId);

			// Insert phrases for pagetemplate title.
			$phraseLib = vB_Library::instance('phrase');
			$guidforphrase = $phraseLib->cleanGuidForPhrase($pageTemplate['guid']);
			$phraseLib->save('pagemeta',
				'pagetemplate_' . $guidforphrase . '_title',
				array(
					'text' => array($pageTemplate['title']),
					'ismaster' => 1,
					'product' => 'vbulletin',
					't' => 0,
					'oldvarname' => 'pagetemplate_' . $guidforphrase . '_title',
					'oldfieldname' => 'pagemeta',
				)
			);

		}
	}

	/**
	 * Replaces the text "channelguid:<GUID>" in any config
	 * options with the actual channel nodeid. This must be
	 * called after channels are imported.
	 */
	public function replaceChannelGuidsInWidgetConfigs()
	{
		// Replace channel guid in widget instance configurations with the channel id.
		// This could be done in vB_Xml_Import_PageTemplate::import, except that
		// during installation, page templates are imported before channels are.

		$widgetInstances = $this->db->getRows('widgetinstance');
		$guids = array();
		$updates = array();

		// find which widget instances need to be updated and get the channel guids
		foreach ($widgetInstances AS $widgetInstance)
		{
			$adminConfig = $widgetInstance['adminconfig'];
			if (($adminConfig = unserialize($adminConfig)) !== false)
			{
				foreach ($adminConfig AS $k => $v)
				{
					if (is_string($v) AND substr($v, 0, 12) == 'channelguid:')
					{
						$guid = substr($v, 12);
						$guids[$guid] = true;
						$updates[$widgetInstance['widgetinstanceid']] = $adminConfig;
					}
				}
			}
		}

		// get the channel ids and update the widget instance configurations with them
		if (!empty($updates))
		{
			$channelIdLookup = array();
			$channels = $this->db->getRows('vBForum:channel', array('guid' => array_keys($guids)));
			if ($channels)
			{
				foreach ($channels AS $channel)
				{
					$channelIdLookup[$channel['guid']] = $channel['nodeid'];
				}

				foreach ($updates AS $widgetInstanceId => $adminConfig)
				{
					foreach ($adminConfig AS $k => $v)
					{
						if (is_string($v) AND substr($v, 0, 12) == 'channelguid:')
						{
							$guid = substr($v, 12);
							$adminConfig[$k] = $channelIdLookup[$guid];
						}
					}

					// Replace "phrase:" placeholders in admin config
					$adminConfig = $this->replacePhrasePlaceholdersInWidgetConfig($adminConfig);

					$adminConfig = serialize($adminConfig);

					$this->db->update('widgetinstance', array('adminconfig' => $adminConfig), array('widgetinstanceid' => $widgetInstanceId));
				}
			}
		}
	}

	/**
	 * Replaces the "phrase:<phrasevarname>" placeholders in widget config
	 * items with the actual phrase.
	 *
	 * @param	array	Array of widget config items
	 *
	 * @return	array	The array of widget config items with phrases replaced.
	 */
	protected function replacePhrasePlaceholdersInWidgetConfig(array $widgetConfig)
	{
		$phrases = array();

		foreach ($widgetConfig AS $k => $v)
		{
			if (is_string($v) AND substr($v, 0, 7) == 'phrase:')
			{
				$phrases[substr($v, 7)] = true;
			}
		}

		if (!empty($phrases))
		{
			$phrases = vB_Api::instanceInternal('phrase')->fetch(array_keys($phrases));

			foreach ($widgetConfig AS $k => $v)
			{
				if (is_string($v) AND substr($v, 0, 7) == 'phrase:' AND !empty($phrases[substr($v, 7)]))
				{
					$widgetConfig[$k] = $phrases[substr($v, 7)];
				}
			}
		}

		return $widgetConfig;
	}

	/**
	 * Replaces the text "phrase:<phrasevarname>" in any config
	 * options with the actual phrase text.
	 *
	 * This replaces placeholders in any widget config, even ones
	 * that already exist, which is why it needs to be done independently
	 * of the import, which does not overwrite existing widget instances.
	 */
	public function replacePhrasePlaceholdersInWidgetConfigs()
	{
		$widgetInstances = $this->db->getRows('widgetinstance');

		// find which widget instances that require it
		foreach ($widgetInstances AS $widgetInstance)
		{
			$adminConfig = $widgetInstance['adminconfig'];
			if (($adminConfig = unserialize($adminConfig)) !== false)
			{
				// Replace "phrase:" placeholders in admin config
				$newAdminConfig = $this->replacePhrasePlaceholdersInWidgetConfig($adminConfig);

				if ($adminConfig != $newAdminConfig)
				{
					// save the update if a phrase was replaced
					$newAdminConfig = serialize($newAdminConfig);
					$this->db->update('widgetinstance', array('adminconfig' => $newAdminConfig), array('widgetinstanceid' => $widgetInstance['widgetinstanceid']));
				}
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88166 $
|| #######################################################################
\*=========================================================================*/
