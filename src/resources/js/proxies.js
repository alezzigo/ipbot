var itemGrid = [];
var itemGridCount = 0;
var orderMessageContainer = document.querySelector('main .message-container.order');
var proxyMessageContainer = document.querySelector('main .message-container.proxies');
var processActions = function(frameName, frameSelector) {
	var orderId = document.querySelector('input[name="order_id"]').value;
	api.setRequestParameters({
		action: 'fetch',
		conditions: {
			foreign_key: 'order_id',
			foreign_value: orderId
		},
		limit: 10,
		offset: 0,
		table: 'actions',
		url: apiRequestParameters.current.settings.base_url + 'api/actions'
	});
	api.sendRequest(function(response) {
		var actionData = '<p class="error message">No recent order actions to list.</p>';

		if (response.data.length) {
			actionData = '<p class="message">Your most-recent actions for order #' + orderId + ' are displayed below.</p>';
			actionData += '<div class="details">';
			response.data.map(function(action, index) {
				var actionParameters = JSON.parse(action.encoded_parameters);
				actionData += '<div class="item-button item-container">';
				actionData += '<div class="item">';
				actionData += '<p><strong>Request to ' + actionParameters.action + ' ' + actionParameters.item_count + ' ' + actionParameters.table + '</strong></p>';

				if (action.created != action.modified) {
					actionData += '<p>Started at ' + action.created + ' ' + apiRequestParameters.current.settings.timezone.display + '</p>';
				}

				actionData += '<p>Completed at ' + action.modified + ' ' + apiRequestParameters.current.settings.timezone.display + '</p>';
				actionData += '<label class="label ' + (action.progress === 100 ? 'active' : 'inactive') + '">' + (action.progress === 100 ? 'Completed' : 'Interrupted') + ' ' + action.progress + '%</label>';
				actionData += '</div>';
				actionData += '</div>';
			});
			actionData += '</div>';
		}

		document.querySelector('.actions-container').innerHTML = actionData;
		api.setRequestParameters({
			action: apiRequestParameters.previous.action,
			conditions: apiRequestParameters.previous.conditions,
			limit: apiRequestParameters.previous.limit,
			offset: apiRequestParameters.previous.offset,
			table: apiRequestParameters.previous.table,
			url: apiRequestParameters.previous.url
		});
	});
};
var processCopy = function(frameName, frameSelector) {
	var processCopyFormat = function() {
		var frameData = {};
		elements.addClass(frameSelector + ' .copy', 'hidden');
		elements.removeClass(frameSelector + ' .loading', 'hidden');
		elements.setAttribute(frameSelector + ' .list-format select', 'disabled', 'disabled');
		elements.loop(frameSelector + ' input, ' + frameSelector + ' select, ' + frameSelector + ' textarea', function(index, element) {
			frameData[element.getAttribute('name')] = element.value;
		});
		api.setRequestParameters({
			action: frameName,
			data: frameData,
			items: {
				proxies: itemGrid
			}
		}, true);
		api.sendRequest(function(response) {
			document.querySelector(frameSelector + ' textarea[name="' + frameName + '"]').value = response.data;
			elements.addClass(frameSelector + ' .loading', 'hidden');
			elements.removeClass(frameSelector + ' .copy', 'hidden');
			elements.removeAttribute(frameSelector + ' .list-format select', 'disabled');
			api.setRequestParameters({
				action: apiRequestParameters.previous.action
			});
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
	api.setRequestParameters({
		action: 'downgrade'
	});
	api.sendRequest(function(response) {
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
			downgradeData += '<div class="details merged-order-details">';
			downgradeData += '<p class="message success">Your current order for ' + response.data.downgraded.order.quantity + ' ' + apiRequestParameters.current.table + ' will downgrade to the following order and invoice:</p>';
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

		if (apiRequestParameters.current.data.confirm_downgrade) {
			closeFrames(defaultTable);
			document.querySelector('.order-name').innerHTML = response.data.downgraded.order.quantity_pending + ' ' + response.data.downgraded.order.name;
			proxyMessageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
			api.setRequestParameters({
				action: 'fetch'
			});
			delete apiRequestParameters.current.data.confirm_downgrade;
		}

		downgradeContainer.innerHTML = downgradeData;
	});
};
var processEndpoint = function(frameName, frameSelector) {
	api.setRequestParameters({
		action: 'endpoint',
		data: {
			order_id: document.querySelector('input[name="order_id"]').value
		},
		table: 'orders',
		url: apiRequestParameters.current.settings.base_url + 'api/orders'
	}, true);
	api.sendRequest(function(response) {
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

		api.setRequestParameters({
			table: 'proxies',
			url: apiRequestParameters.current.settings.base_url + 'api/proxies'
		});
	});
};
var processGroup = function(frameName, frameSelector) {
	var groupGrid = {};
	var groupNameButton = document.querySelector(frameSelector + ' .group-name-button');
	var groupNameField = document.querySelector(frameSelector + ' .group-name-field');
	var groupTable = document.querySelector(frameSelector + ' .group-table');
	var orderId = document.querySelector('input[name="order_id"]').value;
	api.setRequestParameters({
		url: apiRequestParameters.current.settings.base_url + 'api/proxies'
	});
	var groupAdd = function(groupName) {
		api.setRequestParameters({
			action: frameName,
			data: {
				name: groupName,
				order_id: orderId
			}
		}, true);
		delete apiRequestParameters.current.data.id;
		api.sendRequest(function(response) {
			processGroupTable(response);
		});
	};
	var groupDelete = function(button, row) {
		var groupId = row.getAttribute('group_id');
		api.setRequestParameters({
			action: frameName,
			data: {
				id: [groupId]
			}
		}, true);
		delete apiRequestParameters.current.data.name;
		api.sendRequest(function(response) {
			delete groupGrid[frameName + groupId];
			processGroupTable(response);
		});
	};
	var groupEdit = function(button, row) {
		var processGroupEdit = function(row) {
			api.setRequestParameters({
				action: frameName,
				data: {
					id: row.getAttribute('group_id'),
					name: row.querySelector('.group-name-edit-field').value,
					order_id: orderId
				}
			}, true);
			api.sendRequest(function(response) {
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
		closeFrames();
		api.setRequestParameters({
			action: 'search',
			data: {
				groups: [button.getAttribute('group_id')]
			},
			table: 'proxies'
		}, true);
		itemGrid = [];
		itemGridCount = 0;
		api.sendRequest(function() {
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
		api.setRequestParameters({
			items: {
				proxy_groups: groupGrid
			}
		}, true);
	};
	var processGroupTable = function(response) {
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
	api.setRequestParameters({
		action: 'fetch',
		sort: {
			field: 'created'
		},
		table: 'proxy_groups',
		url: apiRequestParameters.current.settings.base_url + 'api/proxies'
	}, true);
	delete apiRequestParameters.current.limit;
	delete apiRequestParameters.current.offset;
	api.sendRequest(function(response) {
		api.setRequestParameters({
			limit: apiRequestParameters.previous.limit,
			offset: apiRequestParameters.previous.offset
		});
		processGroupTable(response);
	});
};
var processOrder = function() {
	api.setRequestParameters({
		action: 'view',
		conditions: {
			id: document.querySelector('input[name="order_id"]').value
		},
		table: 'orders',
		url: apiRequestParameters.current.settings.base_url + 'api/orders'
	});
	api.sendRequest(function(response) {
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
			api.setRequestParameters({
				table: 'proxies',
				url: apiRequestParameters.current.settings.base_url + 'api/proxies'
			});
			elements.html('.order-name', (response.data.order.quantity_active ? response.data.order.quantity_active : response.data.order.quantity) + ' ' + response.data.order.name);

			if (document.querySelector('.pagination')) {
				api.setRequestParameters({
					action: 'fetch'
				});
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

		if (
			typeof itemIndexes[1] === 'number' &&
			itemIndexes[1] < 0
		) {
			return;
		}

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
				itemGrid[key] = itemGrid[key].substr(0, itemGridLineIndex) + +itemState + itemGrid[key].substr(itemGridLineIndex + Math.max(1, ('' + +itemState).length))
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

		api.setRequestParameters({
			items: {
				proxies: itemGrid
			}
		}, true);
	};
	elements.addClass('.item-configuration .item-controls, .item-table', 'hidden');
	pagination.querySelector('.next').setAttribute('page', 0);
	pagination.querySelector('.previous').setAttribute('page', 0);

	if (proxyMessageContainer) {
		proxyMessageContainer.innerHTML = '<p class="message no-margin-top">Loading ...</p>';
	}

	if (!currentPage) {
		currentPage = pagination.hasAttribute('current_page') ? Math.max(1, +pagination.getAttribute('current_page')) : 1;

		if (
			apiRequestParameters.current.action == 'search' &&
			apiRequestParameters.previous.action == 'fetch'
		) {
			currentPage = 1;
		}
	}

	api.setRequestParameters({
		action: apiRequestParameters.current.action,
		conditions: {
			order_id: orderId
		},
		current_page: currentPage,
		limit: resultsPerPage,
		offset: ((currentPage * resultsPerPage) - resultsPerPage),
		table: 'proxies',
		url: apiRequestParameters.current.settings.base_url + 'api/proxies'
	});
	api.setRequestParameters({
		items: {
			proxies: itemGrid
		},
		sort: {
			field: 'modified'
		}
	}, true);
	api.sendRequest(function(response) {
		if (proxyMessageContainer) {
			proxyMessageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			apiRequestParameters.current.action == 'search' &&
			apiRequestParameters.current.data &&
			response.message
		) {
			api.setRequestParameters({
				defaults: {
					action: apiRequestParameters.current.action,
					table: 'proxies'
				}
			});
			setTimeout(function() {
				var itemsClear = document.querySelector('.item-configuration a.clear');
				itemsClear.removeEventListener('click', itemsClear.clickListener);
				itemsClear.clickListener = function() {
					api.setRequestParameters({
						data: {},
						defaults: {
							action: 'fetch',
							table: 'proxies'
						}
					});
					closeFrames(apiRequestParameters.current.defaults);
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
			(
				!response.data.length &&
				!response.processing
			)
		) {
			return items.innerHTML = '';
		}

		if (response.processing) {
			var itemProcessingContainer = document.querySelector('.item-processing-container');
			var actionDetails = 'to ' + response.processing.parameters.action + ' ' + response.processing.parameters.item_count + ' ' + response.processing.parameters.table;
			var itemProcessingData = '<p class="message">Your recent bulk action ' + actionDetails + ' is in progress.</p>';
			var timeoutId = setTimeout(function() {}, 1);
			var processActionProgress = function(response) {
				var actionProgress = (response.processing ? response.processing.progress : 0);
				var actionProcessed = (response.processing ? response.processing.processed : false);
				elements.html('.progress-text', actionProgress + '%');
				elements.setAttribute('.progress', 'style', 'width: ' + actionProgress + '%');

				if (
					actionProgress < 100 &&
					!actionProcessed
				) {
					while (timeoutId--) {
						clearTimeout(timeoutId);
					}

					var timeoutId = setTimeout(function() {
						api.setRequestParameters({
							action: 'fetch',
							conditions: {
								foreign_key: response.processing.foreign_key,
								foreign_value: response.processing.foreign_value
							},
							offset: 0,
							table: 'actions',
							url: apiRequestParameters.current.settings.base_url + 'api/actions'
						});

						api.sendRequest(function(response) {
							if (response.data.length) {
								response.processing = response.data[0];
							}

							processActionProgress(response);
						});
					}, 10000);
				} else {
					api.setRequestParameters({
						action: apiRequestParameters.previous.action,
						conditions: apiRequestParameters.previous.conditions,
						offset: apiRequestParameters.previous.offset,
						table: apiRequestParameters.previous.table,
						url: apiRequestParameters.previous.url
					});
					elements.addClass('.item-processing-container', 'hidden');
					elements.removeClass('.item-configuration-container', 'hidden');

					if (!response.processing.token_id) {
						processProxies(false, false, 1);
					}

					if (
						proxyMessageContainer &&
						actionProgress < 100
					) {
						proxyMessageContainer.innerHTML = '<p class="error message">Action ' + (response.processing.id ? '#' + response.processing.id + ' ' : '') + actionDetails + ' was interrupted at ' + actionProgress + '%, please try again.</p>';
						processWindowEvents('resize');
					}
				}
			};
			itemProcessingData += '<p class="progress-text"></p>';
			itemProcessingData += '<div class="progress-container">';
			itemProcessingData += '<div class="progress"></div>';
			itemProcessingData += '</div>';
			elements.addClass('.item-configuration-container', 'hidden');
			elements.removeClass('.item-processing-container', 'hidden');
			itemProcessingContainer.innerHTML = itemProcessingData;
			processActionProgress(response);
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
		elements.removeClass('.item-configuration .item-controls, .item-table', 'hidden');
		itemGrid = response.items['proxies'];

		if (apiRequestParameters.current.action != 'search') {
			api.setRequestParameters(apiRequestParameters.current.defaults);
		}

		api.setRequestParameters({
			tokens: {
				proxies: response.tokens['proxies']
			}
		}, true);
		processItemGrid(range(0, response.data.length - 1));
	});
};
var processRequests = function(frameName, frameSelector) {
	// ..
};
api.setRequestParameters({
	action: 'fetch',
	defaults: {
		action: 'fetch',
		table: 'proxies'
	},
	sort: {
		field: 'modified',
		order: 'DESC'
	},
	table: 'proxies'
});
