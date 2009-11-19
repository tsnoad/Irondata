<?php

session_start();

header('Content-Type: text/xml'); 
include('common.php');

import_request_variables('gp','p_');

$columns = Database::getKeys($p_table, $p_db);

echo '<?xml version="1.0" standalone="yes" ?>
        <column_response>
        <element>' . $p_id . '</element>
        ';
if (is_array($columns)) {
	$names = array_keys($columns);
	$prefix = array();

	foreach ($names as $i => $name) {
		if (strpos($name, "_id")) {
			$prefix[] = substr($name, 0, -3);
		}
	}
	
	$j = count($names);
	for ($i=0;$i<$j;$i++) {
		if ($names[$i] != "" && strpos($names[$i], "sys_") !== 0 && !in_array($names[$i]."_date", $prefix)) {
			echo '<column>' . $names[$i] . '</column>
	';
		}
	}
}

echo '</column_response>';
?>
