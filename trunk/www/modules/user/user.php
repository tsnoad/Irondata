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
 * User.php
 *
 * The user authenitcation module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class User extends Modules {
	var $dobj;
	var $name = "Users";
	var $description = "User and group management";
	var $module_group = "Core";

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_permission_check()
	 */
	function hook_permission_check($data) {
		switch ($data['function']) {
			case "hook_top_menu":
			case "hook_pagetitle":
			case "hook_auth":
			case "hook_login":
			case "hook_login_priority":
			case "view_login":
			case "view_logout":
			case "set_session_report_acls":
			case "hook_access_users":
				// The login / logout pages and links always available
				if (isset($data['acls']['system']['login'])) {
					return true;
				}
				return false;
				break;
			default:
				if (isset($data['acls']['system']['admin'])) {
					return true;
				}
				return false;
				break;
		}
		return false;
	}
	
	/* The Top Menu hook function.
	 * Displays the module in the main menu. Or menu of primary functions.
	 */
	function hook_top_menu() {
		return array(
			"logout" => array($this->l("user/logout", "Logout"), 4)
			);
	}

	function hook_pagetitle() {
		return "User";
	}

	function hook_admin_tools() {
// 		$admin_tools[] = array("user/users", "Users");
// 		$admin_tools[] = array("user/groups", "Groups");
		$admin_tools[] = array("user/access", "Access");
// 		$admin_tools[] = array("user/default_access", "Default Access - New User");
		return $admin_tools;
	}

	function hook_roles() {
		return array(
			"login" => array("Login", "Permission to log into Irondata"),
			"admin" => array("Admin", "Permission to administer modules, users and permissions")
			);
	}

	function hook_roles_priority() {
		return array(1, false);
	}

	/* The Menu hook function.
	 * Displays items in the side bar. This can be dependant on the actual URL used.
	 */
	function hook_menu() {
		return null;
	}

	function hook_workspace() {
		return null;
	}

	function hook_auth() {
		if (session_id() == "") {
			session_start();
		}
		/* Skip login views */
		if (
			($this->module == 'user' && $this->action == 'login') ||
			($this->module == 'admin' && $this->action == 'run_background') ||
			($this->module == 'cron')
		) {
			return true;
		}
		if (!$_SESSION['user']) {
			$_SESSION['premodule'] = $this->module;
			$_SESSION['preaction'] = $this->action;
			$this->redirect('user/login');
			die();
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Called by User::view_login. Attempts to check the provided login details against users stored in the database. If successful, saves the user's details and acl to the session variable and redirects to the appropriate page.
	 */
	function hook_login($usr=null, $pwd=null) {
		$user = "SELECT * FROM users WHERE user_id='".$usr."'";
		$user = $this->dobj->db_fetch($this->dobj->db_query($user));

		//if the provided password matches the one in the database
		if ($user['password'] == md5($pwd)) {
			//get permissions for the user and their groups
			$sys_acls_users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM system_acls_users WHERE user_id='".$usr."' AND access=true;"));
			$sys_acls_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT g.* FROM users_groups ug INNER JOIN system_acls_groups g ON (g.group_id=ug.group_id) WHERE ug.user_id='".$usr."' AND g.access=true;"));

			//convert the user's and user's groups acls into a single array telling if the user has permission or not for each role.
			$sys_acls_query = array_merge((array)$sys_acls_users_query, (array)$sys_acls_groups_query);
			foreach ($sys_acls_query as $acl_tmp) {
				if (empty($acl_tmp)) continue;

				$role_id = $acl_tmp['role'];

				$acls['system'][$role_id] = true;
			}

			//save in the session
			$_SESSION['user'] = "user_".$user['user_id'];
			$_SESSION['acls']['system'] = $acls['system'];

			//set the report acl
			$this->call_function("user", "set_session_report_acls", array());

			//redirect to front page or last page
			if (isset($_SESSION['premodule']) && isset($_SESSION['preaction'])) {
				$this->redirect($_SESSION['premodule'].'/'.$_SESSION['preaction']);
			} else {
				$this->redirect('/');
			}
		}
	}

	/**
	 * Always try to log in with the user module first: The database is faster than ldap.
	 */
	function hook_login_priority() {
		return array(1, false);
	}

	/**
	 * Called by User::view_access to fetch users, groups, group memberships by user - then translate them to usable arrays for the acl.
	 */
	function hook_access_users() {
		//get data from the database
		$users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM users;"));
		$groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM groups;"));
		$users_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM users_groups;"));

		//rekey by group id
		foreach ($groups_query as $group) {
			$group_name = $group['group_id'];
			$group_id = "user_".$group_name;
			
			$groups[$group_id] = $group_name;

			//no one should be able to revoke the admin user's permissions
			if ($group_id == "user_admin") {
				$disabled['groups'][$group_id] = true;
			}
		}

		//rekey by user id
		foreach ($users_query as $user) {
			$user_id = "user_".$user['user_id'];
			$user_name = $user['given_names']." ".$user['family_name'];

			$users[$user_id] = $user_name;

			//no one should be able to revoke the admin group's permissions
			if ($user_id == "user_admin") {
				$disabled['users'][$user_id] = true;
			}
		}

		//create an array of group memberships by user_id
		foreach ($users_groups_query as $user_group) {
			$user_id = "user_".$user_group['user_id'];
			$group_id = "user_".$user_group['group_id'];

			$users_groups[$user_id][] = $group_id;
		}

		return array(
			"users" => $users,
			"groups" => $groups,
			"users_groups" => $users_groups,
			"disabled" => $disabled
			);
	}

	/**
	 * Called by User::view_access to fetch user and group acls
	 */
	function hook_access_acls() {
		//get data from the database
		$acls_users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT 'user_'||user_id as user_id, role, access FROM system_acls_users WHERE access=true;"));
		$acls_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT 'user_'||group_id as group_id, role, access FROM system_acls_groups WHERE access=true;"));

		return array(
			"acls" => array(
				"users" => $acls_users_query,
				"groups" => $acls_groups_query
				)
			);
	}

	/**
	 * Called by User::view_access_submit to save edited acl
	 */
	function hook_access_submit($acls) {
		//get existing aces from the database
		$acls_users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT user_id as user_id, role, access FROM system_acls_users WHERE access=true;"));
		$acls_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT group_id as group_id, role, access FROM system_acls_groups WHERE access=true;"));

		//convert acl into a readable array. from this we remove the aces that are unchanged. then we can delete the aces that are no longer selected
		foreach (array("users" => $acls_users_query, "groups" => $acls_groups_query) as $users_meta_key => $acls_delete_tmp) {
			if (!empty($acls_delete_tmp)) {
				foreach ($acls_delete_tmp as $acl_delete_tmp) {
					if ($users_meta_key == "users") {
						$user_id = $acl_delete_tmp['user_id'];
					} else if ($users_meta_key == "groups") {
						$user_id = $acl_delete_tmp['group_id'];
					}

					if ($user_id == "admin") continue;

					$role_id = $acl_delete_tmp['role'];

					$acls_delete[$users_meta_key][$user_id][$role_id] = true;
				}
			}
		}

		//loop through data we got back from the form
		if (!empty($acls)) {
			foreach ($acls as $users_meta_key => $users) {
				foreach ($users as $user_id => $roles) {
					//if this user isn't a database user, ignore
					if (substr($user_id, 0, 5) != "user_") continue;

					$user_id = substr($user_id, 5);

					foreach ($roles as $role_id => $access) {
						//if the current ace does not exist in the old acl, then add it to the list of new aces
						if (empty($acls_delete[$users_meta_key][$user_id][$role_id])) {
							$acls_insert[$users_meta_key][$user_id][$role_id] = true;
						//otherwise the ace has not been changed: remove it from the list of aces to delete
						} else {
							unset($acls_delete[$users_meta_key][$user_id][$role_id]);
						}
					}
				}
			}
		}

		//loop through the list of aces to remove
		if (!empty($acls_delete)) {
			foreach ($acls_delete as $users_meta_key => $users) {
				foreach ($users as $user_id => $roles) {
					foreach ($roles as $role_id => $access) {
						if (empty($access)) continue;

						if ($users_meta_key == "users") {
							//remove ace from the database
							$this->dobj->db_query("DELETE FROM system_acls_users WHERE user_id='$user_id' AND role='$role_id';");
						} else if ($users_meta_key == "groups") {
							//remove ace from the database
							$this->dobj->db_query("DELETE FROM system_acls_groups WHERE group_id='$user_id' AND role='$role_id';");
						}
					}
				}
			}
		}

		//loop through the list of aces to add
		if (!empty($acls_insert)) {
			foreach ($acls_insert as $users_meta_key => $users) {
				foreach ($users as $user_id => $roles) {
					foreach ($roles as $role_id => $access) {
						if (empty($access)) continue;

						if ($users_meta_key == "users") {
							//add the ace to the database
							$this->dobj->db_query($this->dobj->insert(array("user_id"=>$user_id, "role"=>$role_id), "system_acls_users"));
						} else if ($users_meta_key == "groups") {
							//add the ace to the database
							$this->dobj->db_query($this->dobj->insert(array("group_id"=>$user_id, "role"=>$role_id), "system_acls_groups"));
						}
					}
				}
			}
		}

		return;
	}
	
	function view_users() {
		$list = $this->dobj->db_fetch_all("SELECT user_id, group_id, given_names, family_name FROM users");
		$output = User_View::view_users($list);
		return $output;
	}

	function view_add_user() {
		//If we're being sent data, then the user has already visited this page, entered data, and hit save.
		if ($_REQUEST['data']) {
			//check data

			//error checks that are only needed when we're editing a user
			if ($this->id) {
// 				//make sure some fnool hasn't changed the value of the username field
// 				if ($_REQUEST['data']['user_id'] != $this->id) {
// 					$error[] = "Please do not change the username. We need it, or we'll explode...";
// 					$user['user_id'] = $this->id;
// 					$_REQUEST['data']['user_id'] = $this->id;
// 				}
			//checks that are only needed when we're adding a new user
			} else {
				if (empty($_REQUEST['data']['user_id'])) {
					$error[] = "Please enter a username.";
				} else {
					//check if this username is already in use
					$name_in_use_query = "SELECT * FROM users WHERE user_id='".$_REQUEST['data']['user_id']."'";
					$name_in_use = $this->dobj->db_fetch($name_in_use_query);
					if ($name_in_use) $error[] = "This username is already in use. Please select another.";
				}
			}

			if (empty($_REQUEST['data']['password'])) $error[] = "Please enter a password.";
			if (empty($_REQUEST['data']['group_id'])) $error[] = "Please select a group.";

			//if everything checks out
			if (empty($error)) {
				//md5 the password, for insertion into the database
				$_REQUEST['data']['password'] = md5($_REQUEST['data']['password']);

				//if there's an ID being transmitted, then we're editing an existing user
				if ($this->id) {
					//We use ****** as place holder for the password: since it's in md5 in the database, we don't know what the original is.
					//If the submitted password is ******, then leave the original in the database, unchanged
					if ($password_unencrypted == "******") unset($_REQUEST['data']['password']);

					//update the user
					$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "user_id", $this->id, "users"));
					//back to the administer users page
					$this->redirect("user/users");
				//otherwise we're creating a new user
				} else {
					//insert the user
					$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "users"));
					//back to the administer users page
					$this->redirect("user/users");
				}
			}
		}

		//if there's an ID being transmitted, then we're editing an existing user
		if ($this->id) {
			$query = "SELECT user_id, given_names, family_name, password, group_id FROM users WHERE user_id='".$this->id."'";
			$user = $this->dobj->db_fetch($query);
			//The password is stored as an md5 hash, so we can't put it into the textbox for the user to edit.
			//Instead, use six asterisks: it's an unreasonable thing to someone to tryu and use as a password
			//Then, if we dont get ****** back after the form is submitted, we know the user wants to change the password
			$user['password'] = "******";
		}

		//If the user has already tried to submit, and there was an error, don't just through away the data they entered. Be sure to put it back on the page
		if (!empty($error)) {
			$user['user_id'] = $_REQUEST['data']['user_id'];
			$user['given_names'] = $_REQUEST['data']['given_names'];
			$user['family_name'] = $_REQUEST['data']['family_name'];
			$user['password'] = $_REQUEST['data']['password'];
			$user['group_id'] = $_REQUEST['data']['group_id'];
		}

		//get all groups to show in the group membership dropdown
		$groups_query = "SELECT * FROM groups;";
		$groups_tmp = $this->dobj->db_fetch_all($groups_query);
		//reorganise the data, so dojo can use it in a filteringselect
		foreach ($groups_tmp as $group_tmp) {
			$groups[$group_tmp['group_id']] = $group_tmp['group_id'];
		}

		$output = User_View::view_add_user($groups, $user, $this->id, $error);
		return $output;
	}

	function view_delete_user() {
		$query = "DELETE FROM users WHERE user_id='".$this->id."'";
		$this->dobj->db_query($query);
		$this->redirect("user/users");
	}

	function view_groups() {
		$list = $this->dobj->db_fetch_all("SELECT group_id FROM groups");
		$output = User_View::view_groups($list);
		return $output;
	}

	function view_add_group() {
		//If we're being sent data, then the user has already visited this page, entered data, and hit save.
		if ($_REQUEST['data']) {
			//check data

			if (empty($_REQUEST['data']['group_id'])) {
				$error[] = "Please enter a group name.";
			} else {
				//check if this groupname is already in use
				$name_in_use_query = "SELECT * FROM groups WHERE group_id='".$_REQUEST['data']['group_id']."' AND group_id!='".$this->id."';";
				$name_in_use = $this->dobj->db_fetch($name_in_use_query);
				if ($name_in_use) $error[] = "This group name is already in use. Please select another.";
			}

			//error checks that are only needed when we're editing a group
			if ($this->id) {
			//checks that are only needed when we're adding a new group
			} else {
			}

			//if everything checks out
			if (empty($error)) {
				//if there's an ID being transmitted, then we're editing an existing group
				if ($this->id) {
					//update the group
					$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "group_id", $this->id, "groups"));
					//back to the administer groups page
					$this->redirect("user/groups");
				//otherwise we're creating a new group
				} else {
					//insert the user
					$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "groups"));
					//back to the administer groups page
					$this->redirect("user/groups");
				}
			}
		}

		//if there's an ID being transmitted, then we're editing an existing group
		if ($this->id) {
			$query = "SELECT group_id FROM groups WHERE group_id='".$this->id."'";
			$user = $this->dobj->db_fetch($query);
		}

		//If the user has already tried to submit, and there was an error, don't just through away the data they entered. Be sure to put it back on the page
		if (!empty($error)) {
			$user['group_id'] = $_REQUEST['data']['group_id'];
		}

		$output = User_View::view_add_group($user, $this->id, $error);
		return $output;
	}

	function view_delete_group() {
		$query = "DELETE FROM groups WHERE group_id='".$this->id."'";
		$this->dobj->db_query($query);
		$this->redirect("user/groups");
	}

	/**
	 * Login page. Calls hook_login() to do actual authentication.
	 */
	function view_login() {
		$message = "";
		$query = $this->dobj->db_fetch("SELECT * FROM settings WHERE module_id='admin' AND key='organisation'");
		$company = $query['value'];
		$query = $this->dobj->db_fetch("SELECT * FROM settings WHERE module_id='admin' AND key='url'");
		$url = $query['value'];

		if (isset($_REQUEST['data']['username'])) {
			//run each modules login function (in order) untill we find one that works. The module will set the session variables and redirect the user.
			$this->call_function("ALL", "hook_login", array($_REQUEST['data']['username'], $_REQUEST['data']['password']), true);

			//if no module can log us in, return an error message to the user
			$message = "Incorrect Login Details";
		}

		$output = User_View::view_login($company, $url, $message);
		return $output;
	}

	function view_logout() {
		unset($_SESSION);
		session_destroy();
		$this->redirect('user/login');
		die();
	}

	/**
	 * Sets report ACLs in $_SESSION. Called at login by User::hook_login and whenever a new report is cerated, so that the permissions for the new report are recognised
	 */
	function set_session_report_acls() {
		//only use set_session_report_acls in this module if a user from the database is logged in (as opposed to a ldap user)
		if (substr($_SESSION['user'], 0, 5) != "user_") return;

		//get the user_id
		$usr = substr($_SESSION['user'], 5);

		//get report permissions for the user and their groups
		$rep_acls_users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM report_acls_users WHERE user_id='".$usr."' AND access=true;"));
		$rep_acls_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT g.* FROM users_groups ug INNER JOIN report_acls_groups g ON (g.group_id=ug.group_id) WHERE ug.user_id='".$usr."' AND g.access=true;"));

		//convert the user's and user's groups acls into a single array telling if the user has permission or not for each role of each report.
		$rep_acls_query = array_merge((array)$rep_acls_users_query, (array)$rep_acls_groups_query);
		foreach ($rep_acls_query as $acl_tmp) {
			if (empty($acl_tmp)) continue;

			$template_id = $acl_tmp['template_id'];
			$role_id = $acl_tmp['role'];

			$acls['report'][$template_id][$role_id] = true;
		}

		//save the report acl in $_SESSION
		$_SESSION['acls']['report'] = $acls['report'];
	}

	/**
	 * ACL page. Shows all users and groups, from the database and ldap. Allows access levels to be set.
	 * Terminology:
	 * 	Access: The name for this page. Also, whether a user is allowed to perform the functions of a role
	 * 	ACE: An entry in the ACL. contains a user_id, a role, and allowed or denied
	 * 	ACL: The list of permissions by user and role
	 * 	Permission: Whether a user is allowed to perform the functions of a role
	 * 	Role: Something a user is allowed or not allowed to do
	 */
	function view_access() {
		//fetch users, groups, group memberships by user
		$users_query = $this->call_function("ALL", "hook_access_users", array());

		//combine results from all the different modules
		foreach ($users_query as $module => $users_query_tmp) {
			$users_tmp = array_merge((array)$users_tmp, (array)$users_query_tmp['users']);
			$groups_tmp = array_merge((array)$groups_tmp, (array)$users_query_tmp['groups']);
			$users_groups_tmp = array_merge((array)$users_groups_tmp, (array)$users_query_tmp['users_groups']);
			$disabled_tmp = array_merge((array)$disabled_tmp, (array)$users_query_tmp['disabled']);
		}

		//fetch user and group acls
		$acls_query = $this->call_function("ALL", "hook_access_acls", array());

		//combine results from all the different modules
		foreach ($acls_query as $module => $acls_query_tmp) {
			$acls_tmp = array_merge_recursive((array)$acls_tmp, (array)$acls_query_tmp['acls']);
		}

		//fetch role names and descriptions
		$roles_query = $this->call_function("ALL", "hook_roles", null, true);

		//combine results from all the different modules
		foreach ($roles_query as $module_roles_tmp) {
			$roles = array_merge((array)$roles, (array)$module_roles_tmp);
		}

		//create a unique id for each user and group, create associating arrays
		list($ids_r, $users, $groups, $user_groups, $disabled, $acls, $membership, $rows) = $this->acl_resort_users($users_tmp, $groups_tmp, $users_groups_tmp, $disabled_tmp, $acls_tmp);

		$titles = array(
			"User",
			"&nbsp;",
			"Memberships"
			);

		//generate markup
		$acl_markup = $this->render_acl($roles, $ids_r, $groups, $users, $acls, $user_groups, $disabled, $titles, $rows);

		return User_View::view_access($acl_markup);
	}

	/**
	 * Save the submitted ACL form. Calls hook_access_submit() to do data processing and saving.
	 */
	function view_access_submit() {
		if (empty($_REQUEST['data'])) return;

		//get the resorted id array
		$ids_r = json_decode(stripslashes($_REQUEST['data']['ids_r']), true);

		$acls_tmp = $_REQUEST['data'];

		//loop through each form input
		foreach ($acls_tmp as $acl_key_tmp => $acl_tmp) {
			//ignore anything but the permission checkboxes
			if (substr($acl_key_tmp, 0, 7) != "access_") continue;

			//extract the relevant data from the checkbox name: role, user id
			$acl_key = substr($acl_key_tmp, 7);
			$break_pos = strrpos($acl_key, "_");
			$role = substr($acl_key, 0, $break_pos);

			//get the resorted unique user id
			$user_id_tmp = substr($acl_key, $break_pos + 1);
			//work out the actual user id
			$user_id = $ids_r[$user_id_tmp][0];
			//user or group
			$user_meta = $ids_r[$user_id_tmp][1];

			//create an array of aces from the checkboxes that came back selected
			$acls[$user_meta][$user_id][$role] = true;
		}

		//call hook_access_submit to save the acl for each module
		$this->call_function("ALL", "hook_access_submit", array($acls));

		//redirect back to acl page
		$this->redirect("user/access");
	}
}

