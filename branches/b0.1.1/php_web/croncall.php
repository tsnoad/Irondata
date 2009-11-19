<?php

/**
 * croncall.php
 *
 * fop?
 */

session_start();

$croncall_path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], "croncall.php"));

$cwd_cache = getcwd();

chdir($croncall_path);

include("inc/db.php");
include("inc/theme.php");
include("inc/modules.php");
include("inc/security.php");

/* Include Core Modules */
include("modules/admin/admin.php");
include("modules/catalogue/catalogue.php");
include("modules/template/template.php");

$mo = new Modules();

$mo->croncall_path = $croncall_path;

$mo->module = "cron";
$mo->action = "check_cron";

// $mo->call_function("ALL", "hook_auth");
session_start();

if ($mo->module) {
	$main = $mo->call_function($mo->module, "view_".$mo->action);
}

chdir($cwd_cache);

exit();

?>
