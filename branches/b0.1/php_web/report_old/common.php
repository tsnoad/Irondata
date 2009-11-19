<?php

$_POST = array_merge($_POST, $_GET);
$start_time = time();

$conf = parse_ini_file("../conf.ini", true);
include('rules.php');
include('database.php');

function write_ini_file($path, $assoc_array) {

        foreach ($assoc_array as $key => $item) {
                if (is_array($item)) {
                        $content .= "\n[$key]\n";
                        foreach ($item as $key2 => $item2) {
                                $content .= "$key2 = \"$item2\"\n";
                        }
                } else {
                        $content .= "$key = \"$item\"\n";
                }
        }

        if (!$handle = fopen($path, 'w')) {
                return false;
        }
        if (!fwrite($handle, $content)) {
                return false;
        }
        fclose($handle);
        return true;
}


function adminSidebar() {
	$query = Database::buildQuery("select", "groups", null, null, "order by group_id");
	$groups = Database::runQuery($query);
	foreach ($groups as $i => $rule) {
		$group[] = "<a href='?group_id=".$rule['group_id']."'>".ucwords($rule['group_id'])."</a>";
	}
	$query = Database::buildQuery("select", "users", null, null, "order by username");
	$users = Database::runQuery($query);
	foreach ($users as $i => $rule) {
		$user[] = "<a href='?user_id=".$rule['username']."'>".ucwords($rule['username'])."</a>";
	}
	$query = Database::buildQuery("select", "db", null, null, "order by db_name");
	$databases = Database::runQuery($query);
	if (is_array($databases)) {
		foreach ($databases as $i => $rule) {
			$database[] = "<a href='?database_id=".$rule['db_id']."'>".ucwords($rule['db_name'])."</a>";
		}
	}
	if (is_array($group)) { $group = implode("<br/>", $group)."<br/>"; }
	if (is_array($user)) { $user = implode("<br/>", $user)."<br/>"; }
	if (is_array($database)) { $database = implode("<br/>", $database)."<br/>"; }
	$sideString .= "<b>Groups</b><br/>";
	$sideString .= "<a href='?admin_type=group'>New Group</a><br/>";
	$sideString .= $group."<br/>";
	$sideString .= "<b>Users</b><br/>";
	$sideString .= "<a href='?admin_type=user'>New User</a><br/>";
	$sideString .= $user."<br/>";
	$sideString .= "<b>Database</b><br/>";
	$sideString .= "<a href='?admin_type=database'>Add database</a><br/>";
	$sideString .= $database."<br/>";
	$sideString .= "<b>Audit Log</b><br/>";
	$sideString .= "<a href='?command=view_audit'>View Audit Logs</a><br/>";
	return $sideString;
}

/**
 * reportSidebar
 * 
 * Generates the HTML code for the sidebar (reports). It will iterate through all available reports and display
 * them by report type. 
 *
 * This respects the function ACL.
 *
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function reportSidebar() {
	$mapString = "";
	$sideString .= "<strong>Data Mart</strong><br/>";
	$sideString .= "<form method='post' action='?command=changedb'>";
	foreach ($_SESSION['dbs'] as $i => $db) {
		#check if they have permission to see any of the tables in this database
		if ($_SESSION['curDB'] == $db['db_id']) {
			$selected = " selected";
		/*	$mapString = "<p><a href=\"maps/".$db['psql_name'].".png\" target=\"_blank\">Map</a></p>\n";*/
			$mapString = "<p><a href=\"index.php?show_map=true\">Map</a></p>\n";
		} else {
			$selected = "";
		}
		$op .= "<option ".$selected." value='".$db['db_id']."'>".$db['db_name']."</option>";
	}
	$sideString .= "<SELECT NAME='changeDB' onchange='this.form.submit()'><OPTION value='0'>None</OPTION>".$op."</SELECT>";
	$sideString .= "</form>";
	$sideString .= $mapString;
	if ($_SESSION['unpublished'] == true) {
		$sideString .= "<a href=\"javascript:userCheck('index.php?".$_SERVER['QUERY_STRING']."&hide_unpub=true', 'You will lose all unsaved work if you continue.')\">Hide Unpublished Reports</a><br/><br/>";
	} else {
		$sideString .= "<a href=\"javascript:userCheck('index.php?".$_SERVER['QUERY_STRING']."&show_unpub=true', 'You will lose all unsaved work if you continue.')\">Show Unpublished Reports</a><br/><br/>";
	}
	$modules = scandir('./Modules', 1);
		
	foreach($modules as $index => $module) {
		if(!preg_match("/^\./", $module)) {
			$mod_obj = Common_Functions::loadModule($module);
			$sideString .= $mod_obj->buildSidebar();
		}
	}
	
	return $sideString;
}

?>
