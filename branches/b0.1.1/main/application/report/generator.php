<?php

/**
 * showChange
 * 
 * Displays the change database page.
 *
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function showChange() {
	$main .= "<form name='dbChange' method='POST' >";
	$query = Database::buildQuery("select", "db");
	$dbs = Database::runQuery($query);
	/* Admins can see all databases regardless of ACL */
	if ($_SESSION['admin'] == "t") {
		$availDB = $dbs;	
	} else {
		foreach ($_SESSION['tables'] as $i => $table) {
			foreach ($dbs as $j => $db) {
				if ($db['db_id'] == $table['db_id']) {
					/* Databases ACL is based on table ACL. $j will be the same for each identical database id.  */
					$availDB[$j] = $db;
					break;
				}
			}
		}
	}
	/* This is the raw text and HTML. */
	$main .= "Please select a database from the list. All report that you can create and run will be based on the data available within this database or datamart<br/><br/>";
	$main .= "<select name='db'>";
	foreach ($availDB as $i => $db) {
		$selected = "";
		if ($db['db_id'] == $_SESSION['curDB']) {
			$selected = " selected";
		}
		$main .= "<option value=".$db['db_id']." ".$selected.">".$db['db_name']."</option>";
	}
	$main .= "</select>";
	$main .= "<input type='submit' value='Change Database'>";
	$main .= "</form>";
	return $main;
}

