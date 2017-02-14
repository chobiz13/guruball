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

class vB_vURL_cURL
{
	/**
	* String that holds the cURL callback data
	*
	* @var	string
	*/
	var $response_text = '';

	/**
	* String that holds the cURL callback data
	*
	* @var	string
	*/
	var $response_header = '';

	/**
	* cURL Handler
	*
	* @var	resource
	*/
	var $ch = null;

	/**
	* vB_vURL object
	*
	* @var	object
	*/
	var $vurl = null;

	/**
	* Filepointer to the temporary file
	*
	* @var	resource
	*/
	var $fp = null;

	/**
	* Length of the current response
	*
	* @var	integer
	*/
	var $response_length = 0;

	/**
	* Private variable when we request headers. Values are one of VURL_STATE_* constants.
	*
	* @var	int
	*/
	var $__finished_headers = VURL_STATE_HEADERS;

	/**
	* If the current result is when the max limit is reached
	*
	* @var	integer
	*/
	var $max_limit_reached = false;

	/**
	* Constructor
	*
	* @param	object	Instance of a vB_vURL Object
	*/
	public function __construct(&$vurl)
	{
		if (!is_a($vurl, 'vB_vURL'))
		{
			throw new Exception('Direct Instantiation of ' . __CLASS__ . ' prohibited.');
		}
		$this->vurl =& $vurl;
	}

	/**
	* Callback for handling headers
	*
	* @param	resource	cURL object
	* @param	string		Request
	*
	* @return	integer		length of the request
	*/
	public function curl_callback_header(&$ch, $string)
	{
		if (trim($string) !== '')
		{
			$this->response_header .= $string;
		}
		return strlen($string);
	}

	/**
	* Callback for handling the request body
	*
	* @param	resource	cURL object
	* @param	string		Request
	*
	* @return	integer		length of the request
	*/
	public function curl_callback_response(&$ch, $response)
	{
		$chunk_length = strlen($response);

		/* We receive both headers + body */
		if ($this->vurl->bitoptions & VURL_HEADER)
		{
			if ($this->__finished_headers != VURL_STATE_BODY)
			{
				if ($this->vurl->bitoptions & VURL_FOLLOWLOCATION AND preg_match('#(?<=\r\n|^)Location:#i', $response))
				{
					$this->__finished_headers = VURL_STATE_LOCATION;
				}

				if ($response === "\r\n")
				{
					if ($this->__finished_headers == VURL_STATE_LOCATION)
					{
						// found a location -- still following it; reset the headers so they only match the new request
						$this->response_header = '';
						$this->__finished_headers = VURL_STATE_HEADERS;
					}
					else
					{
						// no location -- we're done
						$this->__finished_headers = VURL_STATE_BODY;
					}
				}

				return $chunk_length;
			}
		}

		// no filepointer and we're using or about to use more than 100k
		if (!$this->fp AND $this->response_length + $chunk_length >= 1024*100)
		{
			if ($this->fp = @fopen($this->vurl->tmpfile, 'wb'))
			{
				fwrite($this->fp, $this->response_text);
				unset($this->response_text);
			}
		}

		if ($this->fp AND $response)
		{
			fwrite($this->fp, $response);
		}
		else
		{
			$this->response_text .= $response;

		}

		$this->response_length += $chunk_length;

		if (!empty($this->vurl->options[VURL_MAXSIZE]) AND $this->response_length > $this->vurl->options[VURL_MAXSIZE])
		{
			$this->max_limit_reached = true;
			$this->vurl->set_error(VURL_ERROR_MAXSIZE);
			return false;
		}

		return $chunk_length;
	}

	/**
	* Clears all previous request info
	*/
	public function reset()
	{
		$this->response_text = '';
		$this->response_header = '';
		$this->response_length = 0;
		$this->__finished_headers = VURL_STATE_HEADERS;
		$this->max_limit_reached = false;
		$this->closeTempFile();
	}

