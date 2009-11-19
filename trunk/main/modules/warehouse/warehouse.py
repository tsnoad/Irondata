"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

The IronData Core Modules.
Create a data warehouse database
"""

import os, sys, string
import core
import display
import mod

"""
This class processes and loads the catalogue functions
"""
class Main():

	def __init__(self):
		self.name = 'Create Data Warehouse'
		self.description = 'Design and create a new data warehouse or data mart.'
		self.type = 'Core'
		self.subtype = None
		core.Common().output("2", "Warehouse", "Loading")

	def menu(self):
		menu = {}
		menu['warehouse'] = {}
		menu['warehouse']['name'] = 'Warehouse'
		menu['warehouse']['children'] = {}
		menu['warehouse']['children']['targets'] = ['Manage Warehouses', 'Create or manage the data warehouses on the system.']
		return menu

	def process_request(self, request=None):
		if request==None:
			request = {}
			request['sources'] = 1
		xml = ''
		if request.has_key('targets'):
			#List data sources
			xml += self.manage_targets(request)
		return xml

	def manage_targets(self, request):
		objects = self.get_warehouses()
		for module in modules:
			objects[module['name']] = Catalogue().get_objects_by_module(module['module_id'])
		xml += CatalogueDisplay().sources(objects, modules)
		return xml

	def new_warehouse(self):
		pass

	def get_warehouse(self, id):
		pass

	def get_warehouses(self):
		pass

	def delete(self, id):
		return Catalogue().delete(id)

	def synchronise(self, values):
		return Warehouse().synchronise(values)

class Warehouse():
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

	def get_warehouses(self):
		query = "SELECT * FROM %s WHERE module_id='warehouse' ORDER BY name" % (self.table)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	def get_warehouse_by_id(self, object):
		query = "SELECT * FROM %s WHERE object_id=$$%s$$" % (self.table, object)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	def delete(self, id):
		query = "DELETE FROM %s WHERE %s=$$%s$$" % (self.table, self.pk, id)
		results = core.Connection().modify_query(query)
		return None

	def synchronise(self, values):
		where = "WHERE %s=$$%s$$" % (self.table, self.pk)
		set = "SET "
		for k,v in values.iteritems():
			if v==None:
				set = set+"%s=NULL," % k
			else:
				set = set+"%s=$$%s$$," % (k, v)
		set = set.strip(',')
		query = "UPDATE %s %s %s" % (self.table, set, where)
		results = core.Connection().modify_query(query)
		return None

class WarehouseDisplay():
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
