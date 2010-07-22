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
		dojo.subscribe('/dnd/drop', function(source,nodes,iscopy,t){
			if (source.parent.parentNode.className == 'style') {
				//do nothing
			} else {
				label = dnd_getlabel(nodes);
				if (t.node.className.substr(0, 10) == 'constraint') {
					col = create_con_column(node_id, label, {'type':['eq'], 'value':[''], 'choose':[false]});
					if (col) {
						t.node.parentNode.parentNode.appendChild(col);
					}
				}
				if (t.node.className.substr(0, 7) == 'columns') {
					col = create_cell_column(node_id, label, {'sort':['ASC'], 'optional':[false]});
					if (col) {
						t.node.parentNode.parentNode.appendChild(col);
					}
				}
			}
			window.setTimeout('save_template()', 1000);
			window.setTimeout('remove_dropped_column()', 1000);
			return false;
		});

		/*
		 * Remove Dropped Column Function
		 * Once a column is dragged and dropped from the left bar, to Select Report Columns, the dragged button that was copied needs to be removed. This method is ugly as all hell, but at the time I could not find a better way...
		 */
		function remove_dropped_column() {
			//The table that contains the selected report columns is always called columns. Get all list item children of this table.
			var dropped_columns = window.document.getElementById('columns').getElementsByTagName('li');
			//if there are any...
			if (dropped_columns) {
				//... loop through them
				for (var i in dropped_columns) {
					//the ones we want allways have an id that starts with 'dojoUnique'. Look for this.
					if (dropped_columns[i].id && dropped_columns[i].id.substr(0, 10) == 'dojoUnique') {
						//DESTROY!
						dropped_columns[i].parentNode.removeChild(dropped_columns[i]);
					}
				}
			}
		}

		function save_template() {
			current = dijit.byId('mainTabContainer').selectedChildWidget.containerNode;
			if (current.id == 'page_columns') {
				passContent = save_columns();
			}
			if (current.id == 'page_style') {
				passContent = save_styles();
			}
			if (current.id == 'page_group') {
				passContent = save_groups();
			}
			if (current.id == 'page_constraints') {
				passContent = save_constraints();
			}
			if (passContent) {
				results = ajax_load('".$this->webroot()."listing/save/".$this->id."', passContent, 'demo');
			}
		};

		function save_columns() {
			var passContent = {};
			var container = dojo.byId('columns');
			all_columns = container.getElementsByTagName('input');
			for (var i=0; i<all_columns.length; i++) {
				if (dijit.byId(all_columns[i].id) != undefined) {
					last_pos = all_columns[i].id.lastIndexOf('_');
					id = all_columns[i].id.substring(last_pos+1)
					passContent['data['+id+'][id_'+id+']'] = id;
					passContent['data['+id+'][id]'] = id;
					passContent['data['+id+']['+all_columns[i].id.substring(0, last_pos)+']'] = dijit.byId(all_columns[i].id).getValue();
				}
			}
			return passContent;
		}

		function save_styles() {
			var passContent = {};
			var container = dojo.byId('style');
			// level goes from -2 to 2
			level = -2;
			rows = container.getElementsByTagName('div');
			for (var j=0; j<rows.length; j++) {
				if ((rows[j].className.indexOf('dojoDndSource')) == -1) {
					continue;
				}
				console.log(rows[j].className);
				all_columns = rows[j].getElementsByTagName('button');
				for (var i=0; i<all_columns.length; i++) {
					id = all_columns[i].id.substring(9);
					passContent['data['+id+'][id_'+id+']'] = id;
					passContent['data['+id+'][id]'] = id;
					if (dojo.byId('s_id_'+id)) {
						passContent['data['+id+']['+dijit.byId('label_'+id).name+']'] = dijit.byId('label_'+id).getValue();
						passContent['data['+id+']['+dijit.byId('display_label_'+id).name+']'] = dijit.byId('display_label_'+id).getValue();
						passContent['data['+id+']['+dijit.byId('display_duplicates_'+id).name+']'] = dijit.byId('display_duplicates_'+id).getValue();
						passContent['data['+id+']['+dijit.byId('subtotal_'+id).name+']'] = dijit.byId('subtotal_'+id).getValue();
						passContent['data['+id+']['+dijit.byId('style_'+id).name+']'] = dijit.byId('style_'+id).getValue();
						passContent['data['+id+']['+dijit.byId('indent_cells_'+id).name+']'] = dijit.byId('indent_cells_'+id).getValue();
						passContent['data['+id+'][level]'] = level;
					}
				}
				level++;
			}
			return passContent;
		}
		
		function save_groups() {
			var passContent = {};
			var container = dojo.byId('group');
			all_columns = container.getElementsByTagName('input');
			for (var i=0; i<all_columns.length; i++) {
				id = all_columns[i].id.substring(10);
				passContent['data['+id+'][id_'+id+']'] = id;
				passContent['data['+id+'][id]'] = id;
				if (dojo.byId('aggregate_'+id)) {
					passContent['data['+id+']['+dijit.byId('aggregate_'+id).name+']'] = dijit.byId('aggregate_'+id).getValue();
				}
			}
			return passContent;
		}

		function create_cell_column(id, name, data) {
			/* Check if it already exists */
			if (dijit.byId('sort_'+id)) {
				return false;
			}

			//Add the optional cell
			var container = dojo.doc.createElement('tr');

			var td = dojo.doc.createElement('td');
			container.appendChild(td)
			var button = dojo.doc.createElement('div');
			button.innerHTML = name;
			td.appendChild(button);

			sortStore = new dojo.data.ItemFileReadStore({id:'sortStore',url: '".$this->webroot()."listing/sort_dd_json'});

			var xid = create_input('id', 'hidden', {id:'c_id_'+id, value: id, label: false});
			var sort = create_input('sort', 'select', {label: false, id:'sort_'+id, value: data['sort'][0], store:sortStore, onChange: save_template});
			var opt = create_input('optional', 'checkbox', {label: false, id:'optional_'+id, value: data['optional'][0], onChange: save_template});
			var remove = create_input('remove', 'button', {label: 'Remove', id:'remove_'+id, onClick: remove_column});

			var td2 = dojo.doc.createElement('td');
			container.appendChild(td2)
			td2.appendChild(xid);
			td2.appendChild(sort);

			var td3 = dojo.doc.createElement('td');
			container.appendChild(td3)
			td3.appendChild(opt);

			var td4 = dojo.doc.createElement('td');
			container.appendChild(td4)
			td4.appendChild(remove);

			return container;
		}

		function create_cell_style(id, name, data) {

			//Add the optional cell
			var container = dojo.doc.createElement('div');
			var hidden = dojo.doc.createElement('div');
			hidden.style.display='none';
			container.appendChild(hidden);

			var button = dojo.doc.createElement('div');
			container.appendChild(button);
			var button = new dijit.form.DropDownButton({label: name, id:'s_column_'+id, className: 'column', connectId:['s_column_dialog_'+id]},button);
			
			// The dialog. WHat opens when you click. This needs to be created first (before the button)
			var dialog = dojo.doc.createElement('div');
			hidden.appendChild(dialog);
			var dialog2 = new dijit.TooltipDialog({id:'s_column_dialog_'+id},dialog);
			button.dropDown = dialog2;

			styleStore = new dojo.data.ItemFileReadStore({id:'aggStore',url: '".$this->webroot()."listing/style_dd_json'});

			var xid = create_input('id', 'hidden', {id:'s_id_'+id, value: id, label: false});
			var label = create_input('label', 'text', {label: 'Label: ', id:'label_'+id, value: data['label'][0], onChange: save_template});
			var dl = create_input('display_label', 'checkbox', {label: 'Display Label: ', id:'display_label_'+id, value: data['display_label'][0], onChange: save_template});
			var dd = create_input('display_duplicates', 'checkbox', {label: 'Display Duplicates: ', id:'display_duplicates_'+id, value: data['duplicates'][0], onChange: save_template});
			var st = create_input('indent_cells', 'number', {label: 'Indent Cells: ', id:'indent_cells_'+id, value: data['indent_cells'][0], onChange: save_template});
			var ic = create_input('subtotal', 'checkbox', {label: 'Subtotal: ', id:'subtotal_'+id, value: data['subtotal'][0], onChange: save_template});
			var style = create_input('style', 'select', {label: 'Style: ', id:'style_'+id, store:styleStore, value: data['style'][0], onChange: save_template});

			var dialog = dojo.doc.createElement('div');
			dialog.appendChild(xid);
			dialog.appendChild(label);
			dialog.appendChild(dl);
			dialog.appendChild(dd);
			dialog.appendChild(st);
			dialog.appendChild(ic);
			dialog.appendChild(style);
			dialog2.setContent(dialog);
			
			return container;
		}

		function create_cells() {
			template.fetch({onComplete: proc_cell});
		}
		
		var node_creator = function(data, hint){
			var types = [];
			var node = dojo.doc.createElement('td');
			types.push('stylebox');dojo.addClass(node, 'stylebox');
			if (hint != 'avatar') {
				node.appendChild(data);
			}
			node.id = dojo.dnd.getUniqueId();
			return {node: node, data: data, type: types};
		};
		
		function proc_cell(items, request) {
			cell = 1;
			current = dijit.byId('mainTabContainer').selectedChildWidget.containerNode;
			if (current.id == 'page_style' && dojo.byId('style')) {
				var tr1 = dojo.doc.createElement('div');
				var td = dojo.doc.createElement('span'); td.className='list-2'; td.innerHTML='Heading 1'; tr1.appendChild(td);
				dojo.byId('style').appendChild(tr1);
				c1 = new dojo.dnd.Source(tr1, {creator: node_creator,accept: ['stylebox']});
				
				var tr2 = dojo.doc.createElement('div');
				var td = dojo.doc.createElement('span'); td.className='list-1'; td.innerHTML='Heading 2'; tr2.appendChild(td);
				dojo.byId('style').appendChild(tr2);
				c2 = new dojo.dnd.Source(tr2, {creator: node_creator,accept: ['stylebox']});

				var tr3 = dojo.doc.createElement('div');
				var td = dojo.doc.createElement('span'); td.className='list0'; td.innerHTML='Normal'; tr3.appendChild(td);
				dojo.byId('style').appendChild(tr3);
				c3 = new dojo.dnd.Source(tr3, {creator: node_creator,accept: ['stylebox']});

				var tr4 = dojo.doc.createElement('div');
				var td = dojo.doc.createElement('span'); td.className='list1'; td.innerHTML='Sub 1'; tr4.appendChild(td);
				dojo.byId('style').appendChild(tr4);
				c4 = new dojo.dnd.Source(tr4, {creator: node_creator,accept: ['stylebox']});

				var tr5 = dojo.doc.createElement('div');
				var td = dojo.doc.createElement('span'); td.className='list2'; td.innerHTML='Sub 2'; tr5.appendChild(td);
				dojo.byId('style').appendChild(tr5);
				c5 = new dojo.dnd.Source(tr5, {creator: node_creator,accept: ['stylebox']});
			}
			dojo.forEach(items, function(i){
				if (current.id == 'page_style' && dojo.byId('style')) {
					input = create_cell_style(i['column_id'][0], i['chuman'][0], i);
					if (i['level'][0] == 0) {
						c3.insertNodes(false, [input]);
					}
					if (i['level'][0] == -1) {
						c2.insertNodes(false, [input]);
					}
					if (i['level'][0] == -2) {
						c1.insertNodes(false, [input]);
					}
					if (i['level'][0] == 1) {
						c4.insertNodes(false, [input]);
					}
					if (i['level'][0] == 2) {
						c5.insertNodes(false, [input]);
					}
				}
				cell = cell +1
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
		$blah['columns'] = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM list_templates ll WHERE ll.template_id='".$this->id."';"));
		return $blah;
	}
	
	/**
	 * Called by Listing::view_add. Gets all data required to show the add/edit column page
	 */
	function view_editcolumn() {
		$column_query = null;
		$column_id = $this->subid;

		if ($constraint_id == "new") {
		} else {
			$constraint_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM list_templates ll WHERE ll.template_id='".$this->id."' AND ll.list_template_id='".$column_id."' LIMIT 1;"));

			$blah['data']['column_id'] = $constraint_query['column_id'];
			$blah['data']['type'] = $constraint_query['type'];
			$blah['data']['value'] = $constraint_query['value'];

			$_REQUEST['data']['column_id'] = $constraint_query['column_id'];
			$table_join_ajax = $this->view_table_join_ajax($constraint_query['table_join_id']);
			$table_join_ajax = $table_join_ajax->data;
			unset($_REQUEST['data']['column_id']);
		}

		$this->current = $this->get_template($this->id);
		$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

		foreach ($tables['catalogue'] as $i => $column) {
			foreach ($column as $j => $cell) {
				$column_id = $cell['column_id'];

				$blah['options']['column_id'][$column_id] = $cell['table_name'].".".$cell['column_name'];

				switch ($cell['data_type']) {
					default:
						break;
					case "timestamp":
					case "timestamp with time zone":
					case "timestamp without time zone":
						$blah['column_types'][$column_id] = "date";
						break;
				}

				if ($cell['dropdown'] == "t") {
					$blah['column_options'][$column_id] = true;
				}
			}
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

		if ($this->subid == "new") {
			$_REQUEST['data']['column_id'] = reset(array_keys($blah['options']['column_id']));
			$table_join_ajax = $this->view_table_join_ajax($constraint_query['table_join_id']);
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
// 					$preview_table .= '<script>dojo.addOnLoad(function () { setTimeout("update_data_preview_first();", 7500); });</script>';
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
		$tabular_templates_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT tt.*, tta.tabular_templates_auto_id, ttt.tabular_templates_trend_id, tts.tabular_templates_single_id, ttm.tabular_templates_manual_id FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_single tts ON (tts.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_manual ttm ON (ttm.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND ((tt.axis_type = 'auto' AND tta.tabular_templates_auto_id IS NOT NULL) OR (tt.axis_type = 'trend' AND ttt.tabular_templates_trend_id IS NOT NULL) OR (tt.axis_type = 'single' AND tts.tabular_templates_single_id IS NOT NULL) OR (tt.axis_type = 'manual' AND ttm.tabular_templates_manual_id IS NOT NULL));"));

		if (!empty($tabular_templates_query)) {
			foreach ($tabular_templates_query as $tabular_template_tmp) {
				$tabular_templates[$tabular_template_tmp['type']] = $tabular_template_tmp;
			}
		}

		//put all step data in a usable array
		//TODO: listing_templates
		if (empty($listing_templates['columns'])) {
			$steps[0][0] = "Add Columns";
			$steps[0][2] = true;
			$steps[0][3] = "disabled";
		} else {
			$steps[0][0] = "Edit Columns";
			$steps[0][2] = false;
		}
		$steps[0][1] = $this->webroot()."listing/add/".$this->id."/columns/source";
		if ($this->subvar == "columns") $steps[0][3] .= " current";

		$steps[1][0] = "Preview";
		$steps[1][1] = $this->webroot()."listing/add/".$this->id."/preview";
		$steps[1][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
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
		$output = Listing_View::view_add($template, $blah, $steps, $preview_table, $tabular_template_auto, $table_join_ajax, $tabular_template);

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
			case "columns":
				$update_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM list_templates lt WHERE lt.template_id='".$this->id."';"));
				$update_query = $this->sortByColumn($update_query);
				print_r($_REQUEST);
				die();
				/*
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
				*/
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
	
	function view_saveX() {
// 		/* Submitted information */
// 		$order = 1;
// 		foreach ($_REQUEST['data'] as $i => $post) {
// 			if (strpos($i, "con") === 0) {
// 				$col = array();
// 				$col['column_id'] = $post['id'];
// 				$col['template_id'] = $this->id;
// 				$col['value'] = $post['value'];
// 				$col['type'] = $post['type'];
// 				$op = ($post['choose'] == "on") ? 't' : 'f';
// 				$col['choose'] = $op;
// 				/* Does it already exist. Column/Template must be unique */
// 				$query = "SELECT * FROM list_constraints WHERE template_id=".$this->id." AND column_id=".$post['id'].";";
// 				$cur = $this->dobj->db_fetch($this->dobj->db_query($query));
// 				if ($cur) {
// 					$this->dobj->db_query($this->dobj->update($col, "list_constraints_id", $cur['list_constraints_id'], "list_constraints"));
// 				} else {
// 					$this->dobj->db_query($this->dobj->insert($col, "list_constraints"));
// 				}
// 			} else {
// 				$col = array();
// 				$col['column_id'] = $post['id'];
// 				/* This value is invalid. It must have an id */
// 				if (!$post['id']) {
// 					continue;
// 				}
// 				$col['template_id'] = $this->id;
// 				if ($post['display_duplicates']) {
// 					$dd = ($post['display_duplicates'] == "on" || $post['display_duplicates'] == "f" || $post['display_duplicates'] == "t" ) ? 't' : 'f';
// 					$col['duplicates'] = $dd;
// 				}
// 				if ($post['subtotal']) {
// 					$st = ($post['subtotal'] == "on" || $post['subtotal'] == "f" || $post['subtotal'] == "t") ? 't' : 'f';
// 					$col['subtotal'] = $st;
// 				}
// 				if ($post['display_label']) {
// 					$dl = ($post['display_label'] == "on" || $post['display_label'] == "f" || $post['display_label'] == "t") ? 't' : 'f';
// 					$col['display_label'] = $dl;
// 				}
// 				if ($post['style']) {
// 					$col['style'] = $post['style'];
// 				}
// 				if ($post['indent_cells']) {
// 					$col['indent_cells'] = $post['indent_cells'];
// 				}
// 				if ($post['optional']) {
// 					$op = ($post['optional'] == "on") ? 't' : 'f';
// 					$col['optional'] = $op;
// 				}
// 				if ($post['aggregate']) {
// 					$col['aggregate'] = $post['aggregate'];
// 				}
// 				if ($post['label']) {
// 					$col['label'] = $post['label'];
// 				}
// 				if ($post['level']) {
// 					$col['level'] = $post['level'];
// 				}
// 				if ($post['sort']) {
// 					$col['sort'] = $post['sort'];
// 					$col['col_order'] = $order;
// 					$order++;
// 				}
// 				/* Does it already exist. Column/Template must be unique */
// 				$query = "SELECT * FROM list_templates WHERE template_id=".$this->id." AND column_id=".$post['id'].";";
// 				$cur = $this->dobj->db_fetch($this->dobj->db_query($query));
// 				if ($cur) {
// 					$this->dobj->db_query($this->dobj->update($col, "list_template_id", $cur['list_template_id'], "list_templates"));
// 				} else {
// 					$this->dobj->db_query($this->dobj->insert($col, "list_templates"));
// 				}
// 			}
// 		}
// 		$output = Listing_View::view_save($data, $template);
// 		return $output;
	}
	
// 	function hook_run($demo=false) {
// 		$template = $this->get_columns($this->id);
// 		$constraints = $this->get_constraints($this->id);
// 		/* Concatenate the predefined constraints and the user defined constraints */
// 		if ($_REQUEST['data']['constraint']) {
// 			foreach ($_REQUEST['data']['constraint'] as $i => $cons) {
// 				foreach ($constraints as $j => $cons2) {
// 					if ($cons2['list_constraints_id'] == $i) {
// 						$constraints[$j]['value'] = $cons;
// 						break;
// 					}
// 				}
// 			}
// 		}
// 		/* Generate the query to run */
// 		$query = $this->hook_query($template, $constraints, $demo);
// 		$start = time();
// 		/* Run the query and get the results */
// 		$data = parent::hook_run_query($template[0]['object_id'], $query);
// 		$end = time();
// 		if (!$demo) {
// 			/* Only update the run statistics if this is a complete run, not a preview run */
// 			$update_query = "UPDATE templates SET last_run=now(), last_time='".($end-$start)."', last_by=1, last_size=".count($data)." WHERE template_id=".$this->id."";
// 			$update = $this->dobj->db_query($update_query);
// 			$now = $this->save_results($this->id, $data, 't', 'f', ($end-$start), 1);
// 		}
// 		$output = Listing_View::hook_run($data, $template, $demo, $now);
// /*		if ($demo) {
// 			$output->data .= "<br/><br/>".$query;
// 		}*/
// 		return $output;
// 	}
	
// 	function view_run() {
// 		$template = $this->get_columns($this->id);
// 		$constraints = $this->get_constraints($this->id);
// 		$output = true;
// 		/* We skip this step if the constraints values are already populated from the $_REQUEST array */
// 		if (empty($_REQUEST['data']['constraint'])) {
// 			/* Get the constraints */
// 			$output = Listing_View::view_run($template, $constraints);
// 		}
// 		/* If there a form to fill out? If not (either because there are no user modifiable constriants, or the form has
// 		 * already been filled out) go directly to running the report.  */
// 		if ($output === true) {
// 			$output = $this->hook_run();
// 		}
// 		return $output;
// 	}
	
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
				$output->title = "Edit Constraint";
				$output->title_desc = "";

				$output->data .= Tabular_view::view_editconstraint($blah);

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
				//TODO Update this
				$constraint_id = $column['constraint_id'];

				$output .= "<tr>";
				$output .= "<td>";

				switch ($column['foobar']) {
					case "constraint":
						$output .= "<span class='".$$column['foobar']."'>";
						$output .= $$column['constraint'];
						$output .= "</span>";
						break;
				}

				$output .= "</td>";
				$output .= "<td>";
				$output .= "<ul>";

				switch ($column['foobar']) {
					case "constraint":
						if ($blah['default']) {
							if ($this->subvar == "constraints") {
								$output .= "<li><a href='".$this->webroot()."tabular/add/".$this->id."/editconstraint/".$constraint_id."'>Edit</a></li>";
								$output .= "<li><a href='".$this->webroot()."tabular/save/".$this->id."/removeconstraintsubmit/".$constraint_id."' onclick='if (confirm(\"Remove constraint?\")) {return true;} else {return false;}'>Remove</a></li>";
							} else if ($this->subid == "squidconstraints") {
								$output .= "<li><a href='".$this->webroot()."tabular/add/{$this->id}/editsquidconstraint/{$this->aux1}/{$constraint_id}'>Edit</a></li>";
// 								$output .= "<li><a href='".$this->webroot()."tabular/save/{$this->id}/removeconstraintsubmit/{$this->aux1}/{$constraint_id}' onclick='if (confirm(\"Remove constraint?\")) {return true;} else {return false;}'>Remove</a></li>";
							}
						} else {
							$output .= "<li>&nbsp;</li>";
						}
						break;
				}

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
}


?>
