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
 * croncall.php
 *
 * Called by crontab every minute. When run as scheduler, this page calls for any reports that are set to be run at this date and time, then adds them to the execution queue. When run as executor, this page calls the cron module to execute reports that are queued
 *
 */
if (!isset($_SERVER['SHELL'])) {
	die("croncall may only be run by CLI");
}
if ($argv[1] != "scheduler" && $argv[1] != "executor") die("croncall must be run as either scheduler or executor.\n");

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
		$run_executor = isset($return_tmp['cron']) ? $return_tmp['cron'] : null;
		break;
	default:
		break;
}

chdir($cwd_cache);

if (isset($run_executor)) {
	shell_exec("php -f /var/www/irondata/croncall.php executor");
}

exit();

?>
