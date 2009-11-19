<?php

include_once("Common_Functions.php");

$logon = "select * from users where username='%1'";

$getAllQuery = "SELECT * FROM %TABLE ";
$getQuery = "SELECT * FROM %TABLE WHERE (%WHERE) ";
$delQuery = "DELETE FROM %TABLE WHERE (%WHERE) ";
$setQuery = "UPDATE %TABLE set %SET WHERE (%WHERE) ";
$addQuery = "INSERT INTO %TABLE (%VALUESCOL) VALUES (%VALUESVAL) ";
$buildQuery = "SELECT %SET FROM %TABLE WHERE (%WHERE) ";
$buildAllQuery = "SELECT %SET FROM %TABLE  ";

$getNext = "select nextval('%1_%1_id_seq')";

class Database extends Common_Functions {
	
	function __construct() {
		
		
	}
	
	function buildWhere ($where, $comp="=") {
		foreach ($where as $i => $value) {
			$whereS .= " ".$i." ".$comp." '".$value."' and";
		}
		$whereS = rtrim($whereS, "and");
		return $whereS;
	}
	
	/**
	 * $where is an assoc array.
	 * $set is an assoc array.
	 * $values is an assoc array
	 */
	function buildQuery($type, $table, $where="", $set="", $order="", $limit="", $offset="0") {
		global $getAllQuery;
		global $getQuery;
		global $delQuery;
		global $setQuery;
		global $addQuery;
		global $buildQuery;
		global $buildAllQuery;
		global $getNext;
		$query = "";
		if (is_array($where)) {
			$whereS .= Database::buildWhere($where, "=");
		} elseif ($where) {
			$whereS = $where;
		}
		switch ($type) {
			case "select":
	
				if ($set && $where) {
					$query = str_replace("%TABLE", $table, $buildQuery);
					$query = str_replace("%WHERE", $whereS, $query);
					foreach ($set as $i => $value) {
						if ($value == "DISTINCT") {
							$setS .= $value ." ";
						} else {
							$setS .= $value." ,";
						}
					}
					$setS = trim($setS, " ,");
					$query = str_replace("%SET", $setS, $query);
					$query .= $order;
				} elseif ($set) {
					$query = str_replace("%TABLE", $table, $buildAllQuery);
					foreach ($set as $i => $value) {
											if ($value == "DISTINCT") {
													$setS .= $value ." ";
											} else {
													$setS .= $value." ,";
											}
					}
					$setS = trim($setS, " ,");
					$query = str_replace("%SET", $setS, $query);
					$query .= $order;
				} elseif ($where) {
					$query = str_replace("%TABLE", $table, $getQuery);
					$query = str_replace("%WHERE", $whereS, $query);
					$query .= $order;
				} else {
					$query = str_replace("%TABLE", $table, $getAllQuery);
					$query .= $order;
					if ($limit) {
						$query .= " LIMIT $limit OFFSET $offset";
					}
				}
				break;
			case "delete":
				$query = str_replace("%TABLE", $table, $delQuery);
				$query = str_replace("%WHERE", $whereS, $query);
				break;
			case "update":
				$query = str_replace("%TABLE", $table, $setQuery);
				$query = str_replace("%WHERE", $whereS, $query);
				foreach ($set as $i => $value) {
					if ($value == "DEFAULT" || $value == "NULL" || substr($value, 0, 4) == "md5(") {
						$setS .= $i."=".$value." ,";
					} else {					
						$setS .= $i."='".$value."' ,";
					}
				}
				$setS = rtrim($setS, ",");
				$query = str_replace("%SET", $setS, $query);
				break;
			case "insert":
				$query = str_replace("%TABLE", $table, $addQuery);
				foreach ($set as $i => $value) {
					$col .= $i." ,";
					if ($value == "DEFAULT" || $value == "NULL" || substr($value, 0, 4) == "md5(") {
						$val .= "".$value." ,";
					} else {
						$val .= "'".$value."' ,";
					}
				}
				$col = rtrim($col, ",");
				$val = rtrim($val, ",");
				$query = str_replace("%VALUESCOL", $col, $query);
				$query = str_replace("%VALUESVAL", $val, $query);
				break;
			case "next":
				$query = str_replace("%1", $table, $getNext);
				break;
		}
		return $query;
	}
	
	function runQuery ($query, $database=NULL) {
		global $conf;
		if (!$database) {
			$database = $conf['DB']['dbname'];
		}
		$conn_string = "host=".$conf['DB']['dbhost']." dbname=".$database." user=".$conf['DB']['dbuser']." password='".$conf['DB']['dbpass']."'";
		$db_conn = pg_connect($conn_string);
		$query = str_replace("''", "NULL", $query);
		include_once('Common_Functions.php'); // Remove this
		Common_Functions::addToLog(5, "[".$database."] ".$query);	
		$result = pg_query($db_conn, $query);
		$arr = pg_fetch_all($result);
		$error = pg_result_error($result);
	#	echo $query."<br/>";	
		return $arr;
	}
	
