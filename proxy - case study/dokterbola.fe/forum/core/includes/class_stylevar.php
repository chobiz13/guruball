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
* Class to handle style variable storage
*
* @package	vBulletin
* @version	$Revision: 89092 $
* @date		$Date: 2016-06-16 16:18:40 -0700 (Thu, 16 Jun 2016) $
*/

abstract class vB_StyleVar
{
	public $registry;

	public $stylevarid;

	protected $definition;
	protected $value;
	protected $inherited = 0;		// used to set the color, 0 = unchanged, 1 = inherited from parent, -1 = customized in this style

	private $styleid = -1;

	// static variables for printing color input rows
	protected static $need_colorpicker = true;
	protected static $count = 0;

	// only output the background preview js once
	protected static $need_background_preview_js = true;

	// static variables for including stylevars-as-values autocomplete functionality
	protected static $need_stylevar_autocomplete_js = true;

	// Styelvar cache for stylevar as value references
	protected static $stylevar_cache = array();

	//abstract public function validate();
	function validate()
	{
		return true;
	}

	public function print_editor()
	{
		global $vbulletin, $vbphrase;

		$vb5_config =& vB::getConfig();

		$header = $vbphrase["stylevar_{$this->stylevarid}_name"] ? $vbphrase["stylevar_{$this->stylevarid}_name"] : $this->stylevarid;

		$addbit = false;
		if ($vbulletin->GPC['dostyleid'] == -1)
		{
			$header .= ' - <span class="smallfont">' . construct_link_code($vbphrase['edit'], "stylevar.php?" . vB::getCurrentSession()->get('sessionurl') . "do=dfnedit&amp;stylevarid=" . $this->stylevarid);
			$addbit = true;
		}

		if ($this->inherited == -1)
		{
			if (!$addbit)
			{
				$header .= ' - <span class="smallfont">';
				$addbit = true;
			}
			else
			{
				$header .= ' - ';
			}

			$header .= construct_link_code($vbphrase['revert_gcpglobal'], "stylevar.php?" . vB::getCurrentSession()->get('sessionurl') . "do=confirmrevert&amp;dostyleid=" . $vbulletin->GPC['dostyleid'] . "&amp;stylevarid=" . $this->stylevarid . "&amp;rootstyle=-1");
		}

		if ($addbit)
		{
			$header .= '</span>';
		}

		print_table_header($header);

		if ($vbphrase["stylevar_{$this->stylevarid}_description"])
		{
			print_description_row($vbphrase["stylevar_{$this->stylevarid}_description"], false, 2);
		}

		if ($vb5_config['Misc']['debug'])
		{
			print_label_row($vbphrase['stylevarid'], $this->stylevarid);
		}

		// output this stylevar's inheritance level (inherited or customized)
		// so that we can update the stylevar list and show inherited status
		// immediately
		echo '<script type="text/javascript">
			window.vBulletinStylevarInheritance = window.vBulletinStylevarInheritance ? window.vBulletinStylevarInheritance : {};
			window.vBulletinStylevarInheritance["' . $this->stylevarid . '"] = ' . $this->inherited . ';
		</script>';

		// once we have LSB change this to self::
		$this->print_editor_form();
	}

	abstract public function print_editor_form();

	public function set_value($value)
	{
		$this->value = $value;
		//$this->validate();

		$stylevar_value_prefix = 'stylevar_';
		foreach ($this->value as $key => $value)
		{
			if ((strpos($key, $stylevar_value_prefix)) === 0)
			{
				continue;
			}

			$stylevar_value_key = $stylevar_value_prefix . $key;
			if (empty($value) AND !empty($this->value[$stylevar_value_key]))
			{
				$this->value[$key] = $this->fetch_sub_stylevar_value($this->value[$stylevar_value_key]);
			}
		}
	}

