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

abstract class vB_Xml_Import
{
	const OPTION_OVERWRITE            = 1;
	const OPTION_IGNOREMISSINGROUTES  = 2;
	const OPTION_IGNOREMISSINGPARENTS = 4;
	const OPTION_ADDWIDGETS           = 8;

	const TYPE_ROUTE        = 'routes';
	const TYPE_CHANNEL      = 'channels';
	const TYPE_PAGE         = 'pages';
	const TYPE_PAGETEMPLATE = 'pageTemplates';
	const TYPE_WIDGET       = 'widgets';
	const TYPE_THEME        = 'themes';
	const TYPE_SCREENLAYOUT = 'screenLayouts';

	/**
	 *
	 * @var vB_dB_Assertor
	 */
	protected $db;

	/**
	 *
	 * @var int
	 */
	protected $options;

	/**
	 *
	 * @var array
	 */
	protected $parsedXML;

	protected $productid;

//	/**
//	 *
//	 * @var array
//	 */
//	protected static $existingElements;

	/**
	 *
	 * @var array
	 */
	protected static $importedElements;

	public function __construct($productid = 'vbulletin', $options = 9)
	{
		$this->db = vB::getDbAssertor();
		$this->productid = $productid;
		$this->options = $options;
	}

	public function setOptions($options)
	{
		$this->options = $options;
	}

//	public static function getIdFromGuid($type, $guid)
//	{
//		if ($id = self::getExistingId($type, $guid) OR $id = self::getImportedId($type, $guid))
//		{
//			return $id;
//		}
//		else
//		{
//			return false;
//		}
//	}

//	/**
//	 * Stores an existing element id
//	 * @param string $type
//	 * @param string $guid
//	 * @param int $id
//	 */
//	protected static function setExistingId($type, $guid, $id)
//	{
//		self::$existingElements[$type][$guid] = $id;
//	}
//
//	/**
//	 * Returns the id for an existing element
//	 * @param string $type
//	 * @param string $guid
//	 * @return int
//	 */
//	protected static function getExistingId($type, $guid)
//	{
//		if (isset(self::$existingElements[$type]) AND isset(self::$existingElements[$type][$guid]))
//		{
//			return self::$existingElements[$type][$guid];
//		}
//		else {
//			return false;
//		}
//	}

	/**
	 * Stores an imported element with the new id
	 * @param string $type
	 * @param string $guid
	 * @param int $element
	 */
	protected static function setImportedId($type, $guid, $newid)
	{
		self::$importedElements[$type][$guid] = $newid;
	}

	/**
	 * Returns the id for an imported element
	 * @param string $type
	 * @param string $guid
	 * @return int
	 */
	public static function getImportedId($type, $guid = NULL)
	{
		if ($guid == NULL)
		{
			// if no GUID is passed return an array with all elements
			return (isset(self::$importedElements[$type]) ? self::$importedElements[$type] : array());
		}
		else
		{
			if (isset(self::$importedElements[$type]) AND isset(self::$importedElements[$type][$guid]))
			{
				return self::$importedElements[$type][$guid];
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Imports objects from the specified filepath
	 * @param string $filepath
	 * @param string $guid Only import the record associated with this guid
	 */
	public function importFromFile($filepath, $guid = false)
	{
		$this->parsedXML = vB_Xml_Import::parseFile($filepath);
		$this->import($guid);
	}

	/**
	 * Imports objects from parsed XML starting at the base of the relevant objects.
	 * @param array $parsedXML
	 */
	public function importFromParsedXML($parsedXML)
	{
		$this->parsedXML = $parsedXML;
		$this->import();
	}

	/**
	 * Import objects
	 */
	protected abstract function import();

	public static function parseFile($filepath)
	{
		$xmlobj = new vB_XML_Parser(false, $filepath);

		if ($xmlobj->error_no() == 1 OR $xmlobj->error_no() == 2)
		{
			throw new Exception("Please ensure that the file $filepath exists");
		}

		if (!$parsed_xml = $xmlobj->parse())
		{
			throw new Exception('xml error '.$xmlobj->error_string().', on line ' . $xmlobj->error_line());
		}

		return $parsed_xml;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 84968 $
|| #######################################################################
\*=========================================================================*/
