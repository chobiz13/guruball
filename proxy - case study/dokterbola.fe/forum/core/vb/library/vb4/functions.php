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

/**
 * vB_Library_VB4_Functions
 *
 * @package vBApi
 * @access public
 */
class vB_Library_VB4_Functions extends vB_Library
{
	protected $baseurl_corecdn = null;

	public function getPreview($text, $length = 100) {
		return strip_tags(
			vB_String::unHtmlSpecialChars(
				substr(
					vB_String::stripBbcode($text, true, false, false, true, false),
					0, $length)
				)
			);
	}

	public function pageNav($pagenumber, $perpage, $results)
	{
		$totalpages = ceil($results / $perpage);
		$pagenavarr = array();
		$pagenavarr['total'] = $results;
		$pagenavarr['pagenumber'] = $pagenumber;
		$pagenavarr['totalpages'] = $totalpages ? $totalpages : 1;

		if($pagenavarr['totalpages'] == 1)
		{
			$pagenavarr['pagenav'][] = array('curpage' => 1, 'total' => $results);
			return $pagenavarr;
		}

		$pages = array(1, $pagenumber, $totalpages);

		if ($totalpages < 5)
		{
			for ($i = 2; $i < $totalpages; $i++)
			{
				$pages[] = $i;
			}
		}
		if ($totalpages >= 5)
		{
			if ($pagenumber > 1)
			{
				$pages[] = $pagenumber - 1;
			}
			if ($pagenumber < $totalpages)
			{
				$pages[] = $pagenumber + 1;
			}
		}

		if ($totalpages >= 30)
		{
			if ($pagenumber > 5)
			{
				$pages[] = $pagenumber - 5;
			}
			if ($pagenumber < $totalpages - 5)
			{
				$pages[] = $pagenumber + 5;
			}
		}

		if ($totalpages >= 60)
		{
			if ($pagenumber > 10)
			{
				$pages[] = $pagenumber - 10;
			}
			if ($pagenumber < $totalpages - 10)
			{
				$pages[] = $pagenumber + 10;
			}
		}

		$pages = array_unique($pages);
		sort($pages);

		foreach ($pages AS $curpage)
		{
			if ($curpage < 1 OR $curpage > $totalpages)
			{
				continue;
			}

			$pagenavarr['pagenav'][] = array('curpage' => $curpage, 'total' => $results);
		}

		return $pagenavarr;
	}

	/**
	 * [Resolves -1 and 0 as perpage values for users]
	 *
	 * @param  [integer] $perpage
	 * @return [integer] $perpage [the correct number]
	 */
	public function getUsersPostPerPage($perpage)
	{
		// Get user defined posts per page
		if (empty($perpage) OR ($perpage < 1))
		{
			$userinfo = vB_Api::instance('user')->fetchUserinfo();
			$perpage = (!empty($userinfo['maxposts']) AND $userinfo['maxposts'] > 0) ? $userinfo['maxposts'] : 20;
		}

		return $perpage;
	}

	public function avatarUrl($userid)
	{
		$options = vB::getDatastore()->getValue('options');
		$avatarurl = vB_Api::instance('user')->fetchAvatars(array($userid));
		$avatarurl = array_pop($avatarurl);
		if (is_null($this->baseurl_corecdn))
		{
			$this->getBaseUrlCoreCdn();
		}
		$avatarurl = $this->baseurl_corecdn . '/' .$avatarurl['avatarpath'];
		return $avatarurl;
	}

	// Mostly replicates the $baseurl_corecdn parameter set globally in templates, and sets it to $this->baseurl_corecdn
	private function getBaseUrlCoreCdn()
	{
		if (is_null($this->baseurl_corecdn))
		{
			/*
				Taken from includes/vb5/template.php registerDefaultGlobals()
			 */
			$vboptions = vB::getDatastore()->getValue('options');
			$baseurl_cdn = $vboptions['cdnurl'];

			if($baseurl_cdn)
			{
				$this->baseurl_corecdn = $baseurl_cdn . '/core';
			}
			else
			{
				/*
					For some reason, the android APP doesn't know how to deal with
					a "./core" prefix if the avatarpath points to image.php ...
					I'm guessing the app does some kind of validation on the URL and
					fails it if it begins with a period or something.
					If we want the absolute url prefix instead, prepend it with $baseurl
					where
						$baseurl = $vboptions['frontendurl'];
				 */
				$this->baseurl_corecdn = 'core';
			}
		}

		return $this->baseurl_corecdn;

	}

