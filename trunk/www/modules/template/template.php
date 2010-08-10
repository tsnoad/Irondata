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
 * template.php
 *
 * The template module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class Template extends Modules {
	var $current;
	var $id;
	var $module;
	var $action;
	var $subvar;
	var $subid;
	var $dobj;
	var $name = "Template";
	var $description = "Report template functions.";
	var $module_group = "Core";
	
	function __construct() {
		parent::__construct();
		$this->web_path = $this->conf['paths']['web_path'];
		$this->sw_path = $this->conf['paths']['sw_path'];
		$this->tmp_path = $this->conf['paths']['tmp_path'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_permission_check()
	 */
	function hook_permission_check($data) {
		//admin will automatically have access. No need to specify
		switch ($data['function']) {
			case "hook_admin_tools":
			case "hook_roles":
				if (isset($data['acls']['system']['admin'])) {
					return true;
				}
				break;
			case "hook_pagetitle":
			case "hook_top_menu":
			case "hook_javascript":
			case "hook_workspace":
			case "hook_menu":
			case "view_home":
				// these can be called by other modules
				if (isset($data['acls']['system']['login'])) {
					return true;
				}
				return false;
				break;
			case "view_delete":
			case "hook_access_report_acls":
			case "hook_access_report_submit":
				// these permissions are based on the report id and the histories permission
				if (isset($data['acls']['report'][$this->id]['edit'])) {
					return true;
				}
				break;
			case "view_add":
			case "view_add_select_object":
			default:
				//only people with permission to create reports can access these functions
				if (isset($data['acls']['system']['reportscreate'])) {
					return true;
				}
				//or users with permission to edit a specific report
				if (isset($data['acls']['report'][$this->id]['edit'])) {
					return true;
				}
				return false;
				break;
		}
		return false;
	}
	
	/**
	 * Returns the page title for this module
	 */
	function hook_pagetitle() {
		return "Report";
	}

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_workspace()
	 */
	function hook_workspace() {
		return array("title"=>"Report Workspace", "path"=>"".$this->webroot()."template/workspace_display");
	}

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_roles()
	 */
	function hook_roles() {
		return array(
			"reportscreate" => array("Create Reports", "")
			);
	}

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_admin_tools()
	 */
	function hook_admin_tools() {
		//TODO: What does this do?
		$admin_tools = array();
// 		$admin_tools[] = array("template/default_access", "Default Report Access - New User");
// 		$admin_tools[] = array("template/default_access", "Default Report Access - New Report");
		return $admin_tools;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_top_menu()
	 */
	function hook_top_menu() {
		return array(
			"reports" => array($this->l("template/home", "Reports"), 1)
			);
	}

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_menu()
	 */
	function hook_menu($pre = true, $con = true, $pub = true, $exe = true, $acc = true) {
		$type = $this->module;
		$steps = array();
		// The following javascript is only for template modules. Not for the template itself.
		if ($type != "template") {
			$steps[0][0] = "Preview";
			$steps[0][1] = $this->webroot().$type."/add/".$this->id."/preview";
			$steps[0][2] = $pre;
			$steps[0][3] = "";
			if (!$steps[0][2]) $steps[0][3] = "disabled";
			if ($this->subvar == "preview") $steps[0][3] .= " current";
			
			$steps[1][0] = "Constraints";
			$steps[1][1] = $this->webroot().$type."/add/".$this->id."/constraints";
			$steps[1][2] = $con;
			$steps[1][3] = "";
			if (!$steps[1][2]) $steps[1][3] = "disabled";
			if ($this->subvar == "constraints") $steps[1][3] .= " current";
			
			$steps[2][0] = "Publishing";
			$steps[2][1] = $this->webroot().$type."/add/".$this->id."/publish";
			$steps[2][2] = $pub;
			$steps[2][3] = "";
			if (!$steps[2][2]) $steps[2][3] = "disabled";
			if ($this->subvar == "publish") $steps[2][3] .= " current";
			
			$steps[3][0] = "Execution";
			$steps[3][1] = $this->webroot().$type."/add/".$this->id."/execution";
			$steps[3][2] = $exe;
			$steps[3][3] = "";
			if (!$steps[3][2]) $steps[3][3] = "disabled";
			if ($this->subvar == "execution") $steps[3][3] .= " current";
			
			$steps[4][0] = "Access";
			$steps[4][1] = $this->webroot().$type."/add/".$this->id."/access";
			$steps[4][2] = $acc;
			$steps[4][3] = "";
			if (!$steps[4][2]) $steps[4][3] = "disabled";
			if ($this->subvar == "access") $steps[4][3] .= " current";
		}
		return $steps;
	}

	/**
	 * Called by view_add to fetch user and group acls for a given report
	 *
	 * @param int $template_id
	 * @return An array of access control requirements
	 */
	function hook_access_report_acls($template_id) {
		if (empty($template_id)) return;

		$acls_users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT 'user_'||user_id as user_id, role, access FROM report_acls_users WHERE access=true AND template_id='$template_id';"));
		$acls_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT 'user_'||group_id as group_id, role, access FROM report_acls_groups WHERE access=true AND template_id='$template_id';"));

		return array(
			"acls" => array(
				"users" => $acls_users_query,
				"groups" => $acls_groups_query
				)
			);
	}

	/**
	 * Called by view_save to save edited acl for a given report
	 *
	 * @param $acls array The permissions to save
	 * @param $template_id int The template id
	 */
	function hook_access_report_submit($acls, $template_id) {
		//get existing acls from the database
		$acls_users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT user_id as user_id, role, access FROM report_acls_users WHERE access=true AND template_id='$template_id';"));
		$acls_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT group_id as group_id, role, access FROM report_acls_groups WHERE access=true AND template_id='$template_id';"));

		//convert acl into a readable array. from this we remove the aces that are unchanged. then we can delete the aces that are no longer selected
		foreach (array("users" => $acls_users_query, "groups" => $acls_groups_query) as $users_meta_key => $acls_delete_tmp) {
			if (!empty($acls_delete_tmp)) {
				foreach ($acls_delete_tmp as $acl_delete_tmp) {
					if ($users_meta_key == "users") {
						$user_id = $acl_delete_tmp['user_id'];
					} else if ($users_meta_key == "groups") {
						$user_id = $acl_delete_tmp['group_id'];
					}

					if ($user_id == "admin") continue;

					$role_id = $acl_delete_tmp['role'];

					$acls_delete[$users_meta_key][$user_id][$role_id] = true;
				}
			}
		}

		//loop through data we got back from the form
		if (!empty($acls)) {
			foreach ($acls as $users_meta_key => $users) {
				foreach ($users as $user_id => $roles) {
					//if this user isn't a database user, ignore
					if (substr($user_id, 0, 5) != "user_") continue;

					$user_id = substr($user_id, 5);

					foreach ($roles as $role_id => $access) {
						//if the current ace does not exist in the old acl, then add it to the list of new aces
						if (empty($acls_delete[$users_meta_key][$user_id][$role_id])) {
							$acls_insert[$users_meta_key][$user_id][$role_id] = true;
						//otherwise the ace has not been changed: remove it from the list of aces to delete
						} else {
							unset($acls_delete[$users_meta_key][$user_id][$role_id]);
						}
					}
				}
			}
		}

		//loop through the list of aces to remove
		if (!empty($acls_delete)) {
			foreach ($acls_delete as $users_meta_key => $users) {
				foreach ($users as $user_id => $roles) {
					foreach ($roles as $role_id => $access) {
						if (empty($access)) continue;

						if ($users_meta_key == "users") {
							//remove ace from the database
							$this->dobj->db_query("DELETE FROM report_acls_users WHERE user_id='$user_id' AND template_id='$template_id' AND role='$role_id';");
						} else if ($users_meta_key == "groups") {
							//remove ace from the database
							$this->dobj->db_query("DELETE FROM report_acls_groups WHERE group_id='$user_id' AND template_id='$template_id' AND role='$role_id';");
						}
					}
				}
			}
		}

		//loop through the list of aces to add
		if (!empty($acls_insert)) {
			foreach ($acls_insert as $users_meta_key => $users) {
				foreach ($users as $user_id => $roles) {
					foreach ($roles as $role_id => $access) {
						if (empty($access)) continue;

						if ($users_meta_key == "users") {
							//add the ace to the database
							$this->dobj->db_query($this->dobj->insert(array("user_id"=>$user_id, "template_id"=>$template_id, "role"=>$role_id), "report_acls_users"));
						} else if ($users_meta_key == "groups") {
							//add the ace to the database
							$this->dobj->db_query($this->dobj->insert(array("group_id"=>$user_id, "template_id"=>$template_id, "role"=>$role_id), "report_acls_groups"));
						}
					}
				}
			}
		}

		return;
	}
	
	/* The Constraint Options hook function.
	 * Displays the list of possible contraints.
	 */
	function hook_constraint_options() {
		return array(
			"eq"=>"Equals",
			"neq"=>"Does not Equal",
			"lt"=>"Less Than",
			"gt"=>"Greater Than",
			"lte"=>"Less Than or Equal To",
			"gte"=>"Greater Than or Equal To",
			"like"=>"Contains"
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_javascript()
	 */
	function hook_javascript(){
		$type = $this->module;
		$js = "";
		// The following javascript is only for template modules. Not for the template itself.
		if ($type != "template") {
			$js .= "
			
			function update_data_preview_first() {
				dojo.byId('data_preview_load').style.display = 'none';
				dojo.byId('data_preview_loading').style.display = 'block';
				url = '".$this->webroot().$type."/data_preview_first_ajax/".$this->id."';
				data = {};
				div = 'data_preview_first';
	
				var d = dojo.xhrPost({
					url: url,
					handleAs: 'text',
					sync: false,
					content: data,
					// The LOAD function will be called on a successful response.
					load: function(response, ioArgs) {
						if (div) {
							dojo.byId(div).innerHTML = response;
						}
						update_data_preview_slow();
						return response;
					},
					// The ERROR function will be called in an error case.
					error: function(response, ioArgs) {
						console.error(\"HTTP status code: \", ioArgs.xhr.status);
						return response;
					}
				});
			}
			
			function update_data_preview_slow() {
				var saved_report_id = window.document.getElementById('saved_report_id').innerHTML;
				url = '".$this->webroot().$type."/data_preview_slow_ajax/".$this->id."/'+saved_report_id;
				data = {};
				div = 'data_preview_first';
				
				var d = dojo.xhrPost({
					url: url,
					handleAs: 'text',
					sync: false,
					content: data,
					// The LOAD function will be called on a successful response.
					load: function(response, ioArgs) {
						if (response == 'finished') {
							return;
						}
						if (div) {
							dojo.byId(div).innerHTML = response;
						}
						update_data_preview_slow();
					},
					// The ERROR function will be called in an error case.
					error: function(response, ioArgs) {
						console.error(\"HTTP status code: \", ioArgs.xhr.status);
						return response;
					}
				});
			}
			function update_join_display(o) {
				var passContent = {};
				passContent[o.name] = o.value;
				ajax_load('".$this->webroot().$type."/table_join_ajax/".$this->id."', passContent, 'join_display');
			}
			";
		}
		return $js;
	}

	/**
	 * Create the initial report object in the database
	 *
	 * @param $object_id int The database id
	 * @return The report template array
	 */
	function view_add_select_object($object_id) {
		$type = $this->module;
		//create the new template in the database
		$temp =array();
		$temp['name'] = "Unnamed Report - ".date("g:i A l jS F, Y");
		$temp['module'] = $type;
		$temp['object_id'] = $object_id;
		$temp['template_id'] = $this->dobj->nextval("templates");
		$temp['header'] = $this->default_header();
		$temp['footer'] = $this->default_footer();
		$temp['owner'] = $_SESSION['user'];
		$query = $this->dobj->insert($temp, "templates");
		$this->dobj->db_query($query);

		//update the user's report acl: a trigger will have granted them access in the database
		$this->call_function("ALL", "set_session_report_acls", array());
		return $temp;
	}
	
	/**
	 * First point of contact for almost every page, when creating a report.
	 * Runs queries to gather data to display in MODULE_View::view_add().
	 * Takes aguments about which page from the url in the id, subvar, subid, etc variables
	 *
	 * (non-PHPdoc)
	 * @see modules/template/Template::view_add()
	 */
	function view_add() {
		$type = $this->module;
		$output = null;
		$blah = null; //TODO: bad variable name. Must change
		
		if ($type == 'template') {
			// If this is called specifically (when the module is template)
			// It should display the initial add view
			$modules = $this->call_function("ALL", "hook_template_entry");
			$objects = $this->call_function("catalogue", "get_databases");
			$output = Template_View::view_add($objects, $modules);
		} else {
			// Otherwise it should provide standard functions to any module that
			// implements template
			$preview_table = null;
			switch ($this->subvar) {
				case "preview":
					if ((int)$this->id) {
						$output = Template_View::view_add_preview();
					} else {
						//TODO: Preview Error
					}
					break;
				case "constraints":
					if ((int)$this->id) {
						$blah = Template::view_constraints();
						$output = Template_View::view_add_constraints($blah);
					} else {
						//TODO: Constraint Error
					}
					break;
				case "editconstraint":
					if ($this->subid) {
						$blah = array();
						list($blah, $table_join_ajax) = Template::view_editconstraint();
						$output = Template_View::view_add_editconstraints($blah);
					} else {
						//TODO: EditConstraint Error
					}
					break;
				case "publish":
					$template = $this->get_template($this->id);
					$output = Template_View::view_add_publish($template);
					break;
				case "execution":
					$template = $this->get_template($this->id);
					$output = Template_View::view_add_execute($template);
					break;
				case "access":
					$users_query = $this->call_function("ALL", "hook_access_users", array());
					$users_tmp = array();
					$groups_tmp = array();
					$users_groups_tmp = array();
					$disabled_tmp = array();
					foreach ($users_query as $module => $users_query_tmp) {
						$users_tmp = array_merge((array)$users_tmp, (array)$users_query_tmp['users']);
						$groups_tmp = array_merge((array)$groups_tmp, (array)$users_query_tmp['groups']);
						$users_groups_tmp = array_merge((array)$users_groups_tmp, (array)$users_query_tmp['users_groups']);
						$disabled_tmp = array_merge((array)$disabled_tmp, (array)$users_query_tmp['disabled']);
					}
					$acls_query = $this->call_function("ALL", "hook_access_report_acls", array($this->id));
					$acls_tmp = array();
					foreach ($acls_query as $module => $acls_query_tmp) {
						$acls_tmp = array_merge_recursive((array)$acls_tmp, (array)$acls_query_tmp['acls']);
					}
					$roles = array(
						"histories" => array("Histories", ""),
						"edit" => array("Edit", ""),
						"execute" => array("Execute", "")
						);
					list($ids_r, $users, $groups, $user_groups, $disabled, $acls, $membership, $rows) = $this->acl_resort_users($users_tmp, $groups_tmp, $users_groups_tmp, $disabled_tmp, $acls_tmp);
					$titles = array(
						"User",
						"&nbsp;",
						"Memberships"
						);
					$blah['acl_markup'] = $this->render_acl($roles, $ids_r, $groups, $users, $acls, $user_groups, $disabled, $titles, $rows);
					$output = Template_View::view_add_access($blah);
					break;
			}
		}
		return $output;
	}
	
	/**
	 * Called by Template::view_add. Gets all data required to view constraints and constraint logic editor
	 */
	function view_constraints() {
		switch ($this->subid) {
			default:
				$blah['default'] = true;
				break;
		}
		
		if ($this->subvar == "constraints") {
			$squid = false;
		} else if ($this->subid == "squidconstraints") {
			$squid = true;
		}
		
		if (!$squid) {
			$constraints_query = $this->dobj->db_fetch_all($this->dobj->db_query("
				SELECT
					tc.*,
					tcl.*,
					c.name AS column_name,
					t.name AS table_name,
					c.human_name AS column_human_name,
					t.human_name AS table_human_name
				FROM
					template_constraints tc
					INNER JOIN template_constraint_logic tcl ON (tcl.template_id=tc.template_id)
					INNER JOIN columns c ON (c.column_id=tc.column_id)
					INNER JOIN tables t ON (t.table_id=c.table_id)
				WHERE
					tc.template_id='{$this->id}'
				ORDER BY
					tc.template_constraints_id
				;"));
		} else {
			$constraints_query = $this->dobj->db_fetch_all($this->dobj->db_query("
				SELECT
					sc.*,
					scl.*,
					c.name AS column_name,
					t.name AS table_name,
					c.human_name AS column_human_name,
					t.human_name AS table_human_name
				FROM
					tabular_templates_manual_squid_constraints sc
					INNER JOIN tabular_templates_manual_squid_constraint_logic scl ON (scl.tabular_templates_manual_squid_id=sc.tabular_templates_manual_squid_id)
					INNER JOIN columns c ON (c.column_id=sc.column_id)
					INNER JOIN tables t ON (t.table_id=c.table_id)
				WHERE
					sc.tabular_templates_manual_squid_id='{$this->aux1}'
				ORDER BY
					sc.squid_constraints_id
				;"));
		}
		
		if (!empty($constraints_query)) {
			$blah['logic'] = $constraints_query[0]['logic'];
			
			$constraint_index = 0;
			
			foreach ($constraints_query as $constraint_tmp) {
				if (!$squid) {
					$constraint_id = $constraint_tmp['template_constraints_id'];
				} else {
					$constraint_id = $constraint_tmp['squid_constraints_id'];
				}
			
				$index_id = isset($constraint_tmp['index_id']) ? $constraint_tmp['index_id'] : null;
				$table_name = $constraint_tmp['table_name'];
				$column_name = $constraint_tmp['column_name'];
				/* The constraints should show with the human name, not data name */
				$table_human_name = $constraint_tmp['table_human_name'];
				$column_human_name = $constraint_tmp['column_human_name'];
				$column = $table_human_name.".".$column_human_name;
				
				$type_array = array(
					"eq"=>"Equals",
					"neq"=>"Does not Equal",
					"lt"=>"Is Less Than",
					"gt"=>"Is Greater Than",
					"lte"=>"Is Less Than or Equal To",
					"gte"=>"Is Greater Than or Equal To",
					"like"=>"Contains"
					);
				$type = strtolower($type_array[$constraint_tmp['type']]);
				$value = $constraint_tmp['value'];
				
				$constraints[$constraint_index]['constraint_id'] = $constraint_id;
				$constraints[$constraint_index]['index_id'] = $index_id;
				$constraints[$constraint_index]['foobar'] = "constraint";
				$constraints[$constraint_index]['constraint'] = "$column $type '$value'";
				
				$constraint_index ++;
			}
			
			if ($blah['default']) {
				$constraints_query = $constraints;
				$indentation = 0;
				
				foreach ($constraints_query as $constraint_index => $constraint_tmp) {
					$index_id_tmp = $constraint_tmp['index_id'];
					
					$new_constraints[] = array_merge((array)$constraint_tmp, (array)array("indentation" => $indentation));
				}
				
				$constraints = $new_constraints;
			}
			
			$blah['constraints'] = $constraints;
		}
		
		return $blah;
	}
	
	/**
	 * Called by Template::view_save to create a new constraint, or save changes to an existing one. Also used to edit constaints on manual axies
	 */
	function view_editconstraintsubmit() {
		switch ($this->subvar) {
			case "editconstraintsubmit":
				$_REQUEST['data']['template_id'] = $this->id;
				break;
			case "editsquidconstraintsubmit":
				$squid_id = $this->subid;
				$_REQUEST['data']['tabular_templates_manual_squid_id'] = $squid_id;
				break;
		}
		
		$selected_input_id = $_REQUEST['data']['value_input_selected'];
		$selected_input_value = $_REQUEST['data'][$selected_input_id];
		
		$_REQUEST['data']['value'] = $selected_input_value;
		
		foreach (explode(",", $_REQUEST['data']['value_inputs']) as $input_id) {
			unset($_REQUEST['data'][$input_id]);
		}
		
		unset($_REQUEST['data']['value_inputs']);
		unset($_REQUEST['data']['value_input_selected']);
		
		switch ($this->subvar) {
			case "editconstraintsubmit":
				$constraint_id = $this->subid;
				if ($constraint_id == "new") {
					$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "template_constraints"));
				} else {
					$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "template_constraints_id", $constraint_id, "template_constraints"));
				}
				break;
			case "editsquidconstraintsubmit":
				$constraint_id = $this->aux1;
				if ($constraint_id == "new") {
					$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_manual_squid_constraints"));
				} else {
					$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "squid_constraints_id", $constraint_id, "tabular_templates_manual_squid_constraints"));
				}
				break;
		}
	}
	
	/**
	 * Called by Template::view_add. Gets all data required to show the add/edit constraint page
	 */
	function view_editconstraint() {
		$constraint_query = null;
		$squid = false;
		$blah = $this->getSourceOptions(false);
		switch ($this->subvar) {
			case "editconstraint":
				$constraint_id = $this->subid;
				break;
			case "editsquidconstraint":
				$squid = true;
				$squid_id = $this->subid;
				$constraint_id = $this->aux1;
				break;
		}
		
		if ($constraint_id == "new") {
		} else {
			if (!$squid) {
				$constraint_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM template_constraints WHERE template_constraints_id='".$constraint_id."' LIMIT 1;"));
			} else {
				$constraint_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates_manual_squid_constraints WHERE squid_constraints_id='".$constraint_id."' LIMIT 1;"));
			}
			
			$blah['data']['column_id'] = $constraint_query['column_id'];
			$blah['data']['type'] = $constraint_query['type'];
			$blah['data']['value'] = $constraint_query['value'];
			
			$_REQUEST['data']['column_id'] = $constraint_query['column_id'];
			$table_join_ajax = $this->view_table_join_ajax($constraint_query['table_join_id']);
			$table_join_ajax = $table_join_ajax->data;
			unset($_REQUEST['data']['column_id']);
		}
		
		$blah['options']['type'] = array(
			"eq"=>"Equals",
			"neq"=>"Does not Equal",
			"lt"=>"Less Than",
			"gt"=>"Greater Than",
			"lte"=>"Less Than or Equal To",
			"gte"=>"Greater Than or Equal To",
			"like"=>"Contains"
			);
		
		$table_join_ajax = "";
		/*if ($this->subid == "new") {
			$_REQUEST['data']['column_id'] = reset(array_keys($blah['options']['column_id']));
			$table_join_ajax = $this->view_table_join_ajax($constraint_query['table_join_id']);
			$table_join_ajax = $table_join_ajax->data;
			unset($_REQUEST['data']['column_id']);
		}*/
		
		return array($blah, $table_join_ajax);
	}
	
	function getSourceOptions($warnings = false) {
		
		if (!isset($this->current) || empty($this->current)) {
			$this->current = $this->get_template($this->id);
		}
		$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));
		
		$blah = array();
		$blah['options'] = array();
		$blah['option_warnings'] = null;
		foreach ($tables['catalogue'] as $i => $column) {
			foreach ($column as $j => $cell) {
				$blah['options']['columns'][$cell['column_id']] = $cell;
				switch ($cell['data_type']) {
					case "timestamp":
					case "timestamp with time zone":
					case "timestamp without time zone":
						$blah['column_types'][$cell['column_id']] = "date";
						break;
					case "text":
						if ($warnings) {
							$blah['option_warnings'][$cell['column_id']] = "Warning: The data type of the selected Source Column is ".ucwords($cell['data_type']).". This may cause unexpected results when calculating the Sum, Minimum, Maximum or Average values.";
						}
						break;
					default:
						break;
				}
				if ($cell['dropdown'] == "t") {
					$blah['column_options'][$cell['column_id']] = true;
				}
			}
		}
		return $blah;
	}
	
	/**
	 * Called as the action on forms on almost every page when creating a report.
	 * Takes aguments about which page from the url in the id, subvar, subid, etc variables
	 *
	 * @return null
	 */
	function view_save() {
		switch ($this->subvar) {
			case "cancel":
				break;
			case "constraintlogicsubmit":
				Template::view_constraintlogicsubmit();
				break;
			case "editconstraintsubmit":
				if ($this->subid) {
					Template::view_editconstraintsubmit();
				}
				break;
			case "removeconstraintsubmit":
				$template_id = $this->id;
				$constraint_id = $this->subid;
				if (empty($template_id)) return;
				if (empty($constraint_id)) return;
				$constraint_logic = $this->get_constraint_logic($template_id);
				
				if (preg_match("/^ ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
					//if the constraint to be removed is the only constraint in the logic: simply set logic to ''
					$constraint_logic = "";
				} else if (preg_match("/^ ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					//if the constrain to be be removed is at the start and is followed by an and/or, then remove the constraint and the and/or
					$constraint_logic = preg_replace("/^ ?($constraint_id) ?(AND|OR) ?/", "", $constraint_logic);
				} else if (preg_match("/\( ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
					//if the constraint to be removed is on it's own in a set of brackets, then remove the constraint only. This will make the logic invailid...
					$constraint_logic = preg_replace("/\( ?($constraint_id) ?\)/", "", $constraint_logic);
				} else if (preg_match("/ ?\( ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					//if the constraint to be removed comes after a bracket and is followed by an and/or, then remove the constraint and the and/or
					$constraint_logic = preg_replace("/\( ?($constraint_id) ?(AND|OR) ?/", "(", $constraint_logic);
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
					//if the constraint to be removed comes after an and/or and is at the end of the logic, then remove the and/or and the constraint
					$constraint_logic = preg_replace("/ ?(AND|OR) ?($constraint_id) ?$/", "", $constraint_logic);
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
					//if the constraint to be removed comes after an and/or and is followed by a bracket, then remove the and/or and the constraint
					$constraint_logic = preg_replace("/ ?(AND|OR) ?($constraint_id) ?\)/", ")", $constraint_logic);
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					//if the constraint to be removed comes after an and/or and is followed by another and/or, then remove the constraint and the second and/or
					$constraint_logic = preg_replace("/ ?($constraint_id) ?(AND|OR) ?/", " ", $constraint_logic);
				} else {
				}
				
				$this->dobj->db_query($this->dobj->update(array("logic"=>$constraint_logic), "template_id", $this->id, "template_constraint_logic"));
				$this->dobj->db_query("DELETE FROM template_constraints WHERE template_constraints_id='$constraint_id';");
				break;
			case "publishsubmit":
				if ($_REQUEST['data']['publish_table'] == "on") {
					$_REQUEST['data']['publish_table'] = "t";
				} else {
					$_REQUEST['data']['publish_table'] = "f";
				}
				
				if ($_REQUEST['data']['publish_graph'] == "on") {
					$_REQUEST['data']['publish_graph'] = "t";
				} else {
					$_REQUEST['data']['publish_graph'] = "f";
				}
					
				$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "template_id", $this->id, "templates"));
				break;
			case "executionsubmit":
				$_REQUEST['data']['execute'] = "f";
				$_REQUEST['data']['execute_hourly'] = "f";
				$_REQUEST['data']['execute_daily'] = "f";
				$_REQUEST['data']['execute_weekly'] = "f";
				$_REQUEST['data']['execute_monthly'] = "f";
				
				switch ($_REQUEST['data']['execution_interval']) {
					case "manually":
						break;
					case "hourly":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_hourly'] = "t";
						break;
					case "daily":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_daily'] = "t";
						break;
					case "weekly":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_weekly'] = "t";
						break;
					case "monthly":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_monthly'] = "t";
						break;
				}
				
				unset($_REQUEST['data']['execution_interval']);
				
				if ($_REQUEST['data']['email_dissemination'] == "on") {
					$_REQUEST['data']['email_dissemination'] = "t";
				} else {
					$_REQUEST['data']['email_dissemination'] = "f";
				}
				
				//TODO: I do not believe this is required. If I am wrong it should be moved to the LDAP module.
				//$ldap_recipient_selector = ($_REQUEST['data']['ldap'] == "ldap");
				//unset($_REQUEST['data']['ldap']);
				
				$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "template_id", $this->id, "templates"));
				
				//if ($ldap_recipient_selector) {
				//	$this->redirect("ldap/recipient_selector/".$this->id);
				//	die();
				//}
				break;
			case "accesssubmit":
				if (empty($_REQUEST['data'])) return;
				$ids_r = json_decode(stripslashes($_REQUEST['data']['ids_r']), true);
				$acls_tmp = $_REQUEST['data'];
				foreach ($acls_tmp as $acl_key_tmp => $acl_tmp) {
					if (substr($acl_key_tmp, 0, 7) != "access_") continue;
					$acl_key = substr($acl_key_tmp, 7);
					$break_pos = strrpos($acl_key, "_");
					$role = substr($acl_key, 0, $break_pos);
					$user_id_tmp = substr($acl_key, $break_pos + 1);
					$user_id = $ids_r[$user_id_tmp][0];
					$user_meta = $ids_r[$user_id_tmp][1];
					$acls[$user_meta][$user_id][$role] = true;
				}
				$this->call_function("ALL", "hook_access_report_submit", array($acls, $this->id));
				break;
			default:
				break;
		}
		return;
	}

	/**
	 * Called by Template::view_save to create edit constraint. Also used to edit constaint logic on manual axies
	 */
	function view_constraintlogicsubmit() {
		$logic = $_REQUEST['data']['constraint_logic'];
		$constraints_id = json_decode(stripslashes($_REQUEST['data']['constraints_id']), true);
		$constraints_ascii = json_decode(stripslashes($_REQUEST['data']['constraints_ascii']), true);
		
		foreach ($constraints_ascii as $index_tmp => $ascii_tmp) {
			$logic = str_replace($ascii_tmp, $constraints_id[$index_tmp], $logic);
		}
		
		unset($_REQUEST['data']['constraint_logic']);
		unset($_REQUEST['data']['constraints_id']);
		unset($_REQUEST['data']['constraints_ascii']);
		$_REQUEST['data']['logic'] = $logic;
		
		if ($this->subvar == "constraintlogicsubmit") {
			$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "template_id", $this->id, "template_constraint_logic"));
		} else if ($this->subid == "squidconstraintlogicsubmit") {
			$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_templates_manual_squid_id", $this->aux1, "tabular_templates_manual_squid_constraint_logic"));
		}
	}
	
	/**
	 * Retrieve the constraint logic from the database
	 *
	 * @param int $template_id The template id
	 */
	function get_constraint_logic($template_id) {
		$query = "SELECT * FROM template_constraint_logic WHERE template_id='$template_id' LIMIT 1;";
		$data = $this->dobj->db_fetch($this->dobj->db_query($query));
		return $data['logic'];
	}
	
	/**
	 * Retrieve the templates constraints
	 *
	 * @param int $template_id The template id to extract the constraints for
	 */
	function get_constraints($template_id) {
		$query = "SELECT l.*, t.template_id, t.name, t.module, t.object_id, c.column_id, tb.table_id, c.human_name as chuman, tb.human_name as thuman, c.name as column, tb.name as table FROM template_constraints l, templates t, columns c, tables tb WHERE tb.table_id=c.table_id AND c.column_id=l.column_id AND t.template_id=l.template_id AND t.template_id=".$template_id.";";
		$data = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		return $data;
	}
	
	/**
	 * Called anywhere a column is selected.
	 * Given a selected column id and the insersection column id, shows all the possible table joins between them, and produces html form elements to allow the user so select one.
	 *
	 * @param int $current_join The id of the current join
	 * @param int $intersection_column_id The id of the primary column to join to
	 * @return The HTML string output
	 */
	function view_table_join_ajax($current_join=null, $intersection_column_id=null) {
		$foobar = "";
		$selected_column_id = $_REQUEST['data']['column_id'];
		if ($intersection_column_id == 0) die;
		
		//self referential joins
		$sr_joins_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT c.*, t.*, c.name AS column_name, t.name AS table_name FROM columns c INNER JOIN tables t ON (t.table_id=c.table_id) WHERE c.column_id='".$selected_column_id."' OR c.column_id='".$intersection_column_id."';"));
		
		foreach ($sr_joins_query as $sr_join_tmp) {
			$sr_joins[$sr_join_tmp['column_id']] = $sr_join_tmp;
		}
		
		$selected_table_id = $sr_joins[$selected_column_id]['table_id'];
		$intersection_table_id = $sr_joins[$intersection_column_id]['table_id'];
		
		if ($selected_table_id == $intersection_table_id) {
			$foobar .= "<div class='input radio'>";
			$foobar .= "<input type='radio' name='data[table_join_id]' checked='true' disabled='true' />";
			$foobar .= "<label>";
			$foobar .= "<span style='font-weight: bold;'>";
			$foobar .= $sr_joins[$selected_column_id]['table_name'];
			$foobar .= ".";
			$foobar .= $sr_joins[$selected_column_id]['column_name'];
			$foobar .= "</span>";
			$foobar .= " &#x21C4; ";
			$foobar .= "<span style='font-weight: bold;'>";
			$foobar .= $sr_joins[$intersection_column_id]['table_name'];
			$foobar .= ".";
			$foobar .= $sr_joins[$intersection_column_id]['column_name'];
			$foobar .= "</span>";
			$foobar .= "</label>";
			$foobar .= "</div>";
			
			$explaination_tmp = "The selected column is linked, via a self referential join, to the intersection column.";
			$foobar .= "<p>".$explaination_tmp."</p>";
		} else {
			$table_joins_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM table_joins WHERE table1='".$selected_table_id."' AND table2='".$intersection_table_id."';"));
			if (empty($table_joins_query)) {
				die();
			}
			
			$columns_tmp = array($selected_column_id, $intersection_column_id);
			foreach ($table_joins_query as $table_join_tmp) {
				$columns_tmp = array_merge((array)$columns_tmp, (array)explode(",", $table_join_tmp['method']));
				$table_joins[$table_join_tmp['table_join_id']] = $table_join_tmp;
			}
			$columns_tmp = array_unique($columns_tmp);
			
			$columns_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT c.column_id, c.name as column_name, t.table_id, t.name as table_name FROM columns c INNER JOIN tables t ON (c.table_id=t.table_id) WHERE c.column_id='".implode("' OR c.column_id='", $columns_tmp)."';"));
			foreach ($columns_query as $column_tmp) {
				$columns[$column_tmp['column_id']] = $column_tmp;
				$tables[$column_tmp['table_id']] = $column_tmp;
			}
			
			foreach ($table_joins as $table_join) {
				$table_join_id = $table_join['table_join_id'];
				$method_tmp = explode(",", $table_join['method']);
				$method_start_table = $table_join['table1'];
				$method_end_table = $table_join['table2'];
				
				$this_pair_start_id = 0;
				$this_pair_end_id = $this_pair_start_id + 1;
				$last_pair_start_table = $method_start_table;
				unset($method_reorg);
				
				while (isset($method_tmp[$this_pair_start_id])) {
					$this_pair_start_table = $columns[$method_tmp[$this_pair_start_id]]['table_id'];
					if ($this_pair_start_id !== 0) $method_reorg[] = "internal join";
					if ($last_pair_start_table != $this_pair_start_table) {
						$method_reorg[] = $method_tmp[$this_pair_end_id];
						$method_reorg[] = "referenced by";
						$method_reorg[] = $method_tmp[$this_pair_start_id];
					} else {
						$method_reorg[] = $method_tmp[$this_pair_start_id];
						$method_reorg[] = "references";
						$method_reorg[] = $method_tmp[$this_pair_end_id];
					}
					$last_pair_start_table = isset($columns[$method_reorg[$this_pair_end_id]]['table_id']) ? $columns[$method_reorg[$this_pair_end_id]]['table_id'] : null;
					$this_pair_start_id += 2;
					$this_pair_end_id = $this_pair_start_id + 1;
				}
				$foobar .= "<div class='input'>";
				$join_selected = $current_join == $table_join_id || count($table_joins) === 1;
				$foobar .= "<input type='radio' name='data[table_join_id]' value='".$table_join_id."' ".($join_selected ? "checked=\"checked\"" : "")." /><label>";
				
				$explaination_count = 0;
				$explaination_sr_count = 0;;
				$explaination_in_count = 0;
				$explaination_tmp = "The selected column is linked, ";
				
				if ($method_reorg[0] != $selected_column_id) {
					$column = $columns[$selected_column_id];
					$foobar .= "<span style='font-weight: bold;'>";
					$foobar .= ucwords($column['table_name']);
					$foobar .= ".";
					$foobar .= ucwords($column['column_name']);
					$foobar .= "</span>";
					$foobar .= " &#x21C4; ";
					$explaination_tmp .= "via a self referential join, ";
					$explaination_count += 1;
					$explaination_sr_count += 1;
				}
				
				foreach ($method_reorg as $method_step) {
					if ($method_step == $selected_column_id || $method_step == $intersection_column_id) $foobar .= "<span style='font-weight: bold;'>";
					
					switch ($method_step) {
						case "internal join":
							$foobar .= " &#x21C4; ";
							$explaination_tmp .= ($explaination_count > 0 ? "then " : "")."via ".($explaination_sr_count > 0 ? "another" : "a")." self referential join, ";
							$explaination_count += 1;
							$explaination_sr_count += 1;
							break;
						case "references":
						case "referenced by":
							$foobar .= " <span style='font-style: italic;'>";
							$foobar .= $method_step;
							$foobar .= "</span> ";
							$explaination_tmp .= ($explaination_count > 0 ? "then " : "")."via ".($explaination_in_count > 0 ? "another" : "an")." inner join, ";
							$explaination_count += 1;
							$explaination_in_count += 1;
							break;
						default:
							$column = $columns[$method_step];
							$foobar .= ucwords($column['table_name']);
							$foobar .= ".";
							$foobar .= ucwords($column['column_name']);
							break;
					}
					
					if ($method_step == $selected_column_id || $method_step == $intersection_column_id) $foobar .= "</span>";
				}
					
				if (end($method_reorg) != $intersection_column_id) {
					$foobar .= " &#x21C4; ";
					$column = $columns[$intersection_column_id];
					$foobar .= "<span style='font-weight: bold;'>";
					$foobar .= ucwords($column['table_name']);
					$foobar .= ".";
					$foobar .= ucwords($column['column_name']);
					$foobar .= "</span>";
					$explaination_tmp .= ($explaination_count > 0 ? "then " : "")."via ".($explaination_sr_count > 0 ? "another" : "a")." self referential join, ";
					$explaination_count += 1;
					$explaination_sr_count += 1;
				}
				
				$foobar .= "</label>";
				$foobar .= "</div>";
				
				$explaination_tmp .= "to the intersection column.";
				$foobar .= "<p>".$explaination_tmp."</p>";
			}
		}
		
		$output = Template_View::view_table_join_ajax($foobar);
		return $output;
	}
	
	/**
	 * Display a dropdown list of all values in a constraint column
	 */
	function view_constraint_column_options_ajax() {
		$template_id = $this->id;
		$column_id = $this->subvar;

		$this->current = $this->get_template($this->id);
		$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

		foreach ($tables['catalogue'] as $i => $column) {
			foreach ($column as $j => $cell) {
				if ($column_id != $cell['column_id']) continue;
				if ($cell['dropdown'] != "t") return;
				if (!isset($obj)) {
					$obj = new Catalogue();
				}
				foreach ($obj->hook_query_source($this->current['object_id'], "SELECT ".$cell['column_sql_name']." FROM ".$cell['table_sql_name']." GROUP BY ".$cell['column_sql_name'].";") as $tmp) {
					$column_options_json['items'][] = array("name"=>$tmp[$cell['column_sql_name']], "label"=>$tmp[$cell['column_sql_name']], "abbreviation"=>$tmp[$cell['column_sql_name']]);
				}
				break 2;
			}
		}
		$column_options_json['identifier'] = "abbreviation";
		$column_options_json['label'] = "name";
		$column_options_json = json_encode($column_options_json);
		$output = Template_View::view_constraint_column_options_ajax($column_options_json);
		return $output;
	}
	
	/**
	 * This is called by ajax to load the preview page
	 */
	function view_data_preview_first_ajax() {
		$data_preview = "";
		if ((int)$this->id) {
			$template = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='".$this->id."';"));
			$saved_report_id = $this->execute_demo_outline($this->id);
			$data_preview .= '<div id="saved_report_id" style="display: none;">'.$saved_report_id.'</div>';
			$data_preview .= '<div id="data_preview">';
			
			$data_preview .= "<h3>Tabular Data</h3>";
			$data_preview .= $this->hook_output(array($saved_report_id, $this->id, true, null, false))->data;
			
			if ($template['publish_graph'] == "t") {
				$data_preview .= "<h3>Graphic Data</h3>";
				$data_preview .= "<div style='height: 690px;'>";
				$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], true, false));
				$data_preview .= $graph['graphing']['object'];
				$data_preview .= "</div>";
			}
			$data_preview .= '</div>';
		}
		
		$output = Template_View::view_data_preview_ajax($data_preview);
		return $output;
	}
	
	/**
	 * This is called to display the preview (cellwise) of this report
	 */
	function view_data_preview_slow_ajax() {
		$data_preview = "";
		if ((int)$this->id) {
			$template = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='".$this->id."';"));
			$saved_report_id = $this->execute_demo_cellwise($this->id);
			$data_preview .= '<div id="saved_report_id" style="display: none;">'.$saved_report_id.'</div>';
			$data_preview .= '<div id="data_preview">';
			if (!$saved_report_id) {
				return Template_View::view_data_preview_ajax("finished");
			}
			
			$data_preview .= "<h3>Tabular Data</h3>";
			$data_preview .= $this->hook_output(array($saved_report_id, $this->id, true, null, false))->data;
			
			if ($template['publish_graph'] == "t") {
				$data_preview .= "<h3>Graphic Data</h3>";
				$data_preview .= "<div style='height: 690px;'>";
				$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], true, false));
				$data_preview .= $graph['graphing']['object'];
				$data_preview .= "</div>";
			}
			$data_preview .= '</div>';
		}
		$output = Template_View::view_data_preview_ajax($data_preview);
		return $output;
	}
	
	/**
	 * Convert and save the results as a HTML page
	 *
	 * @param $data The report data
	 * @param $template The complete template information
	 * @param $saved_report_id The id of the saved data
	 * @return The HTML output
	 */
	function save_document($data, $template, $saved_report_id, $demo) {
		$html = true;
		
		$path_base = $this->sw_path.$this->tmp_path;
		$url_base = $this->web_path.$this->tmp_path;
		
		$html_path = $path_base."table_$saved_report_id.html";
		$html_url = $url_base."table_$saved_report_id.html";
		$insert = array(
			"saved_report_id" => $saved_report_id,
			"created" => "now()",
			"html_path" => $html_path,
			"html_url" => $html_url,
		);
		
		$this->dobj->db_query($this->dobj->insert($insert, "table_documents"));
		$output = $this->hook_output(array($data, $template, null, null, false));
		$table_html = $output->data;
		
		file_put_contents($html_path, $table_html);
		
		return $table_html;
	}
	
	
	
	
	
	
	
	function where($alias_tmp, $column_tmp, $type_tmp, $value_tmp) {
		switch ($type_tmp) {
			case "gt":
				#MYSQL $where[] .= $post['table'].".".$post['column']." > $$".$post['value']."$$";
				$where = "$alias_tmp.$column_tmp > '$value_tmp'";
				break;
			case "lt":
				#MYSQL $where[] .= $post['table'].".".$post['column']." < $$".$post['value']."$$";
				$where = "$alias_tmp.$column_tmp < '$value_tmp'";
				break;
			case "gte":
				#MYSQL $where[] .= $post['table'].".".$post['column']." >= $$".$post['value']."$$";
				$where = "$alias_tmp.$column_tmp >= '$value_tmp'";
				break;
			case "lte":
				#MYSQL $where[] .= $post['table'].".".$post['column']." <= $$".$post['value']."$$";
				$where = "$alias_tmp.$column_tmp <= '$value_tmp'";
				break;
			case "neq":
				#MYSQL $where[] .= $post['table'].".".$post['column']." = $$".$post['value']."$$";
				$where ="$alias_tmp.$column_tmp != '$value_tmp'";
				break;
			case "like":
				#MYSQL $where[] .= $post['table'].".".$post['column']." = $$".$post['value']."$$";
				$where = "CAST($alias_tmp.$column_tmp AS TEXT) LIKE '%$value_tmp%'";
				break;
			case "eq":
			default:
				#MYSQL $where[] .= $post['table'].".".$post['column']." = $$".$post['value']."$$";
				$where = "$alias_tmp.$column_tmp = '$value_tmp'";
				break;
		}
		return $where;
	}
	
	function add_template($data) {
		$temp =array();
		$temp['name'] = $data['name'];
		$temp['module'] = $data['module'];
		$temp['object_id'] = $data['database'];
		$temp['template_id'] = $this->dobj->nextval("templates");
		$template['header'] = $this->default_header();
		$template['footer'] = $this->default_footer();
		$query = $this->dobj->insert($temp, "templates");
		$this->dobj->db_query($query);
		return $temp['template_id'];
	}

	function view_save_template() {
		$this->save_template($_REQUEST['data'], $this->id);
		die();
	}

	function save_template($data, $id=0) {
		if (!$id) {
			$id = $this->id;
		}
		if ($data['header']) {
			$data['header'] = stripslashes($data['header']);
		}
		if ($data['footer']) {
			$data['footer'] = stripslashes($data['footer']);
		}
		$query = $this->dobj->update($data, "template_id", $id, "templates");
		$this->dobj->db_query($query);
		return $id;
	}
	
	function get_template($template_id) {
		$query = "SELECT * FROM templates WHERE template_id='".$template_id."';";
		return $this->dobj->db_fetch($this->dobj->db_query($query));
	}
	
	function hook_run_query($object_id, $query) {
		$obj = new Catalogue();
		if (is_array($query)) {
			$res = array();
			foreach ($query as $i => $q) {
				$res[$i] = $obj->hook_query_source($object_id, $q);
			}
		} else {
			$res = $obj->hook_query_source($object_id, $query);
		}
		return $res;
	}
	
	function view_aggregate_dd_json() {
		$values = array("none"=>"", "countdistinct"=>"Count Once", "count"=>"Count", "sum"=>"Sum", "max"=>"Maximum", "min"=>"Minimum");
		$output = Template_View::view_dd_json($values);
		return $output;
	}
		
	function get_reports() {
		$query = "SELECT t.*, (SELECT count(*) from saved_reports r WHERE t.template_id=r.template_id) as saved FROM templates t ORDER BY name;";
		return $this->dobj->db_fetch_all($this->dobj->db_query($query));
	}
		
	function get_report() {
		$query = "SELECT t.*, (SELECT count(*) from saved_reports r WHERE t.template_id=r.template_id) as saved FROM templates t WHERE template_id='".$this->id."';";
		return $this->dobj->db_fetch($this->dobj->db_query($query));
	}
	
	function save_results($template_id, $data, $demo='f', $runtime=0, $runby=0) {
		$now = date("Y-m-d H:i:s");
		$saved_report_id = $this->dobj->nextval("saved_reports");
		$data = json_encode($data);
		if (strtolower($demo) == "t") {
			$query = $this->dobj->db_query("DELETE FROM saved_reports WHERE template_id='$template_id' AND demo='t'");
		}
		$query = $this->dobj->db_query("INSERT INTO saved_reports (saved_report_id, template_id, report, demo, created, run_time, run_by, run_size) VALUES ('$saved_report_id', '$template_id', $$".$data."$$, '$demo', '$now', '$runtime', '$runby', '".count($data)."')");
		return $saved_report_id;
	}
	
	function view_save_reports() {
		$output = Template_View::view_save_reports();
		return $output;
	}

	function view_delete() {
		$query = "DELETE FROM templates WHERE template_id='".$this->id."'";
		$query = $this->dobj->db_query($query);
		$output = $this->redirect("template/home/");
		die();
	}

	function view_saved_report() {
		$data = $this->get_saved_report();
		$report = json_decode($data['report'], true);
		$module = $data['module'];
		$output = $this->call_function($module, "hook_output", array($report));
		$output = $output[$module];
		#$output = $this->hook_run(true, $data);
		return $output;
	}

	function view_saved_report_raw() {
		$data = $this->get_saved_report();
		echo $data['report'];
		die();
	}
	
	function get_saved_report($template_id=null, $saved_report_id=null) {
		if (empty($template_id) && empty($saved_report_id)) $template_id = $this->id;

		$where_template_id = "r.template_id='$template_id'";

		if (empty($template_id) && !empty($saved_report_id)) {
			$where_saved_report_id = "r.saved_report_id='$saved_report_id'";
			$where_template_id = $where_saved_report_id;
		}

		//if ($this->subvar == "preview") {
		//	$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND $where_template_id AND r.demo='t' ORDER BY r.created LIMIT 1";
		//} else {
		//	$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND $where_template_id AND r.demo='f' ORDER BY r.created LIMIT 1";
		//}
		$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND $where_template_id ORDER BY r.created DESC LIMIT 1";
		
		$res = $this->dobj->db_fetch($this->dobj->db_query($query));

		return $res;
	}

	function view_saved_list() {
		$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND r.template_id='".$this->id."' ORDER BY r.created DESC";
		$res = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		$output = Template_View::view_saved_list($res);
		return $output;
	}

	function view_workspace_display() {
		$reports = $this->get_reports();
		return Template_View::view_workspace_display($reports);
	}

	/*
	 * $select Array of columns to output. If the key is non-numeric it is used as an alias.
	 *       array("c"=>"foo", "x"=>"count(*)");
	 * $from
	 *	array("a", "b", "c")
	 */
	function hook_build_query($select, $from, $where = false, $group = false, $order = false, $limit = false) {
  		$vals = $this->call_function("ALL", "hook_alter_query", array($select, $from, $where, $group, $order, $limit));
		if (count($vals) > 0) {
			list($select, $from, $where, $group, $order, $limit) = array_pop($vals);
		}
		$q = "";
		$s = "SELECT ";
		$f = " FROM ";
		$w = "";
		$o = "";
		$g = "";
		$l = "";
		foreach ($select as $i => $col) {
			$s .= $col;
			if (!is_numeric($i)) {
				$s .= " as \"".$i."\"";
			}
			$s .= ",";
		}
		
		if (count($from) == 1) {
			foreach ($from as $i => $join) {
				if (is_array($join)) {
					$f .= $join['table']." ".$join['alias'];
				} else {
					$f .= $i;
				}
			}
		} else {
			$f .= $this->join($from);
		}

		if ($where) {
			$w = " WHERE ";
			$w .= implode(" AND ", $where)." ";
		}

		if ($order) {
			$o = " ORDER BY ";
			$o .= implode(",", $order);
		}
		
		if ($group) {
			$g = " GROUP BY ";
			$g .= implode(",", $group);
		}
		
		if ($limit) {
			$l = " LIMIT ";
			$l .= $limit;
		}
		
		$s = trim($s, ",");
		$q = "".$s."".$f."".$w."".$g."".$o."".$l.";";

// 		echo $q."<br/>";
		return $q;
	}

	/**
	 * Called by hook_build_query. Takes an array of all tables that need to be placed into the query, using these tables' names, aliases and table join ids, creates the FROM part of the sql query.
	 */
	function join($foobar) {
		$tables = array();
		$columns = array();
		// We expect the first array entry, without a join_id, to be the primary intersect
		reset($foobar);
		$intersect_key = key($foobar);
		
		foreach ($foobar  as $i => $alias) {
			if (!isset($alias['join_id']) || empty($alias['join_id'])) {
				$intersect_key = $i;
				break;
			}
		}
		
		$return = "";
		//first things first: the intersection column's table
		$table_tmp = $foobar[$intersect_key]['table'];
		$alias_tmp = $foobar[$intersect_key]['alias'];
		$return = "$table_tmp $alias_tmp ";

		//create an associate array of alises, keyed by table id, so we can look them up when needed
		$aliases[$foobar[$intersect_key]['table_id']] = $foobar[$intersect_key]['alias'];

		//if we need to create aliases for joined columns, use this counter so we don't get two with the same name
		$alias_counter = 1;

		//create and array of keys for x and y axies, constraints, etc. then loop through
		$keys = array_combine(array_keys($foobar), array_keys($foobar));
		unset($keys[$intersect_key]);

		foreach ($keys as $alias) {
			if (empty($foobar[$alias])) continue;

			//hook_build_query has told us how to join this table
			if (!empty($foobar[$alias]['manual_join'])) {
				$table_tmp = $foobar[$alias]['table'];
				$alias_tmp = $foobar[$alias]['alias'];
				$manual_join_tmp = $foobar[$alias]['manual_join'];
				//use the join string hook_build_query gave us
				$return .= "JOIN $table_tmp $alias_tmp $manual_join_tmp ";
				//and that's all for this table... next!
				continue;
			}
			
			//add this alias to the array
			$aliases[$foobar[$alias]['table_id']] = $foobar[$alias]['alias'];
			
			//this table id
			$table1_tmp = $foobar[$alias]['table_id'];
			//insersection column's table id: every table that is called must somehow be linked to the intersection column's table
			$table2_tmp = $foobar[$intersect_key]['table_id'];
			//table join id to be used to link this table to the intersection
			$table_join_tmp = $foobar[$alias]['join_id'];

			//get the details of the table join
			$table_join_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM table_joins WHERE table1=$table1_tmp AND table2=$table2_tmp AND table_join_id=$table_join_tmp LIMIT 1;"));
			//create an array of the columns that form the link
			$method = explode(",", $table_join_query['method']);

			//get information for each column that is used in the link
			$columns_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM columns WHERE column_id='".implode("' OR column_id='", array_unique($method))."';"));
			//create array of columns by column_id, and a list of all tables they belong to
			foreach ($columns_query as $column_tmp) {
				$columns[$column_tmp['column_id']] = $column_tmp;
				$table_ids[$column_tmp['table_id']] = $column_tmp['table_id'];
			}

			//get information for each table that is used in the link
			$tables_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM tables WHERE table_id='".implode("' OR table_id='", $table_ids)."';"));
			//create array of tables by table id
			foreach ($tables_query as $table_tmp) {
				$tables[$table_tmp['table_id']] = $table_tmp;
			}

			//the method stored in table_join is made up steps from one column to another
			$method_steps = array_chunk($method, 2);
			//method is stored in the database as from axis to intersection. we need it the other way around.
			$method_steps = array_reverse($method_steps);

			$first_step = reset($method_steps);
			$last_step = end($method_steps);

			//for each step
			foreach ($method_steps as $step) {
				//each step has a start column and an end column. these are order by the direction of the relationship, so they may not be in to order of intersection to axis, like we need
				$step_a = $step[0];
				$step_b = $step[1];

				//step column ids
				$step_a_column_id = $step_a;
				$step_b_column_id = $step_b;

				//step table ids
				$step_a_table_id = $columns[$step_a_column_id]['table_id'];
				$step_b_table_id = $columns[$step_b_column_id]['table_id'];

				//make sure these variables are empty
				$step_start = null;
				$step_end = null;
				$intermediate_step = null;

				//if this step is the first step
				if ($step == $first_step) {
					//if column a's table is the intersection column's table
					if ($step_a_table_id == $foobar[$intersect_key]['table_id']) {
						//then column a is the start column
						$step_start = $step_a;
						$step_end = $step_b;
					//else if column b's table is the intersection column's table
					} else if ($step_b_table_id == $foobar[$intersect_key]['table_id']) {
						//then column b is the start column
						$step_start = $step_b;
						$step_end = $step_a;
					}
				//if this step is the last step
				} else if ($step == $last_step) {
					//if column a's table is the axis column's table
					if ($step_a_table_id == $foobar[$alias]['table_id']) {
						$step_start = $step_b;
						//then column a is the end column
						$step_end = $step_a;
					//else if column b's table is the axis column's table
					} else if ($step_b_table_id == $foobar[$alias]['table_id']) {
						$step_start = $step_a;
						//then column a is the end column
						$step_end = $step_b;
					}
				} else {
					$intermediate_step = true;
				}


				//if this is the first or last step (although what we do here only counts for the first step)
				if (!$intermediate_step) {
					//make sure these variables are clear
					$prev_step_start = null;
					$prev_step_end = null;

					//record the start and end column of this step, for reference in the next step
					$prev_step_start = $step_start;
					$prev_step_end = $step_end;
				//else if this is an intermediate step
				} else {
					//get the end column column id of the last step
					$prev_step_end_column_id = $prev_step_end;

					//get the end column table id of the last step
					$prev_step_end_table_id = $columns[$prev_step_end_column_id]['table_id'];

					//if column a's table is the previous end column's table
					if ($step_a_table_id == $prev_step_end_table_id) {
						//then column a is the start column
						$step_start = $step_a;
						$step_end = $step_b;
					//else if column b's table is the previous end column's table
					} else if ($step_b_table_id == $prev_step_end_table_id) {
						//then column b is the start column
						$step_start = $step_b;
						$step_end = $step_a;
					}
				}

				//start and end column ids
				$step_start_column_id = $step_start;
				$step_end_column_id = $step_end;

				//start and end column names
				$step_start_column_name = $columns[$step_start_column_id]['name'];
				$step_end_column_name = $columns[$step_end_column_id]['name'];

				//start and end column table ids
				if (isset($columns[$step_start_column_id]['table_id'])) {
					$step_start_table_id = $columns[$step_start_column_id]['table_id'];
				} else {
					$step_start_table_id = null;
				}
				if (isset($columns[$step_end_column_id]['table_id'])) {
					$step_end_table_id = $columns[$step_end_column_id]['table_id'];
				} else {
					$step_end_table_id = null;
				}

				//start and end column table names
				if (isset($tables[$step_start_table_id]['name'])) {
					$step_start_table_name = $tables[$step_start_table_id]['name'];
				} else {
					$step_start_table_name = null;
				}
				if (isset($tables[$step_end_table_id]['name'])) {
					$step_end_table_name = $tables[$step_end_table_id]['name'];
				} else {
					$step_end_table_name = null;
				}

				//end column table name, ready for insertion into sql
				$table_tmp = $step_end_table_name;

				//if no alias has been set for the start column's table
				if (empty($aliases[$step_start_table_id])) {
					//then set one
					$aliases[$step_start_table_id] = "j".$foobar[$alias]['alias'].$alias_counter;
					$alias_counter ++;
				}

				//if no alias has been set for the end column's table
				if (empty($aliases[$step_end_table_id])) {
					//then set one
					$aliases[$step_end_table_id] = "j".$foobar[$alias]['alias'].$alias_counter;
					$alias_counter ++;
				}

				//table alias and column name, ready for insertion into sql
				$alias_tmp = $aliases[$step_start_table_id];
				$join_foo = "$alias_tmp.$step_start_column_name";

				//table alias and column name, ready for insertion into sql
				$alias_tmp = $aliases[$step_end_table_id];
				$join_bar = "$alias_tmp.$step_end_column_name";

				//sql for this step: joining this step's end column's table.
				$return .= "JOIN $table_tmp $alias_tmp ON ($join_foo=$join_bar) ";
			}
		}
		return $return;
	}

	function view_constraint_options_json() {
		$options = $this->hook_constraint_options();
		$output = Template_View::view_constraint_options_json($options);
		return $output;
	}
	
	function view_demo() {
		$output = $this->hook_run(true);
		$output->layout = "ajax";
		return $output;
	}
	
	function view_sort_dd_json() {
		$values = array("ASC"=>"Ascending", "DESC"=>"Descending");
		$output = Template_View::view_dd_json($values);
		return $output;
	}
	
	/**
	 * Can the current user see the given report. The user must have at least one
	 * permission out of histories, edit and execute
	 *
	 * @param $report_id The report to check
	 * @return true/false based on the users permissions
	 */
	public function report_visible($report_id) {
		$edit = isset($_SESSION['acls']['report'][$report_id]['edit']);
		$history = isset($_SESSION['acls']['report'][$report_id]['histories']);
		$execute = isset($_SESSION['acls']['report'][$report_id]['execute']);
		if ($edit || $history || $execute) {
			return true;
		}
		return false;
	}
	
	/**
	 * Displays the report home screen. This will also restrict to only those reports the user has either edit, execute or
	 * histories permission on. Similarly functions will be visible with the appropriate permission.
	 *
	 * @return The HTML for the home screen
	 */
	function view_home() {
		$reports = $this->get_reports();
		$my_reports = array();
		if (is_array($reports)) {
			foreach ($reports as $i => $report) {
				$edit = isset($_SESSION['acls']['report'][$report['template_id']]['edit']);
				$histories = isset($_SESSION['acls']['report'][$report['template_id']]['histories']);
				$execute = isset($_SESSION['acls']['report'][$report['template_id']]['execute']);
				if ($edit || $histories || $execute) {
					$report['permission_edit'] = $edit;
					$report['permission_histories'] = $histories;
					$report['permission_execute'] = $execute;
					$my_reports[] = $report;
				}
			}
		}
		return Template_View::view_workspace_display($my_reports);
	}
}

