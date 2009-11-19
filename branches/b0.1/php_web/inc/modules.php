<?php

/**
 * modules.php
 *
 * Manages all the modules settings and other information
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 * 
 */

class Modules extends Theme {
	var $dir = "modules/";
	/* An array of module class. So we don't instantiate them too often */
	var $mobs = array();
	var $id;
	var $module;
	var $action;
	var $subvar;
	var $subid;
	var $dobj;

	var $newschool = true;

	function __construct() {
		include("conf.php");
		$this->conf = $conf;

		$url = explode("/", $_REQUEST['url']);
		$this->set_this($url);
	}
	
	function set_this($url) {
		include_once("inc/db.php");
		$this->dobj = new DB();

		if (array_key_exists(2, $url)) {
			$this->id = $url[2];
		}
		if (array_key_exists(3, $url)) {
			$this->subvar = $url[3];
		}
		if (array_key_exists(4, $url)) {
			$this->subid = $url[4];
		}
		if (array_key_exists(5, $url)) {
			$this->aux1 = $url[5];
		}
		if (array_key_exists(6, $url)) {
			$this->aux2 = $url[6];
		}
		if (array_key_exists(7, $url)) {
			$this->aux3 = $url[7];
		}
		if (array_key_exists(8, $url)) {
			$this->aux4 = $url[8];
		}
		$this->module = $url[0];
		$this->action = $url[1];
	}

	function getActive() {
		$query = "SELECT * FROM modules WHERE status='active'";
		$active = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		$dirs = array();
		foreach ($active as $i => $mod) {
			$dirs[$mod['module_id']] = $mod['module_id'];
		}
		return $dirs;
	}

	function getDetailed() {
		$query = "SELECT * FROM modules";
		$active = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		$raw_dirs = scandir($this->dir);
		$dirs = array();
		foreach ($raw_dirs as $i => $dir) {
			if (substr($dir, 0, 1) == ".") {
				continue;
			}
			$file = $this->dir.$dir."/".$dir.".php";
			if (file_exists($file)) {
				include_once($file);
				$className = ucwords($dir);
 				$foo = new $className;
				$mod = null;
				$mod->name = $foo->name ? $foo->name : $dir;
				$mod->description = $foo->description;
				foreach ($active as $i => $actmod) {
					if ($actmod['module_id'] == $dir) {
						$mod->status = $actmod['status'];;
						$mod->core = $actmod['core'];
						break;
					} else {
						$mod->status = false;
					}
				}
				$dirs[$dir] = $mod;
			}
		}
		return $dirs;
	}

	function call_function($modules, $function, $data=array(), $check_priority=false) {
		if ($modules == "ALL") {
			/* The special case */
			$modules = $this->getActive();
		} else if (!is_array($modules)) {
			/* If they only send one module name through */
			$modules = array($modules);
		}

		//if we need to check the order in which functions are run
		if ($check_priority) {
			foreach ($modules as $i => $module) {
				include_once($this->dir.$module."/".$module.".php");
				$className = ucwords($module);
				if (!array_key_exists($module, $this->mobs)) {
					$this->mobs[$module] = new $className;
				}

				if (method_exists($this->mobs[$module], $function."_priority")) {
					//call the priority function of the function to be called
					list($priority, $exclusive) = call_user_func(array($this->mobs[$module], $function."_priority"));

					//only run the function in this module, empty the $modules array of all but this
					if ($exclusive) {
						$modules = array($module);
						break;
					}

					if (empty($priority)) continue;

					//create an array of priorities
					$priorities[$module] = $priority;
				}
			}

			if (!empty($priorities)) {
				//sort by priority
				asort($priorities);

				//array of modules indexed by modules, ordered by priority
				$modules_prioritised = array_combine(array_keys($priorities), array_keys($priorities));

				//remove prioritised modules from original module list
				foreach ($modules_prioritised as $module) {
					unset($modules[$module]);
				}

				//add the prioritised modules before all other modules
				$modules = array_merge((array)$modules_prioritised, (array)$modules);

			}
		}

		$res = array();
		foreach ($modules as $i => $module) {
			include_once($this->dir.$module."/".$module.".php");
			$className = ucwords($module);
			if (!array_key_exists($module, $this->mobs)) {
				$this->mobs[$module] = new $className;
			}
			if (method_exists($this->mobs[$module], $function)) {
				if (count($data)) {
					switch ($function) {
						case "hook_login":
						case "hook_recipients":
						case "hook_recipient_selector":
						case "hook_access_users":
							$modres = call_user_func_array(array($this->mobs[$module], $function), $data);
							break;
						default:
							$modres = $this->mobs[$module]->$function($data);
							break;
					}
				} else {
					$modres = call_user_func(array($this->mobs[$module], $function));
				}
				if ($modres) {
					$res[$module] = $modres;
				}
			}
		}

		return $res;
	}
	
	function background_function($url) {
		/* There can only be 1 background per URL at any given time */
		$query = $this->dobj->db_query("DELETE FROM backgrounds WHERE url=$$".$url."$$");
		$query = $this->dobj->db_query("INSERT INTO backgrounds (url) VALUES ($$".$url."$$);");
		return $url;
	}
	
	function run_background() {
		/* Get the first background */
		$url = $this->dobj->db_fetch($this->dobj->db_query("SELECT background_id, url FROM backgrounds WHERE running='f' AND complete IS NULL ORDER BY background_id LIMIT 1"));
		if ($url) {
			/* Mark the background as running */
			$data = array();
			$data['running'] = "t";
			$save = $this->dobj->db_query($this->dobj->update($data, "background_id", $url['background_id'], "backgrounds"));
			
			$newurl = explode("/", $url['url']);
			$this->set_this($newurl);
			$_REQUEST['url'] = $url['url'];
			$res = $this->call_function($this->module, "view_".$this->action);

			$data = array();
			$data['running'] = "f";
			$data['complete'] = "now()";
			$data['results'] = $res[$this->module]->data;
			$save = $this->dobj->db_query($this->dobj->update($data, "background_id", $url['background_id'], "backgrounds"));
		}
		return $res;
	}

	function get_settings($module_id) {
		$query = "SELECT * FROM settings WHERE module_id='".$module_id."'";
		$modules = $this->dobj->db_fetch_all($query);
		return $modules;
	}

	function save_settings($module_id, $data) {
		foreach ($data as $k => $value) {
			/* Check if it already exists */
			$query = "SELECT * FROM settings WHERE module_id='".$module_id."' AND key='".$k."'";
			$val = $this->dobj->db_fetch($query);
			if ($val) {
				$query = "UPDATE settings SET value=$$".$value."$$ WHERE module_id='".$module_id."' AND key='".$k."'";
				$val = $this->dobj->db_query($query);
			} else {
				$query = "INSERT INTO settings VALUES ($$".$module_id."$$, $$".$k."$$, $$".$value."$$)";
				$val = $this->dobj->db_query($query);
			}
		}
	}

	function delete_settings($module_id, $key=false) {
		$query = "DELETE FROM settings WHERE module_id='".$module_id."'";
		$query .= $key ? " AND key=".$key : "";
		$modules = $this->dobj->db_query($query);
		return $modules;
	}

}

?>
