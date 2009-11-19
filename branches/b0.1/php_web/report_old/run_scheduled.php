<?php

/**
 * Run schedule reports, as listed in the auth database.
 */

$conf = parse_ini_file("../conf.ini", true);
 
require_once("./Common_Functions.php");
require_once("./rules.php");
require_once("./database.php");
require_once "ezc/Base/base.php"; 

function __autoload($class_name) {
	if (is_file($class_name . '.php')) {
		require_once $class_name . '.php';
	}
	if (strpos($class_name, "ezc") === 0) {
		ezcBase::autoload( $class_name );
	}
}

$sql = "SELECT * FROM reportschedule";
$results = Database::runQuery($sql);

$maxday = array(0,31,28,31,30,31,30,31,31,30,31,30,31);

foreach($results as $num => $result) {
	// 1. If daily run, run it
	// 2. If weekly run, run if days match
	// 3. If monthly run, run if date matches or current_date == month_length and report_date > month_length (e.g. scheduled for 31st, but it's Feb 28th)
	// 4. If yearly run, run if month and date match (same rules as above apply)
	
	$todayname = date('l');
	$todayday = date('j');
	$todaymonth = date('n');

	$frequency = $result['frequency'];	
	$weekday = $result['weekday'];
	$month = $result['month'];
	$day = $result['day'];
	if($month != '' and $day > $maxday[$month])
	$day = $maxday[$month];

	#echo "Report id: ".$result['report_id']."\ntoday: $todayname $todayday $todaymonth 2008\nfrequency: $frequency\ndate: $weekday $day $month 2008\n";

	if
	($frequency == 'daily' or 
	($frequency == 'weekly' and $todayname == $weekday) or 
	($frequency == 'monthly' and $day == $todayday) or 
	($frequency == 'yearly' and $month == $todaymonth and $day == $todayday)
	)
	{	
		$rules = getRules($result['report_id']);		
		$_GET['saved_report']=$result['report_id'];
			
		if ($rules) {
			$type = $rules['ATTRS']['REPORT_TYPE'];
			if(!($type == 'trend' || $type == 'table' || $type == 'listing')) {
				echo "Error: Invalid report type specified.";
				end;
			}
			
			$module = Common_Functions::loadModule($type);
	
			$_GET["report_from"]=$result["report_id"];
			
			$dbquery = "SELECT psql_name FROM db inner join report on db.db_id = report.db_id where report.report_id = ".$result["report_id"];
			$dbresult = current(Database::runQuery($dbquery));
			$_SESSION["curDB_psql"]=$dbresult["psql_name"];		
			
			echo $module->runReport($rules); 
	
			$suffix = "";
			if ($autovalues) {
				$suffix .= " (".implode(", ", $autovalues).")";
			}
			$suffix .= " - ".date("Y-m-d");
			
			$module->saveReport($result['report_id'], $suffix, $rules['ATTRS']['NAME']);
		} else {
			echo "Error: Report rules not found!\n";
		}
	}
}	
?>
