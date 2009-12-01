CREATE LANGUAGE plpgsql;


--If the intersection column of a tabular report is changed we need to dump the selected x and y columns.
CREATE OR REPLACE FUNCTION tabular_templates_auto_update() RETURNS trigger AS $$
  DECLARE
    tmp RECORD;
  BEGIN
    FOR tmp IN SELECT * FROM tabular_templates WHERE tabular_template_id=NEW.tabular_template_id AND type='c' LOOP
      IF (OLD.column_id != NEW.column_id) THEN
        DELETE FROM tabular_templates WHERE template_id=tmp.template_id AND type='x';
        DELETE FROM tabular_templates WHERE template_id=tmp.template_id AND type='y';
      END IF;
    END LOOP;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tabular_templates_auto_update AFTER UPDATE ON tabular_templates_auto
  FOR EACH ROW EXECUTE PROCEDURE tabular_templates_auto_update();


--If the x or y axis type is changes (like from database to date trend) we need to dump the old axis information.
CREATE OR REPLACE FUNCTION tabular_templates_update() RETURNS trigger AS $$
  BEGIN
    IF (OLD.type = 'x' OR OLD.type = 'y') AND OLD.axis_type != NEW.axis_type THEN
      IF OLD.axis_type = 'auto' THEN
        DELETE FROM tabular_templates_auto WHERE tabular_template_id = OLD.tabular_template_id;
      END IF;
      IF OLD.axis_type = 'trend' THEN
        DELETE FROM tabular_templates_trend WHERE tabular_template_id = OLD.tabular_template_id;
      END IF;
      IF OLD.axis_type = 'single' THEN
        DELETE FROM tabular_templates_single WHERE tabular_template_id = OLD.tabular_template_id;
      END IF;
      IF OLD.axis_type = 'manual' THEN
        DELETE FROM tabular_templates_manual WHERE tabular_template_id = OLD.tabular_template_id;
      END IF;
    END IF;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tabular_templates_update AFTER UPDATE ON tabular_templates
  FOR EACH ROW EXECUTE PROCEDURE tabular_templates_update();


--If an intersection is deleted, clean up the related axies
CREATE OR REPLACE FUNCTION tabular_templates_delete() RETURNS trigger AS $$
  BEGIN
    IF OLD.type='c' THEN
      DELETE FROM tabular_templates WHERE template_id=OLD.template_id AND type='x';
      DELETE FROM tabular_templates WHERE template_id=OLD.template_id AND type='y';
    END IF;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tabular_templates_delete AFTER DELETE ON tabular_templates
  FOR EACH ROW EXECUTE PROCEDURE tabular_templates_delete();


--After a new constraint is added, add the constraint id to the constraint logic. Constraint logic is what tells us how to put the where clause together and looks something like: 1 AND (2 OR 3)
CREATE OR REPLACE FUNCTION tabular_constraints_create() RETURNS trigger AS $$
  DECLARE
    tmp RECORD;
  BEGIN
    FOR tmp IN SELECT COUNT(*) FROM tabular_constraint_logic WHERE template_id = NEW.template_id LOOP 
      IF tmp.count < 1 THEN
        INSERT INTO tabular_constraint_logic (template_id, logic) VALUES (NEW.template_id, NEW.tabular_constraints_id);
      ELSE
        UPDATE tabular_constraint_logic SET logic = logic || ' AND ' || NEW.tabular_constraints_id WHERE template_id = NEW.template_id;
      END IF;
    END LOOP;

    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tabular_constraints_create AFTER INSERT ON tabular_constraints
  FOR EACH ROW EXECUTE PROCEDURE tabular_constraints_create();


--If constraint logic is updated and all constraints are removed, delete the constraint logic row
CREATE OR REPLACE FUNCTION tabular_constraints_update() RETURNS trigger AS $$
  BEGIN
    DELETE FROM tabular_constraint_logic WHERE template_id=NEW.template_id AND logic IS NULL;

    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tabular_constraints_update AFTER UPDATE ON tabular_constraint_logic
  FOR EACH ROW EXECUTE PROCEDURE tabular_constraints_update();


--Constraint logic for manual axies
--After a new constraint is added, add the constraint id to the constraint logic. Constraint logic is what tells us how to put te where clause together and looks something like: 1 AND (2 OR 3)
CREATE OR REPLACE FUNCTION squid_constraints_create() RETURNS trigger AS $$
  DECLARE
    tmp RECORD;
  BEGIN
    FOR tmp IN SELECT COUNT(*) FROM tabular_templates_manual_squid_constraint_logic WHERE tabular_templates_manual_squid_id = NEW.tabular_templates_manual_squid_id LOOP 
      IF tmp.count < 1 THEN
        INSERT INTO tabular_templates_manual_squid_constraint_logic (tabular_templates_manual_squid_id, logic) VALUES (NEW.tabular_templates_manual_squid_id, NEW.squid_constraints_id);
      ELSE
        UPDATE tabular_templates_manual_squid_constraint_logic SET logic = logic || ' AND ' || NEW.squid_constraints_id WHERE tabular_templates_manual_squid_id = NEW.tabular_templates_manual_squid_id;
      END IF;
    END LOOP;

    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER squid_constraints_create AFTER INSERT ON tabular_templates_manual_squid_constraints
  FOR EACH ROW EXECUTE PROCEDURE squid_constraints_create();

