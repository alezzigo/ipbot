'use_strict';

var defaultTable = 'invoices';
var previousAction = 'find';
var processInvoice = function() {
	requestParameters.action = 'invoice';
	var invoiceId = document.querySelector('input[name="invoice_id"]').value;
	requestParameters.conditions = {
		id: invoiceId
	};
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');
		}

		if (response.data.invoice) {
			document.querySelector('.invoice-name').innerHTML = '<label class="label ' + response.data.invoice.status + '">' + capitalizeString(response.data.invoice.status) + '</label> Invoice #' + response.data.invoice.id;
			elements.removeClass('.item-configuration .item-controls', 'hidden');
		}
	});
};
var processInvoices = function() {
	requestParameters.action = previousAction;
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
				document.querySelector('.invoices-container').innerHTML += '<div class="item-container item-button"><div class="item"><div class="item-body"><p><strong>Invoice #' + item.id + '</strong></p><label class="label ' + item.status + '">' + capitalizeString(item.status) + '</label></div></div><div class="item-link-container"><a class="item-link" href="/invoices/' + item.id + '"></a></div></div>';
			});
		}
	});
};
requestParameters.table = defaultTable;
requestParameters.url = '/api/invoices';
