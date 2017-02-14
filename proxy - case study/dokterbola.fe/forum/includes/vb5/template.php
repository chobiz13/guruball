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

/**
 * This class is a simplified version of the one implemented in includes/class_core.php
 */
class vB5_Template
{
	const WIDGET_ERROR_TEMPLATE = 'widget_error';

	/**
	 * Name of the template to render
	 *
	 * @var	string
	 */
	protected $template = '';

	/**
	 * Array of registered variables.
	 * @see vB5_Template::preRegister()
	 *
	 * @var	array
	 */
	protected $registered = array();

	/**
	 * Array of global registered variables.
	 * Global registered variables are available in main templates and child templates
	 * included with {vb:template}
	 *
	 * @var array
	 */
	protected static $globalRegistered = array();

	/**
	 * List of templates rendered (for debugging output)
	 *
	 * @var array
	 */
	protected static $renderedTemplates = array();
	protected static $renderedTemplateNames = array();
	protected static $renderedTemplatesStack = array();
	/*
	 * jQuery URL
	 */
	protected static $jQueryUrl = '';
	protected static $jQueryUrlLogin = '';

	/**
	 * Constructor
	 */
	public function __construct($templateName)
	{
		$this->template = $templateName;

		$this->registerDefaultGlobals();
	}

	/**
	 * Register a variable with the template.
	 * Global registered variables are available in main templates and child templates
	 * included with {vb:template}
	 *
	 * @param	string	Name of the variable to be registered
	 * @param	mixed	Value to be registered. This may be a scalar or an array.
	 * @param	bool	Whether to overwrite existing vars
	 * @return	bool	Whether the var was registered
	 */
	public function register($name, $value, $overwrite = true)
	{
		if (!$overwrite AND $this->isRegistered($name))
		{
			return false;
		}

		$this->registered[$name] = $value;

		return true;
	}

	/**
	 * Register a global variable with the template.
	 *
	 * @param	string	Name of the variable to be registered
	 * @param	mixed	Value to be registered. This may be a scalar or an array.
	 * @param	bool	Whether to overwrite existing vars
	 * @return	bool	Whether the var was registered
	 */
	public function registerGlobal($name, $value, $overwrite = true)
	{
		if (!$overwrite AND $this->isGlobalRegistered($name))
		{
			return false;
		}

		self::$globalRegistered[$name] = $value;

		return true;
	}

	/**
	 * Determines if a named variable is registered.
	 *
	 * @param	string	Name of variable to check
	 * @return	bool
	 */
	public function isRegistered($name)
	{
		return isset($this->registered[$name]);
	}

	/**
	 * Determines if a named variable is global registered.
	 *
	 * @param	string	Name of variable to check
	 * @return	bool
	 */
	public function isGlobalRegistered($name)
	{
		return isset(self::$globalRegistered[$name]);
	}

	protected function registerjQuery()
	{
		if (!self::$jQueryUrl)
		{
			// create the path to jQuery depending on the version
			$customjquery_path = vB::getDatastore()->getOption('customjquery_path');
			$remotejquery = vB::getDatastore()->getOption('remotejquery');

			$session = vB::getCurrentSession();

			if ($session)
			{
				$protocol = vB::getRequest()->getVbUrlScheme();
			}
			else  if (!empty($_SERVER['HTTPS'])) //session isn't set.
			{
				$protocol = 'https';
			}
			else
			{
				$protocol = 'http';
			}

			if ($customjquery_path)
			{
				$path = str_replace('{version}', JQUERY_VERSION, $customjquery_path);
				if (!preg_match('#^https?://#si', $customjquery_path))
				{
					$path = $protocol . '://' . $path;
				}
				self::$jQueryUrlLogin = self::$jQueryUrl = $path;
			}
			else if ($remotejquery == 1)
			{	// Google CDN
				self::$jQueryUrlLogin = self::$jQueryUrl = $protocol . '://ajax.googleapis.com/ajax/libs/jquery/' . JQUERY_VERSION . '/jquery.min.js';
			}
			else if ($remotejquery == 2)
			{	// jQuery CDN
				self::$jQueryUrlLogin = self::$jQueryUrl = $protocol . '://code.jquery.com/jquery-' . JQUERY_VERSION . '.min.js';
			}
			else if ($remotejquery == 3)
			{	// Microsoft CDN
				self::$jQueryUrlLogin = self::$jQueryUrl = $protocol . '://ajax.aspnetcdn.com/ajax/jquery/jquery-' . JQUERY_VERSION . '.min.js';
			}
			else
			{
				self::$jQueryUrl =  'js/jquery/jquery-' . JQUERY_VERSION . '.min.js';
				self::$jQueryUrlLogin = 'js/jquery/jquery-' . JQUERY_VERSION . '.min.js';			}
		}

		$this->registerGlobal('jqueryurl', self::$jQueryUrl);
		$this->registerGlobal('jqueryurl_login', self::$jQueryUrlLogin);
		$this->registerGlobal('jqueryversion', JQUERY_VERSION);
	}

