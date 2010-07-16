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
 * search.php
 *
 * The search module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 * 
 */

class Search extends Modules {
	var $current;
	var $id;
	var $module;
	var $action;
	var $subvar;
	var $subid;
	var $dobj;
	var $name = "Search";
	var $description = "Basic report search functionality.";
	var $module_group = "Other";
	
	function hook_pagetitle() {
		return "Seach";
	}

	function hook_style() {

		return "/*#search {
			font-size: 14px;
			width: 100px;*/
		/* 	background: url('".$this->webroot()."themes/".$this->get_theme()."/images/id_search_background.png'); */
		/* 	background-position: right; */
		/* 	background-repeat: no-repeat; */
		/*	background-color: white;
			padding-right: 28px;
			border: 1px solid white;
		}*/

		.search_span {
		}
			.search_span .search_text {
			}
			.search_span .search_form {
				position: relative;
				margin: 0px;
				padding: 0px;
					background-color: green;
			}
				.search_span .search_form .search_input {
					width: 150px;
					height: 23px;
					margin: 0px;
					padding: 3px 5px 0px 5px;
					border: 1px solid #555753;
					vertical-align: middle;
					background-color: #eeeeec;
				}
				/* Clunky search button for IE */
				.search_span .search_form .search_submit {
					vertical-align: middle;
				}
				/* Shiny search button for every browser that isn't crap */
				html>body .search_span .search_form .search_submit {
					width: 31px;
					height: 27px;
					position: absolute;
					left: 122px;
					top: -8px;
					margin: 0px;
					padding: 0px;
					border: 0px;
					background-color: transparent;
					background-image: url('".$this->webroot()."themes/".$this->get_theme()."/images/search_submit_bg.png');
					color: transparent;
					cursor: pointer;
				}";
	}

	function hook_header() {
		return
			"<span class=\"search_span\">".
				"<span class=\"search_text\">search: </span>".
				"<form class=\"search_form\" onSubmit='window.document.location=\"".$this->webroot()."search/home/\"+window.document.getElementById(\"search\").value; return false;'>".
					"<input class=\"search_input\" id='search' type='text' value='' />".
					"<input class=\"search_submit\" type='submit' value='search' />".
				"</form>".
			"</span>";
	}

	function hook_workspace() {
		return array("title"=>"Search Workspace", "path"=>"".$this->webroot()."search/workspace_display/".$this->id);
	}
	
	function __construct() {
		include_once("inc/db.php");
		$this->dobj = new DB();
		$url = explode("/", $_REQUEST['url']);
		if (array_key_exists(2, $url)) {
			$this->id = $url[2];
		}
		if (array_key_exists(3, $url)) {
			$this->subvar = $url[3];
		}
		if (array_key_exists(4, $url)) {
			$this->subvar = $url[4];
		}
		$this->module = $url[0];
		$this->action = $url[1];
	}
	
	/* The Top Menu hook function. 
	 * Displays the module in the main menu. Or menu of primary functions. 
	 */
	function hook_top_menu() {
		return array(
			"search" => $this->l("search/home", "Search", "class='disabled'")
			);
	}

	function view_home() {
		$modules = $this->call_function("ALL", "hook_workspace");
		return Search_View::view_home($modules);
	}

	function view_workspace_display() {
		$search_string = $this->id;
		return Search_View::view_workspace_display($search_string);
	}
}

class Search_View {
	/**
	* Just like the view_home function in workspace.php that's used to create the front page, Except this one is called by the topmenu search. Takes all the module workspaces and puts the search workspace first. Thus, the search results are automatically shown.
	*/
	function view_home($modules) {
		$output->data = "<div id='workspace_container' dojoType='dijit.layout.TabContainer' style='height: 100%;'>";

		//place the search workspace first
		$search_module = $modules['search'];
		unset($modules['search']);
		array_unshift($modules, $search_module);

		foreach ($modules as $i => $module) {
			$output->data .= "<div href='".$module['path']."' dojoType='dijit.layout.ContentPane' title='".$module['title']."' style='width:100%; height:100%;'></div>";
		}
		$output->data .= "</div>";
		return $output;
	}

