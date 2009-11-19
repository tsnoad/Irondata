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
		"install.html" => array("Installation", "Step by step guide to installing Irondata.", "help/install.html")
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
			"help" => "<a href='".$this->webroot()."help/topics' class='disabled'>Help</a>"
			);
	}

	function view_topics() {
		return Help_View::view_topics($this->topics);
	}
}

class Help_View {
	function view_topics($topics) {
		$output->Title = "Help Topics";

		$output->data .= "
			<ul>
			";

		foreach ($topics as $topic) {
			$title = $topic[0];
			$description = $topic[1];
			$path = $topic[2];

			$output->data .= "<li>".$this->l($path, $title)." $description</li>";
		}

		$output->data .= "
			</ul>
			";

		return $output;
	}
}