<?php

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
	
	/* The Top Menu hook function. 
	 * Displays the module in the main menu. Or menu of primary functions. 
	 */
	function hook_top_menu() {
		return null;
	}

	function hook_admin_tools() {
		return null;
	}
	
	function hook_style() {
		return "td.columns div{ display: none;}
		#style div.dojoDndSource {margin: 5px; background: #ccc;}
		span.list-2 {font-size: 1.2em; font-weight: bold;}
		span.list-1 {font-size: 1.1em; font-weight: bold;}
		span.list1 {font-size: 0.9em;}
		span.list2 {font-size: 0.8em;}
		#style td {display: block;}";
	}

	function hook_workspace() {
		return null;
	}
	
	/* The Menu hook function. 
	 * Displays items in the side bar. This can be dependant on the actual URL used. 
	 */
	function hook_menu() {
		$menu = array();
		switch ($this->action) {
			default:
				$menu = parent::hook_menu($url);
		}
		return $menu;
	}

	/* The Template hook function. 
	 * Is this module available within the Templates
	 */
	function hook_template_entry() {
		return array(
			"label"=>"List Report",
			"module"=>"list"
		);
	}
	
	/* The Javascript hook function. 
	 * Send the following javascript to the browser.
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

	function hook_query($template, $constraints, $demo=false) {
		/* TODO: Change $$ to module specific */
		$cols = array();
		$sort = array();
		$tables = array();
		$join_tables = array();
		$optional_tables = array();
		$group = array();
		$where = array();

		/* SELECT Clause */
		foreach ($template as $i => $post) {
			$col = "";
			/* This is added to a. ensure all columns are unique and b. aggregates sort properly */
			if (!$post['label']) {
				$post['label'] = $post['table'].".".$post['column'];
			}
			if ($post['aggregate'] && $post['aggregate'] != "none") {
				$use_group = true;
				if ($post['aggregate'] == "countdistinct") {
					$post['aggregate'] = "count";
					$distinct = "DISTINCT";
				}
				$col = $post['aggregate']."(".$distinct." ".$post['table'].".".$post['column'].")";
			} else {
				$col = $post['table'].".".$post['column']."";
				$group[] = $post['table'].".".$post['column'];
				$sort[] = $post['table'].".".$post['column']." ".$post['sort'];
			}
			$cols[$post['label']] = $col;
			$tables[$post['table']] = $post['table_id'];
			$join_tables[$post['table']] = $post['table_id'];
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
		$query = $this->hook_build_query($cols, $join_tables, $where, $group, $sort, $limit);
		var_dump($query);
		return $query;
	}
	
	function hook_output($results, $template=false, $demo=false, $now=false) {
		if (!$template) {
			$template = $this->get_columns($this->id);
		}
		$output = Listing_View::hook_output($results, $template, $demo, $now);
		return $output;
	}
	
	function view_add() {
		if (!$this->id) {
			/* This will create the template record and redirect the page */
			$output = parent::view_add('listing');
		} else {
			$template = $this->get_template($this->id);
			$output = Listing_View::view_add($template);
		}
		return $output;
	}
	
	function view_display_table() {
		/* Get all the tables and create the wrapped columns */
		$template = $this->get_columns($this->id);
		$object = $this->dobj->db_fetch($this->dobj->db_query("SELECT object_id FROM templates WHERE template_id='".$this->id."'"));
		$tables = $this->call_function("catalogue", "get_structure", array($object['object_id'], $constraints));
		$output = Listing_View::view_display_table($tables, $template);
		return $output;
	}
	
	function view_remove() {
		$query = "DELETE FROM list_templates WHERE template_id=".$this->id." AND column_id='".$this->subvar."';";
		$cur = $this->dobj->db_query($query);
		die();
	}
	
	function view_remove_constraint() {
		$query = "DELETE FROM list_constraints WHERE template_id=".$this->id." AND column_id='".$this->subvar."';";
		$cur = $this->dobj->db_query($query);
		die();
	}
	
	function view_save() {
		/* Submitted information */
		$order = 1;
		foreach ($_REQUEST['data'] as $i => $post) {
			if (strpos($i, "con") === 0) {
				$col = array();
				$col['column_id'] = $post['id'];
				$col['template_id'] = $this->id;
				$col['value'] = $post['value'];
				$col['type'] = $post['type'];
				$op = ($post['choose'] == "on") ? 't' : 'f';
				$col['choose'] = $op;
				/* Does it already exist. Column/Template must be unique */
				$query = "SELECT * FROM list_constraints WHERE template_id=".$this->id." AND column_id=".$post['id'].";";
				$cur = $this->dobj->db_fetch($this->dobj->db_query($query));
				if ($cur) {
					$this->dobj->db_query($this->dobj->update($col, "list_constraints_id", $cur['list_constraints_id'], "list_constraints"));
				} else {
					$this->dobj->db_query($this->dobj->insert($col, "list_constraints"));
				}
			} else {
				$col = array();
				$col['column_id'] = $post['id'];
				/* This value is invalid. It must have an id */
				if (!$post['id']) {
					continue;
				}
				$col['template_id'] = $this->id;
				if ($post['display_duplicates']) {
					$dd = ($post['display_duplicates'] == "on" || $post['display_duplicates'] == "f" || $post['display_duplicates'] == "t" ) ? 't' : 'f';
					$col['duplicates'] = $dd;
				}
				if ($post['subtotal']) {
					$st = ($post['subtotal'] == "on" || $post['subtotal'] == "f" || $post['subtotal'] == "t") ? 't' : 'f';
					$col['subtotal'] = $st;
				}
				if ($post['display_label']) {
					$dl = ($post['display_label'] == "on" || $post['display_label'] == "f" || $post['display_label'] == "t") ? 't' : 'f';
					$col['display_label'] = $dl;
				}
				if ($post['style']) {
					$col['style'] = $post['style'];
				}
				if ($post['indent_cells']) {
					$col['indent_cells'] = $post['indent_cells'];
				}
				if ($post['optional']) {
					$op = ($post['optional'] == "on") ? 't' : 'f';
					$col['optional'] = $op;
				}
				if ($post['aggregate']) {
					$col['aggregate'] = $post['aggregate'];
				}
				if ($post['label']) {
					$col['label'] = $post['label'];
				}
				if ($post['level']) {
					$col['level'] = $post['level'];
				}
				if ($post['sort']) {
					$col['sort'] = $post['sort'];
					$col['col_order'] = $order;
					$order++;
				}
				/* Does it already exist. Column/Template must be unique */
				$query = "SELECT * FROM list_templates WHERE template_id=".$this->id." AND column_id=".$post['id'].";";
				$cur = $this->dobj->db_fetch($this->dobj->db_query($query));
				if ($cur) {
					$this->dobj->db_query($this->dobj->update($col, "list_template_id", $cur['list_template_id'], "list_templates"));
				} else {
					$this->dobj->db_query($this->dobj->insert($col, "list_templates"));
				}
			}
		}
		$output = Listing_View::view_save($data, $template);
		return $output;
	}
	
	function hook_run($demo=false) {
		$template = $this->get_columns($this->id);
		$constraints = $this->get_constraints($this->id);
		/* Concatenate the predefined constraints and the user defined constraints */
		if ($_REQUEST['data']['constraint']) {
			foreach ($_REQUEST['data']['constraint'] as $i => $cons) {
				foreach ($constraints as $j => $cons2) {
					if ($cons2['list_constraints_id'] == $i) {
						$constraints[$j]['value'] = $cons;
						break;
					}
				}
			}
		}
		/* Generate the query to run */
		$query = $this->hook_query($template, $constraints, $demo);
		$start = time();
		/* Run the query and get the results */
		$data = parent::hook_run_query($template[0]['object_id'], $query);
		$end = time();
		if (!$demo) {
			/* Only update the run statistics if this is a complete run, not a preview run */
			$update_query = "UPDATE templates SET last_run=now(), last_time='".($end-$start)."', last_by=1, last_size=".count($data)." WHERE template_id=".$this->id."";
			$update = $this->dobj->db_query($update_query);
			$now = $this->save_results($this->id, $data, 't', 'f', ($end-$start), 1);
		}
		$output = Listing_View::hook_run($data, $template, $demo, $now);
/*		if ($demo) {
			$output->data .= "<br/><br/>".$query;
		}*/
		return $output;
	}
	
	function view_run() {
		$template = $this->get_columns($this->id);
		$constraints = $this->get_constraints($this->id);
		$output = true;
		/* We skip this step if the constraints values are already populated from the $_REQUEST array */
		if (empty($_REQUEST['data']['constraint'])) {
			/* Get the constraints */
			$output = Listing_View::view_run($template, $constraints);
		}
		/* If there a form to fill out? If not (either because there are no user modifiable constriants, or the form has
		 * already been filled out) go directly to running the report.  */
		if ($output === true) {
			$output = $this->hook_run();
		}
		return $output;
	}
	
	function view_tables_json() {
		$template = $this->get_columns($this->id);
		$output = Listing_View::view_tables_json($template);
		return $output;
	}
	
	function view_style_dd_json() {
		$values = array("none"=>"Default", "heading1"=>"Heading 1", "heading2"=>"Heading 2", "small1"=>"Small 1", "small2"=>"Small2");
		$output = Listing_View::view_dd_json($values);
		return $output;
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
	
	function view_add_columns() {
		$columns = $this->get_columns($this->id);
		$output = Listing_View::view_add_columns($columns);
		return $output;
	}
	
	function view_add_style() {
		$output = Listing_View::view_add_style();
		return $output;
	}
	
	function view_add_group() {
		$columns = $this->get_columns($this->id);
		$output = Listing_View::view_add_group($columns);
		return $output;
	}
	
	function view_clone() {
		$template = "SELECT * FROM templates WHERE template_id='".$this->id."';";
		$template = $this->dobj->db_fetch($this->dobj->db_query($template));
		unset($template['template_id']);
		$template['name'] = $template['name'] ." (Clone)";
		$template['database'] = $template['object_id'];
		$template_id = $this->add_template($template);
		$list_templates = "SELECT * FROM list_templates WHERE template_id='".$this->id."';";
		$list_templates = $this->dobj->db_fetch_all($this->dobj->db_query($list_templates));
		foreach ($list_templates as $i => $temp) {
			unset($temp['list_template_id']);
			$temp['template_id'] = $template_id;
			$this->dobj->db_query($this->dobj->insert($temp, "list_templates"));
		}
		$list_constraints = "SELECT * FROM list_constraints WHERE template_id='".$this->id."';";
		$list_constraints = $this->dobj->db_fetch_all($this->dobj->db_query($list_constraints));
		foreach ($list_constraints as $i => $temp) {
			unset($temp['list_constraints_id']);
			$temp['template_id'] = $template_id;
			$this->dobj->db_query($this->dobj->insert($temp, "list_constraints"));
		}
		$this->redirect('listing/add/'.$template_id);
	}
	
}

