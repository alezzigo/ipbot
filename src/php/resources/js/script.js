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
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 1) : callback();
};
var processItems = (currentPage = 1) => {
	var items = document.querySelector('.proxy-configuration .proxy-table'),
		pagination = document.querySelector('.pagination');
	var resultsPerPage = +pagination.getAttribute('results');
	var itemToggle = (checkbox) => {
		items.setAttribute('current_checked', checkbox.target.getAttribute('index'));
		processItemGrid(window.event.shiftKey ? range(items.getAttribute('previous_checked'), checkbox.target.getAttribute('index')) : [checkbox.target.getAttribute('index')], window.event.shiftKey ? +document.querySelector('.checkbox[index="' + items.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +checkbox.target.getAttribute('checked') === 0);
		items.setAttribute('previous_checked', checkbox.target.getAttribute('index'));
	};
	var itemToggleAllVisible = (checkbox) => {
		items.setAttribute('current_checked', 0);
		items.setAttribute('previous_checked', 0);
		processItemGrid(range(0, selectAllElements('tr .checkbox').length - 1), +checkbox.target.getAttribute('checked') === 0);
	};
	var processItemGrid = (itemIndexes, itemState) => {
		var itemCount = 0;
			itemGridLineSize = +('1' + repeat(Math.min(elements.html('.total-results').length, 4), '0'));
		itemIndexes.map((itemIndex) => {
			var index = ((currentPage * resultsPerPage) - resultsPerPage) + +itemIndex,
				item = document.querySelector('.checkbox[index="' + itemIndex + '"]'),
				serializeCount = 1;
			var key = Math.floor(index / itemGridLineSize),
				serializedGridLineItems = [];
			var itemGridLineIndex = index - (key * itemGridLineSize);

			if (!itemGrid[key]) {
				itemGrid[key] = repeat(Math.min(itemGridLineSize, +elements.html('.total-results') - (key * itemGridLineSize)), '0');
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

		range(0, resultsPerPage - 1).map((itemIndex) => {
			var item = document.querySelector('.checkbox[index="' + itemIndex + '"]');

			if (+(item.getAttribute('checked'))) {
				itemCount++;
			}
		});

		if (typeof itemState === 'boolean') {
			elements.html('.total-checked', +elements.html('.total-checked') + (itemCount - itemGridCount));
		}

		+elements.html('.total-checked') ? elements.removeClass('span.icon[proxy-function]', 'hidden') : elements.addClass('span.icon[proxy-function]', 'hidden');
		document.querySelector('.checkbox[index="all-visible"]').setAttribute('checked', +(itemCount === resultsPerPage));
		itemGridCount = itemCount;
	};
	pagination.querySelector('.next').setAttribute('page', 0);
	pagination.querySelector('.previous').setAttribute('page', 0);
	items.innerHTML = '<p>Loading ...</p>';
	requestParameters.current.conditions = {
		order_id: document.querySelector('input[name="order_id"]').value
	},
		requestParameters.current.grid = itemGrid,
		requestParameters.current.limit = resultsPerPage,
		requestParameters.current.offset = ((currentPage * resultsPerPage) - resultsPerPage);
	sendRequest(requestParameters, (response) => {
		response = JSON.parse(response.target.response);

		if (response.code !== 200) {
			alert('There was an error processing your request.' + (response.message ? ' ' + response.message + '.' : ''));
			return;
		}

		items.innerHTML = '<table class="table"></table>';
		response.data.map((item, index) => {
			items.querySelector('table').innerHTML += '<tr page="' + currentPage + '" proxy_id="' + item.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" proxy_id="' + item.id + '"></span></td><td><span class="details-container"><span class="details">' + item.status + ' Proxy IP ' + item.ip + ' Location ' + item.city + ', ' + item.region + ' ' + item.country_code + ' <span class="icon-container"><img src="../../resources/images/icons/flags/' + item.country_code.toLowerCase() + '.png" class="flag" alt="' + item.country_code + ' flag"></span> ISP ' + item.asn + ' Timezone ' + item.timezone + ' HTTP + HTTPS Port ' + (item.disable_http == 1 ? 'Disabled' : '80') + ' Whitelisted IPs ' + (item.whitelisted_ips ? '<textarea>' + item.whitelisted_ips + '</textarea>' : 'N/A') + ' Username ' + (item.username ? item.username : 'N/A') + ' Password ' + (item.password ? item.password : 'N/A') + '</span></span><span class="table-text">' + item.ip + '</span></td>';
		});
		elements.html('.total-results', response.count);
		elements.html('.first-result', currentPage === 1 ? currentPage : ((currentPage * resultsPerPage) - resultsPerPage) + 1);
		elements.html('.last-result', (lastResult = currentPage * resultsPerPage) >= response.count ? response.count : lastResult);
		pagination.setAttribute('current_page', currentPage);
		pagination.querySelector('.next').setAttribute('page', +elements.html('.last-result') < response.count ? currentPage + 1 : 0);
		pagination.querySelector('.previous').setAttribute('page', currentPage <= 0 ? 0 : currentPage - 1);
		elements.loop('.proxy-configuration tr', (index, row) => {
			var item = row.querySelector('.checkbox');
			item.removeEventListener('click', item.listener);
			item.listener = itemToggle;
			item.addEventListener('click', itemToggle);
		});
		var itemAllVisible = document.querySelector('.checkbox[index="all-visible"]');
		itemAllVisible.removeEventListener('click', itemAllVisible.listener);
		itemAllVisible.listener = itemToggleAllVisible;
		itemAllVisible.addEventListener('click', itemToggleAllVisible);
		processItemGrid(range(0, response.data.length - 1));
		requestParameters.previous = requestParameters.current;
	});
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
		order: 'modified DESC',
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
		callback(response);
	};
};
var unique = (value, index, self) => {
	return self.indexOf(value) === index;
};
var windowEvents = {
	onscroll: [],
	onresize: []
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
	elements.removeClass('.proxy-configuration', 'hidden');
	elements.addClass('.loading', 'hidden');

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
					element[1].parentNode.querySelector('.item-body').setAttribute('style', 'padding-top: ' + (element[1].querySelector('.item-header').clientHeight + 20) + 'px');
					element[1].setAttribute('style', 'max-width: ' + elementContainerDetails.width + 'px;');
				}

				element[1].setAttribute('scrolling', +(window.pageYOffset > (elementContainerDetails.top + window.pageYOffset)));
			};
			windowEvents.onresize.push(scrollEvent);
			windowEvents.onscroll.push(scrollEvent);
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
	Object.entries(windowEvents).map((windowEvents) => {
		window[windowEvents[0]] = () => {
			windowEvents[1].map((windowEvent) => {
				windowEvent();
			});
		};
	});
});
