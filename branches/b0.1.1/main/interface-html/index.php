<?php

/**
 * index.php
 *
 * Called by the browser; instantiates the software, passing in state-tracking
 * variables via the GET string (how tiresome!). Handles basic authentication,
 * instantiation of the core object, and output buffering (saving output from
 * other portions of the software, and putting it into a debug div).
 *
 * @author Andy White
 * @date 19-07-2006
 * 
 */


session_start();
/* Connect to the socket and get stuff XML */
include("requests.php");
include("connection.php");
include("interface.php");

if (!$_POST) {
	include("html.inc");
	$output .= "<script>";
	if ($_SESSION['SESSION']) {
		$front = str_replace("%SESSION%", "<session>".$_SESSION['SESSION']."</session>", $front);
	} else {
		$front = str_replace("%SESSION%", "", $front);
		#$output .= "hideElement('menu');\n";
	}
	$responseXML = connect($front);
} else {
	$submit = str_replace("%MOD%", $_POST['module'], $submit);
	$query = "<command name='".$_POST['name']."'>\n";
	foreach ($_POST as $i => $post) {
		if ($i == "module" || $i == "name") {
			continue;
		}
		$query .= "<element name='".$i."'>".$post."</element>\n";
	}
	$query .= "</command>";
	$submit = str_replace("%QUERY%", $query, $submit);
	if ($_SESSION['SESSION']) {
		$submit = str_replace("%SESSION%", "<session>".$_SESSION['SESSION']."</session>", $submit);
	} else {
		$submit = str_replace("%SESSION%", "", $submit);
	}
	$responseXML = connect($submit);
	$output = $scripts;
}
$responseHTML = parseXML($responseXML);

if ($responseHTML['main']) {
	$output .= "loadElement('main', '".rawurlencode($responseHTML['main'])."');\n";
}

if ($responseHTML['footer']) {
	$output .= "loadElement('footer', '".rawurlencode($responseHTML['footer'])."');\n";
}

if ($responseHTML['header']) {
	$output .= "loadElement('header', '".rawurlencode($responseHTML['header'])."');\n";
}

if ($responseHTML['menu']) {
	$output .= "loadElement('menu', '".rawurlencode($responseHTML['menu'])."');\n";
}

if ($responseHTML['message']) {
	$output .= "loadPopup('".rawurlencode($responseHTML['message'])."');\n";
}

if (!$_POST) {
	$output .= "</script>";
}

echo $output;

?>
