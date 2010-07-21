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
 * Pgsql.php
 *
 * The PostgreSQL module.
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */
require_once("modules/listing/listing.php");

class Etl extends Listing {
	var $conn;
	var $dobj;
	var $name = "ETL";
	var $description = "Provide Extration, Transformation and Load (ETL) functionality.";
	var $module_group = "Other";
	
	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_permission_check()
	 */
	function hook_permission_check($data) {
		//TODO: This module is incomplete
		return false;
	}
	
	/* The Template hook function.
	 * Is this module available within the Templates
	 */
	function hook_template_entry() {
		//TODO: This module is broken
		return false;
	}
	
	/* The Top Menu hook function.
	 * Displays the module in the main menu. Or menu of primary functions.
	 */
	function hook_top_menu() {
// 		return array(
// 			"name"=>"ETL",
// 			"link"=>"display",
// 			"module"=>"etl"
// 		);
		return array(
			"etl" => array("<a href='".$this->webroot()."etl/display'>ETL</a>", 2)
			);
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
			case "display":
			$menu['Templates'][] = array(
				"name"=>"New ETL",
				"link"=>"add",
				"module"=>"etl"
			);
			break;
			default:
				$menu = parent::hook_menu($url);
			break;
		}
		return $menu;
	}
	
	/* The Javascript hook function.
	 * Send the following javascript to the browser.
	 */
	function hook_javascript() {
		$js = "";
		$js .= parent::hook_javascript();
		$js .= "

		function update_table(id) {
			console.log(id);
			if (dijit.byId('load_table')) {
				dijit.byId('load_table').destroy();
				dojo.byId('load_dd').innerHTML = '';
			}
			target_table_options = new dojo.data.ItemFileReadStore({id:'target_table_options',url: '".$this->webroot()."catalogue/tables_json/'+id});
			var target = create_input('table', 'select', {label: 'Table', id:'load_table', store:target_table_options});
			dojo.byId('load_dd').appendChild(target);
		}
		
		function update_column(id) {
			// FIX CATALOGUE JSON CALL (OVERLOADED FUNCTION)
			targetStore = new dojo.data.ItemFileReadStore({id:'targetStore',url: '".$this->webroot()."catalogue/columns_json/'+id});
			create_cells();
		}

		function save_template() {
			current = dijit.byId('mainTabContainer').selectedChildWidget.containerNode;
			if (current.id == 'page_columns') {
				passContent = save_columns();
			}
			if (current.id == 'page_load') {
				passContent = save_loads();
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

		function save_loads() {
			var passContent = {};
			var container = dojo.byId('loads');
			all_columns = container.getElementsByTagName('button');
			for (var i=0; i<all_columns.length; i++) {
				id = all_columns[i].id.substring(9);
				passContent['data['+id+'][id_'+id+']'] = id;
				passContent['data['+id+'][id]'] = id;
				if (dojo.byId('c_id_'+id)) {
					passContent['data['+id+']['+dijit.byId('target_'+id).name+']'] = dijit.byId('target_'+id).getValue();
				}
			}
			return passContent;
		}

		function create_cell_load(id, name, data) {

			//Add the optional cell
			var container = dojo.doc.createElement('div');
			var hidden = dojo.doc.createElement('div');
			hidden.style.display='none';
			container.appendChild(hidden);

			var button = dojo.doc.createElement('div');
			container.appendChild(button);
			var button = new dijit.form.DropDownButton({label: name, id:'t_column_'+id, className: 'column', connectId:['t_column_dialog_'+id]},button);
			
			// The dialog. WHat opens when you click. This needs to be created first (before the button)
			var dialog = dojo.doc.createElement('div');
			hidden.appendChild(dialog);
			var dialog2 = new dijit.TooltipDialog({id:'t_column_dialog_'+id},dialog);
			button.dropDown = dialog2;

			var xid = create_input('id', 'hidden', {id:'t_id_'+id, value: id, label: false});
			var target = create_input('target', 'select', {label: 'Target', id:'target_'+id, value: data['target'][0], store:targetStore, onChange: save_template});

			var dialog = dojo.doc.createElement('div');
			dialog.appendChild(xid);
			dialog.appendChild(target);
			dialog2.setContent(dialog);
			
			return container;
		}
		
		function proc_cell(items, request) {
			cell = 1;
			current = dijit.byId('mainTabContainer').selectedChildWidget.containerNode;
			dojo.forEach(items, function(i){
				if (current.id == 'page_columns' && dojo.byId('columns')) {
					input = create_cell_column(i['column_id'][0], i['chuman'][0], i);
					dojo.byId('columns').appendChild(input);
				}
				if (current.id == 'page_load' && dojo.byId('load')) {
					input = create_cell_load(i['column_id'][0], i['chuman'][0], i);
					dojo.byId('load').appendChild(input);
				}
				if (current.id == 'page_group' && dojo.byId('group')) {
					input = create_cell_group(i['column_id'][0], i['chuman'][0], i);
					dojo.byId('group').appendChild(input);
				}
				cell = cell +1
			});
		}
		";
		return $js;
	}
	
	function view_add() {
		if (!$this->id) {
			/* This will create the template record and redirect the page */
			$output = parent::view_add('etl', 'etl');
		} else {
			$output = Etl_View::view_add();
		}
		return $output;
	}
	
	function view_save() {
		/* Submitted information */
		$order = 1;
		$query = "DELETE FROM etl_constraints WHERE template_id=".$this->id.";";
		$cur = $this->dobj->db_query($query);
		foreach ($_REQUEST['data'] as $i => $post) {
			if (strpos($i, "con") === 0) {
				$col = array();
				$col['column_id'] = $post['id'];
				$col['template_id'] = $this->id;
				$col['value'] = $post['value'];
				$col['type'] = $post['type'];
				$op = ($post['choose'] == "on") ? 't' : 'f';
				$col['choose'] = $op;
				$this->dobj->db_query($this->dobj->insert($col, "etl_constraints"));
			} else {
				$col = array();
				$col['column_id'] = $post['id'];
				$col['template_id'] = $this->id;
				if ($post['optional']) {
					$op = ($post['optional'] == "on") ? 't' : 'f';
					$col['optional'] = $op;
				}
				if ($post['aggregate']) {
					$col['aggregate'] = $post['aggregate'];
				}
				if ($post['sort']) {
					$col['sort'] = $post['sort'];
					$col['col_order'] = $order;
					$order++;
				}
				/* Does it already exist. Column/Template must be unique */
				$query = "SELECT * FROM etl_templates WHERE template_id=".$this->id." AND column_id=".$post['id'].";";
				$cur = $this->dobj->db_fetch($this->dobj->db_query($query));
				if ($cur) {
					$this->dobj->db_query($this->dobj->update($col, "etl_template_id", $cur['etl_template_id'], "etl_templates"));
				} else {
					$this->dobj->db_query($this->dobj->insert($col, "etl_templates"));
				}
			}
		}
		#$template = $this->get_columns($this->id);
		#$constraints = $this->get_constraints($this->id);
		#$query = $this->hook_query($template, $constraints, true);
		#$data = parent::hook_run_query($template[0]['object_id'], $query);
		#$output = Etl_View::view_save($data, $template);
		return $output;
	}
	
	function get_columns($template_id) {
		$query = "SELECT j.*, l.*, t.*, c.column_id, tb.table_id, c.human_name as chuman, tb.human_name as thuman, c.name as column, tb.name as table FROM templates t, columns c, tables tb, etl_templates l LEFT OUTER JOIN etl_load j ON (j.etl_template_id=l.etl_template_id) WHERE tb.table_id=c.table_id AND c.column_id=l.column_id AND t.template_id=l.template_id AND t.template_id=".$template_id." ORDER BY l.col_order;";
		$data = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		return $data;
	}
	
	function get_constraints($template_id) {
		$query = "SELECT l.*, t.*, c.column_id, tb.table_id, c.human_name as chuman, tb.human_name as thuman, c.name as column, tb.name as table FROM etl_constraints l, templates t, columns c, tables tb WHERE tb.table_id=c.table_id AND c.column_id=l.column_id AND t.template_id=l.template_id AND t.template_id=".$template_id.";";
		$data = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		return $data;
	}
	
	function view_add_load() {
		$output = Etl_View::view_add_load();
		return $output;
	}
	
	
	function view_tables_json() {
		$template = $this->get_columns($this->id);
		$output = Etl_View::view_tables_json($template);
		return $output;
	}
}

class Etl_View {
	
	function view_add() {
		$output->title = "ETL Template";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."etl/tables_json/".$this->id."' jsId='template'></div>";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."etl/constraint_options_json' jsId='constraint_options'></div>";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."catalogue/objects_json' jsId='target_db_options'></div>";
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."catalogue/tables_json' jsId='target_table_options'></div>";
		$output->data .= "<button id='previous' onClick='dijit.byId(\"mainTabContainer\").back();'><</button>
			<button onClick='dijit.byId(\"mainTabContainer\").selectChild(dijit.byId(\"page_columns\"));'>Columns</button>
			<button onClick='dijit.byId(\"mainTabContainer\").selectChild(dijit.byId(\"page_group\"));'>Group</button>
			<button onClick='dijit.byId(\"mainTabContainer\").selectChild(dijit.byId(\"page_constraints\"));'>Constraints</button>
			<button onClick='dijit.byId(\"mainTabContainer\").selectChild(dijit.byId(\"page_demo\"));'>Demo</button>
			<button onClick='dijit.byId(\"mainTabContainer\").selectChild(dijit.byId(\"page_load\"));'>Load</button>
			<button id='next' onClick='dijit.byId(\"mainTabContainer\").forward();'>></button>";
		$output->data .= "<div id='mainTabContainer' dojoType='dijit.layout.StackContainer' style='width: 100%; height: 100%;'>";
		$output->data .= "<div executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."etl/add_columns/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_columns' title='Columns' label='Columns'></div>";
		$output->data .= "<div executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."etl/add_group/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_group' title='Group' label='Group'></div>";
		$output->data .= "<div executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."etl/display_constraints/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_constraints' title='Constraints' label='Constraints'></div>";
		$output->data .= "<div executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."etl/demo/".$this->id."' dojoType='dijit.layout.ContentPane' style='width: 100%; height: 100%;' id='page_demo' title='Demo' label='Demo'></div>";
		$output->data .= "<div executeScripts='true' parseContent='true' refreshOnShow='false' scriptSeparation='false' extractContent='false' href='".$this->webroot()."etl/add_load/".$this->id."' dojoType='dojox.layout.ContentPane' style='width: 100%; height: 100%;' id='page_load' title='Load' label='Load'></div>";
		$output->data .= "</div>";
		return $output;
	}
	
	function view_add_load() {
		$output->layout = 'ajax';
		$output->title = "Load";
		$output->data .= "<p>Select the table and columns which which to load the information.</p>";
		$output->data .= $this->i("load_object", array("label"=>"Data Source", "dojoType"=>"dijit.form.FilteringSelect", "store"=>"target_db_options", "onchange"=>"update_table(arguments[0])"));
		$output->data .= "<div id='load_dd'></div>";
		$output->data .= "<ul id='load' class='style' style='border: 1px solid black;'>
		</ul>\n";
		return $output;
	}
	
	function view_save($data, $template) {
		$output = $this->hook_output($data, $template, true);
		$output->layout = "ajax";
		return $output;
	}
	
	function view_tables_json($tables) {
		$output->layout = 'ajax';
		$output->data = "{
	identifier:'list_template_id',
	items: [";
	$opt = array();
	foreach ($tables as $i => $option) {
		$opt[] = "{list_template_id: '".$option['list_template_id']."', column_id: '".$option['column_id']."', sort: '".$option['sort']."', aggregate: '".$option['aggregate']."', target: '".$option['column_id']."', optional: '".$option['optional']."', col_order: '".$option['col_order']."', chuman: '".$option['chuman']."', column: '".$option['column']."'}";
	}
	$output->data .= implode(",", $opt);
	$output->data .= "]
}
		";
		return $output;
	}
}


?>
