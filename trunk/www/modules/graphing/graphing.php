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
 * Table.php
 *
 * The Table report template module.
 *
 * @author Tobias Snoad
 * @date 26-07-2008
 *
 */

class Graphing extends Template {
	var $conn;
	var $dobj;
	var $name = "Graphing";
	var $description = "Generate graphs from tabular and list data.";

	var $line_colors = array(
		array("#3465a4", "#3465a4", "8,8"), 
		array("#73d216", "#73d216", "8,8"), 
		array("#cc0000", "#cc0000", "8,8"), 
		array("#f57900", "#f57900", "8,8"), 
		array("#75507b", "#75507b", "8,8"), 

		array("#204a87", "#729fcf", "8,8"), 
		array("#8ae234", "#4e9a06", "8,8"), 
		array("#ef2929", "#a40000", "8,8"), 
		array("#fcaf3e", "#ce5c00", "8,8"), 
		array("#ad7fa8", "#5c3566", "8,8"), 

		array("#204a87", "#729fcf", "4,16"), 
		array("#8ae234", "#4e9a06", "4,16"), 
		array("#ef2929", "#a40000", "4,16"), 
		array("#fcaf3e", "#ce5c00", "4,16"), 
		array("#ad7fa8", "#5c3566", "4,16"), 

		array("#204a87", "#729fcf", "16,4"), 
		array("#8ae234", "#4e9a06", "16,4"), 
		array("#ef2929", "#a40000", "16,4"),
		array("#fcaf3e", "#ce5c00", "16,4"), 
		array("#ad7fa8", "#5c3566", "16,4")
		);

	var $bar_colors = array(
		array("#204a87", "#3465a4", ""), 
		array("#4e9a06", "#73d216", ""), 
		array("#a40000", "#cc0000", ""), 
		array("#ce5c00", "#f57900", ""), 
		array("#5c3566", "#75507b", ""), 

		array("#204a87", "#3465a4", "8,8"), 
		array("#4e9a06", "#73d216", "8,8"), 
		array("#a40000", "#cc0000", "8,8"), 
		array("#ce5c00", "#f57900", "8,8"), 
		array("#5c3566", "#75507b", "8,8"), 

		array("#204a87", "#3465a4", "4,16"), 
		array("#4e9a06", "#73d216", "4,16"), 
		array("#a40000", "#cc0000", "4,16"), 
		array("#ce5c00", "#f57900", "4,16"), 
		array("#5c3566", "#75507b", "4,16"), 

		array("#204a87", "#3465a4", "16,4"), 
		array("#4e9a06", "#73d216", "16,4"), 
		array("#a40000", "#cc0000", "16,4"), 
		array("#ce5c00", "#f57900", "16,4"), 
		array("#5c3566", "#75507b", "16,4")
		);

	var $show_layout = false;

	function hook_admin_tools() {
		return null;
	}

	function hook_roles() {
		return null;
	}

	/**
	 * Get Or Generate
	 *
	 * Called by Template to generate graphs of various kinds
	 *
	 * @param $data Graph type and data in JSON format
	 * @return Completed html for insertion into page
	 */
	function get_or_generate($data=array()) {
		$saved_report_id = $data[0];
		$graph_type = $data[1];
		$svg = $data[2];
		$pdf = $data[3];

		if (empty($saved_report_id)) return;

		if (!is_dir($this->sw_path.$this->tmp_path)) {
			mkdir($this->sw_path.$this->tmp_path);
		}

		//check if the graph document exists
		$saved_report = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM saved_reports r LEFT OUTER JOIN graph_documents g ON (g.saved_report_id=r.saved_report_id) WHERE r.saved_report_id='$saved_report_id' LIMIT 1;"));

		//if the graph document does not exist, create it, and return data about where it can be found
		if (empty($saved_report['graph_document_id']) || !is_file($saved_report['svg_path']) || !is_file($saved_report['pdf_path'])) {
			if (empty($graph_type)) {
				$graph_type = "Lines";
			}

			return $this->hook_graph($saved_report['template_id'], $saved_report_id, $graph_type, $saved_report['report'], $svg, $pdf);
		}

		if ($svg) {
			$area = $this->define_area();
			return array("object"=>Graphing_View::line_graph($saved_report['svg_url'], $area['graph_w'], $area['graph_h']+90), "pdf_url"=>$saved_report['pdf_url']);
		}

		if ($pdf) {
			return $saved_report;
		}

	}

	/**
	 * Hook Graph
	 *
	 * Called by Template to generate graphs of various kinds
	 *
	 * @param $data Graph type and data in JSON format
	 * @return Completed html for insertion into page
	 */
	function hook_graph($template_id, $saved_report_id, $graph_type, $graph_data, $svg, $pdf) {
		switch ($graph_type) {
			case "Lines":
				return $this->line_graph($template_id, $saved_report_id, $graph_data, false, $svg, $pdf);
			case "Areas":
				return;
			case "StackedAreas":
				return $this->area_graph($template_id, $saved_report_id, $graph_data, true, $svg, $pdf);
			case "Columns":
				return;
			case "StackedColumns":
				return $this->bar_graph($template_id, $saved_report_id, $graph_data, true, $svg, $pdf);
			case "ClusteredColumns":
				return;
		}
	}

	/**
	 * Line Graph
	 *
	 * Generate a line graph
	 *
	 * @param $graph_data Graph data in JSON format
	 * @return Completed html for insertion into page
	 */
	function line_graph($template_id, $saved_report_id, $graph_data, $stacked=false, $svg=true, $pdf=false) {
		$template = $this->call_function("tabular", "get_columns", $template_id);
		$template = $template['tabular'];

		foreach ($template as $template_tmp) {
			$template_axies[$template_tmp['type']] = $template_tmp;
		}

		$results = $this->index_data("line", $graph_data, $stacked);

		//file paths - so we know where to save files, and where we access them from
		$paths = $this->define_paths($saved_report_id);

		$area = $this->define_area();

		$svg_data = "";

		if ($this->show_layout) $svg_data .= $this->show_layout_boxes($area);

		//title
		$svg_data .= "<text x='".$area['title_x']."' y='".($area['title_y'] + $area['title_h'])."' style='font-family: Georgia; font-size: 12pt; text-align: center;'>Graph 1. Title</text>";

		//x axis title
		$svg_data .= $this->generate_x_index("line", $results, $template_axies['x']['tabular_template_human_name'], $area);

		//y axis title
		$svg_data .= $this->generate_y_index($results, $template_axies['c']['tabular_template_human_name'], $area);

		//c axis title
		$return_tmp = $this->generate_c_index("line", $results, $template_axies['y']['tabular_template_human_name'], $area);
		$svg_data .= $return_tmp[0];
		$area = $return_tmp[1];

		//datapoints
		$svg_data .= $this->generate_datapoints("line", $results, $area);

		$this->export_pdf($paths, $svg_data, $area['graph_w'], $area['graph_h']);

		$this->export_svg($paths['svg_path'], $svg_data, $area['graph_w'], $area['graph_h']);

		if ($pdf) {
			return $paths;
		}

		if ($svg) {
			return array(
				"object" => Graphing_View::line_graph($paths['svg_url'], $area['graph_w'], $area['graph_h']),
				"pdf_url" => $paths['pdf_url']
			);
		}
	}

