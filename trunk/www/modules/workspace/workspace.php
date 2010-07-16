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
 * workspace.php
 *
 * The workspace module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 * 
 */

class Workspace extends Modules {
	var $dobj;
	var $name = "Workspace";
	var $description = "The users personal workspace";
	var $current;
	var $module_group = "Core";
	
	function __construct() {
		include_once("inc/db.php");
		$this->dobj = new DB();
	}

	function hook_style() {
		return "div.functions {
			border: 1px dashed black;
			float:right;
			height:200px;
			position:relative;
			width:140px;
			padding: 5px;
		}

		div.report_div {
			color:#555753;
			cursor:default;
			display:inline;
			float:left;
			font-family:Verdana,Arial,'sans serif';
			font-size:10px;
			margin:10px;
			position:relative;
			width:144px;
			z-index:0;
			border: 1px dashed #333;
			background: white;
		}

		div.report_div.draft {
			background: lightgrey;
		}

		div.report_div div.title {
		/* 	background-color:white; */
			height:16px;
			position:relative;
			width:144px;
		}

		div.report_div div.links {
		/* 	background-color:white; */
			padding:2px 8px;
			position:absolute;
			right:0;
			Xtop:0;
			float: right;
		}

		div.report_div div.title .filename {
			height:12px !important;
			left:0;
			overflow:hidden;
			padding:2px 8px;
			Xposition:absolute;
			top:0;
		}

		div.report_div div.icon {
		/* 	background-color:white; */
			height:70px;
			position:relative;
			width:144px;
		}

		div.report_div div.icon a {
			padding: 2px 8px;
		}

		div.report_div div.report_info {
		/* 	background-color:white; */
			height:175px;
			position:relative;
			overflow: hidden;
			width:144px;
		}

		div.report_div div.report_info div.details {
			padding: 2px 8px;
		}
		div.report_div div.report_info div.details span.label {
			font-weight: bold;
			display: block;
		}

		";
	}
	
	function hook_pagetitle() {
		return "Workspace";
	}
	
	function hook_top_menu() {
		return null;
	}

	function view_home() {
		$this->redirect("template/home");
// 		$modules = $this->call_function("ALL", "hook_workspace");
// 		return Workspace_View::view_home($modules);
	}
}

class Workspace_View {
// 	function view_home($modules) {
// 		$output->data = "<div id='workspace_container' dojoType='dijit.layout.TabContainer' style='height: 100%;'>";
// 		foreach ($modules as $i => $module) {
// 			$output->data .= "<div href='".$module['path']."' dojoType='dijit.layout.ContentPane' title='".$module['title']."' style='height:100%;'></div>";
// 		}
// 		$output->data .= "</div>";
// 		return $output;
// 	}
}

?>
