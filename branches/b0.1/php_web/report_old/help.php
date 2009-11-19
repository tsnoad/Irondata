<?php

$help .= "<div id='help-01'>
<p>The Golf Data Warehouse system has been created to allow users to easily interrogate and investigate a historical record of the transactional data recorded by Engineers Australia. Data from external sources is pulled into the data warehouse on a once-daily basis, and organised into a number of <em>Data Marts</em>. Each of these Data Marts focuses on specific business entities (ie. member details, subscriptions, organisations, etc.). Using the web-based Report Generator interface, users can create reports from this data.<br/><br/></p>
<h3>Where to find additional help</h3>
<p>If you're stuck (and this help text does you no good), your first port of call should be the off-line documentation (in the form of the User's Guide and the Quick Reference). Copies should be available from the Technical Services Department. Failing this, you can speak to Technical Services directly by writing to <a href=\"mailto:&#105;&#116;&#115;&#117;&#112;&#112;&#111;&#114;&#116;&#064;&#101;&#110;&#103;&#105;&#110;&#101;&#101;&#114;&#115;&#097;&#117;&#115;&#116;&#114;&#097;&#108;&#105;&#097;&#046;&#111;&#114;&#103;&#046;&#097;&#117;\">itsupport@engineersaustralia.org.au</a>.</p><br/><br/>
</div>
<fieldset>
<legend onclick=\"Effect.toggle('help-02','slide'); return false;\">Getting Started</legend>
<div style='display:none;' id='help-02'>
<span>
<p>Firstly, <strong>remember that if you are not the administrator, you may have limited access to some features of the program.</strong></p>
<p class='helpbox'><table border=0><tr><td>Reports</td><td><img src='images/home.png'></td></tr><tr><td>Administration</td><td><img src='images/admin.png'></td></tr><tr><td>Scheduler</td><td><img src='images/calendar.png'></td></tr><tr><td>Logout</td><td><img src='images/logout.png'></td></tr></table></p>
<p>You are currently on the Home Page of the Golf Report Generator. This page can be reached at any time by clicking the 'Reports' icon on the left of the interface (at the top of the navigation menu).</p>
<p>If you have administrative privileges, you can access the admin interface by clicking the administration icon (which appears to the right of the Reports icon in the navigation menu, if it is available to you).</p>
<p>If you want to schedule a report to run you can access the report scheduler by clicking the scheduler icon (to the left of the log out button in the navigation menu).</p>
<p>Finally, you can log out of the Report Generator by clicking the 'log out' button (to the right of the Report and Administration icons in the navigation menu).</p>
<h4>First steps - Selecting a data mart</h4>
<p>The very first thing you need to do to accomplish anything in the Report interface is to select a <strong>data mart</strong>. A data mart is a collection of data in the warehouse about a specific subject or entity relevant to Engineers Australia - member details, or details of subscriptions, for example. All Report Templates and Report Outputs belong to a particular data mart. You select a Data Mart from the Data Mart drop-down at the top left of the interface. You can change data marts at any time - however, if you do so, you will lose whatever you are currently working on in the Generator, so make sure you save your changes first!</p>
</span>
</div>
</fieldset>

<fieldset>
<legend onclick=\"Effect.toggle('help-03','slide'); return false;\">Key Terms - Report Types, Templates and Outputs</legend>
<div style='display:none;' id='help-03'>
<span>
<p>The Report Generator uses a number of terms in particular ways, or with particular meanings. These key terms are described below.</p>
<h4>Data Mart</h4>
<p>A data mart is a collection of data in the warehouse, usually with a specific purpose, or based on a particular business entity (ie. member, division, organisation).</p>
<h4>Report Types</h4>
<p>A report type is a particular class of report output styling. By default, Golf supports the Trend, Table and Listing report types. Owing to its modular nature, in the future it could be extended to include Pie charts or three-dimensional graphs.</p>
<p><em>Trend</em>: The Trend Report Type is a bar or line graph (in 2 or 3 dimensional varieties). It consists of two axis of data, one of which (the Y axis) is always a time element (days, weeks, months, quarters, years). Displayed on these axis are multiple trend lines, each of which consists of several points of data creating a line or series of bars.</p>
<p><em>Table</em>: The Table Report Type is one (or more) tables of data, again with an X and Y axis. Each cell of the table represents some data correlation between values of the two axis. For example, a table can represent the breakdown of members' gender according to the organisation(s) that they belong to - the first cell might show the number of men in the Canberra Division; depending on how you organise the axis, the cell next to it might then show the number of women in the Canberra Division.</p>
<p><em>List</em>: This Report Type allows the user to view the data available in the form of listed columns. A list consists of multiple columns of data, usually taken from a single table or several related tables.</p>

