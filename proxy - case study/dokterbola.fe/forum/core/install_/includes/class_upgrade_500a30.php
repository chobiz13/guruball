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

class vB_Upgrade_500a30 extends vB_Upgrade_Version
{
	/*Constants=====================================================================*/

	/*Properties====================================================================*/

	/**
	* The short version of the script
	*
	* @var	string
	*/
	public $SHORT_VERSION = '500a30';

	/**
	* The long version of the script
	*
	* @var	string
	*/
	public $LONG_VERSION  = '5.0.0 Alpha 30';

	/**
	* Versions that can upgrade to this script
	*
	* @var	string
	*/
	public $PREV_VERSION = '5.0.0 Alpha 29';

	/**
	* Beginning version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_STARTS = '';

	/**
	* Ending version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_ENDS   = '';

	/** reseting blog pagetemplates to update blog sidebar **/
	public function step_1()
	{
		// we need to force the page template to be updated
		$blogPageTemplate = vB_Page::getBlogChannelPageTemplate();

		$db = vB::getDbAssertor();

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'));
		$db->delete('widgetinstance', array('pagetemplateid' => $blogPageTemplate));
		$db->delete('pagetemplate', array('pagetemplateid' => $blogPageTemplate));

		// import widgets and pagetemplates
		$this->show_message($this->phrase['final']['import_latest_widgets']);
		$widgetFile = DIR . '/install/vbulletin-widgets.xml';

		if (!($xml = file_read($widgetFile)))
		{
			$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-widgets.xml'), self::PHP_TRIGGER_ERROR, true);
			return;
		}
		
		$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-widgets.xml'));
		$xml_importer = new vB_Xml_Import_Widget();
		$xml_importer->importFromFile($widgetFile);
		$this->show_message($this->phrase['core']['import_done']);

		$pageTemplateFile = DIR . '/install/vbulletin-pagetemplates.xml';

		if (!($xml = file_read($pageTemplateFile)))
		{
			$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-pagetemplates.xml'), self::PHP_TRIGGER_ERROR, true);
			return;
		}
		
		$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-pagetemplates.xml'));
		$xml_importer = new vB_Xml_Import_PageTemplate('vbulletin', 0);
		$xml_importer->importFromFile($pageTemplateFile);

		// now update pages with new pagetemplate
		$newBlogPageTemplate = vB_Page::getBlogChannelPageTemplate();
		$db->update('page', array('pagetemplateid' => $newBlogPageTemplate), array('pagetemplateid' => $blogPageTemplate));
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
