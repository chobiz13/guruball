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
 * @package vBulletin
 */


/**
 * Class to handle product access and autoinstall
 *
 * @package vBulletin
 */
class vB_Products
{
	private $products = array();
	private $packages = array();
	private $productObjects = array();

	/**
	 *	Construct the products object.
	 *
	 *	@param array products -- array of the form productname => isenabled for all installed products.
	 *		this would generally be taken from the
	 *	@param string packagesDir
	 */
	public function __construct($products, $packagesDir, $autoinstall)
	{
		$this->products = $products;
		$this->packages = $this->getPackagesInternal($packagesDir);

		if ($autoinstall)
		{
			$this->autoinstall($this->packages, $products, $packagesDir);
		}

		foreach($this->products AS $name => $enabled)
		{
			//the vbulletin product isn't really a normal product/package
			//and will never have an object associated with it.
			if ($name == 'vbulletin')
			{
				continue;
			}

			if($enabled)
			{
				$class = $name . '_Product';
				if (class_exists($class))
				{
					$this->productObjects[$name] = new $class;
				}
			}
		}
	}

	private function autoinstall($packages, $products, $packagesDir)
	{
		//the product name *must* the same name as the package name for
		//any autoinstalled product otherwise unpleasant things happen
		foreach($packages AS $package)
		{
			if (!isset($products[$package]))
			{
				$xmlDir = "$packagesDir/$package/xml";
				$class = $package . '_Product';

				if (class_exists($class) AND property_exists($class, 'AutoInstall') AND $class::$AutoInstall)
				{
					$info = vB_Library_Functions::installProduct($package, $xmlDir);
					if ($info !== false)
					{
						$this->products[$package] = $info['active'];
					}
				}
			}
		}
	}

	/**
	 *	Compile a list of all of the hook classes from all of the active
	 *	products.
	 */
	public function getHookClasses()
	{
		$hookClasses = array();
		foreach($this->productObjects AS $name => $object)
		{
			if (isset($object->hookClasses) AND is_array($object->hookClasses))
			{
				foreach($object->hookClasses AS $hookClass)
				{
					$hookClasses[] = $hookClass;
				}
			}
		}
		return $hookClasses;
	}

	/**
	 *	Get the list of installed products.
	 *
	 *	This should be the same as the 'products' value in the datastore and the
	 *	function mostly exists so that the unit tests can verify that.
	 */
	public function getProducts()
	{
		return $this->products;
	}

	public function getPackages()
	{
		return $this->packages;
	}

	/**
	 * gets the list of packages (folder names).
	 */
	private function getPackagesInternal($packagesDir)
	{
		$folders = array();

		if (is_dir($packagesDir))
		{
			if ($handle = opendir($packagesDir))
			{
				$prefix = $packagesDir . DIRECTORY_SEPARATOR;

				while (($file = readdir($handle)) !== false)
				{
					if (substr($file, 0, 1) != '.' and filetype($prefix . $file) == 'dir')
					{
						$folders[] = $file;
					}
				}

				closedir($handle);
			}
			else
			{
				throw new Exception("Could not open $packagesDir");
			}
		}

		return $folders;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88919 $
|| #######################################################################
\*=========================================================================*/
