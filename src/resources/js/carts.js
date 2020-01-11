var cartItemGrid = {};
var messageContainer = document.querySelector('.item-configuration .message-container');
const processCart = function() {
	api.setRequestParameters({
		action: 'cart',
		url: apiRequestParameters.current.settings.baseUrl + 'api/carts'
	});
	api.setRequestParameters({
		listCartItems: {
			initial: true,
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
							name: 'data-title',
							value: 'Delete item from cart'
						},
						{
							name: 'item-function'
						},
						{
							name: 'process',
							value: 'delete'
						},
					],
					tag: 'span'
				}
			],
			page: 1,
			resultsPerPage: 100,
			selector: '.item-list[table="carts"]',
			table: 'carts'
		}
	});
	api.sendRequest(function(response) {
		processCartItems(response);
	});
};
const processConfirm = function() {
	api.setRequestParameters({
		action: 'complete',
		url: apiRequestParameters.current.settings.baseUrl + 'api/carts'
	});
	api.sendRequest(function(response) {
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
const processCartItems = function(response) {
	var cartItemAddButtons = selectAllElements('.button.add-to-cart');
	var cartItemAllVisible = document.querySelector('.item-container .checkbox[index="all-visible"]');
	var cartItemContainer = document.querySelector('.cart-items-container');
	const cartItemToggle = function(button) {
		cartItemContainer.setAttribute('current_checked', button.getAttribute('index'));
		processCartItemGrid(window.event.shiftKey ? range(cartItemContainer.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +cartItemContainer.querySelector('.checkbox[index="' + cartItemContainer.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
		cartItemContainer.setAttribute('previous_checked', button.getAttribute('index'));
	};
	const cartItemToggleAllVisible = function(button) {
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
	const processCartItemAdd = function(cartItemAddButton) {
		api.setRequestParameters({
			data: {
				intervalType: cartItemAddButton.hasAttribute('interval_type') ? cartItemAddButton.getAttribute('interval_type') : 'month',
				intervalValue: cartItemAddButton.hasAttribute('interval_value') ? cartItemAddButton.getAttribute('interval_value') : 1,
				productId: cartItemAddButton.hasAttribute('product_id') ? cartItemAddButton.getAttribute('product_id') : 0,
				quantity: cartItemAddButton.hasAttribute('quantity') ? cartItemAddButton.getAttribute('quantity') : 0
			}
		}, true);
		api.sendRequest(function(response) {
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
	const processCartItemGrid = function(cartItemIndexes, cartItemState) {
		cartItemIndexes.map(function(cartItemIndex) {
			const cartItem = cartItemContainer.querySelector('.checkbox[index="' + cartItemIndex + '"]');
			const cartItemId = cartItem.getAttribute('cart_item_id');
			cartItem.setAttribute('checked', +cartItemState);
			cartItemGrid['cartItem' + cartItemId] = cartItemId;

			if (!+cartItemState) {
				delete cartItemGrid['cartItem' + cartItemId];
			}
		});
		elements.html('.item-configuration .total-checked', +(allVisibleChecked = Object.entries(cartItemGrid).length));
		allVisibleChecked ? elements.removeClass('.item-configuration span.icon[item-function]', 'hidden') : elements.addClass('.item-configuration span.icon[item-function]', 'hidden');
		cartItemAllVisible.setAttribute('checked', +(allVisibleChecked === selectAllElements('.cart-items-container .item-button-selectable').length));
		api.setRequestParameters({
			items: {
				carts: cartItemGrid
			}
		});
	};
	const processCartItemUpdate = function(cartItemId) {
		var cartItem = document.querySelector('.item-container[cart_item_id="' + cartItemId + '"]');
		api.setRequestParameters({
			data: {
				id: cartItemId,
				intervalType: cartItem.querySelector('select.interval-type').value,
				intervalValue: cartItem.querySelector('select.interval-value').value,
				quantity: cartItem.querySelector('select.quantity').value
			}
		}, true);
		elements.setAttribute('.button.checkout', 'disabled');
		api.sendRequest(function(response) {
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
			var quantityIncrementValue = 1;
			var quantityValueCount = cartItem[1].maximumQuantity - cartItem[1].minimumQuantity;

			if (quantityValueCount > 1000) {
				quantityIncrementValue = +(cartItem[1].minimumQuantity.toString().charAt(0) + repeat(Math.floor(Math.min(quantityValueCount.toString().length / 2, 8)), '0'));
			}

			var quantityValues = range(cartItem[1].minimumQuantity, cartItem[1].maximumQuantity, quantityIncrementValue);
			intervalTypes.map(function(intervalType, index) {
				intervalSelectTypes += '<option ' + (intervalType == cartItem[1].intervalType ? 'selected ' : '') + 'value="' + intervalType + '">' + capitalizeString(intervalType) + (cartItem[1].intervalValue > 1 ? 's' : '') + '</option>';
			});
			intervalValues.map(function(intervalValue, index) {
				intervalSelectValues += '<option ' + (intervalValue == cartItem[1].intervalValue ? 'selected ' : '') + 'value="' + intervalValue + '">' + intervalValue + '</option>';
			});
			quantityValues.map(function(quantityValue, index) {
				quantitySelectValues += '<option ' + (quantityValue == cartItem[1].quantity ? 'selected ' : '') + 'value="' + quantityValue + '">' + quantityValue + '</option>';
			});
			cartItems += '<div class="item-button item-button-selectable item-container" cart_item_id="' + cartItem[1].id + '">';
			cartItems += '<span checked="' + +(typeof cartItemGrid['cartItem' + cartItem[1].id] !== 'undefined') + '" class="checkbox" index="' + index + '" cart_item_id="' + cartItem[1].id + '"></span>';
			cartItems += '<p><a href="' + apiRequestParameters.current.settings.baseUrl + cartItem[1].uri + '">' + cartItem[1].name + '</a></p>';
			cartItems += '<div class="field-group">';
			cartItems += '<span>Quantity:</span><select class="quantity" name="quantity">' + quantitySelectValues + '</select>';
			cartItems += '</div>';
			cartItems += '<div class="field-group no-margin">';
			cartItems += '<span>Price:</span><span class="display">' + cartItem[1].price + ' ' + apiRequestParameters.current.settings.billingCurrency + '</span><span>for</span>';
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
		elements.html('.item-configuration .cart-subtotal .total', (Math.round(cartSubtotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + apiRequestParameters.current.settings.billingCurrency);
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
			window.location.href = apiRequestParameters.current.settings.baseUrl + 'cart';
			return false;
		}

		checkoutItems += '<h2>Order Items</h2>';
		cartItemData.map(function(cartItem, index) {
			checkoutItems += '<div class="item-container item-button"><p>' + cartItem[1].quantity + ' ' + cartItem[1].name + '</p><p class="no-margin-bottom">' + cartItem[1].price + ' ' + apiRequestParameters.current.settings.billingCurrency + ' for ' + cartItem[1].intervalValue + ' ' + cartItem[1].intervalType + (cartItem[1].intervalValue !== 1 ? 's' : '') + '</p><div class="item-link-container"></div></div>';
			cartSubtotal += parseFloat(cartItem[1].price);
		});
		cartTotal = cartSubtotal;
		checkoutItems += '<h2>Pricing Details</h2>';
		checkoutItems += '<p class="no-margin-bottom"><label>Subtotal</label></p>';
		checkoutItems += '<p>' + (Math.round(cartSubtotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + apiRequestParameters.current.settings.billingCurrency + '</p>';
		checkoutItems += '<p class="no-margin-bottom"><label>Cart Total</label></p>';
		checkoutItems += '<p>' + (Math.round(cartTotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + apiRequestParameters.current.settings.billingCurrency + '</p>';
		checkoutItems += '<p class="message">Additional fees for shipping and/or tax may apply before submitting final payment.</p>';
		checkoutItems += '<a class="button confirm main-button" disabled href="' + apiRequestParameters.current.settings.baseUrl + 'confirm">Proceed to Payment</a>';
		checkoutItemContainer.innerHTML = checkoutItems;
		elements.html('.item-configuration .cart-total .total', (Math.round(cartTotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + apiRequestParameters.current.settings.billingCurrency);
		elements.removeAttribute('.button.confirm', 'disabled');
	}

	if (cartItemData.length) {
		elements.removeClass('.item-configuration .item-controls', 'hidden');
	}

	processWindowEvents('resize');
};
const processDelete = function() {
	api.setRequestParameters({
		data: {
			id: cartItemGrid
		},
		url: apiRequestParameters.current.settings.baseUrl + 'api/carts'
	});
	api.sendRequest(function(response) {
		processCartItems(response);
	});
};
api.setRequestParameters({
	table: 'carts'
});
