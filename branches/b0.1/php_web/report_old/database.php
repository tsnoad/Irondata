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
	
	public function runQuery ($query, $database=NULL) {
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
		echo "\n\n\nQUERY:\n\n".$query."\n\n\n";	
		return $arr;
	}

	function ldapAuthenticated($usr, $pwd) {
		if (!$pwd) {
			return false;
		}
		#Connect to the ldap server
		$ldaphost = '192.168.25.10';
		$ds=ldap_connect($ldaphost);
		if ($ds) {
			#Bind anonymously. Used to find the dn of the user. 
			$r = ldap_bind($ds);
			#Search for the user. 
			$sr = ldap_search($ds, "o=ieaust", "cn=$usr");

			#Get all results. 
			$info = ldap_get_entries($ds, $sr);
			foreach ($info as $i => $info_row) {
				#Do not attempt to bind if the record has no DN (ie system records).
				if (!($info_row['dn'])) {
					continue;
				}
				$fulldn = $info_row['dn'];
				#Check that the supplied user can bind/login with the supplied password. 
				if (ldap_bind($ds, $fulldn, $pwd)) {
					ldap_close($ds);
					#Success. Return the users group membership
					return $info_row;
				}
			}
			ldap_close($ds);
		}
		#All bindings failed (or noone by that username). 
		return false;
	}
	
	function logon () {
		global $logon;
		if ($_SESSION['username']) {
			return true;
		} elseif ($_POST['username'] ){
			$query = str_replace("%1", $_POST['username'], $logon);
			$arr = Database::runQuery($query);
			if($arr)
			{
				$arr = $arr[0];
				$query = "SELECT * from user_groups WHERE username='".$arr['username']."'";
				$groups = Common_Functions::reKeyArray(Database::runQuery($query), "group_id");
				$_SESSION['displayname'] = $_POST['username'];
			} else {
			
				$ldap = Database::ldapAuthenticated($_POST['username'], $_POST['password']);
				if ($ldap['groupmembership']) {
					$arr['username'] = $_POST['username'];
					$arr['password'] = md5($_POST['password']);
					if ($ldap['groupmembership'] !== true) {
						foreach ($ldap['groupmembership'] as $i=>$group) {
							$name = explode(",", $group);
							$cn_name = '';
							foreach ($name as $j => $groupname) {
								$part = explode("=", $groupname);
								if ($part[0] == "cn") {
									$cn_name = $part[1];
									break;
								}
							}
							$groups[$cn_name] = $cn_name;
						}
					}
					if ($ldap['givenname']) {
						$_SESSION['displayname'] = $ldap['givenname'][0];
					} else {
						$_SESSION['displayname'] = $_POST['username'];
					}
				} 
			}
			if (!$arr['username']) {
				$_SESSION['msg'] = "That user does not exist.";
				return false;
			} elseif ($arr['password'] != md5($_POST['password'])) {
				$_SESSION['msg'] = "Invalid Password. Please try again.";
				return false;
			} else {
				$_SESSION['username'] = $_POST['username'];
				$_SESSION['admin'] = "f";
				$_SESSION['groups'] = $groups;
				$_SESSION['functions'] = array();
				$_SESSION['reports'] = array();
				$_SESSION['dbs'] = array();
				$allgroups = array();
				foreach ($groups as $i => $group) {
					$i = strtolower($i);
					$query = "SELECT * FROM groups WHERE lower(group_id)=lower('".$i."') order by group_id";
					$group = Database::runQuery($query);
					if ($group[0]['admin'] == "t") {
						$_SESSION['admin'] = "t";
						$query = Database::buildQuery("select", "function", NULL, NULL, " order by function_name");
						$_SESSION['functions'] = Common_Functions::reKeyArray(Database::runQuery($query), "function_name");
						$query = Database::buildQuery("select", "report", NULL, NULL, " order by report_name");
						$_SESSION['reports'] = Common_Functions::reKeyArray(Database::runQuery($query), "report_id");
						$query = Database::buildQuery("select", "db", NULL, NULL, " order by db_name");
						$_SESSION['dbs'] = Common_Functions::reKeyArray(Database::runQuery($query), "db_id");
						#No need to continue;
						Common_Functions::auditLog("Core", "Authenticated and Logged In", "");
						return true;
					} else {
						$allgroups[] = $i;
					}
				}
				if ($_SESSION['admin'] != "t") {
					$query = "select DISTINCT f.*, x.access from function f, funcacl x where f.function_id=x.function_id and (lower(x.group_id)=lower('".implode("') or lower(x.group_id)=lower('", $allgroups)."')) and x.access='t' order by f.function_name";
					$_SESSION['functions'] = Common_Functions::reKeyArray(Database::runQuery($query), "function_name");
					$query = "select DISTINCT f.*, x.access from report f, dbacl x where f.db_id=x.db_id and (lower(x.group_id)=lower('".implode("') or lower(x.group_id)=lower('", $allgroups)."')) and x.access='t' order by f.report_name";
					$_SESSION['reports'] = Common_Functions::reKeyArray(Database::runQuery($query), "report_id");
					$query = "select DISTINCT f.*, x.access from db f, dbacl x where f.db_id=x.db_id and (lower(x.group_id)=lower('".implode("') or lower(x.group_id)=lower('", $allgroups)."')) and x.access='t' order by f.db_name";
					$_SESSION['dbs'] = Database::runQuery($query);
				}
			}
			Common_Functions::auditLog("Core", "Authenticated and Logged In", "");
			return true;
		} else {
			return false;
		}
	}
	
	/* Gets the permissions for databases, reports and functions */
	function getPerms() {
	
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
		$query = "select c.ea_column_name, c.fks from ea_column c, ea_table t, db d where d.db_id=t.db_id and c.ea_table_id=t.ea_table_id and t.ea_table_name='".$table."' and d.psql_name='".$database."' and c.fks is not null and c.ea_column_name != 'start_date' and c.ea_column_name != 'end_date';";
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
		$query = "select max(timestamp_id) from dw_timestamp where complete='t'";
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
			} elseif ($entry['NAME']=='GLOBAL_CONSTRAINT') {
				$child_name = $entry['ATTRS']['NAME'];
				foreach ($entry['CHILDREN'] as $child2 => $entry2) {
					if ($entry2['NAME']=='CONSTRAINT') {
						$child_cons = $entry2['ATTRS']['NAME'];
						foreach ($entry2['CHILDREN'] as $child3 => $entry3) {
							$constraintCell["G"][$child_cons][$entry3['NAME']]=$entry3['TAGDATA'];
						}
					}
				}
			}
		}
		$timeCell['START_DATE'] = $rules['ATTRS']['START_DATE'];
		$timeCell['END_DATE'] = $rules['ATTRS']['END_DATE'];
		$timeCell['INTERVAL'] = $rules['ATTRS']['INTERVAL'];
		$module = Common_Functions::loadModule($rules['ATTRS']['REPORT_TYPE']);
		$query = $module->buildSQL($rules, $linkFromRules, $line_no, $cell, $constraintCell, $timeCell, $maxTimestamp, $minTimestamp, $type);
		#echo "\nDEBUG:\n";print_r($query);echo"\n\n";
		
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
		foreach ($tables as $j => $newtable) {
			/* Must use the alias if it exists */
			if (!is_int($j)) {
				$alias = $j;
			} else {
				$alias = $newtable;
			}
			$allTables[] = $alias;
			/* Insert the timestamp checks. Including all foreign relationships based on start_date */
			if (!$skipTime) {
				$where = " ".$alias.".end_date='".$maxTimestamp."' and";
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
