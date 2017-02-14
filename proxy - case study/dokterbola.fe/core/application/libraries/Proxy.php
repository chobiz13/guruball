<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Proxy Library
 *
 * This class enables to make call to internal and external site with general HTTP request by opening a socket connection to the remote host
 *
 * @package     Proxy Library
 * @category    Libraries
 * @version     1.0.7
 * @author      Taufan Aditya A.K.A Toopay
 * @license     BSD
 */
 
class Proxy { 

	protected $_CI;

	protected $_base_url;

	protected $_domain;

	protected $_path;

	protected $_old_host;

	protected $_combine_host = FALSE;

	private $_user_agent = 'CIProxyLibrary/1.0.7';

	private $_useproxy 	 = FALSE;

	private $_proxy_host = '';

	private $_proxy_port = '';

	private $_proxy_user = '';

	private $_proxy_pass = '';

	private $_cache 	 = FALSE;

	private $_onrequest  = FALSE;

	private $_delay 	 = 1;

	private $_body 		 = array();

	private $_cookies 	 = array();

	private $_addressbar = '';
	
	private $_http_header_params  = array();

	private $_http_body_params    = array();

	private $_http_content_length = 0;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_CI =& get_instance();

		$this->set_flag(true);
		
		// Check if zlib is available 
		if (function_exists('gzopen')) $this->_cache = true;
	} 

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		// Remove temporary file for gzip encoding 
		if (file_exists('tmp.gz')) unlink('tmp.gz');
	} 

	/**
	 * Head
	 *
	 * Outputs an array of HTTP Response Header from target site
	 *
	 * @access	public
	 * @param	string	The url to call
	 * @param	boolean	Render features option(optional)
	 * @return	array	HTTP Response
	 */
	public function head($url = '', $render = FALSE)
	{ 
		$res = $this->_execute($url, 'HEAD');

		if ($render==TRUE)
		{
			var_dump($res);
		}
		else
		{
			return $res; 
		}
	} 
	
	/**
	 * Crawl
	 *
	 * Outputs an informative array of web content from target site, like searching engine's spider
	 *
	 * @access	public
	 * @param	string	The url to call
	 * @param	boolean	Render features option(optional)
	 * @return	array	Web content information
	 */
	public function crawl($url = '', $render = FALSE)
	{ 
		$res = $this->_parse_info($url,$this->_execute($url, 'GET'));

		if ($render==TRUE)
		{
			var_dump($res);
		}
		else
		{
			return $res; 
		}
	} 
	
	/**
	 * Google Geocode
	 *
	 * The Google Geocoding API provides a direct way to access a geocoder via an HTTP request
	 *
	 * @access	public
	 * @param	string	The address or latlng
	 * @param	string	The output format (JSON/XML)
	 * @param	boolean	The sensor
	 * @return	
	 */
	public function geocode($address, $output = 'json', $sensor = FALSE)
	{ 
		if (is_array($address))
		{
			$ltln = array_values($address);

			$lat = isset($ltln[0]) ? $ltln[0] : '0';

			$lng = isset($ltln[1]) ? $ltln[1] : '0';

			$address = trim($lat).','.trim($lng);

			$geotype = 'latlng';
		}
		else 
		{
			$address = str_replace(' ', '%20', $address);

			$geotype = 'address';
		}
		
		$output = strtolower($output) == 'xml' ? 'xml' : 'json';

		$sensor = $sensor == TRUE ? 'true' : 'false';
			
		$geolookup = 'http://maps.googleapis.com/maps/api/geocode/'.$output.'?'.$geotype.'='.$address.'&sensor='.$sensor;

		return $this->site($geolookup);
	} 
	
	/**
	 * Site
	 *
	 * Outputs a html from target site
	 *
	 * @access	public
	 * @param	string	The url to call
	 * @param	boolean	Render features option(optional)
	 * @return	string	The content
	 */
	public function site($url = '', $render = FALSE)
	{ 
		$res = $this->_execute($url, 'GET');

		if ($res == FALSE AND $this->_combine_host == TRUE) $res = $this->_execute($this->_old_host, 'GET');

		if ($render==TRUE)
		{
			echo $this->_maintain_html($res,$this->_base_url);
		}
		else
		{
			return $res; 
		}
	} 
	
	/**
	 * Controller
	 *
	 * Outputs a html from internal site, pointing to controller name
	 *
	 * @access	public
	 * @param	string	The controller to call (optional you can define/passes the function and param too)
	 * @param	boolean	Render features option(optional)
	 * @return	string
	 */
	public function controller($class = '', $render = FALSE)
	{
		$this->_CI->load->helper('url');

		$url = site_url($class);

		return $this->site($url,$render);
	}
	
	/**
	 * HTTP
	 *
	 * Outputs a http response
	 *
	 * @access	public
	 * @param	string	The METHOD (GET, POST, PUT, DELETE)
	 * @param	string	The url to call
	 * @param	array	The Body Parameter (for GET, POST, PUT, DELETE)
	 * @param	array	The Head Parameter (like basic auth, api key, etc)
	 * @return	string	The content
	 */
	public function http($method = '', $url = '', $body_params = array(), $head_params = array())
	{ 
		$http_method = '';

		$http_head_params = array();

		$http_body_params = array();
		
		// Validate HTTP methods
		switch (strtoupper($method))
		{
			case 'GET' : 

				$http_method = 'GET';

				break;

			case 'POST' :

				$http_method = 'POST';

				break;

			case 'PUT' :

				$http_method = 'PUT';

				break;

			case 'DELETE' :

				$http_method = 'DELETE';

				break;

			default :

				show_error('Unable to determine HTTP method. Use \'GET\' \'POST\' \'PUT\' or \'DELETE\'.');
		}
		
		// Validate HTTP body parameter
		if ( ! empty($body_params))
		{
			if ($http_method == 'GET')
			{
				// Put that directly in url
				$i = 0;
				foreach ($body_params as $param_key => $param_val) 
				{
					if ($i == 0)
					{
						$url .= '?' . $param_key . '=' . $param_val;
					}
					else 
					{
						$url .= '&' . $param_key . '=' . $param_val;
					}
					
					$i++;
				}
				
			}
			else 
			{
				$http_body_params = $body_params;
			}
		}
		
		// Validate HTTP header parameter
		if( ! empty($head_params)) $http_head_params = $head_params;
		
		// Shoot it!
		$res = $this->_execute($url, $http_method, 10, $http_body_params, $http_head_params);
		
		// Failed? Some chaining procedure...
		if($res == FALSE AND $this->_combine_host == TRUE) $res = $this->_execute($this->_old_host, $http_method, 10, $http_body_params, $http_head_params);
		
		// Here you go...
		return $res; 
	} 
	
	/**
	 * Set HTTP
	 *
	 * Setting HTTP parameter
	 *
	 * @access	public
	 * @param	array	The Arguments
	 */
	public function set_http($args = array())
	{ 
		if (array_key_exists('head', $args)) 
		{
	    	$this->_http_header_params = $args['head'];
		}
		elseif(array_key_exists('body', $args))
		{
			$this->_http_body_params = $args['body'];
		}
		else 
		{
			show_error('Unable to determine HTTP parameter. Use \'head\' or \'body\'.');
		}
	}
	
	/**
	 * Set Flag
	 *
	 * HTTP request flag
	 *
	 * @access	public
	 * @param	boolean	Flag for HTTP process
	 * @return	
	 */
	public function set_flag($bool)
	{
		$this->_onrequest = $bool;

		if (!$this->_onrequest) return;

		log_message('debug', "Proxy Class Initialized");
	} 
	
	/**
	 * Set Proxy
	 *
	 * Setting up a proxy configuration
	 *
	 * @access	public
	 * @param	string	Host
	 * @param	string	Port
	 * @param	string	User
	 * @param	string	Password
	 * @return  void	Proxy connection flag
	 */
	public function set_proxy($host, $port, $user = '', $pass = '')
	{
		$this->_proxy_host = $host;

		$this->_proxy_port = $port;

		$this->_proxy_user = $user;

		$this->_proxy_pass = $pass;

		$this->_useproxy   = TRUE;

		return $this;
	} 

	/**
	 * Set Delay
	 *
	 * Setting up a delay between HTTP request
	 *
	 * @access	public
	 * @param	int		Time for delay
	 * @return			Delay time flag
	 */
	public function set_delay($sec)
	{ 
		if (!preg_match('/^\d+$/', $sec) || $sec <= 0) 
		{
			$this->_delay = 1;
		} 
		else 
		{ 
			$this->_delay = $sec;
		}
	} 

	/**
	 * Set User Agent
	 *
	 * Setting up a User Agent property
	 *
	 * @access	public
	 * @param	string	User agent description
	 * @return			User agent flag
	 */
	public function set_useragent($ua)
	{
		$this->_user_agent = $ua;
	}
	
	/**
	 * Execute
	 *
	 * Make a HTTP request
	 *
	 * @access	protected
	 * @param	string	The URL
	 * @param	string	The METHOD (GET, POST, PUT, DELETE)
	 * @param	int		The max redirection number
	 * @param	array	The Body Parameter (for POST, PUT, DELETE parameter)
	 * @param	array	The Head Parameter (for API calls, authentification, and other boring stuff)
	 * @return			HTTP Responses
	 */
	protected function _execute($url = '', $method = '', $maxredir = 10, $body_params = array(), $head_params = array())
	{
		// Get the base URL
		$this->_check_host($url);
		
		// Get the valid URL schema
		$url = $this->_rel2abs($url, $this->_base_url);
		
		if (substr($url, 0, 4) != 'http') show_error('Invalid URL!');

		// Save this for chaining HTTP request
		$this->_addressbar = $url;

		// Star build request
		$reqbody = $this->_get_reqbody($method, $body_params);

		$reqhead = $this->_get_reqhead($url, $method, $head_params);
		

		// Log request flag
		if ($this->_onrequest) $this->_log_httpprocess($url, $reqhead, $reqbody);

		// Parse URL and convert to local variables:
		$parts = parse_url($url);
		if (!$parts) 
		{ 
			show_error('Invalid URL!');
		} 
		else 
		{ 
			foreach ($parts as $key=>$val) $$key = $val;

			if ($key = 'host') $this->_old_host = $val;
		} 
		
		// Uh-Oh, Lets open this sockect!
		if ($this->_useproxy) 
		{
			$fp = @fsockopen($this->_proxy_host, $this->_proxy_port, $errno, $errstr);
		} 
		else  
		{
			$fp = @fsockopen(($scheme=='https' ? "ssl://$host" : $host), $scheme == 'https' ? 443 : 80, $errno, $errstr);
		}
		
		// Is everythings fine?
		if (!$fp) 
		{
			if ($this->_combine_host == FALSE)
			{
				$this->_combine_host = TRUE;

				$this->_old_host = substr($this->_old_host, 2, strlen($this->_old_host)-3);

				return FALSE;
			}
			else 
			{
				show_error($errstr.' [ERROR_CODE] = '.$errno);
			}
		}
		
		// Send request & read response
		@fputs($fp, $reqhead.$reqbody);

		for($res=''; !feof($fp) ; $res.=@fgets($fp, 4096)) {} 

		fclose($fp);

		// Set delay between requests.
		sleep($this->_delay);

		// Get response header & body 
		list($reshead, $resbody) = explode("\r\n\r\n", $res, 2);

		// Convert header to associative array
		$head = $this->_parse_head($reshead);

		// Uh-Oh, its HEAD! return now!
		if ($method == 'HEAD')
		{ 
			if ($this->_onrequest) $this->_log_httpprocess($url, $reshead, null);

			return $head;
		} 

		// Cooking for a cookie
		if (!empty($head['Set-Cookie'])) $this->_save_cookies($head['Set-Cookie'], $url);
					
		// Referer
		if ($head['Status']['Code'] == 200) $this->_referer = $url;
			
		// Transfer-encoding: Chunked! */
		if (isset($head['Transfer-Encoding']) AND $head['Transfer-Encoding'] == 'chunked') 
		{
			$body = $this->_join_chunks($resbody);
		} 
		else 
		{
			$body = $resbody;
		} 

		// Content-encoding: gzip
		if (isset($head['Content-Encoding']) AND $head['Content-Encoding'] == 'gzip') 
		{
			@file_put_contents('tmp.gz', $body);

			$fp = @gzopen('tmp.gz', 'r');

			for($body = ''; !@gzeof($fp); $body.=@gzgets($fp, 4096)) {}

			@gzclose($fp);
		} 

		// Log request flag...again
		if ($this->_onrequest) $this->_log_httpprocess($url, $reshead, $body);
		
		array_unshift($this->_body, $body);

		if (isset($head['Location']) AND $maxredir > 0) $this->_execute($this->_rel2abs($head['Location'], $url), 'GET', $maxredir--);
		
		// Parse meta tags
		$meta = $this->_parse_metatags($body);

		// Redirects: <meta http-equiv=refresh...bla..bla>
		if (isset($meta['http-equiv']['refresh']) AND $maxredir > 0) 
		{ 
			list($delay, $loc) = explode(';', $meta['http-equiv']['refresh'], 2);

			$loc = substr(trim($loc), 4);

			if (!empty($loc) AND $loc != $url)

				$this->_execute($this->_rel2abs($loc, $url), 'GET', $maxredir--);
		}

		// Get body and clear cache
		$body = $this->_body[0];

		for ($i = 1; $i < count($this->_body); $i++) 
		{
			unset($this->_body[$i]);
		}
		
		// And we done!
		return $body;
	} 

	/**
	 * Get Request Header
	 *
	 * Build a HTTP request header
	 *
	 * @access	protected
	 * @param	string	The URL
	 * @param	string	The METHOD
	 * @param	array	The head parameter
	 * @return			HTTP Request Header
	 */
	protected function _get_reqhead($url, $method, $params = array())
	{
		// Parse URL elements to local variables:
		$parts = parse_url($url);

		foreach ($parts as $key=>$val) $$key = $val;

		// Setup path
		$path = empty($path)  ? '/' : $path 

			  .(empty($query) ? ''  : "?$query");
			
		// The request header
		if ($this->_useproxy) 
		{
			$head = "$method $url HTTP/1.1\r\nHost: $this->_proxy_host\r\n";
		} 
		else  
		{
			$head = "$method $path HTTP/1.1\r\nHost: $host\r\n";
		}

		// Cookies 
		$head .= $this->_get_cookies($url);
		
		// Is there some funky header params passed?
		$head_params =  ! empty($params) ? $params : $this->_http_header_params;
		
		// Hey, theres something here...
		if ( ! empty($head_params))
		{
			$keys = array();

			foreach ($head_params as $params_key => $param_val) 
			{
				// If there is an authorization key found, we should take it with care
				if ($params_key == 'auth')
				{
					$rawtoken = str_replace(' ','',trim($param_val));

					$keys     = explode(':', $rawtoken, 2);

					$user     = $keys[0];

					$pass     = $keys[1];

					$auth     = 'Basic';

					$token    = base64_encode("$user:$pass");
				}
				else 
				{
					$head .= "$params_key: $param_val\r\n";
				}
				
			}
		}
		
		// Basic authentication 
		if (!$this->_useproxy AND !empty($user) AND !empty($pass)) $head .= "Authorization: $auth $token\r\n";
		
		// Basic authentication for proxy 
		if ($this->_useproxy AND !empty($this->_proxy_user) AND !empty($this->_proxy_pass)) $head .= "Authorization: Basic ". base64_encode("$this->_proxy_user:$this->_proxy_pass")."\r\n";
		
		// Internal cache (gzip)
		if ($this->_cache) $head .= "Accept-Encoding: gzip\r\n";
		
		// Lets make it like real browsers, shall we?
		if (!empty($this->_user_agent)) $head .= "User-Agent: $this->_user_agent\r\n";
		
		if (!empty($this->_referer)) $head .= "Referer: $this->_referer\r\n";
		
		// POST this then
		if($method == 'POST') $head .= "Content-Type: application/x-www-form-urlencoded\r\n";
		
		// Is there any body content?
		if($this->_http_content_length > 0) $head .= "Content-Length: $this->_http_content_length\r\n";
		
		// No pipelining yet
		$head .= "Connection: Close\r\n\r\n";
		
		// Request header is ready!
		return $head;
	} 
	
	/**
	 * Get Request Body
	 *
	 * Build a HTTP request body
	 *
	 * @access	protected
	 * @param	string	The METHOD
	 * @param	array	The body params
	 * @return			HTTP Request Header
	 */
	protected function _get_reqbody($method, $params)
	{
		// Default is blank
		$body = '';
		
		// Is there some body params passed?
		$body_params =  ! empty($params) ? $params : $this->_http_body_params;
		
		// Just make sure we didnt do something uncool
		if ($method != 'GET')
		{
			$i = 0;

			foreach ($body_params as $param_key => $param_val) 
			{
				if ($i == 0)
				{
					$body .= $param_key . '=' . $param_val;
				}
				else 
				{
					$body .= '&' . $param_key . '=' . $param_val;
				}
				
				$i++;
			}
		}
		// Flag the content-length, for header use
		$this->_http_content_length = strlen($body);
		
		// Request body is ready!
		return $body;
	} 

	/**
	 * Join Chunks
	 *
	 * Read chunked pages
	 *
	 * @access	protected
	 * @param	string	The string to join
	 * @return	string	The joined chunks
	 */
	protected function _join_chunks($str)
	{
		$CRLF = "\r\n";

		for ($tmp = $str, $res = ''; !empty($tmp); $tmp = trim($tmp)) { 

			if (($pos = strpos($tmp, $CRLF)) === FALSE) return $str;

			$len = hexdec(substr($tmp, 0, $pos));

			$res.= substr($tmp, $pos + strlen($CRLF), $len);

			$tmp = substr($tmp, $pos + strlen($CRLF) + $len);

		} 

		return $res;
	} 

	/**
	 * Save Cookies
	 *
	 * Save cookies from server
	 *
	 * @access	protected
	 * @param	array	The cookies part
	 * @param	string	The URL
	 * @return	
	 */
	protected function _save_cookies($set_cookies, $url) 
	{ 
		foreach ($set_cookies as $str) 
		{
			$parts = explode(';', $str);

			foreach ($parts as $part) 
			{ 
				$arrpart = explode('=', trim($part), 2);

				$key = $arrpart[0];

				$val = isset($arrpart[1]) ? $arrpart[1] : '';

				$k = strtolower($key);

				switch($k)
				{
					case 'secure':

						$secure = TRUE;

						break;

					case 'httponly':

						$httponly = TRUE;

						break;

					case 'domain':

						$domain = $val;

						break;

					case 'path':

						$path = $val;

						break;

					case 'expires':

						$expires = $val;

						break;

					default:

						$name  = $key;

						$value = $val;

						break;
				}
			} 

			// Cookie's domain 
			if (empty($domain)) $domain = parse_url($url, PHP_URL_HOST);

			// Cookie's path 	
			if (empty($path)) 
			{
				$path = parse_url($url, PHP_URL_PATH);

				$path = preg_replace('#/[^/]*$#', '', $path);

				$path = empty($path) ? '/' : $path;
			} 

			// Cookie's expire time 
			if (!empty($expires)) $expires = strtotime($expires);
							
			// Setup cookie ID, a simple trick to add/update existing cookie and cleanup local variables later 
			$id = md5("$domain;$path;$name");

			// Add/update cookie
			$this->_cookies[$id] = array(

				'domain'   => substr_count($domain, '.') == 1 ? ".$domain" : $domain, 

				'path'     => $path, 

				'expires'  => isset($expires) ? $expires : date('d/m/Y h:i:s',mktime(0, 0, 0, date('m')  , date('d')+1, date('Y'))), 

				'name'     => $name, 

				'value'    => $value, 

				'secure'   => isset($secure) ? $secure : FALSE, 

				'httponly' => isset($httponly) ? $httponly : FALSE
			);

			// Cleanup local variables */
			foreach ($this->_cookies[$id] as $key=>$val) unset($$key);
		} 

		return TRUE;
	} 

	/**
	 * Get Cookies
	 *
	 * Get cookies for URL
	 *
	 * @access	protected
	 * @param	string	The URL
	 * @return	string	string for HTTP header
	 */
	protected function _get_cookies($url)
	{
		$tmp = array();

		$res = array();

		// Remove expired cookies first 
		foreach ($this->_cookies as $id=>$cookie) 
		{
			if (empty($cookie['expires']) || $cookie['expires'] >= time()) $tmp[$id] = $cookie;
		}

		// Cookies ready
		$this->_cookies = $tmp;

		// Parse URL to local variables:
		$parts = parse_url($url);

		foreach ($parts as $key=>$val) $$key = $val;

		if (empty($path)) $path = '/';

		// Get all cookies for this domain and path
		foreach ($this->_cookies as $cookie) 
		{
			$d = substr($host, -1 * strlen($cookie['domain']));

			$p = substr($path, 0, strlen($cookie['path']));
			
			if (($d == $cookie['domain'] || ".$d" == $cookie['domain']) AND $p == $cookie['path']) 
			{ 
				if ($cookie['secure'] == true  AND $scheme == 'http') continue;

				$res[] = $cookie['name'].'='.$cookie['value'];
			}
		} 

		// Return the string for HTTP header
		return (empty($res) ? '' : 'Cookie: '.implode('; ', $res)."\r\n");
	} 
	
	/**
	 * Maintain HTML
	 *
	 * Outputs a valid html tag from target site, maintain valid image, anchor and form
	 *
	 * @access	protected
	 * @param	string	The html
	 * @param	string	The base url
	 * @return	string
	 */
	protected function _maintain_html($html = '',$base_url = '')
	{
		// First lets process the head
		if (($pos = strpos(strtolower($html), '</head>')) === false) 
		{ 
			$head = $html; 
		} 
		else 
		{
			$poshead = strpos(strtolower($html), '<head>');

			$head = substr($html, ($poshead+1), $pos);
		} 

		$old_head = $head;
		
		// Maintain CSS Path
		preg_match_all("/<link (.+)\>/siU", $head, $m);

		foreach ($m[1] as $csstag)
		{
			preg_match("/href=\"(.+)\"/siU", $csstag, $m);

			if (isset($m[1]) AND $m[1] != '/' AND $m[1] != $base_url)
			{
				$old_css[] = $m[1];

				$css[] = $this->_rel2abs($m[1], $base_url);
			}
			
		}

		$head = (isset($old_css) AND ! is_null($old_css) AND ! is_null($css)) ? str_replace(array_unique($old_css), array_unique($css), $head) : $head;	
		
		// Maintain Javascript Path
		preg_match_all("/<script (.+)\>/siU", $head, $m);

		foreach ($m[1] as $jstag)
		{
			preg_match("/src=\"(.+)\"/siU", $jstag, $m);

			if (isset($m[1]) AND $m[1] != '/' AND substr($m[1], 0, 4) != 'http' AND $m[1] != $base_url)
			{
				$old_js[] = $m[1];

				$js[] = $this->_rel2abs($m[1], $base_url);
			}
			
		}
		$head = (isset($old_js) AND ! is_null($old_js) AND ! is_null($js)) ? str_replace(array_unique($old_js), array_unique($js), $head) : $head;	
		
		$html = str_replace($old_head, $head, $html);
		
		// Now we process the body
		if (($pos = strpos(strtolower($html), '</body>')) === false) 
		{ 
			$body = $html; 
		} 
		else 
		{
			$poshead = strpos(strtolower($html), '</head>');

			$body = substr($html, ($poshead+1), ($pos-$poshead));
		} 

		$old_body = $body;
		
		// Maintain Image Path
		preg_match_all("/<img (.+)\>/siU", $body, $m);

		foreach ($m[1] as $imgtag)
		{
			preg_match("/src=\"(.+)\"/siU", $imgtag, $m);

			if (isset($m[1]) AND $m[1] != '/' AND $m[1] != $base_url)
			{
				$old_src[] = $m[1];

				$src[] = $this->_rel2abs($m[1], $base_url);
			}
			
		}
		$body = (isset($old_src) AND ! is_null($old_src) AND ! is_null($src)) ? str_replace(array_unique($old_src), array_unique($src), $body) : $body;	
		
		// Maintain Anchor Path
		preg_match_all("/<a (.+)\>/siU", $body, $m);

		foreach ($m[1] as $anchortag)
		{
			preg_match("/href=\"(.+)\"/siU", $anchortag, $m);  

			if (isset($m[1]) AND $m[1] != '/' AND $m[1] != $base_url)
			{
				$old_href[] = 'href="'.$m[1].'"';

				$href[] = 'href="'.$this->_rel2abs($m[1], $base_url).'"';
			}
		}

		$body = (isset($old_href) AND ! is_null($old_href) AND ! is_null($href)) ? 	str_replace(array_unique($old_href), array_unique($href), $body) : $body;	
		
		// Maintain Form Action URL
		preg_match_all("/<form (.+)\>/siU", $body, $m);

		foreach ($m[1] as $actiontag)
		{
			preg_match("/action=\"(.+)\"/siU", $actiontag, $m);  

			if (isset($m[1]) AND $m[1] != '/' AND $m[1] != $base_url)
			{
				$old_action[] = 'action="'.$m[1].'"';

				$action[] = 'action="'.$this->_rel2abs($m[1], $base_url).'"';
			}
		}

		$body = (isset($old_action) AND ! is_null($old_action) AND ! is_null($action)) ? str_replace(array_unique($old_action), array_unique($action), $body) : $body;	
		
		return str_replace($old_body, $body, $html);
	}
	
	/**
	 * Check host
	 *
	 * Outputs a html from internal site, pointing to controller name
	 *
	 * @access	protected
	 * @param	string	The url
	 * @return	
	 */
	protected function _check_host($url = '')
	{
		preg_match('@^(?:http://)?([^/]+)@i', $url, $matches);

		$host = isset($matches[1]) ? $matches[1] : 'undefined';;

		$this->_base_url = 'http://'.$host;

		preg_match('/[^.]+\.[^.]+$/', $host, $matches);

		$this->_domain = isset($matches[0]) ? $matches[0] : NULL;
	}
	
	/**
	 * Relative to Absolute
	 *
	 * Outputs a absolute path from relative path url
	 *
	 * @access	protected
	 * @param	string	The relative path
	 * @param	string	The base url
	 * @return	string
	 */
	protected function _rel2abs($rel, $base)
	{ 
		// Parameters is required
		if (empty($rel) AND empty($base)) return;

		// URL is domain!
		if (strrpos($rel, $this->_domain) !== false) 
		{
			if(strrpos($rel, $base) !== false)
			{
				return $rel;
			}
			else 
			{
				// Some site use 'static' subdomain, check again then...
				return strrpos($rel, 'http') !== false ? $rel : 'http://'.$rel;
			}
		}
				
		$rel = str_replace('&amp;', '&', $rel);

		// Hey, URL is already abolute
		if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
		
		// Handle anchors and query's part
		$c = substr($rel, 0, 1);

		if ($c == '#' || $c == '&') return "$base$rel";

		// And query string...
		if ($c == '?') 
		{
			$pos = strpos($base, '?');

			if ($pos !== FALSE) $base = substr($base, 0, $pos);

			return "$base$rel";
		}

		// Parse URL and convert to local variables:
		$parts = parse_url($base);

		foreach ($parts as $key=>$val) $$key = $val;

		// Remove non-directory part from path
		$this->_path = preg_replace('#/[^/]*$#', '', $this->_path);

		// Set path to '/' if empty
		$this->_path = preg_match('#^/#', $rel) ? '/' : $this->_path;

		// Uh-Oh, dirty absolute URL
		$abs = "$host$this->_path/$rel";

		// Last step
		while($abs = preg_replace(array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'), '/', $abs, -1, $count)) 
			if (!$count) break;

		// Ready to go...
		return "$scheme://$abs";
	} 
	
	/**
	 * Parse Head
	 *
	 * Convert response header to associative array
	 *
	 * @access	protected
	 * @param	string	The head
	 * @return	array	The [fixed] head
	 */
	protected function _parse_head($str)
	{
		$lines = explode("\r\n", $str);

		list($ver, $code, $msg) = explode(' ', array_shift($lines), 3);

		$stat = array('Version' => $ver, 'Code' => $code, 'Message' => $msg);

		$head = array('Status' => $stat);

		foreach ($lines as $line) 
		{ 
			list($key, $val) = explode(':', $line, 2);

			if ($key == 'Set-Cookie') 
			{
				$head['Set-Cookie'][] = trim($val);
			} 
			else 
			{
				$head[$key] = trim($val);
			}
		} 
		
		return $head;
	} 
	
	/**
	 * Parse Metatags
	 *
	 * Convert meta tags to associative array
	 *
	 * @access	protected
	 * @param	string	The html
	 * @return	array	The metatags
	 */
	protected function _parse_metatags($html) 
	{ 
		// Extract to </head> 
		if (($pos = strpos(strtolower($html), '</head>')) === FALSE) 
		{ 
			return array();
		} 
		else 
		{
			$head = substr($html, 0, $pos);
		} 

		// Get page's title
		preg_match("/<title>(.+)<\/title>/siU", $head, $m);

		$meta = array('title' => isset($m[1]) ? $m[1] : '');

		// Get all <meta...> 
		preg_match_all('/<meta\s+[^>]*name\s*=\s*[\'"][^>]+>/siU', $head, $m);

		foreach ($m[0] as $row) 
		{ 
			preg_match('/name\s*=\s*[\'"](.+)[\'"]/siU', $row, $key);

			preg_match('/content\s*=\s *[\'"](.+)[\'"]/siU', $row, $val);

			if (!empty($key[1]) AND !empty($val[1]))

				$meta[$key[1]] = $val[1];
		} 

		// Get <meta http-equiv=refresh...>
		preg_match('/<meta[^>]+http-equiv\s*=\s*[\'"]?refresh[\'"]?[^>]+content\s*=\s*[\'"](.+)[\'"][^>]*>/siU', $head, $m);

		if (!empty($m[1])) 
		{
			$meta['http-equiv']['refresh'] = preg_replace('/&#0?39;/', '', $m[1]);
		} 

		return $meta;
	} 
	
	/**
	 * Parse Info
	 *
	 * Convert web content to informative associative array
	 *
	 * @access	protected
	 * @param	string	The url
	 * @param	string	The html
	 * @return	array 	The web content info
	 */
	protected function _parse_info($url,$html)
	{
		// Get the title 
		preg_match('/<title>(.+)<\/title>/siU', $html, $matches);

		$title = isset($matches[1]) ? $matches[1] : '[No title found]';
 
		// Get the keywords
		$pat_keywords = "<meta\s+name=['\"]??keywords['\"]??\s+content=['\"]??(.+)['\"]??\s*\/?>";

		preg_match("/$pat_keywords/siU", $html, $matches);

		$keywords = isset($matches[1]) ? $matches[1] : '[No keywords found]';
 
		// Get the description
		$pat_description = "<meta\s+name=['\"]??description['\"]??\s+content=['\"]??(.+)['\"]??\s*\/?>";

		preg_match("/$pat_description/siU", $html, $matches);

		$description = isset($matches[1]) ? $matches[1] : '[No description found]';
 
		// Get all links
		$pat_links = "<a\s[^>]*href\s*=\s*(['\"]??)([^'\">]*?)\\1[^>]*>(.*)<\/a>";

		preg_match_all("/$pat_links/siU", $html, $matches);

		$links = $matches[2];
 
		$res = array(

					'url'         => $url,

					'md5'         => md5($html),

					'title'       => $title,

					'keywords'    => $keywords,

					'description' => $description,

					'links'       => array_unique($links)

				);    
   
    	return($res);
	}

	/**
	 * Log HTTP Process
	 *
	 * Log HTTP request/response
	 *
	 * @access	protected
	 * @param	string	The html
	 * @param	string	The head
	 * @param	string	The body
	 * @return
	 */
	protected function _log_httpprocess($url, $head, $body)
	{ 
		// Log all HTTP streaming process
		$head = "\r\n$url\r\n\r\n" . trim($head);

		log_message('debug', "Proxy Flagged Log :".str_repeat('-', 20)."\nHeader : ".trim($head)."\r\n\r\nContent : $body\r\n\r\n");
		
		return;
	} 
}
/* End of file Proxy.php */
/* Location: ./application/libraries/Proxy.php */