	private function fetch_sub_stylevar_value($stylevar)
	{
		$styleid = $this->styleid;

		if (!isset(self::$stylevar_cache[$styleid]))
		{
			self::$stylevar_cache[$styleid] = vB_Api::instance('style')->fetchStyleVars(array($styleid));
		}

		$style = self::$stylevar_cache[$styleid];

		$parts = explode('.', $stylevar);

		if (isset($style[$parts[0]]))
		{
			if (isset($parts[1]) AND empty($style[$parts[0]][$parts[1]]) AND !empty($style[$parts[0]]['stylevar_' . $parts[1]]))
			{
				return $this->fetch_sub_stylevar_value($style[$parts[0]]['stylevar_' . $parts[1]]);
			}
			else if (isset($parts[1]))
			{
				return $style[$parts[0]][$parts[1]];
			}
		}

		return $stylevar;
	}

	public function set_definition($definition)
	{
		$this->definition = $definition;
	}

	public function set_inherited($inherited)
	{
		$this->inherited = $inherited;
	}

	public function set_stylevarid($stylevarid)
	{
		$this->stylevarid = $stylevarid;
	}

	public function set_styleid($styleid)
	{
		$this->styleid = $styleid;
	}

	public function get()
	{
		return ($this->value);
	}

	protected function fetch_inherit_color()
	{
		switch($this->inherited)
		{
			case 0:
				$class = 'col-g';
				break;

			case 1:
				$class = 'col-i';
				break;

			case -1:
			default:
				$class = 'col-c';
				break;
		}
		return $class;
	}

	public function build()
	{
		if (!is_array($this->value))
		{
			$this->value = array($this->value);
		}

		$value = serialize($this->value);
		$this->registry->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "stylevar
			(stylevarid, styleid, value, dateline, username)
			VALUE
			(
				'" . $this->registry->db->escape_string($this->stylevarid) . "',
				" . intval($this->styleid) . ",
				'" . $this->registry->db->escape_string($value) . "',
				" . TIMENOW . ",
				'" . $this->registry->db->escape_string($this->registry->userinfo['username']) . "'
			)
		");
	}

	protected function print_units($current_units, $stylevar_value)
	{
		global $vbphrase;
		$svunitsarray = array(
			'' => '',
			'%' => '%',
			'px' => 'px',
			'pt' => 'pt',
			'em' => 'em',
			'ex' => 'ex',
			'pc' => 'pc',
			'in' => 'in',
			'cm' => 'cm',
			'mm' => 'mm'
		);

		$this->print_select_row($vbphrase['units'], $this->stylevarid, 'units', $svunitsarray, $current_units, $stylevar_value);
	}

	protected function fetch_stylevar_input($stylevarid, $input_type, $stylevar_value)
	{
		global $vbphrase;

		$vb5_config =& vB::getConfig();
		if (!$vb5_config['Misc']['debug'])
		{
			return '';
		}

		$autocomplete_js = '';
		if (self::$need_stylevar_autocomplete_js == true)
		{
			// This relies on GPC['dostyleid']. We're assuming this won't change in a way where you can edit multiple styles at the same time.
			$style = fetch_stylevars_array();
			$global_groups = array('Global');
			$global_stylevars = array();

			foreach ($global_groups AS $group)
			{
				if (!isset($style[$group]))
				{
					continue;
				}

				foreach ($style[$group] AS $global_stylevarid => $global_stylevar)
				{
					$global_stylevar = unserialize($global_stylevar['value']);

					if (empty($global_stylevar))
					{
						continue;
					}

					foreach (array_keys($global_stylevar) AS $type)
					{
						if (strpos($type, 'stylevar_') === 0)
						{
							continue;
						}

						$global_stylevars[] = "'" . vB_Template_Runtime::escapeJS($global_stylevarid) . '.' . $type . "'";
					}
				}
			}

			$autocomplete_js .= "\n<script type=\"text/javascript\" src=\"js/jquery/jquery-ui-1.11.4.custom.min.js?v=" . SIMPLE_VERSION . "\"></script>\n\r" .
				'<script type="text/javascript">
				//<!--
				(function($) {
					$(document).ready(function(){
						var stylevars = [' . implode(', ', $global_stylevars) . '];
						$(".stylevar-autocomplete")
							.autocomplete({
								source: stylevars,
								appendTo: ".stylevar-autocomplete-menu",
								minLength: 0
							})
							.focus(function(){
								$(this).autocomplete("search", "");
							})
					});
				})(jQuery);
				//-->
				</script>
				<div class="stylevar-autocomplete-menu"></div>';
			self::$need_stylevar_autocomplete_js = false;
		}

		$stylevar_name = 'stylevar[' . $stylevarid .'][stylevar_' . $input_type . ']';
		$stylevar_title_attr = "title=\"name=&quot;$stylevar_name&quot;\"";
		$uniqueid = fetch_uniqueid_counter();

		return ' ' . $vbphrase['or_stylevar_part']. ' ' .
			"<input name=\"$stylevar_name\" id=\"inp_{$stylevar_name}_$uniqueid\" class=\"stylevar-autocomplete\" value=\"" . htmlspecialchars_uni($stylevar_value) . "\" " .
				"tabindex=\"1\" size=\"35\" $stylevar_title_attr  data-options-id=\"sel_{$stylevar_name}_$uniqueid\" />\n" .
			$autocomplete_js;
	}

