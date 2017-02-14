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

// ###################### Start dodigest #######################
function exec_digest($type = 2)
{

	// type = 2 : daily
	// type = 3 : weekly

	$lastdate = mktime(0, 0); // midnight today
	if ($type == 2)
	{ // daily
		// yesterday midnight
		$lastdate -= 24 * 60 * 60;
	}
	else
	{ // weekly
		// last week midnight
		$lastdate -= 7 * 24 * 60 * 60;
	}

	if (trim(vB::getDatastore()->getOption('globalignore')) != '')
	{
		$coventry = preg_split('#\s+#s', vB::getDatastore()->getOption('globalignore'), -1, PREG_SPLIT_NO_EMPTY);
	}
	else
	{
		$coventry = array();
	}

	require_once(DIR . '/includes/class_bbcode_alt.php');
	$vbulletin = &vB::get_registry();
	$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());


	vB_Mail::vbmailStart();

	$bf_misc_useroptions = vB::get_datastore()->get_value('bf_misc_useroptions');
	$bf_ugp_genericoptions = vB::get_datastore()->get_value('bf_ugp_genericoptions');
	$bf_ugp_forumpermissions = vB::get_datastore()->get_value('bf_ugp_forumpermissions');

	// we want to fetch all language records at once and using cache if possible
	$defaultLanguage = false;
	$languageIds = array();

	// get new threads (Topic Subscription)
	$threads = vB::getDbAssertor()->getRows('getNewThreads', array(
			'dstonoff' => $bf_misc_useroptions['dstonoff'],
			'hasaccessmask' => $bf_misc_useroptions['hasaccessmask'],
			'isnotbannedgroup' => $bf_ugp_genericoptions['isnotbannedgroup'],
			'lastdate' => intval($lastdate)
	));

	// grab all forums / subforums for given subscription (Channel Subscription)
	$forums = vB::getDbAssertor()->assertQuery('getNewForums', array(
		'dstonoff' => $bf_misc_useroptions['dstonoff'],
		'hasaccessmask' => $bf_misc_useroptions['hasaccessmask'],
		'type' => intval($type),
		'lastdate' => intval($lastdate),
		'channelcontenttype' => vB_Api::instanceInternal('contenttype')->fetchContentTypeIdFromClass('Channel'),
		'isnotbannedgroup' => $bf_ugp_genericoptions['isnotbannedgroup']
	));

	// Let's see which languageids we wanna fetch
	foreach ($threads AS $thread)
	{
		if ($thread['languageid'] == 0)
		{
			if (!$defaultLanguage)
			{
				$defaultLanguage = intval(vB::getDatastore()->getOption('languageid'));
				$languageIds[] = $defaultLanguage;
			}
		}
		else
		{
			$languageIds[] = $thread['languageid'];
		}
	}
	foreach ($forums AS $forum)
	{
		if ($forum['languageid'] == 0)
		{
			if (!$defaultLanguage)
			{
				$defaultLanguage = intval(vB::getDatastore()->getOption('languageid'));
				$languageIds[] = $defaultLanguage;
			}
		}
		else
		{
			$languageIds[] = $forum['languageid'];
		}
	}

	// fetch languages
	$languages = vB_Library::instance('language')->fetchLanguages($languageIds);

	// process threads
	foreach ($threads as $thread)
	{
		$postbits = '';

		// Make sure user have correct email notification settings.
		if ($thread['emailnotification'] != $type)
		{
			continue;
		}

		if ($thread['lastauthorid'] != $thread['userid'] AND in_array($thread['lastauthorid'], $coventry))
		{
			continue;
		}

		$usercontext = vB::getUserContext($thread['userid']);
		if (
			!$usercontext->getChannelPermission('forumpermissions', 'canview', $thread['nodeid'])
			OR
			!$usercontext->getChannelPermission('forumpermissions', 'canviewthreads', $thread['nodeid'])
			OR
			($thread['lastauthorid'] != $thread['userid'] AND !$usercontext->getChannelPermission('forumpermissions', 'canviewothers', $thread['nodeid']))
		)
		{
			continue;
		}

		$langInfo =& $languages[$thread['languageid']];

		$userinfo = array(
			'lang_locale'    => $langInfo['locale'],
			'dstonoff'       => $thread['dstonoff'],
			'timezoneoffset' => $thread['timezoneoffset'],
		);

		$thread['lastreplydate'] = vbdate($langInfo['dateoverride'] ? $langInfo['dateoverride'] : vB::getDatastore()->getOption('dateformat'), $thread['lastcontent'], false, true, true, false, $userinfo);
		$thread['lastreplytime'] = vbdate($langInfo['timeoverride'] ? $langInfo['timeoverride'] : vB::getDatastore()->getOption('timeformat'), $thread['lastcontent'], false, true, true, false, $userinfo);
		$thread['htmltitle'] = unhtmlspecialchars($thread['htmltitle']);
		$thread['username'] = unhtmlspecialchars($thread['username']);
		$thread['postusername'] = unhtmlspecialchars($thread['authorname']);
		$thread['lastposter'] = unhtmlspecialchars($thread['lastcontentauthor']);
		$thread['newposts'] = 0;

		//not currently used and probably needs rethinking, but don't want to remove until this code gets rewritten
		//$thread['auth'] = md5($thread['userid'] . $thread['subscribediscussionid'] . $thread['secret'] . vB_Request_Web::$COOKIE_SALT);

		if ($thread['prefixid'])
		{
			// need prefix in correct language
			$phraseAux = vB_Api::instanceInternal('phrase')->fetch(array("prefix_$thread[prefixid]_title_plain"));
			$thread['prefix_plain'] = $phraseAux["prefix_$thread[prefixid]_title_plain"] . ' ';
		}
		else
		{
			$thread['prefix_plain'] = '';
		}

		// Note: closure.depth = 1  on the where clause means getNewPosts only grabs replies, not comments.
		// get posts
		$posts = vB::getDbAssertor()->getRows('getNewPosts', array('threadid' => intval($thread['nodeid']), 'lastdate' => intval($lastdate)));
		// compile
		$haveothers = false;
		foreach ($posts as $post)
		{
			if ($post['userid'] != $thread['userid'] AND in_array($post['userid'], $coventry))
			{
				continue;
			}

			if ($post['userid'] != $thread['userid'])
			{
				$haveothers = true;
			}
			$thread['newposts']++;
			$post['htmltitle'] = unhtmlspecialchars($post['htmltitle']);
			$post['postdate'] = vbdate($langInfo['dateoverride'] ? $langInfo['dateoverride'] : vB::getDatastore()->getOption('dateformat'), $post['publishdate'], false, true, true, false, $userinfo);
			$post['posttime'] = vbdate($langInfo['timeoverride'] ? $langInfo['timeoverride'] : vB::getDatastore()->getOption('timeformat'), $post['publishdate'], false, true, true, false, $userinfo);
			$post['postusername'] = unhtmlspecialchars($post['authorname']);

			$plaintext_parser->set_parsing_language($thread['languageid']);
			$contentAPI = vB_Library_Content::getContentApi($post['contenttypeid']);
			$contents = $contentAPI->getContent($post['nodeid']);
			$post['pagetext'] = $plaintext_parser->parse($contents[$post['nodeid']]['rawtext'], $thread['parentid']);
			$postlink = vB5_Route::buildUrl($post['routeid'] . '|bburl', array('nodeid' => $post['nodeid']));
			/*$postlink = fetch_seo_url('thread|nosession|bburl',
				array('threadid' => $thread['nodeid'], 'title' => htmlspecialchars_uni($thread['title']))) .
				"#post$post[nodeid]";*/

			// Legacy Hook 'digest_thread_post' Removed //

			$phrases = vB_Api::instanceInternal('phrase')->fetch('digestpostbit', $thread['languageid']);
			$postbits .= sprintf($phrases['digestpostbit'], $post['htmltitle'], $postlink, $post['postusername'], $post['postdate'], $post['posttime'], $post['pagetext']);

		}

		// Legacy Hook 'digest_thread_process' Removed //

		// Don't send an update if the subscriber is the only one who posted in the thread.
		if ($haveothers)
		{
			// make email
			// magic vars used by the phrase eval
			$threadlink = vB5_Route::buildUrl($thread['routeid'] . '|fullurl', array('nodeid' => $thread['nodeid']));
			//$threadlink = fetch_seo_url('thread|nosession|bburl', array('threadid' => $thread['threadid'], 'title' => htmlspecialchars_uni($thread['title'])));

			//this link probably doesn't do what the author thinks it does, need to validate.
			$unsubscribelink =  vB5_Route::buildUrl('subscription|fullurl', array('tab' => 'subscriptions', 'userid' => $thread['userid']));

			$maildata = vB_Api::instanceInternal('phrase')->fetchEmailPhrases(
				'digestthread',
				array(
					$thread['username'],
					$thread['prefix_plain'],
					$thread['htmltitle'],
					$thread['postusername'],
					$thread['newposts'],
					$thread['lastposter'],
					$threadlink,
					$postbits,
					vB::getDatastore()->getOption('bbtitle'),
					$unsubscribelink
				),
				array(
					$thread['prefix_plain'],
					$thread['htmltitle']
				),
				$thread['languageid']
			);
			vB_Mail::vbmail($thread['email'], $maildata['subject'], $maildata['message']);
		}
	}

	unset($plaintext_parser);

	// process forums
	foreach ($forums as $forum)
	{
		$langInfo =& $languages[$forum['languageid']];

		$userinfo = array(
			'lang_locale'       => $langInfo['locale'],
			'dstonoff'          => $forum['dstonoff'],
			'timezoneoffset'    => $forum['timezoneoffset'],
		);

		$newthreadbits = '';
		$newthreads = 0;
		$updatedthreadbits = '';
		$updatedthreads = 0;

		$forum['username'] = unhtmlspecialchars($forum['username']);
		$forum['title_clean'] = unhtmlspecialchars($forum['title_clean']);

		//not currently used and probably needs rethinking, but don't want to remove until this code gets rewritten
		//$forum['auth'] = md5($forum['userid'] . $forum['subscribeforumid'] . $forum['secret'] . vB_Request_Web::$COOKIE_SALT);

		$threads = vB::getDbAssertor()->assertQuery('fetchForumThreads', array(
				'forumid' =>intval($forum['forumid']),
				'lastdate' => intval ($lastdate)
		));

		foreach ($threads AS $thread)
		{
			if ($thread['postuserid'] != $forum['userid'] AND in_array($thread['postuserid'], $coventry))
			{
				continue;
			}

			// allow those without canviewthreads to subscribe/receive forum updates as they contain not post content
			$usercontext = vB::getUserContext($thread['userid']);
			if (
				!$usercontext->getChannelPermission('forumpermissions', 'canview', $thread['nodeid'])
				OR
				($thread['lastauthorid'] != $thread['userid'] AND !$usercontext->getChannelPermission('forumpermissions', 'canviewothers', $thread['nodeid']))
			)
			{
				continue;
			}

			$thread['forumhtmltitle'] = unhtmlspecialchars($thread['forumhtmltitle']);
			$thread['lastreplydate'] = vbdate($langInfo['dateoverride'] ? $langInfo['dateoverride'] : vB::getDatastore()->getOption('dateformat'), $thread['lastpost'], false, true, true, false, $userinfo);
			$thread['lastreplytime'] = vbdate($langInfo['timeoverride'] ? $langInfo['timeoverride'] : vB::getDatastore()->getOption('timeformat'), $thread['lastpost'], false, true, true, false, $userinfo);

			$thread['htmltitle'] = unhtmlspecialchars($thread['htmltitle']);
			$thread['postusername'] = unhtmlspecialchars($thread['postusername']);
			$thread['lastposter'] = unhtmlspecialchars($thread['lastposter']);

			if ($thread['prefixid'])
			{
				// need prefix in correct language
				$phraseAux = vB_Api::instanceInternal('phrase')->fetch(array("prefix_$thread[prefixid]_title_plain"));
				$thread['prefix_plain'] = $phraseAux["prefix_$thread[prefixid]_title_plain"] . ' ';
			}
			else
			{
				$thread['prefix_plain'] = '';
			}

			$threadlink = vB5_Route::buildUrl($thread['routeid'] . '|fullurl', array('nodeid' => $thread['threadid']));
			// Legacy Hook 'digest_forum_thread' Removed //


			$maildata = vB_Api::instanceInternal('phrase')->fetchEmailPhrases(
				'digestthreadbit',
				array(
					$thread['prefix_plain'],
					$thread['htmltitle'],
					$threadlink,
					$thread['forumhtmltitle'],
					$thread['postusername'],
					$thread['lastreplydate'],
					$thread['lastreplytime']
				),
				array(),
				$forum['languageid']
			);
			if ($thread['dateline'] > $lastdate)
			{ // new thread
				$newthreads++;
				$newthreadbits .= $maildata['message'];
			}
			else
			{
				$updatedthreads++;
				$updatedthreadbits .= $maildata['message'];
			}

		}

		// Legacy Hook 'digest_forum_process' Removed //

		if (!empty($newthreads) OR !empty($updatedthreadbits))
		{
			// make email
			// magic vars used by the phrase eval
			//$forumlink = fetch_seo_url('forum|nosession|bburl', $forum);
			$forumlink = vB5_Route::buildUrl($forum['routeid'] . '|fullurl', array('nodeid' => $forum['forumid']));

			//this link probably doesn't do what the author thinks it does.  Need to validate.
			$unsubscribelink = vB5_Route::buildUrl('subscription|fullurl', array('tab' => 'subscriptions', 'userid' => $forum['userid']));

			$maildata = vB_Api::instanceInternal('phrase')->fetchEmailPhrases(
				'digestforum',
				array(
					$forum['username'],
					$forum['title_clean'],
					$newthreads,
					$updatedthreads,
					$forumlink,
					$newthreadbits,
					$updatedthreadbits,
					vB::getDatastore()->getOption('bbtitle'),
					$unsubscribelink
				),
				array($forum['title_clean']),
				$forum['languageid']
			);
			vB_Mail::vbmail($forum['email'], $maildata['subject'], $maildata['message'], true);
		}
	}

	// ******* Social Group Digests **********
	$bf_misc_socnet = vB::get_datastore()->get_value('bf_misc_socnet');
	if (vB::getDatastore()->getOption('socnet') & $bf_misc_socnet['enable_groups'])
	{
		require_once(DIR . '/includes/functions_socialgroup.php');

		/** @todo review this part*/
		/** @todo if we reenable this code, we need to prefetch language records as for threads and forums */
		/*
		$groups = vB::getDbAssertor()->assertQuery('fetchSocialGroupDigests', array(
			'dstonoff' => $bf_misc_useroptions['dstonoff'],
			'hasaccessmask' => $bf_misc_useroptions['hasaccessmask'],
			'languageid' => intval(vB::getDatastore()->getOption('languageid')),
			'type' => $type == 2 ? 'daily' : 'weekly',
			'lastdate' => intval($lastdate),
			'isnotbannedgroup' => $bf_ugp_genericoptions['isnotbannedgroup']
		));


		foreach ($groups as $group)
		{
			$userperms = cache_permissions($group, false);
			if (!($userperms['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview'])
				OR !($userperms['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canviewgroups'])
			)
			{
				continue;
			}

			if ($group['options'] & $vbulletin->bf_misc_socialgroupoptions['join_to_view'] AND $vbulletin->options['sg_allow_join_to_view'])
			{
				if ($group['membertype'] != 'member'
					AND !($userperms['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canalwayspostmessage'])
					AND !($userperms['socialgrouppermissions'] & $vbulletin->bf_ugp_socialgrouppermissions['canalwascreatediscussion'])
				)
				{
					continue;
				}
			}

			$userinfo = array(
				'lang_locale'       => $group['lang_locale'],
				'dstonoff'          => $group['dstonoff'],
				'timezoneoffset'    => $group['timezoneoffset'],
			);

			$new_discussion_bits = '';
			$new_discussions = 0;
			$updated_discussion_bits = '';
			$updated_discussions = 0;

			$group['username'] = unhtmlspecialchars($group['username']);
			$group['name'] = unhtmlspecialchars($group['name']);
			$discussions = vB::getDbAssertor()->assertQuery('fetchGroupDiscussions', array(
					'groupid' => $group['groupid'],
					'lastdate' => intval($lastdate)
			));
			foreach ($discussions as $discussion)
			{
				$discussion['lastreplydate'] = vbdate($group['lang_dateoverride'] ? $group['lang_dateoverride'] : $vbulletin->options['default_dateformat'], $discussion['lastpost'], false, true, true, false, $userinfo);
				$discussion['lastreplytime'] = vbdate($group['lang_timeoverride'] ? $group['lang_timeoverride'] : $vbulletin->options['default_timeformat'], $discussion['lastpost'], false, true, true, false, $userinfo);

				$discussion['title'] = unhtmlspecialchars($discussion['title']);
				$discussion['postusername'] = unhtmlspecialchars($discussion['postusername']);
				$discussion['lastposter'] = unhtmlspecialchars($discussion['lastposter']);

				// Legacy Hook 'digest_group_discussion' Removed //

				//magic variables that will be picked up by the phrase eval
				$discussionlink = fetch_seo_url('groupdiscussion', $discussion);

				$maildata = vB_Api::instanceInternal('phrase')
					->fetchEmailPhrases('digestgroupbit', array($discussion['htmltitle'], $discussionlink, $group['name'], $discussion['postusername'], $discussion['lastreplydate'], $discussion['lastreplytime']), array(), $group['languageid']);
				if ($discussion['dateline'] > $lastdate)
				{ // new discussion
					$new_discussions++;
					$new_discussion_bits .= $maildata['message'];
				}
				else
				{
					$updated_discussions++;
					$updated_discussion_bits .= $maildata['message'];
				}

			}

			// Legacy Hook 'digest_group_process' Removed //

			if (!empty($new_discussion_bits) OR !empty($updated_discussion_bits))
			{
				//magic variables that will be picked up by the phrase eval
				$grouplink = fetch_seo_url('group|nosession|bburl', $group);

				// make email
				$maildata = vB_Api::instanceInternal('phrase')
					->fetchEmailPhrases('digestgroup', array($group['username'], $group['name'], $new_discussions, $updated_discussions, $grouplink, $new_discussion_bits, $updated_discussion_bits, $vbulletin->options['bbtitle']), array($group['name']), $group['languageid']);
				vB_Mail::vbmail($group['email'], $maildata['subject'], $maildata['message']);
			}
		}
		*/
	}

	vB_Mail::vbmailEnd();
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83432 $
|| #######################################################################
\*=========================================================================*/
