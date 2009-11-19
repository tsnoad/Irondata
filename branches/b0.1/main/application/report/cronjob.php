#!/usr/bin/php
<?php

include_once("generator.php");
include_once("common.php");

if (count($argv) != 3) {
	echo "Usage: ".basename($argv[0])." [db name] [report name]\n";
} else {
	$name = $argv[2];

	$db = $argv[1];
	
	$res = getRules($name);
	
	if ($res) {
		$type = $res['ATTRS']['REPORT_TYPE'];
		$_GET['query'] = $res['ATTRS']['NAME'];
		$_SESSION['curDB_psql'] = $db;
		if(!($type == 'trend' || $type == 'table' | $type == 'list')) {
			echo "Error: Invalid report type specified.";
			end;
		}
		$module = Common_Functions::loadModule($type);
		$module->runReport($res);
		switch ($type) {
			case 'trend':
				$_GET['file_name']="graph.jpeg";
				break;
			case 'table':
			case 'list':
				$_GET['file_name']=$res['ATTRS']['NAME'].".csv";
				break;
			default:
				break;
		}
		//$module->saveReport();
	} else {
		echo "Error: Report rules not found!\n";
	}
}
?>
