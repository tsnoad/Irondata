<?php

/**
 * editUser
 *
 * EditUser prints a form for creating users. If an array is passed to it, it will use that to prefill the values.
 * Thus overloading the function such that it can be used to edit users as well as create a new user.
 *
 * @param array $details               The array to use as default
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function editUser($details=array()) {
	/* Display the user form */
	$main .= "<form name='user' method='POST' >";
	$main .= "<input type='hidden' name='oldID' value='".$details['username']."'>";
	$main .= "<input type='hidden' name='oldPW' value='".$details['password']."'>";
	$main .= "<input type='hidden' name='username' value='".$details['username']."'>";
	$main .= "<h3>New User</h3>";
	$main .= "<table border=0>";
	$main .= "<tr><td>Username:</td><td><input type='text' name='username-val' value='".$details['username']."'></td></tr>";
	$main .= "<tr><td>Password:</td><td><input type='password' name='password-val' value=''></td></tr>";
	$main .= "<tr><td>Retype Password:</td><td><input type='password' name='retype_password-val' value=''></td></tr>";
	/* Because checkboxes need to be checked, unlike printing a value */
	if ($details['admin'] == "on" || $details['admin'] == "t") {
		$checked = " checked";
	}
	$main .= "<tr><td>Administrator:</td><td><input type='checkbox' name='admin' ".$checked."></td></tr>";
	if ($details['oldID'] || $_POST['command'] == 'edit_template') {
		$main .= "<tr><td colspan=2><input type='submit' value='Update User'></td></tr>";
	} else {
		$main .= "<tr><td colspan=2><input type='submit' value='Create New User'></td></tr>";
	}
	$main .= "</table>";
	$main .= "<h3>User Permissions</h3>";
	if ($details['admin'] == "t") {
		$main .= "This user has administrator privileges. Thus you do not need additional permsissions for database, table, function or report access,";
	} else {
		$main .= "<table><tr>";
		/* Display the Functions ACL form */
		$query = Database::buildQuery("select", "function");
		$functions = Database::runQuery($query);
		$query = Database::buildQuery("select", "funcacl", array("username"=>$details['username'], "access"=>"t"));
		$funcacl = Database::runQuery($query);
		$main .= "<td valign=top>
			<strong>Functions</strong><br/>
			<table>";
		$main .= "<tr><td><strong>Function Name</strong></td><td><strong>Access</strong></td></tr>";
		foreach ($functions as $i => $function) {
			$value = "";
			if (is_array($funcacl)) {
				foreach ($funcacl as $j => $acl) {
					if ($acl['function_id'] == $function['function_id']) {
						/* Because checkboxes need to be checked, unlike printing a value */
						$value = "checked";
					}
				}
			}
			$main .= "<tr><td>".$function['function_name']."</td><td><input type='checkbox' name='functions[".$function['function_id']."]' ".$value."></td></tr>";
		}
		$main .= "</table>";
		/* Display the Tables ACL */
		$query = Database::buildQuery("select", "ea_table");
		$tables = Database::runQuery($query);
		$query = Database::buildQuery("select", "tableacl", array("username"=>$details['username'], "access"=>"t"));
		$tableacl = Database::runQuery($query);
		$query = Database::buildQuery("select", "db");
		$database = Database::runQuery($query);
		$database = Common_Functions::reKeyArray($database, "db_id");
		$main .= "<br/><br/>
			<strong>Tables</strong><br/>
			<table>";
		$main .= "<tr><td><strong>Table Name</strong></td><td><strong>Access</strong></td></tr>";
		foreach ($tables as $i => $table) {
			$value = "";
			if (is_array($tableacl)) {
				foreach ($tableacl as $j => $acl) {
					if ($acl['ea_table_id'] == $table['ea_table_id']) {
						/* Because checkboxes need to be checked, unlike printing a value */
						$value = "checked";
					}
				}
			}
			$main .= "<tr><td>".$database[$table['db_id']]['db_name']." :: </td><td>".$table['ea_table_name']."</td><td><input type='checkbox' name='table[".$table['ea_table_id']."]' ".$value."></td></tr>";
		}
		$main .= "</table></td>";
		/* Display the Reports ACL */
		$query = Database::buildQuery("select", "report");
		$reports = Database::runQuery($query);
		$query = Database::buildQuery("select", "reportacl", array("username"=>$details['username']));
		$reportacl = Database::runQuery($query);
		$main .= "<td valign=top>
			<strong>Reports</strong><br/>
			<table>";
		$main .= "<tr><td><strong>Report Name</strong></td><td><strong>View</strong></td><td><strong>Edit</strong></td><td><strong>Delete</strong></td><td><strong>Run</strong></td><td><strong>Save</strong></td></tr>";
		foreach ($reports as $i => $report) {
			$view = "";
			$edit = "";
			$delete = "";
			$run = "";
			$save = "";
			if (is_array($reportacl)) {
			foreach ($reportacl as $j => $acl) {
				if ($acl['report_id'] == $report['report_id']) {
					/* Because checkboxes need to be checked, unlike printing a value */
					if ($acl['view'] == "t"){
						$view .= "checked";
					}
					if ($acl['edit'] == "t"){
						$edit .= "checked";
					}
					if ($acl['delete'] == "t"){
						$delete .= "checked";
					}
					if ($acl['run'] == "t"){
						$run .= "checked";
					}
					if ($acl['save'] == "t"){
						$save .= "checked";
					}
				}
			}
			}
			$main .= "<tr><td>".$report['report_name']."</td><td><input type='checkbox' name='report[".$report['report_id']."][view]' ".$view."></td><td><input type='checkbox' name='report[".$report['report_id']."][edit]' ".$edit."></td><td><input type='checkbox' name='report[".$report['report_id']."][delete]' ".$delete."></td><td><input type='checkbox' name='report[".$report['report_id']."][run]' ".$run."></td><td><input type='checkbox' name='report[".$report['report_id']."][save]' ".$save."></td></tr>";
		}
		$main .= "</table></td>";
		$main .= "</tr></table>";
	}
	$main .= "</form>";
	return $main;
}

