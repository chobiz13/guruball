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
 * vB_Api_Content_Gallery
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id: gallery.php 85158 2015-07-31 17:22:34Z jinsoojo.ib $
 * @access public
 */
class vB_Api_Content_Gallery extends vB_Api_Content_Text
{
	//override in client- the text name
	protected $contenttype = 'vBForum_Gallery';

	//The table for the type-specific data.
	protected $tablename = array('gallery', 'text');

	//We need the primary key field name.
	protected $primarykey = 'nodeid';

	//Whether we change the parent's text count- 1 or zero
	protected $textCountChange = 1;

	//Does this content show author signature?
	protected $showSignature = true;

	//Whether we handle showapproved,approved fields internally or not
	protected $handleSpecialFields = 1;

	//Is text required for this content type?
	protected $textRequired = false;

	/**
	 * Normal constructor- protected to prevent direct instantiation
	 */
	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('Content_Gallery');
	}

	/**
	 * Adds a new node.
	 *
	 * @param  array Array of field => value pairs which define the record.
	 * @param  array Array of options for the content being created
	 *               Understands skipTransaction, skipFloodCheck, floodchecktime, skipDupCheck, skipNotification, nl2br, autoparselinks.
	 *               - nl2br: if TRUE, all \n will be converted to <br /> so that it's not removed by the html parser (e.g. comments).
	 *               - wysiwyg: if true convert html to bbcode.  Defaults to true if not given.
	 *
	 * @return int   the new nodeid
	 */
	public function add($data, $options = array())
	{
		vB_Api::instanceInternal('hv')->verifyToken($data['hvinput'], 'post');

		$result = $this->validateGalleryData($data, $options['filedataid']);

		if ((vB_Api::instanceInternal('node')->fetchAlbumChannel() == $data['parentid']) AND (!vB::getUserContext()->hasPermission('albumpermissions', 'picturefollowforummoderation')))
		{
			$data['approved'] = 0;
			$data['showapproved'] = 0;
		}

		//viewperms can only be 0, 1, or 2
		if (!isset($data['viewperms']) OR ($data['viewperms'] > 2) OR ($data['viewperms'] < 0))
		{
			$data['viewperms'] = 2;
		}

		if ($result === true)
		{
			return parent::add($data, $options);
		}
		else
		{
			return $result;
		}

	}

	/**
	 * Returns the node indexable node text
	 *
	 * @param  int   The id in the primary table
	 *
	 * @return array title and caption
	 */
	public function getIndexableFromNode($node, $include_attachments = true)
	{
		$indexableContent['title'] = $content[$nodeId]['title'];
		$indexableContent['caption'] = $content[$nodeId]['caption'];
		return $indexableContent;
	}

	/**
	 * Updates from a web save
	 *
	 * @param  int The id in the primary table
	 *
	 * @return int Number of updates-standard save response.
	 */
	public function updateFromWeb($nodeid, $postdata, $filedataids = array())
	{
		//First do we have a nodeid?
		if (!$nodeid OR !intval($nodeid) OR !$this->validate($postdata, parent::ACTION_UPDATE, $nodeid))
		{
			throw new Exception('invalid_data');
		}
		$data = array();
		//And are we authorized to make changes?
		if (!$this->validate($data, parent::ACTION_UPDATE, $nodeid))
		{
			throw new Exception('no_permission');
		}

		if (isset($postdata['title']))
		{
			$postdata['urlident'] = vB_String::getUrlIdent($postdata['title']);
		}

		$existing = $this->getContent($nodeid);
		$existing = $existing[$nodeid];
		$cleaner = vB::getCleaner();
		//clean the gallery data.
		$fields = array('title' => vB_Cleaner::TYPE_STR,
			'caption' => vB_Cleaner::TYPE_STR,
			'htmltitle' => vB_Cleaner::TYPE_STR,
			'rawtext' => vB_Cleaner::TYPE_STR,
			'reason' => vB_Cleaner::TYPE_STR,
			'keyfields' => vB_Cleaner::TYPE_STR,
			'publishdate' => vB_Cleaner::TYPE_UINT,
			'unpublishdate' => vB_Cleaner::TYPE_UINT,
			'description' => vB_Cleaner::TYPE_STR,
			'displayorder' => vB_Cleaner::TYPE_UINT,
			'urlident' => vB_Cleaner::TYPE_STR,
			'tags' => vB_Cleaner::TYPE_STR,
			'allow_post' => vB_Cleaner::TYPE_BOOL,
			'parentid' => vB_Cleaner::TYPE_UINT,
			'viewperms' => vB_Cleaner::TYPE_UINT,
			'attachments' => vB_Cleaner::TYPE_NOCLEAN,	//cleaned separately below
			'removeattachments' => vB_Cleaner::TYPE_NOCLEAN,	//''
		);

		// copy data before they're dropped by the cleaner. These will be cleaned separately just a few lines down.
		$unclean['attachments'] = isset($postdata['attachments'])?$postdata['attachments']:array();
		$unclean['removeattachments'] = isset($postdata['removeattachments'])?$postdata['removeattachments']:array();

		$cleaned = $cleaner->cleanArray($postdata, $fields);
		// just unset the uncleaned ones. They're cleaned & set again below. I would've just unset them from
		// $postdata before it was tossed into cleanArray(), but there's special logic a few blocks down that
		// requires keys in $fields to be set in both $postdata & $cleaned for it to be sent into update()
		unset($cleaned['attachments']);
		unset($cleaned['removeattachments']);
		if (!isset($postdata['allow_post']))
		{
			// Apparently (copy pasted from createcontent getArticleInput()) :
			//do not set if not provide, use the API default values.  Otherwise things like the forums which aren't thinking about it
			//get set incorrectly.
			unset($cleaned['allow_post']);
		}

		if (!empty($unclean['attachments']))
		{
			// keep these fields in sync with controller's addAttachments()
			$attachfields = array(
				'filedataid' => vB_Cleaner::TYPE_UINT,
				'filename' => vB_Cleaner::TYPE_STR,
				'settings' => vB_Cleaner::TYPE_STR,
			);
			foreach ($unclean['attachments'] AS $key => $attachdata)
			{
				$key = (int) $key;
				$cleaned['attachments'][$key] = $cleaner->cleanArray($attachdata, $attachfields);
			}
			unset($unclean['attachments']);
		}

		if (!empty($unclean['removeattachments']))
		{
			// keep these fields in sync with controller's addAttachments()
			foreach ($unclean['removeattachments'] AS $key => $attachnodeid)
			{
				$key = (int) $key;
				$cleaned['removeattachments'][$key] = (int) $attachnodeid;
			}
			unset($unclean['removeattachments']);
		}

		$updates = array();

		//viewperms can only be 0, 1, or 2
		if (empty($cleaned['viewperms']) OR ($cleaned['viewperms'] > 2) OR ($cleaned['viewperms'] < 0))
		{
			$cleaned['viewperms'] = 2;
		}
		/*
		 *	Okay, I"m pretty sure the below isn't doing what was originally intended,
		 *	judging by the comment & the fact that we grab the pre-update node values,
		 *	$existing, above.
		 *	I'm guessing what was *supposed* to happen is that each $iteam in $cleaned
		 *	is compared against $existing's data, and is set to $updates only if it's
		 *	different.
		 *	However, there's been a lot of changes to the various bits of content
		 *	update() code, so I'm afraid to unset things from $updates now.
		 *	If anyone is going to edit below, make sure that handling for
		 *	$cleaned['attachments']and $cleaned['removeattachments'] are proplery
		 *	dealt with, otherwise there will be issues when editing a gallery post
		 *	and adding/removing attachments.
		 */
		//If nothing has changed we don't need to update the parent.
		foreach (array_keys($fields) as $fieldname)
		{
			if (isset($postdata[$fieldname]) AND isset($cleaned[$fieldname]))
			{
				$updates[$fieldname] = $cleaned[$fieldname];
			}
		}
		$results = true;
		if (!empty($updates))
		{
			$results = $this->update($nodeid, $updates);
		}

		if ($results AND (!is_array($results) OR empty($results['errors'])))
		{
			//let's get the current photo information;

			$existing = $this->getContent($nodeid);
			$existing = $existing[$nodeid];

			if (empty($existing['photo']))
			{
				$delete = array();
			}
			else
			{
				$delete = $existing['photo'];
			}

			//Now we match the submitted data against the photos
			//if they match, we remove from "delete" and do nothing else.
			//if the title is updated we do an immediate update.
			//Otherwise we add.
			if (!empty($filedataids) AND is_array($filedataids))
			{
				$photoApi = vB_Api::instanceInternal('content_photo');

				foreach ($filedataids AS $filedataid => $title)
				{
					//it has to be at least a integer.
					if (intval($filedataid))
					{
						//First see if we have a match.
						$foundMatch = false;
						foreach ($delete as $photoNodeid => $photo)
						{
							if ($filedataid == $photo['filedataid'])
							{
								$foundMatch = $photo;
								unset($delete[$photoNodeid]);
								break;
							}
						}

						if ($foundMatch)
						{
							if ($title != $foundMatch['title'])
							{
								$titles[$foundMatch['nodeid']] = $title;
							}
							//unset this record.

							//Skip to the next record
							continue;
						}
						//If we got here then this is new and must be added.
						//We do an add.
						$photoApi->add(array('parentid' => $nodeid,
							'caption' => $title, 'title' => $title, 'filedataid' => intval($filedataid)));
					}

				}
				if (!empty($delete))
				{
					foreach ($delete as $photo)
					{
						$photoApi->delete($photo['nodeid']);
					}
				}

				if (!empty($titles))
				{
					foreach ($titles as $photonodeid => $title)
					{
						$photoApi->update($photonodeid, array('caption' => $title, 'title' => $title));
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Adds content info to $result so that merged content can be edited.
	 *
	 * @param array $result
	 * @param array $content
	 */
	public function mergeContentInfo(&$result, $content)
	{
		if (vb::getUserContext()->getChannelPermission('forumpermissions', 'canviewthreads', $result['nodeid']))
		{
			$this->library->mergeContentInfo($result, $content);
		}
	}

	/**
	 * validates that the current can create a node with these values
	 *
	 * @param  array  Array of field => value pairs which define the record.
	 * @param  string Parameters to be checked for permission
	 *
	 * @return bool
	 */
	public function validate(&$data, $action = self::ACTION_ADD, $nodeid = false, $nodes = false)
	{
		//One extra check. If the node would otherwise be viewable but viewperms is zero for an album, the the current user
		//is the owner or follows the owner, they can see it.
		if (parent::validate($data, $action, $nodeid, $nodes))
		{
			return true;
		}

		if ($action == self::ACTION_VIEW)
		{
			if (empty($data) AND !empty($nodeid))
			{
				$data = vB_Library::instance('node')->getNodeBare($nodeid);
			}

			if (isset($data['nodeid']) AND isset($data['userid']) AND isset($data['parentid']) AND isset($data['viewperms']))
			{
				$nodes = array($data);
			}
			else
			{
				if (!is_array($nodeid))
				{
					$nodeid = array($nodeid);
				}

				if (!$nodes)
				{
					$nodes = vB_Api::instanceInternal('node')->getNodes($nodeid);
				}
			}

			$albumChannel = vB_Library::instance('node')->fetchAlbumChannel();
			$following = vB_Api::instanceInternal('follow')->getFollowingParameters();

			if (empty($following['user']))
			{
				$following = array(vB::getCurrentSession()->get('userid'));
			}
			else
			{
				$following = $following['user'];
				$following[] = vB::getCurrentSession()->get('userid');
			}

			foreach ($nodes AS $node)
			{
				if (($node['parentid'] != $albumChannel) OR ($node['viewperms'] != 0) OR !in_array($node['userid'], $following ))
				{
					return false;
				}
			}
			//If we got here all is O.K.
			return true;
		}
		return false;
	}


	/**
	 * Validates the gallery data
	 *
	 * @param  array info about the photos
	 *
	 * @return bool
	 */
	protected function validateGalleryData($data, $filedataid)
	{
		$usercontext = vB::getUserContext();
		$albumChannel = vB_Api::instanceInternal('node')->fetchAlbumChannel();

		if (!empty($data['parentid']) AND $data['parentid'] == $albumChannel AND !$usercontext->hasPermission('albumpermissions', 'canviewalbum'))
		{
			throw new vB_Exception_Api('no_permission');
		}

		$albummaxpic = $usercontext->getLimit('albummaxpics');

		if (!empty($albummaxpic))
		{
			$overcount = count($filedataid) - $albummaxpic;
			if($overcount > 0)
			{
				throw new vB_Exception_Api('upload_album_pics_countfull_x', array($overcount));
			}
		}

		return true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85158 $
|| #######################################################################
\*=========================================================================*/
