<?php

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
	
	function webroot() {
		$url = $_REQUEST['url'];
		$count = strlen($url);
		$webroot = substr(urldecode($_SERVER['REQUEST_URI']), 0, ($count*-1));

		if (!empty($webroot)) {
			//cull extra slashes from the url
			$webroot = rtrim($webroot, "/");
			$webroot .= "/";
		}
		return $webroot;
	}

	function render_menu($marray) {
		if (!$marray) {
			return false;
		}
		$string .= "<div dojoType='dijit.layout.AccordionContainer' duration='200' style='width: 200px; height: 300px; overflow: hidden'> ";
		if (is_array($marray)) {
			foreach ($marray as $i => $mod) {
				$string .= "<div dojoType='dijit.layout.AccordionPane' title='".$i."'> ";
				$string .= $this->render_innermenu($mod);
				$string .= "</div>";
			}
		}
		$string .= "</div>";
		return $string;
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

		if ($data->submenu) {
			$main .= $data->submenu;
		}
		
		/* Wrap the elements in tags */
		if ($data->title) {
			$main .= "<h2>".ucwords($data->title)."</h2>";

			if ($data->title_desc) {
				$main .= "<p class='h2attach'>".$data->title_desc."</p>";
			}
		}

		$main .= $data->data;

		if ($data->layout == 'ajax') {
			echo $main;
		} else {
			require_once('themes/'.$theme.'/html.inc');
		}
		return true;
	}
	
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
		if (!array_key_exists('label', $data)) {
			$data['label'] = ucwords($name);
		}
		if ($data['label']) {
			if ($data['type'] == "button" || $data['type'] == "submit" ) {
				$label = $data['label'];
			} else {
				if ($data['label'] != "&nbsp;" && $data['type'] != "checkbox" && $data['type'] != "radio") {
					$data['label'] .= ": ";
				}

				$label = "<label for='".$name."' >".$data['label']."</label>";
			}
		}
		$attrs = "";
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

						if ($data['options_disabled'][$i] === true) {
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
				if ($data['default'] == true) {
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
				$input = "<div $div_id class='input $classes'><button type='submit' ".$attrs." name='".$name."' />".$label."</button></div>";
				break;
			case 'button':
				$input = "<div $div_id class='input $classes'><button ".$type." ".$attrs." name='".$name."' />".$label."</button></div>";
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
		return "<table width='100%'><tr>
		<td style='width: 49%;'>%logo</td>
		<td style='width: 49%; text-align: right;'><strong>%name</strong><br />
		%desc<br />
		</td>
		</tr></table>";
		
	}
	
	function default_footer() {
		return "<p>%run - %by - %size records</p>";
	}

	function render_top_menu($menu_items) {
		$output .= "
			<ul class='menu'>
			";

		$reports_item = $menu_items['template']['reports'];
		$help_item = $menu_items['help']['help'];
		$search_item = $menu_items['search']['search'];
		$admin_item = $menu_items['admin']['admin'];
		$databases_item = $menu_items['catalogue']['databases'];
		$logout_item = $menu_items['user']['logout'];

		if (!empty($reports_item)) {
			$block1[] = "<li>$reports_item</li>";
		}

		if (!empty($help_item)) {
			$block1[] = "<li>$help_item</li>";
		}

		if (!empty($search_item)) {
			$block1[] = "<li>$search_item</li>";
		}

		if (!empty($admin_item)) {
			$block2[] = "<li>$admin_item</li>";
		}

		if (!empty($databases_item)) {
			$block2[] = "<li>$databases_item</li>";
		}

		if (!empty($logout_item)) {
			$block4[] = "<li>$logout_item</li>";
		}

		if (!empty($block1)) {
			$output .= "<li>".implode("", $block1)."</li>";
		}

		if (!empty($block1) && !empty($block2)) {
			$output .= "<li>&#x2766;</li>";
		}

		if (!empty($block2)) {
			$output .= "<li>".implode("", $block2)."</li>";
		}

		if (!empty($block2) && !empty($block4)) {
			$output .= "<li>&#x2766;</li>";
		}

		if (!empty($block4)) {
			$output .= "<li>".implode("", $block4)."</li>";
		}

		$output .= "
			</ul>
			";

		return $output;
	}

	function render_acl($things, $groups, $users, $permissions, $user_groups, $disabled, $titles, $rows) {
		$inherit_permission_keys = array_keys($users);
		$inherit_permission_vals = array_pad(array(), count($inherit_permission_keys), array(false, false, false));
		$inherit_permissions = array_combine($inherit_permission_keys, $inherit_permission_vals);

		$output .= "
			<script>
				function update_inherited() {
					//data to work out group membership is placed here by php
					var things = ".json_encode($things).";
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
						<th>".ucwords($thing)."</th>
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
					unset($checkbox);

					$checkbox .= "<input id='access_".$thing."_$user_id' type='checkbox' ".($permissions[$user_id][$thing_id] ? "checked='true'" : "")." ".($disabled[$user_id] ? "disabled='true'" : "")." onchange='update_inherited();' />";
					$checkbox .= "<span style='visibility: hidden; padding-left: 10px;'>&#x21b3;<input id='access_".$thing."_inherit_$user_id' type='checkbox' disabled='true' /></span>";

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
}

?>