/**
 * showUser
 *
 * ShowUser will print all the user information stored in the database.
 *
 * @param array $details               The details to display
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function showUser($details) {
	$user_id = isset($details['oldID'])?$details['oldID']:$details['username'];
	$main .= "<h3>User: ".$user_id."</h3>";;
	$main .= "<table border=0>";
	$main .= "<tr><td>Username:</td><td>".$user_id."</td></tr>";
	$main .= "<tr><td>Password:</td><td>********</td></tr>";
	if ($details['admin'] == "on" || $details['admin'] == "t") {
		$admin = " Yes";
	} else {
		$admin = "No";
	}
	$main .= "<tr><td>Administrator:</td><td>".$admin."</td></tr>";
	$main .= "</table>";
	$main .= "<h3>User Permissions</h3>";
	if ($details['admin'] == "t") {
		$main .= "This user has administrator priviledges. Thus you do not need additional permsissions for database, table, function or report access,";
	} else {
		$main .= "<table><tr>";
		/* Display the Function ACL */
		$query = Database::buildQuery("select", "function");
		$functions = Database::runQuery($query);
		$query = Database::buildQuery("select", "funcacl", array("username"=>$user_id, "access"=>"t"));
		$funcacl = Database::runQuery($query);
		$main .= "<td valign=top>
			<strong>Functions</strong><br/>
			<table>";
		foreach ($functions as $i => $function) {
			$value = "None";
			if (is_array($funcacl)) {
			foreach ($funcacl as $j => $acl) {
				if ($acl['function_id'] == $function['function_id']) {
					$value = "Access";
				}
			}
			}
			$main .= "<tr><td>".$function['function_name']."</td><td>".$value."</td></tr>";
		}
		$main .= "</table>";
		/* Display the Table ACL */
		$query = Database::buildQuery("select", "ea_table");
		$tables = Database::runQuery($query);
		$query = Database::buildQuery("select", "db");
		$database = Database::runQuery($query);
		$database = Common_Functions::reKeyArray($database, "db_id");
		$query = Database::buildQuery("select", "tableacl", array("username"=>$user_id, "access"=>"t"));
		$tableacl = Database::runQuery($query);
		$main .= "<br/><br/><strong>Tables</strong><br/>
			<table>";
		
		foreach ($tables as $i => $table) {
			$value = "None";
			if (is_array($tableacl)) {
			foreach ($tableacl as $j => $acl) {
				if ($acl['ea_table_id'] == $table['ea_table_id']) {
					$value = "Access";
				}
			}
			}
			$main .= "<tr><td>".$database[$table['db_id']]['db_name']." :: </td><td>".$table['ea_table_name']."</td><td>".$value."</td></tr>";
		}
		$main .= "</table></td>";
		/* Display the Report ACL */
		$query = Database::buildQuery("select", "report");
		$reports = Database::runQuery($query);
		$query = Database::buildQuery("select", "reportacl", array("username"=>$user_id));
		$reportacl = Database::runQuery($query);
		$main .= "</td><td valign=top>
			<strong>Reports</strong><br/>
			<table>";
		foreach ($reports as $i => $report) {
			$value = "";
			if (is_array($reportacl)) {
			foreach ($reportacl as $j => $acl) {
				if ($acl['report_id'] == $report['report_id']) {
					if ($acl['view'] == "t"){
						$value .= "View, ";
					}
					if ($acl['edit'] == "t"){
						$value .= "Edit, ";
					}
					if ($acl['delete'] == "t"){
						$value .= "Delete, ";
					}
					if ($acl['run'] == "t"){
						$value .= "Run, ";
					}
					if ($acl['save'] == "t"){
						$value .= "Save, ";
					}
				}
			}
			}
			$main .= "<tr><td>".$report['report_name']."</td><td>".$value."</td></tr>";
		}
		$main .= "</table></td>";
		$main .= "</tr></table>";
	}
	$main .= "</form>";
	return $main;
}

