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
 * vB_Api_Filedata
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id: filedata.php 85576 2015-09-30 20:22:29Z jinsoojo.ib $
 * @access public
 */
class vB_Api_Filedata extends vB_Api
{
	/**#@+
	* Allowed resize labels
	*/
	const SIZE_ICON	  = 'icon';
	const SIZE_THUMB  = 'thumb';
	const SIZE_SMALL  = 'small';
	const SIZE_MEDIUM = 'medium';
	const SIZE_LARGE  = 'large';
	const SIZE_FULL   = 'full';
	/**#@-*/

	/**
	 * Contains white listed methods which act as normal when API is disabled
	 * no matter of special scenarios like forum closed, password expiry, ip ban and others.
	 *
	 * @var array $disableWhiteList
	 */
	protected $disableWhiteList = array(
		'fetchImageByFiledataid', // used applicationLight fetchImage for custom logos
	);

	/*
	 * Ensures that Sent in thumbnail type is valid
	 *
	 * @param	mixed	Image size to get
	 *
	 * @return	string	Valid image size to get
	 */
	public function sanitizeFiletype($type)
	{
		if ($type == 1 OR $type === true OR $type === 'thumbnail')
		{
			$type = vB_Api_Filedata::SIZE_THUMB;
		}

		$options = vB::getDatastore()->get_value('options');
		$sizes = @unserialize($options['attachresizes']);
		if (!isset($sizes[$type]) OR empty($sizes[$type]))
		{
			$type = vB_Api_Filedata::SIZE_FULL;
		}

		switch ($type)
		{
			case vB_Api_Filedata::SIZE_ICON:
			case vB_Api_Filedata::SIZE_THUMB:
			case vB_Api_Filedata::SIZE_SMALL:
			case vB_Api_Filedata::SIZE_MEDIUM:
			case vB_Api_Filedata::SIZE_LARGE:
				break;
			default:
				$type = vB_Api_Filedata::SIZE_FULL;
		}

		return $type;
	}

	/** fetch image information about an attachment based on file data id
	 *
	 * 	@param 	int		filedataid
	 * 	@param	mixed	size requested
	 * 	@param	bool	should we include the image content
	 *
	 *	@return	mixed	array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 **/
	public function fetchImageByFiledataid($id, $type = vB_Api_Filedata::SIZE_FULL, $includeData = true)
	{
		if (empty($id) OR !is_numeric($id) OR !intval($id))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		$type = $this->sanitizeFiletype($type);

		//If the record belongs to this user, or if this user can view attachments
		//in this section, then this is O.K.
		$userinfo = vB::getCurrentSession()->fetch_userinfo();

		$params = array('filedataid' => $id, 'type' => $type);
		$record = vB::getDbAssertor()->getRow('vBForum:getFiledataContent', $params);

		if (empty($record))
		{
			return false;
		}

		if (($userinfo['userid'] == $record['userid']) OR ($record['publicview'] > 0))
		{
			$imageHandler = vB_Image::instance();

			return $imageHandler->loadFileData($record, $type, $includeData);
		}

		throw new vB_Exception_Api('no_view_permissions');
	}

	/** fetch filedata records based on filedata ids
	 *
	 * 	@param 	array/int		filedataids
	 *
	 *	@return	mixed	array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 **/
	public function fetchFiledataByid($ids)
	{
		if (empty($ids) OR !is_array($ids))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		//If the record belongs to this user, or if this user can view attachments
		//in this section, then this is O.K.
		$userinfo = vB::getCurrentSession()->fetch_userinfo();

		$records = vB::getDbAssertor()->assertQuery('vBForum:getFiledataWithThumb', array('filedataid' => $ids));
		$filedatas = array();
		foreach ($records as $record)
		{
			if (($userinfo['userid'] == $record['userid']) OR ($record['publicview'] > 0))
			{
				$record['visible'] = $record['publicview'];
				$record['counter'] = $record['refcount'];
				$record['filename'] = $record['filehash'] . '.' . $record['extension'];
				$filedatas[$record['filedataid']] = $record;
			}
		}
		return $filedatas;
	}