	public function parseAttachments($attaches)
	{
		//avoid some warnings on NULL (or other malformed input);
		if (!is_array($attaches))
		{
			$attaches = array();
		}

		/*
			Note, $attaches can be photos of a gallery (vB5 content type of a post, not a user album)
			or attachment (usual). Currently, only blogs pass in photos to this function, see
			vB_Api_Vb4_blog::blog()
			A Photo is associated with a `photo` record, while an Attachment are associated with an
			`attach` record. Attach has filename, photo has caption.
		 */
		if (empty($this->bbcode_parser))
		{
			$this->bbcode_parser = new vB_Library_BbCode(true, true);
		}
		$userinfo = vB_Api::instance('user')->fetchUserinfo();
		$thumbnailattachments = array();
		$imageattachments = array();
		$imageattachmentlinks = array();
		$otherattachments = array();
		$moderatedattachments = array();
		$options = vB::getDatastore()->getValue('options');
		/*
			Taken from vB_Attach_Display_Content::process_attachments()   ({vb4}/packages/vbattach/attach.php)
			Look for switch($ext)
		 */
		$vB4ImageExtensionList = array(
			'gif' => true,
			'jpg' => true,
			'jpeg' => true,
			'jpe' => true,
			'png' => true,
			'bmp' => true,
		);
		$vB4ImageLinksExtensionsList = array(// Note, thumbnails are broken, VBV-14569.
			'tiff' => true,
			'tif' => true,
			'psd' => true,
			//'pdf' => true,	// PDF had some special handling going on in vB4, but let's just consider it as non-image in vB5.
		);
		$attachCount = count($attaches);
		foreach ($attaches as $nodeid => $attachment)
		{
			$pictureurl = $this->bbcode_parser->getAttachmentLink($attachment);

			if (!isset($attachment['filename']))
			{
				// This is probably a photo. For now, let's make sure we won't through any notices.
				$attachment['filename'] = "";
			}

			/*
			 *	Note, attachmentid != filedataid. In vb4, the `attachment` table had an attachmentid column.
			 * 	In vb5, `node`.oldid is the best candidate for attachmentid, but that's only set if it was an attachment
			 * 	imported during an upgrade from a vB4 forum. So for a fresh vB5 site for ex., attachmentid has no meaning.
			 *	In vB5, we use either the nodeid (usually prefixed by 'n') or the filedataid of the `attach` record for
			 *	images.
			 * 	To see how attachmentid is used in vB5, see core/attachment.php
			 *	For an upgrade step that imports the `attachment` data into `node`/`attach` tables, see upgrade step_147() of 500a1.
			 */
			$parsed = array(
				'attachment' => array(
					'attachmentextension' => $attachment['extension'],
					//'attachmentid' => $attachment['filedataid'], // old, incorrect
					// Hack to let attachment.php differentiate legacy vs vB5 attachments.
					'attachmentid' => - intval($attachment['nodeid']),
					'dateline' => $attachment['dateline'],
					'filename' => $attachment['filename'],
					'filesize' => $attachment['filesize'],
				),
				'pictureurl' =>  $pictureurl,
				'thumburl' => $pictureurl . "&type=thumb",
				'url' =>  $pictureurl,
			);

			$isImg = isset($vB4ImageExtensionList[$attachment['extension']]);
			$isImgLink = isset($vB4ImageLinksExtensionsList[$attachment['extension']]);
			$attachment_node = vB_Api::instance('node')->getNode($attachment['nodeid']);
			// If viewattachedimages = "thumbnails only (1)" OR  viewattachedimages = "full size if only one image (2)" AND there is more than 1 image
			$showThumbs = ($options['viewattachedimages'] == 1 OR ($options['viewattachedimages'] == 2 AND $attachCount > 1));
			// If viewattachedimages = "full size if only one image (2)" AND there is only 1 image OR viewattachedimages = "Full Size (3)"
			$showFullImg = (($options['viewattachedimages'] == 2 AND $attachCount == 1) OR $options['viewattachedimages'] == 3);
			if ($attachment_node['approved'])
			{
				if ($isImg)
				{
					// Skipping check for (!$this->registry->userinfo['showimages']), as I don't recognize that as a vB5 user option
					// It's in adminCP's user editor, but individual users cannot edit that by themselves.
					if ($showThumbs)
					{
						// use the thumburl
						$parsed['pictureurl'] = $parsed['thumburl'];
						$thumbnailattachments[] = $parsed;
					}
					else if($showFullImg)
					{
						$imageattachments[] = $parsed;
					}
					else
					{
						$imageattachmentlinks[] = $parsed;
					}
				}
				elseif ($isImgLink)
				{
					if ($showThumbs)
					{
						/*
						 * Once thumbnails work for tiff, tif, psd & pdfs (VBV-14569), uncomment below and remove this
						 */
						/*
						// use the thumburl
						$parsed['pictureurl'] = $parsed['thumburl'];
						$thumbnailattachments[] = $parsed;
						 */
						$imageattachmentlinks[] = $parsed;
					}
					else
					{
						$imageattachmentlinks[] = $parsed;
					}
				}
				else
				{
					// If it's not an image, it shouldn't have a pictureurl or thumburl
					unset($parsed['pictureurl']);
					unset($parsed['thumburl']);
					$otherattachments[] = $parsed;
				}
			}
			else
			{
				if (!$isImg AND !$isImgLink)
				{
					// If it's not an image, it shouldn't have a pictureurl or thumburl
					unset($parsed['pictureurl']);
					unset($parsed['thumburl']);
				}
				$moderatedattachments[] = $parsed;
			}
		}

		return array($thumbnailattachments, $imageattachments, $imageattachmentlinks, $otherattachments, $moderatedattachments);
	}