class Template_View {
	function view_home($modules) {
		return $this->view_workspace_display($modules);
	}
	
	/**
	 * The generic view for adding templates. This is the initial view to select which
	 * template module and database to use.
	 * Each module should override this to their own view_add
	 *
	 * @param $objects array The catalogue objects (databases) to select from
	 * @param $modules array An array of template modules
	 * @return The HTML output string
	 */
	function view_add($objects, $modules) {
		$module = $this->id;
		$output->data = "";

		if (empty($module)) {
			foreach ($modules as $i => $module) {
				$output->data .= "<h3>".$this->l("template/add/".$module['module'], "Create ".$module['label'])."</h3>";
				$output->data .= "<p class='h3attach'>".$module['description']."</p>";
			}
		} else {
			$output->title = "Source Database";

			if (!empty($objects['catalogue'])) {
				$output->data .= "
					<div class='reports'>
						<table cellpadding='0' cellspacing='0'>
							<tr>
								<th>Name</th>
								<th>Description</th>
								<th>&nbsp;</th>
							</tr>
							";
				foreach ($objects['catalogue'] as $database_tmp) {
					if (empty($database_tmp['description'])) {
						$database_tmp['description'] = "&nbsp;";
					}

					$output->data .= "
							<tr>
								<td>".$database_tmp['human_name']."</td>
								<td>".$database_tmp['description']."</td>
								<td>
									<ul>
										<li><button dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."$module/add_select_object/".$database_tmp['object_id']."\"; return false;' />Select Database</button></li>
									</ul>
								</td>
							</tr>
							";
				}
				$output->data .= "
						</table>
					</div>
					";
			}
		}

		return $output;
	}
	
