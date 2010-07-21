INSERT INTO groups VALUES ('admin');
INSERT INTO users (user_id, given_names, family_name, password) VALUES ('admin', 'Administration', 'User', md5('admin'));
INSERT INTO users_groups (user_id, group_id) VALUES ('admin', 'admin');

-- INSERT INTO groups VALUES ('staff');
-- INSERT INTO users (user_id, given_names, family_name, password) VALUES ('staff', 'Staff', 'Member', md5('password'));
-- INSERT INTO users_groups (user_id, group_id) VALUES ('staff', 'staff');

-- INSERT INTO groups VALUES ('visitors');
-- INSERT INTO users (user_id, given_names, family_name, password) VALUES ('visitor', 'Visitor', 'User', md5('password'));
-- INSERT INTO users (user_id, given_names, family_name, password) VALUES ('visitor2', 'Visitor', 'The Second', md5('password'));
-- INSERT INTO users_groups (user_id, group_id) VALUES ('visitor', 'visitors');
-- INSERT INTO users_groups (user_id, group_id) VALUES ('visitor2', 'visitors');

INSERT INTO system_acls_users (user_id, role, access) VALUES ('admin', 'login', true);
INSERT INTO system_acls_users (user_id, role, access) VALUES ('admin', 'reportscreate', true);
INSERT INTO system_acls_users (user_id, role, access) VALUES ('admin', 'admin', true);
INSERT INTO system_acls_users (user_id, role, access) VALUES ('admin', 'catalogue', true);

INSERT INTO system_acls_groups (group_id, role, access) VALUES ('admin', 'login', true);
INSERT INTO system_acls_groups (group_id, role, access) VALUES ('admin', 'reportscreate', true);
INSERT INTO system_acls_groups (group_id, role, access) VALUES ('admin', 'admin', true);
INSERT INTO system_acls_groups (group_id, role, access) VALUES ('admin', 'catalogue', true);

-- INSERT INTO system_acls_users (user_id, role, access) VALUES ('staff', 'admin', true);

-- INSERT INTO system_acls_groups (group_id, role, access) VALUES ('staff', 'login', true);
-- INSERT INTO system_acls_groups (group_id, role, access) VALUES ('staff', 'reports', true);

-- INSERT INTO system_acls_users (user_id, role, access) VALUES ('visitor2', 'reports', true);

-- INSERT INTO system_acls_groups (group_id, role, access) VALUES ('visitors', 'login', true);

-- INSERT INTO system_acls_ldap_groups (group_id, role, access) VALUES ('information management', 'login', true);
-- INSERT INTO system_acls_ldap_groups (group_id, role, access) VALUES ('information management', 'reports', true);
-- INSERT INTO system_acls_ldap_groups (group_id, role, access) VALUES ('information management', 'admin', true);
-- 
-- INSERT INTO system_acls_ldap_groups (group_id, role, access) VALUES ('finance', 'login', true);
-- INSERT INTO system_acls_ldap_groups (group_id, role, access) VALUES ('finance', 'reports', true);
-- 
-- INSERT INTO system_acls_ldap_users (user_id, role, access) VALUES ('tsnoad', 'login', true);
-- INSERT INTO system_acls_ldap_users (user_id, role, access) VALUES ('tsnoad', 'reports', true);
-- INSERT INTO system_acls_ldap_users (user_id, role, access) VALUES ('tsnoad', 'admin', true);

INSERT INTO modules VALUES ('admin', 'IronData Administration Functionality', 'This is the administration module.', 'Core', NULL, 'active', 't');
INSERT INTO modules VALUES ('catalogue', 'Data Source Catalogue', 'Store and manipulate the available data sources.', 'Core', NULL, 'active', 't');
INSERT INTO modules VALUES ('pgsql', 'PostgreSQL Data Source', 'Store and manipulate PostgreSQL data sources.', 'Data Source', 'Database', 'active', 't');
INSERT INTO modules VALUES ('tabular', 'Tabular Report Type', 'The tabular report types.', 'Report', NULL, 'active', 't');
INSERT INTO modules VALUES ('template', 'Template Reports', 'Report template functions', 'Report', NULL, 'active', 't');
INSERT INTO modules VALUES ('user', 'User Management', 'User and group management', 'Core', NULL, 'active', 't');
INSERT INTO modules VALUES ('workspace', 'User Workspaces', 'The users personal workspace', 'Core', NULL, 'active', 't');
INSERT INTO modules VALUES ('csv', '', '', '', NULL, 'active', 't');
INSERT INTO modules VALUES ('graphing', '', '', '', NULL, 'active', 't');
INSERT INTO modules VALUES ('pdf', '', '', '', NULL, 'active', 't');
INSERT INTO modules VALUES ('cron', '', '', '', NULL, 'active', 't');
INSERT INTO modules VALUES ('search', '', '', '', NULL, 'active', 'f');
INSERT INTO modules VALUES ('help', '', '', '', NULL, 'active', 'f');

