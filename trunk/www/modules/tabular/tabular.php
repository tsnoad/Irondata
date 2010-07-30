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
 * Tablular.php
 *
 * The Table report template module.
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */
class Tabular extends Template {
	var $conn;
	var $dobj;
	var $name = "Tabular";
	var $description = "A tabular report type. Multiple axis' with a numeric intersection between them.";
	var $module_group = "Core";
	
	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_permission_check()
	 */
	function hook_permission_check($data) {
		//admin will automatically have access. No need to specify
		switch ($data['function']) {
			case "hook_admin_tools":
				if (isset($data['acls']['system']['admin'])) {
					return true;
				}
				break;
			case "hook_pagetitle":
			case "hook_top_menu":
			case "hook_javascript":
			case "hook_workspace":
			case "hook_template_entry":
				// these can be called by other modules
				if (isset($data['acls']['system']['login'])) {
					return true;
				}
				return false;
				break;
			case "view_add_select_object":
				//only people with permission to create reports can access these functions
				if (isset($data['acls']['system']['reportscreate'])) {
					return true;
				}
				return false;
				break;
			case "view_histories":
			case "view_history":
			case "view_processing_history_ajax":
				//only people with permission to create reports can access these functions
				//if (isset($data['acls']['system']['reportscreate'])) {
				//	return true;
				//}
				//or users with permission to access a specific report
				if (isset($data['acls']['report'][$this->id]['histories'])) {
					return true;
				}
				return false;
				break;
			case "view_execute_manually":
				//only people with permission to create reports can access these functions
				//if (isset($data['acls']['system']['reportscreate'])) {
				//	return true;
				//}
				//or users with permission to execute a specific report
				if (isset($data['acls']['report'][$this->id]['execute'])) {
					return true;
				}
				return false;
				break;
			case "view_add":
			case "view_save":
			case "view_table_join_ajax":
			case "get_columns":
			case "hook_output":
			case "view_data_preview_ajax":
			case "view_data_preview_first_ajax":
			case "view_data_preview_slow_ajax":
			case "view_constraint_column_options_ajax":
			case "hook_recipient_selector":
			default:
				//only people with permission to create reports can access these functions
				//if (isset($data['acls']['system']['reportscreate'])) {
				//	return true;
				//}
				//or users with permission to edit a specific report
				if (isset($data['acls']['report'][$this->id]['edit'])) {
					return true;
				}
				return false;
				break;
		}
		return false;
/*

	function add_axis_automatic($type, $columns) {
	function add_axis_manual($type, $columns) {
	function add_axis_trend($type, $columns) {
	function execute_demo_cellwise($template_id) {
	function execute_demo_quick($template_id) {
	function execute_demo($template_id) {
	function execute_manually($template_id) {
	function execute_scheduled($template_id) {
	function execute($template_id, $demo, $quick=false, $cellwise=false) {
	function get_constraint_logic($template_id) {
	function get_constraints($template_id) {
	function hook_menu() {
	function hook_permission_check($data) {
	function hook_query($template, $constraints, $constraint_logic=null, $demo=false, $axis_limits=null) {
	function hook_recipients($template_id, $template_recipients=null) {
	function hook_run($demo=false, $data_only=false, $draft=true, $template_id=null) {
	function view_add_axis() {
	function view_add_axis($type, $columns) {
	function view_add_intersection() {
	function view_add_intersection($columns) {
	function view_add_next() {
	function view_clone() {
	function view_constraintlogicsubmit() {
	function view_constraints() {
	function view_constraints($blah) {
	function view_display_table() {
	function view_display_table($tables, $template) {
	function view_editconstraint() {
	function view_editconstraint($blah) {
	function view_editconstraintsubmit() {
	function view_execute_manually() {
	function view_execute_scheduled() {
	function view_execute_scheduled($data=array()) {
	function view_interval_dd_json() {
	function view_remove() {
	function view_remove_constraint() {
	function view_run() {
	function view_run($template, $constraints) {
	function view_tables_json() {
	function view_tables_json($tables) {
*/
	}
	
	/**
	 * Overwrite hook_top_menu in Template.php - this module should have no top menu
	 *
	 * (non-PHPdoc)
	 * @see modules/template/Template::hook_top_menu()
	 */
	function hook_top_menu() {
		return null;
	}

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_menu()
	 */
	function hook_menu() {
		//Steps: what steps have been competed, and what step are we at
		$tabular_templates_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT tt.*, tta.tabular_templates_auto_id, ttt.tabular_templates_trend_id, tts.tabular_templates_single_id, ttm.tabular_templates_manual_id FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_single tts ON (tts.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_manual ttm ON (ttm.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND ((tt.axis_type = 'auto' AND tta.tabular_templates_auto_id IS NOT NULL) OR (tt.axis_type = 'trend' AND ttt.tabular_templates_trend_id IS NOT NULL) OR (tt.axis_type = 'single' AND tts.tabular_templates_single_id IS NOT NULL) OR (tt.axis_type = 'manual' AND ttm.tabular_templates_manual_id IS NOT NULL));"));
		
		if (!empty($tabular_templates_query)) {
			foreach ($tabular_templates_query as $tabular_template_tmp) {
				$tabular_templates[$tabular_template_tmp['type']] = $tabular_template_tmp;
			}
		}
		
		$steps = array();
		//put all step data in a usable array
		$steps[0][3] = "";
		if (empty($tabular_templates['c'])) {
			$steps[0][0] = "Add Intersection";
			$steps[0][2] = false;
			$steps[0][3] = "disabled";
		} else {
			$steps[0][0] = "Edit Intersection";
			$steps[0][2] = true;
		}
		$steps[0][1] = $this->webroot()."tabular/add/".$this->id."/c/source";
		if ($this->subvar == "c") $steps[0][3] .= " current";
		
		$steps[1][3] = "";
		if (empty($tabular_templates['x'])) {
			$steps[1][0] = "Add X Axis";
			$steps[1][2] = false;
			$steps[1][3] = "disabled";
		} else {
			$steps[1][0] = "Edit X Axis";
			$steps[1][2] = true;
		}
		$steps[1][1] = $this->webroot()."tabular/add/".$this->id."/x/type";
		if ($this->subvar == "x") $steps[1][3] .= " current";
		
		$steps[2][3] = "";
		if (empty($tabular_templates['y'])) {
			$steps[2][0] = "Add Y Axis";
			$steps[2][2] = false;
			$steps[2][3] = "disabled";
		} else {
			$steps[2][0] = "Edit Y Axis";
			$steps[2][2] = true;
		}
		$steps[2][1] = $this->webroot()."tabular/add/".$this->id."/y/type";
		if ($this->subvar == "y") $steps[2][3] .= " current";
		
		$valid = !empty($tabular_templates['c']) && !empty($tabular_templates['x']) && !empty($tabular_templates['y']);
		$parent_steps = parent::hook_menu($valid, $valid, $valid, $valid, $valid);
		$steps = array_merge($steps, $parent_steps);
		
		return $steps;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template::hook_admin_tools()
	 */
	function hook_admin_tools() {
		return null;
	}

	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template::hook_workspace()
	 */
	function hook_workspace() {
		return null;
	}

	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template::hook_javascript()
	 */
	function hook_javascript() {
		$js = parent::hook_javascript();
		return $js."
		function update_join_display(o) {
			var passContent = {};
			passContent[o.name] = o.value;
			ajax_load('".$this->webroot()."tabular/table_join_ajax/".$this->id."', passContent, 'join_display');
		}
		";
	}
	
	/**
	 * The Template hook function.
	 * Is this module available within the Templates
	 *
	 * @return Returns an array describing the entry in the new template screen
	 */
	function hook_template_entry() {
		return array(
			"label"=>"Tabular Report",
			"module"=>"tabular",
			"description"=>"A tabular report takes numerical values from a selected database column, and indexes them by unique values in the X axis and Y axis, also taken from database columns, that have a relationship with the first column."
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template::view_add_select_object()
	 */
	function view_add_select_object() {
		$object_id = $this->id;

		if (empty($object_id)) return;
		$temp = parent::view_add_select_object($object_id);
		$this->redirect("tabular/add/".$temp['template_id']);
	}

	/**
	 * First point of contact for almost every page, when createing a tabular report.
	 * Runs queries to gather data to display in Tabular_View::view_add().
	 * Takes aguments about which page from the url in the id, subvar, subid, etc variables
	 *
	 * (non-PHPdoc)
	 * @see modules/template/Template::view_add()
	 */
	function view_add() {
		//define default variables
		$table_join_ajax = null;
		$tabular_template = null;
		$tabular_template_auto = null;
		$blah = null; //TODO: bad variable name. Must change
		
		switch ($this->subvar) {
			case "x":
			case "y":
				switch ($this->subid) {
					case "type":
						if ((int)$this->id) {
							$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

							if (!empty($tabular_templates_query)) {
								$tabular_template = $tabular_templates_query;
							}
						}
						break;
					case "source":
					case "autosource":
					case "trendsource":
						if ((int)$this->id) {
							$this->current = $this->get_template($this->id);
							$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

							$blah = array();
							foreach ($tables['catalogue'] as $i => $column) {
								foreach ($column as $j => $cell) {
									$blah[$cell['column_id']] = $cell;
								}
							}

							if ($this->subid == "autosource") {
								$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));
							} else if ($this->subid == "trendsource") {
								$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));
							}

							if (!empty($tabular_templates_query)) {
								$tabular_template_auto = $tabular_templates_query;

								//edit times from "2009-06-01 00:00:00" to "2009-06-01": dojo doesn't understand times in datetextbox
								if (isset($tabular_template_auto['start_date'])) {
									$tabular_template_auto['start_date'] = substr($tabular_template_auto['start_date'], 0, strpos($tabular_template_auto['start_date'], " "));
								}
								if (isset($tabular_template_auto['end_date'])) {
									$tabular_template_auto['end_date'] = substr($tabular_template_auto['end_date'], 0, strpos($tabular_template_auto['end_date'], " "));
								}

								$_REQUEST['data']['column_id'] = $tabular_template_auto['column_id'];
							} else {
								$_REQUEST['data']['column_id'] = reset(array_keys($blah));
							}

							$table_join_ajax = $this->view_table_join_ajax($tabular_template_auto['table_join_id']);
							$table_join_ajax = $table_join_ajax->data;
							unset($_REQUEST['data']['column_id']);
						}
						break;
					case "manualsource":
						if ((int)$this->id) {
							$tabular_templates_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_manual ttm ON (ttm.tabular_template_id=tt.tabular_template_id) INNER JOIN tabular_templates_manual_squids ttms ON (ttms.tabular_templates_manual_id=ttm.tabular_templates_manual_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' ORDER BY ttms.human_name ASC;"));

							$tabular_template_auto = $tabular_templates_query;
						}
						break;
					case "squidname":
						if ($this->aux1 != "new") {
							$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates_manual_squids ttms WHERE ttms.tabular_templates_manual_squid_id='{$this->aux1}' LIMIT 1;"));

							$tabular_template_auto = $tabular_templates_query;
						}
						break;
					case "squidconstraints":
						$blah = Tabular::view_constraints();
						break;
					default:
						break;
				}
				$output = Tabular_View::view_add_xy($blah, $tabular_template_auto, $table_join_ajax, $tabular_template);
				break;
			case "c":
				switch ($this->subid) {
					case "type":
						break;
					case "source":
						if ((int)$this->id) {
							$this->current = $this->get_template($this->id);
							$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

							$blah = array();
							foreach ($tables['catalogue'] as $i => $column) {
								foreach ($column as $j => $cell) {
									$blah['options'][$cell['column_id']] = $cell;

									switch ($cell['data_type']) {
										default:
											break;
										case "text":
											$blah['option_warnings'][$cell['column_id']] = "Warning: The data type of the selected Source Column is ".ucwords($cell['data_type']).". This may cause unexpected results when calculating the Sum, Minimum, Maximum or Average values.";
											break;
									}
								}
							}
							
							$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='c' LIMIT 1;"));
							
							if (!empty($tabular_templates_query)) {
								$tabular_template_auto = $tabular_templates_query;
							}
						}
						break;
					default:
						break;
				}
				$output = Tabular_View::view_add_c($blah, $tabular_template_auto);
				break;
			case "editsquidconstraint":
				if ($this->subid) {
					$blah = array();
					
					list($blah, $table_join_ajax) = Tabular::view_editconstraint();
				}
				$output = Tabular_View::view_add_editsquidconstraint($blah);
				break;
			default:
				$output = parent::view_add();
				if ($output == null) {
					// This action did not exist in template.php
					$this->view_add_next();
				}
				break;
		}
		
		return $output;
	}

	/**
	 * Called as the action on forms on almost every page when creating a tabular report.
	 * Once complete, calls Tabular::view_add_next() to go to the next page.
	 * Takes aguments about which page from the url in the id, subvar, subid, etc variables
	 *
	 * @return null
	 */
	function view_save() {
		switch ($this->subvar) {
			case "cancel":
				break;
			case "x":
			case "y":
				switch ($this->subid) {
					case "typesubmit":
						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_template_id']) {
							$this->dobj->db_query($this->dobj->update(array("axis_type"=>$_REQUEST['data']['axis_type']), "tabular_template_id", $update_query['tabular_template_id'], "tabular_templates"));
						} else {
							$this->dobj->db_query($this->dobj->insert(array("template_id"=>$this->id, "type"=>$this->subvar, "axis_type"=>$_REQUEST['data']['axis_type']), "tabular_templates"));
						}
						break;
					case "autosourcesubmit":
						$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));
						$tabular_template_id = $tabular_template_query['tabular_template_id'];
						$_REQUEST['data']['tabular_template_id'] = $tabular_template_id;
						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));
						if ($update_query['tabular_templates_auto_id']) {
							if (empty($_REQUEST['data']['table_join_id'])) $_REQUEST['data']['table_join_id'] = "";
							$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_templates_auto_id", $update_query['tabular_templates_auto_id'], "tabular_templates_auto"));
						} else {
							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_auto"));
						}
						break;
					case "trendsourcesubmit":
						$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