	function logon () {
		global $logon;
		if ($_SESSION['username']) {
			return true;
		} elseif ($_POST['username'] ){
			$query = str_replace("%1", $_POST['username'], $logon);
			$arr = Database::runQuery($query);
			if (!$arr[0]['username']) {
				$_SESSION['msg'] = "That user does not exist.";
				return false;
			} elseif ($arr[0]['password'] != md5($_POST['password'])) {
				$_SESSION['msg'] = "Invalid Password. Please try again.";
				return false;
			} else {
				$_SESSION['username'] = $_POST['username'];
				if ($arr[0]['admin'] == "t") {
					$_SESSION['admin'] = $arr[0]['admin'];
					$query = Database::buildQuery("select", "function", NULL, NULL, " order by function_name");
					$_SESSION['functions'] = Common_Functions::reKeyArray(Database::runQuery($query), "function_name");
					$query = Database::buildQuery("select", "report", NULL, NULL, " order by report_name");
					$_SESSION['reports'] = Common_Functions::reKeyArray(Database::runQuery($query), "report_name");
					$query = Database::buildQuery("select", "ea_table", NULL, NULL, " order by ea_table_name");
					$_SESSION['tables'] = Common_Functions::reKeyArray(Database::runQuery($query), "ea_table_id");
				} else {
					$_SESSION['admin'] = $arr[0]['admin'];
					$query = "select * from function f, funcacl x where f.function_id=x.function_id and x.username='".$_POST['username']."' and x.access='t' order by f.function_name";
					$_SESSION['functions'] = Common_Functions::reKeyArray(Database::runQuery($query), "function_name");
					$query = "select * from report f, reportacl x where f.report_id=x.report_id and x.username='".$_POST['username']."' and x.view='t' order by f.report_name";
					$_SESSION['reports'] = Common_Functions::reKeyArray(Database::runQuery($query), "report_name");
					$query = "select * from ea_table f, tableacl x where f.ea_table_id=x.ea_table_id and x.username='".$_POST['username']."' and x.access='t' order by f.ea_table_name";
					$_SESSION['tables'] = Common_Functions::reKeyArray(Database::runQuery($query), "ea_table_id");
				}
				return true;
			}
		} else {
			return false;
		}
	}
	

	/**
	 * getKeys($table)
	 *
	 * This returns the all the keys in the given table
	 *
	 * @param	string   table   	The table name
	 * @return array               	An empty array containing the table keys as the keys to the array.
	 */
	function getKeys($table, $database=NULL) {
		global $conf;
		if (!$database) {
			$database = $conf['DB']['dbname'];
		}
		$conn_string = "host=".$conf['DB']['dbhost']." dbname=".$database." user=".$conf['DB']['dbuser']." password='".$conf['DB']['dbpass']."'";
		$db_conn = pg_connect($conn_string);
		$keys = pg_meta_data($db_conn, $table);
		return $keys;
	}
	
		
	/**
	 * getFKeys($table)
	 *
	 * This returns the all the foreign keys in the given table
	 *
	 * @param	string   table   	The table name
	 * @return array               	An empty array containing the table keys as the keys to the array.
	 */
	function getFKeys($table, $database=NULL) {
		$query = "select c.ea_column_name, c.fks from ea_column c, ea_table t, db d where d.db_id=t.db_id and c.ea_table_id=t.ea_table_id and t.ea_table_name='".$table."' and d.psql_name='".$database."' and c.fks is not null and c.ea_column_name != 'start_timestamp_id' and c.ea_column_name != 'end_timestamp_id' and c.ea_column_name != 'touched_timestamp_id';";
		$keys = Database::runQuery($query);
		if (is_array($keys)) {
			foreach ($keys as $i => $key) {
				$key['fks'] = explode(":", $key['fks']);
				$ikeys[$i] = $key;
			}
		}
		return $ikeys;
	}
	
	/**
	 * getLatestTime()
	 *
	 * This returns the latest timestamp
	 *
	 * @return string               	The latest timestamp
	 */
	function getLatestTime($database=NULL) {
		$query = "select max(timestamp_id) from dw_timestamp where timestamp_id != '1/1/3000'";
		$max = Database::runQuery($query, $database);
		$max = $max[0]['max'];
		return $max;
	}
	
	/**
	 * generateSQL()
	 *
	 * Takes a saved XML ruleset, and creates relevant SQL queries from it
	 * in conjunction with code from the relevant module.
	 *
	 * @return	string/array		SQL query to be executed to get results
	 */
	