	function view_workspace_display($search_string) {
		$output->layout = "ajax";
		$output->report_title = "Search Workspace";
		$output->data = "<div dojoType='dojox.layout.ContentPane' layoutAlign='client' id='search_workspace'>";

		if (!empty($search_string)) $search_array = explode(" ", $search_string);

		//search input for re-searching
		$output->data = "
			<p><span class=\"search_span\">
				<form class=\"search_form\" onSubmit='window.document.location=\"".$this->webroot()."search/home/\"+window.document.getElementById(\"search_again\").value; return false;'>
					<input class=\"search_input\" id='search_again' type='text' value='".$search_string."' />
					<input class=\"search_submit\" type='submit' value='search' />
				</form>
			</span></p>
			";

		if (!empty($search_array)) {
			//matching report name or description
			$match_name_query = "SELECT * FROM templates WHERE name ILIKE '%".implode("%' OR name ILIKE '%", $search_array)."%' OR description ILIKE '%".$search_string."%';";
			$match_name_query = $this->dobj->db_fetch_all($this->dobj->db_query($match_name_query));
		}

		if (!empty($match_name_query) && !empty($search_array)) {
			foreach ($match_name_query as $match_name_tmp) {
				//name/description matches rated higher: come first in list
				$rating = 400;

				foreach ($search_array as $search_tmp) {
					//highest rating for exact name matches
					if ($match_name_tmp['name'] == $search_tmp) {
						$rating += 5;
					//lower rating when start of name matches search term
					} else if (strpos($match_name_tmp['name'], $search_tmp) === 0) {
						$rating += 4;
					//lower rating for partial name matches
					} else {
						$rating += 3;
					}
		
					//highest rating for exact description matches
					if ($match_name_tmp['description'] == $search_tmp) {
						$rating += 2;
					//lower rating when start of description matches search term
					} else if (strpos($match_name_tmp['description'], $search_tmp) === 0) {
						$rating += 1;
					//lower rating for partial description matches
					} else {
						$rating += 0;
					}
				}
	
				//place the report details in an array, so we can render it later
				$result_array[$match_name_tmp['template_id']] = $match_name_tmp;
	
				//place the the rating in an array, so we know what order to render reports in
				$rating_array[$match_name_tmp['template_id']] += $rating;
			}
		}


		if (!empty($search_array)) {
			//matching columns (in list reports) reported on
			$match_column_list_query = "SELECT t.*, c.name as column_name FROM templates t INNER JOIN list_templates lt ON (t.template_id=lt.template_id) INNER JOIN columns c ON (lt.column_id=c.column_id) WHERE c.name ILIKE '%".implode("%' OR c.name ILIKE '%", $search_array)."%';";
			$match_column_list_query = $this->dobj->db_fetch_all($this->dobj->db_query($match_column_list_query));
	
			//matching columns (in tabular reports) reported on
			$match_column_tabular_query = "SELECT t.*, c.name as column_name FROM templates t INNER JOIN tabular_templates tt ON (t.template_id=tt.template_id) INNER JOIN columns c ON (tt.column_id=c.column_id) WHERE c.name ILIKE '%".implode("%' OR c.name ILIKE '%", $search_array)."%';";
			$match_column_tabular_query = $this->dobj->db_fetch_all($this->dobj->db_query($match_column_tabular_query));
	
			//merge list and tabular reports, so we can loop through them
			$match_column_query = array_merge((array)$match_column_list_query, (array)$match_column_tabular_query);
		}

		if (!empty($match_column_query)) {
			foreach ($match_column_query as $match_column_tmp) {
				//disregard empty values created by the array_merge
				if (empty($match_column_tmp)) continue;
	
				//column matches rated lower than name/desciption matches
				$rating = 200;
	
				foreach ($search_array as $search_tmp) {
					//highest rating for exact column name matches
					if ($match_column_tmp['column_name'] == $search_tmp) {
						$rating += 2;
					//lower rating when start of column name matches search term
					} else if (strpos($match_column_tmp['column_name'], $search_tmp) === 0) {
						$rating += 1;
					//lower rating for partial column name matches
					} else {
						$rating += 0;
					}
				}
	
				//place the report details in an array, so we can render it later
				$result_array[$match_column_tmp['template_id']] = $match_column_tmp;
	
				//place the the rating in an array, so we know what order to render reports in
				$rating_array[$match_column_tmp['template_id']] += $rating;
			}
		}

		if (!empty($search_array)) {
			//matching constraints (in list reports) reported on
			$match_const_list_query = "SELECT t.*, c.name as column_name FROM templates t INNER JOIN list_constraints lc ON (t.template_id=lc.template_id) INNER JOIN columns c ON (lc.column_id=c.column_id) WHERE c.name ILIKE '%".implode("%' OR c.name ILIKE '%", $search_array)."%';";
			$match_const_list_query = $this->dobj->db_fetch_all($this->dobj->db_query($match_const_list_query));
	
			//matching constraints (in tabular reports) reported on
			$match_const_tabular_query = "SELECT t.*, c.name as column_name FROM templates t INNER JOIN tabular_constraints tc ON (t.template_id=tc.template_id) INNER JOIN columns c ON (tc.column_id=c.column_id) WHERE c.name ILIKE '%".implode("%' OR c.name ILIKE '%", $search_array)."%';";
			$match_const_tabular_query = $this->dobj->db_fetch_all($this->dobj->db_query($match_const_tabular_query));
	
			$match_const_query = array_merge((array)$match_const_list_query, (array)$match_const_tabular_query);
		}

		if (!empty($match_const_query)) {
			foreach ($match_const_query as $match_const_tmp) {
				//disregard empty values created by the array_merge
				if (empty($match_const_tmp)) continue;
	
				//constraint matches rated lowest
				$rating = 100;
	
				foreach ($search_array as $search_tmp) {
					//highest rating for exact constraint column name matches
					if ($match_const_tmp['column_name'] == $search_tmp) {
						$rating += 2;
					//lower rating when start of constraint column name matches search term
					} else if (strpos($match_const_tmp['column_name'], $search_tmp) === 0) {
						$rating += 1;
					//lower rating for partial constraint column name matches
					} else {
						$rating += 0;
					}
				}
	
				//place the report details in an array, so we can render it later
				$result_array[$match_const_tmp['template_id']] = $match_const_tmp;
	
				//place the the rating in an array, so we know what order to render reports in
				$rating_array[$match_const_tmp['template_id']] += $rating;
			}
		}

		if (empty($search_string)) {
		} else if (empty($rating_array) || empty($result_array)) {
			$output->data .= "<p>No reports found.</p>";
		} else {
			$output->data .= "
				<table class=\"report_table\" cellspacing=\"0px\" cellpadding=\"0px\">
				";

			//sort ratings: higher rated results first
			arsort($rating_array);

			foreach ($rating_array as $rating_key => $rating_tmp) {
				//get the matching result from the result array
				$report = $result_array[$rating_key];

				//render the report
				unset($view_id);

				$output->data .= Template_View::theme_reports($report, $view_id);
			}

			$output->data .= "
				</table>
				";
		}

		$output->data .= "</div>";

		return $output;
	}
}

?>