						$tabular_template_id = $tabular_template_query['tabular_template_id'];
						$_REQUEST['data']['tabular_template_id'] = $tabular_template_id;

						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_templates_trend_id']) {
							$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_templates_trend_id", $update_query['tabular_templates_trend_id'], "tabular_templates_trend"));
						} else {
							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_trend"));
						}
						break;
					case "singlesourcesubmit":
						$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

						$tabular_template_id = $tabular_template_query['tabular_template_id'];
						$_REQUEST['data']['tabular_template_id'] = $tabular_template_id;

						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_single tts ON (tts.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_templates_single_id']) {
						} else {
							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_single"));
						}
						break;
					case "manualsourcesubmit":
						break;
					case "removesquidsubmit":
						$this->dobj->db_query("DELETE FROM tabular_templates_manual_squids WHERE tabular_templates_manual_squid_id='{$this->aux1}';");
						break;
					case "squidnamesubmit":
						$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));
						$tabular_template_id = $tabular_template_query['tabular_template_id'];

						$tabular_templates_manual_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates_manual ttm WHERE ttm.tabular_template_id='".$tabular_template_id."' LIMIT 1;"));
						$tabular_templates_manual_id = $tabular_templates_manual_query['tabular_templates_manual_id'];

						if (empty($tabular_templates_manual_id)) {
							$tabular_templates_manual_id = $this->dobj->nextval("tabular_templates_manual");

							$this->dobj->db_query($this->dobj->insert(array("tabular_templates_manual_id"=>$tabular_templates_manual_id, "tabular_template_id"=>$tabular_template_id), "tabular_templates_manual"));
						}

						if ($this->aux1 == "new") {
							$tabular_templates_manual_squid_id = $this->dobj->nextval("tabular_templates_manual_squids");

							$_REQUEST['data']['tabular_templates_manual_squid_id'] = $tabular_templates_manual_squid_id;
							$_REQUEST['data']['tabular_templates_manual_id'] = $tabular_templates_manual_id;
							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_manual_squids"));

							$this->aux1 = $tabular_templates_manual_squid_id;
						} else if (!empty($this->aux1)) {
							$tabular_templates_manual_squid_id = $this->aux1;

							$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_templates_manual_squid_id", $tabular_templates_manual_squid_id, "tabular_templates_manual_squids"));
						}

						break;
					case "squidconstraintlogicsubmit":
						if ($this->aux1) {
							Tabular::view_constraintlogicsubmit();
						}
						break;
					default:
						break;
				}
				break;
			case "c":
				switch ($this->subid) {
					case "sourcesubmit":
						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_template_id']) {
						} else {
							$this->dobj->db_query($this->dobj->insert(array("template_id"=>$this->id, "type"=>$this->subvar, "axis_type"=>"auto"), "tabular_templates"));
						}

						if ($update_query['tabular_templates_auto_id']) {
							$tabular_template_id = $update_query['tabular_template_id'];

							$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_template_id", $tabular_template_id, "tabular_templates_auto"));
						} else {
							$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

							$tabular_template_id = $tabular_template_query['tabular_template_id'];
							$_REQUEST['data']['tabular_template_id'] = $tabular_template_id;

							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_auto"));
						}
						break;
					default:
						break;
				}
				break;
			case "editsquidconstraintsubmit":
				if ($this->aux1) {
					Tabular::view_editconstraintsubmit();
				}
				break;
			default:
				parent::view_save();
				break;
		}
		
		$this->view_add_next();
		return;
	}

	/**
	 * View Add Next
	 *
	 * Called by Tabular::view_save() and others. Works out the present status of a report, if anything step needs to be performed (like add in axis), or if not, what is the next step after the current, then redirects accordingly
	 *
	 */
	function view_add_next() {
		if (empty($this->id)) {
			$this->redirect("template/home/");
			return;
		}

		$tabular_template_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."'"));

		if (!empty($tabular_template_query)) {
			foreach ($tabular_template_query as $tabular_template_tmp) {
				$tabular_template[$tabular_template_tmp['type']] = $tabular_template_tmp;
			}
		}

		if ($this->subvar == "x" && $this->subid == "typesubmit" && $tabular_template['x']['axis_type'] == "auto") {
			$this->redirect("tabular/add/".$this->id."/x/autosource");

		} else if ($this->subvar == "x" && $this->subid == "typesubmit" && $tabular_template['x']['axis_type'] == "trend") {
			$this->redirect("tabular/add/".$this->id."/x/trendsource");

		} else if ($this->subvar == "y" && $this->subid == "typesubmit" && $tabular_template['y']['axis_type'] == "auto") {
			$this->redirect("tabular/add/".$this->id."/y/autosource");

		} else if ($this->subvar == "y" && $this->subid == "typesubmit" && $tabular_template['y']['axis_type'] == "trend") {
			$this->redirect("tabular/add/".$this->id."/y/trendsource");

		} else if ($this->subvar == "y" && $this->subid == "typesubmit" && $tabular_template['y']['axis_type'] == "single") {
			$this->redirect("tabular/save/".$this->id."/y/singlesourcesubmit");

		} else if ($this->subvar == "y" && $this->subid == "typesubmit" && $tabular_template['y']['axis_type'] == "manual") {
			$this->redirect("tabular/add/".$this->id."/y/manualsource");

		} else if ($this->subvar == "y" && $this->subid == "removesquidsubmit" && $tabular_template['y']['axis_type'] == "manual") {
			$this->redirect("tabular/add/".$this->id."/y/manualsource");

		} else if ($this->subvar == "y" && $this->subid == "squidnamesubmit" && $tabular_template['y']['axis_type'] == "manual") {
			$this->redirect("tabular/add/".$this->id."/y/squidconstraints/".$this->aux1);

		} else if ($this->subvar == "y" && $this->subid == "squidconstraintlogicsubmit" && $tabular_template['y']['axis_type'] == "manual") {
			$this->redirect("tabular/add/".$this->id."/y/squidconstraints/".$this->aux1);

		//if no intersection is selected...
		} else if (empty($tabular_template['c'])) {
			//... go to the intersection page
			$this->redirect("tabular/add/".$this->id."/c/source");

		} else if (empty($tabular_template['c']['tabular_templates_auto_id'])) {
			$this->redirect("tabular/add/".$this->id."/c/source");

		//if no x axis is selected...
		} else if (empty($tabular_template['x'])) {
			//... go to the x axis page
			$this->redirect("tabular/add/".$this->id."/x/type");

		} else if ($tabular_template['x']['axis_type'] == "auto" && empty($tabular_template['x']['tabular_templates_auto_id'])) {
			$this->redirect("tabular/add/".$this->id."/x/autosource");

		} else if ($tabular_template['x']['axis_type'] == "trend" && empty($tabular_template['x']['tabular_templates_trend_id'])) {
			$this->redirect("tabular/add/".$this->id."/x/trendsource");

		//if no y axis is selected...
		} else if (empty($tabular_template['y'])) {
			//... go to the y axis page
			$this->redirect("tabular/add/".$this->id."/y/type");

		} else if ($tabular_template['y']['axis_type'] == "auto" && empty($tabular_template['y']['tabular_templates_auto_id'])) {
			$this->redirect("tabular/add/".$this->id."/y/autosource");

		} else if ($tabular_template['y']['axis_type'] == "trend" && empty($tabular_template['y']['tabular_templates_trend_id'])) {
			$this->redirect("tabular/add/".$this->id."/y/trendsource");

		} else if ($this->subvar == "editsquidconstraintsubmit") {
			$this->redirect("tabular/add/".$this->id."/y/squidconstraints/".$this->subid);

		} else if ($this->subvar == "editconstraintsubmit") {
			$this->redirect("tabular/add/".$this->id."/constraints");

		} else if ($this->subvar == "removeconstraintsubmit") {
			$this->redirect("tabular/add/".$this->id."/constraints");

		} else {
			$this->redirect("tabular/add/".$this->id."/preview");

		}
		return;
	}

	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template::view_table_join_ajax()
	 */
	function view_table_join_ajax($current_join=null) {
		$intersection = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) INNER JOIN columns c ON (c.column_id=tta.column_id) WHERE tt.template_id='".$this->id."' AND tt.type='c' LIMIT 1;"));
		if (empty($intersection)) die;
		$intersection_column_id = $intersection['column_id'];
		$output = parent::view_table_join_ajax($current_join, $intersection_column_id);
		return $output;
	}
	
	/**
	 * Called by Tabular::execute. Given a template, generate the queries to get all the data for the report.
	 *
	 * @param $template int The template id
	 * @param $constraints array Any constraints to apply
	 * @param $constraint_logic string The constraint logic
	 * @param $demo boolean Is this a demo (ie restrict to 10 results)
	 * @param $axis_limits array Are there any limits to the axes
	 * @return A SQL string
	 */
	function hook_query($template, $constraints, $constraint_logic=null, $demo=false, $axis_limits=null) {
		//$templates contains information about how we show each axis, and how we show the intersection
		$templates_tmp = $template;
		unset($template);

		//order by type: x, y or c for the intersection
		foreach ($templates_tmp as $template_tmp) {
			$template[$template_tmp['type']] = $template_tmp;
		}

		if ($demo) {
			$limit = 10;
		}

		$intersection = $template['c'];
		$x_axis = $template['x'];
		$y_axis = $template['y'];

		//we deal with the intersection column first
		if ($intersection) {
			//set these variables for easy access
			$axis_name_tmp = $intersection['type'];
			$alias_tmp = "a".$axis_name_tmp;
			$table_id_tmp = $intersection['auto_column']['table_id'];
			$table_name_tmp = $intersection['auto_column']['table_name'];
			$column_name_tmp = $intersection['auto_column']['name'];
			$aggregate_tmp = $intersection['aggregate'];

			//depending on the aggregate, we need to do special things...
			switch ($aggregate_tmp) {
				case "countdistinct":
				case "count distinct":
					$select[$axis_name_tmp] = "COUNT(DISTINCT $alias_tmp.$column_name_tmp)";
					break;
				case "count":
				case "sum":
				case "min":
				case "max":
					//these aggregate functions only accept numeric data, so we need to cast the data to a float
					$select[$axis_name_tmp] = "$aggregate_tmp(CAST($alias_tmp.$column_name_tmp AS FLOAT))";
					//we can only cast to float if the data is numeric, so ignore any non-numeric data
					$where[] = "CAST($alias_tmp.$column_name_tmp AS TEXT) ~ '^-?[0-9]+.?[0-9]*$'";
					break;
				case "average":
					//as above but useing the avg() function and round to 4 signigicant digits
					$select[$axis_name_tmp] = "ROUND(AVG(CAST($alias_tmp.$column_name_tmp AS FLOAT))::NUMERIC, FLOOR(4 - LOG(AVG(CAST($alias_tmp.$column_name_tmp AS FLOAT))::NUMERIC))::INT)";

					$where[] = "CAST($alias_tmp.$column_name_tmp AS TEXT) ~ '^-?[0-9]+.?[0-9]*$'";
					break;
				default:
					break;
			}

			//join the intersection table to the query
			$join_tables[$axis_name_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp);
		}

		//then we deal with the x and y axis columns
		foreach (array($x_axis, $y_axis) as $axis_tmp) {
			switch ($axis_tmp['axis_type']) {
				case "auto":
					//set these variables for easy access
					$axis_name_tmp = $axis_tmp['type'];
					$alias_tmp = "a".$axis_name_tmp;
					$table_id_tmp = $axis_tmp['auto_column']['table_id'];
					$table_name_tmp = $axis_tmp['auto_column']['table_name'];
					$table_join_id_tmp = $axis_tmp['table_join_id'];
					$column_name_tmp = $axis_tmp['auto_column']['name'];
					$sort_tmp = $axis_tmp['sort'];

					//is this axis column in the same table as the intersection column?
					if ($table_id_tmp == $intersection['auto_column']['table_id']) {
						//then just select results from the ac table: don't worry about joining the same table again
						$alias_tmp = "ac";
						$select[$axis_name_tmp] = "$alias_tmp.$column_name_tmp";
					//if not...
					} else {
						$select[$axis_name_tmp] = "$alias_tmp.$column_name_tmp";
						//...join this axis' table to the query
						$join_tables[$axis_name_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp, "join_id"=>$table_join_id_tmp);
					}

					if (empty($axis_limits[$axis_name_tmp])) {
						unset($a_select);

						//get distinct values that will make up the index for this axis
						$a_select[$axis_name_tmp] = "$alias_tmp.$column_name_tmp";
						$query[$axis_name_tmp] = $this->hook_build_query($a_select, array("$table_name_tmp $alias_tmp" => 0), false, array("$alias_tmp.$column_name_tmp"), array("$axis_name_tmp $sort_tmp"), $limit);
					} else {
						//only select data with specified value on this axis
						$where[] = "($alias_tmp.$column_name_tmp='".implode("' OR $alias_tmp.$column_name_tmp='", $axis_limits[$axis_name_tmp])."')";
					}

					//add the axis to the group by clause
					$group[$axis_name_tmp] = "$alias_tmp.$column_name_tmp";

					//add the axis to the order by clause
					$order[$axis_name_tmp] = "\"$axis_name_tmp\" $sort_tmp";
					break;
				case "trend":
					//set these variables for easy access
					$axis_name_tmp = $axis_tmp['type'];
					$alias_tmp = "a".$axis_name_tmp;
					$table_id_tmp = $axis_tmp['trend_column']['table_id'];
					$table_name_tmp = $axis_tmp['trend_column']['table_name'];
					$table_join_id_tmp = $axis_tmp['table_join_id'];
					$column_name_tmp = $axis_tmp['trend_column']['name'];
					$sort_tmp = $axis_tmp['sort'];
					$start_date_tmp = $axis_tmp['start_date'];
					$end_date_tmp = $axis_tmp['end_date'];
					$interval_tmp = $axis_tmp['interval'];

					//is this axis column in the same table as the intersection column?
					if ($table_id_tmp == $intersection['auto_column']['table_id']) {
						//then just select results from the ac table: don't worry about joining the same table again
						$alias_tmp = "ac";
					//if not...
					} else {
						//...join this axis' table to the query
						$join_tables[$axis_name_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp, "join_id"=>$table_join_id_tmp);
					}

					unset($a_select);
					unset($a_where);
					unset($a_group);

					switch ($interval_tmp) {
						case "hourly":
							//sql to select date as YYYY-MM-DD HH:00
							$tmp_select = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)||$$-$$||LPAD(EXTRACT(MONTH FROM $alias_tmp.$column_name_tmp::TIMESTAMP)::TEXT, 2, $$0$$)||$$-$$||LPAD(EXTRACT(DOM FROM $alias_tmp.$column_name_tmp::TIMESTAMP)::TEXT, 2, $$0$$)||$$ $$||LPAD(EXTRACT(HOUR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)::TEXT, 2, $$0$$)||$$:00$$";

							//sql to group by hour
							$tmp_group = "EXTRACT(HOUR FROM $alias_tmp.$column_name_tmp::TIMESTAMP), EXTRACT(DOY FROM $alias_tmp.$column_name_tmp::TIMESTAMP), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)";
							break;
						default:
						case "daily":
							//sql to select date as YYYY-MM-DD
							$tmp_select = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)||$$-$$||LPAD(EXTRACT(MONTH FROM $alias_tmp.$column_name_tmp::TIMESTAMP)::TEXT, 2, $$0$$)||$$-$$||LPAD(EXTRACT(DAY FROM $alias_tmp.$column_name_tmp::TIMESTAMP)::TEXT, 2, $$0$$)";

							//sql to group by day
							$tmp_group = "EXTRACT(DAY FROM $alias_tmp.$column_name_tmp::TIMESTAMP), EXTRACT(MONTH FROM $alias_tmp.$column_name_tmp::TIMESTAMP), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)";
							break;
						case "weekly":
							//sql to select date as YYYY week: WW
							$tmp_select = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)||$$ week: $$||LPAD(EXTRACT(WEEK FROM $alias_tmp.$column_name_tmp::TIMESTAMP)::TEXT, 2, $$0$$)";

							//sql to group by week of year
							$tmp_group = "EXTRACT(WEEK FROM $alias_tmp.$column_name_tmp::TIMESTAMP), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)";
							break;
						case "monthly":
							//sql to select date as YYYY-MM
							$tmp_select = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)||$$-$$||LPAD(EXTRACT(MONTH FROM $alias_tmp.$column_name_tmp::TIMESTAMP)::TEXT, 2, $$0$$)";

							//sql to group by month
							$tmp_group = "EXTRACT(MONTH FROM $alias_tmp.$column_name_tmp::TIMESTAMP), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)";
							break;
						case "yearly":
							//sql to select date as YYYY
							$tmp_select = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)";

							//sql to group by year
							$tmp_group = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp::TIMESTAMP)";
							break;
					}

					//select clause to get distinct dates for the axis query
					$a_select[$axis_name_tmp] = $tmp_select;

					//select clause to dates for main query
					$select[$axis_name_tmp] = $tmp_select;

					//group results by distinct date in axis query and main query
					$a_group[$axis_name_tmp] = $tmp_group;
					$group[$axis_name_tmp] = $tmp_group;

					$a_where[] = "$alias_tmp.$column_name_tmp >= '$start_date_tmp'";
					$a_where[] = "$alias_tmp.$column_name_tmp <= '$end_date_tmp'";

					$where[] = "$alias_tmp.$column_name_tmp >= '$start_date_tmp'";
					$where[] = "$alias_tmp.$column_name_tmp <= '$end_date_tmp'";

					//get distinct values that will make up the index for this axis
					$query[$axis_name_tmp] = $this->hook_build_query($a_select, array("$table_name_tmp $alias_tmp" => 0), $a_where, $a_group, array("$axis_name_tmp $sort_tmp"), $limit);

					//only select data with specified value on this axis
					if (!empty($axis_limits[$axis_name_tmp]) && $demo) {
						$where[] = "($tmp_select='".implode("' OR $tmp_select='", $axis_limits[$axis_name_tmp])."')";
					}

					//add the axis to the order by clause
					$order[$axis_name_tmp] = "\"$axis_name_tmp\" $sort_tmp";
					break;
				case "single":
					//set these variables for easy access
					$axis_name_tmp = $axis_tmp['type'];
					$alias_tmp = "a".$axis_name_tmp;

					//for type single SQL should be SELECT 1 as y, so that data is grouped by this single value
					$select[$axis_name_tmp] = "1";

					//same goes for x/y axis query
					unset($a_select);
					$a_select[$axis_name_tmp] = "1";

					//get distinct values that will make up the index for this axis
					$query[$axis_name_tmp] = "SELECT {$a_select[$axis_name_tmp]} AS {$axis_name_tmp};";
					break;
				case "manual":
					if (!empty($axis_tmp['squid_constraints'])) {
						//set these variables for easy access
						$axis_name_tmp = $axis_tmp['type'];
						$alias_tmp = "a".$axis_name_tmp;

						foreach ($axis_tmp['squid_constraints'] as $squid_tmp) {
							$squid_id_tmp = $squid_tmp['tabular_templates_manual_squid_id'];

							//create an array of squids! if you don't understand what this means, I suggest you familiarise yourself with the tabular_templates_manual table structure. srsly ^_^
							$squids_tmp[$squid_id_tmp] = $squid_tmp['human_name'];

							//set these variables for easy access
							$squid_constraints_id_tmp = $squid_tmp['squid_constraints_id'];
							$squid_alias_tmp = "{$alias_tmp}sc{$squid_constraints_id_tmp}";

							$table_id_tmp = $squid_tmp['table_id'];
							$table_name_tmp = $squid_tmp['table'];
							$table_join_id_tmp = $squid_tmp['table_join_id'];
							$column_name_tmp = $squid_tmp['column'];
							$type_tmp = $squid_tmp['type'];
							$value_tmp = $squid_tmp['value'];

							//is this constraint's column in the same table as the intersection column?
							if ($table_id_tmp == $intersection['auto_column']['table_id']) {
								$squid_alias_tmp = "ac";
							//if not...
							} else if (!empty($table_join_id_tmp)) {
								//...join this constraint's table to the query
								$join_tables[$squid_alias_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$squid_alias_tmp, "join_id"=>$table_join_id_tmp);
							}

							unset($where_tmp);

							//generate the constraint SQL
							$where_tmp = $this->where($squid_alias_tmp, $column_name_tmp, $type_tmp, $value_tmp);

							//create an array of constraints by their (squid) constraint ids. in a moment we'll put them into logical order
							$squid_wheres[$squid_constraints_id_tmp] = $where_tmp;
						}

						//loop through the constraint logic for each squid
						foreach ($axis_tmp['squid_constraint_logic'] as $squid_logic_tmp) {
							$squid_id_tmp = $squid_logic_tmp['tabular_templates_manual_squid_id'];

							//constraint logic sql will need to be kept separated by squid_id
							$squid_where_logical[$squid_id_tmp] = $squid_logic_tmp['logic'];

							//loop through all the constraints we just created
							foreach ($squid_wheres as $squid_constraints_id_tmp => $squid_where_tmp) {
								//place the constraint into the constraint logic
								$squid_where_logical[$squid_id_tmp] = str_replace($squid_constraints_id_tmp, $squid_where_tmp, $squid_where_logical[$squid_id_tmp]);
							}
						}

						$select[$axis_name_tmp] = "f.human_name";

						//loop thorugh the array of squids, that we created a moment ago. looking at the squid_ids will give us access to the squid's constraint logic.
						foreach ($squids_tmp as $squid_id_tmp => $squid_tmp) {
							//create an array to use in the sql FROM VALUES statement
							$tmp_values[] = "({$squid_id_tmp}, '{$squid_tmp}')";

							//create an array to use in the sql JOIN statement
							$tmp_join_on[] = "(({$squid_where_logical[$squid_id_tmp]}) AND f.id={$squid_id_tmp})";
						}

						//create a fake table of squid names, using sql VALUES (awesome!)
						$tmp_values = "(VALUES ".implode(", ", $tmp_values).") AS f (id, human_name)";

						//join the fake table using the squid constraints
						$tmp_join_on = "ON (".implode(" OR ", $tmp_join_on).")";

						//add the fake table to the query
						$join_tables[$axis_name_tmp] = array("table"=>$tmp_values, "alias"=>"", "manual_join"=>$tmp_join_on);

						if (!empty($axis_limits[$axis_name_tmp]) && $demo) {
							$where[] = "(f.human_name='".implode("' OR f.human_name='", $axis_limits[$axis_name_tmp])."')";
						}

						$group[$axis_name_tmp] = "f.human_name";

						$query[$axis_name_tmp] = "SELECT f.human_name as \"{$axis_name_tmp}\" FROM {$tmp_values};";
					}
					break;
				default:
					break;
			}
		}

		//now, we deal with the constraints
		if (is_array($constraints)) {
			//constraint logic contains the ands, ors, brackets, and constraint ids. Next we will replace the constraint ids with the constraints themselves
			$where_logical = "($constraint_logic)";

			foreach ($constraints as $constraint) {
				//set these variables for easy access
				$table_constraints_id_tmp = $constraint['template_constraints_id'];
				$alias_tmp = "c".$table_constraints_id_tmp;
				$table_id_tmp = $constraint['table_id'];
				$table_name_tmp = $constraint['table'];
				$table_join_id_tmp = $constraint['table_join_id'];
				$column_name_tmp = $constraint['column'];
				$type_tmp = $constraint['type'];
				$value_tmp = $constraint['value'];

				//is this constraint's column in the same table as the intersection column?
				if ($table_id_tmp == $intersection['auto_column']['table_id']) {
					$alias_tmp = "ac";
				//if not...
				} else if (!empty($table_join_id_tmp)) {
					//...join this constraint's table to the query
					$join_tables[$alias_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp, "join_id"=>$table_join_id_tmp);
				}

				unset($where_tmp);

				//generate the constraint SQL
				$where_tmp = $this->where($alias_tmp, $column_name_tmp, $type_tmp, $value_tmp);

				//place the constraint into the constraint logic
				$where_logical = str_replace($table_constraints_id_tmp, $where_tmp, $where_logical);
			}

			//add the constraints to the query's where clause
			$where[] = $where_logical;
		}

		//x before y!
		ksort($order);
		ksort($group);

		//generate the query
		$query['c'] = $this->hook_build_query($select, $join_tables, $where, $group, $order, pow($limit, 2));

