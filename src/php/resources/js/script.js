'use_strict';

var closeWindows = () => {
	document.querySelector('main').classList.remove('hidden');
	elements.addClass('.window-container', 'hidden');
	requestParameters.current.data = {};
};
var elements = {
	addClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.add(className);
		});
	},
	loop: (selector, callback) => {
		selectAllElements(selector).map((element) => {
			callback(element[0], element[1]);
		});
	},
	removeClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.remove(className);
		});
	},
	html: (selector, value = null) => {
		return selectAllElements(selector).map((element) => {
			return value !== null ? element[1].innerHTML = value : element[1].innerHTML;
		})[0];
	},
	setAttribute: (selector, attribute, value) => {
		selectAllElements(selector).map((element) => {
			element[1].setAttribute(attribute, value);
		});
	}
};
var itemGrid = [],
	itemGridCount = 0;
var onLoad = (callback) => {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 10) : callback();
};
var processItems = (currentPage = 1) => {
	var items = document.querySelector('.item-configuration .item-table'),
		pagination = document.querySelector('.pagination');
	var resultsPerPage = +pagination.getAttribute('results');
	var itemToggle = (item) => {
		items.setAttribute('current_checked', item.getAttribute('index'));
		processItemGrid(window.event.shiftKey ? range(items.getAttribute('previous_checked'), item.getAttribute('index')) : [item.getAttribute('index')], window.event.shiftKey ? +document.querySelector('.checkbox[index="' + items.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +item.getAttribute('checked') === 0);
		items.setAttribute('previous_checked', item.getAttribute('index'));
	};
	var itemAllVisible = document.querySelector('.checkbox[index="all-visible"]'),
		itemToggleAllVisible = (item) => {
			items.setAttribute('current_checked', 0);
			items.setAttribute('previous_checked', 0);
			processItemGrid(range(0, selectAllElements('tr .checkbox').length - 1), +item.getAttribute('checked') === 0);
		};
	var processItemGrid = (itemIndexes, itemState) => {
		var itemCount = 0;
			itemGridLineSizeMaximum = +('1' + repeat(Math.min(elements.html('.total-results').length, 4), '0')),
			pageResultCount = (+elements.html('.last-result') - +elements.html('.first-result') + 1),
			totalResults = +elements.html('.total-results');
		var itemGridLineSize = (key) => {
			return Math.min(itemGridLineSizeMaximum, totalResults - (key * itemGridLineSizeMaximum)).toString();
		}
		var processItemGridSelection = (item) => {
			var keyIndexes = range(0, Math.floor(totalResults / itemGridLineSizeMaximum));
			elements.html('.total-checked', (selectionStatus = +item.getAttribute('status')) ? totalResults : 0);
			item.querySelector('.action').innerText = (selectionStatus ? 'Unselect' : 'Select');
			item.setAttribute('status', +(selectionStatus === 0));
			keyIndexes.map((key) => {
				itemGrid[key] = selectionStatus + itemGridLineSize(key);
			});
			itemGrid = selectionStatus ? itemGrid : [];
			processItemGrid(range(0, selectAllElements('tr .checkbox').length - 1));
		};

		if (!itemGrid.length) {
			elements.html('.total-checked', 0);
		}

		itemIndexes.map((itemIndex) => {
			var index = ((currentPage * resultsPerPage) - resultsPerPage) + +itemIndex,
				item = document.querySelector('.checkbox[index="' + itemIndex + '"]'),
				serializeCount = 1;
			var key = Math.floor(index / itemGridLineSizeMaximum),
				serializedGridLineItems = [];
			var itemGridLineIndex = index - (key * itemGridLineSizeMaximum);

			if (!itemGrid[key]) {
				itemGrid[key] = repeat(itemGridLineSize(key), '0');
			} else {
				itemGrid[key] = itemGrid[key].split('_');
				itemGrid[key].map((itemStatus, itemStatusIndex) => {
					itemStatusCount = itemStatus.substr(1);
					itemStatus = itemStatus.substr(0, 1);
					itemGrid[key][itemStatusIndex] = repeat(itemStatusCount, itemStatus);
				});
				itemGrid[key] = itemGrid[key].join("");
			}

			if (typeof itemState === 'boolean') {
				itemGrid[key] = replaceCharacter(itemGrid[key], itemGridLineIndex, +itemState);
			}

			itemGrid[key] = itemGrid[key].split("");
			itemGrid[key].map((itemStatus, itemStatusIndex) => {
				if (itemStatus != itemGrid[key][itemStatusIndex + 1]) {
					serializedGridLineItems.push(itemStatus + serializeCount);
					serializeCount = 0;
				}

				serializeCount++;
			});
			item.setAttribute('checked', +itemGrid[key][itemGridLineIndex]);
			itemGrid[key] = serializedGridLineItems.join('_');
		});

		range(0, pageResultCount - 1).map((itemIndex) => {
			var item = document.querySelector('.checkbox[index="' + itemIndex + '"]');

			if (+(item.getAttribute('checked'))) {
				itemCount++;
			}
		});

		if (typeof itemState === 'boolean') {
			elements.html('.total-checked', +elements.html('.total-checked') + (itemCount - itemGridCount));
		}

		var itemAll = document.querySelector('.item-action[index="all"]');
		itemAll.classList.add('hidden');
		itemAll.removeEventListener('click', itemAll.listener);
		itemAll.listener = () => {
			processItemGridSelection(itemAll)
		};
		itemAll.addEventListener('click', itemAll.listener);
		itemAllVisible.setAttribute('checked', +(allVisibleChecked = (itemCount === pageResultCount)));
		itemAllVisible.removeEventListener('click', itemAllVisible.listener);
		itemAllVisible.listener = () => {
			itemToggleAllVisible(itemAllVisible);
		};
		itemAllVisible.addEventListener('click', itemAllVisible.listener);

		if (
			pageResultCount != totalResults &&
			(
				(
					allVisibleChecked &&
					+elements.html('.total-checked') < totalResults
				) ||
				+elements.html('.total-checked') === totalResults
			)
		) {
			itemAll.classList.remove('hidden');
		}

		processWindowEvents(windowEvents, 'resize');
		+elements.html('.total-checked') ? elements.removeClass('span.icon[item-function]', 'hidden') : elements.addClass('span.icon[item-function]', 'hidden');
		itemGridCount = itemCount;
	};
	pagination.querySelector('.next').setAttribute('page', 0);
	pagination.querySelector('.previous').setAttribute('page', 0);
	items.innerHTML = '<p class="message no-margin-bottom">Loading ...</p>';
	requestParameters.current.conditions = {
		order_id: document.querySelector('input[name="order_id"]').value
	},
		requestParameters.current.grid = itemGrid,
		requestParameters.current.limit = resultsPerPage,
		requestParameters.current.offset = ((currentPage * resultsPerPage) - resultsPerPage);
	sendRequest(requestParameters, (response) => {
		items.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');

		if (response.code !== 200) {
			return;
		}

		items.innerHTML += '<table class="table"></table>';
		response.data.map((item, index) => {
			items.querySelector('table').innerHTML += '<tr page="' + currentPage + '" proxy_id="' + item.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" proxy_id="' + item.id + '"></span></td><td><span class="details-container"><span class="details">' + item.status + ' Proxy IP ' + item.ip + ' Location ' + item.city + ', ' + item.region + ' ' + item.country_code + ' <span class="icon-container"><img src="../../resources/images/icons/flags/' + item.country_code.toLowerCase() + '.png" class="flag" alt="' + item.country_code + ' flag"></span> ISP ' + item.asn + ' Timezone ' + item.timezone + ' HTTP + HTTPS Port ' + (item.disable_http == 1 ? 'Disabled' : '80') + ' Whitelisted IPs ' + (item.whitelisted_ips ? '<textarea>' + item.whitelisted_ips + '</textarea>' : 'N/A') + ' Username ' + (item.username ? item.username : 'N/A') + ' Password ' + (item.password ? item.password : 'N/A') + '</span></span><span class="table-text">' + item.ip + '</span></td>';
		});
		elements.html('.first-result', currentPage === 1 ? currentPage : ((currentPage * resultsPerPage) - resultsPerPage) + 1);
		elements.html('.last-result', (lastResult = currentPage * resultsPerPage) >= response.count ? response.count : lastResult);
		elements.html('.total-results', response.count);
		pagination.setAttribute('current_page', currentPage);
		pagination.querySelector('.next').setAttribute('page', +elements.html('.last-result') < response.count ? currentPage + 1 : 0);
		pagination.querySelector('.previous').setAttribute('page', currentPage <= 0 ? 0 : currentPage - 1);
		elements.loop('.item-configuration tr', (index, row) => {
			var item = row.querySelector('.checkbox');
			item.removeEventListener('click', item.listener);
			item.listener = () => {
				itemToggle(item);
			};
			item.addEventListener('click', item.listener);
		});
		itemGrid = response.grid;
		requestParameters.current.token = response.token;
		processItemGrid(range(0, response.data.length - 1));
		requestParameters.previous = requestParameters.current;
	});
};
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
	return string.substr(0, index) + character + string.substr(index + ('' + character).length);
};
var requestParameters = {
	current: {
		action: 'find',
		sort: {
			field: 'modified',
			order: 'DESC'
		},
		table: 'proxies',
	},
	url: '/src/php/views/api.php'
};
var selectAllElements = (selector) => {
	return Object.entries(document.querySelectorAll(selector));
};
var sendRequest = (requestParameters, callback) => {
	var request = new XMLHttpRequest();
	request.open('POST', requestParameters.url, true);
	request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	request.send('json=' + JSON.stringify(requestParameters));
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

onLoad(() => {
	if (document.querySelector('.pagination')) {
		processItems();
		selectAllElements('.pagination .button').map((element) => {
			element[1].addEventListener('click', (element) => {
				if ((page = +element.target.getAttribute('page')) > 0) {
					processItems(page);
				}
			});
		});
	}

	if ((scrollableElements = selectAllElements('.scrollable')).length) {
		scrollableElements.map((element) => {
			var scrollEvent = () => {
				var elementContainerDetails = element[1].parentNode.getBoundingClientRect();

				if (elementContainerDetails.width) {
					element[1].parentNode.querySelector('.item-body').setAttribute('style', 'padding-top: ' + (element[1].querySelector('.item-header').clientHeight + 21) + 'px');
					element[1].setAttribute('style', 'max-width: ' + elementContainerDetails.width + 'px;');
				}

				element[1].setAttribute('scrolling', +(window.pageYOffset > (elementContainerDetails.top + window.pageYOffset)));
			};
			windowEvents.resize.push(scrollEvent);
			windowEvents.scroll.push(scrollEvent);
		});
	}

	selectAllElements('.button.window').map((element) => {
		element[1].addEventListener('click', (element) => {
			document.querySelector('main').classList.add('hidden');
			elements.removeClass('.window-container[window="' + element.target.getAttribute('window') + '"]', 'hidden');
		});
	});
	selectAllElements('.window .button.close').map((element) => {
		element[1].addEventListener('click', (element) => {
			closeWindows();
		});
	});
	selectAllElements('.window .button.submit').map((element) => {
		element[1].addEventListener('click', (element) => {
			var action = element.target.getAttribute('form');
			var form = '.window-container[window="' + action + '"]';
			closeWindows();
			elements.loop(form + ' input, ' + form + ' select, ' + form + ' textarea', (index, element) => {
				requestParameters.current.data[element.getAttribute('name')] = element.value;
			});
			elements.loop(form + ' .checkbox', (index, element) => {
				requestParameters.current.data[element.getAttribute('name')] = +element.getAttribute('checked');
			});
			requestParameters.current.action = action;
			processItems();
		});
	});
	selectAllElements('.window .checkbox, .window label.custom-checkbox-label').map((element) => {
		element[1].addEventListener('click', (element) => {
			var hiddenField = document.querySelector('div[field="' + element.target.getAttribute('name') + '"]'),
				item = document.querySelector('.checkbox[name="' + element.target.getAttribute('name') + '"]');
			item.setAttribute('checked', +!+item.getAttribute('checked'));
			hiddenField ? (hiddenField.classList.contains('hidden') ? hiddenField.classList.remove('hidden') : hiddenField.classList.add('hidden')) : null;
		});
	});
	processWindowEvents(windowEvents);
});
