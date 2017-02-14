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

class vB_DataManager_StyleVarBackground extends vB_DataManager_StyleVar
{
	var $childfields = array(
		'image'           => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_image'),
		'color'           => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD),
		'repeat'          => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_repeat'),
		'units'           => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_units'),
		'x'               => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_background_position'),
		'y'               => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_background_position'),
		'stylevar_image'  => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_value_stylevar'),
		'stylevar_color'  => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_value_stylevar'),
		'stylevar_repeat' => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_value_stylevar'),
		'stylevar_units'  => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_value_stylevar'),
		'stylevar_x'      => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_value_stylevar'),
		'stylevar_y'      => array(vB_Cleaner::TYPE_STR, vB_DataManager_Constants::REQ_NO, vB_DataManager_Constants::VF_METHOD,	'verify_value_stylevar'),
	);

	public $datatype = 'Background';

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85464 $
|| #######################################################################
\*=========================================================================*/