// 		var_dump($query);

		return $query;
	}

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_output()
	 */
	function hook_output($results) {
		$template = $results[1];
		$demo = $results[2];
		$now = $results[3];
		$pdf = $results[4];

		$results = $results[0];

		$output = Tabular_View::hook_output($results, $template, $demo, $now, $pdf);
		return $output;
	}

	function view_display_table() {
		/* Get all the tables and create the wrapped columns */
		$template = $this->get_columns($this->id);
		$object = $this->dobj->db_fetch($this->dobj->db_query("SELECT object_id FROM templates WHERE template_id='".$this->id."'"));
		$tables = $this->call_function("catalogue", "get_structure", array($object['object_id'], $constraints));
		$output = Tabular_View::view_display_table($tables, $template);
		return $output;
	}

	/*
	 * View Remove
	 *
	 * Used to delete a column from automatic fields.
	 *
	 */
	function view_remove() {
		//The url that this was called through will tell us two things: the template id and the column id.
		//We need to delete the entry for this column in the tabular_templates table. BUT, column id is not stored in this table. So, we have to query the tabular_templates_auto table to work out exactly which entry to delete
		$query = "SELECT * FROM tabular_templates t INNER JOIN tabular_templates_auto ta ON (ta.tabular_template_id=t.tabular_template_id) WHERE t.template_id='".$this->id."' AND ta.column_id='".$this->subvar."';";
		$cur = $this->dobj->db_fetch($this->dobj->db_query($query));
		if ($cur) {
			//delete the entry by it's tabular_template_id. This will cascade down to the tabular_templates_auto table
			$this->dobj->db_query("DELETE FROM tabular_templates WHERE tabular_template_id='".$cur['tabular_template_id']."';");
		}
		die();
	}

	function view_remove_constraint() {
		$query = "DELETE FROM template_constraints WHERE template_id=".$this->id." AND column_id='".$this->subvar."';";
		$cur = $this->dobj->db_query($query);
		die();
	}

	/**
	 * Deprecated function: replaced by Tabular::execute()... I think
	 *
	 */
	function hook_run($demo=false, $data_only=false, $draft=true, $template_id=null) {
		if (!empty($template_id)) {
			$this->id = $template_id;
		}

		$template = $this->get_columns($this->id);
		$constraints = $this->get_constraints($this->id);
		$constraint_logic = $this->get_constraint_logic($this->id);

// 		/* Concatenate the predefined constraints and the user defined constraints */
// 		if ($_REQUEST['data']['constraint']) {
// 			foreach ($_REQUEST['data']['constraint'] as $i => $cons) {
// 				foreach ($constraints as $j => $cons2) {
// 					if ($cons2['template_constraints_id'] == $i) {
// 						$constraints[$j]['value'] = $cons;
// 						break(2);
// 					}
// 				}
// 			}
// 		}

		/* Generate the query to run */
		$query = $this->hook_query($template, $constraints, $constraint_logic, $demo);

		/* Run the query and get the results */
		$start = time();
		$data = parent::hook_run_query($template[0]['object_id'], $query);
		$end = time();

		if (!$demo) {
			/* Only update the run statistics if this is a complete run, not a preview run */
			$update_query = "UPDATE templates SET last_run=now(), last_time='".($end-$start)."', last_by=1, last_size=".count($data)." WHERE template_id=".$this->id."";
			$update = $this->dobj->db_query($update_query);
			$saved_report_id = $this->save_results($this->id, $data, ($draft ? "t" : "f"), 'f', ($end-$start), 1);
		} else {
			$saved_report_id = $this->save_results($this->id, $data, ($draft ? "t" : "f"), 't', ($end-$start), 1);
		}

		if ($data_only) {
			return $saved_report_id;
		} else {
			$foo_json = $this->get_saved_report();
			$foo_json = $foo_json['report'];

			return Tabular_View::hook_run($data, $template, $demo, $now, $foo_json);
		}
	}

	function view_tables_json() {
		$template = $this->get_columns($this->id);
		$output = Tabular_View::view_tables_json($template);
		return $output;
	}

	function view_interval_dd_json() {
		$values = array("daily"=>"Daily", "weekly"=>"Weekly", "monthly"=>"Monthly", "quarterly"=>"Quarterly", "yearly"=>"Yearly");
		$output = Tabular_View::view_dd_json($values);
		return $output;
	}

	function get_columns($template_id, $type=false) {
		if ($type) {
			$type = " AND l.type='".$type."'";
		} else {
			$type = "";
		}

		$tabular_templates_type_query = $this->dobj->db_fetch_all($this->dobj->db_query("
SELECT
  *,
  a.column_id as auto_column_id,
  tr.column_id as trend_column_id,
  a.table_join_id as auto_join_id,
  tr.table_join_id as trend_join_id,
  a.human_name as tabular_template_auto_human_name,
  tr.human_name as tabular_template_trend_human_name
FROM
  templates t,
  tabular_templates l
  LEFT OUTER JOIN tabular_templates_auto a ON (l.tabular_template_id=a.tabular_template_id)
  LEFT OUTER JOIN tabular_templates_trend tr ON (l.tabular_template_id=tr.tabular_template_id)
  LEFT OUTER JOIN tabular_templates_single s ON (l.tabular_template_id=s.tabular_template_id)
  LEFT OUTER JOIN tabular_templates_manual m ON (l.tabular_template_id=m.tabular_template_id)
WHERE
  t.template_id=l.template_id ".$type."
  AND t.template_id=".$template_id.";"));

		foreach ($tabular_templates_type_query as $tabular_templates_type_tmp) {
			if (!empty($tabular_templates_type_tmp['auto_column_id'])) {
				$column_ids_array[] = $tabular_templates_type_tmp['auto_column_id'];
			}
			if (!empty($tabular_templates_type_tmp['trend_column_id'])) {
				$column_ids_array[] = $tabular_templates_type_tmp['trend_column_id'];
			}
		}

		$columns_query = $this->dobj->db_fetch_all($this->dobj->db_query("
SELECT
  c.column_id,
  c.name,
  t.table_id,
  t.name as table_name
FROM
  columns c
  INNER JOIN tables t ON (t.table_id=c.table_id)
WHERE
  c.column_id='".implode("' OR c.column_id='", $column_ids_array)."';"));

		foreach ($columns_query as $column_tmp) {
			$columns[$column_tmp['column_id']] = $column_tmp;
		}

		foreach ($tabular_templates_type_query as $i => $tabular_templates_type_tmp) {
			if (!empty($tabular_templates_type_tmp['auto_column_id'])) {
				$tabular_templates_type_query[$i]['auto_column'] = $columns[$tabular_templates_type_tmp['auto_column_id']];
			}
			if (!empty($tabular_templates_type_tmp['trend_column_id'])) {
				$tabular_templates_type_query[$i]['trend_column'] = $columns[$tabular_templates_type_tmp['trend_column_id']];
			}

			switch ($tabular_templates_type_tmp['axis_type']) {
				case "auto":
					$tabular_templates_type_query[$i]['table_join_id'] = $tabular_templates_type_query[$i]['auto_join_id'];

					if (empty($tabular_templates_type_tmp['tabular_template_auto_human_name'])) {
						$tabular_templates_type_query[$i]['tabular_template_auto_human_name'] = $tabular_templates_type_query[$i]['auto_column']['name'];
					}
					$tabular_templates_type_query[$i]['tabular_template_human_name'] = $tabular_templates_type_query[$i]['tabular_template_auto_human_name'];
					break;
				case "trend":
					$tabular_templates_type_query[$i]['table_join_id'] = $tabular_templates_type_query[$i]['trend_join_id'];

					if (empty($tabular_templates_type_tmp['tabular_template_trend_human_name'])) {
						$tabular_templates_type_query[$i]['tabular_template_trend_human_name'] = $tabular_templates_type_query[$i]['trend_column']['name'];
					}
					$tabular_templates_type_query[$i]['tabular_template_human_name'] = $tabular_templates_type_query[$i]['tabular_template_trend_human_name'];
					break;
				case "single":
					break;
				case "manual":
					$tabular_templates_type_query[$i]['tabular_template_human_name'] = "Manual";

					$squid_query = $this->dobj->db_fetch_all($this->dobj->db_query("
SELECT
  ttms.*,
  ttmsc.*,
  c.table_id,
  c.name as column,
  t.name as table
FROM
  tabular_templates_manual_squids ttms
  INNER JOIN
    tabular_templates_manual_squid_constraints ttmsc
      ON (ttmsc.tabular_templates_manual_squid_id=ttms.tabular_templates_manual_squid_id)
  INNER JOIN
    columns c
      ON (ttmsc.column_id=c.column_id)
  INNER JOIN
    tables t
      ON (c.table_id=t.table_id)
WHERE
  ttms.tabular_templates_manual_id='{$tabular_templates_type_tmp['tabular_templates_manual_id']}';"));


					if (!empty($squid_query)) {
						$tabular_templates_type_query[$i]['squid_constraints'] = $squid_query;
					}

					$squid_logic_query = $this->dobj->db_fetch_all($this->dobj->db_query("
SELECT
  ttms.*,
  ttmscl.*
FROM
  tabular_templates_manual_squids ttms
  INNER JOIN
    tabular_templates_manual_squid_constraint_logic ttmscl
      ON (ttmscl.tabular_templates_manual_squid_id=ttms.tabular_templates_manual_squid_id)
WHERE
  ttms.tabular_templates_manual_id='{$tabular_templates_type_tmp['tabular_templates_manual_id']}';"));

					if (!empty($squid_logic_query)) {
						$tabular_templates_type_query[$i]['squid_constraint_logic'] = $squid_logic_query;
					}
					break;
				default:
					break;
			}
		}

		$data = $tabular_templates_type_query;

		return $data;
	}

	function get_constraints($template_id) {
		$query = "SELECT l.*, t.template_id, t.name, t.draft, t.module, t.object_id, c.column_id, tb.table_id, c.human_name as chuman, tb.human_name as thuman, c.name as column, tb.name as table FROM template_constraints l, templates t, columns c, tables tb WHERE tb.table_id=c.table_id AND c.column_id=l.column_id AND t.template_id=l.template_id AND t.template_id=".$template_id.";";
		$data = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		return $data;
	}

	function view_add_axis() {
		$columns = $this->get_columns($this->id, $this->subvar);
		$output = Tabular_View::view_add_axis($this->subvar, $columns);
		return $output;
	}

	function view_add_intersection() {
		$columns = $this->get_columns($this->id, 'c');
		$output = Tabular_View::view_add_intersection($columns);
		return $output;
	}

	function view_clone() {
		$template = "SELECT * FROM templates WHERE template_id='".$this->id."';";
		$template = $this->dobj->db_fetch($this->dobj->db_query($template));
		unset($template['template_id']);
		$template['name'] = $template['name'] ." (Clone)";
		$template['database'] = $template['object_id'];
		$template_id = $this->add_template($template);
		$list_templates = "SELECT * FROM tabular_templates WHERE template_id='".$this->id."';";
		$list_templates = $this->dobj->db_fetch_all($this->dobj->db_query($list_templates));
		foreach ($list_templates as $i => $temp) {
			unset($temp['tabular_template_id']);
			$temp['template_id'] = $template_id;
			$this->dobj->db_query($this->dobj->insert($temp, "tabular_templates"));
		}
		$list_constraints = "SELECT * FROM template_constraints WHERE template_id='".$this->id."';";
		$list_constraints = $this->dobj->db_fetch_all($this->dobj->db_query($list_constraints));
		foreach ($list_constraints as $i => $temp) {
			unset($temp['template_constraints_id']);
			$temp['template_id'] = $template_id;
			$this->dobj->db_query($this->dobj->insert($temp, "template_constraints"));
		}
		$this->redirect('tabular/add/'.$template_id);
	}

	/**
	 * View Execute Scheduled
	 *
	 * Called by Cron::view_executor() to run a report, generate graphs, and email them. Calls Tabular::execute_scheduled() to do the heavy lifting
	 *
	 */
	function view_execute_scheduled($data=array()) {
		$template = (array)$data[0];
		$template_id = $template['template_id'];

		if (empty($template_id)) return;

		$saved_report_id = $this->execute_scheduled($template_id);

		//get the table pdf
		if ($template['publish_table'] == "t") {
			$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, false, false));
			$table = $table['pdf'];
		}

		//get the graph pdf
		if ($template['publish_graph'] == "t") {
			$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], false, true));
			$graph = $graph['graphing'];
		}

		//get the csv file
		if (true) {
			$csv = $this->call_function("csv", "get_or_generate", array($saved_report_id));
			$csv = $csv['csv'];
		}

		if ($template['email_dissemination'] == "t") {
			$recipients_query = $this->call_function("ALL", "hook_recipients", array($template_id, $template['email_recipients']));

			foreach ($recipients_query as $recipients_tmp) {
				$recipients = array_merge((array)$recipients_tmp, (array)$recipients);
			}
		}

		if ($template['email_dissemination'] == "t" && !empty($recipients)) {

			require_once($this->conf['paths']['phpmailer_path']."class.phpmailer.php");
			
			if (!isset($mail)) {
				$mail = new PHPMailer();
			}
	
			$mail->IsSMTP();
			$mail->Host = $this->conf['email']['host'];
			$mail->SMTPAuth = true;
			$mail->Username = $this->conf['email']['username'];
			$mail->Password = $this->conf['email']['password'];

			$mail->From = $this->conf['email']['from_address'];
			$mail->FromName = $this->conf['email']['from_name'];
			$mail->AddReplyTo($this->conf['email']['from_address'], $this->conf['email']['from_name']);

			foreach ($recipients as $recipient) {
				$mail->AddAddress($recipient[1], $recipient[0]);
			}

			if (!empty($table)) {
				$mail->AddAttachment($table['pdf_path'], "Table.pdf");
			}

			if (!empty($graph)) {
				$mail->AddAttachment($graph['pdf_path'], "Graph.pdf");
			}

			if (!empty($csv)) {
				$mail->AddAttachment($csv['txt_path'], "CSV.txt");
			}

			$mail->IsHTML(true);

			$template['email_subject'] = str_replace("%name", $template['name'], $template['email_subject']);
			$template['email_subject'] = str_replace("%desc", $template['description'], $template['email_subject']);
			$template['email_subject'] = str_replace("%run", date("Y-m-d H:i:s", strtotime($template['email_subject'])), $template['email_subject']);
			$template['email_subject'] = str_replace("%by", $template['last_by'], $template['email_subject']);
			$template['email_subject'] = str_replace("%size", $template['last_size'], $template['email_subject']);

			$mail->Subject = $template['email_subject'];

			$template['email_body'] = str_replace("%name", $template['name'], $template['email_body']);
			$template['email_body'] = str_replace("%desc", $template['description'], $template['email_body']);
			$template['email_body'] = str_replace("%run", date("Y-m-d H:i:s", strtotime($template['last_run'])), $template['email_body']);
			$template['email_body'] = str_replace("%by", $template['last_by'], $template['email_body']);
			$template['email_body'] = str_replace("%size", $template['last_size'], $template['email_body']);

			$mail->Body = stripslashes($template['email_body']);
			
			if(!$mail->Send()) {
				echo "Message could not be sent.\n";
				echo "Mailer Error: ".$mail->ErrorInfo."\n";
				exit;
			}
			
			echo "Message has been sent\n";
		}

		$output = Tabular_View::view_execute_scheduled();
		return $output;
	}

	/**
	 * View Histories
	 *
	 * Fetches all saved reports for a given report, for the histories page
	 *
	 */
	function view_histories() {
		$template_id = $this->id;

		if (empty($template_id)) die;

		$saved_reports = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM saved_reports WHERE template_id='$template_id' AND demo=false AND draft=false ORDER BY created DESC;"));

		$processing_report = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='$template_id' AND (execution_queued=true OR execution_executing=true);"));

		return Tabular_View::view_histories($saved_reports, $processing_report);
	}

	/**
	 * View History
	 *
	 * Gets data and table/graph files for the history page
	 *
	 */
	function view_history() {
		$tmp_table = null;
		$tmp_graph = null;
		$template_id = $this->id;
		$saved_report_id = $this->subvar;

		if (empty($template_id)) die;
		if (empty($saved_report_id)) die;

		//check that the saved report id matches the template_id
		$saved_report_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM saved_reports WHERE template_id='$template_id' AND saved_report_id='$saved_report_id' AND demo=false AND draft=false;"));

		if (empty($saved_report_query)) die;

		if (true) {
			$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, false, true));
			$tmp_table .= $table['pdf']['object'];
		}

		if (true) {
			$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, null, true, false));
			$tmp_graph .= $graph['graphing']['object'];
		}

		if (true) {
			$csv = $this->call_function("csv", "get_or_generate", array($saved_report_id));
		}

		if (!empty($table['pdf']['pdf_url'])) {
			$downloads['Download Table'] = $table['pdf']['pdf_url'];
		}

		if (!empty($graph['graphing']['pdf_url'])) {
			$downloads['Download Graph'] = $graph['graphing']['pdf_url'];
		}

		if (!empty($csv['csv']['txt_url'])) {
			$downloads['Download CSV'] = $csv['csv']['txt_url'];
		}
	
		return Tabular_View::view_history($tmp_table, $tmp_graph, $downloads);
	}

	/**
	 * Execute Manually
	 *
	 * Calls Tabular::execute() with appropriate arguments to execute full (as opposed to execute demo)
	 *
	 */
	function execute_manually($template_id) {
		return $this->execute($template_id, false);
	}

	/**
	 * Execute Scheduled
	 *
	 * Calls Tabular::execute() with appropriate arguments to execute full (as opposed to execute demo)
	 *
	 */
	function execute_scheduled($template_id) {
		return $this->execute($template_id, false);
	}

	/**
	 * Execute Demo Quick
	 *
	 * Called by Tabular::view_data_preview_first_ajax() to get values for axies only. Calls Tabular::execute() with appropriate arguments to do so
	 *
	 */
	function execute_demo_quick($template_id) {
		return $this->execute($template_id, true, true);
	}

	function execute_demo_cellwise($template_id) {
		return $this->execute($template_id, true, false, true);
	}

	function execute_demo($template_id) {
		return $this->execute($template_id, true);
	}

	function execute($template_id, $demo, $quick=false, $cellwise=false) {
		$template = $this->get_columns($template_id);
		$constraints = $this->get_constraints($template_id);
		$constraint_logic = $this->get_constraint_logic($template_id);

		if ($cellwise && $demo) {
			$saved_report_id = $this->subvar;

			$saved_report = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM saved_reports r WHERE r.saved_report_id='$saved_report_id' LIMIT 1;"));
			$saved_report = json_decode($saved_report['report'], true);

			if (!empty($saved_report['c'])) {
				foreach ($saved_report['c'] as $c_key => $c_tmp) {
					$x_tmp = $c_tmp['x'];
					$y_tmp = $c_tmp['y'];

					$saved_report_translate[$x_tmp][$y_tmp] = $c_key;
				}
			}

			foreach ($saved_report['y'] as $y_tmp) {
				foreach ($saved_report['x'] as $x_tmp) {
					if (isset($saved_report_translate[$x_tmp['x']][$y_tmp['y']])) {
						$c_key = $saved_report_translate[$x_tmp['x']][$y_tmp['y']];
					} else {
						$c_key = null;
					}

					if (!empty($saved_report['c'][$c_key])) {
						continue;
					}

					$x_limit = $x_tmp['x'];
					$y_limit = $y_tmp['y'];

					break 2;
				}
			}

			//if we've run out of cells to update, stop here. view_data_preview_slow_ajax will detect that we havn't sent back a saved_report_id, and stop sending ajax requests
			if (empty($x_limit) || empty($x_limit)) {
				return null;
			}

			$query = $this->hook_query($template, $constraints, $constraint_logic, $demo, array("x"=>array($x_limit), "y"=>array($y_limit)));

			$data_tmp = parent::hook_run_query($template[0]['object_id'], $query);

			if (empty($data_tmp['c'][0])) {
				$data_tmp['c'][0] = array("c"=>null, "x"=>$x_limit, "y"=>$y_limit);
			}

			$saved_report['c'][] = array(
				"c"=>$data_tmp['c'][0]['c'],
				"x"=>$data_tmp['c'][0]['x'],
				"y"=>$data_tmp['c'][0]['y']
				);

			$this->dobj->db_query("UPDATE saved_reports SET report='".json_encode($saved_report)."' WHERE saved_report_id='$saved_report_id';");

			$this->dobj->db_query("DELETE FROM csv_documents WHERE saved_report_id='$saved_report_id';");
			$this->dobj->db_query("DELETE FROM graph_documents WHERE saved_report_id='$saved_report_id';");
			$this->dobj->db_query("DELETE FROM table_documents WHERE saved_report_id='$saved_report_id';");

			return $saved_report_id;
		}

		/* Generate the query to run */
		$query = $this->hook_query($template, $constraints, $constraint_logic, $demo);
		$start = time();

		if ($quick && $demo) {
			unset($query['c']);

			$data = parent::hook_run_query($template[0]['object_id'], $query);
		} else if ($demo) {
			unset($query['c']);

			$data_tmp = parent::hook_run_query($template[0]['object_id'], $query);

			foreach ($data_tmp['x'] as $x_tmp) {
				$x_limit[] = $x_tmp['x'];
			}

			foreach ($data_tmp['y'] as $y_tmp) {
				$y_limit[] = $y_tmp['y'];
			}

			$query = $this->hook_query($template, $constraints, $constraint_logic, $demo, array("x"=>$x_limit, "y"=>$y_limit));
// var_dump($query);
			unset($query['x']);
			unset($query['y']);
//
			$data = parent::hook_run_query($template[0]['object_id'], $query);

			$data = array_merge((array)$data, (array)$data_tmp);

		} else {
			/* Run the query and get the results */
			$data = parent::hook_run_query($template[0]['object_id'], $query);
		}
		$end = time();
		$saved_report_id = $this->save_results($template_id, $data, "f", ($demo ? "t" : "f"), ($end-$start), 1);
		
		return $saved_report_id;
	}

	function hook_recipients($template_id, $template_recipients=null) {
		if (empty($template_recipients)) return null;

		foreach (explode(",", $template_recipients) as $recipient_tmp) {
			$recipients[] = array("", trim($recipient_tmp));
		}

		return $recipients;
	}

	function hook_recipient_selector($recipients) {
		return "
			<div class='input text' style='margin-left: 30px;'><input type='text' id='tabular_recipients' name='data[email_recipients]' value='".$recipients."' dojoType='dijit.form.TextBox' /><span id='tabular_recipients_count' style='padding-left: 20px; vertical-align: middle; color: #555753; font-size: 10pt; font-style: italic;'></span></div>
			";
	}

	function view_data_preview_ajax() {
		if ((int)$this->id) {
			$template = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='".$this->id."';"));
			$saved_report_id = $this->execute_demo($this->id);

			if ($template['publish_table'] == "t") {
				$data_preview .= "<h3>Tabular Data</h3>";

				$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, true, true));
				$data_preview .= $table['pdf']['object'];
			}

			if ($template['publish_graph'] == "t") {
				$data_preview .= "<h3>Graphic Data</h3>";

				$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], true, false));
				$data_preview .= $graph['graphing']['object'];
			}
		}

		$output = Tabular_View::view_data_preview_ajax($data_preview);
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

			if (!$saved_report_id) {
				return Tabular_View::view_data_preview_ajax("finished");
			}

			if ($template['publish_table'] == "t") {
				$data_preview .= "<h3>Tabular Data</h3>";

				$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, true, true));
				$data_preview .= $table['pdf']['object'];
			}

			if ($template['publish_graph'] == "t") {
				$data_preview .= "<h3>Graphic Data</h3>";

				$data_preview .= "<div style='height: 690px;'>";
				$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], true, false));
				$data_preview .= $graph['graphing']['object'];
				$data_preview .= "</div>";
			}
		}

		$output = Tabular_View::view_data_preview_ajax($data_preview);
		return $output;
	}

	function view_data_preview_first_ajax() {
		$data_preview = "";
		if ((int)$this->id) {
			$template = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='".$this->id."';"));
			$saved_report_id = $this->execute_demo_quick($this->id);

			$data_preview .= '<div id="saved_report_id" style="display: none;">'.$saved_report_id.'</div>';
			$data_preview .= '<div id="data_preview">';

			if ($template['publish_table'] == "t") {
				$data_preview .= "<h3>Tabular Data</h3>";

				$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, true, true));
				$data_preview .= $table['pdf']['object'];
			}

			if ($template['publish_graph'] == "t") {
				$data_preview .= "<h3>Graphic Data</h3>";

				$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], true, false));
				$data_preview .= $graph['graphing']['object'];
			}

			$data_preview .= '</div>';
		}

		$output = Tabular_View::view_data_preview_ajax($data_preview);
		return $output;
	}

	function view_processing_history_ajax() {
		$template_id = $this->id;
		$report_query = null;

		if (!empty($template_id)) {
			$template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='$template_id' AND (execution_queued=true OR execution_executing=true);"));

			if (empty($template_query)) {
				$report_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM saved_reports WHERE template_id='$template_id' AND demo=false AND draft=false ORDER BY created DESC LIMIT 1;"));
			}
		}

		$output = Tabular_View::view_processing_history_ajax($report_query, $template_query);
		return $output;
	}

	function view_execute_manually() {
		$template_id = $this->id;

		if (empty($template_id)) return;

		$this->dobj->db_query($this->dobj->update(array("execute_now"=>"t", "execution_queued"=>"t"), "template_id", $template_id, "templates"));

		if ($_SESSION['acls']['report'][$template_id]['histories']) {
			$this->redirect("tabular/histories/".$template_id);
		} else {
			$this->redirect("template/home");
		}
	}
}

