<?php

$indexpdf = "<html>
<head>
<title>Report Export</title>
<style>
body {
	color: #333;
	font: normal 12px Arial, Verdana, Helvetica, sans-serif; 
	margin: 0px;
}

td a, td {
	color: #333;
	font: normal 12px Arial, Verdana, Helvetica, sans-serif; 
	text-decoration: none;
	overflow: hidden;
}

table.bordered {
	border-collapse: collapse;
	border: thin solid black;
	width: 99%;
	table-layout: fixed;
	overflow:hidden;
	text-align: right;
}
table.bordered th {
	border: thin solid black;
	font-size: 10px;
}
table.bordered td {
	border-right: thin solid black;
	border-bottom: thin solid black;
	padding: 2px;
	font-size: 10px;
}

td strong {
	text-align: left;
	font-weight: normal;
	font-family: georgia;
}

</style>
</head>
<body>
";
$indexpdf .= "<div style='width: 99%; border-bottom:thin solid black; margin-bottom: 20px;'>
	<table style='width: 99%;'>
	<tr>
		<td valign='top' width='150px' rowspan='2'><img src='http://".$_SERVER['SERVER_ADDR']."/".$conf['Dir']['WebPath']."/images/ieaust_logo.png' alt='logo' title='logo' /></td>
		<td valign='top'><h1>".substr($filename, 0, strpos($filename, "-"))."</h1></td>
	</tr>
	<tr><td>";
if ($auto) {
	$indexpdf .= "<p>";
	foreach ($auto as $i => $value) {
		$indexpdf .= "<strong>".ucwords($i).": </strong>".$value."<br />";
	}
	$indexpdf .= "</p>";
} 
$indexpdf .= "<h3>Golf Report Generator</h3></td>
	</tr>
	</table>
	</div>
<div>";
$indexpdf .= $data;
$indexpdf .= "</div>
<div style='margin-top: 20px; width: 99%; border-top:thin solid black;'>
<table width='99%'>
<tr>
<td>(c) Engineers Australia</td>";

if ($options) {
	$num = 0;
	foreach ($options as $i => $option) {
		if ($num == 3) {
			$indexpdf .= "</tr><tr><td></td>";
			$num = 0;
		} else {
			$num++;
		}
		$indexpdf .= "<td><em>".ucwords($i)."</em>: ".$option."</td>";
	}
}

$indexpdf .= "
</tr></table>
</div>
</body>
</html>";
?>
