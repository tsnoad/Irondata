<?php

/**
 * editGroup
 *
 * editGroup prints a form for creating groups. If an array is passed to it, it will use that to prefill the values.
 * Thus overloading the function such that it can be used to edit groups as well as create a new group.
 *
 * @param array $details               The array to use as default
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function editGroup($details=array()) {
	/* Display the group form */
	$main .= "<form name='group' method='POST' >";
	$main .= "<input type='hidden' name='oldID' value='".$details['group_id']."'>";
	$main .= "<input type='hidden' name='group_id' value='".$details['group_id']."'>";
	$main .= "<h3>New Group</h3>";
	$main .= "<table border=0>";
	$main .= "<tr><td>Group Name:</td><td><input type='text' name='group_id-val' value='".$details['group_id']."'></td></tr>";
	$main .= "<tr><td>Description:</td><td><input type='text' name='description-val' value='".$details['description']."'></td></tr>";
	/* Because checkboxes need to be checked, unlike printing a value */
	if ($details['admin'] == "on" || $details['admin'] == "t") {
		$checked = " checked";
	}
	$main .= "<tr><td>Administrator:</td><td><input type='checkbox' name='admin' ".$checked."></td></tr>";
	if ($details['oldID'] || $_POST['command'] == 'edit_template') {
		$main .= "<tr><td colspan=2><input type='submit' value='Update Group'></td></tr>";
	} else {
		$main .= "<tr><td colspan=2><input type='submit' value='Create New Group'></td></tr>";
	}
	$main .= "</table>";
	$main .= "<h3>Group Permissions</h3>";
	if ($details['admin'] == "t") {
		$main .= "This group has administrator privileges. Thus you do not need additional permsissions for database, table, function or report access,";
	} else {
		$main .= "<table><tr>";
		/* Display the Functions ACL form */
		$query = Database::buildQuery("select", "function", null, null, "order by function_name");
		$functions = Database::runQuery($query);
		$query = Database::buildQuery("select", "funcacl", array("group_id"=>$details['group_id'], "access"=>"t"));
		$funcacl = Database::runQuery($query);
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

		/* Display the DB ACL */
		$query = Database::buildQuery("select", "db", NULL, NULL, "order by db_name");
		$dbs = Database::runQuery($query);
		$query = Database::buildQuery("select", "dbacl", array("group_id"=>$details['group_id'], "access"=>"t"));
		$dbacl = Database::runQuery($query);
		$main .= "<tr><td>&nbsp;</td></tr>";
		$main .= "<tr><td><strong>Database Name</strong></td><td><strong>Access</strong></td></tr>";
		foreach ($dbs as $i => $db) {
			$value = "";
			if (is_array($dbacl)) {
				foreach ($dbacl as $j => $acl) {
					if ($acl['db_id'] == $db['db_id']) {
						/* Because checkboxes need to be checked, unlike printing a value */
						$value = "checked";
					}
				}
			}
			$main .= "<tr><td>".$db['db_name']."</td><td><input type='checkbox' name='db[".$db['db_id']."]' ".$value."></td></tr>";
		}
		$main .= "</table></td>";
		$main .= "</tr></table>";
	}
	$main .= "</form>";
	return $main;
}

