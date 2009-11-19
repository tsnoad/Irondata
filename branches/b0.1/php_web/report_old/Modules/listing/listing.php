<?php

/**
 * listing.php -- Implements the listing module
 * @package Modules--Listing
 * @author Andy White <andy@lgsolutions.com.au>
 * @version 2.0
 */

/**
 * Listing Module
 *
 * This module implements listings - multiple column
 * lists of data from the database.
 *
 * @package Modules--Listing
 * @author Andy White
 *
 */

class Listing extends Common_Functions {

	/**
	 * showRules shows the HTML elements used to
	 * create or edit the report template.
	 * @param	string	$saved_report	A report to load for editing
	 * @return 	string
	 */
	function showRules($saved_report=array()) {
		$query = Database::buildQuery("select", "ea_table", array("db_id"=>$_SESSION['curDB']), NULL, " order by ea_table_name");
		$tables = Database::runQuery($query);
		foreach ($tables as $i => $table) {
			$tableList[$table['ea_table_name']] = $table['ea_table_name'];
		}
	
		$main .= "<input type='hidden' value='0' id='id_number' />";
		$main .= "<h3>List Report Generator</h3>";
		$main .= "<form name='list' action='index.php?command=save_template&report_type=listing&saved_report=".$_GET['saved_report']."' method='POST'>";
		$main .= "<input type='hidden' value='save_template' id='command' name='command'/>";
		$main .= "<input type='hidden' name='report_type' value='listing'>"; 
		$main .= $default_table_values;
	
		$highestTrendVal = 0;
		$highestConsVal = 0;
		$trendnumber = 0;
		$constraintnumber = 0;
	
		if ($_GET['sql'] == "true") {
			$showSql = true;
		} 
	
		/* If you are editing a saved report. Use the saved values instead of the default ones */
		if ($saved_report) {
			$saved_report['ATTRS']['NAME'] = str_replace("'","&apos;",$saved_report['ATTRS']['NAME']);
			$this->auditLog("listing", "Viewed saved report template", $saved_report['ATTRS']['NAME'], $_GET['saved_report']);
			$values['save_as'] = $saved_report['ATTRS']['NAME'];
			$values['publish_report'] = $saved_report['ATTRS']['PUBLISH'];
			foreach ($saved_report['CHILDREN'] as $i => $rulevalue) {
				switch ($rulevalue['NAME']) {
					case "LINKTO":
						$values['LINKTO'] = $rulevalue['TAGDATA'];
						break;
					case "SQL":
						$values['sql'] = $rulevalue['TAGDATA'];
						$showSql = true;
						break;
					case "COLUMN":
						$trendnumber = $rulevalue['ATTRS']['NAME'];
						foreach ($rulevalue['CHILDREN'] as $j => $trendvalue) {
							$trendval[$trendnumber][strtolower($trendvalue['NAME'])] = $trendvalue['TAGDATA'];
						}
						break;
					case "CONSTRAINT":
						$constraintnumber = $rulevalue['ATTRS']['NAME'];
						foreach ($rulevalue['CHILDREN'] as $j => $trendvalue) {
							$constraintval[$constraintnumber][strtolower($trendvalue['NAME'])] = $trendvalue['TAGDATA'];
						}
						break;
				}
			}
		} else {
			$values['save_as'] = $_SESSION['username']."-".date("Y-m-d");
			$values['LINKTO'] = "";
			$trendval[0]['title'] = "";
			$trendval[0]['table'] = "";
			$trendval[0]['columns'] = "";
			$trendval[0]['aggregate'] = "";
			$constraintval[0][0]['constraint_table'] = "";
			$constraintval[0][0]['constraint_columns'] = "";
			$constraintval[0][0]['constraint_type'] = "";
			$constraintval[0][0]['constraint_value'] = "Value";
			$highestVal = 0;
		}
		$highestTrendVal = $trendnumber;
		$highestConsVal = $constraintnumber;
	
		/* This displays the list report generator  */
		$main .= "<script>
			".showConstraintBlock($tableList)."
			".showListTrendBlock($tableList)."
			trend_number=".max(array_keys($trendval))."
		</script>";

		if ($showSql == false) {
			$trend .= "<table id='trend_table'>";
			$trend .= "<thead><tr>
				<th>Label</th>
				<th>Table</th>
				<th>Column</th>
				<th>Aggregate (optional)</th>
				<th>&nbsp;</th>
				</tr></thead><tbody id='trend_table_body'>";
			foreach ($trendval as $i => $trendaxis) {
				$trend .= "<tr id='trendrow__".$i."'>";
				$trend .= "<td><input id='title__".$i."' type='text' name='title__".$i."' value='".$trendaxis['title']."' class='title'></td>";
				$trend .= "<td><select id='table__".$i."' name='table__".$i."' onChange='javascript:changeOptions(&quot;table__".$i."&quot;,&quot;columns__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $trendaxis['table'])."</select></td>";
				$trend .= "<td><select id='columns__".$i."' name='columns__".$i."'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendaxis['table'], $trendaxis['columns'])."</select></td>";
				$trend .= "<td><select name='aggregate__".$i."'>".
                                Common_Functions::generate_options(array('count once'=>'count once', 'count'=>'count', 'avg'=>'average', 'max'=>'max', 'min'=>'min', 'sum'=>'sum'), $trendaxis['aggregate'], "None (default)")."</select></td>";
				$trend .= "<td>";
				$trend .= "<input class='remove' type='button' onclick='removeTableTrend(\"trend_table_body\", \"trendrow__".$i."\")' name='remove_trend' value='-' /><br/>";
				$trend .= "</td>";
				$trend .= "</tr>";
			}
			$trend .= "</tbody></table>";

		} else {
			$trend .= "<textarea name='sqlquery' class='sql' rows='7'>".$values['sql']."</textarea>";
		}
	
		$trend .= "<br />";
	
		if ($showSql == false) {
			$trend .= "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__R' id='clipper__R' width='0' height='0'></iframe>
			<div class='constraints' id='constraints__R'>
			<div class='cons_header' id='cons_header__R'><strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__R\"); makeInvisible(\"clipper__R\");'><br/><br/></div>
			<script type='text/javascript'>
			//<![CDATA[
			new Draggable('constraints__R', {handle:'cons_header__R', starteffect:false})
			//]]>
			</script>
			<div id='constraints_values__R' class='cons_values'>";
			foreach ($constraintval as $i => $constraintaxis) {
				$trend .= "
					<div id='trend__R".$i."Div'>
					<select id='constraint_table__R__".$i."' name='constraint_table__R__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__R__".$i."&quot;,&quot;constraint_columns__R__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraintval[$i]['constraint_table'])."</select>
					<select id='constraint_columns__R__".$i."' name='constraint_columns__R__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__R__".$i."&quot;,&quot;constraint_columns__R__".$i."&quot;,&quot;constraint_type__R__".$i."&quot;,&quot;constraint_value__R__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraintval[$i]['constraint_table'], $constraintval[$i]['constraint_columns'])."
					</select>
					<select name='constraint_type__R__".$i."' id='constraint_type__R__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__R__".$i."&quot;,&quot;constraint_columns__R__".$i."&quot;,&quot;constraint_type__R__".$i."&quot;,&quot;constraint_value__R__".$i."&quot;);'>
					".Common_Functions::generate_comparisons($constraintval[$i]['constraint_type'])."</select>
					<input type='text' name='constraint_value__R__".$i."' id='constraint_value__R__".$i."' value='".$constraintval[$i]['constraint_value']."'/>
					";
					if ($constraintaxis['constraint_auto'] == "on") {
						$checked = "checked";
					} else {
						$checked = "";
					}
					$trend .= "<input type='checkbox' name='constraint_auto__R__".$i."' id='constraint_auto__R__".$i."' ".$checked."'/>";
				if ($i == 0) {
					$trend_cons_class = '__first';
				} else {
					$trend_cons_class = '__notfirst';
				}
				$trend .= "<input class='".$trend_cons_class."' type='button' value='-' onclick='javascript:removeElement(\"constraints_values__R\", \"R\", \"".$i."\"); underClip(\"clipper__R\", \"constraints__R\");' name='remove_constraint' name='remove_constraint' />";
				$trend .= "<script>constraint_number += " . $i . "</script>";
				$trend .= "</div>
					";
			}
			$trend .= "</div><br/>";
			$trend .= "<input style='margin-bottom: 20px; margin-left: 20px;' type='button' value='Add New Constraint' onclick='javascript:addConstraint(\"constraints_values__R\", \"__R\"); underClip(\"clipper__R\", \"constraints__R\");' name='additional_constraint' />";
			$trend .= "</div>";
			$trend .= "<p style='margin-top: 100px;'><input type='button' onclick='; cloneTableElement(\"trend_table_body\", \"trendrow\", \"trend\", null)' name='additional_trend' value='Add New Column' style='z-index:0' /></p>";
			$trend .= "<p><input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__R\"); underClip(\"clipper__R\", \"constraints__R\");''></p>";
			$trend .= "<br />";
		}
		
		$main .= $trend;
		$main .= "<hr>";
 		$main .= "<br />";
 		if ($values['publish_report']) {
 			$pub_sel = "checked";
 		}
 		$main .= "<label for='publish_report'>Publish</label><input value='1' type='checkbox' name='publish_report' id='publish_report' ".$pub_sel."><br />";
		$main .= "<p><input type='text' name='save_as' value='".$values['save_as']."'> <input class='button' type='submit' name='submit' value='Save'> <input class='button' type='submit' name='submit' value='Save As'></p>";
		$main .= "</form>";
		return $main;
	}

	
	/**
	 * makeRules formats data from a rules template edit screen into
	 * XML.
	 * @param	string	$report		The values sent from the HTML form
	 * @return 	string
	 */
	function makeRules($report) {
		$xml = array();
		$xml['NAME'] = 'report';
		$xml['ATTRS']['NAME']=str_replace("'","&apos;",$report['save_as']);
		$xml['ATTRS']['PUBLISH']=$report['publish_report'];
		$xml['ATTRS']['REPORT_TYPE']='listing';
		$xml['CHILDREN'] = array();
		foreach ($report as $i => $data) {
			if ($i == "save_as" || $i == "start_date" || $i == "end_date" || $i == "interval" || $i == "linkTo") {
				continue;
			}
			if ($i == "sqlquery") {
				if (strtolower(substr($data, 0, 6)) != "select") {
					return false;
				}
				$xml['CHILDREN'][0]['NAME'] = "sql";
				$xml['CHILDREN'][0]['TAGDATA'] = stripslashes($data);
				return $xml;
			}
			$keys = explode("__", $i);
			if (count($keys) == 2) {
				$list[$keys[1]][$keys[0]] = $data;
			}
			if (count($keys) == 3) {
				$constraints[$keys[1]][$keys[2]][$keys[0]] = $data;
			}
		}
		foreach ($list as $col => $item) {
			$temp = array();
			$temp['NAME']='column';
			$temp['ATTRS']['NAME']=$col;
			$temp['CHILDREN'] = array();
			
			foreach ($item as $tag => $data) {
				$temp2 = array();
				$temp2['NAME'] = $tag;
				$temp2['TAGDATA'] = $data;
				array_push($temp['CHILDREN'], $temp2);
			}
			array_push($xml['CHILDREN'], $temp);
		}
		
		foreach ($constraints as $col => $item) {
			if (is_array($constraints[$col])) {
			foreach ($constraints[$col] as $cons => $cons_items) {
				$temp = array();
				$temp['NAME'] = 'constraint';
				$temp['ATTRS']['NAME']=$cons;
				$temp['CHILDREN'] = array();
				#automatically add auto/global constraints
				foreach ($cons_items as $tag => $value) {
					$temp2 = array();
					$temp2['NAME'] = $tag;
					$temp2['TAGDATA'] = $value;
					array_push($temp['CHILDREN'], $temp2);
				}
				array_push($xml['CHILDREN'], $temp);
			}
			}
		}
		return $xml;
	}


	
	/**
	 * runReport runs a specified set of rules through the database via
	 * runQuery(), takes the results and builds the list from them.
	 * @param	string	rules	The XML rules to run through the system
	 * @return 	string
	 */
	function runReport($report) {
		$mail = "";
		$report_name = $report['ATTRS']['NAME'];
		$report_type = $report['ATTRS']['REPORT_TYPE'];
		$auto = '';
		$autovalues = array();
		$autovalues_string = "";
		$total = array();
		#Getting user input
		foreach ($report['CHILDREN'] as $i => $child) {
			if ($child['NAME'] == "COLUMN") {
				$curname = false;
				foreach ($child['CHILDREN'] as $k => $attr) {
					if ($attr['NAME'] == "TITLE") {
						$curname = $attr['TAGDATA'];
					}
					if ($attr['NAME'] == "COLUMNS" && !$curname) {
						$curname = $attr['TAGDATA'];
					}
					if ($attr['NAME'] == "AGGREGATE" && $attr['TAGDATA'] != "None (default)") {
						$total[$curname] = true;
					}
				}
			}
			if ($child['NAME'] == "CONSTRAINT") {
				foreach ($child['CHILDREN'] as $k => $attr) {
					$cons[$attr['NAME']] = $attr['TAGDATA'];
					if ($attr['NAME'] == "CONSTRAINT_VALUE") {
						$valk = $k;
					}
					if ($attr['NAME'] == "CONSTRAINT_AUTO" && $attr['TAGDATA'] == "on") {
						if ($_REQUEST['auto'] ) {
							$report['CHILDREN'][$i]['CHILDREN'][$valk]['TAGDATA'] = $_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']][$cons['CONSTRAINT_TYPE']];
							$autovalues[$cons['CONSTRAINT_COLUMNS']] = $_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']][$cons['CONSTRAINT_TYPE']];
							$autovalues_string .= "<strong>".ucwords($cons['CONSTRAINT_COLUMNS']).": </strong>".$_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']][$cons['CONSTRAINT_TYPE']]."<br />";
						} else {
							$auto .= "<p>Please enter a value for the following fields from the <strong>".$cons['CONSTRAINT_TABLE']."</strong> table that you wish to report on.</br>";

							$auto .= "<input type='hidden' id='".$cons['CONSTRAINT_TABLE']."' value='".$cons['CONSTRAINT_TABLE']."'>";
							$auto .= "<input type='hidden' id='".$cons['CONSTRAINT_COLUMNS']."' value='".$cons['CONSTRAINT_COLUMNS']."'>";
							$auto .= "<input type='hidden' id='is' value='is'>";
							$auto .= "<label style='width:160px;' for='auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."]'>".$cons['CONSTRAINT_COLUMNS']." (".trim($this->buildConstraintClause($cons['CONSTRAINT_TYPE'], ".", null, null), "'.").")</label>
							<select id='auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."][".$cons['CONSTRAINT_TYPE']."]' name='auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."][".$cons['CONSTRAINT_TYPE']."]' >".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $cons['CONSTRAINT_TABLE'], $cons['CONSTRAINT_COLUMNS'])."</select>";
							$auto .= "<script>changeColumn('".$cons['CONSTRAINT_TABLE']."', '".$cons['CONSTRAINT_COLUMNS']."', 'is', 'auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."][".$cons['CONSTRAINT_TYPE']."]');</script>";
							$auto .= "</p>";
						}
						$gcons[] = $cons;
					}		
				}
			}
		}
		#create the text that goes after the report name and before the extension in the filename. 
		$suffix = "";
		if ($autovalues) {
			$suffix .= " (".implode(", ", $autovalues).")";
		}
		$suffix .= " - ".date("Y-m-d");
		$title = "<div class='heading'>
		<h3>List Report - ".$report_name."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='tmp/".$report_name.$suffix.".pdf'><img src='images/pdf.png' title='Download PDF' alt='Download PDF'></a> <a href='tmp/".$report_name.$suffix.".xls'><img src='images/spreadsheet.png' title='Download Spreadsheet' alt='Download Spreadsheet'></a></h3>
		<p>$autovalues_string</p>
		</div>";

		#Displaying the request for input;
		if ($auto && !$_REQUEST['auto']) {
			$main .= "<p>This report requires user input to proceed.<br/>";
			$main .= "<form method='post' action='".$_SERVER['REQUEST_URI']."'>";
			$main .= $auto;
			$main .= "<input type='submit'>";
			$main .= "</form>";
			$main .= "</p>";
			return $main;
		}

		$xlsfile = $report_name;
		$main .= "<p>";
		$imported_rules = Array();
		if($_GET['parent_from']) {
			$imported_rules[0] = getRules($_GET['parent_from']); 
		}
		$imported_rules[1] = getRules($_GET['report_from']); 
		$queries = Database::generateSQL($report, $imported_rules);
		foreach ($queries as $i => $query) {
			$results[] = Database::runQuery($query, $_SESSION['curDB_psql']);
		}
		#Check and alert to user if the query returns no results. 
		if (!$results[0]) {
			$main = Common_Functions::invalid($report_name, "main");
			return $main;
		}
		#$excel = Common_Functions::writeExcel($results, "tmp/".$xlsfile." - ".date("Y-m-d").".xls");

		$main .= "<table class='bordered'>";
		$first = true;
		$ztotal = array();
		foreach ($results as $i => $indiresult) {
			foreach ($indiresult as $j => $result) {
				if ($first == true) {
					$first = false;
					$main .= "<tr>";
					$main .= "<td>#</td>";
					if ($report_type == "trend") {
						$main .= "<td></td>";
					}
				
					foreach ($result as $k => $cell) {
						$ztotal[$k] = true;
						$main .= "<td class='bordered'><strong>".$k."</strong></td>";
					}
					$main .= "</tr>";
				}
				$main .= "<tr>";
				$main .= "<td>".($j+1)."</td>";
				if ($report_type == "trend") {
					$main .= "<td>"."</td>";
				}
				foreach ($result as $k => $cell) {
					if ($total) {
						if ($total[$k] == true) {
							$ztotal[$k] += $cell;
						} else {
							$ztotal[$k] = '';
						}
					} 
					if ($this->isemail($cell)) {
						$mail .= $cell.",";
						$cell = "<a href='mailto:".$cell."'>".$cell."</a>";
					}
					$main .= "<td class='bordered'>".$cell."</td>";
				}
				$main .= "</tr>";
			}
		}
		if ($total) {
			$main .= "<tr><td><strong>Total:</strong></td>";
			foreach ($ztotal as $i => $cell) {
				$main .= "<td>".$cell."</td>";
			}
			$main .= "</tr>";
		}
		$main .= "</table>";
		$main .= "</p>";
		$excel = Common_Functions::writeExcel($results, "tmp/".$xlsfile.$suffix.".xls");
		#Only make PDFs of small lists
		if (count($results[0]) < 1000) {
			$pdf = Common_Functions::makePdf($main, $report_name.$suffix, array(), $autovalues); /* The ./tmp/ directory is automatically added, as is the .pdf extension. */
		}
		if ($mail) {
			$mail = "<p><a href='mailto:?bcc=".$mail."'>Send mail to all listed email addresses</a></p>";
		}
		
		global $saveicons;
		$saveicons = "index.php?command=save_report&saved_report=".$_GET['saved_report']."&report_type=listing&suffix=".$suffix;
		
		$main = $save_link.$main;
		
		$this->auditLog("listing", "Ran Report", $report_name, $_GET['saved_report']);
		
		$main = $title.$mail.$main;
		return $main;
	}
	
	
	/**
	 * displayReport displays a pre-generated list, with various 
	 * controls to modify it.
	 * @param	string	$query		The name of the report template
	 * @param	string	$which		The name of the stored results to load
	 * @return	string
	 */
	function displayReport($query, $which="") {
		global $conf;
		$saveddir = $conf['Dir']['FullPath']."saved/list";
		$report_name = $_SESSION['reports'][$query]['report_name'];
		$path = $saveddir."/".$query;
		if ($which=="") {
			if (!is_dir($saveddir)) {
				mkdir($saveddir);
			}
			
			if (!is_dir($path)) {
				mkdir($path);
			}
			
			$dh = opendir($path);
			$files = scandir($path, 1);
			$reports = array();
			
			$first = true;

			if (count($files) > 2) {
				foreach ($files as $file) {
					if (preg_match("/\.xls$/", $file)) {
						$tmp = preg_replace(array("/.xls$/"), array(""), $file);
						if ($first == true) {
							$main = "<div class='heading'><h3>Current Report:</h3><ul>";
						}
						if (file_exists($path."/".$tmp.".pdf")) {
							$pdf = "<a href='".$conf['Dir']['WebPath']."/saved/list/".$query."/".$tmp.".pdf'><img src='images/pdf.png' title='View PDF' alt='View PDF'></a>";
						} else {
							$pdf = '';
						}
						$main .= "<li>$tmp: <a href='".$conf['Dir']['WebPath']."/saved/list/$query/$file'><img src='images/spreadsheet.png' title='View Spreadsheet' alt='View Spreadsheet'></a> ".$pdf."</li>";
						# <a href='index.php?command=delete_report&saved_report=$query&report_type=list&id=$tmp'>delete</a>
						if ($first == true) {
							$main .= "</ul></div><ul>";
							$first = false;
						}
					}
				}
				$main .= "</ul>";
			} else {
				$main = "<div class='heading'><h3>No saved reports</h3></div>";
			}
			
			
			$lastupdate = $this->getLastUpdate('listing', $report_name);
			if($lastupdate[0]['username'] != null && $lastupdate[0]['username'] != "") {
				$main .= "<p>Report Template last updated on <strong>".$lastupdate[0]['time']."</strong> by <strong>".$lastupdate[0]['username']."</strong></p>";
			}
			
			return $main;
		} else {
			$file_name = $path."/$which.xls";
			#$main = "<a href='toPDF.php?saved_report=$query&report_type=table&id=$which'>Download PDF version</a><br/><br/>";
			global $pdficons;
			$pdficons = "toPDF.php?saved_report=".$_GET['saved_report']."&report_type=listing&id=".$which."'";
			$main .= "<h3>Raw Data - $report_name </h3>";
			$main .= "<p><a href='saved/list/".$query."/".$which.".xls'>Download Excel file</a></p>";
			if (file_exists($file_name)) {
				$this->auditLog("listing", "Viewed pre-generated Report", $report_name, $query);
				$lines = file($file_name);
				$j = count($lines);
				$main .="<table class='bordered'>";
				$main .= displayListLine($lines[0], true);
				
				for ($i=1;$i<$j;$i++) {
					$main .= displayListLine($lines[$i]);
				}
				$main .= "</table>";
				return $main;
			}
		}
	}


	
	/**
	 * deleteReport deletes a saved report (NOT the report template!)
	 * @param	string	$query		The name of the report template
	 * @param	string	$which		The name of the stored results to delete
	 * @return	string
	 */
	function deleteReport($query, $which="") {
		global $conf;
		$filename = $conf['Dir']['FullPath']."saved/list/$query/$which.xls";
		if (file_exists($filename)) {
			$res = unlink($filename);
		}
		$filename = $conf['Dir']['FullPath']."saved/list/$query/$which.pdf";
		if (file_exists($filename)) {
			$res = unlink($filename);
		}
		
		$this->auditLog("listing", "Deleted Report Output - $query/$which", $this->getReportName($query), $query);
		
		return $this->displayReport($query);
	}
	
	/**
	 * saveReport saves the results of a report template execution into
	 * a file on the server; so we don't need to run the report again to
	 * see the results.
	 * @return	string
	 */
	function saveReport($id, $suffix, $report_name) {
		global $conf;
		//$id = $_GET['saved_report'];
		//$suffix = $_GET['suffix'];
		//$report_name = $_SESSION['reports'][$id]['report_name'];
		$path = $conf['Dir']['FullPath']."saved/list";
		$dest_dir = $path."/".$id;
		
		if (!is_dir($path)) {
			mkdir ($path);
		}
		
		if (!is_dir($dest_dir)) {
			mkdir($dest_dir);
		}
		
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".xls", $dest_dir."/".$report_name.$suffix.".xls");
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".pdf", $dest_dir."/".$report_name.$suffix.".pdf");
		
		$this->auditLog("listing", "Saved Report Output; filename(s) like: ".$report_name." - ".date("Y-m-d"), $report_name, $id);
		
		//echo "Saved report output at ". $dest_dir."/".$report_name.$suffix.".xls\n";
			
		//TODO: Test for success here, and notify user by returning false if we failed to save!
		return true;
	}
	
	/**
	 * adds this module's content to the sidebar of the report 
	 * screen.
	 *
	 * @return string		HTML to be added to the sidebar.
	 */
	function buildSidebar() {
		$listing=0;
		if(is_array($_SESSION['reports'])) {
			foreach ($_SESSION['reports'] as $i => $rule) {
				if ($rule['report_type'] == "listing" && $rule['db_id'] == $_SESSION['curDB']) {
					if ($rule['published'] || $_SESSION['unpublished'] == true) {
						$pub_class = "published";
						$listing++;
					} else {
						$pub_class = "notpublished";
					}
					$listinglist .= "<span class='".$pub_class."'>&raquo; <a href='?command=view_report&report_type=listing&saved_report=".$rule['report_id']."'>".$rule['report_name']."</a><br /></span>";
				} 
			}
		}
		
		$sideString .= "<b>List</b><br/>";
		if ($_SESSION['curDB'] && ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create Report Templates']['access'] == "t")) {
			$sideString .= "&raquo; <a href='?command=new&report_type=listing&new=true'>New List Report</a><br />";
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create/Edit Raw SQL']['access'] == "t") {
				$sideString .= "&raquo; <a href='?command=new&report_type=listing&sql=true'>New List SQL</a><br />";
			}
		}
		$sideString .= '<fieldset>
			<legend onclick="Effect.toggle(\'side-list\',\'slide\'); return false;">List Reports ('.$listing.')</legend>
			<div id="side-list" style="display: none;"><span>';
		$sideString .= $listinglist;
		$sideString .= "</span></div></fieldset><br />";
		return $sideString;
	}

	/**
	 * buildConstraintClause creates a new clause for the 'where' string of an SQL query,
	 * based on rules passed in.
	 *
	 * @comment Broken out 24/04/07 by AW
	 *
	 * @param
	 * @return
	 */
	 function buildConstraintClause($type, $value, $table, $columns) {
		switch($type) {
			case 'isnot':
				$condition = "!=";
			break;
			case 'is':
				if ($value == "") {
					$condition = "is null";
					$value = "";
				} else {
					$condition = "=";
				}
			break;
			case 'containsnot':
				$condition = "not ilike";
				$value = "%".$value."%";
			break;
			case 'contains':
				$condition = "ilike";
				$value = "%".$value."%";
			break;
			case 'gt':
				$condition = ">";
			break;
			case 'lt':
				$condition = "<";
			break;
			case 'gte':
				$condition = ">=";
			break;
			case 'lte':
				$condition = "<=";
			break;
			case 'ex':
				$condition = " is not null";
				$value = "";
			break;
			case 'dne':
				$condition = " is null";
				$value = "";
			break;
		}		
		if ($condition == '<' or $condition == '>' or $condition == '<=' or $condition == '>=') {
			if (is_numeric($value)) {
				$table = "cast(".$table;
				$columns = $columns." as int)";
			} elseif (strtotime($value)) {
	                        $table = "cast(".$table;
				$columns = $columns." as timestamp)";
			}
		} 
		
		if ($value != "") {
			$value = "'".$value."'";
		}
		
		return $table.".".$columns." ".$condition." ".$value."";
	 }
	
	/**
	 * buildSQL creates an SQL query (or array of SQL queries) which will be used by the module
	 * to interrogate the database and produce results.
	 *
	 * @param	string	$rules					The XML rules to be executed
	 * @param	string	$rules					The XMl rules of a parent report, if this report was linked-to
	 * @param	int		$line_no				The line number
	 * @param	array	$cell						A pre-computed array of values from the XML ruleset
	 * @param	array	$constraintCell		A pre-computed array of values from the XML ruleset
	 * @param	array	$timeCell				A pre-computed array of values from the XML ruleset
	 * @param	array	$maxTimestamp	The maximum timestamp in the database
	 * @param	array	$minTimestamp	The minimum timestamp in the database
	 * @return	array	The queries to be executed		
	 */
	function buildSQL($rules, $linkFromRules, $line_no = -1, $cell, $constraintCell, $timeCell, $maxTimestamp, $minTimestamp, $type) {
		$table = array();
		$set = array();
		$where = array();
		$order = "";
		$table_cells = array();
		$timestamp_done = false;
		if ($sql) {
			$query[] = Database::getTables($sql, '3000-01-01');
		} else {
			if (is_array($linkFromRules)) {
				foreach($linkFromRules as $reportRules) {
					if (is_array($reportRules['CHILDREN'])) {
					foreach ($reportRules['CHILDREN'] as $i => $child) {
						if ($child['NAME'] != "AXIS" && $child['NAME'] != "TREND") {
							continue;
						}
						
						if(isset($_GET['line']) && $child['NAME'] == 'TREND' && $child['CHILDREN'][0]['TAGDATA'] != $_GET['line']) {
							continue;
						}
						
						$axis = $child['ATTRS']['NAME'];
						if ($axis == "C") {
							$table[$child['CHILDREN'][0]['TAGDATA']] = $child['CHILDREN'][0]['TAGDATA'];
							for($counter = 3; $counter < count($child['CHILDREN']); $counter++) {
									if($child['CHILDREN'][$counter]['CHILDREN'][0]['TAGDATA'] == 'Select Table ...' ) {
										continue;
									}
								$constraintCell[0]['C-'.$counter]['CONSTRAINT_TABLE'] = $child['CHILDREN'][$counter]['CHILDREN'][0]['TAGDATA'];
								$constraintCell[0]['C-'.$counter]['CONSTRAINT_COLUMNS'] = $child['CHILDREN'][$counter]['CHILDREN'][1]['TAGDATA'];
								$constraintCell[0]['C-'.$counter]['CONSTRAINT_TYPE'] = $child['CHILDREN'][$counter]['CHILDREN'][2]['TAGDATA'];
								$constraintCell[0]['C-'.$counter]['CONSTRAINT_VALUE'] = $child['CHILDREN'][$counter]['CHILDREN'][3]['TAGDATA'];
							} 
							continue;
						} else if($child['NAME'] == "TREND") {
								if($child['CHILDREN'][$counter]['CHILDREN'][0]['TAGDATA'] == 'Select Table ...' ) {
										break;
								}
								$constraintCell[0][$child['ATTRS']['NAME']]['CONSTRAINT_TABLE'] = $child['CHILDREN'][5]['CHILDREN'][0]['TAGDATA'];
								$constraintCell[0][$child['ATTRS']['NAME']]['CONSTRAINT_COLUMNS'] = $child['CHILDREN'][5]['CHILDREN'][1]['TAGDATA'];
								$constraintCell[0][$child['ATTRS']['NAME']]['CONSTRAINT_TYPE'] = $child['CHILDREN'][5]['CHILDREN'][2]['TAGDATA'];
								$constraintCell[0][$child['ATTRS']['NAME']]['CONSTRAINT_VALUE'] = $child['CHILDREN'][5]['CHILDREN'][3]['TAGDATA'];
						}
						if($axis == 'X' || $axis == 'Y' || $axis == 'Z') {
							foreach ($child['CHILDREN'] as $j => $child2) {
								switch ($child2['NAME']) {
									case "TABLE":
										$constraintCell[0][$axis]['CONSTRAINT_TABLE'] = $child2['TAGDATA'];
										break;
									case "COLUMNS":
										$constraintCell[0][$axis]['CONSTRAINT_COLUMNS'] = $child2['TAGDATA'];
									break;
								}
							}
							$constraintCell[0][$axis]['CONSTRAINT_TYPE'] = "is";
							$constraintCell[0][$axis]['CONSTRAINT_VALUE'] = $_GET[$axis];
						}
					}
					}
				}
				
				$constraintCell[0]['start_timestamp']['CONSTRAINT_TABLE'] = $table[$child['CHILDREN'][0]['TAGDATA']];
				$constraintCell[0]['start_timestamp']['CONSTRAINT_COLUMNS'] = 'start_date';
				$constraintCell[0]['start_timestamp']['CONSTRAINT_TYPE'] = 'lt';
				$constraintCell[0]['start_timestamp']['CONSTRAINT_VALUE'] = $_GET['point'];
				$constraintCell[0]['end_timestamp']['CONSTRAINT_TABLE'] = $table[$child['CHILDREN'][0]['TAGDATA']];
				$constraintCell[0]['end_timestamp']['CONSTRAINT_COLUMNS'] = 'end_date';
				$constraintCell[0]['end_timestamp']['CONSTRAINT_TYPE'] = 'gte';
				$constraintCell[0]['end_timestamp']['CONSTRAINT_VALUE'] = $_GET['point'];
				$set[] = "DISTINCT";
			}
			foreach ($cell as $i => $entry) {
				if ($entry['TABLE'] == "") {
					continue;
				}
				$table[$entry['TABLE']] = $entry['TABLE'];
				if ($entry['AGGREGATE'] == "sum") {
					$setString = "sum(".$entry['TABLE'].".".$entry['COLUMNS'].") ";
					if(!$entry['TITLE']){$setString.="as ".$entry['COLUMNS'];}
				} elseif ($entry['AGGREGATE'] == "avg") {
					$setString = "round(avg(".$entry['TABLE'].".".$entry['COLUMNS']."), 2) ";
					if(!$entry['TITLE']){$setString.="as ".$entry['COLUMNS'];}
				} elseif ($entry['AGGREGATE'] == "count") {
					$setString = "count(".$entry['TABLE'].".".$entry['COLUMNS'].") ";
					if(!$entry['TITLE']){$setString.="as ".$entry['COLUMNS'];}
				} elseif ($entry['AGGREGATE'] == "count once") {
					$setString = "count(distinct ".$entry['TABLE'].".".$entry['COLUMNS'].") ";
echo "\n-----\nDEBUG entry\n-----\n";print_r($entry);echo "\n-----\n";
					if(!$entry['TITLE']){$setString.="as ".$entry['COLUMNS'];}
				} elseif ($entry['AGGREGATE'] == "max") {
					$setString = "max(".$entry['TABLE'].".".$entry['COLUMNS'].") ";
					if(!$entry['TITLE']){$setString.="as ".$entry['COLUMNS'];}
				} elseif ($entry['AGGREGATE'] == "min") {
					$setString = "min(".$entry['TABLE'].".".$entry['COLUMNS'].") ";
					if(!$entry['TITLE']){$setString.="as ".$entry['COLUMNS'];}
				} else {
					$setString = $entry['TABLE'].".".$entry['COLUMNS']." ";
					$group[] = $entry['TABLE'].".".$entry['COLUMNS']." ";
				}
				if ($entry['TITLE']) {
					$setString .= "as \"".$entry['TITLE']."\"";
				}
				$set[] = $setString;
			}
			foreach ($constraintCell[0] as $j => $c_entry) {
				if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
					continue;
				}
				if ($c_entry['CONSTRAINT_COLUMNS'] == "start_date" || $c_entry['CONSTRAINT_COLUMNS'] == "end_date") {
					$timestamp_done = true;
				}
				$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
				$where[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
			}
			
			foreach ($table as $k => $t_entry) {
				if ($timestamp_done == false) {
					$where[] = $k.".end_date = '$maxTimestamp'";
				}
				$keys = Database::getFKeys($t_entry, $_SESSION['curDB_psql']);
				if (is_array($keys)) {
					foreach ($keys as $l => $key) {
						if (in_array($key['fks'][0], $table)) {
							$where[] = $key['fks'][0].".".$key['fks'][1]."=".$t_entry.".".$key['ea_column_name'];
						}
					}
				}
			}
			
			$table = implode(",", $table);
			$where = implode(" and ", $where);
			if ($where) {
				$where .= " ";
			}
			$order = "group by ".implode(",", $group)." order by ".$cell[0]['TABLE'].".".$cell[0]['COLUMNS'];
			$query[] = Database::buildQuery($type, $table, $where, $set, $order);
		}
		
		return $query;
	}
	
}











?>
