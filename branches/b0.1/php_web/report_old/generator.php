<?php

/**
 * makeChange
 * 
 * Changes the current database. This effects the tables and reports that can be utilised.
 * It will update the curDB and curDB_psql SESSION variables.
 *
 * @return  string                  The HTML to print to the screen. It expects to be wrapped in a HTML page.
 */
function makeChange() {
	$query = Database::buildQuery("select", "db", array("db_id"=>$_POST['changeDB']));
	$dbs = Database::runQuery($query);
	$main .= "The new current database is: ".$dbs[0]['db_name'];
	$_SESSION['curDB'] = $_POST['changeDB'];
	$_SESSION['curDB_psql'] = $dbs[0]['psql_name'];
	return $main;
}

function showConstraintBlock($tables) {
	$constraint .= "var constraintString = \"<select id='constraint_table__!!!__???' name='constraint_table__!!!__???' onChange='javascript:changeOptions(&quot;constraint_table__!!!__???&quot;,&quot;constraint_columns__!!!__???&quot;);' >".Common_Functions::generate_options($tables)."</select> <select id='constraint_columns__!!!__???' name='constraint_columns__!!!__???' onChange='javascript:changeColumn(&quot;constraint_table__!!!__???&quot;,&quot;constraint_columns__!!!__???&quot;,&quot;constraint_type__!!!__???&quot;,&quot;constraint_value__!!!__???&quot;);'><option value=''>Select Column...</select> <select name='constraint_type__!!!__???' id='constraint_type__!!!__???' onChange='javascript:changeColumn(&quot;constraint_table__!!!__???&quot;,&quot;constraint_columns__!!!__???&quot;,&quot;constraint_type__!!!__???&quot;,&quot;constraint_value__!!!__???&quot;);'>".Common_Functions::generate_comparisons('')."</select> <input type='text' name='constraint_value__!!!__???' id='constraint_value__!!!__???' value='Value'> <input type='checkbox' name='constraint_auto__!!!__???' id='constraint_auto__!!!__???'/><input class='__notfirst' type='button' value='-' onclick='javascript:removeElement(\\\"constraints_values__!!!\\\", \\\"__!!!\\\", \\\"???\\\"); underClip(\\\"clipper__!!!\\\", \\\"constraints__!!!\\\");' name='remove_constraint' >\";";
	return $constraint;
}

function showListTrendBlock($tables) {
	$trend .= "var trendString = \"<p><input type='text' name='title__!!!' value='' class='title' style='width: 170px;'><br/></p><p><select id='table__!!!' name='table__!!!' onChange='javascript:changeOptions(&quot;table__!!!&quot;,&quot;columns__!!!&quot;);' style='position: absolute; top: 175px; left: 20px; width: 170px;' >".Common_Functions::generate_options($tables)."</select><br/></p><p><select id='columns__!!!' name='columns__!!!' style='position: absolute; top: 200px; left: 20px; width: 170px;' ><option value=''>Select Column...</option></select><br/></p><input type='button' onclick='makeInvisible(\\\"trendlines__!!!\\\"); addTrend(\\\"listlines\\\", trendString);' name='additional_trend' value='Add an additional column' style='position:absolute; top: 230px; left: 20px; width: 170px; '><input type='button' onclick='javascript:removeTrend(&quot;listlines&quot;, this.parentNode.id);' name='remove_trend' value='Remove this trend line' style='position:absolute; top: 260px; left: 20px; width: 170px' /><br/><br/>\";";
	return $trend;
}

function showLinkTo($type, $selected="") {
	$ret = "<select name='linkTo'>";
	$ret .= "<option value=''>Nothing</option>";
	if(count($_SESSION['reports']) > 0) {
		foreach ($_SESSION['reports'] as $report) {
			if ($report['report_type'] == $type && $report['db_id'] == $_SESSION['curDB']) {
				$id = $report['report_id'];
				$name = $report['report_name'];
				if($id == $selected) {
					$ret .= "<option value='$id' selected>$name</option>";
				} else {
					$ret .= "<option value='$id'>$name</option>";
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
