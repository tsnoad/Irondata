<?php
    $conf = parse_ini_file("../../conf.ini", true);
    session_start();
?>
 
var trend_number = 0;
var constraint_number = 0;
 
 /*
  * This brings up a confirm box with a given message. If it is accepted then it will 
  * proceed onto the given URL, otherwise it abort the click
  */
function userCheck(url,msg) {
    if (confirm(msg)) location.href=url;
}

function makeVisible(id) {
    document.getElementById(id).style.display = "block";
}

/*
 * underClip(id, target)
 *
 * Places a 'clipping div' under another div which contains select boxes.
 * On IE and a few other browsers, select boxes are treated as window UI
 * elements, which means that they are always on top of other UI elements.
 * This prevents them showing through.
 *
 */

function underClip(id, target) {
	this.clipper = $(id);
	if(this.clipper) {
		//$(id).style.width=$(target).offsetWidth-4+"px";
		//$(id).style.height=$(target).offsetHeight-4+"px";
		//$(id).style.top = $(target).offsetTop;
		//if(id == 'clipper__X' || id == 'clipper__Y' || id == 'clipper__Z' || id == 'clipper__C' || id == 'clipper__R') {
		//	this.left_offset = -230;
		//} else {
		//	this.left_offset = 0;
		//}
		//$(id).style.left = $(target).offsetLeft + this.left_offset;
		makeVisible(id);
		$(id).style.zIndex = '98';
		$(target).style.zIndex = '99';
	}
}

function makeInvisible(id) {
    document.getElementById(id).style.display = "none";
}

/*
 * Clear Default Text: functions for clearing and replacing default text in
 * <input> elements.
 */


addEvent(window, 'load', init, false);

var trend_number = 0;
var current_trend = 0;
var constraint_number = 0;

function init() {
    var formInputs = document.getElementsByTagName('input');
    for (var i = 0; i < formInputs.length; i++) {
        var theInput = formInputs[i];
        
        if (theInput.type == 'text' && theInput.className.match(/\bcleardefault\b/)) {  
            /* Add event handlers */          
            addEvent(theInput, 'focus', clearDefaultText, false);
            addEvent(theInput, 'blur', replaceDefaultText, false);
            
            /* Save the current value */
            if (theInput.value != '') {
                theInput.defaultText = theInput.value;
            }
        }
    }
    
        createTrendList();

}

function getOptionPosByValue(select, value) {
    for(var c = 0; c < $(select).options.length; c++) {
        if($(select).options[c].value == value) {
            return c;
        }
    }
}

function clearDefaultText(e) {
    var target = window.event ? window.event.srcElement : e ? e.target : null;
    if (!target) return;
    
    if (target.value == target.defaultText) {
        target.value = '';
    }
}

function replaceDefaultText(e) {
    var target = window.event ? window.event.srcElement : e ? e.target : null;
    if (!target) return;
    
    if (target.value == '' && target.defaultText) {
        target.value = target.defaultText;
    }
}

/* 
 * Cross-browser event handling, by Scott Andrew
 */
function addEvent(element, eventType, lamdaFunction, useCapture) {
    if (element.addEventListener) {
        element.addEventListener(eventType, lamdaFunction, useCapture);
        return true;
    } else if (element.attachEvent) {
        var r = element.attachEvent('on' + eventType, lamdaFunction);
        return r;
    } else {
        return false;
    }
}

/* 
 * Kills an event's propagation and default action
 */
function knackerEvent(eventObject) {
    if (eventObject && eventObject.stopPropagation) {
        eventObject.stopPropagation();
    }
    if (window.event && window.event.cancelBubble ) {
        window.event.cancelBubble = true;
    }
    
    if (eventObject && eventObject.preventDefault) {
        eventObject.preventDefault();
    }
    if (window.event) {
        window.event.returnValue = false;
    }
}

/* 
 * Safari doesn't support canceling events in the standard way, so we must
 * hard-code a return of false for it to work.
 */
function cancelEventSafari() {
    return false;        
}

/* 
 * Cross-browser style extraction, from the JavaScript & DHTML Cookbook
 * <http://www.oreillynet.com/pub/a/javascript/excerpt/JSDHTMLCkbk_chap5/index5.html>
 */
function getElementStyle(elementID, CssStyleProperty) {
    var element = $(elementID);
    if (element.currentStyle) {
        return element.currentStyle[toCamelCase(CssStyleProperty)];
    } else if (window.getComputedStyle) {
        var compStyle = window.getComputedStyle(element, '');
        return compStyle.getPropertyValue(CssStyleProperty);
    } else {
        return '';
    }
}

