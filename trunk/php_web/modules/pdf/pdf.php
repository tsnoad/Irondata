<?php

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

	function get_or_generate($data=array()) {
		$saved_report_id = $data[0];
		$demo = $data[1];
		$html = $data[2];

		if (empty($saved_report_id)) return;

		if (!is_dir("/tmp/".$this->tmp_path)) {
			mkdir("/tmp/".$this->tmp_path);
		}

		//check if the table document exists
		$saved_report = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM saved_reports r LEFT OUTER JOIN table_documents t ON (t.saved_report_id=r.saved_report_id) WHERE r.saved_report_id='$saved_report_id' LIMIT 1;"));

		//if the table document does not exist, create it, and return data about where it can be found
		if (empty($saved_report['table_document_id']) || !is_file($saved_report['html_path']) || !is_file($saved_report['pdf_path'])) {
			return $this->view_export(array($saved_report['report'], $saved_report_id, $saved_report['template_id'], $demo, $html));
		}

		//if it does, simply return data about where it can be found
// 		return $saved_report;

		if ($html) {
			return array("object"=>file_get_contents($saved_report['html_path']), "pdf_url"=>$saved_report['pdf_url']);
		}

		return $saved_report;
	}
	
	function view_export($data=array()) {
		$foo_json = $data[0];
		$saved_report_id = $data[1];
		$template_id = $data[2];
		$demo = $data[3];
		$html = $data[4];

		$template = $this->call_function("tabular", "get_columns", $template_id);
		$template = $template['tabular'];

		$path_base = "/tmp/".$this->tmp_path;
		$url_base = "/".$this->tmp_path;

		$table_document_id = $this->dobj->nextval("table_documents");

		$html_path = $path_base."table_$table_document_id.html";
		$html_url = $url_base."table_$table_document_id.html";

		$pdf_tmp_path = $path_base."table_".$table_document_id."_tmp.html";

		$pdf_path = $path_base."table_$table_document_id.pdf";
		$pdf_url = $url_base."table_$table_document_id.pdf";

		$insert = array(
			"table_document_id" => $table_document_id,
			"saved_report_id" => $saved_report_id,
			"created" => "now()",
			"html_path" => $html_path,
			"html_url" => $html_url,
			"pdf_path" => $pdf_path,
			"pdf_url" => $pdf_url,
		);

		$this->dobj->db_query($this->dobj->insert($insert, "table_documents"));

		$table_html = Pdf_View::table_html($foo_json, $template, false);

		if ($demo) {
			$table_pdf_html = Pdf_View::table_html_wrapped($table_html);
		} else {
			$table_pdf_html = Pdf_View::table_html($foo_json, $template, true);
			$table_pdf_html = Pdf_View::table_html_wrapped($table_pdf_html);
		}

		file_put_contents($html_path, $table_html);
		file_put_contents($pdf_tmp_path, $table_pdf_html);

		Pdf_View::convert_to_pdf($pdf_tmp_path, $pdf_path);

		if ($html) {
			return array(
				"object" => file_get_contents($html_path),
				"pdf_url" => $pdf_url
			);
		}

		return $insert;
	}
}

class Pdf_View {
	function table_html($foo_json, $template, $pdf) {
		$report = json_decode($foo_json, true);
		$output = $this->call_function("tabular", "hook_output", array($report, $template, null, null, $pdf));
		$output = $output['tabular'];
		$output = $output->data;

		return $output;
	}

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