class Tabular_View extends Template_View {
	
	/**
	 * Display the X and Y axis views
	 *
	 * @param array $tabular_template_auto The tabular template auto information
	 * @param array $table_join_ajax The table join information
	 * @param array $tabular_template The tabular template information
	 */
	function view_add_xy($blah, $tabular_template_auto=null, $table_join_ajax=null, $tabular_template=null) {
		$output->title = strtoupper($this->subvar)." Axis";
		$output->title_desc = "The ".strtoupper($this->subvar)." axis is a column selected from the database. Unique values from this column will be used to index the intersection data.";
		
		switch ($this->subid) {
			default:
			case ("type"):
				$output->data .= "<h3>Axis Type</h3>";
				$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/typesubmit", "dojoType='dijit.form.Form'");
				$output->data .= "<div class='input'><input type='radio' name='data[axis_type]' value='auto' ".($tabular_template['axis_type'] == "auto" ? "checked='checked'" : "")." /><label>Database</label></div>";
				$output->data .= "<p>Data will be indexed, along the ".strtoupper($this->subvar)." axis, by unique values of a selected database column.</p>";
				
				$output->data .= "<div class='input'><input type='radio' name='data[axis_type]' value='trend' ".($tabular_template['axis_type'] == "trend" ? "checked='checked'" : "")." /><label>Date Trend</label></div>";
				$output->data .= "<p>Data will be indexed, daily, weekly, monthly, etc., along the ".strtoupper($this->subvar)." axis, by a selected database date column.</p>";
				
				if ($this->subvar == "y") {
					$output->data .= "<div class='input'><input type='radio' name='data[axis_type]' value='single' ".($tabular_template['axis_type'] == "single" ? "checked='checked'" : "")." /><label>Single</label></div>";
					$output->data .= "<p>Data will not be indexed along the ".strtoupper($this->subvar)." axis.</p>";
					$output->data .= "<div class='input'><input type='radio' name='data[axis_type]' value='manual' ".($tabular_template['axis_type'] == "manual" ? "checked='checked'" : "")." /><label>Custom Values</label></div>";
					$output->data .= "<p>Data will be indexed along the ".strtoupper($this->subvar)." axis, by manually created criteria.</p>";
				}
				
				$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();
				break;
			case ("autosource"):
				$output->data .= "<h3>Axis Source</h3>";
				$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/autosourcesubmit", "dojoType='dijit.form.Form'");
				$output->data .= $this->source_column_i("data[column_id]", $blah, $tabular_template_auto['column_id'], "update_join_display(this);");
				$output->data .= $this->i("data[sort]", array("label"=>"Order", "type"=>"select", "default"=>$tabular_template_auto['sort'], "options"=>array("ASC"=>"Ascending", "DESC"=>"Descending"), "dojoType"=>"dijit.form.FilteringSelect"));
				$output->data .= $this->i("data[human_name]", array("label"=>"Axis Name", "type"=>"text", "default"=>$tabular_template_auto['human_name'], "dojoType"=>"dijit.form.ValidationTextBox"));
				$output->data .= "<hr />";
				$output->data .= "<div id='join_display'>
					".$table_join_ajax."
					</div>
					";
				$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();
				break;
			case ("trendsource"):
				$output->data .= "<h3>Axis Source</h3>";
				$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/trendsourcesubmit", "dojoType='dijit.form.Form'");
				$output->data .= $this->i("data[column_id]", array("label"=>"Source Column", "type"=>"select", "default"=>$tabular_template_auto['column_id'], "options"=>$blah, "onchange"=>"update_join_display(this);", "dojoType"=>"dijit.form.FilteringSelect"));
				$output->data .= $this->i("data[sort]", array("label"=>"Order", "type"=>"select", "default"=>$tabular_template_auto['sort'], "options"=>array("ASC"=>"Ascending", "DESC"=>"Descending"), "dojoType"=>"dijit.form.FilteringSelect"));
				$output->data .= $this->i("data[start_date]", array("id"=>"data[start_date]", "type"=>"text", "dojoType"=>"dijit.form.DateTextBox", "label"=>"Start Date", "value"=>$tabular_template_auto['start_date']));
				$output->data .= $this->i("data[end_date]", array("id"=>"data[end_date]", "type"=>"text", "dojoType"=>"dijit.form.DateTextBox", "label"=>"End Date", "value"=>$tabular_template_auto['end_date']));
				$output->data .= $this->i("data[interval]", array("label"=>"Interval", "type"=>"select", "default"=>$tabular_template_auto['interval'], "options"=>array("daily"=>"Daily", "weekly"=>"Weekly", "monthly"=>"Monthly", "quarterly"=>"Quarterly", "yearly"=>"Yearly"), "dojoType"=>"dijit.form.FilteringSelect"));
				$output->data .= $this->i("data[human_name]", array("label"=>"Axis Name", "type"=>"text", "default"=>$tabular_template_auto['human_name'], "dojoType"=>"dijit.form.ValidationTextBox"));
				$output->data .= "<hr />";
				$output->data .= "<div id='join_display'>
					".$table_join_ajax."
					</div>
					";
				$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();
				break;
			case ("manualsource"):
				$output->title_desc = "The ".strtoupper($this->subvar)." axis is a set of values: each made up of a set of manually created constraints. The intersection data will be indexed by the way it matches each of these values.";
				$output->data .= "<h3>Axis Source</h3>";
				$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/manualsourcesubmit", "dojoType='dijit.form.Form'");
				$output->data .= "<a href='".$this->webroot()."tabular/add/".$this->id."/".$this->subvar."/squidname/new'>Create Value</a>";
				if (empty($tabular_template_auto)) {
					$output->data .= "<p>No values can be found.</p>";
				} else {
					$output->data .= "
						<div class='reports'>
							<table cellpadding='0' cellspacing='0'>
								<tr>
									<th>Value</th>
									<th>&nbsp;</th>
								</tr>
								";
					foreach ($tabular_template_auto as $squid_tmp) {
						$squid_id = $squid_tmp['tabular_templates_manual_squid_id'];
						$output->data .= "<tr>";
						$output->data .= "<td>";
						$output->data .= ucwords($squid_tmp['human_name']);
						$output->data .= "</td>";
						$output->data .= "<td>";
						$output->data .= "<ul>";
						$output->data .= "<li><a href='".$this->webroot()."tabular/add/{$this->id}/y/squidname/{$squid_id}'>Edit</a></li>";
						$output->data .= "<li><a href='".$this->webroot()."tabular/save/{$this->id}/y/removesquidsubmit/{$squid_id}' onclick='if (confirm(\"Remove Value?\")) {return true;} else {return false;}'>Remove</a></li>";
						$output->data .= "</ul>";
						$output->data .= "</td>";
						$output->data .= "</tr>";
					}
					$output->data .= "
							</table>
						</div>
						";
				}
				$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();
				break;
			case "squidname":
				$output->data .= "<h3>Custom Value</h3>";
				$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/squidnamesubmit/".$this->aux1, "dojoType='dijit.form.Form'");
				$output->data .= $this->i("data[human_name]", array("label"=>"Value Name", "type"=>"text", "default"=>$tabular_template_auto['human_name'], "dojoType"=>"dijit.form.ValidationTextBox"));
				$output->data .= "
					<hr />
					<div class='input'>
						<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."tabular/add/{$this->id}/{$this->subvar}/manualsource\"; return false;' name='cancel' >Cancel</button>
						<button type='submit' value='Next' dojoType='dijit.form.Button' name='next' >Next</button>
					</div>
					";
				$output->data .= $this->f_close();
				break;
			case "squidconstraints":
				$output->data .= Tabular_view::view_constraints($blah);
				$output->data .= "
					<hr />
					<div class='input'>
						<button value='Back' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."tabular/add/{$this->id}/{$this->subvar}/squidname/{$this->aux1}\"; return false;' name='constraint_page_back' id='constraint_page_back' >Back</button>
						<button value='Done' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."tabular/add/{$this->id}/{$this->subvar}/manualsource\"; return false;' name='constraint_page_forward' id='constraint_page_forward' >Done</button>
					</div>
					";
				break;
		}
		return $output;
	}
	
