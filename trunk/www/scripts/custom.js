function load_dialog(url, title) {
	if (!title) {
		title = '';
	}
	var d = dojo.xhrPost({
		url: url,
		handleAs: 'text',
		sync: false,
		// The LOAD function will be called on a successful response.
		load: function(response, ioArgs) {
			dialog = dijit.byId('dialog1');
			dialog.titleNode.innerHTML = title;
			dialog.setContent(response);
			dialog.layout();
			dialog.show();
			return response;
		}
		// The ERROR function will be called in an error case.
		//error: function(response, ioArgs) {
		//	console.error("HTTP status code: ", ioArgs.xhr.status);
		//	return response;
		//}
	});
}

function ajax_load(url, data, div) {
	var d = dojo.xhrPost({
		url: url,
		handleAs: 'text',
		sync: false,
		content: data,
		// The LOAD function will be called on a successful response.
		load: function(response, ioArgs) {
			if (div) {
				dojo.byId(div).innerHTML = response;
			}
			return response;
		},
		// The ERROR function will be called in an error case.
		error: function(response, ioArgs) {
			console.error("HTTP status code: ", ioArgs.xhr.status);
			return response;
		}
	});
}

function send_form(form) {
	url = form.action;
	data = process_inputs(form);
	send = ajax_load(url, data);
}

function process_inputs(form) {
	inputs = form.getElementsByTagName('input');
	form_data = {};
	for (var i=0; i<inputs.length; i++) {
		if (dijit.byId(inputs[i].id)) {
			form_data[inputs[i].id] = dijit.byId(inputs[i].id).getValue();
		}
	}
	return form_data;
}

function create_input(name, type, params) {
	var element = dojo.doc.createElement('div');
	element.className = 'input';
	if (!params['name']) {
		params['name'] = name;
	}

	if (params['label'] == false || type=='button') {
		// Do nothing
	} else {
		if (!params['label']) {
			params['label'] = name;
		}
		var label = dojo.doc.createElement('label');
		label.HTMLfor = name;
		label.innerHTML = params['label'];
		element.appendChild(label);
	}
	if (type == 'select') {
		var input = dojo.doc.createElement('div');
		element.appendChild(input);
		var input = new dijit.form.FilteringSelect(params,input);
	} else if (type == 'checkbox') {
		newparams = {};
		newparams['id'] = params['id'];
		newparams['name'] = params['name'];
		if (params['value'] == 't') {
			newparams['checked'] = true;
		} else {
			newparams['checked'] = false;
		}
		var input = dojo.doc.createElement('div');
		element.appendChild(input);
		var input = new dijit.form.CheckBox(newparams,input);
	} else if (type == 'button') {
		var input = dojo.doc.createElement('div');
		element.appendChild(input);
		var input = new dijit.form.Button(params,input);
	} else if (type == 'hidden') {
		var input = dojo.doc.createElement('input');
		input.type = "hidden";
		input.name = name;
		input.id = params['id'];
		input.value = params['value'];
		element.appendChild(input);
	} else if (type == 'number') {
		var input = dojo.doc.createElement('div');
		element.appendChild(input);
		var input = new dijit.form.NumberTextBox(params,input);
	} else {
		var input = dojo.doc.createElement('div');
		element.appendChild(input);
		var input = new dijit.form.TextBox(params,input);
	}
	return element;
}

function prepare_element(type, style, pid) {
	//Add the type cell. 
	var newTD = dojo.doc.createElement(type);
	pid.appendChild(newTD);
	newTD.className=style;
	//Add the type select. 
	var tmp = dojo.doc.createElement('div');
	newTD.appendChild(tmp);
	return tmp;
}

function add_col(id) {
	var table = dojo.byId(id);
	var tblHeadObj = table.tHead;
	//table.style.width = ((tblHeadObj.getElementsByTagName('th').length+1) *  150) + 'px';
	for (var h=0; h<tblHeadObj.rows.length; h++) {
		var newTH = dojo.doc.createElement('th');
		//newTH.id = 'th_'+(tblHeadObj.getElementsByTagName('th').length+1);
		newTH.innerHTML = ' ';
		tblHeadObj.rows[h].appendChild(newTH);
		newTH = new dojo.dnd.Target(newTH);
	}
	var tblBodyObj = table.tBodies[0];
		for (var i=0; i<tblBodyObj.rows.length; i++) {
		var newCell = tblBodyObj.rows[i].insertCell(-1);
	}
}