class User_View {
	function view_login($company, $url, $message = "") {
		$output->title = "Login";

		if (!empty($company)) {
			$output->data .= $this->p("This instance of IronData belongs to <strong>".$company."</strong>.");
		}

		$output->data .= $this->p("To view and manage this data please log into the system below.");
		$output->data .= $this->f("user/login");
		$output->data .= $this->i("data[username]", array("type"=>"text", "label"=>"Username", "dojo"=>"dijit.form.TextBox"));
		$output->data .= $this->i("data[password]", array("type"=>"password", "label"=>"Password", "dojo"=>"dijit.form.TextBox"));
		if ($message) {
			$output->data .= $this->p($message, "error");
		}
		$output->data .= $this->i("data[submit]", array("type"=>"submit", "label"=>"Submit", "dojo"=>"dijit.form.Button"));
		$output->data .= $this->f_close();

		return $output;
	}
	
	function view_users($list) {
		$output->title = "Users";
		$output->data .= "<div dojoType='dijit.layout.ContentPane' layoutAlign='client'>";
		$format = array(
			"user_id"=>array("type"=>"title"),
			"group_id"=>array("header"=>"Group Membership"),
			"given_names"=>array("header"=>"Given Name"),
			"family_name"=>array("header"=>"Family Name")
		);
		$functions = array(
			"add"=>"user/add_user",
			"delete"=>"user/delete_user",
			"edit"=>"user/add_user",
			"id"=>"user_id"
		);
		$output->data .= $this->quick_table($list, $format, $functions);
		$output->data .= "</div>";
		return $output;
	}
	
