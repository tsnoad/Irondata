<?php

$file = file($_SERVER['argv'][1], FILE_IGNORE_NEW_LINES);
$mart = $_SERVER['argv'][2];
$selline = false;
$filename = substr($_SERVER['argv'][1], strrpos($_SERVER['argv'][1], "/")+1, -4);
$inputi = 0;
foreach ($file as $i => $line) {
	$line = str_replace("&lt;", "<", $line);
	$line = str_replace("&gt;", ">", $line);
	$line = trim($line);
	#if select is on a single line, or has ended
	if (strtolower(substr($line, -9, 9)) == "</select>") {
		#if select is on a single line
		if ($selline == false) {
			$selline .= " ".substr(substr($line, 0, -9), 8);
		} else {
			$selline .= " ".substr($line, 0, -9);
		}
		$procd = procline($selline);
		$select_name = $procd[0];
		$select_cell = $procd[1];
		$from = $procd[2];
		$where = $procd[3];
		$startdate = $procd[4];
		$enddate = $procd[5];

		$selline = false;
		continue;
	} elseif (strtolower(substr($line, 0, 8)) == "<select>") {
		#if select starts on this line
		$selline = " ".substr($line, 8);
	} elseif ($selline == true) {
		#if select start before and ends after this line
		$selline .= " ".$line;
	} elseif (strtolower(substr($line, 0, 14)) == "<defaulttable>") {
		$table = trim(substr(substr($line, 14), 0, -15));
	} elseif (strtolower(substr($line, 0, 11)) == "<transname>") {
		$transname = trim(substr(substr($line, 11), 0, -12));
	} elseif (strtolower(substr($line, 0, 11)) == "<transform>") {
		$tran_on = true;
		$type = strtolower(substr(substr(trim($file[$i+1]), 6), 0, -7));
		switch ($type) {
			case "add":
				$select_name[] = substr(substr(trim($file[$i+2]), 5), 0, -6);
				$select_cell[] = "'".substr(substr(trim($file[$i+3]), 7), 0, -8)."'";
				break;
			case "join":
				$in = explode(",", substr(substr(trim($file[$i+2]), 4), 0, -5));
				$out = substr(substr(trim($file[$i+3]), 5), 0, -6);
				$final = '';
				$finalno = null;
				foreach ($select_name as $j => $name) {
					if (in_array($name, $in)) {
						$final[]="coalesce(".$select_cell[$j].", '')";
					} 
					if ($name == $out) {
						$finalno = $j;
					}
				}
				$select_cell[$finalno] = implode("||", array_reverse($final));
				break;
			case "regex":
				$in = substr(substr(trim($file[$i+2]), 4), 0, -5);
				$out = substr(substr(trim($file[$i+3]), 5), 0, -6);
				$action = substr(substr(trim($file[$i+4]), 8), 0, -9);
				foreach ($select_name as $j => $name) {
					if ($name == $out) {
						$action = explode("/", $action);
						$select_cell[$j] = "replace(".$select_cell[$j].", '".$action[1]."', '".$action[2]."')";
					}
				}
				
				break;
		}
		if (strtolower(substr($line, 0, 12)) == "</transform>") {
			continue;
		}
	} elseif ($line == "</input>") {
		$inputi++;
		$results = buildquery($table, $select_name, $select_cell, $from, $where, $filename, $inputi, $startdate, $enddate);
		echo $results;
		echo "\n";
		$results = null;
	} 
}

