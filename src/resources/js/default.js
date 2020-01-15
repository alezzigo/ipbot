const api = {
	setRequestParameters: function(requestParameters, mergeRequestParameters) {
		if (
			typeof requestParameters === 'object' &&
			requestParameters
		) {
			for (let requestParameterKey in requestParameters) {
				if (typeof apiRequestParameters.current[requestParameterKey] !== 'undefined') {
					Object.defineProperty(apiRequestParameters.previous, requestParameterKey, {
						configurable: true,
						enumerable: true,
						value: apiRequestParameters.current[requestParameterKey],
						writable: false
					});

					if (mergeRequestParameters === true) {
						let apiRequestParametersToMerge = apiRequestParameters.current[requestParameterKey];

						if (typeof requestParameters[requestParameterKey] === 'object') {
							for (let requestParameterNestedKey in requestParameters[requestParameterKey]) {
								apiRequestParametersToMerge[requestParameterNestedKey] = requestParameters[requestParameterKey][requestParameterNestedKey];
							}
						} else {
							apiRequestParametersToMerge = requestParameters[requestParameterKey];
						}

						requestParameters[requestParameterKey] = apiRequestParametersToMerge;
					}
				}

				Object.defineProperty(apiRequestParameters.current, requestParameterKey, {
					configurable: true,
					enumerable: true,
					value: requestParameters[requestParameterKey],
					writable: false
				});
			}
		}
	},
	sendRequest: function(callback) {
		let request = new XMLHttpRequest();
		request.open('POST', apiRequestParameters.current.url, true);
		request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		request.send('json=' + encodeURIComponent(JSON.stringify(apiRequestParameters.current)));
		request.onload = function(response) {
			callback(JSON.parse(response.target.response));
		};
	}
};
var apiRequestParameters = {
	current: {
		data: {},
		defaults: {},
		items: {},
		tokens: {}
	},
	previous: {}
};
const browserDetails = function() {
	const browserDetails = window.clientInformation ? window.clientInformation : window.navigator;
	const retrieveMimeTypes = function(mimeTypeObject) {
		let response = [];

		for (let mimeTypeObjectKey in Object.entries(mimeTypeObject)) {
			let mimeType = mimeTypeObject[mimeTypeObjectKey];
			response.push(mimeType.description + mimeType.suffixes + mimeType.type + (mimeType.enabledPlugin ? mimeType.enabledPlugin.description + mimeType.enabledPlugin.filename + mimeType.enabledPlugin.length + mimeType.enabledPlugin.name : false));
		}

		return response;
	};
	const retrievePlugins = function(pluginObject) {
		let response = [];

		for (let pluginObjectKey in Object.entries(pluginObject)) {
			let plugin = pluginObject[pluginObjectKey];
			response.push(plugin.description + plugin.filename + plugin.length + plugin.name);
		}

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
const capitalizeString = function(string) {
	stringParts = string.split(' ');
	stringParts.map(function(stringPart, stringPartIndex) {
		stringParts[stringPartIndex] = stringPart.charAt(0).toUpperCase() + stringPart.substr(1);
	});
	return stringParts.join(' ');
};
const closeFrames = function(closeFrameApiRequestParameters) {
	elements.addClass('.frame-container', 'hidden');
	elements.html('.frame .message-container', '');
	elements.removeClass('footer, header, main', 'hidden');
	api.setRequestParameters(closeFrameApiRequestParameters);
	window.scroll(0, 0);
};
const elements = {
	addClass: function(selector, className) {
		selectAllElements(selector, function(selectedElementKey, selectedElement) {
			selectedElement.classList.add(className);
		});
	},
	addScrollable: function(selector, callback) {
		selectAllElements(selector, function(selectedElementKey, selectedElement) {
			let event = function() {
				const elementContainerDetails = selectedElement.parentNode.getBoundingClientRect();
				selectedElement.details = elementContainerDetails;
				callback(selectedElement);
				selectedElement.setAttribute('scrolling', +(window.pageYOffset > (selectedElement.details.top + window.pageYOffset)));
			};

			windowEvents.resize.push(event);
			windowEvents.scroll.push(event);
		});
	},
	get: function(selector) {
		return document.querySelector(selector);
	},
	getAttribute: function(selector, attribute) {
		let element = document.querySelector(selector);
		let value = false;

		if (
			element &&
			element.hasAttribute(attribute)
		) {
			value = element.getAttribute(attribute);
		}

		return value;
	},
	hasClass: function(selector, className) {
		let hasClass = false;

		selectAllElements(selector, function(selectedElementKey, selectedElement) {
			if (selectedElement.classList.contains(className)) {
				hasClass = true;
			}
		});

		return hasClass;
	},
	html: function(selector, value) {
		let element = document.querySelector(selector);

		if (!element) {
			return false;
		}

		let html = value || element.innerHTML;

		if (typeof value !== 'undefined') {
			selectAllElements(selector, function(selectedElementKey, selectedElement) {
				selectedElement.innerHTML = value;
			});
		}

		return html;
	},
	loop: function(selector, callback) {
		selectAllElements(selector, function(selectedElementKey, selectedElement) {
			callback(selectedElementKey, selectedElement);
		});
	},
	removeAttribute: function(selector, attribute) {
		selectAllElements(selector, function(selectedElementKey, selectedElement) {
			if (selectedElement.hasAttribute(attribute)) {
				selectedElement.removeAttribute(attribute);
			}
		});
	},
	removeClass: function(selector, className) {
		selectAllElements(selector, function(selectedElementKey, selectedElement) {
			selectedElement.classList.remove(className);
		});
	},
	setAttribute: function(selector, attribute, value) {
		selectAllElements(selector, function(selectedElementKey, selectedElement) {
			selectedElement.setAttribute(attribute, value);
		});
	}
};
const onLoad = function(callback) {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 10) : callback();
};
const openFrame = function(frameName, frameSelector) {
	elements.addClass('footer, header, main', 'hidden');
	elements.removeClass(frameSelector, 'hidden');
	window.scroll(0, 0);
};
const processItemList = function(itemListName, callback) {
	let itemListParameters = apiRequestParameters.current[itemListName];

	if (apiRequestParameters.current[itemListName].initial === true) {
		let itemListData = '<div class="hidden item-container item-processing-container"></div>';
		itemListData += '<div class="item-container item-configuration-container">';
		itemListData += '<div class="item">';
		itemListData += '<div class="item-configuration">';
		itemListData += '<div class="item-controls-container controls-container scrollable">';
		itemListData += '<div class="item-header">';
		itemListData += '<div class="align-right">';
		itemListData += '<span class="pagination" current_page="' + itemListParameters.page + '" results="' + itemListParameters.resultsPerPage + '">';
		itemListData += '<span class="align-left hidden item-controls results">';
		itemListData += '<span class="first-result"></span> - <span class="last-result"></span> of <span class="total-results"></span>';
		itemListData += '</span>';
		itemListData += '<span class="align-left button icon previous"></span>';
		itemListData += '<span class="align-left button icon next"></span>';
		itemListData += '</span>';
		itemListData += '</div>';

		if (
			typeof itemListParameters.options === 'object' &&
			itemListParameters.options
		) {
			itemListData += '<div class="align-left hidden item-controls">';

			for (let optionKey in itemListParameters.options) {
				let option = itemListParameters.options[optionKey];
				itemListData += '<' + option.tag;

				if (
					typeof option.attributes === 'object' &&
					option.attributes
				) {
					for (let attributeKey in option.attributes) {
						let attribute = option.attributes[attributeKey];
						itemListData += ' ' + attribute.name;

						if (typeof attribute.value !== 'undefined') {
							itemListData += '="' + attribute.value + '"';
						}
					}
				}

				itemListData += '></' + option.tag + '>';
			}

			itemListData += '</div>';
		}

		itemListData += '<div class="clear"></div>';
		itemListData += '<p class="hidden item-controls no-margin-bottom">';
		itemListData += '<span class="checked-container">';
		itemListData += '<span class="total-checked">0</span> of <span class="total-results"></span> selected.</span>';
		itemListData += '<a class="item-action hidden" href="javascript:void(0);" index="all" status="1"><span class="action">Select</span> all results</a>';
		itemListData += '<span class="clear"></span>';
		itemListData += '</p>';
		itemListData += '<div class="additional-item-controls"></div>';

		if (
			typeof itemListParameters.messages === 'object' &&
			itemListParameters.messages
		) {
			itemListData += '<div class="clear"></div>';

			for (let messageKey in itemListParameters.messages) {
				let message = itemListParameters.messages[messageKey];
				itemListData += '<div class="message-container ' + messageKey + '">';

				if (typeof message !== 'undefined') {
					itemListData += message;
				}

				itemListData += '</div>';
			}
		}

		itemListData += '</div>';
		itemListData += '</div>';
		itemListData += '<div class="item-body">';
		itemListData += '<div class="items" previous_checked="0"></div>';
		itemListData += '</div>';
		elements.html(itemListParameters.selector, itemListData);
	}

	let itemListGrid = apiRequestParameters.current.items[itemListParameters.table] || [];
	let itemListGridCount = itemListGrid.length;
	const itemToggle = function(itemListItem) {
		let previousChecked = elements.getAttribute(itemListParameters.selector + ' .items', 'previous_checked');
		elements.setAttribute(itemListParameters.selector + ' .items', 'current_checked', itemListItem.getAttribute('index'));
		processItemListGrid(window.event.shiftKey ? range(previousChecked, itemListItem.getAttribute('index')) : [itemListItem.getAttribute('index')], window.event.shiftKey ? +elements.getAttribute(itemListParameters.selector + ' .checkbox[index="' + previousChecked + '"]', 'checked') !== 0 : +itemListItem.getAttribute('checked') === 0);
		elements.setAttribute(itemListParameters.selector + ' .items', 'previous_checked', itemListItem.getAttribute('index'));
	};
	const itemAll = elements.get(itemListParameters.selector + ' .item-action[index="all"]');
	const itemAllVisible = elements.get(itemListParameters.selector + ' .checkbox[index="all-visible"]');
	const itemToggleAllVisible = function(item) {
		elements.setAttribute(itemListParameters.selector + ' .items', 'current_checked', 0);
		elements.setAttribute(itemListParameters.selector + ' .items', 'previous_checked', 0);
		processItemListGrid(range(0, selectAllElements(itemListParameters.selector + ' .items .checkbox').length - 1), +item.getAttribute('checked') === 0);
	};
	const processItemListGrid = function(itemListIndexes, itemState) {
		let itemListCount = 0;
		const itemListGridLineSizeMaximum = +('1' + repeat(Math.min(elements.html(itemListParameters.selector + ' .total-results').length, 4), '0'));
		const itemListPageResultCount = (+elements.html(itemListParameters.selector + ' .last-result') - +elements.html(itemListParameters.selector + ' .first-result') + 1);
		const itemListTotalResults = +elements.html(itemListParameters.selector + ' .total-results');
		const itemListGridLineSize = function(key) {
			return Math.min(itemListGridLineSizeMaximum, itemListTotalResults - (key * itemListGridLineSizeMaximum)).toString();
		};
		const processItemListGridSelection = function(item) {
			let keyIndexes = range(0, Math.floor(itemListTotalResults / itemListGridLineSizeMaximum));
			elements.html('.total-checked', (selectionStatus = +item.getAttribute('status')) ? itemListTotalResults : 0);
			keyIndexes.map(function(key) {
				itemListGrid[key] = selectionStatus + itemListGridLineSize(key);
			});
			itemListGrid = selectionStatus ? itemListGrid : [];
			processItemListGrid(range(0, selectAllElements(itemListParameters.selector + ' .items .checkbox').length - 1));
		};

		if (
			(
				typeof itemListIndexes[1] === 'number' &&
				itemListIndexes[1] < 0
			) ||
			(
				!itemAll &&
				!itemAllVisible
			)
		) {
			return;
		}

		if (!itemListGrid.length) {
			elements.html('.total-checked', 0);
		}

		itemListIndexes.map(function(itemIndex) {
			let encodeCount = 1;
			let encodedListGridLineItems = [];
			let index = ((itemListParameters.page * itemListParameters.resultsPerPage) - itemListParameters.resultsPerPage) + +itemIndex;
			let item = elements.get(itemListParameters.selector + ' .checkbox[index="' + itemIndex + '"]');
			let key = Math.floor(index / itemListGridLineSizeMaximum);

			if (!itemListGrid[key]) {
				itemListGrid[key] = repeat(itemListGridLineSize(key), '0');
			} else {
				itemListGrid[key] = itemListGrid[key].split('_');
				itemListGrid[key].map(function(itemStatus, itemStatusIndex) {
					itemStatusCount = itemStatus.substr(1);
					itemStatus = itemStatus.substr(0, 1);
					itemListGrid[key][itemStatusIndex] = repeat(itemStatusCount, itemStatus);
				});
				itemListGrid[key] = itemListGrid[key].join("");
			}

			const itemListGridLineIndex = index - (key * itemListGridLineSizeMaximum);

			if (typeof itemState === 'boolean') {
				itemListGrid[key] = itemListGrid[key].substr(0, itemListGridLineIndex) + +itemState + itemListGrid[key].substr(itemListGridLineIndex + Math.max(1, ('' + +itemState).length))
			}

			itemListGrid[key] = itemListGrid[key].split("");
			itemListGrid[key].map(function(itemStatus, itemStatusIndex) {
				if (itemStatus != itemListGrid[key][itemStatusIndex + 1]) {
					encodedListGridLineItems.push(itemStatus + encodeCount);
					encodeCount = 0;
				}

				encodeCount++;
			});
			item.setAttribute('checked', +itemListGrid[key][itemListGridLineIndex]);
			itemListGrid[key] = encodedListGridLineItems.join('_');
		});
		range(0, itemListPageResultCount - 1).map(function(itemIndex) {
			if (+(elements.getAttribute(itemListParameters.selector + ' .checkbox[index="' + itemIndex + '"]', 'checked'))) {
				itemListCount++;
			}
		});

		if (typeof itemState === 'boolean') {
			elements.html('.total-checked', +elements.html('.total-checked') + (itemListCount - itemListGridCount));
		}

		itemAll.classList.add('hidden');
		itemAll.removeEventListener('click', itemAll.clickListener);
		itemAll.clickListener = function() {
			processItemListGridSelection(itemAll);
		};
		itemAll.addEventListener('click', itemAll.clickListener);
		itemAllVisible.setAttribute('checked', +(allVisibleChecked = (itemListCount === itemListPageResultCount)));
		itemAllVisible.removeEventListener('click', itemAllVisible.clickListener);
		itemAllVisible.clickListener = function() {
			itemToggleAllVisible(itemAllVisible);
		};
		itemAllVisible.addEventListener('click', itemAllVisible.clickListener);

		if (
			itemListPageResultCount != itemListTotalResults &&
			(
				(
					allVisibleChecked &&
					+elements.html('.total-checked') < itemListTotalResults
				) ||
				+elements.html('.total-checked') === itemListTotalResults
			)
		) {
			itemAll.classList.remove('hidden');
			itemAll.querySelector('.action').innerText = (selectionStatus = +(+elements.html('.total-checked') === itemListTotalResults)) ? 'Unselect' : 'Select';
			itemAll.setAttribute('status', +(selectionStatus === 0));
		}

		processWindowEvents('resize');
		+elements.html('.total-checked') ? elements.removeClass(itemListParameters.selector + ' span.icon[item-function]', 'hidden') : elements.addClass(itemListParameters.selector + ' span.icon[item-function]', 'hidden');
		itemListGridCount = itemListCount;

		if (itemListTotalResults === +elements.html('.total-checked')) {
			elements.addClass(itemListParameters.selector + ' span.icon[item-function][process="downgrade"]', 'hidden');
		}

		var mergeRequestParameters = {
			items: {}
		};
		mergeRequestParameters.items[itemListParameters.table] = itemListGrid;
		api.setRequestParameters(mergeRequestParameters, true);
	};
	elements.addClass(itemListParameters.selector + ' .item-controls, ' + itemListParameters.selector + ' .items', 'hidden');
	elements.setAttribute(itemListParameters.selector + ' .pagination .next', 'page', 0);
	elements.setAttribute(itemListParameters.selector + ' .pagination .previous', 'page', 0);

	if (
		!itemListParameters.page ||
		(
			apiRequestParameters.current.action == 'search' &&
			apiRequestParameters.previous.action == 'fetch'
		)
	) {
		itemListParameters.page = 1;
	}

	api.setRequestParameters({
		action: apiRequestParameters.current.action,
		limit: itemListParameters.resultsPerPage,
		offset: ((itemListParameters.page * itemListParameters.resultsPerPage) - itemListParameters.resultsPerPage),
		table: itemListParameters.table,
		url: apiRequestParameters.current.settings.baseUrl + 'api/' + itemListParameters.table
	});

	if (apiRequestParameters.current.orderId) {
		api.setRequestParameters({
			conditions: {
				orderId: apiRequestParameters.current.orderId
			}
		});
	}

	var mergeRequestParameters = {
		items: {}
	};
	mergeRequestParameters.items[itemListParameters.table] = itemListGrid;
	api.setRequestParameters(mergeRequestParameters, true);
	api.sendRequest(function(response) {
		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (
			typeof response.items[itemListParameters.table] === 'object' &&
			response.items[itemListParameters.table].length === 0
		) {
			var mergeRequestParameters = {
				items: {}
			};
			mergeRequestParameters.items[itemListParameters.table] = itemListGrid = [];
			api.setRequestParameters(mergeRequestParameters, true);
			itemListGridCount = 0;
		}

		if (typeof itemListParameters.callback === 'function') {
			itemListParameters.callback(response, itemListParameters);
		}

		let lastResult = itemListParameters.page * itemListParameters.resultsPerPage;
		elements.html(itemListParameters.selector + ' .first-result', itemListParameters.page === 1 ? itemListParameters.page : ((itemListParameters.page * itemListParameters.resultsPerPage) - itemListParameters.resultsPerPage) + 1);
		elements.html(itemListParameters.selector + ' .last-result', lastResult >= response.count ? response.count : lastResult);
		elements.html(itemListParameters.selector + ' .total-results', response.count);
		elements.setAttribute(itemListParameters.selector + ' .pagination', 'current_page', itemListParameters.page);
		elements.setAttribute(itemListParameters.selector + ' .pagination .next', 'page', +elements.html('.item-configuration .last-result') < response.count ? itemListParameters.page + 1 : 0);
		elements.setAttribute(itemListParameters.selector + ' .pagination .previous', 'page', itemListParameters.page <= 0 ? 0 : itemListParameters.page - 1);

		if (apiRequestParameters.current[itemListName].initial === true) {
			elements.loop(itemListParameters.selector + ' .pagination .button', function(index, element) {
				element.addEventListener('click', function(element) {
					if ((page = +element.target.getAttribute('page')) > 0) {
						var mergeRequestParameters = {};
						mergeRequestParameters[itemListName] = {};
						mergeRequestParameters[itemListName].page = page;
						api.setRequestParameters(mergeRequestParameters, true);
						elements.html('.message-container.status', '<p class="message">Loading</p>');
						processItemList(itemListName);
					}
				});
			});
		}

		elements.loop('.item-configuration .items .checkbox', function(index, item) {
			item.removeEventListener('click', item.clickListener);
			item.clickListener = function() {
				itemToggle(item);
			};
			item.addEventListener('click', item.clickListener);
		});
		elements.removeClass(itemListParameters.selector + ' .item-controls, .items', 'hidden');

		if (response.tokens[itemListParameters.table] !== 'undefined') {
			var mergeRequestParameters = {
				tokens: []
			};
			mergeRequestParameters.tokens[itemListParameters.table] = response.tokens[itemListParameters.table];
			api.setRequestParameters(mergeRequestParameters, true);
		}

		processItemListGrid(range(0, response.data.length - 1));

		if (typeof callback === 'function') {
			callback(response, itemListParameters);
		}

		if (apiRequestParameters.current[itemListName].initial === true) {
			var mergeRequestParameters = {};
			mergeRequestParameters[itemListName] = {
				initial: false
			};
			api.setRequestParameters(mergeRequestParameters, true);
			elements.addScrollable('.item-controls-container.scrollable', function(element) {
				if (element.details.width) {
					element.parentNode.querySelector('.item-body').setAttribute('style', 'padding-top: ' + (element.parentNode.querySelector('.item-header').clientHeight + 2) + 'px');
					element.setAttribute('style', 'width: ' + element.details.width + 'px;');
				}
			});
			selectAllElements(itemListParameters.selector + ' .button.frame-button', function(selectedElementKey, selectedElement) {
				selectedElement.addEventListener('click', function() {
					processMethodForm(selectedElement);
				});
			});
		}

		elements.html('.message-container.status', '');
		processWindowEvents('resize');
	});
};
const processWindowEvents = function(event) {
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
const range = function(low, high, step) {
	let response = [];
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
const repeat = function(count, pattern) {
	let response = '';

	while (count > 1) {
		if (count & 1) {
			response += pattern;
		}

		count >>= 1;
		pattern += pattern;
	}

	return response + (count < 1 ? '' : pattern);
};
const selectAllElements = function(selector, callback) {
	let response = [];
	let nodeList = document.querySelectorAll(selector);

	if (nodeList.length) {
		response = Object.entries(nodeList);
	}

	if (typeof callback === 'function') {
		for (let selectedElementKey in response) {
			callback(selectedElementKey, response[selectedElementKey][1]);
		}
	}

	return response;
};
const unique = function(value, index, self) {
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

		let response = [];

		for (let objectKey in object) {
			if (object.hasOwnProperty(objectKey)) {
				response.push([objectKey, object[objectKey]]);
			}
		}

		return response;
	};
}

onLoad(function() {
	if (document.querySelector('.hidden.keys')) {
		let keys = JSON.parse(document.querySelector('.hidden.keys').innerHTML);
		Object.defineProperty(keys, 'users', {
			configurable: true,
			value: keys.users + JSON.stringify(browserDetails())
		});
		api.setRequestParameters({
			keys: keys
		});
	}

	if (document.querySelector('.hidden.settings')) {
		api.setRequestParameters({
			settings: JSON.parse(document.querySelector('.hidden.settings').innerHTML)
		});
	}
});
