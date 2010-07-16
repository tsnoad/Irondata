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

class Pgsql extends Catalogue {
	var $conn;
	var $dobj;
	var $name = "PostgreSQL";
	var $description = "Store and manipulate PostgreSQL data sources.";
	var $module_group = "Core";
	
	/* The Top Menu hook function. 
	 * Displays the module in the main menu. Or menu of primary functions. 
	 */
	function hook_top_menu() {
		return null;
	}

	/* The Catalogue hook function. 
	 * Is this module available within the Catalogue.
	 */
	function hook_catalogue_entry() {
		return array(
			"label"=>"PostgreSQL Database",
			"type"=>"database",
			"name"=>"postgres",
			"module"=>"pgsql"
		);
	}

	function hook_workspace() {
		return null;
	}
	
	/* The Connect hook function. 
	 * How to connect to this modules database. Returns a PHP connection object if appropriate
	 */
	function hook_connect($hostname, $database, $username, $password) {
		$conn_string = "host=$hostname dbname=$database";

		if (!empty($username)) {
			$conn_string .= " user=$username";
		}

		if (!empty($password)) {
			$conn_string .= " password='$password'";
		}

		$this->conn = pg_connect($conn_string);
		return true;
	}
	
	/* The Query hook function. 
	 * How to query this modules database. Returns an array of all results. 
	 */
	function hook_query($query) {
		//echo $query;
		$res = pg_query($this->conn, $query);
		if ($res) {
			$vals = pg_fetch_all($res);
		} else {
			$vals = null;
		}
		return $vals;
	}
	
	/* The Query Source hook function. 
	 * This allows the templates modules to connect to the source database to run a query.
	 */
	function hook_query_source($object_id, $query) {
		$db = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM databases WHERE object_id='".$object_id."'; "));
		$conn = $this->hook_connect($db['host'], $db['name'], $db['username'], $db['password']);
		$tables = $this->hook_query($query);
		return $tables;
	}
	
	/* URL: /pgsql/update_record_count/TABLE_ID */
	function view_update_record_count() {
		$table = $this->get_table($this->id);
		$database = $this->get_database($table['database_id']);
		$conn = $this->hook_connect($database['host'], $database['name'], $database['username'], $database['password']);
		$size = $this->hook_query("select count(*) from ".$table['name'].";");
		$database['records'] = $database['records'] - $table['records'] + $size[0]['count'];
		$table['records'] = $size[0]['count'];
		$table_id = $this->save_table($table, $this->id);
		$database_id = $this->save_database($database, $table['database_id']);
		#$output->data = $table['records'];
		return true;
	}
	
	/* URL: /pgsql/update_example/TABLE_ID */
	function view_update_example($unlimited = false) {
		$table = $this->get_table($this->id);
		$database = $this->get_database($table['database_id']);
		$columns = $this->get_columns($this->id);
		$conn = $this->hook_connect($database['host'], $database['name'], $database['username'], $database['password']);

		#Get the examples of the column data
		foreach ($columns as $i => $column) {
			if ($column['data_type'] == "bytea") {
				$examples = null;
			} else {
				if ($unlimited) {
					$limit = "250";
				} else {
					$limit = "10";
				}
				$examples = $this->hook_query("SELECT DISTINCT ".$column['name']." from ".$table['name']." limit ".$limit);
			}
			$column['example'] = '';
			if (is_array($examples)) {
				foreach ($examples as $k => $example) {
					$column['example'][] = $example[$column['name']];
				}
				$column['example'] = implode(",", $column['example']);
			}
			$column_id = $this->save_column($column, $column['column_id']);
		}
		return true;
	}

