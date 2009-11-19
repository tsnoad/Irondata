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
		$main .= "<form name='table' action='index.php?command=save_template&report_type=table&saved_report=".$_GET['saved_report']."' method='POST'>";
		$main .= "<input type='hidden' name='report_type' value='table'>"; 
		$main .= "<input type='hidden' value='save_template' id='command' name='command'/>";
		$query = Database::buildQuery("select", "ea_table", array("db_id"=>$_SESSION['curDB']), NULL, " order by ea_table_name");
		$tables = Database::runQuery($query);
		foreach ($tables as $i => $table) {
			$tableList[$table['ea_table_name']] = $table['ea_table_name'];
		}
						 
		$highestTrendVal = 0;
		$highestConsVal = 0;
	
		if ($_GET['sql'] == "true") {
			$showSql = true;
		} 
		/* If you are editing a saved report. Use the saved values instead of the default ones */
		if ($saved_report) {
			$saved_report['ATTRS']['NAME'] = str_replace("'","&apos;",$saved_report['ATTRS']['NAME']);
			$this->auditLog("table", "Viewed saved report template", $saved_report['ATTRS']['NAME'], $_GET['saved_report']);
			$values['save_as'] = $saved_report['ATTRS']['NAME'];
			$values['publish_report'] = $saved_report['ATTRS']['PUBLISH'];
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
			$values['save_as'] = $_SESSION['username']."-".date("Y-m-d");
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
		
		$main .= "<table id='trend_table'>";
		$main .= "<thead><tr>
			<th>Name</th>
			<th>Table</th>
			<th>Column</th>
			<th>Interval / Aggregate</th>
			<th>Constraints</th>
			<th>&nbsp;</th>
			</tr></thead><tbody id='trend_table_body'>";


		$main .= "<div id='table_main'>";
	
		/* X Axis */
		$main .= "<tr><td>X-Axis</td>";
		if ($showSql) {	
			$main .= "<td colspan='4'>";
			$main .= "<textarea name='sqlquery__X' class='sql' rows='6'>".$constraintval['X']['sql']."</textarea>";
			$main .= "</td>";
		} else {
			$main .= "<td><select name='table__X' id='table__X' onChange='changeOptions(&quot;table__X&quot;, &quot;columns__X&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['X']['table']);
			$main .= "</select></td>";
			$main .= "<td><select name='columns__X' id='columns__X' >".
				Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['X']['table'], $trendval['X']['columns'])."</select></td>";
			$main .= "<td><select name='aggregate__X'>".
				Common_Functions::generate_options(array('group10'=>'Interval of 10s', 'group100'=>'Interval of 100s', 'group1000'=>'Interval of 1000s'), $trendval['X']['aggregate'], "No Interval (default)")."</select></td>";
			$main .= "<td><input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__X\"); underClip(\"clipper__X\", \"constraints__X\");' >";
			$main .= "<iframe frameBorder=\"0\" scrolling=\"no\" name='clipper__X' id='clipper__X' width='0' height='0'></iframe>
				<div class='constraints' id='constraints__X' >
				<div class='cons_header' id='cons_header__X'><strong>Constraints (X-Axis)</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__X\"); makeInvisible(\"clipper__X\");'><br/><br/></div>
				<script type='text/javascript'>
				//<![CDATA[
				new Draggable('constraints__X', {handle:'cons_header__X', starteffect:false})
				//]]>
				</script>
				<div id='constraints_values__X' class='cons_values'>";
				
			foreach($constraintval['X'] as $i => $constraint) {
				$main .= "
					<div id='trend__X".$i."Div'>
					<select id='constraint_table__X__".$i."' name='constraint_table__X__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__X__".$i."&quot;,&quot;constraint_columns__X__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select> 
					<select id='constraint_columns__X__".$i."' name='constraint_columns__X__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__X__".$i."&quot;,&quot;constraint_columns__X__".$i."&quot;,&quot;constraint_type__X__".$i."&quot;,&quot;constraint_value__X__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select> 
					<select name='constraint_type__X__".$i."' id='constraint_type__X__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__X__".$i."&quot;,&quot;constraint_columns__X__".$i."&quot;,&quot;constraint_type__X__".$i."&quot;,&quot;constraint_value__X__".$i."&quot;);'>".Common_Functions::generate_comparisons($constraint['constraint_type']) . "</select>
					<input type='text' name='constraint_value__X__".$i."' id='constraint_value__X__".$i."' value='".$constraint['constraint_value']."'>";
				if ($i == 0) {
					$trend_cons_class = '__first';
				} else {
					$trend_cons_class = '__notfirst';
				}
				$main .= "<input class='".$trend_cons_class."' type='button' value='-' onclick='javascript:removeElement(\"constraints_values__X\", \"X\", \"".$i."\"); underClip(\"clipper__X\", \"constraints__X\");' name='remove_constraint' >";
				$main .= "</div>
					";
			}
			$main .= "</div><br/>";
			$main .= "<input style='margin-bottom: 20px; margin-left: 20px;' type='button' value='Add New Constraint' onclick='javascript:addConstraint(\"constraints_values__X\", \"__X\"); underClip(\"clipper__X\", \"constraints__X\");' name='additional_constraint' />";
			$main .= "</div>";
			$main .= "</td>";
		}
		$main .= "</tr>";

		/* Y Axis */
		$main .= "<tr><td>Y-Axis</td>";
		if ($showSql) {	
			$main .= "<td colspan='4'>";
			$main .= "<textarea name='sqlquery__Y' class='sql' rows='6'>".$constraintval['Y']['sql']."</textarea>";
			$main .= "</td>";
		} else {
			$main .= "<td><select name='table__Y' id='table__Y' onChange='changeOptions(&quot;table__Y&quot;, &quot;columns__Y&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['Y']['table']);
			$main .= "</select></td>";
			$main .= "<td><select name='columns__Y' id='columns__Y' >".
				Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['Y']['table'], $trendval['Y']['columns'])."</select></td>";
			$main .= "<td><select name='aggregate__Y'>".
				Common_Functions::generate_options(array('group10'=>'Interval of 10s', 'group100'=>'Interval of 100s', 'group1000'=>'Interval of 1000s'), $trendval['Y']['aggregate'], "No Interval (default)")."</select></td>";
			$main .= "<td><input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__Y\"); underClip(\"clipper__Y\", \"constraints__Y\");' >";
			$main .= "<iframe frameBorder=\"0\" scrolling=\"no\" name='clipper__Y' id='clipper__Y' width='0' height='0'></iframe>
				<div class='constraints' id='constraints__Y'>
				<div class='cons_header' id='cons_header__Y'><strong>Constraints (Y-Axis)</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__Y\"); makeInvisible(\"clipper__Y\");'><br/><br/></div>
				<script type='text/javascript'>
				//<![CDATA[
				new Draggable('constraints__Y', {handle:'cons_header__Y', starteffect:false})
				//]]>
				</script>
				<div id='constraints_values__Y' class='cons_values' class='cons_values'>
				";
				
			foreach($constraintval['Y'] as $i => $constraint) {
				$main .= "
					<div id='trend__Y".$i."Div'>
					<select id='constraint_table__Y__".$i."' name='constraint_table__Y__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__Y__".$i."&quot;,&quot;constraint_columns__Y__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select>
					<select id='constraint_columns__Y__".$i."' name='constraint_columns__Y__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Y__".$i."&quot;,&quot;constraint_columns__Y__".$i."&quot;,&quot;constraint_type__Y__".$i."&quot;,&quot;constraint_value__Y__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select>
					<select name='constraint_type__Y__".$i."' id='constraint_type__Y__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Y__".$i."&quot;,&quot;constraint_columns__Y__".$i."&quot;,&quot;constraint_type__Y__".$i."&quot;,&quot;constraint_value__Y__".$i."&quot;);'>".Common_Functions::generate_comparisons($constraint['constraint_type'])."</select>
					<input type='text' name='constraint_value__Y__".$i."' id='constraint_value__Y__".$i."' value='".$constraint['constraint_value']."'>";
				if ($i == 0) {
					$trend_cons_class = '__first';
				} else {
					$trend_cons_class = '__notfirst';
				}
				$main .= "<input class='".$trend_cons_class."' type='button' value='-' onclick='javascript:removeElement(\"constraints_values__Y\", \"Y\", \"".$i."\"); underClip(\"clipper__Y\", \"constraints__Y\");' name='remove_constraint' >";
				$main .= "</div>
					";
			}
			$main .= "</div><br/>";
			$main .= "<input style='margin-bottom: 20px; margin-left: 20px;' type='button' value='Add New Constraint' onclick='javascript:addConstraint(\"constraints_values__Y\", \"__Y\"); underClip(\"clipper__Y\", \"constraints__Y\");' name='additional_constraint' />";
			$main .= "</div>";
			$main .= "</td>";
		}
		$main .= "</tr>";

		/* Z Axis */
		$main .= "<tr><td>Z-Axis (optional)</td>";
		if ($showSql) {	
			$main .= "<td colspan='4'>";
			$main .= "<textarea name='sqlquery__Z' class='sql' rows='6'>".$constraintval['Z']['sql']."</textarea>";
			$main .= "</td>";
		} else {
			$main .= "<td><select name='table__Z' id='table__Z' onChange='changeOptions(&quot;table__Z&quot;, &quot;columns__Z&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['Z']['table']);
			$main .= "</select></td>";
			$main .= "<td><select name='columns__Z' id='columns__Z' >".
				Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['Z']['table'], $trendval['Z']['columns'])."</select></td>";
			$main .= "<td><select name='aggregate__Z'>".
				Common_Functions::generate_options(array('group10'=>'Interval of 10s', 'group100'=>'Interval of 100s', 'group1000'=>'Interval of 1000s'), $trendval['Z']['aggregate'], "No Interval (default)")."</select></td>";
			$main .= "<td><input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__Z\"); underClip(\"clipper__Z\", \"constraints__Z\");' >";
			$main .= "<iframe frameBorder=\"0\" scrolling=\"no\" name='clipper__Z' id='clipper__Z' width='0' height='0'></iframe>
				<div class='constraints' id='constraints__Z'>
				<div class='cons_header' id='cons_header__Z'><strong>Constraints (Z-Axis)</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__Z\"); makeInvisible(\"clipper__Z\");'><br/><br/></div>
				<script type='text/javascript'>
				//<![CDATA[
				new Draggable('constraints__Z', {handle:'cons_header__Z', starteffect:false})
				//]]>
				</script>
				<div id='constraints_values__Z' class='cons_values'>";
				
			foreach($constraintval['Z'] as $i => $constraint) {
				$main .= "
					<div id='trend__Z".$i."Div'>
					<select id='constraint_table__Z__".$i."' name='constraint_table__Z__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__Z__".$i."&quot;,&quot;constraint_columns__Z__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select>
					<select id='constraint_columns__Z__".$i."' name='constraint_columns__Z__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Z__".$i."&quot;,&quot;constraint_columns__Z__".$i."&quot;,&quot;constraint_type__Z__".$i."&quot;,&quot;constraint_value__Z__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select>
					<select name='constraint_type__Z__".$i."' id='constraint_type__Z__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__Z__".$i."&quot;,&quot;constraint_columns__Z__".$i."&quot;,&quot;constraint_type__Z__".$i."&quot;,&quot;constraint_value__Z__".$i."&quot;);'>".
					Common_Functions::generate_comparisons($constraint['constraint_type'])."</select>
					<input type='text' name='constraint_value__Z__".$i."' id='constraint_value__Z__".$i."' value='".$constraint['constraint_value']."'>";
				if ($i == 0) {
					$trend_cons_class = '__first';
				} else {
					$trend_cons_class = '__notfirst';
				}
				$main .= "<input class='".$trend_cons_class."' type='button' value='-' onclick='javascript:removeElement(\"constraints_values__Z\", \"Z\", \"".$i."\"); underClip(\"clipper__Z\", \"constraints__Z\");' name='remove_constraint' >";
				$main .= "</div>
					";
			}
			$main .= "</div><br/>";
			$main .= "<input style='margin-bottom: 20px; margin-left: 20px;' type='button' value='Add New Constraint' onclick='javascript:addConstraint(\"constraints_values__Z\", \"__Z\"); underClip(\"clipper__Z\", \"constraints__Z\");' name='additional_constraint' />";
			$main .= "</div>";
			$main .= "</td>";
		}
		$main .= "</tr>";

		/* POINT AXIS */
		$main .= "<tr><td>Table Cells</td>";
		if ($showSql) {	
			$main .= "<td colspan='4'>";
			$main .= "<textarea name='sqlquery__C' class='sql' rows='6'>".$constraintval['C']['sql']."</textarea>";
			$main .= "</td>";
		} else {
			$main .= "<td><select name='table__C' id='table__C' onChange='changeOptions(&quot;table__C&quot;, &quot;columns__C&quot; );'>";
			$main .= Common_Functions::generate_options($tableList, $trendval['C']['table']);
			$main .= "</select></td>";
			$main .= "<td><select name='columns__C' id='columns__C' >".
				Common_Functions::generate_column_options($_SESSION['curDB_psql'], $trendval['C']['table'], $trendval['C']['columns'])."</select></td>";
			$main .= "<td><select name='aggregate__C'>".
				Common_Functions::generate_aggregates($trendval['C']['aggregate'])."</select></td>";
			$main .= "<td><input type=button value='Constraints' id='constraints' onclick='makeVisible(\"constraints__C\"); underClip(\"clipper__C\", \"constraints__C\");' >";
			$main .= "<iframe frameBorder=\"0\" scrolling=\"no\" name='clipper__C' id='clipper__C' width='0' height='0'></iframe>
				<div class='constraints' id='constraints__C'>
				<div class='cons_header' id='cons_header__C'><strong>Constraints (Cells)</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__C\"); makeInvisible(\"clipper__C\");'><br/><p>These values will automatically be constrained against the X, Y, and Z axis</p></div>
				<script type='text/javascript'>
				//<![CDATA[
				new Draggable('constraints__C', {handle:'cons_header__C', starteffect:false})
				//]]>
				</script>
				<div id='constraints_values__C' class='cons_values'>";
				
			foreach($constraintval['C'] as $i => $constraint) {
				$main .= "
					<div id='trend__C".$i."Div'>
					<select id='constraint_table__C__".$i."' name='constraint_table__C__".$i."' onChange='javascript:changeOptions(&quot;constraint_table__C__".$i."&quot;,&quot;constraint_columns__C__".$i."&quot;);' >".Common_Functions::generate_options($tableList, $constraint['constraint_table'])."</select>
					<select id='constraint_columns__C__".$i."' name='constraint_columns__C__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__C__".$i."&quot;,&quot;constraint_columns__C__".$i."&quot;,&quot;constraint_type__C__".$i."&quot;,&quot;constraint_value__C__".$i."&quot;);'>".Common_Functions::generate_column_options($_SESSION['curDB_psql'], $constraint['constraint_table'],$constraint['constraint_columns'])."</select>
					<select name='constraint_type__C__".$i."' id='constraint_type__C__".$i."' onChange='javascript:changeColumn(&quot;constraint_table__C__".$i."&quot;,&quot;constraint_columns__C__".$i."&quot;,&quot;constraint_type__C__".$i."&quot;,&quot;constraint_value__C__".$i."&quot;);'>".Common_Functions::generate_comparisons($constraint['constraint_type'])."</select>
					<input type='text' name='constraint_value__C__".$i."' id='constraint_value__C__".$i."' value='".$constraint['constraint_value']."'>";
				if ($constraint['constraint_auto'] == "on") {
					$checked = "checked";
				} else {
					$checked = "";
				}
				$main .= "<input type='checkbox' name='constraint_auto__C__".$i."' id='constraint_auto__C__".$i."' ".$checked."'/>";
				if ($i == 0) {
					$trend_cons_class = '__first';
				} else {
					$trend_cons_class = '__notfirst';
				}
				$main .= "<input class='".$trend_cons_class."' type='button' value='-' onclick='javascript:removeElement(\"constraints_values__C\", \"C\", \"".$i."\"); underClip(\"clipper__C\", \"constraints__C\");' name='remove_constraint' >";
				$main .= "</div>
					";
			}
			$main .= "</div><br/>";
			$main .= "<input style='margin-bottom: 20px; margin-left: 20px;' type='button' value='Add New Constraint' onclick='javascript:addConstraint(\"constraints_values__C\", \"__C\"); underClip(\"clipper__C\", \"constraints__C\");' name='additional_constraint' />";
			$main .= "</div>";
			$main .= "</td>";
		}
		$main .= "</tr>";

		$main .= "</tbody></table>";
		$main .= "<br />";
		$main .= "<hr>";
		$main .= "<br />";
		if ($showSql) {	
			$main .= "<p>The Axis's must be in the format of; <br/><em>SELECT DISTINCT ? FROM ? ORDER BY ?</em></br/>Where ? should be the axis's above</p>";
			$main .= "<p>The Tables Cells must be in the format of; <br/><em>SELECT ? AS X, ? AS Y, [? AS Z], count(*) AS cell FROM ? GROUP BY X,Y [,Z] ORDER BY [Z,] Y,X</em></br/>Where ? should be the axis's above</p>";
		} else {
			$main .= "<label for='linkTo'>Link this to</label>".showLinkTo("listing", $values['LINKTO'])."<br/>";
		}
 		if ($values['publish_report']) {
 			$pub_sel = "checked";
 		}
 		$main .= "<label for='publish_report'>Publish</label><input value='1' type='checkbox' name='publish_report' id='publish_report' ".$pub_sel."><br />";
		$main .= "<input type='text' name='save_as' value='".$values['save_as']."'> <input class='button' type='submit' name='submit' value='Save'> <input class='button' type='submit' name='submit' value='Save As'>";
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
	
		$auto = '';
		$autovalues = array();
		$autovalues_string = "";
		#Getting user input
		foreach ($report['CHILDREN'] as $i => $child) {
			if ($child['NAME'] == "AXIS") {
				foreach ($child['CHILDREN'] as $j => $constraint) {
					foreach ($constraint['CHILDREN'] as $k => $attr) {
						$cons[$attr['NAME']] = $attr['TAGDATA'];
						if ($attr['NAME'] == "CONSTRAINT_VALUE") {
							$valk = $k;
						}
						if ($attr['NAME'] == "CONSTRAINT_AUTO" && $attr['TAGDATA'] == "on") {
							if ($_REQUEST['auto'] ) {
								$report['CHILDREN'][$i]['CHILDREN'][$j]['CHILDREN'][$valk]['TAGDATA'] = $_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']];
								$autovalues[$cons['CONSTRAINT_COLUMNS']] = $_REQUEST['auto'][$cons['CONSTRAINT_TABLE']][$cons['CONSTRAINT_COLUMNS']][$cons['CONSTRAINT_TYPE']];
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
		$top = "<div class='heading'>
		<h3>Table Report - ".$report_name."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='tmp/".$report_name.$suffix.".pdf'><img src='images/pdf.png' alt='Download PDF' title='Download PDF'></a> <a href='tmp/".$report_name.$suffix.".xls'><img src='images/spreadsheet.png' title='Download Spreadsheet' alt='Download Spreadsheet'></a></h3>
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
			$queryString .= "<strong>Query $i: </strong>".$query."<br/><br/>\n";
		}

		
	
		$xaxis = Database::runQuery($queries['X'], $_SESSION['curDB_psql']);
		$X = array_keys($xaxis[0]);
		$X = $X[0];
		#Check and alert to user if the query returns no results.
		if (!$xaxis) {
			$main = Common_Functions::invalid($report_name, "X Axis");
			return $main;
		}
			
		$yaxis = Database::runQuery($queries['Y'], $_SESSION['curDB_psql']);
		$Y = array_keys($yaxis[0]);
		$Y = $Y[0];
		#Check and alert to user if the query returns no results. 
		if (!$yaxis) {
			$main = Common_Functions::invalid($report_name, "Y Axis");
			return $main;
		}

		if ($queries['Z']) {
			$zaxis = Database::runQuery($queries['Z'], $_SESSION['curDB_psql']);
		} else {
			$zaxis[0][$Z] = "";
		}
		$Z = array_keys($zaxis[0]);
		$Z = $Z[0];
		#Check and alert to user if the query returns no results. 
		if (!$zaxis) {
			$main = Common_Functions::invalid($report_name, "Z Axis");
			return $main;
		}

		$cells = Database::runQuery($queries['C'], $_SESSION['curDB_psql']);
		#Check and alert to user if the query returns no results. 
		if (!$cells) {
			$main = Common_Functions::invalid($report_name, "table cells");
			return $main;
		}

		$excelcells = array();
		/* Get the total for the percentage*/
		foreach ($cells as $i => $cell) {
			$percentageTotal += $cell['cell'];
		}
		$cell = array_shift($cells);
		foreach ($zaxis as $h => $zcell) {
			$main .= "<strong>".$zcell[$Z]."</strong>";
			$main .= "<table class='bordered'>";
			$first = true;
			$totals = array();
			$avgcountY = array();
			if ($zcell[$Z] == '') {
				$excelz = 'Sheet';
			} else {
				$excelz = $zcell[$Z];
			}
			$excelcells[$excelz] = array();
			foreach ($yaxis as $i => $ycell) {
				$line = "";
				$main .= "<tr>";
				$firstString = "<td class='bordered'>&nbsp;</td>";
				if ($ycell[$Y] || $ycell[$Y] != '') {
					$line .= "<td class='bordered'><strong>".$ycell[$Y]."</strong></td>";
				} else {
					$line .= "<td class='bordered'><strong>Unknown</strong></td>";
				}
				$sum = 0;
				$avgcount =0;
				$max = 0;
				$min = 1000;
				if ($ycell[$Y] == '') {
					$excely = 'Unknown';
				} else {
					$excely = $ycell[$Y];
				}
				$excelcells[$excelz][$excely] = array();
				foreach ($xaxis as $j => $xcell) {
					if ($xcell[$X] == '') {
						$excelx = 'Unknown';
					} else {
						$excelx = $xcell[$X];
					}
					$excelcells[$excelz][$excely][$excelx] = '';
					if ($first == true) {
						if ($xcell[$X] || $xcell[$X] != '') {
							$firstString .= "<td class='bordered'><strong>".$xcell[$X]."</strong></td>";
						} else {
							$firstString .= "<td class='bordered'><strong>Unknown</strong></td>";
						}
					}
					if ($cell['y'] == $ycell[$Y] and $cell['x'] == $xcell[$X]) {
						$excelcells[$excelz][$excely][$excelx] = $cell['cell'];
						$linerun = true;
						if ($aggregate == "percentage") {
							$printno = $cell['cell']."<br/>".round($cell['cell']/$percentageTotal*100, 2)."%";
						} else {
							$printno = $cell['cell'];
						}
						if ($linkto) {
							if($_GET['report_from']) {
								$line .= "<td><a href='index.php?line=".$_GET['line']."&report_type=listing&point=".$_GET['point']."&parent_from=".$_GET['report_from']."&report_from=".$_REQUEST['saved_report']."&command=run_report&saved_report=".$linkto."&point=".$timestamp."&X=".htmlentities(rawurlencode($xcell[$X]), ENT_QUOTES)."&Y=".htmlentities(rawurlencode($ycell[$Y]), ENT_QUOTES)."&Z=".htmlentities(rawurlencode($zcell[$Z]), ENT_QUOTES)."'>".$printno."</a></td>";
							} else {
								$line .= "<td><a href='index.php?report_type=listing&report_from=".$_REQUEST['saved_report']."&command=run_report&saved_report=".$linkto."&point=".$timestamp."&X=".htmlentities(rawurlencode($xcell[$X]), ENT_QUOTES)."&Y=".htmlentities(rawurlencode($ycell[$Y]), ENT_QUOTES)."&Z=".htmlentities(rawurlencode($zcell[$Z]), ENT_QUOTES)."'>".$printno."</a></td>";
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
						$line .= "<td class='bordered'>-</td>";
						$totals[$xcell[$X]] += 0;
					}
				}
				/* Printing the X-Axis totals */
				if ($first == true) {
					if ($sql == false) {
						$firstString = $firstString."<td class='bordered'><strong> Total </strong></td>";
					}
					$firstString .= "</tr><tr>";
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
					$line .= "<td> $printno </td>";
				}
				$main .= $line;
				$main .= "</tr>";
			}
			/* SQL Queries dont have totals */
			if ($sql == false) {
				$main .= "<tr><td class='bordered'><strong> Total </strong></td>";
				$sum = 0;
				$avgCount = 0;
				foreach ($totals as $i => $sub) {
					if ($aggregate == "percentage") {
						$printno = $sub."<br/>".round($sub/$percentageTotal*100, 2)."%";
						$sum += $sub;
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
					$avgCount++;
				}
				if ($aggregate == "percentage") {
					$main .= "<td>".$sum."</td></tr>";
				} elseif ($aggregate == "avg") {
					$main .= "<td>".round($sum/$avgCount, 2)."</td></tr>";
				} elseif ($aggregate == "max") {
					$main .= "<td>".max($maxY)."</td></tr>";
				} elseif ($aggregate == "min") {
					$main .= "<td>".min($minY)."</td></tr>";
				} else {
					$main .= "<td>".$sum."</td></tr>";
				}
			}
			$main .= "</table><br />";
			$main .= "
<!--NewPage--><br />";
		}
		$main = substr($main, 0, -20); 
		$excel = Common_Functions::writeExcel($excelcells, "tmp/".$report_name.$suffix.".xls");
		$pdf = Common_Functions::makePdf($main, $report_name.$suffix, array(), $autovalues); /* The ./tmp/ directory is automatically added, as is the .pdf extension. */
		
		global $saveicons;
		$saveicons = "index.php?command=save_report&saved_report=".$_GET['saved_report']."&report_type=table&suffix=".$suffix;
		
		$this->auditLog("table", "Ran Report", $report_name, $_GET['saved_report']);
		
		$main = $save_link.$top.$main;
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
		$saveddir = $conf['Dir']['FullPath']."saved/table";
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
							$pdf = "<a href='".$conf['Dir']['WebPath']."/saved/table/".$query."/".$tmp.".pdf'><img src='images/pdf.png' title='View PDF' alt='View PDF'></a>";
						} else {
							$pdf = '';
						}
						$main .= "<li>$tmp: <a href='".$conf['Dir']['WebPath']."/saved/table/$query/$file'><img src='images/spreadsheet.png' title='View Spreadsheet' alt='View Spreadsheet'></a> ".$pdf."</li>";
						# <a href='index.php?command=delete_report&saved_report=$query&report_type=table&id=$tmp'>delete</a>
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
			$lastupdate = $this->getLastUpdate('table', $report_name);
			if($lastupdate[0]['username'] != null && $lastupdate[0]['username'] != "") {
				$main .= "<p>Report Template last updated on <strong>".$lastupdate[0]['time']."</strong> by <strong>".$lastupdate[0]['username']."</strong></p>";
			}
			
			return $main;
		} else {
			$file_name = $path."/$which.xls";
			if (file_exists($file_name)) {
				$this->auditLog("table", "Viewed pre-generated Report", $report_name, $query);
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
		$filename = $conf['Dir']['FullPath']."saved/table/$query/$which.xls";
		if (file_exists($filename)) {
			$res = unlink($filename);
		}
		$filename = $conf['Dir']['FullPath']."saved/table/$query/$which.pdf";
		if (file_exists($filename)) {
			$res = unlink($filename);
		}

		$this->auditLog("table", "Deleted Report Output - $query/$which", $this->getReportName($query), $query);
		
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
		$path = $conf['Dir']['FullPath']."saved/table";
		$dest_dir = $path."/".$id;
	
		if (!is_dir($path)) {
			mkdir($path);
		}
		
		if (!is_dir($dest_dir)) {
			mkdir($dest_dir);
		}
		
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".xls", $dest_dir."/".$report_name.$suffix.".xls");
		copy($conf['Dir']['FullPath']."tmp/".$report_name.$suffix.".pdf", $dest_dir."/".$report_name.$suffix.".pdf");
		
		$this->auditLog("table", "Saved Report Output; filename(s) like: ".$report_name." - ".date("Y-m-d"), $report_name, $id);

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
		$table=0;
		if(is_array($_SESSION['reports'])) {
			foreach ($_SESSION['reports'] as $i => $rule) {
				if ($rule['report_type'] == "table" && $rule['db_id'] == $_SESSION['curDB']) {
					if ($rule['published'] || $_SESSION['unpublished'] == true) {
						$pub_class = "published";
						$table++;
					} else {
						$pub_class = "notpublished";
					}
					$tablelist .= "<span class='".$pub_class."'>&raquo; <a href='?command=view_report&report_type=table&saved_report=".$rule['report_id']."'>".$rule['report_name']."</a><br /></span>";
				} 
			}
		}
		
		$sideString .= "<b>Table</b><br/>";
		if ($_SESSION['curDB'] && ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create Report Templates']['access'] == "t")) {
			$sideString .= "&raquo; <a href='?command=new&report_type=table&new=true'>New Table Report</a><br />";
			if ($_SESSION['admin'] == "t" || $_SESSION['functions']['Create/Edit Raw SQL']['access'] == "t") {
				$sideString .= "&raquo; <a href='?command=new&report_type=table&sql=true'>New Table SQL</a><br />";
			}
		}
                $sideString .= '<fieldset>
                        <legend onclick="Effect.toggle(\'side-table\',\'slide\'); return false;">Table Reports ('.$table.')</legend>
                        <div id="side-table" style="display: none;"><span>';
                $sideString .= $tablelist;
                $sideString .= "</span></div></fieldset><br />";
                return $sideString;
	}


    /**
	 * buildSQL creates an SQL query (or array of SQL queries) which will be used by the module
	 * to interrogate the database and produce results.
	 *
	 * @param	string	$rules					The XML rules to be executed
	 * @param	string	$rules					The XMl rules of a parent report, if this report was linked-to
	 * @param	int	$line_no				The line number
	 * @param	array	$cell					A pre-computed array of values from the XML ruleset
	 * @param	array	$constraintCell				A pre-computed array of values from the XML ruleset
	 * @param	array	$timeCell				A pre-computed array of values from the XML ruleset
	 * @param	array	$maxTimestamp	The maximum timestamp in the database
	 * @param	array	$minTimestamp	The minimum timestamp in the database
	 * @return	array	The queries to be executed		
	 */

/* TODO: cell constraints aren't actually applied to axis queries - they need to be! This is urgent! */

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
				if ($entry['AGGREGATE'] == "group10" || $entry['AGGREGATE'] == "group100" || $entry['AGGREGATE'] == "group1000") {
					$number = substr($entry['AGGREGATE'], 5);
					$set[] = "cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)/".$number."||'0 - '||cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)/".$number."||'9', cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)/".$number." as order".$i;
					$sets[] = "cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)/".$number."||'0 - '||cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)/".$number."||'9' as ".$i.", cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)/".$number." as order".$i;
					$order = "cast(".$entry['TABLE'].".".$entry['COLUMNS']." as int)/".$number."";
					$overorder[$i] = "order".$i;
				} else {
					$set[] = $entry['TABLE'].".".$entry['COLUMNS']." ";
					$sets[] = $entry['TABLE'].".".$entry['COLUMNS']." as ".$i ." ";
					$order = $entry['TABLE'].".".$entry['COLUMNS'];
				}
				foreach ($constraintCell[$i] as $j => $c_entry) {
					if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
						continue;
					}
					if ($c_entry['CONSTRAINT_COLUMNS'] == "start_date" || $c_entry['CONSTRAINT_COLUMNS'] == "end_date") {
						$timestamp_done = true;
					}
					$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
					$tables[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
	                                $where[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
	                                $wheres[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
				}
				if($i != 'C') {
					foreach ($constraintCell['C'] as $j => $c_entry) {
						if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
							continue;
						}
						if ($c_entry['CONSTRAINT_COLUMNS'] == "start_date" || $c_entry['CONSTRAINT_COLUMNS'] == "end_date") {
							$timestamp_done = true;
						}
						$table[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
						$tables[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
		                                $where[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
		                                $wheres[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
					}
				}
				if ($i == "Z") {
					foreach ($orCell as $j => $orSet) {
						$whereOrSub = "";
						foreach ($orSet as $k => $o_entry) {
							if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
								continue;
							}
							if ($o_entry['CONSTRAINT_COLUMNS'] == "start_date" || $c_entry['CONSTRAINT_COLUMNS'] == "end_date") {
								$timestamp_done = true;
							}
							$table[$o_entry['CONSTRAINT_TABLE']] = $o_entry['CONSTRAINT_TABLE'];
							$tables[$o_entry['CONSTRAINT_TABLE']] = $o_entry['CONSTRAINT_TABLE'];
	                               			$whereOrSub .= $this->buildConstraintClause($o_entry['CONSTRAINT_TYPE'], $o_entry['CONSTRAINT_VALUE'], $o_entry['CONSTRAINT_TABLE'], $o_entry['CONSTRAINT_COLUMNS']);
						}
						$whereOr .= "(" . rtrim($whereOrSub, "and") .") or";
					}
					$whereOr = rtrim($whereOr, "or");
				}
				if (!$timestamp_done) {
					$where[] = "(".$entry['TABLE'].".end_date = '".$maxTimestamp."')";
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
				$order = "order by ".$order;
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
                                $sets[] = "round(".$entry['AGGREGATE']."(cast(".$entry['TABLE'].".".$entry['COLUMNS']." as numeric)), 2) as cell";
			} elseif ($entry['AGGREGATE'] == 'max' || $entry['AGGREGATE'] == 'min' || $entry['AGGREGATE'] == 'sum' ) {
                                $sets[] = "round(".$entry['AGGREGATE']."(cast(".$entry['TABLE'].".".$entry['COLUMNS']." as numeric)), 2) as cell";
                        } else {
                                $sets[] = $entry['AGGREGATE']."(".$entry['TABLE'].".".$entry['COLUMNS'].") as cell";
                        }
			if(is_array($linkFromRules) && ($line_no > 0 || $line_no === 0)) {
				for($index = 0; $index <= count($linkFromRules['CHILDREN']); $index++) {
					if($linkFromRules['CHILDREN'][$index]['CHILDREN'][0]['TAGDATA'] == $line_no) {
						$line_index = $index;
					}
				}
				$linkedConstraints = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'];
				for($c = 5; $c < count($linkedConstraints); $c++) {
					if ($linkedConstraints[$c]['CHILDREN'][1]['TAGDATA'] == "start_date" || $linkedConstraints[$c]['CHILDREN'][1]['TAGDATA'] == "end_date") {
						$timestamp_done = true;
					}
					
					$tables[$linkedConstraints[$c]['CHILDREN'][0]['TAGDATA']] = $linkedConstraints[$c]['CHILDREN'][0]['TAGDATA'];
					
	                                $wheres[] = $this->buildConstraintClause($linkedConstraints[$c]['CHILDREN'][2]['TAGDATA'], $linkedConstraints[$c]['CHILDREN'][3]['TAGDATA'], $linkedConstraints[$c]['CHILDREN'][0]['TAGDATA'], $linkedConstraints[$c]['CHILDREN'][1]['TAGDATA']);
				}
				/* We know there will always be an X-Axis */
				$tables[$linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA']] = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA'];
				$wheres[] = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA'].".start_date <= '".$_GET['point']."'";
				$wheres[] = $linkFromRules['CHILDREN'][$line_index]['CHILDREN'][1]['TAGDATA'].".end_date > '".$_GET['point']."'";
				$timestamp_done = true;
			} 
			foreach ($constraintCell['C'] as $j => $c_entry) {
				if ($c_entry['CONSTRAINT_TABLE'] == "" || $c_entry['CONSTRAINT_TABLE'] == "Select Table ...") {
					continue;
				}
				if ($c_entry['CONSTRAINT_COLUMNS'] == "start_date" || $c_entry['CONSTRAINT_COLUMNS'] == "end_date") {
					$timestamp_done = true;
				}
				$tables[$c_entry['CONSTRAINT_TABLE']] = $c_entry['CONSTRAINT_TABLE'];
                                $wheres[] = $this->buildConstraintClause($c_entry['CONSTRAINT_TYPE'], $c_entry['CONSTRAINT_VALUE'], $c_entry['CONSTRAINT_TABLE'], $c_entry['CONSTRAINT_COLUMNS']);
				if (!$timestamp_done) {
					$wheres[] = $c_entry['CONSTRAINT_TABLE'].".end_date = '".$maxTimestamp."'";
				}
			}
			if (!$timestamp_done) {
				$wheres[] = "(".$entry['TABLE'].".end_date = '".$maxTimestamp."')";
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
			$override_group = '';
			$override_order = '';
			if ($overorder['X']) {
				$overorder['X'] .= ",";
			}
			if ($overorder['Y']) {
				$overorder['Y'] .= ",";
			}
			if ($overorder['Z']) {
				$overorder['Z'] .= ",";
			}
			$orders = " group by ".$overorder['X']." X, ".$overorder['Y']." Y ".$overorder['Z']." ".$groupZ." order by ".$overorder['Z']." ".$orderZ." ".$overorder['Y']." Y, ".$overorder['X']." X";
			$query['C'] = Database::buildQuery($type, $tables, $wheres, $sets, $orders) .";";
			
		}	
		return $query;
	}


}









?>
