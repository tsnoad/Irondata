"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

Defines the common and core classes for the IronData modules.
"""

# Import required modules
from pyPgSQL import PgSQL
import os, sys, string
import socket, time, datetime
import ConfigParser
import mod
import display

running = 1
config = {}

# Common functions available to every module
class Common():
	def __init__(self):
		config = self.parse_config("conf.ini")

	# Parses a (ini) configuration file.
	# Each value is placed into a dictionary entry
	def parse_config(self, file):
		cp = ConfigParser.ConfigParser()
		cp.read(file)
		for sec in cp.sections():
			name = string.lower(sec)
			tmp = {}
			for opt in cp.options(sec):
				tmp[string.lower(opt)] = string.strip(cp.get(sec, opt))
				config[name] = tmp
		return config

	# Add a message to the log file
	# Will only add, if the message priority is less than (or equal to)
	# the system maximum log level
	def add_to_log(self, priority, module, message):
		if priority <= config['system']['error_level']:
			messageTime = time.ctime()
			#Add to log???#
			print "[%s] [%s] %s" % (messageTime, module, message)
			if priority == 1:
				self.output(module, message)

	# Prints a message to the standard out of the console.
	def output(self, priority, module, message):
		if priority <= config['system']['error_level']:
			print "[%s]\t%s" % (module, message)

	# Returns the current time as an integer.
	def now(self):
		return int(time.time())


"""
Connect to a PostgreSQL database (the Metabase)
"""
class Connection():

	"""Build a connection string from a dictionary of parameters.
	Returns string."""
	def __init__(self):
		self.db = PgSQL.connect(host=config['database']['host'], database=config['database']['database'], user=config['database']['user'], password=config['database']['password'])

	# Runs a read (select) query 
	def select_query(self, query):
		Common().add_to_log("4", "Application", query)
		cursor = self.db.cursor()
		cursor.execute(query)
		results = self._get_dict(cursor.fetchall(), cursor.description)
		Common().add_to_log("5", "Application", results)
		return results

	# Runs a modify (update/insert/delete) query
	# Commits the transaction after it is complete.
	def modify_query(self, query, sequence=None):
		Common().add_to_log("4", "Application", query)
		cursor = self.db.cursor()
		results = cursor.execute(query)
		if sequence != None:
			cursor.execute("select currval('"+sequence+"')")
			results = cursor.fetchone()[0]
		self.db.commit()
		return results

	# Returns a list of ResultRow objects based upon 
	# already retrieved results and the query description 
	# returned from cursor.description
	def _get_dict(self, results, description):

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

"""
Processes Irondata control command such as kill and restart
"""
class Control():

	# Decides the action to take as per the command sent.
	def parse_control(self, command):
		if command == 'quit':
			self.quit()
		return ''

	# Neatly ends the currently running program.
	def quit(self):
		global running
		running = 0
		"""After setting running to 0, we need to make one more
			connection to force the socket loop to end."""
		client = socket.socket ( socket.AF_INET, socket.SOCK_STREAM )
		client.connect ( ( 'localhost', 2727 ) )

"""
Processes the core commands (menu, introduction) etc. 
These are the base level display commands that do not belong to a module
"""
class Core():

	# Decides the action to take as per the command/request sent.
	def process_core(self, request):
		# Display the menu
		if request.has_key('menu'):
			return CoreDisplay().menu(mod.ModuleHome().get_menu())
		# Display the introduction page
		if request.has_key('introduction'):
			return CoreDisplay().default()
		xml = CoreDisplay().menu(mod.ModuleHome().get_menu())
		xml += CoreDisplay().default()
		return xml

"""
Output the XML to display the Core pages
"""
class CoreDisplay():

	# Display the menu
	def menu(self, menu):
		disp = display.Display()
		#Display the menu options for all modules.
		content = ''
		for k,v in menu.iteritems():
			subcontent = ''
			for subk,subv in v['children'].iteritems():
				subcontent += disp.menuitem(subv[0], k, subk)
			content += disp.menu(subcontent, v['name'])
		xml = disp.module(content, 'core', 'menu')
		return xml

	# Display the introduction page
	def intro(self):
		disp = display.Display()
		content = disp.text('Welcome to IronData...')
		content = disp.para(content)
		xml = disp.module(content, 'core', 'main')
		return xml

	# Process as run the default module page	
	def default(self):
		status = mod.ModuleHome().module_status(config['workflow']['default'])
		Common().add_to_log("2", "Application", "Module: "+config['workflow']['default']+" Status: "+status)
		if status == "active":
			modx = __import__(config['workflow']['default'])
			xml = modx.Main().process_request()
			return xml