	/* Regenerate Schema
	 * Goes through the information_schema tables in order to find all the metadata and relationships for each table and column.
	 */
	function hook_regen_schema($data) {
		$database_id = $data[0];
		$autojoin = $data[1];

		/* Connect to the database and get the schema */
		$conn = $this->hook_connect($_REQUEST['data']['host'], $_REQUEST['data']['name'], $_REQUEST['data']['username'], $_REQUEST['data']['password']);
		$ignore = explode("\n", $_REQUEST['data']['ignore']);
		$ignore = array_map("trim", $ignore);
		$tables = $this->hook_query("select * from information_schema.tables where table_schema='public' order by table_name");
		/* We save the id's to make the references lookup faster. i.e. we don't need to 
		 * query the database each time */
		$id_array = array();
		$tb_array = array();
		#$total = 0;
		foreach ($tables as $i => $table) {
			$full_array[$table['table_name']] = array();
			$id_array[$table['table_name']] = array();
			$tableval = array();
			$tableval['database_id'] = $database_id;
			$tableval['name'] = $table['table_name'];
			$tableval['human_name'] = ucwords(str_replace("_", " ", $table['table_name']));
			/* Save the table */
			$table_id = $this->add_table($tableval);
			/* Background the count function */
			$this->background_function("pgsql/update_record_count/".$table_id);

			$tb_array[$table['table_name']] = $table_id;
			$column_schema = $this->hook_query("select * from information_schema.columns where table_name='".$table['table_name']."' AND table_schema='public' order by column_name");
			if (is_array($column_schema)) {
				foreach ($column_schema as $j => $column) {
					$colval = array();
					$colval['key_type'] = '';
					$keys = $this->hook_query("select distinct t.constraint_type from information_schema.table_constraints t, information_schema.key_column_usage k where k.constraint_name=t.constraint_name and t.table_name='".$table['table_name']."' and k.column_name='".$column['column_name']."' AND t.constraint_type = 'PRIMARY KEY' AND t.table_schema='public' AND k.table_schema='public' ");
					if (is_array($keys)) {
						$colval['key_type'] = "PK";
					}
					$colval['table_id'] = $table_id;
					$colval['name'] = $column['column_name'];
					$colval['human_name'] = ucwords(str_replace("_", " ", $column['column_name']));
					$colval['data_type'] = $column['data_type'];
					$column_id = $this->add_column($colval);
					$full_array[$table['table_name']][$column_id] = $column;
					$id_array[$table['table_name']][$column['column_name']] = $column_id;
				}
			}
			$this->background_function("pgsql/update_example/".$table_id);
		}
		/* Because we need to look-ahead at columns, all columns must be
		 * in the database first. This we re-iterate through the tables
		 * a second time */
		$joins = array();
		$jname = array();
// 		print_r($ignore);
		foreach ($tables as $i => $table) {
			#$joins[$tb_array[$table['table_name']]] = array();
			#$jname[$table['table_name']] = array();
			foreach ($full_array[$table['table_name']] as $j => $column) {
				$colval = array();

				#Get the foreign key details
				$constraints = $this->hook_query("select k2.column_name, k2.table_name from information_schema.key_column_usage k1, information_schema.key_column_usage k2, information_schema.referential_constraints r where k1.constraint_name=r.constraint_name and k1.ordinal_position=k2.ordinal_position and k2.constraint_name=r.unique_constraint_name and k1.table_name='".$table['table_name']."' and k1.column_name='".$column['column_name']."' AND k2.table_schema='public' AND k1.table_schema='public'");
				if (is_array($constraints)) {
					foreach ($constraints as $k => $constraint) {
						$colval['references_column'] = $id_array[$constraint['table_name']][$constraint['column_name']];
						$column_id = $this->save_column($colval, $id_array[$table['table_name']][$column['column_name']]);
						if (in_array($column['column_name'], $ignore) || in_array($constraint['column_name'], $ignore)) {
							continue;
						}
// 						echo $column['column_name']." ".$constraint['column_name']."\n";
// 						echo "X\n";
						$joins[$tb_array[$table['table_name']]][$tb_array[$constraint['table_name']]][] = $id_array[$table['table_name']][$column['column_name']].",".$id_array[$constraint['table_name']][$constraint['column_name']];
// 						$jname[$table['table_name']][$constraint['table_name']][] = $table['table_name']."/".$column['column_name'].",".$constraint['table_name']."/".$constraint['column_name'];
						$joins[$tb_array[$constraint['table_name']]][$tb_array[$table['table_name']]][] = $id_array[$table['table_name']][$column['column_name']].",".$id_array[$constraint['table_name']][$constraint['column_name']];
// 						$jname[$constraint['table_name']][$table['table_name']][] = $table['table_name']."/".$column['column_name'].",".$constraint['table_name']."/".$constraint['column_name'];
					}
				}
			}
		}
// die();
		if ($autojoin) {
			$joins = $this->find_join2($joins);
			$joins = $this->find_join2($joins);
			$joins = $this->find_join2($joins);
			$joins = $this->find_join2($joins);
			$joins = $this->find_join2($joins);
// 			$jname = $this->find_join2($jname);
			/* Two levels deep */
			#$joins = $this->find_join2($tb_array[$table['table_name']], $tb_array[$table2['table_name']], $joins);
			foreach ($joins as $i => $join) {
				foreach ($join as $j => $cell) {
					foreach ($cell as $k => $method) {
						$arr = array();
						$arr['table1'] = $i;
						$arr['table2'] = $j;
						$arr['method'] = $method;
						$this->dobj->db_query($this->dobj->insert($arr, 'table_joins'));
					}
				}
			}
		}
	}

	/* Find Joins
	 * Examines the list of first order joins to build the joins between all tables. This may or may not be acurate.
	 * For now we are limited the found joins to 1
	 */
	function find_join2($joins) {
		foreach ($joins as $a => $t1) {
			foreach ($t1 as $b => $path) {
				foreach ($joins[$b] as $x => $px) {
					if ($a == $x || $b == $x || is_array($joins[$a][$x])) {
						/* Avoid Infinite Loop */
						continue;
					}
					foreach ($path as $p1 => $pcell) {
						foreach ($px as $p2 => $pcell2) {
							/* We use an assoc array to avoid dups */
							$newpath = $pcell.",".$pcell2;
							$joins[$a][$x][$newpath] = $newpath;
						}
					}
				}
			}
		}
		return $joins;
	}


	/* Test Connection
	 * Tries to connect to the database, returns error text 
	 */
	function test_connection($data) {
		$db = $data[0];

		$conn_string = "host=".$db['host']." dbname=".$db['name'];

		if (!empty($db['username'])) {
			$conn_string .= " user=".$db['username'];
		}

		if (!empty($db['password'])) {
			$conn_string .= " password='".$db['password']."'";
		}

		$test_connection = @pg_connect($conn_string);

		if ($test_connection === false) $error['error'] = "Could not connect to the database.";

		return $error;
	}
}

class Pgsql_View extends Catalogue {

	function view_update_record_count() {
		return true;
	}

	function view_update_example() {
		return true;
	}

}
?>
