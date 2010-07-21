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
 * Cron.php
 *
 * Squeebop
 */

class Cron extends Template {
	var $conn;
	var $dobj;
	var $name = "Cron";
	var $description = "Scheduling";

	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_permission_check()
	 */
	function hook_permission_check($data) {
		if (isset($data['acls']['system']['admin'])) {
			return true;
		}
		return false;
	}
	
	/**
	 * The Top Menu hook function.
	 *
	 * Displays the module in the main menu.
	 */
	function hook_top_menu() {
		return null;
	}

	/**
	 * The Admin Menu hook function.
	 *
	 * Displays links to administrative functions on the admin page.
	 */
	function hook_admin_tools() {
		return null;
	}

	function hook_roles() {
		return null;
	}

	/**
	 * Schedulor
	 *
	 * Get any reports that are to be executed this hour. Mark them as 'to be executed' for the executor
	 */
	function view_scheduler() {
		$hour = date("H");
		$dayofweek = date("N");
		$day = date("j");

		$last_day_of_month = date("j", strtotime("-1 day", strtotime("+1 month", strtotime(time("Y-m")))));

		$where_hourly = "execute_hourly=true";
		$where_daily = "(execute_daily=true AND execute_hour='$hour')";
		$where_weekly = "(execute_weekly=true AND execute_dayofweek='$dayofweek' AND execute_hour='$hour')";
		$where_monthly = "(execute_monthly=true AND (execute_day='$day' OR (('$day'='$last_day_of_month') AND execute_day>'$last_day_of_month')) AND execute_hour='$hour')";

		$templates = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM templates WHERE execute=true AND ($where_hourly OR $where_daily OR $where_weekly OR $where_monthly);"));

		if (!empty($templates)) {
			echo count($templates)." templates requiring execution\n";

			foreach ($templates as $template_tmp) {
				$this->dobj->db_query($this->dobj->update(array("execute_now"=>"t", "execution_queued"=>"t"), "template_id", $template_tmp['template_id'], "templates"));
			}
		} else {
			echo "no templates requiring execution\n";
		}
	}

	/**
	 * Executor
	 *
	 * Take a single report that is to be run, and run it. Once croncall.php is complete it will run itself again.
	 */
	function view_executor() {
		$templates = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE execute_now=true LIMIT 1;"));

		if (empty($templates)) return false;

		$this->dobj->db_query($this->dobj->update(array("execute_now"=>"f", "execution_queued"=>"f", "execution_executing"=>"t"), "template_id", $templates['template_id'], "templates"));

		$this->call_function("tabular", "view_execute_scheduled", array($templates));

		$this->dobj->db_query($this->dobj->update(array("execute_now"=>"f", "execution_executing"=>"f"), "template_id", $templates['template_id'], "templates"));

		return true;
	}
}

class Cron_View extends Template_View {
	/**
	 * Check Cron
	 *
	 * Check if there are any templates that need to be executed at this time
	 */
	function view_check_cron() {
	}
}