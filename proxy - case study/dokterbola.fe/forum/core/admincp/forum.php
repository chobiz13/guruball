<?php
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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 86207 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin,$imodcache;;
$phrasegroups = array('forum', 'cpuser', 'forumdisplay', 'prefix');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_template.php');
require_once(DIR . '/includes/adminfunctions_forums.php');
require_once(DIR . '/includes/adminfunctions_prefix.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminforums'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################


$vbulletin->input->clean_array_gpc('r', array(
	'moderatorid' 	=> vB_Cleaner::TYPE_UINT,
	'nodeid'		=> vB_Cleaner::TYPE_UINT
));

log_admin_action(iif($vbulletin->GPC['moderatorid'] != 0, " moderator id = " . $vbulletin->GPC['moderatorid'],
						iif($vbulletin->GPC['nodeid'] != 0, "node id = " . $vbulletin->GPC['nodeid'])));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['channel_manager_gforum']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// Legacy Hook 'channeladmin_start' Removed //

// ###################### Start add #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'nodeid'			=> vB_Cleaner::TYPE_UINT,
		'defaultnodeid'	=> vB_Cleaner::TYPE_UINT,
		'parentid'			=> vB_Cleaner::TYPE_UINT
	));
	if ($_REQUEST['do'] == 'add')
	{
		// get a list of other usergroups to base this one off of
		print_form_header('admincp/forum', 'add');
		print_description_row(construct_table_help_button('defaultnodeid') . '<b>' . $vbphrase['create_channel_based_off_of_channel'] . '</b> <select name="defaultnodeid" tabindex="1" class="bginput">' . construct_channel_chooser() . '</select> <input type="submit" class="button" value="' . $vbphrase['go'] . '" tabindex="1" />', 0, 2, 'tfoot', 'center');
		print_table_footer();

		if ($vbulletin->GPC['parentid'] == vB_Api::instanceInternal('Content_Channel')->fetchChannelByGUID(vB_Channel::MAIN_CHANNEL))
		{
			print_stop_message2('cant_add_channel_to_root');
		}
		// Set Defaults;
		$channel = array(
			'title' => '',
			'description' => '',
			'displayorder' => 1,
			'parentid' => $vbulletin->GPC['parentid'],
			'styleid' => '',
			'styleoverride' => 0,
			'cancontainthreads' => 1,
			'options' => array(
				'allowbbcode' => 1,
				'allowsmilies' => 1,
			),
		);

		if (!empty($vbulletin->GPC['defaultnodeid']))
		{
			$newchannel = vB_Api::instanceInternal('node')->getNode($vbulletin->GPC['defaultnodeid']);

			foreach (array_keys($channel) AS $title)
			{
				$channel["$title"] = $newchannel["$title"];
			}
		}

		// Legacy Hook 'channeladmin_add_default' Removed //

		print_form_header('admincp/forum', 'update');
		print_table_header($vbphrase['add_new_forum_gforum']);
	}
	else
	{
		$channel = vB_Library::instance('Content_Channel')->getContent($vbulletin->GPC['nodeid']);
		if (is_array($channel))
		{
			$channel = array_pop($channel);
		}
		if (empty($channel))
		{
			print_stop_message2('invalid_channel_specified');
		}

		print_form_header('admincp/forum', 'update');
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['channel'], vB_String::htmlSpecialCharsUni($channel['title']), $channel['nodeid']));
		construct_hidden_code('nodeid', $vbulletin->GPC['nodeid']);
	}

	$channel['title'] = str_replace('&amp;', '&', $channel['title']);
	$channel['description'] = str_replace('&amp;', '&', $channel['description']);

	print_input_row($vbphrase['title'], 'channel[title]', $channel['title']);
	print_textarea_row($vbphrase['description_gcpglobal'], 'channel[description]', $channel['description']);
	//print_input_row($vbphrase['forum_link'], 'forum[link]', $forum['link']);
	print_input_row("$vbphrase[display_order]<dfn>$vbphrase[zero_equals_no_display]</dfn>", 'channel[displayorder]', $channel['displayorder']);
	//print_input_row($vbphrase['default_view_age'], 'forum[daysprune]', $forum['daysprune']);
	if ($vbulletin->GPC['nodeid'] != -1)
	{
		if (!isset($channel['guid']) OR ($channel['guid'] != vB_Channel::MAIN_CHANNEL AND !in_array($channel['nodeid'], vB_Api::instance('content_channel')->fetchTopLevelChannelIds())))
		{
			print_channel_chooser($vbphrase['parent_forum'], 'channel[parentid]', $channel['parentid'], false, false, false, null, true);
		}
	}
	else
	{
		construct_hidden_code('parentid', 0);
	}
	print_table_header($vbphrase['style_options']);

	if ($channel['styleid'] == 0)
	{
		$channel['styleid'] = -1; // to get the "use default style" option selected
	}
	print_style_chooser_row('channel[styleid]', $channel['styleid'], $vbphrase['use_default_style_gforum'], $vbphrase['custom_forum_style'], 1);
	print_yes_no_row($vbphrase['override_style_choice'], 'channel[options][styleoverride]', $channel['options']['styleoverride']);
