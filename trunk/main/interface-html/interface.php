<?php
$depth = array();
$html = "";
$previous = "";
$loc = "";
$form = "";
$module = "";

function startElement($parser, $name, $attrs) {
	global $html;
	global $previous ;
	global $loc;
	global $form;
	global $module;
	
	#echo $name." ";
	switch ($name) {
	case "MODULE":
		$loc = $attrs["REGION"];
		$module = $attrs["NAME"];
		if ($loc == "control") {
			// Do nothing
		} elseif ($loc == "menu") {
			$html[$loc] .= "<div dojoType='AccordionContainer' id='".$attrs['ID']."'>";
		} else {
			$html[$loc] .= "<div id='".$attrs['ID']."'>";
		} 
		if ($attrs['TITLE'] != '') {
			$html[$loc] .= "<h1>".$attrs['TITLE']."</h1>";
			$html[$loc] .= "<div class='hr'></div>";
		}
		break;
	case "IMAGE":
		switch ($attrs["TYPE"]) {
			case "welcome":
			$html[$loc] .= "<img src='images/id_welcome.png' alt='".$attrs["TYPE"]."' title='".$attrs["TYPE"]."' />";
			$html[$loc] .= "<div class='hr'></div>";
			break;
		}
	case "PARA":
		$html[$loc] .= "<p>";
		break;
	case "HEADER":
		$html[$loc] .= "<h2>";
		break;
	case "TEXT":
		switch ($attrs["STYLE"]) {
			case "bold":
				$style = "font-weight: bold;";
				break;
			case "emphasis":
				$style = "font-weight: italics;";
				break;
		}
		$html[$loc] .= "<span style='".$style."'>";
		break;
	case "FORM":
		$form = $attrs["NAME"];
		$html[$loc] .= "<div id ='".$form."'>";
		$html[$loc] .= "<form method='post' id='".$form."_form' name='".$form."_form'>";
		$html[$loc] .= "<input type='hidden' id='module' name='module' value='".$module."' />";
		$html[$loc] .= "<input type='hidden' id='name' name='name' value='".$form."' />";
		break;
	case "INPUT":
		if ($previous == "INPUT") {
			$html[$loc] .= "<br/>";
		}
		switch($attrs["TYPE"]) {
		case "text":
			$html[$loc] .= "<label for='".$attrs["ID"]."'>".$attrs["LABEL"].": </label><input type='text' id='".$attrs["ID"]."' name='".$attrs["ID"]."' value='".$attrs["VALUE"]."' />";
			break;
		case "hidden":
			$html[$loc] .= "<input type='hidden' id='".$attrs["ID"]."' name='".$attrs["ID"]."' value='".$attrs["VALUE"]."' />";
			break;
		case "password":
			$html[$loc] .= "<label for='".$attrs["ID"]."'>".$attrs["LABEL"].": </label><input type='password' id='".$attrs["ID"]."' name='".$attrs["ID"]."' value='".$attrs["VALUE"]."' />";
			break;
		case "submit":
			$html[$loc] .= "<div class='submit' onclick='javascript:submit_form(\"".$form."_form\")'><span>".strtoupper($attrs["LABEL"])."</span></div>";
			break;
		}
		break;
	case "LINK":
		if ($previous == "LINK") {
			$seperator = " | ";
		} else {
			$seperator = "";
		}
		if ($attrs["HREF"]) {
			$html[$loc] .= $seperator."<a href='".$attrs["HREF"]."'>";
		} else {
			$html[$loc] .= "<form method='post' id='".$attrs["VIEW"]."_".$attrs["ACTION"]."_form' name='".$attrs["VIEW"]."_".$attrs["ACTION"]."_form'>";
			$html[$loc] .= "<input type='hidden' id='module' name='module' value='".$attrs["MODULE"]."' />";
			$html[$loc] .= "<input type='hidden' id='name' name='name' value='".$attrs["VIEW"]."' />";
			$html[$loc] .= "<input type='hidden' id='action' name='action' value='".$attrs["ACTION"]."' />";
			$html[$loc] .= "</form>";
			if ($attrs["CONFIRM"]) {
				$confirm = "javascript:var go = check(\"".$attrs["CONFIRM"]." --???Fix confirm\");";
			} else {
				$confirm = "javascript:var go=true;";
			}
			$html[$loc] .= $seperator."<a class='local_link' onclick='".$confirm." if (go==true) {submit_form(\"".$attrs["VIEW"]."_".$attrs["ACTION"]."_form\")}'>";
		}
		break;
	case "BREAK":
		$html[$loc] .= "<br/>";
		break;
	case "MENU":
		$html[$loc] .= "<div class='menudiv' dojoType='ContentPane' label='".$attrs['NAME']."'>";
		break;
	case "MENUITEM":
		$form = $attrs['NAME'];
		$html[$loc] .= "<form method='post' id='".$form."_form' name='".$form."_form'>";
		$html[$loc] .= "<input type='hidden' id='module' name='module' value='".$attrs['MODULE']."' />";
		$html[$loc] .= "<input type='hidden' id='name' name='name' value='".$form."' />";
		$html[$loc] .= "<span class='menuitem' onclick='javascript:submit_form(\"".$form."_form\")'>";
		break;
	case "TABLE":
		$style = '';
		if ($attrs['BORDER-LEFT']) {
			$style .= 'border-left: 1px solid;';
		}
		if ($attrs['BORDER-RIGHT']) {
			$style .= 'border-right: 1px solid;';
		}
		if ($attrs['BORDER-TOP']) {
			$style .= 'border-top: 1px solid;';
		}
		if ($attrs['BORDER-BOTTOM']) {
			$style .= 'border-bottom: 1px solid;';
		}
		$html[$loc] .= "<table style='".$style."border-collapse: collapse;'>";
		break;
	case "TROW":
		$html[$loc] .= "<tr>";
		break;
	case "THEAD":
	case "TCELL":
		$style = '';
		if ($attrs['BORDER-LEFT']) {
			$style .= 'border-left: 1px solid;';
		}
		if ($attrs['BORDER-RIGHT']) {
			$style .= 'border-right: 1px solid;';
		}
		if ($attrs['BORDER-TOP']) {
			$style .= 'border-top: 1px solid;';
		}
		if ($attrs['BORDER-BOTTOM']) {
			$style .= 'border-bottom: 1px solid;';
		}
		if ($attrs['SPAN']) {
			$colspan = "colspan='".$attrs['SPAN']."'";
		}
		$html[$loc] .= "<td ".$colspan." id='".$attrs['ID']."' style='".$style."'>";
		break;
	case "PANE":
		if ($attrs['STATUS'] == 'collapsed') {
			$style .= 'display: none;';
		}
		$javascript = "onclick='javascript:expandPane(\"".$attrs['NAME']."\");'";
		$html[$loc] .= "<fieldset id='".$attrs['NAME']."' class='".$attrs['STATUS']."'><legend ".$javascript.">".$attrs['NAME']."</legend><div id='".$attrs['NAME']."_div' style='".$style."'>";
		break;
	} 
	
	global $depth;
	$previous = $name;
	$depth[$parser]++;
}

