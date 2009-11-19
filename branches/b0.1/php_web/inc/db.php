<?php

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
			$this->conn = pg_connect("host=".$conf['metabase']['hostname']." dbname=".$conf['metabase']['database']." user=".$conf['metabase']['username']." password='".$conf['metabase']['password']."'");
		}
	}

	function db_query($query) {
// 		file_put_contents("/tmp/foo.txt", $query."\n", FILE_APPEND);
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
			switch ($table) {
				default:
					$sing = substr($table, 0, -1);
					break;
				case "people":
					$sing = "person";
					break;
				case "tabular_constraints":
					$sing = "tabular_constraints";
					break;
			}

			$query = "SELECT nextval('".$table."_".$sing."_id_seq')";
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
