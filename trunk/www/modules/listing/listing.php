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
			case "hook_style":
			case "hook_top_menu":
			case "hook_javascript":
			case "hook_menu":
			case "hook_workspace":
			case "hook_template_entry":
				// these can be called by other modules
				if (isset($data['acls']['system']['login'])) {
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
			case "view_add_select_object":
			case "view_add":
			case "view_save":
			case "hook_output":
			case "get_columns":
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
	 * @see inc/Modules::hook_style()
	 */
	function hook_style() {
		return "td.columns div{ display: none;}
		#style div.dojoDndSource {margin: 5px; background: #ccc;}
		span.list-2 {font-size: 1.2em; font-weight: bold;}
		span.list-1 {font-size: 1.1em; font-weight: bold;}
		span.list1 {font-size: 0.9em;}
		span.list2 {font-size: 0.8em;}
		#style td {display: block;}
		.label_first label {float: none;}
		";
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
	 * @see modules/template/Template::hook_menu()
	 */
	function hook_menu() {
		$menu = array();
		switch ($this->action) {
			default:
				$menu = parent::hook_menu($url);
		}
		return $menu;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template::hook_javascript()
	 */
	function hook_javascript() {
		$js = parent::hook_javascript("listing");
		return $js."
		function update_join_display(o) {
			var passContent = {};
			passContent[o.name] = o.value;
			ajax_load('".$this->webroot()."listing/table_join_ajax/".$this->id."', passContent, 'join_display');
		}

		function update_data_preview_first() {
			dojo.byId('data_preview_load').style.display = 'none';
			dojo.byId('data_preview_loading').style.display = 'block';

			url = '".$this->webroot()."listing/data_preview_first_ajax/".$this->id."';
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

			url = '".$this->webroot()."listing/data_preview_slow_ajax/".$this->id."/'+saved_report_id;
			data = {};
			div = 'data_preview';

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

	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template::view_add_select_object()
	 */
	function view_add_select_object() {
		$object_id = $this->id;

		if (empty($object_id)) return;
		$temp = parent::view_add_select_object($object_id, 'listing');
		$this->redirect("listing/add/".$temp['template_id']);
	}
	
	function view_columns() {
		$this->current = $this->get_template($this->id);
		$blah['columns'] = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT ll.*, c.name AS column_name, t.name AS table_name, c.human_name AS column_human_name, t.human_name AS table_human_name FROM list_templates ll, columns c, tables t WHERE c.table_id=t.table_id AND ll.column_id=c.column_id AND ll.template_id='".$this->id."';"));
		return $blah;
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
		if (empty($intersection)) return null;

		$selected_column_id = $_REQUEST['data']['column_id'];
		$intersection_column_id = $intersection['column_id'];

		$foobar = "<h3>Axis Relationship</h3>";
		$foobar .= "<p class='h3attach'>The selected column may be linked to first column, by one of a number of different routes.</p>";

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
			
			$explaination_tmp = "The selected column is linked, via a self referential join, to the first column.";
			$foobar .= "<p>".$explaination_tmp."</p>";
		}

		$table_joins_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM table_joins WHERE table1='".$selected_table_id."' AND table2='".$intersection_table_id."';"));

		if (empty($table_joins_query)) {
			$output = Listing_View::view_table_join_ajax($foobar);
			return $output;
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

		$output = Listing_View::view_table_join_ajax($foobar);
		return $output;
	}
	
	/**
	 * Called by Listing::view_add. Gets all data required to show the add/edit column page
	 */
	function view_editcolumn() {
		$column_query = null;
		$list_template_id = $this->subid;

		if ($list_template_id == "new") {
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
			$blah['data']['order'] = $column_query['order'];
			$blah['data']['level'] = $column_query['level'];
			$blah['data']['style'] = $column_query['style'];
			$blah['data']['show_label'] = $column_query['show_label'];
			$blah['data']['indent'] = $column_query['indent'];
			
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
		if ($this->subid == "new") {
			$_REQUEST['data']['column_id'] = reset(array_keys($blah['options']['column_id']));
			$table_join_ajax = $this->view_table_join_ajax($column_query['table_join_id']);
			$table_join_ajax = $table_join_ajax->data;
			unset($_REQUEST['data']['column_id']);
		}

		return array($blah, $table_join_ajax);
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
		$preview_table = null;
		
		switch ($this->subvar) {
			case "columns":
				if ((int)$this->id) {
					$blah = Listing::view_columns();
				}
				break;
			case "editcolumn":
				if ($this->subid) {
					$blah = array();
					list($blah, $table_join_ajax) = Listing::view_editcolumn();
				}
				break;
			case "editsquidconstraint":
				if ($this->subid) {
					$blah = array();

					list($blah, $table_join_ajax) = Tabular::view_editconstraint();
				}
				break;
			case "preview":
				if ((int)$this->id) {
					$preview_table .= '<div id="data_preview_first">';
						$preview_table .= '<div id="data_preview_loading" style="display: none; text-align: center;">Loading Report...</div>';
						$preview_table .= '<div id="data_preview_load" style="text-align: center;"><a href="javascript:update_data_preview_first();">Load Preview</a></div>';
					$preview_table .= '</div>';

					$template = $blah;
				}
				break;
			case "constraints":
				if ((int)$this->id) {
					$blah = Tabular::view_constraints();
				}
				break;
			case "editconstraint":
				if ($this->subid) {
					$blah = array();

					list($blah, $table_join_ajax) = Tabular::view_editconstraint();
				}
				break;
			case "publish":
				break;
			case "execution":
				break;
			case "access":
				$users_query = $this->call_function("ALL", "hook_access_users", array());

				foreach ($users_query as $module => $users_query_tmp) {
					$users_tmp = array_merge((array)$users_tmp, (array)$users_query_tmp['users']);
					$groups_tmp = array_merge((array)$groups_tmp, (array)$users_query_tmp['groups']);
					$users_groups_tmp = array_merge((array)$users_groups_tmp, (array)$users_query_tmp['users_groups']);
					$disabled_tmp = array_merge((array)$disabled_tmp, (array)$users_query_tmp['disabled']);
				}

				$acls_query = $this->call_function("ALL", "hook_access_report_acls", array($this->id));

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

				break;
			default:
				$this->view_add_next();
				break;
		}

		//Steps: what steps have been competed, and what step are we at
		$listing_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT count(*) as count FROM list_templates WHERE template_id='".$this->id."';"));
		$listing_templates = isset($listing_templates_query['count']) ? $listing_templates_query['count'] : 0;

		//put all step data in a usable array
		//TODO: listing_templates
		if ($listing_templates === 0) {
			$steps[0][0] = "Add Columns";
			$steps[0][2] = true;
			$steps[0][3] = "disabled";
		} else {
			$steps[0][0] = "Edit Columns";
			$steps[0][2] = false;
			$steps[0][3] = "";
		}
		$steps[0][1] = $this->webroot()."listing/add/".$this->id."/columns";
		if ($this->subvar == "columns") $steps[0][3] .= " current";

		$steps[1][0] = "Preview";
		$steps[1][1] = $this->webroot()."listing/add/".$this->id."/preview";
		$steps[1][2] = $steps[0][2];
		$steps[1][3] = "";
		if ($steps[1][2]) $steps[1][3] = "disabled";
		if ($this->subvar == "preview") $steps[1][3] .= " current";

		$steps[2][0] = "Constraints";
		$steps[2][1] = $this->webroot()."listing/add/".$this->id."/constraints";
		$steps[2][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		$steps[2][3] = "";
		if ($steps[2][2]) $steps[2][3] = "disabled";
		if ($this->subvar == "constraints") $steps[2][3] .= " current";

		$steps[3][0] = "Publishing";
		$steps[3][1] = $this->webroot()."listing/add/".$this->id."/publish";
		$steps[3][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[3][2]) $steps[3][3] = "disabled";
		if ($this->subvar == "publish") $steps[3][3] .= " current";

		$steps[4][0] = "Execution";
		$steps[4][1] = $this->webroot()."listing/add/".$this->id."/execution";
		$steps[4][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[4][2]) $steps[4][3] = "disabled";
		if ($this->subvar == "execution") $steps[4][3] .= " current";

		$steps[5][0] = "Access";
		$steps[5][1] = $this->webroot()."listing/add/".$this->id."/access";
		$steps[5][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[5][2]) $steps[5][3] = "disabled";
		if ($this->subvar == "access") $steps[5][3] .= " current";

		$template = $this->get_template($this->id);
		$output = Listing_View::view_add($template, $blah, $steps, $preview_table, $table_join_ajax, $listing_template);

		return $output;
	}

	function view_add_next() {
		if (empty($this->id)) {
			$this->redirect("template/home/");
			return;
		}

		$this->redirect("listing/add/".$this->id."/columns");
	}
	
	private function sortByColumn($results) {
		$new_results = array();
		foreach ($results as $i => $result) {
			$new_results[$result['column_id']] = $result;
		}
		return $new_results;
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
				$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM list_templates lt WHERE lt.template_id='".$this->id."' AND lt.list_template_id='".$this->subid."';"));
				//$update_query = $this->sortByColumn($update_query);

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
			case "editsquidconstraintsubmit":
				if ($this->aux1) {
					Tabular::view_editconstraintsubmit();
				}
				break;
			case "constraintlogicsubmit":
				Tabular::view_constraintlogicsubmit();
				break;
			case "editconstraintsubmit":
				if ($this->subid) {
					Tabular::view_editconstraintsubmit();
				}
				break;
			case "removeconstraintsubmit":
				$template_id = $this->id;
				$constraint_id = $this->subid;

				if (empty($template_id)) return;
				if (empty($constraint_id)) return;

				$constraint_logic = $this->get_constraint_logic($template_id);

				//if the constraint to be removed is the only constraint in the logic: simply set logic to ''
				if (preg_match("/^ ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
					$constraint_logic = "";

// 				} else if (preg_match("/^ ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
// 					var_dump($matches);
// 					var_dump("INVALID");

				//if the constrain to be be removed is at the start and is followed by an and/or, then remove the constraint and the and/or
				} else if (preg_match("/^ ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/^ ?($constraint_id) ?(AND|OR) ?/", "", $constraint_logic);

// 				} else if (preg_match("/\( ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
// 					var_dump($matches);
// 					var_dump("INVALID");

				//if the constraint to be removed is on it's own in a set of brackets, then remove the constraint only. This will make the logic invailid...
				} else if (preg_match("/\( ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/\( ?($constraint_id) ?\)/", "", $constraint_logic);

				//if the constraint to be removed comes after a bracket and is followed by an and/or, then remove the constraint and the and/or
				} else if (preg_match("/ ?\( ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/\( ?($constraint_id) ?(AND|OR) ?/", "(", $constraint_logic);

				//if the constraint to be removed comes after an and/or and is at the end of the logic, then remove the and/or and the constraint
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/ ?(AND|OR) ?($constraint_id) ?$/", "", $constraint_logic);

				//if the constraint to be removed comes after an and/or and is followed by a bracket, then remove the and/or and the constraint
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/ ?(AND|OR) ?($constraint_id) ?\)/", ")", $constraint_logic);

				//if the constraint to be removed comes after an and/or and is followed by another and/or, then remove the constraint and the second and/or
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/ ?($constraint_id) ?(AND|OR) ?/", " ", $constraint_logic);

				} else {
				}

				$this->dobj->db_query($this->dobj->update(array("logic"=>$constraint_logic), "template_id", $this->id, "tabular_constraint_logic"));

				$this->dobj->db_query("DELETE FROM tabular_constraints WHERE tabular_constraints_id='$constraint_id';");
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

		$this->view_add_next();
		return;
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

	function execute($template_id, $demo) {
		$template = $this->get_columns($template_id);
		$constraints = $this->get_constraints($template_id);
// 		$constraint_logic = $this->get_constraint_logic($template_id);

		/* Generate the query to run */
		$query = $this->hook_query($template, $constraints, $demo);

		$start = time();
		$data = parent::hook_run_query($template[0]['object_id'], $query);
		$end = time();
print_r($data);

		$saved_report_id = $this->save_results($template_id, $data, "f", ($demo ? "t" : "f"), ($end-$start), 1);

		return $saved_report_id;
	}
}


class Listing_View extends Template_View {
	
	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template_View::view_add()
	 */
	/**
	 * (non-PHPdoc)
	 * @see modules/template/Template_View::view_add()
	 */
	function view_add($template, $blah=null, $steps=null, $preview_table=null, $listing_template_columns=null, $table_join_ajax=null, $listing_template=null) {
		if (!empty($steps)) {
			$output->submenu .= "<ol>";
			foreach ($steps as $i => $step) {
				$step[3] = isset($step[3]) ? $step[3] : null;
				$output->submenu .= "<li>";
				$output->submenu .= ($i + 1 === 1 ? "Step " : "").($i + 1).". ";
				$output->submenu .= "<a href=\"".$step[1]."\" class=\"".$step[3]."\" ".($step[2] ? "onClick=\"void(0); return false;\"" : "").">";
				$output->submenu .= ucwords($step[0]);
				$output->submenu .= "</a>";
				$output->submenu .= "</li>";
			}
			$output->submenu .= "</ol>";
		}

		switch ($this->subvar) {
			default:
			case "columns":
				$output->title = "Select Columns";
				//$output->title_desc = "Select the output columns for the List report. Each row will be made up of the values from each of these columns";
				$output->data .= Listing_view::view_columns($blah);

				/*$output->data .= $this->f("listing/save/".$this->id."/".$this->subvar, "dojoType='dijit.form.Form'");
				foreach ($blah['options'] as $i => $column) {
					//TODO: DEFAULT
					//TODO: "dojoType"=>"dijit.form.CheckBox",
					$output->data .= $this->i("data[columns][".$i."]", array("label"=>$column, "type"=>"checkbox", "default"=>$listing_template_columns[$column]['column_id'], "class"=>"label_first"));
				}

				$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();*/
				break;
			case "editcolumn":
				$output->title = "Add/Edit Column";
				$output->title_desc = "";

				$output->data .= Listing_view::view_editcolumn($blah);

				break;
			case "editsquidconstraint":
				$output->title = "Edit Constraint";
				$output->title_desc = "";

				$output->data .= Tabular_view::view_editconstraint($blah);
				break;
			case "preview":
				$output->title = "Preview";
				$output->title_desc = "";

				$output->data .= $preview_table;

				break;
			case "constraints":
				$output->title = "Constraints";
				$output->title_desc = "";

				$output->data .= Tabular_view::view_constraints($blah);

				break;
			case "editconstraint":
				$output->title = "Edit Constraint";
				$output->title_desc = "";

				$output->data .= Tabular_view::view_editconstraint($blah);

				break;
			case "publish":
				//prevent the editor from adding more escapes than neccessary
				$template['header'] = stripslashes($template['header']);
				$template['footer'] = stripslashes($template['footer']);

				$output->title = "Publishing";
				$output->title_desc = "";

				$output->data .= $this->f("tabular/save/".$this->id."/publishsubmit", "id='publishing_form' dojoType='dijit.form.Form'");
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
				break;
			case "execution":
				//prevent the editor from adding more escapes than neccessary
				$template['email_body'] = stripslashes($template['email_body']);

				$output->title = "Execution";
				$output->title_desc = "";

				$output->data .= $this->f("tabular/save/".$this->id."/executionsubmit", "id='execution_form'", "dojoType='dijit.form.Form'");
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_manually]", "label"=>"Execute Manually", "type"=>"radio", "value"=>"manually"/*, "onchange"=>'console.log("skoo");'*/));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_hourly]", "label"=>"Execute Hourly", "type"=>"radio", "value"=>"hourly", "default"=>($template['execute_hourly'] == "t")));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_daily]", "label"=>"Execute Daily", "type"=>"radio", "value"=>"daily", "default"=>($template['execute_daily'] == "t")));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_weekly]", "label"=>"Execute Weekly", "type"=>"radio", "value"=>"weekly", "default"=>($template['execute_weekly'] == "t")));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_monthly]", "label"=>"Execute Monthly", "type"=>"radio", "value"=>"monthly", "default"=>($template['execute_monthly'] == "t")));
				$output->data .= "<hr />";

				$output->data .= $this->i("data[execute_hour]", array("id"=>"data[execute_hour]", "div_id"=>"execute_hour_div", "label"=>"Hour of Execution", "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "default"=>$template['execute_hour'], "options"=>array(
					"0"=>"0 AM",
					"1"=>"1 AM",
					"2"=>"2 AM",
					"3"=>"3 AM",
					"4"=>"4 AM",
					"5"=>"5 AM",
					"6"=>"6 AM",
					"7"=>"7 AM",
					"8"=>"8 AM",
					"9"=>"9 AM",
					"10"=>"10 AM",
					"11"=>"11 AM",
					"12"=>"12 PM",
					"13"=>"1 PM",
					"14"=>"2 PM",
					"15"=>"3 PM",
					"16"=>"4 PM",
					"17"=>"5 PM",
					"18"=>"6 PM",
					"19"=>"7 PM",
					"20"=>"8 PM",
					"21"=>"9 PM",
					"22"=>"10 PM",
					"23"=>"11 PM"
					)));
				$output->data .= "<p>Hour of the day to execute the report.</p>";

				$output->data .= $this->i("data[execute_dayofweek]", array("id"=>"data[execute_dayofweek]", "div_id"=>"execute_dayofweek_div", "label"=>"Day of Execution", "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "default"=>$template['execute_dayofweek'], "options"=>array(
					"1"=>"Monday",
					"2"=>"Tuesday",
					"3"=>"Wednesday",
					"4"=>"Thursday",
					"5"=>"Friday",
					"6"=>"Saturday",
					"7"=>"Sunday"
					)));
				$output->data .= "<p>Day of the week to execute the report.</p>";

				$output->data .= $this->i("data[execute_day]", array("id"=>"data[execute_day]", "div_id"=>"execute_day_div", "label"=>"Date of Execution", "type"=>"select", "dojoType" =>"dijit.form.FilteringSelect", "default"=>$template['execute_day'], "options"=>array(
					"1"=>"1st",
					"2"=>"2nd",
					"3"=>"3rd",
					"4"=>"4th",
					"5"=>"5th",
					"6"=>"6th",
					"7"=>"7th",
					"8"=>"8th",
					"9"=>"9th",
					"10"=>"10th",
					"11"=>"11th",
					"12"=>"12th",
					"13"=>"13th",
					"14"=>"14th",
					"15"=>"15th",
					"16"=>"16th",
					"17"=>"17th",
					"18"=>"18th",
					"19"=>"19th",
					"20"=>"20th",
					"21"=>"21st",
					"22"=>"22nd",
					"23"=>"23rd",
					"24"=>"24th",
					"25"=>"25th",
					"26"=>"26th",
					"27"=>"27th",
					"28"=>"28th",
					"29"=>"29th",
					"30"=>"30th",
					"31"=>"31st (or last day of month)"
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
				break;
			case "access":
				$output->title = "Access";
				$output->title_desc = "";

				$output->data .= $this->f("tabular/save/".$this->id."/accesssubmit");
				$output->data .=  $blah['acl_markup'];
				$output->data .= $this->i("submit", array("label"=>"Save", "type"=>"submit", "value"=>"Save", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();
				break;
		}
		return $output;
	}

	/**
	 * Called by Listing::view_add to show the columns for a report.
	 */
	function view_columns($blah) {
		$output = "";
		$output .= "<a href='".$this->webroot()."listing/add/".$this->id."/editcolumn/new'>Create Column</a>";
	
		if (!empty($blah['columns'])) {

			$output .= "
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
				
				$output .= "<tr>";
				$output .= "<td>";
				$output .= "<span>";
				$output .= $column['table_human_name'].".".$column['column_human_name'];
				$output .= "</span>";
				$output .= "</td>";
				$output .= "<td>";
				$output .= "<ul>";

				$output .= "<li><a href='".$this->webroot()."listing/add/".$this->id."/editcolumn/".$list_template_id."'>Edit</a></li>";
				$output .= "<li><a href='".$this->webroot()."listing/save/".$this->id."/removecolumn/".$list_template_id."' onclick='if (confirm(\"Remove Column?\")) {return true;} else {return false;}'>Remove</a></li>";

				$output .= "</ul>";
				$output .= "</td>";
				$output .= "</tr>";
			}
			$output .= "
					</table>
				</div>
				";
		} else {

			$output .= "<p>No columns can be found.</p>";
		}

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

	/**
	 * Output the markup for the table join radio buttons (called via ajax)
	 *
	 * @param $table_join_markup
	 * @return The HTML string output
	 */
	function view_table_join_ajax($table_join_markup) {
		$output->layout = "ajax";
		$output->data = $table_join_markup;
		$output->data .= "<hr />";
		return $output;
	}
	
	/**
	 * Called by Listing_view::view_add to show the add/edit constraint form. Also used for add/edit manual axis contraint
	 *
	 * @param array $blah The parameters that describe how to display the form elements
	 * @return The HTML string output
	 */
	function view_editcolumn($blah) {
		// Set empty variables
		$output = "";
		if (!isset($blah['data']['column_id'])) {
			$blah['data']['column_id'] = null;
		}
		if (isset($blah['error'])) {
			$output .= "<p style='color: #a40000; font-family: Arial; font-size: 10pt; font-weight: bold;'>".$blah['error']."</p>";
		}

		switch ($this->subvar) {
			case "editcolumn":
				$list_template_id = $this->subid;

				$output .= $this->f("listing/save/{$this->id}/editcolumnsubmit/{$list_template_id}", "dojoType='dijit.form.Form'");
				$cancel = "<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."listing/add/{$this->id}/columns\"; return false;' name='cancel' >Cancel</button>";
				break;
		}

		$output .= $this->i("data[column_id]", array("id"=>"data[column_id]", "label"=>"Column", "type"=>"select", "default"=>$blah['data']['column_id'], "options"=>$blah['options']['column_id'], "onchange"=>"update_join_display(this);", "dojoType"=>"dijit.form.FilteringSelect"));
		$output .= $this->i("data[label]", array("id"=>"data[label]", "label"=>"Column Label", "type"=>"text", "default"=>$blah['data']['label'], "dojoType"=>"dijit.form.TextBox"));
		$output .= $this->i("data[show_label]", array("id"=>"data[show_label]", "label"=>"Show the Column Label", "type"=>"checkbox", "default"=>$blah['data']['show_label'], "dojoType"=>"dijit.form.CheckBox"));
		$output .= $this->i("data[duplicates]", array("id"=>"data[duplicates]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//TODO: Does subtotal mean on change or at the bottom???
		$output .= $this->i("data[subtotal]", array("id"=>"data[subtotal]", "label"=>"Subtotal This Column", "type"=>"checkbox", "default"=>$blah['data']['subtotal'], "dojoType"=>"dijit.form.CheckBox"));
		$output .= $this->i("data[sort]", array("id"=>"data[sort]", "label"=>"Sort The Report By This Column", "desc"=>"Sorting will also be based on the order in which the columns are listed", "type"=>"checkbox", "default"=>$blah['data']['sort'], "dojoType"=>"dijit.form.CheckBox"));
		//$output .= $this->i("data[aggregate]", array("id"=>"data[aggregate]", "label"=>"???", "type"=>"???", "default"=>$blah['data']['aggregate'], "dojoType"=>"dijit.form.CheckBox"));
		//$output .= $this->i("data[optional]", array("id"=>"data[duplicates]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//$output .= $this->i("data[level]", array("id"=>"data[duplicates]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//$output .= $this->i("data[style]", array("id"=>"data[duplicates]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		//$output .= $this->i("data[indent]", array("id"=>"data[duplicates]", "label"=>"Allow Duplicates", "type"=>"checkbox", "default"=>$blah['data']['duplicates'], "dojoType"=>"dijit.form.CheckBox"));
		$output .= "<hr />";
		$output .= "<div id='join_display'>";
		
		//TODO: Not required - DELETE $output .= $table_join_ajax;
		$output .= "</div>";

		$output .= "
			<div class='input'>
				{$cancel}<button type='submit' value='Next' dojoType='dijit.form.Button' name='submit' >Save</button>
			</div>
			";

		$output .= $this->f_close();

		return $output;
	}
	
}


?>
