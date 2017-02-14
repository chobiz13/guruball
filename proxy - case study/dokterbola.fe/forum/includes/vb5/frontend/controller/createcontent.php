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

class vB5_Frontend_Controller_CreateContent extends vB5_Frontend_Controller
{
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns input needed to create the different content types, common to all
	 * types.  This is incomplete and mostly deals with the items used by the
	 * CMS to create articles.
	 *
	 * @TODO This function is a stop-gap measure to avoid a bunch of code duplication
	 * in the different content type functions in this class.  This should be updated
	 * to include all of the values common to all types as a first step to refactoring
	 * the class.
	 *
	 * This class needs a rewrite to normalize how the different content types are created,
	 * updated, and handled, and to reduce code duplication.
	 *
	 * @return	array	Array of input items
	 */
	protected function getArticleInput()
	{
		$input = array(
			'urlident'               => (isset($_POST['urlident']) ? trim(strval($_POST['urlident'])) : ''),
			'htmltitle'              => (isset($_POST['htmltitle']) ? trim(strval($_POST['htmltitle'])) : ''),
			'description'            => (isset($_POST['description']) ? trim(strval($_POST['description'])) : ''),
			'public_preview'         => (isset($_POST['public_preview']) ? trim(intval($_POST['public_preview'])) : 0),
			'disable_bbcode'         => (isset($_POST['disable_bbcode']) ? (bool)$_POST['disable_bbcode'] : false), // CMS static HTML type
			'hide_title'             => (isset($_POST['hide_title']) ? (bool)$_POST['hide_title'] : false), // CMS
			'hide_author'            => (isset($_POST['hide_author']) ? (bool)$_POST['hide_author'] : false), // CMS
			'hide_publishdate'       => (isset($_POST['hide_publishdate']) ? (bool)$_POST['hide_publishdate'] : false), // CMS
			'display_fullincategory' => (isset($_POST['display_fullincategory']) ? (bool)$_POST['display_fullincategory'] : false), // CMS
			'display_pageviews'      => (isset($_POST['display_pageviews']) ? (bool)$_POST['display_pageviews'] : false), // CMS
			'hide_comment_count'     => (isset($_POST['hide_comment_count']) ? (bool)$_POST['hide_comment_count'] : false), // CMS
		);

		//enable/disable article comments -- this is now used generally
		//do not set if not provide, use the API default values.  Otherwise things like the forums which aren't thinking about it
		//get set incorrectly.
		if (isset($_POST['allow_post']))
		{
			$input['allow_post'] = (bool)$_POST['allow_post'];
		}

		if (!empty($_POST['save_draft']))
		{
			$input['publish_now'] = false;
			$input['publishdate'] = 0;
		}
		else if (!empty($_POST['publish_now']))
		{
			$input['publish_now'] = (int)$_POST['publish_now'];
		}
		else
		{
			$input['publishdate'] = $this->getPublishDate();
		}

		//enable/disable blog comments. For blogs, this uses a checkbox which isn't sent when it's unchecked
		//so we use a hidden input flag to tell us to look for it.
		if (!empty($_POST['allow_post_checkbox']))
		{
			$input['allow_post'] = (bool) (isset($_POST['allow_post']) ? $_POST['allow_post'] : 0);
		}

		// HTML State. Non-article content entry doesn't have UI for this, so we should set it only when provided in the form data.
		// Otherwise, the added content will always have htmlstate = 'off' regardless of user permissions
		if (isset($_POST['htmlstate']))
		{
			$input['htmlstate'] = trim(strval($_POST['htmlstate']));
		}

		return $input;
	}

	/**
	 * Returns the correct publish date for this item, taking into account the
	 * Future publish and draft options. Returns boolean false when the publish
	 * date should not be set.
	 *
	 * @return	mixed	Publish date (which can be empty to save as draft) or false to not set publish date.
	 */
	protected function getPublishDate()
	{
		// for save draft and specify publish date, we always want to
		// set the publishdate, when updating and when creating new
		if (isset($_POST['save_draft']) AND $_POST['save_draft'] == 1)
		{
			// no publish date == draft (currently used for articles)
			return '';
		}
		else if (!empty($_POST['publish_now']))
		{
			return false;
		}
		else
		{
			// specify publish date (currently used for articles)
			if (!empty($_POST['publish_hour']) AND isset($_POST['publish_minute']) AND !empty($_POST['publish_month']) AND !empty($_POST['publish_day']) AND !empty($_POST['publish_year']) AND !empty($_POST['publish_ampm']))
			{
				if ($_POST['publish_ampm'] == 'pm')
				{
					$_POST['publish_hour'] = $_POST['publish_hour'] + 12;
				}
				$dateInfo = array('hour' => $_POST['publish_hour'], 'minute' => $_POST['publish_minute'], 'month' =>  $_POST['publish_month'], 'day' => $_POST['publish_day'], 'year' => $_POST['publish_year']);
				$api = Api_InterfaceAbstract::instance();
				return  $api->callApi('user', 'vBMktime', array($dateInfo));
			}
			else
			{
				// we don't have the correct fields to generate the publish date
				// save as draft
				return '';
			}
		}
	}

