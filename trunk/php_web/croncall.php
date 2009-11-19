<?php

/**
 * croncall.php
 *
 * fop?
 */

if ($argv[1] != "scheduler" && $argv[1] != "executor") die("croncall must be run as either scheduler or executor.\n");

$runcheck = shell_exec("ps aux | grep 'su - www-data -c php -f /var/www/irondata/croncall.php' | grep -v 'grep'");
if (!empty($runcheck)) $runcheck = trim($runcheck);
if (!empty($runcheck)) $runcheck = explode("\n", $runcheck);
$runcheck = count($runcheck);
if ($runcheck < 1) die("croncall may only be run by CLI");

if ($argv[1] == "executor") {
	$runcheck = shell_exec("ps aux | grep 'su - www-data -c php -f /var/www/irondata/croncall.php executor' | grep -v 'grep'");
	if (!empty($runcheck)) $runcheck = trim($runcheck);
	if (!empty($runcheck)) $runcheck = explode("\n", $runcheck);
	$runcheck = count($runcheck);
	if ($runcheck > 1) die("executor is already running");
}

session_start();

$croncall_path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], "croncall.php"));

$cwd_cache = getcwd();

chdir($croncall_path);

$_SESSION['skip_auth'] = true;

include("inc/db.php");
include("inc/theme.php");
include("inc/modules.php");
include("inc/security.php");

/* Include Core Modules */
include("modules/admin/admin.php");
include("modules/catalogue/catalogue.php");
include("modules/template/template.php");

$mo = new Modules();

switch ($argv[1]) {
	case "scheduler":
		$mo->module = "cron";
		$mo->action = "scheduler";

		$mo->call_function($mo->module, "view_".$mo->action);
		break;
	case "executor":
		$mo->module = "cron";
		$mo->action = "executor";

		$return_tmp = $mo->call_function($mo->module, "view_".$mo->action);
		$run_executor = $return_tmp['cron'];
		break;
	default:
		break;
}

chdir($cwd_cache);

var_dump($run_executor);

if ($run_executor) {
	shell_exec("php -f /var/www/irondata/croncall.php executor");
}

exit();

?>