	/**
	 * Display the intersection view
	 *
	 * @param array $blah Generic information for the view
	 * @param array $tabular_template_auto Information on the tabular template auto
	 */
	function view_add_c($blah=null, $tabular_template_auto=null) {
		switch ($this->subid) {
			case ("type"):
				$output->title = "Intersection Data Type";
				//$output->title_desc = "The intersection is a numerical column selected from the database. Values from this column will be indexed by unique values in two related colums (the X and Y axies), and will fill the area of the table.";
				$output->data .= $this->i("data[aggregate]", array("label"=>"Squid", "type"=>"radio", "value"=>"squid", "default"=>false));
				$output->data .= $this->i("data[aggregate]", array("label"=>"Kippers", "type"=>"radio", "value"=>"kippers", "default"=>false));
				$output->data .= $this->i("data[aggregate]", array("label"=>"Giant Sailor Killing Squid", "type"=>"giantsquid", "value"=>"count", "default"=>false));
				break;
			default:
			case ("source"):
				$output->title = "Intersection Data Source";
				$output->title_desc = "The intersection is a numerical column selected from the database. Values from this column will be indexed by unique values in two related colums (the X and Y axies), and will fill the area of the table.";
				$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/sourcesubmit", "dojoType='dijit.form.Form'");
				$output->data .= $this->source_column_i("data[column_id]", $blah['options'], $tabular_template_auto['column_id'], "intersection_source_type_warning(this);");
				if (!empty($blah['option_warnings'][$tabular_template_auto['column_id']])) {
					$output->data .= "<p class='warning' id='intersection_source_type_warning' style='display: block;'>".$blah['option_warnings'][$tabular_template_auto['column_id']]."</p>";
				} else {
					$output->data .= "<p class='warning' id='intersection_source_type_warning' style='display: none;'></p>";
				}
				$output->data .= "
					<script>
						function intersection_source_type_warning(o) {
							var warnings = ".json_encode($blah['option_warnings']).";

							var warning = warnings[o.value];

							var warining_div = dojo.byId('intersection_source_type_warning');

							if (warning) {
								warining_div.innerHTML = warning;
								warining_div.style.display = 'block';
							} else {
								warining_div.style.display = 'none';
								warining_div.innerHTML = '';
							}
						}
					</script>
					";
				$output->data .= "<hr />";
				$output->data .= $this->i("data[aggregate]", array("label"=>"Count All Values", "type"=>"radio", "value"=>"count", "default"=>($tabular_template_auto['aggregate'] == "count")));
				$output->data .= "<p>The number of records that match the given X axis and Y Axis.</p>";
				$output->data .= $this->i("data[aggregate]", array("label"=>"Count Only Unique Values", "type"=>"radio", "value"=>"count distinct", "default"=>($tabular_template_auto['aggregate'] == "count distinct")));
				$output->data .= "<p>The number of records, with a distinct value in the selected column, that match the given X axis and Y Axis.</p>";
				$output->data .= $this->i("data[aggregate]", array("label"=>"The Sum of All Values", "type"=>"radio", "value"=>"sum", "default"=>($tabular_template_auto['aggregate'] == "sum")));
				$output->data .= "<p>The total sum of all values that match the given X axis and Y Axis.</p>";
				$output->data .= $this->i("data[aggregate]", array("label"=>"The Minimum Value", "type"=>"radio", "value"=>"min", "default"=>($tabular_template_auto['aggregate'] == "min")));
				$output->data .= "<p>The smallest value of all values that match the given X axis and Y Axis.</p>";
				$output->data .= $this->i("data[aggregate]", array("label"=>"The Maximum Value", "type"=>"radio", "value"=>"max", "default"=>($tabular_template_auto['aggregate'] == "max")));
				$output->data .= "<p>The largest value of all values that match the given X axis and Y Axis.</p>";
				$output->data .= $this->i("data[aggregate]", array("label"=>"The Average of All Values", "type"=>"radio", "value"=>"average", "default"=>($tabular_template_auto['aggregate'] == "average")));
				$output->data .= "<p>The average of all values that match the given X axis and Y Axis.</p>";
				$output->data .= "<hr />";
				
				$output->data .= $this->i("data[human_name]", array("label"=>"Intersection Name", "type"=>"text", "default"=>$tabular_template_auto['human_name'], "dojoType"=>"dijit.form.ValidationTextBox"));
				$output->data .= "<hr />";
				$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();
				break;
		}
		return $output;
	}
	