	/**
	 * Creates the "poll" bits for a poll
	 *
	 * @param  Array	$node	node content array, must have content.options
	 *
	 * @return	Array	"poll" array used by mobile app
	 *
	 * @access protected
	 */
	protected function parsePoll($node)
	{
		$pollbits = array();
		if (is_array($node['content']['options']) AND !empty($node['content']['options']))
		{
			foreach ($node['content']['options'] AS $key => $polloption)
			{
				$pollbits[]['option'] = array(
					"optionnumber"  => (int)    $polloption['polloptionid'],
					"question"      => (string) $polloption['title'],
					"votes"         => (int)    $polloption['votes'],
					"percentraw"    => (float)  $polloption['percentage'],
					"uservote"      => (int)    !empty($polloption['uservote']),
				);
			}
		}

		/*
		 So Active is a mystery. There's a poll.active column, which seems to be in
		 node.content.active, but AFAIK there's no code that uses this, and no UI
		 that can close a poll at whim like in vB4.
		 For now, I'm just going to pass the value through from node.content, but
		 perhaps we need to "make up" a different value combining node.showapproved,
		 node.showpublished, poll.timeout, & canvote permissions
		 */
		$active = $node['content']['active'];
		/*
			See http://tracker.vbulletin.com/browse/VBI-1227 for this spec
			Update:
			Actually, see http://tracker.vbulletin.com/browse/VBIV-16132 .
			Apparently vB4 already had the array info, so we're going with
			the already existing spec, + any in VBI-1227 that isn't in the
			previous vB4 spec.
		 */
		$pollinfo = array(
			"pollid"        => (int)    $node['content']['poll_nodeid'],
			"question"      => (string) "", // vB5 doesn't have a separate poll question...
			"numbervotes"   => (int)    $node['content']['poll_votes'],
			"multiple"      => (int)    $node['content']['multiple'],
			"active"        => (int)    $active,
			"timeout"       => (int)    $node['content']['timeout'],
			"has_voted"     => (int)    $node['content']['voted'],
			"public"        => (int)    $node['content']['public'],
		);
		return array(
			"pollinfo"      => $pollinfo,
			"pollbits"      => $pollbits,
		);
	}

	/**
	 * Creates the "link_attachment" bits for a link or video post
	 *
	 * @param  Array	$node	node content array, must have content.url, .url_title & .url_meta, and
	 *							optionally filedataid (link) OR thumburl (video)
	 *
	 * @return	Array	"link_attachment" array used by mobile app
	 *
	 * @access protected
	 */
	protected function parseLinkAndVideo($node)
	{
		$thumburl = '';
		$options = vB::getDatastore()->getValue('options');
		if (!empty($node['content']['filedataid']))
		{
			// If filedatid exists, this link has a thumbnail.
			$thumburl = 'filedata/fetch?linkid=' . $node['nodeid'] . '&type=thumb';
		}
		elseif (!empty($node['content']['thumbnail']))
		{
			// video thumbnail URL pointing to an external source
			$thumburl = $node['content']['thumbnail'];
		}

		$linkTypeid = vB_Types::instance()->getContentTypeId('vBForum_Link');
		$videoTypeid = vB_Types::instance()->getContentTypeId('vBForum_Video');
		$type = '';
		switch ($node['contenttypeid'])
		{
			case $linkTypeid:
				$type = 'link';
				break;
			case $videoTypeid:
				$type = 'video';
				break;
			default:
				// We don't ever expect that this would be called on a non-video or link type node, and we don't really have defined
				// behavior in such a case. This is a debug-only exception to let the dev know to look into what happened.
				$config = vB::getConfig();
				if (!empty($config['Misc']['debug']))
				{
					throw new vB_Exception_Api("Unexpected " . __FUNCTION__ . " call on a non-link/video node, or incorrect contenttypeid.");
				}
				break;
		}

		/*
			Note, looking at the backend code, it seems possible that a node could have multiple videos associated with it (content.items),
			but we don't have UI for that, so I'm going to ignore it.
		 */

		/*
			See http://tracker.vbulletin.com/browse/VBI-1227 for this spec
		 */
		return array(
			'type'     => (string) $type,
			'thumburl' => (string) $thumburl,
			'url'      => (string) $node['content']['url'],
			'title'    => (string) $node['content']['url_title'],
			'meta'     => (string) $node['content']['meta'],
		);
	}

