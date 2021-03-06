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
 * inline_historical.php
 *
 * The administration module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class Inline_historical extends Catalogue {
	var $dobj;
	var $name = "Inline Historical";
	var $description = "Looks for, and updates the queries for an inline start_date and end_date field.";
	var $module_group = "Templates";
	
	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_permission_check()
	 */
	function hook_permission_check($data) {
		//TODO: This module is incomplete
		return false;
	}
	
	/* The Top Menu hook function.
	 * Displays the module in the main menu. Or menu of primary functions.
	 */
	function hook_top_menu() {
		return null;
	}

	function hook_module_settings() {
		return "inline_historical/settings";
	}

	function hook_workspace() {
		return false;
	}

	function view_settings() {
		if ($_REQUEST['data']) {
			$this->delete_settings('inline_historical');
			$this->save_settings('inline_historical', $_REQUEST['data']['inline_historical']);
		}
		$settings = $this->get_settings('inline_historical');
		$dbs = $this->get_databases();
		$output = Inline_historical_View::view_settings($dbs, $settings);
		return $output;
	}
}

class Inline_historical_View {

	function view_settings($dbs, $settings) {
		$output->title = "Inline Historical Settings";
		$output->data .= "<div dojoType='dijit.layout.ContentPane' layoutAlign='client'>";
		$output->data .= $this->f("inline_historical/settings");
		foreach ($dbs as $j => $all) {
			$on = false;
			foreach ($settings as $i => $setting) {
				if ($setting['key'] == $all['name']) {
					$on = true;
					unset($settings['key']);
					break;
				}
			}
			$output->data .= $this->i("data[inline_historical][".$all['name']."]", array("label"=>ucwords($all['human_name']), "type"=>"checkbox", "default"=>$on));
		}
		$output->data .= $this->i("save", array("type"=>"submit", "label"=>"Save Settings"));
		$output->data .= $this->f_close();
		$output->data .= "</div>";
		return $output;
	}
}
?>