/**
 * makeChange
 * 
 * Changes the current database. This effects the tables and reports that can be utilised.
 * It will update the curDB and curDB_psql SESSION variables.
 *
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function makeChange() {
	$query = Database::buildQuery("select", "db", array("db_id"=>$_POST['db']));
	$dbs = Database::runQuery($query);
	$_SESSION['curDB'] = $_POST['db'];
	$_SESSION['curDB_psql'] = $dbs[0]['psql_name'];
	if ($_SESSION['admin'] == "t") {
		$query = Database::buildQuery("select", "ea_table", array("db_id"=>$_SESSION['curDB']));
		$_SESSION['tables'] = Database::runQuery($query);
	} else {
		$query = "select * from ea_table t, tableacl x where t.ea_table_id=x.ea_table_id and x.username='".$_SESSION['username']."' and x.access='t' and t.db_id='".$_SESSION['curDB']."' ";
		$_SESSION['tables'] = Database::runQuery($query);
	}
	return $main;
}

function showConstraintBlock($tables) {
	$constraint .= "var constraintString = \"<select id='constraint_table__!!!__???' name='constraint_table__!!!__???' onChange='javascript:changeOptions(&quot;constraint_table__!!!__???&quot;,&quot;constraint_columns__!!!__???&quot;);' >".Common_Functions::generate_options($tables)."</select><select id='constraint_columns__!!!__???' name='constraint_columns__!!!__???' onChange='javascript:changeColumn(&quot;constraint_table__!!!__???&quot;,&quot;constraint_columns__!!!__???&quot;,&quot;constraint_type__!!!__???&quot;,&quot;constraint_value__!!!__???&quot;);'><option value=''>Select Column...</select><select name='constraint_type__!!!__???' id='constraint_type__!!!__???' onChange='javascript:changeColumn(&quot;constraint_table__!!!__???&quot;,&quot;constraint_columns__!!!__???&quot;,&quot;constraint_type__!!!__???&quot;,&quot;constraint_value__!!!__???&quot;);'><option value='is'>Is (Equals)</option><option value='lt'>Less Than</option><option value='gt'>Greater Than</option><option value='contains'>Contains</option><option value='ex'>Exists</option><option value='dne'>Does Not Exist</option></select><input type='text' name='constraint_value__!!!__???' id='constraint_value__!!!__???' value='Value'><input type='button' value='+' onclick='javascript:addElement(\\\"constraints__!!!\\\", constraintString, \\\"constraint\\\", \\\"!!!\\\"); underClip(\\\"clipper__!!!\\\", \\\"constraints__!!!\\\");' name='additional_constraint' ><input type='button' value='-' onclick='javascript:removeElement(\\\"constraints__!!!\\\", \\\"!!!\\\", \\\"???\\\"); underClip(\\\"clipper__!!!\\\", \\\"constraints__!!!\\\");' name='remove_constraint' >\";";
	return $constraint;
}

function showTrendBlock($tables) {
	$trend = "var trendString = \"<p>#1 <input type='text' name='title__!!!' value='' class='title'><br/></p><p><select id='table__!!!' name='table__!!!' onChange='javascript:changeOptions(&quot;table__!!!&quot;,&quot;columns__!!!&quot;);' >".Common_Functions::generate_options($tables)."</select><br/></p><p><select id='columns__!!!' name='columns__!!!'><option value=''>Select Column...</select><br/></p><p><select id='aggregate__!!!' name='aggregate__!!!'><option value=''>Select Aggregate Type...</option><option value='count once'>count once</option><option value='count'>count</option><option value='average'>average</option><option value='max'>max</option><option value='min'>min</option></select><br/></p><p><a href='javascript:pickColor(\\\"colour__!!!\\\");' id='colour__!!!' style='border: 1px solid #000000; font-family:Verdana; font-size:10px; text-decoration: none;'>&nbsp;&nbsp;&nbsp;</a> <input id='icolour__!!!' size='7' name='icolour__!!!'></p><p><input type=button value='Constraints' id='constraints' onclick='makeVisible(\\\"constraints__!!!\\\"); underClip(\\\"clipper__!!!\\\", \\\"constraints__!!!\\\");'></p><iframe style='position: absolute; display: none; z-index: 1;' frameBorder='0' scrolling='no' name=\\\"clipper__!!!\\\" id=\\\"clipper__!!!\\\" width='0' height='0'></iframe><div class='constraints' id='constraints__!!!'><strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\\\"constraints__!!!\\\"); makeInvisible(\\\"clipper__!!!\\\");'><br/><br/><select id='constraint_table__!!!__0' name='constraint_table__!!!__0' onChange='javascript:changeOptions(&quot;constraint_table__!!!__0&quot;,&quot;constraint_columns__!!!__0&quot;);' >".Common_Functions::generate_options($tables)."</select><select id='constraint_columns__!!!__0' name='constraint_columns__!!!__0'  onChange='javascript:changeColumn(&quot;constraint_table__!!!__0&quot;,&quot;constraint_columns__!!!__0&quot;,&quot;constraint_type__!!!__0&quot;,&quot;constraint_value__!!!__0&quot;);'><option value=''>Select Column...</option></select><select name='constraint_type__!!!__0' id='constraint_type__!!!__0' onChange='javascript:changeColumn(&quot;constraint_table__!!!__0&quot;,&quot;constraint_columns__!!!__0&quot;,&quot;constraint_type__!!!__0&quot;,&quot;constraint_value__!!!__0&quot;);'><option value='is'>Is (Equals)<option value='lt'>Less Than<option value='gt'>Greater Than<option value='contains'>Contains</select><input type='text' name='constraint_value__!!!__0' id='constraint_value__!!!__0' value='Value'><input type='button' value='+' onclick='javascript:addElement(\\\"constraints__!!!\\\", constraintString, \\\"constraint\\\"); underClip(\\\"clipper__!!!\\\", \\\"constraints__!!!\\\")' name='additional_constraint' ></div><input type='button' onclick='makeInvisible(this.parentNode.id); addElement(\\\"trend_lines\\\", trendString, \\\"trend\\\")' name='additional_trend' value='Add an additional trend line'><br/><input type='button' onclick='javascript:removeTrend(&quot;trend_lines&quot;, this.parentNode.id);' name='remove_trend' value='Remove this trend line' /><br/>\";";
	return $trend;
}

function showSqlTrendBlock($tables) {
	$trend = "var sqlTrendString = \"<p>#1 <input type='text' name='title__!!!' value='' class='title'><br/></p><p><textarea name='sqlquery__!!!' class='sql' rows='9'></textarea><br/><a href='javascript:pickColor(&quot;colour__!!!&quot;);' id='colour__!!!' style='background-color: #FFFFFF; border: 1px solid #000000; font-family:Verdana; font-size:10px; text-decoration: none;'> &nbsp;&nbsp;&nbsp;</a><input id='icolour__!!!' size='7' name='icolour__!!!' value='#FFFFFF'></p><input type='button' onclick='makeInvisible(this.parentNode.id); addElement(&quot;trend_lines&quot;, sqlTrendString, &quot;trend&quot;);' name='additional_trend' value='Add an additional trend line' style='position: relative; top: -10px'><br/><input type='button' onclick='javascript:removeTrend(&quot;trend_lines&quot;, this.parentNode.id);' name='remove_trend' value='Remove this trend line' style='position: relative; top: -10px' /><br/>\";";
	return $trend;
}

function showListTrendBlock($tables) {
	$trend .= "var trendString = \"<p><input type='text' name='title__!!!' value='' class='title' style='width: 170px;'><br/></p><p><select id='table__!!!' name='table__!!!' onChange='javascript:changeOptions(&quot;table__!!!&quot;,&quot;columns__!!!&quot;);' style='position: absolute; top: 175px; left: 20px; width: 170px;' >".Common_Functions::generate_options($tables)."</select><br/></p><p><select id='columns__!!!' name='columns__!!!' style='position: absolute; top: 200px; left: 20px; width: 170px;' ><option value=''>Select Column...</option></select><br/></p><input type='button' onclick='makeInvisible(\\\"trendlines__!!!\\\"); addElement(\\\"listlines\\\", trendString, \\\"trend\\\");' name='additional_trend' value='Add an additional column' style='position:absolute; top: 230px; left: 20px; width: 170px; '><input type='button' onclick='javascript:removeTrend(&quot;listlines&quot;, this.parentNode.id);' name='remove_trend' value='Remove this trend line' style='position:absolute; top: 260px; left: 20px; width: 170px' /><br/><br/>\";";
	return $trend;
}

function showLinkTo($type, $selected="") {
	$ret = "<select name='linkTo'>";
	$ret .= "<option value=''>Nothing</option>";
	if(count($_SESSION['reports']) > 0) {
		foreach ($_SESSION['reports'] as $report) {
			if ($report['report_type'] == $type) {
				$name = $report['report_name'];
				if($name == $selected) {
					$ret .= "<option value='$name' selected>$name</option>";
				} else {
					$ret .= "<option value='$name'>$name</option>";
				}
			}
		}
	}
	$ret .= "</select>";
	return $ret;
}


function displayLine($line, $empty="-") {
	$tmp = "<tr>";
	$el = array();
	
	$tok = strtok($line, ",");
	while ($tok != false) {
		array_push($el, $tok);
		$tok = strtok(",");
	}
	
	$j = count($el);
	for ($i=0;$i<$j;$i++) {
		if ($el[$i]==" ") {
			$cell = $empty;
		} else {
			$cell = $el[$i];
		}
		
		if ($i==0) {
			$tmp .= "<td class='bordered'><strong>".$cell."</strong></td>";
		} else {
			$tmp .= "<td class='bordered'>".$cell."</td>";
		}
	}
	$tmp .= "</tr>";
	return $tmp;
}

function displayListLine($line, $isBold=false) {
	$tmp = "<tr>";
	$el = array();
	
	$tok = strtok($line, "	");
	while ($tok != false) {
		array_push($el, $tok);
		$tok = strtok("	");
	}
	
	$j = count($el);
	for ($i=0;$i<$j-1;$i++) {
		if ($isBold) {
			$tmp .= "<td class='bordered'><strong>".$el[$i]."</strong></td>";
		} else {
			$tmp .= "<td class='bordered'>".$el[$i]."</td>";
		}
	}
	$tmp .= "</tr>";
	return $tmp;
}


?>
