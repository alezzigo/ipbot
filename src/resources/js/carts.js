'use_strict';

var cartItemGrid = {};
var messageContainer = document.querySelector('.item-configuration .message-container');
var processCart = function() {
	requestParameters.action = 'cart';
	requestParameters.table = 'carts';
	sendRequest(function(response) {
		processCartItems(response);
	});
};
var processConfirm = function() {
	requestParameters.action = 'complete';
	requestParameters.table = 'carts';
	sendRequest(function(response) {
		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}
	});
};
var processCartItems = function(response) {
	var cartItemAddButtons = selectAllElements('.button.add-to-cart');
	var cartItemAllVisible = document.querySelector('.item-container .checkbox[index="all-visible"]');
	var cartItemContainer = document.querySelector('.cart-items-container');
	var cartItemToggle = function(button) {
		cartItemContainer.setAttribute('current_checked', button.getAttribute('index'));
		processCartItemGrid(window.event.shiftKey ? range(cartItemContainer.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +cartItemContainer.querySelector('.checkbox[index="' + cartItemContainer.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		cartItemContainer.setAttribute('previous_checked', button.getAttribute('index'));
	};
	var cartItemToggleAllVisible = function(button) {
		var selectableItemsCount = selectAllElements('.cart-items-container .item-button-selectable').length;
		cartItemContainer.setAttribute('current_checked', 0);
		cartItemContainer.setAttribute('previous_checked', 0);

		if (selectableItemsCount) {
			processCartItemGrid(range(0, selectableItemsCount - 1), +button.getAttribute('checked') === 0);
		}
	};
	var cartItems = checkoutItems = '';
	var cartSubtotal = cartTotal = 0;
	var checkoutItemContainer = document.querySelector('.checkout-items-container');
	var confirmContainer = document.querySelector('.confirm-items-container');
	var intervalTypes = ['month', 'year'];
	var intervalValues = range(1, 12);
	var processCartItemAdd = function(cartItemAddButton) {
		requestParameters.data.interval_type = cartItemAddButton.hasAttribute('interval_type') ? cartItemAddButton.getAttribute('interval_type') : 'month';
		requestParameters.data.interval_value = cartItemAddButton.hasAttribute('interval_value') ? cartItemAddButton.getAttribute('interval_value') : 1;
		requestParameters.data.product_id = cartItemAddButton.hasAttribute('product_id') ? cartItemAddButton.getAttribute('product_id') : 0;
		requestParameters.data.quantity = cartItemAddButton.hasAttribute('quantity') ? cartItemAddButton.getAttribute('quantity') : 0;
		sendRequest(function(response) {
			var messageContainer = document.querySelector('main.product .message-container');

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
		});
	};
	var processCartItemGrid = function(cartItemIndexes, cartItemState) {
		cartItemIndexes.map(function(cartItemIndex) {
			var cartItem = cartItemContainer.querySelector('.checkbox[index="' + cartItemIndex + '"]');
			var cartItemId = cartItem.getAttribute('cart_item_id');
			cartItem.setAttribute('checked', +cartItemState);
			cartItemGrid['cartItem' + cartItemId] = cartItemId;

			if (!+cartItemState) {
				delete cartItemGrid['cartItem' + cartItemId];
			}
		});
		elements.html('.item-configuration .total-checked', +(allVisibleChecked = Object.entries(cartItemGrid).length));
		allVisibleChecked ? elements.removeClass('.item-configuration span.icon[item-function]', 'hidden') : elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		cartItemAllVisible.setAttribute('checked', +(allVisibleChecked === selectAllElements('.cart-items-container .item-button-selectable').length));
		requestParameters.items[requestParameters.table] = cartItemGrid;
	};
	var processCartItemUpdate = function(cartItemId) {
		var cartItem = document.querySelector('.item-container[cart_item_id="' + cartItemId + '"]');
		requestParameters.data.id = cartItemId;
		requestParameters.data.interval_type = cartItem.querySelector('select.interval-type').value;
		requestParameters.data.interval_value = cartItem.querySelector('select.interval-value').value;
		requestParameters.data.quantity = cartItem.querySelector('select.quantity').value;
		elements.setAttribute('.button.checkout', 'disabled');
		sendRequest(function(response) {
			processCartItems(response);
		});
	};

	if (messageContainer) {
		messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
	}

	if (response.code !== 200) {
		return;
	}

	var cartItemData = Object.entries(response.data);

	if (!cartItemData.length) {
		elements.addClass('.item-configuration .item-controls', 'hidden');
	}

	if (cartItemAddButtons.length) {
		cartItemAddButtons.map(function(cartItemAddButton, index) {
			cartItemAddButton = cartItemAddButton[1];
			cartItemAddButton.removeEventListener('click', cartItemAddButton.clickListener);
			cartItemAddButton.clickListener = function() {
				processCartItemAdd(cartItemAddButton);
			};
			cartItemAddButton.addEventListener('click', cartItemAddButton.clickListener);
		});
		elements.removeAttribute('.button.add-to-cart', 'disabled');
	}

	if (cartItemContainer) {
		cartItemData.map(function(cartItem, index) {
			var intervalSelectTypes = intervalSelectValues = quantitySelectValues = '';
			var quantityValues = range(cartItem[1].minimum_quantity, cartItem[1].maximum_quantity);
			intervalTypes.map(function(intervalType, index) {
				intervalSelectTypes += '<option ' + (intervalType == cartItem[1].interval_type ? 'selected ' : '') + 'value="' + intervalType + '">' + capitalizeString(intervalType) + (cartItem[1].interval_value > 1 ? 's' : '') + '</option>';
			});
			intervalValues.map(function(intervalValue, index) {
				intervalSelectValues += '<option ' + (intervalValue == cartItem[1].interval_value ? 'selected ' : '') + 'value="' + intervalValue + '">' + intervalValue + '</option>';
			});
			quantityValues.map(function(quantityValue, index) {
				quantitySelectValues += '<option ' + (quantityValue == cartItem[1].quantity ? 'selected ' : '') + 'value="' + quantityValue + '">' + quantityValue + '</option>';
			});
			cartItems += '<div class="item-button item-button-selectable item-container" cart_item_id="' + cartItem[1].id + '">';
			cartItems += '<span checked="' + +(typeof cartItemGrid['cartItem' + cartItem[1].id] !== 'undefined') + '" class="checkbox" index="' + index + '" cart_item_id="' + cartItem[1].id + '"></span>';
			cartItems += '<p><a href="' + requestParameters.settings.base_url + cartItem[1].uri + '">' + cartItem[1].name + '</a></p>';
			cartItems += '<div class="field-group">';
			cartItems += '<span>Quantity:</span><select class="quantity" name="quantity">' + quantitySelectValues + '</select>';
			cartItems += '</div>';
			cartItems += '<div class="field-group no-margin">';
			cartItems += '<span>Price:</span><span class="display">' + cartItem[1].price + ' ' + requestParameters.settings.billing_currency + '</span><span>for</span>';
			cartItems += '<select class="interval-value" name="interval_value">' + intervalSelectValues + '</select>';
			cartItems += '<select class="interval-type" name="interval_type">' + intervalSelectTypes + '</select>';
			cartItems += '</div>';
			cartItems += '<div class="clear"></div>';
			cartItems += '</div>';
			cartSubtotal += parseFloat(cartItem[1].price);
		});
		cartItemContainer.innerHTML = cartItems;
		cartItemAllVisible.removeEventListener('click', cartItemAllVisible.clickListener);
		cartItemAllVisible.clickListener = function() {
			cartItemToggleAllVisible(cartItemAllVisible);
		};
		cartItemAllVisible.addEventListener('click', cartItemAllVisible.clickListener);
		cartItemGrid = {};
		elements.loop('.cart-items-container .item-button-selectable', function(index, row) {
			var cartItemToggleButton = row.querySelector('.checkbox');
			var cartItemUpdateIntervalTypeSelect = row.querySelector('select.interval-type');
			var cartItemUpdateIntervalValueSelect = row.querySelector('select.interval-value');
			var cartItemUpdateQuantitySelect = row.querySelector('select.quantity');
			cartItemToggleButton.removeEventListener('click', cartItemToggleButton.clickListener);
			cartItemUpdateIntervalTypeSelect.removeEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.removeEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);
			cartItemUpdateQuantitySelect.removeEventListener('change', cartItemUpdateQuantitySelect.changeListener);
			var cartItemId = cartItemToggleButton.getAttribute('cart_item_id');
			cartItemToggleButton.clickListener = function() {
				cartItemToggle(cartItemToggleButton);
			};
			cartItemUpdateIntervalTypeSelect.changeListener = cartItemUpdateIntervalValueSelect.changeListener = cartItemUpdateQuantitySelect.changeListener = function() {
				processCartItemUpdate(cartItemId);
			};
			cartItemToggleButton.addEventListener('click', cartItemToggleButton.clickListener);
			cartItemUpdateIntervalTypeSelect.addEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.addEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);
			cartItemUpdateQuantitySelect.addEventListener('change', cartItemUpdateQuantitySelect.changeListener);

			if (+cartItemToggleButton.getAttribute('checked')) {
				cartItemGrid['cartItem' + cartItemId] = cartItemId;
			}
		});
		var cartItemGridLength = +(Object.entries(cartItemGrid).length);
		var selectableItemsCount = selectAllElements('.cart-items-container .item-button-selectable').length;
		elements.html('.item-configuration .cart-subtotal .total', (Math.round(cartSubtotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + requestParameters.settings.billing_currency);
		elements.html('.item-configuration .total-checked', cartItemGridLength);
		elements.html('.item-configuration .total-results', cartItemData.length);
		elements.removeAttribute('.button.checkout', 'disabled');

		if (
			!selectableItemsCount ||
			!cartItemGridLength
		) {
			cartItemAllVisible.setAttribute('checked', 0);
			elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		}
	}

	if (checkoutItemContainer) {
		if (!cartItemData.length) {
			window.location.href = requestParameters.settings.base_url + 'cart';
			return false;
		}

		checkoutItems += '<h2>Order Items</h2>';
		cartItemData.map(function(cartItem, index) {
			checkoutItems += '<div class="item-container item-button"><p>' + cartItem[1].quantity + ' ' + cartItem[1].name + '</p><p class="no-margin-bottom">' + cartItem[1].price + ' ' + requestParameters.settings.billing_currency + ' for ' + cartItem[1].interval_value + ' ' + cartItem[1].interval_type + (cartItem[1].interval_value !== 1 ? 's' : '') + '</p><div class="item-link-container"></div></div>';
			cartSubtotal += parseFloat(cartItem[1].price);
		});
		cartTotal = cartSubtotal;
		checkoutItems += '<h2>Pricing Details</h2>';
		checkoutItems += '<p class="no-margin-bottom"><label>Subtotal</label></p>';
		checkoutItems += '<p>' + (Math.round(cartSubtotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + requestParameters.settings.billing_currency + '</p>';
		checkoutItems += '<p class="no-margin-bottom"><label>Cart Total</label></p>';
		checkoutItems += '<p>' + (Math.round(cartTotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + requestParameters.settings.billing_currency + '</p>';
		checkoutItems += '<p class="message">Additional fees for shipping and/or tax may apply before submitting final payment.</p>';
		checkoutItems += '<a class="button confirm main-button" disabled href="' + requestParameters.settings.base_url + 'confirm">Proceed to Payment</a>';
		checkoutItemContainer.innerHTML = checkoutItems;
		elements.html('.item-configuration .cart-total .total', (Math.round(cartTotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + requestParameters.settings.billing_currency);
		elements.removeAttribute('.button.confirm', 'disabled');
	}

	if (cartItemData.length) {
		elements.removeClass('.item-configuration .item-controls', 'hidden');
	}

	processWindowEvents('resize');
};
var processDelete = function() {
	requestParameters.data = {
		id: cartItemGrid
	};
	sendRequest(function(response) {
		processCartItems(response);
	});
};
requestParameters.url = '/api/carts';
