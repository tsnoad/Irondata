"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

Listen for incoming connections, parse XML and pass control to modules.
"""

# Import required modules
import os, sys, string, threading, socket, pickle, Queue
from xml.parsers import expat
import users
import core
import mod
import display

"""
Create a new thread for each new client connection
"""
class clientThread(threading.Thread):
	def __init__(self, clientPool):
		self.clientPool = clientPool
		threading.Thread.__init__ (self)

	# Run the threads from the client pool
	def run(self):
		# Always run
		while True:
			# Get the next thread
			client = self.clientPool.get()
			if client != None:
				message = client[0].recv(8192)
				response = parseXML().request(message)
				#Only send if there is a response
				if response:
					sent = client[0].send(response)
				client[0].close()

"""
This class parses the XML DOM and turns it into a python dictionary
"""
class parseXML():
	def __init__(self):
		self.doc = {}
		self.session = ''
		self.response = ''

	# Begin processing the request. This will also check for 
	# user permission.
	def request(self, message):
		# This is for testing purposes
		core.Common().add_to_log("5", "Application", message)
		# Parse the XML in a dictionary
		self.doc = requestHandler().parse(message)
		core.Common().add_to_log("5", "Application", self.doc)

		# Begin user checks. Any response assumes a problem.
		# And no other requests will be run.
		self.response = users.UserHome().check_session(self.doc)
		core.Common().add_to_log("5", "User", users.currentUser)
		# Only if the user is authenticated
		if (users.currentUser != None):
			"""Parse any control parameters"""
			self.response += core.Control().parse_control(self.doc.get('control', ''))
			self.response += self._process_modules()
		# Wrap the content in the correct XML headers and footers
		self.response = display.Display().wrap_content(self.response)
		core.Common().add_to_log("5", "Application", self.response)
		return self.response

	# Iterate through the XML requests as pass to the correct modules
	def _process_modules(self):
		response = ''
		for k,v in self.doc['module'].iteritems():
			if k == 'core':
				response += core.Core().process_core(v)
			else:
				status = mod.ModuleHome().module_status(k)
				core.Common().add_to_log("2", "Application", "Module: "+k+" Status: "+status)
				if status == "active":
					modx = __import__(k)
					response += modx.Main().process_request(v)

		return response

"""
The XML parser
"""
class requestHandler(object):
	def __init__(self):
		self.level = 0
		self.doc = {"module":{}, "session":'', "control":''}
		self.root = None
		self.module = ''
		self.command = ''
		self.element = ''

	def _start_element(self, name, attrs):
		self.root = name
		self.doc.setdefault('module', {})
		if name == 'session':
			pass
		elif name == 'control':
			pass
		elif name == 'request':
			pass
		elif name == 'module':
			self.module = attrs.get('name', '')
			self.doc['module'][self.module] = {}
		elif name == 'command':
			self.command = attrs.get('name', '')
			self.doc['module'][self.module][self.command] = {}
		elif name == 'element':
			self.element = attrs.get('name', '')
			self.doc['module'][self.module][self.command][self.element] = ''

	def _end_element(self, name):
		pass

	def _character_data(self, ch):
		if self.root == 'session':
			self.doc['session'] = self.doc['session'] + ch.strip()
		elif self.root == 'control':
			self.doc['control'] = self.doc['control'] + ch.strip()
		elif self.root == 'element':
			self.doc['module'][self.module][self.command][self.element] = self.doc['module'][self.module][self.command][self.element] + ch.strip()


	def parse(self, object):
		parser = expat.ParserCreate()
		parser.StartElementHandler = self._start_element
		parser.EndElementHandler = self._end_element
		parser.CharacterDataHandler = self._character_data
		parserStatus = parser.Parse(object)
		return self.doc


