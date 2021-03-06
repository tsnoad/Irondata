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
 * pdf.php
 *
 * The export to pdf module
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class Pdf extends Template {
	var $dobj;
	var $name = "PDF";
	var $description = "Outputs a report to PDF. ";
	
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
			"label"=>"Export as PDF",
			"module"=>"pdf",
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
			$default = $this->dobj->db_fetch("SELECT publish_pdf FROM templates WHERE template_id='".$this->id."'");
			$default = $default['publish_pdf'] == 't' ? true: false;
			return array("name"=>$this->name, "default"=>$default);
		}
	}
	
	/**
	 * Save the publish status
	 */
	function hook_save_publish() {
		$save = array();
		if (isset($_REQUEST['data']['publish_pdf'])) {
			$save['publish_pdf'] = "t";
		} else {
			$save['publish_pdf'] = "f";
		}
		unset($_REQUEST['data']['publish_pdf']);
		$this->dobj->db_query($this->dobj->update($save, "template_id", $this->id, "templates"));
	}
	
	function hook_get_or_generate($data=array()) {
		$saved_report_id = $data["saved_report_id"];
		$demo = $data["demo"];
		
		if (empty($saved_report_id)) return;
		if ($demo) return;
		
		if (!is_dir($this->sw_path.$this->tmp_path)) {
			mkdir($this->sw_path.$this->tmp_path);
		}
		
		//check if the table document exists
		$saved_report = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM saved_reports r LEFT OUTER JOIN table_documents t ON (t.saved_report_id=r.saved_report_id) WHERE r.saved_report_id='$saved_report_id' LIMIT 1;"));
		
		//if the table document does not exist, create it, and return data about where it can be found
		if (empty($saved_report['saved_report_id']) || !is_file($saved_report['html_path']) || !is_file($saved_report['pdf_path'])) {
			// If this is a demo, do nothing
			if (!$demo) {
				$saved_report = $this->save_document(array($saved_report['report'], $saved_report_id, $saved_report['template_id']));
			}
		}

		//if it does, simply return data about where it can be found
		return array('object'=>null, 'url'=>$saved_report['pdf_url']);
	}
	
	function save_document($data=array()) {
		$report = $data[0];
		$saved_report_id = $data[1];
		$template_id = $data[2];
		$html_value = parent::save_document($report, $template_id, $saved_report_id, false);
		
		$path_base = $this->sw_path.$this->tmp_path;
		$url_base = $this->web_path.$this->tmp_path;
		
		$pdf_tmp_path = $path_base."table_".$saved_report_id."_tmp.html";
		$pdf_path = $path_base."table_$saved_report_id.pdf";
		$pdf_url = $url_base."table_$saved_report_id.pdf";
		
		$update = array(
			"created" => "now()",
			"pdf_path" => $pdf_path,
			"pdf_url" => $pdf_url,
		);
		
		$this->dobj->db_query($this->dobj->update($update, "saved_report_id", $saved_report_id, "table_documents"));
		
		$table_pdf_html = Pdf_View::table_html_wrapped($html_value);
		file_put_contents($pdf_tmp_path, $table_pdf_html);
		Pdf_View::convert_to_pdf($pdf_tmp_path, $pdf_path);
		
		return $update;
	}
}

class Pdf_View {
	function table_html_wrapped($output) {
		$theme = $this->get_theme();
		$webroot = $this->webroot();
		$themeroot = 'themes/'.$theme.'/';

		return "
			<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN' 'http://www.w3.org/TR/html4/strict.dtd'>
			<html>
				<head>
					<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
					<style>
						".file_get_contents($themeroot."style.css")."
					</style>
				</head>
				<body>
					$output
				</body>
			</html>
			";
	}

	/**
	* Runs the HTML->PDF conversion with default settings
	*
	* Warning: if you have any files (like CSS stylesheets and/or images referenced by this file,
	* use absolute links (like http://my.host/image.gif).
	*
	* @param $path_to_html String path to source html file.
	* @param $path_to_pdf  String path to file to save generated PDF to.
	*/
	function convert_to_pdf($path_to_html, $path_to_pdf) {


	// 	require_once(dirname(__FILE__).'/../config.inc.php');
		require_once('config.inc.php');
	// 	require_once(HTML2PS_DIR.'pipeline.factory.class.php');
		require_once('pipeline.factory.class.php');

// 		error_reporting(E_ALL);
// 		ini_set("display_errors","1");
		@set_time_limit(10000);
		parse_config_file(HTML2PS_DIR.'html2ps.config');


		$pipeline = PipelineFactory::create_default_pipeline("", // Attempt to auto-detect encoding
								"");
		// Override HTML source
		$pipeline->fetchers[] = new MyFetcherLocalFile($path_to_html);

// 		$filter = new PreTreeFilterHeaderFooter("HEADER", "FOOTER");
// 		$pipeline->pre_tree_filters[] = $filter;
		$pipeline->pre_tree_filters[] = new PreTreeFilterHTML2PSFields();

		// Override destination to local file
		$pipeline->destination = new MyDestinationFile($path_to_pdf);

		$baseurl = "";
		$media = Media::predefined("A4");
		$media->set_landscape(false);
		$media->set_margins(array('left' => 15,
		                          'right' => 15,
		                          'top' => 15,
		                          'bottom' => 15));
// 		$media->set_pixels(1024);
		$media->set_pixels(724);

		global $g_px_scale;
		$g_px_scale = mm2pt($media->width() - $media->margins['left'] - $media->margins['right']) / $media->pixels;
		global $g_pt_scale;
		$g_pt_scale = $g_px_scale * 1.43;

		global $g_config;
		$g_config = array(
				'cssmedia'     => 'screen',
				'scalepoints'  => '1',
				'renderimages' => true,
				'renderlinks'  => false,
				'renderfields' => true,
				'renderforms'  => false,
				'mode'         => 'html',
				'encoding'     => '',
				'debugbox'     => false,
				'pdfversion'    => '1.4',
				'draw_page_border' => false,
				'smartpagebreak' => true,
				'html2xhtml' => true
				);
		$pipeline->configure($g_config);
// 		$pipeline->add_feature('toc', array('location' => 'before'));
		$pipeline->process($baseurl, $media);
	}
}

set_include_path(get_include_path() . PATH_SEPARATOR . $this->dir."pdf/html2ps/public_html/");
// 	require_once(dirname(__FILE__).'/../config.inc.php');
	require_once('config.inc.php');
// 	require_once(HTML2PS_DIR.'pipeline.factory.class.php');
	require_once('pipeline.factory.class.php');

// 	error_reporting(E_ALL);
// 	ini_set("display_errors","1");
	@set_time_limit(10000);
	parse_config_file(HTML2PS_DIR.'html2ps.config');


/**
* Handles the saving generated PDF to user-defined output file on server
*/
class MyDestinationFile extends Destination {
	/**
	* @var String result file name / path
	* @access private
	*/
	var $_dest_filename;

	function MyDestinationFile($dest_filename) {
		$this->_dest_filename = $dest_filename;
	}

	function process($tmp_filename, $content_type) {
		copy($tmp_filename, $this->_dest_filename);
	}
}

class MyFetcherLocalFile extends Fetcher {
	var $_content;

	function MyFetcherLocalFile($file) {
		$this->_content = file_get_contents($file);
	}

	function get_data($dummy1) {
		return new FetchedDataURL($this->_content, array(), "");
	}

	function get_base_url() {
		return "http://127.0.0.1/graphs/";
	}
}

?>
