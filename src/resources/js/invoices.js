'use_strict';

var defaultTable = 'invoices';
var previousAction = 'find';
var processInvoice = function() {
	var invoiceId = document.querySelector('input[name="invoice_id"]').value;
	requestParameters.conditions = {
		id: invoiceId
	};
	sendRequest(function(response) {
		// ..
	});
};
var processInvoices = function() {
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
				document.querySelector('.invoices-container').innerHTML += '<div class="item-container item-button"><div class="item"><div class="item-body"><p><strong>Invoice #' + item.id + '</strong></p><p>Status: ' + item.status + '</p></div></div><div class="item-link-container"><a class="item-link" href="/invoices/' + item.id + '"></a></div></div>';
			});
		}
	});
};
requestParameters.action = previousAction;
requestParameters.table = defaultTable;
requestParameters.url = '/api/invoices';
