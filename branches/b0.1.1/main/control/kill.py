"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

Send the kill control to the IronData backend.
"""

import pickle
import socket
import threading, time

# Here's our thread:
class ConnectionControl ( threading.Thread ):

	def run ( self ):
		# Connect to the server:
		client = socket.socket ( socket.AF_INET, socket.SOCK_STREAM )
		client.connect ( ( 'localhost', 2727 ) )
		print "Sending: Quit"
		# Create kill command
		message = """<?xml version="1.0" encoding="iso-8859-1"?>
<request>
<module name="core">
<command name='element_login'>
<element name='username'>admin</element>
<element name='password'>admin</element>
</command>
</module>
<control>quit</control>
</request>
"""
		# Send the message:
		client.send ( message )
		message = client.recv(8192)
		print message
		# Close the connection
		client.close()

# Begin:
ConnectionControl().start()
