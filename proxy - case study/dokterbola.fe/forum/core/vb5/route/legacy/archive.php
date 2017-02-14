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

class vB5_Route_Legacy_Archive extends vB5_Route_Legacy_Page
{
	protected $prefix = 'archive/index.php';
	
	// archive/index.php does not have frendly URL
	protected function getNewRouteInfo()
	{
		// go to home page if path is exactly like prefix
		if (count($this->matches) == 1 AND empty($this->queryParameters))
		{
			$forumHomeChannel = vB_Library::instance('content_channel')->getForumHomeChannel();
			return $forumHomeChannel['routeid'];
		}
		// capture old id
		$argument = & $this->arguments;
		$oldid = $argument['oldid'];
		// calculate old contenttypeid
		$oldtype = array('t' => 'Thread', 'f' => 'Forum');
		$oldcontenttypeid = vB_Types::instance()->getContentTypeID(array('package' => 'vBForum', 'class' => $oldtype[$this->matches['nodetype']]));
		
		$node = vB::getDbAssertor()->getRow('vBForum:node', array(
			'oldid' => $oldid,
			'oldcontenttypeid' => $oldcontenttypeid
		));
		
		if (empty($node))
		{
			throw new vB_Exception_404('invalid_page');
		}
		
		$argument['nodeid'] = $node['nodeid'];
		return $node['routeid'];
	}
	
	public function getRegex()
	{
		return $this->prefix . '(?:/(?P<nodetype>t|f)-(?P<oldid>[1-9]\d*)(?:-p-(?P<pagenum>[1-9]\d*))?\.html)?';
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