	public function index()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'title'     => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text'      => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'nodeid'    => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'parentid'  => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid' => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'ret'       => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'tags'      => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'reason'    => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
			'iconid'    => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'  => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'   => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'subtype'   => (isset($_POST['subtype']) ? trim(strval($_POST['subtype'])) : ''),
			'nl2br'     => (isset($_POST['nl2br']) ? (bool)$_POST['nl2br'] : false),
		);

		$api = Api_InterfaceAbstract::instance();

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}

		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		$time = vB5_Request::get('timeNow');
		$tagRet = false;

		$textData = array(
			'title'                  => $input['title'],
			'parentid'               => $input['parentid'],
			'prefixid'               => $input['prefixid'],
			'iconid'                 => $input['iconid'],
		);

		if ($input['nodeid'])
		{
			$result = array();
			if ($user['userid'] < 1)
			{
				$result['error'] = 'logged_out_while_editing_post';
				$this->sendAsJson($result);
				exit;
			}

			// when *editing* comments, it uses create-content/text (this function)
			// when *creating* comments, it uses ajax/post-comment (actionPostComment)
			if ($input['subtype'] == 'comment')
			{
				// NOTE: Keep this in sync with
				//       vB5_Frontend_Controller_Ajax:: actionPostComment
				//
				// htmlspecialchars and nl2br puts the text into the same state
				// it is when the text api receives input from ckeditor
				// specifically, newlines are passed as <br /> and any HTML tags
				// that are typed literally into the editor are passed as HTML-escaped
				// because non-escaped HTML that is sent is assumed to be formatting
				// generated by ckeditor and will be parsed & converted to bbcode.
				$textData['rawtext'] = nl2br(htmlspecialchars($input['text'], ENT_NOQUOTES));
			}
			else
			{
				$textData['rawtext'] = $input['text'];
			}

			$textData['reason'] = $input['reason'];

			$textData += $this->getArticleInput();

			$options = array();

			// We need to convert WYSIWYG html here and run the img check
			if (isset($textData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($textData['rawtext'], $options));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			if ($input['nl2br'])
			{
				// not using ckeditor (on edit, 'nl2br' goes in the data array)
				$textData['nl2br'] = true;
			}

			// add attachment info so update() can do permission checking & add/remove attachments to this node.
			$this->addAttachments($textData);

			$updateRet = $api->callApi('content_text', 'update', array($input['nodeid'], $textData, $options));
			$this->handleErrorsForAjax($result, $updateRet);
			// If the update failed, just return and don't edit tags, attachments etc.
			if (!empty($updateRet['errors']))
			{
				return $this->sendAsJson($result);
			}

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));
			$this->handleErrorsForAjax($result, $tagRet);

			$this->sendAsJson($result);
		}
		else
		{
			//not sure why rawtext is different here from the above
			$textData['rawtext'] = $input['text'];
			$textData['userid'] = $user['userid'];
			$textData['authorname'] = $user['username'];
			$textData['created'] = $time;
			$textData['hvinput'] = $input['hvinput'];

			$publish = array(
				'facebook' => !empty($_POST['fbpublish'])
			);

			if (!empty($_POST['setfor']))
			{
				$textData['setfor'] = intval($_POST['setfor']);
			}

			if(!$this->createNewNode('content_text', $textData, $publish, $input))
			{
				return;
			}
		}
		exit;
	}

	/**
	 *	creates a new node based on the type
	 *
	 *	This handle the JSON output for both errors and success.
	 *
	 *	@param string $apilib -- the library to use to create the node
	 *	@param array $data -- the data needed by the api function for a particular type.  See the calling functions for
	 *		details.
	 *	@param array $publish -- exernal locations where this node should be published.  This is a key => boolean array
	 *		with true meaning we should publish to that location.
	 *	@param array $input -- the input variables
	 *
	 *	@return boolean -- false means an error happened and the calling action should return immediately.  true means
	 *		success and the caller should continue.
	 */
	private function createNewNode($apilib, $data, $publish, $input)
	{
		// Note that the return behavior of this function is strange but in intended to mimic the behavior
		// of the code this was refactored from exactly.  Its not clear why in some cases we call exit from the
		// action method and other cases we return.  This needs to be sorted out, but should be easier to do
		// once the code is consolidated

		//the input parameter could be better handled.  It's used this way because that's how it exisited in the
		//code before it was a parameter.

		$api = Api_InterfaceAbstract::instance();

		// sets publishdate
		$data += $this->getArticleInput();

		$options = array();
		$result = array();

		// We need to convert WYSIWYG html here and run the img check
		if (isset($data['rawtext']))
		{
			$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($data['rawtext'], $options));
			if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
			{
				$result['error'] = $phrase;
				$this->sendAsJson($result);
				return false;
			}
		}

		if ($input['nl2br'])
		{
			// not using ckeditor (on add, 'nl2br' goes in the options array)
			$options['nl2br'] = true;
		}

		// add attachments
		$this->addAttachments($data);

		$nodeId = $api->callApi($apilib, 'add', array($data, $options));
		if (!is_int($nodeId) OR $nodeId < 1)
		{
			$this->handleErrorsForAjax($result, $nodeId);
			$this->sendAsJson($result);
			exit;
		}

		if (!empty($input['tags']))
		{
			$tagRet = $api->callApi('tags', 'addTags', array($nodeId, $input['tags']));
			$this->handleErrorsForAjax($result, $tagRet);
		}

		$node = $api->callApi('node', 'getNode', array($nodeId));
		if ($node AND empty($node['errors']))
		{
			if (empty($node['approved']))
			{
				$result['moderateNode'] = true;
			}
		}

		if (!empty($publish['facebook']))
		{
			$node = $api->callApi('node', 'getContentForNodes', array(array($nodeId)));
			if ($node AND empty($node['errors']))
			{
				$node = reset($node);

				//we pretty much ignore errors here because we don't really want to indicate that
				//we failed to post if this fails and don't have a way of saying the post succeeded
				//except for this in our ajax return.
				$api->callApi('facebook', 'publishNode', array($node, true));
			}
		}

		$this->getReturnUrl($result, $input['channelid'], $input['parentid'], $nodeId);
		$result['nodeId'] = $nodeId;
		$this->sendAsJson($result);
		return true;
	}


	public function actionPoll()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();
		$offset = $api->callApi('user', 'fetchTimeOffset', array());

		$input = array(
			'title'           => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text'            => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'polloptions'     => (array)$_POST['polloptions'],
			'parentid'        => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'nodeid'          => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'ret'             => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'timeout'         => ((isset($_POST['timeout']) AND !empty($_POST['timeout'])) ? intval(strtotime(trim(strval($_POST['timeout'])))) - $offset : 0),
			'multiple'        => (isset($_POST['multiple'])? (boolean)$_POST['multiple'] : false),
			'public'          => (isset($_POST['public'])? (boolean)$_POST['public'] : false),
			'parseurl'        => (isset($_POST['parseurl']) ? (boolean)$_POST['parseurl'] : false),
			'tags'            => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'reason'          => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
			'nl2br'           => (isset($_POST['nl2br']) ? (bool)$_POST['nl2br'] : false),
		);

		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		// Poll Options
		$polloptions = array();
		foreach ($input['polloptions'] as $k => $v)
		{
			if ($v)
			{
				if ($k == 'new')
				{
					foreach ($v as $v2)
					{
						$v2 = trim(strval($v2));
						if ($v2 !== '')
						{
							$polloptions[]['title'] = $v2;
						}
					}
				}
				else
				{
					$polloptions[] = array(
						'polloptionid' => intval($k),
						'title' => trim($v),
					);
				}
			}
		}

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		if ($input['nodeid'])
		{
			$result = array();
			$pollData = array(
				'title'           => $input['title'],
				'rawtext'         => $input['text'],
				'parentid'        => $input['parentid'],
//				'userid'          => $user['userid'],
				'options'         => $polloptions,
				'multiple'        => $input['multiple'],
				'public'          => $input['public'],
				'parseurl'        => $input['parseurl'],
				'timeout'         => $input['timeout'],
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'reason'          => $input['reason'],
			);

			$pollData += $this->getArticleInput();

			if ($input['nl2br'])
			{
				// not using ckeditor (on edit, 'nl2br' goes in the data array)
				$pollData['nl2br'] = true;
			}

			// We need to convert WYSIWYG html here and run the img check
			if (isset($textData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($textData['rawtext'], $options));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			// add attachment info so update() can do permission checking & add/remove attachments to this node.
			$this->addAttachments($pollData);

			$updateRet = $api->callApi('content_poll', 'update', array($input['nodeid'], $pollData));
			$this->handleErrorsForAjax($result, $updateRet);
			// If the update failed, just return and don't edit tags, attachments etc.
			if (!empty($updateRet['errors']))
			{
				return $this->sendAsJson($result);
			}

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));
		}
		else
		{
			$time = vB5_Request::get('timeNow');
			$pollData = array(
				'title'           => $input['title'],
				'rawtext'         => $input['text'],
				'parentid'        => $input['parentid'],
				'userid'          => $user['userid'],
				'authorname'      => $user['username'],
				'created'         => $time,
				//'publishdate'     => $time,
				'options'         => $polloptions,
				'multiple'        => $input['multiple'],
				'public'          => $input['public'],
				'parseurl'        => $input['parseurl'],
				'timeout'         => $input['timeout'],
				'prefixid'        => $input['prefixid'],
				'hvinput'         => $input['hvinput'],
			);

			$publish = array(
				'facebook' => !empty($_POST['fbpublish'])
			);

			//this function differs from the code it replaces
			//1) The function will run the convertWysiwygTextToBbcode like all the other types.  This should fix VBV-6557
			//2) The return url is handled via different logic.  It appears that there was some updates for the other types that
			//		the poll didn't get.  Trying to keep things consistent
			//3) Removed a handleErrorsForAjax call that won't do anything.  It's called on $nodeId which at that point is guaranteed
			//		to be an int.  It was probably intended to be $node, but since it wasn't doing anything before it seemed likely
			//		that we didn't need it.

			//for polls the parent is always the channel and not passed seperately.  Will need to
			//fix if we allow polls to be replies.
			$input['channelid'] = $input['parentid'];
			if(!$this->createNewNode('content_poll', $pollData, $publish, $input))
			{
				return;
			}
		}
		exit;
	}

	/**
	 * Creates a gallery, used by actionAlbum and actionGallery
	 */
	private function createGallery()
	{
		if (!isset($_POST['parentid']) OR !intval($_POST['parentid']))
		{
			return '';
		}

		$time = vB5_Request::get('timeNow');
		$input = array(
			'parentid'        => intval($_POST['parentid']),
			//'publishdate'     => $time,
			'created'         => $time,
			'rawtext'         => (isset($_POST['text'])) ? trim(strval($_POST['text'])) : '',
			'title'           => (isset($_POST['title'])) ? trim(strval($_POST['title'])) : 'No Title',
			'tags'            => (isset($_POST['tags'])) ? trim(strval($_POST['tags'])) : '',
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'viewperms'       => (isset($_POST['viewperms']) ? (int)$_POST['viewperms'] : 2), // Currently used only for albums
			'nl2br'           => (isset($_POST['nl2br']) ? (bool)$_POST['nl2br'] : false),
		);

		// sets publishdate
		$input += $this->getArticleInput();

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}

		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		$api = Api_InterfaceAbstract::instance();

		if (!empty($_POST['filedataid']))
		{
			// We need to convert WYSIWYG html here and run the img check
			if (isset($input['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($input['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			$options = array(
				'facebook' => $this->getFacebookOptionsForAddNode(),
				'filedataid' => $_POST['filedataid'],
			);

			if ($input['nl2br'])
			{
				// not using ckeditor (on add, 'nl2br' goes in the options array)
				$options['nl2br'] = true;
			}

			// by circumstance, photos added to an album seem to use input name="filedataid"
			// while other attachments use input name="filedataids". So thankfully we can
			// distinguish between gallery photos & extraneous attachments. Whew.
			$input['photos'] = array();
			foreach($_POST['filedataid'] AS $filedataid)
			{

				$titleKey = "title_$filedataid";
				if (isset($_POST[$titleKey]))
				{
					$caption = $_POST[$titleKey];
				}
				else
				{
					$caption = '';
				}

				$input['photos'][] =
					array(
						'caption' => $caption,
						'title' => $caption,
						'filedataid' => $filedataid,
						'options' => array(
							'isnewgallery' => true,
							'skipNotification' => true,
						)
					);
			}
		}

		// add non-gallery attachments. They will be saved under the keys 'attachments' & 'removeattachments'
		$this->addAttachments($input);
		$nodeId = $api->callApi('content_gallery', 'add', array($input, $options));

		if (!empty($nodeId['errors']))
		{
			return $nodeId;
		}

		if (!empty($input['tags']))
		{
			$tagRet = $api->callApi('tags', 'addTags', array($nodeId, $input['tags']));
			if (!empty($tagRet['errors']))
			{
				return $tagRet;
			}
		}

		return $nodeId;
	}

	/**
	 * Creates a user album, which is really just a gallery in the "Albums" channel
	 *
	 * @deprecated -- I believe this is obsolete along with the album_photo template
	 * the media tab appears to use the actionGallery below instead of this.
	 */
	function actionAlbum()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();
		$_POST['parentid'] = $api->callApi('node', 'fetchAlbumChannel', array());
		$galleryid = $this->createGallery();
		$html = '';

		$galleries = $api->callApi('profile', 'fetchAlbums', array());
		$templater = new vB5_Template('album_photo');
		foreach ($galleries as $gallery)
		{
			$templater->register('node', $gallery);
			$html .=  $templater->render();
		}

		$this->outputPage($html);
	}

	/**
	 * Creates a gallery
	 * This is called when creating a thread or reply using the "Photos" tab
	 * And when uploading photos at Profile => Media => Share Photos
	 */
	function actionGallery()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();
		$galleryid = $this->createGallery();

		$input = array(
			'parentid' => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid' => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'ret' => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
		);

		$result = array();

		if (!is_int($galleryid))
		{
			$this->handleErrorsForAjax($result, $galleryid);
			$this->sendAsJson($result);
			exit;
		}

		$node = $api->callApi('node', 'getNode', array($galleryid));
		if ($node AND empty($node['errors']))
		{
			if (empty($node['approved']))
			{
				$result['moderateNode'] = true;
			}
		}

		if (!empty($_POST['fbpublish']))
		{
			$node = $api->callApi('node', 'getContentForNodes', array(array($galleryid)));
			if ($node AND empty($node['errors']))
			{
				$node = reset($node);

				//we pretty much ignore errors here because we don't really want to indicate that
				//we failed to post if this fails and don't have a way of saying the post succeeded
				//except for this in our ajax return.
				$api->callApi('facebook', 'publishNode', array($node, true));
			}
		}

		// Sets redirect url when creating new conversation
		$this->getReturnUrl($result, $input['channelid'], $input['parentid'], $galleryid);
		$result['nodeId'] = $galleryid;
		if (!$api->callApi('user', 'hasPermissions', array('albumpermissions', 'picturefollowforummoderation')))
		{
			$result['alert'] = 'post_avaiting_moderation';
		}
		$this->sendAsJson($result);
		exit;
	}

	function actionVideo()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'title'           => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text'            => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'parentid'        => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid'       => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'nodeid'          => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'ret'             => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'tags'            => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'url_title'       => (isset($_POST['url_title']) ? trim(strval($_POST['url_title'])) : ''),
			'url'             => (isset($_POST['url']) ? trim(strval($_POST['url'])) : ''),
			'url_meta'        => (isset($_POST['url_meta']) ? trim(strval($_POST['url_meta'])) : ''),
			'videoitems'      => (isset($_POST['videoitems']) ? $_POST['videoitems'] : array()),
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'reason'          => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
			'nl2br'           => (isset($_POST['nl2br']) ? (bool)$_POST['nl2br'] : false),
		);

		//@TODO: There is no title for posting a reply or comment but api throws an error if blank. Fix this.

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		$videoitems = array();
		foreach ($input['videoitems'] as $k => $v)
		{
			if ($k == 'new')
			{
				foreach ($v as $v2)
				{
					if ($v2)
					{
						$videoitems[]['url'] = $v2['url'];
					}
				}
			}
			else
			{
				$videoitems[] = array(
					'videoitemid' => intval($k),
					'url' => $v['url'],
				);
			}
		}

		$api = Api_InterfaceAbstract::instance();

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		if ($input['nodeid'])
		{
			$result = array();
			$videoData = array(
				'title'           => $input['title'],
				'rawtext'         => $input['text'],
				'url_title'       => $input['url_title'],
				'url'             => $input['url'],
				'meta'            => $input['url_meta'],
				'videoitems'      => $videoitems,
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'reason'          => $input['reason'],
				'parentid'        => $input['parentid'],
			);

			$videoData += $this->getArticleInput();

			// We need to convert WYSIWYG html here and run the img check
			if (isset($videoData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($videoData['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			if ($input['nl2br'])
			{
				// not using ckeditor (on edit, 'nl2br' goes in the data array)
				$videoData['nl2br'] = true;
			}

			// add attachment info so update() can do permission checking & add/remove attachments to this node.
			$this->addAttachments($videoData);

			$updateRet = $api->callApi('content_video', 'update', array($input['nodeid'], $videoData));
			$this->handleErrorsForAjax($result, $updateRet);
			// If the update failed, just return and don't edit tags, attachments etc.
			if (!empty($updateRet['errors']))
			{
				return $this->sendAsJson($result);
			}

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));
		}
		else
		{
			$videoData = array(
				'title'           => $input['title'],
				'parentid'        => $input['parentid'],
				'rawtext'         => $input['text'],
				'userid'          => $user['userid'],
				'authorname'      => $user['username'],
				'created'         => vB5_Request::get('timeNow'),
				//'publishdate'     => $api->callApi('content_text', 'getTimeNow', array()),
				'url_title'       => $input['url_title'],
				'url'             => $input['url'],
				'meta'            => $input['url_meta'],
				'videoitems'      => $videoitems,
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'hvinput'         => $input['hvinput'],
			);

			$publish = array(
				'facebook' => !empty($_POST['fbpublish'])
			);


			if (!empty($_POST['setfor']))
			{
				$videoData['setfor'] = $_POST['setfor'];
			}

			//this differs from the code it replaced
			//1) Check if the returned node is moderated as add a flag to the return if it is
			if(!$this->createNewNode('content_video', $videoData, $publish, $input))
			{
				return;
			}
		}
		exit;
	}

	function actionLink()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (isset($_POST['videoitems']))
		{
			return $this->actionVideo();
		}

		$input = array(
			'title'           => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text'            => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'parentid'        => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid'       => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'nodeid'          => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'ret'             => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'tags'            => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'url_image'       => (isset($_POST['url_image']) ? trim(strval($_POST['url_image'])) : ''),
			'url_title'       => (isset($_POST['url_title']) ? trim(strval($_POST['url_title'])) : ''),
			'url'             => (isset($_POST['url']) ? trim(strval($_POST['url'])) : ''),
			'url_meta'        => (isset($_POST['url_meta']) ? trim(strval($_POST['url_meta'])) : ''),
			'url_nopreview'   => (isset($_POST['url_nopreview']) ? intval($_POST['url_nopreview']) : 0),
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'reason'          => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
			'nl2br'           => (isset($_POST['nl2br']) ? (bool)$_POST['nl2br'] : false),
		);

		//@TODO: There is no title for posting a reply or comment but api throws an error if blank. Fix this.

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		$api = Api_InterfaceAbstract::instance();

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		// Upload images
		$filedataid = 0;
		if (!$input['url_nopreview'] AND $input['url_image'])
		{
			$ret = $api->callApi('content_attach', 'uploadUrl', array($input['url_image']));

			if (empty($ret['error']))
			{
				$filedataid = $ret['filedataid'];
			}
		}

		$linkData = array(
			'title'           => $input['title'],
			'url_title'       => $input['url_title'],
			'rawtext'         => $input['text'],
			'url'             => $input['url'],
			'meta'            => $input['url_meta'],
			'filedataid'      => $filedataid,
			'iconid'          => $input['iconid'],
			'prefixid'        => $input['prefixid'],
			'parentid'        => $input['parentid'],
		);

		if ($input['nodeid'])
		{
			$result = array();
			$linkData['reason'] = $input['reason'];
			$linkData += $this->getArticleInput();

			// We need to convert WYSIWYG html here and run the img check
			if (isset($linkData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($linkData['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$result['error'] = $phrase;
					$this->sendAsJson($result);
					return;
				}
			}

			if ($input['nl2br'])
			{
				// not using ckeditor (on edit, 'nl2br' goes in the data array)
				$linkData['nl2br'] = true;
			}

			// add attachment info so update() can do permission checking & add/remove attachments to this node.
			$this->addAttachments($linkData);
			$updateRet = $api->callApi('content_link', 'update', array($input['nodeid'], $linkData));
			$this->handleErrorsForAjax($result, $updateRet);
			// If the update failed, just return and don't edit tags, attachments etc.
			if (!empty($updateRet['errors']))
			{
				return $this->sendAsJson($result);
			}

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));
		}
		else
		{
			$linkData['userid'] = $user['userid'];
			$linkData['authorname'] = $user['username'];
			$linkData['created'] = vB5_Request::get('timeNow');
			$linkData['hvinput'] = $input['hvinput'];

			$publish = array(
				'facebook' => !empty($_POST['fbpublish'])
			);

			if (!empty($_POST['setfor']))
			{
				$linkData['setfor'] = $_POST['setfor'];
			}

			if(!$this->createNewNode('content_link', $linkData, $publish, $input))
			{
				return;
			}
		}
		exit;
	}

	/**
	 * Creates a private message.
	 */
	public function actionPrivateMessage()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		if (!empty($_POST['autocompleteHelper']) AND empty($_POST['msgRecipients']))
		{
			$msgRecipients = $_POST['autocompleteHelper'];


			if (substr($msgRecipients, -1) == ';')
			{
				$msgRecipients = substr($msgRecipients, 0, -1);
			}
			$_POST['msgRecipients'] = $msgRecipients;
		}

		if (!empty($_POST['msgRecipients']) AND (substr($_POST['msgRecipients'], -1) == ';'))
		{
			$_POST['msgRecipients'] = substr($_POST['msgRecipients'], 0, -1);
		}

		$hvInput = isset($_POST['humanverify']) ? $_POST['humanverify'] : '';
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$hvInput['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$hvInput['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}
		$_POST['hvinput'] =& $hvInput;

		$_POST['rawtext'] = $_POST['text'];
		unset($_POST['text']);

		$options = array();

		if (!empty($_POST['nl2br']))
		{
			// not using ckeditor (on add, 'nl2br' goes in the options array)
			$options['nl2br'] = true;
		}

		// add attachment info so update() can do permission checking & add/remove attachments to this node.
		$data = $_POST; // let's not try to edit magic globals directly.
		$this->addAttachments($data);

		$result = $api->callApi('content_privatemessage', 'add', array($data, $options));
		$results = array();

		if (!empty($result['errors']))
		{
			if (is_array($result['errors'][0]))
			{
				$errorphrase = array_shift($result['errors'][0]);
				$phrases = $api->callApi('phrase', 'fetch', array(array($errorphrase)));
				$results['error'] = vsprintf($phrases[$errorphrase], $result['errors'][0]);
			}
			else
			{
				$phrases = $api->callApi('phrase', 'fetch', array(array($result['errors'][0])));
				$results['error'] =  $phrases[$result['errors'][0]];
			}

		}
		else
		{
			$phrases = $api->callApi('phrase', 'fetch', array(array('pm_sent')));
			$results['message'] = $phrases['pm_sent'];

			$results['nodeId'] = (int) $result;
		}

		return $this->sendAsJson($results);
	}

	public function actionParseWysiwyg()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$options = array();
		$attachments = array();
		// if this is an existing node, we need to fetch attachments so converting from source mode to
		// wysiwyg mode displays attachments. If attachments are not passed, they won't be set in the
		// vB5_Template_BbCode_Wysiwyg instance. See vB5_Template_BbCode's setAttachments() function.
		if (isset($_POST['nodeid']) AND intval($_POST['nodeid']))
		{
			$attachments =  Api_InterfaceAbstract::instance()->callApi('node', 'getNodeAttachments', array(intval($_POST['nodeid'])));
		}
		// eventually goes through vB5_Template_BbCode_Wysiwyg's doParse()
		$data = vB5_Frontend_Controller_Bbcode::parseWysiwyg($_POST['data'], $options, $attachments);
		/*
		 *	we might have some placeholders from bbcode parser. Replace them before we send it back.
		 *	I added this call because the parser was adding placeholders for the 'image_larger_version_x_y_z' phrase
		 *	in the image alt texts for images that didn't get titles set, and ckeditor was having a field day with the
		 *	placeholder, not to mention causing issues with wysiwyghtmlparser's parseUnmatchedTags() (the regex fails
		 *	to match image tags if any attribute before src has a > character).
		 *	While parseUnmatchedTags() will still have problems* if the alt text (or any attribute before src) contains
		 *	a >, getting rid of the placeholder at least prevents the problem from being caused by the parser itself.
		 *		* see VBV-12308
		 */
		$phraseCache = vB5_Template_Phrase::instance();
		$phraseCache->replacePlaceholders($data);

		return $this->sendAsJson(array('data' => $data));
	}

	/**
	 * Creates the edit title form
	 *
	 * We load the form via AJAX to ensure that the title populated in the form is the current
	 * title, instead of pulling it from the DOM.
	 */
	public function actionLoadTitleEdit()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'nodeid' => (isset($_POST['nodeid']) ? intval($_POST['nodeid']) : 0),
		);

		$results = array();

		if ($input['nodeid'] < 1)
		{
			$results['error'] = 'invalid_node';
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();
		$node = $api->callApi('node', 'getNodeContent', array($input['nodeid'], false));
		$node = $node[$input['nodeid']];

		if (!$node)
		{
			$results['error'] = 'invalid_node';
			$this->sendAsJson($results);
			return;
		}

		// render the template
		$results = vB5_Template::staticRenderAjax('contententry_titleedit', array('node' => $node));

		$this->sendAsJson($results);
	}

	/**
	 * Saves the edited title
	 */
	public function actionSaveTitleEdit()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'nodeid' => (isset($_POST['nodeid']) ? intval($_POST['nodeid']) : 0),
			'title'  => (isset($_POST['title']) ? strval($_POST['title']) : ''),
		);

		$api = Api_InterfaceAbstract::instance();

		$node = $api->callApi('node', 'getNodeContent', array($input['nodeid'], false));
		$node = $node[$input['nodeid']];

		$apiName = 'Content_' . $node['contenttypeclass'];
		$updateResult = $api->callApi($apiName, 'update', array(
			'nodeid' => $input['nodeid'],
			'data'   => array(
				'title' => $input['title'],
				'parentid' => $node['parentid'],
			),
		));

		$node = $api->callApi('node', 'getNodeContent', array($input['nodeid'], false));
		$node = $node[$input['nodeid']];

		$results = array(
			'title' => $node['title'],
		);

		if (!empty($updateResult['errors']))
		{
			$results['errors'] = $updateResult['errors'];
		}

		$this->sendAsJson($results);

	}

	public function actionLoadeditor()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'nodeid' => (isset($_POST['nodeid']) ? intval($_POST['nodeid']) : 0),
			'type' => (isset($_POST['type']) ? trim(strval($_POST['type'])) : ''),
			'view' => (isset($_POST['view']) ? trim($_POST['view']) : 'stream'),
		);

		$results = array();

		if (!$input['nodeid'])
		{
			$results['error'] = 'error_loading_editor';
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();
		$user  = $api->callApi('user', 'fetchUserinfo', array());
		$node = $api->callApi('node', 'getNodeContent', array($input['nodeid'], false));
		$node = $node[$input['nodeid']];

		if (!$node)
		{
			$results['error'] = 'error_loading_editor';
			$this->sendAsJson($results);
			return;
		}

		// the contententry template uses createpermissions, but content library's assembleContent seems to
		// set createpermissions for *REPLYING* to the node.
		// The createpermissions for editing should be handled differently, so let's re-assemble them
		// and set it for the node. (This assumes loadeditor is only called for editing)
		$createPermissions = $api->callApi('node', 'getCreatepermissionsForEdit', array($node));
		$node['createpermissions'] = $createPermissions['createpermissions'];

		//See if we should show delete
		$node['canremove'] = 0;

		// if user can soft OR hard delete, we should show the delete button. The appropriate template
		// should handle *which* delete options to show.
		$canDelete = $api->callApi('node', 'getCanDeleteForEdit', array($node));
		$node['canremove'] = $canDelete['candelete'];

		/* VM checks. I'm leaving these alone for now, but I'LL BE BACK
		 * We should update vB_Library_Content's getCanDelete() (which is downstream of node API's getCanDelete())
		 * to detect/handle the VM checks, and just remove the below code altogether.
		 */
		if (
			($node['starter'] > 0)
			AND
			($node['setfor'] > 0)
			AND
			(
				$api->callApi('user', 'hasPermissions', array('moderatorpermissions2', 'candeletevisitormessages'))
				OR
				$api->callApi('user', 'hasPermissions', array('moderatorpermissions2', 'canremovevisitormessages'))
			)
		)
		{
			// Make the editor show Delete button
			$node['canremove'] = 1;
		}
		else if (
			($node['starter'] > 0)
			AND
			($node['setfor'] > 0)
			AND
			($user['userid'] == $node['setfor'])
			AND
			$api->callApi('user', 'hasPermissions', array('visitormessagepermissions', 'can_delete_own_visitor_messages'))
		)
		{
			// Make the editor show Delete button
			$node['canremove'] = 1;
		}


		if (in_array($node['contenttypeclass'], array('Text', 'Gallery', 'Poll', 'Video', 'Link')))
		{
			if ($input['type'] == 'comment' AND $node['contenttypeclass'] == 'Text')
			{
				$results = vB5_Template::staticRenderAjax('editor_contenttype_Text_comment', array(
					'conversation'	=> $node,
					'showDelete'	=> $node['canremove'],
				));
			}
			else
			{
				$templateData = array(
					'nodeid'               => $node['nodeid'],
					'conversation'         => $node,
					'parentid'             => $node['parentid'],
					'showCancel'           => 1,
					'showDelete'           => $node['canremove'],
					'showPreview'          => 1,
					'showToggleEditor'     => 1,
					'showSmiley'           => 1,
					'showAttachment'       => 1,
					'showTags'             => ($node['nodeid'] == $node['starter'] AND $node['channeltype'] != 'vm'),
					'showTitle'            => ($node['nodeid'] == $node['starter'] AND $node['channeltype'] != 'vm'),
					'editPost'             => 1,
					'conversationType'     => $input['type'],
					'compactButtonSpacing' => 1,
					'initOnPageLoad'       => 1,
					'focusOnPageLoad'      => 1,
					'noJavascriptInclude'  => 1,
				);

				//for blog posts and articles, we need the channel info to determine if we need to display the blog / article options panel
				$channelInfo = $api->callApi('content_channel', 'fetchChannelById', array($node['channelid']));
				$templateData['channelInfo'] = $channelInfo;

				foreach (array('Text', 'Gallery', 'Poll', 'Video', 'Link') AS $type)
				{
					$templateFlag = (($type == 'Gallery') ? 'Photo' : $type);
					$templateFlagValue = ($node['contenttypeclass'] == $type ? 1 : 0);
					$templateData['allowType' . $templateFlag] =  $templateFlagValue;
					if ($templateFlagValue == 1)
					{
						$templateData['defaultContentType'] = $node['contenttypeclass'];
					}
				}

				if ($node['contenttypeclass'] == 'Gallery')
				{
					if (!empty($node['photo']))
					{
						$templateData['maxid'] = max(array_keys($node['photo']));
					}
					else
					{
						$templateData['maxid'] = 0;
					}
					//for albums we enable the viewperms edit.
					if ($node['channeltype'] == 'album')
					{
						$templateData['showViewPerms'] = 1;
					}

				}

				//content types that has no Tags. Types used should be the same used in $input['type']
				$noTagsContentTypes = array('media', 'visitorMessage'); //add more types as needed
				if ($node['nodeid'] == $node['starter'])
				{
					if (!in_array($input['type'], $noTagsContentTypes)) //get tags of the starter (exclude types that don't use tags)
					{
						$tagList = $api->callApi('tags', 'getNodeTags', array($input['nodeid']));
						if (!empty($tagList) AND !empty($tagList['tags']))
						{
							$tags = array();
							foreach ($tagList['tags'] as $tag)
							{
								$tags[] = $tag['tagtext'];
							}

							$tagList['displaytags']	= implode(', ', $tags);
							$templateData['tagList'] = $tagList;
						}
					}
				}
				if (in_array($input['type'], $noTagsContentTypes) OR $node['nodeid'] != $node['starter'])
				{
					$templateData['showTags'] = 0;
				}

				$results = vB5_Template::staticRenderAjax('contententry', $templateData);
			}
		}
		else
		{
			$results['error'] = 'error_loading_editor';
		}

		$this->sendAsJson($results);
		return;
	}

	public function actionLoadPreview()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'parentid'         => (isset($_POST['parentid'])         ? intval($_POST['parentid']) : 0),
			'channelid'        => (isset($_POST['channelid'])        ? intval($_POST['channelid']) : 0),
			'pagedata'         => (isset($_POST['pagedata'])         ? ((array)$_POST['pagedata']) : array()),
			'conversationtype' => (isset($_POST['conversationtype']) ? trim(strval($_POST['conversationtype'])) : ''),
			'posttags'         => (isset($_POST['posttags'])         ? trim(strval($_POST['posttags'])) : ''),
			'rawtext'          => (isset($_POST['rawtext'])          ? trim(strval($_POST['rawtext'])) : ''),
			'filedataid'       => (isset($_POST['filedataid'])       ? ((array)$_POST['filedataid']) : array()),
			'link'             => (isset($_POST['link'])             ? ((array)$_POST['link']) : array()),
			'poll'             => (isset($_POST['poll'])             ? ((array)$_POST['poll']) : array()),
			'video'            => (isset($_POST['video'])            ? ((array)$_POST['video']) : array()),
			'htmlstate'        => (isset($_POST['htmlstate'])        ? trim(strval($_POST['htmlstate'])) : ''),
			'disable_bbcode'   => (isset($_POST['disable_bbcode'])   ? intval($_POST['disable_bbcode']) : 0),
		);

		$results = array();

		if ($input['parentid'] < 1)
		{
			$results['error'] = 'invalid_parentid';
			$this->sendAsJson($results);
			return;
		}

		if (!in_array($input['htmlstate'], array('off', 'on_nl2br', 'on'), true))
		{
			$input['htmlstate'] = 'off';
		}

		// when creating a new content item, channelid == parentid
		$input['channelid'] = ($input['channelid'] == 0) ? $input['parentid'] : $input['channelid'];

		$templateName = 'display_contenttype_conversationreply_';
		$templateName .= ucfirst($input['conversationtype']);

		$api = Api_InterfaceAbstract::instance();
		$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', array($input['channelid']));

		// The $node['starter'] and $node['nodeid'] values are just there to differentiate starters and replies
		$node = array(
			'rawtext' => '',
			'userid' => vB5_User::get('userid'),
			'authorname' => vB5_User::get('username'),
			'tags' => $input['posttags'],
			'taglist' => $input['posttags'],
			'approved' => true,
			'created' => time(),
			'avatar' => $api->callApi('user', 'fetchAvatar', array('userid' => vB5_User::get('userid'))),
			'parentid' => $input['parentid'],
			'starter' => ($input['channelid'] == $input['parentid']) ? 0 : $input['parentid'],
			'nodeid' => ($input['channelid'] == $input['parentid']) ? 0 : 1,
		);

		if ($input['conversationtype'] == 'gallery')
		{
			$node['photopreview'] = array();
			foreach ($input['filedataid'] AS $filedataid)
			{
				$node['photopreview'][] = array(
					'nodeid' => $filedataid,
					'htmltitle' => isset($_POST['title_' . $filedataid]) ? vB_String::htmlSpecialCharsUni($_POST['title_' . $filedataid]) : '',
				);

				//photo preview is up to 3 photos only
				if (count($node['photopreview']) == 3)
				{
					break;
				}
			}
			$node['photocount'] = count($input['filedataid']);
		}

		if ($input['conversationtype'] == 'link')
		{
			$node['url_title'] = !empty($input['link']['title']) ? $input['link']['title'] : '';
			$node['url'] = !empty($input['link']['url']) ? $input['link']['url'] : '';
			$node['meta'] = !empty($input['link']['meta']) ? $input['link']['meta'] : '';
			$node['previewImage'] = !empty($input['link']['url_image']) ? $input['link']['url_image'] : '';
		}

		if ($input['conversationtype'] == 'poll')
		{
			$node['multiple'] = !empty($input['poll']['mutliple']);
			$node['options'] = array();
			if (!empty($input['poll']['options']) and is_array($input['poll']['options']))
			{
				$optionIndex = 1;
				foreach ($input['poll']['options'] AS $option)
				{
					$node['options'][] = array (
						'polloptionid' => $optionIndex,
						'title' => $option,
					);
					$optionIndex++;
				}
			}
			$node['permissions']['canviewthreads'] = 1; //TODO: Fix this!!
		}

		if ($input['conversationtype'] == 'video')
		{
			$node['url_title'] = !empty($input['video']['title']) ? $input['video']['title'] : '';
			$node['url'] = !empty($input['video']['url']) ? $input['video']['url'] : '';
			$node['meta'] = !empty($input['video']['meta']) ? $input['video']['meta'] : '';
			$node['items'] = !empty($input['video']['items']) ? $input['video']['items'] : '';
		}

		try
		{
			$results = vB5_Template::staticRenderAjax(
				$templateName,
				array(
					'nodeid' => $node['nodeid'],
					'conversation' => $node,
					'currentConversation' => $node,
					'bbcodeOptions' => $channelBbcodes,
					'pagingInfo' => array(),
					'postIndex' => 0,
					'reportActivity' => false,
					'showChannelInfo' => false,
					'showInlineMod' => false,
					'commentsPerPage' => 1,
					'view' => 'stream',
					'previewMode' => true,
				)
			);
		}
		catch (Exception $e)
		{
			if (vB5_Config::instance()->debug)
			{
				$results['error'] = 'error_rendering_preview_template ' . (string) $e;
			}
			else
			{
				$results['error'] = 'error_rendering_preview_template';
			}
			$this->sendAsJson($results);
			return;
		}

		$bbcodeoptions = array(
			'allowhtml' => in_array($input['htmlstate'], array('on', 'on_nl2br'), true),
			'allowbbcode' => !$input['disable_bbcode'],
			'htmlstate' => $input['htmlstate'],
		);

		$results = array_merge($results, $this->parseBbCodeForPreview(fetch_censored_text($input['rawtext']), $bbcodeoptions));

		$this->sendAsJson($results);
	}

	public function actionLoadnode()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = array(
			'nodeid' => (isset($_REQUEST['nodeid']) ? intval($_REQUEST['nodeid']) : 0),
			'view' => (isset($_REQUEST['view']) ? trim($_REQUEST['view']) : 'stream'),
			'page' => (isset($_REQUEST['page']) ? $_REQUEST['page'] : array()),
			'index' => (isset($_REQUEST['index']) ? floatval($_REQUEST['index']) : 0),
			'type' => (isset($_REQUEST['type']) ? trim(strval($_REQUEST['type'])) : ''),
		);

		$results = array();
		$results['css_links'] = array();

		if (!$input['nodeid'])
		{
			$results['error'] = 'error_loading_post';
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();

		$node = $api->callApi('node', 'getNodeFullContent', array('nodeid' => $input['nodeid'], 'contenttypeid' => false, 'options' => array('showVM' => 1, 'withParent' => 1)));
		$node = isset($node[$input['nodeid']]) ? $node[$input['nodeid']] : null;

		if (!$node)
		{
			$results['error'] = 'error_loading_post';
			$this->sendAsJson($results);
			return;
		}

		$currentNodeIsBlog = $node['channeltype'] == 'blog';
		$currentNodeIsArticle = $node['channeltype'] == 'article';

		if (!in_array($input['view'], array('stream', 'thread', 'activity-stream', 'full-activity-stream')))
		{
			$input['view'] = 'stream';
		}

		// add article views
		if ($currentNodeIsArticle)
		{
			// mergeNodeviewsForTopics expects an array of search results
			$tempNodes = array(
				$node['nodeid'] => array(
					'content' => array(),
				),
			);
			$tempNodes = $api->callApi('node', 'mergeNodeviewsForTopics', array($tempNodes));
			if (isset($tempNodes[$node['nodeid']]['content']['views']))
			{
				$node['views'] = $tempNodes[$node['nodeid']]['content']['views'];
			}
			unset($tempNodes);
		}

		//comment in Thread view
		// TODO Should $node['contenttypeclass'] == 'Text' be here?
		if (($input['view'] == 'thread' OR $currentNodeIsBlog OR $currentNodeIsArticle) AND $input['type'] == 'comment' AND $node['contenttypeclass'] == 'Text')
		{
			$templater = new vB5_Template('conversation_comment_item');
			$templater->register('conversation', $node);
			$templater->register('conversationIndex', floor($input['index']));
			if ($currentNodeIsBlog OR $currentNodeIsArticle)
			{
				$templater->register('commentIndex', $input['index']);
				$templater->register('parentNodeIsBlog', (bool)$currentNodeIsBlog);
				$templater->register('parentNodeIsArticle', (bool)$currentNodeIsArticle);

				$enableInlineMod = (
					!empty($node['moderatorperms']['canmoderateposts']) OR
					!empty($node['moderatorperms']['candeleteposts']) OR
					!empty($node['moderatorperms']['caneditposts']) OR
					!empty($node['moderatorperms']['canremoveposts'])
				);
				$templater->register('enableInlineMod', $enableInlineMod);
			}
			else if ($input['index'] - floor($input['index']) > 0)
			{
				$commentIndex = explode('.', strval($input['index']));
				$templater->register('commentIndex', $commentIndex[1]);
			}
			else
			{
				$templater->register('commentIndex', 1);
			}
		}
		else //reply or starter node or comment in Stream view
		{
			//Media tab Video Album
			if ($input['type'] == 'media' AND $node['contenttypeclass'] == 'Video')
			{
				$templater = new vB5_Template('profile_media_videoitem');
				$templater->register('conversation', $node);
				$templater->register('reportActivity', true);
				$results['template'] = $templater->render(true, true);
				$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

				$this->sendAsJson($results);
				return;
			}
			else
			{
				//designed to duplicate some logic in the widget_conversationdisplay template that updates a flag on the nodes used
				//by the conversation_footer template.  This really needs to be pushed back on the node API, but that's a riskier fix
				$starter = $api->callApi('node', 'getNodeFullContent', array($node['starter']));
				if (!isset($starter['error']))
				{
					$node['can_use_multiquote'] = ($starter[$node['starter']]['canreply'] AND
						($starter[$node['starter']]['channeltype'] != 'blog'));
				}
				else
				{
					//explicitly handle the error case.  This is unlikely and throwing an error here would be bad.
					//so we'll ignore it and just return false as the safest behavior.
					$node['can_use_multiquote'] = false;
				}


				$template = 'display_contenttype_';
				if ($node['nodeid'] == $node['starter'])
				{
					$template .= ($input['view'] == 'thread') ? 'conversationstarter_threadview_' : 'conversationreply_';
				}
				else
				{
					$template .= ($input['view'] == 'thread') ? 'conversationreply_threadview_' : 'conversationreply_';
				}
			}

			$conversationRoute = $api->callApi('route', 'getChannelConversationRoute', array($node['channelid']));
			$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', array($node['channelid']));

			if (strpos($input['view'], 'stream') !== false)
			{
				$totalCount = $node['totalcount'];
			}
			else
			{
				$totalCount = $node['textcount'];
			}

			$arguments = array(
				'nodeid'	=>	$node['nodeid'],
				'pagenum'	=>	$input['page']['pagenum'],
				'channelid'	=>	$input['page']['channelid'],
				'pageid'	=>	$input['page']['pageid']
			);

			$routeInfo = array(
				'routeId' => $conversationRoute,
				'arguments'	=> $arguments,
			);

			$pagingInfo = $api->callApi('page', 'getPagingInfo', array(
				$input['page']['pagenum'],
				$totalCount,
				(isset($input['page']['posts-perpage']) ? $input['page']['posts-perpage'] : null),
				$routeInfo,
				vB5_Template_Options::instance()->get('options.frontendurl')
			));

			if (!isset($node['parsedSignature']))
			{
				$signatures = array($node['userid'] => $node['signature']);
				$parsed_signatures = Api_InterfaceAbstract::instance()->callApi('bbcode', 'parseSignatures', array(array_keys($signatures), $signatures));
				$node['parsedSignature'] = $parsed_signatures[$node['userid']];
			}

			// check if user can comment on this blog / article
			// same check as can be found in widget_conversationdisplay
			$userCanCommentOnThisBlog = false;
			$userCanCommentOnThisArticle = false;
			if ($currentNodeIsBlog)
			{
				$temp = $api->callApi('blog', 'userCanComment', array($node));
				if ($temp AND empty($temp['errors']))
				{
					$userCanCommentOnThisBlog = array_shift($temp);
				}
				unset($temp);
			}
			else if ($currentNodeIsArticle)
			{
				$userCanCommentOnThisArticle = $node['canreply'];
			}

			$template .= $node['contenttypeclass'];

			$templater = new vB5_Template($template);
			$templater->register('nodeid', $node['nodeid']);
			$templater->register('currentNodeIsBlog', $currentNodeIsBlog);
			$templater->register('currentNodeIsArticle', $currentNodeIsArticle);
			$templater->register('userCanCommentOnThisBlog', $userCanCommentOnThisBlog);
			$templater->register('userCanCommentOnThisArticle', $userCanCommentOnThisArticle);
			$templater->register('conversation', $node);
			$templater->register('currentConversation', $node);
			$templater->register('bbcodeOptions', $channelBbcodes);
			$templater->register('pagingInfo', $pagingInfo);
			$templater->register('postIndex', $input['index']);
			$templater->register('reportActivity', strpos($input['view'], 'activity-stream') !== false);
			$templater->register('showChannelInfo', $input['view'] == 'full-activity-stream');
			if ($input['view'] == 'thread')
			{
				$templater->register('showInlineMod', true);
				$templater->register('commentsPerPage', $input['page']['comments-perpage']);
			}
			else if ($input['view'] == 'stream' AND !$node['isVisitorMessage']) // Visitor Message doesn't allow to be quoted. See VBV-5583.
			{
				$templater->register('view', 'conversation_detail');
			}
		}

		// send subscribed info for updating the UI
		if (!empty($node['starter']))
		{
			$topicSubscribed = $api->callApi('follow', 'isFollowingContent', array('contentId' => $node['starter']));
		}
		else
		{
			$topicSubscribed = 0;
		}

		$results['template'] = $templater->render(true, true);
		$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		$results['topic_subscribed'] = $topicSubscribed;

		$this->sendAsJson($results);
		return;
	}


	public function actionLoadNewPosts()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		/*
			BEGIN >>> Clean Input <<<
		 */
		$input = array(
			'parentid'			=> (isset($_POST['parentid'])		? intval($_POST['parentid']) : 0),	// form's parentid input. The topic starter.
			'channelid'			=> (isset($_POST['channelid'])		? intval($_POST['channelid']) : 0),
			'newreplyid'		=> (isset($_POST['newreplyid'])		? intval($_POST['newreplyid']) : 0),
			'lastloadtime'		=> (isset($_POST['lastloadtime'])		? intval($_POST['lastloadtime']) : 0),
			'lastpublishdate'	=> (isset($_POST['lastpublishdate'])		? intval($_POST['lastpublishdate']) : 0),
			'pageload_servertime'	=> (isset($_POST['pageload_servertime'])		? intval($_POST['pageload_servertime']) : 0),
			'view'				=> (isset($_POST['view'])		? trim($_POST['view']) : 'stream'),
			'currentpage'		=> (isset($_POST['currentpage'])		? intval($_POST['currentpage']) : 1),
			'pagetotal'			=> (isset($_POST['pagetotal'])		? intval($_POST['pagetotal']) : 0),
			'postcount'			=> (isset($_POST['postcount'])		? intval($_POST['postcount']) : 0),
			'postsperpage'		=> (isset($_POST['postsperpage'])		? intval($_POST['postsperpage']) : 0),
			'commentsperpage'	=> (isset($_POST['commentsperpage'])		? intval($_POST['commentsperpage']) : 0),
			'past_page_limit_aware' => (isset($_POST['past_page_limit_aware'])	? filter_var($_POST['past_page_limit_aware'], FILTER_VALIDATE_BOOLEAN) : false),
			'loadednodes'		=> array(), // Individually cleaned below
		);
		$addOneStarterExcludeFix = 0;
		// loadednodes - nodeids that are already on the page
		if (isset($_POST['loadednodes']))
		{
			$unclean['loadednodes'] = (array) $_POST['loadednodes'];
			foreach ($unclean['loadednodes'] AS $nodeid)
			{
				$nodeid = intval($nodeid);
				/*
					Currently, the "exclude" JSON results in a join like
					... LEFT JOIN closure AS exclude_closure ON ... exclude_closure.parent IN ({exclude list})
					... WHERE exclude_closure.child IS NULL ...
					which means that if we pass in the starter nodeid in the list, it'll exclude the entire thread,
					resulting in 0 results. A bit annoying, but this is the "workaround".
				*/
				if ($nodeid !== $input['parentid'])
				{
					$input['loadednodes'][$nodeid]  = $nodeid;
				}
				else
				{
					$addOneStarterExcludeFix = 1;
				}
			}
			unset($unclean);
		}
		// END >>> Clean Input <<<



		$api = Api_InterfaceAbstract::instance();
		if (!empty($input['newreplyid']))
		{
			$usersNewReply = $api->callApi('node', 'getFullContentforNodes', array($input['newreplyid']));
			$usersNewReply = (empty($usersNewReply) ? null : reset($usersNewReply));
		}
		else
		{
			$usersNewReply = null;
		}




		/*
			BEGIN >>> Redirect to new page <<<
			If we're trying to load a nodeid, and currentpage is < pagetotal, this indicates a scenario where
			a reply was posted on a page that's not the last page. vB4 behavior for this was to redirect browser
			to the page that the reply is on, so we should do the same.
		 */
		if (!empty($usersNewReply) AND $input['currentpage'] < $input['pagetotal'])
		{
			// redirect to loadnode
			$url = $api->callApi('route', 'getUrl',
				array(
					'route' => $usersNewReply['routeid'],
					'data' => $usersNewReply,
					'extra' => array('p' => $usersNewReply['nodeid'])
				)
			);
			if (is_string($url))
			{
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
				// TODO, return a template saying "redirecting... or something. The wait before reload is noticeable."
				return $this->sendAsJson(array('redirect' => $url));
			}
			else
			{
				// UNTESTED.
				// todo, send user to same topic, but with ?goto=newpost
				$url = $api->callApi('route', 'getUrl',
					array(
						'route' => $usersNewReply['routeid'],
						'data' => array('nodeid' => $usersNewReply['starter']),
						'extra' => array('goto' => 'newpost')
					)
				);
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
				return $this->sendAsJson(array('redirect' => $url));
			}
		}
		// END >>> Redirect to new page <<<



		/*
			BEGIN >>> Fetch new replies under topic <<<
		 */
		// based on widget_conversationdisplay search options
		$search_json = array(
			'date' => array('from' => $input['lastpublishdate']),
			//'date' => array('from' => $input['pageload_servertime']),	// test
			'channel' => $input['parentid'],	// parentid may not be a channel, but this is how the widget gets the data displayed.
			//'filter_show' => ???,	// TODO: should we filter "new posts" by current filter?
		);
		if ($input['view'] == 'stream')
		{
			// UNTESTED &  UNSUPPORTED

			// based on vB5_Frontend_Controller_Activity::actionGet()
			$search_json['depth'] = 2;
			$search_json['view'] = 'conversation_stream';
			$search_json['sort']['created'] = 'DESC';
		}
		else
		{
			$input['view'] = 'thread';
			$search_json['view'] = 'thread';
			// thread
			$search_json['depth'] = 1;
			$search_json['view'] = 'conversation_thread';
			$search_json['sort']['created'] = 'ASC';
			$search_json['nolimit'] = 1; // TODO: remove this?
		}
		$search_json['ignore_protected'] = 1;
		if (!empty($input['loadednodes']))
		{
			$search_json['exclude'] = $input['loadednodes'];
		}

		$numAllowed = max($input['postsperpage'] - $input['postcount'], 0);
		if (!empty($usersNewReply))
		{
			// Grab 2 extra *just* in case the one immediately after $numAllowed is the new reply
			$perpage = $numAllowed + 2 + $addOneStarterExcludeFix;
		}
		else
		{
			$perpage = $numAllowed + 1 + $addOneStarterExcludeFix;
		}

		$functionParams = array(
			$search_json,
			$perpage,
			1, 	 //pagenum
		);
		$searchResult = Api_InterfaceAbstract::instance()->callApi('search', 'getInitialResults',  $functionParams);
		$newReplies = $searchResult['results'];

		// END >>> Fetch new replies under topic <<<

		/*
			BEGIN >>> Get next page URL <<<
		 */
		$routeid = false;
		$firstnode = reset($newReplies);
		if (isset($firstnode['routeid']))
		{
			$routeid = $firstnode['routeid'];
		}
		else
		{
			// UNTESTED
			$parentnode = $api->callApi('node', 'getNodeFullContent', array('nodeid' => $input['parentid'], 'contenttypeid' => false, 'options' => array('showVM' => 1, 'withParent' => 1)));
			$parentnode = $parentnode[$input['parentid']];
			$routeid = $parentnode['routeid'];
		}
		$nextPageUrl = $api->callApi('route', 'getUrl',
			array(
				'route' => $routeid,
				'data' => array(
					'nodeid' => $input['parentid'],
					'pagenum' => $input['currentpage'] + 1,
				),
				'extra' => array()
			)
		);
		$nextPageUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $nextPageUrl;
		// END >>> Get next page URL <<<




		/*
			BEGIN >>> GENERATE TEMPLATE <<<
		 */
		$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', array($input['channelid']));
		// Used for display_contenttype_threadview_header template, post index (ex. #123 link)
		$pagingInfo = array(
			'currentpage' => $input['currentpage'],
			'perpage' => $input['postsperpage'],
		);
		// the template automatically calculates what the postIndex should be given the $postIndex *offset* (# of posts already on the page)
		$postIndex = $input['postcount'];
		$templateInfo = array(); // This is handy for debugging. Can remove once this code is stabilized.
		$topHTML = '';
		$bottomHTML = '';
		$counter = 1;
		$newRepliesSinceTime = false;	// "New replies since ##:##"
		$moreUnreadReplies = false;		// "There are more unread replies after the current page. Please click here to..."
		$past_page_limit = false;


		// ** START ** set can_use_multiquote
		// adapted from code to set can_use_multiquote in actionLoadnode()
		// we may already have the starter in $newReplies
		$starterContent = false;
		$canUseMultiquote = false;
		foreach ($newReplies AS $k => $node)
		{
			if ($node['nodeid'] == $input['parentid'])
			{
				$starterContent = $newReplies[$k]['content'];
				break;
			}
		}
		if (!$starterContent)
		{
			$starterContent = $api->callApi('node', 'getNodeFullContent', array($input['parentid']));
			if (!isset($starterContent['error']))
			{
				$starterContent = array_pop($starterContent);
			}
			else
			{
				$starterContent = false;
			}
		}
		//designed to duplicate some logic in the widget_conversationdisplay template that updates a flag on the nodes used
		//by the conversation_footer template.  This really needs to be pushed back on the node API, but that's a riskier fix
		if ($starterContent)
		{
			$canUseMultiquote = (
				$starterContent['canreply']
				AND $starterContent['channeltype'] != 'blog'
				AND $starterContent['channeltype'] != 'article'
			);
		}
		unset($starterContent);
		// ** END ** set can_use_multiquote


		foreach ($newReplies AS $node)
		{
			$node['content']['can_use_multiquote'] = $canUseMultiquote;

			if ($addOneStarterExcludeFix AND ($node['nodeid'] == $input['parentid']))
			{
				// This is the starter node that we couldn't exclude via search params,
				// so we have to filter it out via PHP here.
				continue;
			}
			if ($counter <= $numAllowed)
			{
				$templateInfo['reply'][$node['nodeid']] = true;
				$extra = array(
					'pagingInfo' => $pagingInfo,
					'postIndex' => $postIndex++,
				);
				$topHTML .= $this->renderSinglePostTemplate($node, $input['view'], $channelBbcodes, $extra) . "\n";

				if ($input['newreplyid'] AND $node['nodeid'] == $input['newreplyid'])
				{
					// We don't want to accidentally duplicate the user's reply if it's included here.
					unset($usersNewReply);
				}
				else
				{
					// Only prepend the "New post(s) since {time}" if there are posts other than the user's post that triggered
					// this.
					$newRepliesSinceTime = true;
				}
				$counter++; // We only care about this while we're still within limit.
			}
			else  // Since we limit the search results by $numAllowed +1 or +2, we'll hit this at most twice.
			{
				// Let's not show a warning more than once.
				$past_page_limit = true;
				if (!empty($usersNewReply))
				{
					// If we've yet to render the user's new reply, there's a possibility that this node is
					// the user's. Only show the "there are more unread replies" message when there are new
					// posts OTHER than the user's new reply since the last time they checked ($input['lastpublishdate'])
					if ($usersNewReply['nodeid'] != $node['nodeid'])
					{
						$moreUnreadReplies = true;
					}
				}
				else
				{
					// If we're not also fetching the user's reply, or we already rendered it within $numAllowed (above),
					// this reply will always be on the 'second page'.
					$moreUnreadReplies = true;
				}
			}
		}


		if ($newRepliesSinceTime)
		{
			$templateInfo['new_replies_since_x'] = true;
			$topHTML = $this->renderPostNoticeTemplate('new_replies_since_x', array('timestamp' => $input['lastloadtime']))
						. "\n" . $topHTML;
		}
		if (!empty($topHTML))
		{
			$topHTML .= "\n"; // If we have any replies etc rendered, add newline for human eyes looking at the HTML
		}

		if (!empty($usersNewReply))
		{
			// TODO: Add something for stream view (reverse order)?
			if (empty($input['past_page_limit_aware']) AND $input['view'] == 'thread')
			{
				$templateInfo['replies_below_on_next_page'] = true;
				// Put up a warning saying below do not fit on the current page
				$bottomHTML = $this->renderPostNoticeTemplate('replies_below_on_next_page', array('nextpageurl' => $nextPageUrl));
			}
			$templateInfo['user_own_reply'][$usersNewReply['nodeid']] = true;
			$extra = array(
				'pagingInfo' => $pagingInfo,
				'postIndex' => $postIndex++,
			);

			$usersNewReply['content']['can_use_multiquote'] = $canUseMultiquote;

			$bottomHTML .= $this->renderSinglePostTemplate($usersNewReply, $input['view'], $channelBbcodes, $extra) . "\n";
		}

		if ($moreUnreadReplies)
		{
			$templateInfo['more_replies_after_current_page'] = true;
			$bottomHTML .= $this->renderPostNoticeTemplate('more_replies_after_current_page', array('nextpageurl' => $nextPageUrl));
		}

		$template = $topHTML . $bottomHTML;
		if (empty($template))
		{
			$templateInfo['no_new_replies_at_x'] = true;
			$template = $this->renderPostNoticeTemplate('no_new_replies_at_x', array('timestamp' => vB5_Request::get('timeNow')));
		}

		// END >>> GENERATE TEMPLATE <<<

		/*
			BEGIN	>>> Return results array <<<
		 */
		$results = array();
		$results['success'] = true;
		$results['past_page_limit'] = $past_page_limit;
		$results['timenow'] = vB5_Request::get('timeNow');
		$results['template'] = $template;
		$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		// CLOSE CONNECTION BEFORE WE DO SOME RESPONSE-UNRELATED BACKEND WORK
		$this->sendAsJsonAndCloseConnection($results);

		// END	>>> Return results array <<<

		/*
			The reason I decided not to just do markread via AJAX + apidetach is that the timenow would be different, since
			the current session's time and the that apidetach/node/markread call would have a bit of lag. So it's more
			correct to do it here, and saves a request to do so.
			We should decouple the "close request" logic from applicationlight's handleAjaxApiDetached() into a separate
			function, and call it from here.
		 */
		// The library markRead() function handles the case when user is a guest. JS needs to handle the case when
		// it's cookie based threadmarking.
		$api->callApi('node', 'markRead', array($input['parentid']));

		return;
	}

	protected function renderPostNoticeTemplate($phrase_name, $data = array())
	{
		/*
			Template display_threadview_post_notice only supports single phrase var atm.
			If we need to support variable phrase var, we either need a vb:var_array or
			use vb:raw on the phrase_var parameter and investigate whether allowing
			vb:raw there is safe, and html-escape any URLs used in html (nextpageurl).
		 */
		$template_name = 'display_threadview_post_notice';
		switch($phrase_name)
		{
			case 'new_replies_since_x':
				$phrase_var = vB5_Template_Runtime::time($data['timestamp']);
				break;
			case 'no_new_replies_at_x':
				$phrase_var = vB5_Template_Runtime::time($data['timestamp']);
				break;
			case 'replies_below_on_next_page':
				$phrase_var = $data['nextpageurl'];
				break;
			case 'more_replies_after_current_page':
				$phrase_var = $data['nextpageurl'];
				break;
			default:
				return;
				break;
		}

		$templater = new vB5_Template($template_name);
		$templater->register('phrase_name', $phrase_name);
		$templater->register('phrase_var', $phrase_var);

		return $templater->render(true, true);
	}

	protected function renderSinglePostTemplate($node, $view, $channelBbcodes, $additionalData = array())
	{
		if (empty($node))
		{
			return '';
		}
		/*
		TODO: add support for blogs & articles
		 */

		if ($view == 'stream')
		{
			$templatenamePrefix = 'display_contenttype_conversationreply_';
		}
		else
		{
			// thread
			$templatenamePrefix = 'display_contenttype_conversationreply_threadview_';
		}

		$template = $templatenamePrefix . $node['contenttypeclass'];

		$templater = new vB5_Template($template);
		$templater->register('nodeid', $node['nodeid']);
		$templater->register('conversation', $node['content']);
		$templater->register('currentConversation', $node);
		$templater->register('bbcodeOptions', $channelBbcodes);
		//$templater->register('hidePostIndex', true);	// TODO: figure post# bits out.
		if (isset($additionalData['pagingInfo']))
		{
			$templater->register('pagingInfo', $additionalData['pagingInfo']);
		}
		if (isset($additionalData['pagingInfo']))
		{
			$templater->register('postIndex', $additionalData['postIndex']);
		}
		$templater->register('reportActivity', ($view == 'stream'));
		$templater->register('showChannelInfo', false);
		if ($view == 'thread')
		{
			$templater->register('showInlineMod', true);
			//$templater->register('commentsPerPage', $additionalData['comments-perpage']); // TODO: comments
		}
		else if ($view == 'stream' AND !$node['isVisitorMessage']) // Visitor Message doesn't allow to be quoted. See VBV-5583.
		{
			$templater->register('view', 'conversation_detail');
		}

		return $templater->render(true, true);
	}



	/**
	 * This handles all saves of blog data.
	 */
	public function actionBlog()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$fields = array('title', 'description', 'nodeid', 'filedataid', 'invite_usernames', 'invite_userids', 'viewperms', 'commentperms',
			'moderate_comments', 'approve_membership', 'allow_post', 'autoparselinks', 'disablesmilies', 'sidebarInfo');

		// forum options map
		$channelOpts = array('allowsmilies' => 'disablesmilies', 'allowposting' => 'allow_post');

		$input = array();
		foreach ($fields as $field)
		{
			if (isset($_POST[$field]))
			{
				$input[$field] = $_POST[$field];
			}
		}

		// allowsmilies is general
		if (isset($_POST['next']) AND ($_POST['next'] == 'permissions'))
		{
			foreach (array('autoparselinks', 'disablesmilies') AS $field)
			{
				// channeloptions
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 0 : 1);
					}
					else
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 1 : 0);
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}


		//If this is the "permission" step, we must pass the three checkboxes
		if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
		{
			foreach (array( 'moderate_comments', 'approve_membership', 'allow_post') AS $field )
			{
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = 1;
					}
					else
					{
						$input['options'][$idx] = 0;
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}
		if (empty($input['options']))
		{
			$input['options'] = array();
		}
		// Other default options
		$input['options'] += array(
			'allowbbcode' => 1,
			'allowimages' => 1,
		);
		$input['auto_subscribe_on_join'] = 1;
		$input['displayorder'] = 1;

		$api = Api_InterfaceAbstract::instance();

		$quickCreateBlog = (isset($_POST['wizard']) AND $_POST['wizard'] == '0') ? true : false; //check if in quick create blog mode (in overlay and non-wizard type)

		if (count($input) > 1)
		{
			$input['parentid'] = $api->callApi('blog', 'getBlogChannel');
			if (empty($input['nodeid']))
			{
				$nodeid = $api->callApi('blog', 'createBlog', array($input));
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . '/blogadmin/create/settings';
				if (is_array($nodeid) AND array_key_exists('errors', $nodeid))
				{
					if ($quickCreateBlog)
					{
						$this->sendAsJson($nodeid);
						return;
					}
					else
					{
						vB5_ApplicationAbstract::handleFormError($nodeid['errors'], $url);
					}

				}
				if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
				{
					if ($quickCreateBlog)
					{
						$this->sendAsJson($nodeid);
						return;
					}
					else
					{
						$urlparams = array('blogaction' => 'create', 'action2' => 'settings');
						$url = $api->callApi('route', 'getUrl', array('blogadmin', $urlparams, array()));
						header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
						vB5_Cookie::set('blogadmin_error', $nodeid['errors'][0][0]);
						if (isset($input['title']))
						{
							vB5_Cookie::set('blog_title', $input['title']);
						}
						if (isset($input['description']))
						{
							vB5_Cookie::set('blog_description', $input['description']);
						}
						die();
					}
				}
			}
			else if(isset($input['invite_usernames']) AND $input['nodeid'])
			{
				$inviteUnames = explode(',', $input['invite_usernames']);
				$inviteIds = (isset($input['invite_userids'])) ? $input['invite_userids'] : array();
				$nodeid = $input['nodeid'];
				$api->callApi('user', 'inviteMembers', array($inviteIds, $inviteUnames, $nodeid, 'member_to'));
			}
			else if (isset($input['sidebarInfo']) AND $input['nodeid'])
			{
				$modules = explode(',', $input['sidebarInfo']);
				$nodeid = $input['nodeid'];
				foreach ($modules AS $key => $val)
				{
					$info = explode(':', $val);
					$modules[$key] = array('widgetinstanceid' => $info[0], 'hide' => ($info[1] == 'hide'));
				}
				$api->callApi('blog', 'saveBlogSidebarModules', array($input['nodeid'], $modules));
			}
			else
			{

				foreach (array('allow_post', 'moderate_comments', 'approve_membership', 'autoparselinks', 'disablesmilies') as $bitfield)
				{
					if (!empty($_POST[$bitfield]))
					{
						$input[$bitfield] = 1;
					}
				}

				$nodeid = $input['nodeid'];
				unset($input['nodeid']);
				$api->callApi('content_channel', 'update', array($nodeid, $input));

				//if this is for the permission page we handle differently

			}
//			set_exception_handler(array('vB5_ApplicationAbstract','handleException'));
//
//			if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
//			{
//				throw new exception($nodeid['errors'][0][0]);
//			}
		}
		else if (isset($_POST['nodeid']))
		{
			$nodeid = $_POST['nodeid'];
			if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
			{
				$updates = array();
				foreach (array('allow_post', 'moderate_comments', 'approve_membership') as $bitfield)
				{

					if (empty($_POST[$bitfield]))
					{
						$updates[$bitfield] = 0;
					}
					else
					{
						$updates[$bitfield] = 1;
					}
				}
				$api->callApi('node', 'setNodeOptions', array($nodeid, $updates));
				$updates = array();

				if (isset($_POST['viewperms']))
				{
					$updates['viewperms'] = $_POST['viewperms'];
				}

				if (isset($_POST['commentperms']))
				{
					$updates['commentperms'] = $_POST['commentperms'];
				}

				if (!empty($updates))
				{
					$results = $api->callApi('node', 'setNodePerms', array($nodeid, $updates));
				}

			}
		}
		else
		{
			$nodeid = 0;
		}

		//If the user clicked Next we go to the permissions page. Otherwise we go to the node.
		if (isset($_POST['btnSubmit']))
		{
			if (isset($_POST['next']))
			{
				$action2 = $_POST['next'];
			}
			else
			{
				$action2 = 'permissions';
			}

			if (isset($_POST['blogaction']))
			{
				$blogaction = $_POST['blogaction'];
			}
			else
			{
				$blogaction = 'admin';
			}

			if (($action2 == 'permissions') AND
				!($api->callApi('user', 'hasPermissions', array( 'group' => 'forumpermissions2', 'permission' => 'canconfigchannel', 'nodeid' => $nodeid)))
				)
			{
				$action2 = 'contributors';
			}

			$urlparams = array('nodeid' => $nodeid, 'blogaction' => $blogaction, 'action2' => $action2);
			$url = $api->callApi('route', 'getUrl', array('blogadmin', $urlparams, array()));
		}
		else if ($quickCreateBlog)
		{
			$this->sendAsJson(array('nodeid' => $nodeid));
			return;
		}
		else
		{
			$node = $api->callApi('node', 'getNode', array('nodeid' => $nodeid));
			$url = $api->callApi('route', 'getUrl', array($node['routeid'], array('nodeid' => $nodeid, 'title' => $node['title'], 'urlident' => $node['urlident']), array()));
		}

		header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
	}

	/**
	 * This added one or more channels.  It is intended to be called from the wizard.
	 **/
	public function actionChannel()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (empty($_REQUEST['title']))
		{
			return array('error' => 'invalid_data');
		}
		$api = Api_InterfaceAbstract::instance();
		//We don't need a parentid, because the channels are by default create at root.

		if (empty($_REQUEST['parentid']) OR !intval($_REQUEST['parentid']))
		{
			$rootChannels = $api->callApi('content_channel', 'fetchTopLevelChannelIds', array());
			$data['parentid'] = $rootChannels['forum'];
		}
		else
		{
			$data['parentid'] = $_REQUEST['parentid'];
		}

		$data['title'] = $_REQUEST['title'];
		if (!empty($_REQUEST['description']) AND is_string($_REQUEST['description']))
		{
			$data['description'] = $_REQUEST['description'];
		}
		$result =  $api->callApi('content_channel', 'add', array($data));

		if (!empty($result['errors']))
		{
			return $result['errors'];
		}

		$canDelete = $api->callApi('user', 'hasPermissions', array('adminpermissions', 'canadminforums'));

		if (!$canDelete)
		{
			$canDelete = $api->callApi('user', 'hasPermissions', array('forumpermissions2', 'candeletechannel', $data['parentid']));
		}
		$this->sendAsJson(array('nodeid' => $result, 'candelete' => (int)$canDelete));
	}

	/**
	 * This handles all saves of social group data.
	 */
	public function actionSocialgroup()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$fields = array('title', 'description', 'nodeid', 'filedataid', 'invite_usernames', 'parentid', 'invite_userids',
			'group_type', 'viewperms', 'commentperms', 'moderate_topics', 'autoparselinks',
			'disablesmilies', 'allow_post', 'approve_subscription', 'group_type');

		// forum options map
		$channelOpts = array('allowsmilies' => 'disablesmilies', 'allowposting' => 'allow_post');

		$input = array();
		foreach ($fields as $field)
		{
			if (isset($_POST[$field]))
			{
				$input[$field] = $_POST[$field];
			}
		}

		//If this is the "permission" step, we must pass the four checkboxes
		if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
		{
			foreach (array( 'moderate_comments', 'autoparselinks', 'disablesmilies', 'allow_post', 'approve_subscription', 'moderate_topics') AS $field)
			{
				// channeloptions
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 0 : 1);
					}
					else
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 1 : 0);
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}

		// default input values
		$input['displayorder'] = 1;

		$api = Api_InterfaceAbstract::instance();
		if (count($input) > 1)
		{
			if (!isset($input['nodeid']) OR (intval($input['nodeid']) == 0))
			{
				$nodeid = $api->callApi('socialgroup', 'createSocialGroup', array($input));
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . '/sgadmin/create/settings';
				if (is_array($nodeid) AND array_key_exists('errors', $nodeid))
				{
					$message = $api->callApi('phrase', 'fetch', array('phrases' => $nodeid['errors'][0][0]));
					if (empty($message))
					{
						$message = $api->callApi('phrase', 'fetch', array('phrases' => 'pm_ajax_error_desc'));
					}

					vB5_ApplicationAbstract::handleFormError(array_pop($message), $url);
				}
				if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
				{
					$urlparams = array('sgaction' => 'create', 'action2' => 'settings');
					$url = $api->callApi('route', 'getUrl', array('sgadmin', $urlparams, array()));
					header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
					vB5_Cookie::set('sgadmin_error', $nodeid['errors'][0][0]);
					if (isset($input['title']))
					{
						vB5_Cookie::set('sg_title', $input['title']);
					}
					if (isset($input['description']))
					{
						vB5_Cookie::set('sg_description', $input['description']);
					}
					die();
				}

				if ($nodeid AND !empty($nodeid['errors']))
				{
					$urlparams = array('sgaction' => 'create', 'action2' => 'settings');
					$url = $api->callApi('route', 'getUrl', array('sgadmin', $urlparams, array()));
					header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
					vB5_Cookie::set('sgadmin_error', $nodeid['errors'][0][0]);
					if (isset($input['title']))
					{
						vB5_Cookie::set('sg_title', $input['title']);
					}
					if (isset($input['description']))
					{
						vB5_Cookie::set('sg_description', $input['description']);
					}
					die();
				}

			}
			else if(isset($input['invite_usernames']) AND $input['nodeid'])
			{
				$inviteUnames = explode(',', $input['invite_usernames']);
				$inviteIds = (isset($input['invite_userids'])) ? $input['invite_userids'] : array();
				$nodeid = $input['nodeid'];
				$api->callApi('user', 'inviteMembers', array($inviteIds, $inviteUnames, $nodeid, 'sg_member_to'));
			}
			else
			{
				$nodeid = $input['nodeid'];
				unset($input['nodeid']);

				$update = $api->callApi('content_channel', 'update', array($nodeid, $input));

				// set group type nodeoptions
				if (empty($update['errors']) AND isset($input['group_type']))
				{
					$bitfields = array();
					switch ($input['group_type'])
					{
						case 2:
							$bitfields['invite_only'] = 1;
							$bitfields['approve_membership'] = 0;
							break;
						case 1:
							$bitfields['invite_only'] = 0;
							$bitfields['approve_membership'] = 0;
							break;
						default:
							$bitfields['invite_only'] = 0;
							$bitfields['approve_membership'] = 1;
							break;
					}

					$api->callApi('node', 'setNodeOptions', array($nodeid, $bitfields));
				}

				//if this is for the permission page we handle differently

			}
			//			set_exception_handler(array('vB5_ApplicationAbstract','handleException'));
			//
			//			if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
			//			{
			//				throw new exception($nodeid['errors'][0][0]);
			//			}
		}
		else if (isset($_POST['nodeid']))
		{
			$nodeid = $_POST['nodeid'];
			if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
			{
				$updates = array();
				foreach (array('allow_post', 'moderate_comments', 'autoparselinks', 'disablesmilies', 'approve_subscription') as $bitfield)
				{
					if (empty($_POST[$bitfield]))
					{
						$updates[$bitfield] = 0;
					}
					else
					{
						$updates[$bitfield] = 1;
					}
				}
				$api->callApi('node', 'setNodeOptions', array($nodeid, $updates));
				$updates = array();

				if (isset($_POST['viewperms']))
				{
					$updates['viewperms'] = $_POST['viewperms'];
				}

				if (isset($_POST['commentperms']))
				{
					$updates['commentperms'] = $_POST['commentperms'];
				}

				if (!empty($updates))
				{
					$results = $api->callApi('node', 'setNodePerms', array($nodeid, $updates));
				}

			}
		}
		else
		{
			$nodeid = 0;
		}

		//If the user clicked Next we go to the permissions page. Otherwise we go to the node.
		if (isset($_POST['btnSubmit']))
		{
			if (isset($_POST['next']))
			{
				$action2 = $_POST['next'];
			}
			else
			{
				$action2 = 'permissions';
			}

			if (isset($_POST['sgaction']))
			{
				$sgaction = $_POST['sgaction'];
			}
			else
			{
				$sgaction = 'admin';
			}

			$urlparams = array('nodeid' => $nodeid, 'sgaction' => $sgaction, 'action2' => $action2);
			$url = $api->callApi('route', 'getUrl', array('sgadmin', $urlparams, array()));
		}
		else
		{
			$node = $api->callApi('node', 'getNode', array('nodeid' => $nodeid));
			$url = $api->callApi('route', 'getUrl', array($node['routeid'], array('nodeid' => $nodeid, 'title' => $node['title'], 'urlident' => $node['urlident']), array()));
		}

		header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
	}

	/**
	 * This sets a return url when creating new content and sets if the created content
	 * is a visitor message
	 *
	 */
	protected function getReturnUrl(&$result, $channelid, $parentid, $nodeid)
	{
		$api = Api_InterfaceAbstract::instance();
		$returnUrl = '';

		// ensure we have a channelid for the redirect
		if (!$channelid && $parentid)
		{
			try
			{
				$channel = $api->callApi('content_channel', 'fetchChannelById', array($parentid));
				if ($channel && isset($channel['nodeid']) && $channel['nodeid'])
				{
					$channelid = $channel['nodeid'];
				}
			}
			catch (Exception $e){}
		}

		//Get the conversation detail page of the newly created post if we are creating a starter
		if ($channelid == $parentid)
		{
			if(isset($result['moderateNode']))
			{
				$nodeid = $parentid;
			}
			$node = $api->callApi('node', 'getNode', array($nodeid));
			if ($node AND empty($node['errors']))
			{
				$url = $api->callApi('route', 'getUrl', array('route' => $node['routeid'], 'data' => $node, 'extra' => array()));
				if (is_string($url))
				{
					$returnUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
				}
				else
				{
					// if the user can't view the item they just created, return to the channel.
					$channel = $api->callApi('content_channel', 'fetchChannelById', array($channelid));
					$url = $api->callApi('route', 'getUrl', array('route' => $channel['routeid'], 'data' => $channel, 'extra' => array()));
					if (is_string($url))
					{
						$returnUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
					}
				}
			}
		}

		if (!empty($returnUrl))
		{
			$result['retUrl'] = $returnUrl;
		}
	}

	/**
	 * Get facebook related options to pass to the add node apis
	 *
	 * @return	array
	 *
	 */
	protected function getFacebookOptionsForAddNode()
	{
		return array(
			'fbpublish' => (isset($_POST['fbpublish']) && intval($_POST['fbpublish']) === 1),
			'baseurl' => vB5_Template_Options::instance()->get('options.frontendurl'),
		);
	}

	// handleAttachmentUploads() removed. Adding/removing attachments are now done inside vB_Library_Content_Text->add() & update()
	// using the 'attachments' & 'removeattachments' data generated from $_POST by addAttachments().

	// addAttachments() moved to parent so that other controllers that saves
	// post content (ex. upload which handles gallery edits) can have access to it.
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88847 $
|| #######################################################################
\*=========================================================================*/
