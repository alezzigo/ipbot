'use_strict';

var defaultTable = 'proxies',
	defaultUrl = '/src/views/proxies/api.php',
	itemGrid = [],
	itemGridCount = 0,
	previousAction = 'find';
var processCopy = (windowName, windowSelector) => {
	previousAction = requestParameters.action;
	var processCopyFormat = () => {
		requestParameters.action = windowName;
		elements.addClass(windowSelector + ' .copy', 'hidden');
		elements.removeClass(windowSelector + ' .loading', 'hidden');
		elements.setAttribute(windowSelector + ' .list-format select', 'disabled', 'disabled');
		elements.loop(windowSelector + ' input, ' + windowSelector + ' select, ' + windowSelector + ' textarea', (index, element) => {
			requestParameters.data[element.getAttribute('name')] = element.value;
		});
		requestParameters.items[requestParameters.table] = itemGrid;
		sendRequest((response) => {
			document.querySelector(windowSelector + ' textarea[name="' + windowName + '"]').value = response.data;
			elements.addClass(windowSelector + ' .loading', 'hidden');
			elements.removeClass(windowSelector + ' .copy', 'hidden');
			elements.removeAttribute(windowSelector + ' .list-format select', 'disabled');
			requestParameters.action = previousAction;
		});
	};
	elements.loop(windowSelector + ' .list-format select', (index, element) => {
		element.removeEventListener('change', element.changeListener);
		element.changeListener = () => {
			processCopyFormat();
		}
		element.addEventListener('change', element.changeListener);
	});

	var itemsCopy = document.querySelector(windowSelector + ' .button.' + windowName);
	itemsCopy.removeEventListener('click', itemsCopy.clickListener);
	itemsCopy.clickListener = () => {
		document.querySelector('[name="copy"]').select();
		document.execCommand(windowName);
	};
	itemsCopy.addEventListener('click', itemsCopy.clickListener);
	processCopyFormat();
};
var processGroup = (windowName, windowSelector) => {
	var groupGrid = {},
		groupNameField = document.querySelector(windowSelector + ' .group-name-field'),
		groupNameButton = document.querySelector(windowSelector + ' .group-name-button'),
		groupTable = document.querySelector(windowSelector + ' .group-table'),
		orderId = document.querySelector('input[name="order_id"]').value;
	var processGroupGrid = (groupIndexes, groupState) => {
		groupIndexes.map((groupIndex) => {
			var group = document.querySelector(windowSelector + ' .checkbox[index="' + groupIndex + '"]');
			var groupId = group.getAttribute('group_id');
			group.setAttribute('checked', +groupState);
			groupGrid[windowName + groupId] = groupId;
		});
		requestParameters.items[requestParameters.table] = groupGrid;
	};
	var groupAdd = (groupName) => {
		requestParameters.action = windowName;
		requestParameters.data.name = groupName;
		requestParameters.data.order_id = orderId;
		delete requestParameters.data.id;
		sendRequest((response) => {
			processGroupTable(response);
		});
	};
	var groupDelete = (button, row) => {
		var groupId = row.getAttribute('group_id');
		requestParameters.action = windowName;
		requestParameters.data.id = [groupId];
		delete requestParameters.data.name;
		sendRequest((response) => {
			delete groupGrid[windowName + groupId];
			processGroupTable(response);
		});
	};
	var groupEdit = (button, row) => {
		var processGroupEdit = (row) => {
			requestParameters.action = windowName;
			requestParameters.data.id = row.getAttribute('group_id');
			requestParameters.data.order_id = orderId;
			requestParameters.data.name = row.querySelector('.group-name-edit-field').value;
			sendRequest((response) => {
				processGroupTable(response);
			});
		}
		var originalRow = row.querySelector('.table-text').innerHTML;
		row.querySelector('.table-text').innerHTML = '<div class="field-group no-margin"><input class="group-name-edit-field no-margin" id="group-name-edit" name="group_name" type="text" value="' + row.querySelector('.view').innerText + '"><button class="button group-name-save-edit-button">Save</button><button class="button group-name-cancel-edit-button">Cancel</button></div>';
		row = document.querySelector(windowSelector + ' tbody tr[group_id="' + row.getAttribute('group_id') + '"]');
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
		processGroupGrid(window.event.shiftKey ? range(groupTable.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +document.querySelector(windowSelector + ' .checkbox[index="' + groupTable.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		groupTable.setAttribute('previous_checked', button.getAttribute('index'));
	};
	var groupView = (button, row) => {
		document.querySelector('.item-configuration .item-table').innerHTML = '<p class="message">Loading ...</p>';
		elements.addClass('.item-configuration .item-controls', 'hidden');
		closeWindows(defaultTable);
		requestParameters.action = 'search';
		requestParameters.data.groups = [button.getAttribute('group_id')];
		requestParameters.table = 'proxies';
		itemGrid = [];
		itemGridCount = 0;
		sendRequest(() => {
			processProxies();
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
		elements.loop(windowSelector + ' tbody tr', (index, row) => {
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
			var group = document.querySelector(windowSelector + ' .checkbox[group_id="' + groupId[1] + '"]');
			processGroupGrid([group.getAttribute('index')], true);
		});
	};
	+elements.html('.total-checked') ? elements.removeClass(windowSelector + ' .submit', 'hidden') : elements.addClass(windowSelector + ' .submit', 'hidden');
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
var processOrdersView = () => {
	var orderId = document.querySelector('input[name="order_id"]').value;
	requestParameters.conditions = {
		id: orderId
	};
	requestParameters.table = 'orders';
	requestParameters.url = '/src/views/orders/api.php';
	sendRequest((response) => {
		var messageContainer = document.querySelector('.orders-list .message-container');

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
			document.querySelector('.order-name').innerHTML = response.data[0].name;
			requestParameters.table = defaultTable;
			requestParameters.url = defaultUrl;

			if (document.querySelector('.pagination')) {
				processProxies();
				selectAllElements('.pagination .button').map((element) => {
					element[1].addEventListener('click', (element) => {
						if ((page = +element.target.getAttribute('page')) > 0) {
							processProxies(false, false, page);
						}
					});
				});
			}
		}
	});
};
var processProxies = (windowName = false, windowSelector = false, currentPage = 1) => {
	previousAction = requestParameters.action;
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
					previousAction = 'find';
					requestParameters.data = {};
					closeWindows(defaultTable);
					itemGrid = [];
					itemGridCount = 0;
					processProxies();
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
			return false;
		}

		items.innerHTML += '<table class="table"><thead><th style="width: 35px;"></th><th>Proxy IP</th></thead></table>';
		response.data.map((item, index) => {
			items.querySelector('table').innerHTML += '<tbody><tr page="' + currentPage + '" proxy_id="' + item.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" proxy_id="' + item.id + '"></span></td><td><span class="details-container"><span class="details"><span class="detail"><strong>Status:</strong> ' + capitalizeString(item.status) + '</span><span class="detail"><strong>Proxy IP:</strong> ' + item.ip + '</span><span class="detail"><strong>Location:</strong> ' + item.city + ', ' + item.region + ' ' + item.country_code + ' <span class="icon-container"><img src="../../resources/images/icons/flags/' + item.country_code.toLowerCase() + '.png" class="flag" alt="' + item.country_code + ' flag"></span></span><span class="detail"><strong>ISP:</strong> ' + item.asn + ' </span><span class="detail"><strong>HTTP + HTTPS Port:</strong> ' + (item.disable_http == 1 ? 'Disabled' : '80') + '</span><span class="detail"><strong>Whitelisted IPs:</strong> ' + (item.whitelisted_ips ? '<textarea>' + item.whitelisted_ips + '</textarea>' : 'N/A') + '</span><span class="detail"><strong>Username:</strong> ' + (item.username ? item.username : 'N/A') + '</span><span class="detail"><strong>Password:</strong> ' + (item.password ? item.password : 'N/A') + '</span></span></span><span class="table-text">' + item.ip + '</span></td></tbody>';
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
		requestParameters.tokens[requestParameters.table] = response.tokens[requestParameters.table];
		elements.removeClass('.item-configuration .item-controls', 'hidden');
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
onLoad(() => {
	processOrdersView();
});