/**
 * updateUser
 *
 * POST will only be filled when adding or editting a user (or database). This checks the POST values and updates the database
 * as required.
 *
 * @return string	Either the editUser screen again, or the showUser screen, depending on the validity of the results.
 */
function updateUser() {
	
	/* Are both passwords the same. If not reload the previous page */
	if ($_POST['password-val'] == $_POST['retype_password-val']) {
		if ($_POST['admin'] == "on") {
			$_POST['admin'] = "t";
		} else {
			$_POST['admin'] = "f";
		}
		/* A POST key oldID will only be filled if editting a user. Thus it will be an update in the DB not an insert */
		if ($_POST['oldID']) {
			/* Blank passwords mean the password will not be changed. Don't add it to the set array */
			if ($_POST['password-val'] == "" && $_POST['retype_password-val'] == "") {
				$setArray = array("username"=>$_POST['username-val'], "admin"=>$_POST['admin']);
			} else {
				$setArray = array("username"=>$_POST['username-val'], "password"=>"md5('".$_POST['password-val']."')", "admin"=>$_POST['admin']);
			}
			/* Update the user in the database */
			$query = Database::buildQuery("update", "users", array("username"=>$_POST['oldID']), $setArray);
		} else {
			/* Insert the new user in the database */
			$query = Database::buildQuery("insert", "users", "", array("username"=>$_POST['username-val'], "password"=>"md5('".$_POST['password-val']."')", "admin"=>$_POST['admin']));
		}
		/* Delete all ACL values for the given user. We do this because we then reinsert everything. This is a poor way of doing it, but
		 * it is much faster to develop and also a smaller POST overhead.
		 */
		$results = Database::runQuery($query);
		$query = Database::buildQuery("delete", "funcacl", array("username"=>$_POST['username-val']));
		$results = Database::runQuery($query);
		$query = Database::buildQuery("delete", "tableacl", array("username"=>$_POST['username-val']));
		$results = Database::runQuery($query);
		$query = Database::buildQuery("delete", "reportacl", array("username"=>$_POST['username-val']));
		$results = Database::runQuery($query);
		/* Insert all the functions, tables, and report ACL values */
		foreach ($_POST['functions'] as $i => $func) {
			$query = Database::buildQuery("insert", "funcacl", NULL, array("username"=>$_POST['username-val'], "function_id"=>$i, "access"=>"t"));
			$results = Database::runQuery($query);
		}
		foreach ($_POST['table'] as $i => $table) {
			$query = Database::buildQuery("insert", "tableacl", NULL, array("username"=>$_POST['username-val'], "ea_table_id"=>$i, "access"=>"t"));
			$results = Database::runQuery($query);
		}
		foreach ($_POST['report'] as $i => $func) {
			$set = array("username"=>$_POST['username-val'], "report_id"=>$i);
			/* Reports have multiple ACL types (edit, delete, run, save) unlike the others (access) */
			if ($func['view']) {
				$set['view'] = "t";
			}
			if ($func['edit']) {
				$set['edit'] = "t";
			}
			if ($func['delete']) {
				$set['delete'] = "t";
			}
			if ($func['run']) {
				$set['run'] = "t";
			}
			if ($func['save']) {
				$set['save'] = "t";
			}
			$query = Database::buildQuery("insert", "reportacl", NULL, $set);
			$results = Database::runQuery($query);
		}
		$main .= showUser($_POST);
	} else {
		/* Are both passwords the same. If not reload the previous page */
		$mainicons = false;
		$main = "<h3 style='color: red;'>Please ensure that both passwords are identical</h3>";
		$main .= editUser($_POST);
	}
	return $main;
}

