<?php

session_start();
$conf = parse_ini_file("../conf.ini", true);

session_destroy();
unset($_SESSION);

$main = "<h3>Logout</h3>Thank you for using the Golf Report Generator";
$meta = '<META HTTP-EQUIV="Refresh" CONTENT="0; URL='.$conf['Dir']['WebPath'].'/index.php">';
include($conf['Dir']['FullPath']."/html.inc");

?>
