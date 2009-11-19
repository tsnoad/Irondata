-- INSERT INTO users VALUES ('admin', 'Administration', 'User', md5('admin'));
INSERT INTO groups VALUES ('admin');
INSERT INTO users (user_id, given_names, family_name, password) VALUES ('admin', 'Administration', 'User', md5('admin'));

INSERT INTO users_groups (user_id, group_id) VALUES ('admin', 'admin');

INSERT INTO modules VALUES ('admin', 'IronData Administration Functionality', 'This is the administration module.', 'Core', NULL, 'active', 't');
INSERT INTO modules VALUES ('catalogue', 'Data Source Catalogue', 'Store and manipulate the available data sources.', 'Core', NULL, 'active', 't');
INSERT INTO modules VALUES ('pgsql', 'PostgreSQL Data Source', 'Store and manipulate PostgreSQL data sources.', 'Data Source', 'Database', 'active', 't');
INSERT INTO modules VALUES ('tabular', 'Tabular Report Type', 'The tabular report types.', 'Report', NULL, 'active', 't');
INSERT INTO modules VALUES ('template', 'Template Reports', 'Report template functions', 'Report', NULL, 'active', 't');
INSERT INTO modules VALUES ('user', 'User Management', 'User and group management', 'Core', NULL, 'active', 't');
INSERT INTO modules VALUES ('workspace', 'User Workspaces', 'The users personal workspace', 'Core', NULL, 'active', 't');