	/**
	 * Globally registers the default variables that are globally avialable
	 * to all templates.
	 */
	protected function registerDefaultGlobals()
	{
		static $done = false;

		if (!$done)
		{
			$this->registerGlobal('admincpdir', 'admincp');

			$this->registerjQuery();

			$config = vB5_Config::instance();
			$this->registerGlobal('config', $config, true);

			$user = vB5_User::instance();
			$this->registerGlobal('user', $user, true);

			$baseurl = vB5_Template_Options::instance()->get('options.frontendurl');
			$this->registerGlobal('baseurl', $baseurl, true);

			$baseurl_core = vB5_Template_Options::instance()->get('options.bburl');
			$this->registerGlobal('baseurl_core', $baseurl_core, true);

			$baseurl_login = vB5_Template_Options::instance()->get('options.frontendurl_login');
			
			if (trim($baseurl_login) == '')
			{
				$baseurl_login = vB5_Template_Options::instance()->get('options.frontendurl');
			}
			$this->registerGlobal('baseurl_login', $baseurl_login, true);

			$baseurl_data = vB5_String::parseUrl($baseurl);
			$this->registerGlobal('baseurl_data', $baseurl_data, true);

			if (isset($baseurl_data['path']))
			{
				$baseurl_path = $baseurl_data['path'];
			}
			$baseurl_path = isset($baseurl_path) ? ($baseurl_path . (substr($baseurl_path, -1) != '/' ? '/' : '')) : '/'; //same as cookie path
			$this->registerGlobal('baseurl_path', $baseurl_path, true);

			$cookie_prefix = $config->cookie_prefix;
			$this->registerGlobal('cookie_prefix', $cookie_prefix, true);

			$vboptions = vB5_Template_Options::instance()->getOptions();
			$vboptions = $vboptions['options'];
			$this->registerGlobal('vboptions', $vboptions, true);

			//this assumes that core is in the core directory which is not something we've generally assumed
			//however as noncollapsed mode look unlikely to be as useful as we thought, we'll start making that
			//assumption.  However setting a seperate variable means we don't spread that assumption all through
			//the template code.
			$baseurl_cdn = $vboptions['cdnurl'];

			if($baseurl_cdn)
			{
				$baseurl_corecdn = $baseurl_cdn . '/core';
			}
			else
			{
				//if we haven't set a cdn url, then let's default to the actual site urls.
				$baseurl_cdn = '.';
				$baseurl_corecdn = './core';
			}
			$this->registerGlobal('baseurl_cdn', $baseurl_cdn, true);
			$this->registerGlobal('baseurl_corecdn', $baseurl_corecdn, true);

			$vbproducts = vB::getDatastore()->getValue('products');
			$this->registerGlobal('vbproducts', $vbproducts, true);

			$preferred_styleid = vB5_Template_Stylevar::instance()->getPreferredStyleId() > 0 ? vB5_Template_Stylevar::instance()->getPreferredStyleId() : $vboptions['styleid'];
			$this->registerGlobal('preferred_styleid', $preferred_styleid, true);

			$preferred_languageid  = vB5_User::getLanguageId() > 0 ? vB5_User::getLanguageId() : $vboptions['languageid'];
			$this->registerGlobal('preferred_languageid', $preferred_languageid, true);

			$timenow = time();
			$this->registerGlobal('timenow', $timenow, true);

			$done = true;
		}
	}