	/**
	 * Area Graph
	 *
	 * Generate an area graph
	 *
	 * @param $graph_data Graph data in JSON format
	 * @param $stacked Generate a plot with datapoints stacked atop each other
	 * @return Completed html for insertion into page
	 */
	function area_graph($template_id, $saved_report_id, $graph_data, $stacked=false, $svg=true, $pdf=false) {
		$template = $this->call_function("tabular", "get_columns", $template_id);
		$template = $template['tabular'];

		foreach ($template as $template_tmp) {
			$template_axies[$template_tmp['type']] = $template_tmp;
		}

		$results = $this->index_data("area", $graph_data, $stacked);

		//file paths - so we know where to save files, and where we access them from
		$paths = $this->define_paths($saved_report_id);

		$area = $this->define_area();

		unset($svg_data);

		if ($this->show_layout) $svg_data .= $this->show_layout_boxes($area);

		//title
		$svg_data .= "<text x='".$area['title_x']."' y='".($area['title_y'] + $area['title_h'])."' style='font-family: Georgia; font-size: 12pt; text-align: center;'>Graph 1. Title</text>";

		//x axis title
		$svg_data .= $this->generate_x_index("area", $results, $template_axies['x']['tabular_template_human_name'], $area);

		//y axis title
		$svg_data .= $this->generate_y_index($results, $template_axies['c']['tabular_template_human_name'], $area);

		//c axis title
		$return_tmp = $this->generate_c_index("area", $results, $template_axies['y']['tabular_template_human_name'], $area);
		$svg_data .= $return_tmp[0];
		$area = $return_tmp[1];

		//datapoints
		$svg_data .= $this->generate_datapoints("area", $results, $area);

		$this->export_pdf($paths, $svg_data, $area['graph_w'], $area['graph_h']);

		$this->export_svg($paths['svg_path'], $svg_data, $area['graph_w'], $area['graph_h']);

		if ($pdf) {
			return $paths;
		}

		if ($svg) {
			return array(
				"object" => Graphing_View::line_graph($paths['svg_url'], $area['graph_w'], $area['graph_h']),
				"pdf_url" => $paths['pdf_url']
			);
		}
	}

	/**
	 * Bar Graph
	 *
	 * Generate an bar graph
	 *
	 * @param $graph_data Graph data in JSON format
	 * @param $stacked Generate a plot with datapoints stacked atop each other
	 * @return Completed html for insertion into page
	 */
	function bar_graph($template_id, $saved_report_id, $graph_data, $stacked=false, $svg=true, $pdf=false) {
		$template = $this->call_function("tabular", "get_columns", $template_id);
		$template = $template['tabular'];

		foreach ($template as $template_tmp) {
			$template_axies[$template_tmp['type']] = $template_tmp;
		}

		$results = $this->index_data("bar", $graph_data, $stacked);

		//file paths - so we know where to save files, and where we access them from
		$paths = $this->define_paths($saved_report_id);

		$area = $this->define_area();

		unset($svg_data);

		if ($this->show_layout) $svg_data .= $this->show_layout_boxes($area);

		//title
		$svg_data .= "<text x='".$area['title_x']."' y='".($area['title_y'] + $area['title_h'])."' style='font-family: Georgia; font-size: 12pt; text-align: center;'>Graph 1. Title</text>";

		//x axis title
		$svg_data .= $this->generate_x_index("bar", $results, $template_axies['x']['tabular_template_human_name'], $area);

		//y axis title
		$svg_data .= $this->generate_y_index($results, $template_axies['c']['tabular_template_human_name'], $area);

		//c axis title
		$return_tmp = $this->generate_c_index("bar", $results, $template_axies['y']['tabular_template_human_name'], $area);
		$svg_data .= $return_tmp[0];
		$area = $return_tmp[1];

		//datapoints
		$svg_data .= $this->generate_datapoints("bar", $results, $area);

		$this->export_pdf($paths, $svg_data, $area['graph_w'], $area['graph_h']);

		$this->export_svg($paths['svg_path'], $svg_data, $area['graph_w'], $area['graph_h']);

		if ($pdf) {
			return $paths;
		}

		if ($svg) {
			return array(
				"object" => Graphing_View::line_graph($paths['svg_url'], $area['graph_w'], $area['graph_h']),
				"pdf_url" => $paths['pdf_url']
			);
		}
	}

	/**
	 * Index Data
	 *
	 * Take the data ouput by the database, re-organize so we can loop though it, and create arrays of the axis indexes
	 *
	 * @param $graph_data Graph data in JSON format
	 * @param $stacked Generate data for a stacked plot
	 * @return Array of the X, Y & C Axis indexes, datapoints, min/max datapoints and colors
	 */
	function index_data($type, $graph_data, $stacked=false) {
		$max_c = null;
		$min_c = null;
		$results_foo = null;
		$results = json_decode($graph_data, true);

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
				
				if (is_numeric($c_tmp)) {
					//record the maximum number found in the intersection
					if ($max_c == null || $c_tmp > $max_c) {
						$max_c = $c_tmp;
					}
					//record the minimum number found in the intersection
					if ($min_c == null || $c_tmp < $min_c) {
						$min_c = $c_tmp;
					}

					//index by Y THEN X. counter-intuitive, i know, but trust me...
					$results_foo[$y_tmp][$x_tmp] = $c_tmp;
				}
			}

			if ($stacked) {
				$x_stacking_tmp = array_combine($x_index, array_pad(array(), count($x_index), 0));
				$x_stacking_neg_tmp = array_combine($x_index, array_pad(array(), count($x_index), 0));

				foreach ($y_index as $y_tmp) {
					foreach ($x_index as $x_tmp) {
						$c_tmp = isset($results_foo[$y_tmp][$x_tmp]) ? $results_foo[$y_tmp][$x_tmp] : null;

						switch ($type) {
							case "line":
								break;
							case "area":
								$x_stacking_tmp[$x_tmp] += $c_tmp;
								$c_tmp = $x_stacking_tmp[$x_tmp];

								$results_foo[$y_tmp][$x_tmp] = $c_tmp;
								break;
							case "bar":
								if (isset($results_foo[$y_tmp][$x_tmp])) {
									if ($c_tmp >= 0) {
										$x_stacking_tmp[$x_tmp] += $c_tmp;
										$c_tmp = $x_stacking_tmp[$x_tmp];
									} else {
										$x_stacking_neg_tmp[$x_tmp] += $c_tmp;
										$c_tmp = $x_stacking_neg_tmp[$x_tmp];
									}

									$results_foo[$y_tmp][$x_tmp] = $c_tmp;
								}
								break;
						}
					}

				}
			}

			if ($stacked && $min_c > 0) {
				$min_c = 0;
			}

			if ($min_c === $max_c && $min_c >= 0) {
				$min_c = 0;
			} elseif ($min_c === $max_c && $min_c < 0) {
				$max_c = 0;
			}