/**
 * showGroup
 *
 * ShowGroup will print all the group information stored in the database.
 *
 * @param array $details               The details to display
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function showGroup($details) {
	$group_id = isset($details['oldID'])?$details['oldID']:$details['group_id'];
	$main .= "<h3>Group: ".$group_id."</h3>";;
	$main .= "<table border=0>";
	$main .= "<tr><td>Group Name:</td><td>".$group_id."</td></tr>";
	$main .= "<tr><td>Description:</td><td>".$details['description']."</td></tr>";
	if ($details['admin'] == "on" || $details['admin'] == "t") {
		$admin = " Yes";
	} else {
		$admin = "No";
	}
	$main .= "<tr><td>Administrator:</td><td>".$admin."</td></tr>";
	$main .= "</table>";
	$main .= "<h3>Group Permissions</h3>";
	if ($details['admin'] == "t") {
		$main .= "This group has administrator priviledges. Thus you do not need additional permsissions for database, table, function or report access,";
	} else {
		$main .= "<table><tr>";
		/* Display the Function ACL */
		$query = Database::buildQuery("select", "function", null, null, "order by function_name");
		$functions = Database::runQuery($query);
		$query = Database::buildQuery("select", "funcacl", array("group_id"=>$group_id, "access"=>"t"));
		$funcacl = Database::runQuery($query);
		$main .= "<tr><td><strong>Function Name</strong></td><td><strong>Access</strong></td></tr>";
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

		/* Display the Table ACL */
		$query = Database::buildQuery("select", "db", null, null, "order by db_id");
		$dbs = Database::runQuery($query);
		$database = Common_Functions::reKeyArray($database, "db_id");
		$query = Database::buildQuery("select", "dbacl", array("group_id"=>$group_id, "access"=>"t"));
		$dbacl = Database::runQuery($query);
		$main .= "<tr><td>&nbsp;</td></tr>";
		$main .= "<tr><td><strong>Database Name</strong></td><td><strong>Access</strong></td></tr>";
		
		foreach ($dbs as $i => $db) {
			$value = "None";
			if (is_array($dbacl)) {
			foreach ($dbacl as $j => $acl) {
				if ($acl['db_id'] == $db['db_id']) {
					$value = "Access";
				}
			}
			}
			$main .= "<tr><td>".$db['db_name']."</td><td>".$value."</td></tr>";
		}
		$main .= "</table></td>";
		$main .= "</tr></table>";
	}
	$main .= "</form>";
	return $main;
}

/**
 * updateGroup
 *
 * POST will only be filled when adding or editting a group (or database). This checks the POST values and updates the database
 * as required.
 *
 * @return string	Either the editGroup screen again, or the showGroup screen, depending on the validity of the results.
 */
