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
 * Content Collection
 * Fetches a collection of content model items of any type.
 *
 * The content collection does not perform any queries directly.  It accepts itemid
 * as an array of key => contenttypeid => contentid and creates a collection for
 * each distinct contenttype.  It then maps the item results back to the original
 * itemid keys and uses the result as the collection.
 *
 * The client code can then iterate the collection and treat each item as a generic
 * content type.
 *
 * Because of the mapping, sorting and ordering must be performed by the client code
 * that provides the original ids as performing any ordering on the contenttype
 * collections will have no affect.
 *
 * TODO: Check vB_Aggregator and see if we can move that functionality here.
 * TODO: This class is stale, do not use.
 * TODO: Remove final, and publish the constructor.
 *
 * @package vBulletin
 * @author vBulletin Development Team
 * @version $Revision: 87733 $
 * @since $Date: 2016-03-28 15:19:17 -0700 (Mon, 28 Mar 2016) $
 * @copyright vBulletin Solutions Inc.
 */
final class vB_Collection_Content extends vB_Collection
{
	/**
	 * Content collections require an item id.
	 *
	 * @var bool
	 */
	protected $allow_no_itemid = false;

	/**
	 * Distinct content types required.
	 *
	 * @var array contenttypeid => array contentids
	 */
	protected $contenttypes;



	/*Initialisation================================================================*/

	/**
	 * Constructs the collection.
	 * The itemid passed should be an array of key => contenttypeid => contentid.
	 *
	 * @param mixed $itemid					- The id of the item
	 * @param int $load_flags				- Any required info prenotification
	 */
	public function __construct($itemid = false, $load_flags = false)
	{
		if ($itemid AND is_array($itemid))
		{
			foreach ($itemid AS $content)
			{
				if (!is_array($content))
				{
					$this->is_valid = false;
					break;
				}

				foreach ($content AS $contenttypeid => $contentid)
				{
					if (!$contenttypeid)
					{
						$this->is_valid = false;
						break;
					}

					$this->contenttypes[$contenttypeid] = $contentid;
				}
			}
		}
		else
		{
			$this->is_valid = false;
		}

		parent::__construct($itemid, $load_flags);
	}



	/*LoadInfo======================================================================*/

	/**
	 * Builds or updates the collection from a db result.
	 * If child classes need to apply loaded info to items that are not part of the
	 * item model properties then they will have to extend or override this method.
	 *
	 * @param resource $result					- The result resource of the query
	 * @param int $load+query					- The query that the result is from
	 * @return bool								- Success
	 */
	protected function applyLoad($result, $load_query)
	{
		if (!parent::applyLoad($result, $load_query))
		{
			return false;
		}

		// Map the collection items back to the original itemid's to preserve the order.
		$collection = $this->itemid;

		foreach ($items AS &$content)
		{
			foreach($content AS $contenttypeid => $contentid)
			{
				if (isset($this->collection[$contenttypeid][$contentid]))
				{
					$content = $this->collection[$contenttypeid][$contentid];
				}
			}
		}

		$this->collection = $collection;

		return true;
	}


	/**
	 * Creates a contenttype collection and adds it to the content collection.
	 *
	 * @param array mixed $iteminfo				- The known properties of the new item
	 * @return vB_Item							- The created item
	 */
	protected function createItem($iteminfo, $load_flags = false)
	{
		$package = vB_Types::instance()->getContentTypePackage($iteminfo[$this->primary_key]);
		$class = vB_Types::instance()->getContentTypeClass($iteminfo[$this->primary_key]);

		$class = $package . '_Collection_Content_' . $class;

		return new $class($this->{$iteminfo[$this->primary_key]});
	}


	/**
	 * Checks if an item of a valid type to be in the collection.
	 *
	 * @param $item
	 * @return bool
	 */
	protected function validCollectionItem($item)
	{
		if (!($item instanceof vB_Item_Content))
		{
			return false;
		}

		return true;
	}


	/**
	 * Sets info on a single item.
	 * setInfo has no affect on the generic content collection.
	 */
	public function setInfo($iteminfo, $load_flags = false)
	{
		return;
	}



	/*SQL===========================================================================*/

	/**
	 * Fetches the SQL for loading.
	 *
	 * @param int $required_query				- The required query
	 * @param bool $force_rebuild				- Whether to rebuild the string
	 *
	 * @return string
	 */
	protected function getLoadQuery($required_query = self::QUERY_BASIC, $force_rebuild = false)
	{
		/* Legacy Hook $this->query_hook Removed */

		if (self::QUERY_BASIC == $required_query)
		{
			return "SELECT contenttypeid AS itemid
					FROM " . TABLE_PREFIX . "contenttype AS contenttype
			";
		}

		throw (new vB_Exception_Model('Invalid query id \'' . htmlspecialchars($required_query) . '\'specified for node item: ' . htmlspecialchars($query)));
	}



	/*Sort&Order====================================================================*/

	/**
	 * Sets the order to ASC or DESC.
	 * Content collections cannot be ordered.
	 *
	 * @param bool $descending
	 */
	public function orderDescending($descending = true)
	{
		throw (new vB_Exception_Model('Ordering on content collections must be performed before providing the itemids'));
	}


	/**
	 * Sets the sort field.
	 * Content collections cannot be sorted.
	 *
	 * @param string $field						- The client id of the field to sort by
	 */
	public function orderSortField($field)
	{
		throw (new vB_Exception_Model('Sorting on content collections must be performed before providing the itemids'));
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 87733 $
|| #######################################################################
\*=========================================================================*/
