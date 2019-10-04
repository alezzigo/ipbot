'use_strict';

var orderGrid = {};
var productIdGrid = {};
var defaultTable = 'orders';
var previousAction = 'find';
var processDowngrade = function() {
	var downgradeContainer = document.querySelector('.downgrade-container');
	requestParameters.action = 'downgrade';
	sendRequest(function(response) {
		// ..
	});
};
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
		requestParameters.items[requestParameters.table] = orderGrid;
	};
	requestParameters.action = previousAction;
	requestParameters.conditions = {
		'status !=': 'merged'
	};
	requestParameters.sort = {
		field: 'modified',
		order: 'DESC'
	};
	var ordersData = '';
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			if (response.user === false) {
				window.location.href = requestParameters.settings.base_url + '?#login';
				return false;
			}

			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			ordersContainer &&
			response.data.length
		) {
			elements.removeClass('.item-configuration .item-controls', 'hidden');
			response.data.map(function(item, index) {
				ordersData += '<div class="item-container item-button"><div class="item"><span class="checkbox-container"><span checked="0" class="checkbox" index="' + index + '" order_id="' + item.id + '" product_id="' + item.product_id + '"></span></span><div class="item-body item-checkbox"><p><strong>' + item.quantity + ' ' + item.name + '</strong></p><p>$' + item.price + ' per ' + (item.interval_value > 1 ? item.interval_value + ' ' : '') + item.interval_type + (item.interval_value > 1 ? 's' : '') + '</p><label class="label ' + item.status + '">' + capitalizeString(item.status) + '</label></div></div><div class="item-link-container"><a class="item-link" href="/orders/' + item.id + '"></a></div></div>';
			});
			ordersContainer.innerHTML = ordersData;
			ordersAllVisible.removeEventListener('click', ordersAllVisible.clickListener);
			ordersAllVisible.clickListener = function() {
				orderToggleAllVisible(ordersAllVisible);
			};
			ordersAllVisible.addEventListener('click', ordersAllVisible.clickListener);
			elements.loop('.orders-container .item-button', function(index, row) {
				var orderToggleButton = row.querySelector('.checkbox');
				orderToggleButton.removeEventListener('click', orderToggleButton.clickListener);
				var orderId = orderToggleButton.getAttribute('order_id');
				orderToggleButton.clickListener = function() {
					orderToggle(orderToggleButton);
				};
				orderToggleButton.addEventListener('click', orderToggleButton.clickListener);
			});
			elements.html('.item-configuration .total-results', response.data.length);
		}

		processWindowEvents('resize');
	});
};
var processUpgrade = function(windowName, windowSelector, upgradeQuantity = 1) {
	requestParameters.action = 'upgrade';
	requestParameters.data.orders = orderGrid;
	requestParameters.data.products = productIdGrid;
	requestParameters.data.upgrade_quantity = upgradeQuantity;
	var upgradeContainer = document.querySelector('.upgrade-container');
	var upgradeData = '';
	sendRequest(function(response) {
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
			var orderGridCount = Object.entries(orderGrid).length;
			upgradeData += '<div class="align-left item-container no-margin-top no-padding">';
			upgradeData += '<label for="upgrade-quantity">Select Order Upgrade Quantity</label>';
			upgradeData += '<div class="field-group no-margin">';
			upgradeData += '<a class="button change-quantity-button decrease decrease-quantity" href="javascript:void(0);" event_step="-1">-</a>';
			upgradeData += '<input class="change-quantity-field upgrade-quantity width-auto" event_step="0" id="upgrade-quantity" max="' + response.data.product.maximum_quantity + '" min="' + response.data.product.minimum_quantity + '" name="upgrade_quantity" step="1" type="number" value="' + response.data.upgrade_quantity + '">';
			upgradeData += '<input class="hidden" name="confirm_upgrade" type="hidden" value="1">';
			upgradeData += '<a class="button change-quantity-button increase increase-quantity" href="javascript:void(0);" event_step="1">+</a>';
			upgradeData += '</div>';
			upgradeData += '</div>';
			upgradeData += '<div class="clear"></div>';
			upgradeData += '<div class="merged-order-details">';
			upgradeData += '<p class="message no-margin-top success">The ' + orderGridCount + ' order' + (orderGridCount !== 1 ? 's' : '') + ' selected will merge into the following upgraded order and invoice:</p>';
			upgradeData += '<div class="item-container item-button no-margin-bottom"><p><strong>' + response.data.merged.order.quantity_pending + ' ' + response.data.merged.order.name + '</strong></p><p class="no-margin-bottom">' + response.data.merged.invoice.payment_currency_symbol + response.data.merged.order.price_pending + ' ' + response.data.merged.invoice.payment_currency_name + ' for ' + response.data.merged.order.interval_value_pending + ' ' + response.data.merged.order.interval_type_pending + (response.data.merged.order.interval_value_pending !== 1 ? 's' : '') + '</p><div class="item-link-container"></div></div>';
			upgradeData += '<h2>Upgraded Invoice Pricing Details</h2>';
			upgradeData += '<p><strong>Subtotal</strong><br>' + response.data.merged.invoice.payment_currency_symbol + response.data.merged.invoice.subtotal_pending + ' ' + response.data.merged.invoice.payment_currency_name + '</p>';
			upgradeData += '<p><strong>Shipping</strong><br>' + response.data.merged.invoice.payment_currency_symbol + response.data.merged.invoice.shipping_pending + ' ' + response.data.merged.invoice.payment_currency_name + '</p>';
			upgradeData += '<p><strong>Tax</strong><br>' + response.data.merged.invoice.payment_currency_symbol + response.data.merged.invoice.tax_pending + ' ' + response.data.merged.invoice.payment_currency_name + '</p>';
			upgradeData += '<p><strong>Total</strong><br>' + response.data.merged.invoice.payment_currency_symbol + response.data.merged.invoice.total_pending + ' ' + response.data.merged.invoice.payment_currency_name + '</p>';
			upgradeData += '<p><strong>Amount Paid</strong><br><span class="paid">' + response.data.merged.invoice.payment_currency_symbol + response.data.merged.invoice.amount_paid + ' ' + response.data.merged.invoice.payment_currency_name + '</span></p>';
			upgradeData += '<p><strong>Amount Due for Upgrade</strong><br>' + response.data.merged.invoice.payment_currency_symbol + response.data.merged.invoice.prorate_pending + ' ' + response.data.merged.invoice.payment_currency_name + '</p>';
			upgradeData += '</div>';
			upgradeContainer.innerHTML = upgradeData;
			var decreaseButton = upgradeContainer.querySelector('.decrease-quantity');
			var increaseButton = upgradeContainer.querySelector('.increase-quantity');
			var upgradeField = upgradeContainer.querySelector('.upgrade-quantity');
			decreaseButton.removeEventListener('click', decreaseButton.clickListener);
			increaseButton.removeEventListener('click', increaseButton.clickListener);
			decreaseButton.clickListener = increaseButton.clickListener = upgradeField.keyupListener = function(button) {
				upgradeContainer.querySelector('.merged-order-details').innerHTML = '<p class="message">Loading ...</p>';
				var timeoutId = setTimeout(function() {}, 1);

				while (timeoutId--) {
					clearTimeout(timeoutId);
				}

				var upgradeValue = parseInt(upgradeField.value) ? parseInt(upgradeField.value) + parseInt(button.target.getAttribute('event_step')) : response.data.product.minimum_quantity;
				var timeoutId = setTimeout(function() {
					processUpgrade(false, false, upgradeValue);
				}, 400);
				upgradeField.value = Math.min(response.data.product.maximum_quantity, Math.max(1, upgradeValue));
			};
			decreaseButton.addEventListener('click', decreaseButton.clickListener);
			increaseButton.addEventListener('click', increaseButton.clickListener);
			upgradeField.addEventListener('keyup', upgradeField.keyupListener);
		}
	});
};
requestParameters.table = defaultTable;
requestParameters.url = '/api/orders';
