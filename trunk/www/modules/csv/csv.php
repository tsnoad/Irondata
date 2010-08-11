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
 * csv.php
 *
 * The export to csv module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class Csv extends Template {
	var $dobj;
	var $name = "CSV";
	var $description = "Export a report in Comma Seperated Value (CSV) format.";
	
	/**
	 * (non-PHPdoc)
	 * @see inc/Modules::hook_permission_check()
	 */
	function hook_permission_check($data) {
		//admin will automatically have access. No need to specify
		switch ($data['function']) {
			case "hook_admin_tools":
			case "hook_roles":
				if (isset($data['acls']['system']['admin'])) {
					return true;
				}
				break;
			default:
				//only people logged in can access these functions
				if (isset($data['acls']['system']['login'])) {
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
		return null;
	}

	function hook_admin_tools() {
		return null;
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

	/* The Template hook function.
	 * Is this module available within the Templates
	 */
	function hook_export_entry() {
		return array(
			"label"=>"Export as CSV",
			"module"=>"csv",
			"callback"=>"export"
		);
	}

	function hook_roles() {
		return null;
	}

	/**
	 * Does this module extend the publish functionality
	 */
	function hook_publish($data=array()) {
		if (!isset($this->id)) {
			$this->id = $data["template_id"];
		}
		if (isset($data['demo']) && $data['demo'] == true) {
			return null;
		} else {
			$default = $this->dobj->db_fetch("SELECT publish_csv FROM templates WHERE template_id='".$this->id."'");
			$default = $default['publish_csv'] == 't' ? true: false;
			return array("name"=>$this->name, "default"=>$default);
		}
	}
	
	function hook_save_publish() {
		$save = array();
		if (isset($_REQUEST['data']['publish_csv'])) {
			$save['publish_csv'] = "t";
		} else {
			$save['publish_csv'] = "f";
		}
		unset($_REQUEST['data']['publish_csv']);
		$this->dobj->db_query($this->dobj->update($save, "template_id", $this->id, "templates"));
	}
	
	function hook_get_or_generate($data=array()) {
		$saved_report_id = $data["saved_report_id"];
		$demo = $data["demo"];
		$template = $data['template'];
		
		if (empty($saved_report_id)) return;
		if ($demo) return;

		if (!is_dir($this->sw_path.$this->tmp_path)) {
			mkdir($this->sw_path.$this->tmp_path);
		}

		//check if the csv document exists
		$saved_report = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM saved_reports r LEFT OUTER JOIN csv_documents c ON (c.saved_report_id=r.saved_report_id) WHERE r.saved_report_id='$saved_report_id' LIMIT 1;"));

		//if the csv document does not exist, create it, and return data about where it can be found
		if (empty($saved_report['csv_document_id']) || !is_file($saved_report['txt_path'])) {
			$saved_report = $this->view_export(array($saved_report['report'], $saved_report_id));
		}

		//if it does, simply return data about where it can be found
		return array('object'=>null, 'url'=>$saved_report['txt_url']);
	}
	
	function view_export($data=array()) {
		$results_foo = array();
		$foo_json = $data[0];
		$saved_report_id = $data[1];
		$csv_data = "";

		$path_base = $this->sw_path.$this->tmp_path;
		$url_base = $this->web_path.$this->tmp_path;

		$txt_path = $path_base."csv_$saved_report_id.txt";
		$txt_url = $url_base."csv_$saved_report_id.txt";

		$insert = array(
			"saved_report_id" => $saved_report_id,
			"created" => "now()",
			"txt_path" => $txt_path,
			"txt_url" => $txt_url,
		);

		$this->dobj->db_query($this->dobj->insert($insert, "csv_documents"));

		$results = json_decode($foo_json, true);

		if (is_array($results)) {
			//re-organise the x axis so we can use it easily
			foreach ($results['x'] as $result_tmp) {
				$x_tmp = $result_tmp['x'];
				$x_index[] = $x_tmp;
			}

			//re-organise the y axis so we can use it easily
			foreach ($results['y'] as $result_tmp) {
				$y_tmp = $result_tmp['y'];
				$y_index[] = $y_tmp;
			}

			//re-organise intersection data so we can access it by x and y keys
			if (!empty($results['c'])) {
				foreach ($results['c'] as $result_tmp) {
					$x_tmp = $result_tmp['x'];
					$y_tmp = $result_tmp['y'];
					$c_tmp = $result_tmp['c'];

					//index by Y THEN X. counter-intuitive, i know, but trust me...
					$results_foo[$y_tmp][$x_tmp] = $c_tmp;
				}
			}

			$csv_data .= ",";

			$csv_data .= implode(",", $x_index);

			$csv_data .= "\n";

			foreach ($y_index as $y_tmp) {
				$csv_data .= "$y_tmp,";

				unset($c_tmp_array);

				foreach ($x_index as $x_tmp) {
					$c_tmp = isset($results_foo[$y_tmp][$x_tmp]) ? $results_foo[$y_tmp][$x_tmp] : null;
					$c_tmp_array[] = $c_tmp;
				}

				$csv_data .= implode(",", $c_tmp_array);

				$csv_data .= "\n";
			}
		}

		file_put_contents($txt_path, $csv_data, FILE_APPEND);

		return $insert;
	}
}
		
?>
