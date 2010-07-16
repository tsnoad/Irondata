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
 * Mysql.php
 *
 * The MySQL module. 
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 * 
 */

class Mysql extends Catalogue {
	var $conn;
	var $dobj;
	var $name = "MySQL";
	var $description = "A MySQL data source type. OBSOLETE";
	var $module_group = "Data Sources";
	
	function hook_top_menu() {
		return null;
	}

	function hook_catalogue_entry() {
		return array(
			"label"=>"MySQL Database",
			"type"=>"database",
			"name"=>"mysql",
			"module"=>"mysql"
		);
	}

	function hook_workspace() {
		return null;
	}
	
	function hook_connect($hostname, $database, $username, $password) {
		$this->conn = mysql_connect($hostname, $username, $password);
		mysql_select_db($database, $this->conn);
		return true;
	}
	
	function hook_query($query) {
		/* echo $query;*/
		$res = mysql_query($query, $this->conn);
		$vals = array();
		if ($res) {
			while ($row = mysql_fetch_assoc($res)) {
				$vals[] = $row;
			}
		}
		return $vals;
	}
	
	function view_add() {
		if ($_REQUEST['data']) {
			$obj = array();
			$obj['name'] = $_REQUEST['data']['name'];
			$obj['type'] = "mysql";
			$object_id = $this->add_object($obj);
			
			$_REQUEST['data']['object_id'] = $object_id;
			$database_id = $this->add_database($_REQUEST['data']);

			/* Connect to the database and get the schema */
			$conn = $this->hook_connect($_REQUEST['data']['host'], $_REQUEST['data']['name'], $_REQUEST['data']['username'], $_REQUEST['data']['password']);
			$tables = $this->hook_query("select * from information_schema.tables where table_schema='".$_REQUEST['data']['name']."' order by table_name");
			/* We save the id's to make the references lookup faster. i.e. we don't need to 
			 * query the database each time */
			$id_array = array();
			$total = 0;
			foreach ($tables as $i => $table) {
				$id_array[$table['TABLE_NAME']] = array();
				$tableval = array();
				$tableval['database_id'] = $database_id;
				$tableval['name'] = $table['TABLE_NAME'];
				$tableval['human_name'] = $table['TABLE_NAME'];
				$size = $this->hook_query("select count(*) as count from ".$table['TABLE_NAME'].";");
				$tableval['records'] = $size[0]['count'];
				$total += $size[0]['count'];
				$table_id = $this->add_table($tableval);

				$column_schema = $this->hook_query("select * from information_schema.columns where table_name='".$table['TABLE_NAME']."' order by column_name");
				foreach ($column_schema as $j => $column) {
					$colval = array();
					#Get the examples of the column data
					if ($column['DATA_TYPE'] == "bytea") {
						$examples = null;
					} else {
						$examples = $this->hook_query("SELECT DISTINCT ".$column['COLUMN_NAME']." from ".$table['TABLE_NAME']." limit 10;");
					}
					$colval['example'] = '';
					$colval['key_type'] = '';
					if (is_array($examples)) {
						foreach ($examples as $k => $example) {
							$colval['example'][] = $example[$column['COLUMN_NAME']];
						}
						$colval['example'] = implode(",", $colval['example']);
					}
					$keys = $this->hook_query("select distinct k.constraint_name from information_schema.key_column_usage k where k.table_name='".$table['TABLE_NAME']."' and k.column_name='".$column['COLUMN_NAME']."' AND k.constraint_name = 'PRIMARY'; ");
					if (is_array($keys)) {
						$colval['key_type'] = "PK";
					}
					$colval['table_id'] = $table_id;
					$colval['name'] = $column['COLUMN_NAME'];
					$colval['human_name'] = $column['COLUMN_NAME'];
					$colval['data_type'] = $column['DATA_TYPE'];
					$column_id = $this->add_column($colval);
					$id_array[$table['TABLE_NAME']][$column['COLUMN_NAME']] = $column_id;
				}
			}
			$obj = array();
			$obj['records'] = $total;
			$database_id = $this->save_database($obj, $database_id);
			
		} else {
			$output = parent::view_add("mysql");
		}
		return $output;
	}
	
	function hook_query_source($object_id, $query) {
		$db = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM databases WHERE object_id='".$object_id."'; "));
		$conn = $this->hook_connect($db['host'], $db['name'], $db['username'], $db['password']);
		$tables = $this->hook_query($query);
		return $tables;
	}

}

?>
