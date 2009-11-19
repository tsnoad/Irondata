<?php

/**
 * index.php
 *
 * Called by the browser; instantiates the software, passing in state-tracking
 * variables via the GET string (how tiresome!). Handles basic authentication,
 * instantiation of the core object, and output buffering (saving output from
 * other portions of the software, and putting it into a debug div).
 *
 * @author Andy White
 * @date 19-07-2006
 * 
 */


require_once "ezc/Base/base.php"; 
/**
 * autoload
 *
 * Automatically loads an object. Eventually, this will be used to auto-load modules.
 *
 * @param 	string		class_name		Name of class to be loaded
 *
 */
function __autoload($class_name) {
	if (is_file($class_name . '.php')) {
		require_once $class_name . '.php';
	}
	if (strpos($class_name, "ezc") === 0) {
		ezcBase::autoload( $class_name );
	}
}

$conf = parse_ini_file("../conf.ini", true);

include($conf['Dir']['FullPath']."/common.php");
include_once("Common_Functions.php");
include_once("database.php");

//$database = new Database();
session_start();
ob_start();
Database::logon();
include("error.php");
include("generator.php");

$core = new Core();
$main = $core->main();

$errors = ob_get_contents();
ob_end_clean();
include($conf['Dir']['FullPath']."/html.inc");

?>

