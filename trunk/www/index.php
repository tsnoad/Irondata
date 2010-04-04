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
 * index.php
 *
 * Called by the browser; instantiates the software, passing in state-tracking
 * variables via the GET string (how tiresome!). Handles basic authentication,
 * instantiation of the core object, and output buffering (saving output from
 * other portions of the software, and putting it into a debug div).
 *
 * @author Evan Leybourn
 * @date 19-07-2006
 * 
 */

session_start();
include("inc/db.php");
include("inc/theme.php");
include("inc/modules.php");
include("inc/security.php");

/* Include Core Modules */
include("modules/admin/admin.php");
include("modules/catalogue/catalogue.php");
include("modules/template/template.php");

/* Clean up input data */
$so = new Security();
$so->sanitise();

$url = explode("/", $_REQUEST['url']);
if (!$url[0]) {
	$url[0] = 'workspace';
	$url[1] = 'home';
}

//calling the cron module is not allowed from index.php, as it bypasses authentication.
if ($url[0] == "cron") {
	exit();
}

$mo = new Modules();
$mo->call_function("ALL", "hook_auth");

/* Display */
if ($url[0]) {
	$main = $mo->call_function($url[0], "view_".$url[1]);
	if ($main[$url[0]]->layout != "ajax") {
		$js = $mo->call_function($url[0], "hook_javascript");
		$display->js = $js[$url[0]];
		$js_href = $mo->call_function($url[0], "hook_javascript_href");
		$display->js_href = $js_href[$url[0]];
// 		$menu = $mo->call_function($url[0], 'hook_menu');
// 		$display->menu = $mo->render_menu($menu[$url[0]]);
		$pagetitle = $mo->call_function($url[0], 'hook_pagetitle');
		$display->pagetitle = $pagetitle[$url[0]];
	}
}

if ($main[$url[0]]->layout != "ajax") {
	/* Always call hooks */
	/* top_menu hook */
	$display->top = $mo->render_top_menu($mo->call_function("ALL", "hook_top_menu"));
}

$bottom = '';
$display->style = implode(" ", $mo->call_function("ALL", "hook_style"));
$display->header = implode(" ", $mo->call_function("ALL", "hook_header"));
$mo->render($display, $main[$url[0]]);

exit();

?>