function endElement($parser, $name) {
	global $html;
	global $previous ;
	global $loc;
	$depth[$parser]--;
        switch ($name) {
        case "PANE":
                $html[$loc] .= "</div></fieldset>";
                break;
        case "MENU":
        case "MODULE":
                $html[$loc] .= "</div>";
                break;
        case "PARA":
                $html[$loc] .= "</p>";
                break;
        case "HEADER":
                $html[$loc] .= "</h2>";
                break;
        case "TEXT":
                $html[$loc] .= "</span>";
		break;
	case "FORM":
		$html[$loc] .= "</form></div>";
		break;
	case "LINK":
		$html[$loc] .= "</a>";
		break;
	case "MENUITEM":
		$html[$loc] .= "</span>";
		$html[$loc] .= "</form>";
		break;
	case "TABLE":
		$html[$loc] .= "</table>";
		break;
	case "TROW":
		$html[$loc] .= "</tr>";
		break;
	case "THEAD":
	case "TCELL":
		$html[$loc] .= "</td>";
		break;
        }
}

function tagData($parser, $data) {
	global $html;
	global $previous ;
	global $loc;
	if ($loc == "control") {
		$_SESSION[$previous] = $data;
	} else {
		$html[$loc] .= $data;
	}
}

function parseXML($response) {
	global $html;
	$xml_parser = xml_parser_create();
	xml_set_element_handler($xml_parser, "startElement", "endElement");
	xml_set_character_data_handler($xml_parser, "tagData");
	$lines = explode("\n", $response);
	foreach ($lines as $i => $line) {
		if (!xml_parse($xml_parser, $line)) {
			die(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($xml_parser)),
			xml_get_current_line_number($xml_parser)));
		}
	}
	xml_parser_free($xml_parser);
	return $html;
}

?> 
