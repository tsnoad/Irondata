<?php

/**
 * Converts the rules XML (taken from the database) into a set of PHP arrays.
 */
class xml2Array {

	var $arrOutput = array();
	var $resParser;
	var $strXmlData;

	function parse($strInputXML) {

		$this->resParser = xml_parser_create ();
		xml_set_object($this->resParser,$this);
		xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");

		xml_set_character_data_handler($this->resParser, "tagData");

		$this->strXmlData = xml_parse($this->resParser,$strInputXML );
		if(!$this->strXmlData) {
			die(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($this->resParser)),
			xml_get_current_line_number($this->resParser)));
		}
		        
		xml_parser_free($this->resParser);
		return $this->arrOutput;
	}

	function tagOpen($parser, $name, $attrs) {
		$tag=array("NAME"=>$name,"ATTRS"=>$attrs);
		array_push($this->arrOutput,$tag);
	}

	function tagData($parser, $tagData) {
		if(trim($tagData) || $tagData === '0') {
			if(isset($this->arrOutput[count($this->arrOutput)-1]['TAGDATA'])) {
				$this->arrOutput[count($this->arrOutput)-1]['TAGDATA'] .= $tagData;
			} else {
				$this->arrOutput[count($this->arrOutput)-1]['TAGDATA'] = $tagData;
			}
		}
	}
  
   function tagClosed($parser, $name) {
       $this->arrOutput[count($this->arrOutput)-2]['CHILDREN'][] = $this->arrOutput[count($this->arrOutput)-1];
       array_pop($this->arrOutput);
   }
}

/**
 * Extracts a specific ruleset from the set of all rulesets taken from the database.
 */
function getRules($rule_id) {
	$sql = "SELECT rules FROM report WHERE report_id = ".$rule_id;
	$rules = Database::runQuery($sql);
	$rulesXML = stripslashes(stripslashes($rules[0]['rules']));
	$objXML = new xml2Array();
	$rules = array();
	$rules = $objXML->parse($rulesXML);
	#$rules[0]['XML'] = $rulesXML;
	return $rules[0];
}

/**
 * safestring()
 *
 * Makes a string safe for XML use by replacing < and >.
 *
 */
 
 function safestring($string) {
 	$outstring = str_replace('<','&lt;', $string);
 	$outstring = str_replace('>','&gt;', $outstring);
 	return $outstring; 
 }

function putRules($xmlArray) {
	foreach ($xmlArray as $i => $array) {
		$xml .= "<".strtoupper($array['NAME']);
		if ($array['ATTRS']) {
			foreach ($array['ATTRS'] as $j => $attr) {
				$xml .= " ".strtoupper($j)."='".safestring($attr)."'";
			}
		}
		$xml .= ">";
		if ($array['CHILDREN']) {
			$xml .= "\n";
			$xml .= putRules($array['CHILDREN']);
		} 
		$xml .= safestring($array['TAGDATA'])."</".strtoupper($array['NAME']).">\n";
	}
	$xml = addslashes($xml);
	return $xml;
}

function addRule($report, $rules, $type, $published=false) {
	$query = "select * from report WHERE db_id='".$_SESSION['curDB']."' and report_id='".$report."'";
	$allreports = Database::runQuery($query);
	if (is_array($allreports) && $_REQUEST['submit'] == "Save") {
		$query = "update report set report_name='".$rules['ATTRS']['NAME']."', published='".$published."', rules='".putRules(array($rules))."', report_type='".$type."', db_id='".$_SESSION['curDB']."' where db_id='".$_SESSION['curDB']."' and report_id='".$report."'";
		Database::runQuery($query);
		Common_Functions::auditLog($type, "Updated Report", $rules['ATTRS']['NAME'], $report);
	} else {
		$query = Database::buildQuery("next", "report");
		$id = Database::runQuery($query);
		$report = $id[0]['nextval'];
		$query = "insert into report (report_id, report_name, report_type, rules, published, owner, db_id) values ('".$report."', '".$rules['ATTRS']['NAME']."', '".$type."', '".putRules(array($rules))."', '".$published."', '".$_SESSION['username']."', '".$_SESSION['curDB']."')";
		Database::runQuery($query);
		foreach ($_SESSION['groups'] as $i => $group) {
			$query = "INSERT INTO reportacl VALUES ('$group', '$report', 't');";
			Database::runQuery($query);
		}
		Common_Functions::auditLog($type, "Created Report", $rules['ATTRS']['NAME'], $report);
	}
	$allgroups = array();
	foreach ($_SESSION['groups'] as $i => $group) {
		$i = strtolower($i);
		$query = "SELECT * FROM groups WHERE lower(group_id)=lower('".$i."') order by group_id";
		$group = Database::runQuery($query);
		$allgroups[] = $group[0]['group_id'];
		if ($group[0]['admin'] == "t") {
			$query = Database::buildQuery("select", "report", NULL, NULL, " order by report_name");
			$_SESSION['reports'] = Common_Functions::reKeyArray(Database::runQuery($query), "report_id");
			return $report;
		} 
	}
	$query = "select DISTINCT f.*, x.access from report f, dbacl x where f.db_id=x.db_id and (lower(x.group_id)=lower('".implode("') or lower(x.group_id)=lower('", $allgroups)."')) and x.access='t' order by f.report_name";
	$_SESSION['reports'] = Common_Functions::reKeyArray(Database::runQuery($query), "report_id");
	return $report;
}

function deleteRule($report) {
	$name = Common_Functions::getReportName($report);
	$query = Database::buildQuery("delete", "report", array("report_id"=>$report));
	Database::runQuery($query);
	Common_Functions::auditLog("trend", "Deleted Report", $name, $report);
	unset($_SESSION['reports'][$report]);
	return "";
}

?>
