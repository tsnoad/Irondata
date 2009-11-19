ETL TOOL DOCUMENTATION
updated 21/9/06
##########################################

Prerequisites:
php-pgsql
freetds
lots of perl modules

In /usr/local/etc/freetds.conf setup the server as:
[JDBC]

        host = 192.168.25.15
        port = 8006
        tds version = 5.0

to get perl to point to tds modules:
in /etc/ld.so.conf, add: /usr/local/lib

transforms.xml INFO:

<input> - defines a data set to input and output. It must contain:
    <select>'query'</select> - SQL select query to run
    <insert>'query'</insert> - beginning of SQL insert query to run (ie. INSERT INTO custs)
    <defaulttable>'name'</defaulttable> - default table name to insert data into
    <transform> - defines a tranform on the data (multiple transforms must exist, ie > 1)

accepted transform types are:
    join
        desc: joins two (or more) cols to create a new one. Leaves the existing cols intact (therefore you will have to add a delete transform for them later).
        required xml: 
		"out - col name to create"
		"in - comma seperated list of cols to join"
		"action - deliminator to use"
    split
        desc: splits a col in two cols based on a regex
        required xml: "col_in - name of col to split", "col_out - comma seperated list of output cols", "action - pattern (in regex) to split against"
    regex
        desc: regular expression to be performed on col.
        required xml: 
		"in - name of col to use"
		"out - name of column to output as"
		"action - regex to run"

	example:
		<select>select * from fruits</select>
                <transform>
                        <type>regex</type>
                        <in>crispness</in>
                        <out>crispness</out>
                        <action>s/low/squishy/i</action>
                </transform>

    delete
        desc: deletes a column or columns
        required xml: 
		"in - col to delete (or comma seperated list of cols to delete)"

	example:
		<select>select * from fruits</select>
                <transform>
                        <type>delete</type>
                        <in>crispness</in>
                </transform>

    drop
	desc: drops an entire row if your regular expression matches
	required xml: 
		"in - col to check"
		"action - regex used to validate"

	example:
		<transname>CrispnessDrop</transname>
                <select>select * from fruits</select>
                <transform>
                        <type>drop</type>
                        <in>crispness</in>
                        <action>/low/i</action>
                </transform>

    rename
	desc: changes the column name, leaving the value un-touched
	required xml: 
		"in - col to change"
		"out - name of col to rename to"
    add
	desc: adds an arbitrary column and value
	required xml: 
		"col_out - col name to add"
		"value - value the column should take"

	example:
		<transform>
                        <type>add</type>
                        <out>badger</out>
                        <value>badger</value>
                </transform>



NOTE: any "drop"s should be listed first in the transforms list

a <table> tag may also be defined for each transform, this will set which table the table will be placed into 
	- it should only exist if the data is being placed into a different table other than <defaulttable>
	- if this does not exist, <defaulttable> will be used.


 
Any column that does not a have a transform specified within the transforms file will be left alone and added to the output DB without any change.

Using hashes for retrieving the selected data which is considerably slower than using arrays. The ideal would be to use arrays and then bind the column names to the array, see -> http://www.stardata.it/articoli_en/dbi_recipes_articoli_en.html#about_fetching_records
however grabbing the column names dynamically is a bit of an issue.