	/**
	 * Display the preview table. It will be populated via AJAX
	 *
	 * @param string $preview_table The HTML for the preview table
	 */
	public function view_add_preview() {
		$output->title = "Preview";
		$output->title_desc = "";
		$output->data .= '<div id="data_preview_first">';
		$output->data .= '<div id="data_preview_loading" style="display: none; text-align: center;">Loading Report...</div>';
		$output->data .= '<div id="data_preview_load" style="text-align: center;"><a href="javascript:update_data_preview_first();">Load Preview</a></div>';
		$output->data .= '</div>';
		return $output;
	}

	/**
	 * Display the access view
	 *
	 * @param array $blah The acl information to generate the constraint options
	 */
	public function view_add_access($blah) {
		$type = $this->module;
		$output->title = "Access";
		$output->title_desc = "";
		$output->data .= $this->f($type."/save/".$this->id."/accesssubmit");
		$output->data .=  $blah['acl_markup'];
		$output->data .= $this->i("submit", array("label"=>"Save", "type"=>"submit", "value"=>"Save", "dojoType"=>"dijit.form.Button"));
		$output->data .= $this->f_close();
		return $output;
	}
	
	/**
	 * Display the execute template page
	 *
	 * @param array $template The generic template information
	 */
	public function view_add_execute($template) {
		$type = $this->module;
		//prevent the editor from adding more escapes than neccessary
		$template['email_body'] = stripslashes($template['email_body']);
		$output->title = "Execution";
		$output->title_desc = "";
		
		$output->data .= $this->f($type."/save/".$this->id."/executionsubmit", "id='execution_form'", "dojoType='dijit.form.Form'");
		$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_manually]", "label"=>"Execute Manually", "type"=>"radio", "value"=>"manually"/*, "onchange"=>'console.log("skoo");'*/));
		$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_hourly]", "label"=>"Execute Hourly", "type"=>"radio", "value"=>"hourly", "default"=>($template['execute_hourly'] == "t")));
		$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_daily]", "label"=>"Execute Daily", "type"=>"radio", "value"=>"daily", "default"=>($template['execute_daily'] == "t")));
		$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_weekly]", "label"=>"Execute Weekly", "type"=>"radio", "value"=>"weekly", "default"=>($template['execute_weekly'] == "t")));
		$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_monthly]", "label"=>"Execute Monthly", "type"=>"radio", "value"=>"monthly", "default"=>($template['execute_monthly'] == "t")));
		$output->data .= "<hr />";
		
		$output->data .= $this->i("data[execute_hour]", array("id"=>"data[execute_hour]", "div_id"=>"execute_hour_div", "label"=>"Hour of Execution", "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "default"=>$template['execute_hour'], "options"=>array(
			"0"=>"0 AM", "1"=>"1 AM", "2"=>"2 AM", "3"=>"3 AM", "4"=>"4 AM", "5"=>"5 AM",
			"6"=>"6 AM", "7"=>"7 AM", "8"=>"8 AM", "9"=>"9 AM", "10"=>"10 AM", "11"=>"11 AM",
			"12"=>"12 PM", "13"=>"1 PM", "14"=>"2 PM", "15"=>"3 PM", "16"=>"4 PM", "17"=>"5 PM",
			"18"=>"6 PM", "19"=>"7 PM", "20"=>"8 PM", "21"=>"9 PM", "22"=>"10 PM", "23"=>"11 PM"
			)));
		$output->data .= "<p>Hour of the day to execute the report.</p>";
		
		$output->data .= $this->i("data[execute_dayofweek]", array("id"=>"data[execute_dayofweek]", "div_id"=>"execute_dayofweek_div", "label"=>"Day of Execution", "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "default"=>$template['execute_dayofweek'], "options"=>array(
			"1"=>"Monday", "2"=>"Tuesday", "3"=>"Wednesday", "4"=>"Thursday", "5"=>"Friday", "6"=>"Saturday", "7"=>"Sunday" )));
		$output->data .= "<p>Day of the week to execute the report.</p>";
		
		$output->data .= $this->i("data[execute_day]", array("id"=>"data[execute_day]", "div_id"=>"execute_day_div", "label"=>"Date of Execution", "type"=>"select", "dojoType" =>"dijit.form.FilteringSelect", "default"=>$template['execute_day'], "options"=>array(
			"1"=>"1st", "2"=>"2nd", "3"=>"3rd", "4"=>"4th", "5"=>"5th", "6"=>"6th", "7"=>"7th",
			"8"=>"8th", "9"=>"9th", "10"=>"10th", "11"=>"11th", "12"=>"12th", "13"=>"13th",
			"14"=>"14th", "15"=>"15th", "16"=>"16th", "17"=>"17th", "18"=>"18th", "19"=>"19th",
			"20"=>"20th", "21"=>"21st", "22"=>"22nd", "23"=>"23rd", "24"=>"24th", "25"=>"25th",
			"26"=>"26th", "27"=>"27th", "28"=>"28th", "29"=>"29th", "30"=>"30th", "31"=>"31st (or last day of month)"
		)));
		$output->data .= "<p>Day of the month to execute the report.</p>";
		
		$output->data .= "
			<script>
				dojo.addOnLoad(execution_interval_input_toggle_init);

				function execution_interval_input_toggle_init() {
					dojo.connect(dojo.byId('data[execution_interval_manually]'), 'onclick', 'execution_interval_input_toggle');
					dojo.connect(dojo.byId('data[execution_interval_hourly]'), 'onclick', 'execution_interval_input_toggle');
					dojo.connect(dojo.byId('data[execution_interval_daily]'), 'onclick', 'execution_interval_input_toggle');
					dojo.connect(dojo.byId('data[execution_interval_weekly]'), 'onclick', 'execution_interval_input_toggle');
					dojo.connect(dojo.byId('data[execution_interval_monthly]'), 'onclick', 'execution_interval_input_toggle');

					execution_interval_input_toggle();
				}

				function execution_interval_input_toggle() {
					var execution_interval_daily = dojo.byId('data[execution_interval_daily]').checked;
					var execution_interval_weekly = dojo.byId('data[execution_interval_weekly]').checked;
					var execution_interval_monthly = dojo.byId('data[execution_interval_monthly]').checked;

					var execute_hour_div = dojo.byId('execute_hour_div');
					var execute_dayofweek_div = dojo.byId('execute_dayofweek_div');
					var execute_day_div = dojo.byId('execute_day_div');

					//enable all the execution inputs
					dijit.byId('data[execute_hour]').setDisabled(false);
					dijit.byId('data[execute_dayofweek]').setDisabled(false);
					dijit.byId('data[execute_day]').setDisabled(false);

					//make all labels look enabled
					execute_hour_div.className = execute_hour_div.className.replace('disabled', '');
					execute_dayofweek_div.className = execute_dayofweek_div.className.replace('disabled', '');
					execute_day_div.className = execute_day_div.className.replace('disabled', '');

					//if no appropriate interval is selected, disable the hour input
					if (!execution_interval_daily && !execution_interval_weekly && !execution_interval_monthly) {
						dijit.byId('data[execute_hour]').setDisabled(true);
						execute_hour_div.className = execute_hour_div.className+' disabled';
					}

					//as above, but for the day of week input
					if (!execution_interval_weekly) {
						dijit.byId('data[execute_dayofweek]').setDisabled(true);
						execute_dayofweek_div.className = execute_dayofweek_div.className+' disabled';
					}

					//as above, but for the day of month input
					if (!execution_interval_monthly) {
						dijit.byId('data[execute_day]').setDisabled(true);
						execute_day_div.className = execute_day_div.className+' disabled';
					}
				}
			</script>
			";
		
		$output->data .= "<hr />";
		$output->data .= "<h3>Email Dissemination</h3>";
		$output->data .= $this->i("data[email_dissemination]", array("label"=>"Disseminate Via Email", "type"=>"checkbox", "default"=>($template['email_dissemination'] == "t")));
		$output->data .= "<hr />";
		
		$recipient_selectors = $this->call_function("ALL", "hook_recipient_selector", array($template['email_recipients']));
		$output->data .= "
			<div style=''>Recipients:</div>
			<script>
				dojo.addOnLoad(recipients_count_init);

				var recipient_selectors = ".json_encode(array_keys($recipient_selectors)).";

				function recipients_count_init() {
					for (var i in recipient_selectors) {
						recipients_count(null, dojo.byId(recipient_selectors[i]+'_recipients'));
						dojo.byId(recipient_selectors[i]+'_recipients').onchange = recipients_count;
					}
				}

				function recipients_count(e, o) {
					if (e) {
						var object = e.currentTarget;
					} else if (o) {
						var object = o;
					}

					if (object.id == 'tabular_recipients') {
						var emails = object.value;
						emails = emails.replace(' ', '');

						if (emails.length > 0) {
							emails = emails.split(',');
						}

						if (emails.length === 1) {
							var count_text = '1 recipient';
						} else {
							var count_text = (emails.length)+' recipients';
						}

						dojo.byId(object.id+'_count').innerHTML = count_text;
					} else {
					}
				}
			</script>
			";
		
		$output->data .= implode("\n", $recipient_selectors);
		$output->data .= "<p>This should be a comma seperated list of email addresses.</p>";
		$output->data .= $this->i("data[email_subject]", array("label"=>"Message Subject", "type"=>"text", "default"=>$template['email_subject'], "dojo"=>"dijit.form.TextBox"));
		$output->data .= $this->i("data[email_body]", array("label"=>"Message Body", "type"=>"wysiwyg", "default"=>$template['email_body'], "parent_form"=>"execution_form"));
		$output->data .= "<p>The following placeholders can be used to dynamically update the header and footer at runtime. %name, %desc, %run, %by, %size</p>";
		$output->data .= "<hr />";
		
		$output->data .= $this->i("submit", array("label"=>"Edit", "type"=>"submit", "value"=>"Edit", "dojoType"=>"dijit.form.Button"));
		$output->data .= $this->f_close();
		return $output;
	}
	
	/**
	 * Display the publish template page
	 *
	 * @param array $template The generic template information
	 */
	function view_add_publish($template) {
		$type = $this->module;
		//prevent the editor from adding more escapes than neccessary
		$template['header'] = stripslashes($template['header']);
		$template['footer'] = stripslashes($template['footer']);
		
		$output->title = "Publishing";
		$output->title_desc = "";
		$output->data .= $this->f($type."/save/".$this->id."/publishsubmit", "id='publishing_form' dojoType='dijit.form.Form'");
		$output->data .= $this->i("data[name]", array("label"=>"Report Name", "default"=>$template['name'], "dojo"=>"dijit.form.TextBox"));
		$output->data .= $this->i("data[description]", array("label"=>"Description", "default"=>$template['description'], "dojo"=>"dijit.form.Textarea"));
		$output->data .= "<hr />";
		
		$output->data .= "<h3>Publishing</h3>";
		$output->data .= $this->i("data[publish_table]", array("label"=>"Publish Tabular Data", "type"=>"checkbox", "default"=>$template['publish_table']));
		$output->data .= $this->i("data[publish_graph]", array("label"=>"Publish Graphic Data", "type"=>"checkbox", "default"=>$template['publish_graph']));
		$output->data .= $this->i("data[publish_csv]", array("label"=>"Publish CSV Data", "type"=>"checkbox", "default"=>true, "disabled"=>true));
		$output->data .= "<hr />";
		
		$output->data .= "<h3>Graph</h3>";
		$output->data .= $this->i("data[graph_type]", array("label"=>"Scatter Graph", "type"=>"radio", "value"=>"Scatter", "default"=>($template['graph_type'] == "Scatter"), "disabled"=>true));
		$output->data .= $this->i("data[graph_type]", array("label"=>"Line Graph", "type"=>"radio", "value"=>"Lines", "default"=>($template['graph_type'] == "Lines")));
		$output->data .= $this->i("data[graph_type]", array("label"=>"Line Graph - Stacked", "type"=>"radio", "value"=>"StackedLines", "default"=>($template['graph_type'] == "StackedLines"), "disabled"=>true));
		$output->data .= $this->i("data[graph_type]", array("label"=>"Area Graph", "type"=>"radio", "value"=>"Areas", "default"=>($template['graph_type'] == "Areas"), "disabled"=>true));
		$output->data .= $this->i("data[graph_type]", array("label"=>"Area Graph - Stacked", "type"=>"radio", "value"=>"StackedAreas", "default"=>($template['graph_type'] == "StackedAreas")));
		$output->data .= $this->i("data[graph_type]", array("label"=>"Bar Graph - Vertical", "type"=>"radio", "value"=>"Columns", "default"=>($template['graph_type'] == "Columns"), "disabled"=>true));
		$output->data .= $this->i("data[graph_type]", array("label"=>"Bar Graph - Vertical, Stacked", "type"=>"radio", "value"=>"StackedColumns", "default"=>($template['graph_type'] == "StackedColumns")));
		$output->data .= $this->i("data[graph_type]", array("label"=>"Bar Graph - Vertical, Clustered", "type"=>"radio", "value"=>"ClusteredColumns", "default"=>($template['graph_type'] == "ClusteredColumns"), "disabled"=>true));
		$output->data .= "<hr />";
		
		$output->data .= "<h3>Page Addenda</h3>";
		$output->data .= $this->i("data[header]", array("type"=>"wysiwyg", "label"=>"Report Header", "default"=>$template['header'], "parent_form"=>"publishing_form"));
		$output->data .= "<p>The following placeholders can be used to dynamically update the header and footer at runtime. %logo, %name, %desc, %run, %by, %size</p>";
		$output->data .= $this->i("data[footer]", array("type"=>"wysiwyg", "label"=>"Report Footer", "default"=>$template['footer'], "parent_form"=>"publishing_form"));
		$output->data .= "<p>The following placeholders can be used to dynamically update the header and footer at runtime. %logo, %name, %desc, %run, %by, %size</p>";
		$output->data .= "<hr />";
		
		$output->data .= $this->i("submit", array("label"=>"Edit", "type"=>"submit", "value"=>"Edit", "dojoType"=>"dijit.form.Button"));
		$output->data .= $this->f_close();
		return $output;
	}
	
	/**
	 * Display the edit constraints view
	 *
	 * @param array $blah The structure information to generate the constraint options
	 */
	function view_add_editconstraints($blah) {
		$type = $this->module;
		$output->title = "Edit Constraint";
		$output->title_desc = "";
		$output->data .= "";
		if (!isset($blah['data']['value'])) {
			$blah['data']['value'] = null;
		}
		if (!isset($blah['data']['type'])) {
			$blah['data']['type'] = null;
		}
		if (!isset($blah['data']['column_id'])) {
			$blah['data']['column_id'] = null;
		}
		if (isset($blah['error'])) {
			$output->data .= "<p style='color: #a40000; font-family: Arial; font-size: 10pt; font-weight: bold;'>".$blah['error']."</p>";
		}
		
		switch ($this->subvar) {
			case "editconstraint":
				$constraint_id = $this->subid;
				$output->data .= $this->f($type."/save/{$this->id}/editconstraintsubmit/{$constraint_id}", "dojoType='dijit.form.Form'");
				$cancel = "<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot().$type."/add/{$this->id}/constraints\"; return false;' name='cancel' >Cancel</button>";
				break;
			case "editsquidconstraint":
				$squid_id = $this->subid;
				$constraint_id = $this->aux1;
				$output->data .= $this->f($type."/save/{$this->id}/editsquidconstraintsubmit/{$squid_id}/{$constraint_id}", "dojoType='dijit.form.Form'");
				$cancel = "<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot().$type."/add/{$this->id}/y/squidconstraints/{$squid_id}\"; return false;' name='cancel' >Cancel</button>";
				break;
		}
		$output->data .= $this->source_column_i("data[column_id]", $blah['options']['columns'], $blah['data']['column_id'], "constraint_input_toggle(this);");
		$output->data .= $this->i("data[type]", array("id"=>"data[type]", "label"=>"&nbsp;", "type"=>"select", "default"=>$blah['data']['type'], "options"=>$blah['options']['type'], "dojoType"=>"dijit.form.FilteringSelect"));
		$output->data .= $this->i("data[value_text]", array("id"=>"data[value_text]", "div_id"=>"value_text_div", "label"=>"&nbsp;", "type"=>"text", "value"=>$blah['data']['value'], "dojoType"=>"dijit.form.ValidationTextBox"));
		$output->data .= $this->i("data[value_date]", array("id"=>"data[value_date]", "div_id"=>"value_date_div", "label"=>"&nbsp;", "type"=>"text", "value"=>$blah['data']['value'], "dojoType"=>"dijit.form.DateTextBox"));
		
		if (!empty($blah['column_options'])) {
			foreach ($blah['column_options'] as $column_id => $column_options) {
				$output->data .= $this->i("data[value_select_$column_id]", array("id"=>"data[value_select_$column_id]", "div_id"=>"value_select_div_$column_id", "label"=>"&nbsp;", "type"=>"select", "default"=>$blah['data']['value'], "options"=>array(), "dojoType"=>"dijit.form.FilteringSelect"));
			}
		}
		
		$output->data .= "<p id='dropdown_loading' style='display: none;'>Loading possible options...</p>";
		$output->data .= $this->i("data[value_inputs]", array("id"=>"data[value_inputs]", "type"=>"hidden", "default"=>json_encode(array())));
		$output->data .= $this->i("data[value_input_selected]", array("id"=>"data[value_input_selected]", "type"=>"hidden", "default"=>""));
		$output->data .= "
			<script>
				dojo.addOnLoad(hideAll);
				var column_options = ".json_encode((array)$blah['column_options']).";
				var types = ".json_encode((array)$blah['column_types']).";
				var value_text_div = dojo.byId('value_text_div');
				var value_date_div = dojo.byId('value_date_div');
				
				var skoo = [];
				skoo[skoo.length] = 'value_text';
				skoo[skoo.length] = 'value_date';
				
				//function constraint_input_toggle_init() {
				//	dojo.connect(dijit.byId('data[column_id]'), 'onChange', 'constraint_input_toggle');
				//	dojo.connect(dijit.byId('data[type]'), 'onChange', 'constraint_input_toggle');
				//	constraint_input_toggle();
				//}
				function hideAll() {
					value_text_div.style.display = 'none';
					value_date_div.style.display = 'none';
					for (var i in column_options) {
						dojo.byId('value_select_div_'+i).style.display = 'none';
						skoo[skoo.length] = 'value_select_'+i;
					}
				}
				
				function constraint_input_toggle(o) {
					hideAll();
					update_join_display(o);
					dojo.byId('data[value_inputs]').value = skoo;
					
					if (dijit.byId('data[type]').value != 'like') {
						if (column_options[o.value]) {
							dojo.byId('value_select_div_'+o.value).style.display = 'block';
							dojo.byId('dropdown_loading').style.display = 'block';
							dijit.byId('data[value_select_'+o.value+']').setAttribute('disabled', true);
							var pantryStore = new dojo.data.ItemFileReadStore({url: '".$this->webroot().$type."/constraint_column_options_ajax/".$this->id."/'+o.value});
							pantryStore.fetch({
								onComplete: function () {
									dijit.byId('data[value_select_'+o.value+']').setAttribute('disabled', false);
									dojo.byId('dropdown_loading').style.display = 'none';
									return true;
								}
							});
							dijit.byId('data[value_select_'+o.value+']').store = pantryStore;
							dojo.byId('data[value_input_selected]').value = 'value_select_'+o.value;
							return;
						}
						
						if (types[o.value] == 'date') {
							value_date_div.style.display = 'block';
							dojo.byId('data[value_input_selected]').value = 'value_date';
							return;
						}
					}
					
					value_text_div.style.display = 'block';
					dojo.byId('data[value_input_selected]').value = 'value_text';
					return;
				}
			</script>
			";
		
		$output->data .= "<hr />";
		$output->data .= "<div id='join_display'>";
		//TODO: Not required - DELETE $output .= $table_join_ajax;
		$output->data .= "</div>";
		$output->data .= "
			<div class='input'>
				{$cancel}<button type='submit' value='Next' dojoType='dijit.form.Button' name='submit' >Save</button>
			</div>
			";
		$output->data .= $this->f_close();
		return $output;
	}
	
	/**
	 * Display the add constraints view
	 *
	 * @param array $blah The structure information to generate the constraint options
	 */
	function view_add_constraints($blah) {
		$type = $this->module;
		$output->title = "Constraints";
		$output->title_desc = "";
		$output->data = "";
		if (!empty($blah['constraints']) && count($blah['constraints']) > 0) {
			$output->data .= "<h3>Constraint Logic</h3>";
			$constraint_index = 1;
			foreach ($blah['constraints'] as $constraint_tmp) {
				$constraints_ascii[$constraint_index] = chr($constraint_index);
				$constraints_id[$constraint_index] = $constraint_tmp['constraint_id'];
				$constraints_text[$constraint_index] = $constraint_tmp['constraint'];
				$constraint_index ++;
			}
		
			$logic_ascii = $blah['logic'];
			foreach ($constraints_id as $constraint_index_tmp => $constraint_id_tmp) {
				$logic_ascii = str_replace($constraint_id_tmp, $constraints_ascii[$constraint_index_tmp], $logic_ascii);
			}
		
			$output->data .= "
				<script>
					";
			$output->data .= "
					var constraints_ascii = new Array;
					";
			foreach ($constraints_ascii as $i => $tmp) {
				$output->data .= "
					constraints_ascii[$i] = '$tmp';
					";
			}

			$output->data .= "
					var constraints_id = new Array;
					";
			foreach ($constraints_id as $i => $tmp) {
				$output->data .= "
					constraints_id[$i] = '$tmp';
					";
			}
			$output->data .= "
					var constraints_text = new Array;
					";
			foreach ($constraints_text as $i => $tmp) {
				$output->data .= "
					constraints_text[$i] = '".str_replace("'", "\'", $tmp)."';
					";
			}

			$output->data .= file_get_contents($this->sw_path."modules/template/constraints_ui.js");
			$output->data .= "
				</script>
				<style>
					#confoo_div {
						margin: 20px 0px;
						padding: 10px;
						border: 1px solid #d3d7cf;
					}
						#confoo_div span {
							position: relative;
							border: 1px solid white;
							vertical-align: middle;
						}
							#confoo_div span.constraint {
								color: #888a85;
							}
							#confoo_div span.bracket {
								border: 1px solid #fce94f;
								background-color: #fce94f;
							}
							#confoo_div span.cursor_before {
								border-left: 1px solid black;
							}
							#confoo_div span.selected {
								border: 1px solid #204a87;
								background-color: #204a87;
								color: #eeeeec;
							}
							#confoo_div span.constraint.selected {
								color: #babdb6;
							}
					#confoo_in {
						width: 0px;
						height: 0px;
						position: absolute;
						margin: 0px;
						padding: 0px;
						border: 0px;
					}
				</style>
				";
			if ($this->subvar == "constraints") {
				$output->data .= $this->f($type."/save/{$this->id}/constraintlogicsubmit/{$this->subid}", "dojoType='dijit.form.Form'");
			} else if ($this->subid == "squidconstraints") {
				$output->data .= $this->f($type."/save/{$this->id}/{$this->subvar}/squidconstraintlogicsubmit/{$this->aux1}", "dojoType='dijit.form.Form'");
			}
			$output->data .= "
				<div id='confoo_div'></div>
				<input type='text' id='confoo_in' name='data[constraint_logic]' value='$logic_ascii' autocomplete='off' />
				<input type='hidden' id='confoo_old' value='$logic_ascii' />
				<input type='hidden' name='data[constraints_id]' value='".json_encode($constraints_id)."' />
				<input type='hidden' name='data[constraints_ascii]' value='".json_encode($constraints_ascii)."' />
				<div id='confoo_save'>
					<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=window.location; return false;' name='cancel' >Cancel</button>
					<button type='submit' value='Save' dojoType='dijit.form.Button' name='save' >Save</button>
				</div>
				";
			$output->data .= $this->f_close();
		}
		
		$output->data .= "<h3>Constraints</h3>";
		if ($this->subvar == "constraints") {
			$output->data .= "<a href='".$this->webroot().$type."/add/".$this->id."/editconstraint/new'>Create Constraint</a>";
		} else if ($this->subid == "squidconstraints") {
			$output->data .= "<a href='".$this->webroot().$type."/add/".$this->id."/editsquidconstraint/".$this->aux1."/new'>Create Constraint</a>";
		}
		
		if (!empty($blah['constraints'])) {
			$output->data .= "
				<div class='reports'>
					<table cellpadding='0' cellspacing='0'>
						<tr>
							<th>Constraint</th>
							<th>&nbsp;</th>
						</tr>
						";
			
			foreach ($blah['constraints'] as $constraint_tmp) {
				$constraint_id = $constraint_tmp['constraint_id'];
				$output->data .= "<tr>";
				$output->data .= "<td>";
				switch ($constraint_tmp['foobar']) {
					case "constraint":
						$output->data .= "<span class='".$constraint_tmp['foobar']."'>";
						$output->data .= $constraint_tmp['constraint'];
						$output->data .= "</span>";
						break;
				}
				$output->data .= "</td>";
				$output->data .= "<td>";
				$output->data .= "<ul>";
				
				switch ($constraint_tmp['foobar']) {
					case "constraint":
						if ($blah['default']) {
							if ($this->subvar == "constraints") {
								$output->data .= "<li><a href='".$this->webroot().$type."/add/".$this->id."/editconstraint/".$constraint_id."'>Edit</a></li>";
								$output->data .= "<li><a href='".$this->webroot().$type."/save/".$this->id."/removeconstraintsubmit/".$constraint_id."' onclick='if (confirm(\"Remove constraint?\")) {return true;} else {return false;}'>Remove</a></li>";
							} else if ($this->subid == "squidconstraints") {
								$output->data .= "<li><a href='".$this->webroot().$type."/add/{$this->id}/editsquidconstraint/{$this->aux1}/{$constraint_id}'>Edit</a></li>";
// 								$output .= "<li><a href='".$this->webroot().$type."/save/{$this->id}/removeconstraintsubmit/{$this->aux1}/{$constraint_id}' onclick='if (confirm(\"Remove constraint?\")) {return true;} else {return false;}'>Remove</a></li>";
							}
						} else {
							$output->data .= "<li>&nbsp;</li>";
						}
						break;
				}
				$output->data .= "</ul>";
				$output->data .= "</td>";
				$output->data .= "</tr>";
			}
			$output->data .= "
					</table>
				</div>
				";
		} else {
			$output->data .= "<p>No constraints can be found.</p>";
		}
		return $output;
	}
	
	function view_dd_json($values) {
		$output->layout = 'ajax';
		$output->data = "{
	identifier:'id',
	items: [";
	$opt = array();
	foreach ($values as $i => $option) {
		$opt[] = "{id: '".$i."', name: '".$option."'}";
	}
	$output->data .= implode(",", $opt);
	$output->data .= "]
}
		";
		return $output;
	}
	
	function view_save_reports() {
		$output->layout = "ajax";
		$output->data = "Results Saved";
		return $output;
	}

	function view_saved_list($reports) {
 		$output->layout = "ajax";
		$output->title = "Saved Report - ".$reports[0]['name'];
//		$output->data = "<div dojoType='dojox.layout.ContentPane' layoutAlign='client' id='report_workspace'>";
		if (is_array($reports)) {
			foreach ($reports as $i => $report) {
				$output->data .= Template_View::theme_saved_reports($report);
			}
		}
//		$output->data .= "</div>";
		return $output;
	}

	function view_workspace_display($reports) {
		$output->title = "Reports";

		$output->data .= $this->l("template/add", "Create Report");

		if (is_array($reports) && count($reports) > 0) {
			$output->data .= "
				<div class='reports'>
					<table cellpadding='0' cellspacing='0'>
						<tr>
							<th>Name</th>
							<th>&nbsp;</th>
							<th>Type</th>
							<th>Created</th>
							<th>&nbsp;</th>
						</tr>
						";

			foreach ($reports as $i => $report) {
				$view_id = null;

				if ($report['saved'] > 0) {
					$view_id = $report['template_id'];
				}
				$output->data .= Template_View::theme_reports($report, $view_id);
			}

			$output->data .= "
					</table>
				</div>
				";
		} else {
			$output->data .= "<p>No reports can be found.</p>";
		}

// 		$output->data .= "</div>";
		return $output;
	}

	/**
	 * This will display a single report line. Function links are only available to users with the appropriate permission.
	 *
	 * @param array $report A list of reports
	 * @param int $view_id The id of the report
	 */
	function theme_reports($report, $view_id) {
		$theme = $this->get_theme();
		$webroot = $this->webroot();
		$themeroot = $webroot.'themes/'.$theme.'/';
		$output = "";

		$run = $report['last_run'] ? date("Y-m-d H:i:s", strtotime($report['last_run'])) : 'never run';
		$time = $report['last_run'] ? $report['last_time']." seconds" : 'never run';
		$by = $report['last_by'] ? $report['last_by'] : 'never run';
		$size = $report['last_size'] ? $report['last_size'] : 'never run';
		$desc = !empty($report['description']) ? $report['description'] : "&nbsp;";

		if (empty($report['last_run'])) {
			$run = "never run";
		} else if (time() - strtotime($report['last_run']) < 60) {
			$run = round(time() - strtotime($report['last_run']))." second(s) ago";
		} else if (time() - strtotime($report['last_run']) < 60 * 60) {
			$run = round((time() - strtotime($report['last_run'])) / (60))." minute(s) ago";
		} else if (time() - strtotime($report['last_run']) < 60 * 60 * 24) {
			$run = round((time() - strtotime($report['last_run'])) / (60 * 60))." hour(s) ago";
		} else if (time() - strtotime($report['last_run']) < 60 * 60 * 24 * 3) {
			$run = round((time() - strtotime($report['last_run'])) / (60 * 60 * 24))." day(s) ago";
		}

		$output .= "
			<tr>
				<td>".$report['name']."</td>
				<td>".$report['description']."</td>
				";

		switch ($report['module']) {
			case "tabular":
				$output .= "<td>Tabular</td>";
				break;
			case "listing":
				$output .= "<td>List</td>";
				break;
			default:
				$output .= "<td>&nbsp;</td>";
				break;
		}

		$output .= "
				<td>&nbsp;</td>
				<td>
					<ul>
					";

		if ($report['permission_histories'] == true) {
			$output .= "<li>".$this->l($report['module']."/histories/".$report['template_id'], "Histories")."</li>";
		}
		if ($report['permission_execute'] == true) {
			$output .= "<li>".$this->l($report['module']."/execute_manually/".$report['template_id'], "Execute", "onclick='if (confirm(\"Execute report?\")) {return true;} else {return false;}'")."</li>";
		}
		if ($report['permission_edit'] == true) {
			$output .= "<li>".$this->l($report['module']."/add/".$report['template_id'], "Edit")."</li>";
			//$output .= "<li><a href=''>Duplicate</a></li>";
			$output .= "<li>".$this->l($report['module']."/delete/".$report['template_id'], "Remove", "onclick='if (confirm(\"Remove report?\")) {return true;} else {return false;}'")."</li>";
		}
		$output .= "</ul>
				</td>
			</tr>
			";
		return $output;
	}

	function view_constraint_options_json($options) {
		$output->layout = 'ajax';
		$output->data = "{
	identifier:'id',
	items: [";
	$opt = array();
	foreach ($options as $i => $option) {
		$opt[] = "{name: '".$option."', label: '".$option."', id: '".$i."'}";
	}
	$output->data .= implode(",", $opt);
	$output->data .= "]
}
		";
		return $output;
	}

	/**
	 * Output the markup for the table join radio buttons (called via ajax)
	 *
	 * @param $table_join_markup
	 * @return The HTML string output
	 */
	function view_table_join_ajax($table_join_markup) {
		$output->layout = "ajax";
		$output->data = "<h3>Axis Relationship</h3>";
		$output->data .= "<p class='h3attach'>The selected column may be linked to the primary column, by one of a number of different routes.</p>";
		
		$output->data .= $table_join_markup;
		$output->data .= "<hr />";
		return $output;
	}
	
	/**
	 * Display a list of values for a dropdown constraint column
	 *
	 * @param string $column_options_json A json string of values
	 * @return The JSON string output
	 */
	function view_constraint_column_options_ajax($column_options_json) {
		$output->layout = "ajax";
		$output->data = $column_options_json;
		return $output;
	}

	/**
	 * Display the preview output via ajax
	 *
	 * @param string $data_preview The preview HTML
	 * @return The display object
	 */
	function view_data_preview_ajax($data_preview) {
		$output->layout = "ajax";
		$output->data = $data_preview;
		return $output;
	}
	
}
?>
