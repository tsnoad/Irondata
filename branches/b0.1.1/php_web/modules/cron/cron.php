<?php

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

	/**
	 * Check Cron
	 *
	 * Check if there are any templates that need to be executed at this time
	 */
	function view_check_cron() {
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
				$this->call_function("tabular", "view_execute_manually", array($template_tmp));
			}
		} else {
			echo "no templates requiring execution\n";
		}
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