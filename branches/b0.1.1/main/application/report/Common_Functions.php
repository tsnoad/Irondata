<?php

/**
 * Common_Functions
 *
 * Defines a variety of functions, mostly of a presentational or utility nature,
 * which are used by more than one object within the system or are desirable
 * to have access to from multiple objects in the system.
 *
 * Most classes in the Report Generator will inherit from this class.
 *
 * @author Andrew White
 * @package Core
 * @version 1.2
 * @date 28-07-2006 
 */

class Common_Functions {
	/**
	 * authCheck() 
	 *
	 * Verifies that the user has permission to do something (edit a report, etc.)
	 *
	 * @params	string	checktype		The role to check - edit, delete, etc.
	 * @return	bool								Whether the user has permission.
	 *
	 */
	
	public function authCheck($type="reports", $specifier='Run Report Templates', $class = "access") {
		return ($_SESSION['admin']=='t' || $_SESSION[$type][$specifier][$class] == 't');
	}


  /**
	 * denied
	 * 
	 * Prints the permission denied message
	 *
	 * @return string	The message
	 */
	public function denied($action="") {
		if (!$action) {
			$action = "perform this action";
		}
		$main = "<p>Apologies<br/><br/>You currently do not have permission to ".$action.".</p>";
		$main .= "<p>If you feel that this is in error, please contact your systems administator.</p>";
		return $main;
	}
	
	/**
     * loads an optional report type (or other) module.
	 *
	 * Loads an optional module from the Modules sub-directory. Should
	 * probably do something intelligent if an error is encountered, but
	 * at the moment just returns -1.
	 *
	 *	@param	string	$moduleName		The name of the module to load.
	 * @return	object								The module object.
	 */
	public function loadModule($moduleName) {
		$filename = "./Modules/".$moduleName."/".$moduleName.".php";
		if(file_exists($filename)) {
			include_once($filename);
			$module = new $moduleName;
			return $module;
		} else {
				$this->addToLog(1, "Unable to load module " . $moduleName);
		}
	}
	
	/**
	 * adds arbitrary text to the log file
	 *
	 * @param	integer		$priority		The priority of the message
	 * @param	string		$message	The message itself
	 */
	
	public function addToLog($priority, $message, $type='Generic') {
		$conf = parse_ini_file("../conf.ini", true);
		$handle = fopen($conf['Dir']['Log'], "a");
		$message = "[".date("c")."] [".$type."] ".$message."\n";
		fwrite($handle, $message);
		fclose($handle);
	}
	
	/**
	 * sends an email message to some recipient using the phpmailer class 
	 * (an external library!)
	 *
	 * @param 	string	$to
	 * @param 	string	$subject
	 * @param 	string	$smtp
	 * @param 	string	$attachment
	 * @return	boolean
	 *
	 */
	
	function sendMail($to, $subject, $body, $smtp="no", $attachment="") {
		$conf = parse_ini_file("../conf.ini", true);
	
		$from = "nobody@engineersaustralia.org.au";
		$fromName = "Golf Report Generator";
		$body .= "\n\n---\nGolf Report Generator";
	
		if (is_file($conf['Dir']['FullPath'] . "/Common/class.phpmailer.php")) {
			require("class.phpmailer.php");
			
			$mail = new phpmailer();
			$mail->SetLanguage("en", $langDir);
			
			$mail->Mailer = "mail";
			
			if (!is_array($to)) {
				$to = array($to);
			}
			foreach ($to as $i=>$address) {
				$mail->AddAddress($address);
			}
			$mail->Subject = $subject;
			$mail->Body = $body;
	
			$mail->From = $from;
			$mail->FromName = $fromName;
	
			if ($attachment != "") {
				if (!is_array($attachment)) {
					$attachment = array($attachment);
				}
				foreach ($attachment as $i=>$attach) {
					$mail->AddAttachment($attach);
				}
			}
		
			$mail->Send();
		} else {
			$body = str_replace("\n.", "\n..", $body);
			$fromHeader = "From: ".$from;
			$success = mail($to, $subject, $body, $fromHeader);
		}
		
		return $success;
	}
	
	/**
	 * Builds a new array using a given value as the key
	 *
	 * @param array $oldArray               The array to parse
	 * @param string $key           The key to use
	 * @return array                The new assoc array
	 */
	function reKeyArray($oldArray, $key) {
		$newArray = array(array(), $key);
		if (is_array($oldArray)) {
			array_walk($oldArray, create_function('&$n, $key, &$newArray', '$newArray[0][$n[$newArray[1]]] = $n;'), &$newArray);
		}
		return $newArray[0];
	}
	
