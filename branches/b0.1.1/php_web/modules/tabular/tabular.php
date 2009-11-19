<?php

/**
 * Table.php
 *
 * The Table report template module.
 *
 * @author Evan Leybourn
 * @date 26-07-2008
 *
 */

class Tabular extends Template {
	var $conn;
	var $dobj;
	var $name = "Tabular";
	var $description = "A tabular report type. Multiple axis' with a numeric intersection between them.";

	/* The Top Menu hook function.
	 * Displays the module in the main menu. Or menu of primary functions.
	 */
	function hook_top_menu() {
		return null;
	}

	function hook_admin_tools() {
		return null;
	}

	function hook_workspace() {
		return null;
	}

	/* The Menu hook function.
	 * Displays items in the side bar. This can be dependant on the actual URL used.
	 */
	function hook_menu() {
		$menu = array();
		switch ($this->action) {
			default:
				$menu = parent::hook_menu($url);
		}
		return $menu;
	}

	/* The Template hook function.
	 * Is this module available within the Templates
	 */
	function hook_template_entry() {
		return array(
			"label"=>"Tabular Report",
			"module"=>"tabular"
		);
	}

	/* The Javascript hook function.
	 * Send the following javascript to the browser.
	 */
	function hook_javascript() {
		$js = parent::hook_javascript("tabular");
		return $js."
		function squidfoo(o) {
			var passContent = {};
			passContent[o.name] = o.value;
			ajax_load('".$this->webroot()."tabular/table_join_ajax/".$this->id."', passContent, 'squeebop');
		}
		";
	}

	function hook_query($template, $constraints, $constraint_logic=null, $demo=false) {
		//$templates contains information about how we show each axis, and how we show the intersection
		$templates_tmp = $template;
		unset($template);

		//order by type: x, y or c for the intersection
		foreach ($templates_tmp as $template_tmp) {
			$template[$template_tmp['type']] = $template_tmp;
		}

		if ($demo) {
			$limit = 10;
		}

		$intersection = $template['c'];
		$x_axis = $template['x'];
		$y_axis = $template['y'];

		//we deal with the intersection column first
		if ($intersection) {
			//set these variables for easy access
			$axis_name_tmp = $intersection['type'];
			$alias_tmp = "a".$axis_name_tmp;
			$table_id_tmp = $intersection['auto_column']['table_id'];
			$table_name_tmp = $intersection['auto_column']['table_name'];
			$column_name_tmp = $intersection['auto_column']['name'];
			$aggregate_tmp = $intersection['aggregate'];

			//depending on the aggregate, we need to do special things...
			switch ($aggregate_tmp) {
				case "countdistinct":
					$select[$axis_name_tmp] = "$aggregate_tmp(DISTINCT $alias_tmp.$column_name_tmp)";
					break;
				case "count":
				case "sum":
				case "min":
				case "max":
					//these aggregate functions only accept numeric data, so we need to cast the data to a float
					$select[$axis_name_tmp] = "$aggregate_tmp(CAST($alias_tmp.$column_name_tmp AS FLOAT))";
					//we can only cast to float if the data is numeric, so ignore any non-numeric data
					$where[] = "CAST($alias_tmp.$column_name_tmp AS TEXT) ~ '^-?[0-9]+.?[0-9]*$'";
					break;
				case "average":
					//as above but useing the avg() function
					$select[$axis_name_tmp] = "AVG(CAST($alias_tmp.$column_name_tmp AS FLOAT))";
					$where[] = "CAST($alias_tmp.$column_name_tmp AS TEXT) ~ '^-?[0-9]+.?[0-9]*$'";
					break;
				default:
					break;
			}

			//join the intersection table to the query
			$join_tables[$axis_name_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp);
		}

		//then we deal with the x and y axis columns
		foreach (array($x_axis, $y_axis) as $axis_tmp) {
			switch ($axis_tmp['axis_type']) {
				case "auto":
					//set these variables for easy access
					$axis_name_tmp = $axis_tmp['type'];
					$alias_tmp = "a".$axis_name_tmp;
					$table_id_tmp = $axis_tmp['auto_column']['table_id'];
					$table_name_tmp = $axis_tmp['auto_column']['table_name'];
					$table_join_id_tmp = $axis_tmp['table_join_id'];
					$column_name_tmp = $axis_tmp['auto_column']['name'];
					$sort_tmp = $axis_tmp['sort'];

					//is this axis column in the same table as the intersection column?
					if ($table_id_tmp == $intersection['auto_column']['table_id']) {
						//then just select results from the ac table: don't worry about joining the same table again
						$alias_tmp = "ac";
						$select[$axis_name_tmp] = "$alias_tmp.$column_name_tmp";
					//if not...
					} else {
						$select[$axis_name_tmp] = "$alias_tmp.$column_name_tmp";
						//...join this axis' table to the query
						$join_tables[$axis_name_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp, "join_id"=>$table_join_id_tmp);
					}

					unset($a_select);

					//get distinct values that will make up the index for this axis
					$a_select[$axis_name_tmp] = "DISTINCT $alias_tmp.$column_name_tmp";
					$query[$axis_name_tmp] = $this->hook_build_query($a_select, array("$table_name_tmp $alias_tmp" => 0), false, false, array("$axis_name_tmp $sort_tmp"), $limit);

					//if we're only previwing the report, then only get the first ten distinct values of this index
					if ($demo) {
						$where[] = "$alias_tmp.$column_name_tmp IN (".rtrim($query[$axis_name_tmp], ";").")";
					}

					//add the axis to the group by clause
					$group[$axis_name_tmp] = '"'.$axis_name_tmp.'"';

					//add the axis to the order by clause
					$order[$axis_name_tmp] = "\"$axis_name_tmp\" $sort_tmp";
					break;
				case "trend":
					//set these variables for easy access
					$axis_name_tmp = $axis_tmp['type'];
					$alias_tmp = "a".$axis_name_tmp;
					$table_id_tmp = $axis_tmp['trend_column']['table_id'];
					$table_name_tmp = $axis_tmp['trend_column']['table_name'];
					$table_join_id_tmp = $axis_tmp['table_join_id'];
					$column_name_tmp = $axis_tmp['trend_column']['name'];
					$sort_tmp = $axis_tmp['sort'];
					$start_date_tmp = $axis_tmp['start_date'];
					$end_date_tmp = $axis_tmp['end_date'];
					$interval_tmp = $axis_tmp['interval'];

					//is this axis column in the same table as the intersection column?
					if ($table_id_tmp == $intersection['auto_column']['table_id']) {
						//then just select results from the ac table: don't worry about joining the same table again
						$alias_tmp = "ac";
						$select[$axis_name_tmp] = "MIN($alias_tmp.$column_name_tmp)";
					//if not...
					} else {
						$select[$axis_name_tmp] = "MIN($alias_tmp.$column_name_tmp)";
						//...join this axis' table to the query
						$join_tables[$axis_name_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp, "join_id"=>$table_join_id_tmp);
					}

					unset($a_select);
					unset($a_where);
					unset($a_group);

					//start putting together the query that will get distinct values for the index
					$a_select[$axis_name_tmp] = "MIN($alias_tmp.$column_name_tmp)";

					switch ($interval_tmp) {
						case "hourly":
							//use extract to get the hour, the day and the month from the selected date column. Use group by to get distinct values of these columns
							$a_group[$axis_name_tmp] = "EXTRACT(HOUR FROM $alias_tmp.$column_name_tmp), EXTRACT(DOY FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";

							//same, but for the main query
							$group[$axis_name_tmp] = "EXTRACT(HOUR FROM $alias_tmp.$column_name_tmp), EXTRACT(DOY FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";
							break;
						default:
						case "daily":
							//as above, but for daily interval
							$a_group[$axis_name_tmp] = "EXTRACT(DOY FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";

							$group[$axis_name_tmp] = "EXTRACT(DOY FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";
							break;
						case "weekly":
							//as above, but for weekly interval
							$a_group[$axis_name_tmp] = "EXTRACT(WEEK FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";

							$group[$axis_name_tmp] = "EXTRACT(WEEK FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";
							break;
						case "monthly":
							//as above, but for monthly interval
							$a_group[$axis_name_tmp] = "EXTRACT(MONTH FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";

							$group[$axis_name_tmp] = "EXTRACT(MONTH FROM $alias_tmp.$column_name_tmp), EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";
							break;
						case "yearly":
							//as above, but for yearly interval
							$a_group[$axis_name_tmp] = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";

							$group[$axis_name_tmp] = "EXTRACT(YEAR FROM $alias_tmp.$column_name_tmp)";
							break;
					}

					$a_where[] = "$alias_tmp.$column_name_tmp >= '$start_date_tmp'";
					$a_where[] = "$alias_tmp.$column_name_tmp <= '$end_date_tmp'";

					$where[] = "$alias_tmp.$column_name_tmp >= '$start_date_tmp'";
					$where[] = "$alias_tmp.$column_name_tmp <= '$end_date_tmp'";

					//get distinct values that will make up the index for this axis
					$query[$axis_name_tmp] = $this->hook_build_query($a_select, array("$table_name_tmp $alias_tmp" => 0), $a_where, $a_group, array("$axis_name_tmp $sort_tmp"), $limit);

					//if we're only previwing the report, then only get the first ten distinct values of this index
					if ($demo) {
						$where[] = "$alias_tmp.$column_name_tmp IN (".rtrim($query[$axis_name_tmp], ";").")";
					}

					//add the axis to the order by clause
					$order[$axis_name_tmp] = "\"$axis_name_tmp\" $sort_tmp";
					break;
				case "manual":
					break;
				default:
					break;
			}
		}

		//now, we deal with the constraints
		if (is_array($constraints)) {
			//constraint logic contains the ands, ors, brackets, and constraint ids. Next we will replace the constraint ids with the constraints themselves
			$where_logical = "($constraint_logic)";

			foreach ($constraints as $constraint) {
				//set these variables for easy access
				$table_constraints_id_tmp = $constraint['tabular_constraints_id'];
				$alias_tmp = "c".$table_constraints_id_tmp;
				$table_id_tmp = $constraint['table_id'];
				$table_name_tmp = $constraint['table'];
				$table_join_id_tmp = $constraint['table_join_id'];
				$column_name_tmp = $constraint['column'];
				$type_tmp = $constraint['type'];
				$value_tmp = $constraint['value'];

				//is this contstraint's column in the same table as the intersection column?
				if ($table_id_tmp == $intersection['auto_column']['table_id']) {
					$alias_tmp = "ac";
				//if not...
				} else if (!empty($table_join_id_tmp)) {
					//...join this constraint's table to the query
					$join_tables[$alias_tmp] = array("table"=>$table_name_tmp, "table_id"=>$table_id_tmp, "alias"=>$alias_tmp, "join_id"=>$table_join_id_tmp);
				}

				unset($where_tmp);

				//generate the constraint SQL
				$where_tmp = $this->where($alias_tmp, $column_name_tmp, $type_tmp, $value_tmp);

				//place the constraint into the constraint logic
				$where_logical = str_replace($table_constraints_id_tmp, $where_tmp, $where_logical);
			}

			//add the constraints to the query's where clause
			$where[] = $where_logical;
		}

		//x before y!
		ksort($order);
		ksort($group);

		//generate the query
		$query['c'] = $this->hook_build_query($select, $join_tables, $where, $group, $order, pow($limit, 2));

// 		var_dump($query);

		return $query;
	}

	function hook_output($results/*, $template=false, $demo=false, $now=false*/) {
		$template = $results[1];
		$demo = $results[2];
		$now = $results[3];
		$pdf = $results[4];

		$results = $results[0];

// 		if (!$template) {
// 			$template = $this->get_columns($this->id);
// 		}

		$output = Tabular_View::hook_output($results, $template, $demo, $now, $pdf);
		return $output;
	}

	function view_add_select_object() {
		$object_id = $this->id;

		if (empty($object_id)) return;

		$temp =array();
		$temp['name'] = "Unnamed Report - ".date("g:i A l jS F, Y");
		$temp['module'] = "tabular";
		$temp['object_id'] = $object_id;
		$temp['template_id'] = $this->dobj->nextval("templates");
		$temp['header'] = $this->default_header();
		$temp['footer'] = $this->default_footer();
		$query = $this->dobj->insert($temp, "templates");
		$this->dobj->db_query($query);

		$this->redirect("tabular/add/".$temp['template_id']);
	}

	function view_add() {
		switch ($this->subvar) {
			case "x":
			case "y":
				switch ($this->subid) {
					case "type":
						if ((int)$this->id) {
							$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

							if (!empty($tabular_templates_query)) {
								$tabular_template = $tabular_templates_query;
							}
						}
						break;
					case "source":
					case "autosource":
					case "trendsource":
						if ((int)$this->id) {
							$this->current = $this->get_template($this->id);
							$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

							$blah = array();
							foreach ($tables['catalogue'] as $i => $column) {
								foreach ($column as $j => $cell) {
									$blah[$cell['column_id']] = $cell['table_name'].".".$cell['column_name'];
								}
							}

// 							$blah = $tables['catalogue'];

							if ($this->subid == "autosource") {
								$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));
							} else if ($this->subid == "trendsource") {
								$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));
							}

