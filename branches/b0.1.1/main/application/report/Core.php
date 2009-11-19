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
		$main = $module->saveReport();

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
			$rules = addRule($rules['ATTRS']['NAME'], $rules, $_GET['report_type']);
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
			if ($_SESSION['admin']!='t' && $_SESSION['reports'][$_GET['saved_report']]['run'] != 't') {
				return $this->denied();
			}
			$module = $this->loadModule($_GET['report_type']);
			$main = $module->runReport($rules);
		} else {
			$module = $this->loadModule($_GET['report_type']);
			$main = $module->displayReport($rules['ATTRS']['NAME']);
		}
		
		return $main;
	}

	function displayWorkspace() {
		global $conf;
		if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Run Report Templates']['access'] == "t") {
			$run = true;
		}
		if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create/Edit Report Templates']['access'] == "t") {
			$edit = true;
		}
		if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Delete Report Templates']['access'] == "t") {
			$delete = true;
		}
		if(is_array($_SESSION['reports'])) {
			foreach ($_SESSION['reports'] as $i => $rule) {
				$mainiconURL = "index.php?db=".$rule['db_id']."&report_type=".$rule['report_type']."&saved_report=".$rule['report_name'];

				$workspace .= "<div id='block_".$rule['report_id']."' class='workspace_block'>
				<img border='0' src='Modules/".$rule['report_type']."/".$rule['report_type']."_template.png' alt='".$rule['report_type']."' title='".$rule['report_type']."' />
				<p>".$rule['report_name']."</p>
				<div id='menu_".$rule['report_id']."' class='workspace_menu'>";
				if ($run) {
					$workspace .= "<a href='#' onclick='javascript:dojo.widget.byId(\"layout_block_center\").setUrl(\"".$mainiconURL."&command=run_report\");$(\"layout_block\").style.display=\"block\";'><img border='0' src='images/id_icon_small_run.png' alt='Run' title='Run'></a> ";
				} 
				if ($edit) {
					$workspace .= "<a href='".$mainiconURL."&command=edit_template'><img border='0' src='images/id_icon_small_edit.png' alt='Edit' title='Edit'></a> ";
				} 
				if ($delete) {
					$workspace .= "<a href='#' onclick='javascript:userCheck(\"".$mainiconURL."&command=delete_template\", \"Are you sure you want to delete this object.\\nThis action is irreversible.\")'><img border='0' src='images/id_icon_small_delete.png' alt='Delete' title='Delete'></a> ";
				}
				$saveddir = $conf['Dir']['FullPath']."/saved/".$rule['report_type'];
				$path = $saveddir."/".$rule['report_name'];
				if (!is_dir($saveddir)) {
					mkdir($saveddir);
				}
				if (!is_dir($path)) {
					mkdir($path);
				}
			
				$dh = opendir($path);
				$files = scandir($path);
				$reports = array();
			
				if (count($files) > 2) {
					$workspace .= "<a href='#' onclick='javascript:dojo.widget.byId(\"layout_block_center\").setUrl(\"?command=view_report&report_type=listing&saved_report=".$rule['report_name']."\");$(\"layout_block\").style.display=\"block\";'><img border='0' src='images/id_icon_small_view.png' alt='View' title='View' /></a>";

					#foreach ($files as $file) {
					#	if (preg_match("/\.csv/", $file)) {
					#		$tmp = preg_replace(array("/.csv/"), array(""), $file);
					#		$main .= "$tmp: <a href='index.php?command=view_report&saved_report=$query&report_type=listing&id=$tmp'>view</a> <a href='index.php?command=delete_report&saved_report=$query&report_type=listing&id=$tmp'>delete</a><br>";
					#	}
					#}
				}

				$workspace .= "</div></div>";
			}
		}
		return $workspace;
	}
	
	/**
	 * This iterates through all the available options from the reports (index.php) 
	 * page. If an option is not in this this or the user doesn't have permission to 
	 * perform the action they will be directed to a permission denied screen.
	 *
	 * @global string $maintitle
	 * @global string $mainiconURL
	 * @return string 	The HTML to be displayed to the user.
	 */
	
	function main() {
		global $maintitle;
		global $mainiconURL;
		include_once("pages.php");
		$main = "";
		if ($_GET['db']) {
			$main = makeChange();
		}
		if (!$_GET) {
			$_SESSION['page'] = true;
			$main = $this->displayWorkspace($run, $edit, $delete);
		} else {
			$_SESSION['page'] = false;
			switch($_REQUEST['command']) {
				case 'help':
					$main = help();
					break;
				case 'adminhelp':
					$main = adminHelp();
					break;
				case 'save_template':
					// Save a trend line currently being edited
					if($this->authCheck('functions','Create/Edit Report Templates','access')) {
						$this->saveRules();
						$_SESSION['msg'] = 'Report ' . $_POST['save_as'].': template saved successfully.';
						$main = $this->displayWorkspace($run, $edit, $delete);
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'save_report':
					// Save the results of a database query, ie. the output
					if($this->authCheck('functions','Run Report Templates','access')) {
						$this->saveReports();
						$_SESSION['msg'] = 'Report ' . $_POST['save_as'].': report saved successfully.';
						$main = $this->displayWorkspace($run, $edit, $delete);
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'view_report':
					// View a saved query; exposes the menu to edit the query, run it,
					// and displays saved query results
					if($this->authCheck('reports', $_GET['saved_report'], 'view')) {
						$maintitle = 'Report: ' . $_GET['saved_report'];
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
						$main .= $this->displayReports();
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'delete_template':
					// Delete the currently viewed report template
					if($this->authCheck('functions', 'Delete Report Templates', 'access') && $this->authCheck('reports', $_GET['saved_report'], 'delete')) {
						deleteRule($_GET['saved_report']);
						$_SESSION['msg'] = "Template Deleted";
						$main = $this->displayWorkspace($run, $edit, $delete);
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'delete_report':
					// Delete the currently viewed report template
					if($this->authCheck('functions', 'Delete Report Output', 'access') && $this->authCheck('reports', $_GET['saved_report'], 'delete')) {
						$main .= "<h3>Report deleted.</h3>";
						$mainicons = true;
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
						$main .= $this->deleteReports();
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'new':
					// Create a new query
					if($this->authCheck('functions', 'Create/Edit Report Templates', 'access')) {
						$maintitle = "Report: Generator";
						$main .= $this->editRules();
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'edit_template':
					if($this->authCheck('reports', $_GET['saved_report'], 'edit')) {
						$maintitle = "Report: ".$_GET['saved_report']."";
						$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
						$rules = getRules($_GET['saved_report']);
						$main .= $this->editRules($rules);
					} else {
						$maintitle = "Permission Denied";
						$main = $this->denied();
					}
				break;
				case 'run_report':
					$mainiconURL = "index.php?report_type=".$_GET['report_type']."&saved_report=".$_GET['saved_report'];
					$_GET['run'] = 'true';
					$main .= $this->displaySavedReport();
				break;
			}
		}
		
		$menutitle = "Reports:";
		return $main;
	}

}


?>
