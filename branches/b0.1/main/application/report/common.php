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
	$query = Database::buildQuery("select", "users");
	$users = Database::runQuery($query);
	foreach ($users as $i => $rule) {
		$user[] = "<a href='?user_id=".$rule['username']."'>".ucwords($rule['username'])."</a>";
	}
	$query = Database::buildQuery("select", "db");
	$databases = Database::runQuery($query);
	if (is_array($databases)) {
		foreach ($databases as $i => $rule) {
			$database[] = "<a href='?database_id=".$rule['db_id']."'>".ucwords($rule['db_name'])."</a>";
		}
	}
	if (is_array($user)) { $user = implode("<br/>", $user)."<br/>"; }
	if (is_array($database)) { $database = implode("<br/>", $database)."<br/>"; }
	$sideString .= "<span><a href='?admin_type=user'>New User</a></span><br/>";
	$sideString .= $user."<br/>";
	$sideString .= "<span><a href='?admin_type=database'>Add database</a></span><br/>";
	$sideString .= $database."<br/>";
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
	
	$sideString .= "<div dojoType='ContentPane' open='true' label='Reports'>";
	$sideString .= "<span><a href='index.php'>Reports Home</a></span> <br/>";
	$modules = scandir('./Modules', 1);
	/*
	$sideString .= "<strong>database: </strong>";
	$sideString .= displayDatabases();
	$sideString .= "<br/>";
	*/
	foreach($modules as $index => $module) {
		if(!preg_match("/^\./", $module)) {
			$sideString .= "<span><a href='?command=new&report_type=".$module."&new=true'>New ".ucwords($module)." Report</a></span><br/>";
			/*
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create/Edit Raw SQL']['access'] == "t") {
				$sideString .= " <a href='?command=new&report_type=".$module."&sql=true'><img onmouseover='javascript:this.src=\"images/new_hover.png\";' onmouseout='javascript:this.src=\"images/new.png\";' border='0' src='images/new.png' alt='New' title='New'> New ".ucwords($module)." SQL Report</a><br/>";
			}
			*/
		}
	}
	$sideString .= '</div>';
	if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Administration Functions']['access'] == "t") {
		$sideString .= "<div dojoType='ContentPane' label='Settings'>";
		$sideString .= adminSidebar();
		$sideString .= '</div>';
	}
	$sideString .= "<div dojoType='ContentPane' label='Analysis'>";
	$sideString .= '<p>This functionality has not yet been implimented.</p>';
	$sideString .= '</div>';
	$sideString .= "<div dojoType='ContentPane' label='Designer'>";
	$sideString .= '<p>This functionality has not yet been implimented.</p>';
	$sideString .= '</div>';
	$sideString .= "<div dojoType='ContentPane' label='Extraction'>";
	$sideString .= '<p>This functionality has not yet been implimented.</p>';
	$sideString .= '</div>';
	$sideString .= "<div dojoType='ContentPane' label='Help'>";
	$sideString .= "<span><a href='index.php?command=help'>Report Help</a></span><br/>";
	$sideString .= "<span><a href='index.php?command=adminhelp'>Settings Help</a></span><br/>";
	$sideString .= '</div>';

	return $sideString;
}
/**
 * showChange
 * 
 * Displays the change database page.
 *
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function displayDatabases() {
	$main .= "<form name='dbChange' method='GET' >";
	$query = Database::buildQuery("select", "db");
	$dbs = Database::runQuery($query);
	/* Admins can see all databases regardless of ACL */
	if ($_SESSION['admin'] == "t") {
		$availDB = $dbs;	
	} else {
		foreach ($_SESSION['tables'] as $i => $table) {
			foreach ($dbs as $j => $db) {
				if ($db['db_id'] == $table['db_id']) {
					/* Databases ACL is based on table ACL. $j will be the same for each identical database id.  */
					$availDB[$j] = $db;
					break;
				}
			}
		}
	}
	/* This is the raw text and HTML. */
	$main .= "<select name='db' onchange='document.dbChange.submit()'>";
	$main .= "<option value='' selected>-None-</option>";
	foreach ($availDB as $i => $db) {
		$selected = "";
		if ($db['db_id'] == $_SESSION['curDB']) {
			$selected = " selected";
		}
		$main .= "<option value=".$db['db_id']." ".$selected.">".$db['db_name']."</option>";
	}
	$main .= "</select>";
	$main .= "</form>";
	return $main;
}


?>