/*
 * EditDB prints a form for creating a new database and editting an existing one is the details are passed to it
 */
function editDB($details=array()) {
	if ($details) {
		$query = Database::buildQuery("select", "ea_table", array("db_id"=>$details['db_id']));
		$tables = Database::runQuery($query);
	}
	$main .= "<form name='db' method='POST' >";
	$main .= "<input type='hidden' name='oldID' value='".$details['db_id']."'>";
	$main .= "<h3>New Database</h3>";;
	$main .= "If changes have been made to the database structure, or this is a new database being added. This will regenerate the available tables within this database for the report generator.<br/><br/>";
	$main .= "<table border=0>";
	$main .= "<tr><td>Database Name:</td><td><input type='text' name='db_name' value='".$details['db_name']."'></td></tr>";
	$main .= "<tr><td>PostgreSQL Name:</td><td><input type='text' name='psql_name' value='".$details['psql_name']."'></td></tr>";
	$main .= "<tr><td>SQL Create Script:</td><td><input type='text' name='create_script'></td></tr>";
	$main .= "<tr><td colspan=2><input type='submit' value='Add/Edit Database'></td></tr>";
	$main .= "<tr><td><strong>Tables</strong><td><strong>Columns</strong><td><strong>Foreign Keys</strong></td><td><strong>Selectable</strong></td><td><strong>Select Values</strong></td></tr>";
	if (is_array ($tables)) {
		foreach ($tables as $i => $table) {
			$main .= "<tr><td>".$table['ea_table_name']."</td>";
			$query = Database::buildQuery("select", "ea_column", array("ea_table_id"=>$table['ea_table_id']));
			$columns = Database::runQuery($query);
			foreach ($columns as $j=> $column) {
				$checked = "";
				if ($column['constraint_select'] == "t") {
					$checked = "checked";
				}
				$main .= "<td>".$column['ea_column_name']."</td><td><input type='text' value='".$column['fks']."' name='fks-".$table['ea_table_name']."-".$column['ea_column_name']."'></td><td><input type='checkbox' name='column-".$table['ea_table_name']."-".$column['ea_column_name']."' ".$checked."></td><td>".$column['select_values']."</td></tr>";
				$main .= "<tr><td></td>";
			}
			$main .= "</tr>";
		}
	}
	$main .= "</table>";
	$main .= "</form>";
	return $main;
}

/* showDB will print the given database details */
function showDB($details=array()) {
	$query = Database::buildQuery("select", "ea_table", array("db_id"=>$details['db_id']));
	$tables = Database::runQuery($query);
	$main .= "<h3>Database: ".$details['db_name']."</h3>";
	$main .= "<table border=0>";
	$main .= "<tr><td>PostgreSQL Name:</td><td>".$details['psql_name']."</td></tr>";
	$main .= "<tr><td colspan='5'><hr></td></tr>";
	$main .= "<tr><td><strong>Tables</strong></td><td><strong>Columns</strong></td><td><strong>Foreign Keys</strong></td><td><strong>Select Values</strong></td></tr>";
	foreach ($tables as $i => $table) {
		$main .= "<tr><td>".$table['ea_table_name']."</td>";
		$query = Database::buildQuery("select", "ea_column", array("ea_table_id"=>$table['ea_table_id']));
		$columns = Database::runQuery($query);
		foreach ($columns as $j=> $column) {
			$main .= "<td>".$column['ea_column_name']."</td><td>".$column['fks']."</td><td>".$column['select_values']."</td></tr>";
			$main .= "<tr><td></td>";
		}
		$main .= "</tr>";
	}
	$main .= "</table>";
	$main .= "</form>";
	return $main;
}

