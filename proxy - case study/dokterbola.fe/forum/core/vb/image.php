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
* Class for image processing
*
* @package 		vBulletin
* @version		$Revision: 88586 $
* @date 		$Date: 2016-05-12 11:28:42 -0700 (Thu, 12 May 2016) $
*
*/
abstract class vB_Image
{
	/**
	 * Class constants
	 */

	/**
	 * Global image type defines used by serveral functions
	 */
	const GIF = 1;
	const JPG = 2;
	const PNG = 3;

	/**
	* These make up the bit field to enable specific parts of image verification
	*/
	const ALLOW_RANDOM_FONT = 1;
	const ALLOW_RANDOM_SIZE = 2;
	const ALLOW_RANDOM_SLANT = 4;
	const ALLOW_RANDOM_COLOR = 8;
	const ALLOW_RANDOM_SHAPE = 16;

	/**
	* Options from datastore
	*
	* @var	array
	*/
	var $options = null;

	/**
	* @var	array
	*/
	var $thumb_extensions = array();

	/**
	* @var	array
	*/
	var $info_extensions = array();

	/**
	* @var	array
	*/
	var $must_convert_types = array();

	/**
	* @var	array
	*/
	var $resize_types = array();

	/**
	* @var	mixed
	*/
	var $imageinfo = null;

	/**
	* @var	array $extension_map
	*/
	var $extension_map = array(
		'gif'  => 'GIF',
		'jpg'  => 'JPEG',
		'jpeg' => 'JPEG',
		'jpe'  => 'JPEG',
		'png'  => 'PNG',
		'bmp'  => 'BMP',
		'tif'  => 'TIFF',
		'tiff' => 'TIFF',
		'psd'  => 'PSD',
		'pdf'  => 'PDF',
	);

	/*
	 * @var	bool	invalid file
	 */
	var $invalid = false;

	/**
	* @var	array	$regimageoption
	*/
	var $regimageoption = array(
		'randomfont'  => false,
		'randomsize'  => false,
		'randomslant' => false,
		'randomcolor' => false,
		'randomshape'  => false,
	);

	/**
	 * Used to translate from imagetype constants to extension name.
	 * @var	array	$imagetype_constants
	 */
	var $imagetype_constants = array(
		1 => 'GIF',
		2 => 'JPEG',
		3 => 'PNG',
		5 => 'PSD',
		6 => 'BMP',
		7 => 'TIFF',
		8 => 'TIFF'
	);

	/**
	* Constructor
	* Don't allow direct construction of this abstract class
	* Sets registry
	*
	* @return	void
	*/
	public function __construct($options)
	{
		if (!defined('ATTACH_AS_DB'))
		{
			define('ATTACH_AS_DB', 0);
		}

		if (!defined('ATTACH_AS_FILES_OLD'))
		{
			define('ATTACH_AS_FILES_OLD', 1);
		}

		if (!defined('ATTACH_AS_FILES_NEW'))
		{
			define('ATTACH_AS_FILES_NEW', 2);
		}

		if (!defined('IMAGEGIF'))
		{
			if (function_exists('imagegif'))
			{
				define('IMAGEGIF', true);
			}
			else
			{
				define('IMAGEGIF', false);
			}
		}

		if (!defined('IMAGEJPEG'))
		{
			if (function_exists('imagejpeg'))
			{
				define('IMAGEJPEG', true);
			}
			else
			{
				define('IMAGEJPEG', false);
			}
		}


		if (!defined('IMAGEPNG'))
		{
			if (function_exists('imagepng'))
			{
				define('IMAGEPNG', true);
			}
			else
			{
				define('IMAGEPNG', false);
			}
		}

		if ((($current_memory_limit =  vB_Utilities::ini_size_to_bytes(@ini_get('memory_limit'))) < 256 * 1024 * 1024) AND ($current_memory_limit > 0))
		{
			try
			{
				@ini_set('memory_limit', 256 * 1024 * 1024);
			}
			catch (Exception $e)
			{
				// just ignore
			}
		}

		$this->options = $options;
		$this->regimageoption['randomfont'] = $this->options['regimageoption'] & self::ALLOW_RANDOM_FONT;
		$this->regimageoption['randomsize'] = $this->options['regimageoption'] & self::ALLOW_RANDOM_SIZE;
		$this->regimageoption['randomslant'] = $this->options['regimageoption'] & self::ALLOW_RANDOM_SLANT;
		$this->regimageoption['randomcolor'] = $this->options['regimageoption'] & self::ALLOW_RANDOM_COLOR;
		$this->regimageoption['randomshape'] = $this->options['regimageoption'] & self::ALLOW_RANDOM_SHAPE;


		/*
			Whitelist of known & accepted file signatures that our image classes can process.
		 */
		$this->magic_numbers = array(
			// start of image => end of image
			// gif
			hex2bin("4749" . "4638" . "3961") => array('eoi' => '', 'type' => "GIF"),	// GIF89a
			hex2bin("4749" . "4638" . "3761") => array('eoi' => '', 'type' => "GIF"),	// GIF87a
			// jpeg
			hex2bin("ffd8") => array('eoi' => hex2bin("ffd9"), 'type' => "JPEG"),
			// png
			hex2bin("8950" . "4e47" . "0d0a" . "1a0a") => array('eoi' => '', 'type' => "PNG"),
			// bmp
			hex2bin("424d") => array('eoi' => '', 'type' => "BMP"),
			// tiff
			hex2bin("4d4d" . "002a") => array('eoi' => '', 'type' => "TIFF"),
			hex2bin("4949" . "2a00") => array('eoi' => '', 'type' => "TIFF"),
			// psd
			hex2bin("3842" . "5053") => array('eoi' => '', 'type' => "PSD"),	// are PSDs safe??
			// pdf - not really an image, but imagemagick accepts this for thumbnail purposes...
			//hex2bin("2550" . "4446") => array('eoi' => '', 'type' => "PDF"), // PDF added for imagemagick only.
			// MVG, SVG & possibly other "complex" formats allow external file inclusion.
		);
		$this->magic_numbers_shortcut = array();
		$this->magic_numbers_types = array();
		foreach ($this->magic_numbers AS $soi => $filetypeData)
		{
			$two = substr($soi, 0, 2);
			$this->magic_numbers_shortcut[$two][$soi] = $filetypeData;
			$this->magic_numbers_types[$filetypeData['type']] = true;
		}
	}