	/**
	 * Creates the "gallery" bits for a gallery post.
	 *
	 * @param  Array	$node	 					node content array,
	 *												must have content.photo
	 * @param	Array	$publicAttachDataArray		Array created by
	 *												separateGalleryPhotosFromAttachment()
	 *
	 * @return	Array	"gallery" array used by mobile app
	 *
	 * @access protected
	 */
	protected function parseGallery($node, $publicAttachDataArray)
	{
		$options = vB::getDatastore()->getValue('options');
		$photoArray = $node['content']['photo'];
		$gallery = array();
		foreach ($photoArray AS $photo)
		{
			/*
			 See VBI-1233 for this spec
			 */
			$pictureurl = 'filedata/fetch?photoid=' . $photo['nodeid'];
			$moreData = $publicAttachDataArray[$photo['nodeid']];
			$parsed = array(
				'attachment' => array(
					'attachmentextension'	=> $moreData['extension'],
					// Yes, the space after the integer is apparently part of spec, and used by
					// the app, according to the mobile devs. VBV-14561
					'filesize' 	=> trim($moreData['filesize']) . ' ',	// Bytes
					// Hack to let attachment.php differentiate legacy vs vB5 attachments.
					'attachmentid' 	=> - intval($photo['nodeid']),
					'filename' 	=> "",	// Photos do not have filenames, but required to pass back per spec.
					'caption' 	=> $photo['caption'],
					'userid' 	=> $photo['userid'],
				),
				'url' =>  $pictureurl,
				'pictureurl' =>  $pictureurl,
				'thumburl' => $pictureurl . "&type=thumb",
			);

			$gallery[] = $parsed;
		}

		return $gallery;
	}

	private function parseBBCode($record)
	{
		$this->bbcode_parser = new vB_Library_BbCode(true, true);
		/*
			We can rarely hit cases where 'attachments' but not 'attach'.
			I haven't been able to reproduce this reliably, so I'm still not sure why
			it happens, but I've seen it happen during a sprint demo. Perhaps it has
			to do with improper caching or the wrong content class being called when
			building up the content information?
			For now, I'll leave the default behavior of using 'attach' when it's available,
			but add a fallback to try 'attachments'	when 'attach' is empty.

			Note: parsePost() will now just unset 'attach' and set relevant non-gallery
				attachments to 'attachments' for simplicity.
		 */
		if (empty($record['content']['attach']) AND !empty($record['content']['attachments']))
		{
			$this->bbcode_parser->setAttachments($record['content']['attachments']);
		}
		else
		{
			$this->bbcode_parser->setAttachments($record['content']['attach']);
		}
		$this->bbcode_parser->setParseUserinfo($record['userid']);

		$authorContext = vB::getUserContext($record['userid']);

		$canusehtml = $authorContext->getChannelPermission('forumpermissions2', 'canusehtml', $record['parentid']);
		require_once DIR . '/includes/functions.php';
		$record['pagetext'] = fetch_censored_text($this->bbcode_parser->doParse(
			$record['content']['rawtext'],
			$canusehtml,
			true,
			true,
			$authorContext->getChannelPermission('forumpermissions', 'cangetattachment', $record['parentid']),
			true
		));

		$record['previewtext'] = $this->bbcode_parser->get_preview($record['content']['rawtext'], 200, false, true);
		return array($record, $this->bbcode_parser->getAttachments());
	}

	protected function separateGalleryPhotosFromAttachment($node)
	{
		$nodeid = $node['nodeid'];
		$apiResult = vB_Api::instance('node')->getNodeAttachmentsPublicInfo(array($nodeid));
		$attachments = array();
		$galleryPhotos = array();
		if (!empty($apiResult[$nodeid]))
		{
			$photoTypeid = vB_Types::instance()->getContentTypeId('vBForum_Photo');
			foreach ($apiResult[$nodeid] AS $attach)
			{
				$attach['extension'] = strtolower($attach['extension']);
				// Gallery photos should not show up in the attachment list.
				if ($attach['contenttypeid'] != $photoTypeid)
				{
					$attachments[$attach['nodeid']] = $attach;
				}
				else
				{
					$galleryPhotos[$attach['nodeid']] = $attach;
				}
			}
		}

		return array($attachments, $galleryPhotos);
	}