/*
	print_input_row($vbphrase['prefix_for_forum_status_images'], 'forum[imageprefix]', $forum['imageprefix']);

	print_table_header($vbphrase['access_options']);

	print_input_row($vbphrase['forum_password'], 'forum[password]', $forum['password']);
	if ($_REQUEST['do'] == 'edit')
	{
		print_yes_no_row($vbphrase['apply_password_to_children'], 'applypwdtochild', 0);
	}
	print_yes_no_row($vbphrase['can_have_password'], 'forum[options][canhavepassword]', $forum['canhavepassword']);
 */

	print_table_header($vbphrase['posting_options']);

	print_yes_no_row($vbphrase['act_as_forum'], 'channel[options][cancontainthreads]', ($channel['category'] ? 0 : 1));
	print_yes_no_row($vbphrase['allow_bbcode'], 'channel[options][allowbbcode]', $channel['options']['allowbbcode']);
	print_yes_no_row($vbphrase['allow_smilies'], 'channel[options][allowsmilies]', $channel['options']['allowsmilies']);
	print_yes_no_row($vbphrase['moderatepublish'], 'channel[options][moderatepublish]', $channel['options']['moderatepublish']);

	print_submit_row($vbphrase['save']);
}

// ###################### Start update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'nodeid'         => vB_Cleaner::TYPE_UINT,
		'channel'           => vB_Cleaner::TYPE_ARRAY,
	));

	$channelAPI = vB_Api::instance('Content_Channel');
	$data = array();
	$data = $vbulletin->GPC['channel'];

	if (!empty($vbulletin->GPC['nodeid']))
	{
		$channelid = $vbulletin->GPC['nodeid'];

		$response = $channelAPI->switchForumCategory(((int)$data['options']['cancontainthreads']) ? 0 : 1, $channelid);
		if (!empty($response['errors']))
		{
			print_stop_message2($response['errors'][0]);
		}

		$prior = vB::getDbAssertor()->getRow('vBForum:node', array('nodeid' => $channelid));
		$response = $channelAPI->update($channelid, $data);
		if (!empty($response['errors']))
		{
			print_stop_message2($response['errors'][0]);
		}

		if (isset($data['parentid']) AND ($prior['parentid'] != $data['parentid']))
		{
			$response = vB_Api::instance('node')->moveNodes($channelid, $data['parentid']);
			if (!empty($response['errors']))
			{
				print_stop_message2($response['errors'][0]);
			}
		}
	}
	else
	{
		$data['category'] = ((int)$data['options']['cancontainthreads']) ? 0 : 1;
		// Allow IMG BB Code
		$data['options']['allowimages'] = 1;
		// Allow HTML (but control it with channel permissions instead)
		$data['options']['allowhtml'] = 1;

		// article channels require different routes & pagetemplates to be set.
		if (isset($data['parentid']))
		{
			$parentid = $data['parentid'];
			$parentFullContent = vB_Library::instance('node')->getNodeFullContent($parentid);
			$parentFullContent = $parentFullContent[$parentid];
			if(!empty($parentFullContent['channeltype']) AND ($parentFullContent['channeltype'] == 'article'))
			{
				$data['templates']['vB5_Route_Channel'] = $channelPgTemplateId;
				$data['templates']['vB5_Route_Article'] = $channelConvTemplateid;
				$data['childroute'] = 'vB5_Route_Article';
				unset($data['category']);
			}
		}

		$response = $channelid = $channelAPI->add($data);
		if (!empty($response['errors']))
		{
			print_stop_message2($response['errors'][0]);
		}
	}

	$vbulletin->GPC['nodeid'] = $channelid;

