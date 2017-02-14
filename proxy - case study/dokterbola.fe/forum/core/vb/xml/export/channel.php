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

class vB_Xml_Export_Channel extends vB_Xml_Export
{
	protected $nonExportableFields = array('guid', 'routeid', 'contenttypeid', 'userid', 'parentid', 'lastcontent', 'lastcontentid', 'lastauthorid');
	
	public function getXml(vB_XML_Builder &$xml = NULL)
	{
		if (empty($xml))
		{
			$xml = new vB_XML_Builder();
			$returnString = TRUE;
		}
		else
		{
			$returnString = FALSE;
		}
		
		$xml->add_group('channels');
		
		$channelTable = $this->db->fetchTableStructure('vbforum:channel');
		$channelTableColumns = array_diff($channelTable['structure'], array('guid', $channelTable['key']));
		
		$nodeTable = $this->db->fetchTableStructure('vbforum:node');
		$nodeTableColumns = array_diff($nodeTable['structure'], array($nodeTable['key']), $this->nonExportableFields);
		
		$channels = $this->db->assertQuery('vbforum:getChannelInfoExport', array('productid' => $this->productid));
		
		if (!empty($channels))
		{
			foreach ($channels AS $channel)
			{
				$xml->add_group('channel', array('guid' => $channel['guid']));
				foreach ($channelTableColumns AS $column)
				{
					if ($channel[$column] != NULL)
					{
						$xml->add_tag($column, $channel[$column]);
					}
				}
				$xml->add_group('node');
				foreach ($nodeTableColumns as $column)
				{
					if ($channel[$column] != NULL)
					{
						$xml->add_tag($column, $channel[$column]);
					}
				}
				$xml->add_tag('routeguid', $channel['routeguid']);
				$xml->add_tag('parentguid', $channel['parentguid']);
				$xml->close_group();

				$xml->close_group();
			}
		}
		
		$xml->close_group();
		
		if ($returnString)
		{
			return $xml->fetch_xml();
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
