<?php

/**
 * trend.php -- Implements the trend module
 * @package Modules--Trend
 * @author Andy White <andy@lgsolutions.com.au>
 * @version 2.0
 */

/**
 * Trend Module
 *
 * This module implements trend reports - lines and points on
 * a graph representing data elements.
 *
 * @package Modules--Trend
 * @author Andy White
 *
 */

class Trend extends Common_Functions {
	
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
	
		/* This displays the trend report generator  */
		$main .= "<input type='hidden' value='0' id='id_number' />";
		$main .= "<form name='trend' action='index.php?command=save_template&report_type=trend&saved_report='".$saved_report['ATTRS']['NAME']."' method='POST' >";
		$main .= "<input type='hidden' value='save_template' id='command' name='command'/>";
		$main .= "<input type='hidden' name='report_type' value='trend'>"; 
		
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
			$values['start_date'] = $saved_report['ATTRS']['START_DATE'];
			$values['end_date'] = $saved_report['ATTRS']['END_DATE'];
			$values['interval'] = $saved_report['ATTRS']['INTERVAL'];
			if ($saved_report['ATTRS']['GRAPHTYPE'] == "line") {
				$graphtypeLine = "selected";
			} elseif ($saved_report['ATTRS']['GRAPHTYPE'] == "line3d") {
				$graphtypeLine3d = "selected";
			} elseif ($saved_report['ATTRS']['GRAPHTYPE'] == "bar") {
				$graphtypeBar = "selected";
			} elseif ($saved_report['ATTRS']['GRAPHTYPE'] == "bar3d") {
				$graphtypeBar3d = "selected";
			}
			if ($saved_report['ATTRS']['SHOWVALUE'] == "on") {
				$showValueChecked = "checked";
			}
			foreach ($saved_report['CHILDREN'] as $i => $rulevalue) {
				switch ($rulevalue['NAME']) {
					case "LINKTO":
						$values['LINKTO'] = $rulevalue['TAGDATA'];
						break;
					case "TREND":
						$trendnumber = $rulevalue['ATTRS']['NAME'];
						foreach ($rulevalue['CHILDREN'] as $j => $trendvalue) {
							switch ($trendvalue['NAME']) {
								case "SQL":
									$showSql = true;
									$constraintnumber = $trendvalue['ATTRS']['NAME'];
									$constraintval[$trendnumber]['sql'] = $trendvalue['TAGDATA'];
								case "CONSTRAINT":
									$constraintnumber = $trendvalue['ATTRS']['NAME'];
									foreach ($trendvalue['CHILDREN'] as $k => $constraintvalue) {
										$constraintval[$trendnumber][$constraintnumber][strtolower($constraintvalue['NAME'])] = $constraintvalue['TAGDATA'];
									}
									break;
								default:
									$trendval[$trendnumber][strtolower($trendvalue['NAME'])] = $trendvalue['TAGDATA'];
									break;
							}
						}
						break;
				}
			}
		} else {
			$values['save_as'] = $_SESSION['username']."-".date("Y-m-d H-i");
			$values['start_date'] = "Start Date (YYYY-MM-DD)";
			$values['end_date'] = "End Date (YYYY-MM-DD)";
			$values['LINKTO'] = "";
			$trendval[0]['title'] = "";
			$trendval[0]['table'] = "";
			$trendval[0]['columns'] = "";
			$trendval[0]['colour'] = "";
			$constraintval[0][0]['constraint_table'] = "";
			$constraintval[0][0]['constraint_columns'] = "";
			$constraintval[0][0]['constraint_type'] = "";
			$constraintval[0][0]['constraint_value'] = "Value";
			$highestVal = 0;
		}
		$highestTrendVal = $trendnumber;
		$highestConsVal = $constraintnumber;
	
		$main .= "<script>
			".showConstraintBlock($tableList)."
			".showSQLTrendBlock($tableList)."
			".showTrendBlock($tableList)."
		</script>";
		$trend .= "<div id='trend_lines'>";
		/* Print the trend number line */
		$trend_nos = "<script>trend_number=".$highestTrendVal."</script><script>constraint_number=".$highestConsVal."</script>";
		$trend .= "<span id='trend_no' class='trend_no'>".$trend_nos."</span>";
		/* Create clickable divs for left/right arrows */
		$trend .= "<div id='left_arrow' class='left_arrow' onclick='javascript:moveLeft();' style='position:absolute; top: -5px; left: 55px; height: 25px; width: 25px'></div>";
		$trend .= "<div id='right_arrow' class='right_arrow' onclick='javascript:moveRight();' style='position:absolute; top: -5px; left: 180px; height: 25px; width: 25px'></div>";
		/* Print each existing trend block */
		foreach($trendval as $i => $trendaxis) {
			$trend .= "<div id='trendlines__".$i."' class='trendlines'".$invisible.">";
			$trend .= "<p>#".($i+1)." <input id='title__".$i."' type='text' name='title__".$i."' value='".$trendaxis['title']."' class='title'><br/></p>";
			if ($showSql == true) {
				/* Which javascript block to load */
				$block = "sqlTrendString";
				$trend .= "<p><textarea name='sqlquery__".$i."' class='sql' rows='9'>".$constraintval[$i]['sql']."</textarea><br/>";
			} else {
				/* Which javascript block to load */
				$block = "trendString";
				$trend .= "<p><select id='table__".$i."' name='table__".$i."' onChange='javascript:changeOptions(&quot;table__".$i."&quot;,&quot;columns__".$i."&quot;);' >".
				Common_Functions::generate_options($tableList, $trendaxis['table'])."</select><br/></p>";
				$trend .= "<p><select id='columns__".$i."' name='columns__".$i."'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendaxis['table'], $trendaxis['columns'])."</select><br/></p>";
				$trend .= "<p><select id='aggregate__".$i."' name='aggregate__".$i."'>".
				Common_Functions::generate_options(array('count once'=>'count once', 'count'=>'count', 'avg'=>'average', 'max'=>'max', 'min'=>'min'), $trendaxis['aggregate'], "Select Aggregate Type ...")."</select><br/></p>";
				$trend .= "<p><input type='button' value='Constraints' id='constraints' onclick='makeVisible(\"constraints__".$i."\"); underClip(\"clipper__".$i."\", \"constraints__".$i."\");'></p>";
				$trend .= "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__".$i."' id='clipper__".$i."' width='0' height='0'></iframe>
	<div class='constraints' id='constraints__".$i."'>
				<strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__".$i."\"); makeInvisible(\"clipper__".$i."\");'><br/><br/>";
				foreach($constraintval[$i] as $key => $constraintaxis) {
					$trend .= "<div id='trend".$i.$key."Div'>";
					$trend .= "<select id='constraint_table__".$i."__".$key."' name='constraint_table__".$i."__".$key."' onChange='javascript:changeOptions(&quot;constraint_table__".$i."__".$key."&quot;,&quot;constraint_columns__".$i."__".$key."&quot;);' >".Common_Functions::generate_options($tableList, $constraintaxis['constraint_table'])."</select>
					<select id='constraint_columns__".$i."__".$key."' name='constraint_columns__".$i."__".$key."' onChange='javascript:changeColumn(&quot;constraint_table__".$i."__".$key."&quot;,&quot;constraint_columns__".$i."__".$key."&quot;,&quot;constraint_type__".$i."__".$key."&quot;,&quot;constraint_value__".$i."__".$key."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraintaxis['constraint_table'], $constraintaxis['constraint_columns'])."</select>
					<select name='constraint_type__".$i."__".$key."' id='constraint_type__".$i."__".$key."' onChange='javascript:changeColumn(&quot;constraint_table__".$i."__".$key."&quot;,&quot;constraint_columns__".$i."__".$key."&quot;,&quot;constraint_type__".$i."__".$key."&quot;,&quot;constraint_value__".$i."__".$key."&quot;);'>".Common_Functions::generate_comparisons($constraintaxis['constraint_type']) . "</select>" .
					Common_Functions::generate_value_box($constraintaxis['constraint_table'], $constraintaxis['constraint_columns'], $_SESSION['curDB_psql'], "constraint", $i, $key, $constraintaxis['constraint_value']) . "
					<input type='button' value='+' onclick='javascript:addElement(\"constraints__".$i."\", constraintString, \"constraint\", null); underClip(\"clipper__".$i."\", \"constraints__".$i."\");' name='additional_constraint' />";
					if ($key != 0) {
						$trend .= "<input type='button' value='-' onclick='javascript:removeElement(\"constraints__".$i."\", ".$i.", \"".$key."\"); underClip(\"clipper\", \"constraints__".$i."\");' name='remove_constraint' />";
					}
					$trend .= "</div>";
				}
				$trend .= "</div>";
			}
			
			$trend .= "<input type='button' onclick='makeInvisible(this.parentNode.id); cloneElement(\"trend_lines\", \"trendlines\", \"trend\", null)' name='additional_trend' value='Add an additional trend line' style='zIndex:0' /><br/>";
			if($i != 0) {
				$trend .= "<input type='button' onclick='removeTrend(\"trend_lines\", this.parentNode.id)' name='remove_trend' value='Remove this trend line' /><br/>";
			}
			$trend .= "</div>";
			$invisible = "style='display: none;'";
		}
		
		$trend .= "</div>";
		$time .= "<table border=0>
			<tr><td colspan='3'><img src='images/axis.png' alt='trend_graph'></td></tr>";
		$time .= "<tr><td valign='bottom' ><input type=image id='start_trigger' src='images/calendar.png' alt='...'> <input type='text' id='start_date' name='start_date' value='".$values['start_date']."'/></td>";
		$time .= "<td colspan='2' align='right'><input type='text' id='end_date' name='end_date' value='".$values['end_date']."'/> 
			<input type=image id='end_trigger' src='images/calendar.png' alt='...'></td></tr>";
		$time .= "<tr><td><select name='interval'>".Common_Functions::generate_options(array("day"=>"daily", "week"=>"weekly", "month"=>"monthly", "quarter"=>"quarterly", "year"=>"yearly"), $values['interval'], "Select Interval ...")."
			</select></td></tr></table>";
		$time .= "<script type='text/javascript'>Calendar.setup( { inputField : 'start_date', ifFormat : '%Y-%m-%d', button : 'start_trigger' } );</script>";
		$time .= "<script type='text/javascript'>Calendar.setup( { inputField : 'end_date', ifFormat : '%Y-%m-%d', button : 'end_trigger' } );</script>";
		
		$main .= $trend;
		$main .= $time;
		$main .= "<p><br/><br/><br/>";
		if($showSql != true) {
			$main .= "Link this to ".showLinkTo("table", $values['LINKTO'])."<br/>";
		}
		$main .= "Graph Type: <select name='graphtype'><option value='line' ".$graphtypeLine.">Line Graph</option><option value='line3d' ".$graphtypeLine3d.">Line 3D Graph</option><option value='bar' ".$graphtypeBar.">Bar Graph</option><option value='bar3d' ".$graphtypeBar3d.">Bar 3D Graph</option></select><br/>";
		$main .= "Show Values in Graph: <input type='checkbox' name='showValue' ".$showValueChecked."><br/>";
		$main .= "<input type='text' name='save_as' value='".$values['save_as']."'><input type='submit' name='submit' value='Save As'></p>";
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
		$xml['NAME'] = 'REPORT';
		$xml['ATTRS']['NAME']=$report['save_as'];
		$xml['ATTRS']['REPORT_TYPE']='trend';
		$xml['ATTRS']['START_DATE']=$report['start_date'];
		$xml['ATTRS']['END_DATE']=$report['end_date'];
		$xml['ATTRS']['INTERVAL']=$report['interval'];
		$xml['ATTRS']['GRAPHTYPE']=$report['graphtype'];
		$xml['ATTRS']['SHOWVALUE']=$report['showValue'];
		$xml['CHILDREN'] = array();
		$xml['CHILDREN'][0]['NAME']='linkto';
		$xml['CHILDREN'][0]['TAGDATA']=$report['linkTo'];
		
		foreach ($report as $i => $data) {
			if ($i == "save_as" || $i == "start_date" || $i == "end_date" || $i == "interval" || $i == "linkTo") {
				continue;
			}
			$keys = explode("__", $i);
			if (count($keys) == 2) {
				$trend[$keys[1]][$keys[0]] = $data;
			}
			if (count($keys) == 3) {
				$constraints[$keys[1]][$keys[2]][$keys[0]] = $data;
			}
		}
		foreach ($trend as $i => $data) {
			$trend = array();
			$trend['NAME'] = 'trend';
			$trend['ATTRS']['NAME']=$i;
			if ($data['sqlquery']) {
				$trend['CHILDREN'][0]['NAME']='title';
				$trend['CHILDREN'][0]['TAGDATA']=$data['title'];
				$trend['CHILDREN'][1]['NAME']='sql';
				$trend['CHILDREN'][1]['TAGDATA']=stripslashes($data['sqlquery']);
				$trend['CHILDREN'][2]['NAME']='colour';
				$trend['CHILDREN'][2]['TAGDATA']=$data['icolour'];
			} else {
				$trend['CHILDREN'][0]['NAME']='title';
				$trend['CHILDREN'][0]['TAGDATA']=$data['title'];
				$trend['CHILDREN'][1]['NAME']='table';
				$trend['CHILDREN'][1]['TAGDATA']=$data['table'];
				$trend['CHILDREN'][2]['NAME']='columns';
				$trend['CHILDREN'][2]['TAGDATA']=$data['columns'];
				$trend['CHILDREN'][3]['NAME']='aggregate';
				$trend['CHILDREN'][3]['TAGDATA']=$data['aggregate'];
				$trend['CHILDREN'][4]['NAME']='colour';
				$trend['CHILDREN'][4]['TAGDATA']=$data['icolour'];
			}
			
			if (is_array($constraints[$i])) {
				foreach ($constraints[$i] as $j => $condata) {
					$cons = array();
					$cons['NAME']='constraint';
					$cons['ATTRS']['NAME']=$j;
					$cons['CHILDREN'][0]['NAME']='constraint_table';
					$cons['CHILDREN'][0]['TAGDATA']=$condata['constraint_table'];
					$cons['CHILDREN'][1]['NAME']='constraint_columns';
					$cons['CHILDREN'][1]['TAGDATA']=$condata['constraint_columns'];
					$cons['CHILDREN'][2]['NAME']='constraint_type';
					$cons['CHILDREN'][2]['TAGDATA']=$condata['constraint_type'];
					$cons['CHILDREN'][3]['NAME']='constraint_value';
					$cons['CHILDREN'][3]['TAGDATA']=$condata['constraint_value'];
					array_push($trend['CHILDREN'], $cons);
				}
			}
			array_push($xml['CHILDREN'], $trend);
		}
		return $xml;
	}

	/**
	 * getIntervalLabel determines the label for a given interval period
	 * ie. if the interval is the string 'day', the label will be 'Days'.
	 *
	 */
	function getIntervalLabel($interval) {
		switch($interval) {
			case 'day':
				return "Days";
			break;
			case 'week':
				return "Weeks";
			break;
			case 'month':
				return "Months";
			break;
			case 'quarter':
				return "Quarter";
			break;
			case 'year':
			default:
				return "Years";
			break;
		}
	}


	/**
	 * runReport runs a specified set of rules through the database via
	 * runQuery(), takes the results and builds the image from them.
	 * @param	string	rules	The XML rules to run through the system
	 * @return 	string
	 */
	function runReport($rules) {
		$report_name = $report['ATTRS']['NAME'];
		$report_type = $report['ATTRS']['REPORT_TYPE'];

		$main .= "<p>";
		$queries = Database::generateSQL($rules);
		$interval = $rules['ATTRS']['INTERVAL'];
		$x_axis = $this->getIntervalLabel($interval);
		$no = 0;
		foreach ($rules['CHILDREN']  as $i => $child) {
			if ($child['NAME'] == 'TREND') {
				foreach ($child['CHILDREN'] as $child2) {
					switch ($child2['NAME']) {
						case "TITLE":
							$name = $child2['TAGDATA'];
							break;
						case "COLOUR":
							$colour[$name] = $child2['TAGDATA'];
							break;
					}
				}
				/* Run each timestamp query */
				foreach ($queries[$name] as $j => $indires) {
					//$this->addToLog(1, "JELLYBEAN - ".$j);
					if ($interval == "year") {
						$j = date("m/Y", strtotime($j)); 
					} elseif ($interval == "month" || $interval == "quarter") {
						$j = date("m/Y", strtotime($j));
					} else {
						$j = date("d/m/Y", strtotime($j));
					}
					$curresults = Database::runQuery($indires, $_SESSION['curDB_psql']);
					$results[$name][$j] = $curresults[0]['point'];
					$realTime[] = $j;
				}
				$queryString .= "<strong>Query: </strong>".$query."<br/><br/>";
				$no++;
			} elseif ($child['NAME'] == 'LINKTO') {
				if ($child['TAGDATA'] != "") {
					$usemap = "usemap='#trendmap'";
					$linkTo = $child['TAGDATA'];
				}
			}
		}
		error_log("CREATING GRAPH: " . print_r($results, true));
		$imgname = $this->drawGraph($results,  true, $report_name, $x_axis, "Values", $colour, $linkTo, $realTime, $rules['ATTRS']['SHOWVALUE'], 1024, 768, $rules['ATTRS']['GRAPHTYPE']);
		$main .= $imgname[1];
		
		$saveicons = "index.php?command=save_report&query=".$rules['ATTRS']['NAME']."&report_type=trend&file_name=".$imgname[0]."'";
		$title = "<p><strong>Trend Report - ".$report_name."</strong> | <a href='".$saveicons."'>Save Report</a> | <a href='tmp/trend-".$report_name.".jpg'>Download jpeg file</a>";
	
		/* Print the results in tabular form as well. */
		$table = "<table class='bordered'>";
		$first = 1;
		foreach ($results as $i => $res) {
			$line = "<tr>";
			$line .= "<td><strong>".$i."</strong></td>";
			foreach ($res as $j => $cell) {
				$firstline .= "<td>".$j."</td>";
				$line .= "<td>".$cell."</td>";
			}
			if ($first == 1) {
				$table .= "<tr><td></td>".$firstline."</tr>";
				$first = 0;
			}
			$line .= "</tr>";
			$table .= $line;
		}
		$table .= "</table>";
		$main .= $table;
		global $conf;
		$filename = $conf['Dir']['FullPath']."/tmp/".$report_name."-table.txt";
		file_put_contents($filename, $table);
		$main .= "<object type='image/svg+xml' width='100%' height='100%' border='0' data='tmp/".$imgname[0]."' ".$usemap."/><br/><br/>
		";
		$main .= "</p>";
		return $title.$main;
}


	
	/**
	 * displayReport displays a pre-generated trend image, with various 
	 * controls to modify it.
	 * @param	string	$query		The name of the report template
	 * @param	string	$which		The name of the stored results to load
	 * @return	string
	 */
	function displayReport($query, $which="") {
		global $conf;
		$saveddir = $conf['Dir']['FullPath']."/saved/trend";
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
			
			if (count($files)>0) {
				foreach ($files as $file) {
					if (preg_match("/\.svg/", $file)) {
						$tmp = preg_replace(array("/.svg/"), array(""), $file);
						$main .= "$tmp: <a href='index.php?command=view_report&saved_report=$query&report_type=trend&id=$tmp'>view</a> <a href='index.php?command=delete_report&saved_report=$query&report_type=trend&id=$tmp'>delete</a><br>";
					}
				}
			} else {
				$main .= "No saved report found!";
			}
			return $main;
		} else {
			if (file_exists($path."/$which.svg")) {
				$main = "<h3>Report - $query</h3>";
				$main .= file_get_contents($path."/".$which.".txt");
				$main .= "<p><object width='1024' height='768' border='0' data='saved/trend/".$query."/".$which.".svg'/><br/><br/></p>";
				global $pdficons;
				$pdficons = "toPDF.php?saved_report=".$query."&report_type=trend&id=".$which."'";
				$main .= "<a href='#' onclick='window.history.back();return false;'>back</a>";
			} else {
				$main = "File not found<br/><br/>";
			}
			return $main;
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
		$filename = $conf['Dir']['FullPath']."/saved/trend/$query/$which.svg";
		$filename2 = $conf['Dir']['FullPath']."/saved/trend/$query/$which.txt";
		if (file_exists($filename)) {
			$res = unlink($filename);
		}
		if(file_exists($filename2)) {
			$res = unlink($filename2);
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
		$path = $conf['Dir']['FullPath']."/saved/trend";
		$dest_dir = $path."/".$_GET['query'];
	
		if (!is_dir($path)) {
			mkdir($path);
		}
		
		if (!is_dir($dest_dir)) {
			mkdir($dest_dir);
		}
		
		copy($conf['Dir']['FullPath']."/tmp/".$_GET['query']."-table.txt", $dest_dir."/".date("Y-m-d-H-i-s").".txt");
		copy($conf['Dir']['FullPath']."/tmp/".$file_name, $dest_dir."/".date("Y-m-d-H-i-s").".svg");
		return $this->displayReport($_GET['query']);
	}
	

	/**
	 * drawGraph
	 * 
	 * Draws either a bar or line graph, based on an array of numeric data
	 * passed to the function. Non-numeric data within the array will not be
	 * entirely ignored - so don't pass in anything but numeric data!
	 * The keys of the array are used as the y-values for the graph.
	 *
	 * @param   integer $width          The width of the desired graph
	 * @param   integer $height         The height of the desired graph
	 * @param   array   $array          Array of numeric data
	 * @param   bool    $linegraph      If true, line. If false, bar graph.
	 * @param   string  $title          Title of the graph
	 * @param   string  $x-title        Title of the x-axis
	 * @param   stringy $y-title        Title of the y-axis
	 * @return  string                  The filename of the image in WWW/tmp
	 */
	function drawGraph($array, $linegraph=true, $title="", $x_title = "", $y_title = "", $colours=array(), $linkTo="", $realTime=array(), $showValue='', $width=1024, $height=768, $type="line") {
	
		if ($type == "bar3d") {
			$graph = new ezcGraphBarChart();
			$graph->renderer = new ezcGraphRenderer3d();
			$graph->renderer->options->legendSymbolGleam = .5;
			$graph->renderer->options->barChartGleam = .5;		// Output graph and clear image from memory
		} elseif ($type == "bar") {
			$graph = new ezcGraphBarChart();
		} elseif ($type == "line3d") {
			$graph = new ezcGraphLineChart();
			$graph->renderer = new ezcGraphRenderer3d();
			$graph->renderer->options->legendSymbolGleam = .5;
			$graph->renderer->options->barChartGleam = .5;		// Output graph and clear image from memory
		} else {
			$graph = new ezcGraphLineChart();
		}
		$graph->background->color = '#ffffff';
		$graph->legend->title = 'Legend';
		$graph->legend->position = ezcGraph::BOTTOM;
		$graph->legend->landscapeSize = .3;
		$graph->options->fillLines = 210;
		$graph->title->font->name = 'sans-serif';
		$graph->title->font->maxFontSize = '16';
 		$graph->title = $title;
		$graph->options->font->maxFontSize = 12;
		foreach ($array as $i => $row) {
			$graph->data[$i] = new ezcGraphArrayDataSet( $row );
		}
		
		$filename = "trend-".$title.".svg";
		$full_filename = "tmp/" . $filename;


		$graph->render( $width, $height, $full_filename );
		$execoutput = array();
		exec('convert -font Helvetica "'.$full_filename.'" "tmp/trend-'.$title.'.jpg"', $execoutput);
		error_log("Converting: ".$full_filename." to tmp/trend-".$title.".png. Results: ".print_r($execoutput, true));
		return array($filename, $map);
		
	}

	/**
	 * getStartDate returns the start date of a given interval period, based on
	 * the presence/absence of a specified start date or the interval chosen
	 * for the report.
	 *
	 * @comment Broken out of buildSQL() on 24/04/07 by AW
	 *
	 * @param	string	$startdate		The start date, or nothing
	 * @param	string	$interval		The interval, or nothing
	 */
	 function getStartDate($startdate, $interval) {
		$date = strtotime($startdate);
		if($date == -1 || $date == 0) {
		  // Time was not set; default based on interval
		  switch($interval) {
		    case "day":
			     $date = strtotime("-1 month");
			break;
		    case "week":
			     $date = strtotime("-3 months");
		     break;
			 case "month":
		         $date = strtotime("-1 year");
			 break;
		     case "Year":
			 case "year":
			 default:
		         $date = strtotime("-10 years");
			 break;
		    }
		}
		return $date;
	 }

	 /**
	  * getEndDate returns the end date of a given interval period, based on
	  * the end date specified in the report or, in the absence of that value,
	  * the current date.
	  *
	  * @comment Broken out of buildSQL on 24/04/07 by AW
	  * @param	string	$enddate		The end date specified, or nothing
	  * 
	  * @return string	The end date for this report
	  */
	  function getEndDate($enddate) {
		$date = strtotime($enddate);
		    
		if ($date > time() || $date == -1 || !is_int($date)) {
		  $date = strtotime("-1 day");
	    }

		return $date;
	  }

	  /**
	   * buildAggregate sets the appropriate aggregate statement for this datapoint
	   *
	   * @comment Broken out on 24/04/07 by AW
	   * 
	   * @param		string	$aggregate		The aggregate string - count once, percentage, max etc.
	   * @param		string	$table			The table we're operating on
	   * @param		string	$columns		The column we're operating on
	   *
	   * @return	string	The SQL aggregate line
	   */
		function buildAggregate($aggregate, $table, $columns) {
			if ($aggregate == 'count once' || $aggregate == 'percentage') {
				return "count (distinct ".$table.".".$columns.") as point";
	    	} elseif ($aggregate == 'avg') {
				return "round(".$aggregate."(cast(".$table.".".$columns." as int)), 2) as point";
			} elseif ($aggregate == 'max' || $aggregate == 'min') {
				return $aggregate."(cast(".$table.".".$columns." as int)) as point";
			} else {
				return $aggregate."(".$table.".".$columns.") as point";
			}
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
			case 'is':
				$condition = "=";
			break;
			case 'contains':
				$condition = "like";
				$value = "%".$value."%";
			break;
			case 'gt':
				$condition = ">";
			break;
			case 'lt':
				$condition = "<";
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
	 * @param	array	           $cell						A pre-computed array of values from the XML ruleset
	 * @param	array            $constraintCell		A pre-computed array of values from the XML ruleset
	 * @param	array	$timeCell				A pre-computed array of values from the XML ruleset
	 * @param	array	$maxTimestamp	The maximum timestamp in the database
	 * @param	array	$minTimestamp	The minimum timestamp in the database
	 * @return	array	The queries to be executed		
	 */
     
     //TODO: Refactor more!
	function buildSQL($rules, $linkFromRules, $line_no = -1, $cell, $constraintCell, $timeCell, $maxTimestamp, $minTimestamp, $type) {

		$interval = $rules['ATTRS']['INTERVAL'];
		
		/* Compute start and end dates for the query ... */
		$startdate = $this->getStartDate($timeCell['START_DATE'], $interval);
		$enddate = $this->getEndDate($timeCell['END_DATE']);
		
		//$this->addToLog(1, 'FHGWGAHDS - '.$enddate);

		foreach ($cell as $i => $entry) {
			if ($entry['TABLE'] == "" && !$entry['SQL']) {
				continue;
			}
		
			$table = array();
			$set = array();
			$where = array();
			$order = "";
			$table_cells = array();

			if ($entry['SQL']) { 
				$newquery = Database::getTables($entry['SQL'], $maxTimestamp, true, $allTables);

				$curdate = $startdate;

				/* Between the start date and end date runs queries once per interval for all
				   data that starts before and ends after */
				/* TODO: Make that comment make some kind of sense */
				while ($curdate < $enddate) {
		          /* Reload the predated values */
				  $subwhere = $where;

		          /* Set the date format based on the chosen interval */
				  /* TODO: Interval processing here */
				  $date = "m/Y";
		          $curdate_string = date($date, $curdate);
				  $subwhere = $allTables[0].".start_timestamp_id <= '".$curdate_string."'";
		          $subwhere .= " and (" . $allTables[0].".end_timestamp_id > '".date($date, $curdate)."')";

				  /* Store in array in m/d/Y format, cuz PHP is weird and expects it */
				  $query[$entry['TITLE']][date('m/d/Y',$curdate)] = $newquery." and ".$subwhere;
				  
				  $curdate = strtotime("+1 ".$interval, $curdate);
				}
			} else {
				$table[$entry['TABLE']] = $entry['TABLE'];
				
				/* Get the aggregate SQL */
				$set[] = $this->buildAggregate($entry['AGGREGATE'], $entry['TABLE'], $entry['COLUMNS']);

				
				/* For each defined constraint ... */
				foreach ($constraintCell[$i] as $j => $c_entry) {
					/* If the user hasn't actually set the constraint's values, skip it */
					if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
						continue;
					}
					
					/* Add the referenced table to the $table array */
					$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
					
					/* Now generate a where clause for this constraint rule */
					$where[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
				}
				
				/* Put together the tables to be referenced, and any foreign-key comparisons that need doing */
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
				
				#$order = "group by ".$entry['TABLE'].".start_timestamp_id order by ".$entry['TABLE'].".start_timestamp_id ";
				
				$table = implode(",", $table);
				
				$curdate = $startdate; // At least initially

				/* Between the start date and end date runs queries once per interval for all
				   data that starts before and ends after */
				while ($curdate < $enddate) {
					/* Reload the predated values */
					$subwhere = $where;
					
					/* Set the date format based on the chosen interval */
					if($interval == 'day') {
						$date = "d/m/Y";
					} else {
						$date = "d/m/Y";
					}
					$curdate_string = date($date, $curdate);
					$subwhere[] = $entry['TABLE'].".start_timestamp_id <= '".$curdate_string."'";
					$subwhere[] = "(" . $entry['TABLE'].".end_timestamp_id > '".$curdate_string."')";
					$subwhere = implode(" and ", $subwhere);
					$subwhere .= " ";

					/* Store them in the array in m/d/Y format, cuz PHP is weird and expects it */
					$query[$entry['TITLE']][date('m/d/Y',$curdate)] = Database::buildQuery($type, $table, $subwhere, $set, $order) .";";
					
					if ($interval == "quarter") {
						$curdate = strtotime("+3 months", $curdate);
					} else {
						$curdate = strtotime("+1 ".$interval, $curdate);
					}
				}
			}
		}
		return $query;
	}
}


?>
