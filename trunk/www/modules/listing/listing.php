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
 * Listing.php
 *
 * The Listing report template module.
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */
class Listing extends Template {
	var $conn;
	var $dobj;
	var $name = "Listing";
	var $description = "The listing report type. Single axis and a list of values. e.g. a list of customers";
	var $module_group = "Templates";
	
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
	function execute_demo($template_id) {
	function execute_manually($template_id) {
	function execute_scheduled($template_id) {
	function execute($template_id, $demo) {
	function get_constraints($template_id) {
	function hook_query($template, $constraints, $demo=false) {
	function sortByColumn($results) {

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
		$valid = false;
		$listing_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT count(*) as count FROM list_templates WHERE template_id='".$this->id."';"));
		$listing_templates = isset($listing_templates_query['count']) ? $listing_templates_query['count'] : 0;
		//put all step data in a usable array
		if ($listing_templates === 0) {
			$steps[0][0] = "Add Columns";
			$steps[0][2] = false;
			$steps[0][3] = "disabled";
		} else {
			$steps[0][0] = "Edit Columns";
			$steps[0][2] = true;
			$steps[0][3] = "";
			$valid = true;
		}
		$steps[0][1] = $this->webroot()."listing/add/".$this->id."/columns";
		if ($this->subvar == "columns") $steps[0][3] .= " current";
		
		$parent_steps = parent::hook_menu($valid, $valid, $valid, $valid, $valid);
		$steps = array_merge($steps, $parent_steps);
		
		return $steps;
	}
	
	/**
	 * Overwrite hook_top_menu in Template.php - this module should have no admin tools
	 *
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
			ajax_load('".$this->webroot()."listing/table_join_ajax/".$this->id."', passContent, 'join_display');
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
			"label"=>"List Report",
			"module"=>"listing",
			"description"=>"A list report takes an index column from the database, then additional columns. Each row of the report shows attributes from the columns, that are related to the index."
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
		$this->redirect("listing/add/".$temp['template_id']);
	}
		
	/**
	 * First point of contact for almost every page, when creating a listing report.
	 * Runs queries to gather data to display in Listing_View::view_add().
	 * Takes aguments about which page from the url in the id, subvar, subid, etc variables
	 *
	 * (non-PHPdoc)
	 * @see modules/template/Template::view_add()
	 */
	function view_add() {
		//define default variables
		$table_join_ajax = null;
		$listing_template = null;
		$listing_template_auto = null;
		$blah = null; //TODO: bad variable name. Must change
		$output = "";
		
		switch ($this->subvar) {
			case "columns":
				if ((int)$this->id) {
					$blah = Listing::view_columns();
					$output = Listing_View::view_add_columns($blah);
				}
				break;
			case "editcolumn":
				if ($this->subid) {
					$blah = array();
					list($blah, $table_join_ajax) = Listing::view_editcolumn();
					$output = Listing_View::view_add_editcolumn($blah);
				}
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
	 * Called as the action on forms on almost every page when creating a listing report.
	 * Once complete, calls Listing::view_add_next() to go to the next page.
	 * Takes aguments about which page from the url in the id, subvar, subid, etc variables
	 *
	 * @return null
	 */
	function view_save() {
		switch ($this->subvar) {
			case "cancel":
				break;
			case "editcolumnsubmit":
				if ($this->subid != "new") {
					$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM list_templates lt WHERE lt.template_id='".$this->id."' AND lt.list_template_id='".$this->subid."';"));
					//$update_query = $this->sortByColumn($update_query);
				}
				if (isset($update_query['list_template_id'])) {
					$list_template_id = $update_query['list_template_id'];
					$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "list_template_id", $list_template_id, "list_templates"));
				} else {
					$_REQUEST['data']['template_id'] = $this->id;
					$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "list_templates"));
				}
				break;
			case "removecolumn":
				$template_id = $this->id;
				$list_template_id = $this->subid;
				
				if (empty($template_id)) return;
				if (empty($list_template_id)) return;
				$this->dobj->db_query("DELETE FROM list_templates WHERE template_id='$template_id' AND list_template_id='".$list_template_id."';");
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
	 * Called by Listing::view_save() and others. Works out the present status of a report, if anything step needs to be performed (like add in axis), or if not, what is the next step after the current, then redirects accordingly
	 *
	 */
	function view_add_next() {
		if (empty($this->id)) {
			$this->redirect("template/home/");
			return;
		}  else if ($this->subvar == "editconstraintsubmit") {
			$this->redirect("listing/add/".$this->id."/constraints");
		} else if ($this->subvar == "removeconstraintsubmit") {
			$this->redirect("listing/add/".$this->id."/constraints");
		}
		
		
		$this->redirect("listing/add/".$this->id."/columns");
	}
	
	/**
	 * Called by column source and edit constraint. Given a selected column id and the first column id, shows all the possible table joins between them, and produces html form elements to allow the user so select one.
	 *
	 * @param int $current_join The id of the current join
	 * @return The HTML string output
	 */
	function view_table_join_ajax($current_join=null) {
		$intersection = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM list_templates lt INNER JOIN columns c ON (c.column_id=lt.column_id) WHERE lt.template_id='".$this->id."' ORDER BY list_template_id LIMIT 1;"));
		// Is this the first column. If so there is nothing to join it to.
		if (empty($intersection)) die;
		$intersection_column_id = $intersection['column_id'];
		$output = parent::view_table_join_ajax($current_join, $intersection_column_id);
		return $output;
	}
	
	/**
	 * Called by Listing::execute. Given a template, generate the queries to get all the data for the report.
	 *
	 * @param $template int The template id
	 * @param $constraints array Any constraints to apply
	 * @param $demo boolean Is this a demo (ie restrict to 10 results)
	 * @return A SQL string
	 */
	function hook_query($template, $constraints, $demo=false) {
		/* TODO: Change $$ to module specific */
		$cols = array();
		$sort = array();
		$tables = array();
		$join_tables = array();
		$optional_tables = array();
		$group = array();
		$where = array();

// 		print_r($template);

		foreach ($template as $column) {
			if ($column['index'] != "t") continue;

			$alias_tmp = "ac";

			$col = $alias_tmp.".".$column['column']."";
			$group[] = $alias_tmp.".".$column['column'];
			$sort[] = $alias_tmp.".".$column['column']." ".$column['sort'];
			$cols[$column['label']] = $col;
			$tables[$column['table']] = $column['table_id'];

			$join_tables['c'] = array(
				"table"=>$column['table'],
				"table_id"=>$column['table_id'],
				"alias"=>"ac"
				);

			$aliai[$column['table_id']] = $alias_tmp;

			break;
		}

		/* SELECT Clause */
		foreach ($template as $i => $post) {
			if ($post['index'] == "t") continue;


			/*if ($post['table_id'] == $join_tables['c']['table_id']) {
				$alias_tmp = "ac";
			} else */if (!empty($aliai[$post['table_id']])) {
				$alias_tmp = $aliai[$post['table_id']];
			} else {
				$alias_counter ++;
				$join_tables[$alias_counter] = array(
					"table"=>$post['table'],
					"table_id"=>$post['table_id'],
					"alias"=>"a{$alias_counter}",
					"join_id"=>"275"
					);
				$alias_tmp = "a{$alias_counter}";
				$aliai[$post['table_id']] = $alias_tmp;
			}

			$col = "";
			/* This is added to a. ensure all columns are unique and b. aggregates sort properly */
			if (!$post['label']) {
				$post['label'] = $alias_tmp.".".$post['column'];
			}
			if ($post['aggregate'] && $post['aggregate'] != "none") {
				$use_group = true;
				if ($post['aggregate'] == "countdistinct") {
					$post['aggregate'] = "count";
					$distinct = "DISTINCT";
				}
				$col = $post['aggregate']."(".$distinct." {$alias_tmp}.".$post['column'].")";
			} else {
				$col = $alias_tmp.".".$post['column']."";
				$group[] = $alias_tmp.".".$post['column'];
				$sort[] = $alias_tmp.".".$post['column']." ".$post['sort'];
			}
			$cols[$post['label']] = $col;
			$tables[$post['table']] = $post['table_id'];
// 			$join_tables[$post['table']] = $post['table_id'];
			// TODO: Optional tables
/*			if ($post['optional']) {
				$optional_tables[$post['table']] = $post['table_id'];
			}*/


		}
		/* WHERE clause */
		if (is_array($constraints)) {
			foreach ($constraints as $i => $post) {
				$join_tables[$post['table']] = $post['table_id'];
				$where[] = $this->where($post);
			}
		}

		/* GROUP BY Clause */
		if (!$use_group) {
			$group = false;
		}
// 		$query = "SELECT ".implode(", ", $cols). " FROM ".$table_str." ".$where_string." ".$group_string." ORDER BY ".implode(", ", $sort). " ";

		/* LIMIT Clause */
		if ($demo) {
			$limit = 10;
		}

		print_r($join_tables);

		$query = $this->hook_build_query($cols, $join_tables, $where, $group, $sort, $limit);
		var_dump($cols);
		var_dump($query);
		var_dump($aliai);
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

		$output = Listing_View::hook_output($results, $template, $demo, $now, $pdf);
		return $output;
	}
	
	function view_columns() {
		$this->current = $this->get_template($this->id);
		$blah['columns'] = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT ll.*, c.name AS column_name, t.name AS table_name, c.human_name AS column_human_name, t.human_name AS table_human_name FROM list_templates ll, columns c, tables t WHERE c.table_id=t.table_id AND ll.column_id=c.column_id AND ll.template_id='".$this->id."';"));
		return $blah;
	}
	
	/**
	 * Called by Listing::view_add. Gets all data required to show the add/edit column page
	 */
	function view_editcolumn() {
		$column_query = null;
		$table_join_ajax = null;
		$list_template_id = $this->subid;

		if ($list_template_id == "new") {
			$blah['data']['index'] = false;
			$blah['data']['duplicates'] = false;
			$blah['data']['subtotal'] = false;
			$blah['data']['sort'] = false;
			$blah['data']['aggregate'] = false;
			$blah['data']['label'] = "";
			$blah['data']['optional'] = false;
			$blah['data']['col_order'] = 0;
			$blah['data']['level'] = false;
			$blah['data']['style'] = "";
			$blah['data']['display_label'] = false;
			$blah['data']['indent_cells'] = false;
		} else {
			$column_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM list_templates ll WHERE ll.template_id='".$this->id."' AND ll.list_template_id='".$list_template_id."' LIMIT 1;"));
			$blah['data']['list_template_id'] = $column_query['list_template_id'];
			$blah['data']['column_id'] = $column_query['column_id'];
			$blah['data']['index'] = $column_query['index'];
			$blah['data']['duplicates'] = $column_query['duplicates'];
			$blah['data']['subtotal'] = $column_query['subtotal'];
			$blah['data']['sort'] = $column_query['sort'];
			$blah['data']['aggregate'] = $column_query['aggregate'];
			$blah['data']['label'] = $column_query['label'];
			$blah['data']['optional'] = $column_query['optional'];
			$blah['data']['col_order'] = $column_query['col_order'];
			$blah['data']['level'] = $column_query['level'];
			$blah['data']['style'] = $column_query['style'];
			$blah['data']['display_label'] = $column_query['display_label'];
			$blah['data']['indent_cells'] = $column_query['indent_cells'];
			
			$_REQUEST['data']['column_id'] = $column_query['column_id'];
			$table_join_ajax = $this->view_table_join_ajax($column_query['table_join_id']);
			$table_join_ajax = $table_join_ajax->data;
			unset($_REQUEST['data']['column_id']);
		}

		$this->current = $this->get_template($this->id);
		$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

		foreach ($tables['catalogue'] as $i => $column) {
			foreach ($column as $j => $cell) {
				$column_id = $cell['column_id'];
				$blah['options']['column_id'][$column_id] = $cell['table_name'].".".$cell['column_name'];
			}
		}

		// TODO: What is this for?
		if ($this->subid == "new" && isset($column_query['list_template_id'])) {
			$_REQUEST['data']['column_id'] = reset(array_keys($blah['options']['column_id']));
			$table_join_ajax = $this->view_table_join_ajax($column_query['table_join_id']);
			$table_join_ajax = $table_join_ajax->data;
			unset($_REQUEST['data']['column_id']);
		}

		return array($blah, $table_join_ajax);
	}
	
	private function sortByColumn($results) {
		$new_results = array();
		foreach ($results as $i => $result) {
			$new_results[$result['column_id']] = $result;
		}
		return $new_results;
	}
	
	function get_columns($template_id) {
		$query = "SELECT l.*, t.*, c.column_id, tb.table_id, c.human_name as chuman, tb.human_name as thuman, c.name as column, tb.name as table FROM list_templates l, templates t, columns c, tables tb WHERE tb.table_id=c.table_id AND c.column_id=l.column_id AND t.template_id=l.template_id AND t.template_id=".$template_id." ORDER BY l.level, l.col_order;";
		$data = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		return $data;
	}
	
	function get_constraints($template_id) {
		$query = "SELECT l.*, t.template_id, t.name, t.draft, t.module, t.object_id, c.dropdown, c.example, c.column_id, tb.table_id, c.human_name as chuman, tb.human_name as thuman, c.name as column, tb.name as table FROM list_constraints l, templates t, columns c, tables tb WHERE tb.table_id=c.table_id AND c.column_id=l.column_id AND t.template_id=l.template_id AND t.template_id=".$template_id.";";
		$data = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		return $data;
	}
	
	function execute_manually($template_id) {
		return $this->execute($template_id, false);
	}

	function execute_scheduled($template_id) {
		return $this->execute($template_id, false);
	}

	function execute_demo($template_id) {
		return $this->execute($template_id, true);
	}


	/**
	 * Called by Listing::view_data_preview_first_ajax() to get values for axies only. Calls Listing::execute() with appropriate arguments to do so
	 *
	 * @return The display object
	 */
	function execute_demo_quick($template_id) {
		return $this->execute($template_id, true, true);
	}
	
	/**
	 * Small helper function to call the execute command for a demo
	 *
	 * @param int $template_id The template to run
	 * @return The id of the saved report
	 */
	function execute_demo_cellwise($template_id) {
		return $this->execute($template_id, true, false, true);
	}
	
	function execute($template_id, $demo) {
		$template = $this->get_columns($template_id);
		$constraints = $this->get_constraints($template_id);
 		$constraint_logic = $this->get_constraint_logic($template_id);

		/* Generate the query to run */
		$query = $this->hook_query($template, $constraints, $demo);

		$start = time();
		$data = parent::hook_run_query($template[0]['object_id'], $query);
		$end = time();
print_r($data);

		$saved_report_id = $this->save_results($template_id, $data, "f", ($demo ? "t" : "f"), ($end-$start), 1);

		return $saved_report_id;
	}
	
	/**
	 * This is called to display the preview (cellwise) of this report
	 *
	 * @return The display object
	 */
	function view_data_preview_slow_ajax() {
		$data_preview = "";
		if ((int)$this->id) {
			$template = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='".$this->id."';"));
			$saved_report_id = $this->execute_demo_cellwise($this->id);
			if (!$saved_report_id) {
				return Listing_View::view_data_preview_ajax("finished");
			}
			
			if ($template['publish_table'] == "t") {
				$data_preview .= "<h3>Data</h3>";
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
		$output = Listing_View::view_data_preview_ajax($data_preview);
		return $output;
	}
	
	/**
	 * This is to setup the preview process
	 *
	 * @return The display object
	 */
	function view_data_preview_first_ajax() {
		$data_preview = "";
		if ((int)$this->id) {
			$template = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='".$this->id."';"));
			$saved_report_id = $this->execute_demo_quick($this->id);
			$data_preview .= '<div id="saved_report_id" style="display: none;">'.$saved_report_id.'</div>';
			$data_preview .= '<div id="data_preview">';
			
			if ($template['publish_table'] == "t") {
				$data_preview .= "<h3>Data</h3>";
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
		
		$output = Listing_View::view_data_preview_ajax($data_preview);
		return $output;
	}
	

}


class Listing_View extends Template_View {
	
	/**
	 * Display the main columns view
	 *
	 * @param array $blah Generic column information
	 */
	function view_add_columns($blah=null) {
		$output->title = "Select Columns";
		//$output->title_desc = "Select the output columns for the List report. Each row will be made up of the values from each of these columns";
		$output->data = "";
		$output->data .= "<a href='".$this->webroot()."listing/add/".$this->id."/editcolumn/new'>Create Column</a>";
		if (!empty($blah['columns'])) {
			$output->data .= "
				<div class='reports'>
					<table cellpadding='0' cellspacing='0'>
						<tr>
							<th>Column</th>
							<th>&nbsp;</th>
						</tr>
						";

			foreach ($blah['columns'] as $column) {
				$column_id = $column['column_id'];
				$list_template_id = $column['list_template_id'];
				$output->data .= "<tr>";
				$output->data .= "<td>";
				$output->data .= "<span>";
				$output->data .= $column['table_human_name'].".".$column['column_human_name'];
				$output->data .= "</span>";
				$output->data .= "</td>";
				$output->data .= "<td>";
				$output->data .= "<ul>";
				$output->data .= "<li><a href='".$this->webroot()."listing/add/".$this->id."/editcolumn/".$list_template_id."'>Edit</a></li>";
				$output->data .= "<li><a href='".$this->webroot()."listing/save/".$this->id."/removecolumn/".$list_template_id."' onclick='if (confirm(\"Remove Column?\")) {return true;} else {return false;}'>Remove</a></li>";
				$output->data .= "</ul>";
				$output->data .= "</td>";
				$output->data .= "</tr>";
			}
			$output->data .= "
					</table>
				</div>
				";
		} else {
			$output->data .= "<p>No columns can be found.</p>";
		}
		return $output;
	}
	
	/**
	 * Called by Listing_view::view_add to show the add/edit constraint form. Also used for add/edit manual axis contraint
	 *
	 * @param array $blah The parameters that describe how to display the form elements
	 * @return The HTML string output
	 */
		function view_add_editcolumn($blah=null) {
		$output->title = "Add/Edit Column";
		$output->title_desc = "";
		$output->data = "";
		if (!isset($blah['data']['column_id'])) {
			$blah['data']['column_id'] = null;
		}
		if (isset($blah['error'])) {
			$output->data .= "<p style='color: #a40000; font-family: Arial; font-size: 10pt; font-weight: bold;'>".$blah['error']."</p>";
		}
		
		switch ($this->subvar) {
			case "editcolumn":
				$list_template_id = $this->subid;
				$output->data .= $this->f("listing/save/{$this->id}/editcolumnsubmit/{$list_template_id}", "dojoType='dijit.form.Form'");
				$cancel = "<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."listing/add/{$this->id}/columns\"; return false;' name='cancel' >Cancel</button>";
				break;
		}
		
		$output->data .= $this->i("data[column_id]", array("id"=>"data[column_id]", "label"=>"Column", "type"=>"select", "default"=>$blah['data']['column_id'], "options"=>$blah['options']['column_id'], "onchange"=>"update_join_display(this);", "dojoType"=>"dijit.form.FilteringSelect"));
		$output->data .= $this->i("data[label]", array("id"=>"data[label]", "label"=>"Column Label", "type"=>"text", "default"=>$blah['data']['label'], "dojoType"=>"dijit.form.TextBox"));
		$output->data .= $this->i("data[display_label]", array("id"=>"data[display_label]", "label"=>"Show the Column Label", "type"=>"checkbox", "default"=>$blah['data']['display_label'], "dojoType"=>"dijit.form.CheckBox"));
		$output->data .= $this->i("data[duplicates]", array("id"=>"data[duplicates]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//TODO: Does subtotal mean on change or at the bottom???
		$output->data .= $this->i("data[subtotal]", array("id"=>"data[subtotal]", "label"=>"Subtotal This Column", "type"=>"checkbox", "default"=>$blah['data']['subtotal'], "dojoType"=>"dijit.form.CheckBox"));
		$output->data .= $this->i("data[sort]", array("id"=>"data[sort]", "label"=>"Sort The Report By This Column", "desc"=>"Sorting will also be based on the order in which the columns are listed", "type"=>"checkbox", "default"=>$blah['data']['sort'], "dojoType"=>"dijit.form.CheckBox"));
		//$output->data .= $this->i("data[aggregate]", array("id"=>"data[aggregate]", "label"=>"???", "type"=>"???", "default"=>$blah['data']['aggregate'], "dojoType"=>"dijit.form.CheckBox"));
		//$output->data .= $this->i("data[optional]", array("id"=>"data[optional]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//$output->data .= $this->i("data[level]", array("id"=>"data[level]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//$output->data .= $this->i("data[style]", array("id"=>"data[style]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//$output->data .= $this->i("data[indent_cells]", array("id"=>"data[indent_cells]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
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
	
	function view_save($data, $template) {
		$output->layout = "ajax";
		$output->data = "";
		return $output;
	}
	
	function hook_output($results, $template, $demo=false, $now=false, $show_header_footer=true) {
		$odd = "";
		$col = array();
		$col_st = array();
		/* Get the maximum number of columns per row. Used for multirow output */
		$num_cols = 0;
		$row_cols = 0;
		$curlevel = false;
		foreach ($template as $i => $tpl) {
			$row_cols++;
			if (!$tpl['label']) {
				$tpl['label'] = $tpl['table'].".".$tpl['column'];
			}
			$col[$tpl['label']] = $tpl['duplicates'];
			$col_st[$tpl['label']] = $tpl['subtotal'];
			$col_lv[$tpl['label']] = $tpl['level'];
			if ($curlevel !== false && $tpl['level'] != $curlevel) {
				if ($row_cols > $num_cols) {
					$num_cols = $row_cols;
				}
				$row_cols = 1;
			}
			$curlevel = $tpl['level'];
			$col_ic[$tpl['label']] = $tpl['indent_cells'];
			if ($tpl['indent_cells']) {
				$row_cols = $row_cols + $tpl['indent_cells'];
			}
			$col_dl[$tpl['label']] = $tpl['display_label'];
			$col_style[$tpl['label']] = $tpl['style'];
		}
		$output->data = "";

		$output->data .= "<table class='results'>";
		$n = $num_cols;
		/* Create the header */
		if (is_array($results)) {
			$header = "<tr>";
			$curlevel = false;
			$multi = false;
			foreach ($results[0] as $i => $cell) {
				/* The results are broken into multiple lines here */
				if ($curlevel !== false && $col_lv[$i] != $curlevel) {
					if (trim($header, "<trh/>") == "") {
						$header = "<tr>";
					} else {
						$multi = true;
						$td = strrpos($header, "<th");
						$header = substr_replace($header, "<th colspan='".$n."'", $td, 3);
						$header .= "</tr>";
						$output->data .= $header;
						$header = "<tr>";
					}
				}
				$curlevel = $col_lv[$i];
				if ($col_ic[$i]) {
					$n = $n - $col_ic[$i];
					$header .= str_repeat("<th></th>", $col_ic[$i]);
				}
				/* Do not display the label */
				if ($col_dl[$i] != 't') {
					$header .= "<th></th>";
				} else {
					$header .= "<th class='".$col_style[$i]."'>".$i."</th>";
				}
				$n--;
			}
			$header .= "</tr>";
			if (trim($header, "<trh/>") == "") {
				$header = "";
			}
		}
		$output->data .= $header;
		$subtotal = array();
		$prev = array();
		if (is_array($results)) {
			foreach ($results as $i => $row) {
				$n = $num_cols;
				$output->data .= "<tr class='".$odd."'>";
				$prow = "";
				$prow_go = false;
				$level = false;
				foreach ($row as $j => $cell) {
					$output_cell = "";
					/* The results are broken into multiple lines here */
					if ($level !== false && $col_lv[$j] != $level) {
						$td = strrpos($output->data, "<td");
						$output->data = substr_replace($output->data, "<td colspan='".($n)."'", $td, 3);
						$output->data .= "</tr><tr class='".$odd."'>";
						$prow .= "</tr><tr>";
						$n = $num_cols;
					}
					if ($col_ic[$j]) {
						$n = $n - $col_ic[$j];
						$output->data .= str_repeat("<td></td>", $col_ic[$j]);
						$prow .= str_repeat("<td></td>", $col_ic[$j]);
					}
					$level = $col_lv[$j];
					/* Calculate the subtotal */
					if (is_numeric($cell)) {
						$subtotal[$j] += $cell;
					}
					if ($col[$j] == 'f' && $i>0 && $results[$i][$j] == $results[$i-1][$j]) {
						$output->data .= "<td class='".$col_style[$j]."' ></td>";
					} else {
						$output->data .= "<td class='".$col_style[$j]."' >".$cell."</td>";
					}
					if ($cell == $prev[$j] || !$prev[$j]) {
						$prow .= "<td></td>";
					} elseif ($col_st[$j] == 't') {
						$prow_go = true;
						$prow .= "<td><span class='subtotal'>".$subtotal[$j]."</span></td>";
						$subtotal[$j] = 0;
					} else {
						$prow .= "<td></td>";
					}
					$prev[$j] = $cell;
					$n--;
				}
				$td = strrpos($output->data, "<td");
				$output->data = substr_replace($output->data, "<td colspan='".$n."'", $td, 3);
				$output->data .= "</tr>";
				if ($prow_go) {
					$output->data .= "<tr>".$prow."</tr>";
				}
				$odd = $odd ? "" : "odd";
			}
			if ($prow_go) {
				$output->data .= "<tr>".$prow."</tr>";
			}
		}
		$output->data .= "</table>";

		if ($show_header_footer) {
			/* Setup the header and footer */
			$template[0]['header'] = str_replace("%name", $template[0]['name'], $template[0]['header']);
			$template[0]['header'] = str_replace("%desc", $template[0]['description'], $template[0]['header']);
			$template[0]['header'] = str_replace("%run", $template[0]['last_run'], $template[0]['header']);
			$template[0]['header'] = str_replace("%by", $template[0]['last_by'], $template[0]['header']);
			$template[0]['header'] = str_replace("%size", $template[0]['last_size'], $template[0]['header']);
			$template[0]['footer'] = str_replace("%name", $template[0]['name'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%desc", $template[0]['description'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%run", $template[0]['last_run'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%by", $template[0]['last_by'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%size", $template[0]['last_size'], $template[0]['footer']);
			
			$output->data = $template[0]['header'] . $output->data. $template[0]['footer'];
		}
		return $output;
	}
	
}


?>