/* 
 * CamelCases CSS property names. Useful in conjunction with 'getElementStyle()'
 * From <http://dhtmlkitchen.com/learn/js/setstyle/index4.jsp>
 */
function toCamelCase(CssProperty) {
    var stringArray = CssProperty.toLowerCase().split('-');
    if (stringArray.length == 1) {
        return stringArray[0];
    }
    var ret = (CssProperty.indexOf("-") == 0)
              ? stringArray[0].charAt(0).toUpperCase() + stringArray[0].substring(1)
              : stringArray[0];
    for (var i = 1; i < stringArray.length; i++) {
        var s = stringArray[i];
        ret += s.charAt(0).toUpperCase() + s.substring(1);
    }
    return ret;
}

/* 
 * Cookie functions
 */
function createCookie(name, value, days) {
    var expires = '';
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        var expires = '; expires=' + date.toGMTString();
    }
    document.cookie = name + '=' + value + expires + '; path=/';
}

function readCookie(name) {
    var cookieCrumbs = document.cookie.split(';');
    var nameToFind = name + '=';
    for (var i = 0; i < cookieCrumbs.length; i++) {
        var crumb = cookieCrumbs[i];
        while (crumb.charAt(0) == ' ') {
            crumb = crumb.substring(1, crumb.length); /* delete spaces */
        }
        if (crumb.indexOf(nameToFind) == 0) {
            return crumb.substring(nameToFind.length, crumb.length);
        }
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, '', -1);
}

/*
 * Bits for manipulating HTML
 */

function clearOptions(column_id) {
    var the_element = $(column_id);
    if(the_element) {
	    if(the_element.tagName == 'SELECT') {
    	    the_element.options.length = 0;
    	}
    }
    
    return true;
}

function getOptions(table_id, column_id) {
    this.element = $(column_id);
    this.table_element = $(table_id);
    this.data = 'id=' + this.element.id + '&table=' + this.table_element.value + '&db=<?php echo $_SESSION['curDB_psql']; ?>';
    
    this.url = '<?php
        echo "http://" . $_SERVER['HTTP_HOST'] . $conf['Dir']['WebPath'] . "/ajax_get_columns.php";
    ?>';
	 
    var getviaAJAX = new Ajax.Request(
                        this.url,
                        {   method: 'post',
                            parameters: this.data,
                            onComplete: setOptions
                        }
                    );
}

function changeColumn(table_id, column_id, operator_id, value_id) {
    if($(operator_id).value == 'is') {
        getValues(table_id, column_id, value_id);
    } else {
        resetInputBox(value_id);
    }
}

function getValues(table_id, column_id, value_box) {
    this.column_element = $(column_id);
    this.element = $(value_box);
    this.table_element = $(table_id);
    this.data = 'id=' + this.element.id + '&table=' + this.table_element.value + '&column=' + this.column_element.value + '&db=<?php echo $_SESSION['curDB_psql']; ?>';
    
    this.url = '<?php
        echo "http://" . $_SERVER['HTTP_HOST'] . $conf['Dir']['WebPath'] . "/ajax_column_values.php";
    ?>';
	
    var getviaAJAX = new Ajax.Request(
                        this.url,
                        {   method: 'post',
                            parameters: this.data,
                            onComplete: setValues 
                        }
                    );
}

function resetInputBox(element) {
    if($(element).type == 'select-one') {     
            this.element_id = $(element).id;
            this.element_name = $(element).name;
            this.parent_id = $(element).parentNode;
            this.next = $(element).nextSibling;
            Element.remove($(element));
            this.newelement = document.createElement('input');
            this.newelement.setAttribute('id', this.element_id);
            this.newelement.setAttribute('type', 'text');
            this.newelement.setAttribute('name', this.element_name);
            this.parent_id.insertBefore(this.newelement, this.next);
    }

}

