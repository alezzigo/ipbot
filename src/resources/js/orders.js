var processOrders = function() {
	api.setRequestParameters({
		conditions: {
			'status !=': 'merged'
		},
		listOrderItems: {
			action: 'fetch',
			callback: function(response, itemListParameters) {
				processOrderItems(response, itemListParameters);
			},
			initial: true,
			messages: {
				orders: '',
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
							value: 'button frame-button icon tooltip tooltip-bottom upgrade'
						},
						{
							name: 'frame',
							value: 'upgrade'
						},
						{
							name: 'item_function'
						},
						{
							name: 'item_title',
							value: 'Request upgrade and/or merge for selected orders'
						},
						{
							name: 'process',
							value: 'upgrade'
						}
					],
					tag: 'span'
				}
			],
			page: 1,
			resultsPerPage: 10,
			selector: '.item-list[table="orders"]',
			table: 'orders'
		},
		sort: {
			field: 'created',
			order: 'DESC'
		},
		url: apiRequestParameters.current.settings.baseUrl + 'api/orders'
	});
	processItemList('listOrderItems');
};
const processOrderItems = function(response, itemListParameters) {
	if (typeof itemListParameters !== 'object') {
		processItemList('listOrderItems');
	} else {
		elements.html('.message-container.orders', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		let itemListData = '';

		if (response.data.length) {
			for (itemListDataKey in response.data) {
				let item = response.data[itemListDataKey];
				let pendingOrderChange = (
					item.quantityPending &&
					item.quantityPending !== item.quantity
				);
				itemListData += '<div class="item-container item-button">';
				itemListData += '<div class="item">';
				itemListData += '<span class="checkbox-container">';
				itemListData += '<span checked="0" class="checkbox" index="' + itemListDataKey + '" order_id="' + item.id + '" product_id="' + item.product_id + '"></span>';
				itemListData += '</span>';
				itemListData += '<div class="item-body item-checkbox">';
				itemListData += '<p><strong>Order #' + item.id + '</strong></p>';
				itemListData += '<p>' + (item.quantityActive ? item.quantityActive : item.quantity) + ' ' + item.name + '</p>';
				itemListData += '<label class="label ' + (item.quantityActive ? 'active' : item.status) + '">' + (item.quantityActive ? 'active' : item.status) + '</label>' + (pendingOrderChange ? '<label class="label">Pending Order ' + (item.quantityPending >= item.quantity ? 'Upgrade' : 'Downgrade') + '</label>' : '');
				itemListData += '</div>';
				itemListData += '</div>';
				itemListData += '<div class="item-link-container"><a class="item-link" href="/orders/' + item.id + '"></a></div>';
				itemListData += '</div>';
			}
		}

		elements.html(itemListParameters.selector + ' .items', itemListData);
	}

	elements.html('.message-container.orders', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
};
var processUpgrade = function(frameName, frameSelector, upgradeValue) {
	if (!elements.get('.item-list[table="orders"] .checkbox[index="0"]')) {
		processOrders();
	}

	let orderId = parseInt(window.location.search.substr(1));
	let orderItemCount = apiRequestParameters.current.listOrderItems.selectedItemCount;
	upgradeValue = upgradeValue || 1;
	api.setRequestParameters({
		action: 'upgrade',
		data: {
			upgradeQuantity: upgradeValue
		},
		url: apiRequestParameters.current.settings.baseUrl + 'api/orders'
	}, true);

	if (apiRequestParameters.current.data.confirmUpgrade) {
		api.setRequestParameters({
			data: {
				upgradeQuantity: elements.get('.upgrade-container .upgrade-quantity').value
			}
		}, true);
	}

	if (
		!orderItemCount &&
		orderId &&
		typeof orderId === 'number'
	) {
		orderItemCount = 1;
		var mergeRequestParameters = {
			items: {}
		};
		mergeRequestParameters.items.listOrderItems = {
			data: [orderId],
			table: 'orders'
		};
		api.setRequestParameters(mergeRequestParameters, true);
	}

	api.sendRequest(function(response) {
		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.message.status === 'success') {
			let upgradeData = '';
			let upgradeValueMinimum = (orderItemCount === 1 ? 1 : 0);
			upgradeData += '<div class="align-left item-container no-margin-top no-padding">';
			upgradeData += '<label for="upgrade-quantity">Select Order Upgrade Quantity</label>';
			upgradeData += '<div class="field-group no-margin">';
			upgradeData += '<a class="button change-quantity-button decrease decrease-quantity"' + (upgradeValue <= upgradeValueMinimum ? ' disabled="disabled"' : '') + ' href="javascript:void(0);" event_step="-1">-</a>';
			upgradeData += '<input class="change-quantity-field upgrade-quantity" event_step="0" id="upgrade-quantity" max="' + response.data.product.maximumQuantity + '" min="' + response.data.product.minimumQuantity + '" name="upgrade_quantity" step="1" type="number" value="' + response.data.upgradeQuantity + '">';
			upgradeData += '<input class="hidden" name="confirm_upgrade" type="hidden" value="1">';
			upgradeData += '<a class="button change-quantity-button increase increase-quantity"' + (upgradeValue >= response.data.product.maximumQuantity ? ' disabled="disabled"' : '') + ' href="javascript:void(0);" event_step="1">+</a>';
			upgradeData += '</div>';
			upgradeData += '</div>';
			upgradeData += '<div class="clear"></div>';
			upgradeData += '<div class="details merged-order-details">';
			upgradeData += '<p class="message no-margin-top success">The ' + orderItemCount + ' order' + (orderItemCount !== 1 ? 's' : '') + ' selected will merge into the following ' + (upgradeValue > 0 ? 'upgraded': '') + ' order and invoice:</p>';
			upgradeData += '<div class="item-container item-button no-margin-bottom">';
			upgradeData += '<p><strong>Merged Order</strong></p>';
			upgradeData += '<p>' + response.data.merged.order.quantityPending + ' ' + response.data.merged.order.name + '</p>';
			upgradeData += '<p class="no-margin-bottom">' + response.data.merged.order.pricePending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + ' for ' + response.data.merged.order.intervalValuePending + ' ' + response.data.merged.order.intervalTypePending + (response.data.merged.order.intervalValuePending !== 1 ? 's' : '') + '</p>';
			upgradeData += '<div class="item-link-container"></div>';
			upgradeData += '</div>';
			upgradeData += '<h2>' + (upgradeValue > 0 ? 'Upgraded': 'Merged') + ' Invoice Pricing Details</h2>';
			upgradeData += '<p><strong>Subtotal</strong><br>' + response.data.merged.invoice.subtotalPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Shipping</strong><br>' + response.data.merged.invoice.shippingPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Tax</strong><br>' + response.data.merged.invoice.taxPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Total</strong><br>' + response.data.merged.invoice.totalPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Amount Paid</strong><br><span' + (response.data.merged.invoice.amountPaid ? ' class="paid"' : '') + '>' + response.data.merged.invoice.amountPaid.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</span>' + (response.data.merged.invoice.amountPaid ? '<br><span class="note">The amount paid will be added to your account balance and won\'t automatically apply to the remaining amount due for the merged order.</span>' : '') + '</p>';
			upgradeData += '<p><strong>Remaining Amount Due</strong><br>' + response.data.merged.invoice.remainderPending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '</div>';
			elements.html('.upgrade-container', upgradeData);
			let decreaseButton = elements.get('.upgrade-container .decrease-quantity');
			let increaseButton = elements.get('.upgrade-container .increase-quantity');
			let upgradeField = elements.get('.upgrade-container .upgrade-quantity');
			upgradeValue = parseInt(upgradeField.value);
			decreaseButton.removeEventListener('click', decreaseButton.clickListener);
			increaseButton.removeEventListener('click', increaseButton.clickListener);
			upgradeField.removeEventListener('change', upgradeField.changeListener);
			upgradeField.removeEventListener('keyup', upgradeField.changeListener);
			decreaseButton.clickListener = increaseButton.clickListener = upgradeField.changeListener = function(button) {
				upgradeValue = Math.min(response.data.product.maximumQuantity, Math.max(upgradeValueMinimum, parseInt(upgradeField.value) + parseInt(button.target.getAttribute('event_step'))));

				if (upgradeValue <= upgradeValueMinimum) {
					elements.setAttribute('.decrease-quantity', 'disabled', 'disabled');
					processUpgrade(false, false, upgradeValueMinimum);
					return false;
				}

				if (upgradeValue >= response.data.product.maximumQuantity) {
					elements.setAttribute('.increase-quantity', 'disabled', 'disabled');
					processUpgrade(false, false, response.data.product.maximumQuantity);
					return false;
				}

				elements.html('.upgrade-container .merged-order-details', '<p class="message">Loading ...</p>');
				elements.removeAttribute('.decrease-quantity', 'disabled', 'disabled');
				elements.removeAttribute('.increase-quantity', 'disabled', 'disabled');
				let timeoutId = setTimeout(function() {}, 1);

				while (timeoutId--) {
					clearTimeout(timeoutId);
				}

				timeoutId = setTimeout(function() {
					processUpgrade(false, false, upgradeValue);
				}, 400);
				upgradeField.value = upgradeValue;
			};

			if (!decreaseButton.hasAttribute('disabled')) {
				decreaseButton.addEventListener('click', decreaseButton.clickListener);
			}

			if (!increaseButton.hasAttribute('disabled')) {
				increaseButton.addEventListener('click', increaseButton.clickListener);
			}

			upgradeField.addEventListener('change', upgradeField.changeListener);
			upgradeField.addEventListener('keyup', upgradeField.changeListener);
		}

		elements.html('.message-container.upgrade', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
	});
};
api.setRequestParameters({
	defaults: {
		action: 'fetch',
		table: 'orders'
	},
	table: 'orders'
});
