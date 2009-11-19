---IronData Application Database Schema
---Looking Glass Solutions
---(c) 2007

CREATE TABLE users (
	user_id TEXT PRIMARY KEY,
	given_names TEXT,
	family_name TEXT,
	password TEXT,
	session INT,
	session_time TIMESTAMP,
	modified_by TEXT,
	modified_time TIMESTAMP DEFAULT now()
);

CREATE TABLE groups (
	group_id TEXT PRIMARY KEY,
	modified_by TEXT,
	modified_time TIMESTAMP DEFAULT now()
);

-- ALTER TABLE users ADD COLUMN group_id TEXT NOT NULL REFERENCES groups ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE users_groups (
	user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	group_id TEXT REFERENCES groups ON DELETE CASCADE ON UPDATE CASCADE,
	UNIQUE (user_id, group_id)
);

CREATE TABLE permissions (
	permission_id SERIAL PRIMARY KEY,
	element TEXT,
	function TEXT,
	permission BOOL,
	group_id TEXT REFERENCES groups ON DELETE CASCADE ON UPDATE CASCADE,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now()
);

CREATE TABLE modules (
	module_id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	description TEXT NOT NULL,
	type TEXT NOT NULL,
	subtype TEXT,
	status TEXT NOT NULL DEFAULT 'inactive',
	core BOOL DEFAULT 'f'
);

--- Each data source and target is a data object.
CREATE TABLE objects (
	object_id SERIAL PRIMARY KEY,
	name TEXT,
	type TEXT,
	module_id TEXT REFERENCES modules ON DELETE CASCADE ON UPDATE CASCADE,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now()
);

CREATE TABLE databases (
	database_id SERIAL PRIMARY KEY,
	name TEXT,
	object_id INT REFERENCES objects ON DELETE CASCADE ON UPDATE CASCADE,
	host TEXT,
	username TEXT,
	password TEXT,
	human_name TEXT,
	description TEXT,
	notes TEXT,
	records INT,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now(),
	UNIQUE (name, object_id)
);
CREATE TABLE tables (
	table_id SERIAL PRIMARY KEY,
	name TEXT,
	database_id INT REFERENCES databases ON DELETE CASCADE ON UPDATE CASCADE,
	table_type TEXT DEFAULT 'Data',
	human_name TEXT,
	description TEXT,
	notes TEXT,
	records INT,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now(),
	UNIQUE (name, database_id)
);
CREATE TABLE columns (
	column_id SERIAL PRIMARY KEY,
	name TEXT,
	table_id INT REFERENCES tables ON DELETE CASCADE ON UPDATE CASCADE,
	human_name TEXT,
	description TEXT,
	data_type TEXT,
	key_type TEXT,
	references_column INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	references_disabled BOOL DEFAULT 'f',
	example TEXT,
	available BOOL DEFAULT 't',
	dropdown BOOL DEFAULT 'f',
	records INT,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now(),
	UNIQUE (name, table_id)
);
CREATE TABLE table_joins (
	table_join_id SERIAL PRIMARY KEY,
	table1 INT REFERENCES tables ON DELETE CASCADE ON UPDATE CASCADE,
	table2 INT REFERENCES tables ON DELETE CASCADE ON UPDATE CASCADE,
	method TEXT,
	UNIQUE (table1, table2, method)
);

CREATE TABLE templates (
	template_id SERIAL PRIMARY KEY,
	name TEXT,
	draft BOOL DEFAULT 't',
	module TEXT,
	type TEXT,
	last_run TIMESTAMP,
	last_time INT,
	last_by INT,
	last_size INT,
	description TEXT,
	header TEXT,
	footer TEXT,
	publish_table BOOLEAN DEFAULT true,
	publish_graph BOOLEAN DEFAULT true,
	graph_type TEXT,
	object_id INT REFERENCES objects ON DELETE CASCADE ON UPDATE CASCADE,
	execute BOOLEAN DEFAULT false,
	execute_hourly BOOLEAN DEFAULT false,
	execute_daily BOOLEAN DEFAULT false,
	execute_weekly BOOLEAN DEFAULT false,
	execute_monthly BOOLEAN DEFAULT false,
	--hour number: 0 to 23
	execute_hour INT DEFAULT '9',
	--Day of week number: 1 to 7 represents Monday to Sunday
	execute_dayofweek INT DEFAULT '1',
	--Day of month
	execute_day INT DEFAULT '1',
	email_dissemination BOOLEAN DEFAULT false,
	email_recipients TEXT,
	email_subject TEXT,
	email_body TEXT
);

CREATE TABLE saved_reports (
	saved_report_id BIGSERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	report TEXT,
	demo BOOL DEFAULT 'f',
	draft BOOL DEFAULT 't',
	created TIMESTAMP,
	run_time INT,
	run_by INT,
	run_size INT
);

CREATE TABLE list_templates (
	list_template_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	duplicates BOOL,
	subtotal BOOL, 
	sort TEXT,
	aggregate TEXT,
	label TEXT,
	optional BOOL,
	col_order INT,
	level INT DEFAULT 0,
	style VARCHAR(50),
	display_label BOOL DEFAULT 't',
	indent_cells INT DEFAULT 0
);