	function view_add_user($groups=array(), $user=false, $user_id=null, $error=null) {
		if ($user && $user_id) {
			$title = "Edit";
			$disabled = true;
			$url = "/".$user_id;
		} else {
			$title = "Add";
			$disabled = false;
			$url="";
		}
		$output->title = $title." User";
		$output->data .= "<div dojoType='dijit.layout.ContentPane' layoutAlign='client'>";

		if ($error) $output->data .= "<p style='color: #a40000; font-weight: bold;'>".implode("<br />", $error)."</p>";

// 		$output->data .= $this->f("user/add_user".$url);
		$output->data .= "<form method='post' dojoType='dijit.form.Form' action='".$this->webroot()."user/add_user".$url."' onSubmit='if (!this.isValid()) {this.validate(); return false;}' >";

		$output->data .= $this->i("data[user_id]", array("label"=>"Username", "value"=>$user["user_id"], "disabled"=>$disabled, "dojoType"=>"dijit.form.ValidationTextBox", "required"=>"true", "invalidMessage"=>"Please enter a username."));
		$output->data .= $this->i("data[family_name]", array("label"=>"Family Name", "value"=>$user["family_name"], "dojoType"=>"dijit.form.TextBox"));
		$output->data .= $this->i("data[given_names]", array("label"=>"Given Names", "value"=>$user["given_names"], "dojoType"=>"dijit.form.TextBox"));
		$output->data .= $this->i("data[password]", array("label"=>"Password", "type"=>"password", "value"=>$user["password"], "dojoType"=>"dijit.form.ValidationTextBox", "required"=>"true", "invalidMessage"=>"Please enter a password."));
		$output->data .= $this->i("data[group_id]", array("label"=>"Group Membership", "type"=>"select", "default"=>$user["group_id"], "options"=>$groups, "dojoType"=>"dijit.form.FilteringSelect"));
		$output->data .= $this->i("submit", array("label"=>"Save User", "type"=>"submit", "value"=>"Save User", "dojoType"=>"dijit.form.Button"));
		$output->data .= $this->f_close();
		$output->data .= "</div>";
		return $output;
	}
	
