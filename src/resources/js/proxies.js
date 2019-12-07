'use_strict';

var defaultTable = 'proxies';
var defaultUrl = '/api/proxies';
var itemGrid = [];
var itemGridCount = 0;
var orderMessageContainer = document.querySelector('main .message-container.order');
var previousAction = 'fetch';
var proxyMessageContainer = document.querySelector('main .message-container.proxies');
var processCopy = function(frameName, frameSelector) {
	previousAction = requestParameters.action;
	var processCopyFormat = function() {
		requestParameters.action = frameName;
		elements.addClass(frameSelector + ' .copy', 'hidden');
		elements.removeClass(frameSelector + ' .loading', 'hidden');
		elements.setAttribute(frameSelector + ' .list-format select', 'disabled', 'disabled');
		elements.loop(frameSelector + ' input, ' + frameSelector + ' select, ' + frameSelector + ' textarea', function(index, element) {
			requestParameters.data[element.getAttribute('name')] = element.value;
		});
		requestParameters.items[requestParameters.table] = itemGrid;
		sendRequest(function(response) {
			document.querySelector(frameSelector + ' textarea[name="' + frameName + '"]').value = response.data;
			elements.addClass(frameSelector + ' .loading', 'hidden');
			elements.removeClass(frameSelector + ' .copy', 'hidden');
			elements.removeAttribute(frameSelector + ' .list-format select', 'disabled');
			requestParameters.action = previousAction;
		});
	};
	elements.loop(frameSelector + ' select', function(index, element) {
		element.removeEventListener('change', element.changeListener);
		element.changeListener = function() {
			processCopyFormat();
		};
		element.addEventListener('change', element.changeListener);
	});
	var itemsCopy = document.querySelector(frameSelector + ' .button.' + frameName);
	itemsCopy.removeEventListener('click', itemsCopy.clickListener);
	itemsCopy.clickListener = function() {
		document.querySelector('[name="copy"]').select();
		document.execCommand(frameName);
	};
	itemsCopy.addEventListener('click', itemsCopy.clickListener);
	processCopyFormat();
};
var processDowngrade = function() {
	var downgradeContainer = document.querySelector('.downgrade-container');
	var pagination = document.querySelector('.item-configuration .pagination');
	requestParameters.action = 'downgrade';
	sendRequest(function(response) {
		var downgradeData = '';
		var downgradeMessageContainer = document.querySelector('.downgrade-configuration .message-container');
		elements.setAttribute('.button.submit', 'disabled');

		if (downgradeMessageContainer) {
			downgradeMessageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.message.status === 'success') {
			downgradeData += '<input class="hidden" name="confirm_downgrade" type="hidden" value="1">';
			downgradeData += '<div class="clear"></div>';
			downgradeData += '<div class="merged-order-details">';
			downgradeData += '<p class="message success">Your current order for ' + response.data.downgraded.order.quantity + ' ' + requestParameters.table + ' will downgrade to the following order and invoice:</p>';
			downgradeData += '<div class="item-container item-button no-margin-bottom">';
			downgradeData += '<p><strong>Downgraded Order</strong></p>';
			downgradeData += '<p>' + response.data.downgraded.order.quantity_pending + ' ' + response.data.downgraded.order.name + '</p>';
			downgradeData += '<p class="no-margin-bottom">' + response.data.downgraded.order.price_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + ' for ' + response.data.downgraded.order.interval_value + ' ' + response.data.downgraded.order.interval_type + (response.data.downgraded.order.interval_value !== 1 ? 's' : '') + '</p>';
			downgradeData += '<div class="item-link-container"></div>';
			downgradeData += '</div>';
			downgradeData += '<div class="align-left item-container no-margin-top no-padding">';
			downgradeData += '<h2>Downgraded Invoice Pricing Details</h2>';
			downgradeData += '<p><strong>Subtotal</strong><br>' + response.data.downgraded.invoice.subtotal_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p><strong>Shipping</strong><br>' + response.data.downgraded.invoice.shipping_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p><strong>Tax</strong><br>' + response.data.downgraded.invoice.tax_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p><strong>Total</strong><br>' + response.data.downgraded.invoice.total_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p class="no-margin-bottom"><strong>Amount Paid</strong><br><span' + (response.data.downgraded.invoice.amount_paid ? ' class="paid"' : '') + '>' + response.data.downgraded.invoice.amount_paid.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</span>' + (response.data.downgraded.invoice.amount_paid ? '<br><span class="note">The amount paid will be added to your account balance and won\'t automatically apply to the remaining amount due for the downgraded order.</span>' : '') + '</p>';
			downgradeData += '</div>';
			downgradeData += '</div>';
			elements.removeAttribute('.button.submit', 'disabled');
		}

		if (requestParameters.data.confirm_downgrade) {
			closeFrames(defaultTable);
			document.querySelector('.order-name').innerHTML = response.data.downgraded.order.quantity_pending + ' ' + response.data.downgraded.order.name;
			proxyMessageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
			requestParameters.action = 'fetch';
			delete requestParameters.data.confirm_downgrade;
		}

		downgradeContainer.innerHTML = downgradeData;
	});
};
var processEndpoint = function(frameName, frameSelector) {
	requestParameters.action = 'endpoint';
	requestParameters.data.order_id = document.querySelector('input[name="order_id"]').value;
	requestParameters.table = 'orders';
	requestParameters.url = '/api/orders';
	sendRequest(function(response) {
		if (proxyMessageContainer) {
			processWindowEvents('resize');
			proxyMessageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (response.data) {
			var endpointEnableCheckboxInput = document.querySelector('.endpoint-enable');
			var endpointEnableCheckboxLabel = document.querySelector('label[for="endpoint-enable"]');
			endpointEnableCheckboxInput.removeEventListener('click', endpointEnableCheckboxInput.clickListener);
			endpointEnableCheckboxLabel.removeEventListener('click', endpointEnableCheckboxLabel.clickListener);
			endpointEnableCheckboxInput.clickListener = endpointEnableCheckboxLabel.clickListener = function() {
				if (+endpointEnableCheckboxInput.getAttribute('checked')) {
					elements.removeClass('.endpoint-enabled-container', 'hidden');
				} else {
					elements.addClass('.endpoint-enabled-container', 'hidden');
				}
			};
			endpointEnableCheckboxInput.addEventListener('click', endpointEnableCheckboxInput.clickListener);
			endpointEnableCheckboxLabel.addEventListener('click', endpointEnableCheckboxLabel.clickListener);
			elements.addClass('.endpoint-enabled-container', 'hidden');

			if (typeof response.data.endpoint_enable !== 'undefined') {
				if (response.data.endpoint_enable) {
					elements.removeClass('.endpoint-enabled-container', 'hidden');
				}

				var endpointShowDocumentation = document.querySelector('.endpoint-show-documentation');
				endpointShowDocumentation.removeEventListener('click', endpointShowDocumentation.clickListener);
				endpointShowDocumentation.clickListener = function() {
					if (elements.hasClass('.endpoint-documentation', 'hidden')) {
						elements.removeClass('.endpoint-documentation', 'hidden');
						elements.addClass('.endpoint-show-documentation', 'hidden');
					}
				};
				endpointShowDocumentation.addEventListener('click', endpointShowDocumentation.clickListener);
				elements.setAttribute('.endpoint-enable', 'checked', +response.data.endpoint_enable);
				elements.setAttribute('.endpoint-password', 'value', response.data.endpoint_password ? response.data.endpoint_password : '');
				elements.setAttribute('.endpoint-require-authentication', 'checked', +response.data.endpoint_require_authentication);
				elements.setAttribute('.endpoint-require-match', 'checked', +response.data.endpoint_require_match);
				elements.setAttribute('.endpoint-username', 'value', response.data.endpoint_username ? response.data.endpoint_username : '');
				elements.html('.endpoint-whitelisted-ips', response.data.endpoint_whitelisted_ips ? response.data.endpoint_whitelisted_ips : '');
			}
		}

		requestParameters.table = defaultTable;
		requestParameters.url = defaultUrl;
	});
};
var processGroup = function(frameName, frameSelector) {
	var groupGrid = {};
	var groupNameButton = document.querySelector(frameSelector + ' .group-name-button');
	var groupNameField = document.querySelector(frameSelector + ' .group-name-field');
	var groupTable = document.querySelector(frameSelector + ' .group-table');
	var orderId = document.querySelector('input[name="order_id"]').value;
	var groupAdd = function(groupName) {
		requestParameters.action = frameName;
		requestParameters.data.name = groupName;
		requestParameters.data.order_id = orderId;
		delete requestParameters.data.id;
		sendRequest(function(response) {
			processGroupTable(response);
		});
	};
	var groupDelete = function(button, row) {
		var groupId = row.getAttribute('group_id');
		requestParameters.action = frameName;
		requestParameters.data.id = [groupId];
		delete requestParameters.data.name;
		sendRequest(function(response) {
			delete groupGrid[frameName + groupId];
			processGroupTable(response);
		});
	};
	var groupEdit = function(button, row) {
		var processGroupEdit = function(row) {
			requestParameters.action = frameName;
			requestParameters.data.id = row.getAttribute('group_id');
			requestParameters.data.order_id = orderId;
			requestParameters.data.name = row.querySelector('.group-name-edit-field').value;
			sendRequest(function(response) {
				processGroupTable(response);
			});
		};
		var originalRow = row.querySelector('.table-text').innerHTML;
		row.querySelector('.table-text').innerHTML = '<div class="field-group no-margin"><input class="group-name-edit-field no-margin" id="group-name-edit" name="group_name" type="text" value="' + row.querySelector('.view').innerText + '"><button class="button group-name-save-edit-button">Save</button><button class="button group-name-cancel-edit-button">Cancel</button></div>';
		row = document.querySelector(frameSelector + ' tbody tr[group_id="' + row.getAttribute('group_id') + '"]');
		var groupNameCancelEditButton = row.querySelector('.group-name-cancel-edit-button');
		var groupNameEditField = row.querySelector('.group-name-edit-field');
		var groupNameSaveEditButton = row.querySelector('.group-name-save-edit-button');
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
		processGroupGrid(window.event.shiftKey ? range(groupTable.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +document.querySelector(frameSelector + ' .checkbox[index="' + groupTable.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		groupTable.setAttribute('previous_checked', button.getAttribute('index'));
	};
	var groupView = function(button, row) {
		if (proxyMessageContainer) {
			proxyMessageContainer.innerHTML = '<p class="message no-margin-top">Loading ...</p>';
		}

		elements.addClass('.item-configuration .item-controls', 'hidden');
		closeFrames(defaultTable);
		requestParameters.action = 'search';
		requestParameters.data.groups = [button.getAttribute('group_id')];
		requestParameters.table = 'proxies';
		itemGrid = [];
		itemGridCount = 0;
		sendRequest(function() {
			processProxies(false, false, 1);
		});
	};
	var processGroupGrid = function(groupIndexes, groupState) {
		groupIndexes.map(function(groupIndex) {
			var group = document.querySelector(frameSelector + ' .checkbox[index="' + groupIndex + '"]');
			var groupId = group.getAttribute('group_id');
			group.setAttribute('checked', +groupState);
			groupGrid[frameName + groupId] = groupId;

			if (!+groupState) {
				delete groupGrid[frameName + groupId];
			}
		});
		requestParameters.items[requestParameters.table] = groupGrid;
	};
	var processGroupTable = function(response) {
		requestParameters.limit = limit;
		requestParameters.offset = offset;
		groupTable.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');

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
		elements.loop(frameSelector + ' tbody tr', function(index, row) {
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
			var group = document.querySelector(frameSelector + ' .checkbox[group_id="' + groupId[1] + '"]');
			processGroupGrid([group.getAttribute('index')], true);
		});
	};
	+elements.html('.total-checked') ? elements.removeClass(frameSelector + ' .submit', 'hidden') : elements.addClass(frameSelector + ' .submit', 'hidden');
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
	requestParameters.action = 'fetch';
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
var processOrder = function() {
	var orderId = document.querySelector('input[name="order_id"]').value;
	requestParameters.action = 'view';
	requestParameters.conditions = {
		id: orderId
	};
	requestParameters.table = 'orders';
	requestParameters.url = '/api/orders';
	sendRequest(function(response) {
		if (orderMessageContainer) {
			orderMessageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.data.order) {
			document.querySelector('.order-name').innerHTML = (response.data.order.quantity_active ? response.data.order.quantity_active : response.data.order.quantity) + ' ' + response.data.order.name;
			requestParameters.table = defaultTable;
			requestParameters.url = defaultUrl;

			if (document.querySelector('.pagination')) {
				requestParameters.action = 'fetch';
				processProxies();
				selectAllElements('.pagination .button').map(function(element) {
					element[1].addEventListener('click', function(element) {
						if ((page = +element.target.getAttribute('page')) > 0) {
							processProxies(false, false, page);
						}
					});
				});
			}

			if (response.data.nodeLocations) {
				var nodeLocationCityOptions = nodeLocationCountryOptions = nodeLocationRegionOptions = '<option value="">All</option>';
				var nodeLocationSelector = '.checkbox-option-container[field="replace_with_specific_node_locations"] .field-group';
				var nodeLocationCitySelect = document.querySelector(nodeLocationSelector + ' select.node-city');
				var nodeLocationCountrySelect = document.querySelector(nodeLocationSelector + ' select.node-country-code');
				var nodeLocationRegionSelect = document.querySelector(nodeLocationSelector + ' select.node-region');

				if (
					nodeLocationCitySelect &&
					nodeLocationCountrySelect &&
					nodeLocationRegionSelect
				) {
					response.data.nodeLocations.map(function(location) {
						nodeLocationCityOptions += '<option value="' + location.city + '">' + location.city + '</option>';
						nodeLocationCountryOptions += '<option value="' + location.country_code + '">' + location.country_name + '</option>';
						nodeLocationRegionOptions += '<option value="' + location.region + '">' + location.region + '</option>';
					});
					nodeLocationCitySelect.innerHTML = nodeLocationCityOptions;
					nodeLocationCountrySelect.innerHTML = nodeLocationCountryOptions;
					nodeLocationRegionSelect.innerHTML = nodeLocationRegionOptions;
				}
			}

			if (response.data.nodeSubnets) {
				// ..
			}
		}
	});
};
var processProxies = function(frameName, frameSelector, currentPage) {
	var currentPage = currentPage || 1;
	var items = document.querySelector('.item-configuration .item-table');
	var orderId = document.querySelector('input[name="order_id"]').value;
	var pagination = document.querySelector('.item-configuration .pagination');
	var resultsPerPage = +pagination.getAttribute('results');
	var itemToggle = function(item) {
		items.setAttribute('current_checked', item.getAttribute('index'));
		processItemGrid(window.event.shiftKey ? range(items.getAttribute('previous_checked'), item.getAttribute('index')) : [item.getAttribute('index')], window.event.shiftKey ? +document.querySelector('.item-configuration .checkbox[index="' + items.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +item.getAttribute('checked') === 0);
		items.setAttribute('previous_checked', item.getAttribute('index'));
	};
	var itemAllVisible = document.querySelector('.item-configuration .checkbox[index="all-visible"]');
	var itemToggleAllVisible = function(item) {
		items.setAttribute('current_checked', 0);
		items.setAttribute('previous_checked', 0);
		processItemGrid(range(0, selectAllElements('.item-configuration tr .checkbox').length - 1), +item.getAttribute('checked') === 0);
	};
	var processItemGrid = function(itemIndexes, itemState) {
		var itemCount = 0;
		var itemGridLineSizeMaximum = +('1' + repeat(Math.min(elements.html('.item-configuration .total-results').length, 4), '0'));
		var pageResultCount = (+elements.html('.item-configuration .last-result') - +elements.html('.item-configuration .first-result') + 1);
		var totalResults = +elements.html('.item-configuration .total-results');
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
			var encodeCount = 1;
			var encodedGridLineItems = [];
			var index = ((currentPage * resultsPerPage) - resultsPerPage) + +itemIndex;
			var item = document.querySelector('.item-configuration .checkbox[index="' + itemIndex + '"]');
			var key = Math.floor(index / itemGridLineSizeMaximum);

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

			var itemGridLineIndex = index - (key * itemGridLineSizeMaximum);

			if (typeof itemState === 'boolean') {
				itemGrid[key] = replaceCharacter(itemGrid[key], itemGridLineIndex, +itemState);
			}

			itemGrid[key] = itemGrid[key].split("");
			itemGrid[key].map(function(itemStatus, itemStatusIndex) {
				if (itemStatus != itemGrid[key][itemStatusIndex + 1]) {
					encodedGridLineItems.push(itemStatus + encodeCount);
					encodeCount = 0;
				}

				encodeCount++;
			});
			item.setAttribute('checked', +itemGrid[key][itemGridLineIndex]);
			itemGrid[key] = encodedGridLineItems.join('_');
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

		processWindowEvents('resize');
		+elements.html('.total-checked') ? elements.removeClass('.item-configuration span.icon[item-function]', 'hidden') : elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		itemGridCount = itemCount;

		if (totalResults === +elements.html('.total-checked')) {
			elements.addClass('.item-configuration span.icon[item-function][process="downgrade"]', 'hidden');
		}

		requestParameters.items[requestParameters.table] = itemGrid;
	};
	elements.addClass('.item-configuration .item-controls', 'hidden');
	pagination.querySelector('.next').setAttribute('page', 0);
	pagination.querySelector('.previous').setAttribute('page', 0);

	if (proxyMessageContainer) {
		proxyMessageContainer.innerHTML = '<p class="message no-margin-top">Loading ...</p>';
	}

	if (!currentPage) {
		currentPage = pagination.hasAttribute('current_page') ? Math.max(1, +pagination.getAttribute('current_page')) : 1;

		if (
			requestParameters.action == 'search' &&
			previousAction == 'fetch'
		) {
			currentPage = 1;
		}
	}

	requestParameters.conditions = {
		order_id: orderId
	};
	requestParameters.current_page = currentPage;
	requestParameters.items[requestParameters.table] = itemGrid;
	requestParameters.limit = resultsPerPage;
	requestParameters.offset = ((currentPage * resultsPerPage) - resultsPerPage);
	requestParameters.sort.field = 'modified';
	sendRequest(function(response) {
		if (proxyMessageContainer) {
			proxyMessageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
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
					previousAction = 'fetch';
					requestParameters.data = {};
					closeFrames(defaultTable);
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

		if (response.processing) {
			var itemProcessingContainer = document.querySelector('.item-processing-container');
			var itemProcessingData = '<p class="message">Your recent bulk request to ' + response.processing.parameters.action + ' ' + response.processing.parameters.item_count + ' ' + response.processing.parameters.table + ' is in progress.</p>';
			var timeoutId = setTimeout(function() {}, 1);
			var processRequestProgress = function(response) {
				var requestProgress = response.processing.request_progress;
				elements.html('.progress-text, .progress', requestProgress + '%');
				elements.setAttribute('style', 'width: ' + requestProgress + '%');

				if (requestProgress < 100) {
					while (timeoutId--) {
						clearTimeout(timeoutId);
					}

					var timeoutId = setTimeout(function() {
						// ..
					}, 10000);
				}
			};
			itemProcessingData += '<p class="progress-text"></p>';
			itemProcessingData += '<div class="progress-container">';
			itemProcessingData += '<div class="progress"></div>';
			itemProcessingData += '</div>';
			elements.addClass('.item-configuration-container', 'hidden');
			elements.removeClass('.item-processing-container', 'hidden');
			itemProcessingContainer.innerHTML = itemProcessingData;
			processRequestProgress(response);
		}

		items.innerHTML = '<table class="table"><thead><tr><th style="width: 35px;"></th><th>Proxy IP</th></tr></thead><tbody></tbody></table>';
		response.data.map(function(item, index) {
			items.querySelector('table tbody').innerHTML += '<tr page="' + currentPage + '" proxy_id="' + item.id + '" class=""><td style="width: 1px;"><span checked="0" class="checkbox" index="' + index + '" proxy_id="' + item.id + '"></span></td><td><span class="details-container"><span class="details"><span class="detail"><strong>Status:</strong> ' + capitalizeString(item.status) + '</span><span class="detail"><strong>Proxy IP:</strong> ' + item.ip + '</span><span class="detail"><strong>Location:</strong> ' + item.city + ', ' + item.region + ' ' + item.country_code + ' </span><span class="detail"><strong>ISP:</strong> ' + item.asn + ' </span><span class="detail"><strong>HTTP + HTTPS Port:</strong> ' + (item.disable_http == 1 ? 'Disabled' : '80') + '</span><span class="detail"><strong>Username:</strong> ' + (item.username ? item.username : 'N/A') + '</span><span class="detail"><strong>Password:</strong> ' + (item.password ? item.password : 'N/A') + '</span><span class="detail"><strong>Whitelisted IPs:</strong> ' + (item.whitelisted_ips ? '<textarea>' + item.whitelisted_ips + '</textarea>' : 'N/A') + '</span></span></span><span class="table-text">' + item.ip + '</span></td>';
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
var processRequests = function(frameName, frameSelector) {
	// ..
};
requestParameters.action = 'fetch';
requestParameters.sort = {
	field: 'modified',
	order: 'DESC'
};
requestParameters.table = defaultTable;
requestParameters.url = defaultUrl;
