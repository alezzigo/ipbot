var processActions = function(frameName, frameSelector) {
	const orderId = elements.get('input[name="order_id"]').value;
	api.setRequestParameters({
		action: 'fetch',
		conditions: {
			foreignKey: 'order_id',
			foreignValue: orderId
		},
		limit: 10,
		offset: 0,
		table: 'actions',
		url: apiRequestParameters.current.settings.baseUrl + 'api/actions'
	});
	api.sendRequest(function(response) {
		let actionData = '<p class="error message">No recent order actions to list.</p>';

		if (response.data.length) {
			actionData = '<p class="message">Your most-recent actions for order #' + orderId + ' are displayed below.</p>';
			actionData += '<div class="details">';

			for (let actionDataKey in response.data) {
				let action = response.data[actionDataKey];
				let actionParameters = action.encodedParameters;
				actionData += '<div class="item-button item-container">';
				actionData += '<div class="item">';
				actionData += '<p><strong>Request to ' + actionParameters.action + ' ' + actionParameters.itemCount + ' ' + actionParameters.table.replace('_', ' ') + '</strong></p>';

				if (action.created != action.modified) {
					actionData += '<p>Started at ' + action.created + ' ' + apiRequestParameters.current.settings.timezone.display + '</p>';
				}

				actionData += '<p>Completed at ' + action.modified + ' ' + apiRequestParameters.current.settings.timezone.display + '</p>';
				actionData += '<label class="label ' + (action.progress === 100 ? 'active' : 'inactive') + '">' + (action.progress === 100 ? 'Completed' : 'Interrupted') + ' ' + action.progress + '%</label>';
				actionData += '</div>';
				actionData += '</div>';
			}

			actionData += '</div>';
		}

		api.setRequestParameters({
			action: apiRequestParameters.previous.action,
			conditions: apiRequestParameters.previous.conditions,
			limit: apiRequestParameters.previous.limit,
			offset: apiRequestParameters.previous.offset,
			table: apiRequestParameters.previous.table,
			url: apiRequestParameters.previous.url
		});
		elements.html('.actions-container', actionData);
	});
};
var processDowngrade = function() {
	api.setRequestParameters({
		action: 'downgrade'
	});
	api.sendRequest(function(response) {
		let downgradeData = '';
		elements.setAttribute('.button.submit', 'disabled');
		elements.html('.downgrade-configuration .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));

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
			downgradeData += '<p>' + response.data.downgraded.order.quantityPending + ' ' + response.data.downgraded.order.name + '</p>';
			downgradeData += '<p class="no-margin-bottom">' + response.data.downgraded.order.pricePending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + ' for ' + response.data.downgraded.order.intervalValue + ' ' + response.data.downgraded.order.intervalType + (response.data.downgraded.order.intervalValue !== 1 ? 's' : '') + '</p>';
			downgradeData += '<div class="item-link-container"></div>';
			downgradeData += '</div>';
			downgradeData += '<div class="align-left item-container no-margin-top no-padding">';
			downgradeData += '<h2>Downgraded Invoice Pricing Details</h2>';
			downgradeData += '<p><strong>Subtotal</strong><br>' + response.data.downgraded.invoice.subtotalPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p><strong>Shipping</strong><br>' + response.data.downgraded.invoice.shippingPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p><strong>Tax</strong><br>' + response.data.downgraded.invoice.taxPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p><strong>Total</strong><br>' + response.data.downgraded.invoice.totalPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</p>';
			downgradeData += '<p class="no-margin-bottom"><strong>Amount Paid</strong><br><span' + (response.data.downgraded.invoice.amountPaid ? ' class="paid"' : '') + '>' + response.data.downgraded.invoice.amountPaid.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.downgraded.invoice.currency + '</span>' + (response.data.downgraded.invoice.amountPaid ? '<br><span class="note">The amount paid will be added to your account balance and won\'t automatically apply to the remaining amount due for the downgraded order.</span>' : '') + '</p>';
			downgradeData += '</div>';
			downgradeData += '</div>';
			elements.removeAttribute('.button.submit', 'disabled');
		}

		if (apiRequestParameters.current.data.confirmDowngrade) {
			closeFrames(defaultTable);
			elements.html('.message-container.proxies', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
			elements.html('.order-name', response.data.downgraded.order.quantityPending + ' ' + response.data.downgraded.order.name);
			api.setRequestParameters({
				action: 'fetch'
			});
			delete apiRequestParameters.current.data.confirmDowngrade;
		}

		elements.html('.downgrade-container', downgradeData);
	});
};
var processDownload = function(frameName, frameSelector) {
	const downloadOptions = {
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
	let downloadData = '';
	const processDownloadFormat = function() {
		let frameData = {};
		elements.addClass(frameSelector + ' .item-controls', 'hidden');
		elements.removeClass(frameSelector + ' .loading', 'hidden');
		elements.setAttribute(frameSelector + ' input[name="confirm_download"]', 'value', 0);
		elements.setAttribute(frameSelector + ' .list-format select', 'disabled', 'disabled');
		elements.loop(frameSelector + ' input, ' + frameSelector + ' select, ' + frameSelector + ' textarea', function(index, element) {
			frameData[element.getAttribute('name')] = element.value;
		});
		api.setRequestParameters({
			action: frameName,
			data: frameData,
			itemListName: snakeCaseString('listProxyItems'),
			url: apiRequestParameters.current.settings.baseUrl + 'api/' + apiRequestParameters.current.defaults.table
		}, true);

		if (apiRequestParameters.current.items.listProxyItems.data.length === 1) {
			api.sendRequest(function(response) {
				elements.addClass(frameSelector + ' .loading', 'hidden');
				elements.get(frameSelector + ' textarea[name="copy"]').value = response.data;
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

	for (let i = 1; i < 5; i++) {
		downloadData += '<select class="ipv4-column' + i + '" name="ipv4_column' + i + '">';

		for (let columnOptionKey in downloadOptions.columns) {
			downloadData += '<option ' + ((+(columnOptionKey) + 1) === i ? 'selected' : '') + ' value="' + downloadOptions.columns[columnOptionKey].value + '">' + downloadOptions.columns[columnOptionKey].name + '</option>';
		}

		downloadData += '</select>';

		if (i < 4) {
			downloadData += '<select class="ipv4-delimiter' + i + '" name="ipv4_delimiter' + i + '">';

			for (let delimiterOptionKey in downloadOptions.delimiters) {
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

	for (let protocolOptionKey in downloadOptions.protocols) {
		downloadData += '<option value="' + downloadOptions.protocols[protocolOptionKey].value + '">' + downloadOptions.protocols[protocolOptionKey].name + '</option>';
	}

	downloadData += '</select>';
	downloadData += '</div>';
	downloadData += '</div>';
	downloadData += '<div class="align-left">';
	downloadData += '<label class="clear">Separated By</label>';
	downloadData += '<div class="field-group no-margin-top separated-by">';
	downloadData += '<select class="separated-by" name="separated_by">';

	for (let separatorOptionKey in downloadOptions.separators) {
		downloadData += '<option value="' + downloadOptions.separators[separatorOptionKey].value + '">' + downloadOptions.separators[separatorOptionKey].name + '</option>';
	}

	downloadData += '</select>';
	downloadData += '</div>';
	downloadData += '</div>';
	downloadData += '<div class="align-left">';
	downloadData += '<label class="clear">Download Format</label>';
	downloadData += '<div class="field-group no-margin-top download-format">';
	downloadData += '<select class="download-format" name="download_format">';

	for (let formatOptionKey in downloadOptions.formats) {
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
	let itemsCopy = elements.get(frameSelector + ' .button.copy');
	itemsCopy.removeEventListener('click', itemsCopy.clickListener);
	itemsCopy.clickListener = function() {
		elements.get('[name="copy"]').select();
		document.execCommand('copy');
	};
	itemsCopy.addEventListener('click', itemsCopy.clickListener);
	processDownloadFormat();
};
var processEndpoint = function(frameName, frameSelector) {
	const orderId = elements.get('input[name="order_id"]').value;
	api.setRequestParameters({
		action: 'endpoint',
		data: {
			orderId: orderId
		},
		table: 'orders',
		url: apiRequestParameters.current.settings.baseUrl + 'api/orders'
	}, true);
	api.sendRequest(function(response) {
		elements.html('.message-container.proxies', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		processWindowEvents('resize');

		if (response.data) {
			elements.addClass('.endpoint-enabled-container', 'hidden');
			let endpointEnableCheckboxInput = elements.get('.endpoint-enable');
			let endpointEnableCheckboxLabel = elements.get('label[for="endpoint-enable"]');
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

			if (typeof response.data.endpointEnable !== 'undefined') {
				if (response.data.endpointEnable) {
					elements.removeClass('.endpoint-enabled-container', 'hidden');
				}

				let endpointShowDocumentation = elements.get('.endpoint-show-documentation');
				endpointShowDocumentation.removeEventListener('click', endpointShowDocumentation.clickListener);
				endpointShowDocumentation.clickListener = function() {
					if (elements.hasClass('.endpoint-documentation', 'hidden')) {
						elements.removeClass('.endpoint-documentation', 'hidden');
						elements.addClass('.endpoint-show-documentation', 'hidden');
					}
				};
				endpointShowDocumentation.addEventListener('click', endpointShowDocumentation.clickListener);
				elements.setAttribute('.endpoint-enable', 'checked', +response.data.endpointEnable);
				elements.setAttribute('.endpoint-password', 'value', response.data.endpointPassword ? response.data.endpointPassword : '');
				elements.setAttribute('.endpoint-require-authentication', 'checked', +response.data.endpointRequireAuthentication);
				elements.setAttribute('.endpoint-require-match', 'checked', +response.data.endpointRequireMatch);
				elements.setAttribute('.endpoint-username', 'value', response.data.endpointUsername ? response.data.endpointUsername : '');
				elements.html('.endpoint-whitelisted-ips', response.data.endpointWhitelistedIps ? response.data.endpointWhitelistedIps : '');
			}
		}

		api.setRequestParameters({
			table: 'proxies',
			url: apiRequestParameters.current.settings.baseUrl + 'api/proxies'
		});
	});
};
var processGroup = function() {
	api.setRequestParameters({
		listProxyGroupItems: {
			action: 'fetch',
			callback: function(response, itemListParameters) {
				processGroupItems(response, itemListParameters);
			},
			initial: true,
			messages: {
				groups: '',
				status: '<p class="message no-margin-top">Loading</p>'
			},
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
							value: 'button icon delete frame-button tooltip tooltip-bottom'
						},
						{
							name: 'item_function'
						},
						{
							name: 'item_title',
							value: 'Remove selected proxy groups'
						},
						{
							name: 'process',
							value: 'remove'
						}
					],
					tag: 'span'
				}
			],
			page: 1,
			resultsPerPage: 10,
			selector: '.item-list[table="proxy_groups"]',
			table: 'proxy_groups'
		}
	}, true);
	processItemList('listProxyGroupItems');
};
const processGroupItems = function(response, itemListParameters) {
	if (typeof itemListParameters !== 'object') {
		processItemList('listProxyGroupItems');
	} else {
		let itemListData = '';
		let groupNameButton = elements.get('.group-name-button');
		let groupNameField = elements.get('.group-name-field');
		const processGroupAdd = function(groupName) {
			api.setRequestParameters({
				action: 'add',
				data: {
					name: groupName,
					orderId: apiRequestParameters.current.orderId
				}
			});
			api.sendRequest(function(response) {
				api.setRequestParameters({
					action: 'fetch',
					items: {
						listProxyGroupItems : {
							data: [],
							table: 'proxy_groups'
						}
					},
				}, true);
				processItemList('listProxyGroupItems', function() {
					elements.html('.message-container.groups', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
				});
			});
		};
		const processGroupEdit = function(button, row) {
			const processGroupEdit = function(row) {
				api.setRequestParameters({
					action: 'edit',
					data: {
						id: row.getAttribute('group_id'),
						name: row.querySelector('.group-name-edit-field').value,
						orderId: apiRequestParameters.current.orderId
					}
				}, true);
				api.sendRequest(function(response) {
					api.setRequestParameters({
						action: 'fetch'
					});
					processItemList('listProxyGroupItems', function() {
						elements.html('.message-container.groups', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
					});
				});
			};
			let originalRow = row.querySelector('.table-text').innerHTML;
			let groupNameEditData = '<div class="field-group no-margin">';
			groupNameEditData += '<input class="group-name-edit-field no-margin" id="group-name-edit" name="group_name" type="text" value="' + row.querySelector('.view').innerText + '">';
			groupNameEditData += '<button class="button group-name-save-edit-button">Save</button>';
			groupNameEditData += '<button class="button group-name-cancel-edit-button">Cancel</button>';
			groupNameEditData += '</div>';
			row.querySelector('.table-text').innerHTML = groupNameEditData;
			row = elements.get(itemListParameters.selector + ' tbody tr[group_id="' + row.getAttribute('group_id') + '"]');
			let groupNameCancelEditButton = row.querySelector('.group-name-cancel-edit-button');
			let groupNameEditField = row.querySelector('.group-name-edit-field');
			let groupNameSaveEditButton = row.querySelector('.group-name-save-edit-button');
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
		const processGroupView = function(groupId) {
			closeFrames();
			api.setRequestParameters({
				action: 'search',
				data: {
					groups: [groupId]
				},
				items: {
					listProxyItems: {
						data: [],
						table: 'proxies'
					}
				},
				listProxyGroupItems: {
					initial: true
				},
				table: 'proxies'
			}, true);
			api.sendRequest(function() {
				processItemList('listProxyItems');
			});
		};
		elements.html(itemListParameters.selector + ' .items', '<table class="table"><thead><th style="width: 35px;"></th><th>Group Name</th></thead><tbody></tbody></table>');
		groupNameField.removeEventListener('keydown', groupNameField.keydownListener);
		groupNameButton.removeEventListener('click', groupNameButton.clickListener);
		groupNameField.keydownListener = function(event) {
			if (event.key == 'Enter') {
				processGroupAdd(groupNameField.value);
			}
		};
		groupNameButton.clickListener = function() {
			processGroupAdd(groupNameField.value);
		};
		groupNameField.addEventListener('keydown', groupNameField.keydownListener);
		groupNameButton.addEventListener('click', groupNameButton.clickListener);

		if (response.data.length) {
			for (let itemListDataKey in response.data) {
				let intervalSelectTypes = intervalSelectValues = quantitySelectValues = '';
				let item = response.data[itemListDataKey];
				itemListData += '<tr group_id="' + item.id + '" class="">';
				itemListData += '<td style="width: 1px;">';
				itemListData += '<span checked="0" class="checkbox" index="' + itemListDataKey + '" group_id="' + item.id + '"></span>';
				itemListData += '</td>';
				itemListData += '<td>';
				itemListData += '<span class="table-text">';
				itemListData += '<a class="view" group_id="' + item.id + '" href="javascript:void(0);">' + item.name + '</a>';
				itemListData += '</span>';
				itemListData += '<span class="table-actions">';
				itemListData += '<span class="button edit icon" group_id="' + item.id + '"></span>';
				itemListData += '</span>';
				itemListData += '</td>';
			}

			elements.html(itemListParameters.selector + ' .items tbody', itemListData);
			selectAllElements(itemListParameters.selector + ' .items tbody tr', function(selectedElementKey, selectedElement) {
				let groupEditButton = selectedElement.querySelector('.edit');
				let groupViewButton = selectedElement.querySelector('.view');
				groupEditButton.removeEventListener('click', groupEditButton.clickListener);
				groupViewButton.removeEventListener('click', groupViewButton.clickListener);
				groupEditButton.clickListener = function() {
					processGroupEdit(groupEditButton, selectedElement);
				};
				groupViewButton.clickListener = function() {
					processGroupView(groupViewButton.getAttribute('group_id'));
				};
				groupEditButton.addEventListener('click', groupEditButton.clickListener);
				groupViewButton.addEventListener('click', groupViewButton.clickListener);
			});
		}

		elements.addClass('.submit[frame="group"]', 'hidden');

		if (+elements.html('.item-list[table="proxies"] .total-checked')) {
			elements.removeClass('.submit[frame="group"]', 'hidden');
		};

		elements.html('.message-container.groups', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
	}
};
var processOrder = function() {
	const orderId = elements.get('input[name="order_id"]').value;
	api.setRequestParameters({
		action: 'view',
		conditions: {
			id: orderId
		},
		orderId: orderId,
		table: 'orders',
		url: apiRequestParameters.current.settings.baseUrl + 'api/orders'
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
				listProxyItems: {
					action: 'fetch',
					callback: function(response, itemListParameters) {
						processProxyItems(response, itemListParameters);
					},
					initial: true,
					messages: {
						proxies: '',
						status: '<p class="message no-margin-top">Loading</p>'
					},
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
									name: 'item_title',
									value: 'Add more proxies to current order'
								},
								{
									name: 'href',
									value: apiRequestParameters.current.settings.baseUrl + 'orders?' + apiRequestParameters.current.orderId + '#upgrade'
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
									name: 'frame',
									value: 'downgrade'
								},
								{
									name: 'item_function'
								},
								{
									name: 'item_title',
									value: 'Downgrade current order to selected proxies'
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
									name: 'frame',
									value: 'endpoint'
								},
								{
									name: 'item_title',
									value: 'Configure proxy API endpoint settings'
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
									name: 'frame',
									value: 'search'
								},
								{
									name: 'item_title',
									value: 'Proxy search and filter'
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
									name: 'frame',
									value: 'group'
								},
								{
									name: 'item_title',
									value: 'Manage proxy groups'
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
									name: 'frame',
									value: 'actions'
								},
								{
									name: 'item_title',
									value: 'View log of recent order actions'
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
									name: 'frame',
									value: 'requests'
								},
								{
									name: 'item_function'
								},
								{
									name: 'item_title',
									value: 'Download proxy request logs'
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
									name: 'frame',
									value: 'replace'
								},
								{
									name: 'item_function'
								},
								{
									name: 'item_title',
									value: 'Configure proxy replacement settings'
								},
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
									name: 'frame',
									value: 'rotate'
								},
								{
									name: 'item_function'
								},
								{
									name: 'item_title',
									value: 'Configure proxy gateway rotation settings'
								},
								{
									name: 'process',
									value: 'rotate'
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
									name: 'frame',
									value: 'authenticate'
								},
								{
									name: 'item_function'
								},
								{
									name: 'item_title',
									value: 'Configure proxy authentication settings'
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
									name: 'frame',
									value: 'download'
								},
								{
									name: 'item_function'
								},
								{
									name: 'item_title',
									value: 'Download list of selected proxies'
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
					resultsPerPage: 100,
					selector: '.item-list[page="all"][table="proxies"]',
					table: 'proxies'
				}
			});
			elements.html('.order-name', (response.data.order.quantityActive ? response.data.order.quantityActive : response.data.order.quantity) + ' ' + response.data.order.name);
			processItemList('listProxyItems');

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
						nodeLocationCountryOptions += '<option value="' + location.countryCode + '">' + location.countryName + '</option>';
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
var processProxyItems = function(response, itemListParameters) {
	if (typeof itemListParameters !== 'object') {
		processItemList('listProxyItems');
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
				let itemsClear = elements.get('.item-configuration a.clear');
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
						listProxyItems: {
							page: 1
						}
					};
					mergeRequestParameters.items.listProxyItems = {
						data: [],
						table: 'proxies'
					};
					api.setRequestParameters(mergeRequestParameters, true);
					closeFrames(apiRequestParameters.current.defaults);
					processItemList('listProxyItems');
				};
				itemsClear.addEventListener('click', itemsClear.clickListener);
			}, 100);
		}

		if (
			response.code !== 200 ||
			(
				!response.data.length &&
				!response.processing
			)
		) {
			return elements.html(itemListParameters.selector + ' .items', '');
		}

		if (response.processing) {
			const actionDetails = 'to ' + response.processing.parameters.action + ' ' + response.processing.parameters.itemCount + ' ' + response.processing.parameters.table;
			let itemProcessingData = '<p class="message">Your recent bulk action ' + actionDetails + ' is in progress.</p>';
			var timeoutId = setTimeout(function() {}, 1);
			const processActionProgress = function(response) {
				const actionProgress = (response.processing ? response.processing.progress : 0);
				const actionProcessed = (response.processing ? response.processing.processed : false);
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
								foreignKey: response.processing.foreignKey,
								foreignValue: response.processing.foreignValue
							},
							offset: 0,
							table: 'actions',
							url: apiRequestParameters.current.settings.baseUrl + 'api/actions'
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

					if (!response.processing.tokenId) {
						var mergeRequestParameters = {
							items: {},
							listProxyItems: {
								page: 1
							}
						};
						api.setRequestParameters(mergeRequestParameters, true);
						processItemList('listProxyItems');
					}

					if (response.processing.chunks > 1) {
						elements.html('.message-container.proxies', '<p class="message success">Your recent bulk action ' + actionDetails + ' is completed.</p>');

						if (actionProgress < 100) {
							elements.html('.message-container.proxies', '<p class="error message">Action ' + (response.processing.id ? '#' + response.processing.id + ' ' : '') + actionDetails + ' was interrupted at ' + actionProgress + '%, please try again.</p>');
						}
					}
				}

				processWindowEvents('resize');
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

		elements.html(itemListParameters.selector + ' .items', '<table class="table"><thead><tr><th style="width: 35px;"></th><th>Proxy IP</th></tr></thead><tbody></tbody></table>');
		let itemListData = '';

		for (let itemListDataKey in response.data) {
			// TODO: add proxy details in frame, remove details-container
			let item = response.data[itemListDataKey];
			itemListData += '<tr page="' + apiRequestParameters.current.listProxyItems.page + '" proxy_id="' + item.id + '" class="">';
			itemListData += '<td style="width: 1px;">';
			itemListData += '<span checked="0" class="checkbox" index="' + itemListDataKey + '" proxy_id="' + item.id + '">';
			itemListData += '</span>';
			itemListData += '</td>';
			itemListData += '<td>';
			itemListData += '<span class="details-container">';
			itemListData += '<span class="details">';
			itemListData += '<span class="detail"><strong>Status:</strong> ' + capitalizeString(item.status) + '</span>';
			itemListData += '<span class="detail"><strong>Proxy IP:</strong> ' + item.ip + '</span>';
			itemListData += '<span class="detail"><strong>Proxy Type:</strong> ' + capitalizeString(item.type) + '</span>';
			itemListData += '<span class="detail"><strong>Location:</strong> ' + item.city + ', ' + item.region + ' ' + item.countryCode + ' </span>';
			itemListData += '<span class="detail"><strong>ISP:</strong> ' + item.asn + ' </span>';
			itemListData += '<span class="detail"><strong>HTTP + HTTPS Port:</strong> ' + (item.disableHttp == 1 ? 'Disabled' : '80') + '</span>';
			itemListData += '<span class="detail"><strong>Username:</strong> ' + (item.username ? item.username : 'N/A') + '</span>';
			itemListData += '<span class="detail"><strong>Password:</strong> ' + (item.password ? item.password : 'N/A') + '</span>';
			itemListData += '<span class="detail"><strong>Whitelisted IPs:</strong> ' + (item.whitelistedIps ? '<textarea>' + item.whitelistedIps + '</textarea>' : 'N/A') + '</span>';
			itemListData += '</span>';
			itemListData += '</span>';
			itemListData += '<span class="table-text">' + item.ip + '</span>';
			itemListData += '</td>';
		}

		elements.html(itemListParameters.selector + ' .items table tbody', itemListData);

		if (apiRequestParameters.current.action != 'search') {
			api.setRequestParameters(apiRequestParameters.current.defaults);
		}
	}
};
var processRemove = function() {
	api.setRequestParameters({
		action: 'remove'
	});
	api.sendRequest(function(response) {
		api.setRequestParameters({
			action: 'fetch',
			items: {
				listProxyGroupItems: {
					data: [],
					table: 'proxy_groups'
				}
			},
			listProxyGroupItems: {
				page: 1
			},
		}, true);
		processItemList('listProxyGroupItems', function() {
			elements.html('.message-container.groups', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		});
	});
};
var processRequests = function(frameName, frameSelector) {
	// ..
};
var processRotate = function(frameName, frameSelector) {
	api.setRequestParameters({
		listStaticProxyItems: {
			action: 'fetch',
			callback: function(response, itemListParameters) {
				processProxyItems(response, itemListParameters);
				let itemListHeadingData = '<label>Select Static Proxies <span class="details icon tooltip tooltip-bottom" item_title="Select a list of static proxies below which will be accessible through the selected gateway proxies at the selected interval."></span></label>';
				elements.html(itemListParameters.selector + ' .item-controls-heading-container', itemListHeadingData);
				processWindowEvents('resize');
			},
			initial: true,
			messages: {
				static: '',
				status: '<p class="message no-margin-top">Loading</p>'
			},
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
				}
			],
			page: 1,
			resultsPerPage: 10,
			selector: '.item-list[page="static"][table="proxies"]',
			table: 'proxies'
		}
	}, true);
	processItemList('listStaticProxyItems');
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
