"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

Configure the available modules for IronData.
"""

# Import required modules
import os, sys, string
import core

"""
Functions to control and use the module system and module registry
"""
class ModuleHome():
	def __init__(self):
		modules = Module().get_modules_by_status('active')
		for module in modules:
			path = "modules/%s" % module['module_id']
			sys.path.append(os.path.abspath(path))

	# Returns whether a module is installed, or unavailble. Modules that do 
	# not exist are considered unavailable.
	def module_status(self, modulename):
		"""The core 'module' is always available."""
		if modulename == 'core':
			return 'active'
		module = Module().get_module(modulename)
		path = "modules/%s" % modulename
		if module == None:
			return 'unavailable'
		elif not os.path.exists(path):
			return 'unavailable'
		else:
			return module['status']

	# Get the core parameters of any given module
	def get_module(self, module_name):
		module = Module().get_module(module_name)
		if module == None:
			path = "modules/%s" % module_name
			sys.path.append(os.path.abspath(path))
			modx = __import__(module_name)
			modmain = modx.Main()
			module = {}
			module['name'] = modmain.name
			module['description'] = modmain.description
			module['type'] = modmain.type
			module['subtype'] = modmain.subtype
			module['module_id'] = module_name
			module['status'] = None
		return module

	# Get the core parameters of all modules.
	def get_all_modules(self):
		modules = os.listdir('modules/')
		truemodules = []
		for module in modules:
			path = "modules/%s" % module
			sys.path.append(os.path.abspath(path))
			file = 'modules/'+module+'/'+module+'.py'
			if os.access(file, os.F_OK):
				modx = __import__(module)
				modmain = modx.Main()
				module_array = {}
				module_array['name'] = modmain.name
				module_array['description'] = modmain.description
				module_array['type'] = modmain.type
				module_array['subtype'] = modmain.subtype
				module_array['module_id'] = module
				truemodules.append(module_array)

		return truemodules

	# Run the main class of a module. All modules must have a main class.
	def runModule(self, module_name):
		module = Module().get_module(module_name)
		if module == None:
			return None
		else:
			modx = __import__(module_name)
			modmain = modx.Main()
			return modmain
	
	#???#
	def runModuleFunction(self, module_name, function_name):
		module = Module().get_module(module_name)
		if module == None:
			return None
		else:
			modx = __import__(module_name)
			###???###
			modmain = modx.Main()
			return modmain
	
	# Return a list of all modules, that are available
	def get_available_modules(self):
		return Module().get_modules_by_status('active')

	# Return a list of all modules by type
	def get_modules_by_type(self, type):
		return Module().get_modules_by_type(type)

	# Create a new module record
	def new_module(self, module):
		return Module().create(module)

	# Get the menu options for a module
	def get_menu(self):
		modules = Module().get_modules_by_status('active')
		menu = {}
		for module in modules:
			modx = __import__(module['module_id'])
			try:
				menu.update(modx.Main().menu())
			except AttributeError:
				continue;
		return menu

"""
Functions to manage module record
"""
class Module():
	def __init__(self):
		self.pk = 'module_id'
		self.table = 'module'

	# Retrieve a module record
	def get_module(self, module):
		query = "SELECT * FROM %s WHERE %s='%s'" % (self.table, self.pk, module)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results[0]

	# Retrieve a module record by its status
	def get_modules_by_status(self, status):
		query = "SELECT * FROM %s WHERE status='%s'" % (self.table, status)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	# Retrieve a module record by its type
	def get_modules_by_type(self, type):
		query = "SELECT * FROM %s WHERE type='%s'" % (self.table, type)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	# Synchronise a module record
	def synchronise(self, module):
		where = "WHERE %s='%s'" % (self.pk, module[self.pk])
		set = "SET "
		for k,v in module.iteritems():
			if v==None:
				set += "%s=NULL," % k
			else:
				set += "%s='%s'," % (k, v)
		set = set.strip(',')
		query = "UPDATE %s %s %s" % (self.table, set, where)
		results = core.Connection().modify_query(query)
		return None

	# Create a new module record
	def create(self, module):
		set = ''
		key = ''
		for k,v in module.iteritems():
			if v==None:
				set += "NULL,"
				key += "%s," % k
			else:
				set += "'%s'," % v
				key += "%s," % k
		set = set.strip(',')
		key = key.strip(',')
		query = "INSERT INTO %s (%s) VALUES (%s)" % (self.table, key, set)
		results = core.Connection().modify_query(query)
		return None
