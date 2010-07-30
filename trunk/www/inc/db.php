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
 * db.php
 *
 * Manages all the database settings and other information
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class DB {
	var $conf;
	var $conn;

	function __construct() {
		include("conf.php");
		$this->conf = $conf;
		if ($this->conf['metabase']['type'] == "postgres") {
			$conn_string = "host=".$conf['metabase']['hostname']." dbname=".$conf['metabase']['database'];

			if (!empty($conf['metabase']['username'])) {
				$conn_string .= " user=".$conf['metabase']['username'];
			}

			if (!empty($conf['metabase']['password'])) {
				$conn_string .= " password='".$conf['metabase']['password']."'";
			}

			$this->conn = pg_connect($conn_string);
		}
	}

	function db_query($query) {
		//file_put_contents("/tmp/foo.txt", $query."\n", FILE_APPEND);
		if ($this->conf['metabase']['type'] == "postgres") {
			$res = pg_query($this->conn, $query);
		}
		return $res;
	}

	function db_fetch_all($res) {
		/* Is a raw query string. Expecting resource */
		if (is_string($res)) {
			$res = $this->db_query($res);
		}
		if ($this->conf['metabase']['type'] == "postgres") {
			$vals = pg_fetch_all($res);
		}
		return $vals;
	}

	function db_fetch($res) {
		/* Is a raw query string. Expecting resource */
		if (is_string($res)) {
			$res = $this->db_query($res);
		}
		if ($this->conf['metabase']['type'] == "postgres") {
			$vals = pg_fetch_assoc($res);
		}
		return $vals;
	}

	function nextval($table) {
		if ($this->conf['metabase']['type'] == "postgres") {
			//work out the primary key name.
			switch ($table) {
				//normally the primary key name is the table name, minus the last character - usually an "s"
				default:
					$sing = substr($table, 0, -1);
					break;
				case "people":
					$sing = "person";
					break;
				case "tabular_constraints":
				case "tabular_templates_manual":
					$sing = $table;
					break;
			}

			//primary key name always ends with "_id"
			$sing .= "_id";

			//postgres cuts table and pkey names, in sequence tables, off at 29 characters
			if (strlen($table) > 29 && strlen($sing) > 29) {
				$table = substr($table, 0, 29);
				$sing = substr($sing, 0, 29);
			}

			$query = "SELECT nextval('".$table."_".$sing."_seq')";
			$val = $this->db_fetch($this->db_query($query));
			$val = $val['nextval'];
		}
		return $val;
	}
	
	function insert($data, $table) {
		$cols = array();
		$vals = array();
		foreach ($data as $i => $val) {
			$cols[] = $i;
			if ($val == "") {
				$vals[] = "NULL";
			} else {
				$vals[] = "$$".$val."$$";
			}
		}
		$query = "INSERT INTO ".$table." (".implode(",", $cols).") VALUES (".implode(",", $vals)."); ";
		return $query;
	}
	
	function update($data, $idcol, $id, $table) {
		$vals = array();
		foreach ($data as $i => $val) {
			if ($val == "") {
				$vals[] = $i."=NULL";
			} elseif ($val == "now()") {
				$vals[] = $i."=now()";
			} else {
				$vals[] = $i."=$$".$val."$$";
			}
		}
		$query = "UPDATE ".$table." SET ".implode(",", $vals)." WHERE ".$idcol."=$$".$id."$$; ";
		return $query;
	}

	function insert_or_update($data, $table, $idcol=false, $id=false) {
/*		if ($id) {
			$query = "UPDATE ".$table." SET ".implode(",", $vals)." WHERE ".$idcol."=$$".$id."$$; ";
		} else {
			$query = "INSERT INTO ".$table." (".implode(",", $cols).") VALUES (".implode(",", $vals)."); ";
		}*/
		return $query;
	}

}

?>
