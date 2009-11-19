<?php

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

	/* The Top Menu hook function.
	 * Displays the module in the main menu. Or menu of primary functions.
	 */
	function hook_top_menu() {
		return array(
			"logout" => "<a href='".$this->webroot()."user/logout'>Logout</a>"
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

	function hook_header() {
		return $this->l("user/logout", "Logout");
	}

	function hook_roles() {
		return array("manage users", "manage permissions");
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

	function view_permissions() {
		$permissions = $this->call_function("ALL", "hook_roles");
		$output = User_View::view_permissions($permissions);
		return $output;
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

	function hook_auth() {
		session_start();
		/* Skip login views */
		if (
			($this->module == 'user' && $this->action='login') || 
			($this->module == 'admin' && $this->action='run_background') || 
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

	function hook_login($usr=null, $pwd=null) {
		$user = "SELECT * FROM users WHERE user_id='".$usr."'";
		$user = $this->dobj->db_fetch($this->dobj->db_query($user));

		if ($user['password'] == md5($pwd)) {
			return $usr;
		}
	}

	function hook_login_priority() {
		return array(1, false);
	}

	function view_login() {
		$query = $this->dobj->db_fetch("SELECT * FROM settings WHERE module_id='admin' AND key='organisation'");
		$company = $query['value'];
		$query = $this->dobj->db_fetch("SELECT * FROM settings WHERE module_id='admin' AND key='url'");
		$url = $query['value'];

		if ($_REQUEST['data']['username']) {
			$aux = $this->call_function("ALL", "hook_login", array($_REQUEST['data']['username'], $_REQUEST['data']['password']), true);

			if (!empty($aux)) {
				foreach ($aux as $user) {
					$_SESSION['user'] = $user;
					$this->redirect($_SESSION['premodule'].'/'.$_SESSION['preaction']);
					die();
				}
			}

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

	function view_access() {
		$users_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM users;"));
		$groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM groups;"));
		$users_groups_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM users_groups;"));

		foreach ($groups_query as $group) {
			$group_id = $group['group_id'];
			$groups[$group_id] = $group_id;
		}

		foreach ($users_query as $user) {
			$user_id = $user['user_id'];
			$user_name = $user['given_names']." ".$user['family_name'];

			$users[$user_id] = $user_name;
		}

		foreach ($users_groups_query as $user_group) {
			$user_id = $user['user_id'];
			$group_id = $group['group_id'];

			$users_groups[$user_id][] = $group_id;
		}

		$aux = $this->call_function("ALL", "hook_access_users", array());

		$aux['user']['users'] = $users;
		$aux['user']['groups'] = $groups;
		$aux['user']['users_groups'] = $users_groups;

		unset($users, $groups, $users_groups);


		foreach ($aux as $module => $aux_tmp) {
			$users = array_merge((array)$users, (array)$aux_tmp['users']);
			$groups = array_merge((array)$groups, (array)$aux_tmp['groups']);
			$users_groups = array_merge((array)$users_groups, (array)$aux_tmp['users_groups']);
		}

		return User_View::view_access($users, $groups, $users_groups);
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

	function view_access($users_tmp, $groups_tmp, $users_groups_tmp) {
		$things = array(
			"login",
			"access",
			"databases",
			"reports"
			);

		$id = 1;

		foreach ($users_tmp as $user_id_tmp => $user_tmp) {
			$users[$id] = $user_tmp;

			$ids['users'][$user_id_tmp] = $id;

			$id ++;
		}

		foreach ($groups_tmp as $group_id_tmp => $group_tmp) {
			$groups[$id] = $group_tmp;

			$ids['groups'][$group_id_tmp] = $id;

			$id ++;
		}

		foreach ($users_groups_tmp as $user_id_tmp => $user_groups_tmp) {
			foreach ($user_groups_tmp as $group_id_tmp) {
				$user_id = $ids['users'][$user_id_tmp];
				$group_id = $ids['groups'][$group_id_tmp];

				$users_groups[$user_id][] = $group_id;
			}
		}

		$user_groups = $users_groups;

		$titles = array(
			"User",
			"&nbsp;",
			"Memberships"
			);

		foreach (array("Group"=>$groups, "User"=>$users) as $users_meta_key => $users_meta) {
			foreach ($users_meta as $user_id => $user) {
				unset($membership);

				if (count($user_groups[$user_id])) {
					foreach ($user_groups[$user_id] as $group_id) {
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

		$output->data .= $this->render_acl($things, $groups, $users, $permissions, $user_groups, $disabled, $titles, $rows);

		return $output;
	}
}

?>