	/**
	* Select image library
	*
	* @return	object
	*/
	public static function instance($type = 'image')
	{
		$vboptions = vB::getDatastore()->getValue('options');

		// Library used for thumbnails, image functions
		if ($type == 'image')
		{
			$selectclass = 'vB_Image_' . (($vboptions['imagetype'] == 'Magick') ? 'ImageMagick' : 'GD');
		}
		// Library used for Verification Image
		else
		{
			switch($vboptions['regimagetype'])
			{
				case 'Magick':
					$selectclass = 'vB_Image_ImageMagick';
					break;
				default:
					$selectclass = 'vB_Image_GD';
			}
		}
		$object = new $selectclass($vboptions);
		return $object; // function defined as returning & must return a defined variable
	}

	/**
	*
	* Fetches image files from the backgrounds directory
	*
	* @return array
	*
	*/
	protected function &fetchRegimageBackgrounds()
	{
		// Get backgrounds
		$backgrounds = array();
		if ($handle = @opendir(DIR . '/images/regimage/backgrounds/'))
		{
			while ($filename = @readdir($handle))
			{
				if (preg_match('#\.(gif|jpg|jpeg|jpe|png)$#i', $filename))
				{
					$backgrounds[] = DIR . "/images/regimage/backgrounds/$filename";
				}
			}
			@closedir($handle);
		}
		return $backgrounds;
	}

	/**
	*
	* Fetches True Type fonts from the fonts directory
	*
	* @return array
	*
	*/
	protected function &fetchRegimageFonts()
	{
		// Get fonts
		$fonts = array();
		if ($handle = @opendir(DIR . '/images/regimage/fonts/'))
		{
			while ($filename =@ readdir($handle))
			{
				if (preg_match('#\.ttf$#i', $filename))
				{
					$fonts[] = DIR . "/images/regimage/fonts/$filename";
				}
			}
			@closedir($handle);
		}
		return $fonts;
	}

	/**
	*
	*
	*
	* @param	string	$type		Type of image from $info_extensions
	*
	* @return	bool
	*/
	public function fetchMustConvert($type)
	{
		return !empty($this->must_convert_types["$type"]);
	}

	/**
	*
	* Checks if supplied extension can be used by fetchImageInfo
	*
	* @param	string	$extension 	Extension of file
	*
	* @return	bool
	*/
	public function isValidInfoExtension($extension)
	{
		return !empty($this->info_extensions[strtolower($extension)]);
	}

	/**
	*
	* Checks if supplied extension can be resized into a smaller permanent image, not to be used for PSD, PDF, etc as it will lose the original format
	*
	* @param	string	$type 	Type of image from $info_extensions
	*
	* @return	bool
	*
	*/
	public function isValidResizeType($type)
	{
		return !empty($this->resize_types["$type"]);
	}

	/**
	*
	* Checks if supplied extension can be used by fetchThumbnail
	*
	* @param	string	$extension 	Extension of file
	*
	* @return	bool
	*
	*/
	public function isValidThumbnailExtension($extension)
	{
		return !empty($this->thumb_extensions[strtolower($extension)]);
	}

