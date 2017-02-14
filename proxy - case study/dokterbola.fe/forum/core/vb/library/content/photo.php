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
 * vB_Api_Content_Photo
 *
 * @package vBApi
 * @author aOrduno
 * @copyright Copyright (c) 2011
 * @version $Id: photo.php 89714 2016-07-27 19:53:24Z ksours $
 * @access public
 */
class vB_Library_Content_Photo extends vB_Library_Content
{
	/** override in client- the text name */
	protected $contenttype = 'vBForum_Photo';

	/** The table for the type-specific data. */
	protected $tablename = 'photo';

	//Control whether this record will display on a channel page listing.
	protected $inlist = 0;

	//Whether we change the parent's text count- 1 or zero
	protected $textCountChange = 0;

	//skip the flood check
	protected $doFloodCheck = false;

	protected $imageHandler = false;

	//Inherit viewperms from parents
	protected $inheritViewPerms = 1;

	/**
	 * Add photo record
	 *
	 * @param	mixed	Array of field => value pairs which define the record.
	 * @param	array		Array of options for the content being created
	 * 						Understands skipTransaction, skipFloodCheck, floodchecktime.
	 *
	 * 	@return	mixed		array with nodeid (int), success (bool), cacheEvents (array of strings), nodeVals (array of field => value).
	 */
	public function add($data, array $options = array())
	{
		//Store this so we know whether we should call afterAdd()
		$skipTransaction = !empty($options['skipTransaction']);
		$options += array('skipDupCheck' => true);

		if (empty($data['filedataid']))
		{
			throw new vB_Exception_Api('incomplete_data');
		}

		if (empty($data['userid']))
		{
			$user = vB::getCurrentSession()->fetch_userinfo();
			$data['authorname'] = $user['username'];
			$userid = $data['userid'] = $user['userid'];
		}
		else
		{
			$userid = $data['userid'];
			if (empty($data['authorname']))
			{
				$user = vB_Api::instanceInternal('user')->fetchUserName($userid);
				$data['authorname'] = $user;
			}
		}

		try
		{
			if (!$skipTransaction)
			{
				$this->assertor->beginTransaction();
			}
			$options['skipTransaction'] = true;
			/** Validate Filedata */
			$newNode = parent::add($data, $options);

			/** Update filedata refcount */
			$fileData = $this->assertor->getRow(
				'vBForum:filedata',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'filedataid' => $data["filedataid"]
				));
			$refCount = $fileData["refcount"] + 1;

			$photodata = array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY =>  array(
					'filedataid' => $data['filedataid']
				),
				'refcount' => $refCount
			);
			$this->assertor->assertQuery('vBForum:filedata', $photodata);

			if (!$skipTransaction)
			{
				$this->beforeCommit($newNode['nodeid'], $data, $options, $newNode['cacheEvents'], $newNode['nodeVals']);
				$this->assertor->commitTransaction();
			}
		}
		catch(exception $e)
		{
			if (!$skipTransaction)
			{
				$this->assertor->rollbackTransaction();
			}
			throw $e;
		}

		if (!$skipTransaction)
		{
			//The child classes that have their own transactions all set this to true so afterAdd is always called just once.
			$this->afterAdd($newNode['nodeid'], $data, $options, $newNode['cacheEvents'], $newNode['nodeVals']);
		}

		return $newNode;
	}

	/**
	 * Delete photo record
	 *
	 * @param	int		photo id
	 *
	 * @return	boolean
	 */
	public function delete($photoId)
	{
		/** Get filedata refcount */
		$fileDataRecord = $this->fetchFileDataRecord($photoId);
		$existing =	$this->nodeApi->getNode($photoId);

		if ($result = parent::delete($photoId))
		{
			$refCount = $fileDataRecord["refcount"] - 1;
			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY =>  array('filedataid' => $fileDataRecord["filedataid"]), 'refcount' => $refCount);
			$this->assertor->assertQuery("vBForum:filedata", $data);

			$this->nodeApi->clearCacheEvents(array($photoId, $existing['parentid']));
			return $result;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Delete the records without updating the parent info. It is used when deleting a whole channel and it's children need to be removed
	 * @param array $childrenIds - list of node ids
	 */
	public function deleteChildren($childrenIds)
	{
		foreach ($childrenIds as $photoId)
		{
			$fileDataRecord = $this->fetchFileDataRecord($photoId);
			$refCount = $fileDataRecord["refcount"] - 1;
			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY =>  array('filedataid' => $fileDataRecord["filedataid"]), 'refcount' => $refCount);
			$this->assertor->assertQuery("vBForum:filedata", $data);
		}

		//delete the main tables
		parent::deleteChildren($childrenIds);
	}


	/**
	 * Checks if user owns a gallery
	 *
	 * @param int	Gallery Id
	 *
	 * @param int	User Id
	 *
	 * @return boolean	Flag indicating if user is or not owner
	 *
	 */
	public function isOwner($galleryId, $userId)
	{
		$nodeInfo = vB_Api::instanceInternal('node')->getNode($galleryId);

		return ($userId == $nodeInfo["userid"]);
	}

	/**	Fetches photo's parentid
	 * @param 	int	Photo Id
	 *
	 * @return	int	Parent Id of the given photo
	 */
	public function fetchParent($photoId)
	{
		$photo = vB_Library::instance('node')->getNodeBare($photoId);
		return $photo["parentid"];
	}

	/**
	 * @param	int		Photo Id
	 *
	 * @return	mixed	Filedata Record
	 */
	protected function fetchFileDataRecord($photoId)
	{
		$photo = $this->assertor->getRow("vBForum:photo", array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						'nodeid' => $photoId ));

		if(empty($photo) OR empty($photo['filedataid']))
		{
			throw new vB_Exception_Api('invalid_photo');
		}

		$fileDataRecord = $this->assertor->getRow("vBForum:filedata", array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						'filedataid' => $photo['filedataid'] ));

		return $fileDataRecord;
	}

	public function fetchImageByPhotoid($id, $type = vB_Api_Filedata::SIZE_FULL, $includeData = true)
	{
		if (empty($id) OR !intval($id))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		//Normal permissions check
		$userContext = vB::getUserContext();

		if (!$userContext->getChannelPermission('forumpermissions', 'canview', $id))
		{
			throw new vB_Exception_Api('no_permission');
		}

		$params = array('nodeid' => $id, 'type' => $type);
		$record = $this->assertor->getRow('vBForum:getPhotoContent', $params);

		if (empty($record))
		{
			return false;
		}

		if (!$this->imageHandler)
		{
			$this->imageHandler = vB_Image::instance();
		}

		return $this->imageHandler->loadFileData($record, $type, $includeData);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89714 $
|| #######################################################################
\*=========================================================================*/
