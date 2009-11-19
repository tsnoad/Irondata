#!/usr/bin/php5
<?php

$sites = array(
	"Canberra" => 1,
	"Mount Ginini" => 2,
	"Sydney Airport" => 3,
	"Sydney Olympic Park" => 4
	);

$observation_types = array(
	"air_temp" => 1,
	"apparent_t" => 2,
	"delta_t" => 3,
	"dewpt" => 4,
	"gust_kmh" => 5,
	"gust_kt" => 6,
	"press" => 7,
	"rain_trace" => 8,
	"rel_hum" => 9,
	"wind_dir" => 10,
	"wind_spd_kmh" => 11,
	"wind_spd_kt" => 12
	);

$site_sources = array(
	"Canberra" => "http://www.bom.gov.au/fwo/IDN60903/IDN60903.94926.json",
	"Mount Ginini" => "http://www.bom.gov.au/fwo/IDN60903/IDN60903.95925.json",
	"Sydney Airport" => "http://www.bom.gov.au/fwo/IDN60901/IDN60901.94767.json",
	"Sydney Olympic Park" => "http://www.bom.gov.au/fwo/IDN60901/IDN60901.95765.json"
	);

foreach ($site_sources as $site_name => $site_source) {
	$json = file_get_contents($site_source);
	$data = json_decode($json, true);

	foreach ($data['observations']['data'] as $observations) {
		$site = $sites[$observations['name']];

		$date_year = substr($observations['local_date_time_full'], 0, 4);
		$date_month = substr($observations['local_date_time_full'], 4, 2);
		$date_day = substr($observations['local_date_time_full'], 6, 2);
		$date_hour = substr($observations['local_date_time_full'], 8, 2);
		$date_minute = substr($observations['local_date_time_full'], 10, 2);
		$date_second = substr($observations['local_date_time_full'], 12, 2);

		$date = "$date_year/$date_month/$date_day $date_hour:$date_minute:$date_second";

		foreach ($observations as $observation_name => $observation_data) {
			switch ($observation_name) {
				case "sort_order":
				case "wmo":
				case "history_product":
				case "local_date_time":
					break;
				case "name":
					break;
				case "local_date_time_full":
					break;
				case "air_temp":
				case "apparent_t":
				case "delta_t":
				case "dewpt":
				case "gust_kmh":
				case "gust_kt":
				case "press":
				case "rain_trace":
				case "rel_hum":
				case "wind_dir":
				case "wind_spd_kmh":
				case "wind_spd_kt":
					$observation_type = $observation_types[$observation_name];

					file_put_contents("insert_data.sql", "INSERT INTO observations (site, observation_type, date, data) VALUES ('$site', '$observation_type', '$date', '$observation_data');\n", FILE_APPEND);
					break;
				default:
					break;
			}
		}
	}
}


// $cities = array(
// 	1 => "Amsterdam",
// 	2 => "Antwerp",
// 	3 => "Athens",
// 	4 => "Barcelona",
// 	5 => "Berlin",
// 	6 => "Bremen",
// 	7 => "Bristol",
// 	8 => "Brussels",
// 	9 => "Bucharest",
// 	10 => "Budapest",
// 	11 => "Cardiff",
// 	12 => "Copenhagen",
// 	13 => "Donetsk",
// 	14 => "Dnipropetrovsk",
// 	15 => "Dublin",
// 	16 => "Frankfurt",
// 	17 => "Greater Galsgow",
// 	18 => "The Hague",
// 	19 => "Hamburg",
// 	20 => "Helsinki",
// 	21 => "Istanbul",
// 	22 => "Katowice",
// 	23 => "Kazan",
// 	24 => "Kharkov"
// 	);
// 
// foreach ($cities as $city_id => $city) {
// 	file_put_contents("insert_data.sql", "INSERT INTO locations (location_id, name) VALUES ('$city_id', '$city');\n", FILE_APPEND);
// }
// 
// for ($date = date("Y-m-d"); strtotime($date) < strtotime("+1 month"); $date = date("Y-m-d", strtotime("+1 day", strtotime($date)))) {
// 	foreach ($cities as $origin => $tmp) {
// 		foreach ($cities as $destination => $tmp2) {
// 			if ($origin == $destination) continue;
// 
// 			$flights_count = rand (1, 5);
// 
// 			for ($i = 1; $i <= $flights_count; $i ++) {
// 				file_put_contents("insert_data.sql", "INSERT INTO flights (origin, destination, date, passengers) VALUES ('$origin', '$destination', '$date', '".rand(100, 300)."');\n", FILE_APPEND);
// 			}
// 		}
// 	}
// }

?>