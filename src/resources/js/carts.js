'use_strict';

var processCart = () => {
	var processCartItems = (response) => {
		var cartItems = {},
			cartItemAllVisible = document.querySelector('.item-container .checkbox[index="all-visible"]'),
			cartItemContainer = document.querySelector('.cart-items-container');
		var processCartItems = (cartItemIndexes, cartItemState) => {
			cartItemIndexes.map((cartItemIndex) => {
				var cartItem = document.querySelector('.cart-items-container .checkbox[index="' + cartItemIndex + '"]');
				var cartItemId = cartItem.getAttribute('cart_item_id');
				cartItem.setAttribute('checked', +cartItemState);
				cartItems['cartItem' + cartItemId] = cartItemId;

				if (!+cartItemState) {
					delete cartItems['cartItem' + cartItemId];
				}
			});
			elements.html('.item-configuration .total-checked', +(allVisibleChecked = Object.entries(cartItems).length));
			cartItemAllVisible.setAttribute('checked', +(allVisibleChecked === selectAllElements('.cart-items-container .item-button-selectable').length));
			requestParameters.items[requestParameters.table] = cartItems;
		};
		var cartItemToggle = (button) => {
			cartItemContainer.setAttribute('current_checked', button.getAttribute('index'));
			processCartItems(window.event.shiftKey ? range(cartItemContainer.getAttribute('previous_checked'), button.getAttribute('index')) : [button.getAttribute('index')], window.event.shiftKey ? +document.querySelector('.cart-items-container .checkbox[index="' + cartItemContainer.getAttribute('previous_checked') + '"]').getAttribute('checked') !== 0 : +button.getAttribute('checked') === 0);
			cartItemContainer.setAttribute('previous_checked', button.getAttribute('index'));
		};
		var cartItemToggleAllVisible = (button) => {
			cartItemContainer.setAttribute('current_checked', 0);
			cartItemContainer.setAttribute('previous_checked', 0);
			processCartItems(range(0, selectAllElements('.cart-items-container .item-button-selectable').length - 1), +button.getAttribute('checked') === 0);
		};
		var messageContainer = document.querySelector('.carts-view .message-container'),
			intervals = range(1, 12);

		if (messageContainer) {
			messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');
		}

		if (
			response.code !== 200 ||
			!response.data.length
		) {
			return;
		}

		response.data.map((item, index) => {
			var intervalValues;
			intervals.map((interval, index) => {
				intervalValues += '<option ' + (interval === item.interval_value ? 'selected ' : '') + 'value="' + interval + '">' + interval + '</option>';
			});
			var cartItem = '<div class="item-container item-button item-button-selectable"><span checked="0" class="checkbox" index="' + index + '" cart_item_id="' + item.id + '"></span><p><strong>' + item.name + '</strong></p><div class="field-group"><span>Quantity:</span><select class="quantity" name="quantity"><option value="1">1</option></select></div><div class="field-group no-margin"><span>USD Price:</span><span class="price">' + item.price + '</span><span>per</span><select class="interval-value" name="interval_value">' + intervalValues + '</select><select class="interval-type" name="interval_type"><option value="month">Month' + (item.interval_value > 1 ? 's' : '') + '</option><option value="year">Year' + (item.interval_value > 1 ? 's' : '') + '</option></select></div><div class="clear"></div></div>';
			document.querySelector('.cart-items-container').innerHTML += cartItem;
		});
		cartItemAllVisible.removeEventListener('click', cartItemAllVisible.clickListener);
		cartItemAllVisible.clickListener = () => {
			cartItemToggleAllVisible(cartItemAllVisible);
		};
		cartItemAllVisible.addEventListener('click', cartItemAllVisible.clickListener);
		elements.loop('.cart-items-container .item-button-selectable', (index, row) => {
			var cartItemToggleButton = row.querySelector('.checkbox');
			cartItemToggleButton.removeEventListener('click', cartItemToggleButton.clickListener);
			cartItemToggleButton.clickListener = () => {
				cartItemToggle(cartItemToggleButton);
			};
			cartItemToggleButton.addEventListener('click', cartItemToggleButton.clickListener);
		});
		elements.html('.item-configuration .total-results', response.data.length);
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
