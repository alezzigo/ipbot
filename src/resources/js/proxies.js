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
			elements.html('.message-container.proxies', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
			elements.html('.order-name', response.data.downgraded.order.quantity_pending + ' ' + response.data.downgraded.order.name);
			api.setRequestParameters({
				action: 'fetch'
			});
			delete apiRequestParameters.current.data.confirm_downgrade;
		}

		downgradeContainer.innerHTML = downgradeData;
	});
};
var processDownload = function(frameName, frameSelector) {
	var downloadOptions = {
		columns: [
			{
				name: 'ip',
				value: 'ip'
			},
			{
				name: 'port',
				value: 'port'
			},
			{
				name: 'user',
				value: 'username'
			},
			{
				name: 'pass',
				value: 'password'
			}
		],
		delimiters: [
			':',
			';',
			',',
			'@'
		],
		formats: [
			{
				name: '.txt file',
				value: 'txt'
			}
			// ..
		],
		separators: [
			{
				name: 'New Line',
				value: 'new_line'
			},
			{
				name: 'Comma',
				value: 'comma'
			},
			{
				name: 'Hyphen',
				value: 'hyphen'
			},
			{
				name: 'Plus',
				value: 'plus'
			},
			{
				name: 'Semicolon',
				value: 'semicolon'
			},
			{
				name: 'Space',
				value: 'space'
			},
			{
				name: 'Underscore',
				value: 'underscore'
			}
		],
		protocols: [
			{
				name: 'HTTP / HTTPS',
				value: 'http'
			}
		]
	};
	var downloadData = '';
	var processDownloadFormat = function() {
		var frameData = {};
		elements.addClass(frameSelector + ' .item-controls', 'hidden');
		elements.removeClass(frameSelector + ' .loading', 'hidden');
		elements.setAttribute(frameSelector + ' input[name="confirm_download"]', 'value', 0);
		elements.setAttribute(frameSelector + ' .list-format select', 'disabled', 'disabled');
		elements.loop(frameSelector + ' input, ' + frameSelector + ' select, ' + frameSelector + ' textarea', function(index, element) {
			frameData[element.getAttribute('name')] = element.value;
		});
		api.setRequestParameters({
			action: frameName,
			data: frameData
		}, true);

		if (apiRequestParameters.current.items.proxies.length === 1) {
			api.sendRequest(function(response) {
				document.querySelector(frameSelector + ' textarea[name="copy"]').value = response.data;
				elements.addClass(frameSelector + ' .loading', 'hidden');
				elements.removeAttribute(frameSelector + ' .list-format select', 'disabled');
				elements.removeClass(frameSelector + ' .item-controls', 'hidden');
				api.setRequestParameters({
					action: apiRequestParameters.previous.action
				});
			});
		} else {
			elements.addClass(frameSelector + ' .loading', 'hidden');
			elements.removeClass(frameSelector + ' .download', 'hidden');
			elements.removeAttribute(frameSelector + ' .list-format select', 'disabled');
		}

		elements.setAttribute(frameSelector + ' input[name="confirm_download"]', 'value', 1);
	};

	downloadData += '<div class="clear"></div>';
	downloadData += '<input class="hidden" name="confirm_download" type="hidden" value="0">';
	downloadData += '<label>Proxy List Format</label>';
	downloadData += '<div class="field-group list-format no-margin-top">';

	for (var i = 1; i < 5; i++) {
		downloadData += '<select class="ipv4-column-' + i + '" name="ipv4_column_' + i + '">';

		for (var columnOptionKey in downloadOptions.columns) {
			downloadData += '<option ' + ((+(columnOptionKey) + 1) === i ? 'selected' : '') + ' value="' + downloadOptions.columns[columnOptionKey].value + '">' + downloadOptions.columns[columnOptionKey].name + '</option>';
		}

		downloadData += '</select>';

		if (i < 4) {
			downloadData += '<select class="ipv4-delimiter-' + i + '" name="ipv4_delimiter_' + i + '">';

			for (var delimiterOptionKey in downloadOptions.delimiters) {
				downloadData += '<option value="' + downloadOptions.delimiters[delimiterOptionKey] + '">' + downloadOptions.delimiters[delimiterOptionKey] + '</option>';
			}

			downloadData += '</select>';
		}
	}

	downloadData += '</div>';
	downloadData += '<div class="clear"></div>';
	downloadData += '<div class="align-left">';
	downloadData += '<label class="clear">Proxy List Type</label>';
	downloadData += '<div class="field-group no-margin-top proxy-list-type">';
	downloadData += '<select class="proxy-list-type" name="proxy_list_type">';

	for (var protocolOptionKey in downloadOptions.protocols) {
		downloadData += '<option value="' + downloadOptions.protocols[protocolOptionKey].value + '">' + downloadOptions.protocols[protocolOptionKey].name + '</option>';
	}

	downloadData += '</select>';
	downloadData += '</div>';
	downloadData += '</div>';
	downloadData += '<div class="align-left">';
	downloadData += '<label class="clear">Separated By</label>';
	downloadData += '<div class="field-group no-margin-top separated-by">';
	downloadData += '<select class="separated-by" name="separated_by">';

	for (var separatorOptionKey in downloadOptions.separators) {
		downloadData += '<option value="' + downloadOptions.separators[separatorOptionKey].value + '">' + downloadOptions.separators[separatorOptionKey].name + '</option>';
	}

	downloadData += '</select>';
	downloadData += '</div>';
	downloadData += '</div>';
	downloadData += '<div class="align-left">';
	downloadData += '<label class="clear">Download Format</label>';
	downloadData += '<div class="field-group no-margin-top download-format">';
	downloadData += '<select class="download_format" name="download_format">';

	for (var formatOptionKey in downloadOptions.formats) {
		downloadData += '<option value="' + downloadOptions.formats[formatOptionKey].value + '">' + downloadOptions.formats[formatOptionKey].name + '</option>';
	}

	downloadData += '</select>';
	downloadData += '</div>';
	downloadData += '</div>';
	downloadData += '<div class="clear"></div>';
	downloadData += '<p class="message loading">Loading</p>';
	downloadData += '<div class="hidden item-controls">';
	downloadData += '<label>Proxy List</label>';
	downloadData += '<div class="copy-textarea-container">';
	downloadData += '<textarea class="copy" id="copy" name="copy"></textarea>';
	downloadData += '</div>';
	downloadData += '</div>';
	downloadData += '<div class="clear"></div>';
	elements.html('.download-container', downloadData);
	elements.loop(frameSelector + ' select', function(index, element) {
		element.removeEventListener('change', element.changeListener);
		element.changeListener = function() {
			processDownloadFormat();
		};
		element.addEventListener('change', element.changeListener);
	});
	var itemsCopy = document.querySelector(frameSelector + ' .button.copy');
	itemsCopy.removeEventListener('click', itemsCopy.clickListener);
	itemsCopy.clickListener = function() {
		document.querySelector('[name="copy"]').select();
		document.execCommand(frameName);
	};
	itemsCopy.addEventListener('click', itemsCopy.clickListener);
	processDownloadFormat();
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
		elements.html('.message-container.proxies', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		processWindowEvents('resize');

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
		elements.addClass('.item-configuration .item-controls', 'hidden');
		elements.html('.message-container.proxies', '<p class="message no-margin-top">Loading</p>');
		closeFrames();
		api.setRequestParameters({
			action: 'search',
			data: {
				groups: [button.getAttribute('group_id')]
			},
			items: {
				proxies: []
			},
			table: 'proxies'
		}, true);
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
	groupTable.innerHTML = '<p class="message no-margin-bottom">Loading</p>';
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
	let orderId = document.querySelector('input[name="order_id"]').value;
	api.setRequestParameters({
		action: 'view',
		conditions: {
			id: orderId
		},
		order_id: orderId,
		table: 'orders',
		url: apiRequestParameters.current.settings.base_url + 'api/orders'
	});
	api.sendRequest(function(response) {
		elements.html('.message-container.order', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.data.order) {
			api.setRequestParameters({
				action: 'fetch',
				table: 'proxies',
				url: apiRequestParameters.current.settings.base_url + 'api/proxies'
			});
			api.setRequestParameters({
				list_proxies: {
					callback: function(response, itemListParameters) {
						processProxies(response, itemListParameters);
					},
					initial: true,
					messages: {
						order: '',
						proxies: '<p class="message no-margin-top">Loading</p>'
					},
					name: 'list_proxies',
					options: [
						{
							attributes: [
								{
									name: 'checked',
									value: '0'
								},
								{
									name: 'class',
									value: 'align-left checkbox no-margin-left'
								},
								{
									name: 'index',
									value: 'all-visible'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button icon upgrade tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Add more proxies to current order'
								},
								{
									name: 'data-title',
									value: 'Add more proxies to current order'
								},
								{
									name: 'href',
									value: apiRequestParameters.current.settings.base_url + 'orders?' + apiRequestParameters.current.order_id + '#upgrade'
								},
								{
									name: 'frame',
									value: 'upgrade'
								}
							],
							tag: 'a'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Downgrade current order to selected proxies'
								},
								{
									name: 'frame',
									value: 'downgrade'
								},
								{
									name: 'item-function'
								},
								{
									name: 'process',
									value: 'downgrade'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Configure proxy API endpoint settings'
								},
								{
									name: 'frame',
									value: 'endpoint'
								},
								{
									name: 'item-function'
								},
								{
									name: 'process',
									value: 'endpoint'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Proxy search and filter'
								},
								{
									name: 'frame',
									value: 'search'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Manage proxy groups'
								},
								{
									name: 'frame',
									value: 'group'
								},
								{
									name: 'process',
									value: 'group'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'View log of recent order actions'
								},
								{
									name: 'frame',
									value: 'actions'
								},
								{
									name: 'process',
									value: 'actions'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button hidden icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Download proxy request logs'
								},
								{
									name: 'frame',
									value: 'requests'
								},
								{
									name: 'item-function'
								},
								{
									name: 'process',
									value: 'requests'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button hidden icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Configure proxy replacement settings'
								},
								{
									name: 'frame',
									value: 'replace'
								},
								{
									name: 'item-function'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button hidden icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Configure proxy gateway rotation settings'
								},
								{
									name: 'frame',
									value: 'rotate'
								},
								{
									name: 'item-function'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button hidden icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Configure proxy authentication settings'
								},
								{
									name: 'frame',
									value: 'authenticate'
								},
								{
									name: 'item-function'
								}
							],
							tag: 'span'
						},
						{
							attributes: [
								{
									name: 'class',
									value: 'button frame-button hidden icon tooltip tooltip-bottom'
								},
								{
									name: 'data-title',
									value: 'Download list of selected proxies'
								},
								{
									name: 'frame',
									value: 'download'
								},
								{
									name: 'item-function'
								},
								{
									name: 'process',
									value: 'download'
								}
							],
							tag: 'span'
						},
					],
					page: 1,
					results_per_page: 100,
					selector: '.item-list[table="proxies"]',
					table: 'proxies'
				}
			});
			elements.html('.order-name', (response.data.order.quantity_active ? response.data.order.quantity_active : response.data.order.quantity) + ' ' + response.data.order.name);
			processItemList('list_proxies');

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
var processProxies = function(response, itemListParameters) {
	if (typeof itemListParameters !== 'object') {
		if (apiRequestParameters.current.action === 'search') {
			var mergeRequestParameters = {
				items: {}
			};
			mergeRequestParameters.items['proxies'] = [];
			api.setRequestParameters(mergeRequestParameters, true);
		}

		processItemList('list_proxies');
	} else {
		elements.html('.message-container.proxies', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');

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
					var mergeRequestParameters = {
						items: {},
						list_proxies: {
							page: 1
						}
					};
					mergeRequestParameters.items['proxies'] = [];
					api.setRequestParameters(mergeRequestParameters, true);
					closeFrames(apiRequestParameters.current.defaults);
					processItemList('list_proxies');
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
			return elements.html(itemListParameters.selector + ' .item-table', '');
		}

		if (response.processing) {
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
						var mergeRequestParameters = {
							list_proxies: {
								page: 1
							}
						};
						api.setRequestParameters(mergeRequestParameters, true);
						processItemList('list_proxies');
					}

					if (response.processing.chunks > 1) {
						elements.html('.message-container.proxies', '<p class="message success">Your recent bulk action ' + actionDetails + ' is completed.</p>');

						if (actionProgress < 100) {
							elements.html('.message-container.proxies', '<p class="error message">Action ' + (response.processing.id ? '#' + response.processing.id + ' ' : '') + actionDetails + ' was interrupted at ' + actionProgress + '%, please try again.</p>');
							processWindowEvents('resize');
						}
					}
				}
			};
			itemProcessingData += '<p class="progress-text"></p>';
			itemProcessingData += '<div class="progress-container">';
			itemProcessingData += '<div class="progress"></div>';
			itemProcessingData += '</div>';
			elements.addClass('.item-configuration-container', 'hidden');
			elements.removeClass('.item-processing-container', 'hidden');
			elements.html('.item-processing-container', itemProcessingData);
			processActionProgress(response);
		}

		elements.html(itemListParameters.selector + ' .item-table', '<table class="table"><thead><tr><th style="width: 35px;"></th><th>Proxy IP</th></tr></thead><tbody></tbody></table>');
		let itemListData = '';

		for (itemListDataKey in response.data) {
			let item = response.data[itemListDataKey];
			itemListData += '<tr page="' + apiRequestParameters.current.list_proxies.page + '" proxy_id="' + item.id + '" class="">';
			itemListData += '<td style="width: 1px;">';
			itemListData += '<span checked="0" class="checkbox" index="' + itemListDataKey + '" proxy_id="' + item.id + '">';
			itemListData += '</span>';
			itemListData += '</td>';
			itemListData += '<td>';
			itemListData += '<span class="details-container">';
			itemListData += '<span class="details">';
			itemListData += '<span class="detail"><strong>Status:</strong> ' + capitalizeString(item.status) + '</span>';
			itemListData += '<span class="detail"><strong>Proxy IP:</strong> ' + item.ip + '</span>';
			itemListData += '<span class="detail"><strong>Location:</strong> ' + item.city + ', ' + item.region + ' ' + item.country_code + ' </span>';
			itemListData += '<span class="detail"><strong>ISP:</strong> ' + item.asn + ' </span>';
			itemListData += '<span class="detail"><strong>HTTP + HTTPS Port:</strong> ' + (item.disable_http == 1 ? 'Disabled' : '80') + '</span>';
			itemListData += '<span class="detail"><strong>Username:</strong> ' + (item.username ? item.username : 'N/A') + '</span>';
			itemListData += '<span class="detail"><strong>Password:</strong> ' + (item.password ? item.password : 'N/A') + '</span>';
			itemListData += '<span class="detail"><strong>Whitelisted IPs:</strong> ' + (item.whitelisted_ips ? '<textarea>' + item.whitelisted_ips + '</textarea>' : 'N/A') + '</span>';
			itemListData += '</span>';
			itemListData += '</span>';
			itemListData += '<span class="table-text">' + item.ip + '</span>';
			itemListData += '</td>';
		}

		elements.html(itemListParameters.selector + ' .item-table table tbody', itemListData);

		if (apiRequestParameters.current.action != 'search') {
			api.setRequestParameters(apiRequestParameters.current.defaults);
		}
	}
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
