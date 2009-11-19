<?php

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

			$output->data .= "<li style='margin: 10px 0px; padding: 0px;'><span style='font-size: 12pt;'>".$this->l($path, $title)."</span> <span style='padding-left: 20px; color: #888a85; font-size: 10pt;'>$description</span></li>";
		}

		$output->data .= "
			</ul>
			";

		return $output;
	}
}