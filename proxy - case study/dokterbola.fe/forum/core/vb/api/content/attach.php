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
class vB_Api_Content_Attach extends vB_Api_Content
{
	/**
	 * @deprecated Appears to be unused
	 */
	protected $types;

	/**
	 * @deprecated Appears to be unused
	 */
	protected $extension_map;

	/**
	 * @var string Override in client- the text name
	 */
	protected $contenttype = 'vBForum_Attach';

	/**
	 * @var string The table for the type-specific data.
	 */
	protected $tablename = 'attach';

	/**
	 * @var int Control whether this record will display on a channel page listing
	 */
	protected $inlist = 0;

	/**
	 * @var object Image processing functions
	 */
	protected $imageHandler;

	/**
	 * Constructor
	 */
	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('Content_Attach');

		$this->imageHandler = vB_Image::instance();
	}

	/**
	 * This validates that a user can upload attachments. Currently that's just verifying that they are logged in.
	 *
	 * @param int User ID
	 */
	protected function checkPermission($userid)
	{
		if (!intval($userid))
		{
			if (isset($_FILES['tmp_name']))
			{
				@unlink($_FILES['tmp_name']);
			}

			throw new vB_Exception_Api('session_timed_out_login');
		}
	}

	/**
	 * Fetch image information about an attachment
	 *
	 * @param  int    Node ID
	 * @param  string Thumbnail version/size requested (SIZE_* constanst in vB_Api_Filedata)
	 * @param  bool   Should we include the image content
	 *
	 * @return mixed  Array of data, includes:
	 *                filesize, dateline, htmltype, filename, extension, and filedataid
	 */
	public function fetchImage($id, $type = vB_Api_Filedata::SIZE_FULL, $includeData = true)
	{
		if (empty($id) OR !intval($id))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		$type = vB_Api::instanceInternal('filedata')->sanitizeFiletype($type);
		$userContext = vB::getUserContext();

		$attachdata = vB::getDbAssertor()->getRow('vBForum:fetchAttachForLoad', array('nodeid' => $id));
		$extension = $attachdata['extension'];
		/*
		 *	This might get a bit confusing. Some files, like PDFs, might have an image thumbnail.
		 *	In such a case, we treat the thumbnail as an image in terms of permissions. To change
		 *	this policy, remove $type.
		 */
		$isImg = $this->imageHandler->isImageExtension($extension, $type);

		if (
			(!$isImg AND !$userContext->getChannelPermission('forumpermissions', 'cangetattachment', $id))
			OR
			($isImg AND !$userContext->getChannelPermission('forumpermissions2', 'cangetimgattachment', $id))
		)
		{
			if ($attachdata['userid'] != $userContext->fetchUserId())
			{
				throw new vB_Exception_Api('no_view_permissions');
			}
		}

		$parent = vB_Api::instanceInternal('node')->getNode($attachdata['parentid']);
		$contentApi = vB_Api_Content::getContentApi($parent['contenttypeid']);
		$valid = $contentApi->validate($parent, vB_Api_Content::ACTION_VIEW, $parent['nodeid'],
			array($parent['nodeid'] => $parent));

		//allow viewing attachments for nodes set to public preview even if the node itself
		//can't be viewed.
		if (!$valid AND empty($parent['public_preview']))
		{
			throw new vB_Exception_Api('no_view_permissions');
		}

		//If the record belongs to this user, or if this user can view attachments
		//in this section, then this is O.K.

		if (!empty($attachdata) && $attachdata['filedataid'])
		{
			$params = array('filedataid' => $attachdata['filedataid'], 'type' => $type);
			$record = vB::getDbAssertor()->getRow('vBForum:getFiledataContent', $params);
		}

		if (empty($record))
		{
			return false;
		}

		return vB_Image::instance()->loadFileData($record, $type, $includeData);
	}

	/**
	 * Sets the main logo for a file
	 *
	 * @param  int    Filedataid
	 * @param  string Which style (or styles) to update. 'current', 'default', or 'all'. see switch case in implementation for details.
	 *
	 * @return mixed  Array of data, includes error message or an int- normally 1.
	 */
	public function setLogo($filedataid, $styleselection = 'current')
	{
		$userContext = vB::getUserContext();

		if (!intval($filedataid))
		{
			throw new Exception('invalid_data');
		}

		$this->checkHasAdminPermission('canadminstyles');

		//validdate that the filedata record exists;
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('filedata', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'filedataid' => $filedataid));
		if (empty($check) OR !empty($check['errors']))
		{
			throw new Exception('invalid_data');
		}
		$styleVar = vB_Api::instanceInternal('Stylevar');
		$var = $styleVar->get("titleimage");

		$curLogoId = intval(substr($var['titleimage']['url'], strrpos($var['titleimage']['url'], '=')+1));
		if ($curLogoId > 0)
		{
			$assertor->assertQuery('decrementFiledataRefcount', array('filedataid' => $curLogoId));
		}

		$assertor->assertQuery('incrementFiledataRefcountAndMakePublic', array('filedataid' => $filedataid));

		$userinfo = vB::getCurrentSession()->fetch_userinfo();
		$stylevarid = 'titleimage';
		$updateStyleids = array();
		switch ($styleselection)
		{
			case 'all':
				/*
					Previously, this set the logo for only the top level styles (parentid = -1) and just removed titleimage stylevar
					from descendants.
					Since each theme is not a top level style, and has a default titleimage that gets overwritten by each upgrade,
					this will not work. Instead, we should set the titleimage for *all* styles, so that a read-able child of a theme
					will get the logo, and will not be accidentally removed by an upgrade.
					Note, this will also set the title image for a read/write-protected theme, but at the moment
					we don't care, as we have superficial blocks to prevent admins or users from setting those themes
					as a default style, so their title image being overwritten wouldn't display on frontend.

					Note, this might be undesired, because if there's a deep descendant line, and they want to reset the logo for that whole line,
					they'd have to go in and individually change each style descendant.
					A few possible ways to get around this:
					A) Only update styles that has a title image, and make sure that every theme's immediate child (the readable/writable one) has its own
					titleimage
					B) Keep track of parents, and only insert a title image for a style if parent is read-protected
					C) Add another $styleselection option like "current style subtree" that'll just set the titleimage for all styleids under the current.
					*
				 */
				$styles = vB_Library::instance('Style')->fetchStyles(false, false);
				foreach($styles AS $style)
				{
					$updateStyleids[] = $style['styleid'];
				}
				break;
			case 'default':
				// Set the logo for the default style.
				$updateStyleids = array(vB::getDatastore()->getOption('styleid'));
				break;
			case 'current':
			default:
				// Set the logo for the current style being used by the user.
				$currentStyleid = vB::getCurrentSession()->get('styleid');
				if (empty($currentStyleid) OR $currentStyleid < 1)
				{
					// In the event there is no styleid passed or we try to update the master,
					// update the user's selected style instead.
					$updateStyleids = array($userinfo['styleid']);
				}
				else
				{
					$updateStyleids = array($currentStyleid);
				}
				break;
		}
		foreach($updateStyleids AS $styleid)
		{
			//Can the stylecache from above be used for this? And can we just switch out the styleid for every style?
			$existing = $assertor->getRow('vBForum:stylevar', array(
				'styleid' => $styleid,
				'stylevarid' => $stylevarid
			));
			$dm = new vB_DataManager_StyleVarImage();
			if (!empty($existing))
			{
				$dm->set_existing(array(
					'styleid' => $styleid,
					'stylevarid' => $stylevarid
				));
			}
			else
			{
				$dm->set('styleid', $styleid);
				$dm->set('stylevarid', $stylevarid);
			}
			$value = array('url' => 'filedata/fetch?filedataid=' . $filedataid);
			$dm->set('value', $value);
			$dm->set('dateline',vB::getRequest()->getTimeNow());
			$dm->set('username', $userinfo['username']);
			$dm->save();

			if ($dm->has_errors(false))
			{
				throw $dm->get_exception();
			}
		}

		vB_Library::instance('Style')->buildStyleDatastore();

		return true;

	}

	/**
	 * Uploads a file
	 *
	 * @param  array Data from $_FILES
	 *
	 * @return array Array of data, which will include either error info or a filedataid
	 */
	public function upload($file)
	{
		return $this->uploadAttachment($file);
	}

	/**
	 * Uploads a photo. Only use for images.
	 *
	 * @param  array Data from $_FILES
	 *
	 * @return array Array of data, which will include either error info or a filedataid
	 */
	public function uploadPhoto($file)
	{
		return $this->uploadAttachment($file, true, true);
	}

	/**
	 * Uploads a file without dimension check - to be cropped later. Only use for images.
	 *
	 * @param  array Data from $_FILES
	 *
	 * @return array Array of data, which will include either error info or a filedataid
	 */
	public function uploadProfilePicture($file)
	{
		return $this->uploadAttachment($file, false, true);
	}

	/**
	 * Uploads an attachment
	 *
	 * For parameters & return data @see vB_Library_Content_Attach::uploadAttachment()
	 *
	 * @access protected
	 *
	 * @param  array     Data from $_FILES
	 * @param  bool      Whether or not to check permissions
	 * @param  bool      Whether or not this is an image only attachment
	 *
	 * @return array     Array of attachment data @see vB_Library_Content_Attach::saveUpload()
	 */
	protected function uploadAttachment($file, $cheperms = true, $imageOnly = false)
	{
		//Only logged-in-users can upload files
		$userid = vB::getCurrentSession()->get('userid');
		$this->checkPermission($userid);

		return $this->library->uploadAttachment($userid, $file, $cheperms, $imageOnly);
	}

	/**
	 * Upload an image based on the url
	 *
	 * @param  string Remote url
	 * @param  bool   Save as attachment
	 * @param  string The name of the upload form
	 *
	 * @return mixed  Array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 */
	public function uploadUrl($url, $attachment = false, $uploadfrom = '')
	{
		//Only logged-in-users can upload files
		$userid = vB::getCurrentSession()->get('userid');
		$this->checkPermission($userid);

		return $this->library->uploadUrl($userid, $url, $attachment, $uploadfrom);
	}

	/**
	 * Fetch image information about an attachment based on file data id
	 *
	 * @param  int   Filedataid
	 * @param  bool  Thumbnail version requested?
	 * @param  bool  Should we include the image content
	 *
	 * @return mixed Array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 */
	public function fetchImageByFiledataid($id, $type = vB_Api_Filedata::SIZE_FULL, $includeData = true)
	{
		if (empty($id) OR !intval($id))
		{
			throw new Exception('invalid_request');
		}

		$type = vB_Api::instanceInternal('filedata')->sanitizeFiletype($type);

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
			return vB_Image::instance()->loadFileData($record, $type, $includeData);
		}

		throw new vB_Exception_Api('no_view_permissions');
	}

	/**
	 * Fetch information of attachments without data
	 *
	 * @param  array $filedataids Array of file data ID
	 *
	 * @return array The attachment data array
	 */
	public function fetchAttachByFiledataids(array $filedataids)
	{
		$userContext = vB::getUserContext();

		$attachments = vB::getDbAssertor()->getRows('vBForum:fetchAttach2', array(
			'filedataid' => $filedataids
		), false, 'filedataid');

		foreach ($attachments as $k => $v)
		{
			$isImg = $this->imageHandler->isImageExtension($v['extension']);

			// Permission check
			if (
				(!$isImg AND !$userContext->getChannelPermission('forumpermissions', 'cangetattachment', $v['nodeid']))
				OR
				($isImg AND !$userContext->getChannelPermission('forumpermissions2', 'cangetimgattachment', $v['nodeid']))
			)
			{
				unset($attachments[$k]);
			}
		}

		return $attachments;
	}

	/**
	 * Remove an attachment
	 *
	 * @param int Nodeid
	 */
	public function delete($nodeid)
	{
		$data = array();

		if ($this->validate($data, $action = vB_Api_Content::ACTION_DELETE, $nodeid))
		{
			return $this->library->delete($nodeid);
		}
	}

	/**
	 * Remove an attachment
	 *
	 * @param int Nodeid
	 */
	public function deleteAttachment($id)
	{
		if (empty($id) OR !intval($id))	{
			throw new Exception('invalid_request');
		}

		//Only the owner or an admin can delete an attachment.
		$userContext = vB::getUserContext();

		if (!$userContext->getChannelPermission('moderatorpermissions', 'canmoderateattachments', $id))
		{
			$node = vB_Library::instance('node')->getNodeBare($id);
			$userinfo = vB::getCurrentSession()->fetch_userinfo();

			if ($node['userid'] != $userinfo['userid'])
			{
				throw new vB_Exception_Api('no_permission');
			}
		}

		return $this->library->removeAttachment($id);
	}

	/**
	 * See base class for information
	 *
	 * @param array The node array
	 * @param bool  Whether or not to include attachments
	 */
	public function getIndexableFromNode($node, $include_attachments = true)
	{
		//deliberately don't call the parent class.  We don't want to load the content
		//twice and there isn't good way to get at the loaded content object.
		//merge in the attachments if any
		if($include_attachments)
		{
			$indexableContent = $this->getIndexableContentForAttachments($node['nodeid']);
		}
		else
		{
			$indexableContnet = array();
		}

		$indexableContent['title'] = $node['title'];
		$indexableContent['description'] = $node['description'];

		return $indexableContent;
	}

	/**
	 * Retrieves the permissions for the specified file type and upload method
	 *
	 * @param  array Data:
	 *               uploadFrom *required
	 *               extension *required
	 *               channelid optional Nodeid of channel which this attachment will be a descendant of
	 * @param  bool  Imageonly
	 *
	 * @return array $results
	 */
	public function getAttachmentPermissions($data)
	{
		//Leave for consistency with admincp
		if (!defined('ATTACH_AS_FILES_NEW'))
		{
			define('ATTACH_AS_FILES_NEW', 2);
		}

		//Only logged-in-users can upload files
		$userid = vB::getCurrentSession()->get('userid');
		$this->checkPermission($userid);
		$uploadFrom = !empty($data['uploadFrom']) ? $data['uploadFrom'] : null;
		$usercontext = vB::getUserContext();
		$totalLimit = intval($usercontext->getUsergroupLimit('attachlimit'));
		$options = vB::getDatastore()->getValue('options');

		// Check if we are not exceeding the quota
		if ($options['attachtotalspace'] > 0)
		{
			if ($totalLimit > 0)
			{
				$totalLimit = min($totalLimit, $options['attachtotalspace']);
			}
			else
			{
				$totalLimit = $options['attachtotalspace'];
			}
		}

		//check to see if this user has their limit already.
		if ($totalLimit > 0)
		{
			$usedSpace = intval(vB::getDbAssertor()->getField('vBForum:getUserFiledataFilesizeSum', array('userid' => $userid)));

			if ($usedSpace > $totalLimit)
			{
				return array('errors' => vB_Phrase::fetchSinglePhrase('upload_attachfull_user', $usedSpace - $totalLimit));
			}
			$spaceAvailable = $totalLimit - $usedSpace;
		}
		else
		{
			$spaceAvailable = false;
		}

		$result = array();

		// Usergroup permissions
		if ($uploadFrom === 'profile')
		{
			$usergroupattachlimit = $usercontext->getLimit('attachlimit');
			$albumpicmaxheight = $usercontext->getLimit('albumpicmaxheight');
			$albumpicmaxwidth = $usercontext->getLimit('albumpicmaxwidth');
			$result['max_size'] = $usergroupattachlimit;
			$result['max_height'] = $albumpicmaxheight;
			$result['max_width'] = $albumpicmaxwidth;

			if ($spaceAvailable !== false)
			{
				$result['max_size'] = min($result['max_size'], $spaceAvailable);
				$result['attachlimit'] = $totalLimit;
			}
		}
		// Default to attachment permissions
		else
		{
			$extension = !empty($data['extension']) ? $data['extension'] : null;
			if ($extension != null)
			{
				// Fetch the parent channel or topic just in case we need to check group in topic.
				// The actual parent may not exist since we may be creating a new post/topic.
				$nodeid = (!empty($data['parentid'])) ? intval($data['parentid']) : false;
				$attachPerms = $usercontext->getAttachmentPermissions($extension, $nodeid);
				if ($attachPerms !== false)
				{
					$result['max_size'] = $attachPerms['size'];
					$result['max_height'] = $attachPerms['height'];
					$result['max_width'] = $attachPerms['width'];

					if ($spaceAvailable !== false)
					{
						$result['max_size'] = min($result['max_size'], $spaceAvailable);
						$result['attachlimit'] = $totalLimit;
					}
				}
				else
				{
					$result['errors'][] = 'invalid_file';
				}
			}
			else
			{
				$result['errors'][] = 'invalid_file';
			}
		}

		return $result;
	}

	/**
	 * Validates that the current can create a node with these values
	 *
	 * @param  mixed  Array of field => value pairs which define the record.
	 * @param  string Parameters to be checked for permission
	 * @param  int    Node ID
	 * @param  array  Nodes
	 *
	 * @return bool
	 */
	public function validate(&$data, $action = self::ACTION_ADD, $nodeid = false, $nodes = false)
	{
		if (parent::validate($data, $action, $nodeid, $nodes) == false)
		{
			return false;
		}

		$userContext = vB::getUserContext();
		switch ($action)
		{
			case vB_Api_Content::ACTION_ADD:
				if (empty($data['filedataid']))
				{
					return false;
				}
				break;
		}

		return true;
	}

	/**
	 * Cleans the input in the $data array, directly updating $data.
	 *
	 * Note: This is called from the cleanInput method in the text API for
	 * all the attachments to the text node.
	 *
	 * @param mixed      Array of fieldname => data pairs, passed by reference.
	 * @param (int|bool) Nodeid of the node being edited, false if creating new
	 */
	public function cleanInput(&$data, $nodeid = false)
	{
		parent::cleanInput($data, $nodeid);

		$data['filedataid'] = intval(isset($data['filedataid']) ? $data['filedataid'] : 0);

		$cleaner = vB::getCleaner();
		$data['filename'] = $cleaner->clean($data['filename'], vB_Cleaner::TYPE_NOHTML);

		// clean and serialize settings
		$data['settings'] = isset($data['settings']) ? $data['settings'] : '';
		if (!empty($data['parentid']))
		{
			$nodeid = $data['parentid'];
		}
		$data['settings'] = $this->cleanSettings($data['settings'], $nodeid);
	}

	/**
	 * Performs permission checks and cleaning for the 'settings' attach data and returns
	 * the serialized string that can be saved.
	 * Assumes that the current user is the user trying to save the settings
	 *
	 * @param  array  $settings Array with keys as specified in getAvailableSettings()
	 *                {@see getavailablesettings}
	 * @param  int    $nodeid Nodeid of attachment or attachment's parent, used to
	 *                check permissions
	 *
	 * @return string String containing the cleaned, serialized data
	 */
	protected function cleanSettings($settings, $nodeid)
	{
		$nodeid = intval($nodeid);
		if (!is_array($settings) OR empty($nodeid))
		{
			// If the caller screwed up a parameter, Let's just remove the
			// settings so we can continue with the attachment save.
			return '';
		}

		// only allow saving of settings that are specified in getAvailableSettings()
		$availableSettings = $this->getAvailableSettings();
		foreach ($settings AS $key => $data)
		{
			if (!in_array($key, $availableSettings['settings']))
			{
				unset($settings[$key]);
			}
		}

		// styles requires a permission check
		$userCanCSSAttachments = vB::getUserContext()->getChannelPermission('forumpermissions', 'canattachmentcss', $nodeid);
		if (isset($settings['styles']) AND !$userCanCSSAttachments)
		{
			unset($settings['styles']);
		}

		// everything returned from getAvailableSettings() should be cleaned here
		// note that we don't *currently* forbid HTML here, since these items
		// (except "styles", see permission check above) go through htmlspecialchars
		// in the bbcode parser. See vB5_Template_BbCode::attachReplaceCallback.
		// @TODO should we use TYPE_NOHTML here instead of escaping in the bbcode parser?
		$cleanVars = array(
			'alignment'   => vB_Cleaner::TYPE_NOHTML,
			'size'        => vB_Cleaner::TYPE_NOHTML,
			'title'       => vB_Cleaner::TYPE_STR,
			'description' => vB_Cleaner::TYPE_STR,
			'styles'      => vB_Cleaner::TYPE_STR,
			'link'        => vB_Cleaner::TYPE_INT,
			'linkurl'     => vB_Cleaner::TYPE_STR,
			'linktarget'  => vB_Cleaner::TYPE_INT,
		);
		foreach ($availableSettings['settings'] AS $availableSetting)
		{
			if (!isset($settings[$availableSetting]))
			{
				unset($cleanVars[$availableSetting]);
			}
		}
		$settings = vB::getCleaner()->cleanArray($settings, $cleanVars);

		// additional cleaning/restrictions for some of the settings
		if (isset($settings['alignment']))
		{
			$settings['alignment'] = in_array($settings['alignment'], array('none', 'left', 'center', 'right'), true) ? $settings['alignment'] : 'none';
		}
		if (isset($settings['size']))
		{
			$settings['size'] = vB_Api::instanceInternal('Filedata')->sanitizeFiletype($settings['size']);
		}

		if (empty($settings))
		{
			return '';
		}
		else
		{
			return serialize($settings);
			// TODO: REPLACE USE OF serialize() above WITH json_encode ALONG W/ CORRESPONDING unserialize()
			// IN vB5_Template_BbCode's attachReplaceCallback() (look for $settings = unserialize($attachment['settings']);)
		}
	}

	/**
	 * Returns an array of settings that can be saved.
	 *
	 * @return array Key 'settings' => array of available setting names
	 */
	public function getAvailableSettings()
	{
		// If you add a setting here, also clean it in cleanSettings()
		return array(
			'settings' => array(
				'alignment',
				'size',
				'title',
				'description',
				'styles',
				'link',
				'linkurl',
				'linktarget',
			)
		);
	}


	/**
	 * Returns an true of the extension & size requested would be treated as an image attachment
	 *
	 * @param	string	$extension	File extension of attachment
	 * @param	string	$type		'icon'|'thumb'|'small'|'medium'|'large'|'full'
	 * @return array Key 'settings' => array of available setting names
	 */
	public function isImage($extension, $type = vB_Api_Filedata::SIZE_FULL)
	{
		$extension = trim(strtolower($extension), " \t\n\r\0\x0B.");
		$type = vB_Api::instanceInternal('filedata')->sanitizeFiletype($type);
		$isImg = $this->imageHandler->isImageExtension($extension, $type);
		return $isImg;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88494 $
|| #######################################################################
\*=========================================================================*/
