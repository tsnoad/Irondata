"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

The IronData Core Modules.
Stores the data source details. Also contains a basic data dictionary.
"""

# Import required modules
import os, sys, string
import core
import display
import mod

"""
The main catalogue class. Maintains the data sources
"""
class Main():

	def __init__(self):
		self.name = 'Data Source Catalogue'
		self.description = 'Store and manipulate the available data sources.'
		self.type = 'Core'
		self.subtype = None
		core.Common().add_to_log("2", "Catalogue", "Loading")

	def menu(self):
		menu = {}
		menu['catalogue'] = {}
		menu['catalogue']['name'] = 'Catalogue'
		menu['catalogue']['children'] = {}
		menu['catalogue']['children']['sources'] = ['Manage Sources', 'The data sources available to the data warehouse.']
		menu['catalogue']['children']['targets'] = ['Manage Warehouses', 'The data marts used for reporting.']
		return menu

	def process_request(self, request=None):
		xml = ''
		if request == None or request.has_key('sources'):
			#List data sources
			modules = mod.ModuleHome().get_modules_by_type('Data Source')
			objects = {}
			try:
				for module in modules:
					current_objects = Catalogue().get_objects_by_module(module['module_id'])
					objects[module['name']] = current_objects
					###??? - run getCount for each object###
				xml += CatalogueDisplay().sources(objects, modules)
			except TypeError:
				pass
		try:
			if request.has_key('consolidation'):
				pass
			if request.has_key('targets'):
				pass
			if request.has_key('deletesource'):
				CatalogueHome().delete(request['deletesource']['action'])
			if request.has_key('dd_viewrecord'):
				object = request['dd_viewrecord']['action'].split()
				record = CatalogueHome().get_object(object[0], object[1], object[2], object[3])
				xml += CatalogueDisplay().viewrecord(record, object[0], object[1], object[2])
			if request.has_key('dd_editrecord'):
				object = request['dd_editrecord']['column_id'].split()
				values = CatalogueHome().get_object(object[0], object[1], object[2], object[3])
				values['human_name'] = request['dd_editrecord']['colhname']
				values['description'] = request['dd_editrecord']['coldesc']
				values['data_type'] = request['dd_editrecord']['coltype']
				values['key_type'] = request['dd_editrecord']['colkey']
				values['example'] = request['dd_editrecord']['colex']
				CatalogueHome().synchronise(values)
			if request.has_key('datadictionary'):
				#display the data dictionary for the object
				cathome = CatalogueHome()
				obj = request['datadictionary']['action']
				object = cathome.get_object(obj)
				databases = cathome.get_objects_by_parent(obj)
				rows = {}
				#Build the data dictionary list to pass to the XML generator
				#Iterate through all databases in the object. Usually only one
				for database in databases:
					db = database['database_id']
					tables = cathome.get_objects_by_parent(obj, db)
					rows[db] = {}
					#Iterate through all tables in the database
					for table in tables:
						tab = table['table_id']
						###TODO: COUNT ROWS###
						columns = cathome.get_objects_by_parent(obj, db, tab)
						rows[db][tab] = {}
						#Iterate through all columns in the table
						try:
							for column in columns:
								col = column['column_id']
								rows[db][tab][col] = column
						except TypeError:
							pass
				xml += CatalogueDisplay().datadictionary(rows, object['type'], obj)
			if request.has_key('addnewsource'):
				if request['addnewsource'].has_key('aname'):
					#Save the new catalogue object
					moduleHome = mod.ModuleHome()
					module = moduleHome.get_module(request['addnewsource']['action'])
					cat = {}
					cat['name'] = request['addnewsource']['aname']
					cat['module_id'] = request['addnewsource']['action']
					cat['type'] = module['subtype']
					object = CatalogueHome().new_object(cat, 'object_object_id_seq')
					#Display the main source list.
					module = moduleHome.runModule(request['addnewsource']['action'])
					module.createSource(request['addnewsource'], object)

				elif request['addnewsource']['action'] != '':
					#Display the new object form
					module = mod.ModuleHome().runModule(request['addnewsource']['action'])
					params = module.sourceParams()
					name = module.name
					xml += CatalogueDisplay().newsource(params, request['addnewsource']['action'], name)
			if request.has_key('editsource'):
				cathome = CatalogueHome()
				obj = request['editsource']['action']
				object = cathome.get_object(obj)
				databases = cathome.get_objects_by_parent(obj)
				if request['editsource'].has_key('aname'):
					#Save the new catalogue object
					moduleHome = mod.ModuleHome()
					module = moduleHome.get_module(request['editsource']['action'])
					cat = {}
					cat['name'] = request['editsource']['aname']
					cat['module_id'] = request['editsource']['action']
					cat['type'] = module['subtype']
					object = CatalogueHome().new_object(cat, 'object_object_id_seq')
					#Display the main source list.
					module = moduleHome.runModule(request['editsource']['action'])
					module.createSource(request['editsource'], object)

				else:
					#Display the new object form
					module = mod.ModuleHome().runModule(object['module_id'])
					params = module.sourceParams()
					name = module.name
					xml += CatalogueDisplay().editsource(params, databases, request['editsource']['action'], name)
		except AttributeError:
			pass
		return xml


class CatalogueHome():
	def new_object(self, cat, sequence=None):
		return Catalogue().create_object(cat, sequence)

	def get_object(self, object, database=None, table=None, column=None):
		if column != None:
			return Catalogue().get_column_by_id(object, database, table, column)
		elif table != None:
			return Catalogue().get_table_by_id(object, database, table)
		elif database != None:
			return Catalogue().get_database_by_id(object, database)
		else:
			return Catalogue().get_object_by_id(object)

	def get_objects_by_parent(self, object, database=None, table=None):
		if table != None:
			return Catalogue().get_columns_by_table(object, database, table)
		elif database != None:
			return Catalogue().get_tables_by_database(object, database)
		else:
			return Catalogue().get_databases_by_object(object)

	def delete(self, id):
		return Catalogue().delete(id)

	def synchronise(self, values):
		return Catalogue().synchronise(values)

class Catalogue():
	def __init__(self):
		self.pk = 'object_id'
		self.table = 'object'

	def create_object(self, catalogue, sequence=None):
		(table, pk) = self._get_table(catalogue)
		set = ''
		key = ''
		for k,v in catalogue.iteritems():
			if v==None:
				set += "NULL,"
				key += "%s," % k
			else:
				set += "$$%s$$," % v
				key += "%s," % k
		set = set.strip(',')
		key = key.strip(',')
		query = "INSERT INTO %s (%s) VALUES (%s)" % (table, key, set)
		results = core.Connection().modify_query(query, sequence)
		return results

	def get_objects_by_module(self, module):
		query = "SELECT * FROM %s WHERE module_id=$$%s$$" % (self.table, module)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	def get_databases_by_object(self, object):
		query = "SELECT * FROM obj_database WHERE object_id=$$%s$$ ORDER BY database_id" % (object)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	def get_tables_by_database(self, object, database):
		query = "SELECT * FROM obj_table WHERE object_id=$$%s$$ and database_id=$$%s$$ ORDER BY table_id" % (object, database)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	def get_columns_by_table(self, object, database, table):
		query = "SELECT * FROM obj_column WHERE object_id=$$%s$$ and database_id=$$%s$$ and table_id=$$%s$$ ORDER BY column_id" % (object, database, table)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	def get_object_by_id(self, object):
		query = "SELECT * FROM object WHERE object_id=$$%s$$" % (object)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results[0]

	def get_database_by_id(self, object, database):
		query = "SELECT * FROM obj_database WHERE object_id=$$%s$$ and database_id=$$%s$$" % (object, database)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results[0]

	def get_table_by_id(self, object, database, table):
		query = "SELECT * FROM obj_table WHERE object_id=$$%s$$ and database_id=$$%s$$ and table_id=$$%s$$" % (object, database, table)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results[0]

	def get_column_by_id(self, object, database, table, column):
		query = "SELECT * FROM obj_column WHERE object_id=$$%s$$ and database_id=$$%s$$ and table_id=$$%s$$ and column_id=$$%s$$" % (object, database, table, column)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results[0]

	def _get_table(self, values):
		#What table do we update.
		if values.has_key('column_id'):
			table = 'obj_column'
			table = 'obj_column'
			pk = "column_id=$$%s$$ and table_id=$$%s$$ and database_id=$$%s$$ and object_id=$$%s$$ " % (values['column_id'], values['table_id'], values['database_id'], values['object_id'])
		elif values.has_key('table_id'):
			table = 'obj_table'
			pk = "table_id=$$%s$$ and database_id=$$%s$$ and object_id=$$%s$$ " % (values['table_id'], values['database_id'], values['object_id'])
		elif values.has_key('database_id'):
			table = 'obj_database'
			pk = "database_id=$$%s$$ and object_id=$$%s$$ " % (values['database_id'], values['object_id'])
		else:
			table = 'object'
			try:
				pk = "object_id=$$%s$$ " % values['object_id']
			except KeyError:
				pk = ''
		return (table, pk)

	def delete(self, id):
		query = "DELETE FROM %s WHERE %s=$$%s$$" % (self.table, self.pk, id)
		results = core.Connection().modify_query(query)
		return None


	def synchronise(self, values):
		(table, pk) = self._get_table(values)
		where = "WHERE %s" % pk
		set = "SET "
		for k,v in values.iteritems():
			if v==None:
				set = set+"%s=NULL," % k
			else:
				set = set+"%s=$$%s$$," % (k, v)
		set = set.strip(',')
		query = "UPDATE %s %s %s" % (table, set, where)
		results = core.Connection().modify_query(query)
		return None

class CatalogueDisplay():
	def sources(self, objects, types):
		disp = display.Display()
		# Count the number of sources.
		numsources = 0
		content = ''

		content += disp.para(disp.text('Each data source contains information available to the data warehouse.'))
		#Turn into closed pane. On click opens to reveal available data sources.
		subcontent = ''
		for type in types:
			subcontent += disp.para(disp.local_link(type['name'], 'catalogue', 'addnewsource', type['module_id']))
		content += disp.pane(subcontent, 'Add New Data Source', 'collapsed')
		source_num = 1
		subcontent = ''
		cellcontent = disp.table_head('#', None, 'r')
		cellcontent += disp.table_head('Data Source', None, 'r', 3)
		#The amount of data in the data warehouse from this source
		cellcontent += disp.table_head('Records', None)
		subcontent += disp.table_row(cellcontent)
		for k,v in objects.iteritems():
			#content += disp.para(disp.text(k+"'s"))
			try:
				for object in v:
					cellcontent = disp.table_cell(source_num, 'catnum-'+str(object['object_id']), 'r')
					cellcontent += disp.table_cell(k, 'cattype-'+str(object['object_id']))
					cellcontent += disp.table_cell(object['name'], 'catname-'+str(object['object_id']))
					#The icons to maniupulate the content
					links = disp.local_link('edit', 'catalogue', 'editsource', object['object_id'])
					links += disp.local_link('delete', 'catalogue', 'deletesource', object['object_id'], 'Are you sure you want to delete this data source.')
					links += disp.local_link('regenerate', 'catalogue', 'regensource', object['object_id'])
					links += disp.local_link('data dictionary', 'catalogue', 'datadictionary', object['object_id'])
					cellcontent += disp.table_cell(links, 'caticon-'+str(object['object_id']), 'r')
					#The amount of data in the data warehouse from this source
					cellcontent += disp.table_cell('...', 'catvalues-'+str(object['object_id']))
					subcontent += disp.table_row(cellcontent)
					source_num = source_num+1
			except:
				continue
		content += disp.table(subcontent, 'source_list', 'lrtb')
		xml = disp.module(content, 'catalogue', 'main', 'Data Sources')
		return xml

	def newsources(self, types):
		disp = display.Display()
		content = ''

		subcontent = disp.text('Select source type.')
		content += disp.para(subcontent)
		subcontent = ''
		for type in types:
			subcontent = disp.local_link(type['name'], 'catalogue', 'addnewsource', type['module_id'])
			content += disp.para(subcontent)
		xml = disp.module(content, 'catalogue', 'main', 'Add New Source')
		return xml

	def newsource(self, params, module_id, name):
		disp = display.Display()
		content = ''

		subcontent = disp.input('Name', 'text', 'aname')
		subcontent += disp.input('Description', 'text', 'description')
		subcontent += disp.input('', 'hidden', 'action', module_id)
		for k,v in params.iteritems():
			subcontent += disp.input(v, 'text', k)
		subcontent += disp.input('Submit', 'submit', 'submit')
		content += disp.form(subcontent, 'addnewsource')
		xml = disp.module(content, 'catalogue', 'message', name)
		return xml

	###TODO: IN PROGRESS. FIX UP PREFILL OF INPUT FIELDS
	def editsource(self, params, object, module_id, name):
		disp = display.Display()
		content = ''

		subcontent = disp.input('Name', 'text', 'aname', object['name'])
		subcontent += disp.input('Description', 'text', 'description')
		subcontent += disp.input('', 'hidden', 'action', module_id)
		for k,v in params.iteritems():
			subcontent += disp.input(v, 'text', k)
		subcontent += disp.input('Submit', 'submit', 'submit')
		content += disp.form(subcontent, 'addnewsource')
		xml = disp.module(content, 'catalogue', 'message', name)
		return xml

	def datadictionary(self, rows, type, obj):
		disp = display.Display()
		content = ''

		content += disp.para(disp.text('The data dictionary lists the objects available within the data source. ...'))
		subcontent = ''
		table_num = 1
		for k1,v1 in rows.iteritems():
			#content += disp.para(disp.text(k+"'s"))
			content += disp.para(type+' '+disp.text(k1, 'emphasis'))
			for k2,v2 in v1.iteritems():
				cellcontent = disp.table_cell(table_num, 'table_num-'+k1+k2, 'tr')
				cellcontent += disp.table_cell(k2, 'table_name-'+k1+k2, 'tr', 5)
				cellcontent += disp.table_cell('..', 'rows-'+k1+k2, 'tr')
				subcontent += disp.table_row(cellcontent)
				column_num = 1
				sub2content = ''
				for k3,v3 in v2.iteritems():
					#This is within a new row
					cell2content = disp.table_cell('', None)
					link = disp.local_link(column_num, 'catalogue', 'dd_viewrecord', obj+' '+k1+' '+k2+' '+k3)
					cell2content += disp.table_cell(link, 'column_num-'+k1+k2+k3, 'lrtb')
					cell2content += disp.table_cell(k3, 'colname-'+k1+k2+k3, 'lrtb')
					cell2content += disp.table_cell(v3['data_type'], 'type-'+k1+k2+k3, 'lrtb')
					if v3['references_table'] == None:
						cell2content += disp.table_cell(v3['key_type'].strip(', '), 'key-'+k1+k2+k3, 'lrtb')
					else:
						cell2content += disp.table_cell(v3['key_type']+' ('+v3['references_table']+')', 'key-'+k1+k2+k3, 'lrtb')
					subcontent += disp.table_row(cell2content)
					column_num += 1
				table_num += 1
		content += disp.table(subcontent, 'data_dictionary', 'lrtb')
		xml = disp.module(content, 'catalogue', 'main', 'Data Dictionary')
		return xml

	def viewrecord(self, record, object, database, table):
		disp = display.Display()

		content = disp.para('...')
		subcontent = disp.para(record['column_id'])
		subcontent += disp.input(None, 'hidden', 'column_id', object+' '+database+' '+table+' '+record['column_id'])
		subcontent += disp.input('Human Name', 'text', 'colhname', record['human_name'])
		subcontent += disp.input('Description', 'text', 'coldesc', record['description'])
		subcontent += disp.input('Data Type', 'text', 'coltype', record['data_type'])
		subcontent += disp.input('Key Type', 'text', 'colkey', record['key_type'])
		subcontent += disp.input('Example', 'text', 'colex', record['example'])
		subcontent += disp.input('Submit', 'submit', 'submit')
		content += disp.form(subcontent, 'dd_editrecord')
		xml = disp.module(content , 'catalogue', 'message', 'Data Dictionary Records')
		return xml