function setValues(originalRequest) {
    this.xml = originalRequest.responseXML;
    this.type = this.xml.getElementsByTagName('value_type').item(0).firstChild.data;
    this.valuefield = this.xml.getElementsByTagName('value_field').item(0).firstChild.data;
    this.column = this.xml.getElementsByTagName('column').item(0).firstChild.data;
    if(this.type == 'select') {
        this.element_id = $(this.valuefield).id;
        this.parent_id = $(this.valuefield).parentNode;
        this.next = $(this.valuefield).nextSibling;
        this.options = this.xml.getElementsByTagName('value_option');
        Element.remove($(this.valuefield));
        this.newelement = document.createElement('select');
        this.newelement.setAttribute('id', this.element_id);
        this.newelement.setAttribute('name', this.element_id);
        this.newelement.options[0] = new Option('Select Value ...', 'Select Value ...');
        this.parent_id.insertBefore(this.newelement, this.next);
        this.counter = 0;
        for(this.counter = 0; this.counter <= this.options.length; this.counter++) {
            if(this.options.item(this.counter).firstChild.data == '(Null)') {
                this.newelement.options[this.counter] = new Option('Null','');
            } else {
                this.newelement.options[this.counter] = new Option(this.options.item(this.counter).firstChild.data, this.options.item(this.counter).firstChild.data);
            }
        }
    } else {
        resetInputBox(this.valuefield);
    }
}

function setOptions(originalRequest) {
   		this.xml = originalRequest.responseXML;
		this.columns = this.xml.getElementsByTagName('column');
		this.element_name = this.xml.getElementsByTagName('element').item(0).firstChild.data;
		this.element = $(this.element_name);
		this.counter = 0;
    for(this.counter = 0; this.counter <= this.element.length; this.counter++) {
        this.optionName = this.columns.item(this.counter).firstChild.data;
        this.element.options[this.counter] = new Option(this.optionName, this.optionName);
    }    
        
}

function printfire(){    if (document.createEvent)    {        printfire.args = arguments;        var ev = document.createEvent("Events");        ev.initEvent("printfire", false, true);        dispatchEvent(ev);    }}


function changeOptions(table_id, column_id) {
    clearOptions(column_id);
    getOptions(table_id, column_id);
}

function cloneTableElement(id, cloneid, type, replaceWith) {
	if (type == "trend") {
		trend_number = trend_number + 1;
	}
	if (replaceWith == null) {
		replaceWith = trend_number;
	}
	newid = cloneid+'__'+replaceWith;
	cloneid = cloneid+'__0';
	node=$(cloneid).cloneNode(true); 
	for (i=0;i<node.childNodes.length;i++) {
		node.childNodes[i].innerHTML = node.childNodes[i].innerHTML.replace(/__0/g, "__"+replaceWith);
		node.childNodes[i].id = node.childNodes[i].id.replace(/__0/g, "__"+replaceWith);
	}
	node.id = newid;
	var olddiv = $(id);
	olddiv.appendChild(node);
	
	/* This printing the rotating list at the top of the trend div */
	if (type == "trend") {
		current_trend = trend_number;
	 	createTrendList();
	}
}

function cloneElement(id, cloneid, type, replaceWith) {
	if (type == "trend") {
		trend_number = trend_number + 1;
	}
	if (replaceWith == null) {
		replaceWith = trend_number;
	}
	newid = cloneid+'__'+replaceWith;
	cloneid = cloneid+'__0';
	node=$(cloneid).cloneNode(true); 
	node.innerHTML = node.innerHTML.replace(/__0/g, "__"+replaceWith);
	node.id = newid;
	node.style.display = "block";
	var olddiv = $(id);
	olddiv.appendChild(node);
	
	/* This printing the rotating list at the top of the trend div */
	if (type == "trend") {
		current_trend = trend_number;
	 	createTrendList();
	}
}

function addConstraint(parentid, curtrend) {
	var parent = $(parentid);
	if (curtrend == null) {
		curtrend = current_trend;
	} else {
		curtrend = curtrend.replace(/__/g, '');
	}
	<?php /* constraint_number = constraint_number + 1; this is wrong if there are existing constraints, hence the replacement line below */ ?>
	constraint_number = parent.getElementsByTagName('div').length + 1;

	var title = "not set";
	var my_class = "not set";

	string = constraintString;
	string = string.replace(/\!\!\!/g, curtrend);
	string = string.replace(/\?\?\?/g, constraint_number);
	title = 'trend__' + curtrend + constraint_number + 'Div';
	my_class = '';

	var newdiv = document.createElement("div");
	newdiv.innerHTML = string;
	newdiv.id = title;
	parent.appendChild(newdiv);
}

