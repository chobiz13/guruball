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

/**
*	This file is a bucket in which functions common to both the
* install and the upgrade can be located.
*/

if (!defined('VB_AREA') AND !defined('THIS_SCRIPT'))
{
	echo 'VB_AREA or THIS_SCRIPT must be defined to continue';
	exit;
}

// Determine which mysql engines to use
// Will use InnoDB & MEMORY where appropriate, if available, otherwise MyISAM

function get_engine($db, $allow_memory)
{
	$memory = $innodb = false;
	$engines = $db->query('SHOW ENGINES');

	while ($row = $db->fetch_array($engines))
	{
		if ($allow_memory
			AND strtoupper($row['Engine']) == 'MEMORY'
			AND strtoupper($row['Support']) == 'YES')
		{
			$memory = true;
		}

		if (strtoupper($row['Engine']) == 'INNODB'
			AND (strtoupper($row['Support']) == 'YES'
			OR strtoupper($row['Support']) == 'DEFAULT'))
		{
			$innodb = true;
		}
	}

	//prefer innodb to memory type even for "memory" tables. The memory type
	//has locking issues similar to MyISAM and InnoDB will use memory caching
	//anyway for high traffic tables like session
	if ($innodb)
	{ // Otherise try Innodb
		return 'InnoDB';
	}

	if ($memory)
	{ // Return Memory if possible, and allowed
		return 'MEMORY';
	}

	return 'MyISAM'; // Otherwise default to MyISAM.
}

// Choose Engine for Session Tables, MEMORY preferred.
function get_memory_engine($db)
{
	return get_engine($db, true);
}

// Determines which mysql engine to use for high concurrency tables
// Will use InnoDB if its available, otherwise MyISAM
function get_innodb_engine($db)
{
	return get_engine($db, false);
}

function should_install_suite()
{
	return false;
}

function print_admin_stop_exception($e)
{
		$args = $e->getParams();
		$message = fetch_phrase($args[0], 'error', '', false);

		if (sizeof($args) > 1)
		{
			$args[0] = $message;
			$message = call_user_func_array('construct_phrase', $args);
		}

		echo "<p>$message</p>\n";
}

function get_default_navbars()
{
	$headernavbar = array(
		array(
			'title' => 'navbar_home',
			'url' => '/',
			'newWindow' => 0,
			'subnav' => array(
				array(
					'title' => 'navbar_newtopics',
					'url' => 'search?searchJSON=%7B%22view%22%3A%22topic%22%2C%22unread_only%22%3A1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D',
					'newWindow' => 0,
					'usergroups' => array(2,5,6,7,9,10,11,12,13,14),
				),
				array(
					'title' => 'navbar_todays_posts',
					'url' => 'search?searchJSON=%7B%22last%22%3A%7B%22from%22%3A%22lastDay%22%7D%2C%22view%22%3A%22topic%22%2C%22starter_only%22%3A+1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D',
					'newWindow' => 0,
					'usergroups' => array(1),
				),
				array(
					'title' => 'navbar_whos_online',
					'url' => 'online',
					'newWindow' => 0,
					'usergroups' => array(2,5,6,7,9,10,11,12,13,14),
				),
				array(
					'title' => 'navbar_mark_channels_read',
					'url' => '#',
					'newWindow' => 0,
				),
				array(
					'title' => 'navbar_member_list',
					'url' => 'memberlist',
					'newWindow' => 0,
					'usergroups' => 0,
				),
			)
		),
		array(
			'title' => 'navbar_blogs',
			'url' => 'blogs',
			'newWindow' => 0,
			'subnav' => array(
				array(
					'title' => 'navbar_create_a_new_blog',
					'url' => 'blogadmin/create',
					'newWindow' => 0,
					'usergroups' => array(2,5,6,7,9,10,11,12,13,14),
				),
				array(
					'title' => 'navbar_newentries',
					'url' => 'search?searchJSON=%7B%22date%22%3A%22lastVisit%22%2C%22view%22%3A%22topic%22%2C%22unread_only%22%3A1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%2C%22channel%22%3A%5B%225%22%5D%7D',
					'newWindow' => 0,
				),
			)
		),
		array(
			'title' => 'navbar_articles',
			'url' => 'articles',
			'newWindow' => 0,
		),
		array(
			'title' => 'navbar_social_groups',
			'url' => 'social-groups',
			'newWindow' => 0,
			'subnav' => array(
				array(
					'title' => 'navbar_create_a_new_group',
					'url' => 'sgadmin/create',
					'newWindow' => 0,
					'usergroups' => array(2,5,6,7,9,10,11,12,13,14)
				),
			)
		),
	);

	$footernavbar = array(
		array(
			'title' => 'navbar_help',
			'url' => 'help',
			'newWindow' => 0,
			'attr' => 'rel="nofollow"',
		),
		array(
			'title' => 'navbar_contact_us',
			'url' => 'contact-us',
			'newWindow' => 0,
			'attr' => 'rel="nofollow"',
		),
		array(
			'title' => 'navbar_admin',
			'url' => 'admincp',
			'newWindow' => 0,
			'usergroups' => array(6),
		),
		array(
			'title' => 'navbar_mod',
			'url' => 'modcp/',
			'newWindow' => 0,
			'usergroups' => array(6,7,5),
		),
	);

	return array('header' => $headernavbar, 'footer' => $footernavbar);
}


/**
 *	Adds a user in the install
 *	Avoids using main system components that might not work without having a user
 */
function install_add_user($userid, $username, $title, $email, $admincp_useroption, $adminpermission, $permissions, $permissions2)
{
	//refactored from class_upgrade_install.  Should look into using library classes here, but could
	//run into problems with that.

	$db = vB::getDBAssertor();

	$data = array(
		'userid' => $userid,
		'username' => $username,
		'usertitle' => $title,
		'email' => $email,
		'joindate' => TIMENOW,
		'lastvisit' => TIMENOW,
		'lastactivity' => TIMENOW,
		'usergroupid' => 6,
		'options' => $admincp_useroption,
		'showvbcode' => 2,
		'membergroupids' => '',
		'secret' => vB_Library::instance('user')->generateUserSecret()
	);

	$db->insert('user', $data);

	$data = array('userid' => $userid);
	$db->insert('vBForum:usertextfield', $data);
	$db->insert('vBForum:userfield', $data);

	$data = array(
		'userid' => $userid,
		'adminpermissions' => $adminpermission,
	);
	$db->insert('vBForum:administrator', $data);

	$data = array(
		'userid' => $userid,
		'nodeid' => 0,
		'permissions' => $permissions,
		'permissions2' => $permissions2
	);
	$db->insert('vBForum:moderator', $data);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88635 $
|| #######################################################################
\*=========================================================================*/
