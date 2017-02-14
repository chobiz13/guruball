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
 * vB_Api_Wrapper
 * This class is just a wrapper for API classes so that exceptions can be handled
 * and translated for the client.
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Wrapper
{
	use vB_Trait_NoSerialize;

	protected $controller;
	protected $api;

	public function __construct($controller, $api)
	{
		$this->controller = $controller;
		$this->api = $api;
	}

	/**
	 * This method prevents catchable fatal errors when calling the API with missing arguments
	 * @param string $method
	 * @param array $arguments
	 */
	protected function validateCall($controller, $method, $arguments)
	{
		if(get_class($controller) == 'vB_Api_Null')
		{
			/* No such Class in the core controllers
			   but it may be defined in an extension */
			return 0;
		}

		if (method_exists($controller, $method))
		{
			$reflection = new ReflectionMethod($controller, $method);
		}
		else
		{
			/* No such Method in the core controller
			   but it may be defined in an extension */
			return 0;
		}

		if ($reflection->isStatic())
		{
			return 2;
		}

		if ($reflection->isConstructor())
		{
			return 3;
		}

		if ($reflection->isDestructor())
		{
			return 4;
		}

		$index = 0;
		foreach($reflection->getParameters() as $param)
		{
			if (!isset($arguments[$index]))
			{
				if (!$param->allowsNull() AND !$param->isDefaultValueAvailable())
				{
					// cannot omit parameter
					throw new vB_Exception_Api('invalid_data');
				}
			}
			else if ($param->isArray() AND !is_array($arguments[$index]))
			{
				// array type was expected
				throw new vB_Exception_Api('invalid_data');
			}

			$index++;
		}

		return 1;
	}

	public function __call($method, $arguments)
	{
		try
		{
			// check if API method is enabled
			// @TODO this is a temp fix, fix as part of VBV-10619
			// performing checkApiState for those being called through callNamed is definitive
			// Also Skip state check for the 'getRoute' and 'checkBeforeView' api calls, because
			// this state check uses the route info from getRoute and calls checkBeforeView  to
			// determine state. See VBV-11808 and the vB5_ApplicationAbstract::checkState calls
			// in vB5_Frontend_Routing::setRoutes.
			// Why does callNamed skip the api state check??? ajax/api/* handler uses
			// callNamed, so anyone can easily skip this check on any API.
			// Adding checkCSRF to skip list, as app light & controllers need to call it before
			// they make "real" api calls.
			if (!in_array($method, array('callNamed', 'getRoute', 'checkBeforeView'))
					AND !($this->controller === 'state' AND $method === 'checkCSRF')
			)
			{
				if (!$this->api->checkApiState($method))
				{
					return false;
				}
			}

			$result = null;
			$type = $this->validateCall($this->api, $method, $arguments);

			if($type)
			{
				if (is_callable(array($this->api, $method)))
				{
					$call = call_user_func_array(array(&$this->api, $method), $arguments);

					if ($call !== null)
					{
						$result = $call;
					}
				}
			}

			if($elist = vB_Api_Extensions::getExtensions($this->controller))
			{
				foreach($elist AS $class)
				{
					if (is_callable(array($class, $method)))
					{
						$args = $arguments;
						array_unshift($args, $result);
						$call = call_user_func_array(array($class, $method), $args);

						if ($call !== null)
						{
							$result = $call;
						}
					}
				}
			}
		}
		catch (vB_Exception_Api $e)
		{
			$errors = $e->get_errors();
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']))
			{
				$trace = '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				$errors[] = array("exception_trace", $trace);
			}
			$result = array('errors' => $errors);
		}
		catch (vB_Exception_Database $e)
		{
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']) OR vB::getUserContext()->hasAdminPermission('cancontrolpanel'))
			{
				$errors = array('Error ' . $e->getMessage());
				$trace = '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				$errors[] = array("exception_trace", $trace);
				$result =  array('errors' => $errors);
			}
			else
			{
				// This text is purposely hard-coded since we don't have
				// access to the database to get a phrase
				$result = array('errors' => array(array('There has been a database error, and the current page cannot be displayed. Site staff have been notified.')));
			}
		}
		catch (Error $e)
		{
			$errors = array(array('unexpected_error', $e->getMessage()));
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']))
			{
				$trace = '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				$errors[] = array("exception_trace", $trace);
			}
			$result = array('errors' => $errors);
		}
		catch (Exception $e)
		{
			$errors = array(array('unexpected_error', $e->getMessage()));
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']))
			{
				$trace = '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				$errors[] = array("exception_trace", $trace);
			}
			$result = array('errors' => $errors);
		}

		//some array returns, unfortunately, don't follow the API conventions.  If we have a scalar
		//or the array looks like a list rather than a key=>value map then decline to add warnings to it.
		//unfortunately this isn't sufficient.  To many return values are assumed to be lists of
		//'name' => 'value' instead of fixed named fields.  The 'warnings' field gets interpreted as
		//just another item rather than being ingnored as its should be.  We'll revisit this when
		//we've normalized the API
		/*
		if (is_array($result) AND !isset($result[0]))
		{
			foreach(vB::getLoggedWarnings() AS $warning)
			{
				$result['warnings'][] = array('php_error_x', $warning);
			}
		}
		 */

		return $result;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 86816 $
|| #######################################################################
\*=========================================================================*/