class Listing_View extends Template_View {
	function view_add($template) {
		$output->title = "".$template['name']." - Listing Report";
		$output->data .= "<div dojoType='dojox.layout.ContentPane' layoutAlign='top'>";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."listing/tables_json/".$this->id."' jsId='template'></div>";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."listing/constraint_options_json' jsId='constraint_options'></div>";
		$output->data .= "<p class='description'>".$template['description']."</p>";
		$output->data .= "<button class='small_nav' dojoType='dijit.form.Button' id='previous' onClick='dijit.byId(\"mainTabContainer\").back();'>&larr;</button>";
		$output->data .= "<span dojoType='dijit.layout.StackController' containerId='mainTabContainer'></span>";
		$output->data .= "<button class='small_nav' dojoType='dijit.form.Button' id='next' onClick='dijit.byId(\"mainTabContainer\").forward();'>&rarr;</button>";
		$output->data .= "</div>";
		$output->data .= "<div dojoType='dojox.layout.ContentPane' layoutAlign='bottom'>";
		$output->data .= "<button dojoType='dijit.form.Button' onClick='window.location=\"".$this->webroot()."workspace/home\";'>Save & Close</button>";
		$output->data .= "<button dojoType='dijit.form.Button' onClick='window.location=\"".$this->webroot()."listing/clone/".$this->id."\";'>Clone Report</button>";
		$output->data .= "<button dojoType='dijit.form.Button' onClick='if (confirm(\"Are you sure you want to delete this report\")) { window.location=\"".$this->webroot()."listing/delete/".$this->id."\"} ;'>Delete Report</button>";
		$output->data .= "</div>";
		$output->data .= "<div layoutAlign='client' id='mainTabContainer' dojoType='dijit.layout.StackContainer' style='width: 100%; height: 100%;'>";
		$output->data .= "<div class='wizarddiv' executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."listing/add_columns/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_columns' title='Columns' label='Columns'></div>";
		$output->data .= "<div class='wizarddiv' executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."listing/add_style/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_style' title='Style' label='Style'></div>";
		$output->data .= "<div class='wizarddiv' executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."listing/add_group/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_group' title='Group' label='Group'></div>";
		$output->data .= "<div class='wizarddiv' executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."listing/display_constraints/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_constraints' title='Constraints' label='Constraints'></div>";
		$output->data .= "<div class='wizarddiv' executeScripts='true' parseContent='true' refreshOnShow='true' scriptSeparation='false' extractContent='false' href='".$this->webroot()."listing/demo/".$this->id."' dojoType='dijit.layout.ContentPane' style='width: 100%; height: 100%;' id='page_demo' title='Preview' label='Preview'></div>";
		$output->data .= "<div class='wizarddiv' executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."tabular/add_details/".$this->id."' dojoType='dijit.layout.ContentPane' style='width: 100%; height: 100%;' id='page_details' title='Details' label='Details'></div>";
		$output->data .= "</div>";
		//$output->data .= "<script>create_cells();</script>";
		return $output;
	}
	
