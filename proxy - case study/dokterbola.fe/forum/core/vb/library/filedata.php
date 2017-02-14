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
* Class to manage FileData.  At the moment the only thing it does is to move filedata between the database and filesystem.
*
* @package	vBulletin
* @version	$Revision: 83435 $
* @date		$Date: 2014-12-10 10:32:27 -0800 (Wed, 10 Dec 2014) $
*/
class vB_Library_Filedata extends vB_Library
{
	/**
	 * Storage Type
	 *
	 * @var  string
	 */
	protected $storage = null;
	protected $filePath = false;

	/**
	 * Supported types of storage
	 *
	 * These should match the legacy defines at the top of core/includes/functions_file.php
	 *
	 * @constant	int
	 */
	const ATTACH_AS_DB = 0;
	const ATTACH_AS_FILES_OLD = 1;
	const ATTACH_AS_FILES_NEW = 2;

	/*
	Standard Constructor
	*/
	protected function __construct()
	{
		parent::__construct();

		$this->storage = vB::getDatastore()->getOption('attachfile');
		$this->filePath = vB::getDatastore()->getOption('attachpath');
		$this->assertor = vB::getDbAssertor();
	}

	/**
	 * moves one record from the database to the filesystem
	 *
	 * @param	mixed		optional filedata record
	 * @param 	int		optional $filedataid
	 *
	 * @return 	array	has either 'success' or 'error'
	 */
	public function moveToFs(&$filedata = false, $filedataid = false, $resize = array())
	{
		/*three non-obvious issues:
		first, we normally get attachment.  It seems strange, but that's because this is inside a loop
		in admincp, so we have the record. Since we have the data, let's use it.
		second, it would appear wiser to do the looping here.  But we have to limit the number of records to prevent timeouts,
		and pass that to admincp page, and then reload. The admincp is already written to handle all this
		third, it would seem we would want to clear the old data.  But that would be bad. Because of the looping per "second", we
		complete all the moves, and then after confirmation wipe the old. Otherwise we could get caught in an invalid state with
		some records in the filesystem and some in the database.  Very bad.
		*/

		if ($this->storage == self::ATTACH_AS_FILES_OLD OR $this->storage == self::ATTACH_AS_FILES_NEW)
		{
			throw new vB_Exception_Api('invalid_request');
		}

		$fileRec = $this->cleanFileParams($filedata, $filedataid);
		//We can skip most of the data cleaning.  We aren't saving new records, we're just moving between filesystem and database
		$filename = $this->fetchAttachmentPath($fileRec['userid'], self::ATTACH_AS_FILES_NEW) . '/' . $fileRec['filedataid'] . '.attach' ;
		$resizeSizes = array();

		file_put_contents($filename, $fileRec['filedata']);

		if (is_array($resize) AND !empty($resize))
		{
			foreach($resize AS $resizeRec)
			{
				$resizeName = $this->fetchAttachmentPath($fileRec['userid'], self::ATTACH_AS_FILES_NEW) . '/' . $fileRec['filedataid'] . '.' . $resizeRec['resize_type'];
				file_put_contents($resizeName, $resizeRec['resize_filedata']);

				$resizeSizes[$resizeRec['resize_type']] = filesize($resizeName);
			}
		}

		return array(
			'success' => true,
			'filesize' => filesize($filename),
			'resize_sizes' => $resizeSizes
		);

	}

	/**
	 * moves one record from the filesystem to the database
	 *
	 * @param	mixed		optional filedata record
	 * @param 	int		optional $filedataid
	 *
	 * @return 	array	has either 'success' or 'error'
	 */
	public function moveToDb(&$filedata = false, $filedataid = false, $resize = array())
	{
		if ($this->storage == self::ATTACH_AS_DB)
		{
			throw new vB_Exception_Api('invalid_request');
		}

		//see introductory comments in moveToFs above
		$fileRec = $this->cleanFileParams($filedata, $filedataid);
		//We can skip most of the data cleaning.  We aren't saving new records, we're just moving between filesystem and database
		$filename = $this->fetchAttachmentPath($fileRec['userid'], self::ATTACH_AS_FILES_NEW) . '/' . $fileRec['filedataid'] . '.attach' ;
		if (!file_exists($filename))
		{
			// In some cases, we may have a mix of ATTACH_AS_FILES_NEW and ATTACH_AS_FILES_OLD. See VBV-13339.
			$filename = $this->fetchAttachmentPath($fileRec['userid'], self::ATTACH_AS_FILES_OLD) . '/' . $fileRec['filedataid'] . '.attach' ;
			if (!file_exists($filename))
			{
				throw new vB_Exception_Api('file_not_found');
			}
		}
		$fileContents = file_get_contents($filename);
		$this->assertor->assertQuery('vBForum:filedata', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			vB_dB_Query::CONDITIONS_KEY => array('filedataid' => $fileRec['filedataid']),
			'filedata' => $fileContents
		));
		$resizeSizes = array();

