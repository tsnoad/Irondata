<?php
session_start();

header('Content-Type: text/xml'); 
include('common.php');

/*
 TODO 
 THIS SCRIPT DOES NOT CHECK USER PERMISSIONS - ANYONE CAN ACCESS IT WITHOUT LOGGING IN
 This is not a huge security hole as the most an attacker can gain is the select values
 of fields. This is, however, still information leakage and it's not difficult to
 imagine how the information thus retrieved could potentially be sensitive.
*/

import_request_variables('gp','p_');

global $db_conn;
$query = "SELECT c.select_values from ea_column c, ea_table t, db d where c.ea_table_id=t.ea_table_id and t.db_id=d.db_id and c.ea_column_name='" .$p_column . "' and t.ea_table_name='" . $p_table."' and d.psql_name='".$p_db."' ";
$stringresult = Database::runQuery($query);
$result = explode(",", $stringresult[0]['select_values']);


echo '<?xml version="1.0" standalone="yes" ?>
    <values_response>
    <table>' . $p_table . '</table>
    <column>' . $p_column . '</column>
    <value_field>' . $p_id . '</value_field>
    ';

if($stringresult[0]['select_values']) {
    echo '<value_type>select</value_type>
         ';
    foreach($result as $key => $row) {
        if($row == "") {
            echo '<value_option>(Null)</value_option>
                 ';
        } else {
            // Don't send null values. 
            echo '<value_option>' . $row . '</value_option>
                 ';
        }
    }
} else {
    echo '<value_type>input</value_type>
         ';
}

echo  '</values_response>
      ';

?>
