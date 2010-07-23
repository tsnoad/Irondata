---IronData Application Database Schema
---Looking Glass Solutions
---(c) 2007

CREATE TABLE users (
	user_id TEXT PRIMARY KEY,
	given_names TEXT,
	family_name TEXT,
	password TEXT,
	session BIGINT,
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
	permission_id BIGSERIAL PRIMARY KEY,
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
	object_id BIGSERIAL PRIMARY KEY,
	name TEXT,
	type TEXT,
	module_id TEXT REFERENCES modules ON DELETE CASCADE ON UPDATE CASCADE,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now()
);

CREATE TABLE databases (
	database_id BIGSERIAL PRIMARY KEY,
	name TEXT,
	object_id BIGINT REFERENCES objects ON DELETE CASCADE ON UPDATE CASCADE,
	host TEXT,
	username TEXT,
	password TEXT,
	human_name TEXT,
	description TEXT,
	notes TEXT,
	records BIGINT,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now(),
	UNIQUE (name, object_id)
);
CREATE TABLE tables (
	table_id BIGSERIAL PRIMARY KEY,
	name TEXT,
	database_id BIGINT REFERENCES databases ON DELETE CASCADE ON UPDATE CASCADE,
	table_type TEXT DEFAULT 'Data',
	human_name TEXT,
	description TEXT,
	notes TEXT,
	records BIGINT,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now(),
	UNIQUE (name, database_id)
);
CREATE TABLE columns (
	column_id BIGSERIAL PRIMARY KEY,
	name TEXT,
	table_id BIGINT REFERENCES tables ON DELETE CASCADE ON UPDATE CASCADE,
	human_name TEXT,
	description TEXT,
	data_type TEXT,
	key_type TEXT,
	references_column BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	references_disabled BOOL DEFAULT 'f',
	example TEXT,
	available BOOL DEFAULT 't',
	dropdown BOOL DEFAULT 'f',
	records BIGINT,
	modified_by TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	modified_time TIMESTAMP DEFAULT now(),
	UNIQUE (name, table_id)
);
CREATE TABLE table_joins (
	table_join_id BIGSERIAL PRIMARY KEY,
	table1 BIGINT REFERENCES tables ON DELETE CASCADE ON UPDATE CASCADE,
	table2 BIGINT REFERENCES tables ON DELETE CASCADE ON UPDATE CASCADE,
	method TEXT,
	UNIQUE (table1, table2, method)
);

CREATE TABLE templates (
	template_id BIGSERIAL PRIMARY KEY,
	name TEXT,
	draft BOOL DEFAULT 't',
	module TEXT,
	type TEXT,
	last_run TIMESTAMP,
	last_time BIGINT,
	last_by BIGINT,
	last_size BIGINT,
	description TEXT,
	--owner user_id: starts with user_ or ldap_
	owner TEXT,
	header TEXT,
	footer TEXT,
	publish_table BOOLEAN DEFAULT true,
	publish_graph BOOLEAN DEFAULT true,
	graph_type TEXT,
	object_id BIGINT REFERENCES objects ON DELETE CASCADE ON UPDATE CASCADE,
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
	--Tells cron to execute template on next pass
	execute_now BOOLEAN DEFAULT false,
	execution_queued BOOLEAN DEFAULT false,
	execution_executing BOOLEAN DEFAULT false,
	email_dissemination BOOLEAN DEFAULT false,
	email_recipients TEXT,
	email_subject TEXT,
	email_body TEXT
);

CREATE TABLE saved_reports (
	saved_report_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	report TEXT,
	demo BOOL DEFAULT 'f',
	draft BOOL DEFAULT 't',
	created TIMESTAMP,
	run_time BIGINT,
	run_by BIGINT,
	run_size BIGINT
);