	/**
	*
	* Checks if supplied extension can be used by fetchThumbnail
	*
	* @param	string	$extension 	Extension of file
	*
	* @return	bool
	*
	*/
	public function fetchImagetypeFromExtension($extension)
	{
		$extension = strtolower($extension);

		if (isset($this->extension_map[$extension]))
		{
			return $this->extension_map[$extension];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gets the extension from a given image file or URL.
	 *
	 * @param	string	Could be a URL string or full path filename
	 *
	 * @return mixed	array of thumbnail ext
	 */
	public function fetchImageExtension($file)
	{
		// @TODO - This should use the vB_vURL class, which optionally uses cURL under the hood
		// let's try to get it from curl
		if (function_exists('curl_getinfo'))
		{
			$connection = curl_init();
			curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($connection , CURLOPT_URL, $file);
			curl_exec($connection);
			$type = @curl_getinfo($connection, CURLINFO_CONTENT_TYPE);
			if (!empty($type))
			{
				$type = explode('/', $type);
				if (!empty($type[1]) AND isset($this->extension_map[$type[1]]))
				{
					return $this->extension_map[$type[1]];
				}
			}
		}

		// now from full path name
		$info = @exif_imagetype($file);
		if (is_numeric($info) AND isset($this->imagetype_constants[$info]))
		{
			return $this->imagetype_constants[$info];
		}

		return false;
	}

	/*
	 *	Returns the "orientation" exif data for image file at specified location
	 *
	 * @param String	$location	File location. Must be readable.
	 *
	 * @return int	0 for undefined or invalid. 1-8 if found & valid.
	 *
	 * @access protected
	 */
	protected function getOrientation($location)
	{
		/*
			This function does not care about security.
			It's not vulnerable to anything I'm aware of (unless exif_read_data has a vulnerability)
			but it also doesn't care if someone stuck in code in the exif data.

			Note, children may have their own library methods of getting this data. This is a fallback.
		 */
		if (function_exists('exif_read_data'))
		{
			$fileinfo = @exif_read_data($location);
			if (isset($fileinfo['Orientation']))
			{
				$orientation = intval($fileinfo['Orientation']);
				if ($orientation >= 1 AND $orientation <= 8)
				{
					return $orientation;
				}
			}
		}

		return 0;
	}

	protected function orientationToAnglesCW($orientation)
	{
		// source: https://beradrian.wordpress.com/2008/11/14/rotate-exif-images/
		$angles = 0;
		switch ($orientation)
		{
			case 3:
				$angles = 180;
				break;
			case 5:
				$angles = 90; // and flop
				break;
			case 6:
				$angles = 90;
				break;
			case 7:
				$angles = 270; // and flop
				break;
			case 8:
				$angles = 270;
				break;
			default:
				break;
		}

		return $angles;
	}

	protected function orientationToFlipFlop($orientation)
	{
		// source: https://beradrian.wordpress.com/2008/11/14/rotate-exif-images/
		$flip = 0; // about x
		$flop = 0; // about y
		switch ($orientation)
		{
			case 2:
				$flop = 1;
				break;
			case 4: // top to bottom
				$flip = 1;
				break;
			case 5:
				$flop = 1;
				break;
			case 7:
				$flop = 1;
				break;
			default:
				break;
		}

		return array(
			'flip' => $flip,
			'flop' => $flop,
		);
	}

	/**
	 * Rotate image from $fileContents as specified by 'orientation' exif tag and save rotated/flipped/flopped
	 * image at temp directory.
	 *
	 * @param	string	  $fileContents		file_get_contents($location)
	 * @param	string	  $location			image location
	 *
	 * @return	String		If successful, non-empty file location that holds the rotated image.
	 *						Empty string if not successful
	 */
	public function orientImage($fileContents, $location)
	{
		/*
			Initially I had $location of the old file passed in as well, and just overwrote the old file,
			but I decided that could be exploitable in the future, so we should never write to
			an unknown location, only the temp directory. This means the CALLER must handle cleaning up
			the old data.
			This way, no matter where this gets called in the future, it won't be able to accidentally/
			maliciously delete a core/system file, for ex.
		 */
		/*
			Use of rand() instead of crypto-safe random_int() is intentional.
			This rand() is meant STRICTLY for md5 collision-avoidance, NOT cryptography, in the case
			when 2 guests upload an image at the same microtime(). So it makes sense to use a quick
			random source.
			We could alternatively pass in like	sessionhash or something, but this is probably simpler,
			faster & enough to dodge filename collisions from getTmpFileName().
			Unless you're unlucky to a divine level.
		 */
		$newfile = vB_Utilities::getTmpFileName(rand(), 'vb_image_rotated_');
		if (empty($newfile))
		{
			return "";
		}

		$location = realpath($location); // apparently windows has issues w/o full path + imagick.
		if (empty($fileContents) OR empty($location))
		{
			return "";
		}

		$safe = $this->verifyImageFile($fileContents, $location);
		if (!$safe)
		{
			// Let's leave it to the caller to delete the dangerous image.
			// I don't want to allow accidental/arbitrary deletes by this function.

			return "";
		}

		$orientation = $this->getOrientation($location);
		if (empty($orientation))
		{
			return "";
		}

		return $this->orientImageInternal($fileContents, $location, $orientation, $newfile);
	}

	/**
	 * Rotate image from $fileContents as specified by 'orientation' exif tag and save rotated/flipped/flopped
	 * image at temp directory.
	 *
	 * @param	string	  $fileContents    File contents, typically result of of file_get_contents($location)
	 * @param	string	  $location        image location
	 * @param	int       $orientation     image orientation from exif data, typically result of getOrientation($location)
	 * @param	string    $newfile         new image write destination
	 *
	 * @return	String		If successful, non-empty file location that holds the rotated image.
	 *						Empty string if not successful
	 *
	 * @access	protected
	 */
	protected function orientImageInternal($fileContents, $location, $orientation, $newfile)
	{
		// Do not make this public. Don't call this outside of orientImage().
	}

	/*
	 * Returns false if $data does not contain the a known file signature for
	 * images we support. Returns the type in the format of $extension_map if identified.
	 *
	 * @param  String  $data
	 *
	 * @return	String|false
	 *
	 * @access protected
	 */
	protected function magicWhiteList($data)
	{
		$magicNumbers = $this->magic_numbers;
		$shortCut = $this->magic_numbers_shortcut;

		$checked = false;

		$two = substr($data, 0, 2);
		if (!isset($shortCut[$two]))
		{
			return false;
		}

		foreach ($shortCut[$two] AS $soi => $filetypeData)
		{
			$eoi = $filetypeData['eoi'];
			$temp_begin = substr($data, 0, strlen($soi));
			// one liner for below block: $temp_end = substr($data, -strlen($eoi), strlen($eoi));
			if (!empty($eoi))
			{
				$temp_end = substr($data, -strlen($eoi));
			}
			else
			{
				$temp_end = '';
			}

			if ($temp_begin === $soi AND $temp_end === $eoi)
			{
				return $filetypeData['type'];
			}
		}

		return false;
	}

	/**
	 * Checks for HTML tags that can be exploited via IE, & scripts in exif tags
	 *
	 * @param string   $fileContents		Contents of the file e.g. file_get_contents($location)
	 * @param string   $location            Full filepah
	 *
	 * @return bool
	 *
	 */
	public function verifyFileHeadersAndExif($fileContents, $location)
	{
		if (empty($fileContents) OR empty($location))
		{
			throw new vB_Exception_Api('upload_invalid_image');
		}

		// Verify that file is playing nice
		$header = substr($fileContents, 0, 256);
		if ($header)
		{
			if (preg_match('#<html|<head|<body|<script|<pre|<plaintext|<table|<a href|<img|<title#si', $header))
			{
				throw new vB_Exception_Api('upload_invalid_image');
			}
		}
		else
		{
			return false;
		}

		if (function_exists('exif_read_data') AND function_exists('exif_imagetype'))
		{
			$filetype = @exif_imagetype($location);
			if (in_array($filetype, array(
				IMAGETYPE_TIFF_II,
				IMAGETYPE_TIFF_MM,
				IMAGETYPE_JPEG
			)))
			{
				if ($fileinfo = @exif_read_data($location))
				{
					$this->invalid = false;
					array_walk_recursive($fileinfo, array($this, 'checkExif'));
					if ($this->invalid)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Checks for HTML tags that can be exploited via IE, scripts in exif tags, and checks file signature
	 * against a whitelist of image signatures (currently only support gif, jpg, bmp, tif, psd)
	 *
	 * @param string   $fileContents		Contents of the file e.g. file_get_contents($location)
	 * @param string   $location            Full filepah
	 *
	 * @return bool		True if nothing was detected, false if headers were not found, throws an exception
	 *					if possible nasty was detected.
	 *
	 */
	public function verifyImageFile($fileContents, $location)
	{
		/*
			Maintenance note:
			Any non image-specific validation/sanitation we add to this should also be applied to
			fetchImageInfoForThumbnails()
		 */

		if (!$this->verifyFileHeadersAndExif($fileContents, $location))
		{
			return false;
		}

		/*
			do NOT use ImageMagick identify for verifying images.
			Older versions of imagick will be vulnerable to imagetragick exploit.
		 */


		// imagetragick filetype whitelist check.
		if (!$this->magicWhiteList($fileContents) OR
			!$this->fileLocationIsImage($location)
		)
		{
			throw new vB_Exception_Api('upload_invalid_image');
		}

		return true;
	}

	/*	Scan string for data that "could" be used to exploit an image but
	 *  would require a badly configured server.
	 *
	 * @param	string String to check for possible invalid data
	 *
	 * @return	bool
	 */
	protected function checkExif($value, $key)
	{
		if (preg_match('#<\?|<script#si', $value))
		{
			$this->invalid = true;
		}
	}

	/**
	*
	* Retrieve info about image
	*
	* @param	string	filename	Location of file
	* @param	string	extension	Extension of file name
	*
	* @return	array	[0]			int		width
	*					[1]			int		height
	*					[2]			string	type ('GIF', 'JPEG', 'PNG', 'PSD', 'BMP', 'TIFF',) (and so on)
	*					[scenes]	int		scenes
	*					[channels]	int		Number of channels (GREYSCALE = 1, RGB = 3, CMYK = 4)
	*					[bits]		int		Number of bits per pixel
	*					[library]	string	Library Identifier
	*
	*/
	public function fetchImageInfo($filename) {}

	/*
	 * See fetchImageInfo()
	 */
	public function fetchImageInfoForThumbnails($filename)
	{
		return $this->fetchImageInfo($filename);
	}

	/**
	*
	* Output an image based on a string
	*
	* @param	string		string	String to output
	* @param 	bool		moveabout	move text about
	*
	* @return	array		Array containing imageInfo: filedata, filesize, filetype and htmltype
	*
	*/
	public function getImageFromString($string, $moveabout = true) {}

	/**
	*
	* Returns an array containing a thumbnail, creation time, thumbnail size and any errors
	*
	* @param	string	filename	filename of the source file
	* @param	string	location	location of the source file
	* @param	int		maxwidth
	* @param	int		maxheight
	* @param	int		quality		Jpeg Quality
	* @param bool		labelimage	Include image dimensions and filesize on thumbnail
	* @param bool		drawborder	Draw border around thumbnail
	* @param	bool	jpegconvert
	* @param	bool	sharpen
	* @param			owidth
	* @param			oheight
	* @param			ofilesize
	*
	* @return	array
	*
	*/
	public function fetchThumbnail($filename, $location, $maxwidth = 100, $maxheight = 100, $quality = 75, $labelimage = false, $drawborder = false, $jpegconvert = false, $sharpen = true, $owidth = null, $oheight = null, $ofilesize = null) {}

	/** Crop the profile image
	 *
	 * 	@param 	array $imgInfo contains all the required information
	 * 	* filename
	 * 	* extension
	 * 	* filedata
	 * 	* width
	 * 	* height
	 * 	* x1
	 * 	* y1
	 * 	@param	int	$maxwidth
	 * 	@param	int	$maxheight
	 * 	@param	bool $forceResize force generation of a new file
	 *
	 *	@return	mixed	array of data with the cropped image info:
	 *	* width
	 *	* height
	 *	* filedata
	 *	* filesize
	 *	* dateline
	 *	* imageerror (not used)
	 *	* filename (if the filename was changed during processing)
	 **/
	public function cropImg($imgInfo, $maxwidth = 100, $maxheight = 100, $forceResize = false){}

	/**
	 * Fetch a resize image from an existing filedata
	 *
	 * @param	array	File information
	 *
	 *
	 */
	public function fetchResizedImageFromFiledata(&$record, $type)
	{
		$options = vB::getDatastore()->get_value('options');
		$sizes = @unserialize($options['attachresizes']);
		$filename = 'temp.' . $record['extension'];
		if (!isset($sizes[$type]) OR empty($sizes[$type]))
		{
			throw new vB_Exception_Api('thumbnail_nosupport');
		}

		if ($options['attachfile'])
		{
			if ($options['attachfile'] == ATTACH_AS_FILES_NEW) // expanded paths
			{
				$path = $options['attachpath'] . '/' . implode('/', preg_split('//', $record['userid'],  -1, PREG_SPLIT_NO_EMPTY)) . '/';
			}
			else
			{
				$path = $options['attachpath'] . '/' . $record['userid'] . '/';
			}
			$location = $path . $record['filedataid'] . '.attach';
		}
		else
		{
			// Must save filedata to a temp file as the img operations require a file read
			$location = vB_Utilities::getTmpFileName($record['userid'], 'vbimage');
			@file_put_contents($location, $record['filedata']);
		}

		$resized = $this->fetchThumbnail($filename, $location, $sizes[$type], $sizes[$type], $options['thumbquality']);

		$record['resize_dateline'] = $resized['filesize'];
		$record['resize_filesize'] = strlen($resized['filedata']);

		if ($options['attachfile'])
		{
			if ($options['attachfile'] == ATTACH_AS_FILES_NEW) // expanded paths
			{
				$path = $options['attachpath'] . '/' . implode('/', preg_split('//', $record['userid'],  -1, PREG_SPLIT_NO_EMPTY)) . '/';
			}
			else
			{
				$path = $options['attachpath'] . '/' . $record['userid'] . '/';
			}
			@file_put_contents($path .  $record['filedataid'] . '.' . $type, $resized['filedata']);
		}
		else
		{
			$record['resize_filedata'] = $resized['filedata'];
		}

 		vB::getDbAssertor()->assertQuery('vBForum:replaceIntoFiledataResize', array(
 			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
 			'filedataid'      => $record['filedataid'],
 			'resize_type'     => $type,
			'resize_filedata' => $options['attachfile'] ? '' : $record['resize_filedata'],
			'resize_filesize' => $record['resize_filesize'],
			'resize_dateline' => vB::getRequest()->getTimeNow(),
			'resize_width'    => $resized['width'],
			'resize_height'   => $resized['height'],
			'reload'          => 0,
 		));

		if (!$options['attachfile'])
		{
			@unlink($location);
		}
	}

	/* Load information about a file base on the data
	 *
	 * @param 	mixed	database record
	 * @param	mixed	size of image requested [ICON/THUMB/SMALL/MEDIUM/LARGE/FULL]
	 * @param	bool	should we include the image content
	 *
	 * @return	mixed	array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 */
	public function loadFileData($record, $type = vB_Api_Filedata::SIZE_FULL, $includeData = true)
	{
		$options = vB::getDatastore()->get_value('options');
		$type = vB_Api::instanceInternal('filedata')->sanitizeFiletype($type);

		// Correct any improper paths. See See VBV-13389.
		// @TODO this block of code can be removed in a future version,
		// when we no longer want to support this instance of bad data.
		if ($options['attachfile'] == ATTACH_AS_FILES_NEW) // expanded paths
		{
			$testpath = $options['attachpath'] . '/' . implode('/', preg_split('//', $record['userid'],  -1, PREG_SPLIT_NO_EMPTY)) . '/';
			$testfile = $testpath . $record['filedataid'] . '.attach';

			// If the path doesn't exist, try alternative (incorrect) paths where
			// we *may* be storing the image, if the admin converted image storage
			// from the database to the filesystem between vB5.0 and vB5.1.3
			// This is basically using ATTACH_AS_FILES_OLD even though the option is
			// set to ATTACH_AS_FILES_NEW. See VBV-13389 for more details.
			if (!file_exists($testfile))
			{
				$testpath = $options['attachpath'] . '/' . $record['userid'] . '/';
				$testfile = $testpath . $record['filedataid'] . '.attach';

				if (file_exists($testfile))
				{
					// We found the incorrectly stored file; let's copy it to the
					// correct location, which will allow this function to display
					// it and/or generate the requested resized version of it.
					// Don't remove the old file, in case the copy fails, and since
					// viewing a resource should never have the possible side effect
					// of deleting it. An upgrade step will fix the bad storage.
					$newpath = vB_Library::instance('filedata')->fetchAttachmentPath($record['userid'], ATTACH_AS_FILES_NEW) . '/';
					$newfile = $newpath . $record['filedataid'] . '.attach';
					copy($testfile, $newfile);
				}
			}
		}
		// end VBV-13389

		if ($type != vB_Api_Filedata::SIZE_FULL)
		{
			if ($options['attachfile'])
			{
				if ($options['attachfile'] == ATTACH_AS_FILES_NEW) // expanded paths
				{
					$path = $options['attachpath'] . '/' . implode('/', preg_split('//', $record['userid'],  -1, PREG_SPLIT_NO_EMPTY)) . '/';
				}
				else
				{
					$path = $options['attachpath'] . '/' . $record['userid'] . '/';
				}
				$path .= $record['filedataid'] . '.' . $type;
			}

			// Resized image wasn't found
			if (
				empty($record['resize_type'])
					OR
				empty($record['resize_filesize'])
					OR
				(empty($record['resize_filedata']) AND !$options['attachfile'])
					OR
				(
					$options['attachfile']
						AND
					!file_exists($path)
				)
				OR
				$record['reload']
			)
			{
				$this->fetchResizedImageFromFiledata($record, $type);
			}

			$results = array(
				'filesize'   => $record['resize_filesize'],
				'dateline'   => $record['resize_dateline'],
				'headers'    => vB_Library::instance('content_attach')->getAttachmentHeaders(strtolower($record['extension'])),
				'filename'   => $type . '_' . $record['filedataid'] . "." . strtolower($record['extension']),
				'extension'  => $record['extension'],
			   	'filedataid' => $record['filedataid'],
			);

			if ($options['attachfile'] AND $includeData)
			{
				$results['filedata'] = @file_get_contents($path);
			}
			else if ($includeData)
			{
				$results['filedata'] = $record['resize_filedata'];
			}
		}
		else
		{
			$results = array(
				'filesize'   => $record['filesize'],
				'dateline'   => $record['dateline'],
				'headers'    => vB_Library::instance('content_attach')->getAttachmentHeaders(strtolower($record['extension'])),
				'filename'   => 'image_' . $record['filedataid'] . "." . strtolower($record['extension']),
				'extension'  => $record['extension'],
				'filedataid' => $record['filedataid'],
			);

			if ($options['attachfile'] AND $includeData)
			{

				if ($options['attachfile'] == ATTACH_AS_FILES_NEW) // expanded paths
				{
					$path = $options['attachpath'] . '/' . implode('/', preg_split('//', $record['userid'],  -1, PREG_SPLIT_NO_EMPTY)) . '/';
				}
				else
				{
					$path = $options['attachpath'] . '/' . $record['userid'] . '/';
				}

				$results['filedata'] = file_get_contents($path .  $record['filedataid'] . '.attach');

			}
			else if ($includeData)
			{
				$results['filedata'] = $record['filedata'];

			}
		}

		return $results;
	}

	/** standard getter
	 *
	 *	@return	mixed	array of file extension-to-type maps , like 'gif' => "GIF'
	 *  **/
	public function getExtensionMap()
	{
		return $this->extension_map;
	}

	/**
	 * standard getter
	 *
	 * @return mixed	array of must conver types
	 */
	public function getConvertTypes()
	{
		return $this->must_convert_types;
	}

	/**
	 * standard getter
	 *
	 * @return mixed	array of valid extensions
	 */
	public function getInfoExtensions()
	{
		return $this->info_extensions;
	}

	/**
	 * standard getter
	 *
	 * @return mixed	array of resize types
	 */
	public function getResizeTypes()
	{
		return $this->resize_types;
	}

	/**
	 * standard getter
	 *
	 * @return mixed	array of thumbnail ext
	 */
	public function getThumbExtensions()
	{
		return $this->thumb_extensions;
	}

	/**
	 * Attempt to resize file if the filesize is too large after an initial resize to max dimensions or the file is already within max dimensions but the filesize is too large
	 *
	 * @param	bool	Has the image already been resized once?
	 * @param	bool	Attempt a resize
	 */
	function bestResize($width, $height)
	{
		// Linear Regression
		$maxuploadsize = vB::getUserContext()->getLimit('avatarmaxsize');
		switch(vB::getDatastore()->getOption('thumbquality'))
		{
			case 65:
				// No Sharpen
				// $magicnumber = round(379.421 + .00348171 * $this->maxuploadsize);
				// Sharpen
				$magicnumber = round(277.652 + .00428902 * $maxuploadsize);
				break;
			case 85:
				// No Sharpen
				// $magicnumber = round(292.53 + .0027378 * $maxuploadsize);
				// Sharpen
				$magicnumber = round(189.939 + .00352439 * $maxuploadsize);
				break;
			case 95:
				// No Sharpen
				// $magicnumber = round(188.11 + .0022561 * $maxuploadsize);
				// Sharpen
				$magicnumber = round(159.146 + .00234146 * $maxuploadsize);
				break;
			default:	//75
				// No Sharpen
				// $magicnumber = round(328.415 + .00323415 * $maxuploadsize);
				// Sharpen
				$magicnumber = round(228.201 + .00396951 * $maxuploadsize);
		}

		$xratio = ($width > $magicnumber) ? $magicnumber / $width : 1;
		$yratio = ($height > $magicnumber) ? $magicnumber / $height : 1;

		if ($xratio > $yratio AND $xratio != 1)
		{
			$new_width = round($width * $xratio);
			$new_height = round($height * $xratio);
		}
		else
		{
			$new_width = round($width * $yratio);
			$new_height = round($height * $yratio);
		}
		if ($new_width == $width AND $new_height == $height)
		{	// subtract one pixel so that requested size isn't the same as the image size
			$new_width--;
		}
		return array('width' => $new_width, 'height' => $new_height);
	}

	/**
	 * Determine if the given extension should be treated as an image for
	 * size $type as far as HTML is concerned. These types also align with
	 * the cangetimgattachment permission.
	 *
	 * @param	String	$extension	File extension, usually from filedata.extension.
	 * @param	String	$type	One of vB_Api_Filedata::SIZE_X strings
	 *
	 * @return	bool
	 */
	public function isImageExtension($extension, $type = vB_Api_Filedata::SIZE_FULL)
	{
		/*
			Extensions don't really matter in terms of validation, only use this
			for purposes of "should I use an <img > or <a > for this file inclusion
			in HTML?

			TODO: deprecate this & move out into bbcode parsers.
		 */
		$extension = trim(strtolower($extension), " \t\n\r\0\x0B.");
		$type = vB_Api::instanceInternal('filedata')->sanitizeFiletype($type);
		$isImage = false;
		// We can support these as images at fullsize:
		$currentlySupportedImageExtensions = array(
			'png' => true,
			'bmp' => true, // this is currently "disabled" by app light due to an IE xss issue...?
			'jpeg' => true,
			'jpg' => true,
			'jpe' => true,
			'gif' => true,
		);

		/*
		Note, the reason we use the above list instead of $this->isValidInfoExtension() is
		that even if the image tool can "get the image info", this doesn't mean we converted the
		file to an image. For example, with imagemagick we could get the "image info" of a PDF,
		but the fullsize file is still gonna be a PDF.
		As far as I know, the only time we convert a file to a "simple" image
		(i.e. a type in the above list) is when we request a resize. As such, I'm going with
		only bothering to check the library-specific lists for resizes, and sticking with the
		above list for full-size images.
		Above is a subset of https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#Supported_image_formats
		 */

		// If requesting a fullsize, determine if it's one of the "basic" images...
		// otherwise, always force them to download etc.
		if ($type == vB_Api_Filedata::SIZE_FULL)
		{
			$isImage = isset($currentlySupportedImageExtensions[$extension]);
		}
		else
		{
			// This means a resize of the image should be provided instead.
			$isImage = $this->imageThumbnailSupported($extension);
		}


		return (bool) $isImage;
	}

	/**
	 * Determine if the given location holds a whitelisted image file. Return false
	 * if not an image or not whitelisted.
	 *
	 * @param	String	$location	Full file path
	 *
	 * @return	bool
	 */
	public function fileLocationIsImage($location)
	{
		/*
			If *any* of the available checks fail, assume it's not an image.
			Report it as image only if *all* available checks pass.
			This might be a bit strict, but is safer.
		 */
		$isImage = false;

		if (function_exists('exif_imagetype'))
		{
			$imageType = @exif_imagetype($location);
			$isImage = (bool)$imageType;
			if (!$isImage)
			{
				return false;
			}
		}

		if (function_exists('finfo_open') AND function_exists('finfo_file'))
		{
			/*
			 * TODO: When pdf thumbnail support is fixed, this check might have to be updated.
			 */

			// Just in case exif_imagetype is not there. finfo extension should be installed
			// by default (except windows), and is an alternative way to detect
			// if this is an image.
			// In the future, perhaps we can just use below to set the mimetype in the database,
			// and have the fetchImage functions return the mimetype as well rather than
			// trying to set it based on the filedata.extension (which may not be correct).
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimetype = finfo_file($finfo, $location);
			if ($mimetype)
			{
				$mimetype = explode('/', $mimetype);
				$toplevel = $mimetype[0];
				if ($toplevel != 'image')
				{
					$isImage = false;
				}
				else
				{
					$isImage = true;
				}
			}
			else
			{
				$isImage = false;
			}
			if (!$isImage)
			{
				return false;
			}
		}


		if (function_exists('getimagesize'))
		{
			$imageData = @getimagesize($location);
			if (empty($imageData))
			{
				return false;
			}
		}

		// Finally... a hard-coded whitelist. This is probably the hardest bit to maintain in the future,
		// and most likely to throw a false flag.
		$magictype = $isImage = $this->magicWhiteList(file_get_contents($location));
		if (!$isImage)
		{
			return false;
		}

		/* Experimental, may not be required
		$filebits = explode('.', $location);
		if (count($filebits) < 2)
		{
			return false;
		}
		$extension = end($filebits);
		$type = $this->fetchImagetypeFromExtension($extension);
		if ($type !== $magictype)
		{
			return false;
		}
		*/

		return (bool) $isImage;
	}

	public function compareExtensionToFilesignature($extension, $location)
	{
		$magictype = $this->magicWhiteList(file_get_contents($location));

		$type = $this->fetchImagetypeFromExtension($extension);
		if (!isset($this->magic_numbers_types[$type]))
		{
			$type = false;
		}

		return ($type === $magictype);
	}

	/**
	 * Determine if the given extension can have an image thumbnail. Basically
	 * an alias for isValidThumbnailExtension().
	 * Mostly for the PDF to image thumbnail handling for imagemagick.
	 *
	 * @param	String	$extension	File extension, usually from filedata.extension.
	 *
	 * @return	bool
	 */
	public function imageThumbnailSupported($extension)
	{
		return $this->isValidThumbnailExtension($extension);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88586 $
|| #######################################################################
\*=========================================================================*/
