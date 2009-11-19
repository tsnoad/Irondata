#!/usr/bin/php5
<?php

$cities = array(
	1 => "Amsterdam",
	2 => "Antwerp",
	3 => "Athens",
	4 => "Barcelona",
	5 => "Berlin",
	6 => "Bremen",
	7 => "Bristol",
	8 => "Brussels",
	9 => "Bucharest",
	10 => "Budapest",
	11 => "Cardiff",
	12 => "Copenhagen",
	13 => "Donetsk",
	14 => "Dnipropetrovsk",
	15 => "Dublin",
	16 => "Frankfurt",
	17 => "Greater Galsgow",
	18 => "The Hague",
	19 => "Hamburg",
	20 => "Helsinki",
	21 => "Istanbul",
	22 => "Katowice",
	23 => "Kazan",
	24 => "Kharkov"
	);

foreach ($cities as $city_id => $city) {
	file_put_contents("insert_data.sql", "INSERT INTO locations (location_id, name) VALUES ('$city_id', '$city');\n", FILE_APPEND);
}

for ($date = date("Y-m-d"); strtotime($date) < strtotime("+1 month"); $date = date("Y-m-d", strtotime("+1 day", strtotime($date)))) {
	foreach ($cities as $origin => $tmp) {
		foreach ($cities as $destination => $tmp2) {
			if ($origin == $destination) continue;

			$flights_count = rand (1, 5);

			for ($i = 1; $i <= $flights_count; $i ++) {
				file_put_contents("insert_data.sql", "INSERT INTO flights (origin, destination, date, passengers) VALUES ('$origin', '$destination', '$date', '".rand(100, 300)."');\n", FILE_APPEND);
			}
		}
	}
}

?>