							if (!empty($tabular_templates_query)) {
								$tabular_template_auto = $tabular_templates_query;

								//edit times from "2009-06-01 00:00:00" to "2009-06-01": dojo doesn't understand times in datetextbox
								$tabular_template_auto['start_date'] = substr($tabular_template_auto['start_date'], 0, strpos($tabular_template_auto['start_date'], " "));
								$tabular_template_auto['end_date'] = substr($tabular_template_auto['end_date'], 0, strpos($tabular_template_auto['end_date'], " "));

								$_REQUEST['data']['column_id'] = $tabular_template_auto['column_id'];
								$table_join_ajax = $this->view_table_join_ajax($tabular_template_auto['table_join_id']);
								$table_join_ajax = $table_join_ajax->data;
								unset($_REQUEST['data']['column_id']);
							} else {
								$_REQUEST['data']['column_id'] = reset(array_keys($blah));
								$table_join_ajax = $this->view_table_join_ajax($tabular_template_auto['table_join_id']);
								$table_join_ajax = $table_join_ajax->data;
								unset($_REQUEST['data']['column_id']);
							}
						}
						break;
					case "manualsource":
						break;
					default:
						break;
				}
				break;
			case "c":
				switch ($this->subid) {
					case "source":
						if ((int)$this->id) {
							$this->current = $this->get_template($this->id);
							$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

							$blah = array();
							foreach ($tables['catalogue'] as $i => $column) {
								foreach ($column as $j => $cell) {
									$blah['options'][$cell['column_id']] = $cell['table_name'].".".$cell['column_name'];

									switch ($cell['data_type']) {
										default:
											break;
										case "text":
											$blah['option_warnings'][$cell['column_id']] = "Warning: The data type of the selected Source Column is ".ucwords($cell['data_type']).". This may cause unexpected results when calculating the Sum, Minimum, Maximum or Average values.";
											break;
									}
								}
							}

							$tabular_templates_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='c' LIMIT 1;"));

							if (!empty($tabular_templates_query)) {
								$tabular_template_auto = $tabular_templates_query;
							}
						}
						break;
					default:
						break;
				}
				break;
			case "preview":
				if ((int)$this->id) {
					$template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM templates WHERE template_id='".$this->id."';"));
					$blah = $template_query;

					$saved_report_id = $this->execute_demo($this->id);

					$template = $blah;

					if ($template['publish_table'] == "t") {
						$preview_table .= "<h3>Tabular Data</h3>";

						$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, true, true));
						$preview_table .= $table['pdf']['object'];
					}

					if ($template['publish_graph'] == "t") {
						$preview_table .= "<h3>Graphic Data</h3>";

						$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], true, false));
						$preview_table .= $graph['graphing']['object'];
					}
				}
				break;
			case "constraints":
				if ((int)$this->id) {
					switch ($this->subid) {
						default:
							$blah['default'] = true;
							break;
						case "addconstraints":
							$blah['addconstraints'] = true;
							break;
						case "addbrackets":
							$blah['addbrackets'] = true;
							break;
						case "addclosingbrackets":
							$blah['addclosingbrackets'] = true;
							break;
					}

					$constraints_query = $this->dobj->db_fetch_all($this->dobj->db_query("
						SELECT 
							tc.*, 
							tcl.*,
							c.name AS column_name, 
							t.name AS table_name 
						FROM 
							tabular_constraints tc 
							INNER JOIN tabular_constraint_logic tcl ON (tcl.template_id=tc.template_id) 
							INNER JOIN columns c ON (c.column_id=tc.column_id) 
							INNER JOIN tables t ON (t.table_id=c.table_id) 
						WHERE 
							tc.template_id='".$this->id."'
						ORDER BY
							tc.tabular_constraints_id
						;"));

					if (!empty($constraints_query)) {
						$blah['logic'] = $constraints_query[0]['logic'];

						$constraint_index = 0;

						foreach ($constraints_query as $constraint_tmp) {
							$constraint_id = $constraint_tmp['tabular_constraints_id'];
							$index_id = $constraint_tmp['index_id'];
							$table_name = $constraint_tmp['table_name'];
							$column_name = $constraint_tmp['column_name'];
							$column = ucwords($table_name).".".ucwords($column_name);

							$type_array = array(
								"eq"=>"Equals",
								"neq"=>"Does not Equal",
								"lt"=>"Is Less Than",
								"gt"=>"Is Greater Than",
								"lte"=>"Is Less Than or Equal To",
								"gte"=>"Is Greater Than or Equal To",
								"like"=>"Contains"
								);
							$type = strtolower($type_array[$constraint_tmp['type']]);

							$value = $constraint_tmp['value'];

							$constraints[$constraint_index]['constraint_id'] = $constraint_id;
							$constraints[$constraint_index]['index_id'] = $index_id;
							$constraints[$constraint_index]['foobar'] = "constraint";
							$constraints[$constraint_index]['constraint'] = "$column $type '$value'";

							$constraint_index ++;
						}

						if ($blah['default']) {
							$constraints_query = $constraints;
							$indentation = 0;

							foreach ($constraints_query as $constraint_index => $constraint_tmp) {
								$index_id_tmp = $constraint_tmp['index_id'];

								$new_constraints[] = array_merge((array)$constraint_tmp, (array)array("indentation" => $indentation));
							}

							$constraints = $new_constraints;
						}

						$blah['constraints'] = $constraints;
					}
				}
				break;
			case "editconstraint":
				if ($this->subid) {
					$blah = array();

					if ($this->subid == "new") {
					} else {
						$constraint_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_constraints WHERE tabular_constraints_id='".$this->subid."' LIMIT 1;"));

						$blah['data']['column_id'] = $constraint_query['column_id'];
						$blah['data']['type'] = $constraint_query['type'];
						$blah['data']['value'] = $constraint_query['value'];

						$_REQUEST['data']['column_id'] = $constraint_query['column_id'];
						$table_join_ajax = $this->view_table_join_ajax($constraint_query['table_join_id']);
						$table_join_ajax = $table_join_ajax->data;
						unset($_REQUEST['data']['column_id']);
					}

					$this->current = $this->get_template($this->id);
					$tables = $this->call_function("catalogue", "get_structure", array($this->current['object_id']));

					foreach ($tables['catalogue'] as $i => $column) {
						foreach ($column as $j => $cell) {
							$column_id = $cell['column_id'];

							$blah['options']['column_id'][$column_id] = $cell['table_name'].".".$cell['column_name'];

							switch ($cell['data_type']) {
								default:
									break;
								case "timestamp":
								case "timestamp with time zone":
								case "timestamp without time zone":
									$blah['column_types'][$column_id] = "date";
									break;
							}

							if ($cell['dropdown'] == "t") {
								if (!isset($obj)) {
									$obj = new Catalogue();
								}

								foreach ($obj->hook_query_source($this->current['object_id'], "SELECT DISTINCT ".$cell['column_sql_name']." FROM ".$cell['table_sql_name'].";") as $tmp) {
									$blah['column_options'][$column_id][$tmp[$cell['column_sql_name']]] = $tmp[$cell['column_sql_name']];
								}

							}
						}
					}

					$blah['options']['type'] = array(
						"eq"=>"Equals",
						"neq"=>"Does not Equal",
						"lt"=>"Less Than",
						"gt"=>"Greater Than",
						"lte"=>"Less Than or Equal To",
						"gte"=>"Greater Than or Equal To",
						"like"=>"Contains"
						);

					if ($this->subid == "new") {
						$_REQUEST['data']['column_id'] = reset(array_keys($blah['options']['column_id']));
						$table_join_ajax = $this->view_table_join_ajax($constraint_query['table_join_id']);
						$table_join_ajax = $table_join_ajax->data;
						unset($_REQUEST['data']['column_id']);
					}
				}
				break;
			case "publish":
				break;
			case "execution":
				break;
			case "access":
				break;
			default:
				$this->view_add_next();
				break;
		}


		$tabular_templates_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT tt.*, tta.tabular_templates_auto_id, ttt.tabular_templates_trend_id FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND ((tt.axis_type = 'auto' AND tta.tabular_templates_auto_id IS NOT NULL) OR (tt.axis_type = 'trend' AND ttt.tabular_templates_trend_id IS NOT NULL));"));

		if (!empty($tabular_templates_query)) {
			foreach ($tabular_templates_query as $tabular_template_tmp) {
				$tabular_templates[$tabular_template_tmp['type']] = $tabular_template_tmp;
			}
		}

		if (empty($tabular_templates['c'])) {
			$steps[0][0] = "Add Intersection";
			$steps[0][2] = true;
			$steps[0][3] = "disabled";
		} else {
			$steps[0][0] = "Edit Intersection";
			$steps[0][2] = false;
		}
		$steps[0][1] = $this->webroot()."tabular/add/".$this->id."/c/source";
		if ($this->subvar == "c") $steps[0][3] .= " current";

		if (empty($tabular_templates['x'])) {
			$steps[1][0] = "Add X Axis";
			$steps[1][2] = true;
			$steps[1][3] = "disabled";
		} else {
			$steps[1][0] = "Edit X Axis";
			$steps[1][2] = false;
		}
		$steps[1][1] = $this->webroot()."tabular/add/".$this->id."/x/type";
		if ($this->subvar == "x") $steps[1][3] .= " current";

		if (empty($tabular_templates['y'])) {
			$steps[2][0] = "Add Y Axis";
			$steps[2][2] = true;
			$steps[2][3] = "disabled";
		} else {
			$steps[2][0] = "Edit Y Axis";
			$steps[2][2] = false;
		}
		$steps[2][1] = $this->webroot()."tabular/add/".$this->id."/y/type";
		if ($this->subvar == "y") $steps[2][3] .= " current";

		$steps[3][0] = "Preview";
		$steps[3][1] = $this->webroot()."tabular/add/".$this->id."/preview";
		$steps[3][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[3][2]) $steps[3][3] = "disabled";
		if ($this->subvar == "preview") $steps[3][3] .= " current";

		$steps[4][0] = "Constraints";
		$steps[4][1] = $this->webroot()."tabular/add/".$this->id."/constraints";
		$steps[4][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[4][2]) $steps[4][3] = "disabled";
		if ($this->subvar == "constraints") $steps[4][3] .= " current";

		$steps[5][0] = "Publishing";
		$steps[5][1] = $this->webroot()."tabular/add/".$this->id."/publish";
		$steps[5][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[5][2]) $steps[5][3] = "disabled";
		if ($this->subvar == "publish") $steps[5][3] .= " current";

		$steps[6][0] = "Execution";
		$steps[6][1] = $this->webroot()."tabular/add/".$this->id."/execution";
		$steps[6][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[6][2]) $steps[6][3] = "disabled";
		if ($this->subvar == "execution") $steps[6][3] .= " current";

		$steps[7][0] = "Access";
		$steps[7][1] = $this->webroot()."tabular/add/".$this->id."/access";
		$steps[7][2] = empty($tabular_templates['c']) || empty($tabular_templates['x']) || empty($tabular_templates['y']);
		if ($steps[7][2]) $steps[7][3] = "disabled";
		if ($this->subvar == "access") $steps[7][3] .= " current";

		$template = $this->get_template($this->id);
		$output = Tabular_View::view_add($template, $blah, $steps, $preview_table, $tabular_template_auto, $table_join_ajax, $tabular_template);

		return $output;
	}

	function view_display_table() {
		/* Get all the tables and create the wrapped columns */
		$template = $this->get_columns($this->id);
		$object = $this->dobj->db_fetch($this->dobj->db_query("SELECT object_id FROM templates WHERE template_id='".$this->id."'"));
		$tables = $this->call_function("catalogue", "get_structure", array($object['object_id'], $constraints));
		$output = Tabular_View::view_display_table($tables, $template);
		return $output;
	}

	/**
	 * View Remove
	 *
	 * Used to delete a column from automatic fields.
	 *
	 */
	function view_remove() {
		//The url that this was called through will tell us two things: the template id and the column id.
		//We need to delete the entry for this column in the tabular_templates table. BUT, column id is not stored in this table. So, we have to query the tabular_templates_auto table to work out exactly which entry to delete
		$query = "SELECT * FROM tabular_templates t INNER JOIN tabular_templates_auto ta ON (ta.tabular_template_id=t.tabular_template_id) WHERE t.template_id='".$this->id."' AND ta.column_id='".$this->subvar."';";
		$cur = $this->dobj->db_fetch($this->dobj->db_query($query));
		if ($cur) {
			//delete the entry by it's tabular_template_id. This will cascade down to the tabular_templates_auto table
			$this->dobj->db_query("DELETE FROM tabular_templates WHERE tabular_template_id='".$cur['tabular_template_id']."';");
		}
		die();
	}

	function view_remove_constraint() {
		$query = "DELETE FROM tabular_constraints WHERE template_id=".$this->id." AND column_id='".$this->subvar."';";
		$cur = $this->dobj->db_query($query);
		die();
	}

	function view_save() {
		switch ($this->subvar) {
			case "cancel":
				break;
			case "x":
			case "y":
				switch ($this->subid) {
					case "typesubmit":
						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_template_id']) {
							$this->dobj->db_query($this->dobj->update(array("axis_type"=>$_REQUEST['data']['axis_type']), "tabular_template_id", $update_query['tabular_template_id'], "tabular_templates"));
						} else {
							$this->dobj->db_query($this->dobj->insert(array("template_id"=>$this->id, "type"=>$this->subvar, "axis_type"=>$_REQUEST['data']['axis_type']), "tabular_templates"));
						}
						break;
					case "autosourcesubmit":
						$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

						$tabular_template_id = $tabular_template_query['tabular_template_id'];
						$_REQUEST['data']['tabular_template_id'] = $tabular_template_id;

						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_templates_auto_id']) {
							if (empty($_REQUEST['data']['table_join_id'])) $_REQUEST['data']['table_join_id'] = "";
							$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_templates_auto_id", $update_query['tabular_templates_auto_id'], "tabular_templates_auto"));
						} else {
							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_auto"));
						}
						break;
					case "trendsourcesubmit":
						$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

						$tabular_template_id = $tabular_template_query['tabular_template_id'];
						$_REQUEST['data']['tabular_template_id'] = $tabular_template_id;

						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_templates_trend_id']) {
							$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_templates_trend_id", $update_query['tabular_templates_trend_id'], "tabular_templates_trend"));
						} else {
							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_trend"));
						}
						break;
					default:
						break;
				}
				break;
			case "c":
				switch ($this->subid) {
					case "sourcesubmit":
						$update_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."' AND tt.type='".$this->subvar."' LIMIT 1;"));

						if ($update_query['tabular_template_id']) {
						} else {
							$this->dobj->db_query($this->dobj->insert(array("template_id"=>$this->id, "type"=>$this->subvar, "axis_type"=>"auto"), "tabular_templates"));
						}

						if ($update_query['tabular_templates_auto_id']) {
							$tabular_template_id = $update_query['tabular_template_id'];

							$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_template_id", $tabular_template_id, "tabular_templates_auto"));
						} else {
							$tabular_template_query = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates WHERE template_id='".$this->id."' AND type='".$this->subvar."' LIMIT 1;"));

							$tabular_template_id = $tabular_template_query['tabular_template_id'];
							$_REQUEST['data']['tabular_template_id'] = $tabular_template_id;

							$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_templates_auto"));
						}
						break;
					default:
						break;
				}
				break;
			case "constraintlogicsubmit":
				$logic = $_REQUEST['data']['constraint_logic'];
				$constraints_id = json_decode(stripslashes($_REQUEST['data']['constraints_id']), true);
				$constraints_ascii = json_decode(stripslashes($_REQUEST['data']['constraints_ascii']), true);

				foreach ($constraints_ascii as $index_tmp => $ascii_tmp) {
					$logic = str_replace($ascii_tmp, $constraints_id[$index_tmp], $logic);
				}

				unset($_REQUEST['data']['constraint_logic']);
				unset($_REQUEST['data']['constraints_id']);
				unset($_REQUEST['data']['constraints_ascii']);

				$_REQUEST['data']['logic'] = $logic;

				$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "template_id", $this->id, "tabular_constraint_logic"));
				break;
			case "editconstraintsubmit":
				if ($this->subid) {
					$_REQUEST['data']['template_id'] = $this->id;

					$selected_input_id = $_REQUEST['data']['value_input_selected'];
					$selected_input_value = $_REQUEST['data'][$selected_input_id];

					$_REQUEST['data']['value'] = $selected_input_value;

					foreach (explode(",", $_REQUEST['data']['value_inputs']) as $input_id) {
						unset($_REQUEST['data'][$input_id]);
					}

					unset($_REQUEST['data']['value_inputs']);
					unset($_REQUEST['data']['value_input_selected']);

					if ($this->subid == "new") {
						$this->dobj->db_query($this->dobj->insert($_REQUEST['data'], "tabular_constraints"));
					} else {
						$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "tabular_constraints_id", $this->subid, "tabular_constraints"));
					}
				}
				break;
			case "removeconstraintsubmit":
				$template_id = $this->id;
				$constraint_id = $this->subid;

				if (empty($template_id)) return;
				if (empty($constraint_id)) return;

				$constraint_logic = $this->get_constraint_logic($template_id);

				//if the constraint to be removed is the only constraint in the logic: simply set logic to ''
				if (preg_match("/^ ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
					$constraint_logic = "";

// 				} else if (preg_match("/^ ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
// 					var_dump($matches);
// 					var_dump("INVALID");

				//if the constrain to be be removed is at the start and is followed by an and/or, then remove the constraint and the and/or
				} else if (preg_match("/^ ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/^ ?($constraint_id) ?(AND|OR) ?/", "", $constraint_logic);

// 				} else if (preg_match("/\( ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
// 					var_dump($matches);
// 					var_dump("INVALID");

				//if the constraint to be removed is on it's own in a set of brackets, then remove the constraint only. This will make the logic invailid...
				} else if (preg_match("/\( ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/\( ?($constraint_id) ?\)/", "", $constraint_logic);

				//if the constraint to be removed comes after a bracket and is followed by an and/or, then remove the constraint and the and/or
				} else if (preg_match("/ ?\( ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/\( ?($constraint_id) ?(AND|OR) ?/", "(", $constraint_logic);

				//if the constraint to be removed comes after an and/or and is at the end of the logic, then remove the and/or and the constraint
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?$/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/ ?(AND|OR) ?($constraint_id) ?$/", "", $constraint_logic);

				//if the constraint to be removed comes after an and/or and is followed by a bracket, then remove the and/or and the constraint
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?\)/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/ ?(AND|OR) ?($constraint_id) ?\)/", ")", $constraint_logic);

				//if the constraint to be removed comes after an and/or and is followed by another and/or, then remove the constraint and the second and/or
				} else if (preg_match("/ ?(AND|OR) ?($constraint_id) ?(AND|OR) ?/", $constraint_logic, &$matches)) {
					$constraint_logic = preg_replace("/ ?($constraint_id) ?(AND|OR) ?/", " ", $constraint_logic);

				} else {
				}

				$this->dobj->db_query($this->dobj->update(array("logic"=>$constraint_logic), "template_id", $this->id, "tabular_constraint_logic"));

				$this->dobj->db_query("DELETE FROM tabular_constraints WHERE tabular_constraints_id='$constraint_id';");
				break;
			case "publishsubmit":
				if ($_REQUEST['data']['publish_table'] == "on") {
					$_REQUEST['data']['publish_table'] = "t";
				} else {
					$_REQUEST['data']['publish_table'] = "f";
				}

				if ($_REQUEST['data']['publish_graph'] == "on") {
					$_REQUEST['data']['publish_graph'] = "t";
				} else {
					$_REQUEST['data']['publish_graph'] = "f";
				}

				$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "template_id", $this->id, "templates"));
				break;
			case "executionsubmit":
				$_REQUEST['data']['execute'] = "f";
				$_REQUEST['data']['execute_hourly'] = "f";
				$_REQUEST['data']['execute_daily'] = "f";
				$_REQUEST['data']['execute_weekly'] = "f";
				$_REQUEST['data']['execute_monthly'] = "f";

				switch ($_REQUEST['data']['execution_interval']) {
					case "manually":
						break;
					case "hourly":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_hourly'] = "t";
						break;
					case "daily":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_daily'] = "t";
						break;
					case "weekly":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_weekly'] = "t";
						break;
					case "monthly":
						$_REQUEST['data']['execute'] = "t";
						$_REQUEST['data']['execute_monthly'] = "t";
						break;
				}

				unset($_REQUEST['data']['execution_interval']);

				if ($_REQUEST['data']['email_dissemination'] == "on") {
					$_REQUEST['data']['email_dissemination'] = "t";
				} else {
					$_REQUEST['data']['email_dissemination'] = "f";
				}

				$ldap_recipient_selector = ($_REQUEST['data']['ldap'] == "ldap");
				unset($_REQUEST['data']['ldap']);

				$this->dobj->db_query($this->dobj->update($_REQUEST['data'], "template_id", $this->id, "templates"));

				if ($ldap_recipient_selector) {
					$this->redirect("ldap/recipient_selector/".$this->id);
					die();
				}
				break;
			default:
				break;
		}

		$this->view_add_next();
		return;
	}

	function view_add_next() {
		if (empty($this->id)) {
			$this->redirect("template/home/");
			return;
		}

		$tabular_template_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM tabular_templates tt LEFT OUTER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) LEFT OUTER JOIN tabular_templates_trend ttt ON (ttt.tabular_template_id=tt.tabular_template_id) WHERE tt.template_id='".$this->id."'"));

		if (!empty($tabular_template_query)) {
			foreach ($tabular_template_query as $tabular_template_tmp) {
				$tabular_template[$tabular_template_tmp['type']] = $tabular_template_tmp;
			}
		}

		if ($this->subvar == "x" && $this->subid == "typesubmit" && $tabular_template['x']['axis_type'] == "auto") {
			$this->redirect("tabular/add/".$this->id."/x/autosource");

		} else if ($this->subvar == "x" && $this->subid == "typesubmit" && $tabular_template['x']['axis_type'] == "trend") {
			$this->redirect("tabular/add/".$this->id."/x/trendsource");

		} else if ($this->subvar == "y" && $this->subid == "typesubmit" && $tabular_template['y']['axis_type'] == "auto") {
			$this->redirect("tabular/add/".$this->id."/y/autosource");

		} else if ($this->subvar == "y" && $this->subid == "typesubmit" && $tabular_template['y']['axis_type'] == "trend") {
			$this->redirect("tabular/add/".$this->id."/y/trendsource");

		} else if (empty($tabular_template['c'])) {
			$this->redirect("tabular/add/".$this->id."/c/source");

		} else if (empty($tabular_template['c']['tabular_templates_auto_id'])) {
			$this->redirect("tabular/add/".$this->id."/c/source");

		} else if (empty($tabular_template['x'])) {
			$this->redirect("tabular/add/".$this->id."/x/type");

		} else if ($tabular_template['x']['axis_type'] == "auto" && empty($tabular_template['x']['tabular_templates_auto_id'])) {
			$this->redirect("tabular/add/".$this->id."/x/autosource");

		} else if ($tabular_template['x']['axis_type'] == "trend" && empty($tabular_template['x']['tabular_templates_trend_id'])) {
			$this->redirect("tabular/add/".$this->id."/x/trendsource");

		} else if (empty($tabular_template['y'])) {
			$this->redirect("tabular/add/".$this->id."/y/type");

		} else if ($tabular_template['y']['axis_type'] == "auto" && empty($tabular_template['y']['tabular_templates_auto_id'])) {
			$this->redirect("tabular/add/".$this->id."/y/autosource");

		} else if ($tabular_template['y']['axis_type'] == "trend" && empty($tabular_template['y']['tabular_templates_trend_id'])) {
			$this->redirect("tabular/add/".$this->id."/y/trendsource");

		} else if ($this->subvar == "editconstraintsubmit") {
			$this->redirect("tabular/add/".$this->id."/constraints");

		} else if ($this->subvar == "removeconstraintsubmit") {
			$this->redirect("tabular/add/".$this->id."/constraints");

		} else if ($this->subvar == "addbracketsubmit") {
			$this->redirect("tabular/add/".$this->id."/constraints");

		} else if ($this->subvar == "addclosingbracketsubmit") {
			$this->redirect("tabular/add/".$this->id."/constraints");

		} else {
			$this->redirect("tabular/add/".$this->id."/preview");

		}
		return;
	}

	function view_table_join_ajax($current_join=null) {
		$intersection = $this->dobj->db_fetch($this->dobj->db_query("SELECT * FROM tabular_templates tt INNER JOIN tabular_templates_auto tta ON (tta.tabular_template_id=tt.tabular_template_id) INNER JOIN columns c ON (c.column_id=tta.column_id) WHERE tt.template_id='".$this->id."' AND tt.type='c' LIMIT 1;"));
		if (empty($intersection)) die;

		$selected_column_id = $_REQUEST['data']['column_id'];
		$intersection_column_id = $intersection['column_id'];


		$foobar .= "<h3>Axis Relationship</h3>";
		$foobar .= "<p class='h3attach'>The selected column may be linked to the intersection column, by one of a number of different routes.</p>";

		//self referential joins
		$sr_joins_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT c.*, t.*, c.name AS column_name, t.name AS table_name FROM columns c INNER JOIN tables t ON (t.table_id=c.table_id) WHERE c.column_id='".$selected_column_id."' OR c.column_id='".$intersection_column_id."';"));

		foreach ($sr_joins_query as $sr_join_tmp) {
			$sr_joins[$sr_join_tmp['column_id']] = $sr_join_tmp;
		}

		$selected_table_id = $sr_joins[$selected_column_id]['table_id'];
		$intersection_table_id = $sr_joins[$intersection_column_id]['table_id'];

		if ($selected_table_id == $intersection_table_id) {
			$foobar .= "<div class='input radio'>";
			$foobar .= "<input type='radio' name='data[table_join_id]' checked='true' disabled='true' />";
			$foobar .= "<label>";
			$foobar .= "<span style='font-weight: bold;'>";
			$foobar .= $sr_joins[$selected_column_id]['table_name'];
			$foobar .= ".";
			$foobar .= $sr_joins[$selected_column_id]['column_name'];
			$foobar .= "</span>";
			$foobar .= " &#x21C4; ";
			$foobar .= "<span style='font-weight: bold;'>";
			$foobar .= $sr_joins[$intersection_column_id]['table_name'];
			$foobar .= ".";
			$foobar .= $sr_joins[$intersection_column_id]['column_name'];
			$foobar .= "</span>";
			$foobar .= "</label>";
			$foobar .= "</div>";
			
			$explaination_tmp = "The selected column is linked, via a self referential join, to the intersection column.";
			$foobar .= "<p>".$explaination_tmp."</p>";
		}

		$table_joins_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM table_joins WHERE table1='".$selected_table_id."' AND table2='".$intersection_table_id."';"));

		if (empty($table_joins_query)) {
			$output = Tabular_View::view_table_join_ajax($foobar);
			return $output;
		}

		$columns_tmp = array($selected_column_id, $intersection_column_id);
		foreach ($table_joins_query as $table_join_tmp) {
			$columns_tmp = array_merge((array)$columns_tmp, (array)explode(",", $table_join_tmp['method']));
			$table_joins[$table_join_tmp['table_join_id']] = $table_join_tmp;
		}
		$columns_tmp = array_unique($columns_tmp);

		$columns_query = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT c.column_id, c.name as column_name, t.table_id, t.name as table_name FROM columns c INNER JOIN tables t ON (c.table_id=t.table_id) WHERE c.column_id='".implode("' OR c.column_id='", $columns_tmp)."';"));
		foreach ($columns_query as $column_tmp) {
			$columns[$column_tmp['column_id']] = $column_tmp;
			$tables[$column_tmp['table_id']] = $column_tmp;
		}

		foreach ($table_joins as $table_join) {
			$table_join_id = $table_join['table_join_id'];

			$method_tmp = explode(",", $table_join['method']);

			$method_start_table = $table_join['table1'];
			$method_end_table = $table_join['table2'];

			$this_pair_start_id = 0;
			$this_pair_end_id = $this_pair_start_id + 1;

			$last_pair_start_table = $method_start_table;

			unset($method_reorg);

			while ($method_tmp[$this_pair_start_id]) {
				$this_pair_start_table = $columns[$method_tmp[$this_pair_start_id]]['table_id'];

				if ($this_pair_start_id !== 0) $method_reorg[] = "internal join";

				if ($last_pair_start_table != $this_pair_start_table) {
					$method_reorg[] = $method_tmp[$this_pair_end_id];
					$method_reorg[] = "referenced by";
					$method_reorg[] = $method_tmp[$this_pair_start_id];
				} else {
					$method_reorg[] = $method_tmp[$this_pair_start_id];
					$method_reorg[] = "references";
					$method_reorg[] = $method_tmp[$this_pair_end_id];
				}

				$last_pair_start_table = $columns[$method_reorg[$this_pair_end_id]]['table_id'];

				$this_pair_start_id += 2;
				$this_pair_end_id = $this_pair_start_id + 1;
			}


			$foobar .= "<div class='input'>";
			$foobar .= "<input type='radio' name='data[table_join_id]' value='".$table_join_id."' ".($current_join == $table_join_id ? "checked=\"checked\"" : "")." /><label>";

			unset($explaination_tmp);
			unset($explaination_count);
			unset($explaination_sr_count);
			unset($explaination_in_count);
			$explaination_tmp .= "The selected column is linked, ";

			if ($method_reorg[0] != $selected_column_id) {
				$column = $columns[$selected_column_id];

				$foobar .= "<span style='font-weight: bold;'>";
				$foobar .= ucwords($column['table_name']);
				$foobar .= ".";
				$foobar .= ucwords($column['column_name']);
				$foobar .= "</span>";

				$foobar .= " &#x21C4; ";

				$explaination_tmp .= "via a self referential join, ";
				$explaination_count += 1;
				$explaination_sr_count += 1;
			}

			foreach ($method_reorg as $method_step) {
				if ($method_step == $selected_column_id || $method_step == $intersection_column_id) $foobar .= "<span style='font-weight: bold;'>";

				switch ($method_step) {
					case "internal join":
						$foobar .= " &#x21C4; ";

						$explaination_tmp .= ($explaination_count > 0 ? "then " : "")."via ".($explaination_sr_count > 0 ? "another" : "a")." self referential join, ";
						$explaination_count += 1;
						$explaination_sr_count += 1;
						break;
					case "references":
					case "referenced by":
						$foobar .= " <span style='font-style: italic;'>";
						$foobar .= $method_step;
						$foobar .= "</span> ";

						$explaination_tmp .= ($explaination_count > 0 ? "then " : "")."via ".($explaination_in_count > 0 ? "another" : "an")." inner join, ";
						$explaination_count += 1;
						$explaination_in_count += 1;
						break;
					default:
						$column = $columns[$method_step];

						$foobar .= ucwords($column['table_name']);
						$foobar .= ".";
						$foobar .= ucwords($column['column_name']);
						break;
				}

				if ($method_step == $selected_column_id || $method_step == $intersection_column_id) $foobar .= "</span>";
			}

			if (end($method_reorg) != $intersection_column_id) {
				$foobar .= " &#x21C4; ";

				$column = $columns[$intersection_column_id];

				$foobar .= "<span style='font-weight: bold;'>";
				$foobar .= ucwords($column['table_name']);
				$foobar .= ".";
				$foobar .= ucwords($column['column_name']);
				$foobar .= "</span>";

				$explaination_tmp .= ($explaination_count > 0 ? "then " : "")."via ".($explaination_sr_count > 0 ? "another" : "a")." self referential join, ";
				$explaination_count += 1;
				$explaination_sr_count += 1;
			}

			$foobar .= "</label>";
			$foobar .= "</div>";

			$explaination_tmp .= "to the intersection column.";
			$foobar .= "<p>".$explaination_tmp."</p>";
		}

		$output = Tabular_View::view_table_join_ajax($foobar);
		return $output;
	}

	function hook_run($demo=false, $data_only=false, $draft=true, $template_id=null) {
		if (!empty($template_id)) {
			$this->id = $template_id;
		}

		$template = $this->get_columns($this->id);
		$constraints = $this->get_constraints($this->id);
		$constraint_logic = $this->get_constraint_logic($this->id);

// 		/* Concatenate the predefined constraints and the user defined constraints */
// 		if ($_REQUEST['data']['constraint']) {
// 			foreach ($_REQUEST['data']['constraint'] as $i => $cons) {
// 				foreach ($constraints as $j => $cons2) {
// 					if ($cons2['tabular_constraints_id'] == $i) {
// 						$constraints[$j]['value'] = $cons;
// 						break(2);
// 					}
// 				}
// 			}
// 		}

		/* Generate the query to run */
		$query = $this->hook_query($template, $constraints, $constraint_logic, $demo);

		/* Run the query and get the results */
		$start = time();
		$data = parent::hook_run_query($template[0]['object_id'], $query);
		$end = time();

		if (!$demo) {
			/* Only update the run statistics if this is a complete run, not a preview run */
			$update_query = "UPDATE templates SET last_run=now(), last_time='".($end-$start)."', last_by=1, last_size=".count($data)." WHERE template_id=".$this->id."";
			$update = $this->dobj->db_query($update_query);
			$saved_report_id = $this->save_results($this->id, $data, ($draft ? "t" : "f"), 'f', ($end-$start), 1);
		} else {
			$saved_report_id = $this->save_results($this->id, $data, ($draft ? "t" : "f"), 't', ($end-$start), 1);
		}

		if ($data_only) {
			return $saved_report_id;
		} else {
			$foo_json = $this->get_saved_report();
			$foo_json = $foo_json['report'];

			return Tabular_View::hook_run($data, $template, $demo, $now, $foo_json);
		}
	}

	function view_run() {
// 		$template = $this->get_columns($this->id);
// 		$constraints = $this->get_constraints($this->id);
// 		$output = true;
// 		/* We skip this step if the constraints values are already populated from the $_REQUEST array */
// 		if (empty($_REQUEST['data']['constraint'])) {
// 			/* Get the constraints */
// 			$output = Tabular_View::view_run($template, $constraints);
// 		}
// 		/* If there a form to fill out? If not (either because there are no user modifiable constriants, or the form has
// 		 * already been filled out) go directly to running the report.  */
// 		if ($output === true) {
// 			$output = $this->hook_run();
// 		}
// 
// 		return $output;

		$template_id = null;
		$saved_report_id = $this->id;

		$return_tmp = $this->get_saved_report($template_id, $saved_report_id);

		$template_id = $return_tmp['template_id'];

		$data = json_decode($return_tmp['report'], true);
		$template = $this->get_columns($template_id);
		$demo = false;
		$now = null;
		$foo_json = $return_tmp['report'];

		return Tabular_View::hook_run($data, $template, $demo, $now, $foo_json);
	}

	function view_tables_json() {
		$template = $this->get_columns($this->id);
		$output = Tabular_View::view_tables_json($template);
		return $output;
	}

	function view_interval_dd_json() {
		$values = array("daily"=>"Daily", "weekly"=>"Weekly", "monthly"=>"Monthly", "quarterly"=>"Quarterly", "yearly"=>"Yearly");
		$output = Tabular_View::view_dd_json($values);
		return $output;
	}

	function get_columns($template_id, $type=false) {
		if ($type) {
			$type = " AND l.type='".$type."'";
		} else {
			$type = "";
		}

		$tabular_templates_type_query = $this->dobj->db_fetch_all($this->dobj->db_query("
SELECT 
  *, 
  a.column_id as auto_column_id, 
  tr.column_id as trend_column_id,
  a.table_join_id as auto_join_id,
  tr.table_join_id as trend_join_id,
  a.human_name as tabular_template_auto_human_name,
  tr.human_name as tabular_template_trend_human_name
FROM 
  templates t, 
  tabular_templates l
  LEFT OUTER JOIN tabular_templates_auto a ON (l.tabular_template_id=a.tabular_template_id) 
  LEFT OUTER JOIN tabular_templates_trend tr ON (l.tabular_template_id=tr.tabular_template_id) 
  LEFT OUTER JOIN tabular_templates_manual m ON (l.tabular_template_id=m.tabular_template_id) 
WHERE 
  t.template_id=l.template_id ".$type." 
  AND t.template_id=".$template_id.";"));

		foreach ($tabular_templates_type_query as $tabular_templates_type_tmp) {
			if (!empty($tabular_templates_type_tmp['auto_column_id'])) {
				$column_ids_array[] = $tabular_templates_type_tmp['auto_column_id'];
			}
			if (!empty($tabular_templates_type_tmp['trend_column_id'])) {
				$column_ids_array[] = $tabular_templates_type_tmp['trend_column_id'];
			}
		}

		$columns_query = $this->dobj->db_fetch_all($this->dobj->db_query("
SELECT 
  c.column_id,
  c.name,
  t.table_id,
  t.name as table_name
FROM 
  columns c 
  INNER JOIN tables t ON (t.table_id=c.table_id) 
WHERE 
  c.column_id='".implode("' OR c.column_id='", $column_ids_array)."';"));

		foreach ($columns_query as $column_tmp) {
			$columns[$column_tmp['column_id']] = $column_tmp;
		}

		foreach ($tabular_templates_type_query as $i => $tabular_templates_type_tmp) {
			if (!empty($tabular_templates_type_tmp['auto_column_id'])) {
				$tabular_templates_type_query[$i]['auto_column'] = $columns[$tabular_templates_type_tmp['auto_column_id']];
			}
			if (!empty($tabular_templates_type_tmp['trend_column_id'])) {
				$tabular_templates_type_query[$i]['trend_column'] = $columns[$tabular_templates_type_tmp['trend_column_id']];
			}

			switch ($tabular_templates_type_tmp['axis_type']) {
				case "auto":
					$tabular_templates_type_query[$i]['table_join_id'] = $tabular_templates_type_query[$i]['auto_join_id'];

					if (empty($tabular_templates_type_tmp['tabular_template_auto_human_name'])) {
						$tabular_templates_type_query[$i]['tabular_template_auto_human_name'] = $tabular_templates_type_query[$i]['auto_column']['name'];
					}
					$tabular_templates_type_query[$i]['tabular_template_human_name'] = $tabular_templates_type_query[$i]['tabular_template_auto_human_name'];
					break;
				case "trend":
					$tabular_templates_type_query[$i]['table_join_id'] = $tabular_templates_type_query[$i]['trend_join_id'];

					if (empty($tabular_templates_type_tmp['tabular_template_trend_human_name'])) {
						$tabular_templates_type_query[$i]['tabular_template_trend_human_name'] = $tabular_templates_type_query[$i]['trend_column']['name'];
					}
					$tabular_templates_type_query[$i]['tabular_template_human_name'] = $tabular_templates_type_query[$i]['tabular_template_trend_human_name'];
					break;
				default:
					break;
			}
		}

		$data = $tabular_templates_type_query;

		return $data;
	}

	function get_constraints($template_id) {
		$query = "SELECT l.*, t.template_id, t.name, t.draft, t.module, t.object_id, c.column_id, tb.table_id, c.human_name as chuman, tb.human_name as thuman, c.name as column, tb.name as table FROM tabular_constraints l, templates t, columns c, tables tb WHERE tb.table_id=c.table_id AND c.column_id=l.column_id AND t.template_id=l.template_id AND t.template_id=".$template_id.";";
		$data = $this->dobj->db_fetch_all($this->dobj->db_query($query));
		return $data;
	}

	function get_constraint_logic($template_id) {
		$query = "SELECT * FROM tabular_constraint_logic WHERE template_id='$template_id' LIMIT 1;";
		$data = $this->dobj->db_fetch($this->dobj->db_query($query));
		return $data['logic'];
	}

	function view_add_axis() {
		$columns = $this->get_columns($this->id, $this->subvar);
		$output = Tabular_View::view_add_axis($this->subvar, $columns);
		return $output;
	}

	function view_add_intersection() {
		$columns = $this->get_columns($this->id, 'c');
		$output = Tabular_View::view_add_intersection($columns);
		return $output;
	}

	function view_clone() {
		$template = "SELECT * FROM templates WHERE template_id='".$this->id."';";
		$template = $this->dobj->db_fetch($this->dobj->db_query($template));
		unset($template['template_id']);
		$template['name'] = $template['name'] ." (Clone)";
		$template['database'] = $template['object_id'];
		$template_id = $this->add_template($template);
		$list_templates = "SELECT * FROM tabular_templates WHERE template_id='".$this->id."';";
		$list_templates = $this->dobj->db_fetch_all($this->dobj->db_query($list_templates));
		foreach ($list_templates as $i => $temp) {
			unset($temp['tabular_template_id']);
			$temp['template_id'] = $template_id;
			$this->dobj->db_query($this->dobj->insert($temp, "tabular_templates"));
		}
		$list_constraints = "SELECT * FROM tabular_constraints WHERE template_id='".$this->id."';";
		$list_constraints = $this->dobj->db_fetch_all($this->dobj->db_query($list_constraints));
		foreach ($list_constraints as $i => $temp) {
			unset($temp['tabular_constraints_id']);
			$temp['template_id'] = $template_id;
			$this->dobj->db_query($this->dobj->insert($temp, "tabular_constraints"));
		}
		$this->redirect('tabular/add/'.$template_id);
	}

// 	function view_graph() {
// 		$output = Tabular_View::view_graph();
// 		return $output;
// 	}

	function view_execute_manually($data=array()) {
		$template = (array)$data[0];
		$template_id = $template['template_id'];

		if (empty($template_id)) return;

		$saved_report_id = $this->execute_scheduled($template_id);

		if ($template['publish_table'] == "t") {
			$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, false, false));
			$table = $table['pdf'];
		}

		if ($template['publish_graph'] == "t") {
			$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, $template['graph_type'], false, true));
			$graph = $graph['graphing'];
		}

		if (true) {
			$csv = $this->call_function("csv", "get_or_generate", array($saved_report_id));
			$csv = $csv['csv'];
		}

		if ($template['email_dissemination'] == "t") {
			$recipients_query = $this->call_function("ALL", "hook_recipients", array($template_id, $template['email_recipients']));

			foreach ($recipients_query as $recipients_tmp) {
				$recipients = array_merge((array)$recipients_tmp, (array)$recipients);
			}
		}

		if ($template['email_dissemination'] == "t" && !empty($recipients)) {

			require_once($this->conf['paths']['phpmailer_path']."class.phpmailer.php");
			
			if (!isset($mail)) {
				$mail = new PHPMailer();
			}
	
			$mail->IsSMTP();
			$mail->Host = $this->conf['email']['host'];
			$mail->SMTPAuth = true;
			$mail->Username = $this->conf['email']['username'];
			$mail->Password = $this->conf['email']['password'];

			$mail->From = $this->conf['email']['from_address'];
			$mail->FromName = $this->conf['email']['from_name'];
			$mail->AddReplyTo($this->conf['email']['from_address'], $this->conf['email']['from_name']);

			foreach ($recipients as $recipient) {
				$mail->AddAddress($recipient[1], $recipient[0]);
			}

			if (!empty($table)) {
				$mail->AddAttachment($table['pdf_path'], "Table.pdf");
			}

			if (!empty($graph)) {
				$mail->AddAttachment($graph['pdf_path'], "Graph.pdf");
			}

			if (!empty($csv)) {
				$mail->AddAttachment($csv['txt_path'], "CSV.txt");
			}

			$mail->IsHTML(true);
			$mail->Subject = $template['email_subject'];
			$mail->Body = stripslashes($template['email_body']);
			
			if(!$mail->Send()) {
				echo "Message could not be sent.\n";
				echo "Mailer Error: ".$mail->ErrorInfo."\n";
				exit;
			}
			
			echo "Message has been sent\n";
		}

		$output = Tabular_View::view_execute_manually();
		return $output;
	}

	function view_histories() {
		$template_id = $this->id;

		if (empty($template_id)) die;

		$saved_reports = $this->dobj->db_fetch_all($this->dobj->db_query("SELECT * FROM saved_reports WHERE template_id='$template_id' AND demo=false AND draft=false ORDER BY created DESC;"));

		return Tabular_View::view_histories($saved_reports);
	}

	function view_history() {
		$saved_report_id = $this->id;

		if (empty($saved_report_id)) die;

		if (true) {
			$table = $this->call_function("pdf", "get_or_generate", array($saved_report_id, false, true));
			$tmp_table .= $table['pdf']['object'];
		}

		if (true) {
			$graph = $this->call_function("graphing", "get_or_generate", array($saved_report_id, null, true, false));
			$tmp_graph .= $graph['graphing']['object'];
		}

		if (true) {
			$csv = $this->call_function("csv", "get_or_generate", array($saved_report_id));
		}

		if (!empty($table['pdf']['pdf_url'])) {
			$downloads['Download Table'] = $table['pdf']['pdf_url'];
		}

		if (!empty($graph['graphing']['pdf_url'])) {
			$downloads['Download Graph'] = $graph['graphing']['pdf_url'];
		}

		if (!empty($csv['csv']['txt_url'])) {
			$downloads['Download CSV'] = $csv['csv']['txt_url'];
		}
	
		return Tabular_View::view_history($tmp_table, $tmp_graph, $downloads);
	}

	function execute_manually($template_id) {
		return $this->execute($template_id, false);
	}

	function execute_scheduled($template_id) {
		return $this->execute($template_id, false);
	}

	function execute_demo($template_id) {
		return $this->execute($template_id, true);
	}

	function execute($template_id, $demo) {
		$template = $this->get_columns($template_id);
		$constraints = $this->get_constraints($template_id);
		$constraint_logic = $this->get_constraint_logic($template_id);

		/* Generate the query to run */
		$query = $this->hook_query($template, $constraints, $constraint_logic, $demo);

		if ($demo) {
			unset($query['c']);
		}

		/* Run the query and get the results */
		$start = time();
		$data = parent::hook_run_query($template[0]['object_id'], $query);
		$end = time();

		$saved_report_id = $this->save_results($template_id, $data, "f", ($demo ? "t" : "f"), ($end-$start), 1);

		return $saved_report_id;
	}

	function hook_recipients($template_id, $template_recipients=null) {
		if (empty($template_recipients)) return null;

		foreach (explode(",", $template_recipients) as $recipient_tmp) {
			$recipients[] = array("", trim($recipient_tmp));
		}

		return $recipients;
	}

	function hook_recipient_selector($recipients) {
		return "
			<div class='input text' style='margin-left: 30px;'><input type='text' id='tabular_recipients' name='data[email_recipients]' value='".$recipients."' dojoType='dijit.form.TextBox' /><span id='tabular_recipients_count' style='padding-left: 20px; vertical-align: middle; color: #555753; font-size: 10pt; font-style: italic;'></span></div>
			";
	}
}