	/*
	 * This function will return true is the given string is formatted as a valid email address.
	 * It will not confirm that the email addres exists.
	 */
	function isemail($email) {
		   // regx to test for valid e-mail adres
		   $regex = '^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]{2,})+$';
		   if (eregi($regex, $email)) return true;
		   else return false;
	}
	

	function generate_options($list, $default_value="", $select_name="Select Table ...") {
		$option_return = "<option>".$select_name."</option>";
    asort($list);
		if (is_array($list)) {
			foreach($list as $i => $option) {
				$option_return .= "<option value='".$i."'" . ($i == $default_value ? " selected " : "") . ">".$option."</option>";
			}
		}
		return $option_return;
	}
	
	function generate_comparisons($default_value="") {
		$returnString = "";
		$options = array("is"=>"Is (Equals)", "lt" => "Less Than", "gt" => "Greater Than", "contains"=>"Contains", "ex"=>"Exists", "dne"=>"Does Not Exist");
		foreach($options as $optionvalue => $optiontext) {
			$returnString .= "<option value='".$optionvalue."'";
			if($default_value == $optionvalue) {
				$returnString .= " selected";
			}
			$returnString .= "'>".$optiontext."</option>";
		}
		return $returnString;
	}
	
	function generate_column_options($database=NULL, $table="", $default_value="") {
		$option_return = "<option value=''>Select Column...</option>";
		if($table) {
			$columns = Database::getKeys($table, $database);
      ksort($columns);
			if(is_array($columns) && count($columns) > 0) {
				foreach($columns as $i => $option) {
					$option_return .= "<option value='".$i."'" . ($i == $default_value ? " selected " : "") . ">".$i."</option>";
				}
		  }
		}
		return $option_return;
	}
	
	function generate_value_box($table, $column, $database=NULL, $prefix=NULL, $line=NULL, $id=NULL, $value=NULL) {
		if($table && $column) {
			global $conf;
			if (!$database) {
				$database = $conf['DB']['dbname'];
			}
		$conn_string = "host=".$conf['DB']['dbhost']." dbname=".$database." user=".$conf['DB']['dbuser']." password='".$conf['DB']['dbpass']."'";
			$db_conn = pg_connect($conn_string);
					
			$query = "SELECT c.select_values from ea_column c, ea_table t, db d where c.ea_table_id=t.ea_table_id and t.db_id=d.db_id and c.ea_column_name='" .$column . "' and t.ea_table_name='" . $table."' and d.psql_name='".$database."' ";
			$stringresult = Database::runQuery($query);
			$result = explode(",", $stringresult[0]['select_values']);
	
			if($stringresult[0]['select_values']) {
				$output = "<select name='".$prefix."_value__".$line."__".$id."' id='".$prefix."_value__".$line."__".$id."'>";
				foreach($result as $key => $row) {
					if($row == "") {
						$row = "(Null)";
					}
					$output.= '<option value="'.$row.'"';
					if($value == $row) {
						$output .= " selected";
					}
					
					$output .= '>' . $row . '</option>';
				}
				$output .= "</select>";
				return $output;
			} else {
				return "<input type='text' name='".$prefix."_value__".$line."__".$id."' id='".$prefix."_value__".$line."__".$id."' value='".$value."'>";   
			}
		} else {
			return "<input type='text' name='".$prefix."_value__".$line."__".$id."' id='".$prefix."_value__".$line."__".$id."'>"; 
		}
	}
	
  /* writeExcel creates an excel spreadsheet out of a two dimensional array
   *
   */
  function writeExcel($cells, $filename) {
    require_once "Spreadsheet/Excel/Writer.php";
    $xls =& new Spreadsheet_Excel_Writer($filename.".xls");
    $sheet =& $xls->addWorksheet('Report Export');
    $titleFormat =& $xls->addFormat();
		$titleFormat->setBold();
		$titleFormat->setFontFamily("Arial");
		$titleFormat->setSize("10");
		$titleFormat->setVAlign("top");
		$titleFormat->setHAlign("left");
		$titleFormat->setBorder("1");
		$bodyFormat->setTextWrap();
		$bodyFormat->setFontFamily("Arial");
		$bodyFormat->setSize("10");
		foreach ($cells as $i => $row) {
			foreach ($row as $j => $cell) {
        if ($i == 0) {
          $sheet->write($i, $j, $cell, $titleFormat);
  			}
			}
		}
	}
}

	
?>
