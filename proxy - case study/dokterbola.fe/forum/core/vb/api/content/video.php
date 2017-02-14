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
 * vB_Api_Content_Video
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id: video.php 84682 2015-04-27 20:05:21Z ksours $
 * @access public
 */
class vB_Api_Content_Video extends vB_Api_Content_Text
{
	//override in client- the text name
	protected $contenttype = 'vBForum_Video';

	//The table for the type-specific data.
	protected $tablename = array('video', 'text');

	//Whether we handle showapproved,approved fields internally or not
	protected $handleSpecialFields = 1;

	//Is text required for this content type?
	protected $textRequired = false;

	const THUMBNAIL_TTL = 432000; //5 days

	/**
	 * Constructor, no external instantiation
	 */
	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('Content_Video');
	}


	/**
	 * Adds a new node.
	 *
	 * @param  mixed   Array of field => value pairs which define the record.
	 * @param  array   Array of options for the content being created.
	 *                 Understands skipTransaction, skipFloodCheck, floodchecktime, skipDupCheck, skipNotification, nl2br, autoparselinks.
	 *                 - nl2br: if TRUE, all \n will be converted to <br /> so that it's not removed by the html parser (e.g. comments).
	 *                 - wysiwyg: if true convert html to bbcode.  Defaults to true if not given.
	 *
	 * @return integer the new nodeid
	 */
	public function add($data, $options = array())
	{
		vB_Api::instanceInternal('hv')->verifyToken($data['hvinput'], 'post');

		if ((vB_Api::instanceInternal('node')->fetchAlbumChannel() == $data['parentid']) AND (!vB::getUserContext()->hasPermission('albumpermissions', 'picturefollowforummoderation')))
		{
			$data['approved'] = 0;
			$data['showapproved'] = 0;
		}

		return parent::add($data, $options);
	}

	/**
	 * Returns the indexable fields for this content type
	 *
	 * @param  array Node information
	 * @param  bool  Whether or not to include attachement fields
	 *
	 * @return array Indexable fields.
	 */
	public function getIndexableFromNode($node, $include_attachments = true)
	{
		$indexableContent['title'] = $node['title'];
		$indexableContent['rawtext'] = $node['rawtext'];
		return $indexableContent;
	}

	/**
	 * Get information from video's URL.
	 * This method makes use of bbcode_video table to get provider information
	 *
	 * @param  string     $url
	 *
	 * @return array|bool Video data. False if the url is not supported or invalid
	 */
	public function getVideoFromUrl($url)
	{
		return $this->library->getVideoFromUrl($url);
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
	 * Extracts the thumbnail from og:image meta data
	 *
	 * @param  string url of video
	 * @param  int    optional nodeid of video node
	 *
	 * @return mixed  url string or false
	 */
	public function getVideoThumbnail($url, $nodeid = false)
	{
		//Note that this is called from the template, and often there will be no ndeid
		if (!empty($nodeid))
		{
			$video = $this->getContent($nodeid);

			if (!empty($video))
			{
				$video= array_pop($video);
			}
		}

		if (empty($video))
		{
			//Try to get from cache first.
			$cacheKey = 'vB_Vid' . md5($url);
			$check = vB_Cache::instance(vB_Cache::CACHE_LARGE)->read($cacheKey);

			if (!empty($check))
			{
				return $check;
			}
			$video = $this->assertor->getRow('vBForum:video', array('url' => $url));
		}

		//check if we have the thumbnail in the video
		if (
				!empty($video)
				AND !empty($video['thumbnail'])
				AND !empty($video['thumbnail_date'])
				//if the thumbnail is too old we need to fetch a fresh one, it might have been updated
				AND ($video['thumbnail_date'] >= (vB::getRequest()->getTimeNow() - self::THUMBNAIL_TTL)))
		{
			return $video['thumbnail'];
		}
		$data = vB_Api::instance('content_link')->parsePage($url);

		if (!empty($data['images']))
		{
			$thumbnail = $data['images'];

			// only return the first image. May want to change this later after product audit?
			if (is_array($thumbnail))
			{
				$thumbnail = $thumbnail[0];
			}

			//save the thumbnail so we don't have to fetch it next time it's needed
			if (!empty($video))
			{
				//there is a video record.  Put it there.
				$this->assertor->update('vBForum:video', array('thumbnail' => $thumbnail, 'thumbnail_date' => vB::getRequest()->getTimeNow()), array('nodeid' => $video['nodeid']));
				vB_Cache::allCacheEvent('nodeChg_' . $video['nodeid']);
			}
			else
			{
				//put into cache
				if (empty($cacheKey))
				{
					$cacheKey = 'vB_Vid' . md5($url);
				}

				vB_Cache::instance(vB_Cache::CACHE_LARGE)->write($cacheKey, $thumbnail,  self::THUMBNAIL_TTL);
			}

			return $thumbnail;
		}

		// we should probably have a default placeholder
		// we can return in case no image is found..
		return false;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84682 $
|| #######################################################################
\*=========================================================================*/
