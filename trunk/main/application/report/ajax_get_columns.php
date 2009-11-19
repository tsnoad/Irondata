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
	
	$j = count($names);
	for ($i=0;$i<$j;$i++) {
		if ($names[$i] != "") {
			echo '<column>' . $names[$i] . '</column>
	';
		}
	}
}

echo '</column_response>';
?>