/* Normal Page Checks */
$conf = parse_ini_file("../conf.ini", true);

include($conf['Dir']['FullPath']."/common.php");
session_start();
Database::logon();

$maintitle = "Administration";
$menutitle = "Administration Menu";
$menustyle = "Admin";


if ($_GET['run'] == "true") {
	/* There is no run command in the Admin screens */
	$main = "That command makes no sense in this context.";
} elseif ($_GET['user_id'] && $_GET['command'] == "delete_template") {
	/* If the confirmation poup was accepted it will delete the user (passed through GET) */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=user&user_id=".$_GET['user_id'];
	$query = Database::buildQuery("delete", "users", array("username"=>$_GET['user_id']));
	$results = Database::runQuery($query);
	$main = "The user '".$_GET['user_id']."' has been deleted from the system.";
} elseif ($_GET['database_id'] && $_GET['command'] == "delete_template") {
	/* If the confirmation poup was accepted it will delete the database (passed through GET) */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=database&database_id=".$_GET['db_id'];
	$query = Database::buildQuery("select", "db", array("db_id"=>$_GET['database_id']));
	$results = Database::runQuery($query);
	$query = Database::buildQuery("delete", "db", array("db_id"=>$_GET['database_id']));
	$delres = Database::runQuery($query);
	$main = "The database '".$results[0]['db_name']."' has been deleted from the system.";
} elseif ($_POST['username-val']) {
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=user&user_id=".$_GET['user_id'];
	$main = updateUser();
} elseif ($_POST['db_name']) {
	/* POST will only be filled when adding or editting a database (or user). This checks the POST values and updates the database
	 * as required.
	 */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=database&database_id=".$_GET['database_id'];
	$setArray = array("db_name"=>$_POST['db_name'], "psql_name"=>$_POST['psql_name']);
	/* A POST key oldID will only be filled if editting a database. Thus it will be an update in the DB not an insert */
	if ($_POST['oldID']) {
		$query = Database::buildQuery("update", "db", array("db_id"=>$_POST['oldID']), $setArray);
	} else {
		$query = Database::buildQuery("insert", "db", "", $setArray);
	}
	/* Autogenerate the foreign key contraints from the SQL create script. */
	if ($_POST['create_script']) {
		$script = file($_POST['create_script']);
	}
	$results = Database::runQuery($query);
	$query = Database::buildQuery("select", "db", $setArray);
	/* Delete all the tables in the auth database for the given database. We do this because if someone edits a database
	 * we retest for tables in case changes have occured. */
	$database = Database::runQuery($query);
	$query = Database::buildQuery("delete", "ea_table", array("db_id"=>$database[0]['db_id']));
	$results = Database::runQuery($query);
	$_POST['db_id'] = $database[0]['db_id'];
	/* Get the tables in the new database. This is postgres specific. */
	$results = Database::runQuery("select * from information_schema.tables where table_schema='public' order by table_name", $_POST['psql_name']);
	foreach ($results as $i => $result) {
		$query = Database::buildQuery("next", "ea_table");
		$next = Database::runQuery($query);
		$next = $next[0]['nextval'];
		$query = Database::buildQuery("insert", "ea_table", "", array("ea_table_id"=>$next, "ea_table_name"=>$result['table_name'], "db_id"=>$database[0]['db_id']));
		$insert = Database::runQuery($query);
		/* Delete all the columns in the auth database for the given table. We do this because if someone edits a table
		 * we retest for tables in case changes have occured. */
		$query = Database::buildQuery("delete", "ea_column", array("ea_table_id"=>$next));
		$delete = Database::runQuery($query);
		$columns = Database::runQuery("select * from information_schema.columns where table_name='".$result['table_name']."' order by column_name", $_POST['psql_name']);
		foreach ($columns as $j => $column) {
			if ($_POST['column-'.$result['table_name'].'-'.$column['column_name']]) {
				$set['constraint_select'] = 't';
				$values = Database::runQuery("select distinct ".$column['column_name']." from ".$result['table_name'], $_POST['psql_name']);
				foreach ($values as $k => $val) {
					$set['select_values'][] = str_replace("&", "&amp;", $val[$column['column_name']]);
					#$val[$column['column_name']] = str_replace("&", "&amp;", $val[$column['column_name']]);
				}
				$set['select_values'] = implode(",", $set['select_values']);

				#if (is_array($set['select_values'])) {
				#	$set['select_values'] = implode(",", $set['select_values']);
				#}
			}
			/* Autogenerate the foreign key contraints from the SQL create script. */
			if ($_POST['create_script']) {
				$go = false;
				foreach ($script as $si => $sline) {
					if (stripos($sline, "CREATE TABLE ".$result['table_name']) === 0) {
						$go = true;
					}
					if (stripos($sline, ");") === 0) {
						$go = false;
					}
					if (stripos($sline, $column['column_name']) && stripos($sline, "FOREIGN KEY") && $go == true && $column['column_name'] != 'start_timestamp_id') {
						$sline = str_replace('FOREIGN KEY', '', $sline);
						$sline = str_replace('(', '', $sline);
						$sline = str_replace(')', '', $sline);
						$sline = str_replace(',', '', $sline);
						$sline = trim($sline);
						$sarray = explode(" ", $sline);
						foreach ($sarray as $sj => $sword) {
							if ($sword == ''.$column['column_name']) {
								$sloc = $sj;
							}
							if ($sword == 'references' || $sword == 'REFERENCES') {
								$stable = $sarray[$sj+1];
								$scolumn = $sarray[$sj+2+$sloc];
								break;
							}
						}
						$set['fks'] = $stable.":".$scolumn;
						$go = false;
						break;
						
					}
				}
			}
			if ($_POST['fks-'.$result['table_name'].'-'.$column['column_name']]) {
				$set['fks'] = $_POST['fks-'.$result['table_name'].'-'.$column['column_name']];
			}
			$set['ea_column_name'] = $column['column_name'];
			$set['ea_table_id'] = $next;
			$query = Database::buildQuery("insert", "ea_column", "", $set);
			unset($set);
			$insert = Database::runQuery($query);
		}
	}
	$main .= showDB($_POST);
} elseif (($_GET['user_id'] && $_GET['command'] == "edit_template") || ($_GET['admin_type'] == "user")) {
	/* Brings up the edit user page (though the editUser() function */
	$mainicons = false;
	//if ($_GET['edit']) {
		$query = Database::buildQuery("select", "users", array("username"=>$_GET['user_id']));
		$results = Database::runQuery($query);
		$results[0]['retype_password'] = $results[0]['password'];
	//}
	$main .= editUser($results[0]);
} elseif (($_GET['database_id'] && $_GET['command'] == "edit_template") || ($_GET['admin_type'] == "database")) {
	/* Brings up the edit database page (though the editDB() function */
	$mainicons = false;
	/* Quickly updated by Evan. Changed Edit to Edit_Tempalte */
	if ($_GET['command'] == "edit_template") {
		$query = Database::buildQuery("select", "db", array("db_id"=>$_GET['database_id']));
		$results = Database::runQuery($query);
	}
	$main .= editDB($results[0]);
} elseif ($_GET['user_id']) {
	/* Brings up the show user page (though the showUser() function */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=user&user_id=".$_GET['user_id'];
	$query = Database::buildQuery("select", "users", array("username"=>$_GET['user_id']));
	$results = Database::runQuery($query);
	$main .= showUser($results[0]);
} elseif ($_GET['database_id']) {
	/* Brings up the show database page (though the showDB() function */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=database&database_id=".$_GET['database_id'];
	$query = Database::buildQuery("select", "db", array("db_id"=>$_GET['database_id']));
	$results = Database::runQuery($query);
	$main .= showDB($results[0]);
}

$menutitle = "Admin:";
include($conf['Dir']['FullPath']."/html.inc");

?>
