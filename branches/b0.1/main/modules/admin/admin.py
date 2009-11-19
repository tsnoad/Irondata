"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

The IronData Core Modules.
Controls the interface, help and configuration.
"""

# Import required modules
import os, sys, string
import core
import users
import mod
import display

"""
The administration main class. Maintains all admin functions such as modules, users etc.
"""
class Main():

	def __init__(self):
		self.name = 'IronData Administration Functionality'
		self.description = 'This is the Administration module. Do not remove this module.'
		self.type = 'Core'
		self.subtype = None
		core.Common().add_to_log("2", "Admin", "Loading")

	def menu(self):
		menu = {}
		menu['admin'] = {}
		menu['admin']['name'] = 'Administration'
		menu['admin']['children'] = {}
		menu['admin']['children']['modules'] = ['Manage Modules', 'A list of all active and available modules.']
		menu['admin']['children']['users'] = ['Manage Users', 'The users available to this IronData system.']
		menu['admin']['children']['help'] = ['Help', 'Administration Help.']
		return menu

	def process_request(self, request):
		xml = ''
		if request.has_key('modules'):
			modules = mod.ModuleHome().get_all_modules()
			installed = mod.ModuleHome().get_available_modules()
			xml += AdminDisplay().modules(modules, installed)
		if request.has_key('view_module'):
			module_name = request['view_module']['action']
			module = mod.ModuleHome().get_module(module_name)
			xml += AdminDisplay().view_module(module)
		if request.has_key('install_module'):
			module_name = request['install_module']['action']
			module = mod.ModuleHome().get_module(module_name)
			module['status'] = 'active'
			mod.ModuleHome().new_module(module)
			xml += AdminDisplay().view_module(module)
		if request.has_key('users'):
			userlist = users.UserHome().get_users()
			xml += AdminDisplay().users(userlist)
		if request.has_key('view_module'):
			module_name = request['view_module']['action']
			module = mod.ModuleHome().get_module(module_name)
			xml += AdminDisplay().view_module(module)
		return xml

class AdminDisplay():
	def modules(self, modules, installed):
		disp = display.Display()
		content = ''
		subcontent = ''
		content += disp.header('Installed Modules')
		complete = {}
		for module in installed:
			content += disp.para(disp.local_link(module['name'], 'admin', 'view_module', module['module_id']))
			complete[module['name']] = module['name']

		subcontent = ''
		content += disp.header('Available Modules')
		for module in modules:
			if module['name'] in complete:
				continue
			content += disp.para(disp.local_link(module['name'], 'admin', 'view_module', module['module_id']))
		xml = disp.module(content, 'admin', 'main', 'Modules')
		return xml

	def view_module(self, module):
		disp = display.Display()
		content = ''
		subcontent = disp.text('Name: ', 'bold')
		subcontent += disp.text(module['name'])
		content += disp.para(subcontent)
		subcontent = disp.text('Description: ', 'bold')
		subcontent += disp.text(module['description'])
		content += disp.para(subcontent)
		subcontent = disp.text('Status: ', 'bold')
		subcontent += disp.text(module['status'])
		content += disp.para(subcontent)
		subcontent = disp.text('Subtype: ', 'bold')
		subcontent += disp.text(module['subtype'])
		content += disp.para(subcontent)
		subcontent = disp.text('Type: ', 'bold')
		subcontent += disp.text(module['type'])
		content += disp.para(subcontent)
		subcontent = disp.text('Functions', 'bold')
		content += disp.para(subcontent)
		subcontent = ''
		if module['status'] == 'active':
			if module['type'] == 'Core':
				subcontent += disp.text('You cannot uninstall Core modules')
			else:
				subcontent += disp.text('Uninstall...')
		else:
			subcontent += disp.local_link('Install', 'admin', 'install_module', module['module_id'])
		content += disp.para(subcontent)
		xml = disp.module(content, 'admin', 'main', 'Module: '+module['name'])
		return xml

	def users(self, users):
		disp = display.Display()
		content = ''
		subcontent = ''
		content += disp.header('All Users')
		content += disp.para(disp.local_link('Create new user', 'admin', 'edit_user'))
		for user in users:
			content += disp.para(disp.local_link(user['person_id'], 'admin', 'edit_user', user['person_id']))

		xml = disp.module(content, 'admin', 'main', 'Users')
		return xml

	def edit_user(self, user=None):
		disp = display.Display()
		content = ''
		subcontent = ''
		content += disp.header('All Users')
		content += disp.para(disp.local_link('Create new user', 'admin', 'edit_user'))
		for user in users:
			content += disp.para(disp.local_link(user['person_id'], 'admin', 'edit_user', user['person_id']))

		xml = disp.module(content, 'admin', 'message', 'Users')
		return xml
