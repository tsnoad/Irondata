<?php

set_error_handler("userErrorHandler");

/**
 * A custom error handling function which uses the gotit logging system
 *
 * It takes in a error array (standard PHP) and generates a string based on
 * the error type, error message, the file name and line number.
 *
 * @author Evan Leybourn <evan@lgsolutions.com.au>
 * @author Looking Glass Solutions
 * @param integer $errno        The error number based on severity [1-8]
 * @param string $errmsg        The error message from PHP
 * @param string $location      The file that threw the error
 * @param string $linenum       The line number where the error occured
 * @param array $vars           The active variables when the error occured
 */
function userErrorHandler($errno, $errmsg, $location, $linenum, $vars) {

   /**
    * Define an assoc array of error strings.
    * In reality the only entries we should consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    * E_USER_WARNING and E_USER_NOTICE
    */
   $errortype = array (
               E_WARNING        => "Warning",
               E_NOTICE          => "Notice",
               E_USER_ERROR      => "User Error",
               E_USER_WARNING    => "User Warning",
               E_USER_NOTICE    => "User Notice",
               );

   /**
    * save to the log
    */
   if ($errno<5) {
        Common_Functions::addToLog(1, $errortype[$errno].": ".$errmsg." in ".$location." at line #".$linenum, 'PHP Error');
   }
}

?>
