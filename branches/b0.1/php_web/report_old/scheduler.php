<?php

/**
 * The scheduler interface.
 * This file contains the code to generate and enact scheduling commands,
 * allowing users to run reports automatically at arbitrary intervals 
 * (within a pre-defined number of options, anyway).
 *
 * @author Andy White
 * @created 31/10/2007
 */
 
 require_once("./Common_Functions.php");
class Scheduler extends Common_Functions {
	
	function __construct() {
		
	}
	
	/**
	 * Shovels out the javascript needed by the scheduler.
	 */
	function schedulerJS() {
		$output = "<script type='text/javascript'>
					function frequencyChanged(report_id) {
						var obj = $('report['+report_id+'][frequency]');
						var target = $('specifics_field_'+report_id);
						var value = obj.options[obj.selectedIndex].value;
						var newText = '';
						if(value == 'none') {
							newText = '';
						} else if (value == 'daily') {
							
						} else if (value == 'weekly') {
							newText = 'Day:  <select style=\'width: 100px;\' id=\'report['+report_id+'][weekday]\' name=\'report['+report_id+'][weekday]\'> \
								<option value=\'Monday\'>Monday</option> \
								<option value=\'Tuesday\'>Tuesday</option> \
								<option value=\'Wednesday\'>Wednesday</option> \
								<option value=\'Thursday\'>Thursday</option> \
								<option value=\'Friday\'>Friday</option> \
								<option value=\'Saturday\'>Saturday</option> \
								<option value=\'Sunday\'>Sunday</option> \
								</select>';
						} else if (value == 'monthly') {
							newText = 'Day:  <select style=\'width: 50px;\' name=\'report['+report_id+'][day]\' id=\'report['+report_id+'][day]\'>";
							for ($i = 1; $i <= 31; $i++) {
								$output .= "<option value=\'".$i."\' selected>".sprintf("%'02u", $i)."</option>";
							}	
			$output .= "			</select>';
						} else if (value == 'yearly') {
							newText = 'Date: <select style=\'width: 100px;\' onchange=\'monthChanged('+report_id+')\' name=\'report['+report_id+'][month]\' id=\'report['+report_id+'][month]\'> \
								<option value=\'1\'>January</option> \
								<option value=\'2\'>February</option> \
								<option value=\'3\'>March</option> \
								<option value=\'4\'>April</option> \
								<option value=\'5\'>May</option> \
								<option value=\'6\'>June</option> \
								<option value=\'7\'>July</option> \
								<option value=\'8\'>August</option> \
								<option value=\'9\'>September</option> \
								<option value=\'10\'>October</option> \
								<option value=\'11\'>November</option> \
								<option value=\'12\'>December</option> \
								</select> \
								<span id=\'days_go_here_'+report_id+'\'></span>'";
									
			$output .= "		}
						
						target.innerHTML = newText;
						
						if(value == 'yearly') {
							monthChanged(report_id);
						}
					}
					
				    function monthChanged(report_id) {
				    	var obj = $('report['+report_id+'][month]');
				    	var month = obj.options[obj.selectedIndex].value;
				    	month = month - 1;
				    	
				    	var days = Date._MD[month];
					var text = '<select style=\'width: 50px;\' name=\'report['+report_id+'][day]\' id=\'report['+report_id+'][day]\'>';
				    	var i = 1;
				    	for(i = 1; i <= days; i++) {
				    		text += '<option value=\''+i+'\'>'+i+'</option>';
				   	}
				   	
				   	var target = $('days_go_here_'+report_id);
				   	target.innerHTML = text;
				    	
				    }
					
				    </script>";
				    
		return $output;
	}
	
	/**
	 * Generates the HTML for the drop-select that specifies how often a report should be automagically run.
	 * Neatly handles the 'default' value (so when you load things out of the database, the correct values
	 * are pre-filled).
	 *
	 * TODO: This or a related function also needs to output the 'specifics' field (day of week / day of month / month and day)
	 */	
	function generateFrequencySelect($report_id, $default = "") {
		$retval = "<select name='report[".$report_id."][frequency]' id='report[".$report_id."][frequency]' onchange=\"frequencyChanged('".$report_id."');\">";
		$options = array('none', 'daily', 'weekly', 'monthly', 'yearly');
		foreach($options as $option) {
			$retval .= "<option value='".$option."' ";
			if($option == $default) {
				$retval .= "selected";
			}
			$retval .= ">".$option."</option>";
		}

	     $retval .= "</select>";
	     return $retval;
	}
	
	/**
	 * Generates the list of reports, plus drop down options for specifying how often they get run.
	 */
	public function listReports() {
		$reportsByDB = array();

		// Grab any pre-set values from the database
		$sql = "SELECT * FROM reportSchedule";
		$schedule = Database::runQuery($sql);
		$schedule = $this->reKeyArray($schedule, 'report_id');
		
		// The keys we're interested in from the $_SESSION['reports'] stack.
		$sessionkeys = array('report_id', 'report_name', 'report_type');
		$schedulekeys = array('frequency', 'weekday', 'day', 'month');
		
		// Reorganise $_SESSION['reports'] by database ID, then report type, then report ID (sorting FTW)
		foreach($_SESSION['reports'] as $report_id => $report_vals) {
			foreach($sessionkeys as $keyname) {
				$reportsByDB[$report_vals['db_id']][$report_vals['report_type']][$report_vals['report_id']][$keyname] = $report_vals[$keyname];
			}
			
			// If schedule data exists for this report, we take it
			if(isset($schedule[$report_id])) {
				foreach($schedulekeys as $keyname) {
					$reportsByDB[$report_vals['db_id']][$report_vals['report_type']][$report_vals['report_id']][$keyname] = $schedule[$report_id][$keyname];
				}
			}
			
		}

		$output = $this->schedulerJS(); // Generate javascript
		$output .= "<form action='scheduler.php' method='post'>";
		$output .= "<input type='hidden' name='php_session_id' value='".session_id()."' />\n";
		
		foreach($reportsByDB as $db_id => $report_types) {
			// Iterate over the list of databases
			
			$output .= "<fieldset>
					<legend>".$this->getDBName($db_id)."</legend>
					<div id='db_".$db_id."'>";
			
			foreach($report_types as $typename => $reports) {
				// Then, over the list of report types
				$output .= "<div><h5>".$typename."</h5>";
				$output .= "<table>
						<thead>
							<td style='width: 450px;'>Report Name</td>
							<td>To be run..</td>
							<td>Specifics:</td>
						</thead>
						";
				
				foreach($reports as $report_id => $values) {
					// Finally, we concern ourselves with the 'real' values!
					$output .= "<tr>";
					$output .= "<td>".$values['report_name']."</td>";
					$output .= "<td>".$this->generateFrequencySelect($report_id, $values['frequency'])."</td>
						     <td id='specifics_field_".$report_id."'>".$this->generateSpecificsField($report_id, $values)."</td>";
					$output .= "</tr>";
				}
				
				$output .= "</table></div>";
			}
			
			$output .= "</fieldset>";
		}
	
		if(!$_SESSION["reports"])
			$output .= "</form>";
		else	
			$output .= "<input type='submit' name='submit' value='Save Schedule' /></form>";

		return $output;
		
	}
	
	/**
	 * Generates drop-down fields that specify day/month/date for certain frequencies
	 */
	function generateSpecificsField($report_id, $values) {
		if($values['frequency'] == "weekly") {
			$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
			$retval = "Day:  <select style='width: 100px;' id='report[".$report_id."][weekday]' name='report[".$report_id."][weekday]'> ";
			foreach($days as $day) {
				$retval .= "<option value='".$day."' ";
				if($values['weekday'] == $day) {
					$retval .= "selected";
				}
				$retval .= ">".$day."</option>";
			}
				
		 	$retval .= "</select>";
		 	return $retval;
		} else if($values['frequency'] == "monthly") {		
			$retval = "Day:  <select style='width: 50px;' name='report[".$report_id."][day]' id='report[".$report_id."][day]'>";
			$i = 0;
			for ($i = 1; $i <= 31; $i++) {
				$retval .= "<option value='".$i."' ";
				if($values['day'] == $i) {
					$retval .= "selected";
				}
				
				$retval .= ">".sprintf("%'02u", $i)."</option>";
			}	
			$retval .= "</select>";
			return $retval;

		} else if($values['frequency'] == "yearly") {
			$retval = "Date: <select style='width: 100px;' onchange='monthChanged(".$report_id.");' name='report[".$report_id."][month]' id='report[".$report_id."][month]'>";
			
			$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
			foreach($months as $num => $month) {
				$retval .= "<option value='".$num."' ";
				if($values['month'] == $num) {
					$retval .= "selected";
				}
				$retval .= ">".$month."</option>";
			}
			$retval .= "</select>";
			$retval .= "<span id=\'days_go_here_'+report_id+'\'><select style='width: 50px;' name='report[".$report_id."][day]' id='report[".$report_id."][day]'>";
			
			$i = 0;
			for ($i = 1; $i <= 31; $i++) {
				$retval .= "<option value='".$i."' ";
				if($values['day'] == $i) {
					$retval .= "selected";
				}
				
				$retval .= ">".sprintf("%'02u", $i)."</option>";
			}	
			$retval .= "</select></span>";
			return $retval;
		}
	}
	
	public function submitted() {
		$sql = "SELECT * FROM reportSchedule ORDER BY report_id";
		$schedule = Database::runQuery($sql);
		$schedule = $this->reKeyArray($schedule, 'report_id');
		$output = "SQL: ";
	
		// See if anything has changed. If it has, update the table or insert a new row.
		// If it hasn't, skip on ahead.
		foreach($_POST['report'] as $report_id => $values) {
			$sql = "";
			if(isset($schedule[$report_id])) {
				if($values['frequency'] == "yearly") {
					if($values['month'] == $schedule[$report_id]['month'] && $values['day'] == $schedule[$report_id]['day']) {
						continue;
					}
				} 
				
				if($values['frequency'] == "monthly") {
					if($values['day'] == $schedule[$report_id]['day']) {
						continue;
					}
				} 
				
				if($values['frequency'] == "weekly") {
					if($values['weekday'] == $schedule[$report_id]['weekday']) {
						continue;
					}
				}
				
				if($values['frequency'] == "none") {
					// We can delete the row, as we don't run a report for this record
					// TODO: Send an email to the user who modified this record last!
					$sql = "DELETE FROM reportSchedule WHERE report_id = ".$report_id;
				} else {
					$sql = "UPDATE reportSchedule SET frequency = '".$values['frequency']."', weekday = '".$values['weekday']."', day = '".$values['day']."', month = '".$values['month']."' WHERE report_id = '".$report_id."'";	
				}
				
			} else {
				if($values['frequency'] == "none") {
					continue;
				}
						
				$sql = "INSERT INTO reportSchedule (report_id, frequency, weekday, day, month) VALUES ('".$report_id."', '".$values['frequency']."', '".$values['weekday']."', '".$values['day']."', '".$values['month']."')";		
			}
			
			Database::runQuery($sql);					
			$output .= $sql;	
		}
		
		return $output;
	}

}


$conf = parse_ini_file("../conf.ini", true);
include($conf['Dir']['FullPath']."/common.php");
if(isset($_POST['php_session_id'])) { session_id($_POST['php_session_id']); }
session_start();

if(Database::logon() == true) {
	// Don't generate content, unless we're already logged in
	$scheduler = new Scheduler();

	$maintitle = "Scheduling";
	$menutitle = "Administration Menu";
	$menustyle = "None";

	$mainicons = false;
	/*if($_POST['submit'] == "Save Schedule") {*/
	if(isset($_POST['php_session_id'])) {
		$main .= "<!-- " . $scheduler->submitted() . " -->";
	} 
	
	$main .= $scheduler->listReports();

	ob_start();echo "<!-- post: ";print_r($_POST);echo " -->";$main .= ob_get_contents(); ob_end_clean();	
}


include($conf['Dir']['FullPath']."/html.inc");

?>
