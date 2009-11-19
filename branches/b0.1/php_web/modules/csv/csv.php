<?php

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

	function get_or_generate($data=array()) {
		$saved_report_id = $data[0];

		if (empty($saved_report_id)) return;

		if (!is_dir("/tmp/".$this->tmp_path)) {
			mkdir("/tmp/".$this->tmp_path);
		}

		//check if the csv document exists
		$saved_report = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM saved_reports r LEFT OUTER JOIN csv_documents c ON (c.saved_report_id=r.saved_report_id) WHERE r.saved_report_id='$saved_report_id' LIMIT 1;"));

		//if the csv document does not exist, create it, and return data about where it can be found
		if (empty($saved_report['csv_document_id']) || !is_file($saved_report['txt_path'])) {
			return $this->view_export(array($saved_report['report'], $saved_report_id));
		}

		//if it does, simply return data about where it can be found
		return $saved_report;
	}
	
	function view_export($data=array()) {
		$foo_json = $data[0];
		$saved_report_id = $data[1];

		$path_base = "/var/www/".$this->tmp_path;
		$url_base = "/".$this->tmp_path;

		$csv_document_id = $this->dobj->nextval("csv_documents");

		$txt_path = $path_base."csv_$csv_document_id.txt";
		$txt_url = $url_base."csv_$csv_document_id.txt";

		$insert = array(
			"csv_document_id" => $csv_document_id,
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
					$c_tmp = $results_foo[$y_tmp][$x_tmp];
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
