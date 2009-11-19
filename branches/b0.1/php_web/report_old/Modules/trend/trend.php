<?php

/**
 * trend.php -- Implements the trend module
 * @package Modules--trend
 * @author Andy White <andy@lgsolutions.com.au>
 * @version 2.0
 */

/**
 * Trend Simple Module
 *
 * This module implements simple trend reports - lines and points on
 * a graph representing data elements.
 *
 * @package Modules--trend
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
		$query = Database::buildQuery("select", "ea_table", array("db_id"=>$_SESSION['curDB']), NULL, " order by ea_table_name");
		$tables = Database::runQuery($query);
		foreach ($tables as $i => $table) {
			$tableList[$table['ea_table_name']] = $table['ea_table_name'];
		}
	
		/* This displays the trend report generator  */
		$main .= "<input type='hidden' value='0' id='id_number' />";
		$main .= "<form name='trend' action='index.php?command=save_template&report_type=trend&saved_report=".$_GET['saved_report']."' method='POST' >";
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
			$saved_report['ATTRS']['NAME'] = str_replace("'","&apos;",$saved_report['ATTRS']['NAME']);

			$this->auditLog("trend", "Viewed saved report template", $saved_report['ATTRS']['NAME'], $_GET['saved_report']);
		
			$values['save_as'] = $saved_report['ATTRS']['NAME'];
			$values['publish_report'] = $saved_report['ATTRS']['PUBLISH'];
			$values['start_date'] = $saved_report['ATTRS']['START_DATE'];
			$values['end_date'] = $saved_report['ATTRS']['END_DATE'];
			$values['interval'] = $saved_report['ATTRS']['INTERVAL'];
			$values['aggregate'] = $saved_report['ATTRS']['AGGREGATE'];
			$values['absolute'] = $saved_report['ATTRS']['ABSOLUTE'];
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
					#Global Constraints
					case "GLOBAL_CONSTRAINT":
						$trendnumber = "G";
						foreach ($rulevalue['CHILDREN'] as $j => $trendvalue) {
							$constraintnumber = $trendvalue['ATTRS']['NAME'];
							foreach ($trendvalue['CHILDREN'] as $k => $constraintvalue) {
								$constraintval[$trendnumber][$constraintnumber][strtolower($constraintvalue['NAME'])] = $constraintvalue['TAGDATA'];
							}
						}
						break;
				}
			}
		} else {
			$values['save_as'] = $_SESSION['username']."-".date("Y-m-d");
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
			trend_number=".max(array_keys($trendval))."
		</script>

		";
		
		
		$trend .= "<table id='trend_table'>";
		$trend .= "<thead><tr>
			<th>Label</th>
			<th>Table</th>
			<th>Column</th>
			<th>Constraints</th>
			<th>&nbsp;</th>
			</tr></thead><tbody id='trend_table_body'>";
		foreach($trendval as $i => $trendaxis) {
			$trend .= "<tr id='trendrow__".$i."'>";
			$trend .= "<td><input id='title__".$i."' type='text' name='title__".$i."' value='".$trendaxis['title']."' class='title'></td>";
			if ($showSql == true) {
				/* Which javascript block to load */
				$trend .= "<td colspan='4'>";
				$trend .= "<textarea name='sqlquery__".$i."' class='sql' rows='9'>".$constraintval[$i]['sql']."</textarea>";
				$trend .= "</td>";
			} else {
				/* Which javascript block to load */
				$trend .= "<td><select id='table__".$i."' name='table__".$i."' onChange='javascript:changeOptions(&quot;table__".$i."&quot;,&quot;columns__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $trendaxis['table'])."</select></td>";
				$trend .= "<td><select id='columns__".$i."' name='columns__".$i."'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendaxis['table'], $trendaxis['columns'])."</select></td>";
				$trend .= "<td><input class='button' type='button' value='Constraints' id='constraints' onclick='makeVisible(\"constraints__".$i."\"); underClip(\"clipper__".$i."\", \"constraints__".$i."\");'>";
				$trend .= "<iframe frameBorder=\"0\" scrolling=\"no\" name='clipper__".$i."' id='clipper__".$i."' width='100%' height='100%'></iframe>
				<div class='constraints' id='constraints__".$i."'>
				<div class='cons_header' id='cons_header__".$i."'><strong>Constraints (".($i+1).")</strong> <input class='button' type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__".$i."\"); makeInvisible(\"clipper__".$i."\");'><br/><br/></div>
				<script type='text/javascript'>
				//<![CDATA[
				new Draggable('constraints__".$i."', {handle:'cons_header__".$i."', starteffect:false})
				//]]>
				</script>
				<div id='constraints_values__".$i."' class='cons_values'>";
				foreach($constraintval[$i] as $key => $constraintaxis) {
					$trend .= "<div id='trend__".$i.$key."Div'>";
					$trend .= "<select id='constraint_table__".$i."__".$key."' name='constraint_table__".$i."__".$key."' onChange='javascript:changeOptions(&quot;constraint_table__".$i."__".$key."&quot;,&quot;constraint_columns__".$i."__".$key."&quot;);' >".Common_Functions::generate_options($tableList, $constraintaxis['constraint_table'])."</select>
					<select id='constraint_columns__".$i."__".$key."' name='constraint_columns__".$i."__".$key."' onChange='javascript:changeColumn(&quot;constraint_table__".$i."__".$key."&quot;,&quot;constraint_columns__".$i."__".$key."&quot;,&quot;constraint_type__".$i."__".$key."&quot;,&quot;constraint_value__".$i."__".$key."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraintaxis['constraint_table'], $constraintaxis['constraint_columns'])."</select>
					<select name='constraint_type__".$i."__".$key."' id='constraint_type__".$i."__".$key."' onChange='javascript:changeColumn(&quot;constraint_table__".$i."__".$key."&quot;,&quot;constraint_columns__".$i."__".$key."&quot;,&quot;constraint_type__".$i."__".$key."&quot;,&quot;constraint_value__".$i."__".$key."&quot;);'>".Common_Functions::generate_comparisons($constraintaxis['constraint_type']) . "</select>" ;
					$trend .= "<input type='text' name='constraint_value__".$i."__".$key."' id='constraint_value__".$i."__".$key."' value='".$constraintaxis['constraint_value']."'>";
#					$trend .= Common_Functions::generate_value_box($constraintaxis['constraint_table'], $constraintaxis['constraint_columns'], $_SESSION['curDB_psql'], "constraint", $i, $key, $constraintaxis['constraint_value']) ;
					if ($key == 0) {
						$trend_cons_class = '__first';
					} else {
						$trend_cons_class = '__notfirst';
					}
					$trend .= "<input class='button ".$trend_cons_class."' type='button' value='-' onclick='javascript:removeElement(\"constraints_values__".$i."\", \"__".$i."\", \"".$key."\"); underClip(\"clipper\", \"constraints__".$i."\");' name='remove_constraint' />";
					$trend .= "</div>";
				}
				$trend .= "</div><br/>";
				$trend .= "<input style='margin-bottom: 20px; margin-left: 20px;' class='button' type='button' value='Add New Constraint' onclick='javascript:addConstraint(\"constraints_values__".$i."\", \"__".$i."\"); underClip(\"clipper__".$i."\", \"constraints__".$i."\");' name='additional_constraint' />";
				$trend .= "</div>";
				$trend .= "</td>";
			}
			$trend .= "<td>";
			$trend .= "<input class='button remove' type='button' onclick='removeTableTrend(\"trend_table_body\", \"trendrow__".$i."\")' name='remove_trend' value='-' /><br/>";
			$trend .= "</td>";
			$trend .= "</tr>";
		}
		
		$trend .= "</tbody></table>";
		$trend .= "<br />";
		#Show the global constraints
		if ($showSql == false) {
			$trend .= "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__G' id='clipper__G' width='0' height='0'></iframe>
			<div class='constraints' id='constraints__G'>
			<div class='cons_header' id='cons_header__G'><strong>Global Constraints</strong> <input class='button' type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__G\"); makeInvisible(\"clipper__G\");'><br/><br/></div>
			<script type='text/javascript'>
			//<![CDATA[
			new Draggable('constraints__G', {handle:'cons_header__G', starteffect:false})
			//]]>
			</script>
			<div id='constraints_values__G' class='cons_values'>";
			foreach ($constraintval['G'] as $i => $constraintaxis) {
				$trend .= "
					<div id='trend__G".$i."Div'>
					<select id='constraint_table__G__".$i."' name='constraint_table__G__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__G__".$i."&quot;,&quot;constraint_columns__G__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraintaxis['constraint_table'])."</select>
					<select id='constraint_columns__G__".$i."' name='constraint_columns__G__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__G__".$i."&quot;,&quot;constraint_columns__G__".$i."&quot;,&quot;constraint_type__G__".$i."&quot;,&quot;constraint_value__G__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraintaxis['constraint_table'], $constraintaxis['constraint_columns'])."
					</select>
					<select name='constraint_type__G__".$i."' id='constraint_type__G__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__G__".$i."&quot;,&quot;constraint_columns__G__".$i."&quot;,&quot;constraint_type__G__".$i."&quot;,&quot;constraint_value__G__".$i."&quot;);'>
					".Common_Functions::generate_comparisons($constraintaxis['constraint_type'])."</select>
					<input type='text' name='constraint_value__G__".$i."' id='constraint_value__G__".$i."' value='".$constraintaxis['constraint_value']."'/>
					";
				if ($constraintaxis['constraint_auto'] == "on") {
					$checked = "checked";
				} else {
					$checked = "";
				}
				$trend .= "<input type='checkbox' name='constraint_auto__G__".$i."' id='constraint_auto__G__".$i."' ".$checked."'/>";
				if ($i == 0) {
					$trend_cons_class = '__first';
				} else {
					$trend_cons_class = '__notfirst';
				}
				$trend .= "<input class='button ".$trend_cons_class."' type='button' value='-' onclick='javascript:removeElement(\"constraints_values__G\", \"G\", \"".$i."\"); underClip(\"clipper__G\", \"constraints__G\");' name='remove_constraint' name='remove_constraint' />";
				$trend .= "<script>constraint_number += " . $i . "</script>";
				$trend .= "</div>
					";
			}
			$trend .= "</div><br/>";
			$trend .= "<input style='margin-bottom: 20px; margin-left: 20px;' class='button setbutton' type='button' value='Add New Constraint' onclick='javascript:addConstraint(\"constraints_values__G\", \"__G\"); underClip(\"clipper__G\", \"constraints__G\");' name='additional_constraint' />";
			$trend .= "</div>";
		}


		$trend .= "<p><input class='button setbutton' type='button' onclick='; cloneTableElement(\"trend_table_body\", \"trendrow\", \"trend\", null)' name='additional_trend' value='Add New Trend Line' style='z-index:0' /></p>";
		$trend .= "<p><input class='button setbutton' type=button value='Global Constraints' id='global_constraints' onclick='makeVisible(\"constraints__G\"); underClip(\"clipper__G\", \"constraints__G\");''></p>";
		$trend .= "<br />";
		$trend .= "<hr>";
		$trend .= "<br />";
		$trend .= "<label for='aggregate'>Aggregate</label><select id='aggregate' name='aggregate'>".Common_Functions::generate_aggregates($values['aggregate'])."</select><br />";
		$trend .= "<label for='start_date'>Start Date: </label><input class='button' type=button id='start_trigger' src='images/calendar.png' value='...'> <input type='text' id='start_date' name='start_date' value='".$values['start_date']."'/><br />";
		$trend .= "<label for='end_date'>End Date: </label><input class='button' type=button id='end_trigger' src='images/calendar.png' value='...'> <input type='text' id='end_date' name='end_date' value='".$values['end_date']."'/><br />";
		$trend .= "<label for='interval'>Interval: </label><select name='interval'>".Common_Functions::generate_options(array("day"=>"daily", "week"=>"weekly", "month"=>"monthly", "quarter"=>"quarterly", "year"=>"yearly"), $values['interval'], "Automatic Interval ...", false)."
			</select><br />";
		$trend .= "<script type='text/javascript'>Calendar.setup( { inputField : 'start_date', ifFormat : '%Y-%m-%d', button : 'start_trigger' } );</script>";
		$trend .= "<script type='text/javascript'>Calendar.setup( { inputField : 'end_date', ifFormat : '%Y-%m-%d', button : 'end_trigger' } );</script>";
		
		$main .= $trend;
		if($showSql != true) {
			#$main .= "Link this to ".showLinkTo("table", $values['LINKTO'])."<br/>";
		}
		$main .= "<label for='graphtype'>Graph Type: </label><select name='graphtype'><option value='line' ".$graphtypeLine.">Line Graph</option><option value='line3d' ".$graphtypeLine3d.">Line 3D Graph</option><option value='bar' ".$graphtypeBar.">Bar Graph</option><option value='bar3d' ".$graphtypeBar3d.">Bar 3D Graph</option></select><br/>";
		$main .= "<br /><br /><br />";
		#$main .= "Show Values in Graph: <input type='checkbox' name='showValue' ".$showValueChecked."><br/>";
 		if ($values['publish_report']) {
 			$pub_sel = "checked";
 		}
 		if ($values['absolute']) {
 			$abs_sel = "selected";
 		}
 		$main .= "<label for='publish_report'>Publish</label><input value='1' type='checkbox' name='publish_report' id='publish_report' ".$pub_sel."><br />";
		$main .= "<label for='absolute'>Time Checks</label><select name='absolute'><option value='0'>Record exists within period</option><option value='1' ".$abs_sel.">Record starts within period</option><option value='2'>Record absolutely starts within period</option></select><br /><br />";
		$main .= "<input type='text' name='save_as' id='save_as' value='".$values['save_as']."'> <input onclick=\"return validate_trend(\$F('start_date'))\" class='button' type='submit' name='submit' value='Save'> <input onclick=\"saveas(\$F('save_as'), 'save_as'); return validate_trend(\$F('start_date'))\" class='button' type='submit' name='submit' value='Save As'></p>";
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
		$xml['ATTRS']['NAME']=str_replace("'","&apos;",$report['save_as']);
		$xml['ATTRS']['PUBLISH']=$report['publish_report'];
		$xml['ATTRS']['REPORT_TYPE']='trend';
		$xml['ATTRS']['START_DATE']=$report['start_date'];
		$xml['ATTRS']['END_DATE']=$report['end_date'];
		$xml['ATTRS']['INTERVAL']=$report['interval'];
		$xml['ATTRS']['GRAPHTYPE']=$report['graphtype'];
		$xml['ATTRS']['SHOWVALUE']=$report['showValue'];
		$xml['ATTRS']['AGGREGATE']=$report['aggregate'];
		$xml['ATTRS']['ABSOLUTE']=$report['absolute'];
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
		#Global Constraints
		if (is_array($constraints["G"])) {
			$trend = array();
			$trend['NAME'] = 'global_constraint';
			$trend['ATTRS']['NAME']='global_constraint';
			$trend['CHILDREN'] = array();
			foreach ($constraints["G"] as $j => $condata) {
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
				$cons['CHILDREN'][4]['NAME']='constraint_auto';
				$cons['CHILDREN'][4]['TAGDATA']=$condata['constraint_auto'];
				array_push($trend['CHILDREN'], $cons);
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
		$report_name = $rules['ATTRS']['NAME'];
		$aggregate = $rules['ATTRS']['AGGREGATE'];
		#$aggregate = $rules['CHILDREN'][1]['CHILDREN'][3]['TAGDATA'];
		$absolute = $rules['ATTRS']['ABSOLUTE'];
		$main .= "<p>";
		$auto = '';
		$autovalues = array();
		$autovalues_string = "";
		#Getting user input
		foreach ($rules['CHILDREN'] as $i => $child) {
			if ($child['NAME'] == "GLOBAL_CONSTRAINT") {
				foreach ($child['CHILDREN'] as $j => $constraint) {
					foreach ($constraint['CHILDREN'] as $k => $attr) {
						$cons[$attr['NAME']] = $attr['TAGDATA'];
						if ($attr['NAME'] == "CONSTRAINT_VALUE") {
							$valk = $k;
						}
						if ($attr['NAME'] == "CONSTRAINT_AUTO" && $attr['TAGDATA'] == "on") {
							if ($_REQUEST['auto'] ) {
								$rules['CHILDREN'][$i]['CHILDREN'][$j]['CHILDREN'][$valk]['TAGDATA'] = $_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']];
								$autovalues[$cons['CONSTRAINT_COLUMNS']] = $_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']];
								$autovalues_string .= "<strong>".ucwords($cons['CONSTRAINT_COLUMNS']).": </strong>".$_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']]."<br />";
							} else {
								$auto .= "<p>Please enter a value for the following fields from the <strong>".$cons['CONSTRAINT_TABLE']."</strong> table that you wish to report on.</br>";

								$auto .= "<input type='hidden' id='".$cons['CONSTRAINT_TABLE']."' value='".$cons['CONSTRAINT_TABLE']."'>";
								$auto .= "<input type='hidden' id='".$cons['CONSTRAINT_COLUMNS']."' value='".$cons['CONSTRAINT_COLUMNS']."'>";
								$auto .= "<input type='hidden' id='is' value='is'>";
								$auto .= "<label style='width:160px;' for='auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."]'>".$cons['CONSTRAINT_COLUMNS']."</label>
								<select id='auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."]' name='auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."]' >".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $cons['CONSTRAINT_TABLE'], $cons['CONSTRAINT_COLUMNS'])."</select>";
								$auto .= "<script>changeColumn('".$cons['CONSTRAINT_TABLE']."', '".$cons['CONSTRAINT_COLUMNS']."', 'is', 'auto[".$cons['CONSTRAINT_TABLE']."][".$cons['CONSTRAINT_COLUMNS']."]');</script>";
								$auto .= "</p>";
							}
							$gcons[] = $cons;
						}
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
		/* We create the heading now to allow for the auto fields to be processed above */
		$top = "<div class='heading'>
		<h3>Trend Report - ".$report_name."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='tmp/".$report_name.$suffix.".pdf'><img src='images/pdf.png' alt='Download PDF' title='Download PDF'></a> <a href='tmp/".$report_name.$suffix.".xls'><img src='images/spreadsheet.png' title='Download Spreadsheet' alt='Download Spreadsheet'></a> <a href='tmp/".$report_name.$suffix.".jpg'><img src='images/graph.png' title='Download Graph' alt='Download Graph'></a></h3>
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

		$queries = Database::generateSQL($rules);
		$title = $rules['ATTRS']['NAME'];
		$interval = $rules['ATTRS']['INTERVAL'];
		$x_axis = $this->getIntervalLabel($interval);
		$no = 0;
		$lasttime = '';
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
					$preTime[] = $j;
					if ($interval == "year") {
						$j = date("Y-m", strtotime($j)); 
					} elseif ($interval == "month" || $interval == "quarter") {
						$j = date("Y-m", strtotime($j));
					} else {
						$j = date("Y-m-d", strtotime($j));
					}
					$curresults = Database::runQuery($indires, $_SESSION['curDB_psql']);
					$results[$name][$j] = $curresults[0]['point'];
					$percentage_sum[$j] += $curresults[0]['point'];
					$percentage_num[$j] ++;
					$realTime[] = $j;
				}
				$queryString .= "<strong>Query: </strong>".$query."<br/><br/>";
				$no++;
			} elseif ($child['NAME'] == 'LINKTO') {
				if ($child['TAGDATA'] != "") {
					$usemap = "usemap='#trendmap'";
					$linkTo = $child['TAGDATA'];
				}
			} elseif ($child['NAME'] == 'GLOBAL_CONSTRAINT') {
				#Global Constraints
				if ($child['TAGDATA'] != "") {
					$usemap = "usemap='#trendmap'";
					$linkTo = $child['TAGDATA'];
				}
			}
		}
		if ($aggregate == "percentage") {
			foreach ($results as $name => $times) {
				foreach ($times as $j => $point) {
					$results[$name][$j] = round($point/$percentage_sum[$j]*100, 2);
				}
			}
		}
		#Check and alert to user if the query returns no results.
		if (!$curresults) {
			$main = Common_Functions::invalid($report_name, "trends");
			return $main;
		}
		error_log("CREATING GRAPH: " . print_r($results, true));
		$imgname = $this->drawGraph($results,  true, $title, $x_axis, "Values", $colour, $linkTo, $realTime, $rules['ATTRS']['SHOWVALUE'], 1000, 500, $rules['ATTRS']['GRAPHTYPE'], $suffix);
		$main .= $imgname[1];

		global $saveicons;
		$saveicons = "index.php?command=save_report&saved_report=".$_GET['saved_report']."&report_type=trend&suffix=".$suffix;
	
		/* Print the results in tabular form as well. */
		$table = "<table class='bordered'>";
		$first = 1;
		$excel = array();
		foreach ($results as $i => $res) {
			$excel[$i] = array();
			$line = "<tr>";
			$line .= "<td><strong>".$i."</strong></td>";
			foreach ($res as $j => $cell) {
				$excel[$i][$j] = $cell;
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
		global $conf;
		$excel = Common_Functions::writeExcel(array($excel), "tmp/".$report_name.$suffix.".xls");
		$pdfmain = "<img src='http://".$_SERVER['SERVER_ADDR']."/".$conf['Dir']['WebPath']."/tmp/".$report_name.$suffix.".jpg' /><br />".$table;
		$lasttime = array_pop($preTime);
		$pdf = Common_Functions::makePdf($pdfmain, $report_name.$suffix, array('start date'=>$rules['ATTRS']['START_DATE'], 'end date'=>$lasttime, 'interval'=>$rules['ATTRS']['INTERVAL']), $autovalues, "landscape"); /* The ./tmp/ directory is automatically added, as is the .pdf extension. */
		$main .= "<object type='image/svg+xml' width='1000' height='500' border='0' data='tmp/".$imgname[0]."' ".$usemap.">Please install the SVG plugin to view this graph.</object><br/><br/>
		";
		$main .= "</p>";
		$main .= $table;
		
		$this->auditLog("trend", "Ran Report", $report_name, $_GET['saved_report']);
		
		return $top.$main;
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
		$report_name = $_SESSION['reports'][$query]['report_name'];
		$saveddir = $conf['Dir']['FullPath']."saved/trend";
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
			
			if (count($files)>2) {
				foreach ($files as $file) {
					if (preg_match("/\.xls$/", $file)) {
						$tmp = preg_replace(array("/.xls$/"), array(""), $file);
						if ($first == true) {
							$main = "<div class='heading'><h3>Current Report:</h3><ul>";
						}
						if (file_exists($path."/".$tmp.".pdf")) {
							$pdf = "<a href='".$conf['Dir']['WebPath']."/saved/trend/".$query."/".$tmp.".pdf'><img src='images/pdf.png' title='View PDF' alt='View PDF'></a>";
						} else {
							$pdf = '';
						}
						if (file_exists($path."/".$tmp.".jpg")) {
							$jpg = "<a href='".$conf['Dir']['WebPath']."/saved/trend/".$query."/".$tmp.".jpg'><img src='images/graph.png' title='View PDF' alt='View PDF'></a>";
						} else {
							$jpg = '';
						}
						$main .= "<li>$tmp: <a href='".$conf['Dir']['WebPath']."/saved/trend/$query/$file'><img src='images/spreadsheet.png' title='View Spreadsheet' alt='View Spreadsheet'></a> ".$pdf." ".$jpg."</li>";
						# <a href='index.php?command=delete_report&saved_report=$query&report_type=trend&id=$tmp'>delete</a>
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
			
			$lastupdate = $this->getLastUpdate('trend', $report_name);
			if($lastupdate[0]['username'] != null && $lastupdate[0]['username'] != "") {
				$main .= "<p>Report Template last updated on <strong>".$lastupdate[0]['time']."</strong> by <strong>".$lastupdate[0]['username']."</strong></p>";
			}
			
			return $main;
		} else {
			if (file_exists($path."/$which.svg")) {
				$main = "<h3>Report - $report_name</h3>";
				$main .= file_get_contents($path."/".$which.".txt");
				$main .= "<p><object width='1000' height='500' border='0' data='saved/trend/".$query."/".$which.".svg'/><br/><br/></p>";
				global $pdficons;
				$pdficons = "toPDF.php?saved_report=".$_GET['saved_report']."&report_type=trend&id=".$which."'";
				$main .= "<a href='#' onclick='window.history.back();return false;'>back</a>";
				
				$this->auditLog("trend", "Viewed pre-generated Report", $report_name, $query);
				
			} else {
				$main = "File not found<br/><br/>";
			}
			return $main;
		}
	}

	
	/** IS NOW NOT AVAILABLE */
	/**
	 * deleteReport deletes a saved report (NOT the report template!)
	 * @param	string	$query		The name of the report template
	 * @param	string	$which		The name of the stored results to delete
	 * @return	string
	 */
	function deleteReport($query, $which="") {
		global $conf;
		$filename = $conf['Dir']['FullPath']."saved/trend/$query/$which.svg";
		$filename2 = $conf['Dir']['FullPath']."saved/trend/$query/$which.xls";
		$filename3 = $conf['Dir']['FullPath']."saved/trend/$query/$which.jpg";
		if (file_exists($filename)) {
			$res = unlink($filename);
		}
		if(file_exists($filename2)) {
			$res = unlink($filename2);
		}
		if(file_exists($filename3)) {
			$res = unlink($filename3);
		}
		return $this->displayReport($query);
		
		$this->auditLog("trend", "Deleted Report Output - $query/$which", $this->getReportName($query), $query);
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
		$path = $conf['Dir']['FullPath']."saved/trend";
		$dest_dir = $path."/".$id;
	
		if (!is_dir($path)) {
			mkdir($path);
		}
		
		if (!is_dir($dest_dir)) {
			mkdir($dest_dir);
		}
		
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".xls", $dest_dir."/".$report_name.$suffix.".xls");
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".jpg", $dest_dir."/".$report_name.$suffix.".jpg");
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".svg", $dest_dir."/".$report_name.$suffix.".svg");
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".pdf", $dest_dir."/".$report_name.$suffix.".pdf");
		
		$this->auditLog("trend", "Saved Report Output; filename(s) like: ".$report_name." - ".date("Y-m-d"), $report_name, $id);
		
		//echo "Saved report output at ".$dest_dir."/".$report_name.$suffix.".xls\n";
			
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
		$trend=0;
		if(is_array($_SESSION['reports'])) {
			foreach ($_SESSION['reports'] as $i => $rule) {
				if ($rule['report_type'] == "trend" && $rule['db_id'] == $_SESSION['curDB']) {
					if ($rule['published'] || $_SESSION['unpublished'] == true) {
						$pub_class = "published";
						$trend++;
					} else {
						$pub_class = "notpublished";
					}
					$trendlist .= "<span class='".$pub_class."'>&raquo; <a href='?command=view_report&report_type=trend&saved_report=".$rule['report_id']."'>".$rule['report_name']."</a><br /><span>";
				} 
			}
		}
		
		if (is_array($trend)) { $trendlist = "".implode("<br />", $trend).""; }
		
		$sideString .= "<b>Trend</b><br/>";
		if ($_SESSION['curDB'] && ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create Report Templates']['access'] == "t")) {
			$sideString .= "&raquo; <a href='?command=new&report_type=trend&new=true'>New Trend Report</a><br />";
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create/Edit Raw SQL']['access'] == "t") {
				$sideString .= "&raquo; <a href='?command=new&report_type=trend&sql=true'>New Trend SQL</a><br />";
			}
		}
                $sideString .= '<fieldset>
                        <legend onclick="Effect.toggle(\'side-trend\',\'slide\'); return false;">Trend Reports ('.$trend.')</legend>
                        <div id="side-trend" style="display: none;"><span>';
                $sideString .= $trendlist;
                $sideString .= "</span></div></fieldset><br />";
                return $sideString;
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
	function drawGraph($array, $linegraph=true, $title="", $x_title = "", $y_title = "", $colours=array(), $linkTo="", $realTime=array(), $showValue='', $width=1000, $height=500, $type="line", $suffix="") {
	
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
		$graph->palette = new customPalette();
		$graph->background->color = '#ffffff';
		$graph->legend->title = 'Legend';
		$graph->legend->position = ezcGraph::RIGHT;
		$graph->legend->landscapeSize = 0.2;
		$graph->legend->portraitSize = 0.2;
		$graph->options->fillLines = 210;
		$graph->title->font->name = 'sans-serif';
		$graph->title->font->maxFontSize = '16';
 		$graph->title = $title;
		$graph->options->font->maxFontSize = 12;
		foreach ($array as $i => $row) {
			$graph->data[$i] = new ezcGraphArrayDataSet( $row );
		}
		
		$filename = $title.$suffix.".svg";
		$full_filename = "tmp/" . $filename;


		$graph->render( $width, $height, $full_filename );
		$execoutput = array();
		exec('convert -font Helvetica "'.$full_filename.'" "tmp/'.$title.$suffix.'.jpg"', $execoutput);
		error_log("Converting: ".$full_filename." to tmp/".$title.".jpg. Results: ".print_r($execoutput, true));
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
	  function getEndDate($enddate, $maxTimestamp) {
		#With the highest timestamp being infinity this will always be true.
		if (!$enddate || $enddate == "End Date (YYYY-MM-DD)" || !strtotime($enddate)) {
			$date = time();
		} else {
			$date = strtotime($enddate);
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
		$aggregate = $rules['ATTRS']['AGGREGATE'];
		$absolute = $rules['ATTRS']['ABSOLUTE'];
		#$aggregate = $rules['CHILDREN'][1]['CHILDREN'][3]['TAGDATA'];

		/* Compute start and end dates for the query ... */
		$startdate = $this->getStartDate($timeCell['START_DATE'], $interval);
		$enddate = $this->getEndDate($timeCell['END_DATE'], $maxTimestamp);
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
					$date = "Y-m-d";
					$curdate_string = date($date, $curdate);
					$subwhere = $allTables[0].".start_date <= '".$curdate_string."'";
					$subwhere .= " and (" . $allTables[0].".end_date > '".date($date, $curdate)."')";
					$newquerytmp = str_replace("%date%", $curdate_string, $newquery);
					$newquerytmp = str_replace("%timeconstraint%", $subwhere, $newquerytmp);

					/* Store in array in Y-m-d format, cuz PHP is weird and expects it */
					$query[$entry['TITLE']][date('Y-m-d',$curdate)] = $newquerytmp;
					if ($interval == "quarter") {
						$curdate = strtotime("+3 months", $curdate);
					} else {
						$curdate = strtotime("+1 ".$interval, $curdate);
					}
				}
			} else {
				$table[$entry['TABLE']] = $entry['TABLE'];
				
				/* Get the aggregate SQL */
				$set[] = $this->buildAggregate($aggregate, $entry['TABLE'], $entry['COLUMNS']);

				
				/* For each defined constraint ... */
				foreach ($constraintCell[$i] as $j => $c_entry) {
					/* If the user hasn't actually set the constraint's values, skip it */
					if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
						continue;
					}
					
					/* Add the referenced table to the $table array */
					$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
					
					/* Now generate a where clause for this constraint rule */
					if ($c_entry['CONSTRAINT_COLUMNS'] == 'age') {
						$c_entry['CONSTRAINT_TABLE'] = "extract(year from age('%date%', cast(".$c_entry['CONSTRAINT_TABLE'];
						$c_entry['CONSTRAINT_COLUMNS'] = "date_of_birth as timestamp)))";
					}
					$where[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
				}
				if ($constraintCell['G']) {
					foreach ($constraintCell["G"] as $j => $c_entry) {
						/* If the user hasn't actually set the constraint's values, skip it */
						if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
							continue;
						}
						
						/* Add the referenced table to the $table array */
						$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
						
						/* Now generate a where clause for this constraint rule */
						if ($c_entry['CONSTRAINT_COLUMNS'] == 'age') {
							$c_entry['CONSTRAINT_TABLE'] = "extract(year from age('%date%', cast(".$c_entry['CONSTRAINT_TABLE'];
							$c_entry['CONSTRAINT_COLUMNS'] = "date_of_birth as timestamp)))";
						}
						$where[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
					}
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
				
				#$order = "group by ".$entry['TABLE'].".start_date order by ".$entry['TABLE'].".start_date ";
				
				$table = implode(",", $table);
				
				$curdate = $startdate; // At least initially

				/* Between the start date and end date runs queries once per interval for all
				   data that starts before and ends after */
				while ($curdate < $enddate) {
					/* Reload the predated values */
					$subwhere = $where;
					
					/* Set the date format based on the chosen interval */
					if($interval == 'day') {
						$date = "Y-m-d";
					} else {
						$date = "Y-m-d";
					}
					$curdate_string = date($date, $curdate);
					if ($absolute == 1) {
						if ($interval == "quarter") {
							$nextdate = strtotime("+3 months", $curdate);
						} else {
							$nextdate = strtotime("+1 ".$interval, $curdate);
						}
						$subwhere[] = $entry['TABLE'].".start_date between '".$curdate_string."' and '".date("Y-m-d", $nextdate)."'";
					} elseif ($absolute == 2) {
						if ($interval == "quarter") {
							$nextdate = strtotime("+3 months", $curdate);
						} else {
							$nextdate = strtotime("+1 ".$interval, $curdate);
						}
						$subwhere[] = $entry['TABLE'].".start_date between '".$curdate_string."' and '".date("Y-m-d", $nextdate)."'";
						$subwhere[] = $entry['TABLE'].".start_date = (SELECT min(start_date) FROM ".$entry['TABLE']." WHERE )";
					} else {
						$subwhere[] = $entry['TABLE'].".start_date <= '".$curdate_string."'";
						$subwhere[] = "(" . $entry['TABLE'].".end_date > '".$curdate_string."')";
					}
					$subwhere = implode(" and ", $subwhere);
					$subwhere .= " ";

					/* Store them in the array in Y-m-d format, cuz PHP is weird and expects it */
					$subwhere = str_replace("%date%", $curdate_string, $subwhere);

					$query[$entry['TITLE']][date('Y-m-d',$curdate)] = Database::buildQuery($type, $table, $subwhere, $set, $order) .";";
					if ($interval == "quarter") {
						$curdate = strtotime("+3 months", $curdate);
					} else {
						$curdate = strtotime("+1 ".$interval, $curdate);
					}
				}
				# Do not include the last (partial) period
			}
		}
		return $query;
	}
}

class customPalette extends ezcGraphPalette {
	protected $axisColor = '#000000';
	protected $majorGridColor = '#000000BB';
	protected $dataSetColor = array(
		'000099',
		'009900',
		'cc0000',
		'cccc00',
		'00cccc',
		'cc00cc',
		'6666ff',
		'66ff66',
		'ff6666',
		'ffff66',
		'66ffff',
		'666666',
		'ff66ff',
		'000033',
		'330000',
		'333300',
		'330033',
		'ffcccc'
	);

	protected $fontName = 'sans-serif';
	protected $fontColor = '#555753';
}

?>
