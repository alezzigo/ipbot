'use_strict';

var defaultTable = 'orders';
var previousAction = 'find';
var processOrders = function() {
	requestParameters.action = previousAction;
	requestParameters.table = defaultTable;
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.data.length) {
			response.data.map(function(item, index) {
				document.querySelector('.orders-container').innerHTML += '<div class="item-container item-button"><div class="item"><div class="item-body"><p><strong>' + item.quantity + ' ' + item.name + '</strong></p><p>$' + item.price + ' per ' + (item.interval_value > 1 ? item.interval_value + ' ' : '') + item.interval_type + (item.interval_value > 1 ? 's' : '') + '</p><label class="label ' + item.status + '">' + capitalizeString(item.status) + '</label></div></div><div class="item-link-container"><a class="item-link" href="/orders/' + item.id + '"></a></div></div>';
			});
		}
	});
};
requestParameters.url = '/api/orders';
