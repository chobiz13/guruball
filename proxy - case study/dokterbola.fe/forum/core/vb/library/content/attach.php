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
 * vB_Api_Content_Attach
 *
 * @package vBApi
 * @access public
 */
class vB_Library_Content_Attach extends vB_Library_Content
{
	/**
	 * @deprecated Appears to be unused
	 */
	protected $types;

	/**
	 * @deprecated Appears to be unused
	 */
	protected $extension_map;

	//override in client- the text name
	protected $contenttype = 'vBForum_Attach';

	//The table for the type-specific data.
	protected $tablename = 'attach';

	//list of fields that are included in the index
	protected $index_fields = array('description');

	//Control whether this record will display on a channel page listing.
	protected $inlist = 0;

	//Image processing functions
	protected $imageHandler;

	protected function __construct()
	{
		parent::__construct();
		$this->imageHandler = vB_Image::instance();
	}

	/**
	 * Adds a new node.
	 *	This function will add a new attachment node & attach table record, increment refcount for the associated filedata table record, and set
	 *	the parent node record's hasphoto to 1
	 *
	 *	@param	mixed	$data		Array of field => value pairs which define the record. Must have all data required by vB_Library_Content::add().
	 *								At the minium, must have:
	 *									int		'parentid'		@see vB_Library_Content::add()
	 *									int 	'filedataid'
	 *								Additional data may include:
	 *									string	'caption'		Optional. Caption for the image. If caption is set, it will overwrite the description.
	 *									string	'description'	Optional. If description is set but caption is not set, the caption will be set to description.
	 *								@see vB_Library_Content::add() for more details
	 *								It can also contain data corresponding to the attach table fields, such as:
	 *									int		'visible'		???
	 *									int		'counter'		???
	 *									string	'filename'		???
	 *									int		'reportthreadid'		???
	 *									string	'settings'		Serialized array of attachment settings that are used by vB5_Template_BbCode's
	 *															attachReplaceCallback() to render the image with the specified settings. @see
	 *															vB_Api_Content_Attach::getAvailableSettings() for a list of the avaliable settings
	 *  @param	array	$options	Array of options for the content being created. Understands skipTransaction, skipFloodCheck, floodchecktime
	 *
	 * 	@return	array	Contains the data of the added node. Array with data-types & keys:
	 *						int			'nodeid'
	 *						bool		'success'
	 *						string[]	'cacheEvents'
	 *						array		'nodeVals' 		Array of field => value pairs representing the node table field values that were added to the node table.
	 *													@see vB_Api_Node::getNodeFields() or the node table structure for these fields
	 */
	public function add($data, array $options = array())
	{
		//Store this so we know whether we should call afterAdd()
		$skipTransaction = !empty($options['skipTransaction']);
		//todo -- lock the caption to the description until we collapse the fields.  Remove when caption goes away
		if (isset($data['caption']))
		{
			$data['description'] = $data['caption'];
		}
		else if (isset($data['description']))
		{
			$data['caption'] = $data['description'];
		}

		try
		{
			if (!$skipTransaction)
			{
				$this->assertor->beginTransaction();
			}
			$options['skipTransaction'] = true;
			$result = parent::add($data, $options);

			if ($result)
			{
				$this->assertor->assertQuery('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'nodeid' => $data['parentid'], 'hasphoto' => 1 ));

				// Increment the refcount in filedata.
				// Note that content_attach API's validate() ensures that we have $data['filedataid'] set.
				$this->assertor->assertQuery('updateFiledataRefCount', array('countChange' => 1, 'filedataid' => $data['filedataid']));
			}
			if (!$skipTransaction)
			{
				$this->beforeCommit($result['nodeid'], $data, $options, $result['cacheEvents'], $result['nodeVals']);
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
			$this->afterAdd($result['nodeid'], $data, $options, $result['cacheEvents'], $result['nodeVals']);
		}
		return $result;
	}


	/**
	 * Remove an attachment
	 * 	@param	INT	nodeid
	 */
	public function delete($nodeid)
	{
		//We need the parent id. After deletion we may need to set hasphoto = 0;
		$existing =	$this->nodeApi->getNode($nodeid);
		$this->removeAttachment($nodeid);
		parent::delete($nodeid);
		$photo = $this->assertor->getRow('vBForum:node', array('contenttypeid' => $this->contenttypeid, 'parentid' => $existing['parentid']));

		//If we got empty or error, there are no longer any attachments.
		if (!empty($existing['parentid']) AND (empty($photo) OR !empty($photo['errors'])))
		{
			$this->assertor->assertQuery('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'hasphoto' => 0, vB_dB_Query::CONDITIONS_KEY => array(
				array(
						'field' => 'nodeid',
						'value' => $existing['parentid'],
						'operator' => vB_dB_Query::OPERATOR_EQ
					))));
		}
		$this->nodeApi->clearCacheEvents(array($nodeid, $existing['parentid']));
	}

	/**
	 * Delete the records without updating the parent info. It is used when deleting a whole channel and it's children need to be removed
	 * @param array $childrenIds - list of node ids
	 */
	public function deleteChildren($childrenIds)
	{
		//existing attach data
		$attachdata = vB::getDbAssertor()->getColumn('vBForum:attach', 'filedataid', array('nodeid' => $childrenIds), false, 'nodeid');
		//the number of times an attachment is used in the list of nodes
		$refcounts = array_count_values($attachdata);
		//the individual existing filedata records
		$filedata = vB::getDbAssertor()->getColumn('filedata', 'refcount', array('filedataid' => array_keys($refcounts)), false, 'filedataid');
		foreach ($filedata as $filedataid => $nr)
		{
			//the new value of the existing refcount
			$refCount = max($nr - $refcounts[$filedataid], 0);
			$this->assertor->update("vBForum:filedata", array('refcount' => $refCount), array('filedataid' => $filedataid));
		}

		//delete the main tables
		parent::deleteChildren($childrenIds);
	}