	/**
	 * Display the edit squid constraint view
	 *
	 * @param array $blah Generic view information
	 */
	function view_add_editsquidconstraint($blah=null) {
		$output = Template_view::view_add_editconstraints($blah);
		return $output;
	}
	
	function view_add_axis($type, $columns) {
	}

	function add_axis_manual($type, $columns) {
	}

	function add_axis_automatic($type, $columns) {
	}

	function add_axis_trend($type, $columns) {
	}

	function view_add_intersection($columns) {
	}

	function view_display_table($tables, $template) {
		$output->layout = 'ajax';
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."tabular/tables_json/".$this->id."' jsId='template'></div>";
		#$output->data = "<div style='display:none;' id='holding_cellA'>";
		#foreach ($tables['catalogue'] as $i => $columns) {
		#	foreach ($columns as $i => $column) {
		#		$output->data .= "".$this->wrap_table_column($column['column_id'], $column['column_name'])."";
		#	}
		#}
		#$output->data .= "</div>";
		$output->data .= "<table id='demo' style='width: 150px;'>
		<thead>
		<tr>
		";
		#if (is_array($template)) {
		#	foreach ($template as $i => $temp) {
		#		$output->data .= "<th class='columns' dojoType='dojo.dnd.Target' id='th_".$i."' ><li class='dojoDndItem'>".$this->wrap_table_column($temp['column_id'], $temp['chuman'], $temp)."</li></th>";
		#	}
		#}
		$output->data .= "<th class='columns' dojoType='dojo.dnd.Source' id='th_".($i+1)."' ></th>
		</tr>
		</thead>
		<tbody id='demo_body' >
		</tbody>
		</table>
		<script>create_cells();save_template();</script>";
		$output->data .= $this->f_close();
		return $output;
	}