	function generateSQL($rules, $linkFromRules=array(), $line_no = -1) {
		$maxTimestamp = Database::getLatestTime($_SESSION['curDB_psql']);
		$minTimestamp = '1/1/2000';
		$type = "select";
		$query = array();
		$cell = array();
		$constraintCell = array();
		
		foreach ($rules['CHILDREN'] as $child => $entry) {
			if ($entry['CHILDREN']) {
				if (in_array(array('NAME' => "TABLE", "ATTRS"=>array(), 'TAGDATA' => "Select Table ..."), $entry['CHILDREN'])) {
					continue;
				}
			}
			if ($entry['NAME'] == "SQL") {
				$sql[] = $entry['TAGDATA'];
				return $sql;
			}
			if (($entry['NAME'] == 'AXIS') || ($entry['NAME'] == 'COLUMN') || ($entry['NAME'] == 'TREND')) {
				$child_name = $entry['ATTRS']['NAME'];
				foreach ($entry['CHILDREN'] as $child2 => $entry2) {
					if ($entry2['NAME']=='CONSTRAINT') {
						$child_cons = $entry2['ATTRS']['NAME'];
						foreach ($entry2['CHILDREN'] as $child3 => $entry3) {
							$constraintCell[$child_name][$child_cons][$entry3['NAME']]=$entry3['TAGDATA'];
						}
					} else {
						$cell[$child_name][$entry2['NAME']] = $entry2['TAGDATA'];
					}
				}
			} elseif ($entry['NAME']=='CONSTRAINT') {
				$child_name = $entry['ATTRS']['NAME'];
				foreach ($entry['CHILDREN'] as $entry2) {
					$constraintCell[0][$child_name][$entry2['NAME']] = $entry2['TAGDATA'];
				}
			}
		}
		$timeCell['START_DATE'] = $rules['ATTRS']['START_DATE'];
		$timeCell['END_DATE'] = $rules['ATTRS']['END_DATE'];
		$timeCell['INTERVAL'] = $rules['ATTRS']['INTERVAL'];
		$module = Common_Functions::loadModule($rules['ATTRS']['REPORT_TYPE']);
		$query = $module->buildSQL($rules, $linkFromRules, $line_no, $cell, $constraintCell, $timeCell, $maxTimestamp, $minTimestamp, $type);
		
		return $query;
	}
	
	/**
	 * getTables will take a query and insert the required timestamp checks for any query.
	 * 
	 * @param string $query	The basic SQL query that will be altered
	 * @param string $maxTimestamp	The timestamp to run the query against
	 * @param bool $skipTime	Whether or not to insert the timestamp check. Mostly for trend reports
	 * @param pointer &$allTables	An array to insert a list of tables (or aliases) into
	 * @return string	The new SQL query
	 */
	function getTables($query, $maxTimestamp, $skipTime=false, &$allTables=array()) {
		/* Get all the required locations for string checks*/
		$origQuery = $query;
		$query = trim($query, "; ");
		$query .= " ";
		$whereLocation = stripos($query, "where");
		$fromLocation = stripos($query, "from");
		$orderLocation = strripos($query, "order by");
		$groupLocation = strripos($query, "group by");
		if ($groupLocation < $orderLocation and $groupLocation != 0) {
			$orderLocation = $groupLocation;
		}
	
		if (!$whereLocation) {
			if (!$orderLocation) {
				$query = $query." where";
			} else {
				$query = substr_replace($query, " where ", $orderLocation, 0);
			}
		} else {
			if (!$orderLocation) {
				$query = $query." and";
			} else {
				$query = substr_replace($query, " and ", $orderLocation, 0);
			}
		}
                $whereLocation = stripos($query, "where");
                $orderLocation = strripos($query, "order by");
                $groupLocation = strripos($query, "group by");
                if ($groupLocation < $orderLocation and $groupLocation != 0) {
                        $orderLocation = $groupLocation;
                }

		$maxLength = $whereLocation;
		/* Returns all the tables used in the query */
		$tables = substr($query, $fromLocation+5, ($maxLength-$fromLocation-6));
		$all_tables = explode(",", $tables);
		$tables = array();
		/* Creates an associated array of the table names and its aliases (if any) */
		foreach ($all_tables as $i => $table) {
			$table_info = explode(" ", trim($table));
			if ($table_info[1]) {
				$tables[$table_info[1]] = trim($table_info[0]);
			} else {
				$tables[] = trim($table_info[0]);
			}
		}
		foreach ($_SESSION['tables'] as $i => $table) {
			/* Must be from the current database */
			if ($table['db_id'] != $_SESSION['curDB']) {
				continue;
			}
			foreach ($tables as $j => $newtable) {
				if ($table['ea_table_name'] == $newtable) {
					/* Must use the alias if it exists */
					if (!is_int($j)) {
						$alias = $j;
					} else {
						$alias = $table['ea_table_name'];
					}
					$allTables[] = $alias;
					/* Insert the timestamp checks. Including all foreign relationships based on start_timestamp_id */
					if (!$skipTime) {
						$where = " ".$alias.".end_timestamp_id='".$maxTimestamp."' and";
					}
				}
			}
		}
    /* Checks for a where clause */
    if ($skipTime) {
      return $origQuery;
    }
		if (!$orderLocation) {
			$query .= rtrim($where, "and");
		} else {
			$query = substr_replace($query, rtrim($where, "and"), $orderLocation, 0);
		}
		$query = rtrim($query, "where");
		$query = rtrim($query, "and");
		return $query;
	}
	

	
}



?>