	/*$pdf = Common_Functions::makePdf($main, $report_name.$suffix, array(), $autovalues); /* The ./tmp/ directory is automatically added, as is the .pdf extension. */
	function view_export($data, $table_document_id, $pdf_path, $html_path, $reportname, $align="", $module_css="", $report=false) {
// 		$data = file_get_contents($html_path);
// 
// // 		if ($align == "landscape") {
// // 			$landscape = true;
// // 			$pixels = 1024;
// // 		} elseif ($align == "portrait") {
// 			$landscape = false;
// 			$pixels = 724;
// // 		} else {
// // 			$numtd = substr_count($data, "<td", 0, strpos($data, "</tr"));
// // 			if ($numtd > 10) {
// // 				$landscape = true;
// // 				$pixels = 1024;
// // 			} else {
// // 				$landscape = false;
// // 				$pixels = 724;
// // 			}
// // 		}
// 
// 		/* PDF Headers */
// 		set_include_path(get_include_path() . PATH_SEPARATOR . "./html2ps/");
// 		require_once('config.inc.php');
// 		require_once('pipeline.factory.class.php');
// 		require_once('fetcher.url.class.php');
// 		parse_config_file(HTML2PS_DIR.'html2ps.config');
// 
// 		global $g_config;
// 		$g_config = array(
// 		                  'cssmedia' => 'screen',
// 		                  'renderimages' => true,
// 		                  'renderforms' => false,
// 		                  'renderlinks' => false,
// 		                  'mode' => 'html',
// 		                  'debugbox' => false,
// 				  'smartpagebreak' => 1,
// 		                  'draw_page_border' => false,
// 				  'html2xhtml' => true,
// 				  'scalepoints' => true,
// 				  'renderfields' => true,
// 				  'output' => 2
// 		                  );
// 
// 		$media = Media::predefined('A4');
// 		$media->set_landscape($landscape);
// 		$media->set_margins(array('left' => 15,
// 		                          'right' => 15,
// 		                          'top' => 15,
// 		                          'bottom' => 15));
// 		$media->set_pixels($pixels);
// 
// 		global $g_px_scale;
// 		$g_px_scale = mm2pt($media->width() - $media->margins['left'] - $media->margins['right']) / $media->pixels;
// 		global $g_pt_scale;
// 		$g_pt_scale = $g_px_scale * 1.43;
// 
// 		$pipeline = new Pipeline;
// 		$pipeline->configure($g_config);
// 
// 		$pipeline->fetchers[] = new MyFetcherContent($data);
// 		$pipeline->fetchers[] = new FetcherURL();
// 
// 		$pipeline->data_filters[] = new DataFilterHTML2XHTML;
// 		$pipeline->parser = new ParserXHTML;
// 		$pipeline->layout_engine = new LayoutEngineDefault;
// 		$pipeline->output_driver = new OutputDriverFPDF($media);
// 		$pipeline->pre_tree_filters[] = new PreTreeFilterHTML2PSFields();
// 
// 		$pdf_name = "table_$table_document_id";
// 
// 		$pipeline->destination = new DestinationFile($pdf_name, ''); 
// 
// 		$status = $pipeline->process($pdf_name, $media);
// 
// 		copy(HTML2PS_DIR."out/$pdf_name.pdf", $pdf_path);
// 		unlink(HTML2PS_DIR."out/$pdf_name.pdf");

// 		return;
	}


















// // 	require_once(dirname(__FILE__).'/../config.inc.php');
// 	require_once('config.inc.php');
// // 	require_once(HTML2PS_DIR.'pipeline.factory.class.php');
// 	require_once('pipeline.factory.class.php');
// 
// 	error_reporting(E_ALL);
// 	ini_set("display_errors","1");
// 	@set_time_limit(10000);
// 	parse_config_file(HTML2PS_DIR.'html2ps.config');



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

// /* Setup for the html2ps functions*/
// set_include_path(get_include_path() . PATH_SEPARATOR . $this->dir."pdf/html2ps/public_html/");
// require_once('config.inc.php');
// require_once('pipeline.factory.class.php');
// require_once('fetcher.url.class.php');
// parse_config_file(HTML2PS_DIR.'html2ps.config');
// 
// class MyFetcherContent extends Fetcher {
// 	var $base_path;
// 	var $content;
// 
// 	function MyFetcherContent($content) {
// 		$this->content = $content;
// 	}
// 	function get_data($dummy1) {
// 		return new FetchedDataURL($this->content, array(), "");
// 	}
// 	function get_base_url() {
// // 		return "";
// 		return null;
// 	}
// }

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