	function view_save($data, $template) {
		$output->layout = "ajax";
		$output->data = "";
		return $output;
	}

	function hook_output($results, $template, $demo=false, $now=false, $pdf=false) {
		$odd = "";
		$output->data = "";

		if ($pdf) {
			 $template[0]['header'] = stripslashes($template[0]['header']);
			 $template[0]['footer'] = stripslashes($template[0]['footer']);

			$logo_path = $this->sw_path.$this->get_theme_path();
			$logo_tmp_path = $this->sw_path.$this->tmp_path;

			if (is_file($logo_path."logo.png")) {
				$logo_name = "logo.png";
			} else if (is_file($logo_path."logo.jpg")) {
				$logo_name = "logo.jpg";
			} else if (is_file($logo_path."logo.gif")) {
				$logo_name = "logo.gif";
			}

			if (!empty($logo_name)) {
				if (!is_file($logo_tmp_path.$logo_name)) {
					symlink($logo_path.$logo_name, $logo_tmp_path.$logo_name);
				}

				$logo_url = $this->web_path.$this->tmp_path;

				$template[0]['header'] = str_replace("%logo", "<img src='$logo_url$logo_name' />", $template[0]['header']);
				$template[0]['footer'] = str_replace("%logo", "<img src='$logo_url$logo_name' />", $template[0]['footer']);
			}

			$template[0]['header'] = str_replace("%name", $template[0]['name'], $template[0]['header']);
			$template[0]['header'] = str_replace("%desc", $template[0]['description'], $template[0]['header']);
			$template[0]['header'] = str_replace("%run", date("Y-m-d H:i:s", strtotime($template[0]['last_run'])), $template[0]['header']);
			$template[0]['header'] = str_replace("%by", $template[0]['last_by'], $template[0]['header']);
			$template[0]['header'] = str_replace("%size", $template[0]['last_size'], $template[0]['header']);

			$template[0]['footer'] = str_replace("%name", $template[0]['name'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%desc", $template[0]['description'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%run", date("Y-m-d H:i:s", strtotime($template[0]['last_run'])), $template[0]['footer']);
			$template[0]['footer'] = str_replace("%by", $template[0]['last_by'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%size", $template[0]['last_size'], $template[0]['footer']);
		}

		foreach ($template as $template_tmp) {
			$axis_names[$template_tmp['type']] = ucwords($template_tmp['tabular_template_human_name']);
		}

		if (is_array($results)) {
			//re-organise the x axis so we can use it easily
			if (!empty($results['x'])) {
				foreach ($results['x'] as $result_tmp) {
					$x_tmp = $result_tmp['x'];
					$x_index[] = $x_tmp;
				}
			}

			//re-organise the y axis so we can use it easily
			if (!empty($results['y'])) {
				foreach ($results['y'] as $result_tmp) {
					$y_tmp = $result_tmp['y'];
					$y_index[] = $y_tmp;
				}
			}

			//re-organise intersection data so we can access it by x and y keys
			if (!empty($results['c'])) {
				foreach ($results['c'] as $result_tmp) {
					$x_tmp = $result_tmp['x'];
					$y_tmp = $result_tmp['y'];
					$c_tmp = $result_tmp['c'];

					//index by Y THEN X. counter-intuitive, i know, but trust me...
					$results_foo[$y_tmp][$x_tmp] = $c_tmp;
				}
			}

			if ($pdf) {
				$table_chunked = array_chunk($x_index, 7);
				unset($x_index);

				$page_counter = 1;
				$pages_total = count($table_chunked);
			} else {
				$table_chunked = array($x_index);
			}

			foreach ($table_chunked as $x_index) {
				if ($pdf) {
					if ($page_counter == $pages_total) {
						$output->data .= "<div>";
					} else {
						$output->data .= "<div style='page-break-after: always'>";
					}

					$output->data .= $template[0]['header'];
				}

				$output->data .= "
					<div class='tabular_data'>
						<table cellpadding='0' cellspacing='0'>
						";

				$table_title = "Table I. ".$axis_names['c'];

				if ($pdf) {
					$table_title .= " Page $page_counter of $pages_total.";
				}

				$output->data .= "
					<tr class='title'>
						<th colspan='".(count($results['x']) + 1)."'>$table_title</th>
					</tr>
					<tr class='x-title'>
						<th></th>
						<th class='x-title' colspan='".count($results['x'])."'>".$axis_names['x']."</th>
					</tr>
					<tr class='x-index'>
						<th class='y-title'>".$axis_names['y']."</th>
						";

				foreach ($x_index as $x_tmp) {
					$output->data .= "<th>".$x_tmp."</th>";
				}

				$output->data .= "
					</tr>
					";

				foreach ($y_index as $y_tmp) {
					$output->data .= "<tr>";
					$output->data .= "<th class='y-index'>".$y_tmp."</th>";
					foreach ($x_index as $x_tmp) {
						if (isset($results_foo[$y_tmp][$x_tmp])) {
							$c_tmp = $results_foo[$y_tmp][$x_tmp];
						} else {
							$c_tmp = null;
						}
						$output->data .= "<td>".$c_tmp."</td>";
					}
					$output->data .= "</tr>";
				}

				$output->data .= "
						</table>
					</div>
					";

				if ($pdf) {
					$output->data .= $template[0]['footer'];

					$output->data .= "</div>";

					$page_counter ++;
				}
			}
		}

		return $output;
	}

	function view_tables_json($tables) {
		$output->layout = 'ajax';
		$output->data = "{
	identifier:'tabular_template_id',
	items: [";
	$opt = array();
	foreach ($tables as $i => $option) {
		$opt[] = "{tabular_template_id: '".$option['tabular_template_id']."', column_id: '".$option['column_id']."', type: '".$option['type']."', sort: '".$option['sort']."', aggregate: '".$option['aggregate']."', label: '".$option['label']."', col_order: '".$option['col_order']."', chuman: '".$option['chuman']."', column: '".$option['column']."'}";
	}
	$output->data .= implode(",", $opt);
	$output->data .= "]
}
		";
		return $output;
	}

	function view_execute_scheduled() {
		$output->layout = "ajax";

		return $output;
	}

	function view_histories($saved_reports, $processing_report) {
		$output->title = "Histories";
		$output->title_desc = "All occasions when the report has been executed.";

		if (!empty($saved_reports) || !empty($processing_report)) {
			$output->data .= "
				<div class='reports'>
					<table cellpadding='0' cellspacing='0'>
						<tr>
							<th>Time</th>
							<th>Format</th>
							<th>Dissemination</th>
							<th>&nbsp;</th>
						</tr>
						";

			if (!empty($processing_report)) {
				$processing_id = $processing_report['template_id'];
				$processing_tr_id = "processing_$processing_id";
				$processing_ind_id = $processing_tr_id."_indicator";

				if ($processing_report['execution_queued'] == "t") {
					$message_tmp = "Report Queued for Execution";
				} else if ($processing_report['execution_executing'] == "t") {
					$message_tmp = "Executing Report";
				}

				$output->data .= "
						<tr id='$processing_tr_id'>
							<td colspan='4'>$message_tmp<span id='$processing_ind_id' class='loading_3'>...</span></td>
						</tr>
						";
			}


			if (!empty($saved_reports)) {
				foreach ($saved_reports as $report_tmp) {
					$dissemination = null;
	// 				$dissemination = rand(-10, 20);
	// 				if ($dissemination < 0) $dissemination = 0;
	// 				$dissemination = "$dissemination user".($dissemination === 1 ? "" : "s");

					$output->data .= "
							<tr>
								<td>".$report_tmp['created']."</td>
								<td>Table, Graph and CSV</td>
								<td>$dissemination</td>
								<td>
									<ul>
										<li>".$this->l("tabular/history/".$report_tmp['template_id']."/".$report_tmp['saved_report_id'], "View/Download")."</li>
									</ul>
								</td>
							</tr>
							";
				}
			}
			$output->data .= "
					</table>
				</div>
				";

			if (!empty($processing_report)) {
				$output->data .= "
					<script>
						dojo.addOnLoad(loading_update);

						function loading_update() {
							var target = window.document.getElementById('$processing_ind_id');
							if (!target) return;

							if (target.className == 'loading_3') {
								var d = dojo.xhrPost({
									url: '".$this->webroot()."tabular/processing_history_ajax/$processing_id',
									handleAs: 'text',
									sync: false,
									content: {},
									// The LOAD function will be called on a successful response.
									load: function(response, ioArgs) {
										if (response) {
											console.log(response);
											if (dojo.byId('$processing_tr_id')) {
												dojo.byId('$processing_tr_id').innerHTML = response;
											}
										} else {
											console.log('no response');
										}
										return response;
									},
									// The ERROR function will be called in an error case.
									error: function(response, ioArgs) {
										console.error('HTTP status code: ', ioArgs.xhr.status);
										return response;
									}
								});
							}

							if (!target) return;

							if (target.className == 'loading_0') {
								target.innerHTML = '.<span style=\"opacity: 0.25;\">..</span>';
								target.className = 'loading_1';

								setTimeout('loading_update();', 500);
								return;
							}

							if (target.className == 'loading_1') {
								target.innerHTML = '<span style=\"opacity: 0.25;\">.</span>.<span style=\"opacity: 0.25;\">.</span>';
								target.className = 'loading_2';

								setTimeout('loading_update();', 500);
								return;
							}

							if (target.className == 'loading_2') {
								target.innerHTML = '<span style=\"opacity: 0.25;\">..</span>.';
								target.className = 'loading_3';

								setTimeout('loading_update();', 500);
								return;
							}

							if (target.className == 'loading_3') {
								target.innerHTML = '<span style=\"opacity: 0.25;\">...</span>';
								target.className = 'loading_0';

								setTimeout('loading_update();', 500);
								return;
							}
						}
					</script>
					";
			}
		}

		return $output;
	}

	function view_history($tmp_table, $tmp_graph, $downloads) {
		if (!empty($downloads)) {
			$output->data .= "
				<ul>
				";
			foreach ($downloads as $download_text => $download_link) {
				$output->data .= "
					<li>".$this->l($download_link, $download_text, null, false)."</li>
					";
			}
			$output->data .= "
				</ul>
				";
		}

		if (!empty($tmp_table)) {
			$output->data .= '<h3>Tabular Data</h3>';
			$output->data .= $tmp_table;
		}

		if (!empty($tmp_graph)) {
			$output->data .= '<h3>Graphic Data</h3>';
			$output->data .= $tmp_graph;
		}

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

	function view_processing_history_ajax($report_query, $template_query) {
		$output->layout = "ajax";
		$dissemination = null;

		if (!empty($report_query)) {
			$output->data = "
				<td>".$report_query['created']."</td>
				<td>Table, Graph and CSV</td>
				<td>$dissemination</td>
				<td>
					<ul>
						<li>".$this->l("tabular/history/".$report_query['template_id']."/".$report_query['saved_report_id'], "View/Download")."</li>
					</ul>
				</td>
				";
		} else if (!empty($template_query)) {
			$processing_id = $template_query['template_id'];
			$processing_tr_id = "processing_$processing_id";
			$processing_ind_id = $processing_tr_id."_indicator";

			if ($template_query['execution_queued'] == "t") {
				$message_tmp = "Report Queued for Execution";
			} else if ($template_query['execution_executing'] == "t") {
				$message_tmp = "Executing Report";
			}

			$output->data = "
					<td colspan='4'>$message_tmp<span id='$processing_ind_id' class='loading_0'><span style='opacity: 0.25;'>...</span></span></td>
					";
		}

		return $output;
	}
}
?>
