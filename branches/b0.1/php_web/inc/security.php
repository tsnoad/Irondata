<?php

/**
 * security.php
 *
 * Manages all the security and sanity checking
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 * 
 */

class Security {

	function sanitise() {
		foreach ($_REQUEST as $i => $request) {
			/* Sanitise the data */
			$_REQUEST[$i] = $request;
		}
	}

	function db_sanitise($data) {
		foreach ($data as $i => $request) {
			/* Sanitise the data */
			$data[$i] = $request;
		}
	}
	
	function check($role, $user=false) {
		if (!$user) {
			$user = $_SESSION['user']['user_id'];
		}
		
	}
}

?>
