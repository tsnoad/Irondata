"""
Irondata Data Warehouse Application.
(c) Looking Glass Solutions 2007

Setup the data warehouse backend (configuration, modules and threads). 
"""

# Importing the required modules
import os, sys, getopt, string, threading, socket, pickle, Queue, time

#sys.path.append(os.path.abspath('application'))
#sys.path.append(os.path.abspath('modules'))
from application.core import *
import interface
import mod

def main():
	# Load the configuration and threading.
	core.Common()

	# Setting up the thread pool
	clientPool = Queue.Queue(0)
	# Create as many threads as specified in the conf.ini file
	for x in xrange(int(Common().config['system']['max_threads'])):
		interface.clientThread(clientPool).start()

	# Create and Listen to the server
	attempt = 0
	Common().output("2", "Application", "Binding to socket.")
	while True:
		try:
			server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
			server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
			server.bind(('', 2727))
			server.listen(5)
			break
		except socket.error:
			attempt = attempt+1
			if attempt == Common().config['system']['timeout']:
				Common().output("1", "Application", "ERROR: Cannot bind to socket 2727.")
				return 2
			time.sleep(1)
	# Have the server serve "forever":
	while Common().running:
		Common().output("2", "Application", "New Connection.")
		clientPool.put(server.accept())

	return 2

# Call the main function.
if __name__ == "__main__":
	try:
		sys.exit(main())
	except KeyboardInterrupt:
		sys.exit()
