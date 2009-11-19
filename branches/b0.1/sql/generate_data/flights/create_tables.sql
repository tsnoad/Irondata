CREATE TABLE locations (
	location_id BIGSERIAL PRIMARY KEY,
	name TEXT
);

CREATE TABLE flights (
	flights_id BIGSERIAL PRIMARY KEY,
	origin BIGINT REFERENCES locations (location_id) ON UPDATE CASCADE ON DELETE CASCADE,
	destination BIGINT REFERENCES locations (location_id) ON UPDATE CASCADE ON DELETE CASCADE,
	date DATE,
	passengers BIGINT
);
