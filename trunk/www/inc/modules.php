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
				$allow = false;

				$url = $module."/".$function;

				if (array_key_exists('skip_auth', $_SESSION) && $_SESSION['skip_auth']) {
					$allow = true;
				}

				if (!$allow) $allow |= preg_match("/\/hook_auth/", $url);
				if (!$allow) $allow |= preg_match("/\/hook_pagetitle/", $url);
				if (!$allow) $allow |= preg_match("/\/hook_top_menu/", $url);
				if (!$allow) $allow |= preg_match("/\/hook_style/", $url);
				if (!$allow) $allow |= preg_match("/\/hook_header/", $url);
				if (!$allow) $allow |= preg_match("/\/hook_login/", $url);
				if (!$allow) $allow |= preg_match("/\/hook_javascript/", $url);
				if (!$allow) $allow |= preg_match("/^user\/view_login/", $url);
				if (!$allow) $allow |= preg_match("/^user\/view_logout/", $url);
				if (!$allow) $allow |= preg_match("/\/set_session_report_acls/", $url);

				if ($_SESSION['acls']['system']['login']) {
					if (!$allow) $allow |= preg_match("/^workspace\/view_home/", $url);
					if (!$allow) $allow |= preg_match("/^template\/view_home/", $url);
					if (!$allow) $allow |= preg_match("/^search/", $url);
					if (!$allow) $allow |= preg_match("/^help/", $url);
					if (!$allow) $allow |= preg_match("/^user\/view_logout/", $url);
				}

				if ($_SESSION['acls']['system']['reportscreate']) {
					if (!$allow) $allow |= preg_match("/^template\/view_add/", $url);
					if (!$allow) $allow |= preg_match("/\/hook_template_entry/", $url);
					if (!$allow) $allow |= preg_match("/^catalogue\/get_databases/", $url);
					if (!$allow) $allow |= preg_match("/\/view_add_select_object/", $url);
				}

				if ($_SESSION['acls']['system']['admin']) {
					if (!$allow) $allow |= preg_match("/^admin/", $url);
					if (!$allow) $allow |= preg_match("/^catalogue/", $url);
					if (!$allow) $allow |= preg_match("/^user/", $url);
					if (!$allow) $allow |= preg_match("/\/hook_admin_tools/", $url);
					if (!$allow) $allow |= preg_match("/\/hook_access_users/", $url);
					if (!$allow) $allow |= preg_match("/\/hook_access_acls/", $url);
					if (!$allow) $allow |= preg_match("/\/hook_roles/", $url);
					if (!$allow) $allow |= preg_match("/\/hook_access_submit/", $url);
					if (!$allow) $allow |= preg_match("/\/hook_module_settings/", $url);
					if (!$allow) $allow |= preg_match("/^pgsql\/test_connection/", $url);
					if (!$allow) $allow |= preg_match("/^pgsql\/hook_regen_schema/", $url);
				}

				if (!empty($_SESSION['acls']['report'])) {
					foreach ($_SESSION['acls']['report'] as $foo_id => $foo) {
						if ($this->id == $foo_id) {
							if ($foo['edit']) {
								if (!$allow) $allow |= preg_match("/\/view_add/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_save/", $url);
								if (!$allow) $allow |= preg_match("/\/view_delete/", $url);
								if (!$allow) $allow |= preg_match("/^catalogue\/get_structure/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_table_join_ajax/", $url);
								if (!$allow) $allow |= preg_match("/\/get_or_generate/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/get_columns/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/hook_output/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_data_preview_ajax/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_data_preview_first_ajax/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_data_preview_slow_ajax/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_constraint_column_options_ajax/", $url);
								if (!$allow) $allow |= preg_match("/\/hook_recipient_selector/", $url);
								if (!$allow) $allow |= preg_match("/\/view_recipient_selector/", $url);
								if (!$allow) $allow |= preg_match("/\/hook_access_users/", $url);
								if (!$allow) $allow |= preg_match("/\/hook_access_report_acls/", $url);
								if (!$allow) $allow |= preg_match("/\/hook_access_report_submit/", $url);
							}
						}
						if ($this->id == $foo_id) {
							if ($foo['histories']) {
								if (!$allow) $allow |= preg_match("/^tabular\/view_histories/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_history/", $url);
								if (!$allow) $allow |= preg_match("/^tabular\/view_processing_history_ajax/", $url);
							}
						}
						if ($this->id == $foo_id) {
							if ($foo['execute']) {
								if (!$allow) $allow |= preg_match("/^tabular\/view_execute_manually/", $url);
							}
						}
					}
				}

				if (!$allow) die("$url not allowed.");

				if (count($data)) {
					switch ($function) {
						case "hook_login":
						case "hook_recipients":
						case "hook_recipient_selector":
						case "hook_access_users":
						case "hook_access_acls":
						case "hook_access_report_acls":
						case "hook_access_submit":
						case "hook_access_report_submit":
						case "acl_resort_users":
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

	function acl_resort_users($users_tmp, $groups_tmp, $users_groups_tmp, $disabled_tmp, $acls_tmp) {
		$id = 1;

		foreach ($users_tmp as $user_id_tmp => $user_tmp) {
			$users[$id] = $user_tmp;

			$ids['users'][$user_id_tmp] = $id;
			$ids_r[$id] = array($user_id_tmp, "users");

			$id ++;
		}

		foreach ($groups_tmp as $group_id_tmp => $group_tmp) {
			$groups[$id] = $group_tmp;

			$ids['groups'][$group_id_tmp] = $id;
			$ids_r[$id] = array($group_id_tmp, "groups");

			$id ++;
		}

		foreach ($users_groups_tmp as $user_id_tmp => $user_groups_tmp) {
			foreach ($user_groups_tmp as $group_id_tmp) {
				$user_id = $ids['users'][$user_id_tmp];
				$group_id = $ids['groups'][$group_id_tmp];

				$users_groups[$user_id][] = $group_id;
			}
		}

		foreach (array("users", "groups") as $users_meta_key) {
			if (!empty($disabled_tmp[$users_meta_key])) {
				foreach ($disabled_tmp[$users_meta_key] as $disabled_user_id => $disabled_user_tmp) {
					$user_id = $ids[$users_meta_key][$disabled_user_id];

					$disabled[$user_id] = true;
				}
			}

			if (!empty($acls_tmp[$users_meta_key])) {
				foreach ($acls_tmp[$users_meta_key] as $acl_tmp) {
					if ($users_meta_key == "users") {
						$user_id_tmp = $acl_tmp['user_id'];
					} else if ($users_meta_key == "groups") {
						$user_id_tmp = $acl_tmp['group_id'];
					}

					$user_id = $ids[$users_meta_key][$user_id_tmp];
					$role = $acl_tmp['role'];

					if ($acl_tmp['access'] != "t") continue;

					$acls[$user_id][$role] = true;
				}
			}
		}

		foreach (array("Group"=>$groups, "User"=>$users) as $users_meta_key => $users_meta) {
			foreach ($users_meta as $user_id => $user) {
				unset($membership);

				if (count($users_groups[$user_id])) {
					foreach ($users_groups[$user_id] as $group_id) {
						$membership[] = $groups[$group_id];
					}

					$membership = implode(", ", $membership);
				} else {
					$membership = "&nbsp;";
				}

				$rows[$user_id] = array(
					$user,
					$users_meta_key,
					$membership
					);
			}
		}

		return array($ids_r, $users, $groups, $users_groups, $disabled, $acls, $membership, $rows);
	}
}

?>