	function view_groups($list) {
		$output->title = "Groups";
		$output->data .= "<div dojoType='dijit.layout.ContentPane' layoutAlign='client'>";
		$format = array(
			"group_id"=>array("type"=>"title")
		);
		$functions = array(
			"add"=>"user/add_group",
			"delete"=>"user/delete_group",
			"edit"=>"user/add_group",
			"id"=>"group_id"
		);
		$output->data .= $this->quick_table($list, $format, $functions);
		$output->data .= "</div>";
		return $output;
	}
	
	function view_add_group($user=false, $group_id=null, $error=null) {
		if ($user && $group_id) {
			$title = "Edit";
			$url = "/".$group_id;
		} else {
			$title = "Add";
			$url="";
		}
		$output->title = $title." Group";
		$output->data .= "<div dojoType='dijit.layout.ContentPane' layoutAlign='client'>";

		if ($error) $output->data .= "<p style='color: #a40000; font-weight: bold;'>".implode("<br />", $error)."</p>";

// 		$output->data .= $this->f("user/add_group".$url);
		$output->data .= "<form method='post' dojoType='dijit.form.Form' action='".$this->webroot()."user/add_group".$url."' onSubmit='if (!this.isValid()) {this.validate(); return false;}' >";

		$output->data .= $this->i("data[group_id]", array("label"=>"Group Name", "value"=>$user["group_id"], "dojoType"=>"dijit.form.ValidationTextBox", "required"=>"true", "invalidMessage"=>"Please enter a group name."));
		$output->data .= $this->i("submit", array("label"=>"Save Group", "type"=>"submit", "value"=>"Save Group", "dojoType"=>"dijit.form.Button"));
		$output->data .= $this->f_close();
		$output->data .= "</div>";
		return $output;
	}

	function view_access($acl_markup) {
		$output->title = "Access";

		$output->data = $this->f("user/access_submit");
		$output->data .= $acl_markup;
		$output->data .= $this->i("submit", array("label"=>"Save", "type"=>"submit", "value"=>"Save", "dojoType"=>"dijit.form.Button"));
		$output->data .= $this->f_close();

		return $output;
	}
}

?>