	protected function print_input_row($title, $stylevarid, $input_type, $value, $stylevar_value)
	{
		$vb5_config =& vB::getConfig();

		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$value = htmlspecialchars_uni($value);
		$size = 35;
		$direction = verify_text_direction('');

		$cell = "<div id=\"ctrl_$name\"><input type=\"text\" class=\"" . iif($inputclass, $inputclass, 'bginput') .
			"\" name=\"$name\" value=\"" . $value . "\" size=\"$size\" dir=\"$direction\" tabindex=\"1\"" .
			iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . " />";

		$cell .= $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);

		$cell .= "</div>\n";

		print_label_row(
			$title,
			$cell,
			'', 'top', $name
		);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_textarea_row($title, $stylevarid, $input_type, $value, $stylevar_value)
	{
		global $vbphrase;
		$vb5_config =& vB::getConfig();

		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$textarea_id = 'ta_' . $name . '_' . fetch_uniqueid_counter();
		$value = htmlspecialchars_uni($value);
		$cols = 40;
		$rows = 20;
		$direction = verify_text_direction('');

		// trigger hasLayout for IE to prevent template box from jumping (#22761)
		$ie_reflow_css = (is_browser('ie') ? 'style="zoom:1"' : '');

		$resizer = "<div class=\"smallfont sizetools\"><a class=\"increase\" href=\"#\" $ie_reflow_css onclick=\"return resize_textarea(1, '$textarea_id')\">$vbphrase[increase_size]</a> <a class=\"decrease\" href=\"#\" $ie_reflow_css onclick=\"return resize_textarea(-1, '$textarea_id')\">$vbphrase[decrease_size]</a></div>";

		$cell = "<div id=\"ctrl_$name\"><textarea name=\"$name\" id=\"$textarea_id\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" dir=\"$direction\" tabindex=\"1\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . ">" . $value . "</textarea>";

		$cell .= $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);

		$cell .= " $resizer</div>\n";

		print_label_row(
			$title,
			$cell,
			'', 'top', $name
		);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_select_row($title, $stylevarid, $input_type, $array, $value, $stylevar_value)
	{
		global $vbphrase;
		$vb5_config =& vB::getConfig();

		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$uniqueid = fetch_uniqueid_counter();

		$select = "<div id=\"ctrl_$name\"><select name=\"$name\" id=\"sel_{$name}_$uniqueid\" tabindex=\"1\" class=\"bginput\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . ">\n";
		$select .= construct_select_options($array, $value, true);
		$select .= "</select>";

		$select .= $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);

		$select .= "</div>\n";