<h4>Report Templates</h4>
<p>A Report Template  is the set of rules created by the user which define the data that will be represented. They could also be called a 'ruleset' or 'definition'. Reports - the actual output of the Generator - always belong to a particular Report Template.</p>

<h4>Reports</h4>
<p>A Report is the output of the Generator - a Report Template is fed into the Generator for a specific period of time and some optional user-defined variable(s), and the Report is the graphical representation of the data that is returned to the user. Reports can be saved in the system and/or downloaded as images, spreadsheets or powerpoint presentation files.</p>
</span>
</div>
</fieldset>

<fieldset>
<legend onclick=\"Effect.toggle('help-04','slide'); return false;\">Selecting a Report Template</legend>
<div style='display:none;' id='help-04'>
<span>
<p>Most actions that users will perform are targeted at a specific Report Template. Before you can do anything in the system, you must select a Report Template for the Generator to load. This is done by opening the relevant report type drop-down menu (at left) an clicking on the name of a particular Report Template. This will bring up the main Report Template control screen.</p>

<p>Once a template has been loaded, several new icons appear at the top right of the interface.<br/>
<img align='middle' border='0' title='Run' alt='Run' src='images/run.png' onmouseout='javascript:this.src=\"images/run.png\";' onmouseover='javascript:this.src=\"images/run_hover.png\";'/> - The Run icon runs the Report Template, creating a new Report.<br/>
<img align='middle' border='0' title='New' alt='New' src='images/new.png' onmouseout='javascript:this.src=\"images/new.png\";' onmouseover='javascript:this.src=\"images/new_hover.png\";'/> - The New icon creates a new Report Template of the same type.<br/>
<img align='middle' border='0' title='Edit' alt='Edit' src='images/edit.png' onmouseout='javascript:this.src=\"images/edit.png\";' onmouseover='javascript:this.src=\"images/edit_hover.png\";'/> - The Edit icon lets you edit the Report Template. You'll only see this if you have edit permission for the template.<br/>
<img align='middle' border='0' title='Delete' alt='Delete' src='images/delete.png' onmouseout='javascript:this.src=\"images/delete.png\";' onmouseover='javascript:this.src=\"images/delete_hover.png\";'/> - The Delete icon, if clicked, deletes the Report Template from the system. You may not see this if you don't have permission to delete a template.


</span>
</div>
</fieldset>

<fieldset>
<legend onclick=\"Effect.toggle('help-05','slide'); return false;\">Running a Report Template and saving Output</legend>
<div style='display:none;' id='help-05'>
<span>
<p><img align='middle' border='0' title='Run' alt='Run' src='images/run.png' onmouseout='javascript:this.src=\"images/run.png\";' onmouseover='javascript:this.src=\"images/run_hover.png\";'/> When you click on the 'run' icon for a Template, the Report Generator will take the template rules and use them to generate a Report. It's quite possible to create a template that doesn't return any relevant or meaningful data, so sometimes the run operation won't do anything, or will take so long that the Generator stops the output process. This isn't bad behaviour!</p>

<p>The output of the Generator may look something like this: <br/>
<center><image src='images/report_output.png'/></center>
<p>When you run a report, several new icons appear:<br/>
<img align='middle' border='0' title='Save' alt='Save' src='images/save.png' onmouseout='javascript:this.src=\"images/save.png\";' onmouseover='javascript:this.src=\"images/save_hover.png\";'/> - The save icon (top right) lets you save the Report Output for later viewing. This saves the report to the server, so other people can view it too. The report is saved for the current date - see below to find out how to view saved reports.<br/>
<img title='Download PDF' alt='Download PDF' src='images/pdf.png' align='middle'/> - The Download PDF button allows you to save the report output as a PDF file to your computer. <br/>
<img alt='Download Spreadsheet' title='Download Spreadsheet' src='images/spreadsheet.png'/> - The Download Spreadsheet button creates a comma-separated value output (CSV) of the report, which you can save to your computer. <br/>
<img alt='Download Graph' title='Download Graph' src='images/graph.png'/> - The Download Graph button lets you save a copy of the graph as it is displayed on your screen.<br/>
<br/>
Some of the above controls may not be present for all report types!

</p>
</span>
</div>
</fieldset>