class Tabular_View extends Template_View {
	function view_add($template, $blah=null, $steps=null, $preview_table=null, $tabular_template_auto=null, $table_join_ajax=null, $tabular_template=null) {
		if (!empty($steps)) {
			$output->submenu .= "<ol>";
			foreach ($steps as $i => $step) {
				$output->submenu .= "<li>";
				$output->submenu .= ($i + 1 === 1 ? "Step " : "").($i + 1).". ";
				$output->submenu .= "<a href=\"".$step[1]."\" class=\"".$step[3]."\" ".($step[2] ? "onClick=\"void(0); return false;\"" : "").">";
				$output->submenu .= ucwords($step[0]);
				$output->submenu .= "</a>";
				$output->submenu .= "</li>";
			}
			$output->submenu .= "</ol>";
		}

		switch ($this->subvar) {
			default:
			case "x":
			case "y":
				$output->title = strtoupper($this->subvar)." Axis";
				$output->title_desc = "The ".strtoupper($this->subvar)." axis is a column selected from the database. Unique values from this column will be used to index the intersection data.";

				switch ($this->subid) {
					default:
					case ("type"):
						$output->data .= "<h3>Axis Type</h3>";
						$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/typesubmit", "dojoType='dijit.form.Form'");
						$output->data .= "<div class='input'><input type='radio' name='data[axis_type]' value='auto' ".($tabular_template['axis_type'] == "auto" ? "checked='checked'" : "")." /><label>Database</label></div>";
						$output->data .= "<p>Data will be indexed, along the ".strtoupper($this->subvar)." axis, by unique values of a selected database column.</p>";

						$output->data .= "<div class='input'><input type='radio' name='data[axis_type]' value='trend' ".($tabular_template['axis_type'] == "trend" ? "checked='checked'" : "")." /><label>Date Trend</label></div>";
						$output->data .= "<p>Data will be indexed, daily, weekly, monthly, etc., along the ".strtoupper($this->subvar)." axis, by a selected database date column.</p>";

						$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
						$output->data .= $this->f_close();
						break;
					case ("autosource"):
						$output->data .= "<h3>Axis Source</h3>";
						$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/autosourcesubmit", "dojoType='dijit.form.Form'");
						$output->data .= $this->i("data[column_id]", array("label"=>"Source Column", "type"=>"select", "default"=>$tabular_template_auto['column_id'], "options"=>$blah, "onchange"=>"squidfoo(this);", "dojoType"=>"dijit.form.FilteringSelect"));
						$output->data .= $this->i("data[sort]", array("label"=>"Order", "type"=>"select", "default"=>$tabular_template_auto['sort'], "options"=>array("ASC"=>"Ascending", "DESC"=>"Descending"), "dojoType"=>"dijit.form.FilteringSelect"));
						$output->data .= $this->i("data[human_name]", array("label"=>"Axis Name", "type"=>"text", "default"=>$tabular_template_auto['human_name'], "dojoType"=>"dijit.form.ValidationTextBox"));

						$output->data .= "<hr />";
						$output->data .= "<div id='squeebop'>
							".$table_join_ajax."
							</div>
							";
						$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
						$output->data .= $this->f_close();
						break;
					case ("trendsource"):
						$output->data .= "<h3>Axis Source</h3>";
						$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/trendsourcesubmit", "dojoType='dijit.form.Form'");

// 						$output->data .= "<div class='input'>Source Column: </div>";
// 						$output->data .= "<p>Skoo</p>";

// 						foreach ($blah as $table_name_tmp => $columns_tmp) {
// 							$output->data .= "<h4 style='margin: 10px 0px 10px 30px; font-weight: normal; font-style: italic;'>".ucwords($table_name_tmp)."</h4>";
// 							$output->data .= "<div style='margin-left: 30px'>";
// 							foreach ($columns_tmp as $column_tmp) {
// 								$output->data .= $this->i("data[column_id]", array("label"=>ucwords($column_tmp['column_name']), "type"=>"radio", "value"=>$column_tmp['column_id'], "default"=>false, "disabled"=>false, "onchange"=>"squidfoo(this);"));
// 								$output->data .= "<p>Skoo</p>";
// 							}
// 							$output->data .= "</div>";
// 
// 							if ($columns_tmp != end($blah)) {
// 								$output->data .= "<hr style='margin: 10px 0px 10px 30px; border-color: #eeeeec;' />";
// 							}
// 						}
// 						$output->data .= "<hr />";

						$output->data .= $this->i("data[column_id]", array("label"=>"Source Column", "type"=>"select", "default"=>$tabular_template_auto['column_id'], "options"=>$blah, "onchange"=>"squidfoo(this);", "dojoType"=>"dijit.form.FilteringSelect"));
						$output->data .= $this->i("data[sort]", array("label"=>"Order", "type"=>"select", "default"=>$tabular_template_auto['sort'], "options"=>array("ASC"=>"Ascending", "DESC"=>"Descending"), "dojoType"=>"dijit.form.FilteringSelect"));
						$output->data .= $this->i("data[start_date]", array("id"=>"data[start_date]", "type"=>"text", "dojoType"=>"dijit.form.DateTextBox", "label"=>"Start Date", "value"=>$tabular_template_auto['start_date']));
						$output->data .= $this->i("data[end_date]", array("id"=>"data[end_date]", "type"=>"text", "dojoType"=>"dijit.form.DateTextBox", "label"=>"End Date", "value"=>$tabular_template_auto['end_date']));
						$output->data .= $this->i("data[interval]", array("label"=>"Interval", "type"=>"select", "default"=>$tabular_template_auto['interval'], "options"=>array("daily"=>"Daily", "weekly"=>"Weekly", "monthly"=>"Monthly", "quarterly"=>"Quarterly", "yearly"=>"Yearly"), "dojoType"=>"dijit.form.FilteringSelect"));
						$output->data .= $this->i("data[human_name]", array("label"=>"Axis Name", "type"=>"text", "default"=>$tabular_template_auto['human_name'], "dojoType"=>"dijit.form.ValidationTextBox"));
						$output->data .= "<hr />";
						$output->data .= "<div id='squeebop'>
							".$table_join_ajax."
							</div>
							";
						$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
						$output->data .= $this->f_close();
						break;
				}
				break;
			case "c":
				switch ($this->subid) {
					default:
					case ("source"):
						$output->title = "Intersection Data Source";
						$output->title_desc = "The intersection is a numerical column selected from the database. Values from this column will be indexed by unique values in two related colums (the X and Y axies), and will fill the area of the table.";

						$output->data .= $this->f("tabular/save/".$this->id."/".$this->subvar."/sourcesubmit", "dojoType='dijit.form.Form'");
						$output->data .= $this->i("data[column_id]", array("label"=>"Source Column", "type"=>"select", "default"=>$tabular_template_auto['column_id'], "options"=>$blah['options'], "dojoType"=>"dijit.form.FilteringSelect", "onchange"=>"intersection_source_type_warning(this);"));

						if (!empty($blah['option_warnings'][$tabular_template_auto['column_id']])) {
							$output->data .= "<p class='warning' id='intersection_source_type_warning' style='display: block;'>".$blah['option_warnings'][$tabular_template_auto['column_id']]."</p>";
						} else {
							$output->data .= "<p class='warning' id='intersection_source_type_warning' style='display: none;'></p>";
						}

						$output->data .= "
							<script>
								function intersection_source_type_warning(o) {
									var warnings = ".json_encode($blah['option_warnings']).";

									var warning = warnings[o.value];

									var warining_div = dojo.byId('intersection_source_type_warning');

									if (warning) {
										warining_div.innerHTML = warning;
										warining_div.style.display = 'block';
									} else {
										warining_div.style.display = 'none';
										warining_div.innerHTML = '';
									}
								}
							</script>
							";

						$output->data .= "<hr />";

						$output->data .= $this->i("data[aggregate]", array("label"=>"Count", "type"=>"radio", "value"=>"count", "default"=>($tabular_template_auto['aggregate'] == "count")));
						$output->data .= "<p>The number of records that match the given X axis and Y Axis.</p>";

						$output->data .= $this->i("data[aggregate]", array("label"=>"Count Distinct Values", "type"=>"radio", "value"=>"count distinct", "default"=>($tabular_template_auto['aggregate'] == "count distinct")));
						$output->data .= "<p>The number of records, with a distinct value in the selected column, that match the given X axis and Y Axis.</p>";

						$output->data .= $this->i("data[aggregate]", array("label"=>"Sum", "type"=>"radio", "value"=>"sum", "default"=>($tabular_template_auto['aggregate'] == "sum")));
						$output->data .= "<p>The total sum of all values that match the given X axis and Y Axis.</p>";

						$output->data .= $this->i("data[aggregate]", array("label"=>"Minimum", "type"=>"radio", "value"=>"min", "default"=>($tabular_template_auto['aggregate'] == "min")));
						$output->data .= "<p>The smallest value of all values that match the given X axis and Y Axis.</p>";

						$output->data .= $this->i("data[aggregate]", array("label"=>"Maximum", "type"=>"radio", "value"=>"max", "default"=>($tabular_template_auto['aggregate'] == "max")));
						$output->data .= "<p>The largest value of all values that match the given X axis and Y Axis.</p>";

						$output->data .= $this->i("data[aggregate]", array("label"=>"Average", "type"=>"radio", "value"=>"average", "default"=>($tabular_template_auto['aggregate'] == "average")));
						$output->data .= "<p>The average of all values that match the given X axis and Y Axis.</p>";
						$output->data .= "<hr />";

						$output->data .= $this->i("data[human_name]", array("label"=>"Intersection Name", "type"=>"text", "default"=>$tabular_template_auto['human_name'], "dojoType"=>"dijit.form.ValidationTextBox"));
						$output->data .= "<hr />";

						$output->data .= $this->i("submit", array("label"=>"Next", "type"=>"submit", "value"=>"Next", "dojoType"=>"dijit.form.Button"));
						$output->data .= $this->f_close();
						break;
				}
				break;
			case "preview":
				$output->title = "Preview";
				$output->title_desc = "";

				$output->data .= $preview_table;

				break;
			case "constraints":
				$output->title = "Constraints";
				$output->title_desc = "";

if (!empty($blah['constraints']) && count($blah['constraints']) > 0) {
	$output->data .= "<h3>Constraint Logic</h3>";

	$constraint_index = 1;

	foreach ($blah['constraints'] as $constraint_tmp) {
		$constraints_ascii[$constraint_index] = chr($constraint_index);
		$constraints_id[$constraint_index] = $constraint_tmp['constraint_id'];
		$constraints_text[$constraint_index] = $constraint_tmp['constraint'];

		$constraint_index ++;
	}

	$logic_ascii = $blah['logic'];

	foreach ($constraints_id as $constraint_index_tmp => $constraint_id_tmp) {
		$logic_ascii = str_replace($constraint_id_tmp, $constraints_ascii[$constraint_index_tmp], $logic_ascii);
	}

	$output->data .= "
		<script>
			";

	$output->data .= "
			var constraints_ascii = new Array;
			";
	foreach ($constraints_ascii as $i => $tmp) {
		$output->data .= "
			constraints_ascii[$i] = '$tmp';
			";
	}

	$output->data .= "
			var constraints_id = new Array;
			";
	foreach ($constraints_id as $i => $tmp) {
		$output->data .= "
			constraints_id[$i] = '$tmp';
			";
	}

	$output->data .= "
			var constraints_text = new Array;
			";
	foreach ($constraints_text as $i => $tmp) {
		$output->data .= "
			constraints_text[$i] = '".str_replace("'", "\'", $tmp)."';
			";
	}

	$output->data .= file_get_contents("/var/www/".$this->sw_path."php_web/modules/tabular/constraints_ui.js");

	$output->data .= "
		</script>
		<style>
			#confoo_div {
				margin: 20px 0px;
				padding: 10px;
				border: 1px solid #d3d7cf;
			}
				#confoo_div span {
					position: relative;
					border: 1px solid white;
					vertical-align: middle;
				}
					#confoo_div span.constraint {
						color: #888a85;
					}
					#confoo_div span.bracket {
						border: 1px solid #fce94f;
						background-color: #fce94f;
					}
					#confoo_div span.cursor_before {
						border-left: 1px solid black;
					}
					#confoo_div span.selected {
						border: 1px solid #204a87;
						background-color: #204a87;
						color: #eeeeec;
					}
					#confoo_div span.constraint.selected {
						color: #babdb6;
					}
			#confoo_in {
				width: 0px;
				height: 0px;
				position: absolute;
				margin: 0px;
				padding: 0px;
				border: 0px;
			}
		</style>
		";
	$output->data .= $this->f("tabular/save/".$this->id."/constraintlogicsubmit/".$this->subid, "dojoType='dijit.form.Form'");
	$output->data .= "
		<div id='confoo_div'></div>
		<input type='text' id='confoo_in' name='data[constraint_logic]' value='$logic_ascii' autocomplete='off' />
		<input type='hidden' id='confoo_old' value='$logic_ascii' />
		<input type='hidden' name='data[constraints_id]' value='".json_encode($constraints_id)."' />
		<input type='hidden' name='data[constraints_ascii]' value='".json_encode($constraints_ascii)."' />
		";
	$output->data .= $this->i("submit", array("div_id"=>"confoo_save", "label"=>"Save", "type"=>"submit", "value"=>"Edit", "dojoType"=>"dijit.form.Button"));
	$output->data .= $this->f_close();
}

				$output->data .= "<h3>Constraints</h3>";

				if (!empty($blah['constraints'])) {
					$output->data .= "<a href='".$this->webroot()."tabular/add/".$this->id."/editconstraint/new'>Create Constraint</a>";

					$output->data .= "
						<div class='reports'>
							<table cellpadding='0' cellspacing='0'>
								<tr>
									<th>Constraint</th>
									<th>&nbsp;</th>
								</tr>
								";

					foreach ($blah['constraints'] as $constraint_tmp) {
						$constraint_id = $constraint_tmp['constraint_id'];

						$output->data .= "<tr>";
						$output->data .= "<td>";

						switch ($constraint_tmp['foobar']) {
							case "constraint":
								$output->data .= "<span class='".$constraint_tmp['foobar']."'>";
								$output->data .= $constraint_tmp['constraint'];
								$output->data .= "</span>";
								break;
						}

						$output->data .= "</td>";
						$output->data .= "<td>";
						$output->data .= "<ul>";

						switch ($constraint_tmp['foobar']) {
							case "constraint":
								if ($blah['default']) {
									$output->data .= "<li><a href='".$this->webroot()."tabular/add/".$this->id."/editconstraint/".$constraint_id."'>Edit</a></li>";
									$output->data .= "<li><a href='".$this->webroot()."tabular/save/".$this->id."/removeconstraintsubmit/".$constraint_id."' onclick='if (confirm(\"Remove constraint?\")) {return true;} else {return false;}'>Remove</a></li>";
								} else {
									$output->data .= "<li>&nbsp;</li>";
								}
								break;
						}

						$output->data .= "</ul>";
						$output->data .= "</td>";
						$output->data .= "</tr>";
					}
					$output->data .= "
							</table>
						</div>
						";
				} else {
					$output->data .= "<a href='".$this->webroot()."tabular/add/".$this->id."/editconstraint/new'>Create Constraint</a>";
					$output->data .= "<p>No constraints can be found.</p>";
				}

				break;
			case "editconstraint":
				$output->title = "Edit Constraint";
				$output->title_desc = "";

				if ($blah['error']) {
					$output->data .= "<p style='color: #a40000; font-family: Arial; font-size: 10pt; font-weight: bold;'>".$blah['error']."</p>";
				}

				$output->data .= $this->f("tabular/save/".$this->id."/editconstraintsubmit/".$this->subid, "dojoType='dijit.form.Form'");
				$output->data .= $this->i("data[column_id]", array("id"=>"data[column_id]", "label"=>"Column", "type"=>"select", "default"=>$blah['data']['column_id'], "options"=>$blah['options']['column_id'], "onchange"=>"squidfoo(this);", "dojoType"=>"dijit.form.FilteringSelect"));
				$output->data .= $this->i("data[type]", array("id"=>"data[type]", "label"=>"&nbsp;", "type"=>"select", "default"=>$blah['data']['type'], "options"=>$blah['options']['type'], "dojoType"=>"dijit.form.FilteringSelect"));
				$output->data .= $this->i("data[value_text]", array("div_id"=>"value_text_div", "label"=>"&nbsp;", "type"=>"text", "value"=>$blah['data']['value'], "dojoType"=>"dijit.form.ValidationTextBox"));
				$output->data .= $this->i("data[value_date]", array("div_id"=>"value_date_div", "label"=>"&nbsp;", "type"=>"text", "value"=>$blah['data']['value'], "dojoType"=>"dijit.form.DateTextBox"));

				if (!empty($blah['column_options'])) {
					foreach ($blah['column_options'] as $column_id => $column_options) {
						$output->data .= $this->i("data[value_select_$column_id]", array("id"=>"data[value_select_$column_id]", "div_id"=>"value_select_div_$column_id", "label"=>"&nbsp;", "type"=>"select", "default"=>$blah['data']['value'], "options"=>$column_options, "dojoType"=>"dijit.form.FilteringSelect"));
					}
				}

				$output->data .= $this->i("data[value_inputs]", array("id"=>"data[value_inputs]", "type"=>"hidden", "default"=>json_encode(array())));
				$output->data .= $this->i("data[value_input_selected]", array("id"=>"data[value_input_selected]", "type"=>"hidden", "default"=>""));

				$output->data .= "
					<script>
						dojo.addOnLoad(constraint_input_toggle_init);

						var column_options = ".json_encode((array)$blah['column_options']).";

						function constraint_input_toggle_init() {
							dojo.connect(dijit.byId('data[column_id]'), 'onChange', 'constraint_input_toggle');
							dojo.connect(dijit.byId('data[type]'), 'onChange', 'constraint_input_toggle');

							constraint_input_toggle();
						}

						function constraint_input_toggle() {
							var types = ".json_encode((array)$blah['column_types']).";

							var value_text_div = dojo.byId('value_text_div');
							var value_date_div = dojo.byId('value_date_div');

							value_date_div.style.display = 'none';
							value_text_div.style.display = 'none';

							var skoo = [];

							skoo[skoo.length] = 'value_text';
							skoo[skoo.length] = 'value_date';

							for (var i in column_options) {
								dojo.byId('value_select_div_'+i).style.display = 'none';

								skoo[skoo.length] = 'value_select_'+i;
							}

							dojo.byId('data[value_inputs]').value = skoo;

							if (dijit.byId('data[type]').value != 'like') {
								if (column_options[dijit.byId('data[column_id]').value]) {
									dojo.byId('value_select_div_'+dijit.byId('data[column_id]').value).style.display = 'block';

									dojo.byId('data[value_input_selected]').value = 'value_select_'+dijit.byId('data[column_id]').value;

									return;
								}

								if (types[dijit.byId('data[column_id]').value] == 'date') {
									value_date_div.style.display = 'block';

									dojo.byId('data[value_input_selected]').value = 'value_date';

									return;
								}
							}

							value_text_div.style.display = 'block';

							dojo.byId('data[value_input_selected]').value = 'value_text';

							return;
						}
					</script>
					";

				$output->data .= "<hr />";
				$output->data .= "<div id='squeebop'>";
				$output->data .= $table_join_ajax;
				$output->data .= "</div>";

				$output->data .= "
					<div class='input'>
						<button value='Cancel' dojoType='dijit.form.Button' onclick='window.location=\"".$this->webroot()."tabular/add/".$this->id."/constraints\"; return false;' name='cancel' />Cancel</button><button type='submit' value='Next' dojoType='dijit.form.Button' name='submit' />Save</button>
					</div>
					";

				$output->data .= $this->f_close();
				break;
			case "publish":
				//prevent the editor from adding more escapes than neccessary
				$template['header'] = stripslashes($template['header']);
				$template['footer'] = stripslashes($template['footer']);

				$output->title = "Publishing";
				$output->title_desc = "";

				$output->data .= $this->f("tabular/save/".$this->id."/publishsubmit", "id='publishing_form' dojoType='dijit.form.Form'");
				$output->data .= $this->i("data[name]", array("label"=>"Report Name", "default"=>$template['name'], "dojo"=>"dijit.form.TextBox"));
				$output->data .= $this->i("data[description]", array("label"=>"Description", "default"=>$template['description'], "dojo"=>"dijit.form.Textarea"));
				$output->data .= "<hr />";

				$output->data .= "<h3>Publishing</h3>";

				$output->data .= $this->i("data[publish_table]", array("label"=>"Publish Tabular Data", "type"=>"checkbox", "default"=>$template['publish_table']));
				$output->data .= $this->i("data[publish_graph]", array("label"=>"Publish Graphic Data", "type"=>"checkbox", "default"=>$template['publish_graph']));
				$output->data .= $this->i("data[publish_csv]", array("label"=>"Publish CSV Data", "type"=>"checkbox", "default"=>true, "disabled"=>true));
				$output->data .= "<hr />";

				$output->data .= "<h3>Graph</h3>";
				$output->data .= $this->i("data[graph_type]", array("label"=>"Scatter Graph", "type"=>"radio", "value"=>"Scatter", "default"=>($template['graph_type'] == "Scatter"), "disabled"=>true));
				$output->data .= $this->i("data[graph_type]", array("label"=>"Line Graph", "type"=>"radio", "value"=>"Lines", "default"=>($template['graph_type'] == "Lines")));
				$output->data .= $this->i("data[graph_type]", array("label"=>"Line Graph - Stacked", "type"=>"radio", "value"=>"StackedLines", "default"=>($template['graph_type'] == "StackedLines"), "disabled"=>true));
				$output->data .= $this->i("data[graph_type]", array("label"=>"Area Graph", "type"=>"radio", "value"=>"Areas", "default"=>($template['graph_type'] == "Areas"), "disabled"=>true));
				$output->data .= $this->i("data[graph_type]", array("label"=>"Area Graph - Stacked", "type"=>"radio", "value"=>"StackedAreas", "default"=>($template['graph_type'] == "StackedAreas")));
				$output->data .= $this->i("data[graph_type]", array("label"=>"Bar Graph - Vertical", "type"=>"radio", "value"=>"Columns", "default"=>($template['graph_type'] == "Columns"), "disabled"=>true));
				$output->data .= $this->i("data[graph_type]", array("label"=>"Bar Graph - Vertical, Stacked", "type"=>"radio", "value"=>"StackedColumns", "default"=>($template['graph_type'] == "StackedColumns")));
				$output->data .= $this->i("data[graph_type]", array("label"=>"Bar Graph - Vertical, Clustered", "type"=>"radio", "value"=>"ClusteredColumns", "default"=>($template['graph_type'] == "ClusteredColumns"), "disabled"=>true));
				$output->data .= "<hr />";

				$output->data .= "<h3>Page Addenda</h3>";
				$output->data .= $this->i("data[header]", array("type"=>"wysiwyg", "label"=>"Report Header", "default"=>$template['header'], "parent_form"=>"publishing_form"));
				$output->data .= "<p>The following placeholders can be used to dynamically update the header and footer at runtime. %logo, %name, %desc, %run, %by, %size</p>";
				$output->data .= $this->i("data[footer]", array("type"=>"wysiwyg", "label"=>"Report Footer", "default"=>$template['footer'], "parent_form"=>"publishing_form"));
				$output->data .= "<p>The following placeholders can be used to dynamically update the header and footer at runtime. %logo, %name, %desc, %run, %by, %size</p>";
				$output->data .= "<hr />";

				$output->data .= $this->i("submit", array("label"=>"Edit", "type"=>"submit", "value"=>"Edit", "dojoType"=>"dijit.form.Button"));
				$output->data .= $this->f_close();
				break;
			case "execution":
				//prevent the editor from adding more escapes than neccessary
				$template['email_body'] = stripslashes($template['email_body']);

				$output->title = "Execution";
				$output->title_desc = "";

				$output->data .= $this->f("tabular/save/".$this->id."/executionsubmit", "id='execution_form'", "dojoType='dijit.form.Form'");
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_manually]", "label"=>"Execute Manually", "type"=>"radio", "value"=>"manually"/*, "onchange"=>'console.log("skoo");'*/));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_hourly]", "label"=>"Execute Hourly", "type"=>"radio", "value"=>"hourly", "default"=>($template['execute_hourly'] == "t")));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_daily]", "label"=>"Execute Daily", "type"=>"radio", "value"=>"daily", "default"=>($template['execute_daily'] == "t")));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_weekly]", "label"=>"Execute Weekly", "type"=>"radio", "value"=>"weekly", "default"=>($template['execute_weekly'] == "t")));
				$output->data .= $this->i("data[execution_interval]", array("id"=>"data[execution_interval_monthly]", "label"=>"Execute Monthly", "type"=>"radio", "value"=>"monthly", "default"=>($template['execute_monthly'] == "t")));
				$output->data .= "<hr />";

				$output->data .= $this->i("data[execute_hour]", array("id"=>"data[execute_hour]", "div_id"=>"execute_hour_div", "label"=>"Hour of Execution", "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "default"=>$template['execute_hour'], "options"=>array(
					"0"=>"0 AM",
					"1"=>"1 AM",
					"2"=>"2 AM",
					"3"=>"3 AM",
					"4"=>"4 AM",
					"5"=>"5 AM",
					"6"=>"6 AM",
					"7"=>"7 AM",
					"8"=>"8 AM",
					"9"=>"9 AM",
					"10"=>"10 AM",
					"11"=>"11 AM",
					"12"=>"12 PM",
					"13"=>"1 PM",
					"14"=>"2 PM",
					"15"=>"3 PM",
					"16"=>"4 PM",
					"17"=>"5 PM",
					"18"=>"6 PM",
					"19"=>"7 PM",
					"20"=>"8 PM",
					"21"=>"9 PM",
					"22"=>"10 PM",
					"23"=>"11 PM"
					)));
				$output->data .= "<p>Hour of the day to execute the report.</p>";

				$output->data .= $this->i("data[execute_dayofweek]", array("id"=>"data[execute_dayofweek]", "div_id"=>"execute_dayofweek_div", "label"=>"Day of Execution", "type"=>"select", "dojoType"=>"dijit.form.FilteringSelect", "default"=>$template['execute_dayofweek'], "options"=>array(
					"1"=>"Monday",
					"2"=>"Tuesday",
					"3"=>"Wednesday",
					"4"=>"Thursday",
					"5"=>"Friday",
					"6"=>"Saturday",
					"7"=>"Sunday"
					)));
				$output->data .= "<p>Day of the week to execute the report.</p>";

				$output->data .= $this->i("data[execute_day]", array("id"=>"data[execute_day]", "div_id"=>"execute_day_div", "label"=>"Date of Execution", "type"=>"select", "dojoType" =>"dijit.form.FilteringSelect", "default"=>$template['execute_day'], "options"=>array(
					"1"=>"1st",
					"2"=>"2nd",
					"3"=>"3rd",
					"4"=>"4th",
					"5"=>"5th",
					"6"=>"6th",
					"7"=>"7th",
					"8"=>"8th",
					"9"=>"9th",
					"10"=>"10th",
					"11"=>"11th",
					"12"=>"12th",
					"13"=>"13th",
					"14"=>"14th",
					"15"=>"15th",
					"16"=>"16th",
					"17"=>"17th",
					"18"=>"18th",
					"19"=>"19th",
					"20"=>"20th",
					"21"=>"21st",
					"22"=>"22nd",
					"23"=>"23rd",
					"24"=>"24th",
					"25"=>"25th",
					"26"=>"26th",
					"27"=>"27th",
					"28"=>"28th",
					"29"=>"29th",
					"30"=>"30th",
					"31"=>"31st (or last day of month)"
					)));
				$output->data .= "<p>Day of the month to execute the report.</p>";

				$output->data .= "
					<script>
						dojo.addOnLoad(execution_interval_input_toggle_init);

						function execution_interval_input_toggle_init() {
							dojo.connect(dojo.byId('data[execution_interval_manually]'), 'onclick', 'execution_interval_input_toggle');
							dojo.connect(dojo.byId('data[execution_interval_hourly]'), 'onclick', 'execution_interval_input_toggle');
							dojo.connect(dojo.byId('data[execution_interval_daily]'), 'onclick', 'execution_interval_input_toggle');
							dojo.connect(dojo.byId('data[execution_interval_weekly]'), 'onclick', 'execution_interval_input_toggle');
							dojo.connect(dojo.byId('data[execution_interval_monthly]'), 'onclick', 'execution_interval_input_toggle');

							execution_interval_input_toggle();
						}

						function execution_interval_input_toggle() {
							var execution_interval_daily = dojo.byId('data[execution_interval_daily]').checked;
							var execution_interval_weekly = dojo.byId('data[execution_interval_weekly]').checked;
							var execution_interval_monthly = dojo.byId('data[execution_interval_monthly]').checked;

							var execute_hour_div = dojo.byId('execute_hour_div');
							var execute_dayofweek_div = dojo.byId('execute_dayofweek_div');
							var execute_day_div = dojo.byId('execute_day_div');

							//enable all the execution inputs
							dijit.byId('data[execute_hour]').setDisabled(false);
							dijit.byId('data[execute_dayofweek]').setDisabled(false);
							dijit.byId('data[execute_day]').setDisabled(false);

							//make all labels look enabled
							execute_hour_div.className = execute_hour_div.className.replace('disabled', '');
							execute_dayofweek_div.className = execute_dayofweek_div.className.replace('disabled', '');
							execute_day_div.className = execute_day_div.className.replace('disabled', '');

							//if no appropriate interval is selected, disable the hour input
							if (!execution_interval_daily && !execution_interval_weekly && !execution_interval_monthly) {
								dijit.byId('data[execute_hour]').setDisabled(true);
								execute_hour_div.className = execute_hour_div.className+' disabled';
							}

							//as above, but for the day of week input
							if (!execution_interval_weekly) {
								dijit.byId('data[execute_dayofweek]').setDisabled(true);
								execute_dayofweek_div.className = execute_dayofweek_div.className+' disabled';
							}

							//as above, but for the day of month input
							if (!execution_interval_monthly) {
								dijit.byId('data[execute_day]').setDisabled(true);
								execute_day_div.className = execute_day_div.className+' disabled';
							}
						}
					</script>
					";

				$output->data .= "<hr />";

				$output->data .= "<h3>Email Dissemination</h3>";
				$output->data .= $this->i("data[email_dissemination]", array("label"=>"Disseminate Via Email", "type"=>"checkbox", "default"=>($template['email_dissemination'] == "t")));
				$output->data .= "<hr />";

				$recipient_selectors = $this->call_function("ALL", "hook_recipient_selector", array($template['email_recipients']));

				$output->data .= "
					<div style=''>Recipients:</div>
					<script>
						dojo.addOnLoad(recipients_count_init);

						var recipient_selectors = ".json_encode(array_keys($recipient_selectors)).";

						function recipients_count_init() {
							for (var i in recipient_selectors) {
								recipients_count(null, dojo.byId(recipient_selectors[i]+'_recipients'));
								dojo.byId(recipient_selectors[i]+'_recipients').onchange = recipients_count;
							}
						}

						function recipients_count(e, o) {
							if (e) {
								var object = e.currentTarget;
							} else if (o) {
								var object = o;
							}

							if (object.id == 'tabular_recipients') {
								var emails = object.value;
								emails = emails.replace(' ', '');
								emails = emails.split(',');

								if (emails.length === 1) {
									var count_text = '1 recipient';
								} else {
									var count_text = (emails.length)+' recipients';
								}

								dojo.byId(object.id+'_count').innerHTML = count_text;
							} else {
							}
						}
					</script>
					";

				$output->data .= implode("\n", $recipient_selectors);

				$output->data .= "<hr />";

				$output->data .= $this->i("data[email_subject]", array("label"=>"Message Subject", "type"=>"text", "default"=>$template['email_subject'], "dojo"=>"dijit.form.TextBox"));

				$output->data .= $this->i("data[email_body]", array("label"=>"Message Body", "type"=>"wysiwyg", "default"=>$template['email_body'], "parent_form"=>"execution_form"));
				$output->data .= "<p>The following placeholders can be used to dynamically update the header and footer at runtime. %logo, %name, %desc, %run, %by, %size</p>";
				$output->data .= "<hr />";

				$output->data .= $this->i("submit", array("label"=>"Edit", "type"=>"submit", "value"=>"Edit", "dojoType"=>"dijit.form.Button"));

				$output->data .= $this->f_close();
				break;
			case "access":
				$output->title = "Access";
				$output->title_desc = "";

				$things = array(
					"edit",
					"execute",
					"histories"
					);

				$groups = array(
					1 => "Administrators",
					2 => "Information Dept.",
					3 => "Marketing Dept.",
					4 => "All Staff"
					);

				$users = array(
					5 => "Administrator",
					6 => "Alice Anderson",
					7 => "Bob Brown",
					8 => "Claire Collins",
					9 => "Dan Debinson",
					10 => "Erick Earhart"
					);

				$permissions = array(
					1 => array(true, true, true),
					2 => array(true, true, true),
					3 => array(false, true, true),
					4 => array(false, false, true),
					5 => array(true, true, true)
					);

				$disabled = array(
					1 => true,
					5 => true
				);

				$user_groups = array(
					5 => array(1, 4),
					6 => array(1, 2, 4),
					7 => array(2, 4),
					8 => array(3, 4),
					9 => array(3, 4),
					10 => array(4)
					);

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

				break;
		}
		return $output;
	}

	function view_add_axis($type, $columns) {
	}

	function add_axis_manual($type, $columns) {
	}

	function add_axis_automatic($type, $columns) {
	}

	function add_axis_trend($type, $columns) {
	}

	function view_add_intersection($columns) {
	}

	function view_display_table($tables, $template) {
		$output->layout = 'ajax';
		$output->data .= "<div dojoType='dojo.data.ItemFileReadStore' url='".$this->webroot()."tabular/tables_json/".$this->id."' jsId='template'></div>";
		#$output->data = "<div style='display:none;' id='holding_cellA'>";
		#foreach ($tables['catalogue'] as $i => $columns) {
		#	foreach ($columns as $i => $column) {
		#		$output->data .= "".$this->wrap_table_column($column['column_id'], $column['column_name'])."";
		#	}
		#}
		#$output->data .= "</div>";
		$output->data .= "<table id='demo' style='width: 150px;'>
		<thead>
		<tr>
		";
		#if (is_array($template)) {
		#	foreach ($template as $i => $temp) {
		#		$output->data .= "<th class='columns' dojoType='dojo.dnd.Target' id='th_".$i."' ><li class='dojoDndItem'>".$this->wrap_table_column($temp['column_id'], $temp['chuman'], $temp)."</li></th>";
		#	}
		#}
		$output->data .= "<th class='columns' dojoType='dojo.dnd.Source' id='th_".($i+1)."' ></th>
		</tr>
		</thead>
		<tbody id='demo_body' >
		</tbody>
		</table>
		<script>create_cells();save_template();</script>";
		$output->data .= $this->f_close();
		return $output;
	}

	function view_save($data, $template) {
		$output->layout = "ajax";
		$output->data = "";
		return $output;
	}

	function view_table_join_ajax($table_join_markup) {
		$output->layout = "ajax";
		$output->data = $table_join_markup;
		$output->data .= "<hr />";
		return $output;
	}

	function view_run($template, $constraints) {
		$skip = true;
		$output->data .= $this->f('tabular/run/'.$this->id);

		if (is_array($constraints)) {
			/* Iterate through all the constraints in the report */
			foreach ($constraints as $i => $constraint) {
				/* If the constraint can be modifified by the user at run time */
				if ($constraint['choose'] == "t") {
					/* Ignore pre populated constraints - see $_REQUEST variable */
					if ($_REQUEST["data"][$constraint['list_constraints_id']]) {
						$constraints[$i]['value'] = $_REQUEST["data"][$constraint['list_constraints_id']];
					} else {
						$skip = false;
						$output->title = "Report Parameters";
						/* Automatically build the form with all the constraint options */
						$output->data .= $this->i("data[constraint][".$constraint['list_constraints_id']."]", array("dojoType"=>"dijit.form.TextBox", "type"=>"text", "label"=>$constraint['chuman']." ".$constraint['type'], "default"=>$constraint['value']));
					}
				}
			}
		}

		$output->data .= $this->submit("Next");
		$output->data .= $this->f_close();

// 		$output->data = "<div style='overflow:auto;' layoutAlign='client' dojoType='dojox.layout.ContentPane'>".$output->data."</div>";
		/* Only return the HTML if there is a form to fill out, otherwise return false */
		if ($skip == false) {
			return $output;
		} else {
			return $skip;
		}
	}

	function hook_output($results, $template, $demo=false, $now=false, $pdf=false) {
		$odd = "";
		$output->data = "";

		if ($pdf) {
			 $template[0]['header'] = stripslashes($template[0]['header']);
			 $template[0]['footer'] = stripslashes($template[0]['footer']);

			$logo_path = "/var/www/".$this->sw_path."php_web/logos/";
			$logo_tmp_path =  "/var/www/".$this->tmp_path;

			if (is_file($logo_path."logo.png")) {
				$logo_name = "logo.png";
			} else if (is_file($logo_path."logo.jpg")) {
				$logo_name = "logo.jpg";
			} else if (is_file($logo_path."logo.gif")) {
				$logo_name = "logo.gif";
			}

			if (!empty($logo_name)) {
				symlink($logo_path.$logo_name, $logo_tmp_path.$logo_name);

				$logo_url = "http://127.0.0.1/".$this->tmp_path;

				$template[0]['header'] = str_replace("%logo", "<img src='$logo_url$logo_name' />", $template[0]['header']);
				$template[0]['footer'] = str_replace("%logo", "<img src='$logo_url$logo_name' />", $template[0]['footer']);
			}

			$template[0]['header'] = str_replace("%name", $template[0]['name'], $template[0]['header']);
			$template[0]['header'] = str_replace("%desc", $template[0]['description'], $template[0]['header']);
			$template[0]['header'] = str_replace("%run", date("Y-m-d H:i:s", strtotime($template[0]['last_run'])), $template[0]['header']);
			$template[0]['header'] = str_replace("%by", $template[0]['last_by'], $template[0]['header']);
			$template[0]['header'] = str_replace("%size", $template[0]['last_size'], $template[0]['header']);

			$template[0]['footer'] = str_replace("%name", $template[0]['name'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%desc", $template[0]['description'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%run", date("Y-m-d H:i:s", strtotime($template[0]['last_run'])), $template[0]['footer']);
			$template[0]['footer'] = str_replace("%by", $template[0]['last_by'], $template[0]['footer']);
			$template[0]['footer'] = str_replace("%size", $template[0]['last_size'], $template[0]['footer']);
		}

		foreach ($template as $template_tmp) {
			$axis_names[$template_tmp['type']] = ucwords($template_tmp['tabular_template_human_name']);
		}

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

			if ($pdf) {
				$table_chunked = array_chunk($x_index, 8);
				unset($x_index);

				$page_counter = 1;
				$pages_total = count($table_chunked);
			} else {
				$table_chunked = array($x_index);
			}

			foreach ($table_chunked as $x_index) {
				if ($pdf) {
					if ($page_counter == $pages_total) {
						$output->data .= "<div>";
					} else {
						$output->data .= "<div style='page-break-after: always'>";
					}

					$output->data .= $template[0]['header'];
				}

				$output->data .= "
					<div class='tabular_data'>
						<table cellpadding='0' cellspacing='0'>
						";

				$table_title = "Table I. ".$axis_names['c'];

				if ($pdf) {
					$table_title .= " Page $page_counter of $pages_total.";
				}

				$output->data .= "
					<tr class='title'>
						<th colspan='".(count($results['x']) + 1)."'>$table_title</th>
					</tr>
					<tr class='x-title'>
						<th></th>
						<th class='x-title' colspan='".count($results['x'])."'>".$axis_names['x']."</th>
					</tr>
					<tr class='x-index'>
						<th class='y-title'>".$axis_names['y']."</th>
						";

				foreach ($x_index as $x_tmp) {
					$output->data .= "<th>".$x_tmp."</th>";
				}

				$output->data .= "
					</tr>
					";

				foreach ($y_index as $y_tmp) {
					$output->data .= "<tr>";
					$output->data .= "<th class='y-index'>".$y_tmp."</th>";
					foreach ($x_index as $x_tmp) {
						$c_tmp = $results_foo[$y_tmp][$x_tmp];
						$output->data .= "<td>".$c_tmp."</td>";
					}
					$output->data .= "</tr>";
				}

				$output->data .= "
						</table>
					</div>
					";

				if ($pdf) {
					$output->data .= $template[0]['footer'];

					$output->data .= "</div>";

					$page_counter ++;
				}
			}
		}

		return $output;
	}

	function view_tables_json($tables) {
		$output->layout = 'ajax';
		$output->data = "{
	identifier:'tabular_template_id',
	items: [";
	$opt = array();
	foreach ($tables as $i => $option) {
		$opt[] = "{tabular_template_id: '".$option['tabular_template_id']."', column_id: '".$option['column_id']."', type: '".$option['type']."', sort: '".$option['sort']."', aggregate: '".$option['aggregate']."', label: '".$option['label']."', col_order: '".$option['col_order']."', chuman: '".$option['chuman']."', column: '".$option['column']."'}";
	}
	$output->data .= implode(",", $opt);
	$output->data .= "]
}
		";
		return $output;
	}