	function view_add_columns($columns) {
		$output->layout = 'ajax';
		$output->title = "Select Report Columns";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."listing/sort_dd_json' jsId='sort_store'></div>";
		$output->data .= "<p class='description'>Select the fields that will make up the report by dragging the appropriate columns from the list of tables on the left to the space below. </p>";
		$output->data .= "<p class='description'>Once dropped you can click on the field to change the sort order or make that field optional in the output (i.e. if it does not exist in the database it will output an empty field)</p>";
		$output->data .= "<table id='columns' class='columns template'>";
		$output->data .= "<tr><th>Column</th>
		<th>Sort By</th>
		<th>Optional Value</th>
		<th></th>
		</tr>";
		$output->data .= "<tr style='height: 25px;'><td colspan='5' class='columns' dojoType='dojo.dnd.Target'>
		<p class='description drop'>Drop the columns here.</p></td></tr>";
		if (is_array($columns)) {
			foreach ($columns as $i => $column) {
				$opt = $column['optional'] == "t" ? true : false;
				$output->data .= "<tr id='row_".$column['column_id']."' style='height: 25px;'>
				<td class='column'>".$column['chuman']."</td>
				<td class='sort'>".$this->i("sort", array("id"=>"sort_".$column['column_id'], "type"=>"text", "dojoType"=>"dijit.form.FilteringSelect", "label"=>false, "store"=>"sort_store", "default"=>$column['sort'], "onChange"=>"save_template()"))."</td>
				<td class='optional'>".$this->i("optional", array("id"=>"optional_".$column['column_id'], "type"=>"checkbox", "dojoType"=>"dijit.form.CheckBox", "label"=>false, "default"=>$column['value'], "onChange"=>"save_template()"))."</td>
				<td class='remove'>".$this->i("remove", array("id"=>"remove_".$column['column_id'], "type"=>"button", "dojoType"=>"dijit.form.Button", "label"=>"Remove", "onClick"=>"remove_column(this)"))."</td>
				</tr>";
			}
		}
		$output->data .= "</table>\n";
// 		$output->data .= "<script type='javascript'>create_cells();</script>";
		return $output;
	}
	
