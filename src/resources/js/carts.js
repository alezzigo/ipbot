'use_strict';

var processCart = () => {
	var processCartItems = (response) => {
		var cartItems = '',
			cartItemGrid = {},
			cartItemAllVisible = document.querySelector('.item-container .checkbox[index="all-visible"]'),
			cartItemContainer = document.querySelector('.cart-items-container'),
			intervalTypes = ['month', 'year'],
			intervalValues = range(1, 12),
			messageContainer = document.querySelector('.carts-view .message-container');
		var processCartItemGrid = (cartItemIndexes, cartItemState) => {
			cartItemIndexes.map((cartItemIndex) => {
				var cartItem = document.querySelector('.cart-items-container .checkbox[index="' + cartItemIndex + '"]');
				var cartItemId = cartItem.getAttribute('cart_item_id');
				cartItem.setAttribute('checked', +cartItemState);
				cartItemGrid['cartItem' + cartItemId] = cartItemId;

				if (!+cartItemState) {
					delete cartItemGrid['cartItem' + cartItemId];
				}
			});
			elements.html('.item-configuration .total-checked', +(allVisibleChecked = Object.entries(cartItemGrid).length));
			cartItemAllVisible.setAttribute('checked', +(allVisibleChecked === selectAllElements('.cart-items-container .item-button-selectable').length));
			requestParameters.items[requestParameters.table] = cartItemGrid;
		};
		var cartItemToggle = (button) => {
			cartItemContainer.setAttribute('current_checked', button.getAttribute('index'));
			processCartItemGrid(window.event.shiftKey ? range(cartItemContainer.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +document.querySelector('.cart-items-container .checkbox[index="' + cartItemContainer.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
			cartItemContainer.setAttribute('previous_checked', button.getAttribute('index'));
		};
		var cartItemToggleAllVisible = (button) => {
			cartItemContainer.setAttribute('current_checked', 0);
			cartItemContainer.setAttribute('previous_checked', 0);
			processCartItemGrid(range(0, selectAllElements('.cart-items-container .item-button-selectable').length - 1), +button.getAttribute('checked') === 0);
		};
		var processCartItemUpdate = (cartItemId) => {
			var cartItem = document.querySelector('.item-container[cart_item_id="' + cartItemId + '"]');
			requestParameters.data.id = cartItemId;
			requestParameters.data.quantity = cartItem.querySelector('select.quantity').value;
			requestParameters.data.interval_type = cartItem.querySelector('select.interval-type').value;
			requestParameters.data.interval_value = cartItem.querySelector('select.interval-value').value;
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
			cartItems += '<div class="item-container item-button item-button-selectable" cart_item_id="' + cartItem.id + '"><span checked="0" class="checkbox" index="' + index + '" cart_item_id="' + cartItem.id + '"></span><p><strong>' + cartItem.name + '</strong></p><div class="field-group"><span>Quantity:</span><select class="quantity" name="quantity">' + quantitySelectValues + '</select></div><div class="field-group no-margin"><span>USD Price:</span><span class="price">' + cartItem.price + '</span><span>per</span><select class="interval-value" name="interval_value">' + intervalSelectValues + '</select><select class="interval-type" name="interval_type">' + intervalSelectTypes + '</select></div><div class="clear"></div></div>';
		});
		document.querySelector('.cart-items-container').innerHTML = cartItems;
		cartItemAllVisible.removeEventListener('click', cartItemAllVisible.clickListener);
		cartItemAllVisible.clickListener = () => {
			cartItemToggleAllVisible(cartItemAllVisible);
		};
		cartItemAllVisible.addEventListener('click', cartItemAllVisible.clickListener);
		elements.loop('.cart-items-container .item-button-selectable', (index, row) => {
			var cartItemToggleButton = row.querySelector('.checkbox');
			var cartItemUpdateQuantitySelect = row.querySelector('select.quantity');
			var cartItemUpdateIntervalTypeSelect = row.querySelector('select.interval-type');
			var cartItemUpdateIntervalValueSelect = row.querySelector('select.interval-value');
			cartItemToggleButton.removeEventListener('click', cartItemToggleButton.clickListener);
			cartItemUpdateQuantitySelect.removeEventListener('change', cartItemUpdateQuantitySelect.changeListener);
			cartItemUpdateIntervalTypeSelect.removeEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.removeEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);
			cartItemToggleButton.clickListener = () => {
				cartItemToggle(cartItemToggleButton);
			};
			cartItemUpdateQuantitySelect.changeListener = cartItemUpdateIntervalTypeSelect.changeListener = cartItemUpdateIntervalValueSelect.changeListener = () => {
				processCartItemUpdate(cartItemToggleButton.getAttribute('cart_item_id'));
			};
			cartItemToggleButton.addEventListener('click', cartItemToggleButton.clickListener);
			cartItemUpdateQuantitySelect.addEventListener('change', cartItemUpdateQuantitySelect.changeListener);
			cartItemUpdateIntervalTypeSelect.addEventListener('change', cartItemUpdateIntervalTypeSelect.changeListener);
			cartItemUpdateIntervalValueSelect.addEventListener('change', cartItemUpdateIntervalValueSelect.changeListener);
		});
		elements.html('.item-configuration .total-results', cartItemData.length);
		elements.removeClass('.item-configuration .item-controls', 'hidden');
		processWindowEvents(windowEvents, 'resize');
	};
	requestParameters.action = 'cart';
	requestParameters.table = 'carts';
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