		print_label_row($title,
			$select, '', 'top', $name
		);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_yes_no_row($title, $stylevarid, $input_type, $value, $stylevar_value)
	{
		global $vbphrase;
		$vb5_config =& vB::getConfig();

		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$uniqueid = fetch_uniqueid_counter();
		$value = intval($value);

		$cell = "<div id=\"ctrl_$name\" class=\"smallfont\" style=\"white-space:nowrap\">
			<label for=\"rb_1_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_1_{$name}_$uniqueid\" value=\"1\" tabindex=\"1\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot; value=&quot;1&quot;\"") . iif($value == 1, ' checked="checked"') . " />$vbphrase[yes]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
			<label for=\"rb_0_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_0_{$name}_$uniqueid\" value=\"0\" tabindex=\"1\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot; value=&quot;0&quot;\"") . iif($value == 0, ' checked="checked"') . " />$vbphrase[no]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>" .
			"\n\t";

		$cell .= $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);

		$cell .= "</div>\n";

		print_label_row(
			$title,
			$cell,
			'', 'top', $name
		);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_color_input_row($title, $stylevarid, $color_value, $stylevar_value)
	{
		global $vbphrase;

		$cp = "";

		$color_value = htmlspecialchars_uni($color_value);

		//only include the colorpicker on the first color element.
		if (self::$need_colorpicker)
		{
			//construct all of the markup/javascript for the color picker.

			//set from construct_color_picker
			global $colorPickerWidth, $colorPickerType;

			$cp = '<script type="text/javascript" src="core/clientscript/vbulletin_cpcolorpicker.js?v=' .
				vB::getDatastore()->getOption('simpleversion') . '"></script>' . "\n";
			$cp .= construct_color_picker(11);

			$js_phrases = array();
			foreach (array(
				'css_value_invalid',
				'color_picker_not_ready',
			) AS $phrasename)
			{
				$js_phrases[] = "vbphrase.$phrasename = \"" . fetch_js_safe_string($vbphrase["$phrasename"]) . "\"";
			}

			$js_phrases = implode(";\r\n\t", $js_phrases) . ";\r\n";

			$cp .= '
					<script type="text/javascript">
					<!--
					var bburl = "' . vB::getDatastore()->getOption('bburl') .'";
					var cpstylefolder = "' .vB::getDatastore()->getOption('cpstylefolder') .'";
					var colorPickerWidth = ' . intval($colorPickerWidth) . ';
					var colorPickerType = ' . intval($colorPickerType) . ';
					if(vbphrase == undefined) {vbphrase = new Object();}
					' . $js_phrases . '

					vBulletin.events.systemInit.subscribe(function()
					{
						init_color_preview();
					});
					//-->
				</script>';

			self::$need_colorpicker = false;
		}

		$vb5_config =& vB::getConfig();

		$id = 'color_'. self::$count;
		$color_name = 'stylevar[' . $stylevarid .'][color]';

		$title_attr = ($vb5_config['Misc']['debug'] ? " title=\"name=&quot;$color_name&quot;\"" : '');
		$cell =
			"<div id=\"ctrl_$color_name\" class=\"color_input_container\">" .
				"<input type=\"text\" name=\"$color_name\" id=\"$id\" " .
					"value=\"$color_value\" " .
					"tabindex=\"1\" $title_attr />" .
			"</div>";

		$color_preview = '<div id="preview_' . self::$count .
			'" class="colorpreview" onclick="open_color_picker(' . self::$count . ', event)"></div>';

		$or_stylevar = $this->fetch_stylevar_input($stylevarid, 'color', $stylevar_value);

		print_label_row(
			$title,
			$cp . $cell . $color_preview . $or_stylevar,
			'', 'top', $color_name
		);
		construct_hidden_code('stylevar[' . $stylevarid .'][original_color]', $color_value, false);

		self::$count++;
	}

	protected function print_background_output()
	{
		//this assumes that there is a base tag such that all relative links are to the site root

		global $vbphrase;
		$image = $this->value['image'];

		// if the image path was entered with quotes, it will cause problems due to the
		// relative path added above, and when outputting the value in the style="" tag below
		$image = str_replace(array('"', "'"), '', $image);

		$background_preview_js = '';
		if (self::$need_background_preview_js)
		{
			$background_preview_js = '
				<script type="text/javascript">
				<!--
					function previewBackground(stylevar)
					{
						/**
						 * @param	string	name of the stylevar
						 * @param	string	the item you want to fetch (color, background image, repeat, etc)
						 * @return	string	the value from the form element
						 */
						var fetch_form_element_value = function(stylevar, item)
						{
							var wrapperid = "ctrl_stylevar[" + stylevar + "][" + item + "]";
							var wrapper = YAHOO.util.Dom.get(wrapperid);

							// input for color, image, and offsets
							var formel = wrapper.getElementsByTagName("input");
							if (formel && formel[0])
							{
								return formel[0].value;
							}

							// select for background repeat and units
							formel = wrapper.getElementsByTagName("select");
							if (formel && formel[0])
							{
								return formel[0].value;
							}
						};

						var backgroundString = "";
						backgroundString += fetch_form_element_value(stylevar, "color");

						// Fix the image path. This assumes the images folder is stored in root directory
						var image_path = fetch_form_element_value(stylevar, "image");
						backgroundString += " " + image_path;

						backgroundString += " " + fetch_form_element_value(stylevar, "repeat");

						var offset_units = fetch_form_element_value(stylevar, "units");
						backgroundString += " " + fetch_form_element_value(stylevar, "x") + offset_units;
						backgroundString += " " + fetch_form_element_value(stylevar, "y") + offset_units;

						YAHOO.util.Dom.get("preview_bg_" + stylevar).style.background = backgroundString;
					}
				-->
				</script>';
			self::$need_background_preview_js = false;
		}

		$cell = "
			<div id=\"preview_bg_" . $this->stylevarid . "\" style=\"
				background: " . $this->value['color'] .
				" " . $image .
				" " . $this->value['repeat'] .
				" " . $this->value['x'] . $this->value['units'].
				" " . $this->value['y'] . $this->value['units'].
				";width:100%;height:30px;border:1px solid #000000;\">
			</div>";

		$label = '<a href="javascript:previewBackground(\'' . $this->stylevarid . '\');">'. $vbphrase['click_here_to_preview'] .' </a>';
		print_label_row($label, $background_preview_js . $cell);
	}
}

