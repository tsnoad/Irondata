CREATE LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION tabular_templates_auto_update() RETURNS trigger AS $$
  DECLARE
    squid RECORD;
  BEGIN
    FOR squid IN SELECT * FROM tabular_templates WHERE tabular_template_id=NEW.tabular_template_id AND type='c' LOOP
      IF (OLD.column_id != NEW.column_id) THEN
        DELETE FROM tabular_templates WHERE template_id=squid.template_id AND type='x';
        DELETE FROM tabular_templates WHERE template_id=squid.template_id AND type='y';
      END IF;
    END LOOP;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tabular_templates_auto_update AFTER UPDATE ON tabular_templates_auto
  FOR EACH ROW EXECUTE PROCEDURE tabular_templates_auto_update();


CREATE OR REPLACE FUNCTION tabular_templates_update() RETURNS trigger AS $$
  BEGIN
    IF (OLD.type = 'x' OR OLD.type = 'y') AND OLD.axis_type != NEW.axis_type THEN
      IF OLD.axis_type = 'auto' THEN
        DELETE FROM tabular_templates_auto WHERE tabular_template_id = OLD.tabular_template_id;
      END IF;
      IF OLD.axis_type = 'trend' THEN
        DELETE FROM tabular_templates_trend WHERE tabular_template_id = OLD.tabular_template_id;
      END IF;
    END IF;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tabular_templates_update AFTER UPDATE ON tabular_templates
  FOR EACH ROW EXECUTE PROCEDURE tabular_templates_update();


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


CREATE OR REPLACE FUNCTION tabular_constraints_create() RETURNS trigger AS $$
  DECLARE
    squid RECORD;
  BEGIN
    FOR squid IN SELECT COUNT(*) FROM tabular_constraint_logic WHERE template_id = NEW.template_id LOOP 
      IF squid.count < 1 THEN
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


CREATE OR REPLACE FUNCTION graph_documents_insert_duplicate() RETURNS trigger AS $$
  BEGIN
    DELETE FROM graph_documents WHERE saved_report_id=NEW.saved_report_id AND graph_document_id!=NEW.graph_document_id;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER graph_documents_insert_duplicate AFTER INSERT ON graph_documents
  FOR EACH ROW EXECUTE PROCEDURE graph_documents_insert_duplicate();


CREATE OR REPLACE FUNCTION table_documents_insert_duplicate() RETURNS trigger AS $$
  BEGIN
    DELETE FROM table_documents WHERE saved_report_id=NEW.saved_report_id AND table_document_id!=NEW.table_document_id;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER table_documents_insert_duplicate AFTER INSERT ON table_documents
  FOR EACH ROW EXECUTE PROCEDURE table_documents_insert_duplicate();


CREATE OR REPLACE FUNCTION csv_documents_insert_duplicate() RETURNS trigger AS $$
  BEGIN
    DELETE FROM csv_documents WHERE saved_report_id=NEW.saved_report_id AND csv_document_id!=NEW.csv_document_id;
    RETURN null;
  END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER csv_documents_insert_duplicate AFTER INSERT ON csv_documents
  FOR EACH ROW EXECUTE PROCEDURE csv_documents_insert_duplicate();

-- CREATE OR REPLACE FUNCTION determined_cast() RETURNS trigger AS $$
--   BEGIN
--     DELETE FROM csv_documents WHERE saved_report_id=NEW.saved_report_id AND csv_document_id!=NEW.csv_document_id;
--     RETURN null;
--   END;
-- $$ LANGUAGE plpgsql;