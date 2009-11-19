<?php

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
       if(trim($tagData)) {
           if(isset($this->arrOutput[count($this->arrOutput)-1]['TAGDATA'])) {
               $this->arrOutput[count($this->arrOutput)-1]['TAGDATA'] .= $tagData;
           }
           else {
               $this->arrOutput[count($this->arrOutput)-1]['TAGDATA'] = $tagData;
           }
       }
   }
  
   function tagClosed($parser, $name) {
       $this->arrOutput[count($this->arrOutput)-2]['CHILDREN'][] = $this->arrOutput[count($this->arrOutput)-1];
       array_pop($this->arrOutput);
   }
}

function getRules($ruleName) {
	$query = "select * from report WHERE db_id='".$_SESSION['curDB']."' and report_name='".$ruleName."'";
	$reports = Database::runQuery($query);
	$rulesXML = stripslashes(stripslashes($reports[0]['rules']));
	$objXML = new xml2Array();
	$rules = array();
	$rules = $objXML->parse($rulesXML);
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

function addRule($report, $rules, $type) {
	$query = "select * from report WHERE db_id='".$_SESSION['curDB']."' and report_name='".$report."'";
	$allreports = Database::runQuery($query);
	if (is_array($allreports)) {
		$query = "update report set rules='".putRules(array($rules))."', report_type='".$type."', db_id='".$_SESSION['curDB']."' where db_id='".$_SESSION['curDB']."' and report_name='".$report."'";
		Database::runQuery($query);
		return $rules;
	} else {
		$query = "insert into report (report_name, report_type, rules, db_id) values ('".$report."', '".$type."', '".putRules(array($rules))."', '".$_SESSION['curDB']."')";
		Database::runQuery($query);
	}
	$query = Database::buildQuery("select", "report", NULL, NULL, " order by report_name");
	$_SESSION['reports'] = Database::runQuery($query);
}

function deleteRule($report) {
	$query = Database::buildQuery("delete", "report", array("report_name"=>$report));
	Database::runQuery($query);
	$query = Database::buildQuery("select", "report", NULL, NULL, " order by report_name");
	$_SESSION['reports'] = Database::runQuery($query);
}

?>