class vB_StyleVar_default extends vB_StyleVar
{
	private $datatype;

	public function __construct($datatype)
	{
		$this->datatype = $datatype;
	}

	public function print_editor_form()
	{
		global $vbphrase;

		// imagedir, url, path, and string are technically all just strings
		switch ($this->datatype)
		{
			case 'string':
				$this->print_input_row($vbphrase['string'],  $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'url':
				$this->print_input_row($vbphrase['url_gstyle'], $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'imagedir':
				$this->print_input_row($vbphrase['image_path'], $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'path':
				$this->print_input_row($vbphrase['path'], $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'numeric':
				$this->print_input_row($vbphrase['numeric'], $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'size':
				$this->print_units($this->value['units'], $this->value['stylevar_units']);
				$this->print_input_row($vbphrase['size_gstyle'], $this->stylevarid, 'size', $this->value['size'], $this->value['stylevar_size']);
				break;

			case 'fontlist':
				$this->print_textarea_row($vbphrase['fontlist'], $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;
		}
	}
}

class vB_StyleVar_padding extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;
		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_yes_no_row($vbphrase['use_same_padding_margin'], $this->stylevarid, 'same', $this->value['same'], $this->value['stylevar_same']);
		$this->print_input_row($vbphrase['top'], $this->stylevarid, 'top', $this->value['top'], $this->value['stylevar_top']);
		$this->print_input_row($vbphrase['right_gstyle'], $this->stylevarid, 'right', $this->value['right'], $this->value['stylevar_right']);
		$this->print_input_row($vbphrase['bottom'], $this->stylevarid, 'bottom', $this->value['bottom'], $this->value['stylevar_bottom']);
		$this->print_input_row($vbphrase['left_gstyle'], $this->stylevarid, 'left', $this->value['left'], $this->value['stylevar_left']);
	}
}

class vB_StyleVar_margin extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;
		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_yes_no_row($vbphrase['use_same_padding_margin'], $this->stylevarid, 'same', $this->value['same'], $this->value['stylevar_same']);
		$this->print_input_row($vbphrase['top'], $this->stylevarid, 'top', $this->value['top'], $this->value['stylevar_top']);
		$this->print_input_row($vbphrase['right_gstyle'], $this->stylevarid, 'right', $this->value['right'], $this->value['stylevar_right']);
		$this->print_input_row($vbphrase['bottom'], $this->stylevarid, 'bottom', $this->value['bottom'], $this->value['stylevar_bottom']);
		$this->print_input_row($vbphrase['left_gstyle'], $this->stylevarid, 'left', $this->value['left'], $this->value['stylevar_left']);
	}
}

class vB_StyleVar_textdecoration extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;
		// needs checked
		$this->print_yes_no_row($vbphrase['none'], $this->stylevarid, 'none', $this->value['none'], $this->value['stylevar_none']);
		$this->print_yes_no_row($vbphrase['underline_gstyle'], $this->stylevarid, 'underline', $this->value['underline'], $this->value['stylevar_underline']);
		$this->print_yes_no_row($vbphrase['overline'], $this->stylevarid, 'overline', $this->value['overline'], $this->value['stylevar_overline']);
		$this->print_yes_no_row($vbphrase['linethrough'], $this->stylevarid, 'line-through', $this->value['line-through'], $this->value['stylevar_line-through']);
	}
}