	function view_add_style() {
		$output->layout = 'ajax';
		$output->title = "Style Output";
		$output->data .= "<p class='description'>You can click on the field to change the label, choose whether to display the label or not, hide duplicated values in following rows, display a subtotal when the value in a row changes, and finally select the size and style to display the text with.</p>";
		$output->data .= "<div id='style' class='style template'>
		</div>\n";
		$output->data .= "<script type='javascript'>create_cells();</script>";
		return $output;
	}
	
	function view_add_group($columns) {
		$output->layout = 'ajax';
		$output->title = "Group Results";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."listing/aggregate_dd_json' jsId='agg_store'></div>";
		$output->data .= "<p class='description'>Group the results of any column together. This will replace the field in the output with either the number of values, the sum of all the values, or the maximum/minimum of the values.</p>";
		$output->data .= "<table id='group' class='group template'>";
		$output->data .= "<tr><th>Column</th>
		<th>Aggregate</th>
		</tr>";
		if (is_array($columns)) {
			foreach ($columns as $i => $column) {
				$output->data .= "<tr><td>".$column['chuman']."</td>";
				$output->data .= "<td class='type'>".$this->i("aggregate", array("id"=>"aggregate_".$column['column_id'], "type"=>"text", "dojoType"=>"dijit.form.FilteringSelect", "label"=>false, "store"=>'agg_store', "default"=>$column['aggregate'], "onChange"=>"save_template()"))."</td></tr>";
			}
		}
		$output->data .= "</table>\n";
// 		$output->data .= "<script type='javascript'>create_cells();</script>";
		return $output;
	}
	
