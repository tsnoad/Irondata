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
	 * @return	bool				Whether the user has permission.
	 *
	 */
	
	public function authCheck($type="functions", $specifier='Run Report Templates', $class = "access") {
		$auth = ($_SESSION['admin']=='t' || $_SESSION[$type][$specifier][$class] == 't');
		###Rewrite. Hack###
		if ($auth == false && $type=="reports") {
			foreach ($_SESSION[$type] as $i => $name) {
				if ($name['report_id'] == $specifier) {
					return $name[$class];
				}
			}
		}
		return $auth;
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
	 * invalid
	 * 
	 * Prints the invalid report message
	 *
	 * @return string	The message
	 */
	public function invalid($reportname="", $location="") {
		$main = "<p>Apologies<br/><br/>The report <strong>".$reportname."</strong> failed to run or returned no results. Please review the report template and resolve any issues.</p>";
		if ($location) {
			$main .= "<p>It appears that the <strong>".$location."</strong> query failed.</p>";
		}
		$main .= "<p>If you feel that this message is in error, please contact your systems administator.</p>";
		return $main;
	}
	
	/**
         * loads an optional report type (or other) module.
	 *
	 * Loads an optional module from the Modules sub-directory. Should
	 * probably do something intelligent if an error is encountered, but
	 * at the moment just returns -1.
	 *
	 * @param	string	$moduleName		The name of the module to load.
	 * @return	object				The module object.
	 */
	public function loadModule($moduleName) {
		$filename = "./Modules/".$moduleName."/".$moduleName.".php";
		if(file_exists($filename)) {
			include_once($filename);
			$module = new $moduleName;
			return $module;
		} else {
			Common_Functions::addToLog(1, "Unable to load module " . $moduleName . ".");
			return $main;
		}
	}
	
	/**
	 * adds arbitrary text to the log file
	 *
	 * @param	integer		$priority	The priority of the message
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
	 * creates an entry in the audit log, recording the user who
	 * performed an action, which module the action related to,
	 * and which report/item within that module the action was on.
	 *
	 */
	 public function auditLog($module="Core", $action="", $report_name="n/a", $report_id=-1) {
	 	$sql = "INSERT INTO auditLog (username, module,	action, subject_name, time, subject_id) VALUES ('".$_SESSION['username']."', '$module', '$action', '$report_name', '".date("Y-m-d H:i:s", time())."', $report_id)";
	 	Database::runQuery($sql);
	 	
	 	return true;
	 }
	
	/**
	 * gets the name of a report, based on an ID (numeric)
	 * (only valid when used in the context of a report module)
	 */
	 public function getReportName($report_id) {
	 	$sql = "SELECT report_name FROM report WHERE report_id = '".$report_id."'";
	 	$result = "";
	 	if(($result = Database::runQuery($sql)) == false) {
	 		return "Unknown";
	 	}
	 	
	 	return $result[0]['report_name'];
	 	
	 }
	 
	 /**
	  * given the id of a database, returns its name
	  */
	 public function getDBName($db_id) {
	 	$sql = "SELECT db_name FROM db WHERE db_id  = '".$db_id."'";
	 	$result = "";
	 	if(($result = Database::runQuery($sql)) == false) {
	 		return "Unknown";
	 	}
	 	
	 	return $result[0]['db_name'];
	 }
	 
	
	/**
	 * returns the date/time of the last update to the report template
	 */
	function getLastUpdate($module, $query) {
		$sql = "SELECT * FROM auditLog WHERE module = '".$module."' AND subject_name = '".$query."' AND action = 'Updated Report' ORDER BY time DESC LIMIT 1";
		return Database::runQuery($sql);		
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
	

	function generate_options($list, $default_value="", $select_name="Select Table ...", $sort=true) {
		$option_return = "<option>".$select_name."</option>";
		if ($sort) {
			asort($list);
		}
		if (is_array($list)) {
			foreach($list as $i => $option) {
				$option_return .= "<option value='".$i."'" . ($i == $default_value ? " selected " : "") . ">".$option."</option>";
			}
		}
		return $option_return;
	}
	
	function generate_comparisons($default_value="") {
		$returnString = "";
		$options = array("is"=>"Is (Equals)", "isnot"=>"Excludes", "lt" => "Less Than", "gt" => "Greater Than", "lte" => "Less Than or Equal", "gte" => "Greater Than or Equal", "contains"=>"Contains", "containsnot"=>"Does Not Contain", "ex"=>"Exists", "dne"=>"Does Not Exist");
		foreach($options as $optionvalue => $optiontext) {
			$returnString .= "<option value='".$optionvalue."'";
			if($default_value == $optionvalue) {
				$returnString .= " selected";
			}
			$returnString .= "'>".$optiontext."</option>";
		}
		return $returnString;
	}
	
	function generate_aggregates($default_value="count once") {
		#Default to count once
		$returnString = "";
		$options = array('count once'=>'Count Once', 'count'=>'Count All', 'avg'=>'Average', 'max'=>'Maximum', 'min'=>'Minimum', 'sum'=>'Sum', 'percentage'=>'Percentage (by Count Once)');
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
		$xls =& new Spreadsheet_Excel_Writer($filename);
		$titleFormat =& $xls->addFormat();
		$titleFormat->setBold();
		$titleFormat->setFontFamily("Arial");
		$titleFormat->setSize("10");
		$titleFormat->setVAlign("top");
		$titleFormat->setHAlign("left");
		$titleFormat->setBorder("1");
		$bodyFormat =& $xls->addFormat();
		$bodyFormat->setFontFamily("Arial");
		$bodyFormat->setSize("10");
		foreach ($cells as $i => $sheet) {
			$j=0;
			$sheetname = 'sheet'.$i;
			if ($i = '') {
				$i = 'sheet';
			}
			$report_name = str_replace(array(":", "[", "]", "/", "\\", "*", "'", "?"), '', $i);
			$report_name=substr($report_name, 0, 30);
			$$sheetname =& $xls->addWorksheet($report_name);
			foreach ($sheet as $jkey => $row) {
				$k=0;
				foreach ($row as $kkey => $cell) {
					if ($j == 0) {
						$$sheetname->write($j, $k+1, $kkey, $titleFormat);
					}
					if ($k == 0) {
						$$sheetname->write($j+1, $k, $jkey, $titleFormat);
					}
					$$sheetname->write($j+1, $k+1, $cell, $bodyFormat);
					$k++;
				}
				$j++;
			}
		}
		$xls->close();
	}

	/*$pdf = Common_Functions::makePdf($main, $report_name.$suffix, array(), $autovalues); /* The ./tmp/ directory is automatically added, as is the .pdf extension. */
	function makePDF($data, $filename, $options=array(), $auto=array(), $align="") {
		global $conf;
		
		$options['run'] = date("Y-m-d");
		$options['run by'] = $_SESSION['username'];
		$options['pages'] = "##PAGES##";
		#Get the current database name
		$query = Database::buildQuery("select", "db", array("db_id"=>$_SESSION['curDB']));
		$dbs = Database::runQuery($query);
		$options['datamart'] = $dbs[0]["db_name"];

		/* Calculate landscape/portrait */
		if ($align == "") {
			$numtd = substr_count($data, "<td", 0, strpos($data, "</tr"));
			if ($numtd > 10) {
				$landscape = true;
				$pixels = 1024;
			} else {
				$landscape = false;
				$pixels = 724;
			}
		} elseif ($align == "landscape") {
			$landscape = true;
			$pixels = 1024;
		} elseif ($align == "portrait") {
			$landscape = false;
			$pixels = 724;
		}
		include("indexpdf.php");
		set_include_path(get_include_path() . PATH_SEPARATOR . "./html2ps/");
		require_once('config.inc.php');
		require_once('pipeline.factory.class.php');
		require_once('fetcher.url.class.php');
		parse_config_file(HTML2PS_DIR.'html2ps.config');

		global $g_config;
		$g_config = array(
		                  'cssmedia'     => 'screen',
		                  'renderimages' => true,
		                  'renderforms'  => false,
		                  'renderlinks'  => false,
		                  'mode'         => 'html',
		                  'debugbox'     => false,
				  'smartpagebreak' => 1,
		                  'draw_page_border' => false,
				  'html2xhtml' => true,
				  'scalepoints' => true,
				  'renderfields' => true
		                  );

		$media = Media::predefined('A4');
		$media->set_landscape($landscape);
		$media->set_margins(array('left'   => 10,
		                          'right'  => 10,
		                          'top'    => 10,
		                          'bottom' => 10));
		$media->set_pixels($pixels);

		global $g_px_scale;
		$g_px_scale = mm2pt($media->width() - $media->margins['left'] - $media->margins['right']) / $media->pixels;
		global $g_pt_scale;
		$g_pt_scale = $g_px_scale * 1.43; 

		file_put_contents("tmp/".$filename.".html", $indexpdf);

		$pipeline = new Pipeline;
		$pipeline->configure($g_config);
		$pipeline->fetchers[] = new MyFetcherMemory($indexpdf, 'http://localhost/'.$conf['Dir']['WebPath'].'/');
		#$pipeline->fetchers[] = new FetcherURL;
/* the following data filter breaks the process() function for report 551 (string is passed as user input) 
apparently it converts tags to lower case
*/
		$pipeline->data_filters[] = new DataFilterHTML2XHTML;
		$pipeline->parser = new ParserXHTML;
		$pipeline->layout_engine = new LayoutEngineDefault;
		$pipeline->output_driver = new OutputDriverFPDF($media);
		$pipeline->pre_tree_filters[] = new PreTreeFilterHTML2PSFields();
		$pipeline->destination = new DestinationFile($filename, 'File saved as: <a href="%link%">%name%</a>');

		$pipeline->process('', $media); 
		#$pipeline->process('http://192.168.25.232/'.$conf['Dir']['WebPath'].'/tmp/'.str_replace(" ", "%20", $filename).'.html', $media); 
		$badfilename = preg_replace("/[^a-zA-Z0-9\-]/", "_", $filename);
		copy('tmp/'.$badfilename.'.pdf', 'tmp/'.$filename.'.pdf');
		unlink('tmp/'.$badfilename.'.pdf');
	}
}

/* Setup for the html2ps functions*/
set_include_path(get_include_path() . PATH_SEPARATOR . "./html2ps/");
require_once('config.inc.php');
require_once('pipeline.factory.class.php');
require_once('fetcher.url.class.php');
parse_config_file(HTML2PS_DIR.'html2ps.config');

class MyFetcherMemory extends Fetcher {
	var $base_path;
	var $content;

	function MyFetcherMemory($content, $base_path) {
		$this->content   = $content;
		$this->base_path = $base_path;
	}

	function get_data($url) {
		if (!$url) {
			return new FetchedDataURL($this->content, array(), "");
		} else {
			// remove the "file:///" protocol
			if (substr($url,0,8)=='file:///') {
				$url=substr($url,8);
				// remove the additional '/' that is currently inserted by utils_url.php
				if (PHP_OS == "WINNT") $url=substr($url,1);
			}
			return new FetchedDataURL(@file_get_contents($url), array(), "");
		}
	}

	function get_base_url() {
		return 'file:///'.$this->base_path.'/dummy.html';
	}
}

	
?>
