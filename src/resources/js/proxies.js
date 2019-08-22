'use_strict';

var defaultTable = 'proxies',
	defaultUrl = '/api/proxies',
	itemGrid = [],
	itemGridCount = 0,
	messageContainer = document.querySelector('.orders-view .message-container'),
	previousAction = 'find';
var processCopy = function(windowName, windowSelector) {
	previousAction = requestParameters.action;
	var processCopyFormat = function() {
		requestParameters.action = windowName;
		elements.addClass(windowSelector + ' .copy', 'hidden');
		elements.removeClass(windowSelector + ' .loading', 'hidden');
		elements.setAttribute(windowSelector + ' .list-format select', 'disabled', 'disabled');
		elements.loop(windowSelector + ' input, ' + windowSelector + ' select, ' + windowSelector + ' textarea', function(index, element) {
			requestParameters.data[element.getAttribute('name')] = element.value;
		});
		requestParameters.items[requestParameters.table] = itemGrid;
		sendRequest(function(response) {
			document.querySelector(windowSelector + ' textarea[name="' + windowName + '"]').value = response.data;
			elements.addClass(windowSelector + ' .loading', 'hidden');
			elements.removeClass(windowSelector + ' .copy', 'hidden');
			elements.removeAttribute(windowSelector + ' .list-format select', 'disabled');
			requestParameters.action = previousAction;
		});
	};
	elements.loop(windowSelector + ' .list-format select', function(index, element) {
		element.removeEventListener('change', element.changeListener);
		element.changeListener = function() {
			processCopyFormat();
		};
		element.addEventListener('change', element.changeListener);
	});
	var itemsCopy = document.querySelector(windowSelector + ' .button.' + windowName);
	itemsCopy.removeEventListener('click', itemsCopy.clickListener);
	itemsCopy.clickListener = function() {
		document.querySelector('[name="copy"]').select();
		document.execCommand(windowName);
	};
	itemsCopy.addEventListener('click', itemsCopy.clickListener);
	processCopyFormat();
};
var processGroup = function(windowName, windowSelector) {
	var groupGrid = {},
		groupNameField = document.querySelector(windowSelector + ' .group-name-field'),
		groupNameButton = document.querySelector(windowSelector + ' .group-name-button'),
		groupTable = document.querySelector(windowSelector + ' .group-table'),
		orderId = document.querySelector('input[name="order_id"]').value;
	var processGroupGrid = function(groupIndexes, groupState) {
		groupIndexes.map(function(groupIndex) {
			var group = document.querySelector(windowSelector + ' .checkbox[index="' + groupIndex + '"]');
			var groupId = group.getAttribute('group_id');
			group.setAttribute('checked', +groupState);
			groupGrid[windowName + groupId] = groupId;

			if (!+groupState) {
				delete groupGrid[windowName + groupId];
			}
		});
		requestParameters.items[requestParameters.table] = groupGrid;
	};
	var groupAdd = function(groupName) {
		requestParameters.action = windowName;
		requestParameters.data.name = groupName;
		requestParameters.data.order_id = orderId;
		delete requestParameters.data.id;
		sendRequest(function(response) {
			processGroupTable(response);
		});
	};
	var groupDelete = function(button, row) {
		var groupId = row.getAttribute('group_id');
		requestParameters.action = windowName;
		requestParameters.data.id = [groupId];
		delete requestParameters.data.name;
		sendRequest(function(response) {
			delete groupGrid[windowName + groupId];
			processGroupTable(response);
		});
	};
	var groupEdit = function(button, row) {
		var processGroupEdit = function(row) {
			requestParameters.action = windowName;
			requestParameters.data.id = row.getAttribute('group_id');
			requestParameters.data.order_id = orderId;
			requestParameters.data.name = row.querySelector('.group-name-edit-field').value;
			sendRequest(function(response) {
				processGroupTable(response);
			});
		};
		var originalRow = row.querySelector('.table-text').innerHTML;
		row.querySelector('.table-text').innerHTML = '<div class="field-group no-margin"><input class="group-name-edit-field no-margin" id="group-name-edit" name="group_name" type="text" value="' + row.querySelector('.view').innerText + '"><button class="button group-name-save-edit-button">Save</button><button class="button group-name-cancel-edit-button">Cancel</button></div>';
		row = document.querySelector(windowSelector + ' tbody tr[group_id="' + row.getAttribute('group_id') + '"]');
		var groupNameCancelEditButton = row.querySelector('.group-name-cancel-edit-button'),
			groupNameEditField = row.querySelector('.group-name-edit-field'),
			groupNameSaveEditButton = row.querySelector('.group-name-save-edit-button');
		groupNameCancelEditButton.removeEventListener('click', groupNameCancelEditButton.clickListener);
		groupNameEditField.removeEventListener('keydown', groupNameEditField.keydownListener);
		groupNameSaveEditButton.removeEventListener('click', groupNameSaveEditButton.clickListener);
		groupNameCancelEditButton.clickListener = function() {
			row.querySelector('.table-text').innerHTML = originalRow;
		};
		groupNameEditField.keydownListener = function() {
			if (event.key == 'Enter') {
				processGroupEdit(row);
			}
		};
		groupNameSaveEditButton.clickListener = function() {
			processGroupEdit(row);
		};
		groupNameCancelEditButton.addEventListener('click', groupNameCancelEditButton.clickListener);
		groupNameEditField.addEventListener('keydown', groupNameEditField.keydownListener);
		groupNameSaveEditButton.addEventListener('click', groupNameSaveEditButton.clickListener);
	};
	var groupToggle = function(button) {
		groupTable.setAttribute('current_checked', button.getAttribute('index'));
		processGroupGrid(window.event.shiftKey ? range(groupTable.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +document.querySelector(windowSelector + ' .checkbox[index="' + groupTable.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		groupTable.setAttribute('previous_checked', button.getAttribute('index'));
	};
	var groupView = function(button, row) {
		if (messageContainer) {
			messageContainer.innerHTML = '<p class="message no-margin-top">Loading ...</p>';
		}

		elements.addClass('.item-configuration .item-controls', 'hidden');
		closeWindows(defaultTable);
		requestParameters.action = 'search';
		requestParameters.data.groups = [button.getAttribute('group_id')];
		requestParameters.table = 'proxies';
		itemGrid = [];
		itemGridCount = 0;
		sendRequest(function() {
			processProxies(false, false, 1);
		});
	};
	var processGroupTable = function(response) {
		requestParameters.limit = limit;
		requestParameters.offset = offset;
		groupTable.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');

		if (
			response.code !== 200 ||
			!response.data.length
		) {
			return;
		}

		groupTable.innerHTML += '<table class="table"><thead><th style="width: 35px;"></th><th>Group Name</th></thead><tbody></tbody></table>';
		response.data.map(function(group, index) {
			groupTable.querySelector('table tbody').innerHTML += '<tr group_id="' + group.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" group_id="' + group.id + '"></span></td><td><span class="table-text"><a class="view" group_id="' + group.id + '" href="javascript:void(0);">' + group.name + '</a></span><span class="table-actions"><span class="button edit icon" group_id="' + group.id + '"></span><span class="button delete icon" group_id="' + group.id + '"></span></span></td>';
		});
		elements.loop(windowSelector + ' tbody tr', function(index, row) {
			var groupDeleteButton = row.querySelector('.delete'),
				groupEditButton = row.querySelector('.edit'),
				groupToggleButton = row.querySelector('.checkbox'),
				groupViewButton = row.querySelector('.view');
			groupDeleteButton.removeEventListener('click', groupDeleteButton.clickListener);
			groupEditButton.removeEventListener('click', groupEditButton.clickListener);
			groupToggleButton.removeEventListener('click', groupToggleButton.clickListener);
			groupViewButton.removeEventListener('click', groupViewButton.clickListener);
			groupDeleteButton.clickListener = function() {
				groupDelete(groupDeleteButton, row);
			};
			groupEditButton.clickListener = function() {
				groupEdit(groupEditButton, row);
			};
			groupToggleButton.clickListener = function() {
				groupToggle(groupToggleButton);
			};
			groupViewButton.clickListener = function() {
				groupView(groupViewButton);
			};
			groupDeleteButton.addEventListener('click', groupDeleteButton.clickListener);
			groupEditButton.addEventListener('click', groupEditButton.clickListener);
			groupToggleButton.addEventListener('click', groupToggleButton.clickListener);
			groupViewButton.addEventListener('click', groupViewButton.clickListener);
		});
		groupNameField.value = '';
		Object.entries(groupGrid).map(function(groupId) {
			var group = document.querySelector(windowSelector + ' .checkbox[group_id="' + groupId[1] + '"]');
			processGroupGrid([group.getAttribute('index')], true);
		});
	};
	+elements.html('.total-checked') ? elements.removeClass(windowSelector + ' .submit', 'hidden') : elements.addClass(windowSelector + ' .submit', 'hidden');
	groupNameField.removeEventListener('keydown', groupNameField.keydownListener);
	groupNameButton.removeEventListener('click', groupNameButton.clickListener);
	groupNameField.keydownListener = function(event) {
		if (event.key == 'Enter') {
			groupAdd(groupNameField.value);
		}
	};
	groupNameButton.clickListener = function() {
		groupAdd(groupNameField.value);
	};
	groupNameField.addEventListener('keydown', groupNameField.keydownListener);
	groupNameButton.addEventListener('click', groupNameButton.clickListener);
	groupTable.innerHTML = '<p class="message no-margin-bottom">Loading ...</p>';
	requestParameters.action = 'find';
	requestParameters.sort.field = 'created';
	requestParameters.table = 'proxy_groups';
	var limit = requestParameters.limit;
	var offset = requestParameters.offset;
	delete requestParameters.limit;
	delete requestParameters.offset;
	sendRequest(function(response) {
		processGroupTable(response);
	});
};
var processOrdersView = function() {
	var orderId = document.querySelector('input[name="order_id"]').value;
	requestParameters.conditions = {
		id: orderId
	};
	requestParameters.table = 'orders';
	requestParameters.url = '/api/orders';
	sendRequest(function(response) {
		if (messageContainer) {
			messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.count) {
			document.querySelector('.order-name').innerHTML = response.data[0].quantity + ' ' + response.data[0].name;
			requestParameters.table = defaultTable;
			requestParameters.url = defaultUrl;

			if (document.querySelector('.pagination')) {
				processProxies();
				selectAllElements('.pagination .button').map(function(element) {
					element[1].addEventListener('click', function(element) {
						if ((page = +element.target.getAttribute('page')) > 0) {
							processProxies(false, false, page);
						}
					});
				});
			}
		}
	});
};
var processProxies = function(windowName = false, windowSelector = false, currentPage = false) {
	var items = document.querySelector('.item-configuration .item-table'),
		orderId = document.querySelector('input[name="order_id"]').value,
		pagination = document.querySelector('.item-configuration .pagination');
	var resultsPerPage = +pagination.getAttribute('results');
	var itemToggle = function(item) {
		items.setAttribute('current_checked', item.getAttribute('index'));
		processItemGrid(window.event.shiftKey ? range(items.getAttribute('previous_checked'), item.getAttribute('index')) : [item.getAttribute('index')], window.event.shiftKey ? +document.querySelector('.item-configuration .checkbox[index="' + items.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +item.getAttribute('checked') === 0);
		items.setAttribute('previous_checked', item.getAttribute('index'));
	};
	var itemAllVisible = document.querySelector('.item-configuration .checkbox[index="all-visible"]'),
		itemToggleAllVisible = function(item) {
			items.setAttribute('current_checked', 0);
			items.setAttribute('previous_checked', 0);
			processItemGrid(range(0, selectAllElements('.item-configuration tr .checkbox').length - 1), +item.getAttribute('checked') === 0);
		};
	var processItemGrid = function(itemIndexes, itemState) {
		var itemCount = 0;
			itemGridLineSizeMaximum = +('1' + repeat(Math.min(elements.html('.item-configuration .total-results').length, 4), '0')),
			pageResultCount = (+elements.html('.item-configuration .last-result') - +elements.html('.item-configuration .first-result') + 1),
			totalResults = +elements.html('.item-configuration .total-results');
		var itemGridLineSize = function(key) {
			return Math.min(itemGridLineSizeMaximum, totalResults - (key * itemGridLineSizeMaximum)).toString();
		};
		var processItemGridSelection = function(item) {
			var keyIndexes = range(0, Math.floor(totalResults / itemGridLineSizeMaximum));
			elements.html('.total-checked', (selectionStatus = +item.getAttribute('status')) ? totalResults : 0);
			keyIndexes.map(function(key) {
				itemGrid[key] = selectionStatus + itemGridLineSize(key);
			});
			itemGrid = selectionStatus ? itemGrid : [];
			processItemGrid(range(0, selectAllElements('.item-configuration tr .checkbox').length - 1));
		};

		if (!itemGrid.length) {
			elements.html('.total-checked', 0);
		}

		itemIndexes.map(function(itemIndex) {
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
				itemGrid[key].map(function(itemStatus, itemStatusIndex) {
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
			itemGrid[key].map(function(itemStatus, itemStatusIndex) {
				if (itemStatus != itemGrid[key][itemStatusIndex + 1]) {
					serializedGridLineItems.push(itemStatus + serializeCount);
					serializeCount = 0;
				}

				serializeCount++;
			});
			item.setAttribute('checked', +itemGrid[key][itemGridLineIndex]);
			itemGrid[key] = serializedGridLineItems.join('_');
		});

		range(0, pageResultCount - 1).map(function(itemIndex) {
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
		itemAll.clickListener = function() {
			processItemGridSelection(itemAll);
		};
		itemAll.addEventListener('click', itemAll.clickListener);
		itemAllVisible.setAttribute('checked', +(allVisibleChecked = (itemCount === pageResultCount)));
		itemAllVisible.removeEventListener('click', itemAllVisible.clickListener);
		itemAllVisible.clickListener = function() {
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
			itemAll.querySelector('.action').innerText = (selectionStatus = +(+elements.html('.total-checked') === totalResults)) ? 'Unselect' : 'Select';
			itemAll.setAttribute('status', +(selectionStatus === 0));
		}

		processWindowEvents(windowEvents, 'resize');
		+elements.html('.total-checked') ? elements.removeClass('.item-configuration span.icon[item-function]', 'hidden') : elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		itemGridCount = itemCount;
	};
	elements.addClass('.item-configuration .item-controls', 'hidden');
	pagination.querySelector('.next').setAttribute('page', 0);
	pagination.querySelector('.previous').setAttribute('page', 0);

	if (messageContainer) {
		messageContainer.innerHTML = '<p class="message no-margin-top">Loading ...</p>';
	}

	if (!currentPage) {
		currentPage = pagination.hasAttribute('current_page') ? Math.max(1, +pagination.getAttribute('current_page')) : 1;

		if (
			requestParameters.action == 'search' &&
			previousAction == 'find'
		) {
			currentPage = 1;
		}
	}

	requestParameters.conditions = {
		order_id: orderId
	};
	requestParameters.items[requestParameters.table] = itemGrid;
	requestParameters.limit = resultsPerPage;
	requestParameters.offset = ((currentPage * resultsPerPage) - resultsPerPage);
	requestParameters.sort.field = 'modified';
	sendRequest(function(response) {
		if (messageContainer) {
			messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');
		}

		if (
			requestParameters.action == 'search' &&
			requestParameters.data &&
			response.message
		) {
			setTimeout(function() {
				var itemsClear = document.querySelector('.item-configuration a.clear');
				itemsClear.removeEventListener('click', itemsClear.clickListener);
				itemsClear.clickListener = function() {
					previousAction = 'find';
					requestParameters.data = {};
					closeWindows(defaultTable);
					itemGrid = [];
					itemGridCount = 0;
					processProxies(false, false, 1);
				};
				itemsClear.addEventListener('click', itemsClear.clickListener);
			}, 100);
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (
			response.code !== 200 ||
			!response.data.length
		) {
			return items.innerHTML = '';
		}

		items.innerHTML = '<table class="table"><thead><tr><th style="width: 35px;"></th><th>Proxy IP</th></tr></thead><tbody></tbody></table>';
		response.data.map(function(item, index) {
			items.querySelector('table tbody').innerHTML += '<tr page="' + currentPage + '" proxy_id="' + item.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" proxy_id="' + item.id + '"></span></td><td><span class="details-container"><span class="details"><span class="detail"><strong>Status:</strong> ' + capitalizeString(item.status) + '</span><span class="detail"><strong>Proxy IP:</strong> ' + item.ip + '</span><span class="detail"><strong>Location:</strong> ' + item.city + ', ' + item.region + ' ' + item.country_code + ' <span class="icon-container"><img src="/resources/images/flags/' + item.country_code.toLowerCase() + '.png" class="flag" alt="' + item.country_code + ' flag"></span></span><span class="detail"><strong>ISP:</strong> ' + item.asn + ' </span><span class="detail"><strong>HTTP + HTTPS Port:</strong> ' + (item.disable_http == 1 ? 'Disabled' : '80') + '</span><span class="detail"><strong>Username:</strong> ' + (item.username ? item.username : 'N/A') + '</span><span class="detail"><strong>Password:</strong> ' + (item.password ? item.password : 'N/A') + '</span><span class="detail"><strong>Whitelisted IPs:</strong> ' + (item.whitelisted_ips ? '<textarea>' + item.whitelisted_ips + '</textarea>' : 'N/A') + '</span></span></span><span class="table-text">' + item.ip + '</span></td>';
		});
		elements.html('.item-configuration .first-result', currentPage === 1 ? currentPage : ((currentPage * resultsPerPage) - resultsPerPage) + 1);
		elements.html('.item-configuration .last-result', (lastResult = currentPage * resultsPerPage) >= response.count ? response.count : lastResult);
		elements.html('.item-configuration .total-results', response.count);
		pagination.setAttribute('current_page', currentPage);
		pagination.querySelector('.next').setAttribute('page', +elements.html('.item-configuration .last-result') < response.count ? currentPage + 1 : 0);
		pagination.querySelector('.previous').setAttribute('page', currentPage <= 0 ? 0 : currentPage - 1);
		elements.loop('.item-configuration tbody tr', function(index, row) {
			var item = row.querySelector('.checkbox');
			item.removeEventListener('click', item.clickListener);
			item.clickListener = function() {
				itemToggle(item);
			};
			item.addEventListener('click', item.clickListener);
		});
		elements.removeClass('.item-configuration .item-controls', 'hidden');
		itemGrid = response.items[requestParameters.table];

		if (requestParameters.action != 'search') {
			requestParameters.action = previousAction;
		}

		requestParameters.tokens[requestParameters.table] = response.tokens[requestParameters.table];
		processItemGrid(range(0, response.data.length - 1));
	});
};
requestParameters.action = 'find';
requestParameters.sort = {
	field: 'modified',
	order: 'DESC'
};
requestParameters.table = defaultTable;
requestParameters.url = defaultUrl;
onLoad(function() {
	setTimeout(function() {
		processOrdersView();
	}, 100);
});
