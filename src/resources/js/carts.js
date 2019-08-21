'use_strict';

var cartItemGrid = {},
	messageContainer = document.querySelector('.item-configuration .message-container');
var processCart = () => {
	requestParameters.action = 'cart';
	requestParameters.table = 'carts';
	sendRequest((response) => {
		processCartItems(response);
	});
};
var processCartItems = (response) => {
	var cartItemAddButtons = selectAllElements('.button.add-to-cart'),
		cartItemAllVisible = document.querySelector('.item-container .checkbox[index="all-visible"]'),
		cartItemContainer = document.querySelector('.cart-items-container'),
		cartItems = checkoutItems = '',
		cartTotal = 0,
		checkoutItemContainer = document.querySelector('.checkout-items-container'),
		intervalTypes = ['month', 'year'],
		intervalValues = range(1, 12);
	var processCartItemGrid = (cartItemIndexes, cartItemState) => {
		cartItemIndexes.map((cartItemIndex) => {
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
	var cartItemToggle = (button) => {
		cartItemContainer.setAttribute('current_checked', button.getAttribute('index'));
		processCartItemGrid(window.event.shiftKey ? range(cartItemContainer.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +cartItemContainer.querySelector('.checkbox[index="' + cartItemContainer.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		cartItemContainer.setAttribute('previous_checked', button.getAttribute('index'));
	};
	var cartItemToggleAllVisible = (button) => {
		var selectableItemsCount = selectAllElements('.cart-items-container .item-button-selectable').length;
		cartItemContainer.setAttribute('current_checked', 0);
		cartItemContainer.setAttribute('previous_checked', 0);

		if (selectableItemsCount) {
			processCartItemGrid(range(0, selectableItemsCount - 1), +button.getAttribute('checked') === 0);
		}
	};
	var processCartItemAdd = (cartItemAddButton) => {
		requestParameters.data.interval_type = cartItemAddButton.hasAttribute('interval_type') ? cartItemAddButton.getAttribute('interval_type') : 'month';
		requestParameters.data.interval_value = cartItemAddButton.hasAttribute('interval_value') ? cartItemAddButton.getAttribute('interval_value') : 1;
		requestParameters.data.product_id = cartItemAddButton.hasAttribute('product_id') ? cartItemAddButton.getAttribute('product_id') : 0;
		requestParameters.data.quantity = cartItemAddButton.hasAttribute('quantity') ? cartItemAddButton.getAttribute('quantity') : 0;
		sendRequest((response) => {
			var messageContainer = document.querySelector('main.product .message-container');

			if (messageContainer) {
				messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : 'asdf');
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
	var processCartItemUpdate = (cartItemId) => {
		var cartItem = document.querySelector('.item-container[cart_item_id="' + cartItemId + '"]');
		requestParameters.data.id = cartItemId;
		requestParameters.data.interval_type = cartItem.querySelector('select.interval-type').value;
		requestParameters.data.interval_value = cartItem.querySelector('select.interval-value').value;
		requestParameters.data.quantity = cartItem.querySelector('select.quantity').value;
		elements.setAttribute('.button.checkout', 'disabled');
		sendRequest((response) => {
			processCartItems(response);
		});
	};

	if (messageContainer) {
		messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');
	}

	if (response.code !== 200) {
		return;
	}

	var cartItemData = Object.values(response.data);

	if (cartItemAddButtons.length) {
		cartItemAddButtons.map((cartItemAddButton, index) => {
			cartItemAddButton = cartItemAddButton[1];
			cartItemAddButton.removeEventListener('click', cartItemAddButton.clickListener);
			cartItemAddButton.clickListener = () => {
				processCartItemAdd(cartItemAddButton);
			};
			cartItemAddButton.addEventListener('click', cartItemAddButton.clickListener);
		});
		elements.removeAttribute('.button.add-to-cart', 'disabled');
	}

	if (cartItemContainer) {
		cartItemData.map((cartItem, index) => {
			var intervalSelectTypes = intervalSelectValues = quantitySelectValues = '';
			var quantityValues = range(cartItem.minimum_quantity, cartItem.maximum_quantity);
			intervalTypes.map((intervalType, index) => {
				intervalSelectTypes += '<option ' + (intervalType == cartItem.interval_type ? 'selected ' : '') + 'value="' + intervalType + '">' + capitalizeString(intervalType) + (cartItem.interval_value > 1 ? 's' : '') + '</option>';
			});
			intervalValues.map((intervalValue, index) => {
				intervalSelectValues += '<option ' + (intervalValue == cartItem.interval_value ? 'selected ' : '') + 'value="' + intervalValue + '">' + intervalValue + '</option>';
			});
			quantityValues.map((quantityValue, index) => {
				quantitySelectValues += '<option ' + (quantityValue == cartItem.quantity ? 'selected ' : '') + 'value="' + quantityValue + '">' + quantityValue + '</option>';
			});
			cartItems += '<div class="item-button item-button-selectable item-container" cart_item_id="' + cartItem.id + '"><span checked="' + +(typeof cartItemGrid['cartItem' + cartItem.id] !== 'undefined') + '" class="checkbox" index="' + index + '" cart_item_id="' + cartItem.id + '"></span><p><a href="' + requestParameters.base_url + cartItem.uri + '">' + cartItem.name + '</a></p><div class="field-group"><span>Quantity:</span><select class="quantity" name="quantity">' + quantitySelectValues + '</select></div><div class="field-group no-margin"><span>USD Price:</span><span class="display">$' + cartItem.price + '</span><span>per</span><select class="interval-value" name="interval_value">' + intervalSelectValues + '</select><select class="interval-type" name="interval_type">' + intervalSelectTypes + '</select></div><div class="clear"></div></div>';
			cartTotal += parseFloat(cartItem.price);
		});
		cartItemContainer.innerHTML = cartItems;
		cartItemAllVisible.removeEventListener('click', cartItemAllVisible.clickListener);
		cartItemAllVisible.clickListener = () => {
			cartItemToggleAllVisible(cartItemAllVisible);
		};
		cartItemAllVisible.addEventListener('click', cartItemAllVisible.clickListener);
		cartItemGrid = {};
		elements.loop('.cart-items-container .item-button-selectable', (index, row) => {
			var cartItemToggleButton = row.querySelector('.checkbox');
			var cartItemUpdateQuantitySelect = row.querySelector('select.quantity');
			var cartItemUpdateIntervalTypeSelect = row.querySelector('select.interval-type');
			var cartItemUpdateIntervalValueSelect = row.querySelector('select.interval-value');
			var cartItemId = cartItemToggleButton.getAttribute('cart_item_id');
			cartItemToggleButton.removeEventListener('click', cartItemToggleButton.clickListener);
			cartItemUpdateQuantitySelect.removeEventListener('change', cartItemUpdateQuantitySelect.changeListener);
			cartItemUpdateIntervalTypeSelect.removeEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.removeEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);
			cartItemToggleButton.clickListener = () => {
				cartItemToggle(cartItemToggleButton);
			};
			cartItemUpdateQuantitySelect.changeListener = cartItemUpdateIntervalTypeSelect.changeListener = cartItemUpdateIntervalValueSelect.changeListener = () => {
				processCartItemUpdate(cartItemId);
			};
			cartItemToggleButton.addEventListener('click', cartItemToggleButton.clickListener);
			cartItemUpdateQuantitySelect.addEventListener('change', cartItemUpdateQuantitySelect.changeListener);
			cartItemUpdateIntervalTypeSelect.addEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.addEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);

			if (+cartItemToggleButton.getAttribute('checked')) {
				cartItemGrid['cartItem' + cartItemId] = cartItemId;
			}
		});
		var cartItemGridLength = +(Object.entries(cartItemGrid).length),
			selectableItemsCount = selectAllElements('.cart-items-container .item-button-selectable').length;
		elements.html('.item-configuration .total-checked', cartItemGridLength);
		elements.html('.item-configuration .total-results', cartItemData.length);
		elements.html('.item-configuration .cart-subtotal .total', '$' + (Math.round(cartTotal * 100) / 100) + ' USD');
		elements.removeClass('.item-configuration .item-controls', 'hidden');
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
		checkoutItems += '<h2 class="no-margin-top">Review Items</h2>';
		cartItemData.map((cartItem, index) => {
			checkoutItems += '<div class="item-button item-button-selectable item-container" cart_item_id="' + cartItem.id + '"><p><strong>' + cartItem.name + '</strong></p><div class="field-group"><span>Quantity:</span><span class="display">' + cartItem.quantity + '</span></div><div class="field-group no-margin"><span>USD Price:</span><span class="display">$' + cartItem.price + '</span><span>per</span><span class="display">' + cartItem.interval_value + ' ' + capitalizeString(cartItem.interval_type) + (cartItem.interval_value > 1 ? 's' : '') + '</span></div><div class="clear"></div></div>';
			cartTotal += parseFloat(cartItem.price);
		});
		checkoutItems += '<h2>Discount Code</h2><div class="field-group no-margin-top"><input class="discount-code-field" id="discount-code" name="discount_code" placeholder="Enter discount code" type="text"><button class="button discount-code-button">Apply Discount</button></div>';
		checkoutItemContainer.innerHTML = checkoutItems;
		elements.html('.item-configuration .cart-total .total', '$' + (Math.round(cartTotal * 100) / 100) + ' USD');
		elements.removeAttribute('.button.confirm', 'disabled');
	}

	processWindowEvents(windowEvents, 'resize');
};
var processDelete = () => {
	requestParameters.data = {
		id: cartItemGrid
	};
	sendRequest((response) => {
		processCartItems(response);
	});
};
requestParameters.url = '/api/carts';
onLoad(() => {
	setTimeout(() => {
		processCart();
	}, 100)
});