CREATE TABLE list_constraints (
	list_constraints_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE simplegraph_templates (
	autotable_template_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	aggregate TEXT,
	axis TEXT
);

CREATE TABLE simplegraph_constraints (
	autotable_constraints_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE backgrounds (
	background_id SERIAL PRIMARY KEY,
	url VARCHAR(255) UNIQUE,
	running BOOL DEFAULT 'f', 
	complete TIMESTAMP,
	results TEXT
);

CREATE TABLE etl_templates (
	etl_template_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	aggregate TEXT,
	optional BOOL,
	col_order INT
);

CREATE TABLE etl_constraints (
	etl_constraints_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE etl_load (
	etl_load_id SERIAL PRIMARY KEY,
	etl_template_id INT REFERENCES etl_templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE tabular_templates (
	tabular_template_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	axis_type VARCHAR(20)
);

CREATE TABLE tabular_constraints (
	tabular_constraints_id SERIAL PRIMARY KEY,
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	table_join_id INT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE tabular_constraint_logic (
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	logic TEXT
);

-- CREATE TABLE org_constraints (
-- 	index_id BIGSERIAL PRIMARY KEY,
-- 	constraints_id BIGSERIAL REFERENCES tabular_constraints (tabular_constraints_id) ON DELETE CASCADE ON UPDATE CASCADE
-- );
-- 
-- CREATE TABLE org_clauses (
-- 	clause_id BIGSERIAL PRIMARY KEY,
-- 	andor TEXT,
-- 	goesbefore BIGINT REFERENCES org_constraints (index_id) ON DELETE CASCADE ON UPDATE CASCADE
-- );
-- 
-- CREATE TABLE org_brackets (
-- 	bracket_id BIGSERIAL PRIMARY KEY,
-- 	goesbefore BIGINT REFERENCES org_constraints (index_id) ON DELETE CASCADE ON UPDATE CASCADE
-- );
-- 
-- CREATE TABLE org_closing_brackets (
-- 	closing_bracket_id BIGSERIAL PRIMARY KEY,
-- 	goesafter BIGINT REFERENCES org_constraints (index_id) ON DELETE CASCADE ON UPDATE CASCADE
-- );

CREATE TABLE tabular_templates_auto (
	tabular_templates_auto_id SERIAL PRIMARY KEY,
	tabular_template_id INT REFERENCES tabular_templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	table_join_id INT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	aggregate TEXT,
	human_name TEXT
);

CREATE TABLE tabular_templates_trend (
	tabular_templates_trend_id SERIAL PRIMARY KEY,
	tabular_template_id INT REFERENCES tabular_templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id INT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	table_join_id INT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	start_date TIMESTAMP,
	end_date TIMESTAMP,
	interval VARCHAR(20),
	human_name TEXT
);

CREATE TABLE tabular_templates_manual (
	tabular_templates_manual_id SERIAL PRIMARY KEY,
	tabular_template_id INT REFERENCES tabular_templates ON DELETE CASCADE ON UPDATE CASCADE,
	col_order INT,
	human_name TEXT
);

CREATE TABLE settings (
	module_id TEXT,
	key TEXT,
	value TEXT,
	PRIMARY KEY (module_id, key)
);

CREATE TABLE graph_documents (
	graph_document_id BIGSERIAL PRIMARY KEY,
	saved_report_id INT REFERENCES saved_reports ON DELETE CASCADE ON UPDATE CASCADE,
	created TIMESTAMP DEFAULT now(),
	svg_path TEXT,
	svg_url TEXT,
	pdf_path TEXT,
	pdf_url TEXT
);

CREATE TABLE table_documents (
	table_document_id BIGSERIAL PRIMARY KEY,
	saved_report_id INT REFERENCES saved_reports ON DELETE CASCADE ON UPDATE CASCADE,
	created TIMESTAMP DEFAULT now(),
	html_path TEXT,
	html_url TEXT,
	pdf_path TEXT,
	pdf_url TEXT
);

CREATE TABLE csv_documents (
	csv_document_id BIGSERIAL PRIMARY KEY,
	saved_report_id INT REFERENCES saved_reports ON DELETE CASCADE ON UPDATE CASCADE,
	created TIMESTAMP DEFAULT now(),
	txt_path TEXT,
	txt_url TEXT
);

CREATE TABLE ldap_recipients (
	template_id INT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	uid TEXT NOT NULL,
	UNIQUE (template_id, uid)
);

-- CREATE TABLE system_acl_types (
-- 	acl_id BIGINT PRIMARY KEY,
-- 	name TEXT NOT NULL,
-- 	default BOOLEAN DEFAULT 'f'
-- );
-- 
-- CREATE TABLE system_acls_users (
-- 	acl_id BIGINT REFERENCES system_acl_types ON DELETE CASCADE ON UPDATE CASCADE,
-- 	user_id BIGINT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
-- 	access BOOLEAN DEFAULT 'f'
-- );
-- 
-- CREATE TABLE system_acls_groups (
-- 	acl_id BIGINT REFERENCES system_acl_types ON DELETE CASCADE ON UPDATE CASCADE,
-- 	group_id BIGINT REFERENCES groups ON DELETE CASCADE ON UPDATE CASCADE,
-- 	access BOOLEAN DEFAULT 'f'
-- );
-- 
-- CREATE TABLE system_acls_ldap_users (
-- );