class vB_StyleVar_texttransform extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;

		$values = array(
			'none'       => $vbphrase['none'],
			'capitalize' => $vbphrase['capitalize'],
			'uppercase'  => $vbphrase['uppercase'],
			'lowercase'  => $vbphrase['lowercase'],
			'initial'    => $vbphrase['initial'],
			'inherit'    => $vbphrase['inherit'],
		);

		$this->print_select_row($vbphrase['text_transform'], $this->stylevarid, 'texttransform', $values, $this->value['texttransform'], $this->value['stylevar_texttransform']);
	}
}

class vB_StyleVar_textalign extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;

		$values = array(
			'left'    => $vbphrase['left_gstyle'],
			'right'   => $vbphrase['right_gstyle'],
			'center'  => $vbphrase['center'],
			'justify' => $vbphrase['justify'],
			'initial' => $vbphrase['initial'],
			'inherit' => $vbphrase['inherit'],
		);

		$this->print_select_row($vbphrase['text_align'], $this->stylevarid, 'textalign', $values, $this->value['textalign'], $this->value['stylevar_textalign']);
	}
}

class vB_StyleVar_font extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;

		$font_weights = array(
			'' => '',
			'normal' => $vbphrase['normal_gstyle'],
			'bold' => $vbphrase['bold_gstyle'],
			'bolder' => $vbphrase['bolder'],
			'lighter' => $vbphrase['lighter'],
		);

		$font_styles = array(
			'' => '',
			'normal' => $vbphrase['normal_gstyle'],
			'italic' => $vbphrase['italic_gstyle'],
			'oblique' => $vbphrase['oblique'],
		);

		$font_variants = array(
			'' => '',
			'normal' => $vbphrase['normal_gstyle'],
			'small-caps' => $vbphrase['small_caps'],
		);

		$this->print_input_row($vbphrase['font_family_gstyle'], $this->stylevarid, 'family', $this->value['family'], $this->value['stylevar_family']);

		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['font_size'], $this->stylevarid, 'size', $this->value['size'], $this->value['stylevar_size']);

		$this->print_select_row($vbphrase['font_weight'], $this->stylevarid, 'weight', $font_weights, $this->value['weight'], $this->value['stylevar_weight']);
		$this->print_select_row($vbphrase['font_style'], $this->stylevarid, 'style', $font_styles, $this->value['style'], $this->value['stylevar_style']);
		$this->print_select_row($vbphrase['font_variant'], $this->stylevarid, 'variant', $font_variants, $this->value['variant'], $this->value['stylevar_variant']);
	}
}

