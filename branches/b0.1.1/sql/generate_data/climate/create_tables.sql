CREATE TABLE sites (
	site_id BIGSERIAL PRIMARY KEY,
	name TEXT
);

CREATE TABLE observation_types (
	observation_type_id BIGSERIAL PRIMARY KEY,
	name TEXT,
	description TEXT,
	unit TEXT
);

CREATE TABLE observations (
	observation_id BIGSERIAL PRIMARY KEY,
	site BIGINT REFERENCES sites (site_id) ON UPDATE CASCADE ON DELETE CASCADE,
	observation_type BIGINT REFERENCES observation_types (observation_type_id) ON UPDATE CASCADE ON DELETE CASCADE,
	date TIMESTAMP,
	data TEXT,
	UNIQUE (site, observation_type, date)
);

INSERT INTO sites (site_id, name) VALUES (1, 'Canberra');
INSERT INTO sites (site_id, name) VALUES (2, 'Mount Ginini');
INSERT INTO sites (site_id, name) VALUES (3, 'Sydney Airport');
INSERT INTO sites (site_id, name) VALUES (4, 'Sydney Olympic Park');

INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (1, 'air_temp', 'Air Temerature', 'Degrees C');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (2, 'apparent_t', 'Apparent Temerature', 'Degrees C');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (3, 'delta_t', 'Temerature Change', 'Degrees C');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (4, 'dewpt', 'Dew Temerature', 'Degrees C');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (5, 'gust_kmh', 'Wind Gust Speed', 'Km/h');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (6, 'gust_kt', 'Wind Gust Speed (Knots)', 'NMp/h');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (7, 'press', 'Pressure', 'hPa');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (8, 'rain_trace', 'Rain Since 9AM', 'mm');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (9, 'rel_hum', 'Relative Humidity', '%');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (10, 'wind_dir', 'Wind Direction', '');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (11, 'wind_spd_kmh', 'Wind Speed', 'Km/hs');
INSERT INTO observation_types (observation_type_id, name, description, unit) VALUES (12, 'wind_spd_kt', 'Wind Speed (Knots)', 'NMm/h');