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
 * help.php
 *
 * The help module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 * 
 */

class Help extends Modules {
	var $dobj;
	var $name = "Help";
	var $description = "Help topics";

	var $topics = array(
		"reports.html" => array("Generating & Running Reports", "Guide to using reports.", "help_files/reports.html"),
		"tabular.html" => array("Tabular Reports", "Tabular reports primer.", "help_files/tabular.html"),
		"nomenclature.html" => array("Nomenclature", "", "help_files/nomenclature.html"),
		"installation.html" => array("Installation", "Step by step guide to installing Irondata.", "help_files/installation.html")
		);
	
	function __construct() {
		include_once("inc/db.php");
		$this->dobj = new DB();
	}
	
	function hook_pagetitle() {
		return "Help";
	}
	
	function hook_top_menu() {
		return array(
			"help" => $this->l("help/topics", "Help")
			);
	}

	function view_topics() {
		return Help_View::view_topics($this->topics);
	}
}

class Help_View {
	function view_topics($topics) {
		$output->title = "Help Topics";

		$output->data .= "
			<ul style='margin: 20px 0px; padding: 0px; list-style-type: none;'>
			";

		foreach ($topics as $topic) {
			$title = $topic[0];
			$description = $topic[1];
			$path = $topic[2];

			$output->data .= "<li style='margin: 10px 0px; padding: 0px;'><span style='font-size: 12pt;'>".$this->l($path, $title, "target='_blank'")."</span> <span style='padding-left: 20px; color: #888a85; font-size: 10pt;'>$description</span></li>";
		}

		$output->data .= "
			</ul>
			";

		return $output;
	}
}
