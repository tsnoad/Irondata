"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

The IronData Data Source Modules
Stores the data source information for PostgreSQL connections.
"""

# Import required modules
from pyPgSQL import PgSQL
import os, sys, string
import core
import display
import catalogue

"""
The postgres data source main class. Maintains any postgres data sources
"""
class Main():

	def __init__(self):
		self.name = 'PostgreSQL Data Source'
		self.description = 'Store and manipulate PostgreSQL data sources.'
		self.type = 'Data Source'
		self.subtype = 'Database'
		self.short_desc = 'PostgreSQL'
		core.Common().add_to_log("2", "PostgreSQL Data Source", "Loading")

	def sourceParams(self):
		params = {}
		params['dbhost'] = 'Hostname'
		params['dbname'] = 'Database Name'
		params['dbuser'] = 'Username'
		params['dbpass'] = 'Password'
		return params

	def getCount(self, object):
		cat = catalogue.CatalogueHome()
		db = cat.get_objects_by_parent(object['object_id'])
		db = db[0]
		conn = PostgreSQL(db['host'], db['database_id'], db['username'], db['password'])
		###TODO###
		count = conn.select_query("select count(*) from information_schema.tables where table_schema='public'")
		return count
		
	def createSource(self, params, object):
		#Get the tables in the new database. This is postgres specific.
		conn = PostgreSQL(params['dbhost'], params['dbname'], params['dbuser'], params['dbpass'])
		cat = catalogue.CatalogueHome()

		#Insert the database details
		dbval = {}
		dbval['object_id'] = object
		dbval['database_id'] = params['dbname']
		dbval['host'] = params['dbhost']
		dbval['username'] = params['dbuser']
		dbval['password'] = params['dbpass']
		dbval['human_name'] = params['aname']
		dbval['description'] = params['description']
		dbrow = cat.new_object(dbval)
		
		schema = conn.select_query("select * from information_schema.tables where table_schema='public' order by table_name")
		for table in schema:
			tableval = {}
			tableval['object_id'] = object
			tableval['database_id'] = params['dbname']
			tableval['table_id'] = table['table_name']
			tablerow = cat.new_object(tableval)

			column_schema = conn.select_query("select * from information_schema.columns where table_name='"+table['table_name']+"' order by column_name")
			for column in column_schema:
				colval = {}
				#Get the examples of the column data
				examples = conn.select_query("SELECT DISTINCT "+column['column_name']+" from "+table['table_name']+" limit 10")
				colval['example'] = ''
				colval['key_type'] = ''
				for example in examples:
					try:
						colval['example'] += example[column['column_name']]+","
					except UnicodeDecodeError:
						continue
					except TypeError:
						continue
				
				keys = conn.select_query("select distinct t.constraint_type from information_schema.table_constraints t, information_schema.key_column_usage k where k.constraint_name=t.constraint_name and t.table_name='"+table['table_name']+"' and k.column_name='"+column['column_name']+"'")
				for key in keys:
					try:
						colval['key_type'] += key['constraint_type']+","
					except TypeError:
						continue
				colval['object_id'] = object
				colval['database_id'] = params['dbname']
				colval['table_id'] = table['table_name']
				colval['column_id'] = column['column_name']
				colval['data_type'] = column['data_type']
				try:
					colval = cat.new_object(colval)
				except UnicodeDecodeError:
					colval['example'] = ''
					colval = cat.new_object(colval)

		#Once more. now that all columns are inserted, update the FK info.
		for table in schema:
			column_schema = conn.select_query("select * from information_schema.columns where table_name='"+table['table_name']+"' order by column_name")
			for column in column_schema:
				#Get the foreign key details
				constraints = conn.select_query("select k2.column_name, k2.table_name from information_schema.key_column_usage k1, information_schema.key_column_usage k2, information_schema.referential_constraints r where k1.constraint_name=r.constraint_name and k1.ordinal_position=k2.ordinal_position and k2.constraint_name=r.unique_constraint_name and k1.table_name='"+table['table_name']+"' and k1.column_name='"+column['column_name']+"'")
				for constraint in constraints:
					if constraint is None:
						continue;
					colval = cat.get_object(object, params['dbname'], table['table_name'], column['column_name'])
					colval['references_table'] = constraint['table_name']
					colval['references_column'] = constraint['column_name']
					colval = cat.synchronise(colval)

	def connect(self):
		pass

class PostgreSQL():
	"""
	Connect to a PostgreSQL database
	"""

	def __init__(self, dbhost, dbname, dbuser, dbpass):
		"""Build a connection string from a dictionary of parameters.

		Returns string."""
		self.db = PgSQL.connect(host=dbhost, database=dbname, user=dbuser, password=dbpass)

	def select_query(self, query):
		core.Common().add_to_log("4", "Application", query)
		cursor = self.db.cursor()
		cursor.execute(query)
		results = self._get_dict(cursor.fetchall(), cursor.description)
		core.Common().add_to_log("5", "Application", results)
		return results

	def _get_dict(self, results, description):
		"""Returns a list of ResultRow objects based upon already retrieved results
		and the query description returned from cursor.description"""

		# get the field names
		fields = {}
		for i in range(len(description)):
			fields[description[i][0]] = i

		# generate the list of ResultRow objects
		rows = []
		for result in results:
			row = {}
			for k,v in fields.iteritems():
				row[k] = result[v]
			rows.append(row)

		# return to the user
		return rows
