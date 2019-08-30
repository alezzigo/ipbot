'use_strict';

var defaultTable = 'invoices';
var previousAction = 'find';
var processInvoice = function() {
	requestParameters.action = 'invoice';
	var invoiceContainer = document.querySelector('.invoice-container');
	var invoiceId = document.querySelector('input[name="invoice_id"]').value;
	var invoiceData = '';
	var invoiceSubtotal = invoiceTotal = 0;
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
					invoiceSubtotal += parseFloat(order.price);
				});
			}

			var invoiceShipping = parseFloat(response.data.invoice.handling) + parseFloat(response.data.invoice.shipping);
			var invoiceTax = parseFloat(response.data.invoice.tax);
			invoiceData += '<h2>Invoice Pricing Details</h2>';
			invoiceData += '<p><strong>Subtotal</strong><br>$' + invoiceSubtotal + ' USD</p>';
			invoiceData += '<p><strong>Shipping + Handling</strong><br>$' + invoiceShipping + ' USD</p>';
			invoiceData += '<p><strong>Tax</strong><br>$' + invoiceTax + ' USD</p>';
			invoiceTotal = invoiceSubtotal + invoiceShipping + invoiceTax;
			invoiceData += '<p><strong>Total</strong><br>$' + invoiceTotal + ' USD</p>';

			if (response.data.invoice.status === 'unpaid') {
				invoiceData += '<p class="message">Additional fees for shipping, handling and/or tax may apply before submitting final payment.</p>';
			}

			invoiceData += '<h2>Invoice Payment Details</h2>';
			invoiceData += '<p><strong>Amount Applied to Account Balance</strong><br>' + response.data.invoice.amount_applied_to_balance + ' ' + response.data.invoice.payment_currency + '</p>';
			invoiceData += '<h2>Invoice Transactions</h2>';
			invoiceData += '<div class="invoice-section-container transactions"><label class="label">Invoice Created</label><div class="transaction"><p><strong>' + response.data.invoice.created + '</strong></p></div>';

			if (response.data.transactions.length) {
				response.data.transactions.map(function(transaction) {
					invoiceData += '<label class="label ' + transaction.transaction_type + '">' + capitalizeString(transaction.transaction_type) + '</label><div class="transaction"><p><strong>' + transaction.transaction_date + '</strong><br>' + transaction.payment_amount + ' ' + transaction.payment_currency + '<br>Transaction ID ' + transaction.id + '</p><p><strong>' + transaction.billing_name + '</strong><br>' + transaction.billing_address_1 + ' ' + transaction.billing_address_2 + '<br>' + transaction.billing_city + ' ' + transaction.billing_region + ' ' + transaction.billing_zip + ' ' + transaction.billing_country_code + '</p></div>';
				});
			}

			if (response.data.invoice.billing) {
				invoiceData += '<h2>Invoiced From</h2>';
				invoiceData += '<p><strong>' + response.data.invoice.billing.company + '</strong><br>' + response.data.invoice.billing.address_1 + '<br>' + response.data.invoice.billing.address_2 + '<br>' + response.data.invoice.billing.city + ', ' + response.data.invoice.billing.region + ' ' + response.data.invoice.billing.zip + ' ' + response.data.invoice.billing.country_code + '</p>';
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