	function view_display_table($tables, $template) {
		$output->layout = 'ajax';
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."listing/tables_json/".$this->id."' jsId='template'></div>";
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
	
	function view_run($template, $constraints) {
		$skip = true;
		$output->data .= $this->f('listing/run/'.$this->id);
		if (is_array($constraints)) {
			/* Iterate through all the constraints in the report */
			foreach ($constraints as $i => $constraint) {
				/* If the constraint can be modifified by the user at run time */
				if ($constraint['choose'] == "t") {
					/* Ignore pre populated constraints - see $_REQUEST variable */
					if ($_REQUEST["data"][$constraint['list_constraints_id']]) {
						$constraints[$i]['value'] = $_REQUEST["data"][$constraint['list_constraints_id']];
					} else {
						$skip = false;
						$output->title = "Report Parameters";
						/* Automatically build the form with all the constraint options */
						$output->data .= $this->i("data[constraint][".$constraint['list_constraints_id']."]", array("dojoType"=>"dijit.form.TextBox", "type"=>"text", "label"=>$constraint['chuman']." ".$constraint['type'], "default"=>$constraint['value']));
					}
				}
			}
		}
		$output->data .= $this->submit("Next");
		$output->data .= $this->f_close();
		$output->data = "<div style='overflow:auto;' layoutAlign='client' dojoType='dojox.layout.ContentPane'>".$output->data."</div>";
		/* Only return the HTML if there is a form to fill out, otherwise return false */
		if ($skip == false) {
			return $output;
		} else {
			return $skip;
		}
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
	
	function view_tables_json($tables) {
		$output->layout = 'ajax';
		$output->data = "{
	identifier:'list_template_id',
	items: [";
	$opt = array();
	foreach ($tables as $i => $option) {
		$opt[] = "{list_template_id: '".$option['list_template_id']."', column_id: '".$option['column_id']."', level: '".$option['level']."', duplicates: '".$option['duplicates']."', style: '".$option['style']."', display_label: '".$option['display_label']."', indent_cells: '".$option['indent_cells']."', subtotal: '".$option['subtotal']."', sort: '".$option['sort']."', aggregate: '".$option['aggregate']."', label: '".$option['label']."', optional: '".$option['optional']."', col_order: '".$option['col_order']."', chuman: '".$option['chuman']."', column: '".$option['column']."'}";
	}
	$output->data .= implode(",", $opt);
	$output->data .= "]
}
		";
		return $output;
	}
}


?>