CREATE TABLE list_templates (
	list_template_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	index BOOL DEFAULT false,
	duplicates BOOL,
	subtotal BOOL, 
	sort TEXT,
	aggregate TEXT,
	label TEXT,
	optional BOOL,
	col_order BIGINT,
	level INT DEFAULT 0,
	style VARCHAR(50),
	display_label BOOL DEFAULT 't',
	indent_cells INT DEFAULT 0,
	table_join_id BIGINT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE list_constraints (
	list_constraints_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE simplegraph_templates (
	autotable_template_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	aggregate TEXT,
	axis TEXT
);

CREATE TABLE simplegraph_constraints (
	autotable_constraints_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE backgrounds (
	background_id BIGSERIAL PRIMARY KEY,
	url VARCHAR(255) UNIQUE,
	running BOOL DEFAULT 'f', 
	complete TIMESTAMP,
	results TEXT
);

CREATE TABLE etl_templates (
	etl_template_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	aggregate TEXT,
	optional BOOL,
	col_order INT
);

CREATE TABLE etl_constraints (
	etl_constraints_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE etl_load (
	etl_load_id BIGSERIAL PRIMARY KEY,
	etl_template_id BIGINT REFERENCES etl_templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE tabular_templates (
	tabular_template_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	axis_type VARCHAR(20)
);

CREATE TABLE tabular_constraints (
	tabular_constraints_id BIGSERIAL PRIMARY KEY,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	table_join_id BIGINT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE,
	choose BOOL DEFAULT 'f'
);

CREATE TABLE tabular_constraint_logic (
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	logic TEXT
);

CREATE TABLE tabular_templates_auto (
	tabular_templates_auto_id BIGSERIAL PRIMARY KEY,
	tabular_template_id BIGINT REFERENCES tabular_templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	table_join_id BIGINT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	aggregate TEXT,
	human_name TEXT
);

CREATE TABLE tabular_templates_trend (
	tabular_templates_trend_id BIGSERIAL PRIMARY KEY,
	tabular_template_id BIGINT REFERENCES tabular_templates ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	table_join_id BIGINT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE,
	sort TEXT,
	start_date TIMESTAMP,
	end_date TIMESTAMP,
	interval VARCHAR(20),
	human_name TEXT
);

CREATE TABLE tabular_templates_single (
	tabular_templates_single_id BIGSERIAL PRIMARY KEY,
	tabular_template_id BIGINT REFERENCES tabular_templates ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE tabular_templates_manual (
	tabular_templates_manual_id BIGSERIAL PRIMARY KEY,
	tabular_template_id BIGINT REFERENCES tabular_templates ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE tabular_templates_manual_squids (
	tabular_templates_manual_squid_id BIGSERIAL PRIMARY KEY,
	tabular_templates_manual_id BIGINT REFERENCES tabular_templates_manual ON DELETE CASCADE ON UPDATE CASCADE,
	human_name TEXT
);

CREATE TABLE tabular_templates_manual_squid_constraints (
	squid_constraints_id BIGSERIAL PRIMARY KEY,
	tabular_templates_manual_squid_id BIGINT REFERENCES tabular_templates_manual_squids ON DELETE CASCADE ON UPDATE CASCADE,
	column_id BIGINT REFERENCES columns ON DELETE CASCADE ON UPDATE CASCADE,
	type TEXT,
	value TEXT,
	table_join_id BIGINT REFERENCES table_joins ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE tabular_templates_manual_squid_constraint_logic (
	tabular_templates_manual_squid_id BIGINT REFERENCES tabular_templates_manual_squids ON DELETE CASCADE ON UPDATE CASCADE,
	logic TEXT
);

CREATE TABLE settings (
	module_id TEXT,
	key TEXT,
	value TEXT,
	PRIMARY KEY (module_id, key)
);

CREATE TABLE graph_documents (
	graph_document_id BIGSERIAL PRIMARY KEY,
	saved_report_id BIGINT REFERENCES saved_reports ON DELETE CASCADE ON UPDATE CASCADE,
	created TIMESTAMP DEFAULT now(),
	svg_path TEXT,
	svg_url TEXT,
	pdf_path TEXT,
	pdf_url TEXT
);

CREATE TABLE table_documents (
	table_document_id BIGSERIAL PRIMARY KEY,
	saved_report_id BIGINT REFERENCES saved_reports ON DELETE CASCADE ON UPDATE CASCADE,
	created TIMESTAMP DEFAULT now(),
	html_path TEXT,
	html_url TEXT,
	pdf_path TEXT,
	pdf_url TEXT
);

CREATE TABLE csv_documents (
	csv_document_id BIGSERIAL PRIMARY KEY,
	saved_report_id BIGINT REFERENCES saved_reports ON DELETE CASCADE ON UPDATE CASCADE,
	created TIMESTAMP DEFAULT now(),
	txt_path TEXT,
	txt_url TEXT
);

CREATE TABLE ldap_recipients (
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	uid TEXT NOT NULL,
	UNIQUE (template_id, uid)
);

CREATE TABLE system_acls_users (
	user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (user_id, role)
);

CREATE TABLE system_acls_groups (
	group_id TEXT REFERENCES groups ON DELETE CASCADE ON UPDATE CASCADE,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (group_id, role)
);

CREATE TABLE system_acls_ldap_users (
	user_id TEXT,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (user_id, role)
);

CREATE TABLE system_acls_ldap_groups (
	group_id TEXT,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (group_id, role)
);

CREATE TABLE report_acls_users (
	user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (user_id, template_id, role)
);

CREATE TABLE report_acls_groups (
	group_id TEXT REFERENCES groups ON DELETE CASCADE ON UPDATE CASCADE,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (group_id, template_id, role)
);

CREATE TABLE report_acls_ldap_users (
	user_id TEXT,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (user_id, template_id, role)
);

CREATE TABLE report_acls_ldap_groups (
	group_id TEXT,
	template_id BIGINT REFERENCES templates ON DELETE CASCADE ON UPDATE CASCADE,
	role TEXT,
	access BOOLEAN DEFAULT 't',
	UNIQUE (group_id, template_id, role)
);
