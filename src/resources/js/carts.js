var processCart = function() {
	api.setRequestParameters({
		action: 'cart',
		url: apiRequestParameters.current.settings.baseUrl + 'api/carts'
	});
	api.setRequestParameters({
		listCartItems: {
			callback: function(response, itemListParameters) {
				processCartItems(response, itemListParameters);
			},
			initial: true,
			messages: {
				carts: '<p class="message no-margin-top">Loading</p>'
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
							name: 'data-title',
							value: 'Remove item from cart'
						},
						{
							name: 'item-function'
						},
						{
							name: 'process',
							value: 'remove'
						},
					],
					tag: 'span'
				}
			],
			page: 1,
			resultsPerPage: 10,
			selector: '.item-list[table="carts"]',
			table: 'carts'
		}
	});
	processItemList('listCartItems');
};
var processConfirm = function() {
	// ..
};
const processCartItems = function(response, itemListParameters) {
	if (typeof itemListParameters !== 'object') {
		processItemList('listCartItems');
	} else {
		elements.html('.message-container.carts', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		let cartSubtotal = cartTotal = 0;
		const intervalTypes = ['month', 'year'];
		const intervalValues = range(1, 12);
		let additionalItemControlData = itemListData = '';
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
				if (
					typeof response.redirect === 'string' &&
					response.redirect
				) {
					window.location.href = response.redirect;
					return false;
				}
			});
		};
		const processCartItemUpdate = function(cartItemId) {
			let cartItemSelector = itemListParameters.selector + ' .item-container[cart_item_id="' + cartItemId + '"]';
			let selectedCartItems = apiRequestParameters.current.items.carts;
			api.setRequestParameters({
				data: {
					id: cartItemId,
					intervalType: elements.get(cartItemSelector + ' select.interval-type').value,
					intervalValue: elements.get(cartItemSelector + ' select.interval-value').value,
					quantity: elements.get(cartItemSelector + ' select.quantity').value
				},
				items: {
					carts: {}
				}
			}, true);
			elements.setAttribute('.button.checkout', 'disabled');
			api.sendRequest(function(response) {
				api.setRequestParameters({
					items: {
						carts: selectedCartItems
					}
				}, true);
				processCartItems(response);
			});
		};
		let processPage = elements.getAttribute(itemListParameters.selector, 'page');

		if (
			processPage === 'cart' &&
			response.data.cartItems
		) {
			for (itemListDataKey in response.data.cartItems) {
				let intervalSelectTypes = intervalSelectValues = quantitySelectValues = '';
				let item = response.data.cartItems[itemListDataKey];
				let quantityIncrementValue = 1;
				const quantityValueCount = item.maximumQuantity - item.minimumQuantity;

				if (quantityValueCount > 1000) {
					quantityIncrementValue = +(item.minimumQuantity.toString().charAt(0) + repeat(Math.floor(Math.min(quantityValueCount.toString().length / 2, 8)), '0'));
				}

				const quantityValues = range(item.minimumQuantity, item.maximumQuantity, quantityIncrementValue);
				intervalTypes.map(function(intervalType, index) {
					intervalSelectTypes += '<option ' + (intervalType == item.intervalType ? 'selected ' : '') + 'value="' + intervalType + '">' + capitalizeString(intervalType) + (item.intervalValue > 1 ? 's' : '') + '</option>';
				});
				intervalValues.map(function(intervalValue, index) {
					intervalSelectValues += '<option ' + (intervalValue == item.intervalValue ? 'selected ' : '') + 'value="' + intervalValue + '">' + intervalValue + '</option>';
				});
				quantityValues.map(function(quantityValue, index) {
					quantitySelectValues += '<option ' + (quantityValue == item.quantity ? 'selected ' : '') + 'value="' + quantityValue + '">' + quantityValue + '</option>';
				});
				itemListData += '<div class="item-button item-button-selectable item-container" cart_item_id="' + item.id + '">';
				itemListData += '<span checked="0" class="checkbox" index="' + itemListDataKey + '" cart_item_id="' + item.id + '"></span>';
				itemListData += '<p><a href="' + apiRequestParameters.current.settings.baseUrl + item.uri + '">' + item.name + '</a></p>';
				itemListData += '<div class="field-group">';
				itemListData += '<span>Quantity:</span><select class="quantity" name="quantity">' + quantitySelectValues + '</select>';
				itemListData += '</div>';
				itemListData += '<div class="field-group no-margin">';
				itemListData += '<span>Price:</span><span class="display">' + item.price + ' ' + apiRequestParameters.current.settings.billingCurrency + '</span><span>for</span>';
				itemListData += '<select class="interval-value" name="interval_value">' + intervalSelectValues + '</select>';
				itemListData += '<select class="interval-type" name="interval_type">' + intervalSelectTypes + '</select>';
				itemListData += '</div>';
				itemListData += '<div class="clear"></div>';
				itemListData += '</div>';
			}

			additionalItemControlData += '<div class="clear"></div>';
			additionalItemControlData += '<p class="item-controls no-margin-bottom">';
			additionalItemControlData += '<a class="align-right button main-button checkout" href="/checkout">Checkout</a>';
			additionalItemControlData += '<span class="align-left cart-subtotal">Cart Subtotal: <span class="total">' + (Math.round(response.data.cart.subtotal * 100) / 100).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + apiRequestParameters.current.settings.billingCurrency + '</span></span>';
			additionalItemControlData += '</p>';
		}

		if (processPage === 'checkout') {
			// ..
		}

		elements.html(itemListParameters.selector + '[page="' + processPage + '"] .items', itemListData);
		elements.html(itemListParameters.selector + '[page="' + processPage + '"] .additional-item-controls', additionalItemControlData);
		elements.loop(itemListParameters.selector + ' .item-button-selectable', function(index, row) {
			let cartItemUpdateIntervalTypeSelect = row.querySelector('select.interval-type');
			let cartItemUpdateIntervalValueSelect = row.querySelector('select.interval-value');
			let cartItemUpdateQuantitySelect = row.querySelector('select.quantity');
			cartItemUpdateIntervalTypeSelect.removeEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.removeEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);
			cartItemUpdateQuantitySelect.removeEventListener('change', cartItemUpdateQuantitySelect.changeListener);
			let cartItemId = row.getAttribute('cart_item_id');
			cartItemUpdateIntervalTypeSelect.changeListener = cartItemUpdateIntervalValueSelect.changeListener = cartItemUpdateQuantitySelect.changeListener = function() {
				processCartItemUpdate(cartItemId);
			};
			cartItemUpdateIntervalTypeSelect.addEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.addEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);
			cartItemUpdateQuantitySelect.addEventListener('change', cartItemUpdateQuantitySelect.changeListener);
		});
		api.setRequestParameters({
			data: {}
		});

		selectAllElements('.button.add-to-cart', function(selectedElementKey, selectedElement) {
			selectedElement.removeEventListener('click', selectedElement.clickListener);
			selectedElement.clickListener = function() {
				processCartItemAdd(selectedElement);
			};
			selectedElement.addEventListener('click', selectedElement.clickListener);
		});
		elements.removeAttribute('.button.add-to-cart', 'disabled');
	}

	elements.html('.message-container.carts', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
};
var processRemove = function() {
	api.setRequestParameters({
		action: 'remove',
		url: apiRequestParameters.current.settings.baseUrl + 'api/carts'
	});
	api.sendRequest(function(response) {
		api.setRequestParameters({
			action: 'cart',
			items: {
				carts: {}
			}
		}, true);
		processItemList('listCartItems', function() {
			elements.html('.message-container.carts', typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		});
	});
};
api.setRequestParameters({
	table: 'carts'
});
