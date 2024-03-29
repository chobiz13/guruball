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
 * vB_Api_Hook
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Hook extends vB_Api
{
	private $assertor;

	protected function __construct()
	{
		parent::__construct();

		$this->assertor = vB::getDbAssertor();
	}

	public function getHookList($order = array(), $where = array())
	{
		if (!$this->hasPermission())
		{
			return false;
		}

		return $this->assertor->getRows('hook', $where, $order);
	}

	public function getHookProductList()
	{
		if (!$this->hasPermission())
		{
			return false;
		}

		return $this->assertor->getRows('getHookProductInfo');
	}

	public function getXmlHooks()
	{
		if (!$this->hasPermission())
		{
			return false;
		}

		$typelist = array();
		$hooklocations = array();

		require_once(DIR . '/includes/class_xml.php');
		$types = $this->assertor->getRows('getHooktypePhrases');
		$hookfiles = vB_Api_Product::loadProductXmlList('hooks');

		foreach ($types AS $type)
		{
			$typelist[] = $type['varname'];
		}

		$vbphrase = vB_Api::instanceInternal('phrase')->fetch($typelist);

		foreach ($hookfiles AS $file)
		{
			if (!preg_match('#hooks_(.*).xml$#i', $file, $matches))
			{
				continue;
			}

			$product = $matches[1];
			$phrased_product = $products[($product ? $product : 'vbulletin')];

			if (!$phrased_product)
			{
				$phrased_product = $product;
			}

			$xmlobj = new vB_XML_Parser(false, $location . $file);
			$xml = $xmlobj->parse();

			if (!is_array($xml['hooktype'][0]))
			{
				$xml['hooktype'] = array($xml['hooktype']);
			}

			foreach ($xml['hooktype'] AS $key => $hooks)
			{
				if (!is_numeric($key))
				{
					continue;
				}

				$phrased_type = isset($vbphrase["hooktype_$hooks[type]"]) ? $vbphrase["hooktype_$hooks[type]"] : $hooks['type'];

				$hooktype = /*$phrased_product . ' : ' . */$phrased_type;

				$hooklocations["#$hooktype#"] = $hooktype;

				if (!is_array($hooks['hook']))
				{
					$hooks['hook'] = array($hooks['hook']);
				}

				foreach ($hooks['hook'] AS $hook)
				{
					$hookid = trim(is_string($hook) ? $hook : $hook['value']);
					if ($hookid !== '')
					{
						$hooklocations[$hookid] = '--- ' . $hookid . ($product != 'vbulletin' ? " ($phrased_product)" : '');
					}
				}
			}
		}

		return $hooklocations;
	}

	public function deleteHook($hookid)
	{
		if (!$this->hasPermission())
		{
			return false;
		}

		if ($hookid)
		{
			$ret = $this->assertor->delete('hook', array('hookid' => $hookid));
		}
		else
		{
			$ret = false;
		}

		$this->buildHookDatastore();

		return $ret;
	}

	public function encodeArguments($arguments)
	{
		if ($arguments AND $matches = preg_split("#[\n]+#", trim($arguments)))
		{
			$results = array();

			foreach($matches AS $argument)
			{
				list($varname, $key) = explode('=', trim($argument), 2);

				$varname = trim($varname);
				$list = array_reverse(explode('.', trim($key)));

				$result = 1;
				foreach($list AS $subkey)
				{
					$this->encodeLevel($result, $subkey);
				}

				$results[$varname] = $result;
			}

			return $results;
		}

		return array();
	}

	private function encodeLevel(&$array, $key)
	{
		$temp[$key] = $array;
		$array = $temp;
	}

	public function decodeArguments($arguments)
	{
		$result = '';
		foreach ($arguments AS $varname => $value)
		{
			$result .= $varname;

			if(is_array($value))
			{
				$this->decodeLevel($result, $value, '=');
			}

			$result .= "\n";
		}

		return $result;
	}

	private function decodeLevel(&$res, $array, $append = '.')
	{
		foreach ($array AS $varname => $value)
		{
			$res .= $append . $varname;

			if(is_array($value))
			{
				$this->decodeLevel($res, $value);
			}
		}
	}

	public function saveHook($hookid, $hookdata)
	{
		if (!$this->hasPermission())
		{
			return false;
		}

		if(isset($hookdata['arguments']))
		{
			if (!is_array($hookdata['arguments']))
			{
				throw new vB_Exception_Api('invalid_data_w_x_y_z', array($hookdata['arguments'], 'hookdata[\'arguments\']', __CLASS__, __FUNCTION__));
			}

			$hookdata['arguments'] = serialize($hookdata['arguments']);
		}

		if ($hookid)
		{
			unset ($hookdata['hookid']); // Dont alter this
			$this->assertor->update('hook', $hookdata, array('hookid' => $hookid));
		}
		else
		{
			$hookid = $this->assertor->insert('hook', $hookdata);
			if (!empty($hookid['errors']))
			{
				throw new vB_Exception_Api('invalid_data');
			}
		}

		$this->buildHookDatastore();

		return $hookid;
	}

	public function updateHookStatus($hookdata)
	{
		if (!$this->hasPermission())
		{
			return false;
		}

		if ($hookdata)
		{
			$ret = $this->assertor->assertQuery('updateHookStatus', array('hookdata' => $hookdata));
		}
		else
		{
			$ret = false;
		}

		$this->buildHookDatastore();

		return $ret;
	}

	public function getHookInfo($hookid)
	{
		if (!$this->hasPermission())
		{
			return false;
		}

		if ($hookid)
		{
			$ret = $this->assertor->getRow('getHookInfo', array('hookid' => $hookid));

			//unserialize the arguments array.  If its not an array something went
			//wrong and we'll make it an array.
			$ret['arguments'] = @unserialize($ret['arguments']);
			if (!is_array($ret['arguments']))
			{
				$ret['arguments'] = array();
			}
		}
		else
		{
			$ret = array();
		}

		return $ret;
	}

	/**
	* Saves the currently installed hooks to the datastore.
	*/
	public function buildHookDatastore()
	{
		$hooks = vB::getDbAssertor()->getRows('getHookProductList');
		vB::getDatastore()->build('hooks', serialize($hooks), 1);
	}

	/**
	* Checks the user is an admin with product/plugin permission.
	*/
	private function hasPermission()
	{
		$userid = vB::getCurrentSession()->get('userid');
		$userContext = vB::getUserContext($userid);
		$allowed = $userContext->hasAdminPermission('canadminproducts');
		unset($userid, $userContext);

		return $allowed ? true : false;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 85749 $
|| #######################################################################
\*=========================================================================*/
