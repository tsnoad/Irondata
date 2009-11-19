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
		foreach ($_SESSION['tables'] as $i => $table) {
			$tableList[$table['ea_table_name']] = $table['ea_table_name'];
		}
	
		$main .= "<input type='hidden' value='0' id='id_number' />";
		$main .= "<h3>List Report Generator</h3>";
		$main .= "<form name='list' action='index.php?command=save_template&report_type=listing&saved_report='".$saved_report['ATTRS']['NAME']."' method='POST'>";
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
			$values['save_as'] = $saved_report['ATTRS']['NAME'];
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
							print_r($trendvalue['TAGDATA']);
						}
						break;
				}
			}
		} else {
			$values['save_as'] = $_SESSION['username']."-".date("Y-m-d H-i");
			$values['LINKTO'] = "";
			$trendval[0]['title'] = "";
			$trendval[0]['table'] = "";
			$trendval[0]['columns'] = "";
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
		</script>";
	
		$trend .= "<div id='listlines'>";
		$trend_nos = "<script>trend_number=".$highestTrendVal."</script><script>constraint_number=".$highestConsVal."</script>";
		$trend .= "<span id='trend_no' class='trend_no' style='position:absolute; top:100px !important; top: 118px; left:73px'>".$trend_nos."</span>";
		
		if ($showSql == false) {
			foreach ($trendval as $i => $trendaxis) {
				$trend .= "<div id='trendlines__".$i."' class='listlines' ".$invisible.">";
				$trend .= "<p><input type='text' name='title__".$i."' class='title' value='".$trendaxis['title']."' style='width: 170px'><br/></p>";
	
				$trend .= "<p><select id='table__".$i."' name='table__".$i."' onChange='javascript:changeOptions(&quot;table__".$i."&quot;,&quot;columns__".$i."&quot;);'  style='position: absolute; top: 175px; left: 20px; width: 170px;' >".Common_Functions::generate_options($tableList,$trendval[$i]['table'])."</select><br/></p>";
				$trend .= "<p><select id='columns__".$i."' name='columns__".$i."''  style='position: absolute; top: 200px; left: 20px; width: 170px;' />".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval[$i]['table'], $trendval[$i]['columns'])."</select><br/></p>";
				$trend .= "<input type='button' onclick='makeInvisible(\"trendlines__".$i."\"); addElement(\"listlines\", trendString, \"trend\")' name='additional_trend' value='Add an additional column' style='position:absolute; top: 230px; left: 20px; width: 170px;' />";
				if($i > 0) {
					$trend .= "<input type='button' onclick='javascript:removeTrend(&quot;listlines&quot;, this.parentNode.id);' name='remove_trend' value='Remove this trend line' style='position:absolute; top: 260px; left: 20px; width: 170px' />";
					}
				$trend .= "<br/><br/>";
				$trend .= "</div>";
				$invisible = "style='display: none;'";
			}
		} else {
			$trend .= "<div id='trendlines__".$i."' class='listlines' ".$invisible.">";
			$trend .= "<textarea name='sqlquery' class='sql' style='position: absolute; top: 125px; left: 20px; width: 600px;' rows='7'>".$values['sql']."</textarea>";
			$trend .= "</div>";
		}
		$invisible = "";
		$trend .= "</div>";
	
		if ($showSql == false) {
			$trend .= "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__R' id='clipper__R' width='0' height='0'></iframe><div class='constraints' id='constraints__R'>
				<strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__R\"); makeInvisible(\"clipper__R\");'><br/><br/>
				";
			foreach ($constraintval as $i => $constraintaxis) {
				$trend .= "
					<div id='trend0".$i."Div'>
					<select id='constraint_table__0__".$i."' name='constraint_table__0__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__0__".$i."&quot;,&quot;constraint_columns__0__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraintval[$i]['constraint_table'])."</select>
					<select id='constraint_columns__0__".$i."' name='constraint_columns__0__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__0__".$i."&quot;,&quot;constraint_columns__0__".$i."&quot;,&quot;constraint_type__0__".$i."&quot;,&quot;constraint_value__0__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraintval[$i]['constraint_table'], $constraintval[$i]['constraint_columns'])."
					</select>
					<select name='constraint_type__0__".$i."' id='constraint_type__0__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__0__".$i."&quot;,&quot;constraint_columns__0__".$i."&quot;,&quot;constraint_type__0__".$i."&quot;,&quot;constraint_value__0__".$i."&quot;);'>
					".Common_Functions::generate_options(array("is"=>"Is (Equals)", "lt"=>"Less Than", "gt"=>"Greater Than", "contains"=>"Contains", "ex"=>"Exists", "dne"=>"Does Not Exist"), $constraintval[$i]['constraint_type'], "Select Constraint Type ...")."</select>
					<input type='text' name='constraint_value__0__".$i."' id='constraint_value__0__".$i."' value='".$constraintval[$i]['constraint_value']."'/>
					<input type='button' value='+' onclick='javascript:addElement(\"constraints__R\", constraintString, \"constraint\", \"R\"); underClip(\"clipper__R\", \"constraints__R\");' name='additional_constraint' >
					";
				if ($i != 0) {
					$trend .= "<input type='button' value='-' onclick='javascript:removeElement(\"constraints__R\", \"R\", \"".$i."\"); underClip(\"clipper__R\", \"constraints__R\");' name='remove_constraint' >";
				}
				$trend .= "<script>constraint_number += " . $i . "</script>";
				$trend .= "</div>
					";
			}
			$trend .= "</div>";
		}
		
		$main .= $trend;
		$main .= "<p><input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__R\"); underClip(\"clipper__R\", \"constraints__R\");''></p>";
		$main .= "<p><input type='text' name='save_as' value='".$values['save_as']."'><input type='submit' name='submit' value='Save As'></p>";
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
		$xml['ATTRS']['NAME']=$report['save_as'];
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
		$csvfile = $report_name.".csv";
		$saveicons = "index.php?command=save_report&query=".$report_name."&report_type=listing&file_name=".$csvfile."'";
		$title = "<p><strong>List Data - ".$report_name."</strong> | <a href='".$saveicons."'>Save Report</a> | <a href='tmp/".$csvfile."'>Download CSV file</a>";
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
		$main .= "<table class='bordered'>";
		$first = true;
		$csv = "";
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
						$main .= "<td class='bordered'><strong>".$k."</strong></td>";
						$csv .= $k;
						if ($k != count($result)-1) {
							$csv .= "	";
						}
					}
					$csv .= "\n";
					$main .= "</tr>";
				}
				$main .= "<tr>";
				$main .= "<td>".($j+1)."</td>";
				if ($report_type == "trend") {
					$main .= "<td>"."</td>";
				}
				foreach ($result as $k => $cell) {
					$csv .= $cell;
					if ($k != count($result)-1) {
						$csv .= "	";
					}
					if ($this->isemail($cell)) {
						$mail .= $cell.",";
						$cell = "<a href='mailto:".$cell."'>".$cell."</a>";
					}
					$main .= "<td class='bordered'>".$cell."</td>";
				}
				$csv .= "\n";
				$main .= "</tr>";
			}
		}
		$main .= "<table>";
		$main .= "</p>";
		file_put_contents("tmp/".$csvfile, $csv);
		if ($mail) {
			$mail = "<p><a href='mailto:?bcc=".$mail."'>Send mail to all listed email addresses</a></p>";
		}
				
		$main = $save_link.$main;
		
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
		$saveddir = $conf['Dir']['FullPath']."/saved/list";
		$path = $saveddir."/".$query;
		if ($which=="") {
			$main = "<h4>Saved '$query' Reports:</h4>";
			if (!is_dir($saveddir)) {
				mkdir($saveddir);
			}
			
			if (!is_dir($path)) {
				mkdir($path);
			}
			
			$dh = opendir($path);
			$files = scandir($path);
			$reports = array();
			
			if (count($files) > 0) {
				foreach ($files as $file) {
					if (preg_match("/\.csv/", $file)) {
						$tmp = preg_replace(array("/.csv/"), array(""), $file);
						$main .= "$tmp: <a href='index.php?command=view_report&saved_report=$query&report_type=listing&id=$tmp'>view</a> <a href='index.php?command=delete_report&saved_report=$query&report_type=listing&id=$tmp'>delete</a><br>";
					}
				}
			} else {
				$main .= "No saved report found!";
			}
			return $main;
		} else {
			$file_name = $path."/$which.csv";
			#$main = "<a href='toPDF.php?saved_report=$query&report_type=table&id=$which'>Download PDF version</a><br/><br/>";
					global $pdficons;
					$pdficons = "toPDF.php?saved_report=".$query."&report_type=listing&id=".$which."'";
			$main .= "<h3>Raw Data - $query";
			if (file_exists($file_name)) {
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
		$filename = $conf['Dir']['FullPath']."/saved/list/$query/$which.csv";
		if (file_exists($filename)) {
			$res = unlink($filename);
		}
		return $this->displayReport($query);
	}
	
	/**
	 * saveReport saves the results of a report template execution into
	 * a file on the server; so we don't need to run the report again to
	 * see the results.
	 * @return	string
	 */
	function saveReport() {
		global $conf;
		$file_name = $_GET['file_name'];
		$path = $conf['Dir']['FullPath']."/saved/list";
		$dest_dir = $path."/".$_GET['query'];
		if (!is_dir($path)) {
			mkdir ($path);
		}
		
		if (!is_dir($dest_dir)) {
			mkdir($dest_dir);
		}
		
		copy($conf['Dir']['FullPath']."/tmp/".$file_name, $dest_dir."/".date("Y-m-d-H-i-s").".csv");
		return $this->displayReport($_GET['query']);
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
				
				/* We know there will always be an X-Axis */
				$constraintCell[0]['start_timestamp']['CONSTRAINT_TABLE'] = $table[$child['CHILDREN'][0]['TAGDATA']];
				$constraintCell[0]['start_timestamp']['CONSTRAINT_COLUMNS'] = 'start_timestamp_id';
				$constraintCell[0]['start_timestamp']['CONSTRAINT_TYPE'] = 'lte';
				$constraintCell[0]['start_timestamp']['CONSTRAINT_VALUE'] = $_GET['point'];
				$constraintCell[0]['end_timestamp']['CONSTRAINT_TABLE'] = $table[$child['CHILDREN'][0]['TAGDATA']];
				$constraintCell[0]['end_timestamp']['CONSTRAINT_COLUMNS'] = 'end_timestamp_id';
				$constraintCell[0]['end_timestamp']['CONSTRAINT_TYPE'] = 'gt';
				$constraintCell[0]['end_timestamp']['CONSTRAINT_VALUE'] = $_GET['point'];
				$set[] = "DISTINCT";
			}
			foreach ($cell as $i => $entry) {
				if ($entry['TABLE'] == "") {
					continue;
				}
				$table[$entry['TABLE']] = $entry['TABLE'];
				$setString = $entry['TABLE'].".".$entry['COLUMNS']." ";
				if ($entry['TITLE']) {
					$setString .= "as \"".$entry['TITLE']."\"";
				}
				$set[] = $setString;
			}
			foreach ($constraintCell[0] as $j => $c_entry) {
				if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
					continue;
				}
				if ($c_entry['CONSTRAINT_COLUMNS'] == "start_timestamp_id" || $c_entry['CONSTRAINT_COLUMNS'] == "end_timestamp_id") {
					$timestamp_done = true;
				}
				$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
				if ($c_entry['CONSTRAINT_TYPE'] == "is") {
					$condition = "=";
				} elseif ($c_entry['CONSTRAINT_TYPE'] == "contains") {
					$condition = "like";
					$c_entry['CONSTRAINT_VALUE'] = "%".$c_entry['CONSTRAINT_VALUE']."%";
				} elseif ($c_entry['CONSTRAINT_TYPE'] == "gt") {
					$condition = ">";
                                } elseif ($c_entry['CONSTRAINT_TYPE'] == "lt") {
                                        $condition = "<";
                                } elseif ($c_entry['CONSTRAINT_TYPE'] == "ex") {
                                        $condition = " is not null";
                                        $c_entry['CONSTRAINT_VALUE'] = "";
				} elseif ($c_entry['CONSTRAINT_TYPE'] == "dne") {
					$condition = " is null";
                                        $c_entry['CONSTRAINT_VALUE'] = "";
				} elseif ($c_entry['CONSTRAINT_TYPE'] == "lte") {
					$condition = "<=";
				}
				if ($condition == '<' or $condition == '>' or $condition == '<=') {
					if (is_numeric($c_entry['CONSTRAINT_VALUE'])) {
						$c_entry['CONSTRAINT_TABLE'] = "cast(".$c_entry['CONSTRAINT_TABLE'];
						$c_entry['CONSTRAINT_COLUMNS'] = $c_entry['CONSTRAINT_COLUMNS']." as int)";
					} elseif (strtotime($c_entry['CONSTRAINT_VALUE'])) {
                                                $c_entry['CONSTRAINT_TABLE'] = "cast(".$c_entry['CONSTRAINT_TABLE'];
						$c_entry['CONSTRAINT_COLUMNS'] = $c_entry['CONSTRAINT_COLUMNS']." as timestamp)";
					}
				} 
				if ($c_entry['CONSTRAINT_VALUE'] != "") {
					$c_entry['CONSTRAINT_VALUE'] = "'".$c_entry['CONSTRAINT_VALUE']."'";
				}
				$where[] = $c_entry['CONSTRAINT_TABLE'].".".$c_entry['CONSTRAINT_COLUMNS']." ".$condition." ".$c_entry['CONSTRAINT_VALUE']."";
			}
			if (!$timestamp_done) {
				$where[] = $entry['TABLE'].".end_timestamp_id = '$maxTimestamp'";
			}
			
			foreach ($table as $k => $t_entry) {
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
			$order = "order by ".$cell[0]['TABLE'].".".$cell[0]['COLUMNS'];
			$query[] = Database::buildQuery($type, $table, $where, $set, $order);
		}
		
		return $query;
	}
	
}











?>
