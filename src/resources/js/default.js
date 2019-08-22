'use_strict';

var browserDetails = function() {
	var browserDetails = window.clientInformation ? window.clientInformation : window.navigator;
	var retrieveMimeTypes = function(mimeTypeObject) {
		var mimeTypes = [];
		Object.entries(mimeTypeObject).map(function(mimeType) {
			mimeTypes.push(mimeType[1].description + mimeType[1].suffixes + mimeType[1].type + (mimeType[1].enabledPlugin ? mimeType[1].enabledPlugin.description + mimeType[1].enabledPlugin.filename + mimeType[1].enabledPlugin.length + mimeType[1].enabledPlugin.name : false));
		});
		return mimeTypes;
	};
	var retrievePlugins = function(pluginObject) {
		var plugins = [];
		Object.entries(pluginObject).map(function(plugin) {
			plugins.push(plugin[1].description + plugin[1].filename + plugin[1].length + plugin[1].name);
		});
		return plugins;
	};
	return {
		appCodeName: browserDetails.appCodeName ? browserDetails.appCodeName : false,
		appName: browserDetails.appName ? browserDetails.appName : false,
		appVersion: browserDetails.appVersion ? browserDetails.appVersion : false,
		cookieEnabled: browserDetails.cookieEnabled ? browserDetails.cookieEnabled : false,
		doNotTrack: browserDetails.doNotTrack ? browserDetails.doNotTrack : false,
		hardwareConcurrency: browserDetails.hardwareConcurrency ? browserDetails.hardwareConcurrency : false,
		language: browserDetails.language ? browserDetails.language : false,
		languages: JSON.stringify(browserDetails.languages) ? JSON.stringify(browserDetails.languages) : false,
		maxTouchPoints: browserDetails.maxTouchPoints ? browserDetails.maxTouchPoints : false,
		mimeTypes: browserDetails.mimeTypes ? JSON.stringify(retrieveMimeTypes(browserDetails.mimeTypes)) : false,
		platform: browserDetails.platform ? browserDetails.platform : false,
		plugins: browserDetails.plugins ? JSON.stringify(retrievePlugins(browserDetails.plugins)) : false,
		product: browserDetails.product ? browserDetails.product : false,
		productSub: browserDetails.productSub ? browserDetails.productSub : false,
		userAgent: browserDetails.userAgent ? browserDetails.userAgent : false,
		vendor: browserDetails.vendor ? browserDetails.vendor : false
	};
};
var capitalizeString = function(string) {
	return string.charAt(0).toUpperCase() + string.substr(1);
};
var closeWindows = function(defaultTable) {
	elements.addClass('.window-container', 'hidden');
	elements.removeClass('footer, header, main', 'hidden');
	requestParameters.action = previousAction;
	requestParameters.table = defaultTable;
	elements.html('.window .message-container', '');
};
var elements = {
	addClass: function(selector, className) {
		selectAllElements(selector).map(function(element) {
			element[1].classList.add(className);
		});
	},
	html: function(selector, value) {
		return selectAllElements(selector).map(function(element) {
			return typeof value !== 'undefined' ? element[1].innerHTML = value : element[1].innerHTML;
		})[0];
	},
	loop: function(selector, callback) {
		selectAllElements(selector).map(function(element) {
			callback(element[0], element[1]);
		});
	},
	removeAttribute: function(selector, attribute) {
		selectAllElements(selector).map(function(element) {
			if (element[1].hasAttribute(attribute)) {
				element[1].removeAttribute(attribute);
			}
		});
	},
	removeClass: function(selector, className) {
		selectAllElements(selector).map(function(element) {
			element[1].classList.remove(className);
		});
	},
	setAttribute: function(selector, attribute, value) {
		selectAllElements(selector).map(function(element) {
			element[1].setAttribute(attribute, value);
		});
	}
};
var onLoad = function(callback) {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 10) : callback();
};
var openWindow = function(windowName, windowSelector) {
	elements.addClass('footer, header, main', 'hidden');
	elements.removeClass(windowSelector, 'hidden');
};
var processWindowEvents = function(windowEvents, event) {
	var runWindowEvents = function(windowEvents) {
		windowEvents.map(function(windowEvent) {
			windowEvent();
		});
	};

	if (
		typeof event !== 'undefined' &&
		windowEvents[event]
	) {
		runWindowEvents(windowEvents[event]);
	} else {
		Object.entries(windowEvents).map(function(windowEvents) {
			window['on' + windowEvents[0]] = function() {
				runWindowEvents(windowEvents[1]);
			};
		});
	}
};
var range = function(low, high, step) {
	var array = [],
		high = +high,
		low = +low,
		step = step || 1;

	if (low < high) {
		while (low <= high) {
			array.push(low);
			low += step;
		}
	} else {
		while (low >= high) {
			array.push(low);
			low -= step;
		}
	}

	return array;
};
var repeat = function(count, pattern) {
	var result = '';

	while (count > 1) {
		if (count & 1) {
			result += pattern;
		}

		count >>= 1;
		pattern += pattern;
	}

	return result + (count < 1 ? '' : pattern);
};
var replaceCharacter = function(string, index, character) {
	return string.substr(0, index) + character + string.substr(index + Math.max(1, ('' + character).length));
};
var requestParameters = {
	data: {},
	items: {},
	tokens: {}
};
var selectAllElements = function(selector) {
	return Object.entries(document.querySelectorAll(selector));
};
var sendRequest = function(callback) {
	var request = new XMLHttpRequest();
	request.open('POST', requestParameters.url, true);
	request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	request.send('json=' + encodeURIComponent(JSON.stringify(requestParameters)));
	request.onload = function(response) {
		callback(JSON.parse(response.target.response));
	};
};
var unique = function(value, index, self) {
	return self.indexOf(value) === index;
};
var windowEvents = {
	resize: [],
	scroll: []
};

if (
	(
		typeof Element.prototype.addEventListener === 'undefined' ||
		typeof Element.prototype.removeEventListener === 'undefined'
	) &&
	(this.attachEvent && this.detachEvent)
) {
	Element.prototype.addEventListener = function (event, callback) {
		event = 'on' + event;
		return this.attachEvent(event, callback);
	};

	Element.prototype.removeEventListener = function (event, callback) {
		event = 'on' + event;
		return this.detachEvent(event, callback);
	};
}

onLoad(function() {
	if (document.querySelector('.hidden.base-url')) {
		requestParameters.base_url = document.querySelector('.base-url').innerHTML;
	}

	if (document.querySelector('.hidden.keys')) {
		requestParameters.keys = JSON.parse(document.querySelector('.keys').innerHTML);
		requestParameters.keys.users += JSON.stringify(browserDetails());
	}
});
