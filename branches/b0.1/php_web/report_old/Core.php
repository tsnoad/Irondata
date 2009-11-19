<?php

/**
 * Core.php
 *
 * Contains the Core object.
 *
 * @author Andrew White
 * @package Core
 * @version 1.2
 * @date 28-07-2006
 */

/**
 * The Core object is the heart of the report generator. It calls other modules and generates
 * response HTML and XML to send to the user.
 *
 * @author Andrew White
 * @package Core
 * @version 1.2
 * @date 28-07-2006
 */

class Core extends Common_Functions {
	
	/**
	 * This will load the approprate saveReport function for each report type.
	 *
	 * @return string	The passed through HTML.
	 */
	function saveReports() {
		$module = $this->loadModule($_GET['report_type']);
		if($module->saveReport($_GET['saved_report'], $_GET['suffix'], $_SESSION['reports'][$_GET['saved_report']]['report_name']) == true) {
			$main .= $module->displayReport($_GET['saved_report']);
		} 
		
		return $main;
	}
	
	/**
	 * This will load the approprate displayReport function for each report type.
	 *
	 * @return string	The passed through HTML.
	 */
	function displayReports() {
		$module = $this->loadModule($_GET['report_type']);
		$main = $module->displayReport($_GET['saved_report'], $_GET['id']);
		return $main;
	}
	
	/**
	 * This will load the approprate deleteReport function for each report type.
	 *
	 * @return string	The passed through HTML.
	 */
	function deleteReports() {
		$module = $this->loadModule($_GET['report_type']);
		$main = $module->deleteReport($_GET['saved_report'], $_GET['id']);
		return $main;
	}
	
	/**
	 * This will load the approprate makeRule function for each report type. If the report was valid
	 * it will return true otherwise false. If it is false it will print an error message to the screen.
	 *
	 * @return string	An error message (if any)
	 */
	function saveRules() {
		global $conf;
		$module = $this->loadModule($_GET['report_type']);
		$rules = $module->makeRules($_POST);
		if ($rules == false) {
			$main = "That report template was invalid. Possible reasons include; <br/><ul><li>An SQL query that starts with something other than \"select\"</li></ul><br/>";
		} else {
			$report = addRule($_REQUEST['saved_report'], $rules, $_REQUEST['report_type'], $_REQUEST['publish_report']);
			if(!$report) {
				$main = "The report was not added. The report name is probably already taken.";
			} else {
				$_GET['saved_report'] = $report;
			}
		}
		return $main;
	}
	
	/**
	 * This will load the approprate showRules function for each report type.
	 *
	 * @return string	The passed through HTML.
	 */
	function editRules($rules=NULL) {
		$module = $this->loadModule($_GET['report_type']);
		$main = $module->showRules($rules);
		return $main;
	}
	
	/**
	 * Runs the report, and presents the output to the user.
	 *
	 * @return string HTML output from the report generator.
	 */
	
	function displaySavedReport() {
		$rules = getRules($_GET['saved_report']);
		if ($_GET['run']=='true') {
			if ($this->authCheck('functions', 'Run Report Templates', 'access') || $this->authCheck('reports', $_GET['saved_report'], 'owner')) {
				$module = $this->loadModule($_GET['report_type']);
				$main = $module->runReport($rules);
			} else {
				return $this->denied();
			}
		} else {
			$module = $this->loadModule($_GET['report_type']);
			$main = $module->displayReport($_SESSION['current_report']);
		}
		
		return $main;
	}
		
	
	/**
	 * This iterates through all the available options from the reports (index.php) 
	 * page. If an option is not in this this or the user doesn't have permission to 
	 * perform the action they will be directed to a permission denied screen.
	 *
	 * @global string $maintitle
	 * @global string $mainicons
	 * @global string $mainiconURL
	 * @return string 	The HTML to be displayed to the user.
	 */
	
