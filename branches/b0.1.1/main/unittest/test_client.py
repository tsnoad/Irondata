"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

Test the IronData backend.
"""

# Import required modules
import pickle
import socket
import threading, time

# Here's our thread:
class ConnectionThread ( threading.Thread ):

	def run ( self ):

		# Connect to the server:
		client = socket.socket ( socket.AF_INET, socket.SOCK_STREAM )
		client.connect ( ( 'localhost', 2727 ) )
		print "Sending: Request"
		message = '<?xml version="1.0" encoding="iso-8859-1"?>\
<request>\
	<module name="core">\
		<command name="menu" />\
		<command name="introducton" />\
	</module>\
</request>'

		# Send some messages:
		client.send ( message )
		message = client.recv(8192)
		print message

		# Close the connection
		client.close()

# Let's spawn a few threads:
for x in xrange ( 3 ):
	ConnectionThread().start()
