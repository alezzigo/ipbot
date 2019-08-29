'use_strict';

var defaultTable = 'invoices';
var previousAction = 'find';
var processInvoice = function() {
	requestParameters.action = 'invoice';
	var invoiceContainer = document.querySelector('.invoice-container');
	var invoiceId = document.querySelector('input[name="invoice_id"]').value;
	var invoiceData = '';
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

			if (response.data.orders.length) {
				invoiceData += '<h2>Invoice Orders</h2>';
				response.data.orders.map(function(order) {
					invoiceData += '<div class="item-container item-button"><p class="no-margin-bottom"><label>' + order.quantity + ' ' + order.name + '</label></p><p>$' + order.price + ' USD for ' + order.interval_value + ' ' + order.interval_type + '</p><div class="item-link-container"><a class="item-link" href="/orders/' + order.id + '"></a></div></div>';
				});
			}

			invoiceData += '<h2>Invoice Transactions</h2>';
			invoiceData	+= '<div class="invoice-section-container transactions"><label class="label">Invoice Created</label><div class="transaction"><p><strong>' + response.data.invoice.created + '</strong></p></div>';

			if (response.data.transactions.length) {
				response.data.transactions.map(function(transaction) {
					invoiceData += '<label class="label ' + transaction.transaction_type + '">' + capitalizeString(transaction.transaction_type) + '</label><div class="transaction"><p><strong>' + transaction.transaction_date + '</strong><br>' + transaction.payment_amount + ' ' + transaction.payment_currency + '<br>Transaction ID ' + transaction.id + '</p><p><strong>' + transaction.billing_name + '</strong><br>' + transaction.billing_address_1 + ' ' + transaction.billing_address_2 + '<br>' + transaction.billing_city + ' ' + transaction.billing_region + ' ' + transaction.billing_zip + ' ' + transaction.billing_country_code + '</p></div>';
				});
			}

			invoiceData	+= '</div>';
			elements.removeClass('.item-configuration .item-controls', 'hidden');
		}

		invoiceContainer.innerHTML = invoiceData;
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
