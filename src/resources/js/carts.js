'use_strict';

var processCart = () => {
	var processCartItems = (response) => {
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