	/**
	 * Renders the output after preperation.
	 * @see vB5_Template::render()
	 *
	 * @param boolean	Whether to suppress the HTML comment surrounding option (for JS, etc)
	 * @param boolean	true if we are rendering for a call to /ajax/render/ and we want CSS <link>s separate
	 *
	 * @return string
	 */
	public function render($isParentTemplate = true, $isAjaxTemplateRender = false)
	{
		// bring template variables into scope
		extract(self::$globalRegistered, EXTR_SKIP | EXTR_REFS);
		extract($this->registered, EXTR_OVERWRITE | EXTR_REFS);

		// NOTE: from here until the eval call, try not to define/set
		// any new variables, since they will technically then be
		// available to the templates as defacto "globally registered"
		// variables.

		// NOTE: the $config variable below is available in this scope via
		// the global registered extract call above

		// NOTE: Keep the debug template render in renderDelayed() in sync
		// with this render() function

		// save info for debug output
		self::handlePreRenderDebugInfo($this->template, $isParentTemplate);

		// todo: remove this once we can remove notices from template code
		// allow developers to turn notices off for templates -- to avoid having them turn off notices entirely
		// We also have problems with static vs non static calls in the "vb:action" handling.
		if ($config->no_template_notices)
		{
			$oldReporting = error_reporting(E_ALL & ~(E_NOTICE | E_STRICT | E_DEPRECATED));
		}

		if ($config->render_debug)
		{
			set_exception_handler(null);
			set_error_handler('vberror');

			// Show which template is being rendered.
			echo 'Template: ' . $this->template . '<br />';
		}

		$templateCache = vB5_Template_Cache::instance();
		$templateCode = $templateCache->getTemplate($this->template);

		if(is_array($templateCode) AND !empty($templateCode['textonly']))
		{
			$final_rendered = $templateCode['placeholder'];
		}
		else if($templateCache->isTemplateText())
		{
			eval($templateCode);
		}
		else
		{
			if ($templateCode !== false)
			{
				include($templateCode);
			}
		}

		if ($config->render_debug)
		{
			restore_error_handler();
			restore_exception_handler();
		}

		if ($config->no_template_notices)
		{
			error_reporting($oldReporting);
		}

		// always replace placeholder for templates, as they are process by levels
		$templateCache->replacePlaceholders($final_rendered);
		if ($isParentTemplate)
		{
			// we only replace phrases/urls/nodetext, insert javascript and stylesheets at the parent template
			$this->renderDelayed($final_rendered, $isAjaxTemplateRender);

			//Store the configuration information in the session
			if (!empty(vB5_Config::instance()->php_sessions))
			{
				if (session_status() == PHP_SESSION_NONE)
				{
					$expires = vB5_Config::instance()->session_expires;
					if (!empty($expires) AND intval($expires))
					{
						session_cache_expire(intval($expires));
					}
					session_start();
					$_SESSION['languageid'] = $preferred_languageid;
					$_SESSION['userid'] = vB5_User::get('userid');
				}
			}
		}

		// save info for debug output
		self::handlePostRenderDebugInfo();

		// add template name to HTML source for debugging
		self::addDebugTemplateName($final_rendered, $this->template);

		return $final_rendered;
	}

	/**
	 * Handles storing the debug info about templates that have been rendered. Called PRE-render.
	 *
	 * @param string Template name
	 * @param bool   Is this a parent template
	 */
	protected static function handlePreRenderDebugInfo($templateName, $isParentTemplate)
	{
		self::$renderedTemplateNames[] = $templateName;

		// debug info for the templates that have been used
		if (vB5_Config::instance()->debug)
		{
			self::$renderedTemplates[] = array(
				'templateName'     => $templateName,
				'isParentTemplate' => (bool) $isParentTemplate,
				'indent'           => str_repeat('|----', count(self::$renderedTemplatesStack)),
			);
			self::$renderedTemplatesStack[] = $templateName;
		}
	}

