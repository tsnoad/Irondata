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
 * admin.php
 *
 * The administration module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 * 
 */

class Admin extends Modules {
	var $dobj;
	var $name = "IronData Administration";
	var $description = "This is the administration module.";
	var $module_group = "Core";

	function __construct() {
		include_once("inc/db.php");
		$this->dobj = new DB();
	}

	function hook_top_menu() {
		return array(
			"admin" => $this->l("admin/home", "Admin")
			);
	}
	
	function hook_pagetitle() {
		return "Administration";
	}

// 	function hook_workspace() {
// 		return array("title"=>"Administration Tools", "path"=>"".$this->webroot()."admin/tools");
// 	}

// 	function hook_module_settings() {
// 		return "admin/settings";
// 	}

	function hook_admin_tools() {
// 		$admin_tools[] = array("admin/settings", "Settings");
		$admin_tools[] = array("admin/modules", "Modules");
		return $admin_tools;
	}
	
	function view_run_background() {
		parent::run_background();
	}

	function view_home () {
		return $this->view_tools();
	}

	function view_tools() {
		//find any tools to show for other modules
		$admin_tools_query = $this->call_function("ALL", "hook_admin_tools");

		//setup for groovy array merging
		$admin_tools = array();

		//because each module will return an array of tools, call_func will return an array of arrays. make useable.
		foreach ($admin_tools_query as $admin_tool_tmp) {
			$admin_tools = array_merge((array)$admin_tools, (array)$admin_tool_tmp);
		}

		return Admin_View::view_tools($admin_tools);
	}

	function view_settings() {
		if ($_REQUEST['data']) {
			$this->save_settings('admin', $_REQUEST['data']['admin']);
		}
		$modules = $this->get_settings('admin');
		$output = Admin_View::view_settings($modules);
		return $output;
	}

	function view_modules() {
		$modules = $this->getDetailed();
		$settings = $this->call_function("ALL", "hook_module_settings");
		if (isset($_REQUEST['data']['modules'])) {
			foreach ($modules as $j => $all) {
				/* Install selected modules */
				if ($all->status == false && isset($_REQUEST['data']['modules'][$j])) {
					/* New module */
					$query = "INSERT INTO modules (module_id, name, description, type, subtype, status, core) VALUES ($$".$j."$$, $$".$all->name."$$, $$".$all->description."$$, $$".$all->type."$$, $$".$all->subtype."$$, 'active', 'f')";
					$all->status = 'active';
					$this->dobj->db_query($query);
				} elseif ($all->status != 'active' && isset($_REQUEST['data']['modules'][$j])) {
					$query = "UPDATE modules SET status='active' WHERE module_id='".$j."'";
					$all->status = 'active';
					$this->dobj->db_query($query);
				} elseif ($all->status == 'active' && $all->core == 'f' && !isset($_REQUEST['data']['modules'][$j])) {
					/* Uninstall unselected modules */
					$query = "UPDATE modules SET status='inactive' WHERE module_id='".$j."'";
					$all->status = 'inactive';
					$this->dobj->db_query($query);
				}
			}
		}
		/* Sort modules by group */
		$sorted = array();
		foreach ($modules as $i => $module) {
			$group = isset($module->module_group) ? $module->module_group : "Other";
			$sorted[$group][$i] = $module;
		}
		ksort($sorted);
		$output = Admin_View::view_modules($sorted, $settings);
		return $output;
	}
	
}

class Admin_View {
	function view_settings($modules) {
		$output->title = "Settings";
		$output->data .= "<div dojoType='dijit.layout.ContentPane' layoutAlign='client'>";
		$output->data .= $this->f("admin/settings");
		foreach ($modules as $j => $all) {
			$output->data .= $this->i("data[".$all['module_id']."][".$all['key']."]", array("label"=>ucwords($all['key']), "default"=>$all['value']));
		}
		$output->data .= $this->i("save", array("type"=>"submit", "label"=>"Save Settings"));
		$output->data .= $this->f_close();
		$output->data .= "</div>";
		return $output;
	}

	function view_modules($modules, $settings) {
		$output->title = "Modules";
		$output->data .= $this->f("admin/modules");
		$output->data .= "<div class='reports'>";
		foreach ($modules as $group_name => $group) {
			$output->data .= "<h3>".$group_name."</h3>";
			$output->data .= "
					<table cellpadding='0' cellspacing='0'>
						<tr>
							<th style='width: 25%;'>Module Name</th>
							<th style='width: 60%;'>Description</th>
							<th style='width: 10%;'>Installed</th>
							<th style='width: 5%;'>&nbsp;</th>
						</tr>
						";
			
			foreach ($group as $i => $mod) {
				$default = ($mod->status == 'active') ? true : false;
				$disabled = (isset($mod->core) && $mod->core == 't') ? true : false;
				$setting = isset($settings[$i]) ? $settings[$i] : null;

				$output->data .= "
						<tr>
							<td>".$mod->name."</td>
							<td>".$mod->description."</td>
							<td><input type='checkbox' name='data[modules][".$i."]' ".($disabled ? "disabled" : "")." ".($default ? "checked" : "")." /></td>
							<td>".($setting ? $this->l($setting, "Module Settings") : "&nbsp;")."</td>
						</tr>
						";
			}
			$output->data .= "</table>";
		}
		$output->data .= "				
			</div>
			";

		$output->data .= $this->i("save", array("label"=>"Save Modules", "type"=>"submit", "value"=>"Save Modules", "dojoType"=>"dijit.form.Button"));
		$output->data .= $this->f_close();
		return $output;
	}
	
	function view_tools($admin_tools) {
		$output->title = "Tools";
		$output->data .= "<ul>";

		foreach ($admin_tools as $admin_tool) {
			$output->data .= "<li>".$this->l($admin_tool[0], $admin_tool[1])."</li>";
		}

		$output->data .= "</ul>";
		return $output;
	}
}
?>
