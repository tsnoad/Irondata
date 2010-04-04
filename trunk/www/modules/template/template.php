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
	
	function hook_pagetitle() {
		return "Report";
	}

	function hook_workspace() {
		return array("title"=>"Report Workspace", "path"=>"".$this->webroot()."template/workspace_display");
	}

	function hook_roles() {
		return null;
	}
	
	function __construct() {
		include("conf.php");
		$this->conf = $conf;

		$this->sw_path = $conf['paths']['sw_path'];
		$this->tmp_path = $conf['paths']['tmp_path'];

		include_once("inc/db.php");
		$this->dobj = new DB();
		$url = explode("/", $_REQUEST['url']);
		if (array_key_exists(2, $url)) {
			$this->id = $url[2];
		}
		if (array_key_exists(3, $url)) {
			$this->subvar = $url[3];
		}
		if (array_key_exists(4, $url)) {
			$this->subid = $url[4];
		}
		if (array_key_exists(5, $url)) {
			$this->aux1 = $url[5];
		}
		if (array_key_exists(6, $url)) {
			$this->aux2 = $url[6];
		}
		if (array_key_exists(7, $url)) {
			$this->aux3 = $url[7];
		}
		if (array_key_exists(8, $url)) {
			$this->aux4 = $url[8];
		}
		$this->module = $url[0];
		$this->action = $url[1];
	}

	function hook_admin_tools() {
// 		$admin_tools[] = array("template/default_access", "Default Report Access - New User");
// 		$admin_tools[] = array("template/default_access", "Default Report Access - New Report");
		return $admin_tools;
	}
	
	/* The Top Menu hook function. 
	 * Displays the module in the main menu. Or menu of primary functions. 
	 */
	function hook_top_menu() {
		return array(
			"reports" => $this->l("template/home", "Reports")
			);
	}

	/* The Menu hook function. 
	 * Displays items in the side bar. This can be dependant on the actual URL used. 
	 */
	function hook_menu() {
// 		$menu = array();
// 
// 		$menu[$i][$j] = array();
// 
// if (!$this->newschool) {
// 		switch ($this->action) {
// 			case "view_add":
// 			case "add":
// if (!$this->newschool) {
// 				if ((int)$this->id) {	
// 					$this->current = $this->get_template($this->id);
// 					$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));
// 					$menu = array();
// 					foreach ($tables['catalogue'] as $i => $column) {
// 						foreach ($column as $j => $cell) {
// 							$menu[$i][$j] = array(
// 								"name"=>$this->wrap_column($cell['column_id'], $j),
// 								'type'=>'dnd',
// 								'link'=>null
// 							);
// 						}
// 					}
// 				}
// }
// 				break;
// 			default:
// // 				$this->current = $this->get_template($this->id);
// // 				$this->current = $this->get_template();
// 				break;
// 		}
// }
// 		return $menu;
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

	function hook_javascript($type = "listing"){
		return "

		var xsource;
		var xnodes;
		var xt;
		function dnd_cancel(nodes, source) {
			xsource = source;
			xnodes = nodes;
			window.setTimeout('xsource.node.appendChild(xnodes[0])', 100);
		}
		function dnd_cancel_clone(t) {
			xt = t;
			window.setTimeout('destroy_dom(xt.node.lastChild.id);', 100);
		}

		function dnd_getlabel(nodes) {
			buttons = nodes[0].getElementsByTagName('button');
			node_id = buttons[0].id.substring(11);
			label = buttons[0].childNodes[1].childNodes[0].innerHTML;
			return label;
		}

		function destroy_dom(id) {
			if (!dojo.byId(id)) {
				return;
			}
			dojo.forEach(
			dojo.query('.dijit*', dojo.byId(id)),
			function(xid) {
				try {
					dijit.getEnclosingWidget(xid).destroy();
				} catch(err) {
					//continue;
				}
			}
			);
			try {
				dojo.byId(id).parentNode.removeChild(dojo.byId(id));
			} catch(err) {
				//continue;
			}
		}
	
		function remove_column(id) {
			console.log(id);
			if (id.target) {
				cid = id.target.id.substr(7);
				id = id.target.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.id;
			} else if (id.id) {
				cid = id.id.substr(7);
				id = id.containerNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.id;
			}
			console.log(cid, id);
			results = ajax_load('".$this->webroot()."".$type."/remove/".$this->id."/'+cid);
			try {
				if (dojo.byId(id)) {
					destroy_dom(id);
				}
			} catch(err) {
				// skip
			}
		};
		function remove_constraint(id) {
			console.log(id);
			if (id.target) {
				console.log('T', id.target.id);
				cid = id.target.id.substr(11);
				id = id.target.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.id;
			} else {
				console.log('I', id.id);
				cid = id.id.substr(11);
				id = id.containerNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.id;
			}
			console.log(cid, id);
			results = ajax_load('".$this->webroot()."".$type."/remove_constraint/".$this->id."/'+cid);
			try {
				if (dojo.byId(id)) {
					destroy_dom(id);
				}
			} catch(err) {
				// skip
			}
		};

		function save_constraints() {
			var passContent = {};
			var container = dojo.byId('constraints');
			all_columns = container.getElementsByTagName('input');
			for (var i=0; i<all_columns.length; i++) {
				if (dijit.byId(all_columns[i].id) != undefined) {
					last_pos = all_columns[i].id.lastIndexOf('_');
					id = all_columns[i].id.substring(last_pos+1)
					passContent['data[con_'+id+'][id_'+id+']'] = id;
					passContent['data[con_'+id+'][id]'] = id;
					passContent['data[con_'+id+']['+all_columns[i].id.substring(0, last_pos)+']'] = dijit.byId(all_columns[i].id).getValue();
				}
			}
			return passContent;
		}

		function create_con_column(id, name, data) {

			//Add the optional cell
			var container = dojo.doc.createElement('tr');
			container.id = 'row_cons_'+id;

			var td = dojo.doc.createElement('td');
			container.appendChild(td)
			var button = dojo.doc.createElement('div');
			button.innerHTML = name;
			td.appendChild(button);

			constraint_options = new dojo.data.ItemFileReadStore({id:'constraint_options',url: '".$this->webroot()."".$type."/constraint_options_json'});

			var xid = create_input('id', 'hidden', {id:'c_id_'+id, value: id, label: false});
			var type = create_input('type', 'select', {label: false, id:'type_'+id, value: data['type'][0], store:constraint_options, onChange: save_template});
			var value = create_input('value', 'textbox', {label: false, id:'value_'+id, value: data['value'][0], onChange: save_template});
			var choose = create_input('choose', 'checkbox', {label: false, id:'choose_'+id, value: data['choose'][0], onChange: save_template});
			var remove = create_input('remove_con', 'button', {label: 'Remove', id:'remove_con_'+id, onClick: remove_constraint});

			var td2 = dojo.doc.createElement('td');
			container.appendChild(td2)
			td2.appendChild(xid);
			td2.appendChild(type);

			var td3 = dojo.doc.createElement('td');
			container.appendChild(td3)
			td3.appendChild(value);

			var td4 = dojo.doc.createElement('td');
			container.appendChild(td4)
			td4.appendChild(choose);

			var td5 = dojo.doc.createElement('td');
			container.appendChild(td5)
			td5.appendChild(remove);

			return container;
		}

		";
	}
	
	function view_add($module='', $type='') {
// 		if ($_REQUEST['module']) {
// 			$template_id = $this->add_template($_REQUEST);
// 			$this->redirect($_REQUEST['module']."/add/".$template_id);
// 		} else {
			$modules = $this->call_function("ALL", "hook_template_entry");
			$objects = $this->call_function("catalogue", "get_databases");

			$output = Template_View::view_add($module, $objects, $type, $modules);
			return $output;
// 		}
	}
	
	function wrap_column($id, $name, $values=array()) {
		/* Drop down button or tooltipdialog? */
		$col = "<div id='raw_column_".$id."' class='column' dojoType='dijit.form.Button'>";
		$col .= "<span>".$name."</span>";
		$col .= "</div>";
		return $col;
	}
	
	function wrap_constraint_column($id, $name, $values=array()) {
		/* Drop down button or tooltipdialog? */
		$col = "<div id='conscolumn_".$id."' class='column' dojoType='dijit.form.DropDownButton'>";
		$col .= "<span>".$name."</span>";
		$col .= "<div dojoType='dijit.TooltipDialog' id='conscolumn_dialog_".$id."'>";
		$col .= $this->b("remove", array("label"=>"Remove", "dojo"=>"dijit.form.Button", "onclick"=>"remove_column(this);", "id"=>"remove_conscolumn_".$id));
		$col .= "</div>";
		$col .= "</div>";
		return $col;
	}

	function column_options($module) {
		$output = "";
		$output->data = "";
		return $output;
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
		$query = "SELECT t.*, (SELECT count(*) from saved_reports r WHERE t.template_id=r.template_id AND r.draft='f') as saved FROM templates t ORDER BY name;";
		return $this->dobj->db_fetch_all($this->dobj->db_query($query));
	}
		
	function get_report() {
		$query = "SELECT t.*, (SELECT count(*) from saved_reports r WHERE t.template_id=r.template_id AND r.draft='f') as saved FROM templates t WHERE template_id='".$this->id."';";
		return $this->dobj->db_fetch($this->dobj->db_query($query));
	}
	
	function save_results($template_id, $data, $draft='t', $demo='f', $runtime=0, $runby=0) {
		$now = date("Y-m-d H:i:s");

		$saved_report_id = $this->dobj->nextval("saved_reports");

		if (strtolower($draft) == "t") {
			$query = $this->dobj->db_query("DELETE FROM saved_reports WHERE template_id='$template_id' AND draft='t' AND demo='".$demo."'");
		}

		$query = $this->dobj->db_query("INSERT INTO saved_reports (saved_report_id, template_id, report, draft, demo, created, run_time, run_by, run_size) VALUES ('$saved_report_id', '$template_id', $$".json_encode($data)."$$, '$draft', '$demo', '$now', '$runtime', '$runby', '".count($data)."')");

		return $saved_report_id;
	}
	
	function view_save_reports() {
		$query = "UPDATE saved_reports SET draft='f' WHERE template_id='".$this->id."'";
		$query = $this->dobj->db_query($query);
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

		if ($this->subvar == "preview") {
			$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND $where_template_id AND r.demo='t' ORDER BY r.created LIMIT 1";
		} else {
			$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND $where_template_id AND r.demo='f' ORDER BY r.created LIMIT 1";
// 			$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND r.template_id='".$template_id."' AND r.created='".$this->subvar."'";
		}

		$res = $this->dobj->db_fetch($this->dobj->db_query($query));

		return $res;
	}

	function view_saved_list() {
		$query = "SELECT t.*, r.* FROM templates t, saved_reports r WHERE t.template_id=r.template_id AND r.template_id='".$this->id."' AND r.draft='f' ORDER BY r.created DESC";
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

// 			foreach ($where as $w_tmp) {
// 				if (!is_array($w_tmp)) {
// 					$w_tmp = array($w_tmp);
// 				}
// 
// 				if (empty($w_tmp[1])) $w_tmp[1] = "AND";
// 
// 				$w .= " ".$w_tmp[1]." ".$w_tmp[0]." ";
// 			}

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
		//first things first: the intersection column's table
		$table_tmp = $foobar['c']['table'];
		$alias_tmp = $foobar['c']['alias'];
		$return .= "$table_tmp $alias_tmp ";

		//create an associate array of alises, keyed by table id, so we can look them up when needed
		$aliases[$foobar['c']['table_id']] = $foobar['c']['alias'];

		//if we need to create aliases for joined columns, use this counter so we don't get two with the same name
		$alias_counter = 1;

		//create and array of keys for x and y axies, constraints, etc. then loop through
		$axis_keys = array_combine(array_keys($foobar), array_keys($foobar));
		unset($axis_keys['c']);

		foreach ($axis_keys as $axis) {
			if (empty($foobar[$axis])) continue;

			//hook_build_query has told us how to join this table
			if (!empty($foobar[$axis]['manual_join'])) {
				$table_tmp = $foobar[$axis]['table'];
				$alias_tmp = $foobar[$axis]['alias'];
				$manual_join_tmp = $foobar[$axis]['manual_join'];

				//use the join string hook_build_query gave us
				$return .= "JOIN $table_tmp $alias_tmp $manual_join_tmp ";

				//and that's all for this table... next!
				continue;
			}

			//add this axis' alias to the array
			$aliases[$foobar[$axis]['table_id']] = $foobar[$axis]['alias'];

			//this table id
			$table1_tmp = $foobar[$axis]['table_id'];
			//insersection column's table id: every table that is called must somehow be linked to the intersection column's table
			$table2_tmp = $foobar['c']['table_id'];
			//table join id to be used to link this table to the intersection
			$table_join_tmp = $foobar[$axis]['join_id'];

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
				unset($step_start);
				unset($step_end);
				unset($intermediate_step);

				//if this step is the first step
				if ($step == $first_step) {
					//if column a's table is the intersection column's table
					if ($step_a_table_id == $foobar['c']['table_id']) {
						//then column a is the start column
						$step_start = $step_a;
						$step_end = $step_b;
					//else if column b's table is the intersection column's table
					} else if ($step_b_table_id == $foobar['c']['table_id']) {
						//then column b is the start column
						$step_start = $step_b;
						$step_end = $step_a;
					}
				//if this step is the last step
				} else if ($step == $last_step) {
					//if column a's table is the axis column's table
					if ($step_a_table_id == $foobar[$axis]['table_id']) {
						$step_start = $step_b;
						//then column a is the end column
						$step_end = $step_a;
					//else if column b's table is the axis column's table
					} else if ($step_b_table_id == $foobar[$axis]['table_id']) {
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
					unset($prev_step_start);
					unset($prev_step_end);

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
				$step_start_table_id = $columns[$step_start_column_id]['table_id'];
				$step_end_table_id = $columns[$step_end_column_id]['table_id'];

				//start and end column table names
				$step_start_table_name = $tables[$step_start_table_id]['name'];
				$step_end_table_name = $tables[$step_end_table_id]['name'];

				//end column table name, ready for insertion into sql
				$table_tmp = $step_end_table_name;

				//if no alias has been set for the start column's table
				if (empty($aliases[$step_start_table_id])) {
					//then set one
					$aliases[$step_start_table_id] = "j".$foobar[$axis]['alias'].$alias_counter;
					$alias_counter ++;
				}

				//if no alias has been set for the end column's table
				if (empty($aliases[$step_end_table_id])) {
					//then set one
					$aliases[$step_end_table_id] = "j".$foobar[$axis]['alias'].$alias_counter;
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
	
	function view_display_constraints() {
		$constraints = $this->get_constraints($this->id);
		$options = $this->hook_constraint_options();
		$object = $this->dobj->db_fetch($this->dobj->db_query("SELECT object_id FROM templates WHERE template_id='".$this->id."'"));
		$tables = $this->call_function("catalogue", "get_structure", array($object['object_id'], $constraints));
		$output = Template_View::view_display_constraints($tables, $options, $constraints);
		return $output;
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

	function view_add_details() {
		$template = $this->get_template($this->id);
		$output = Template_View::view_add_details($template);
		return $output;
	}
	
	function view_home() {
// print_r($_SESSION);
		$reports = $this->get_reports();
		return Template_View::view_workspace_display($reports);

// 		$modules = $this->call_function("ALL", "hook_workspace");
// 		return Template_View::view_home($modules);
	}
}

class Template_View {
	function view_home($modules) {
// 		$output->data = "<div id='workspace_container' dojoType='dijit.layout.TabContainer' style='height: 100%;'>";

// 		//place the report workspace first
// 		$template_module = $modules['template'];
// 		unset($modules['template']);
// 		array_unshift($modules, $template_module);

// 		foreach ($modules as $i => $module) {
// 			$output->data .= "<div href='".$module['path']."' dojoType='dijit.layout.ContentPane' title='".$module['title']."' style='width:100%; height:100%;'></div>";
// 		}
// 		$output->data .= "</div>";
// 		return $output;
	}
	
	function view_add($module, $objects, $type='', $modules) {
		$module = $this->id;

		if (empty($module)) {
			$output->data = "<h3>".$this->l("template/add/tabular", "Create Tabular Report")."</h3>";
			$output->data .= "<p class='h3attach'>A tabular report takes numerical values from a selected database column, and indexes them by unique values in the X axis and Y axis, also taken from database columns, that have a relationship with the first column.</p>";

			$output->data .= "<h3>".$this->l("template/add/listing", "Create List Report", (empty($modules['listing']) ? "class='disabled'" : ""))."</h3>";
			$output->data .= "<p class='h3attach'>A list report takes an index column from the database, then additional columns. Each row of the report shows attributes from the columns, that are related to the index.</p>";
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
	
	function view_display_constraints($tables, $options, $constraints) {
		$output->layout = 'ajax';
		$output->title = "Constraints";
		$output->data .= "<p class='description'>Restrict the output based on the selected fields by dragging the appropriate columns from the list of tables on the left to the space below. </p>";
		$output->data .= "<table id='constraints' class='constraints template'>";
		$output->data .= "<tr><th>Column</th>
		<th>Constraint</th>
		<th>Value</th>
		<th>User Choice</th>
		<th></th>
		</tr>";
		$output->data .= "<tr style='height: 25px;'><td colspan='6' class='constraint' dojoType='dojo.dnd.Target'>
		<p class='description drop'>Drop the columns that make up the constraint here.</p></td>
		</tr>";
		if (is_array($constraints)) {
			foreach ($constraints as $i => $constraint) {
				$choose = $constraint['choose'] == "t" ? true : false;
				$output->data .= "<tr id='row_cons_".$constraint['column_id']."' style='height: 25px;'><td class='constraint'>".$constraint['chuman']."</td>
				<td class='type'>".$this->i("type_".$constraint['column_id'], array("id"=>"type_".$constraint['column_id'], "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "label"=>false, "options"=>$options, "default"=>$constraint['type'], "onChange"=>"save_template()"))."</td>";
				if ($constraint['dropdown'] == 't') {
					$values = explode(",", $constraint['example']);
					$val_options = array();
					foreach ($values as $i=>$val) {
						$val_options[$val] = $val;
					}
					$output->data .= "<td class='type'>".$this->i("value_".$constraint['column_id'], array("id"=>"value_".$constraint['column_id'], "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "options"=>$val_options , "label"=>false, "default"=>$constraint['value'], "onChange"=>"save_template()"))."</td>";
				} else {
					$output->data .= "<td class='type'>".$this->i("value_".$constraint['column_id'], array("id"=>"value_".$constraint['column_id'], "type"=>"text", "dojoType"=>"dijit.form.TextBox", "label"=>false, "default"=>$constraint['value'], "onChange"=>"save_template()"))."</td>";
				}
				$output->data .= "<td class='type'>".$this->i("choose_".$constraint['column_id'], array("id"=>"choose_".$constraint['column_id'], "type"=>"checkbox", "dojoType"=>"dijit.form.CheckBox", "label"=>false, "default"=>$choose, "onChange"=>"save_template()"))."</td>
				<td class='remove'>".$this->i("remove", array("id"=>"remove_con_".$constraint['column_id'], "type"=>"button", "dojoType"=>"dijit.form.Button", "label"=>"Remove", "onClick"=>"remove_constraint(this)"))."</td>
				</tr>";
			}
		}
		$output->data .= "</table>";
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

		if (is_array($reports)) {
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
				unset($view_id);

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

	function theme_reports($report, $view_id) {
		$theme = $this->get_theme();
		$webroot = $this->webroot();
		$themeroot = $webroot.'themes/'.$theme.'/';
		$output = "";

		$draft = $report['draft']=='t' ? "draft" : "";
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

		$output .= "
						<li>".$this->l($report['module']."/histories/".$report['template_id'], "Histories")."</li>
						<li>".$this->l($report['module']."/execute_manually/".$report['template_id'], "Execute", "onclick='if (confirm(\"Execute report?\")) {return true;} else {return false;}'")."</li>
						<li>".$this->l($report['module']."/add/".$report['template_id'], "Edit")."</li>
						"./*<li><a href=''>Duplicate</a></li>.*/"
						<li>".$this->l($report['module']."/delete/".$report['template_id'], "Remove", "onclick='if (confirm(\"Remove report?\")) {return true;} else {return false;}'")."</li>
					</ul>
				</td>
			</tr>
			";
		return $output;
	}

// 	function theme_saved_reports($report) {
// 		$theme = $this->get_theme();
// 		$webroot = $this->webroot();
// 		$themeroot = $webroot.'themes/'.$theme.'/';
// 		$output = "";
// 		$output .= "<div class='report_div'>";
// 			$output .= "<div class='title' style='position: relative;'>";
// 				$output .= "<div class='filename' >".$report['name']."</div>";
// 			$output .= "</div>";
// 		$output .= "<div class='icon'><a href='".$webroot."".$report['module']."/add/".$report['template_id']."'>";
// 			$output .= "<img src='".$themeroot."images/".$report['module'].".png ' />";
// 			$output .= "<div class='links'>".$this->l($report['module']."/saved_report/".$report['template_id']."/".$report['created'], "View") ." </div>";
// 		$output .= "</a></div>";
// 		$output .= "<div class='report_info'>";
// 			$run = $report['created'] ? date("Y-m-d H:i:s", strtotime($report['created'])) : 'never run';
// 			$output .= "<div class='details'><span class='label'>Last Run: </span>".$run."</div>";
// 			$output .= "<div class='details'><span class='label'>Run Time: </span>".$report['run_time']." seconds</div>";
// 			$output .= "<div class='details'><span class='label'>Run By: </span>".$report['run_by']."</div>";
// 			$output .= "<div class='details'><span class='label'>Result Size: </span>".$report['run_size']."</div>";
// 			$details = $report['description'] ? $report['description'] : "No description available.";
// 			$output .= "<div class='details'><span class='label'>Description: </span>".$details."</div>";
// 		$output .= "</div>";
// 		$output .= "</div>";
// 		return $output;
// 	}

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

	function view_add_details($template) {
		if (!$template['header']) {
			$template['header'] = $this->default_header();
		}
		if (!$template['footer']) {
			$template['footer'] = $this->default_footer();
		}
		$output->layout = 'ajax';
		$output->title = "Report Details";
		$output->data = "<p class='description'>When you finish a report you can change it from draft to production. You can also add a description and change the report name here.</p>\n";
		$output->data .= "<div id='details template'>";
		$output->data .= $this->i("data[name]", array("label"=>"Name", "default"=>$template['name'], "dojo"=>"dijit.form.TextBox",  "onChange"=>"ajax_load(\"".$this->webroot()."template/save_template/".$this->id."\", {\"data[name]\":this.value} );"));
		$output->data .= $this->i("data[description]", array("label"=>"Description", "default"=>$template['description'], "dojo"=>"dijit.form.Textarea",  "onChange"=>"ajax_load(\"".$this->webroot()."template/save_template/".$this->id."\", {\"data[description]\":this.value} );"));
		$output->data .= $this->i("data[draft]", array("label"=>"Draft", "default"=>$template['draft'], 'type'=>'checkbox',  "dojo"=>"dijit.form.CheckBox",  "onClick"=>"ajax_load(\"".$this->webroot()."template/save_template/".$this->id."\", {\"data[draft]\":this.getValue()} );"));
		$output->data .= $this->i("data[header]", array("type"=>"wysiwyg", "label"=>"Report Header", "default"=>$template['header'], "dojo"=>"dijit.Editor",  "onChange"=>"ajax_load(\"".$this->webroot()."template/save_template/".$this->id."\", {\"data[header]\":this.getValue()} );"));
		$output->data .= $this->i("data[footer]", array("type"=>"wysiwyg", "label"=>"Report Footer", "default"=>$template['footer'], "dojo"=>"dijit.Editor",  "onChange"=>"ajax_load(\"".$this->webroot()."template/save_template/".$this->id."\", {\"data[footer]\":this.getValue()} );"));
		$output->data .= "<p class='description'>The following placeholders can be used to dynamically update the header and footer at runtime. %logo, %name, %desc, %run, %by, %size</p>";
		$output->data .= "</div>";
		return $output;
	}

	function hook_run($data, $template, $demo=false, $now=false, $foo_json=null) {
// 		if (!$demo) {
// 			$output->title = "Quizblorg";
// 		}
// 
// 		$saved_report_id = $this->id;
// 
// 		if (!$demo) {
// // 			$output->data .= "<button dojoType='dijit.form.Button' onClick='ajax_load(\"".$this->webroot()."tabular/save_reports/".$this->id."\", undefined, \"message\")' >Save Results</button>";
// 
// // 			$hook = $this->call_function("ALL", "hook_export_entry");
// // 			foreach ($hook as $i => $entry) {
// // 				$output->data .= "<button dojoType='dijit.form.Button' onClick='window.location=\"".$this->webroot()."".$entry['module']."/".$entry['callback']."/".$this->id."/".$now."\"' >".$entry['label']."</button>";
// // 			}
// // 			$output->data .= "<button dojoType='dijit.form.Button'  onClick='window.location=\"".$this->webroot()."tabular/graph/".$this->id."/".$now."\"' >Generate Graph</button>";
// // 			$output->data .= "<button dojoType='dijit.form.Button' onClick='window.location=\"".$this->webroot()."workspace/home/\"' >Close</button>";
// 
// 
// 			$output->data .= "<h4>".$this->l("", "Download Table, Graph and CSV")."</h4>";
// 			$output->data .= "<h4>".$this->l("", "Download Table")."</h4>";
// 			$output->data .= "<h4>".$this->l("", "Download Graph")."</h4>";
// 			$output->data .= "<h4>".$this->l("", "Download CSV")."</h4>";
// 		}
// 
// 		if ($template[0]['publish_format'] == "table" || $template[0]['publish_format'] == "table and graph") {
// 			$output->data .= "<h3>Tabular Data</h3>";
// // 			$tmp_output = $this->hook_output(array($data, $template, $demo, $now));
// // 			$output->data .= $tmp_output->data;
// 		}
// 
// 		if ($template[0]['publish_format'] == "graph" || $template[0]['publish_format'] == "table and graph") {
// 			$output->data .= "<h3>Graphic Data</h3>";
// // 			$tmp_output = $this->call_function("graphing", "hook_graph", array($template[0]['graph_type'], $foo_json, true, false));
// // 			$output->data .= $tmp_output['graphing']['object'];
// 
// 			$tmp_output = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template[0]['graph_type'], true, false));
// 			$output->data .= $tmp_output['graphing']['object'];
// 		}
// 		return $output;
	}

}
?>