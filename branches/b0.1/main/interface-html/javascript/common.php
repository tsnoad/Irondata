function submit_form(formId) {
	var bindArgs = {
		url: "index.php",
		error: function(type, data, evt){
			alert("An error occurred.");
		},
		load: function(type, data, evt){
			eval(data);
		},
		mimetype: "text/javascript",
		formNode: document.getElementById(formId)
	};
	dojo.io.bind(bindArgs);
}

function loadElement(id, content) {
	var block = dojo.byId(id);
	block.innerHTML = unescape(content);
	if (id == 'message') {
		var parent = dojo.byId('layout_message');
		parent.style.display = 'block';
		dojo.lfx.toggle.fade.show(parent, 100);
	} else {
		dojo.lfx.toggle.fade.show(block, 100);
	}
}

function loadPopup(content) {
	var dialog = dojo.widget.byId("layout_block");
	var btn = document.getElementById("layout_block_close");
	var block = dojo.byId('message');
	block.innerHTML = unescape(content);
	dialog.setCloseControl(btn);
	dialog.show();
}

function hideElement(id) {
	var block = dojo.byId(id);
	if (id == 'layout_message') {
		var child = dojo.byId('message');
		child.innerHTML = '';
	} else {
		block.innerHTML = '';
	}
	dojo.lfx.toggle.fade.hide(block, 100);
}

function expandPane(id) {
	var block = dojo.byId(id+'_div');
	var field = dojo.byId(id);
	if (field.className == 'expanded') {
		dojo.lfx.toggle.wipe.hide(block, 100);
		field.className = 'collapsed'
	} else {
		dojo.lfx.toggle.wipe.show(block, 100);
		field.className = 'expanded'
	}
}

function check(msg) {
	if (!confirm(msg)) {
		return false;
	} else {
		return true;
	}
}
