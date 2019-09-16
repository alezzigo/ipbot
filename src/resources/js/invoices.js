'use_strict';

var defaultTable = 'invoices';
var previousAction = 'find';
var processInvoice = function() {
	requestParameters.action = 'invoice';
	requestParameters.table = 'invoices';
	requestParameters.url = '/api/invoices';
	var invoiceContainer = document.querySelector('.invoice-container');
	var invoiceData = '';
	var invoiceId = document.querySelector('input[name="invoice_id"]').value;
	var invoiceSubtotal = invoiceTotal = 0;
	requestParameters.conditions = {
		id: invoiceId
	};
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (response.data.invoice) {
			document.querySelector('.invoice-name').innerHTML = '<label class="label ' + response.data.invoice.status + '">' + capitalizeString(response.data.invoice.status) + '</label> Invoice #' + response.data.invoice.id;
			document.querySelector('.billing-amount').value = response.data.invoice.amount_due;
			document.querySelector('.billing-currency-name').innerHTML = response.data.invoice.payment_currency_name;
			document.querySelector('.billing-currency-symbol').innerHTML = response.data.invoice.payment_currency_symbol;
			document.querySelector('.billing-view-details').addEventListener('click', function(element) {
				closeWindows(defaultTable);
			});

			if (response.data.orders.length) {
				invoiceData += '<h2>Invoice Orders</h2>';
				response.data.orders.map(function(order) {
					invoiceData += '<div class="item-container item-button"><p class="no-margin-bottom"><label>' + order.quantity + ' ' + order.name + '</label></p><p>$' + order.price + ' USD for ' + order.interval_value + ' ' + order.interval_type + (order.interval_value !== 1 ? 's' : '') + '</p><div class="item-link-container"><a class="item-link" href="/orders/' + order.id + '"></a></div></div>';
					invoiceSubtotal += parseFloat(order.price);
				});
			}

			var hasBalance = (
				response.user !== false &&
				response.user.balance > 0
			);
			var invoiceShipping = parseFloat(response.data.invoice.shipping);
			var invoiceTax = parseFloat(response.data.invoice.tax);
			invoiceData += '<h2>Invoice Pricing Details</h2>';
			invoiceSubtotal = (Math.round(invoiceSubtotal * 100) / 100);
			invoiceTotal = (Math.round((invoiceSubtotal + invoiceShipping + invoiceTax) * 100) / 100);
			invoiceData += '<p><strong>Subtotal</strong><br>' + response.data.invoice.payment_currency_symbol + invoiceSubtotal + ' ' + response.data.invoice.payment_currency_name + '</p>';
			invoiceData += '<p><strong>Shipping</strong><br>' + response.data.invoice.payment_currency_symbol + invoiceShipping + ' ' + response.data.invoice.payment_currency_name + '</p>';
			invoiceData += '<p><strong>Tax</strong><br>' + response.data.invoice.payment_currency_symbol + invoiceTax + ' ' + response.data.invoice.payment_currency_name + '</p>';
			invoiceData += '<p><strong>Total</strong><br>' + response.data.invoice.payment_currency_symbol + invoiceTotal + ' ' + response.data.invoice.payment_currency_name + '</p>';

			if (response.data.invoice.status === 'unpaid') {
				invoiceData += '<p class="message">Additional fees for shipping and/or tax may apply before submitting final payment.</p>';
			}

			invoiceData += '<h2>Invoice Payment Details</h2>';

			if (response.data.invoice.amount_paid) {
				invoiceData += '<p><strong>Amount Paid to Invoice</strong><br><span class="paid">' + response.data.invoice.payment_currency_symbol + response.data.invoice.amount_paid + ' ' + response.data.invoice.payment_currency_name + '</span></p>';
			}

			invoiceData += '<p><strong>Remaining Amount Due</strong><br>' + response.data.invoice.payment_currency_symbol + response.data.invoice.amount_due + ' ' + response.data.invoice.payment_currency_name + '</p>';
			invoiceData += '<h2>Invoice Transactions</h2>';
			invoiceData += '<div class="invoice-section-container transactions"><label class="label">Invoice Created</label><div class="transaction"><p><strong>' + response.data.invoice.created + '</strong></p></div>';

			if (response.data.transactions.length) {
				response.data.transactions.map(function(transaction) {
					invoiceData += (transaction.payment_status_message ? '<label class="label ' + (Math.sign(transaction.payment_amount) > 0 ? 'payment' : 'refund') + '">' + capitalizeString(transaction.payment_status_message) + '</label>' : '') + '<div class="transaction"><p><strong>' + transaction.transaction_date + '</strong><br>' + transaction.payment_amount + ' ' + transaction.payment_currency + '<br>' + (transaction.payment_method ? transaction.payment_method + ' ' : '') + 'Transaction ID ' + transaction.id + '</p>' + (transaction.billing_name ? '<p>' + (transaction.billing_name ? '<strong>' + transaction.billing_name + '</strong><br>' : '') + (transaction.billing_address_1 ? ' ' + transaction.billing_address_1 : '') + (transaction.billing_address_2 ? ' ' + transaction.billing_address_2 : '') + '<br>' + (transaction.billing_city ? ' ' + transaction.billing_city : '') + (transaction.billing_region ? ' ' + transaction.billing_region : '') + (transaction.billing_zip ? ' ' + transaction.billing_zip : '') + (transaction.billing_country_code ? ' ' + transaction.billing_country_code : '') + '</p>' : '') + '</div>';
				});
			}

			if (response.data.invoice.billing) {
				invoiceData += '<h2>Invoiced From</h2>';
				invoiceData += '<p><strong>' + response.data.invoice.billing.company + '</strong><br>' + response.data.invoice.billing.address_1 + '<br>' + response.data.invoice.billing.address_2 + '<br>' + response.data.invoice.billing.city + ', ' + response.data.invoice.billing.region + ' ' + response.data.invoice.billing.zip + ' ' + response.data.invoice.billing.country_code + '</p>';
			}

			invoiceData	+= '</div>';
			elements.removeClass('.item-configuration .item-controls', 'hidden');
			selectAllElements('.payment-methods input').map(function(element) {
				element[1].addEventListener('change', function(element) {
					var paymentMethod = element.target.getAttribute('id');
					elements.addClass('.payment-method', 'hidden');
					elements.removeClass('.payment-method.' + paymentMethod, 'hidden');

					if (
						hasBalance &&
						paymentMethod == 'balance'
					) {
						document.querySelector('.billing-amount').value = Math.min(response.data.invoice.amount_due, response.user.balance);
						elements.addClass('.recurring-checkbox-container', 'hidden');
					} else {
						document.querySelector('.billing-amount').value = response.data.invoice.amount_due;
						elements.removeClass('.recurring-checkbox-container', 'hidden');
					}
				});
			});
			processLoginVerification(response);

			if (hasBalance) {
				elements.removeClass('.payment-methods label[for="balance"]', 'hidden');
				elements.html('.payment-method.balance .message ', 'You have an available account balance of ' + response.data.invoice.payment_currency_symbol + response.user.balance + ' ' + response.data.invoice.payment_currency_name);
			}
		}

		invoiceContainer.innerHTML = invoiceData;
	});
};
var processInvoices = function() {
	requestParameters.action = 'find';
	requestParameters.sort = {
		field: 'created',
		order: 'DESC'
	};
	requestParameters.table = 'invoices';
	requestParameters.url = '/api/invoices';
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			if (response.user === false) {
				elements.addClass('nav .user', 'hidden');
				elements.removeClass('nav .guest', 'hidden');
				response.message = {
					status: 'error',
					text: 'You\'re currently not logged in, please <a href="' + requestParameters.base_url + '?#login">log in</a> or <a href="' + requestParameters.base_url + '?#register">register an account</a>.'
				};
			}

			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
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
var processLoginVerification = function(response) {
	if (
		response.user !== false &&
		response.user.email
	) {
		document.querySelector('.account-details').innerHTML = '<p class="message">You\'re currently logged in as ' + response.user.email + '.</p>';
	}
};
var processPayment = function(windowName, windowSelector) {
	var invoiceId = document.querySelector('input[name="invoice_id"]').value;
	requestParameters.action = 'payment';
	requestParameters.data.invoice_id = invoiceId;
	requestParameters.table = 'transactions';
	requestParameters.url = '/api/transactions';
	delete requestParameters.conditions;
	sendRequest(function(response) {
		var messageContainer = document.querySelector(windowSelector + ' .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		processInvoice();
		processLoginVerification(response);
		window.scroll(0, 0);

		if (response.message.status === 'success') {
			if (
				typeof response.redirect === 'string' &&
				response.redirect
			) {
				window.location.href = response.redirect;
				return false;
			}
		}
	});
};
