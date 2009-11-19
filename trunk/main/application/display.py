"""
Irondata Data Warehouse Application
(c) Looking Glass Solutions 2007

Creates the XML for all the interface elements.
"""

"""
Functions to output correct XML for each interface element.
"""
class Display():
	# Wrap the output in valid XML tags
	def wrap_content(self, content):
		xml = """<?xml version="1.0" encoding="iso-8859-1"?>
<response>
%s
</response>
""" % content
		return xml

	# Encapsulate each view/display by the module that created it.
	def module(self, content, name, region, title=''):
		#Can this be done automatically?
		id = name+"_"+region;
		xml = "<module name='%s' region='%s' id='%s' title='%s'>\n" % (name, region, id, title)
		xml += content
		xml += "</module>\n"
		return xml

	# Output the menu interface
	def menu(self, content, name):
		xml = "<menu name='%s'>\n" % name
		xml += content
		xml += "</menu>\n"
		return xml

	# Output the menu items. These must be wrapped in the menu object
	def menuitem(self, content, module, name):
		xml = "<menuitem module='%s' name='%s'>%s</menuitem>\n" % (module, name, content)
		return xml

	# Output a paragraph
	def para(self, content):
		xml = "<para>%s</para>\n" % content
		return xml

	# Output a block of text
	def text(self, content, style=''):
		xml = "<text style='%s'>%s</text>\n" % (style, content)
		return xml

	# Output a heading
	def header(self, content):
		xml = "<header>%s</header>\n" % content
		return xml

	# Output a predefined image
	def preimage(self, content):
		xml = "<image type='%s' />\n" % content
		return xml

	# Output a form wrapper. 
	def form(self, content, name):
		xml = "<form name='%s'>\n" % name
		xml += content
		xml += "</form>\n"
		return xml

	# Output an input box. Should be wrapped in a form
	def input(self, content, type, name, value=''):
		xml = "<input type='%s' id='%s' label='%s' value='%s' />\n" % (type, name, content, value)
		return xml

	# Output a link to an external site.
	def ext_link(self, content, href):
		xml = "<link href='%s'>%s</link>\n" % (href, content)
		return xml

	# Output a link to a module within the current site.
	def local_link(self, content, module, view, action='', confirm=None):
		if confirm == None:
			xml = "<link module='%s' view='%s' action='%s' >%s</link>\n" % (module, view, action, content)
		else:
			xml = "<link module='%s' view='%s' action='%s' confirm='%s'>%s</link>\n" % (module, view, action, confirm, content)
		return xml

	# Output a line break
	def br(self):
		xml = "<break />\n"
		return xml

	# Output a table wrapper	
	def table(self, content, id=None, border=None):
		if id!=None:
			id = "id='%s'" % id
		else:
			id = ''
			
		#calculate the border for the cells.
		bs = ''
		try:
			if 'l' in border:
				bs += " border-left='1'"
			if 'r' in border:
				bs += " border-right='1'"
			if 't' in border:
				bs += " border-top='1'"
			if 'b' in border:
				bs += " border-bottom='1'"
		except TypeError:
			pass
		xml = "<table %s %s>\n%s\n</table>\n" % (id, bs, content)
		return xml

	# Output a table row. Must be within a table
	def table_row(self, content):
		xml = "<trow>%s</trow>\n" % content
		return xml

	# Output a table cell. Must be within a table row
	def table_cell(self, content, id=None, border=None, span=None):
		if id!=None:
			id = "id='%s'" % id
		else:
			id = ''
		
		if span!=None:
			span = "span='%s'" % span
		else:
			span = ''
		
		if content==None:
			content = ''
		
		#calculate the border for the cells.
		bs = ''
		try:
			if 'l' in border:
				bs += " border-left='1'"
			if 'r' in border:
				bs += " border-right='1'"
			if 't' in border:
				bs += " border-top='1'"
			if 'b' in border:
				bs += " border-bottom='1'"
		except TypeError:
			pass
		xml = "<tcell %s %s %s>%s</tcell>" % (span, id, bs, content)
		return xml

	# Output a table header. Must be within a table row
	def table_head(self, content, id=None, border=None, span=None):
		if id!=None:
			id = "id='%s'" % id
		else:
			id = ''
		
		if span!=None:
			span = "span='%s'" % span
		else:
			span = ''
		
		if content==None:
			content = ''
		
		#calculate the border for the cells.
		bs = ''
		try:
			if 'l' in border:
				bs += " border-left='1'"
			if 'r' in border:
				bs += " border-right='1'"
			if 't' in border:
				bs += " border-top='1'"
			if 'b' in border:
				bs += " border-bottom='1'"
		except TypeError:
			pass
		xml = "<thead %s %s %s>%s</thead>" % (span, id, bs, content)
		return xml

	# Output a collapsible pane
	def pane(self, content, name, status=''):
		xml = "<pane name='%s' status='%s'>\n%s\n</pane>\n" % (name, status, content)
		return xml
