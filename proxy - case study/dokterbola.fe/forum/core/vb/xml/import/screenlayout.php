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

class vB_Xml_Import_ScreenLayout extends vB_Xml_Import
{
	protected function import()
	{
		if (empty($this->parsedXML['page']))
		{
			$this->parsedXML['page'] = array();
		}

		// get all columns but the key
		$screenLayoutTable = $this->db->fetchTableStructure('screenlayout');
		$screenLayoutTableColumns = array_diff($screenLayoutTable['structure'], array($screenLayoutTable['key']));

		$phraseLib = vB_Library::instance('phrase');

		$screenLayouts = $this->parsedXML['screenlayout'];
		foreach ($screenLayouts AS $screenLayout)
		{
			// insert the screenlayout record
			$screenLayoutId = 0;
			$existing = $this->db->getRow('screenlayout', array('guid' => $screenLayout['guid']));

			if ($existing)
			{
				if ($this->options & self::OPTION_OVERWRITE)
				{
					// overwrite
					$guid = $screenLayout['guid'];
					unset($screenLayout['guid']);
					$this->db->update('screenlayout', $screenLayout, array('guid' => $guid));
				}

				$screenLayoutId = $existing['screenlayoutid'];
			}
			else
			{
				// insert new
				$screenLayoutId = $this->db->insert('screenlayout', $screenLayout);

				if (is_array($screenLayoutId))
				{
					$screenLayoutId = array_pop($screenLayoutId);
				}
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