<fieldset>
<legend onclick=\"Effect.toggle('help-06','slide'); return false;\">Viewing previously saved Report Output</legend>
<div style='display:none;' id='help-06'>
<span>
<p>Once you've saved some report output, it appears on the Report Template control screen (accessed by clicking the name of the Report Template in the menu bar on the left of the screen).<br/>
<center><img src='images/report_viewsaved.png'/></center><br/>
The most recent report is listed inside a bordered box. Older reports are listed below that. Each report output has a number of icons, which are the formats in which it can be downloaded - these correspond with their descriptions in the section above, 'Running a Report Template and Saving Output'.</p>

</span>
</div>
</fieldset>

<fieldset>
<legend onclick=\"Effect.toggle('help-07','slide'); return false;\">Creating or Editing a new Report Template</legend>
<div style='display:none;' id='help-07'>
<span>
<p>To create a new Report Template, either click the 'New <Type> Report' link on the menu on the left of the screen, or click the 'new report' button whilst on the main control screen of any pre-existing Report Template. <br/><br/>
To edit an existing Report Template, select the Template from the list and click the 'edit' button on the right hand side of the screen.<br/><br/>
When creating or editing, you will be confronted with a screen containing something like the following:<br/>
<center><img src='images/report_create.png' alt='Image of the trend Report Generator creation interface' /></center>
The details will vary from report type to report type; the table report type, for example, has pre-defined axis (X, Y, Z and Cell Values), of which all but the Z axis MUST be filled out. The key elements are the same, however - you select the table and column for values to be taken from, and the aggregate defines what to do with them - count each one, count each distinct ocurrence, take an average of the values, take the maximum of the values, or take the minimum of the values.
<br/>
In reports where the trend lines/axis/columns/etc. can be an arbitrary list, you'll have a button to add additional sets of values:<br/><br/>
<img src='images/report_addlines.png' />
</p>

<h4>Constraints</h4>
<p>Constraints define limitations on the axis or trend line you've selected. For example, if you opt to show the count of all distinct member IDs along one axis, you can then opt to show only those members below the age of 35 by defining a constraint. You have a variety of options for a constraint comparison, including: is/equals, excludes/is not, greater than, less than, contains (for string comparisons), does not contain, exists (ie. is not null) and does not exist (ie. the value is null).<br/><br/>
<img src='images/report_constraints.png' /><br/>
Some constraints also have a checkbox, which you can tick to say that for that particular value, the Generator should ask the user for the value to be checked against when the Report Template is run.<br/><br/>
Constraints can also be defined globally (ie. for all trend lines or axis) in some report types.
 </p>

<h4>Saving your Template</h4>
<p>When you are satisfied with your report, you must remember to save your changes. At the time of writing, there is no 'save as' functionality - any changes made to a Template must be saved to that same Template (this will change in the near future). At the bottom of the image below, you'll see a text field and a save button - this is present on all Report Template creation/editing screens.<br/><br/>
<img src='images/report_saveas.png' /><br/><br/>
Simply hit 'save' to save the Template. Some additional options will exist for some report types, such as the Trend Lines' time options (start date, end date and interval).
</p>

</span>
</div>
</fieldset>

<fieldset>
<legend onclick=\"Effect.toggle('help-08','slide'); return false;\">Deleting a Report Template</legend>
<div style='display:none;' id='help-08'>
<span>
<p>To delete a Report Template, select it from the menu on the left, then click the delete button on the right hand side of the interface.<br/><br/>
<img align='middle' border='0' title='Delete' alt='Delete' src='images/delete.png' onmouseout='javascript:this.src=\"images/delete.png\";' onmouseover='javascript:this.src=\"images/delete_hover.png\";'/><br/><br/>
You'll be asked to confirm before the Report Template is deleted. At the time of writing, you cannot undelete a deleted Report Template, so be careful (this is scheduled to change shortly). Deleting a Report Template will not delete the Report Outputs that have been saved; these will be left on the filesystem and must be deleted manually by a system administrator.
</p>

</span>
</div>
</fieldset>

<fieldset>
<legend onclick=\"Effect.toggle('help-09','slide'); return false;\">Scheduling a Report Template to Run Automatically</legend>
<div style='display:none;' id='help-09'>
<span>
<p>The schedule button at the top of the main navigation frame will allow you to schedule report templates to be run automatically on a regular basis.<br /><br />
<img onmouseover='javascript:this.src=\"images/calendar_hover.png\";' onmouseout='javascript:this.src=\"images/calendar.png\";' border='0' src='images/calendar.png' alt='Scheduling' title='Scheduling' /><br /><br /> 
Clicking this button will take you to a menu with a list of Report Templates which can be scheduled. Simply find the template you would like to run regularly and select a period from the drop-down box, giving specific information in the specifics section if needed. Reports will be saved, and can be accessed by navigating to the Report Template in the main navigation bar.
</p>
</span>
</div>
</fieldset>
";

?> 