	/**
	 * updates a record
	 *
	 *	@param	mixed		array of nodeid's
	 *	@param	mixed		array of permissions that should be checked.
	 *
	 * 	@return	boolean
	 */
	public function update($nodeid, $data)
	{
		$existing = $this->assertor->getRow('vBForum:attach', array('nodeid' => $nodeid));
		$existingNode =	$this->nodeApi->getNode($nodeid);

		//todo -- lock the caption to the description until we collapse the fields.  Remove when caption goes away
		if (isset($data['caption']))
		{
			$data['description'] = $data['caption'];
		}
		else if (isset($data['description']))
		{
			$data['caption'] = $data['description'];
		}

		if (parent::update($nodeid, $data))
		{
			//We need to update the filedata ref counts
			if (!empty($data['filedataid']) AND ($existing['filedataid'] != $data['filedataid']))
			{
				//Remove the existing
				$filedata = vB::getDbAssertor()->getRow('filedata', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						'filedataid' => $existing['filedataid']
				));

				if ($filedata['refcount'] > 1)
				{
					$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'filedataid' => $existing['filedataid'],
					'refcount' => $filedata['refcount'] - 1);
				}
				else
				{
					$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
					'filedataid' => $existing['filedataid']);
					$this->assertor->assertQuery('vBForum:filedataresize', $params);
				}

				$this->assertor->assertQuery('filedata', $params);