--If constraint logic is updated and all constraints are removed, delete the constraint logic row
CREATE OR REPLACE FUNCTION squid_constraints_update() RETURNS trigger AS $$
  BEGIN
    DELETE FROM tabular_templates_manual_squid_constraint_logic WHERE tabular_templates_manual_squid_id=NEW.tabular_templates_manual_squid_id AND logic IS NULL;

    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER squid_constraints_update AFTER UPDATE ON tabular_templates_manual_squid_constraint_logic
  FOR EACH ROW EXECUTE PROCEDURE squid_constraints_update();


--If a new graph is generated for an existing saved report, overwrite the old graph
CREATE OR REPLACE FUNCTION graph_documents_insert_duplicate() RETURNS trigger AS $$
  BEGIN
    DELETE FROM graph_documents WHERE saved_report_id=NEW.saved_report_id AND graph_document_id!=NEW.graph_document_id;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER graph_documents_insert_duplicate AFTER INSERT ON graph_documents
  FOR EACH ROW EXECUTE PROCEDURE graph_documents_insert_duplicate();


--If a new table is generated for an existing saved report, overwrite the old table
CREATE OR REPLACE FUNCTION table_documents_insert_duplicate() RETURNS trigger AS $$
  BEGIN
    DELETE FROM table_documents WHERE saved_report_id=NEW.saved_report_id AND table_document_id!=NEW.table_document_id;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER table_documents_insert_duplicate AFTER INSERT ON table_documents
  FOR EACH ROW EXECUTE PROCEDURE table_documents_insert_duplicate();


--If a new csv data file is generated for an existing saved report, overwrite the old csv data file
CREATE OR REPLACE FUNCTION csv_documents_insert_duplicate() RETURNS trigger AS $$
  BEGIN
    DELETE FROM csv_documents WHERE saved_report_id=NEW.saved_report_id AND csv_document_id!=NEW.csv_document_id;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER csv_documents_insert_duplicate AFTER INSERT ON csv_documents
  FOR EACH ROW EXECUTE PROCEDURE csv_documents_insert_duplicate();


--When a new report is created, grant the owner and the admin user and group access
CREATE OR REPLACE FUNCTION template_create_acl() RETURNS trigger AS $$
  DECLARE
    tmp RECORD;
  BEGIN
    IF NEW.owner != 'user_admin' THEN
      INSERT INTO report_acls_users (user_id, template_id, role, access) VALUES ('admin', NEW.template_id, 'histories', true);
      INSERT INTO report_acls_users (user_id, template_id, role, access) VALUES ('admin', NEW.template_id, 'edit', true);
      INSERT INTO report_acls_users (user_id, template_id, role, access) VALUES ('admin', NEW.template_id, 'execute', true);

      INSERT INTO report_acls_groups (group_id, template_id, role, access) VALUES ('admin', NEW.template_id, 'histories', true);
      INSERT INTO report_acls_groups (group_id, template_id, role, access) VALUES ('admin', NEW.template_id, 'edit', true);
      INSERT INTO report_acls_groups (group_id, template_id, role, access) VALUES ('admin', NEW.template_id, 'execute', true);
    END IF;

    IF NEW.owner ~ '^user_' THEN
      INSERT INTO report_acls_users (user_id, template_id, role, access) VALUES (substring(NEW.owner from 6), NEW.template_id, 'histories', true);
      INSERT INTO report_acls_users (user_id, template_id, role, access) VALUES (substring(NEW.owner from 6), NEW.template_id, 'edit', true);
      INSERT INTO report_acls_users (user_id, template_id, role, access) VALUES (substring(NEW.owner from 6), NEW.template_id, 'execute', true);

      FOR tmp IN SELECT * FROM users_groups WHERE user_id=substring(NEW.owner from 6) LOOP
        INSERT INTO report_acls_groups (group_id, template_id, role, access) VALUES (tmp.group_id, NEW.template_id, 'histories', true);
        INSERT INTO report_acls_groups (group_id, template_id, role, access) VALUES (tmp.group_id, NEW.template_id, 'edit', true);
        INSERT INTO report_acls_groups (group_id, template_id, role, access) VALUES (tmp.group_id, NEW.template_id, 'execute', true);
      END LOOP;
    END IF;

    IF NEW.owner ~ '^ldap_' THEN
      INSERT INTO report_acls_ldap_users (user_id, template_id, role, access) VALUES (substring(NEW.owner from 6), NEW.template_id, 'histories', true);
      INSERT INTO report_acls_ldap_users (user_id, template_id, role, access) VALUES (substring(NEW.owner from 6), NEW.template_id, 'edit', true);
      INSERT INTO report_acls_ldap_users (user_id, template_id, role, access) VALUES (substring(NEW.owner from 6), NEW.template_id, 'execute', true);
    END IF;

    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER template_create_acl AFTER INSERT ON templates
  FOR EACH ROW EXECUTE PROCEDURE template_create_acl();