function updateGroup() {
	
	if ($_POST['admin'] == "on") {
		$_POST['admin'] = "t";
	} else {
		$_POST['admin'] = "f";
	}
	/* A POST key oldID will only be filled if editting a group. Thus it will be an update in the DB not an insert */
	if ($_POST['oldID']) {
		$setArray = array("group_id"=>$_POST['group_id-val'], "description"=>$_POST['description-val'], "admin"=>$_POST['admin']);
		/* Update the group in the database */
		$query = Database::buildQuery("update", "groups", array("group_id"=>$_POST['oldID']), $setArray);
	} else {
		/* Insert the new group in the database */
		$query = Database::buildQuery("insert", "groups", "", array("group_id"=>$_POST['group_id-val'], "description"=>$_POST['description-val'], "admin"=>$_POST['admin']));
	}
	/* Delete all ACL values for the given group. We do this because we then reinsert everything. This is a poor way of doing it, but
	 * it is much faster to develop and also a smaller POST overhead.
	 */
	$results = Database::runQuery($query);
	$query = Database::buildQuery("delete", "funcacl", array("group_id"=>$_POST['group_id-val']));
	$results = Database::runQuery($query);
	$query = Database::buildQuery("delete", "dbacl", array("group_id"=>$_POST['group_id-val']));
	$results = Database::runQuery($query);
	/* Insert all the functions, tables, and report ACL values */
	foreach ($_POST['functions'] as $i => $func) {
		$query = Database::buildQuery("insert", "funcacl", NULL, array("group_id"=>$_POST['group_id-val'], "function_id"=>$i, "access"=>"t"));
		$results = Database::runQuery($query);
	}
	foreach ($_POST['db'] as $i => $table) {
		$query = Database::buildQuery("insert", "dbacl", NULL, array("group_id"=>$_POST['group_id-val'], "db_id"=>$i, "access"=>"t"));
		$results = Database::runQuery($query);
	}
	$main .= showGroup($_POST);
	return $main;
}

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
	$main .= "<tr><td valign='top'>Groups:</td><td><select multiple='true' name='user_groups[]'>";
	$query = Database::buildQuery("select", "user_groups", array("username"=>$details['username']));
	$inGroups = Database::runQuery($query);
	$inGroups = Common_Functions::reKeyArray($inGroups, "group_id");

	$query = Database::buildQuery("select", "groups", null, null, "order by group_id");
	$allGroups = Database::runQuery($query);
	foreach ($allGroups as $i => $group) {
		if (array_key_exists($group['group_id'], $inGroups)) {
			$selected = " selected";
		} else {
			$selected = "";
		}
		$main .= "<option ".$selected." value='".$group['group_id']."'>".$group['group_id']."</option>";
	}
	$main .= "</select></td></tr>";
	if ($details['oldID'] || $_POST['command'] == 'edit_template') {
		$main .= "<tr><td colspan=2><input type='submit' value='Update User'></td></tr>";
	} else {
		$main .= "<tr><td colspan=2><input type='submit' value='Create New User'></td></tr>";
	}
	$main .= "</table>";
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
	$main .= "<tr><td valign='top'>Groups:</td><td>";
	$query = Database::buildQuery("select", "user_groups", array("username"=>$user_id));
	$reports = Database::runQuery($query);
	if($reports != null) {
		foreach ($reports as $i => $report) {
			$main .= $report['group_id']."<br />";
		}
	}
	$main .= "</td></tr>";
	$main .= "</table>";
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
		$results = Database::runQuery($query);
		$main .= showUser($_POST);
		$query = Database::buildQuery("delete", "user_groups", array("username"=>$_POST['username-val']));
		$results = Database::runQuery($query);
		foreach ($_POST['user_groups'] as $i => $func) {
			$query = Database::buildQuery("insert", "user_groups", NULL, array("username"=>$_POST['username-val'], "group_id"=>$func));
			$results = Database::runQuery($query);
		}
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
		$query = Database::buildQuery("select", "ea_table", array("db_id"=>$details['db_id']), null, "order by db_id, ea_table_name");
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
			$query = Database::buildQuery("select", "ea_column", array("ea_table_id"=>$table['ea_table_id']), null, "order by ea_table_id, ea_column_name");
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
	$query = Database::buildQuery("select", "ea_table", array("db_id"=>$details['db_id']), null, "order by db_id, ea_table_name");
	$tables = Database::runQuery($query);
	$main .= "<h3>Database: ".$details['db_name']."</h3>";
	$main .= "<table border=0>";
	$main .= "<tr><td>PostgreSQL Name:</td><td>".$details['psql_name']."</td></tr>";
	$main .= "<tr><td colspan='5'><hr></td></tr>";
	$main .= "<tr><td><strong>Tables</strong></td><td><strong>Columns</strong></td><td><strong>Foreign Keys</strong></td><td><strong>Select Values</strong></td></tr>";
	foreach ($tables as $i => $table) {
		$main .= "<tr><td>".$table['ea_table_name']."</td>";
		$query = Database::buildQuery("select", "ea_column", array("ea_table_id"=>$table['ea_table_id']), null, "order by ea_table_id, ea_column_name");
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

/**
 * shows all audit information, listing 50 items at a time
 *
 */
function view_audit() {

	$sql = "select * from auditLog ORDER BY time DESC LIMIT 50";
	$page = 1;
	
	if($_GET['page']) {
		$page = (int)$_GET['page'];
		$amount = 50 * ($page - 1);
		$sql .= " OFFSET ".$amount;		
	}

	$results = Database::runQuery($sql);
	
	$output = "<table class='auditlog'>
			<thead>
			<tr>
				<td class='timestamp'>Timestamp</td>
				<td class='user'>User</td>
				<td class='module'>Module</td>
				<td class='action'>Action</td>
				<td class='id'>ID</td>
				<td class='item'>Item</td>
			</tr>
			</thead>";
			
	foreach($results as $row_num => $row) {
		$output .= "<tr>
				<td>".$row['time']."</td>
				<td>".$row['username']."</td>
				<td>".$row['module']."</td>
				<td>".$row['action']."</td>
				<td>".$row['subject_id']."</td>
				<td>".$row['subject_name']."</td>
			     </tr>";
	}
	
	$output .= "</table><br/>";
	
	
	$sql = "select count(*) from auditLog";
	$result = Database::runQuery($sql);
		
	$output .= generate_pageNumbers($result[0]['count'], $page);
	
	return $output;
}

function generate_pageNumbers($total, $this_page=1) {
	$numPages = max(1, (int)($total / 50));
	
	$start = 1;
	$end = 1;
	$output = "<p><a href='?command=view_audit&page=1'>|&lt;</a>&nbsp;&nbsp;";
	
	
	/**
		!! Display 10 page numbers at a time !!
		
		1. If total pages <= 10, display all
		2. If current page < 5, show pages 1-10
		3. If current page > (lastPage - 5), show pages (lastPage - 9) to (lastPage)
		4. Otherwise, show currentPage-4 to currentPage+5
	
	**/
	
	if($numPages <= 10) {
		$start = 1;
		$end = $numPages;
	} else if($this_page <= 5) {
		$start = 1;
		$end = 10;
	} else if($this_page > ($numPages - 5)) {
		$start = $numPages - 9;
		$end = $numPages;
	} else {
		$start = $this_page - 4;
		$end = $this_page + 5;
	}
	
	
	if(($this_page - 10) > 0) {
		$output .= "<a href='?command=view_audit&page=".($this_page - 10)."'>&lt;&lt;</a>&nbsp;&nbsp;";
	} else {
		$output .= "<a href='?command=view_audit&page=1'>&lt;&lt;</a>&nbsp;&nbsp;";
	}
	
	for($counter = $start; $counter <= $end; $counter++) {
		$output .= "<a href='?command=view_audit&page=".$counter."'>".$counter."</a>&nbsp;";
	} 
	
	if($numPages > ($this_page+10)) {
		$output .= "&nbsp;<a href='?command=view_audit&page=".($this_page + 10)."'>&gt;&gt;</a>&nbsp;";
	} else {
		$output .= "&nbsp;<a href='?command=view_audit&page=".$numPages."'>&gt;&gt;</a>&nbsp;";
	}
	
	$output .= "&nbsp;<a href='?command=view_audit&page=".$numPages."'>&gt;|</a></p>";
	return $output;
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
} elseif ($_GET['group_id'] && $_GET['command'] == "delete_template") {
	/* If the confirmation poup was accepted it will delete the group (passed through GET) */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=group&group_id=".$_GET['group_id'];
	$query = Database::buildQuery("delete", "groups", array("group_id"=>$_GET['group_id']));
	$results = Database::runQuery($query);
	$main = "The group '".$_GET['group_id']."' has been deleted from the system.";
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
	$query = Database::buildQuery("select", "db", array("db_id"=>$_GET['database_id']), null, "order by db_name");
	$results = Database::runQuery($query);
	$query = Database::buildQuery("delete", "db", array("db_id"=>$_GET['database_id']));
	$delres = Database::runQuery($query);
	$main = "The database '".$results[0]['db_name']."' has been deleted from the system.";
} elseif ($_POST['group_id-val']) {
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=group&group_id=".$_GET['group_id'];
	$main = updateGroup();
} elseif ($_POST['username-val']) {
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=user&user_id=".$_GET['user_id'];
	$main = updateUser();
} elseif ($_GET['command'] == 'view_audit') {
	$mainicons = false;
	$main = view_audit();	
} elseif ($_POST['db_name']) {
	/* POST will only be filled when adding or editting a database (or user/group). This checks the POST values and updates the database
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
	$query = Database::buildQuery("select", "db", $setArray, null, "order by db_name");
	/* Delete all the tables in the auth database for the given database. We do this because if someone edits a database
	 * we retest for tables in case changes have occured. */
	$database = Database::runQuery($query);
	$query = Database::buildQuery("delete", "ea_table", array("db_id"=>$database[0]['db_id']));
	$results = Database::runQuery($query);
	$_POST['db_id'] = $database[0]['db_id'];
	/* Get the tables in the new database. This is postgres specific. */
	$results = Database::runQuery("select * from information_schema.tables where table_schema='public' and table_name not ilike '%_tmp' order by table_name", $_POST['psql_name']);
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
					if (stripos($sline, $column['column_name']) && stripos($sline, "FOREIGN KEY") && $go == true && $column['column_name'] != 'start_date') {
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
} elseif (($_GET['group_id'] && $_GET['command'] == "edit_template") || ($_GET['admin_type'] == "group")) {
	/* Brings up the edit group page (though the editGroup() function */
	$mainicons = false;
	$query = Database::buildQuery("select", "groups", array("group_id"=>$_GET['group_id']), null, "order by group_id");
	$results = Database::runQuery($query);
	$results[0]['retype_password'] = $results[0]['password'];
	$main .= editGroup($results[0]);
} elseif (($_GET['user_id'] && $_GET['command'] == "edit_template") || ($_GET['admin_type'] == "user")) {
	/* Brings up the edit user page (though the editUser() function */
	$mainicons = false;
	$query = Database::buildQuery("select", "users", array("username"=>$_GET['user_id']), null, "order by username");
	$results = Database::runQuery($query);
	$results[0]['retype_password'] = $results[0]['password'];
	$main .= editUser($results[0]);
} elseif (($_GET['database_id'] && $_GET['command'] == "edit_template") || ($_GET['admin_type'] == "database")) {
	/* Brings up the edit database page (though the editDB() function */
	$mainicons = false;
	/* Quickly updated by Evan. Changed Edit to Edit_Tempalte */
	if ($_GET['command'] == "edit_template") {
		$query = Database::buildQuery("select", "db", array("db_id"=>$_GET['database_id']), null, "order by db_name");
		$results = Database::runQuery($query);
	}
	$main .= editDB($results[0]);
} elseif ($_GET['group_id']) {
	/* Brings up the show group page (though the showGroup() function */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=group&group_id=".$_GET['group_id'];
	$query = Database::buildQuery("select", "groups", array("group_id"=>$_GET['group_id']), null, "order by group_id");
	$results = Database::runQuery($query);
	$main .= showGroup($results[0]);
} elseif ($_GET['user_id']) {
	/* Brings up the show user page (though the showUser() function */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=user&user_id=".$_GET['user_id'];
	$query = Database::buildQuery("select", "users", array("username"=>$_GET['user_id']), null, "order by username");
	$results = Database::runQuery($query);
	$main .= showUser($results[0]);
} elseif ($_GET['database_id']) {
	/* Brings up the show database page (though the showDB() function */
	$mainicons = true;
	$mainiconURL = "admin.php?admin_type=database&database_id=".$_GET['database_id'];
	$query = Database::buildQuery("select", "db", array("db_id"=>$_GET['database_id']), null, "order by db_name");
	$results = Database::runQuery($query);
	$main .= showDB($results[0]);
} else {
	/* This is the entry text for the administration page. It will contain a brief help file. */
	$mainicons = false;
	$main .= "<h3>User administration and access control. </h3>";
	$main .= "<p>The administration functions allows administrator to create new users, control the availability of data through permission limitations and to import new databases for the report templates. Again on the left hand side of the screen you will note two headings. These are Users and Databases.</p>
<h4>Users</h4>
<p>Under this option, the user can see a list of available user profiles or choose to create a new user by clicking <em>New User</em>. Doing so opens the Create New User window.</p>
<p>To create a new user, simply type in a username, a password and then the password again in the fields provided.</p>
<p>Now select the access permissions for the new user by checking the tick boxes of the rights you wish them to have and which data fields you want them to be able to access or modify. Simply check the Admin tick box if you wish to give them full access. Once you are happy with your choices, click the <em>Create New User</em> button.</p>

<h4>Databases</h4>
<p>Again the user can see a list of databases available or create a new one. Clicking the (insert add new database text) text will open up the add database window. To add a new database, fill in the Database name and PostgreSql name fields and then click the <em>Add/Edit database</em> button. </p>
<p>Now all the tables and columns within that database are displayed. Each one (when editing the database) has a <em>Foreign Key</em> texbox and <em>Selectable</em> checkbox. The foreign key field must be in the format of tablename:columnname and allows foreign keys to be specified to aid in the auto joining of tables. If selectable is checked then when that column is selected as a constraint (is only) a drop down box of values will appear, making creating constraints easier.</p>
";
}

$menutitle = "Admin:";
include($conf['Dir']['FullPath']."/html.inc");

?>
