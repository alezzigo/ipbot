'use_strict';

var capitalizeString = (string) => {
	return string.charAt(0).toUpperCase() + string.substr(1);
}
var closeWindows = (defaultTable) => {
	elements.addClass('.window-container', 'hidden');
	elements.removeClass('footer, header, main', 'hidden');
	requestParameters.action = previousAction;
	requestParameters.table = defaultTable;
	window.location.hash = '';
};
var elements = {
	addClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.add(className);
		});
	},
	html: (selector, value = null) => {
		return selectAllElements(selector).map((element) => {
			return value !== null ? element[1].innerHTML = value : element[1].innerHTML;
		})[0];
	},
	loop: (selector, callback) => {
		selectAllElements(selector).map((element) => {
			callback(element[0], element[1]);
		});
	},
	removeAttribute: (selector, attribute) => {
		selectAllElements(selector).map((element) => {
			if (element[1].hasAttribute(attribute)) {
				element[1].removeAttribute(attribute);
			}
		});
	},
	removeClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.remove(className);
		});
	},
	setAttribute: (selector, attribute, value) => {
		selectAllElements(selector).map((element) => {
			element[1].setAttribute(attribute, value);
		});
	}
};
var onLoad = (callback) => {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 10) : callback();
};
var openWindow = (windowName, windowSelector) => {
	elements.addClass('footer, header, main', 'hidden');
	elements.removeClass(windowSelector, 'hidden');
	window.location.hash = windowName;
}
var processWindowEvents = (windowEvents, event = null) => {
	var runWindowEvents = (windowEvents) => {
		windowEvents.map((windowEvent) => {
			windowEvent();
		});
	};

	if (
		event &&
		windowEvents[event]
	) {
		runWindowEvents(windowEvents[event]);
	} else {
		Object.entries(windowEvents).map((windowEvents) => {
			window['on' + windowEvents[0]] = () => {
				runWindowEvents(windowEvents[1]);
			};
		});
	}
};
var range = (low, high, step = 1) => {
	var array = [],
		high = +high,
		low = +low;

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
}
var repeat = (count, pattern) => {
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
var replaceCharacter = (string, index, character) => {
	return string.substr(0, index) + character + string.substr(index + Math.max(1, ('' + character).length));
};
var requestParameters = {
	data: {},
	items: {},
	keys: document.querySelector('.keys') ? JSON.parse(document.querySelector('.keys').innerHTML) : {},
	tokens: {}
};
var selectAllElements = (selector) => {
	return Object.entries(document.querySelectorAll(selector));
};
var sendRequest = (callback) => {
	var request = new XMLHttpRequest();
	request.open('POST', requestParameters.url, true);
	request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	request.send('json=' + encodeURIComponent(JSON.stringify(requestParameters)));
	request.onload = function(response) {
		callback(JSON.parse(response.target.response));
	};
};
var unique = (value, index, self) => {
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
