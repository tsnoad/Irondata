/*
	Copyright (c) 2004-2006, The Dojo Foundation
	All Rights Reserved.

	Licensed under the Academic Free License version 2.1 or above OR the
	modified BSD license. For more information on Dojo licensing, see:

		http://dojotoolkit.org/community/licensing.shtml
*/

dojo.require("dojo.dom");
dojo.require("dojo.event.*");
dojo.require("dojo.html");
dojo.require("dojo.lfx.*");
dojo.require("dojo.widget.Editor2");
dojo.require("dojo.storage.*");
if (dojo.render.html.ie70 == true && window.location.protocol.indexOf("file") != -1) {
	alert("Moxie on Internet Explorer 7.0 is not yet supported when loaded from the file:// URL");
}
var Moxie = {initialize:function () {
	dojo.byId("offlineLink").href = window.location.href;
	dojo.byId("storageKey").value = "";
	dojo.byId("storageValue").value = "";
	this._printAvailableKeys();
	var directory = dojo.byId("directory");
	dojo.event.connect(directory, "onchange", this, this.directoryChange);
	dojo.event.connect(dojo.byId("saveButton"), "onclick", this, this.save);
	dojo.event.connect(dojo.byId("configureButton"), "onclick", this, this.configure);
}, directoryChange:function (evt) {
	var key = evt.target.value;
	var keyNameField = dojo.byId("storageKey");
	keyNameField.value = key;
	if (key == "") {
		return;
	}
	this._handleLoad(key);
}, save:function (evt) {
	evt.preventDefault();
	evt.stopPropagation();
	var key = dojo.byId("storageKey").value;
	var richTextControl = dojo.widget.byId("storageValue");
	var value = richTextControl.getEditorContent();
	if (key == null || typeof key == "undefined" || key == "") {
		alert("Please enter a file name");
		return;
	}
	if (value == null || typeof value == "undefined" || value == "") {
		alert("Please enter file contents");
		return;
	}
	this._save(key, value);
}, configure:function (evt) {
	evt.preventDefault();
	evt.stopPropagation();
	if (dojo.storage.hasSettingsUI()) {
		var self = this;
		dojo.storage.onHideSettingsUI = function () {
			self._printAvailableKeys();
			if (dojo.render.html.moz) {
				var storageValue = dojo.byId("storageValue");
				storageValue.style.display = "block";
			}
		};
		if (dojo.render.html.moz) {
			var storageValue = dojo.byId("storageValue");
			storageValue.style.display = "none";
		}
		dojo.storage.showSettingsUI();
	}
}, _save:function (key, value) {
	this._printStatus("Saving '" + key + "'...");
	var self = this;
	var saveHandler = function (status, keyName) {
		if (status == dojo.storage.PENDING) {
			if (dojo.render.html.moz) {
				var storageValue = dojo.byId("storageValue");
				storageValue.style.display = "none";
			}
			return;
		}
		if (status == dojo.storage.FAILED) {
			alert("You do not have permission to store data for this web site. " + "Press the Configure button to grant permission.");
		} else {
			if (status == dojo.storage.SUCCESS) {
				dojo.byId("storageKey").value = "";
				dojo.byId("storageValue").value = "";
				self._printStatus("Saved '" + key + "'");
				window.setTimeout(function () {
					self._printAvailableKeys();
				}, 1);
			}
		}
		if (dojo.render.html.moz) {
			var storageValue = dojo.byId("storageValue");
			storageValue.style.display = "block";
		}
	};
	try {
		dojo.storage.put(key, value, saveHandler);
	}
	catch (exp) {
		alert(exp);
	}
}, _printAvailableKeys:function () {
	var directory = dojo.byId("directory");
	directory.innerHTML = "";
	var optionNode = document.createElement("option");
	optionNode.appendChild(document.createTextNode(""));
	optionNode.value = "";
	directory.appendChild(optionNode);
	var availableKeys = dojo.storage.getKeys();
	for (var i = 0; i < availableKeys.length; i++) {
		var optionNode = document.createElement("option");
		optionNode.appendChild(document.createTextNode(availableKeys[i]));
		optionNode.value = availableKeys[i];
		directory.appendChild(optionNode);
	}
}, _handleLoad:function (key) {
	this._printStatus("Loading '" + key + "'...");
	var results = dojo.storage.get(key);
	var richTextControl = dojo.widget.byId("storageValue");
	richTextControl.replaceEditorContent(results);
	richTextControl._updateHeight();
	this._printStatus("Loaded '" + key + "'");
}, _printStatus:function (message) {
	var top = dojo.byId("top");
	for (var i = 0; i < top.childNodes.length; i++) {
		var currentNode = top.childNodes[i];
		if (currentNode.nodeType == dojo.dom.ELEMENT_NODE && currentNode.className == "status") {
			top.removeChild(currentNode);
		}
	}
	var status = document.createElement("span");
	status.className = "status";
	status.innerHTML = message;
	top.appendChild(status);
	dojo.lfx.fadeOut(status, 2000).play();
}};
if (dojo.storage.manager.isInitialized() == false) {
	dojo.event.connect(dojo.storage.manager, "loaded", Moxie, Moxie.initialize);
} else {
	dojo.event.connect(dojo, "loaded", Moxie, Moxie.initialize);
}

