"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

Defines the common and core classes for the IronData modules.
"""

from pyPgSQL import PgSQL
import os, sys, string
import socket, time, datetime
import ConfigParser
import mod
import display

running = 1
config = {}