	public function parsePost($node)
	{
		$options = vB::getDatastore()->getValue('options');
		if (empty($node['content']))
		{
			$node = vB_Api::instance('node')->getFullContentforNodes(array($node['nodeid']));
			$node = $node[0];
		}

		// Fetch attachments completely separately, and set it to node.content.attachments, and
		// just unset node.content.attach
		list($attachments, $galleryPhotos) = $this->separateGalleryPhotosFromAttachment($node);
		unset($node['content']['attach']);
		$node['content']['attachments'] = $attachments;


		list($node, $attachments) = $this->parseBBCode($node);
		$message = $node['pagetext'];

		$channel_bbcode_permissions = vB_Api::instance('content_channel')->getBbcodeOptions($node['content']['channelid']);
		if ($channel_bbcode_permissions['allowbbcode'] === false)
		{
			$message = $node['content']['rawtext'];
		}

		$topic = array(
			'post' => array(
				'posttime' => $node['publishdate'],
				'postid' => $node['nodeid'],
				'threadid' => $node['starter'],
				'title' => vB_String::unHtmlSpecialChars($node['title']),
				'userid' => $node['userid'],
				'username' => $node['userid'] > 0 ? $node['authorname'] : ((string)new vB_Phrase('global', 'guest')),
				'message' => $message,
				'message_html' => $message,
				'message_plain' => $this->buildMessagePlain($node['content']['rawtext']),
				'message_bbcode' => $node['content']['rawtext'],
				'avatarurl' => $this->avatarUrl($node['userid']),
			),
			'show' => array(
				'replylink' => $node['content']['allow_post'] ? 1 : 0,
				'reportlink' => $node['content']['can_flag'] ? 1 : 0,
				'editlink' => $node['content']['canedit'] ? 1 : 0,
				'moderated' => $node['approved'] ? 0 : 1,
			)
		);

		if (!empty($attachments))
		{
			list(
				$topic['post']['thumbnailattachments'],
				$topic['post']['imageattachments'],
				$topic['post']['imageattachmentlinks'],
				$topic['post']['otherattachments'],
				$topic['post']['moderatedattachments']
			) = $this->parseAttachments($attachments);

			if (!empty($topic['post']['thumbnailattachments']))
			{
				// todo check thubmcount vs attachrow
				$thumbCount = count($topic['post']['thumbnailattachments']);
				if ($options['attachrow'] AND ($thumbCount > $options['attachrow']))
				{
					$topic['show']['br'] = 1;
				}
				$topic['show']['thumbnailattachment'] = 1;
			}
			else
			{
				unset($topic['post']['thumbnailattachments']);
			}

			if (!empty($topic['post']['imageattachments']))
			{
				$topic['show']['imageattachment'] = 1;
			}
			else
			{
				unset($topic['post']['imageattachments']);
			}

			if (!empty($topic['post']['imageattachmentlinks']))
			{
				$topic['show']['imageattachmentlink'] = 1;
			}
			else
			{
				unset($topic['post']['imageattachmentlinks']);
			}


			if (!empty($topic['post']['otherattachments']))
			{
				$topic['show']['otherattachment'] = 1;
			}
			else
			{
				unset($topic['post']['otherattachments']);
			}


			if (!empty($topic['post']['moderatedattachments']))
			{
				$topic['show']['moderatedattachment'] = 1;
			}
			else
			{
				unset($topic['post']['moderatedattachments']);
			}


		}

		// Galleries, VBV-14561
		if (!empty($node['content']['photo']))
		{
			$topic['post']['gallery'] = $this->parseGallery($node, $galleryPhotos);
		}

		// Video & Links, VBV-14802
		$linkTypeid = vB_Types::instance()->getContentTypeId('vBForum_Link');
		$videoTypeid = vB_Types::instance()->getContentTypeId('vBForum_Video');
		if ($node['contenttypeid'] == $linkTypeid OR $node['contenttypeid'] == $videoTypeid)
		{
			$topic['post']['link_attachment'] = $this->parseLinkAndVideo($node);
		}

		// Poll, VBV-15049
		// According to mobile devs, the 'poll' block has to be OUTSIDE of the posts block,
		// right under "response"... No, this wouldn't work if someone added the UI to allow
		// polls to be added at reply-levels. Also, this wouldn't work for search results.
		// Mobile team has been informed of these potential issues, but it's 4AM so I'm just
		// going to get their requested fixes in...
		$poll = array();
		$pollTypeid = vB_Types::instance()->getContentTypeId('vBForum_Poll');
		if ($node['contenttypeid'] == $pollTypeid)
		{
			$poll = $this->parsePoll($node);
		}


		if(!empty($node['content']['deleteusername']))
		{
			$topic['post']['del_username'] = $node['content']['deleteusername'];
			$topic['show']['deletedpost'] = 1;
		}

		$user = vB_Api::instance('user')->fetchUserinfo();
		// We have this option in vB5
		// I don't think we should use it in this case though
		// $vboptions = vB::getDatastore()->getValue('options');
		// $showinline = $vboptions['showsignaturesinline']

		if ($user['showsignatures'])
		{
			$topic['post']['signature'] = vB_Api::instance('bbcode')->parseSignature($node['userid']);
		}
		else
		{
			$topic['post']['signature'] = '';
		}
		return array($topic, $poll);
	}