	/**
	 * Returns filedataids, filenames & other publicly visible properties of requested legacy attachments.
	 * Also contains 'cangetattachment' and 'cangetimgattachment' which is specific to the current user.
	 *
	 * @param	int[]	$ids	Integer array of legacy attachment ids (stored in `node`.oldid in vB5)
	 *
	 * @return Array	Array(
	 *						{oldid1} => array(attachment information),
	 *						{oldid2} => array(attachment information),
	 *					)
	 *					Where attachment information contains
	 *						- oldid
	 *						- nodeid
	 *						- parentid
	 *						- filedataid
	 *						- filename
	 *						- filesize
	 *						- settings
	 *						- counter
	 *						- dateline
	 *						- resize_dateline
	 *						- extension
	 *						- cangetattachment
	 *						- cangetimgattachment
	 */
	public function fetchLegacyAttachments($ids)
	{
		if (empty($ids) OR !is_array($ids))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		array_walk($ids, 'intval');

		$rows = vB::getDbAssertor()->assertQuery('vBForum:fetchLegacyAttachments',
				array(
					'oldids' => $ids,
					'oldcontenttypeid' => array(	vB_Api_ContentType::OLDTYPE_THREADATTACHMENT,
													vB_Api_ContentType::OLDTYPE_POSTATTACHMENT,
													vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT,
													vB_Api_ContentType::OLDTYPE_ARTICLEATTACHMENT,
					)
				));
		$result = array();
		$userContext = vB::getUserContext();
		$publicFields = array(
			"oldid",
			"nodeid",
			"contenttypeid",
			"parentid",
			"filedataid",
			"filename",
			"filesize",
			"settings",
			"resize_filesize",
			"hasthumbnail",
			"counter",
			"dateline",
			"resize_dateline",
			"extension",
		);
		foreach($rows AS $row)
		{
			$allowedData = array();
			foreach ($publicFields AS $fieldname)
			{
				if (isset($row[$fieldname]))
				{
					$allowedData[$fieldname] = $row[$fieldname];
				}
			}
			$allowedData['visible'] = $row['publicview'];
			$allowedData['counter'] = $row['refcount'];
			if (empty($allowedData['filename']))
			{
				// I can't recall why we did this during the attachment refactor, but
				// it suggests that filename might be missing in imported attachments (possibly upgrade bug?)
				// I did see that my legacy attachments had `attach`.filename during my local upgrade testing,
				// but I'm leaving this here just in case.
				$allowedData['filename'] = $row['filehash']. '.' . $row['extension'];
			}
			// Not really "public" info since this is dependent on current user, but useful for calling functions
			$allowedData['cangetattachment'] = $userContext->getChannelPermission('forumpermissions', 'cangetattachment', $row['nodeid']);
			$allowedData['cangetimgattachment'] = $userContext->getChannelPermission('forumpermissions2', 'cangetimgattachment', $row['nodeid']);

			$result[$row['oldid']] = $allowedData;
		}

		return $result;
	}

	/** fetch filedataid(s) for the passed photo nodeid(s)
	 *
	 * 	@param 	mixed(array|int)	photoid(s)
	 *
	 *	@return	array	filedataids for the requested photos
	 **/
	public function fetchPhotoFiledataid($nodeid)
	{
		if (!is_array($nodeid))
		{
			$nodeid = array($nodeid);
		}

		$filedataids = array();

		$resultSet = vB::getDbAssertor()->assertQuery('vBForum:photo', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'nodeid' => $nodeid
		));

		foreach ($resultSet as $filedata)
		{
			$filedataids[$filedata['nodeid']] = $filedata['filedataid'];
		}

		return $filedataids;
	}

	/** fetch filedataid(s) for the passed attachment nodeid(s)
	 *
	 * 	@param 	mixed(array|int)	attachmentid(s)
	 *
	 *	@return	array	filedataids for the requested attachments
	 **/
	public function fetchAttachFiledataid($nodeid)
	{
		if (!is_array($nodeid))
		{
			$nodeid = array($nodeid);
		}

		$filedataids = array();

		$resultSet = vB::getDbAssertor()->assertQuery('vBForum:attach', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'nodeid' => $nodeid
		));

		foreach ($resultSet as $filedata)
		{
			$filedataids[$filedata['nodeid']] = $filedata['filedataid'];
		}

		return $filedataids;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85576 $
|| #######################################################################
\*=========================================================================*/
