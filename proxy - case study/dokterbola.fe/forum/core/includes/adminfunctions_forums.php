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

// ###################### Start permboxes #######################
function print_channel_permission_rows($customword, $channelpermission = array(), $extra = '')
{
	global $vbphrase;

	print_label_row(
		"<b>$customword</b>",'
		<input type="button" class="button" value="' . $vbphrase['all_yes'] . '" onclick="' . iif($extra != '', 'if (js_set_custom()) { ') . ' js_check_all_option(this.form, 1);' . iif($extra != '', ' }') . '" class="button" />
		<input type="button" class="button" value=" ' . $vbphrase['all_no'] . ' " onclick="' . iif($extra != '', 'if (js_set_custom()) { ') . ' js_check_all_option(this.form, 0);' . iif($extra != '', ' }') . '" class="button" />
		<!--<input type="submit" class="button" value="Okay" class="button" />-->
	', 'tcat', 'middle');

	// Load permissions
	require_once(DIR . '/includes/class_bitfield_builder.php');

	$bitvalues = array('forumpermissions', 'forumpermissions2', 'moderatorpermissions', 'createpermissions');
	$permFields = vB_ChannelPermission::fetchPermFields();
	$permPhrases = vB_ChannelPermission::fetchPermPhrases();

	if (empty($channelpermission))
	{
		// we need the defaults to be displayed
		$channelpermission = vB_ChannelPermission::instance()->fetchPermissions(1);
		$channelpermission = current($channelpermission);
	}



	foreach($permFields AS $permField => $type)
	{

		//Do the non-bitmap fields first.
		switch ($type)
		{
			case vB_ChannelPermission::TYPE_HOURS :
			case vB_ChannelPermission::TYPE_COUNT :
				$permvalue = $channelpermission[$permField];
				print_input_row($vbphrase[$permPhrases[$permField]], $permField, $permvalue, true, 35, 0, '', false, 'channelPerm_' . $permField);
				break;

			case vB_ChannelPermission::TYPE_BOOL :
				$permvalue = &$channelpermission[$permField];
				print_yes_no_row($vbphrase[$permPhrases[$permField]], $permField, $permvalue, $extra);
				break;
		}

	}

	//now do the bitmaps
	foreach($permFields AS $permField => $type)
	{
		if ($type == vB_ChannelPermission::TYPE_BITMAP)
		{
			if ($permField !== 'forumpermissions2')
			{
				print_table_header($vbphrase[$permPhrases[$permField]]);
			}
			foreach ($channelpermission['bitfields'][$permField] AS $permBit )
			{
				if ($permBit['used'])
				{
					if (empty($permBit['phrase']) AND ($permField == 'moderatorpermissions'))
					{
						$permBit['phrase'] = "moderator_add_edit_" . $permBit['name'] . "_title";
					}
					if (($permField == 'moderatorpermissions') AND ($permBit['name'] == 'canopenclose'))
					{
						$helpOptions = array('prefix' => $permField);
					}
					else
					{
						$helpOptions = array();
					}
					print_yes_no_row((isset($vbphrase[$permBit['phrase']]) ? $vbphrase[$permBit['phrase']] : $permBit['phrase']), $permField . '[' . $permBit['name'] . ']', $permBit['set'], $extra, $helpOptions);
				}
			}

		}

	}

	// Legacy Hook 'admin_nperms_form' Removed //
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