		if (is_array($resize) AND !empty($resize))
		{
			foreach($resize AS $resizeRec)
			{
				$resizeName = $this->fetchAttachmentPath($fileRec['userid'], self::ATTACH_AS_FILES_NEW) . '/' . $fileRec['filedataid'] . '.' . $resizeRec['resize_type'];
				if (!file_exists($resizeName))
				{
					// In some cases, we may have a mix of ATTACH_AS_FILES_NEW and ATTACH_AS_FILES_OLD. See VBV-13339.
					$resizeName = $this->fetchAttachmentPath($fileRec['userid'], self::ATTACH_AS_FILES_OLD) . '/' . $fileRec['filedataid'] . '.' . $resizeRec['resize_type'];
					if (!file_exists($resizeName))
					{
						// In some cases, when files were saved as ATTACH_AS_FILES_OLD, the resized images were incorrectly
						// stored with no dot between the filedataid and the resize type. See VBV-13339.
						$resizeName = $this->fetchAttachmentPath($fileRec['userid'], self::ATTACH_AS_FILES_OLD) . '/' . $fileRec['filedataid'] . $resizeRec['resize_type'];
						if (!file_exists($resizeName))
						{
							throw new vB_Exception_Api('file_not_found');
						}
					}
				}

				$fileContents = file_get_contents($resizeName);
				$this->assertor->assertQuery('vBForum:filedataresize', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'filedataid', 'value' => $resizeRec['filedataid']),
						array('field' => 'resize_type', 'value' => $resizeRec['resize_type']),
					),
					'resize_filedata' => $fileContents
				));

				$resizeSizes[$resizeRec['resize_type']] = filesize($resizeName);
			}
		}

		return array(
			'success' => true,
			'filesize' => filesize($filename),
			'resize_sizes' => $resizeSizes
		);
	}

	protected function cleanFileParams(&$filedata = false, $filedataid = false)
	{
		if (empty($filedata) OR empty($filedata['filedataid']))
		{
			if (empty($filedataid) OR !is_int($filedataid))
			{
				throw new vB_Exception_Api('invalid_request');
			}
			$fileRec = $this->assertor->getRow('vBForum:filedata', array('filedataid' => $filedataid));
		}
		else
		{
			$fileRec = &$filedata;
		}

		if (empty($fileRec) OR !empty($fileRec['errors']) OR empty($fileRec['filedataid']))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		return $fileRec;
	}

	/**
	 * Get the path for a user and make sure it exists
	 *
	 * @param 	int	$userid
	 * @param	int	Attachment storage type to use to generate the path
	 *
	 * @return 	string	path to user's storage.
	 */
	public function fetchAttachmentPath($userid, $storageType)
	{
		// Allow userid to be 0 since vB2 allowed guests to post attachments
		$userid = intval($userid);

		if (empty($attachPath))
		{
			$attachPath = $this->filePath;
		}

		if ($storageType == self::ATTACH_AS_FILES_NEW) // expanded paths
		{
			$path = $attachPath . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY));
		}
		else
		{
			$path = $attachPath . '/' . $userid;
		}

		if (is_dir($path))
		{
			return $path;
		}
		else if (file_exists($path))
		{
			throw new vB_Exception_Api('attachpathfailed');
		}

		if (vB_Library_Functions::vbMkdir($path))
		{
			return $path;
		}
		else
		{
			return false;
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