// 	function view_graph() {
// 		$output->title = "Report Graph";
// 		/* Options are in the menu*/
// 		$output->data = "
// 			<div id='GraphContainerArea' style='width: 900px; height: 450px;'></div>
// 			<div id='legend'></div>
// 			<script type='text/javascript'>
// 				var foo;
// 				var options = {};
// 
// 				var chart = new dojox.charting.Chart2D('GraphContainerArea');
// 				dojo.addOnLoad(makeObjects);
// 			</script>
// 			";
// 		return $output;
// 	}

	function view_execute_manually() {
		$output->layout = "ajax";

		return $output;
	}

	function view_histories($saved_reports) {
		$output->title = "Histories";
		$output->title_desc = "All occasions when the report has been executed.";

		if (!empty($saved_reports)) {
			$output->data .= "
				<div class='reports'>
					<table cellpadding='0' cellspacing='0'>
						<tr>
							<th>Time</th>
							<th>Format</th>
							<th>Dissemination</th>
							<th>&nbsp;</th>
						</tr>
						";

			foreach ($saved_reports as $report_tmp) {
				$dissemination = rand(-10, 20);
				if ($dissemination < 0) $dissemination = 0;
				$dissemination = "$dissemination user".($dissemination === 1 ? "" : "s");

				$output->data .= "
						<tr>
							<td>".$report_tmp['created']."</td>
							<td>Table, Graph and CSV</td>
							<td>$dissemination</td>
							<td>
								<ul>
									<li>".$this->l("tabular/history/".$report_tmp['saved_report_id'], "View/Download")."</li>
									<li>".$this->l("", "Disseminate Manually")."</li>
								</ul>
							</td>
						</tr>
						";
			}
			$output->data .= "
					</table>
				</div>
				";
		}

		return $output;
	}

	function view_history($tmp_table, $tmp_graph, $downloads) {
		if (!empty($downloads)) {
			$output->data .= "
				<ul>
				";
			foreach ($downloads as $download_text => $download_link) {
				$output->data .= "
					<li>".$this->l($download_link, $download_text, null, false)."</li>
					";
			}
			$output->data .= "
				</ul>
				";
		}

		if (!empty($tmp_table)) {
			$output->data .= "<h3>Tabular Data</h3>";
			$output->data .= $tmp_table;
		}

		if (!empty($tmp_graph)) {
			$output->data .= "<h3>Graphic Data</h3>";
			$output->data .= $tmp_graph;
		}

		return $output;
	}
}
?>