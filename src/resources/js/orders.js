'use_strict';

var ordersGrid = {};
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
		orderIndexes.map(function(orderIndex) {
			var order = ordersContainer.querySelector('.checkbox[index="' + orderIndex + '"]');
			var orderId = order.getAttribute('order_id');
			order.setAttribute('checked', +orderState);
			ordersGrid['order' + orderId] = orderId;

			if (!+orderState) {
				delete ordersGrid['order' + orderId];
			}
		});
		elements.html('.item-configuration .total-checked', +(allVisibleChecked = Object.entries(ordersGrid).length));
		allVisibleChecked ? elements.removeClass('.item-configuration span.icon[item-function]', 'hidden') : elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		ordersAllVisible.setAttribute('checked', +(allVisibleChecked === selectAllElements('.orders-container .item-button').length));
		requestParameters.items[requestParameters.table] = ordersGrid;
	};
	requestParameters.action = previousAction;
	var ordersData = '';
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			if (response.user === false) {
				elements.addClass('nav .user', 'hidden');
				elements.removeClass('nav .guest', 'hidden');
				response.message = {
					status: 'error',
					text: 'You\'re currently not logged in, please <a href="' + requestParameters.settings.base_url + '?#login">log in</a> or <a href="' + requestParameters.settings.base_url + '?#register">register an account</a>.'
				};
			}

			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (
			ordersContainer &&
			response.data.length
		) {
			elements.removeClass('.item-configuration .item-controls', 'hidden');
			response.data.map(function(item, index) {
				ordersData += '<div class="item-container item-button"><div class="item"><span class="checkbox-container"><span checked="0" class="checkbox" index="' + index + '" order_id="' + item.id + '"></span></span><div class="item-body item-checkbox"><p><strong>' + item.quantity + ' ' + item.name + '</strong></p><p>$' + item.price + ' per ' + (item.interval_value > 1 ? item.interval_value + ' ' : '') + item.interval_type + (item.interval_value > 1 ? 's' : '') + '</p><label class="label ' + item.status + '">' + capitalizeString(item.status) + '</label></div></div><div class="item-link-container"><a class="item-link" href="/orders/' + item.id + '"></a></div></div>';
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
		}
	});
};
var processUpgrade = function() {
	var upgradeContainer = document.querySelector('.upgrade-container');
	requestParameters.action = 'upgrade';
	sendRequest(function(response) {
		// ..
	});
};
requestParameters.table = defaultTable;
requestParameters.url = '/api/orders';
