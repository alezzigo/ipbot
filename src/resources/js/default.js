var api = {
	setRequestParameters: function(requestParameters, mergeRequestParameters) {
		if (
			requestParameters &&
			typeof requestParameters === 'object'
		) {
			for (var requestParameterKey in requestParameters) {
				if (typeof apiRequestParameters.current[requestParameterKey] !== 'undefined') {
					Object.defineProperty(apiRequestParameters.previous, requestParameterKey, {
						configurable: true,
						value: apiRequestParameters.current[requestParameterKey]
					});

					if (mergeRequestParameters === true) {
						var apiRequestParametersToMerge = apiRequestParameters.current[requestParameterKey];

						for (var requestParameterNestedKey in requestParameters[requestParameterKey]) {
							apiRequestParametersToMerge[requestParameterNestedKey] = requestParameters[requestParameterKey][requestParameterNestedKey];
						}

						requestParameters[requestParameterKey] = apiRequestParametersToMerge;
					}
				}

				Object.defineProperty(apiRequestParameters.current, requestParameterKey, {
					configurable: true,
					value: requestParameters[requestParameterKey]
				});
			}
		}
	},
	sendRequest: function(callback) {
		var request = new XMLHttpRequest();
		request.open('POST', apiRequestParameters.url, true);
		request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		request.send('json=' + encodeURIComponent(JSON.stringify(apiRequestParameters)));
		request.onload = function(response) {
			callback(JSON.parse(response.target.response));
		};
	}
};
var apiRequestParameters = {
	current: {
		data: {},
		items: {},
		tokens: {}
	},
	previous: {}
};
var browserDetails = function() {
	var browserDetails = window.clientInformation ? window.clientInformation : window.navigator;
	var retrieveMimeTypes = function(mimeTypeObject) {
		var response = [];
		Object.entries(mimeTypeObject).map(function(mimeType) {
			response.push(mimeType[1].description + mimeType[1].suffixes + mimeType[1].type + (mimeType[1].enabledPlugin ? mimeType[1].enabledPlugin.description + mimeType[1].enabledPlugin.filename + mimeType[1].enabledPlugin.length + mimeType[1].enabledPlugin.name : false));
		});
		return response;
	};
	var retrievePlugins = function(pluginObject) {
		var response = [];
		Object.entries(pluginObject).map(function(plugin) {
			response.push(plugin[1].description + plugin[1].filename + plugin[1].length + plugin[1].name);
		});
		return response;
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
	stringParts = string.split(' ');
	stringParts.map(function(stringPart, stringPartIndex) {
		stringParts[stringPartIndex] = stringPart.charAt(0).toUpperCase() + stringPart.substr(1);
	});
	return stringParts.join(' ');
};
var closeFrames = function(defaultTable) {
	elements.addClass('.frame-container', 'hidden');
	elements.html('.frame .message-container', '');
	elements.removeClass('footer, header, main', 'hidden');
	requestParameters.action = previousAction;
	requestParameters.table = defaultTable;
	window.scroll(0, 0);
};
var elements = {
	addClass: function(selector, className) {
		selectAllElements(selector).map(function(element) {
			element[1].classList.add(className);
		});
	},
	hasClass: function(selector, className) {
		return !selectAllElements(selector).map(function(element) {
			return element[1].classList.contains(className) ? '' : 1;
		}).join('');
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
var openFrame = function(frameName, frameSelector) {
	elements.addClass('footer, header, main', 'hidden');
	elements.removeClass(frameSelector, 'hidden');
	window.scroll(0, 0);
};
var processWindowEvents = function(event) {
	if (typeof event === 'undefined') {
		return false;
	}

	if (
		typeof windowEvents[event] === 'object' &&
		windowEvents[event]
	) {
		windowEvents[event].map(function(windowEvent) {
			windowEvent();
		});
	}
};
var range = function(low, high, step) {
	var response = [];
	high = +high;
	low = +low;
	step = step || 1;

	if (low < high) {
		while (low <= high) {
			response.push(low);
			low += step;
		}
	} else {
		while (low >= high) {
			response.push(low);
			low -= step;
		}
	}

	return response;
};
var repeat = function(count, pattern) {
	var response = '';

	while (count > 1) {
		if (count & 1) {
			response += pattern;
		}

		count >>= 1;
		pattern += pattern;
	}

	return response + (count < 1 ? '' : pattern);
};
var requestParameters = {
	data: {},
	items: {},
	tokens: {}
};
var selectAllElements = function(selector) {
	var nodeList = document.querySelectorAll(selector);
	var response = [];

	if (nodeList.length) {
		response = Object.entries(nodeList);
	}

	return response;
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

if (!Object.entries) {
	Object.entries = function(object) {
		if (typeof object !== 'object') {
			return false;
		}

		var response = [];

		for (var objectKey in object) {
			if (object.hasOwnProperty(objectKey)) {
				response.push([objectKey, object[objectKey]]);
			}
		}

		return response;
	};
}

onLoad(function() {
	if (document.querySelector('.hidden.keys')) {
		requestParameters.keys = JSON.parse(document.querySelector('.hidden.keys').innerHTML);
		requestParameters.keys.users += JSON.stringify(browserDetails());
	}

	if (document.querySelector('.hidden.settings')) {
		requestParameters.settings = JSON.parse(document.querySelector('.hidden.settings').innerHTML);
	}
});