/*	// find old sets
	$old_prefixsets = array();
	if ($forum_exists)
	{
		$set_list_sql = vB::getDbAssertor()->getRows('forumprefixset', array('forumid' => $vbulletin->GPC['forumid']));
		foreach ($set_list_sql as $set)
		{
			$old_prefixsets[] = $set['prefixsetid'];
		}
	}

	// setup prefixes
	vB::getDbAssertor()->delete('forumprefixset', array('forumid' => $vbulletin->GPC['forumid']));

	$add_prefixsets = array();
	foreach ($vbulletin->GPC['prefixset'] AS $prefixsetid)
	{
		$add_prefixsets[] = array($vbulletin->GPC['forumid'], $prefixsetid);
	}

	if ($add_prefixsets)
	{
		vB::getDbAssertor()->insertMultiple('forumprefixset', array('forumid', 'prefixsetid'), $add_prefixsets);
	}

	$removed_sets = array_diff($old_prefixsets, $vbulletin->GPC['prefixset']);
	if ($removed_sets)
	{
		$removed_sets = array_map(array(&$vbulletin->db, 'escape_string'), $removed_sets);

		$prefixes = array();
		$prefix_sql = vB::getDbAssertor()->getRows('prefix', array('prefixsetid' => $removed_sets));
		foreach ($prefix_sql as $prefix)
		{
			$prefixes[] = $prefix['prefixid'];
		}

		remove_prefixes_forum($prefixes, $vbulletin->GPC['forumid']);
	}

	require_once(DIR . '/includes/adminfunctions_prefix.php');
	build_prefix_datastore();


	// rebuild ad templates for ads using the 'browsing a forum' criteria
	$ad_result = vB::getDbAssertor()->getRows('getForumAds', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED));
	if (count($ad_result) > 0)
	{
		$ad_cache = array();
		$ad_locations = array();

		foreach ($ad_result as $ad)
		{
			$ad_cache["$ad[adid]"] = $ad;
			$ad_locations[] = $ad['adlocation'];
		}

		require_once(DIR . '/includes/functions_ad.php');
		require_once(DIR . '/includes/adminfunctions_template.php');

		foreach($ad_locations AS $location)
		{
			$template = wrap_ad_template(build_ad_template($location), $location);

			$template_un = $template;
			$template = compile_template($template);

			vB::getDbAssertor()->update('template', array(
					'template'		=> $template,
					'template_un' => $template_un,
					'dateline'		=> TIMENOW,
					'username'		=> $vbulletin->userinfo['username']
			), array(
					'title'		=> 'ad_' . $location,
					'styleid' => array(-1,0)
			));
		}

		build_all_styles();
	}
*/
	print_stop_message2(array('saved_channel_x_successfully',  $vbulletin->GPC['channel']['title']), 'forum', array(
		'do'=>'modify',
		'n'=>$vbulletin->GPC['nodeid'] . "#channel" . $vbulletin->GPC['nodeid'])
	);
}
// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array('nodeid' => vB_Cleaner::TYPE_UINT));

	print_delete_confirmation('vBForum:node', $vbulletin->GPC['nodeid'], 'forum', 'kill', 'channel', 0, $vbphrase['are_you_sure_you_want_to_delete_this_channel'], 'htmltitle', 'nodeid');}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'nodeid' => vB_Cleaner::TYPE_UINT
	));

	vB_Api::instanceInternal('content_channel')->delete($vbulletin->GPC['nodeid']);

	print_stop_message2('deleted_channel_successfully', 'forum');
}

