<?php

/**
 * Ldap.php
 *
 * The LDAP authenitcation module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class Ldap extends User {
	var $dobj;
	var $name = "LDAP";
	var $description = "LDAP";
	var $ldaphost = '192.168.25.38';

	function hook_admin_tools() {
		return null;
	}

	function hook_login($usr=null, $pwd=null) {
		return $this->ldapAuthenticated($usr, $pwd);
	}

	function hook_login_priority() {
		return null;
	}

	function ldapAuthenticated($usr=null, $pwd=null) {
		if (!$pwd) {
			return false;
		}
		#Connect to the ldap server
		$ds=ldap_connect($this->ldaphost);
		if ($ds) {
			#Bind anonymously. Used to find the dn of the user. 
			$r = ldap_bind($ds);
			#Search for the user. 
			$sr = ldap_search($ds, "o=ieaust", "cn=$usr");

			#Get all results. 
			$info = ldap_get_entries($ds, $sr);

			foreach ($info as $i => $info_row) {
				#Do not attempt to bind if the record has no DN (ie system records).
				if (!($info_row['dn'])) {
					continue;
				}
				$fulldn = $info_row['dn'];

				#Check that the supplied user can bind/login with the supplied password. 
				if (@ldap_bind($ds, $fulldn, $pwd)) {
					ldap_close($ds);
					#Success. Return the users group membership
					return $usr;
				}
			}
			ldap_close($ds);
		}

		#All bindings failed (or noone by that username).
		return false;
	}

	function hook_recipients($template_id, $template_recipients=null) {
		$recipients_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM ldap_recipients WHERE template_id='".$template_id."';"));

		if (!empty($recipients_query)) {
			foreach ($recipients_query as $recipient) {
				$uid = $recipient['uid'];

				$recipients[$uid] = true;
			}
		}

		if (empty($recipients)) {
			return null;
		}

		$search_string = "(|(uid=".implode(")(uid=", array_keys($recipients))."))";

		#Connect to the ldap server
		$ds=ldap_connect($this->ldaphost);
		if ($ds) {
			$r = ldap_bind($ds);
			$sr = ldap_search($ds, "o=ieaust", $search_string, array("uid", "fullname", "mail"));

			$ldap_entries = ldap_get_entries($ds, $sr);

			foreach ($ldap_entries as $entry) {
				if (empty($entry['uid'][0])) continue;
				if (empty($entry['fullname'][0])) continue;
				if (empty($entry['mail'][0])) continue;

				$uid = $entry['uid'][0];
				$name = $entry['fullname'][0];
				$email = strtolower($entry['mail'][0]);

				$users[] = array($name, $email);
			}

			ldap_close($ds);
		}

		return $users;
	}

	function hook_recipient_selector() {
		$recipients_count = $this->dobj->db_fetch($this->dobj->db_query("SELECT count(uid) FROM ldap_recipients WHERE template_id='".$this->id."';"));

		$recipients_count = $recipients_count['count'];

		if ($recipients_count == "1") {
			$recipients = "1 recipient";
		} else {
			$recipients = "$recipients_count recipients";
		}

		return "
			<div class='input' style='margin-left: 30px;'><button type='submit' name='data[ldap]' id='ldap_recipients' value='ldap' dojoType='dijit.form.Button' />LDAP</button><span id='ldap_recipients_count' style='padding-left: 20px; vertical-align: middle; color: #555753; font-size: 10pt; font-style: italic;'>$recipients</span></div>
			";
	}

	function hook_access_users() {
		#Connect to the ldap server
		$ds=ldap_connect($this->ldaphost);
		if ($ds) {
			$r = ldap_bind($ds);
			$sr = ldap_search($ds, "o=ieaust", "(&(mail=*)(fullname=*))", array("uid", "fullname", "sn", "ou"));

			ldap_sort($ds, $sr, "sn");

			$ldap_entries = ldap_get_entries($ds, $sr);

			foreach ($ldap_entries as $entry) {
				if (empty($entry['uid'][0])) continue;
				if (empty($entry['fullname'][0])) continue;

				$uid = $entry['uid'][0];
				$name = $entry['fullname'][0];

				if ($entry['ou']['count'] > 1) {
					foreach ($entry['ou'] as $unit_index => $unit) {
						if ($unit_index == "count") continue;
						if (empty($unit)) continue;

						$units[$unit] = $unit;

// 						$unit_entries[$unit][$uid] = $uid;
						$uid_units[$uid][] = $unit;
					}
				} else if ($entry['ou']['count'] > 0 && !empty($entry['ou'][0])) {
					$unit = strtolower($entry['ou'][0]);
					$units[$unit] = $unit;

// 					$unit_entries[$unit][$uid] = $uid;
					$uid_units[$uid][] = $unit;
				}

				$users[$uid] = $name;
			}

			ldap_close($ds);

			asort($units);

			return array(
				"users" => $users,
				"groups" => $units,
				"users_groups" => $uid_units
				);
		}

		return;
	}

	function view_recipient_selector() {
		if (!empty($_REQUEST['data'])) {
			$recipients_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM ldap_recipients WHERE template_id='".$this->id."';"));

			if (!empty($recipients_query)) {
				foreach ($recipients_query as $recipient) {
					$uid = $recipient['uid'];

					$recipients[$uid] = true;
				}
			}

			$recipients_subtraction = $recipients;

			foreach ($_REQUEST['data'] as $key_tmp => $data_tmp) {
				if (substr($key_tmp, 0, 10) != "recipient_") continue;

				$uid = substr($key_tmp, 10);

				//user not in database table: add them
				if (empty($recipients[$uid])) {
					$this->dobj->db_query($this->dobj->insert(array("template_id"=>$this->id, "uid"=>$uid), "ldap_recipients"));

					$recipients[$uid] = true;

				//user already in database table: no change
				} else {
				}

				unset($recipients_subtraction[$uid]);
			}

			if (!empty($recipients_subtraction)) {
				foreach ($recipients_subtraction as $uid => $recipient) {
					//user in database table, but not selected: remove them
					$this->dobj->db_query("DELETE FROM ldap_recipients WHERE template_id='".$this->id."' AND uid='$uid';");
				}
			}

			unset($recipients);

			$this->redirect("tabular/add/".$this->id."/execution");
		}

		$recipients_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM ldap_recipients WHERE template_id='".$this->id."';"));

		if (!empty($recipients_query)) {
			foreach ($recipients_query as $recipient) {
				$uid = $recipient['uid'];

				$recipients[$uid] = true;
			}
		}

		#Connect to the ldap server
		$ds=ldap_connect($this->ldaphost);
		if ($ds) {
			$r = ldap_bind($ds);
			$sr = ldap_search($ds, "o=ieaust", "(&(mail=*)(fullname=*))", array("uid", "fullname", "sn", "mail", "ou"));

			ldap_sort($ds, $sr, "sn");

			$ldap_entries = ldap_get_entries($ds, $sr);

			foreach ($ldap_entries as $entry) {
				if (empty($entry['uid'][0])) continue;
				if (empty($entry['fullname'][0])) continue;
				if (empty($entry['mail'][0])) continue;

				$uid = $entry['uid'][0];
				$name = $entry['fullname'][0];
				$email = strtolower($entry['mail'][0]);

				if ($entry['ou']['count'] > 1) {
					foreach ($entry['ou'] as $unit_index => $unit) {
						if ($unit_index == "count") continue;
						if (empty($unit)) continue;

						$units[$unit] = $unit;

						$unit_entries[$unit][$uid] = $uid;
						$uid_units[$uid][$unit] = $unit;
					}
				} else if ($entry['ou']['count'] > 0 && !empty($entry['ou'][0])) {
					$unit = strtolower($entry['ou'][0]);
					$units[$unit] = $unit;

					$unit_entries[$unit][$uid] = $uid;
					$uid_units[$uid][$unit] = $unit;
				}

				$users[$uid] = array($name, $email, implode(", ", (array)$uid_units[$uid]), !empty($recipients[$uid]));
			}

			ldap_close($ds);
		}

		$output = Ldap_View::view_recipient_selector($users);
		return $output;
	}
}

class Ldap_View {
	function view_recipient_selector($users) {
		$output->title = "LDAP Users";

		$output->data .= $this->f("ldap/recipient_selector/".$this->id, "dojoType='dijit.form.Form'");
		$output->data .= "
			<div class='reports'>
				<table cellpadding='0' cellspacing='0'>
					<tr>
						<th>Name</th>
						<th>Email Address</th>
						<th>Units</th>
						<th>&nbsp;</th>
					</tr>
					";
		foreach ($users as $uid => $user) {
			$output->data .= "
					<tr>
						<td>".$user[0]."</td>
						<td>".$user[1]."</td>
						<td>".$user[2]."</td>
						<td><input name='data[recipient_$uid]' type='checkbox' ".($user[3] ? "checked" : "")." /></td>
					</tr>
					";
		}
		$output->data .= "
				</table>
			</div>
			";

		$output->data .= "
			<div class='input'>
				<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."tabular/add/".$this->id."/execution\"; return false;' name='cancel' />Cancel</button><button type='submit' value='Save' dojoType='dijit.form.Button' name='save' />Save</button>
			</div>
			";
		$output->data .= $this->f_close();

		return $output;
	}
}

?>
