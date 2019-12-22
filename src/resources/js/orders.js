var orderGrid = {};
var productIdGrid = {};
var processOrders = function() {
	var ordersAllVisible = document.querySelector('.item-container .checkbox[index="all-visible"]');
	var ordersContainer = document.querySelector('.orders-container');
	var orderToggle = function(button) {
		ordersContainer.setAttribute('current_checked', button.getAttribute('index'));
		processOrderGrid(window.event.shiftKey ? range(ordersContainer.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +ordersContainer.querySelector('.checkbox[index="' + ordersContainer.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		ordersContainer.setAttribute('previous_checked', button.getAttribute('index'));
	};
	var orderToggleAllVisible = function(button) {
		var selectableItemsCount = selectAllElements('.orders-container .item-button').length;
		ordersContainer.setAttribute('current_checked', 0);
		ordersContainer.setAttribute('previous_checked', 0);

		if (selectableItemsCount) {
			processOrderGrid(range(0, selectableItemsCount - 1), +button.getAttribute('checked') === 0);
		}
	};
	var processOrderGrid = function(orderIndexes, orderState) {
		productIdGrid = {};
		orderIndexes.map(function(orderIndex) {
			var order = ordersContainer.querySelector('.checkbox[index="' + orderIndex + '"]');
			var orderId = order.getAttribute('order_id');
			var productId = order.getAttribute('product_id');
			order.setAttribute('checked', +orderState);
			orderGrid['order' + orderId] = orderId;
			productIdGrid['product' + productId] = productId;

			if (!+orderState) {
				delete orderGrid['order' + orderId];
			}
		});
		elements.html('.item-configuration .total-checked', +(allVisibleChecked = Object.entries(orderGrid).length));

		if (Object.entries(productIdGrid).length === 1) {
			allVisibleChecked ? elements.removeClass('.item-configuration span.icon[item-function]', 'hidden') : elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		}

		ordersAllVisible.setAttribute('checked', +(allVisibleChecked === selectAllElements('.orders-container .item-button').length));
		api.setRequestParameters({
			items: {
				orders: orderGrid
			}
		}, true);
	};
	api.setRequestParameters({
		action: apiRequestParameters.previous.action,
		conditions: {
			'status !=': 'merged'
		},
		items: {
			orders: orderGrid
		},
		sort: {
			field: 'modified',
			order: 'DESC'
		},
		url: apiRequestParameters.current.settings.base_url + 'api/orders'
	});
	var ordersData = '';
	api.sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			if (response.user === false) {
				window.location.href = apiRequestParameters.current.settings.base_url + '?#login';
				return false;
			}

			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (ordersContainer) {
			if (response.data.length) {
				elements.removeClass('.item-configuration .item-controls', 'hidden');
				response.data.map(function(item, index) {
					var pendingOrderChange = (
						item.quantity_pending &&
						item.quantity_pending !== item.quantity
					);
					ordersData += '<div class="item-container item-button">';
					ordersData += '<div class="item">';
					ordersData += '<span class="checkbox-container">';
					ordersData += '<span checked="0" class="checkbox" index="' + index + '" order_id="' + item.id + '" product_id="' + item.product_id + '"></span>';
					ordersData += '</span>';
					ordersData += '<div class="item-body item-checkbox">';
					ordersData += '<p><strong>Order #' + item.id + '</strong></p>';
					ordersData += '<p>' + (item.quantity_active ? item.quantity_active : item.quantity) + ' ' + item.name + '</p>';
					ordersData += '<label class="label ' + (item.quantity_active ? 'active' : item.status) + '">' + (item.quantity_active ? 'active' : item.status) + '</label>' + (pendingOrderChange ? '<label class="label">Pending Order ' + (item.quantity_pending >= item.quantity ? 'Upgrade' : 'Downgrade') + '</label>' : '');
					ordersData += '</div>';
					ordersData += '</div>';
					ordersData += '<div class="item-link-container"><a class="item-link" href="/orders/' + item.id + '"></a></div>';
					ordersData += '</div>';
				});
				ordersContainer.innerHTML = ordersData;
				ordersAllVisible.removeEventListener('click', ordersAllVisible.clickListener);
				ordersAllVisible.clickListener = function() {
					orderToggleAllVisible(ordersAllVisible);
				};
				ordersAllVisible.addEventListener('click', ordersAllVisible.clickListener);
				elements.loop('.orders-container .item-button', function(index, row) {
					var orderToggleButton = row.querySelector('.checkbox');
					var orderId = orderToggleButton.getAttribute('order_id');
					orderToggleButton.removeEventListener('click', orderToggleButton.clickListener);
					orderToggleButton.clickListener = function() {
						orderToggle(orderToggleButton);
					};
					orderToggleButton.addEventListener('click', orderToggleButton.clickListener);
				});
				elements.html('.item-configuration .total-results', response.data.length);
			} else {
				messageContainer.innerHTML = '<p class="error message">No orders found, please <a href="' + apiRequestParameters.current.settings.base_url + 'static-proxies">create a new order</a>.</p>';
			}
		}

		processWindowEvents('resize');
	});
};
var processUpgrade = function(frameName, frameSelector, upgradeValue) {
	if (!document.querySelector('.orders-container .checkbox[index="0"]')) {
		processOrders();
	}

	var orderId = parseInt(window.location.search.substr(1));
	var upgradeContainer = document.querySelector('.upgrade-container');
	var upgradeData = '';
	upgradeValue = upgradeValue || 1;
	api.setRequestParameters({
		action: 'upgrade',
		data: {
			orders: orderGrid,
			products: productIdGrid,
			upgrade_quantity: upgradeValue
		},
		url: apiRequestParameters.current.settings.base_url + 'api/orders'
	}, true);

	if (apiRequestParameters.current.data.confirm_upgrade) {
		api.setRequestParameters({
			data: {
				upgrade_quantity: upgradeContainer.querySelector('.upgrade-quantity').value
			}
		}, true);
	}

	var orderGridCount = Object.entries(apiRequestParameters.current.data.orders).length;

	if (
		!orderGridCount &&
		orderId &&
		typeof orderId === 'number'
	) {
		orderGridCount = 1;
		api.setRequestParameters({
			data: {
				orders: {
					orderId: orderId
				}
			}
		}, true);
	}

	api.sendRequest(function(response) {
		var messageContainer = document.querySelector('.upgrade-configuration .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.message.status === 'success') {
			var upgradeValueMinimum = (orderGridCount === 1 ? 1 : 0);
			upgradeData += '<div class="align-left item-container no-margin-top no-padding">';
			upgradeData += '<label for="upgrade-quantity">Select Order Upgrade Quantity</label>';
			upgradeData += '<div class="field-group no-margin">';
			upgradeData += '<a class="button change-quantity-button decrease decrease-quantity"' + (upgradeValue <= upgradeValueMinimum ? ' disabled="disabled"' : '') + ' href="javascript:void(0);" event_step="-1">-</a>';
			upgradeData += '<input class="change-quantity-field upgrade-quantity width-auto" event_step="0" id="upgrade-quantity" max="' + response.data.product.maximum_quantity + '" min="' + response.data.product.minimum_quantity + '" name="upgrade_quantity" step="1" type="number" value="' + response.data.upgrade_quantity + '">';
			upgradeData += '<input class="hidden" name="confirm_upgrade" type="hidden" value="1">';
			upgradeData += '<a class="button change-quantity-button increase increase-quantity"' + (upgradeValue >= response.data.product.maximum_quantity ? ' disabled="disabled"' : '') + ' href="javascript:void(0);" event_step="1">+</a>';
			upgradeData += '</div>';
			upgradeData += '</div>';
			upgradeData += '<div class="clear"></div>';
			upgradeData += '<div class="details merged-order-details">';
			upgradeData += '<p class="message no-margin-top success">The ' + orderGridCount + ' order' + (orderGridCount !== 1 ? 's' : '') + ' selected will merge into the following ' + (upgradeValue > 0 ? 'upgraded': '') + ' order and invoice:</p>';
			upgradeData += '<div class="item-container item-button no-margin-bottom">';
			upgradeData += '<p><strong>Merged Order</strong></p>';
			upgradeData += '<p>' + response.data.merged.order.quantity_pending + ' ' + response.data.merged.order.name + '</p>';
			upgradeData += '<p class="no-margin-bottom">' + response.data.merged.order.price_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + ' for ' + response.data.merged.order.interval_value_pending + ' ' + response.data.merged.order.interval_type_pending + (response.data.merged.order.interval_value_pending !== 1 ? 's' : '') + '</p>';
			upgradeData += '<div class="item-link-container"></div>';
			upgradeData += '</div>';
			upgradeData += '<h2>' + (upgradeValue > 0 ? 'Upgraded': 'Merged') + ' Invoice Pricing Details</h2>';
			upgradeData += '<p><strong>Subtotal</strong><br>' + response.data.merged.invoice.subtotal_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Shipping</strong><br>' + response.data.merged.invoice.shipping_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Tax</strong><br>' + response.data.merged.invoice.tax_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Total</strong><br>' + response.data.merged.invoice.total_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '<p><strong>Amount Paid</strong><br><span' + (response.data.merged.invoice.amount_paid ? ' class="paid"' : '') + '>' + response.data.merged.invoice.amount_paid.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</span>' + (response.data.merged.invoice.amount_paid ? '<br><span class="note">The amount paid will be added to your account balance and won\'t automatically apply to the remaining amount due for the merged order.</span>' : '') + '</p>';
			upgradeData += '<p><strong>Remaining Amount Due</strong><br>' + response.data.merged.invoice.remainder_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.merged.invoice.currency + '</p>';
			upgradeData += '</div>';
			upgradeContainer.innerHTML = upgradeData;
			var decreaseButton = upgradeContainer.querySelector('.decrease-quantity');
			var increaseButton = upgradeContainer.querySelector('.increase-quantity');
			var upgradeField = upgradeContainer.querySelector('.upgrade-quantity');
			upgradeValue = parseInt(upgradeField.value);
			decreaseButton.removeEventListener('click', decreaseButton.clickListener);
			increaseButton.removeEventListener('click', increaseButton.clickListener);
			upgradeField.removeEventListener('change', upgradeField.changeListener);
			upgradeField.removeEventListener('keyup', upgradeField.changeListener);
			decreaseButton.clickListener = increaseButton.clickListener = upgradeField.changeListener = function(button) {
				upgradeValue = Math.min(response.data.product.maximum_quantity, Math.max(upgradeValueMinimum, parseInt(upgradeField.value) + parseInt(button.target.getAttribute('event_step'))));

				if (upgradeValue <= upgradeValueMinimum) {
					elements.setAttribute('.decrease-quantity', 'disabled', 'disabled');
					processUpgrade(false, false, upgradeValueMinimum);
					return false;
				}

				if (upgradeValue >= response.data.product.maximum_quantity) {
					elements.setAttribute('.increase-quantity', 'disabled', 'disabled');
					processUpgrade(false, false, response.data.product.maximum_quantity);
					return false;
				}

				elements.removeAttribute('.decrease-quantity', 'disabled', 'disabled');
				elements.removeAttribute('.increase-quantity', 'disabled', 'disabled');
				upgradeContainer.querySelector('.merged-order-details').innerHTML = '<p class="message">Loading ...</p>';
				var timeoutId = setTimeout(function() {}, 1);

				while (timeoutId--) {
					clearTimeout(timeoutId);
				}

				var timeoutId = setTimeout(function() {
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
	});
};
api.setRequestParameters({
	defaults: {
		action: 'fetch',
		table: 'orders'
	},
	table: 'orders'
});
