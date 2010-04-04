<?php
/**
    Irondata
    Copyright (C) 2009  Evan Leybourn, Tobias Snoad

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

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