function addTrend(id, string, replaceWith) {
	var ni = $(id);
	trend_number = trend_number + 1;

	var title = "not set";
	var my_class = "not set";

	if (replaceWith != null && replaceWith != "!!!") {
		string = string.replace(/\!\!\!/g,replaceWith);
		title = 'trendlines__' + trend_number;
		my_class = 'trendlines';
	} else {
		string = string.replace(/\!\!\!/g, trend_number);
		title = 'trendlines__' + trend_number;
		my_class = 'trendlines';
	}
	
	var olddiv = document.getElementById(id);
	var newdiv = document.createElement("div");
	newdiv.innerHTML = string;
	newdiv.id = title;
	olddiv.appendChild(newdiv);
	if(id == 'listlines') {
		Element.addClassName(newdiv, 'listlines');
	} else {
		Element.addClassName(newdiv, my_class);
	}
	
	/* This printing the rotating list at the top of the trend div */
	current_trend = trend_number;
 	createTrendList();
}

function removeElement(parentid, trend, constraint) {
	trend = trend.replace(/__/g, '');
	var d = $(parentid);

	var olddiv = $('trend__'+trend+constraint+'Div');
	
	d.removeChild(olddiv);
}

function removeTrend(parentid, id) {
	var target = $(id);
	var parent = $(parentid);
	if(parent && target) {
		parent.removeChild(target);
		current_trend = 0;
		makeVisible('trendlines__' + current_trend);
		createTrendList();
	}
}

function removeTableTrend(parentid, id) {
	var target = $(id);
	var parent = $(parentid);
	if(parent && target) {
		parent.removeChild(target);
		createTrendList();
	}
}

function createTrendList(divname) {
	if(!$('trend_lines')) {
		divname = 'listlines';
	} else {
		divname = 'trend_lines';
	}
	
    if($('trend_no') && $(divname)) {
        trendList = "";
        var trendArray = new Array(1);
        var trend_count = 0;
        var trendpos = 0;
        for(i = 0; i <= $(divname).childNodes.length; i++) {
        	if($(divname).childNodes[i]) {
			var tempName = $(divname).childNodes[i].id;
	        	if(tempName == "trend_no" || tempName == "left_arrow" || tempName == "right_arrow") {
    	    		continue;
        		} else {
        			trendArray[trend_count] = $(divname).childNodes[i].id;
        			if(trendArray[trend_count] == "trendlines__" + current_trend) {
        				trendpos = trend_count;
        			}
        			trend_count = trend_count + 1;
        		}
        		
        	}
        }
        			
        if (trendpos > 1) {
            trendList += "<a onclick='makeVisible(\""+trendArray[trendpos-2]+"\");makeInvisible(\""+trendArray[trendpos]+"\");current_trend = "+parseInt(trendArray[trendpos-2].substr(12))+";createTrendList();' style='position:absolute; left: -5px'>" + (trendpos-1) + "</a>";
        } 
        
        if (trendpos > 0) {
            trendList += "<a onclick='makeVisible(\""+trendArray[trendpos-1]+"\");makeInvisible(\""+trendArray[trendpos]+"\");current_trend = "+parseInt(trendArray[trendpos-1].substr(12))+";createTrendList();' style='position:absolute; left: 13px'>" + (trendpos) + "</a>";
        } 
        
        trendList += "<span style='position: absolute; left: 32px; align: center'>" + (trendpos+1) + "</span>";
        if (trendpos < trend_count-1) {
            trendList += "<a onclick='makeVisible(\""+trendArray[trendpos+1]+"\");makeInvisible(\""+trendArray[trendpos]+"\");current_trend = "+parseInt(trendArray[trendpos+1].substr(12))+";createTrendList();' style='position: absolute; left: 50px'>" + (trendpos+2) + "</a>";
        } 
        
        if (trendpos < trend_count-2) {
            trendList += "<a onclick='makeVisible(\""+trendArray[trendpos+2]+"\");makeInvisible(\""+trendArray[trendpos]+"\");current_trend = "+(parseInt(trendArray[trendpos+2].substr(12)))+";createTrendList();' style='position:absolute; left: 70px'>" + (trendpos+3) + "</a>";
        } 
        
        $('trend_no').innerHTML = trendList;
    }
}

function moveLeft() {
	if(current_trend >= 1) {
		makeVisible("trendlines__" + (current_trend-1));
		makeInvisible("trendlines__" + current_trend);
		current_trend -= 1;
		createTrendList();
	}
}

function moveRight() {
	if(current_trend < trend_number) {
		makeVisible("trendlines__" + (current_trend+1));
		makeInvisible("trendlines__" + current_trend);
		current_trend += 1;
		createTrendList();
	}
}

function validate_trend(startdate) {
	if (!startdate || startdate == "Start Date (YYYY-MM-DD)") {
		alert('Please enter a valid start date');
		return false;
	}
	return true;
}

function saveas(default_val, form_name) {
	var saveas = prompt('Please enter a report name', default_val);
	$(form_name).value = saveas;
}

