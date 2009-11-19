// trend.js
// Javascript for the trend module
// Assume we have a 'data' attribute...
// Server sends trendlist, tablelist and columnlist
//TODO: implement generate_options
//TODO: main render
//TODO: time render
//TODO: other controls
//TODO: server-side implementation
//TODO: more refactoring and object-orientification

function trendEditManager(name) {
	this._name = name;
}

trendEditManager.prototype._name;
trendEditManager.prototype._trendlines;
trendEditManager.prototype._selectedline;

trendEditManager.prototype.parse = function (data) {
	var i = 0;
	data = eval(data);
	this.data.tables = data.tables;
	this.data.aggregates = data.aggregates;
	for(i = 0; i <= data.trendlines.length; i++) {
		this.data.trendlines[i] = data.trendlines[i];
	}
}

// Render the trend line controls
trendEditManager.prototype.renderTrends = function () {
	var retVal = '<div id="trend_lines" >';
	retVal += "<div id='left_arrow' class='left_arrow' onclick='javascript:moveLeft();' style='position:absolute; top: -5px; left: 55px; height: 25px; width: 25px'></div>";
	retVal += "<div id='right_arrow' class='right_arrow' onclick='javascript:moveRight();' style='position:absolute; top: -5px; left: 180px; height: 25px; width: 25px'></div>";
	var counter = 0;
	var invisible = "";
	for(counter = 0; counter <= this.data.trendlines.length; counter++) {
		retVal += "<div id='trendlines__"+counter+"' class='trendlines'"+invisible+">";
		retVal += "<p>#"+(counter+1)+" <input id='title__"+counter+"' type='text' name='title__"+counter+"' value='"+this.data.trendlines[counter].title+"' class='title'><br/></p>";
		retVal += "<p><select id='table__"+counter+"' name='table__"+counter+"' onChange='javascript:changeOptions(&quot;table__"+counter+"&quot;,&quot;columns__"+counter+"&quot;);' >";
		retVal += this.generate_options(this.data.tables, this.data.trendline[counter].table)+"</select><br/></p>";
		retVal += "<p><select id='columns__"+counter+"' name='columns__"+counter+"'>"+this.generate_options(this.data.trendline[counter].column_opts, this.data.trendline[counter].column)+"</select><br/></p>";
		retVal += "<p><select id='aggregate__"+counter+"' name='aggregate__"+counter+"'>"+this.generate_options(this.data.aggregates, this.data.trendline[counter].aggregate)+"</select><br/></p>";
		retVal += "<p><input type='button' value='Constraints' id='constraints' onclick='makeVisible(\"constraints__"+counter+"\"); underClip(\"clipper__"+counter+"\", \"constraints__"+counter+"\");'></p>";
		retVal += "<iframe style='position: absolute; display: none; z-index: 1;' frameBorder=\"0\" scrolling=\"no\" name='clipper__"+counter+"' id='clipper__"+counter+"' width='0' height='0'></iframe><div class='constraints' id='constraints__"+counter+"'><strong>Constraints</strong> <input type=button value='Done' id='constraints' onclick='makeInvisible(\"constraints__"+counter+"\"); makeInvisible(\"clipper__"+counter+"\");'><br/><br/>";
		// constraints forloop
		retVal += "</div>"; // End constraints div
		
		if(invisible == "") {
			invisible = 'style: display: none';
		}
		retVal += "<input type='button' onclick='makeInvisible(this.parentNode.id); cloneElement(\"trend_lines\", \"trendlines\", \"trend\", null)' name='additional_trend' value='Add an additional trend line' style='zIndex:0' /><br/>";
		if(counter != 0) {
			retVal += "<input type='button' onclick='removeTrend(\"trend_lines\", this.parentNode.id)' name='remove_trend' value='Remove this trend line' /><br/>";
		}
	
		retVal += "</div>"; // End trend div
	}
	retVal += "</div>"; // End trend_lines div
}