	//
	//	This is a dirty hack to satisfy HV checks on the
	//	forum. MAPI clients currently require this
	//	circumvention to function.
	//
	public function getHVToken()
	{
		require_once(DIR . '/includes/class_humanverify.php');
		$verify =& vB_HumanVerify::fetch_library(vB::get_registry());
		$token = $verify->generate_token();
		$ret = array('input' => $token['answer'], 'hash' => $token['hash']);
		return $ret;
	}

	public function getErrorResponse($result)
	{
		if (!empty($result['errors']))
		{
			//in theory we should never see 'not_logged_no_permission' when userid is not 0
			//but checking doesn't hurt anything and avoids a special case.  If it
			//does occur that way handling it as a "loggedin" error makes as much sense
			//as anything else.
			$error_code = $result['errors'][0][0];
			$permission_errors = array('no_create_permissions', 'not_logged_no_permission');

			if(in_array($error_code, $permission_errors))
			{
				$userid = vB::getCurrentSession()->get('userid');
				if ($userid == 0)
				{
					$error_code = 'nopermission_loggedout';
				}
				else
				{
					$error_code = 'nopermission_loggedin';
				}
			}

			return array('response' => array('errormessage' => $error_code));
		}
		return array('response' => array('errormessage' => 'unknownerror'));
	}

	public function filterUserInfo($userinfo)
	{
		return array(
			'username' => $userinfo['username'],
			'userid' => $userinfo['userid'],
		);
	}

	public function parseThread($node)
	{
		$status = array();
		if (!$node['open'])
		{
			$status['lock'] = 1;
		}
		$topic = array(
			'thread' => array(
				'prefix_rich' => $this->getPrefixTitle($node['prefixid']),
				'forumid' => $node['content']['channelid'],
				'forumtitle' => $node['content']['channeltitle'],
				'threadid' => $node['nodeid'],
				'threadtitle' => vB_String::unHtmlSpecialChars($node['title']),
				'postusername' => $node['userid'] > 0 ? $node['authorname'] : ((string)new vB_Phrase('global', 'guest')),
				'postuserid' => $node['userid'],
				'starttime' => $node['content']['publishdate'],
				'replycount' => $node['textcount'],
				'status' => $status,
				'views' => (isset($node['content']['views']) ? $node['content']['views'] : 0),
				'sticky' => $node['sticky'],
				'typeprefix' => '',
			),
			'userinfo' => $this->filterUserInfo($node['content']['userinfo']),
			'avatar' => array(
				'hascustom' => 1,
				'0' => $this->avatarUrl($node['userid']),
			),
			'show' => array(
				'moderated' => $node['approved'] ? 0 : 1,
				'sticky' => $node['sticky'] ? 1 : 0,
			),
		);
		if ($node['sticky'])
		{
			$phrase = vB_Api::instance('phrase')->fetch(array('sticky_thread_prefix'));
			$topic['thread']['typeprefix'] = $phrase['sticky_thread_prefix'];
		}
		if(!empty($node['deleteuserid']))
		{
			$topic['thread']['del_userid'] = $node['deleteuserid'];
		}
		if(!empty($node['lastcontentauthor']))
		{
			$topic['thread']['lastposter'] = $node['content']['lastcontentauthor'];
			$topic['thread']['lastposttime'] = $node['content']['lastcontent'];
			$topic['thread']['lastpostid'] = $node['content']['lastcontentid'];
		}
		else
		{
			$topic['thread']['lastposter'] = $node['authorname'];
			$topic['thread']['lastposttime'] = $node['created'];
			$topic['thread']['lastpostid'] = $topic['threadid'];
		}

		return $topic;
	}

	private function getPrefixTitle($prefixid)
	{
		$phrases = vB_Api::instance('phrase')->fetch(array('prefix_' . $prefixid . '_title_rich'));

		$ret = $phrases['prefix_' .  $prefixid . '_title_rich'];
		if ($ret === null)
		{
			$ret = "";
		}
		return $ret;
	}