function procline($line) {
	global $mart;
	$state = null;
	
	#positions
	$selpos = stripos($line, "select ");
	$frompos = stripos($line, " from ");
	if (strtolower(substr($line, $frompos, 10)) == " from age(") {
		$frompos = stripos($line, " from ", $frompos+4);
	}
	$wherepos = stripos($line, " where ");
	
	$select = substr($line, $selpos, $frompos-$selpos);
	$select = trim(substr($select, 6));
	
	#remove distinct
	if (strtolower(substr($select, 0, 8)) == "distinct") {
		$select = trim(substr($select, 8));
	}
	$words = explode(",", $select);
	$catword = '';
	$open = 0;
	$closed = 0;
	foreach ($words as $i => $word) {
		#if a function (with ",") is in the select list
		$open = $open+substr_count($word, "(");
		$closed = $closed+substr_count($word, ")");
		if ($open > $closed) {
			$catword .= $word.",";
			continue;
		} else {
			$word = $catword . $word;
			$catword = '';
		}
		$word = trim($word);
		$aspos = strripos($word, " as ");
		$dotpos = stripos($word, ".");
		if ($dotpos) {
			$name = substr($word, $dotpos+1);
		} else {
			$name = $word;
		}
		if ($aspos) {
			$select_cell[] = trim(substr($word, 0, $aspos));
			$select_name[] = trim(substr($word, $aspos+4));
		} else {
			$select_cell[] = $word;
			$select_name[] = $name;
		}
	}
	if ($wherepos) {
		$from = substr($line, $frompos, $wherepos-$frompos);
		$from = trim(substr($from, 5));

		$where = substr($line, $wherepos);
		$where = trim(substr($where, 6));
		
		if (stripos($where, "<") || stripos($where, ">")) {
			$words = explode(" ", $where);
			$subwhere = '';
			$where = '';
			$skip = false;
			foreach ($words as $i => $word) {
				$word = trim($word);
				if (strtolower($word) == "<") {
					$start_date[] = str_replace("timestamp", "date", $subwhere);
					$subwhere = '';
					$skip = true;
				} elseif (strtolower($word) == ">") {
					$end_date[] = str_replace("timestamp", "date", $subwhere);
					$subwhere = '';
					$skip = true;
				} elseif (strtolower($word) == "and" || strtolower($word) == "or") {
					if ($skip == false) {
						$where .= $subwhere." ".strtoupper($word);
					}
					$subwhere = '';
					$skip = false;
				} else {
					if ($skip == false) {
						$subwhere .= " ".$word;
					}
				}
			}
			$where .= " ".$subwhere;
			
			$open = substr_count($where, "(");
			$closed = substr_count($where, ")");
			while ($open > $closed) {
				$where = substr($where, 0, strrpos($where, "("));
				$open = substr_count($where, "(");
				$closed = substr_count($where, ")");
			}

			$where = trim($where);
			$where = trim($where, "ANDORandor");
			$where = trim($where);
			
		} elseif ($_SERVER['argv'][2] == "true") {
			$where = str_replace(" and end_date='infinity'", "", $where);;
		}
	} else {
		$from = substr($line, $frompos);
		$from = trim(substr($from, 5));
	}

	return array($select_name, $select_cell, $from, $where, $start_date, $end_date);
}

function buildquery($table, $select_name, $select_cell, $from, $where, $filename, $inputi, $startdate=false, $enddate=false) {
	if (!$startdate) {
		$sel_start = "''1900-01-01''";
	} else {
		$sel_start = "'|||quote_literal(cast(LEAST(GREATEST(";
		$sel_start .= trim(implode(", ", $startdate), "( ");
		$sel_start .= "), current_date) as text))|||'";
	}
	if (!$enddate) {
		$sel_end = "''infinity''";
	} else {
		$sel_end = "'|||quote_literal(REPLACE(LEAST(";
		$sel_end .= implode(", ", $enddate);
		$sel_end .= ", current_date), current_date, 'infinity'))|||'";
		$sel_end = str_replace("(, current_date)", "(current_date)", $sel_end);
	} 
	if ($_SERVER['argv'][2] == "true") {
		$sel_start = "'|||quote_literal(trim(start_date))|||'";
		$sel_end = "'|||quote_literal(trim(end_date))|||'";
	}
	
	$results = "\o ".str_replace(" ", "_", $filename." ".str_pad($inputi, 2, "0", PAD_LEFT)).".csv\n";
	$results .= "SELECT DISTINCT replace(replace(replace(replace(
'INSERT INTO ".$table." (start_date, end_date, ".implode(", ", $select_name).") VALUES (".$sel_start.", ".$sel_end.", '|||quote_literal(trim(".implode("))|||', '|||quote_literal(trim(", $select_cell)."))|||');', ',,', ', NULL,'), ',,', ', NULL,'), ',,', ', NULL,'), ',)', ', NULL)')
FROM ".$from."";
	if ($where) {
		$results .= " WHERE ".$where ."" ;
	}
	$results .= " ORDER BY replace ASC;\n";
	#$results .= "UPDATE $table SET end_date = 'infinity' WHERE end_date > now();\n";
	return $results;

}


?>