			//create a index for the intersection - this will appear as the y axis on the graph. 
			//there are only three visual indices. the minimum, maximum and mid-point.
			$c_index[] = $min_c;
			$c_index[] = $min_c + (($max_c - $min_c) / 2);
			$c_index[] = $max_c;
		} else {
			//create a index for the intersection - this will appear as the y axis on the graph. 
			//there are only three visual indices. the minimum, maximum and mid-point.
			$c_index[] = 0;
			$c_index[] = 5;
			$c_index[] = 10;
		}

		switch ($type) {
			case "line":
				$colors = $this->line_colors;
				break;
			case "area":
				$colors = $this->bar_colors;
				break;
			case "bar":
				$colors = $this->bar_colors;
				break;
		}

		$color_tmp = reset($colors);

		foreach ($y_index as $y_tmp) {
			$y_colors[$y_tmp] = $color_tmp;
			$color_tmp = next($colors);

			if (empty($color_tmp)) $color_tmp = reset($colors);
		}

		return array(
			"x_index" => $x_index,
			"y_index" => $y_index,
			"c_index" => $c_index,
			"results" => $results_foo,
			"max_c" => $max_c,
			"min_c" => $min_c,
			"y_colors" => $y_colors
		);
	}

	/**
	 * Define paths
	 *
	 * Define where SVG and PDF files will be created and where thay can be accessed
	 *
	 * @return Array of co-ordinates
	 */
	function define_paths($saved_report_id) {
		$path_base = $this->sw_path.$this->tmp_path;
		$url_base = $this->web_path.$this->tmp_path;

		$graph_document_id = $this->dobj->nextval("graph_documents");

		$svg_path = $path_base."graph_$graph_document_id.svg";
		$svg_url = $url_base."graph_$graph_document_id.svg";

		$pdf_tmp_path = $path_base."graph_".$graph_document_id."_tmp.svg";

		$pdf_path = $path_base."graph_$graph_document_id.pdf";
		$pdf_url = $url_base."graph_$graph_document_id.pdf";

		$insert = array(
			"graph_document_id" => $graph_document_id,
			"saved_report_id" => $saved_report_id,
			"created" => "now()",
			"svg_path" => $svg_path,
			"svg_url" => $svg_url,
			"pdf_path" => $pdf_path,
			"pdf_url" => $pdf_url,
		);

		$this->dobj->db_query($this->dobj->insert($insert, "graph_documents"));

		return array(
			"graph_document_id" => $graph_document_id,
			"saved_report_id" => $saved_report_id,
			"svg_path" => $svg_path,
			"svg_url" => $svg_url,
			"pdf_tmp_path" => $pdf_tmp_path,
			"pdf_path" => $pdf_path,
			"pdf_url" => $pdf_url
		);
	}

	/**
	 * Define Area
	 *
	 * Define where each of the parts of the graph will go on the page
	 *
	 * @return Array of co-ordinates
	 */
	function define_area() {
		$area['graph_x'] = 0;
		$area['graph_y'] = 0;
		$area['graph_w'] = 1050;
		$area['graph_h'] = 600;

		$area['plot_x'] = 100;
		$area['plot_y'] = 40;
		$area['plot_w'] = 850;
		$area['plot_h'] = 390;

		$area['title_x'] = 100;
		$area['title_y'] = 10;
		$area['title_w'] = $area['plot_w'];
		$area['title_h'] = 20;

		$area['ytitle_x'] = 10;
		$area['ytitle_y'] = 40;
		$area['ytitle_w'] = 20;
		$area['ytitle_h'] = $area['plot_h'];

		$area['yindex_x'] = 40;
		$area['yindex_y'] = 40;
		$area['yindex_w'] = 50;
		$area['yindex_h'] = $area['plot_h'];

		$area['xtitle_x'] = 100;
		$area['xtitle_y'] = 500;
		$area['xtitle_w'] = $area['plot_w'];
		$area['xtitle_h'] = 20;

		$area['xindex_x'] = 100;
		$area['xindex_y'] = 440;
		$area['xindex_w'] = $area['plot_w'];
		$area['xindex_h'] = 50;

		$area['ltitle_x'] = 40;
		$area['ltitle_y'] = 560;
		$area['ltitle_w'] = 970;
		$area['ltitle_h'] = 20;

		$area['lindex_x'] = 40;
		$area['lindex_y'] = 590;
		$area['lindex_w'] = 970;
		$area['lindex_h'] = 0;

		return $area;
	}

	/**
	 * Show Layout Boxes
	 *
	 * Show layout boxes to make margins, etc in the svg clear
	 *
	 * @param $area Co-ordinates of each part of the graph
	 * @return XML markup used to build the SVG
	 */
	function show_layout_boxes($area) {
		if (!$this->show_layout) continue;

		//graph container
		$svg_data .= "<rect x='".$area['graph_x']."' y='".$area['graph_y']."' width='".$area['graph_w']."' height='".$area['graph_h']."' style='fill: #dddddd;'/>";

		//title container
		$svg_data .= "<rect x='".$area['title_x']."' y='".$area['title_y']."' width='".$area['title_w']."' height='".$area['title_h']."' style='fill: #eeeeee;'/>";

		//x axis title container
		$svg_data .= "<rect x='".$area['xtitle_x']."' y='".$area['xtitle_y']."' width='".$area['xtitle_w']."' height='".$area['xtitle_h']."' style='fill: #eeeeee;'/>";

		//x axis index container
		$svg_data .= "<rect x='".$area['xindex_x']."' y='".$area['xindex_y']."' width='".$area['xindex_w']."' height='".$area['xindex_h']."' style='fill: #eeeeee;'/>";

		//y axis title container
		$svg_data .= "<rect x='".$area['ytitle_x']."' y='".$area['ytitle_y']."' width='".$area['ytitle_w']."' height='".$area['ytitle_h']."' style='fill: #eeeeee;'/>";

		//y axis index container
		$svg_data .= "<rect x='".$area['yindex_x']."' y='".$area['yindex_y']."' width='".$area['yindex_w']."' height='".$area['yindex_h']."' style='fill: #eeeeee;'/>";

		//legend title container
		$svg_data .= "<rect x='".$area['ltitle_x']."' y='".$area['ltitle_y']."' width='".$area['ltitle_w']."' height='".$area['ltitle_h']."' style='fill: #eeeeee;'/>";

		//legend index container
		$svg_data .= "<rect x='".$area['lindex_x']."' y='".$area['lindex_y']."' width='".$area['lindex_w']."' height='".$area['lindex_h']."' style='fill: #eeeeee;'/>";

		//plot container
		$svg_data .= "<rect x='".$area['plot_x']."' y='".$area['plot_y']."' width='".$area['plot_w']."' height='".$area['plot_h']."' style='fill: #eeeeee;'/>";

		return;
	}

	/**
	 * Generate X Index
	 *
	 * Generate the svg for the x axis index and title
	 *
	 * @param $x_index The ordered axis index
	 * @param $title Axis title
	 * @param $area Co-ordinates of each part of the graph
	 * @return XML markup used to build the SVG
	 */
	function generate_x_index($type, $results, $title, $area) {
		$title = htmlentities(ucwords($title));
		$svg_data = "<text x='".$area['xtitle_x']."' y='".($area['xtitle_y'] + $area['xtitle_h'])."' style='font-family: Georgia; font-size: 10pt;' >$title</text>";

		//prepare to start drawing at the left of the plot area
		$x_tmp_x = $area['plot_x'];

		switch ($type) {
			case "line":
			case "area":
				break;
			case "bar":
				$x_tmp_x += $area['plot_w'] / (count($results['x_index']) - 0) / 2;
				break;
		}

		$index_counter = 1;

		foreach ($results['x_index'] as $x_tmp) {
			$minor = count($results['x_index']) > 15 && !($index_counter % round(count($results['x_index']) / 15) == 0);

			switch ($type) {
				case "line":
				case "area":
					if (!$minor) {
						$svg_data .= "<line x1='$x_tmp_x' y1='".$area['plot_y']."' x2='$x_tmp_x' y2='".($area['plot_y'] + $area['plot_h'])."' style='fill: none; stroke: #d3d7cf; stroke-width: 1px; stroke-dasharray: 1,2;' />";
					} else {
						$svg_data .= "<line x1='$x_tmp_x' y1='".$area['plot_y']."' x2='$x_tmp_x' y2='".($area['plot_y'] + $area['plot_h'])."' style='fill: none; stroke: #eeeeec; stroke-width: 1px; stroke-dasharray: 1,2;' />";
					}
					break;
				case "bar":
					break;
			}

			if (!$minor) {
				$x_tmp_segments = explode('\n', wordwrap($x_tmp, 8, '\n'));
				$x_tmp_segments = (array)$x_tmp_segments;

				$segments_array = array();

				foreach ($x_tmp_segments as $segment_tmp) {
					$segment_tmp = htmlentities(ucwords($segment_tmp));

					if (strlen($segment_tmp) > 9) {
						$segments_array = array_merge((array)$segments_array, (array)str_split($segment_tmp, 6));
					} else {
						$segments_array[] = $segment_tmp;
					}
				}

				$svg_data .= "
					<g transform='translate(".($x_tmp_x + 4 - (count($segments_array) - 1) * 5).", ".($area['xindex_y'] + $area['xindex_h']).")'>
						<g transform='rotate(270)'>
							<text x='0' y='0' style='font-family: Arial; font-size: 8pt;' ><tspan x='0'>".implode("</tspan><tspan x='0' dy='10px'>", $segments_array)."</tspan></text>
						</g>
					</g>
					";
			}

			if (count($results['x_index']) > 1) {
				switch ($type) {
					case "line":
					case "area":
						$x_tmp_x += $area['plot_w'] / (count($results['x_index']) - 1);
						break;
					case "bar":
						$x_tmp_x += $area['plot_w'] / (count($results['x_index']) - 0);
						break;
				}
			}

			$index_counter ++;
		}

		return $svg_data;
	}

	/**
	 * Generate Y Index
	 *
	 * Generate the svg for the y axis index and title
	 *
	 * @param $c_index The ordered axis index
	 * @param $title Axis title
	 * @param $area Co-ordinates of each part of the graph
	 * @return XML markup used to build the SVG
	 */
	function generate_y_index($results, $title, $area) {
		$title = htmlentities(ucwords($title));
		$svg_data = "
			<g transform='translate(".($area['ytitle_x'] + $area['ytitle_w']).", ".($area['ytitle_y'] + $area['ytitle_h']).")'>
				<g transform='rotate(270)'>
					<text x='0' y='0' style='font-family: Georgia; font-size: 10pt;'>$title</text>
				</g>
			</g>
			";

		$c_tmp_y = $area['plot_y'];

		if (!empty($results['c_index'])) {
			foreach (array_reverse($results['c_index']) as $c_tmp) {
				$c_tmp = htmlentities(ucwords($c_tmp));

				$svg_data .= "<line x1='".$area['plot_x']."' y1='$c_tmp_y' x2='".($area['plot_x'] + $area['plot_w'])."' y2='$c_tmp_y' style='fill: none; stroke: #babdb6; stroke-width: 1px; stroke-dasharray: 1,2;' />";

				$svg_data .= "
					<text x='".$area['yindex_x']."' y='".($c_tmp_y + 4)."' style='font-family: Arial; font-size: 8pt;'>$c_tmp</text>
					";

				$c_tmp_y += $area['plot_h'] / (count($results['c_index']) - 1);
			}
		}

		return $svg_data;
	}

	/**
	 * Generate C Index
	 *
	 * Generate the svg for the c axis index and title
	 *
	 * @param $y_index The ordered axis index
	 * @param $y_colors Data point colors
	 * @param $title Axis title
	 * @param $area Co-ordinates of each part of the graph
	 * @return XML markup used to build the SVG
	 */
	function generate_c_index($type, $results, $title, $area) {
		$title = htmlentities(ucwords($title));
		$svg_data = "";
		$svg_data .= "<line x1='".$area['ltitle_x']."' y1='".($area['ltitle_y'] - 5)."' x2='".($area['ltitle_x'] + $area['ltitle_w'])."' y2='".($area['ltitle_y'] - 10)."' style='fill: none; stroke: #d3d7cf; stroke-width: 1px;' />";
		$svg_data .= "<text x='".$area['ltitle_x']."' y='".($area['ltitle_y'] + $area['ltitle_h'])."' style='font-family: Georgia; font-size: 12pt;' >$title</text>";

		$c_tmp_y = $area['lindex_y'] + 10;

		foreach (array_chunk($results['y_index'], 4) as $y_index_chunk) {
			$c_tmp_x = $area['lindex_x'];

			foreach ($y_index_chunk as $y_tmp) {
				$dash_tmp = $results['y_colors'][$y_tmp][0];
				$solid_tmp = $results['y_colors'][$y_tmp][1];
				$seq_tmp = $results['y_colors'][$y_tmp][2];

				$y_tmp = htmlentities(ucwords($y_tmp));

				switch ($type) {
					case "line":
						unset($points_tmp);

						$points_tmp = "$c_tmp_x,".($c_tmp_y + 0.5)." ".($c_tmp_x + 50).",".($c_tmp_y + 0.5)."";

						$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: #eeeeec; stroke-width: 4px;' />";
						$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: $solid_tmp; stroke-width: 3px;' />";
						$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: $dash_tmp; stroke-width: 3px; stroke-dasharray: $seq_tmp;' />";

						$svg_data .= "<text x='".($c_tmp_x + 60)."' y='".($c_tmp_y + 4)."' style='font-family: Georgia; font-size: 10pt;' >$y_tmp</text>";
						break;
					case "area":
						$svg_data .= "<rect x='$c_tmp_x' y='".($c_tmp_y - 12)."' width='20' height='20' style='fill: $solid_tmp; stroke: none;' />";
						$svg_data .= "<rect x='".($c_tmp_x + 0.5)."' y='".($c_tmp_y - 12 + 0.5)."' width='19' height='19' style='fill: none; stroke: $dash_tmp; stroke-width: 1;' />";

						if (!empty($seq_tmp)) {
							$svg_data .= "<rect x='$c_tmp_x' y='".($c_tmp_y - 12)."' width='20' height='20' style='fill: url(#clubs".str_replace(",", "", $seq_tmp)."); stroke: none;' />";
						}

						$svg_data .= "<text x='".($c_tmp_x + 30)."' y='".($c_tmp_y + 4)."' style='font-family: Georgia; font-size: 10pt;' >$y_tmp</text>";
						break;
					case "bar":
						$svg_data .= "<rect x='$c_tmp_x' y='".($c_tmp_y - 12)."' width='20' height='20' style='fill: $solid_tmp; stroke: none;' />";
						$svg_data .= "<rect x='".($c_tmp_x + 0.5)."' y='".($c_tmp_y - 12 + 0.5)."' width='19' height='19' style='fill: none; stroke: $dash_tmp; stroke-width: 1;' />";

						if (!empty($seq_tmp)) {
							$svg_data .= "<rect x='$c_tmp_x' y='".($c_tmp_y - 12)."' width='20' height='20' style='fill: url(#hatch".str_replace(",", "", $seq_tmp)."); stroke: none;' />";
						}

						$svg_data .= "<text x='".($c_tmp_x + 30)."' y='".($c_tmp_y + 4)."' style='font-family: Georgia; font-size: 10pt;' >$y_tmp</text>";
						break;
				}

				$c_tmp_x += round($area['lindex_w'] / 4);
			}

			$area['graph_h'] += 30;
			$area['lindex_h'] += 30;

			$c_tmp_y += 30;
		}

		return array($svg_data, $area);
	}


	/**
	 * Generate Datapoints
	 *
	 * Generate the svg for the lines/bars/etc. that represent the datapoints
	 *
	 * @param $plot_type Plot type: line, area, etc.
	 * @param $x_index The ordered axis index
	 * @param $y_index The ordered axis index
	 * @param $results_foo Ordered datapoints
	 * @param $max_c Highest datapoint
	 * @param $min_c Lowest datapoint
	 * @param $y_colors Data point colors
	 * @param $area Co-ordinates of each part of the graph
	 * @return XML markup used to build the SVG
	 */
	function generate_datapoints($plot_type, $results, $area) {
		$svg_data = "";
		switch ($plot_type) {
			case "line":
				foreach ($results['y_index'] as $y_tmp) {
					$c_tmp_x = $area['plot_x'];

					$points_tmp = "";

					foreach ($results['x_index'] as $x_tmp) {
						if (!isset($results['results'][$y_tmp][$x_tmp])) {
							if (count($results['x_index']) > 1) {
								$c_tmp_x += $area['plot_w'] / (count($results['x_index']) - 1);
							}

							continue;
						}

						$c_tmp_y = $area['plot_y'] + $area['plot_h'];

						$c_tmp = $results['results'][$y_tmp][$x_tmp];

						$c_tmp -= $results['min_c'];

						if ($results['max_c'] - $results['min_c'] != 0) {
							$c_tmp /= ($results['max_c'] - $results['min_c']);
						}

						$c_tmp *= $area['plot_h'];

						$c_tmp_y -= $c_tmp;

						$points_tmp .= "$c_tmp_x,$c_tmp_y ";

						if (count($results['x_index']) > 1) {
							$c_tmp_x += $area['plot_w'] / (count($results['x_index']) - 1);
						}
					}

					$dash_tmp = $results['y_colors'][$y_tmp][0];
					$solid_tmp = $results['y_colors'][$y_tmp][1];
					$seq_tmp = $results['y_colors'][$y_tmp][2];

					$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: #eeeeec; stroke-width: 4px;' />";
					$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: $solid_tmp; stroke-width: 3px;' />";
					$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: $dash_tmp; stroke-width: 3px; stroke-dasharray: $seq_tmp;' />";
				}
				break;
			case "area":
				foreach (array_reverse($results['y_index']) as $y_tmp) {
					$c_tmp_x = $area['plot_x'];

					unset($points_tmp);
					unset($points_open_tmp);
					unset($points_close_tmp);

					$c_tmp_y = $area['plot_y'] + $area['plot_h'];
					$points_open_tmp .= "$c_tmp_x,$c_tmp_y ";

					foreach ($results['x_index'] as $x_tmp) {
						if (!is_numeric($results['results'][$y_tmp][$x_tmp])) {
							$c_tmp_x += $area['plot_w'] / (count($results['x_index']) - 1);
							continue;
						}

						$c_tmp_y = $area['plot_y'] + $area['plot_h'];

						$c_tmp = $results['results'][$y_tmp][$x_tmp];

						$c_tmp -= $results['min_c'];

						$c_tmp /= ($results['max_c'] - $results['min_c']);

						$c_tmp *= $area['plot_h'];

						$c_tmp_y -= $c_tmp;

						$points_tmp .= "$c_tmp_x,$c_tmp_y ";

						$c_tmp_x += $area['plot_w'] / (count($results['x_index']) - 1);
					}

					$c_tmp_x = $area['plot_x'] + $area['plot_w'];
					$c_tmp_y = $area['plot_y'] + $area['plot_h'];
					$points_close_tmp .= "$c_tmp_x,$c_tmp_y ";

					$c_tmp_x = $area['plot_x'];
					$c_tmp_y = $area['plot_y'] + $area['plot_h'];
					$points_close_tmp .= "$c_tmp_x,$c_tmp_y ";

					$dash_tmp = $results['y_colors'][$y_tmp][0];
					$solid_tmp = $results['y_colors'][$y_tmp][1];
					$seq_tmp = $results['y_colors'][$y_tmp][2];

// 					$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: #eeeeec; stroke-width: 4px;' />";
// 					$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: $solid_tmp; stroke-width: 3px;' />";

					if (!empty($points_tmp_cache)) {
						if (empty($clip_index)) $clip_index = 0;

// 						$svg_data .= "<clipPath id='clip$clip_index'><polyline points='$points_open_tmp_cache $points_tmp_cache $points_close_tmp_cache' style='fill: black; stroke: none;' /></clipPath>";

// 						$svg_data .= "<polyline points='$points_open_tmp $points_tmp $points_close_tmp' style='fill: black; stroke: none; opacity: 0.5; filter: url(#gaussian_blur);' clip-path='url(#clip$clip_index)' />";

						$clip_index ++;
					}

					$points_open_tmp_cache = $points_open_tmp;
					$points_tmp_cache = $points_tmp;
					$points_close_tmp_cache = $points_close_tmp;

					$svg_data .= "<polyline points='$points_open_tmp $points_tmp $points_close_tmp' style='fill: $solid_tmp; stroke: none;' />";

					if (!empty($seq_tmp)) {
						$svg_data .= "<polyline points='$points_open_tmp $points_tmp $points_close_tmp' style='fill: url(#clubs".str_replace(",", "", $seq_tmp)."); stroke: none;' />";
					}

// 					$svg_data .= "<polyline points='$points_tmp' style='fill: none; stroke: $dash_tmp; stroke-width: 3px; stroke-dasharray: $seq_tmp;' />";
				}
				break;
			case "bar":
				$c_tmp_x = $area['plot_x'];
				$c_tmp_x += $area['plot_w'] / (count($results['x_index']) - 0) / 2;

				$zero_tmp_y = $area['plot_y'] + $area['plot_h'];
				$zero_tmp = 0;
				$zero_tmp -= $results['min_c'];
				$zero_tmp /= (($results['max_c'] - $results['min_c']) !== 0 ? ($results['max_c'] - $results['min_c']) : 1);
				$zero_tmp *= $area['plot_h'];
				$zero_tmp_y -= $zero_tmp;
				$zero_tmp_y = round($zero_tmp_y);

				foreach ($results['x_index'] as $x_tmp) {
					unset($bar_y_cache, $bar_y);
					unset($bar_height_cache, $bar_height);

					foreach (array_reverse($results['y_index']) as $y_tmp) {
						if (!is_numeric($results['results'][$y_tmp][$x_tmp])) {
							continue;
						}

						$dash_tmp = $results['y_colors'][$y_tmp][0];
						$solid_tmp = $results['y_colors'][$y_tmp][1];
						$seq_tmp = $results['y_colors'][$y_tmp][2];

						$c_tmp_y = $area['plot_y'] + $area['plot_h'];

						$c_tmp = $results['results'][$y_tmp][$x_tmp];

						$c_tmp -= $results['min_c'];

						$c_tmp /= ($results['max_c'] - $results['min_c']);

						$c_tmp *= $area['plot_h'];

						$c_tmp_y -= $c_tmp;

						$bar_y_cache = $bar_y;
						$bar_height_cache = $bar_height;

						$bar_width = round($area['plot_w'] / (count($results['x_index']) - 0) / 2) * 2 - 8;
// 						$bar_height = $area['plot_h'] + $area['plot_y'] - round($c_tmp_y);
						$bar_x = round($c_tmp_x) - $bar_width / 2;
// 						$bar_y = round($c_tmp_y);

						if (round($c_tmp_y) < $zero_tmp_y) {
							$bar_height = $zero_tmp_y - round($c_tmp_y);
							$bar_y = round($c_tmp_y);
						} else {
							$bar_height = round($c_tmp_y) - $zero_tmp_y;
							$bar_y = round($zero_tmp_y);
						}

						$svg_data .= "<rect x='$bar_x' y='$bar_y' width='$bar_width' height='$bar_height' style='fill: $solid_tmp; stroke: none;' />";

						$box_border_x = $bar_x + 0.5;
						$bar_border_y = $bar_y + 0.5;
						$bar_border_width = $bar_width - 1;
						$bar_border_height = $bar_height - 1;

						$svg_data .= "<rect x='$box_border_x' y='$bar_border_y' width='$bar_border_width' height='$bar_border_height' style='fill: none; stroke: $dash_tmp; stroke-width: 1px;' />";

						if (!empty($seq_tmp)) {
							$svg_data .= "<rect x='$bar_x' y='$bar_y' width='$bar_width' height='$bar_height' style='fill: url(#hatch".str_replace(",", "", $seq_tmp)."); stroke: none;' />";
						}

						if (!empty($bar_y_cache) && !empty($bar_height_cache)) {
							$box_shadow_x = $bar_x;
							$bar_shadow_y = $bar_y - min($bar_height_cache - $bar_height, 20);
							$bar_shadow_width = $bar_width;
							$bar_shadow_height = min($bar_height_cache - $bar_height, 20);

// 							$svg_data .= "<rect x='$box_shadow_x' y='$bar_shadow_y' width='$bar_shadow_width' height='$bar_shadow_height' style='fill: url(#bar_shadow); stroke: none;' />";
						}
					}

					$c_tmp_x += $area['plot_w'] / (count($results['x_index']) - 0);
				}
				break;
		}

		return $svg_data;
	}

	/**
	 * Wrap SVG
	 *
	 * Wrap SVG markup in the corect header tags and necessary definitions
	 *
	 * @param $svg_data XML markup used to build the SVG
	 * @param $graph_area_w SVG width
	 * @param $graph_area_h SVG height
	 * @return XML markup used to build the SVG
	 */
	function wrap_svg($svg_data, $graph_area_w, $graph_area_h) {
		$svg_data = "
			<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='$graph_area_w' height='$graph_area_h'>
				<defs>
					<linearGradient id='bar_shadow' x1='0%' y1='0%' x2='0%' y2='100%'>
						<stop offset='0%' style='stop-color: black; stop-opacity: 0;'/>
						<stop offset='100%' style='stop-color: black; stop-opacity: 0.25;'/>
					</linearGradient>
					<filter id='gaussian_blur'>
						<feGaussianBlur stdDeviation='5' />
					</filter>
					<pattern id='hatch88' patternUnits='userSpaceOnUse' x='0' y='0' width='16' height='16'>
						<polyline points='0,0 4,0 0,4 0,0' style='fill: black; stroke: none; opacity: 0.1;'/>
						<polyline points='16,0 16,4 4,16 0,16 0,12 12,0 16,0' style='fill: black; stroke: none; opacity: 0.1;'/>
						<polyline points='16,16 16,12 12,16 16,16' style='fill: black; stroke: none; opacity: 0.1;'/>
					</pattern>
					<pattern id='hatch416' patternUnits='userSpaceOnUse' x='0' y='0' width='16' height='16'>
						<polyline points='0,0 6,0 0,6 0,0' style='fill: black; stroke: none; opacity: 0.1;'/>
						<polyline points='16,0 16,6 6,16 0,16 0,10 10,0 16,0' style='fill: black; stroke: none; opacity: 0.1;'/>
						<polyline points='16,16 16,10 10,16 16,16' style='fill: black; stroke: none; opacity: 0.1;'/>
					</pattern>
					<pattern id='hatch164' patternUnits='userSpaceOnUse' x='0' y='0' width='16' height='16'>
						<polyline points='0,0 2,0 0,2 0,0' style='fill: black; stroke: none; opacity: 0.1;'/>
						<polyline points='16,0 16,2 2,16 0,16 0,14 14,0 16,0' style='fill: black; stroke: none; opacity: 0.1;'/>
						<polyline points='16,16 16,14 14,16 16,16' style='fill: black; stroke: none; opacity: 0.1;'/>
					</pattern>
					<path
						id='clubsml'
						d='M 3.3836173,3.9935 L 0.6326035,3.9935 L 0.6585564,3.8832 C 1.0154085,3.8075 1.252229,3.7329 1.3690188,3.6593 C 1.5441995,3.549 1.6869405,3.3858 1.7972425,3.1695 C 1.9097033,2.9532 1.9659345,2.725 1.9659368,2.485 C 1.9659345,2.4504 1.9648533,2.4006 1.9626925,2.3358 C 1.830763,2.6039 1.6696385,2.7996 1.4793188,2.9229 C 1.2911583,3.0462 1.0986741,3.1078 0.9018654,3.1078 C 0.650986,3.1078 0.4379556,3.0202 0.2627738,2.8451 C 0.087591,2.6699 -9.9999907e-08,2.4547 9.3702823e-14,2.1995 C -9.9999907e-08,1.9486 0.0800214,1.7377 0.2400649,1.5669 C 0.4001076,1.3938 0.5817782,1.3074 0.7850771,1.3074 C 0.9148408,1.3074 1.0911045,1.3625 1.3138688,1.4728 C 1.2230319,1.3214 1.1635565,1.2078 1.1354421,1.1322 C 1.1094879,1.0543 1.0965114,0.971 1.0965127,0.8824 C 1.0965114,0.6358 1.1819398,0.4271 1.352798,0.2562 C 1.5236535,0.0854 1.7366838,0 1.9918898,0 C 2.2470915,0 2.4612033,0.0854 2.6342255,0.2562 C 2.8094053,0.4271 2.8969965,0.6304 2.8969993,0.8662 C 2.8969965,1.0586 2.8223815,1.2609 2.6731553,1.4728 C 2.8548228,1.3863 2.9640413,1.3376 3.0008113,1.3268 C 3.059202,1.3095 3.1251655,1.3009 3.1987025,1.3009 C 3.4149735,1.3009 3.6020508,1.3863 3.7599353,1.5572 C 3.9199745,1.7258 3.999996,1.9356 4,2.1865 C 3.999996,2.446 3.9124048,2.6645 3.7372265,2.8418 C 3.5642028,3.017 3.355498,3.1046 3.1111113,3.1046 C 2.9748553,3.1046 2.8342768,3.0722 2.6893755,3.0072 C 2.5466315,2.9402 2.422274,2.8515 2.316302,2.7412 C 2.2406033,2.6612 2.146524,2.5261 2.0340635,2.3358 C 2.042712,2.6796 2.094618,2.9521 2.1897813,3.1532 C 2.2871025,3.3522 2.4352505,3.5177 2.6342255,3.6496 C 2.7683128,3.7383 3.0094588,3.8161 3.3576645,3.8832 L 3.3836173,3.9935'
						style='opacity: 0.25; fill: #000000; fill-opacity: 1; stroke: none;' />
					<path
						id='clubmed'
						d='M 6.767234,7.9868826 L 1.265207,7.9868826 L 1.317113,7.7663826 C 2.030817,7.6148826 2.504458,7.4656826 2.738037,7.3185826 C 3.088399,7.0979826 3.373881,6.7714826 3.594485,6.3388826 C 3.819406,5.9063826 3.931869,5.4499826 3.931873,4.9698826 C 3.931869,4.9006826 3.929706,4.8011826 3.925385,4.6714826 C 3.661526,5.2077826 3.339277,5.5991826 2.958637,5.8457826 C 2.582316,6.0923826 2.197348,6.2155826 1.803731,6.2155826 C 1.301972,6.2155826 0.875911,6.0403826 0.525547,5.6900826 C 0.175182,5.3396826 -9.9999531e-07,4.9092826 4.6895821e-12,4.3988826 C -9.9999531e-07,3.8971826 0.160042,3.4753826 0.48013,3.1336826 C 0.800215,2.7875826 1.163556,2.6146826 1.570154,2.6146826 C 1.829681,2.6146826 2.182209,2.7248826 2.627737,2.9455826 C 2.446063,2.6427826 2.327113,2.4155826 2.270884,2.2642826 C 2.218975,2.1085826 2.193022,1.9419826 2.193025,1.7647826 C 2.193022,1.2715826 2.363879,0.85418262 2.705596,0.51238262 C 3.047307,0.17078262 3.473367,-1.7382812e-05 3.983779,-1.7382812e-05 C 4.494183,-1.7382812e-05 4.922406,0.17078262 5.268451,0.51238262 C 5.61881,0.85418262 5.793993,1.2607826 5.793998,1.7322826 C 5.793993,2.1171826 5.644763,2.5216826 5.34631,2.9455826 C 5.709645,2.7724826 5.928082,2.6751826 6.001622,2.6535826 C 6.118404,2.6189826 6.250331,2.6016826 6.397405,2.6016826 C 6.829947,2.6016826 7.204101,2.7724826 7.51987,3.1142826 C 7.839949,3.4515826 7.999992,3.8711826 8,4.3729826 C 7.999992,4.8919826 7.824809,5.3288826 7.474453,5.6835826 C 7.128405,6.0339826 6.710996,6.2091826 6.222222,6.2091826 C 5.94971,6.2091826 5.668553,6.1442826 5.378751,6.0143826 C 5.093263,5.8803826 4.844548,5.7029826 4.632604,5.4823826 C 4.481206,5.3223826 4.293048,5.0520826 4.068127,4.6714826 C 4.085424,5.3591826 4.189236,5.9041826 4.379562,6.3063826 C 4.574205,6.7043826 4.870501,7.0352826 5.268451,7.2991826 C 5.536625,7.4764826 6.018917,7.6321826 6.715329,7.7663826 L 6.767234,7.9868826'
						style='opacity: 0.25; fill: #000000; fill-opacity: 1; stroke: none;' />
					<path
						id='clublrg'
						d='M 13.534469,15.9739 L 2.530414,15.9739 L 2.634226,15.5328 C 4.061634,15.2299 5.008916,14.9315 5.476075,14.6373 C 6.176798,14.1961 6.747762,13.543 7.18897,12.6779 C 7.638813,11.8128 7.863738,10.9001 7.863747,9.9398 C 7.863738,9.8014 7.859413,9.6024 7.85077,9.343 C 7.323052,10.4157 6.678554,11.1985 5.917275,11.6917 C 5.164633,12.1848 4.394696,12.4313 3.607462,12.4313 C 2.603944,12.4313 1.751823,12.0809 1.051095,11.3803 C 0.350364,10.6795 -9.9999766e-07,9.8187 2.344791e-12,8.7979 C -9.9999766e-07,7.7944 0.320085,6.9509 0.96026,6.2675 C 1.600431,5.5753 2.327113,5.2294 3.140309,5.2294 C 3.659363,5.2294 4.364418,5.4499 5.255475,5.8912 C 4.892128,5.2856 4.654226,4.8313 4.541768,4.5286 C 4.437951,4.2173 4.386045,3.8841 4.38605,3.5296 C 4.386045,2.5432 4.727759,1.7085 5.411192,1.0249 C 6.094614,0.3416 6.946735,-3.5527137e-15 7.967559,-3.5527137e-15 C 8.988366,-3.5527137e-15 9.844813,0.3416 10.536902,1.0249 C 11.237621,1.7085 11.587986,2.5216 11.587997,3.4646 C 11.587986,4.2345 11.289526,5.0435 10.692621,5.8912 C 11.419291,5.5451 11.856165,5.3505 12.003245,5.3072 C 12.236808,5.2381 12.500662,5.2035 12.79481,5.2035 C 13.659894,5.2035 14.408203,5.5451 15.039741,6.2286 C 15.679898,6.9033 15.999984,7.7424 16,8.7461 C 15.999984,9.7841 15.649619,10.6579 14.948906,11.3673 C 14.256811,12.068 13.421992,12.4184 12.444445,12.4184 C 11.899421,12.4184 11.337107,12.2886 10.757502,12.0289 C 10.186526,11.7609 9.689096,11.4061 9.265208,10.9649 C 8.962413,10.6448 8.586096,10.1043 8.136254,9.343 C 8.170848,10.7184 8.378472,11.8085 8.759125,12.6129 C 9.14841,13.4088 9.741002,14.0707 10.536902,14.5984 C 11.073251,14.9531 12.037835,15.2645 13.430658,15.5328 L 13.534469,15.9739'
						style='opacity: 0.25; fill: #000000; fill-opacity: 1; stroke: none;' />
					<pattern id='clubs88' patternUnits='userSpaceOnUse' x='0' y='0' width='32' height='32'>
						<use x='0' y='0' transform='translate(4,4.00655)' xlink:href='#clubmed' />
						<use x='0' y='0' transform='translate(20,20.00655)' xlink:href='#clubmed' />
					</pattern>
					<pattern id='clubs416' patternUnits='userSpaceOnUse' x='0' y='0' width='32' height='32'>
						<use x='0' y='0' transform='translate(6,6.0032326)' xlink:href='#clubsml' />
						<use x='0' y='0' transform='translate(22,22.003233)' xlink:href='#clubsml' />
					</pattern>
					<pattern id='clubs164' patternUnits='userSpaceOnUse' x='0' y='0' width='32' height='32'>
						<use x='0' y='0' transform='translate(0,1.3032617e-2)' xlink:href='#clublrg' />
						<use x='0' y='0' transform='translate(16,16.013033)' xlink:href='#clublrg' />
					</pattern>
				</defs>
				$svg_data
			</svg>
			";
		return $svg_data;
	}

	/**
	 * Export SVG
	 *
	 * Export svg and store on server
	 *
	 * @param $filename_path Filesystem location to write the completed SVG to
	 * @param $svg_data XML markup used to build the SVG
	 * @param $graph_area_w SVG width
	 * @param $graph_area_h SVG height
	 * @return
	 */
	function export_svg($filename_path, $svg_data, $graph_area_w, $graph_area_h) {
		$svg_data = $this->wrap_svg($svg_data, $graph_area_w, $graph_area_h);

		file_put_contents($filename_path, $svg_data);

		return;
	}

	/**
	 * Export PDF
	 *
	 * Export svg graph to pdf and store on server
	 */
	function export_pdf($paths, $svg_data, $graph_area_w, $graph_area_h) {
		$dpi = 72;
		$dpmm = $dpi / 25.4;

		$a4_page_w = 210 * $dpmm;
		$a4_page_h = 297 * $dpmm;

		$a4_page_margin = 10 * $dpmm;

		$a4_ratio = ($a4_page_w - ($a4_page_margin * 2)) / $graph_area_w;
		$header = null;
		$header_height = null;

// 		$header = "
// 			<span>%logo</span><br />
// 			<br />
// 			<span style='font-size: 18pt;'>Skoo</span><br />
// 			<br />
// 			<span style='font-size: 6pt;'>Skoo</span><span style='font-size: 8pt;'>Wop</span><br />
// 			<br />
// 			<span>Skoo</span><br />
// 			";
// 
// 		$header = str_replace("\n", "", $header);
// 		$header = str_replace("\t", "", $header);
// 
// 		$header_tmp = explode("<br />", $header);
// 
// 		$row_index = 0;
// 
// 		foreach ($header_tmp as $header_row) {
// 			preg_match_all("/font\-size: ([0-9]+)pt;/", $header_row, &$matches);
// 
// 			if (!empty($matches[1])) {
// 				$font_height_pt = max($matches[1]);
// 			} else {
// 				$font_height_pt = 10;
// 			}
// 
// 			$font_height_px = $font_height_pt * 1.7 / 2;
// 
// 			$line_height_px = $font_height_px * 1.8;
// 
// 			if (preg_match("/\%logo/", $header_row, &$matches)) {
// 				$logo_path = "/tmp/graphs/logo.png";
// 				list($image_width, $image_height) = getimagesize($logo_path);
// 
// 				if ($image_height > $line_height_px) {
// 					$line_height_px = $image_height + 10;
// 				}
// 
// 				$header_row_images[$row_index] = $logo_path;
// 			}
// 
// 			$header_rows[$row_index] = $header_row;
// 			$header_font_heights[$row_index] = $font_height_px;
// 			$header_line_heights[$row_index] = $line_height_px;
// 
// 			$row_index ++;
// 		}
// 
// 		unset($header);
// 
// 		foreach ($header_rows as $i => $header_row) {
// 			$header_row_tmp = $header_row;
// 			$header_row_tmp = preg_replace("/\<span\>(.*?)\<\/span\>/", "<tspan>$1</tspan>", $header_row_tmp);
// 			$header_row_tmp = preg_replace("/\<span style\=\'(.*?)\'\>(.*?)\<\/span\>/", "<tspan style='$1'>$2</tspan>", $header_row_tmp);
// 
// 			$font_height = array_sum($header_heights);
// 			$header_heights[] = $header_line_heights[$i];
// 
// 			$header .= "<rect x='0' y='$font_height' width='100' height='".$header_line_heights[$i]."' style='fill: red; opacity: 0.5;' />";
// 
// 			if (!empty($header_row_images[$i])) {
// 				$header .= "<image x='0' y='$font_height' width='48' height='48' xlink:href='".$header_row_images[$i]."' />";
// 			} else {
// 				$font_height += $header_line_heights[$i];
// 				$font_height -= $header_font_heights[$i];
// 
// 				$header .= "<text x='0' y='$font_height'>$header_row_tmp</text>";
// 			}
// 		}
// 
// 		$header_height = array_sum($header_heights);
// 
// 		$header = "
// 			<g style='font-family: Georgia; font-size: 10pt;'>
// 				$header
// 			</g>
// 			";

		$svg_data = "
			<g transform='translate($a4_page_margin, $a4_page_margin)'>
				$header
				<g transform='translate(0, $header_height)'>
					<g transform='scale($a4_ratio)'>
						$svg_data
					</g>
				</g>
			</g>
			";

		$graph_area_w = $a4_page_w;
		$graph_area_h = $a4_page_h;

		$this->export_svg($paths['pdf_tmp_path'], $svg_data, $graph_area_w, $graph_area_h);

// 		shell_exec($this->conf['paths']['svg2pdf_path']."svg2pdf '".$paths['pdf_tmp_path']."' '".$paths['pdf_path']."'");
		shell_exec("inkscape -z -A='".$paths['pdf_path']."' '".$paths['pdf_tmp_path']."'");

		return;
	}

	/**
	 * Graph Object
	 *
	 * Embed a svg in browser
	 */
	function graph_object() {
	}
}

class Graphing_View extends Template_View {
	/**
	 * Line Graph
	 *
	 * Generate svg for a line graph
	 */
	function line_graph($filename_url, $graph_area_w="", $graph_area_h="") {
		if (!empty($graph_area_w)) $graph_area_w = ceil($graph_area_w);
		if (!empty($graph_area_h)) $graph_area_h = ceil($graph_area_h);

		return "<object data='$filename_url' type='image/svg+xml' width='$graph_area_w' height='$graph_area_h' style='display: block; margin: 0px auto;'></object>";
	}

	/**
	 * Graph Object
	 *
	 * Generate html object tag to embed a svg in browser
	 */
	function graph_object() {
	}
}

?>
