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
		include_once("inc/db.php");
		$this->dobj = new DB();

		if (isset($_REQUEST['url'])) {
			$url = explode("/", $_REQUEST['url']);
			$this->set_this($url);
		}
	}
	
	/**
	 * Does this module interact with the users workspace. This will populate links on
	 * the workspace
	 *
	 * @return Returns an array containing the title and path of the link on the workspace
	 */
	function hook_workspace() {
		return null;
	}
	
	/**
	 * What does this function do?
	 * TODO: Update this comment
	 */
	function hook_admin_tools() {
		return null;
	}
	
	/**
	 * Include any module specific CSS
	 *
	 * @return Returns a string conatins the module CSS
	 */
	function hook_style() {
		return null;
	}
	
	/**
	 * The Top Menu hook function.
	 * Displays the module in the main menu. Or menu of primary functions.
	 *
	 * @return Returns an associative array on links to display on the menu
	 */
	function hook_top_menu() {
		return null;
	}
	
	/**
	 * The Menu hook function.
	 * Displays items in the side bar. This can be dependant on the actual URL used.
	 *
	 * @return Returns an array of arrays of menu links
	 */
	function hook_menu() {
		return null;
	}
	
	/**
	 * What roles / persmissions are required for users within this module
	 * If blank, we assume anybody can do anything.
	 *
	 * @return Returns an array of permissions
	 */
	function hook_roles() {
		return null;
	}
	
	/**
	 * The Javascript hook function.
	 * Send the following javascript to the browser.
	 *
	 * @param $type string A generic parameter.
	 * @return The javascript string to include
	 */
	function hook_javascript($type=null) {
		return null;
	}
	
	/**
	 * What does this function do?
	 * TODO: Update this comment
	 */
	function hook_output() {
		return null;
	}

	/**
	 * Each module will check whether a user can access a given function
	 *
	 * @param array $data The function to check and the users roles
	 * @return boolean - does the current user have permission
	 */
	function hook_permission_check($data) {
		return false;
	}
	
	function set_this($url) {
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
				$mod->module_group = $foo->module_group;
				$mod->description = $foo->description;
				foreach ($active as $i => $actmod) {
					if ($actmod['module_id'] == $dir) {
						$mod->status = $actmod['status'];
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

	/**
	 * This will call any function within any active module. It will automatically check
	 * for priorities and permissions before calling.
	 *
	 * @param mixed $modules The module(s) to call the function within. Can be a string (for 1) or an array (for many). Can also be the special string "ALL"
	 * @param string $function The function to call.
	 * @param array $data Any data to pass to the function
	 * @param boolean $check_priority Should we check the priority on other modules
	 * @return An array of module results (usually HTML)
	 */
	function call_function($modules, $function, $data=array(), $check_priority=false) {
		//setup ACL variable
		$_SESSION['acls'] = isset($_SESSION['acls']) ? $_SESSION['acls'] : array();
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
				$always_allowed = array("hook_auth", "hook_login", "view_login", "view_logout");
				// do not check permissions under the following conditions
				if ($function == 'hook_permission_check' || isset($_SESSION['acls']['system']['admin'])) {
					$allow = true;
				} elseif (array_key_exists('skip_auth', $_SESSION) && $_SESSION['skip_auth']) {
					$allow = true;
				} elseif (in_array($function, $always_allowed)) {
					$allow = true;
				} else {
					$acl_data = array("function"=>$function, "acls"=>$_SESSION['acls']);
					$permission_check = $this->call_function($module, 'hook_permission_check', $acl_data);
					// if the module has not specified a permission check, assume an authenticated function
					$allow = isset($permission_check[$module]) ? $permission_check[$module] : false;
				}
				
				//if ($function != 'hook_permission_check' && $allow==false) {
				//	echo " " . $allow . " " . $function . " " . $module . "<br />" ;
				//}
				if (!$allow) {
					if (strpos($function, "view_") === 0) {
						$output->data = $this->error("You do not have permission to access this function");
						$modres = $output;
						$res[$module] = $modres;
					}
					continue;
				}

				if (count($data)) {
					// TODO: Why do we have this switch statement
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
				if (isset($modres)) {
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

				if (isset($users_groups[$user_id]) && count($users_groups[$user_id]) > 0) {
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
