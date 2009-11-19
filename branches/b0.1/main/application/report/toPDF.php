<?php
include_once("Common_Functions.php");
include("error.php");
$conf = parse_ini_file("../conf.ini", true);
$javahome = $conf["global"]["javahome"];
$fopdir = $conf["global"]["fop"];

function trendToPDF($query, $which="") {
	
	if (file_exists("saved/trend/$query/$which.jpeg")) {
		$fo = file_get_contents("template.xml");
		$fo = str_replace("%TITLE", "$query-$which", $fo);
		
		copy("saved/trend/$query/$which.jpeg", "tmp/fo.jpeg");
		
		$fo = str_replace("%IMAGE_URL", "fo.jpeg", $fo);
		file_put_contents("tmp/fo.xml", $fo);
		global $javahome;
		$cmd = "export JAVA_HOME=".$javahome."; ".$fopdir."/fop tmp/fo.xml tmp/trend.pdf";
		exec($cmd);
		$pdf = file_get_contents("tmp/trend.pdf");
		return $pdf;
	}
	return $main;
}

function pdfLine($line, $empty="-")
{
	$fo = "<fo:table-row>";
	$el = array();
	$line = str_replace("&", "&amp;", $line);
	$tok = strtok($line, "	");
	while ($tok != false) {
		array_push($el, $tok);
		$tok = strtok("	");
	}
	
	$j = count($el);
	for ($i=0;$i<$j;$i++) {
		if ($el[$i]==" ") {
			$cell = $empty;
		} else {
			$cell = $el[$i];
		}

		/* Dropping the bold for now		
		if ($i==0) {
			$bold = ' font-weight="bold"';
		} else {
			$bold = "";
		}
		*/
		$fo .= '<fo:table-cell border-style="solid"
  							border-width="thin" padding="3px">';
  	$fo .="<fo:block font-size=\"10pt\" $bold>$cell</fo:block>";
  	$fo .= '</fo:table-cell>';
	}
	$fo .= "</fo:table-row>";
	return $fo;
}

//TODO still doing it, not finished.
function tableToPDF($query, $which="") {
	$file_name = "saved/table/$query/$which.csv";
	if (file_exists($file_name)) {
		$lines = file($file_name);
		$j = count($lines);
		$el = array();
		$i = 0;
		if ($i+2 < $j) {
			$fo = '<fo:block font-size="10pt">';
			$fo .= $lines[$i+1]."</fo:block>";
			$fo .= "<fo:table table-layout='fixed'>";
			str_replace("	", "	", $lines[$i+2], $header_count);
			$header_count++;
			$width = round(19/$header_count, 1);
			for ($k=0;$k<$header_count;$k++) {
				$fo .= "<fo:table-column column-width=\"$width\"/>";
			}
			$fo .= "<fo:table-body>";
			$fo .= pdfLine($lines[$i+2], "&nbsp;");
		}
		
		for ($i=3;$i<$j;$i++) {
			if ($lines[$i]=="\n") {
				$fo .= "</fo:table-body></fo:table>";
				if ($i+2 < $j) {
					$fo .= '<fo:block font-size="10pt">';
					$fo .= $lines[$i+1]."</fo:block>";
					$fo .= "<fo:table table-layout='fixed'>";
					str_replace("	", "	", $lines[$i+2], $header_count);
					$header_count++;
					$width = round(19/$header_count, 2);
					for ($j=0;$j<$header_count;$j++) {
						$fo .= "<fo:table-column column-width=\"$width\"/>";
					}
					$fo .="<fo:table-body>";
					$fo .= pdfLine($lines[$i+2], "&nbsp;");
					$i = $i + 2;
				} else {
					break;
				}
			} else {
				$fo .= pdfLine($lines[$i]);
			}
		}
		$fo .= "</fo:table-body></fo:table>";
		$file = file_get_contents("template2.xml");
		$file = str_replace("%TABLE", $fo, $file);
		$file = str_replace("%TITLE", $query." $which", $file);
		file_put_contents("tmp/fo.xml", $file);
                global $javahome;
		$cmd = "export JAVA_HOME=".$javahome."; ".$fopdir."/fop tmp/fo.xml tmp/table.pdf";
		exec($cmd);
		$pdf = file_get_contents("tmp/table.pdf");
		return $pdf;
	} 
	return "";
}

function listToPDF($query, $which="") {
	$file_name = "saved/list/$query/$which.csv";
	$fo = '<fo:block font-weight="bold" font-size="10pt">';
	$fo .= "Raw Data - $query</fo:block>";
	if (file_exists($file_name)) {
		$lines = file($file_name);
		$j = count($lines);
		$fo .= "<fo:table table-layout='fixed'>";
		$header_count = substr_count($lines[2], "	");
                $width = round(19/$header_count, 1);
                for ($k=0;$k<$header_count;$k++) {
                        $fo .= "<fo:table-column column-width=\"$width\"/>";
                }
		$fo .= "<fo:table-body>";
		$fo .= pdfLine($lines[0]);
		
		for ($i=1;$i<$j;$i++) {
			$fo .= pdfLine($lines[$i]);
		}
		$fo .= "</fo:table-body></fo:table>";
		$file = file_get_contents("template2.xml");
		$file = str_replace("%TABLE", $fo, $file);
                $file = str_replace("%TITLE", $query." $which", $file);
		file_put_contents("tmp/fo.xml", $file);
                global $javahome;
		$cmd = "export JAVA_HOME=".$javahome."; ".$fopdir."/fop tmp/fo.xml tmp/list.pdf";
		exec($cmd);
		$pdf = file_get_contents("tmp/list.pdf");
		return $pdf;
	}
	return "";
}


$type = $_REQUEST['report_type'];
$report = $_REQUEST['saved_report'];
$id = $_REQUEST['id'];
switch ($type) {
	case 'trend':
		$pdf = trendToPDF($report, $id);
		break;
	case 'table':
		$pdf = tableToPDF($report, $id);
		break;
	case 'list':
	case 'listing':
		$pdf = listToPDF($report, $id);
		break;
	default:
		$pdf = "";
}

if ($pdf != "") {
	header('Content_type: application/pdf');
	header("Content-Disposition: attachment; filename=$id.pdf");
	header("Content-Transfer-Encoding: binary");
	print $pdf;
} else {
	header("HTTP/1.1 404 Not Found");
	exit;
}
?>
