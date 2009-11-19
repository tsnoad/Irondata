"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

User details, access control and authentication.
"""

# Import required modules
import os, sys, string
import md5, random, time, datetime
import core
import display

currentUser = None

"""
Details on all users and access control
"""
class UserHome():
	# Check if the current user is authenticated and has a valid session. 
	def check_session(self, doc):
		global currentUser
		try:
			# Is this an authentication attempt
			username = doc['module']['core']['element_login']['username']
			password = doc['module']['core']['element_login']['password']
			return self._authenticate_user(username, password)
		except KeyError:
			# Not an authentication attempt
			
			if doc['session'] == '':
				# No session information in XML. Therefore not logged in.
				currentUser = None
				return UserDisplay().login()
			else:
				# Check if session information is valid
				user = User().get_user_by_session(doc['session'])
				# this will be None or a user record
				currentUser=user
				if user == None:
					# Not valid
					xml = UserDisplay().message('Session timed out.')
					xml += UserDisplay().login()
					return xml
				else:
					#Valid
					user = self._set_session(user)
		return ''

	# Return a list of all users in the system
	def get_users(self):
		return User().get_users()

	# Run through the authentication processes
	def _authenticate_user(self, username, password):
		global currentUser
		user = User().get_user(username)
		# Invalid username
		if user == None:
			currentUser=None
			return UserDisplay().message('Incorrect Username or Password')

		md5pass = md5.new(password).hexdigest()
		if  md5pass == user['password']:
			# Correct password
			user = self._set_session(user)
			User().synchronise(user)
			currentUser=user
			xml = UserDisplay().session(user['session'])
			#xml += core.CoreDisplay().default()
			return xml
		else:
			# Incorrect password
			currentUser=None
			return UserDisplay().message('Incorrect Username or Password')

	# Set the session information for the user.
	def _set_session(self, user):
		user['session'] = random.randint(1, 1000000000)
		user['session_time'] = datetime.datetime.fromtimestamp(core.Common().now()).ctime()
		return user

"""
Functions to manage user account in Irondata
"""
class User():
	def __init__(self):
		self.pk = 'person_id'
		self.table = 'person'

	# Retreive a user record from the database
	def get_user(self, user):
		query = "SELECT * FROM %s WHERE %s='%s'" % (self.table, self.pk, user)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results[0]

	# Retreive a user record from the database by a given session id
	def get_user_by_session(self, session):
		# current time - the timeout (in minutes)
		timeout = time.ctime(core.Common().now()-(int(core.config['user']['timeout'])*60))
		query = "SELECT * FROM %s WHERE session='%s' AND session_time > '%s'" % (self.table, session, timeout)
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results[0]

	# Retreive all user records from the database
	def get_users(self):
		query = "SELECT * FROM %s " % self.table
		results = core.Connection().select_query(query)
		if results == []:
			return None
		else:
			return results

	# Sychronise a user record with the database
	def synchronise(self, user):
		where = "WHERE %s='%s'" % (self.pk, user[self.pk])
		set = "SET "
		for k,v in user.iteritems():
			if v==None:
				set = set+"%s=NULL," % k
			else:
				set = set+"%s='%s'," % (k, v)
		set = set.strip(',')
		query = "UPDATE %s %s %s" % (self.table, set, where)
		results = core.Connection().modify_query(query)
		return None

"""
Functions to display user information in the admin screens
"""
class UserDisplay():
	
	# Output the session information
	def session(self, session):
		disp = display.Display()
		content = "<session>%s</session>" % session
		xml = disp.module(content, 'core', 'control')
		return xml

	# Output a message, such as session has expired
	def message(self, message):
		disp = display.Display()
		content = disp.para(disp.text(message))
		xml = disp.module(content, 'core', 'message')
		return xml

	# Output the login page
	def login(self):
		disp = display.Display()
		xml = disp.module('', 'core', 'menu')
		content = ''
		#Welcome information
		content += disp.preimage('welcome')
		subcontent = disp.text('This instance of IronData manages data belonging to ')
		subcontent += disp.text(core.config['company']['name']+'.', 'bold')
		content += disp.para(subcontent)
		content += disp.para(disp.text('To view and manage this data please log into the system below.'))
		#The login form
		subcontent = disp.input('Username', 'text', 'username')
		subcontent += disp.input('Password', 'password', 'password')
		subcontent += disp.input('Login', 'submit', 'submit')
		content += disp.form(subcontent, 'element_login')
		xml += disp.module(content, 'core', 'main')

		#The footer information
		content = ''
		subcontent = disp.text('IronData', 'bold')
		subcontent += disp.text(' is an open source data warehouse system licensed under the ')
		subcontent += disp.ext_link('GNU GPL', 'http://www.gnu.org/copyleft/gpl.html')
		subcontent += disp.text('. All logos and trademarks relating to IronData are the property of ')
		subcontent += disp.ext_link('Looking Glass Solutions', 'http://www.lgsolutions.com.au')
		subcontent += disp.text('.')
		content += disp.para(subcontent)
		subcontent = disp.text('For more information on the system, please refer below.')
		subcontent += disp.br()
		subcontent += disp.ext_link('About '+core.config['company']['name'], core.config['company']['url'])
		subcontent += disp.ext_link('About IronData', 'http://www.irondata.org')
		subcontent += disp.ext_link('Licensing', 'http://www.gnu.org/copyleft/gpl.html')
		subcontent += disp.ext_link('About Looking Glass Solutions', 'http://www.lgsolutions.com.au')
		content += disp.para(subcontent)
		xml += disp.module(content, 'core', 'footer')
		return xml