	/**
	 * Handles storing the debug info about templates that have been rendered. Called POST-render.
	 */
	protected static function handlePostRenderDebugInfo()
	{
		// debug info for the templates that have been used
		if (vB5_Config::instance()->debug)
		{
			array_pop(self::$renderedTemplatesStack);
		}
	}

	/**
	 * Adds the template name as an HTML comment for debugging purposes
	 *
	 * @param  string (reference) The rendered HTML
	 * @param  string The template name.
	 *
	 * @return string The rendered HTML with an HTML comment before/after, indicating the template name
	 */
	public static function addDebugTemplateName(&$final_rendered, $templateName)
	{
		// add template name to HTML source for debugging
		if (!empty(self::$globalRegistered['vboptions']['addtemplatename']) AND self::$globalRegistered['vboptions']['addtemplatename'])
		{
			$final_rendered = "<!-- BEGIN: $templateName -->$final_rendered<!-- END: $templateName -->";
		}
	}

	/**
	 * Handle any delayed rendering. Currently delayed urls and node texts.
	*
	* @param	string
	 * @param	boolean	true if we are rendering for a call to /ajax/render/ and we want CSS <link>s separate
	*
	* @return	string
	 */
	protected function renderDelayed(&$final_rendered_orig, $isAjaxTemplateRender = false)
	{
		$javascript = vB5_Template_Javascript::instance();
		$javascript->insertJs($final_rendered_orig);
		$javascript->resetPending();

		$link = vB5_Template_Headlink::instance();
		$link->insertLinks($final_rendered_orig);
		$link->resetPending();

		$phrase = vB5_Template_Phrase::instance();
		$phrase->replacePlaceholders($final_rendered_orig);
		$phrase->resetPending();

		// we do not reset pending urls, since they may be required by nodetext
		vB5_Template_Url::instance()->replacePlaceholders($final_rendered_orig);

		$nodeText = vB5_Template_NodeText::instance();
		$nodeText->replacePlaceholders($final_rendered_orig);
		$nodeText->resetPending();
		$templateCache = vB5_Template_Cache::instance();
		$templateCache->replaceTextOnly($final_rendered_orig);

		// insert stylesheets after phrases and node text, since both could
		// contain CSS block classes that insertCSS will autoload
		$stylesheet = vB5_Template_Stylesheet::instance();
		$stylesheet->insertCss($final_rendered_orig, $isAjaxTemplateRender);
		$stylesheet->resetPending();

		//We should keep the debug info for truly last.
		if (vB5_Frontend_Controller_Bbcode::needDebug())
		{
			$config = vB5_Config::instance();

			if (!$config->debug)
			{
				return $final_rendered_orig;
			}

			self::$renderedTemplateNames[] = 'debug_info';
			self::$renderedTemplates[] = array(
				'templateName' => 'debug_info',
				'isParentTemplate' => (bool) 0,
				'indent' => str_repeat('|----', 2),
			);

			// Keep this render in sync with the render() function

			$user = vB5_User::instance();
			$this->register('user', $user, true);
			extract(self::$globalRegistered, EXTR_SKIP | EXTR_REFS);
			extract($this->registered, EXTR_OVERWRITE | EXTR_REFS);
			$vboptions = vB5_Template_Options::instance()->getOptions();
			$vboptions = $vboptions['options'];
			$renderedTemplates = array(
				'count' => count(self::$renderedTemplates),
				'countUnique' => count(array_unique(self::$renderedTemplateNames)),
				'templates' => self::$renderedTemplates,
				'styleid' => vB5_Template_Stylevar::instance()->getPreferredStyleId(),
			);
			$cssDebugLog = vB5_Template_Stylesheet::getDebugLog();
			$jsDebugLog = vB5_Template_Javascript::instance()->getDebugLog();
			$includedFileInfo = $this->getIncludedFileInfo();
			$autoloadInfo = $this->getAutoloadInfo();

			$templateCode = $templateCache->getTemplate('debug_info');
			if($templateCache->isTemplateText())
			{
				@eval($templateCode);
			}
			else
			{
				@include($templateCode);
			}

			$phrase->replacePlaceholders($final_rendered);
			$phrase->resetPending();

			// <!-- VB-DEBUG-PAGE-TIME-PLACEHOLDER --> is replaced in the controller's outputPage method
			//$final_rendered = str_replace('<!-- VB-DEBUG-PAGE-TIME-PLACEHOLDER -->', round(microtime(true) - VB_REQUEST_START_TIME, 4), $final_rendered);

			$final_rendered_orig = str_replace('<!-DebugInfo-->', $final_rendered, $final_rendered_orig);
		}
	}