				//add the new
				$filedata = vB::getDbAssertor()->getRow('filedata', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						'filedataid' => $data['filedataid']
				));

				if (!empty($filedata) AND empty($filedata['errors']))
				{
					$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'filedataid' => $data['filedataid'],
					'refcount' => $filedata['refcount'] + 1);

					$this->assertor->assertQuery('filedata', $params);
				}
			}
		}

		$nodesToClear = array($nodeid, $existingNode['parentid']);
		if (isset($data['parentid']) AND ($data['parentid'] != $existingNode['parentid']))
		{
			$nodesToClear[] = $data['parentid'];
		}
		$this->nodeApi->clearCacheEvents($nodesToClear);
	}

	/**
	 *	See base class for information
	 */
	public function getIndexableFromNode($node, $include_attachments = true)
	{
		$indexableContent = parent::getIndexableFromNode($node, $include_attachments);

		if (!empty($node['description']))
		{
			$indexableContent['description'] = $node['description'];
		}

		return $indexableContent;
	}


	/**
	 * Remove an attachment
	 * 	@param	INT	nodeid
	 */
	public function removeAttachment($id)
	{
		// Note that this will NOT remove an attachment record.
		// Going through delete() (which calls this function) will remove the attachment record.
		if (empty($id) OR !intval($id))	{
			throw new Exception('invalid_request');
		}

		$attachdata = vB::getDbAssertor()->getRow('vBForum:attach', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'nodeid' => $id
			));

		if (!empty($attachdata) AND $attachdata['filedataid'])
		{
			$filedata = vB::getDbAssertor()->getRow('filedata', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'filedataid' => $attachdata['filedataid']
			));

			if ($filedata['refcount'] > 1)
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'filedataid' => $attachdata['filedataid'],
				'refcount' => $filedata['refcount'] - 1);
			}
			else
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'filedataid' => $attachdata['filedataid']);
				vB::getDbAssertor()->assertQuery('vBForum:filedataresize', $data);
			}

			vB::getDbAssertor()->assertQuery('vBForum:filedata', $data);
		}

		return true;
	}

	public function removeSignaturePicture($userid)
	{
		$sigpic = vB::getDbAssertor()->getRow('vBForum:sigpicnew', array('userid' => intval($userid)));

		if (empty($sigpic))
		{
			return;
		}

		vB::getDbAssertor()->delete('vBForum:sigpicnew', array('userid' => intval($userid)));

		if ($sigpic['filedataid'])
		{
			$filedata = vB::getDbAssertor()->getRow('filedata', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'filedataid' => $sigpic['filedataid']
			));

			if ($filedata['refcount'] > 1)
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'filedataid' => $sigpic['filedataid'],
				'refcount' => $filedata['refcount'] - 1);
			}
			else
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'filedataid' => $sigpic['filedataid']);
				vB::getDbAssertor()->assertQuery('vBForum:filedataresize', $data);
			}

			vB::getDbAssertor()->assertQuery('vBForum:filedata', $data);
		}
	}

	/**
	 * Get attachments for a content type
	 * 	@param	INT	nodeid
	 */
	public function getAttachmentsFromType($typeid)
	{
		$attachdata = vB::getDbAssertor()->getRows('attachmentsByContentType', array('ctypeid' => $typeid));

		return $attachdata;
	}

	/** Remove all attachments for content type
	 * 	@param	INT	Content Type id
	 *
	 **/
	public function zapAttachmentType($typeid)
	{
		$list = $this->getAttachmentsFromType($typeid);

		foreach($list AS $attachment)
		{
			$this->removeAttachment($attachment['attachmentid']);
		}
	}

	/**
	 * Get array of http headers for this attachment file extension
	 *
	 * @param	string	$extension	file extension, e.g. 'doc', 'gif', 'pdf', 'jpg'
	 *
	 * @return	string[]	Array containing the 'content-type' http header string for $extension.
	 *						If $extension is not found in attachmenttype table, the default
	 *						'Content-type: application/octet-stream' is returned in the array.
	 *
	 * @access	public
	 **/
	public function getAttachmentHeaders($extension)
	{
		$headers = array('Content-type: application/octet-stream');
		if (!empty($extension))
		{
			$attach_meta = vB::getDbAssertor()->getRows('vBForum:fetchAttachPermsByExtension', array('extension' => $extension));
			if (!empty($attach_meta) AND !empty($attach_meta[0]['mimetype']))
			{
				$headers = unserialize($attach_meta[0]['mimetype']);
			}
		}
		return $headers;
	}

	/**
	 * Wrapper to call image class's orientImage() and perform any old file cleanup as necessary.
	 *
	 * @param    string    $fileContents      May be modified! file_get_contents() of the image file being rotated.
	 * @param    array     $filearray         May be modified! Must contain tmp_name, the location of the image file
	 *                                        being rotated. This array is passed into/generated by uploadAttachment().
	 *
	 *
	 * @access private
	 */
	private function orientImage(&$fileContents, &$filearray)
	{
		if (empty($fileContents) OR empty($filearray['tmp_name']))
		{
			return;
		}

		$newFileLocation = $this->imageHandler->orientImage($fileContents, $filearray['tmp_name']);

		if (!empty($newFileLocation))
		{
			// cleanup. Get rid of old file.
			$trash = $filearray['tmp_name'];
			@unlink($trash);
			$fileContents = file_get_contents($newFileLocation);
			$filearray['tmp_name'] = $newFileLocation;
		}
	}

	/**
	 * Processes an uploaded file and saves it as an attachment
	 *
	 * @param	int				$userid		Userid of the user who is uploading the file
	 * @param	array|object	$file		Uploaded file data.
	 *										The object or array should have the following properties or elements with data-types and names:
	 *											string	'name'		Filename
	 *											int		'size'		Filesize
	 *											string	'type'		Filetype
	 *											string	'uploadfrom'	Optional. Where the file was uploaded from. E.g. 'profile', 'sgicon',
	 *																'signature', 'newContent'  or null
	 *											int		'parentid'	Optional. The nodeid/channelid this file should be saved under. Used for permission
	 *																checks
	 *										If it is an object, it should also have the following property:
	 *											string	'contents'	Contents of the file
	 *										If it is an array, it should also have the following element:
	 *											string	'tmp_name'	Filepath to the temporary file created on the server
	 * @param	bool			$cheperms	Optional, whether or not to check attachment permissions. Default true
	 * @param	bool			$imageOnly	Optional, whether or not to only allow an image attachment. Default false
	 *
	 * @return	array	Array of attachment data @see saveUpload()
	 *
	 * @throws	vB_Exception_Api('upload_file_exceeds_php_limit')	If file upload by PHP failed with error code UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
	 * @throws	vB_Exception_Api('upload_file_partially_uploaded')	If file upload by PHP failed with error code UPLOAD_ERR_PARTIAL
	 * @throws	vB_Exception_Api('upload_file_failed')				If file upload by PHP failed with error code UPLOAD_ERR_NO_FILE
	 * @throws	vB_Exception_Api('missing_temporary_folder')		If file upload by PHP failed with error code UPLOAD_ERR_NO_TMP_DIR
	 * @throws	vB_Exception_Api('upload_writefile_failed')			If file upload by PHP failed with error code UPLOAD_ERR_CANT_WRITE
	 * @throws	vB_Exception_Api('upload_stopped_by_extension')		If file upload by PHP failed with error code UPLOAD_ERR_EXTENSION
	 * @throws	Exception('Upload failed. PHP upload error: ' <error code>)		If file upload by PHP failed with an error code that's not included above
	 * @throws	vB_Exception_Api('invalid_file_data')				If $file['tmp_name'] contains no data
	 * @throws	vB_Exception_Api('upload_file_exceeds_limit')		If user exceeded their usergroup's attachlimit
	 * @throws	vB_Exception_Api('upload_exceeds_dimensions')		If the uploaded file exceeds allowed dimensions and resizing the image failed
	 * @throws	vB_Exception_Api('invalid_file')					If fetching getAttachmentPermissions() failed for specified file type & upload method
	 *
	 * @access	public
	 */
	public function uploadAttachment($userid, $file, $cheperms = true, $imageOnly = false)
	{
		//Leave for consistency with admincp
		if (!defined('ATTACH_AS_FILES_NEW'))
		{
			define('ATTACH_AS_FILES_NEW', 2);
		}
		$uploadFrom = '';
		//We can get either an uploaded file or an object. If we have an object let's make it into an array.

		if (is_object($file) AND isset($file->name))
		{
			$filearray = array('name' => $file->name, 'size' => $file->size,'type' => $file->type);
			$filebits = explode('.', $file->name);
			$extension = end($filebits);
			if (isset($file->contents) AND !empty($file->contents))
			{
				$filesize = strlen ($file->contents);
				$filename = vB_Utilities::getTmpFileName("$userid-$filesize", 'vbattach', ".$extension");
				file_put_contents($filename, $file->contents);
				$filearray['tmp_name'] = $filename;
				$fileContents = $file->contents;

				if (!empty($file->uploadfrom))
				{
					$uploadFrom = $file->uploadfrom;
				}

				if (!empty($file->parentid))
				{
					$parentid = $file->parentid;
					$filearray['parentid'] = $file->parentid;
				}
			}
		}
		else
		{

			if (!file_exists($file['tmp_name']))
			{
				// Encountered PHP upload error
				if (!($maxupload = @ini_get('upload_max_filesize')))
				{
					$maxupload = 10485760;
				}
				$maxattachsize = vb_number_format($maxupload, 1, true);

				switch($file['error'])
				{
					case '1': // UPLOAD_ERR_INI_SIZE
					case '2': // UPLOAD_ERR_FORM_SIZE
						throw new vB_Exception_Api('upload_file_exceeds_php_limit', $maxattachsize);
						break;
					case '3': // UPLOAD_ERR_PARTIAL
						throw new vB_Exception_Api('upload_file_partially_uploaded');
						break;
					case '4':
						throw new vB_Exception_Api('upload_file_failed');
						break;
					case '6':
						throw new vB_Exception_Api('missing_temporary_folder');
						break;
					case '7':
						throw new vB_Exception_Api('upload_writefile_failed');
						break;
					case '8':
						throw new vB_Exception_Api('upload_stopped_by_extension');
						break;
					default:
						throw new Exception('Upload failed. PHP upload error: ' . intval($file['error']));
				}
			}
			$filearray = $file;
			$filebits = explode('.', $file['name']);
			$extension = end($filebits);
			$filesize = filesize($file['tmp_name']);
			$fileContents = file_get_contents($file['tmp_name']);

			if (!empty($file['uploadFrom']))
			{
				$uploadFrom = $file['uploadFrom'];
				unset($file['uploadFrom']);
			}

			if (!empty($file['parentid']))
			{
				$parentid = intval($file['parentid']);
			}
		}
		//make sure there's a valid file here
		if (empty($fileContents))
		{
			throw new vB_Exception_Api('invalid_file_data');
		}


		$this->extensionCheck($extension, $filearray['tmp_name']);

		$isImage = $this->imageHandler->fileLocationIsImage($filearray['tmp_name']);
		if ($imageOnly AND !$isImage)
		{
			// taken from saveUpload(), placed here to hopefully avoid issues
			// with signature images etc early on.
			throw new vB_Exception_Api('upload_invalid_image');
		}



		if (empty($uploadFrom))
		{
			$uploadFrom = 'newContent';
		}

		if (empty($parentid))
		{
			$parentid = false;
		}

		//check the usergroup permission for total space.
		$usergroupattachlimit = vB::getUserContext()->getUsergroupLimit('attachlimit');

		if ($usergroupattachlimit > 0 AND  ($filesize > $usergroupattachlimit))
		{
			throw new vB_Exception_Api('upload_file_exceeds_limit', array(
				$filesize, $usergroupattachlimit
			));
		}

		/*
			If it claims to be an image...

			Strip exif data, rotate image, whatever we need to do to the image before we save it.
			A bit of a shame we may do multiple file writes (e.g. file_put_contents() in is_object($file)
			case above), but we need one central place to call this...

			Verify the image before we accept it & save it as filedata in case we *DO NOT* resize it. (Resize will call verify as well)
		 */
		if ($isImage)
		{
			// Note, magic bytes are already checked as part of the $isImage check. Verify will also check
			// potentially dangerous bits in exif data, though that requires further vulnerabilities to exploit
			// e.g. include({image}) which AFAIK we avoid.
			if (!$this->imageHandler->verifyImageFile($fileContents, $filearray['tmp_name']))
			{
				if (file_exists($filearray['tmp_name']))
				{
					@unlink($filearray['tmp_name']);
				}
				throw new vB_Exception_Api('dangerous_image_rejected');
			}
			$this->orientImage($fileContents, $filearray);

			// This bit is moved down here after orientImage() because the image might've been rotated, which would swap width & height.
			list($filewidth, $fileheight) = getimagesize($filearray['tmp_name']);
			if (!$filewidth OR !$fileheight)
			{
				/*
					getimagesize couldn't get the dimensions even though our image verification on the original image passed,
					and we may have recreated an entirely new image??
					Assume something went horribly wrong here.
				 */
				throw new vB_Exception_Api('upload_invalid_image');
			}
			$filesize = filesize($filearray['tmp_name']);
			$fileContents = file_get_contents($filearray['tmp_name']);

			// TODO: fetch image dimensions again, because it might've been rotated
			// Note: re-writing the image as part of orient image actually strips most of the exif, at least for GD.
		}
		else
		{
			$filewidth = false;
			$fileheight = false;
		}


		// Usergroup permissions
		if ($uploadFrom === 'profile')
		{
			$usercontext = vB::getUserContext();

			if ($cheperms)
			{
				$albumpicmaxheight = $usercontext->getLimit('albumpicmaxheight');
				$albumpicmaxwidth = $usercontext->getLimit('albumpicmaxwidth');


				if (($albumpicmaxwidth > 0 AND $filewidth > $albumpicmaxwidth) OR ($albumpicmaxheight > 0 AND $fileheight > $albumpicmaxheight))
				{
					// try resizing image
					$resizeTargets = array(
						'width'    => $albumpicmaxwidth,
						'height'   => $albumpicmaxheight,
						'filesize' => 0,
					);
					$resized = $this->resizeImage($userid, $filearray, $fileContents, $filesize, $filewidth, $fileheight, $extension, $resizeTargets);
					if (!$resized)
					{
						throw new vB_Exception_Api('upload_exceeds_dimensions', array(
							$albumpicmaxwidth, $albumpicmaxheight, $filewidth, $fileheight
						));
					}
				}
			}
		}

		// Channel icon permissions
		if ($uploadFrom === 'sgicon')
		{
			try
			{
				vB_Api::instanceInternal('content_channel')->validateIcon($parentid, array('filedata' => $fileContents, 'filesize' => $filesize));
			}
			catch (vB_Exception_Api $e)
			{
				if ($e->has_error('upload_file_exceeds_limit'))
				{
					// try resizing image
					$resizeTargets = array(
						'width'    => 0,
						'height'   => 0,
						'filesize' => vB::getUserContext()->getChannelLimits($nodeid, 'channeliconmaxsize'),
					);
					$resized = $this->resizeImage($userid, $filearray, $fileContents, $filesize, $filewidth, $fileheight, $extension, $resizeTargets);
					if (!$resized)
					{
						throw $e;
					}
				}
				else
				{
					throw $e;
				}
			}
		}

		// Signature picture
		if ($uploadFrom === 'signature')
		{
			$usercontext = vB::getUserContext();
			// Check if user has permission to upload signature picture
			if (!$usercontext->hasPermission('signaturepermissions', 'cansigpic'))
			{
				throw new vB_Exception_Api('no_permission');
			}

			$imageOnly = true;
			$filearray['is_sigpic'] = 1;
		}

		// Attachment permissions
		if ($cheperms AND $uploadFrom === 'newContent')
		{
			$results = vB_Api::instanceInternal('content_attach')->getAttachmentPermissions(array(
				'uploadFrom' => $uploadFrom,
				'extension' => $extension,
				'parentid' => $parentid,
			));

			if (empty($results['errors']))
			{
				$resizeTargets = array(
					'width'    => $results['max_width'],
					'height'   => $results['max_height'],
					'filesize' => $results['max_size'],
				);

				if (($results['max_size'] > 0) AND ($filesize > $results['max_size']))
				{
					// try resizing image
					$resized = $this->resizeImage($userid, $filearray, $fileContents, $filesize, $filewidth, $fileheight, $extension, $resizeTargets);
					if (!$resized)
					{
						throw new vB_Exception_Api('upload_file_exceeds_limit', array(
							$filesize, $results['max_size']
						));
					}
				}
				if (($results['max_width'] > 0 AND $filewidth > $results['max_width']) OR ($results['max_height'] > 0 AND $fileheight > $results['max_height']))
				{
					// try resizing image
					$resized = $this->resizeImage($userid, $filearray, $fileContents, $filesize, $filewidth, $fileheight, $extension, $resizeTargets);
					if (!$resized)
					{
						throw new vB_Exception_Api('upload_exceeds_dimensions', array(
							$results['max_width'], $results['max_height'], $filewidth, $fileheight
						));
					}
				}
			}
			else
			{
				throw new vB_Exception_Api('invalid_file');
			}
		}

		$result = $this->saveUpload($userid, $filearray, $fileContents, $filesize, $extension, $imageOnly);

		if (file_exists($filearray['tmp_name']))
		{
			@unlink($filearray['tmp_name']);
		}

		return $result;
	}

	/**
	 * Attempts to resize an uploaded image so that it can be saved as an attachment.
	 * If successful, it modifies $filearray, $fileContents, $filesize, $filewidth,
	 * $fileheight, $extension, and the temporary file as saved on disk, then returns true.
	 *
	 * @param	int	user id
	 * @param	array	file data array
	 * @param	string	file contents
	 * @param	int	file size
	 * @param	int	file width
	 * @param	int	file height
	 * @param	string	extension
	 * @param	array	target sizes (width, height, filesize)
	 *
	 * @return	bool	Returns false if the image is not successfully resized,
	 *			so the calling code can throw a size (dimensions or filesize) error
	 */
	protected function resizeImage($userid, &$filearray, &$fileContents, &$filesize, &$filewidth, &$fileheight, &$extension, array $targets)
	{
		/*
			Only allow images through this function. This means PDFs are NOT allowed into this function.
			Note, thumbnails for non-image files (namely PDF atm) is done directly from saveUpload(), not this function.

			Note that we also check isValidResizeType() a bit below, as not all images are resizable (bmp & tif using GD library, for ex)
		 */

		$this->extensioncheck($extension, $filearray['tmp_name']);
		$isImage = $this->imageHandler->fileLocationIsImage($filearray['tmp_name']);

		if (!$isImage)
		{
			return false;
		}

		$userid = (int) $userid;

		$options = vB::getDatastore()->getValue('options');
		$config = vB::getConfig();

		// check validity of image
		if (!$this->imageHandler->verifyImageFile($fileContents, $filearray['tmp_name']))
		{
			if (file_exists($filearray['tmp_name']))
			{
				@unlink($filearray['tmp_name']);
			}
			throw new vB_Exception_Api('dangerous_image_rejected');
		}

		// Note, if we allow non-images (PDF) into this, we should switch this to fetchImageInfoForThumbnails() instaed
		// get image size
		$imageInfo = $this->imageHandler->fetchImageInfo($filearray['tmp_name']);

		if (!$imageInfo OR !$imageInfo[2])
		{
			return false;
		}

		// can we do the resize?
		$resizemaxwidth = ($config['Misc']['maxwidth']) ? $config['Misc']['maxwidth'] : 2592;
		$resizemaxheight = ($config['Misc']['maxheight']) ? $config['Misc']['maxheight'] : 1944;
		if (
			!(
				$options['attachresize']
				AND
				$imageInfo[0] < $resizemaxwidth
				AND
				$imageInfo[1] < $resizemaxheight
				AND
				$this->imageHandler->isValidResizeType($imageInfo[2])
			)
		)
		{
			// not configured to resize, or image to big to attempt resizing
			return false;
		}

		// see if we need to do a resize
		if (
			($targets['width'] > 0 AND $imageInfo[0] > $targets['width'])
			OR
			($targets['height'] > 0 AND $imageInfo[1] > $targets['height'])
			OR
			($targets['filesize'] > 0 AND $filesize > $targets['filesize'])
		)
		{
			$targetWidth = $targets['width'] > 0 ? $targets['width'] : $imageInfo[0];
			$targetHeight = $targets['height'] > 0 ? $targets['height'] : $imageInfo[1];
			$targetSize = $targets['filesize'] > 0 ? $targets['filesize'] : $filesize;

			// if filesize is too large, calculate smaller dimensions
			if ($targetSize < $filesize)
			{
				$factor = $targetSize / $filesize; // factor may need adjusting
				$tempTargetWidth = (int) floor($targetWidth * $factor);
				$tempTargetHeight = (int) floor($targetHeight * $factor);
				$targetWidth = min($tempTargetWidth, $targetWidth);
				$targetHeight = min($tempTargetHeight, $targetHeight);
			}

			// resize (dimensions to large)
			$resizedImage = $this->imageHandler->fetchThumbnail($filearray['name'], $filearray['tmp_name'], $targetWidth, $targetHeight, $options['thumbquality'], false, false, true, false);

			if (empty($resizedImage['filedata']))
			{
				// resize failed
				throw new vB_Exception_Api('unable_to_resize_image');
			}

		}

		if (!empty($resizedImage))
		{
			// save new temp file
			$filename = vB_Utilities::getTmpFileName("$userid-$filesize", 'vbattach', ".$extension");

			file_put_contents($filename, $resizedImage['filedata']);

			if (file_exists($filearray['tmp_name']))
			{
				@unlink($filearray['tmp_name']);
			}

			$filearray['tmp_name'] = $filename;

			$filesize = filesize($filearray['tmp_name']);
			$fileContents = file_get_contents($filearray['tmp_name']);

			$filewidth = $resizedImage['width'];
			$fileheight = $resizedImage['height'];

			$filearray['name'] = !empty($resizedImage['filename']) ? $resizedImage['filename'] : $filearray['name'];

			$filebits = explode('.', $filearray['name']);
			$extension = end($filebits);

			// image successfully resized
			return true;
		}

		return false;
	}

	/** Upload an image based on the url
	 *
	 *  @param  int     user ID
	 * 	@param 	string	remote url
	 *  @param	bool	save as attachment
	 *
	 *	@return	mixed	array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 **/
	public function uploadUrl($userid, $url, $attachment = false, $uploadfrom = '')
	{
		//Leave for consistency with admincp
		if (!defined('ATTACH_AS_FILES_NEW'))
		{
			define('ATTACH_AS_FILES_NEW', 2);
		}

		//Did we get a valid url?
		if (empty($url))
		{
			// throw the same exception to mitigate SSRF (VBV-13082)
			throw new vB_Exception_Api('upload_invalid_image');
		}

		if (!preg_match('#^https?://#i', $url))
		{
			// throw the same exception to mitigate SSRF (VBV-13082)
			throw new vB_Exception_Api('upload_invalid_image');
		}

		// Retrieve the image
		$vurl = new vB_vURL();
		$fileResult = $vurl->fetch_body($url, 0, false, true);

		if (empty($fileResult['body']))
		{
			// throw the same exception to mitigate SSRF (VBV-13082)
			throw new vB_Exception_Api('upload_invalid_image');
		}

		$pathinfo = pathinfo($url);
		if (empty($pathinfo))
		{
			// throw the same exception to mitigate SSRF (VBV-13082)
			throw new vB_Exception_Api('upload_invalid_image');
		}

		// if there's no extension here try get one from elsewhere
		$extension_map = $this->imageHandler->getExtensionMap();
		if (empty($pathinfo['extension']) OR !array_key_exists(strtolower($pathinfo['extension']), $extension_map))
		{
			// try to get an extension from the content type header
			if (!empty($fileResult['headers']['content-type']))
			{
				// should be something like image/jpeg
				$typeData = explode('/', $fileResult['headers']['content-type']);
				if ((count($typeData) == 2) AND array_key_exists(trim($typeData[1]), $extension_map))
				{
					$extension = strtolower($extension_map[trim($typeData[1])]);
				}
			}
			$name = $pathinfo['basename'] . '.' . $extension;
		}
		else
		{
			$extension = $pathinfo['extension'];
			$name = $pathinfo['basename'];
		}
		$extension = strtolower($extension);

		$filename = vB_Utilities::getTmpFileName($userid, 'vbattach', ".$extension");

		file_put_contents($filename, $fileResult['body']);
		$filesize = strlen($fileResult['body']);

		//Make a local copy
		$filearray = array(
			'name'     => $name,
			'size'     => $filesize,
			'type'     => 'image/' . $extension_map[$extension],
			'tmp_name' => $filename
		);

		if (!empty($uploadfrom))
		{
			$filearray['uploadFrom'] = $uploadfrom;
		}

		if ($attachment)
		{
			return $this->uploadAttachment($userid, $filearray);
		}

		$result = $this->saveUpload($userid, $filearray, $fileResult['body'], $filesize, $extension, true);

		if (file_exists($filearray['tmp_name']))
		{
			@unlink($filearray['tmp_name']);
		}

		return $result;
	}


	/**
	 * Saves an uploaded file into the filedata system.
	 *
	 * @param	int		$userid				Id of user uploading the image. This user's permissions will be checked when necessary
	 * @param	array	$filearray			Array of data describing the uploaded file with data-types & keys:
	 *											string	'name'			Filename
	 *											int		'size'			Filesize
	 *											string	'type'			Filetype
	 *											string	'tmp_name'		Filepath to the temporary file created on the server
	 *											int		'parentid'		Optional. Node/Channelid this file will be uploaded under. If provided
	 *																	permissions will be checked under this node.
	 *											bool	'is_sigpic'		Optional. If this is not empty, the saved filedata will replace
	 *																	the user's sigpicnew record (or inserted for the user if none exists),
	 *																	and the filedata record will have refcount incremented & publicview
	 *																	set to 1.
	 * @param	string	$fileContents		String(?) containing file content BLOB
	 * @param	int		$filesize			File size
	 * @param	string	$extension			File extension
	 * @param	bool	$imageOnly			If true, this function will throw an exception if the file is not an image
	 * @param	bool	$skipUploadPermissionCheck		Optional boolean to skip permission checks. Only used internally when the system
	 *													saves a theme icon. Do not use for normal calls to this function.
	 *
	 * @return	array	Array of saved filedata info with data-types & keys:
	 *						int 		'filedataid'
	 *						int 		'filesize'
	 *						int			'thumbsize'		file size of the thumbnail of the saved filedata
	 *						string		'extension'
	 *						string		'filename'
	 *						string[]	'headers'		array containing the content-type http header of the saved filedata
	 *						boolean		'isimage'
	 *
	 * @throws	vB_Exception_Api('invalid_attachment_storage')	If 'attachfile' ("Save attachments as File") is enabled and the path specified
	 *															by 'attachpath' option is not writable for some reason
	 * @throws	vB_Exception_Api('dangerous_image_rejected')	If image verification failed for $fileContents or $filearray['tmp_name']
	 * @throws	vB_Exception_Api('upload_attachfull_total')		If attachment quota specified by 'attachtotalspace' option is exceeded
	 * @throws	vB_Exception_Api('cannot_create_file')			If the user fails the permission checks
	 * @throws	vB_Exception_Api('upload_invalid_image')		If $imageOnly is true and the uploaded file is not an image
	 * @throws	vB_Exception_Api('unable_to_add_filedata')		If adding the filedata record failed
	 * @throws	vB_Exception_Api('attachpathfailed')			If 'attachfile' ("Save attachments as File") is enabled and creating or fetching
	 *															the path to the attachment directory for the user failed
	 * @throws	vB_Exception_Api('upload_file_system_is_not_writable_path')		If 'attachfile' ("Save attachments as File") is enabled and the
	 *															path retrieved for the user is not writable.
	 *
	 * @access	public
	 */
	public function saveUpload($userid, $filearray, $fileContents, $filesize, $extension, $imageOnly = false, $skipUploadPermissionCheck = false)
	{
		$assertor = vB::getDbAssertor();
		$datastore = vB::getDatastore();
		$options = $datastore->getValue('options');
		$config = vB::getConfig();
		$usercontext = vB::getUserContext($userid);

		//make sure there's a place to put attachments.
		if ($options['attachfile'] AND
			(empty($options['attachpath']) OR !file_exists($options['attachpath']) OR !is_writable($options['attachpath']) OR !is_dir($options['attachpath'])))
		{
			throw new vB_Exception_Api('invalid_attachment_storage');
		}

		//make sure the file is good.
		/*
			TODO & other notes...
		If this is meant for non-image files, we need to move verifyFileHeadersAndExif() out of the
		image handler & make the exception generic.
		I think this was initially added to check for exploits slipping in as images, not as a "catch-all/
		just ban HTML just becase" that it currently is. Note that we do an explicit image check and check
		for html in the header, scripts in the exit, check file mimetype & whitelist file signature as well
		as check that the extension is an image extension.

		 */
		if (! $this->imageHandler->verifyFileHeadersAndExif($fileContents, $filearray['tmp_name']))
		{
			@unlink($filearray['tmp_name']);
			throw new vB_Exception_Api('dangerous_image_rejected');
		}

		// Check if this is an image extension we're dealing with for displaying later.
		// exif_imagetype() will check the validity of image
		$isImage = $this->imageHandler->fileLocationIsImage($filearray['tmp_name']);
		if ($isImage)
		{
			if (! $this->imageHandler->verifyImageFile($fileContents, $filearray['tmp_name']))
			{
				@unlink($filearray['tmp_name']);
				throw new vB_Exception_Api('dangerous_image_rejected');
			}
			/*
				Image talk...
				We could call magicWhiteList() and get the expected type from the file signature and
				compare the type to extension here. Since our output logic trusts the extension passed in,
				that could provide a slightly better ui/ux (and/or we could force-switch extensions here
				rather than allow clients to set it incorrectly). E.g. you could upload a gif with a .jpg,
				and it would be downloaded as a .jpg not a .gif, which may cause issues viewing it depending
				on the client.
				Currently, below, we only check that if an extension is *an* image type, the file is an image
				(& vice versa), but not that the extension is the *correct* image type.
			 */
		}

		/*
		 *	Note, this is for identification only, NOT for security!
		 *	If we're going to depend on the extension to determine if it's an image for outputting html,
		 *	let's at least check that it's an image.
		 */
		$this->extensionCheck($extension, $filearray['tmp_name']);

		// Thumbnails are a different story altogether. Something like a PDF
		// might have a thumbnail.
		$canHaveThumbnail = $this->imageHandler->imageThumbnailSupported($extension);

		/*
		 * TODO: We might want to check that the extension matches the mimetype.
		 *
		 */


		//We check to see if this file already exists.
		$filehash = md5($fileContents);

		$fileCheck = $assertor->getRow('vBForum:getFiledataWithThumb', array(
			'filehash' => $filehash,
			'filesize' => $filesize
		));

		// Does filedata already exist?
		if (empty($fileCheck) OR ($fileCheck['userid'] != $userid))
		{
			// Check if we are not exceeding the quota
			if ($options['attachtotalspace'] > 0)
			{
				$usedSpace = $assertor->getField('vBForum:getUserFiledataFilesizeSum', array('userid' => $userid));

				$overage = $usedSpace + $filesize - $options['attachtotalspace'];
				if ($overage > 0)
				{
					$overage = vb_number_format($overage, 1, true);
					$userinfo = vB::getCurrentSession()->fetch_userinfo();

					$maildata = vB_Api::instanceInternal('phrase')->
							fetchEmailPhrases('attachfull', array($userinfo['username'], $options['attachtotalspace'], $options['bburl'], 'admincp'), array($options['bbtitle']), 0);
					vB_Mail::vbmail($options['webmasteremail'], $maildata['subject'], $maildata['message']);

					throw new vB_Exception_Api('upload_attachfull_total', $overage);
				}
			}

			// Can we move this permission check out of this library function?
			if (
				(!$usercontext->canUpload($filesize, $extension, (!empty($filearray['parentid'])) ? $filearray['parentid'] : false))
				AND !$skipUploadPermissionCheck	// TEMPORARY SOLUTION, NEED A BETTER WAY TO GET AROUND THIS FOR THEME ICONS
			)
			{
				@unlink($filearray['tmp_name']);
				throw new vB_Exception_Api('cannot_create_file');
			}

			if ($imageOnly AND !$isImage)
			{
				throw new vB_Exception_Api('upload_invalid_image');
			}
			$timenow =  vB::getRequest()->getTimeNow();

			if ($canHaveThumbnail)
			{
				//Get the image size information.
				$imageInfo = $this->imageHandler->fetchImageInfoForThumbnails($filearray['tmp_name']);
				$sizes = @unserialize($options['attachresizes']);
				if (!isset($sizes['thumb']) OR empty($sizes['thumb']))
				{
					$sizes['thumb'] = 100;
				}
				$thumbnail = $this->imageHandler->fetchThumbnail(
					$filearray['name'],
					$filearray['tmp_name'],
					$sizes['thumb'],
					$sizes['thumb'],
					$options['thumbquality']
				);
			}
			else
			{
				$thumbnail = array('filesize' => 0, 'width' => 0, 'height' => 0, 'filedata' => null);
			}

			$thumbnail_data = array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'resize_type'     => 'thumb',
				'resize_dateline' => $timenow,
				'resize_filesize' => $thumbnail['filesize'],
				'resize_width'    => $thumbnail['width'],
				'resize_height'   => $thumbnail['height'],
			);

			// Note, unless this is a sigpic (defined as !empty($filearray['is_sigpic'])), below will set
			// the refcount of the new filedata record to 0.
			// So the caller MUST increment the refcount if this image should not be removed by the cron.
			$data = array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'userid'    => $userid,
				'dateline'  => $timenow,
				'filesize'  => $filesize,
				'filehash'  => $filehash,
				'extension' => $extension,
				'refcount'  => 0,
			);
			if (!empty($imageInfo))
			{
				$data['width'] = $imageInfo[0];
				$data['height'] = $imageInfo[1];
			}

			//Looks like we're ready to store. But do we put it in the database or the filesystem?
			if ($options['attachfile'])
			{
				//We name the files based on the filedata record, but we don't have that until we create the record. So we need
				// to do an insert, then create/move the files.
				$filedataid = $assertor->assertQuery('filedata', $data);

				if (is_array($filedataid))
				{
					$filedataid = $filedataid[0];
				}

				if (!intval($filedataid))
				{
					throw new vB_Exception_Api('unable_to_add_filedata');
				}

				$path = $this->verifyAttachmentPath($userid);
				if (!$path)
				{
					throw new vB_Exception_Api('attachpathfailed');
				}

				if (!is_writable($path))
				{
					throw new vB_Exception_Api('upload_file_system_is_not_writable_path', array(htmlspecialchars($path)));
				}

				if (!empty($thumbnail['filedata']))
				{
					file_put_contents($path . $filedataid . '.thumb', $thumbnail['filedata']);
				}
				rename($filearray['tmp_name'] , $path . $filedataid . '.attach');
			}
			else
			{
				//We put the file contents into the data record.
				$data['filedata'] = $fileContents;
				$filedataid = $assertor->assertQuery('filedata', $data);

				if (is_array($filedataid))
				{
					$filedataid = $filedataid[0];
				}
				$thumbnail_data['resize_filedata'] = $thumbnail['filedata'];
			}

			$thumbnail_data['filedataid'] = $filedataid;
			if ($canHaveThumbnail)
			{
				$assertor->assertQuery('vBForum:filedataresize', $thumbnail_data);
			}

			if (!empty( $filearray['name']))
			{
				 $filename = $filearray['name'];
			}
			else
			{
				$filename = '';
			}

			$result = array(
				'filedataid' => $filedataid,
				'filesize'   => $filesize,
				'thumbsize'  => $thumbnail['filesize'],
				'extension'  => $extension,
				'filename'   => $filename,
				'headers'    => $this->getAttachmentHeaders(strtolower($extension)),
				'isimage'    => $isImage,
			);

			if (!empty($filearray['is_sigpic']))
			{
				$assertor->assertQuery('replaceSigpic', array('userid' => $userid, 'filedataid' => $filedataid));
				$assertor->assertQuery('incrementFiledataRefcountAndMakePublic', array('filedataid' => $filedataid));
			}
		}
		else
		{
			// file already exists so we are not going to insert a new one
			$filedataid = $fileCheck['filedataid'];

			if (!empty($filearray['is_sigpic']))
			{
				// Get old signature picture data and decrease refcount
				$oldfiledata = vB::getDbAssertor()->getRow('vBForum:sigpicnew', array('userid' => $userid));
				if ($oldfiledata)
				{
					vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $oldfiledata['filedataid']));
				}

				$assertor->assertQuery('replaceSigpic', array('userid' => $fileCheck['userid'], 'filedataid' => $filedataid));
				$assertor->assertQuery('incrementFiledataRefcountAndMakePublic', array('filedataid' => $filedataid));
			}

			$result = array(
				'filedataid' => $filedataid,
				'filesize'   => $fileCheck['filesize'] ,
				'thumbsize'  => $fileCheck['resize_filesize'],
				'extension'  => $extension,
				'filename'   => $filearray['name'],
				'headers'    => $this->getAttachmentHeaders(strtolower($extension)),
				'isimage'    => $isImage,
			);
		}

		return $result;
	}

	protected function verifyAttachmentPath($userid)
	{
		// Allow userid to be 0 since vB2 allowed guests to post attachments
		$userid = intval($userid);

		$path = $this->fetchAttachmentPath($userid);
		if (vB_Library_Functions::vbMkdir($path))
		{
			return $path;
		}
		else
		{
			return false;
		}
	}

	protected function fetchAttachmentPath($userid, $attachmentid = 0, $thumb = false, $overridepath = '')
	{
		$options =  vB::getDatastore()->get_value('options');
		$attachpath = !empty($overridepath) ? $overridepath : $options['attachpath'];

		if ($options['attachfile'] == ATTACH_AS_FILES_NEW) // expanded paths
		{
			$path = $attachpath . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY)) . '/';
		}
		else
		{
			$path = $attachpath . '/' . $userid . '/';
		}

		if ($attachmentid)
		{
			if ($thumb)
			{
				$path .= '/' . $attachmentid . '.thumb';
			}
			else
			{
				$path .= '/' . $attachmentid . '.attach';
			}
		}

		return $path;
	}

	private function extensionCheck($extension, $filename)
	{
		// extension doesn't really mean much... but let's make sure that an image extension is
		// an image & non-image-extension is not an image
		// We might remove this, as we shouldn't be trusting or using provided extensions AT ALL.
		$isImageExtension = $this->imageHandler->isImageExtension($extension);
		$isImage = $this->imageHandler->fileLocationIsImage($filename);

		/*
			Certain types can be considered an image by all the checks, but we may not outwardly consider it an
			image because the browser does not support the type in an img element.
			For example, a legitimate .PSD file will pass all the checks, but we can't include that in an image tag.
			So we can't just check $isImageExtension === $isImage and call it a day.
			If it has an img-embeddable extension, ensure it's an image.
			If it's an image but not embeddable, check that the extension is for the type detected by the file signature.
		 */
		if ($isImageExtension)
		{
			if (!$isImage)
			{
				throw new vB_Exception_Api('image_extension_but_wrong_type');
			}
		}

		/*
			It's using a known file extension, but NOT
		 */
		$check = $this->imageHandler->compareExtensionToFilesignature($extension, $filename);
		if (!$check)
		{
			throw new vB_Exception_Api('image_extension_but_wrong_type');
		}




	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89714 $
|| #######################################################################
\*=========================================================================*/