	public function getPrefixes($channel)
	{
		$prefixes = vB_Api::instance('prefix')->fetch($channel);
		if (empty($prefixes))
		{
			return '';
		}

		$out = array();
		foreach($prefixes as $prefix_group_label => $prefix_group)
		{
			$options = array();
			foreach($prefix_group as $prefix_option)
			{
				$options[] = array(
					'optiontitle' => $this->getPrefixTitle($prefix_option['prefixid']),
					'optionvalue' => $prefix_option['prefixid'],
				);
			}
			$out[] = array(
				'optgroup_label' => "$prefix_group_label",
				'optgroup_options' => $options,
			);
		}
		return $out;
	}

	public function getUsersBlogChannel()
	{
		$userinfo = vB_Api::instance('user')->fetchUserinfo();
		$global_blog_channel = vB_Api::instance('blog')->getBlogChannel();
		$search = array(
			'type' => 'vBForum_Channel',
			'channel' => $global_blog_channel,
		);
		$result = vB_Api::instance('search')->getInitialNodes($search);

		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}
		foreach ($result['nodeIds'] as $node)
		{
			$node_owner = vB_Api::instance('blog')->fetchOwner($node);
			if ($node_owner === $userinfo['userid'])
			{
				return $node;
			}
		}
		return null;
	}

	public function getGlobalBlogCategories()
	{
		// TODO: Implement when vB5 adds them
		return array();
	}

	public function getLocalBlogCategories($userid = 0)
	{
		if (!$userid)
		{
			$userinfo = vB_Api::instance('user')->fetchUserinfo();
			$userid = $userinfo['userid'];
		}
		// TODO: Implement when vB5 adds them
		return array();
	}

	public function parseBlogComment($node)
	{
		list($bbcode,) = $this->parseBBCode($node);
		return array(
			'response' => array(
				'blogtextid' => $node['nodeid'],
				'userid' => $node['userid'],
				'username' => $node['authorname'],
				'time' => $node['publishdate'],
				'avatarurl' => $this->avatarUrl($node['userid']),
				'message_plain' => $this->buildMessagePlain($node['content']['rawtext']),
				'message' => $bbcode['pagetext'],
				'message_bbcode' => $node['content']['rawtext'],
			),
		);
	}

	public function parseBlogHeader($node)
	{
		$result = vB_Api::instance('node')->getNode($node['content']['channelid']);
		return array(
			'blogheader' => array(
				'userid' => $node['content']['starteruserid'],
				'title' => $node['content']['channeltitle'],
				'blog_title' => $node['content']['channeltitle'],
				'description' => $result['description'],
			),
			'userinfo' => array(
				'username' => $node['content']['starterauthorname'],
				'avatarurl' => $this->avatarUrl($node['content']['starteruserid']),
			),
		);
	}

	public function parseBlogEntrySearch($node)
	{
		return array(
			'blog' => array(
				'blogid' => $node['nodeid'],
				'blogposter'	=> $node['authorname'],
				'postedby_username'	=> $node['authorname'],
				'title'	=> $node['title'],
				'lastposttime' => $node['lastupdate'],
				'time' => $node['publishdate'],
				'blogtitle' => $node['title'],
				'message' => $node['content']['rawtext'],
				'message_bbcode' => $node['content']['rawtext'],
				'message_plain' => $this->buildMessagePlain($node['content']['rawtext']),
				'comments_total' => $node['content']['startertotalcount'] - 1,
			),
			'userinfo' => $this->filterUserInfo($node['content']['userinfo']),
			'avatar' => array(
				'hascustom' => 1,
				'0' => $this->avatarUrl($node['userid']),
			),

		);
	}

	public function parseBlogEntry($node)
	{
		list($bbcode,$attachments) = $this->parseBBCode($node);
		$blog = array(
			'blog' => array(
				'blogid' => $node['nodeid'],
				'postedby_username'	=> $node['authorname'],
				'title'	=> html_entity_decode($node['title']),
				'time' => $node['publishdate'],
				'avatarurl'	=> $this->avatarUrl($node['userid']),
				'blogtitle' => $node['content']['channeltitle'],
				'message' => $bbcode['pagetext'],
				'message_html' => $bbcode['pagetext'],
				'message_bbcode' => $node['content']['rawtext'],
				'message_plain' => $this->buildMessagePlain($node['content']['rawtext']),
				'comments_total' => $node['content']['startertotalcount'] - 1,
			),
			'show' => array(
				'postcomment' => ($node['content']['canreply'] > 0 ? 1 : 0),
			),
		);

		// Video & Links, VBV-14802
		// The hasContent check is here because I'm less certain about where the $node data comes from, so
		// I can't be sure that it's the "full" array with the "content" subarray.
		$linkTypeid = vB_Types::instance()->getContentTypeId('vBForum_Link');
		$videoTypeid = vB_Types::instance()->getContentTypeId('vBForum_Video');
		$isLink = ($node['contenttypeid'] == $linkTypeid OR $node['contenttypeid'] == $videoTypeid);
		$hasContent = !empty($node['content']);
		if ($isLink AND $hasContent)
		{
			$blog['blog']['link_attachment'] = $this->parseLinkAndVideo($node);
		}

		return array($blog, $attachments);
	}

	public function parseArticleSearch($node, $parent)
	{
		$content = $node['content'];
		$article = array(
				'title'	=>  vB_String::unHtmlSpecialChars($content['title']),
				'html_title' => $content['title'],
				'username' => $content['authorname'],
				'description' => $content['description'],
				'parenttitle' => vB_String::unHtmlSpecialChars($parent['title']),
				'parentid' => $content['parentid'],
				'previewtext' => $content['previewtext'],
				'publishtime' => $content['publishdate'],
				'replycount' => $content['textcount'],
				'page_url' => vB5_Route::buildUrl($node['routeid'] . '|fullurl', $node),
				'parent_url' => vB5_Route::buildUrl($parent['routeid'] . '|fullurl', $parent),
				'lastposterinfo' => array(
					'userid' => $content['lastauthorid'],
					'username' => $content['lastcontentauthor']
				),
				'avatar' => array(
					'hascustom' => $content['avatar']['hascustom'],
					'0' => $content['avatar']['avatarpath'],
					'1' => ''
				),
				'article' => array(
					'contentid' => $content['nodeid'],
					'nodeid' => $content['nodeid'],
					'username' => $content['authorname'],
					'userid' => $content['userid'],
					'publishtime' => $content['publishdate'],
					'title'	=>  vB_String::unHtmlSpecialChars($content['title'])
				),
				//this is one of the things we didn't really map from vB4,
				//I don't think it affects the app
				'categories' => array(),

				//don't think it affects the app.  Not clear on what the value should be
				'show' => array()
		);

		return $article;
	}

	public function parseForumInfo($node)
	{
		return array(
			'forumid' => $node['nodeid'],
			'title' => $node['title'],
			'description'	=> $node['description'],
			'title_clean'	=> $node['htmltitle'],
			'description_clean'	=> strip_tags($node['description']),
			'prefixrequired' => 0,
		);
	}

	public function parseThreadInfo($node)
	{
		$info = array(
			'title' => vB_String::unHtmlSpecialChars($node['title']),
			'threadid' => $node['nodeid'],
		);
		return $info;
	}

	//
	// Used solely with output from
	// vB_Api_Node->fetchChannelNodeTree
	//
	public function parseForum($node)
	{
		$subforums = array();
		if (isset($node['subchannels']) AND !empty($node['subchannels']))
		{
			foreach ($node['subchannels'] as $subforum)
			{
				$subforums[] = $this->parseForum($subforum);
			}
		}
		$top = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();
		$top = $top['forum'];
		return array(
			'parentid'      => $node['parentid'] == $top ? -1 : $node['parentid'],
			'forumid' 		=> $node['nodeid'],
			'title'			=> $node['title'],
			'description'	=> $node['description'] !== null ? $node['description'] : '',
			'title_clean'	=> strip_tags($node['title']),
			'description_clean'	=> strip_tags($node['description']),
			'threadcount'		=> $node['textcount'],
			'replycount'	=> $node['totalcount'],
			'lastpostinfo' 	=> array(
				'lastthreadid' => $node['lastcontent']['nodeid'],
				'lastthreadtitle' => $node['lastcontent']['title'],
				'lastposter' => $node['lastcontent']['authorname'],
				'lastposterid'	=> $node['lastcontent']['userid'],
				'lastposttime' => $node['lastcontent']['created'],
			),
			'is_category'   => $node['category'],
			'is_link'       => 0,
			'subforums' 	=> $subforums,
		);
	}

	private function buildMessagePlain($message)
	{
		//need to do this before doing the attachment tag replacement
		//otherwise we'll strip the text we just added.
		$newmessage = strip_tags($message);

		// Modify attach tags
		$regex = '#\[(attach)(?>[^\]]*?)\](.*)(\[/\1\])#siU';
		$newmessage = preg_replace($regex, '<attachment:'.trim("$2").'>', $newmessage);
		$newmessage = strip_bbcode($newmessage);
		return $newmessage;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89197 $
|| #######################################################################
\*=========================================================================*/