	/**
	 * Returns the included file information used by renderDelayed for the debug_info template
	 *
	 * @return array Array containing 'files' and 'count'.
	 */
	protected function getIncludedFileInfo()
	{
		$dir = rtrim(str_replace('\\', '/', DIR), '/');
		if (substr($dir, -5) == '/core')
		{
			$dir = substr($dir, 0, -5);
		}

		$len = strlen($dir);

		$includedFiles = get_included_files();
		foreach ($includedFiles AS $k => $v)
		{
			$v = str_replace('\\', '/', $v);
			if (strpos($v, $dir) === 0)
			{
				$includedFiles[$k] = '.' . substr($v, $len);
			}
		}

		return array(
			'files' => $includedFiles,
			'count' => count($includedFiles),
		);
	}

	/**
	 * Returns debug autoload info
	 *
	 * @return array Array of debug info containing 'classes' and 'count'
	 */
	public static function getAutoloadInfo()
	{
		$info = array(
			vB5_Autoloader::getAutoloadInfo(),
			vB::getAutoloadInfo(),
		);

		$autoloadInfo = array();

		foreach ($info AS $loaderInfo)
		{
			foreach ($loaderInfo AS $class => $classInfo)
			{
				if (!isset($autoloadInfo[$class]))
				{
					$autoloadInfo[$class] = $classInfo;
				}
				else
				{
					// keep the one that actually loaded the class
					if (!empty($classInfo['loaded']) AND !empty($classInfo['filename']))
					{
						$autoloadInfo[$class] = $classInfo;
					}
				}
			}
		}

		$dir = rtrim(str_replace('\\', '/', DIR), '/');
		if (substr($dir, -5) == '/core')
		{
			$dir = substr($dir, 0, -5);
		}
		$len = strlen($dir);

		foreach ($autoloadInfo AS $k => $v)
		{
			if (!empty($v['filename']))
			{
				$v['filename'] = str_replace('\\', '/', $v['filename']);
				if (strpos($v['filename'], $dir) === 0)
				{
					$autoloadInfo[$k]['filename'] = '.' . substr($v['filename'], $len);
				}
			}
		}

		return array(
			'classes' => $autoloadInfo,
			'count' => count($autoloadInfo),
		);
	}

	public static function getRenderedTemplates()
	{
		return self::$renderedTemplateNames;
	}

	/**
	 * Returns a string containing the rendered template
	 * @see vB5_Frontend_Controller_Ajax::actionRender
	 * @see vB5_Frontend_Controller_Page::renderTemplate
	 * @param string $templateName
	 * @param array $data
	 * @param bool $isParentTemplate
	 * @param bool $isAjaxTemplateRender - true if we are rendering for a call to /ajax/render/ and we want CSS <link>s separate
	 * @return string
	 */
	public static function staticRender($templateName, $data = array(), $isParentTemplate = true, $isAjaxTemplateRender = false)
	{
		if (empty($templateName))
		{
			return null;
		}

		$templater = new vB5_Template($templateName);

		foreach ($data as $varname => $value)
		{
			$templater->register($varname, $value);
		}

		$core_path = vB5_Config::instance()->core_path;
		vB5_Autoloader::register($core_path);

		$result = $templater->render($isParentTemplate, $isAjaxTemplateRender);
		return $result;
	}

	/**
	 * Returns a string containing the rendered template
	 * @see vB5_Frontend_Controller_Ajax::actionRender
	 * @param string $templateName
	 * @param array $data
	 * @return string
	 */
	public static function staticRenderAjax($templateName, $data = array())
	{
		$rendered = self::staticRender($templateName, $data, true, true);

		$css = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

		return array(
			'template' => $rendered,
			'css_links' => $css,
		);
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89164 $
|| #######################################################################
\*=========================================================================*/