// ###################### Start do order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => vB_Cleaner::TYPE_ARRAY));

	if (is_array($vbulletin->GPC['order']))
	{
		$channels = vB_Api::instanceInternal('search')->getChannels(true);
		foreach ($channels as $channel)
		{
			if (!isset($vbulletin->GPC['order']["$channel[nodeid]"]))
			{
				continue;
			}

			$displayorder = intval($vbulletin->GPC['order'][$channel['nodeid']]);

			if ($channel['displayorder'] != $displayorder)
			{
				vB_Api::instanceInternal('content_channel')->update($channel['nodeid'], array('displayorder' => $displayorder));
			}
		}
	}

	build_channel_permissions();

	print_stop_message2('saved_display_order_successfully', 'forum', array('do'=>'modify'));
}

// ###################### Start forum_is_related_to_forum #######################
/*function forum_is_related_to_forum($partial_list, $forumid, $full_list)
{
	// This function is only used below, only for expand/collapse of forums.
	// If the first forum's parent list is contained within the second,
	// then it is considered related (think of it as an aunt or uncle forum).

	$partial = explode(',', $partial_list);
	if ($partial[0] == $forumid)
	{
		array_shift($partial);
	}
	$full = explode(',', $full_list);

	foreach ($partial AS $fid)
	{
		if (!in_array($fid, $full))
		{
			return false;
		}
	}

	return true;
}*/

