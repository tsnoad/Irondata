dojo.addOnLoad(confoo_init);

function confoo_init() {
	var confoo_div = dojo.byId('confoo_div');
	var confoo_in = dojo.byId('confoo_in');
	var confoo_old = dojo.byId('confoo_old');
	var confoo_save = dojo.byId('confoo_save');

	if (confoo_in.value != confoo_old.value) {
		confoo_save.style.display = 'block';
	} else {
		confoo_save.style.display = 'none';
	}

	confoo();

	confoo_div_init_span_mousedown();

	document.onmousemove = confoo_getmouse;

	confoo_in.onkeyup = function () {
		confoo();
		confoo_div_init_span_mousedown();
		show_selection();

		var confoo_in = dojo.byId('confoo_in');
		var confoo_old = dojo.byId('confoo_old');
		var confoo_save = dojo.byId('confoo_save');

		if (confoo_in.value != confoo_old.value) {
			confoo_save.style.display = 'block';
		} else {
			confoo_save.style.display = 'none';
		}
	}

	confoo_in.onblur = function () {
		var confoo_in = dojo.byId('confoo_in');

		confoo_in.setSelectionRange(null, null);

		show_selection();
	}
}

var mouse_x;

function confoo_getmouse(e) {
	mouse_x = e.pageX
}

function confoo_div_init_span_mousedown() {
	var confoo_div = dojo.byId('confoo_div');

	if (confoo_div.hasChildNodes()) {
		var confoo_div_spans = confoo_div.childNodes;

		for (var i = 0; i < confoo_div_spans.length; i ++) {
			confoo_div_spans[i].onmousedown = confoo_div_span_mousedown;
		}
	}
}

function confoo_div_init_mouseup() {
	window.onmouseup = confoo_div_mouseup;
}

function confoo_div_term_mouseup() {
	window.onmouseup = function () {};
}

function confoo_div_init_span_mousemove() {
	var confoo_div = dojo.byId('confoo_div');

	if (confoo_div.hasChildNodes()) {
		var confoo_div_spans = confoo_div.childNodes;

		for (var i = 0; i < confoo_div_spans.length; i ++) {
			confoo_div_spans[i].onmousemove = confoo_div_span_mousemove;
		}
	}
}

function confoo_div_term_span_mousemove() {
	var confoo_div = dojo.byId('confoo_div');

	if (confoo_div.hasChildNodes()) {
		var confoo_div_spans = confoo_div.childNodes;

		for (var i = 0; i < confoo_div_spans.length; i ++) {
			confoo_div_spans[i].onmousemove = function () {};
		}
	}
}

var start_select;

function confoo_div_span_mousedown(e) {
	var currentpos = e.currentTarget.id.substr(7);

	var char_centerline = e.currentTarget.offsetLeft + e.currentTarget.offsetWidth / 2;

	if (mouse_x > char_centerline) {
		currentpos ++;
	}

	start_select = parseInt(currentpos);
	end_select = null;

	confoo_div_init_mouseup();
	confoo_div_init_span_mousemove();

	return false;
}

var end_select;

function confoo_div_span_mousemove(e) {
	var currentpos = e.currentTarget.id.substr(7);

	var char_centerline = e.currentTarget.offsetLeft + e.currentTarget.offsetWidth / 2;

	if (mouse_x > char_centerline) {
		currentpos ++;
	}

	end_select = parseInt(currentpos);

	make_hash_select();
}

function confoo_div_mouseup() {
	make_hash_select();

	confoo_div_term_mouseup();
	confoo_div_term_span_mousemove();
}

function make_hash_select() {
	var confoo_div = dojo.byId('confoo_div');
	var confoo_in = dojo.byId('confoo_in');

	if (!end_select) {
		end_select = start_select;
	}

	if (end_select < start_select) {
		confoo_in.focus();
		confoo_in.setSelectionRange(end_select, start_select);
	} else {
		confoo_in.focus();
		confoo_in.setSelectionRange(start_select, end_select);
	}

	show_selection();
}

function confoo() {
	var confoo_div = dojo.byId('confoo_div');
	var confoo_in = dojo.byId('confoo_in');

	var con_array_foo = new Array;

	for (var i = 0; i <= confoo_in.value.length; i ++) {
		var con_char = confoo_in.value.substr(i, 1);

		con_array_foo[i] = con_char;
	}

	var confoo_text = '';

	var span_class = '';

	for (var i in con_array_foo) {
		con_char = con_array_foo[i];

		var constraint_text_tmp = null;

		for (var j in constraints_ascii) {
			if (con_char == constraints_ascii[j]) {
				constraint_text_tmp = constraints_text[j];
				break;
			}
		}

		if (constraint_text_tmp) {
			confoo_text += '<span id=\"conpos_'+i+'\" class=\"constraint '+span_class+'\">'+constraint_text_tmp+'</span>';
		} else {
			confoo_text += '<span id=\"conpos_'+i+'\" class=\"'+span_class+'\">'+con_char+'</span>';
		}
	}

	confoo_div.innerHTML = confoo_text;
}

function show_selection() {
	var confoo_div = dojo.byId('confoo_div');
	var confoo_in = dojo.byId('confoo_in');

	var con_array_foo = new Array;

	for (var i = 0; i <= confoo_in.value.length; i ++) {
		var con_char = confoo_in.value.substr(i, 1);

		con_array_foo[i] = con_char;
	}

	var span_class = '';

	for (var i in con_array_foo) {
		if (i == confoo_in.selectionStart && i == confoo_in.selectionEnd) {
			span_class = 'cursor_before';
		} else if (i == confoo_in.selectionStart) {
			span_class = 'selected';
		} else if (i == confoo_in.selectionEnd) {
			span_class = '';
		}

		if (dojo.byId('conpos_'+i).className.toString().search(/constraint/) == 0) {
			dojo.byId('conpos_'+i).className = 'constraint '+span_class;
		} else {
			dojo.byId('conpos_'+i).className = span_class;
		}

		if ((con_array_foo[i] == "(" || con_array_foo[i] == ")") && span_class == 'cursor_before') {
			var highlight_bracket = i;
		}

		if (i == confoo_in.selectionStart && i == confoo_in.selectionEnd) {
			span_class = '';
		}
	}

	if (highlight_bracket) {
		if (con_array_foo[highlight_bracket] == "(") {
			var depth_count = 0;

			for (var j = highlight_bracket; j < con_array_foo.length; j ++) {
				if (con_array_foo[j] == "(") {
					depth_count ++;
				}

				if (con_array_foo[j] == ")") {
					depth_count --;

					if (depth_count == 0) {
						dojo.byId('conpos_'+highlight_bracket).className += ' bracket';
						dojo.byId('conpos_'+j).className += ' bracket';

						break;
					}
				}
			}
		} else if (con_array_foo[highlight_bracket] == ")") {
			var depth_count = 0;

			for (var j = highlight_bracket; j >= 0; j --) {
				if (con_array_foo[j] == ")") {
					depth_count ++;
				}

				if (con_array_foo[j] == "(") {
					depth_count --;

					if (depth_count == 0) {
						dojo.byId('conpos_'+highlight_bracket).className += ' bracket';
						dojo.byId('conpos_'+j).className += ' bracket';

						break;
					}
				}
			}
		}
	}
}