	/**
	* Performs fetching of the file if possible
	*
	* @return	integer		Returns one of two constants, VURL_NEXT or VURL_HANDLED
	*/
	public function exec()
	{
		$urlinfo = @vB_String::parseUrl($this->vurl->options[VURL_URL]);

		if(!$this->validateUrl($urlinfo))
		{
			return VURL_NEXT;
		}

		if (!function_exists('curl_init') OR ($this->ch = curl_init()) === false)
		{
			return VURL_NEXT;
		}


		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->vurl->options[VURL_TIMEOUT]);
		if (!empty($this->vurl->options[VURL_CUSTOMREQUEST]))
		{
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->vurl->options[VURL_CUSTOMREQUEST]);
		}
		else if ($this->vurl->bitoptions & VURL_POST)
		{
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->vurl->options[VURL_POSTFIELDS]);
		}
		else
		{
			curl_setopt($this->ch, CURLOPT_POST, 0);
		}
		curl_setopt($this->ch, CURLOPT_HEADER, ($this->vurl->bitoptions & VURL_HEADER) ? 1 : 0);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->vurl->options[VURL_HTTPHEADER]);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, ($this->vurl->bitoptions & VURL_RETURNTRANSFER) ? 1 : 0);
		if ($this->vurl->bitoptions & VURL_NOBODY)
		{
			curl_setopt($this->ch, CURLOPT_NOBODY, 1);
		}

		//never use CURLOPT_FOLLOWLOCATION -- we need to make sure we are as careful with the
		//urls returned from the server as we are about the urls we initially load.
		//we'll loop internally up to the recommended tries.
		$redirect_tries = 1;

		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
		if ($this->vurl->bitoptions & VURL_FOLLOWLOCATION)
		{
			$redirect_tries = $this->vurl->options[VURL_MAXREDIRS];
		}

		//sanity check to avoid an infinite loop
		if ($redirect_tries < 1)
		{
			$redirect_tries = 1;
		}

		if ($this->vurl->options[VURL_ENCODING])
		{
			// this will work on versions of cURL after 7.10, though was broken on PHP 4.3.6/Win32
			@curl_setopt($this->ch, CURLOPT_ENCODING, $this->vurl->options[VURL_ENCODING]);
		}

		curl_setopt($this->ch, CURLOPT_WRITEFUNCTION, array(&$this, 'curl_callback_response'));
		curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array(&$this, 'curl_callback_header'));

		if (!($this->vurl->bitoptions & VURL_VALIDSSLONLY))
		{
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		}

		$url = $this->vurl->options[VURL_URL];

		$redirectCodes = array(301, 302, 307, 308);
		for ($i = $redirect_tries; $i > 0; $i--)
		{
			$isHttps = ($urlinfo['scheme'] == 'https');
			if ($isHttps)
			{
				// curl_version crashes if no zlib support in cURL (php <= 5.2.5)
				$curlinfo = curl_version();
				if (empty($curlinfo['ssl_version']))
				{
					curl_close($this->ch);
					return VURL_NEXT;
				}
			}

			$result = $this->execCurl($url, $isHttps);

			//if we don't have another iteration of the loop to go, skip the effort here.
			if (($i > 1) AND in_array(curl_getinfo($this->ch, CURLINFO_HTTP_CODE), $redirectCodes))
			{
				$url = curl_getinfo($this->ch, CURLINFO_REDIRECT_URL);
				$urlinfo = @vB_String::parseUrl($url);

				if(!$this->validateUrl($urlinfo))
				{
					$this->closeTempFile();
					return VURL_NEXT;
				}
			}
		}

		//if we are following redirects and still have a redirect code, its because we hit our limit without finding a real page
		//we want the fallback code to mimic the behavior of curl in this case
		if (($this->vurl->bitoptions & VURL_FOLLOWLOCATION) && in_array(curl_getinfo($this->ch, CURLINFO_HTTP_CODE), $redirectCodes))
		{
			$this->closeTempFile();
			return VURL_NEXT;
		}

		//close the connection and clean up the file.
		curl_close($this->ch);
		$this->closeTempFile();

		if ($result !== false OR (!$this->vurl->options[VURL_DIEONMAXSIZE] AND $this->max_limit_reached))
		{
			return VURL_HANDLED;
		}

		return VURL_NEXT;
	}


	private function closeTempFile()
	{
		if ($this->fp)
		{
			fclose($this->fp);
			$this->fp = null;
		}
	}

	/**
	 *	Actually load the url from the interweb
	 *	@param string $url
	 *	@params boolean $isHttps
	 *
	 *	@return string|false The result of curl_exec
	 */
	private function execCurl($url, $isHttps)
	{
		$this->reset();
		curl_setopt($this->ch, CURLOPT_URL, $url);
		$result = curl_exec($this->ch);

		if ($isHttps AND $result === false AND curl_errno($this->ch) == '60') ## CURLE_SSL_CACERT problem with the CA cert (path? access rights?)
		{
			curl_setopt($this->ch, CURLOPT_CAINFO, DIR . '/includes/paymentapi/ca-bundle.crt');
			$result = curl_exec($this->ch);
		}


		return $result;
	}


	/**
	 *	Determine if the url is safe to load
	 *
	 *	@param $urlinfo -- The parsed url info from vB_String::parseUrl -- scheme, port, host
	 * 	@return boolean
	 */
	private function validateUrl($urlinfo)
	{
		// VBV-11823, only allow http/https schemes
		if (!isset($urlinfo['scheme']) OR !in_array(strtolower($urlinfo['scheme']), array('http', 'https')))
		{
			return false;
		}

		// VBV-11823, do not allow localhost and 127.0.0.0/8 range by default
		if (!isset($urlinfo['host']) OR preg_match('#localhost|127\.(\d)+\.(\d)+\.(\d)+#i', $urlinfo['host']))
		{
			return false;
		}

		if (empty($urlinfo['port']))
		{
			if ($urlinfo['scheme'] == 'https')
			{
				$urlinfo['port'] = 443;
			}
			else
			{
				$urlinfo['port'] = 80;
			}
		}

		// VBV-11823, restrict detination ports to 80 and 443 by default
		// allow the admin to override the allowed ports in config.php (in case they have a proxy server they need to go to).
		$config = vB::getConfig();
		$allowedPorts = isset($config['Misc']['uploadallowedports']) ? $config['Misc']['uploadallowedports'] : array();
		if (!is_array($allowedPorts))
		{
			$allowedPorts = array(80, 443, $allowedPorts);
		}
		else
		{
			$allowedPorts = array_merge(array(80, 443), $allowedPorts);
		}

		if (!in_array($urlinfo['port'], $allowedPorts))
		{
			return false;
		}

		return true;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89762 $
|| #######################################################################
\*=========================================================================*/
