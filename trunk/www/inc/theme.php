<?php
/**
    Irondata
    Copyright (C) 2009  Evan Leybourn, Tobias Snoad

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * theme.php
 *
 * Manages all the theme settings and other information
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class Theme {
	
	function get_theme() {
		/* TODO: Implement themes properly */
		return 'default';
	}
	
	function get_theme_path() {
		return 'themes/'.$this->get_theme().'/';
	}
	
	function webroot() {
		$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : "";
		$count = strlen($url);
		$webroot = substr(urldecode($_SERVER['REQUEST_URI']), 0, ($count*-1));

		if (!empty($webroot)) {
			//cull extra slashes from the url
			$webroot = rtrim($webroot, "/");
			$webroot .= "/";
		}
		return $webroot;
	}

	/**
	 * Out the HTML to display an error message
	 *
	 * @param string $msg The message to display
	 * @return A HTML string
	 */
	function error($msg) {
		$string = "<h2 class='error'>Warning: ".$msg."</h2>";
		return $string;
	}
	
	function render_menu($steps) {
		$submenu = "";
		if (!empty($steps)) {
			$submenu .= "<ol>";
			foreach ($steps as $i => $step) {
				$step[3] = isset($step[3]) ? $step[3] : null;
				$submenu .= "<li>";
				$submenu .= ($i + 1 === 1 ? "Step " : "").($i + 1).". ";
				$submenu .= "<a href=\"".$step[1]."\" class=\"".$step[3]."\" ".(!$step[2] ? "onClick=\"void(0); return false;\"" : "").">";
				$submenu .= ucwords($step[0]);
				$submenu .= "</a>";
				$submenu .= "</li>";
			}
			$submenu .= "</ol>";
		}
		return $submenu;
	}
	
	function render_innermenu($marray) {
		$string = '<ul dojoType="dojo.dnd.Source" copyOnly="true">';
		foreach ($marray as $i => $mod) {
			if (is_array($mod)) {
				if (array_key_exists("link", $mod)) {
					if ($mod['type'] == 'dnd') {
						$dojo = " dojoDndItem";
					} else {
						$dojo = "";
					}
					$string .= "<li class='".$mod['class']." ".$dojo." ".$mod['active']."'>";
					if ($mod['link']) {
						$string .= "<a href='".$this->webroot().$mod['module']."/".$mod['link']."'>".$mod['name']."</a>";
					} else {
						$string .= $mod['name'];
					}
					$string .= "</li>";
				} elseif (array_key_exists("block", $mod)) {
					$string .= $mod['block'];
				} else {
					$string .= $this->render_innermenu($mod);
				}
			}
		}
		$string .= "</ul>";
		return $string;
	}
	
	function render($display, $data) {
		$theme = $this->get_theme();
		$webroot = $this->webroot();
		$themeroot = $webroot.'themes/'.$theme.'/';
		$scriptroot = $webroot.'scripts/';
		$main = "";
		
		//setup display variables
		if (!isset($display->js)) {
			$display->js = null;
		}
		if (!isset($display->js_href)) {
			$display->js_href = null;
		}
		if (!isset($display->pagetitle)) {
			$display->pagetitle = null;
		}
		if (!isset($display->top)) {
			$display->top = null;
		}
		if (!isset($data->title)) {
			$data->title = null;
		}

		// create the main output string
		if (isset($display->submenu)) {
			$main .= $display->submenu;
		}
		
		/* Wrap the elements in tags */
		if (isset($data->title) && !empty($data->title_desc)) {
			$main .= "<h2>".ucwords($data->title)."</h2>";

			if (isset($data->title_desc) && !empty($data->title_desc)) {
				$main .= "<p class='h2attach'>".$data->title_desc."</p>";
			}
		}

		if (isset($data->data)) {
			$main .= $data->data;
		}

		if (isset($data->layout) && $data->layout == 'ajax') {
			echo $main;
		} else {
			require_once('themes/'.$theme.'/html.inc');
		}
		return true;
	}
	
	/**
	 * Dynamically create a HTML link. Utilises the correct webroot for internal links
	 *
	 * @param string $url The main url of the link
	 * @param string $title The visible title of link
	 * @param string $misc Any additional parameters
	 * @param boolean $internal Is it an internal or external link
	 */
	function l($url, $title, $misc=false, $internal=true) {
		if ($internal) {
			$path = $this->webroot();
		} else {
			$path = "";
		}

		return "<a $misc href='".$path.$url."'>".$title."</a>";
	}

	function p($text, $class=null) {
		if (!empty($class)) {
			$class = "class='$class'";
		}

		return "<p $class>$text</p>";
	}

	function img($image, $alt='') {
		$theme = $this->get_theme();
		$webroot = $this->webroot();
		$themeroot = $webroot.'themes/'.$theme.'/';
		return "<img src='".$themeroot."images/".$image."' alt='".$alt."' title='".$title."'/>";
	}
		
	function lp($url, $title, $data) {
		$attrs = "";
		foreach ($data as $i => $string) {
			$attrs .= $i."='".$string."'";
		}
		return "<a ".$attrs." href='javascript:load_dialog(\"".$this->webroot().$url."\", \"".$title."\")'>".$title."</a>";
	}

	function la($url, $title, $div, $data=array()) {
		// Turn the data array into json
		$attrs = "{";
		foreach ($data as $i => $string) {
			$attrs .= $i.":'".$string."',";
		}
		$attrs = trim($attrs, ",")."}";
		return "<a ".$attrs." href='javascript:ajax_load(\"".$this->webroot().$url."\", ".$attrs.", \"".$div."\")'>".$title."</a>";
	}

	function f($url, $data="") {
		return "<form method='post' action='".$this->webroot().$url."' $data >";
	}
	
	function f_close() {
		return "</form>";
	}
	
	function i($name, $data=array()) {
		//setup variables
		$div_id = "";
		$classes = "";
		$attrs = "";
		$label = "";
		if (!array_key_exists('label', $data)) {
			$data['label'] = ucwords($name);
		}
		if (!array_key_exists('type', $data)) {
			$data['type'] = null;
		}
		if (!array_key_exists('default', $data)) {
			$data['default'] = null;
		}
		
		if ($data['label']) {
			if ($data['type'] == "button" || $data['type'] == "submit") {
				$label = $data['label'];
			} else {
				if ($data['label'] != "&nbsp;" && $data['type'] != "checkbox" && $data['type'] != "radio") {
					$data['label'] .= ": ";
				}

				$label = "<label for='".$name."' >".$data['label']."</label>";
			}
		}
		foreach ($data as $i => $string) {
			switch ($i) {
				case "type":
				case "label":
				case "options":
				case "default":
					break;
				case "disabled":
					if ($string == "t" || $string == "true" || $string == "disabled") {
						$attrs .= "disabled ";
						$classes .= "disabled";
					}
					break;
				case "dojo":
					$attrs .= "dojoType='".$string."'";
					break;
				case "div_id":
					$div_id = "id='".$string."'";
					break;
				case "class":
					$classes .= $string;
					$attrs .= $i."='".$string."'";
					break;
				default:
					$attrs .= $i."='".$string."'";
					break;
			}
		}
		switch ($data['type']) {
			case 'select':
				$input = "<div $div_id class='input $classes'>".$label."<select ".$attrs." name='".$name."' >";
				if (is_array($data['options'])) {
					foreach ($data['options'] as $i => $option) {
						if ($data['default'] == $i) {
							$sel = "selected";
						} else {
							$sel = "";
						}

						if (isset($data['options_disabled'][$i]) && $data['options_disabled'][$i] === true) {
							$dis = "disabled='true'";
						} else {
							$dis = "";
						}

						$input .= "<option $sel $dis value='$i'>$option</option>";
					}
				}
				$input .= "</select></div>";
				break;
			case 'checkbox':
				if ($data['default'] === true || $data['default'] === $name || $data['default'] === 't') {
					$check = "checked";
				} else {
					$check = "";
				}
				$input = "<div $div_id class='input checkbox $classes'><input type='checkbox' ".$attrs." name='".$name."' ".$check." />".$label."</div>";
				break;
			case 'radio':
				if ($data['default'] == true) {
					$check = "checked";
				} else {
					$check = "";
				}
				$input = "<div $div_id class='input radio $classes'><input type='radio' $attrs name='$name' $check />$label</div>";
				break;
			case 'submit':
				$input = "<div $div_id class='input $classes'><button type='submit' ".$attrs." name='".$name."' >".$label."</button></div>";
				break;
			case 'button':
				$input = "<div $div_id class='input $classes'><button ".$type." ".$attrs." name='".$name."' >".$label."</button></div>";
				break;
			case 'hidden':
				$input = "<input type='hidden' name='".$name."' value='".$data['default']."' ".$attrs." />";
				break;
			case 'password':
				$input = "<div $div_id class='input $classes'>".$label."<input type='password' ".$attrs." name='".$name."' value='".$data['default']."' /></div>";
				break;
			case 'wysiwyg':
				if (empty($data['parent_form'])) return;

				$input = "
					<script type='text/javascript'>
						dojo.require('dijit._editor.plugins.TextColor');
						dojo.require('dijit._editor.plugins.FontChoice');
						dojo.addOnLoad(function () {
							dojo.connect(dojo.byId('".$data['parent_form']."'), 'onsubmit', function () {
								dojo.byId('".$name."_hidden').value = dijit.byId('".$name."_editor').getValue(false);
							});
						});
					</script>
					<input type='hidden' id='".$name."_hidden' name='".$name."' />
					<div $div_id class='input $classes'>".$label."<div id='".$name."_editor' dojoType='dijit.Editor' ".$attrs." plugins=\"[
						'undo',
						'redo',
						'|',
						'cut',
						'copy',
						'paste',
						'|',
						{name:'dijit._editor.plugins.FontChoice', command:'fontName', custom: ['', 'Andale Mono', 'Arial', 'Comic Sans MS', 'Courier New', 'Georgia', 'Impact', 'Times New Roman', 'Trebuchet MS', 'Verdana']},
						{name:'dijit._editor.plugins.FontChoice', command:'fontSize', custom: ['', '1', '2', '3', '4', '5', '6', '7']},
						'foreColor',
						'bold',
						'italic',
						'underline',
						'strikethrough',
						'|',
						'justifyLeft',
						'justifyRight',
						'justifyCenter',
						'justifyFull'
						]\">".$data['default']."</div></div>";
				break;
			default:
				$input = "<div $div_id class='input $classes'>".$label."<input type='text' ".$attrs." name='".$name."' value='".$data['default']."' /></div>";
				break;
		}
		return $input;
	}
	
	function b($name, $data=array()) {
		if (!array_key_exists('label', $data)) {
			$data['label'] = ucwords($name);
		}
		$attrs = "";
		foreach ($data as $i => $string) {
			switch ($i) {
				case "label":
					break;
				case "dojo":
					$attrs .= "dojoType='".$string."'";
					break;
				case "href":
					$attrs .= "onClick='window.location=\"".$this->webroot().$string."\"'";
					break;
				default:
					$attrs .= $i."='".$string."'";
					break;
			}
		}
		$input = "<button ".$attrs." >".$data['label']."</button>";
		return $input;
	}
	
	function submit($label) {
		$input = "<div class='input'><input type='submit' value='".$label."' /></div>";
		return $input;
	}
	
	function redirect($url) {
		header("Location: ".$this->webroot().$url);
		die();
	}

	function theme_metabase($object) {
		$theme = $this->get_theme();
		$webroot = $this->webroot();
		$themeroot = $webroot.'themes/'.$theme.'/';

		$output = "";

		$name = $object['human_name'] ? $object['human_name'] : ($object['name'] ? $object['name'] : "&nbsp;");
		$details = $object['description'] ? $object['description'] : "&nbsp;";
		$host = $object['host'];
		$records = $object['records'] ? $object['records'] : "Uncounted";

		$output .= "
			<tr>
				<td>".$name."</td>
				<td>".$details."</td>
				<td>".$host."</td>
				<td>
					<ul>
						<li>".$this->l("catalogue/edit/".$object['database_id'], "Edit")."</li>
						<li>".$this->l("catalogue/remove/".$object['database_id'], "Remove", "onclick='if (confirm(\"Remove database?\")) {return true;} else {return false;}'")."</li>
					</ul>
				</td>
			</tr>
			";
		return $output;
	}

	function quick_table($data, $format, $functions=array()) {
		if ($functions['form']) {
			$output .= $this->f($functions['form']);
		}

		$output .= "
			<table class=\"report_table\" cellspacing=\"0px\" cellpadding=\"0px\">
			";
		if ($functions['add']) {
			$output .= "<tr class=\"add_row\">
					<td class=\"icon_case\">
						<div class=\"report_add\"></div>
					</td>
					<th colspan=\"7\">".$this->l($functions['add'], "Add")."</th>
				</tr>
				<tr class=\"report_spacer_row\">
					<td></td>
				</tr>
			";
		}
		foreach ($data as $i => $row) {
			$output .= "
				<tr class=\"report_row\">
					<td class=\"icon_case\">
						<div class=\"report_icon\"></div>
					</td>
					";
			foreach ($row as $j => $value) {
				if ($format[$j]['header']) {
					$output .= "<td class=\"stat_case\">".$format[$j]['header'].":<br /><span style=\"font-weight: bold;\">".$value."</span></td>";
				} elseif ($format[$j]['type'] == "title") {
					$output .= "<th>".$value."</th>";
				} else {
					$output .= "<td>".$value."</td>";
				}
			}
			$output .= "<td class=\"tool_case\">
					<div>
					";
			if ($functions['edit']) {
				$output .= "".$this->l($functions['edit']."/".$row[$functions['id']], "<span>Edit</span>", "class=\"edit_button\"")."";
			}
			if ($functions['delete']) {
// 				$output .= "".$this->l("#", "<span>Delete</span>", "class=\"delete_button\" onClick='if (confirm(\"Are you sure you want to delete this\")) { window.location=\"".$this->webroot().$functions['delete']."/".$row[$functions['id']]."\"; return false;} else {return false;};'");
				$output .= "".$this->l($functions['delete']."/".$row[$functions['id']], "<span>Delete</span>", "class=\"delete_button\" onClick='if (confirm(\"Are you sure you want to delete this\")) {} else {return false;};'");
			}
			$output .= "
						</div>
					</td>
				</tr>
				<tr class=\"report_spacer_row\">
					<td></td>
				</tr>
				";
		}
		if ($functions['form']) {
			$output .= $this->i("save", array("type"=>"submit", "label"=>"Save Modules"));
			$output .= $this->f_close();
		}

		return $output;

		$output->data .= "
			</table>
			";
		
		$output .= "<table>";
		/* Build Header */
		$output .= "<tr>";
		foreach ($format as $i => $header) {
			if ($header['type'] == "hidden") {
				continue;
			}
			$output .= "<th>".$header['header']."</th>";
		}
		$output .= "</tr>";
		foreach ($data as $i => $row) {
			$output .= "<tr>";
			foreach ($row as $j => $cell) {
				if ($format[$j]["type"] == "hidden") {
					continue;
				}
				$output .= "<td>".$cell."</td>";
			}
			$output .= "</tr>";
		}
		$output .= "<table>";
		$output .= "</div>";
		return $output;
	}
	
	function default_header() {
		return "
			%logo<br />
			<br />
			<strong>%name</strong><br />
			%desc
			";
		
	}
	
	function default_footer() {
		return "
			Run at: %run<br />
			By user: %by<br />
			Records returned: %size";
	}

	/**
	 * Create the list of links for the top menu
	 *
	 * @param array $menu_items An array of arrays, 0=>the array link, 1=> which block to put it in
	 * @return The menu HTML string
	 */
	function render_top_menu($menu_items) {
		$output = "
			<ul class='menu'>
			";
		
		foreach ($menu_items as $i => $module) {
			foreach($module as $j => $menu) {
				switch ($menu[1]) {
					case 2:
						$block2[] = "<li>$menu[0]</li>";
						break;
					case 3:
						$block3[] = "<li>$menu[0]</li>";
						break;
					case 4:
						$block4[] = "<li>$menu[0]</li>";
						break;
					default:
					case 1:
						$block1[] = "<li>$menu[0]</li>";
						break;
				}
			}
		}

		if (!empty($block1)) {
			$blocks[] = implode("", $block1);
		}

		if (!empty($block2)) {
			$blocks[] = implode("", $block2);
		}

		if (!empty($block4)) {
			$blocks[] = implode("", $block4);
		}

		if (!empty($blocks)) {
			$output .= implode("<li>&#x2766;</li>", $blocks);
		}

		$output .= "
			</ul>
			";

		return $output;
	}

	function render_acl($things, $ids_r, $groups, $users, $permissions, $user_groups, $disabled, $titles, $rows) {
		$inherit_permission_keys = array_keys($users);
		$inherit_permission_vals = array_pad(array(), count($inherit_permission_keys), array(false, false, false));
		$inherit_permissions = array_combine($inherit_permission_keys, $inherit_permission_vals);

		$things_json = array_combine(array_keys($things), array_keys($things));
		
		$output = "
			<input type='hidden' name='data[ids_r]' value='".json_encode($ids_r)."' />
			";

		$output .= "
			<script>
				function update_inherited() {
					//data to work out group membership is placed here by php
					var things = ".json_encode($things_json).";
					var groups = ".json_encode($groups).";
					var users = ".json_encode($users).";
					var user_groups = ".json_encode($user_groups).";
					//empty, defined array, ready for us to fill
					var inherit_permissions = ".json_encode($inherit_permissions).";

					//for each user
					for (var i in users) {
						//for each group the user is member of
						for (var j in user_groups[i]) {
							//for each of the permission types
							for (var k in things) {
								//if this group gives the user permission, mark it as so in the inherit_permissions array
								if (dojo.byId('access_'+things[k]+'_'+user_groups[i][j]).checked) {
									inherit_permissions[i][k] = true;
								}
							}
						}

						//for each of the permission types
						for (var k in things) {
							//if this user has inherited permission for this type, check and show the inherited checkbox
							if (inherit_permissions[i][k]) {
								dojo.byId('access_'+things[k]+'_inherit_'+i).checked = true;
								dojo.byId('access_'+things[k]+'_inherit_'+i).parentNode.style.visibility = 'visible';
							//if not, hide and uncheck
							} else {
								dojo.byId('access_'+things[k]+'_inherit_'+i).parentNode.style.visibility = 'hidden';
								dojo.byId('access_'+things[k]+'_inherit_'+i).checked = false;
							}
						}
					}
				}

				dojo.addOnLoad(update_inherited);
			</script>
			<div class='reports'>
				<table cellpadding='0' cellspacing='0'>
					<tr>
					";
		foreach ($titles as $title) {
			$output .= "
						<th>$title</th>
						";
		}

		foreach ($things as $thing) {
			$output .= "
						<th>".ucwords($thing[0])."</th>
					";
		}

		$output .= "
					</tr>
					";

		foreach (array("Group"=>$groups, "User"=>$users) as $users_meta_key => $users_meta) {
			foreach ($users_meta as $user_id => $user) {
				$output .= "
					<tr>
					";

				$rows[$user_id] = array_pad((array)$rows[$user_id], count($titles), "");

				foreach ($rows[$user_id] as $row) {
					$output .= "
						<td>$row</td>
						";
				}

				foreach ($things as $thing_id => $thing) {
					$checkbox = "<input id='access_".$thing_id."_$user_id' name='data[access_".$thing_id."_$user_id]' type='checkbox' ".(isset($permissions[$user_id][$thing_id]) ? "checked='true'" : "")." ".(isset($disabled[$user_id]) ? "disabled='true'" : "")." onchange='update_inherited();' title='".ucwords($thing[0])."' />";
					$checkbox .= "<span style='visibility: hidden; padding-left: 10px;'>&#x21b3;<input id='access_".$thing_id."_inherit_$user_id' type='checkbox' disabled='true' /></span>";

					$output .= "
						<td>$checkbox</td>
						";
				}

				$output .= "
					</tr>
					";
			}
		}

		$output .= "
				</table>
			</div>
			";

		return $output;
	}

	/**
	 * Render the source column input as used on the edit intersection, x axis, etc, pages
	 *
	 * @param string $name The message to display
	 * @param array $column_data The message to display
	 * @param string $default_column_id The message to display
	 * @param string $onchange The message to display
	 * @return A HTML string
	 */
	function source_column_i($name, $column_data, $default_column_id=null, $onchange="") {
		$output = "<div style='position: relative;'>";

		$output .= "<div style='width: 130px; height: 200px; position: absolute;'>";
		$output .= "<div style='padding-top: 20px;'>Source Column:</div>";
		$output .= "</div>";
		$output .= "<div style='height: 200px; overflow-y: scroll; margin-left: 130px; padding: 10px 20px; border: 1px solid #d3d7cf;'>";


		$last_table_id = "";
		
		foreach ($column_data as $option) {
			if ($last_table_id != $option['table_id']) {
				if ($option != reset($column_data)) {
					$output .= "<hr style='' />";
				}
		
				$output .= "<div style='font-size: 14pt;'>".$option['table_name']."</div>";
				$output .= "<div style='margin-left: 20px; margin-top: 5px; font-size: 10pt; font-style: italic;'>".$option['table_description']."</div>";
				$output .= "<div style='margin-left: 20px; margin-top: 10px; font-size: 8pt;'>Columns:</div>";
			}

			$output .= "<div class='input radio'>";
			$output .= "<input type='radio' name='data[column_id]' value='".$option['column_id']."' onchange='$onchange' />";
			$output .= "<label for='data[column_id]' >".$option['column_name']."</label>";
			$output .= "</div>";

			$output .= "<p>".$option['column_description']."</p>";

			if (!empty($option['example'])) {
				$option['example'] = str_replace(", ", ",", $option['example']);
				$option['example'] = str_replace(",", "<span style='color: #888a85;'>, </span>", $option['example']);

				$output .= "<p style='margin-top: 0px; color: #888a85;'>example values: <span style='color: #555753; font-size: 9pt; font-style: normal;'>".$option['example']."</span></p>";
			}
		
			$last_table_id = $option['table_id'];
		}
		
		$output .= "</div>";
		$output .= "</div>";
		if ($default_column_id != null) {
			$output .= "<script>
				dojo.addOnLoad(selectSourceColumnDefault);
				function selectSourceColumnDefault() {
					inputs = document.getElementsByName('data[column_id]');
					for (i = 0; i<inputs.length; i++) {
						if (inputs[i].value == '".$default_column_id."') {
							inputs[i].checked = true;
							inputs[i].onchange();
						}
					}
				}
			</script>";
		}

		return $output;
	}
}

?>