class vB_StyleVar_background extends vB_StyleVar
{

	public function print_editor_form()
	{
		global $vbphrase;

		$values = array(
			'' => '',
			'repeat' => $vbphrase['repeat'],
			'repeat-x' => $vbphrase['repeat_x'],
			'repeat-y' => $vbphrase['repeat_y'],
			'no-repeat' => $vbphrase['no_repeat'],
		);

		$this->print_color_input_row($vbphrase['background_color'], $this->stylevarid, $this->value['color'], $this->value['stylevar_color']);
		$this->print_input_row($vbphrase['background_image'], $this->stylevarid, 'image', $this->value['image'], $this->value['stylevar_image']);
		$this->print_select_row($vbphrase['background_repeat'], $this->stylevarid, 'repeat', $values, $this->value['repeat'], $this->value['stylevar_repeat']);
		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['background_position_x'], $this->stylevarid, 'x', $this->value['x'], $this->value['stylevar_x']);
		$this->print_input_row($vbphrase['background_position_y'], $this->stylevarid, 'y', $this->value['y'], $this->value['stylevar_y']);
		$this->print_background_output();
	}

}

class vB_StyleVar_dimension extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;

		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['width'], $this->stylevarid, 'width', $this->value['width'], $this->value['stylevar_width']);
		$this->print_input_row($vbphrase['height'], $this->stylevarid, 'height', $this->value['height'], $this->value['stylevar_height']);
	}
}

class vB_StyleVar_border extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;

		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['width'], $this->stylevarid, 'width', $this->value['width'], $this->value['stylevar_width']);
		$this->print_input_row($vbphrase['border_style'], $this->stylevarid, 'style', $this->value['style'], $this->value['stylevar_style']);
		$this->print_color_input_row($vbphrase['color_gstyle'], $this->stylevarid, $this->value['color'], $this->value['stylevar_color']);
	}
}

class vB_StyleVar_color extends vB_StyleVar
{
	public function print_editor_form()
	{
		global $vbphrase;

		$this->print_color_input_row($vbphrase['color_gstyle'], $this->stylevarid, $this->value['color'], $this->value['stylevar_color']);
	}
}

class vB_StyleVar_factory
{
	/**
	 * Creates a stylevar.
	 *
	 * @param string $type
	 * @return vB_StyleVar
	 */
	public static function create($type)
	{
		// not really a good factory, in fact, this is a dumb factory
		$stylevarobj = null;
		switch ($type)
		{
			case 'numeric':
			case 'string':
			case 'url':
			case 'imagedir':
			case 'image':
			case 'path':
			case 'fontlist':
			case 'size':
				$stylevarobj = new vB_StyleVar_default($type);
				break;

			case 'color':
				$stylevarobj = new vB_StyleVar_color();
				break;

			case 'background':
				$stylevarobj = new vB_StyleVar_background();
				break;

			case 'textdecoration':
				$stylevarobj = new vB_StyleVar_textdecoration();
				break;

			case 'texttransform':
				$stylevarobj = new vB_StyleVar_texttransform();
				break;

			case 'textalign':
				$stylevarobj = new vB_StyleVar_textalign();
				break;

			case 'font':
				$stylevarobj = new vB_StyleVar_font();
				break;

			case 'dimension':
				$stylevarobj = new vB_StyleVar_dimension();
				break;

			case 'border':
				$stylevarobj = new vB_StyleVar_border();
				break;

			case 'padding':
				$stylevarobj = new vB_StyleVar_padding();
				break;

			case 'margin':
				$stylevarobj = new vB_StyleVar_margin();
				break;

			default:
				trigger_error("Unknown Data Type ( Type: " . $type . ")", E_USER_ERROR);
		}
		return $stylevarobj;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 89092 $
|| #######################################################################
\*=========================================================================*/
