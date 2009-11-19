<?php

/**
 * table.php -- Implements the table module
 * @package Modules--Table
 * @author Andy White <andy@lgsolutions.com.au>
 * @version 2.0
 */

/**
 * Table Module
 *
 * This module implements table reports - data represented
 * by cells in a table
 *
 * @package Modules--Table
 * @author Andy White
 *
 */

class Table extends Common_Functions {
	
	/**
	 * showRules shows the HTML elements used to
	 * create or edit the report template.
	 * @param	string	$saved_report	A report to load for editing
	 * @return 	string
	 */
	function showRules($saved_report=array()) {
		$main .= "<h3>Table Report Generator</h3>";
		$main .= "<form name='table' action='index.php?command=save_template&report_type=table&saved_report='".$saved_report['ATTRS']['NAME']."' method='POST'>";
		$main .= "<input type='hidden' name='report_type' value='table'>"; 
		$main .= "<input type='hidden' value='save_template' id='command' name='command'/>";
		foreach ($_SESSION['tables'] as $i => $table) {
			$tableList[$table['ea_table_name']] = $table['ea_table_name'];
		}
						 
		$highestTrendVal = 0;
		$highestConsVal = 0;
	
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
					case "AXIS":
						$axis = $rulevalue['ATTRS']['NAME'];
						foreach ($rulevalue['CHILDREN'] as $j => $trendvalue) {
							switch ($trendvalue['NAME']) {
								case "SQL":
									$showSql = true;
									$constraintnumber = $trendvalue['ATTRS']['NAME'];
									$constraintval[$axis]['sql'] = $trendvalue['TAGDATA'];
								case "CONSTRAINT":
									$constraintnumber = $trendvalue['ATTRS']['NAME'];
									foreach ($trendvalue['CHILDREN'] as $k => $constraintvalue) {
										$constraintval[$axis][$constraintnumber][strtolower($constraintvalue['NAME'])] = $constraintvalue['TAGDATA'];
									}
									break;
								default:
									$trendval[$axis][strtolower($trendvalue['NAME'])] = $trendvalue['TAGDATA'];
									break;
							}
						}
						break;
				}
			}
		} else {
			$values['save_as'] = $_SESSION['username']."-".date("Y-m-d H-i");
			$values['LINKTO']= "";
			$axisplural = Array('C', 'X', 'Y', 'Z');
			foreach($axisplural as $i => $axis) {
				$trendval[$axis]['label'] = "Type label here ...";
				$trendval[$axis]['title'] = "";
				$trendval[$axis]['table'] = "";
				$trendval[$axis]['columns'] = "";
				$constraintval[$axis][0]['constraint_table'] = "";
				$constraintval[$axis][0]['constraint_columns'] = "";
				$constraintval[$axis][0]['constraint_type'] = "";
				$constraintval[$axis][0]['constraint_value'] = "Value";
			}
			$highestVal = 0;
		}
		$highestConsVal = $constraintnumber;
	
		$table_select = "<option value='' selected>Select Table...</option>";
		foreach ($tableList as $i => $table) {
			$table_select .= "<option value='".$table['tablename']."'>".$table['tablename'];
		}
		$main .= "<script>
			".showConstraintBlock($tableList)."
			</script>
			";
		/* Start Table Table */
		$main .= "<div id='table_main'>";
		$main .= "<img src='images/table_axis.png' />";
	
		/* X Axis */
		$main .= "<div id='x-axis' class='table_box' style='position:absolute; left:270px; top:100px;'>
			<p class='table_axis_title'>X-Axis</p>
			<img src='images/hrule.png' class='hr' />";
		$main .= "<input type='text' name='label__X' value='".$trendval['X']['label']."' style='position:absolute; top: 50px; left: 20px; text-indent: 5px; width: 220px'/>";
		if ($showSql) {	
			$main .= "<p><textarea name='sqlquery__X'  style='position: relative; top: 70px; left: 20px; width: 220px;' class='sql' rows='6'>".$constraintval['X']['sql']."</textarea><br/>";
		} else {
			$main .= "<select name='table__X' id='table__X' style='position: absolute; top: 70px; left: 20px; width: 220px;' onChange='changeOptions(&quot;table__X&quot;, &quot;columns__X&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['X']['table']);
			$main .= "</select>
				<select name='columns__X' id='columns__X' style='position: absolute; top: 90px; left: 20px; width: 220px;'>".
				Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['X']['table'], $trendval['X']['columns'])."</select>
				<input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__X\"); underClip(\"clipper__X\", \"constraints__X\");' style='position:absolute; top: 120px; left: 20px;'>";
			$main .= "</div>";
			$main .= "<iframe style='position: absolute; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__X' id='clipper__X' width='0' height='0'></iframe><div class='constraints' id='constraints__X' style='position:absolute; top: 100px; left: 0px'>
				<strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__X\"); makeInvisible(\"clipper__X\");'><br/><br/>";
				
			foreach($constraintval['X'] as $i => $constraint) {
				$main .= "
					<div id='trendX".$i."Div'>
					<select id='constraint_table__X__".$i."' name='constraint_table__X__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__X__".$i."&quot;,&quot;constraint_columns__X__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select>
					<select id='constraint_columns__X__".$i."' name='constraint_columns__X__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__X__".$i."&quot;,&quot;constraint_columns__X__".$i."&quot;,&quot;constraint_type__X__".$i."&quot;,&quot;constraint_value__X__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select>
					<select name='constraint_type__X__".$i."' id='constraint_type__X__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__X__".$i."&quot;,&quot;constraint_columns__X__".$i."&quot;,&quot;constraint_type__X__".$i."&quot;,&quot;constraint_value__X__".$i."&quot;);'>".Common_Functions::generate_options(array("is"=>"Is (Equals)", "lt"=>"Less Than", "gt"=>"Greater Than", "contains"=>"Contains"), $constraint['constraint_type'], "Select Constraint Type ...")."
					<input type='text' name='constraint_value__X__".$i."' id='constraint_value__X__".$i."' value='".$constraint['constraint_value']."'>
					<input type='button' value='+' onclick='javascript:addElement(\"constraints__X\", constraintString, \"constraint\", \"X\"); underClip(\"clipper__X\", \"constraints__X\");' name='additional_constraint' >
					</div>
					";
			}
		}
		$main .= "</div>";
	
		/* Y Axis */
		$main .= "<div id='y-axis' class='table_box' style='position:absolute; left: 0px; top: 300px;'>
			<p class='table_axis_title'>Y-Axis</p>
			<img src='images/hrule.png' class='hr' />";
		$main .= "<input type='text' name='label__Y' value='".$trendval['Y']['label']."' style='position:absolute; top: 50px; left: 20px; text-indent: 5px; width: 220px'/>";
		if ($showSql) {	
			$main .= "<p><textarea name='sqlquery__Y'  style='position: absolute; top: 70px; left: 20px; width: 220px;' class='sql' rows='6'>".$constraintval['Y']['sql']."</textarea><br/>";
		} else {
			$main .= "<select name='table__Y' id='table__Y' style='position: absolute; top: 70px; left: 20px; width: 220px;' onChange='changeOptions(&quot;table__Y&quot;, &quot;columns__Y&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['Y']['table']);
			$main .= "</select>
				<select name='columns__Y' id='columns__Y' style='position: absolute; top: 90px; left: 20px; width: 220px;'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['Y']['table'],$trendval['Y']['columns'])."</select>
				<input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__Y\"); underClip(\"clipper__Y\", \"constraints__Y\");' style='position:absolute; top: 120px; left: 20px;'>";
			$main .= "</div>";
			$main .= "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__Y' id='clipper__Y' width='0' height='0'></iframe><div class='constraints' id='constraints__Y' style='position:absolute; left: 0px; top: 300px'>
				<strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__Y\"); makeInvisible(\"clipper__Y\");'><br/><br/>";
	
			foreach($constraintval['Y'] as $i => $constraint) {
				$main .= "
					<div id='trendY".$i."'>
					<select id='constraint_table__Y__".$i."' name='constraint_table__Y__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__Y__".$i."&quot;,&quot;constraint_columns__Y__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select>
					<select id='constraint_columns__Y__".$i."' name='constraint_columns__Y__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Y__".$i."&quot;,&quot;constraint_columns__Y__".$i."&quot;,&quot;constraint_type__Y__".$i."&quot;,&quot;constraint_value__Y__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select>
					<select name='constraint_type__Y__".$i."' id='constraint_type__Y__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Y__".$i."&quot;,&quot;constraint_columns__Y__".$i."&quot;,&quot;constraint_type__Y__".$i."&quot;,&quot;constraint_value__Y__".$i."&quot;);'>".Common_Functions::generate_options(array("is"=>"Is (Equals)", "lt"=>"Less Than", "gt"=>"Greater Than", "contains"=>"Contains"), $constraint['constraint_type'], "Select Constraint Type ...")."
					<input type='text' name='constraint_value__Y__".$i."' id='constraint_value__Y__".$i."' value='".$constraint['constraint_value']."'>
					<input type='button' value='+' onclick='javascript:addElement(\"constraints__Y\", constraintString, \"constraint\", \"Y\");  underClip(\"clipper__Y\", \"constraints__Y\");' name='additional_constraint' >
					</div>
					";
			}
		}
		$main .="</div>";
	
		/* Z Axis */
		$main .= "<div id='z-axis' class='table_box' style='position:absolute; left:270px; top: 300px'>
			<p class='table_axis_title'>Z-Axis (Optional)</p>
			<img src='images/hrule.png' class='hr' />";
		$main .= "<input type='text' name='label__Z' value='".$trendval['Z']['label']."' style='position:absolute; top: 50px; left: 20px; text-indent: 5px; width: 220px'/>";
		if ($showSql) {	
			$main .= "<p><textarea name='sqlquery__Z'  style='position: absolute; top: 70px; left: 20px; width: 220px;' class='sql' rows='6'>".$constraintval['Z']['sql']."</textarea><br/>";
		} else {
			$main .= "<select name='table__Z' id='table__Z' style='position: absolute; top: 70px; left: 20px; width: 220px;' onChange='changeOptions(&quot;table__Z&quot;, &quot;columns__Z&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['Z']['table']);
			$main .= "</select>
				<select name='columns__Z' id='columns__Z' style='position: absolute; top: 90px; left: 20px; width: 220px;'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['Z']['table'],$trendval['Z']['columns'])."</select>
				<input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__Z\"); underClip(\"clipper__Z\", \"constraints__Z\");' style='position:absolute; top: 120px; left: 20px;'>";
			$main .= "</div>";
			$main .= "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__Z' id='clipper__Z' width='0' height='0'></iframe>
	<div class='constraints' id='constraints__Z' style='position:absolute; top:300px; left:0px'>
				<strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__Z\"); makeInvisible(\"clipper__Z\");'><br/><br/>";
	
			foreach($constraintval['Z'] as $i => $constraint) {
				$main .= "
					<div id='trendZ".$i."'>
					<select id='constraint_table__Z__".$i."' name='constraint_table__Z__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__Z__".$i."&quot;,&quot;constraint_columns__Z__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select>
					<select id='constraint_columns__Z__".$i."' name='constraint_columns__Z__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Z__".$i."&quot;,&quot;constraint_columns__Z__".$i."&quot;,&quot;constraint_type__Z__".$i."&quot;,&quot;constraint_value__Z__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select>
					<select name='constraint_type__Z__".$i."' id='constraint_type__Z__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Z__".$i."&quot;,&quot;constraint_columns__Z__".$i."&quot;,&quot;constraint_type__Z__".$i."&quot;,&quot;constraint_value__Z__".$i."&quot;);'>".Common_Functions::generate_options(array("is"=>"Is (Equals)", "lt"=>"Less Than", "gt"=>"Greater Than", "contains"=>"Contains"), $constraint['constraint_type'], "Select Constraint Type ...")."
					<input type='text' name='constraint_value__Z__".$i."' id='constraint_value__Z__".$i."' value='".$constraint['constraint_value']."'>
					<input type='button' value='+' onclick='javascript:addElement(\"constraints__Z\", constraintString, \"constraint\", \"Z\"); underClip(\"clipper__Z\", \"constraints__Z\");' name='additional_constraint' >
					</div>
					";
			}
		}
		$main .= "</div>";
	
		/* POINT AXIS */
		$main .= "<div class='table_smallbox' style='position:absolute; left: 0px; top:500px'>";
		$main .= "<p class='table_small_axis_title'>Table Cells</p>";
		if ($showSql) {	
			$main .= "<p><textarea name='sqlquery__C'  style='position: absolute; top: 50px; left: 20px; width: 170px;' class='sql' rows='7'>".$constraintval['C']['sql']."</textarea><br/>";
		} else {
			$main .= "<select name='table__C' id='table__C' style='position: absolute; top: 45px; left: 20px; width: 170px;' onChange='changeOptions(&quot;table__C&quot;, &quot;columns__C&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['C']['table']);
			$main .= "</select>
				<select name='columns__C' id='columns__C' style='position: absolute; top: 65px; left: 20px; width: 170px;'>".
				Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['C']['table'], $trendval['C']['columns'])."</select>
				<input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__C\"); underClip(\"clipper__C\", \"constraints__C\");' style='position:absolute; top: 160px; left: 20px;'>";
			$main .= "<p><select name='aggregate__C' style='position: absolute; top: 85px; left: 20px; width: 170px;'>".
				Common_Functions::generate_options(array('count once'=>'count once', 'count'=>'count', 'percentage'=>'percentage', 'avg'=>'average', 'max'=>'max', 'min'=>'min'), $trendval['C']['aggregate'], "Select Aggregate Type ...")."</select><br/></p>";
			$main .= "</div>";
			$main .= "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__C' id='clipper__C' width='0' height='0'></iframe><div class='constraints' id='constraints__C' style='position:absolute; top: 500px; left: 0px;'><strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__C\"); makeInvisible(\"clipper__C\");'><br/>
					<p>These values will automatically be constrained against the X, Y, and Z axis</p>";
	
			foreach($constraintval['C'] as $i => $constraint) {
				$main .= "
					<div id='trendC".$i."'>
					<select id='constraint_table__C__".$i."' name='constraint_table__C__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__C__".$i."&quot;,&quot;constraint_columns__C__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select>
					<select id='constraint_columns__C__".$i."' name='constraint_columns__C__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__C__".$i."&quot;,&quot;constraint_columns__C__".$i."&quot;,&quot;constraint_type__C__".$i."&quot;,&quot;constraint_value__C__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select>
					<select name='constraint_type__C__".$i."' id='constraint_type__C__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__C__".$i."&quot;,&quot;constraint_columns__C__".$i."&quot;,&quot;constraint_type__C__".$i."&quot;,&quot;constraint_value__C__".$i."&quot;);'>".Common_Functions::generate_options(array("is"=>"Is (Equals)", "lt"=>"Less Than", "gt"=>"Greater Than", "contains"=>"Contains"), $constraint['constraint_type'], "Select Constraint Type ...")."
					<input type='text' name='constraint_value__C__".$i."' id='constraint_value__C__".$i."' value='".$constraint['constraint_value']."'>
					<input type='button' value='+' onclick='javascript:addElement(\"constraints__C\", constraintString, \"constraint\", \"C\"); underClip(\"clipper__C\", \"constraints__C\");' name='additional_constraint' >
					</div>
				";
			}
		}
		$main .= "</div></div>";
	
		/* Save Table Details */
		$main .= "<div id='save_stuff' style='position:absolute; left: 250px; top: 500px'>";
		if ($showSql) {	
			$main .= "<p>The Axis's must be in the format of; <br/><em>SELECT DISTINCT ? FROM ? ORDER BY ?</em></br/>Where ? should be the axis's above</p>";
			$main .= "<p>The Tables Cells must be in the format of; <br/><em>SELECT ? AS X, ? AS Y, [? AS Z], count(*) AS cell FROM ? GROUP BY X,Y [,Z] ORDER BY [Z,] Y,Z</em></br/>Where ? should be the axis's above</p>";
		} else {
			$main .= "<p>Link this to ".showLinkTo("listing", $values['LINKTO'])."<br/>";
		}
		
		$main .= "<input type='text' name='save_as' value='".$values['save_as']."'> <input type='submit' name='submit' value='Save As'></p>";
		$main .= "</div></div>";
		$main .= "</div>";
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
		$xml['ATTRS']['REPORT_TYPE']='table';
		$xml['CHILDREN'] = array();
		$temp['NAME']='linkto';
		$temp['TAGDATA']=$report['linkTo'];
		array_push($xml['CHILDREN'], $temp);
		foreach ($report as $i => $data) {
			if ($i == "save_as" || $i == "start_date" || $i == "end_date" || $i == "interval" || $i == "linkTo") {
				continue;
			}
			$keys = explode("__", $i);
			if (count($keys) == 2) {
				$table[$keys[1]][$keys[0]] = $data;
			}
			if (count($keys) == 3) {
				$constraints[$keys[1]][$keys[2]][$keys[0]] = $data;
			}
		}
		foreach ($table as $axis => $axisdata) {
			$node['NAME']='axis';
			$node['ATTRS']['NAME']=$axis;
			$node['CHILDREN'] = array();
			
			if ($axisdata['sqlquery']) {
				$node['CHILDREN'][0]['NAME']='label';
				$node['CHILDREN'][0]['TAGDATA']=$axisdata['label'];
				$node['CHILDREN'][1]['NAME']='sql';
				$node['CHILDREN'][1]['TAGDATA']=stripslashes($axisdata['sqlquery']);
			} else {
				foreach ($axisdata as $tag => $data) {
					$temp = array();
					$temp['NAME']=$tag;
					$temp['TAGDATA']=$data;
					array_push($node['CHILDREN'], $temp);
				}
				
				foreach ($constraints[$axis] as $constraint => $cons_items) {
					$cons = array();
					$cons['NAME'] = 'constraint';
					$cons['ATTRS']['NAME']=$constraint;
					$cons['CHILDREN'] = array();
					
					foreach ($cons_items as $tag => $value) {
						$temp = array();
						$temp['NAME'] = $tag;
						$temp['TAGDATA'] = $value;
						array_push($cons['CHILDREN'], $temp);
					}
					array_push($node['CHILDREN'], $cons);
				}
			}
			array_push($xml['CHILDREN'], $node);
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
		$report_name = $report['ATTRS']['NAME'];
		$report_type = $report['ATTRS']['REPORT_TYPE'];
		$sql = false;
		/* To find a potential drill down capability */	
		foreach ($report['CHILDREN'] as $i => $child) {
			if ($child['NAME'] == "LINKTO" && $child['TAGDATA']) {
				$linkto = $child['TAGDATA'];
			}
			if ($child['NAME'] == "AXIS" && $child['ATTRS']['NAME'] == "C") {
				foreach ($child['CHILDREN'] as $j => $attrs) {
					/* Check for SQL. SQL doesn't display totals */
					if ($attrs['NAME'] == "SQL") {
						$sql = true;
					}
					if ($attrs['NAME'] == "AGGREGATE") {
						$aggregate = $attrs['TAGDATA'];
					}
				}
			}
		}
	
		$csvfile = $report_name.".csv";
		$csv = "";
		$saveicons = "index.php?command=save_report&query=".$report_name."&report_type=table&file_name=".$csvfile."'";
		$title = "<p><strong>Table Report - ".$report_name."</strong> | <a href='".$saveicons."'>Save Report</a> | <a href='tmp/".$csvfile."'>Download CSV file</a>";
		$main .= "<p>";
		if($_GET['report_from']) {
			$queries = Database::generateSQL($report, getRules($_GET['report_from']), $_GET['line']);
		} else {
			$queries = Database::generateSQL($report);
		}
	
		if ($_POST['point']) {
			$timestamp = $_POST['point'];
		} else {
			$timestamp = Database::getLatestTime($_SESSION['curDB_psql']);
		}
	
		foreach ($queries as $i => $query) {
			$queryString .= "<strong>Query $i: </strong>".$query."<br/><br/>";
			/* Debugging */
			echo "<strong>Query $i: </strong>".$query."<br/><br/>";
		}
		
	
		$xaxis = Database::runQuery($queries['X'], $_SESSION['curDB_psql']);
		$X = array_keys($xaxis[0]);
		$X = $X[0];
	
		$yaxis = Database::runQuery($queries['Y'], $_SESSION['curDB_psql']);
		$Y = array_keys($yaxis[0]);
		$Y = $Y[0];
		if ($queries['Z']) {
			$zaxis = Database::runQuery($queries['Z'], $_SESSION['curDB_psql']);
		} else {
			$zaxis[0][$Z] = "";
		}
		$Z = array_keys($zaxis[0]);
		$Z = $Z[0];
		$cells = Database::runQuery($queries['C'], $_SESSION['curDB_psql']);

		/* Get the total for the percentage*/
		foreach ($cells as $i => $cell) {
			$percentageTotal += $cell['cell'];
		}
		$cell = array_shift($cells);
		foreach ($zaxis as $h => $zcell) {
			$csv .= "\n".$zcell[$Z]."\n";
			$main .= "<strong>".$zcell[$Z]."</strong>";
			$main .= "<table class='bordered'>";
			$first = true;
			$totals = array();
			$avgcountY = array();
			
			foreach ($yaxis as $i => $ycell) {
				$line_csv = "";
				$line = "";
				$main .= "<tr>";
				$first_csv = " ";
				$firstString = "<td class='bordered'>&nbsp;</td>";
				$line_csv .= $ycell[$Y];
				$line .= "<td class='bordered'><strong>".$ycell[$Y]."</strong></td>";
				$sum = 0;
				$avgcount =0;
				$max = 0;
				$min = 1000;
				foreach ($xaxis as $j => $xcell) {
					if ($first == true) {
						$firstString .= "<td class='bordered'><strong>".$xcell[$X]."</strong></td>";
						$first_csv .= " , ".$xcell[$X];
					}
					if ($cell['y'] == $ycell[$Y] and $cell['x'] == $xcell[$X]) {
						$linerun = true;
						if ($aggregate == "percentage") {
							$printno = $cell['cell']."<br/>".round($cell['cell']/$percentageTotal*100, 2)."%";
						} else {
							$printno = $cell['cell'];
						}
						$line_csv .= ",".$cell['cell'];
						if ($linkto) {
							if($_GET['report_from']) {
								$line .= "<td><a href='index.php?line=".$_GET['line']."&report_type=listing&point=".$_GET['point']."&parent_from=".$_GET['report_from']."&report_from=".$report_name."&command=run_report&saved_report=".$linkto."&point=".$timestamp."&X=".$xcell[$X]."&Y=".$ycell[$Y]."&Z=".$zcell[$Z]."'>".$printno."</a></td>";
							} else {
								$line .= "<td><a href='index.php?report_type=listing&report_from=".$report_name."&command=run_report&saved_report=".$linkto."&point=".$timestamp."&X=".$xcell[$X]."&Y=".$ycell[$Y]."&Z=".$zcell[$Z]."'>".$printno."</a></td>";
							}
						} else {
							$line .= "<td>".$printno."</td>";
						} 
						/* Setting the totals column for each of the different aggregate types */
						$totals[$xcell[$X]] += $cell['cell'];
						$sum += $cell['cell'];
						$avgcount++;
						$avgcountY[$xcell[$X]]++;
						if ($cell['cell'] > $max) {
							$max = $cell['cell'];
						}
						if ($cell['cell'] > $maxY[$xcell[$X]]) {
							$maxY[$xcell[$X]] = $cell['cell'];
						}
						if ($cell['cell'] < $min) {
							$min = $cell['cell'];
						}
						if ($cell['cell'] < $minY[$xcell[$X]]) {
							$minY[$xcell[$X]] = $cell['cell'];
						}
						$cell = array_shift($cells);
					} else {
						$line_csv .= " , ";
						$line .= "<td class='bordered'>-</td>";
						$totals[$xcell[$X]] += 0;
					}
				}
				/* Printing the X-Axis totals */
				if ($first == true) {
					if ($sql == false) {
						$first_csv .=" , Total";
						$firstString = $firstString."<td class='bordered'><strong> Total </strong></td>";
					}
					$firstString .= "</tr><tr>";
					$csv .= $first_csv."\n";
					$main .= $firstString;
					$first = false;
				}
				if ($sql == false) {
								if ($aggregate == "percentage") {
										$printno = $sum."<br/>".round($sum/$percentageTotal*100, 2)."%";
					} elseif ($aggregate == "avg") {
						$printno = round($sum/$avgcount, 2);
					} elseif ($aggregate == "max") {
						$printno = $max;
					} elseif ($aggregate == "min") {
						$printno = $min;
								} else {
										$printno = $sum;
								}
					$line_csv .= " , ".$sum;
					$line .= "<td> $printno </td>";
				}
				$csv .=$line_csv."\n";
				$main .= $line;
				$main .= "</tr>";
			}
			/* SQL Queries dont have totals */
			if ($sql == false) {
				$main .= "<tr><td class='bordered'><strong> Total </strong></td>";
				$csv .= "Total";
				$sum = 0;
				$avgCount = 0;
				foreach ($totals as $i => $sub) {
								if ($aggregate == "percentage") {
										$printno = $sub."<br/>".round($sub/$percentageTotal*100, 2)."%";
					} elseif ($aggregate == "avg") {
						$printno = round($sub/$avgcountY[$i], 2);
						$sum += $printno;
					} elseif ($aggregate == "max") {
						$printno = $maxY[$i];
					} elseif ($aggregate == "min") {
						$printno = $minY[$i];
								 } else {
										$printno = $sub;
						$sum += $sub;
								}
					$main .= "<td>".$printno."</td>";
					$csv .= ", $sub";
					$avgCount++;
				}
				if ($aggregate == "avg") {
					$main .= "<td>".round($sum/$avgCount, 2)."</td></tr>";
				} elseif ($aggregate == "max") {
					$main .= "<td>".max($maxY)."</td></tr>";
				} elseif ($aggregate == "min") {
					$main .= "<td>".min($minY)."</td></tr>";
				} else {
					$main .= "<td>".$sum."</td></tr>";
				}
			}
			$main .= "</table><br/><br/>";
			$csv .= ", ".$sum."\n";
		}
		
		file_put_contents("tmp/".$csvfile, $csv);
		
		$main = $title.$save_link.$main;
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
		$saveddir = $conf['Dir']['FullPath']."/saved/table";
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
						$main .= "$tmp: <a href='index.php?command=view_report&saved_report=$query&report_type=table&id=$tmp'>view</a> <a href='index.php?command=delete_report&saved_report=$query&report_type=table&id=$tmp'>delete</a><br>";
					}
				}
			} else {
				$main .= "No saved report found!";
			}
			return $main;
		} else {
			$file_name = $path."/$which.csv";
			if (file_exists($file_name)) {
				$lines = file($file_name);
				$j = count($lines);
				$el = array();
				$i = 0;
				
				if ($i+2 < $j) {
					#$main = "<a href='toPDF.php?saved_report=$query&report_type=table&id=$which'>Download PDF version</a><br/><br/>";
						global $pdficons;
						$pdficons = "toPDF.php?saved_report=".$query."&report_type=table&id=".$which."'";
					$main .= "<strong>".$lines[$i+1]."</strong>";
					$main .= "<table class='bordered'>";
					$main .= displayLine($lines[$i+2], "&nbsp;");
				}
				
				for ($i=3;$i<$j;$i++) {
					if ($lines[$i]=="\n") {
						$main .= "</table>";
						if ($i+2 < $j) {
							$main .= "<strong>".$lines[$i+1]."</strong>";
							$main .= "<table class='bordered'>";
							$main .= displayLine($lines[$i+2], "&nbsp;");
							$i = $i + 2;
						} else {
							break;
						}
					} else {
						$main .= displayLine($lines[$i]);
					}
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
		$filename = $conf['Dir']['FullPath']."/saved/table/$query/$which.csv";
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
		$path = $conf['Dir']['FullPath']."/saved/table";
		$dest_dir = $path."/".$_GET['query'];
	
		if (!is_dir($path)) {
			mkdir($path);
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
/* Axis's */
		$tables = array();
		$sets = array();
		$wheres = array();
		$orders = "";
		$timestamp_done = false;
		foreach ($cell as $i => $entry) {
			if (($entry['TABLE'] == "" && !$entry['SQL']) || $i == "C" ) {
				continue;
			}
			$table = array();
			$set = array("DISTINCT");
			$where = array();
			$order = "";
			if ($entry['SQL']) {
				if (strpos($entry['SQL'], "timestamp_id")) {
					$skiptime = true;
				}
				$newquery = Database::getTables($entry['SQL'], $maxTimestamp, $skiptime);
				$query[$i] = $newquery;
			} else {
				$table_cells = array();
				$table[$entry['TABLE']] = $entry['TABLE'];
				$tables[$entry['TABLE']] = $entry['TABLE'];
				$set[] = $entry['TABLE'].".".$entry['COLUMNS']." ";
				$sets[] = $entry['TABLE'].".".$entry['COLUMNS']." as ".$i ." ";
				foreach ($constraintCell[$i] as $j => $c_entry) {
					if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
						continue;
					}
					if ($c_entry['CONSTRAINT_COLUMNS'] == "start_timestamp_id" || $c_entry['CONSTRAINT_COLUMNS'] == "end_timestamp_id") {
						$timestamp_done = true;
					}
					$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
					$tables[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
					if ($c_entry['CONSTRAINT_TYPE'] == "is") {
						$condition = "=";
					} elseif ($c_entry['CONSTRAINT_TYPE'] == "contains") {
						$condition = "like";
						$c_entry['CONSTRAINT_VALUE'] = "%".$c_entry['CONSTRAINT_VALUE']."%";
					} elseif ($c_entry['CONSTRAINT_TYPE'] == "gt") {
						$condition = ">";
	                                } elseif ($c_entry['CONSTRAINT_TYPE'] == "ex") {
	                                        $condition = " is not null";
	                                        $c_entry['CONSTRAINT_VALUE'] = "";
	                                } elseif ($c_entry['CONSTRAINT_TYPE'] == "dne") {
	                                        $condition = " is null";
	                                        $c_entry['CONSTRAINT_VALUE'] = "";
					} elseif ($c_entry['CONSTRAINT_TYPE'] == "lt") {
						$condition = "<";
					} 
	                                if ($c_entry['CONSTRAINT_VALUE'] != "") {
	                                        $c_entry['CONSTRAINT_VALUE'] = "'".$c_entry['CONSTRAINT_VALUE']."'";
	                                }
					$where[] = $c_entry['CONSTRAINT_TABLE'].".".$c_entry['CONSTRAINT_COLUMNS']." ".$condition." ".$c_entry['CONSTRAINT_VALUE']."";
					$wheres[] = $c_entry['CONSTRAINT_TABLE'].".".$c_entry['CONSTRAINT_COLUMNS']." ".$condition." ".$c_entry['CONSTRAINT_VALUE']."";
				}
				if ($i == "Z") {
					foreach ($orCell as $j => $orSet) {
						$whereOrSub = "";
						foreach ($orSet as $k => $o_entry) {
							if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
								continue;
							}
							if ($o_entry['CONSTRAINT_COLUMNS'] == "start_timestamp_id" || $c_entry['CONSTRAINT_COLUMNS'] == "end_timestamp_id") {
								$timestamp_done = true;
							}
							$table[$o_entry['CONSTRAINT_TABLE']] = $o_entry['CONSTRAINT_TABLE'];
							$tables[$o_entry['CONSTRAINT_TABLE']] = $o_entry['CONSTRAINT_TABLE'];
							if ($o_entry['CONSTRAINT_TYPE'] == "is") {
								$condition = "=";
							} elseif ($o_entry['CONSTRAINT_TYPE'] == "contains") {
								$condition = "like";
								$o_entry['CONSTRAINT_VALUE'] = "%".$o_entry['CONSTRAINT_VALUE']."%";
							} elseif ($o_entry['CONSTRAINT_TYPE'] == "gt") {
								$condition = ">";
							} elseif ($o_entry['CONSTRAINT_TYPE'] == "lt") {
								$condition = "<";
							} 
							$whereOrSub .= " ".$o_entry['CONSTRAINT_TABLE'].".".$o_entry['CONSTRAINT_COLUMNS']." ".$condition." '".$o_entry['CONSTRAINT_VALUE']."' and";
						}
						$whereOr .= "(" . rtrim($whereOrSub, "and") .") or";
					}
					$whereOr = rtrim($whereOr, "or");
				}
				if (!$timestamp_done) {
					$where[] = "(".$entry['TABLE'].".end_timestamp_id = '".$maxTimestamp."')";
					$timestamp_done = true;
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
				if ($where != array()) {
					$where = implode(" and ", $where);
					$where .= " ";
				} else {
					$where = NULL;
				}
				if ($whereOr) {
					$where .=  "  and (" . $whereOr .")";
				}
				$order = "order by ".$entry['TABLE'].".".$entry['COLUMNS'];
				$query[$i] = Database::buildQuery($type, $table, $where, $set, $order) .";";
			}
		}
		if ($cell['C']['SQL']) {
			if (strpos($entry['SQL'], "timestamp_id")) {
				$skiptime = true;
			}
			$newquery = Database::getTables($entry['SQL'], $maxTimestamp, $skiptime);
			$query['C'] = $newquery;
		} else {
			/* Cells */
			$table_cells = array();
			$timestamp_done = false;
			$entry = $cell['C'];
			$tables[$entry['TABLE']] = $entry['TABLE'];
                        if ($entry['AGGREGATE'] == 'count once' || $entry['AGGREGATE'] == 'percentage') {
                                $sets[] = "count (distinct ".$entry['TABLE'].".".$entry['COLUMNS'].") as cell";
			} elseif ($entry['AGGREGATE'] == 'avg') {
                                $sets[] = "round(".$entry['AGGREGATE']."(cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)), 2) as cell";
			} elseif ($entry['AGGREGATE'] == 'max' || $entry['AGGREGATE'] == 'min') {
                                $sets[] = $entry['AGGREGATE']."(cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)) as cell";
                        } else {
                                $sets[] = $entry['AGGREGATE']."(".$entry['TABLE'].".".$entry['COLUMNS'].") as cell";
                        }
			if(is_array($linkFromRules) && $line_no >= 0) {
				for($index = 0; $index <= count($linkFromRules['CHILDREN']); $index++) {
					if($linkFromRules['CHILDREN'][$index]['CHILDREN'][0]['TAGDATA'] == $line_no) {
						$line_index = $index;
					}
				}
				$linkedConstraints = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'];
				for($c = 5; $c < count($linkedConstraints); $c++) {
					if ($linkedConstraints[$c]['CHILDREN'][1]['TAGDATA'] == "start_timestamp_id" || $linkedConstraints[$c]['CHILDREN'][1]['TAGDATA'] == "end_timestamp_id") {
						$timestamp_done = true;
					}
					
					$tables[$linkedConstraints[$c]['CHILDREN'][0]['TAGDATA']] = $linkedConstraints[$c]['CHILDREN'][0]['TAGDATA'];
					if ($linkedConstraints[$c]['CHILDREN'][2]['TAGDATA'] == "is") {
						$condition = "=";
					} elseif ($linkedConstraints[$c]['CHILDREN'][2]['TAGDATA'] == "contains") {
						$condition = "like";
						$linkedConstraints[$c]['CHILDREN'][3]['TAGDATA'] = "%".$linkedConstraints[$c]['CHILDREN'][3]['TAGDATA']."%";
					} elseif ($linkedConstraints[$c]['CHILDREN'][2]['TAGDATA'] == "gt") {
						$condition = ">";
					} elseif ($linkedConstraints[$c]['CHILDREN'][2]['TAGDATA'] == "lt") {
						$condition = "<";
					} 
					
					$wheres[] = $linkedConstraints[$c]['CHILDREN'][0]['TAGDATA'].".".$linkedConstraints[$c]['CHILDREN'][1]['TAGDATA']." ".$condition." '".$linkedConstraints[$c]['CHILDREN'][3]['TAGDATA']."'";
				}
				/* We know there will always be an X-Axis */
				$tables[$linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA']] = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA'];
				$wheres[] = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA'].".start_timestamp_id <= '".$_GET['point']."'";
				$wheres[] = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA'].".end_timestamp_id > '".$_GET['point']."'";
				$timestamp_done = true;
			} 
			
			foreach ($constraintCell['C'] as $j => $c_entry) {
				if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
					continue;
				}
				if ($c_entry['CONSTRAINT_COLUMNS'] == "start_timestamp_id" || $c_entry['CONSTRAINT_COLUMNS'] == "end_timestamp_id") {
					$timestamp_done = true;
				}
				$tables[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
				if ($c_entry['CONSTRAINT_TYPE'] == "is") {
					$condition = "=";
				} elseif ($c_entry['CONSTRAINT_TYPE'] == "contains") {
					$condition = "like";
					$c_entry['CONSTRAINT_VALUE'] = "%".$c_entry['CONSTRAINT_VALUE']."%";
				} elseif ($c_entry['CONSTRAINT_TYPE'] == "gt") {
					$condition = ">";
				} elseif ($c_entry['CONSTRAINT_TYPE'] == "lt") {
					$condition = "<";
				} 
				$wheres[] = $c_entry['CONSTRAINT_TABLE'].".".$c_entry['CONSTRAINT_COLUMNS']." ".$condition." '".$c_entry['CONSTRAINT_VALUE']."'";
				if (!$timestamp_done) {
					$wheres[] = $c_entry['CONSTRAINT_TABLE'].".end_timestamp_id = '".$maxTimestamp."'";
					$timestamp_done = true;
				}
			}
			if (!$timestamp_done) {
				$wheres[] = "(".$entry['TABLE'].".end_timestamp_id = '".$maxTimestamp."')";
			}
			foreach ($tables as $k => $t_entry) {
				$keys = Database::getFKeys($t_entry, $_SESSION['curDB_psql']);
				if (is_array($keys)) {
					foreach ($keys as $l => $key) {
						if (in_array($key['fks'][0], $tables)) {
							$wheres[] = $key['fks'][0].".".$key['fks'][1]."=".$t_entry.".".$key['ea_column_name'];
						}
					}
				}
			}
			$tables = implode(",", $tables);
			$wheres = implode(" and ", $wheres);
			$wheres .= " ";
			if ($whereOr) {
				$wheres .=  "  and (" . $whereOr .")";
			}
			if ($query['Z']) {
				$groupZ = ",Z";
				$orderZ = "Z,";
			}
			$orders = " group by X, Y ".$groupZ." order by ".$orderZ."Y, X";
			$query['C'] = Database::buildQuery($type, $tables, $wheres, $sets, $orders) .";";
			
		}	
		return $query;
	}


}









?>