	function main() {
		global $maintitle;
		global $mainicons;
		global $mainiconURL;
		$main = "";
		if (!$_GET) {
			include("help.php");
			$main = $help;
			$maintitle = "Welcome ".$_SESSION['displayname']."";
			$mainicons = false;
		} else {
			if ($_REQUEST['show_unpub']) {
				$_SESSION['unpublished'] = true;
				include("help.php");
				$main = $help;
				$maintitle = "Welcome ".$_SESSION['displayname']."";
				$mainicons = false;
				return $main;
			} 
			if ($_REQUEST['hide_unpub']) {
				$_SESSION['unpublished'] = false;
				include("help.php");
				$main = $help;
				$maintitle = "Welcome ".$_SESSION['displayname']."";
				$mainicons = false;
				return $main;
			}
			if ($_REQUEST['show_map']) {
				foreach ($_SESSION['dbs'] as $i => $db) {
        			        #check if they have permission to see any of the tables in this database
			                if ($_SESSION['curDB'] == $db['db_id']) {
						$main = '<img src="maps/'.$db['psql_name'].'.png" />';
						$maintitle = $db['db_name'];
					}
				}
				/*ob_start();echo "<pre>\n";print_r($_SESSION['dbs']);echo "\n</pre>\n";$main = ob_get_contents();ob_end_clean();
				$maintitle = "i 4m a 1337 h4Xx0rz!!1";*/
				$mainicons = false;
				return $main;
			} 
			$report_name = $_SESSION['report'][$query]['report_name'];
			/* There is a possiblity of a URL hack attack, ie guess at report_id.
			 * This will be fixed later
			 */
			switch($_REQUEST['command']) {
				case 'changedb':
					// Change the current source database
					$maintitle = "New Current Database";
					$mainicons = false;
					$main = makeChange();
				break;
				case 'save_template':
					// Save a trend line currently being edited
					if($this->authCheck('functions','Create Report Templates','access') || $this->authCheck('reports', $_GET['saved_report'], 'owner')) {
						$mainicons = true;
						$maintitle = 'Report: ' . $_POST['save_as'];
						$saverules = $this->saveRules();
						$main .= $saverules;
						if($saverules=='') {
							$main .= "<h3>Template saved successfully.</h3>";
						}
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'save_report':
					// Save the results of a database query, ie. the output
					if($this->authCheck('functions','Run Report Templates','access') || $this->authCheck('reports', $_GET['saved_report'], 'owner')) {
						$mainicons = true;
						$maintitle = 'Report: ' . $report_name;
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
						$main .= $this->saveReports();
						$main .= "<h3>Report saved successfully.</h3>";
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'view_report':
					// View a saved query; exposes the menu to edit the query, run it,
					// and displays saved query results
					$maintitle = 'Report: ' . $_SESSION['reports'][$_GET['saved_report']]['report_name'];
					$mainicons = true;
					$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
					$main .= $this->displayReports();
				break;
				case 'delete_template':
					// Delete the currently viewed report template
					if($this->authCheck('functions', 'Delete Report Templates', 'access') || $this->authCheck('reports', $_GET['saved_report'], 'owner')) {
						$mainicons = false;
						$main .= deleteRule($_GET['saved_report']);
						$main .= "<h3>Template deleted.</h3>";
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				/*
				case 'delete_report':
					// Delete the currently viewed report template
					if($this->authCheck('functions', 'Delete Report Output', 'access')) {
						$main .= "<h3>Report deleted.</h3>";
						$mainicons = true;
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
						$main .= $this->deleteReports();
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				*/
				case 'new':
					// Create a new query
					$_GET['saved_report'] = null;
					if($this->authCheck('functions', 'Create Report Templates', 'access')) {
						$mainicons = false;
						$maintitle = "Report: Generator";
						$main .= $this->editRules();
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'edit_template':
					//TODO
					if($this->authCheck('functions', 'Edit Report Templates', 'access') || $this->authCheck('reports', $_GET['saved_report'], 'owner')) {
						$mainicons = true;
						$maintitle = "Report: ".$_SESSION['reports'][$_GET['saved_report']]['report_name']."";
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
						$rules = getRules($_GET['saved_report']);
						$main .= $this->editRules($rules);
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'run_report':
					if($this->authCheck('functions', 'Run Report Templates', 'access') || $this->authCheck('reports', $_GET['saved_report'], 'owner')) {
						$maintitle = "Report: ".$_SESSION['reports'][$_GET['saved_report']]['report_name']."";
						$mainicons = true;
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
						$_GET['run'] = 'true';
						$main .= $this->displaySavedReport();
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
			}
		}
		
		$menutitle = "Reports:";
		return $main;
	}

}


?>