function print_channel_rows($channels, $expanded_parents)
{
	global $imodcache;
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(array(
			'edit_forum', 'view_forum', 'delete_forum', 'add_child_forum', 'add_moderator_gforum', 'list_moderators', 'add_announcement', 'view_permissions_gforum',
			'set_channel_type', 'edit_display_order', 'moderators', 'go', 'node_id'
	));
	$mainoptions_def = array(
		'edit'    => $vbphrase['edit_forum'],
		'view'    => $vbphrase['view_forum'],
		'remove'  => $vbphrase['delete_forum'],
		'add'     => $vbphrase['add_child_forum'],
		'addmod'  => $vbphrase['add_moderator_gforum'],
		'listmod' => $vbphrase['list_moderators'],
		'annc'    => $vbphrase['add_announcement'],
		'perms'   => $vbphrase['view_permissions_gforum'],
	);
	foreach ($channels as $nodeid => $channel)
	{
		$modcount = sizeof($imodcache["$nodeid"]);
		$cell = array();
		$mainoptions = $mainoptions_def;
		if ($modcount)
		{
			$mainoptions['listmod'] = $vbphrase['list_moderators'] . " ($modcount)";
		}
		else
		{
			unset($mainoptions['listmod']);
		}

		$firstcell = "";
		$cp_collapse_forums = vB::getDatastore()->getOption('cp_collapse_forums');
		if ($cp_collapse_forums)
		{
			if (in_array($nodeid, $expanded_parents) OR empty($channel['channels']))
			{
				$firstcell = '[-] ';
			}
			else
			{
				$firstcell = "<a name=\"forum$nodeid\" href=\"admincp/forum.php?" . vB::getCurrentSession()->get('sessionurl') . "do=modify&amp;expandid=$nodeid\">[+]</a> ";
			}
		}
		$firstcell .=  str_repeat('- - ', $channel['depth']);
		$cell[] = $firstcell .
		"<a name=\"forum$nodeid\" href=\"admincp/forum.php?" . vB::getCurrentSession()->get('sessionurl') . "do=edit&amp;n=$nodeid\">$channel[htmltitle]</a> (" . $vbphrase['node_id'] . ": " . $channel['nodeid'] . ")</b>";
		$cell[] = "\n\t<select name=\"n$nodeid\" onchange=\"js_channel_jump($nodeid);\" class=\"bginput\">\n" . construct_select_options($mainoptions) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_channel_jump($nodeid);\" />\n\t";
		$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$nodeid]]\" value=\"$channel[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";

		$mods = array('no_value' => $vbphrase['moderators'].' (' . $modcount . ')');
		if (is_array($imodcache["$nodeid"]))
		{
			foreach ($imodcache["$nodeid"] AS $moderator)
			{
				$mods['']["$moderator[moderatorid]"] = $moderator['username'];
			}
		}
		$mods['add'] = $vbphrase['add_moderator_gforum'];
		$cell[] = "\n\t<select name=\"m$nodeid\" onchange=\"js_moderator_jump($nodeid);\" class=\"bginput\">\n" . construct_select_options($mods) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_moderator_jump($nodeid);\" />\n\t";
		print_cells_row($cell);
		if (!empty($channel['channels']) AND (!$cp_collapse_forums OR in_array($nodeid, $expanded_parents)))
		{
			print_channel_rows($channel['channels'], $expanded_parents);
		}
	}

}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'nodeid' 	=> vB_Cleaner::TYPE_UINT,
		'expandid'	=> vB_Cleaner::TYPE_INT,
	));

	if (!$vbulletin->GPC['expandid'])
	{
		$vbulletin->GPC['expandid'] = -1;
	}
	else if ($vbulletin->GPC['expandid'] == -2)
	{
		// expand all -- easiest to just turn off collapsing
		$vbulletin->options['cp_collapse_forums'] = false;
	}

	// a little javascript for the options menus
	?>
	<script type="text/javascript">
	<!--
	function js_channel_jump(channelinfo)
	{
		var cp_collapse_forums = <?php echo intval($vbulletin->options['cp_collapse_forums']); ?>;
		if (channelinfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['please_select_forum']); ?>');
			return;
		}
		else if (typeof(document.cpform.nodeid) != 'undefined')
		{
			action = document.cpform.controls.options[document.cpform.controls.selectedIndex].value;
		}
		else
		{
			action = eval("document.cpform.n" + channelinfo + ".options[document.cpform.n" + channelinfo + ".selectedIndex].value");
		}
		if (action != '')
		{
			switch (action)
			{
				case 'edit': page = "admincp/forum.php?do=edit&n="; break;
				case 'remove': page = "admincp/forum.php?do=remove&n="; break;
				case 'add': page = "admincp/forum.php?do=add&parentid="; break;
				case 'addmod': page = "admincp/moderator.php?do=add&n="; break;
				case 'listmod': page = "admincp/moderator.php?do=showmods&n=";break;
				case 'annc': page = "admincp/announcement.php?do=add&n="; break;
				case 'view': page = "admincp/forum.php?do=view&n="; break;
				case 'perms':
					if (cp_collapse_forums > 0)
					{
						page = "admincp/forumpermission.php?do=modify&n=";
					}
					else
					{
						page = "admincp/forumpermission.php?do=modify&devnull=";
					}
					break;
				case 'empty': page = "admincp/forum.php?do=empty&n="; break;
			}
			document.cpform.reset();
			jumptopage = page + channelinfo + "&s=<?php echo vB::getCurrentSession()->get('sessionhash'); ?>";
			if (action == 'perms')
			{
				vBRedirect(jumptopage + '#channel' + channelinfo);
			}
			else if(action == 'view' && (typeof top == 'object'))
			{
				top.location = getBaseUrl() + jumptopage;
			}
			else
			{
				vBRedirect(jumptopage);
			}
		}
		else
		{
			alert('<?php echo addslashes_js($vbphrase['invalid_action_specified_gcpglobal']); ?>');
		}
	}

	function js_moderator_jump(foruminfo)
	{
		if (foruminfo == 0)
		{
			alert('<?php echo addslashes_js($vbphrase['please_select_forum']); ?>');
			return;
		}
		else if (typeof(document.cpform.nodeid) != 'undefined')
		{
			modinfo = document.cpform.moderator[document.cpform.moderator.selectedIndex].value;
		}
		else
		{
			modinfo = eval("document.cpform.m" + foruminfo + ".options[document.cpform.m" + foruminfo + ".selectedIndex].value");
			document.cpform.reset();
		}

		var page = "admincp/moderator.php?s=<?php echo vB::getCurrentSession()->get('sessionhash'); ?>&";
		switch (modinfo)
		{
			case 'add': page += "do=add&n=" + foruminfo; break;
			case 'show': page += "do=showmods&n=" + foruminfo; break;
			case '': return false; break;
			default: page += "do=edit&moderatorid=" + modinfo; break;
		}
		vBRedirect(page);
	}

	function js_returnid()
	{
		return document.cpform.nodeid.value;
	}
	//-->
	</script>
	<?php

	$channeloptions = array(
		'edit'    => $vbphrase['edit_forum'],
		'view'    => $vbphrase['view_forum'],
		'remove'  => $vbphrase['delete_forum'],
		'add'     => $vbphrase['add_child_forum'],
		'addmod'  => $vbphrase['add_moderator_gforum'],
		'listmod' => $vbphrase['list_moderators'],
		'annc'    => $vbphrase['add_announcement'],
		'perms'   => $vbphrase['view_permissions_gforum'],
	);

	require_once(DIR . '/includes/functions_databuild.php');
	if ($vbulletin->options['cp_collapse_forums'] != 2)
	{
		print_form_header('admincp/forum', 'doorder');
		print_table_header($vbphrase['channel_manager_gforum'], 4);
		print_description_row($vbphrase['if_you_change_display_order'], 0, 4);
		fetch_row_bgclass(); // restart alternating row classes

		require_once(DIR . '/includes/functions_forumlist.php');
		cache_moderators();
		//$nodeAPI = vB_Api::instanceInternal('node');
		// Hide protected channels. A bug in search API was already hiding them, and per VBV-14764 we'll continue to hide them unless
		// customer feedback overwhelmingly indicate they want to be able to edit the special/protected channels.
		$channels = vB_Api::instanceInternal('search')->getChannels(false, array('include_protected' => 0));
		$expanded_parents = array();
		if (!empty($vbulletin->GPC['expandid']))
		{
			//We need to know this record's parentage first.
			$parentage = vB::getDbAssertor()->assertQuery('vBForum:closure',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,	'child' => $vbulletin->GPC['expandid']), 'depth');
			$parent = $parentage->current();
			while($parentage->valid())
			{
				$expanded_parents[] = $parent['parent'];
				$parent = $parentage->next();
			}
		}
		print_cells_row(array($vbphrase['channel'], $vbphrase['controls'], $vbphrase['display_order'], $vbphrase['moderators']), 1, 'tcat');

		print_channel_rows($channels, $expanded_parents);

		print_table_footer(4, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save_display_order'] . "\" accesskey=\"s\" />" . construct_button_code($vbphrase['add_new_forum_gforum'], "admincp/forum.php?" . vB::getCurrentSession()->get('sessionurl') . "do=add"));

		if ($vbulletin->options['cp_collapse_forums'])
		{
			echo '<p class="smallfont" align="center">' . construct_link_code($vbphrase['expand_all'], "admincp/forum.php?" . vB::getCurrentSession()->get('sessionurl') . "do=modify&amp;expandid=-2") . '</p>';
		}
	}
	else
	{
		print_form_header('admincp/forum', 'doorder');
		print_table_header($vbphrase['channel_manager_gforum'], 2);

		print_cells_row(array($vbphrase['channel'], $vbphrase['controls']), 1, 'tcat');
		$cell = array();

		$select = '<select name="nodeid" id="sel_foruid" tabindex="1" class="bginput">';
		$select .= construct_channel_chooser($vbulletin->GPC['nodeid'], true);
		$select .= "</select>\n";

		$cell[] = $select;
		$cell[] = "\n\t<select name=\"controls\" class=\"bginput\">\n" . construct_select_options($channeloptions) . "\t</select><input type=\"button\" class=\"button\" value=\"" . $vbphrase['go'] . "\" onclick=\"js_channel_jump(js_returnid());\" />\n\t";
		print_cells_row($cell);
		print_table_footer(2, construct_button_code($vbphrase['add_new_forum_gforum'], "admincp/forum.php?" . vB::getCurrentSession()->get('sessionurl') . "do=add"));
	}
}

// ###################### Start update #######################
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'nodeid' => vB_Cleaner::TYPE_UINT,
	));
	$channel = vB_Api::instanceInternal('node')->getNode($vbulletin->GPC['nodeid']);
	if (empty($channel))
	{
		print_stop_message2('invalid_channel_specified');
	}


	$path = vB_Api::instanceInternal('route')->getUrl($channel['routeid'], array(), array());
	$baseurl = vB::getDatastore()->getOption('frontendurl');
	print_cp_redirect($baseurl . $path);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86207 $
|| #######################################################################
\*=========================================================================*/
