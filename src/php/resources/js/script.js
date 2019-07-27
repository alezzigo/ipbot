'use_strict';

var closeWindows = () => {
	document.querySelector('main').classList.remove('hidden');
	elements.addClass('.window-container', 'hidden');
	requestParameters.action = 'find';
	requestParameters.table = 'proxies';
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
var itemGrid = [],
	itemGridCount = 0;
var onLoad = (callback) => {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 10) : callback();
};
var processCopy = (action, currentWindow) => {
	var previousAction = requestParameters.action;
	var processCopyFormat = () => {
		requestParameters.action = action;
		elements.addClass(currentWindow + ' .copy', 'hidden');
		elements.removeClass(currentWindow + ' .loading', 'hidden');
		elements.setAttribute(currentWindow + ' .list-format select', 'disabled', 'disabled');
		elements.loop(currentWindow + ' input, ' + currentWindow + ' select, ' + currentWindow + ' textarea', (index, element) => {
			requestParameters.data[element.getAttribute('name')] = element.value;
		});
		requestParameters.items[requestParameters.table] = itemGrid;
		sendRequest((response) => {
			document.querySelector(currentWindow + ' textarea[name="' + action + '"]').value = response.data;
			elements.addClass(currentWindow + ' .loading', 'hidden');
			elements.removeClass(currentWindow + ' .copy', 'hidden');
			elements.removeAttribute(currentWindow + ' .list-format select', 'disabled');
			requestParameters.action = previousAction;
		});
	};
	elements.loop(currentWindow + ' .list-format select', (index, element) => {
		element.removeEventListener('change', element.changeListener);
		element.changeListener = () => {
			processCopyFormat();
		}
		element.addEventListener('change', element.changeListener);
	});

	var itemsCopy = document.querySelector(currentWindow + ' .button.' + action);
	itemsCopy.removeEventListener('click', itemsCopy.clickListener);
	itemsCopy.clickListener = () => {
		document.querySelector('[name="copy"]').select();
		document.execCommand(action);
	};
	itemsCopy.addEventListener('click', itemsCopy.clickListener);
	processCopyFormat();
};
var processGroup = (action, currentWindow) => {
	var groupGrid = {},
		groupNameField = document.querySelector(currentWindow + ' .group-name-field'),
		groupNameButton = document.querySelector(currentWindow + ' .group-name-button'),
		groupTable = document.querySelector(currentWindow + ' .group-table'),
		orderId = document.querySelector('input[name="order_id"]').value;
	var processGroupGrid = (groupIndexes, groupState) => {
		groupIndexes.map((groupIndex) => {
			var group = document.querySelector(currentWindow + ' .checkbox[index="' + groupIndex + '"]');
			var groupId = group.getAttribute('group_id');
			group.setAttribute('checked', +groupState);
			groupGrid[action + groupId] = groupId;
		});
		requestParameters.items[requestParameters.table] = groupGrid;
	};
	var groupAdd = (groupName) => {
		requestParameters.action = action;
		requestParameters.data = {
			name: groupName,
			order_id: orderId
		};
		sendRequest((response) => {
			processGroupTable(response);
		});
	};
	var groupDelete = (button, row) => {
		var groupId = row.getAttribute('group_id');
		requestParameters.action = action;
		requestParameters.data = {
			id: [groupId]
		};
		sendRequest((response) => {
			delete groupGrid[action + groupId];
			processGroupTable(response);
		});
	};
	var groupEdit = (button, row) => {
		var processGroupEdit = (row) => {
			requestParameters.action = action;
			requestParameters.data = {
				id: row.getAttribute('group_id'),
				order_id: orderId,
				name: row.querySelector('.group-name-edit-field').value
			};
			sendRequest((response) => {
				processGroupTable(response);
			});
		}
		var originalRow = row.querySelector('.table-text').innerHTML;
		row.querySelector('.table-text').innerHTML = '<div class="field-group no-margin"><input class="group-name-edit-field no-margin" id="group-name-edit" name="group_name" type="text" value="' + row.querySelector('.view').innerText + '"><button class="button group-name-save-edit-button">Save</button><button class="button group-name-cancel-edit-button">Cancel</button></div>';
		row = document.querySelector(currentWindow + ' tbody tr[group_id="' + row.getAttribute('group_id') + '"]');
		var groupNameCancelEditButton = row.querySelector('.group-name-cancel-edit-button'),
			groupNameEditField = row.querySelector('.group-name-edit-field'),
			groupNameSaveEditButton = row.querySelector('.group-name-save-edit-button');
		groupNameCancelEditButton.removeEventListener('click', groupNameCancelEditButton.clickListener);
		groupNameEditField.removeEventListener('keydown', groupNameEditField.keydownListener);
		groupNameSaveEditButton.removeEventListener('click', groupNameSaveEditButton.clickListener);
		groupNameCancelEditButton.clickListener = () => {
			row.querySelector('.table-text').innerHTML = originalRow;
		};
		groupNameEditField.keydownListener = () => {
			if (event.key == 'Enter') {
				processGroupEdit(row);
			}
		};
		groupNameSaveEditButton.clickListener = () => {
			processGroupEdit(row);
		};
		groupNameCancelEditButton.addEventListener('click', groupNameCancelEditButton.clickListener);
		groupNameEditField.addEventListener('keydown', groupNameEditField.keydownListener);
		groupNameSaveEditButton.addEventListener('click', groupNameSaveEditButton.clickListener);
	};
	var groupToggle = (button) => {
		groupTable.setAttribute('current_checked', button.getAttribute('index'));
		processGroupGrid(window.event.shiftKey ? range(groupTable.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +document.querySelector(currentWindow + ' .checkbox[index="' + groupTable.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		groupTable.setAttribute('previous_checked', button.getAttribute('index'));
	};
	var groupView = (button, row) => {
		document.querySelector('.item-configuration .item-table').innerHTML = '<p class="message">Loading ...</p>';
		elements.addClass('.item-configuration .item-controls', 'hidden');
		closeWindows();
		requestParameters.action = 'search';
		requestParameters.data = {
			groups: [button.getAttribute('group_id')]
		};
		requestParameters.table = 'proxies';
		itemGrid = [];
		itemGridCount = 0;
		sendRequest((response) => {
			processItems();
		});
	};
	var processGroupTable = (response) => {
		groupTable.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');

		if (
			response.code !== 200 ||
			!response.data.length
		) {
			return;
		}

		groupTable.innerHTML += '<table class="table"><thead><th style="width: 35px;"></th><th>Group Name</th></thead><tbody></tbody></table>';
		response.data.map((group, index) => {
			groupTable.querySelector('table tbody').innerHTML += '<tr group_id="' + group.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" group_id="' + group.id + '"></span></td><td><span class="table-text"><a class="view" group_id="' + group.id + '" href="javascript:void(0);">' + group.name + '</a></span><span class="table-actions"><span class="button edit icon" group_id="' + group.id + '"></span><span class="button delete icon" group_id="' + group.id + '"></span></span></td>';
		});
		elements.loop(currentWindow + ' tbody tr', (index, row) => {
			var groupDeleteButton = row.querySelector('.delete'),
				groupEditButton = row.querySelector('.edit'),
				groupToggleButton = row.querySelector('.checkbox'),
				groupViewButton = row.querySelector('.view');
			groupDeleteButton.removeEventListener('click', groupDeleteButton.clickListener);
			groupEditButton.removeEventListener('click', groupEditButton.clickListener);
			groupToggleButton.removeEventListener('click', groupToggleButton.clickListener);
			groupViewButton.removeEventListener('click', groupViewButton.clickListener);
			groupDeleteButton.clickListener = () => {
				groupDelete(groupDeleteButton, row);
			};
			groupEditButton.clickListener = () => {
				groupEdit(groupEditButton, row);
			};
			groupToggleButton.clickListener = () => {
				groupToggle(groupToggleButton);
			};
			groupViewButton.clickListener = () => {
				groupView(groupViewButton);
			};
			groupDeleteButton.addEventListener('click', groupDeleteButton.clickListener);
			groupEditButton.addEventListener('click', groupEditButton.clickListener);
			groupToggleButton.addEventListener('click', groupToggleButton.clickListener);
			groupViewButton.addEventListener('click', groupViewButton.clickListener);
		});
		groupNameField.value = '';
		Object.entries(groupGrid).map((groupId) => {
			var group = document.querySelector(currentWindow + ' .checkbox[group_id="' + groupId[1] + '"]');
			processGroupGrid([group.getAttribute('index')], true);
		});
	};
	+elements.html('.total-checked') ? elements.removeClass(currentWindow + ' .submit', 'hidden') : elements.addClass(currentWindow + ' .submit', 'hidden');
	groupNameField.removeEventListener('keydown', groupNameField.keydownListener);
	groupNameButton.removeEventListener('click', groupNameButton.clickListener);
	groupNameField.keydownListener = (event) => {
		if (event.key == 'Enter') {
			groupAdd(groupNameField.value);
		}
	};
	groupNameButton.clickListener = () => {
		groupAdd(groupNameField.value);
	};
	groupNameField.addEventListener('keydown', groupNameField.keydownListener);
	groupNameButton.addEventListener('click', groupNameButton.clickListener);
	groupTable.innerHTML = '<p class="message no-margin-bottom">Loading ...</p>';
	requestParameters.action = 'find';
	requestParameters.sort.field = 'created';
	requestParameters.table = 'proxy_groups';
	sendRequest((response) => {
		processGroupTable(response);
	});
};
var processItems = (currentPage = 1) => {
	var items = document.querySelector('.item-configuration .item-table'),
		orderId = document.querySelector('input[name="order_id"]').value,
		pagination = document.querySelector('.item-configuration .pagination');
	var resultsPerPage = +pagination.getAttribute('results');
	var itemToggle = (item) => {
		items.setAttribute('current_checked', item.getAttribute('index'));
		processItemGrid(window.event.shiftKey ? range(items.getAttribute('previous_checked'), item.getAttribute('index')) : [item.getAttribute('index')], window.event.shiftKey ? +document.querySelector('.item-configuration .checkbox[index="' + items.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +item.getAttribute('checked') === 0);
		items.setAttribute('previous_checked', item.getAttribute('index'));
	};
	var itemAllVisible = document.querySelector('.item-configuration .checkbox[index="all-visible"]'),
		itemToggleAllVisible = (item) => {
			items.setAttribute('current_checked', 0);
			items.setAttribute('previous_checked', 0);
			processItemGrid(range(0, selectAllElements('.item-configuration tr .checkbox').length - 1), +item.getAttribute('checked') === 0);
		};
	var processItemGrid = (itemIndexes, itemState) => {
		var itemCount = 0;
			itemGridLineSizeMaximum = +('1' + repeat(Math.min(elements.html('.item-configuration .total-results').length, 4), '0')),
			pageResultCount = (+elements.html('.item-configuration .last-result') - +elements.html('.item-configuration .first-result') + 1),
			totalResults = +elements.html('.item-configuration .total-results');
		var itemGridLineSize = (key) => {
			return Math.min(itemGridLineSizeMaximum, totalResults - (key * itemGridLineSizeMaximum)).toString();
		}
		var processItemGridSelection = (item) => {
			var keyIndexes = range(0, Math.floor(totalResults / itemGridLineSizeMaximum));
			elements.html('.total-checked', (selectionStatus = +item.getAttribute('status')) ? totalResults : 0);
			item.querySelector('.item-configuration .action').innerText = (selectionStatus ? 'Unselect' : 'Select');
			item.setAttribute('status', +(selectionStatus === 0));
			keyIndexes.map((key) => {
				itemGrid[key] = selectionStatus + itemGridLineSize(key);
			});
			itemGrid = selectionStatus ? itemGrid : [];
			processItemGrid(range(0, selectAllElements('.item-configuration tr .checkbox').length - 1));
		};

		if (!itemGrid.length) {
			elements.html('.total-checked', 0);
		}

		itemIndexes.map((itemIndex) => {
			var index = ((currentPage * resultsPerPage) - resultsPerPage) + +itemIndex,
				item = document.querySelector('.item-configuration .checkbox[index="' + itemIndex + '"]'),
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
			var item = document.querySelector('.item-configuration .checkbox[index="' + itemIndex + '"]');

			if (+(item.getAttribute('checked'))) {
				itemCount++;
			}
		});

		if (typeof itemState === 'boolean') {
			elements.html('.total-checked', +elements.html('.total-checked') + (itemCount - itemGridCount));
		}

		var itemAll = document.querySelector('.item-configuration .item-action[index="all"]');
		itemAll.classList.add('hidden');
		itemAll.removeEventListener('click', itemAll.clickListener);
		itemAll.clickListener = () => {
			processItemGridSelection(itemAll)
		};
		itemAll.addEventListener('click', itemAll.clickListener);
		itemAllVisible.setAttribute('checked', +(allVisibleChecked = (itemCount === pageResultCount)));
		itemAllVisible.removeEventListener('click', itemAllVisible.clickListener);
		itemAllVisible.clickListener = () => {
			itemToggleAllVisible(itemAllVisible);
		};
		itemAllVisible.addEventListener('click', itemAllVisible.clickListener);

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
		+elements.html('.total-checked') ? elements.removeClass('.item-configuration span.icon[item-function]', 'hidden') : elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		itemGridCount = itemCount;
	};
	elements.addClass('.item-configuration .item-controls', 'hidden');
	pagination.querySelector('.next').setAttribute('page', 0);
	pagination.querySelector('.previous').setAttribute('page', 0);
	items.innerHTML = '<p class="message no-margin-bottom">Loading ...</p>';
	requestParameters.conditions = {
		order_id: orderId
	};
	requestParameters.items[requestParameters.table] = itemGrid;
	requestParameters.limit = resultsPerPage;
	requestParameters.offset = ((currentPage * resultsPerPage) - resultsPerPage);
	requestParameters.sort.field = 'modified';
	sendRequest((response) => {
		items.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');

		if (
			requestParameters.action == 'search' &&
			requestParameters.data &&
			response.message
		) {
			setTimeout(() => {
				var itemsClear = items.querySelector('.clear');
				itemsClear.removeEventListener('click', itemsClear.clickListener);
				itemsClear.clickListener = () => {
					requestParameters.data = {};
					closeWindows();
					itemGrid = [];
					itemGridCount = 0;
					processItems();
				};
				itemsClear.addEventListener('click', itemsClear.clickListener);
			}, 100);
		}

		if (
			response.code !== 200 ||
			!response.data.length
		) {
			return;
		}

		items.innerHTML += '<table class="table"><thead><th style="width: 35px;"></th><th>Proxy IP</th></thead></table>';
		response.data.map((item, index) => {
			items.querySelector('table').innerHTML += '<tbody><tr page="' + currentPage + '" proxy_id="' + item.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" proxy_id="' + item.id + '"></span></td><td><span class="details-container"><span class="details"><span class="detail"><strong>Status:</strong> ' + item.status.charAt(0).toUpperCase() + item.status.substr(1) + '</span><span class="detail"><strong>Proxy IP:</strong> ' + item.ip + '</span><span class="detail"><strong>Location:</strong> ' + item.city + ', ' + item.region + ' ' + item.country_code + ' <span class="icon-container"><img src="../../resources/images/icons/flags/' + item.country_code.toLowerCase() + '.png" class="flag" alt="' + item.country_code + ' flag"></span></span><span class="detail"><strong>ISP:</strong> ' + item.asn + ' </span><span class="detail"><strong>HTTP + HTTPS Port:</strong> ' + (item.disable_http == 1 ? 'Disabled' : '80') + '</span><span class="detail"><strong>Whitelisted IPs:</strong> ' + (item.whitelisted_ips ? '<textarea>' + item.whitelisted_ips + '</textarea>' : 'N/A') + '</span><span class="detail"><strong>Username:</strong> ' + (item.username ? item.username : 'N/A') + '</span><span class="detail"><strong>Password:</strong> ' + (item.password ? item.password : 'N/A') + '</span></span></span><span class="table-text">' + item.ip + '</span></td></tbody>';
		});
		elements.html('.item-configuration .first-result', currentPage === 1 ? currentPage : ((currentPage * resultsPerPage) - resultsPerPage) + 1);
		elements.html('.item-configuration .last-result', (lastResult = currentPage * resultsPerPage) >= response.count ? response.count : lastResult);
		elements.html('.item-configuration .total-results', response.count);
		pagination.setAttribute('current_page', currentPage);
		pagination.querySelector('.next').setAttribute('page', +elements.html('.item-configuration .last-result') < response.count ? currentPage + 1 : 0);
		pagination.querySelector('.previous').setAttribute('page', currentPage <= 0 ? 0 : currentPage - 1);
		elements.loop('.item-configuration tbody tr', (index, row) => {
			var item = row.querySelector('.checkbox');
			item.removeEventListener('click', item.clickListener);
			item.clickListener = () => {
				itemToggle(item);
			};
			item.addEventListener('click', item.clickListener);
		});
		itemGrid = response.items[requestParameters.table];
		elements.removeClass('.item-configuration .item-controls', 'hidden');
		processItemGrid(range(0, response.data.length - 1));
		var table = requestParameters.table;
		requestParameters.tokens[table] = response.token;
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
	action: 'find',
	data: {},
	items: {},
	sort: {
		field: 'modified',
		order: 'DESC'
	},
	table: 'proxies',
	tokens: {},
	url: '/src/php/views/api.php'
};
var selectAllElements = (selector) => {
	return Object.entries(document.querySelectorAll(selector));
};
var sendRequest = (callback) => {
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
					element[1].parentNode.querySelector('.item-body').setAttribute('style', 'padding-top: ' + (element[1].querySelector('.item-header').clientHeight + 1) + 'px');
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
			var action = element.target.getAttribute('window');
			var currentWindow = '.window-container[window="' + action + '"]';
			document.querySelector('main').classList.add('hidden');
			elements.removeClass(currentWindow, 'hidden');

			switch (action) {
				case 'copy':
					processCopy(action, currentWindow);
					break;
				case 'group':
					processGroup(action, currentWindow);
					break;
			}
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
			var currentWindow = '.window-container[window="' + action + '"]';
			closeWindows();
			elements.loop(currentWindow + ' input, ' + currentWindow + ' select, ' + currentWindow + ' textarea', (index, element) => {
				requestParameters.data[element.getAttribute('name')] = element.value;
			});
			elements.loop(currentWindow + ' .checkbox', (index, element) => {
				requestParameters.data[element.getAttribute('name')] = +element.getAttribute('checked');
			});
			requestParameters.action = action;

			if (action == 'search') {
				itemGrid = [];
				itemGridCount = 0;
			}

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
