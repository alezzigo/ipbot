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
	requestParameters.conditions = {
		id: invoiceId
	};
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.data.invoice) {
			var amountDue = response.data.invoice.amount_due;
			var billingAmountField = document.querySelector('.billing-amount');
			var interval = '';
			var pendingChange = (typeof response.data.invoice.amount_due_pending === 'number');

			if (pendingChange) {
				amountDue = response.data.invoice.amount_due_pending;
				response.data.invoice.shipping = response.data.invoice.shipping_pending;
				response.data.invoice.subtotal = response.data.invoice.subtotal_pending;
				response.data.invoice.tax = response.data.invoice.tax_pending;
				response.data.invoice.total = response.data.invoice.total_pending;
			}

			billingAmountField.value = amountDue.toLocaleString(false, {minimumFractionDigits: 2});
			document.querySelector('.invoice-name').innerHTML = '<label class="label ' + response.data.invoice.status + '">' + capitalizeString(response.data.invoice.status) + '</label> Invoice #' + response.data.invoice.id;
			document.querySelector('.billing-currency').innerHTML = response.data.invoice.currency;
			document.querySelector('.billing-view-details').addEventListener('click', function(element) {
				closeWindows(defaultTable);
			});

			if (response.data.items.length) {
				response.data.orders = response.data.items;
			}

			invoiceData += '<h2>Invoice Payment Details</h2>';
			invoiceData += '<p><strong>Amount Paid to Invoice</strong><br><span' + (response.data.invoice.amount_paid ? ' class="paid"' : '') + '>' + response.data.invoice.amount_paid.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</span></p>';
			invoiceData += '<p><strong>Remaining Amount Due</strong><br>' + amountDue.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</p>';

			if (
				response.data.invoice.due &&
				response.data.orders.length
			) {
				invoiceData += '<p><strong>Due Date</strong><br>' + response.data.invoice.due + '</p>';
			}

			if (response.data.orders.length) {
				interval = response.data.orders[0].interval_value + ' ' + response.data.orders[0].interval_type + (response.data.orders[0].interval_value !== 1 ? 's' : '');
				invoiceData += '<h2>Invoice Order' + (response.data.orders.length !== 1 ? 's' : '') + '</h2>';
				response.data.orders.map(function(order) {
					var pendingOrderChange = (
						pendingChange &&
						order.quantity_pending &&
						order.quantity_pending !== order.quantity
					);
					invoiceData += '<div class="item-container item-button">';
					invoiceData += '<p><strong>Order #' + order.id + '</strong></p>';

					if (pendingOrderChange) {
						var pendingChangeType = (order.quantity_pending > order.quantity ? 'upgrade' : 'downgrade');
					}

					invoiceData += '<p>' + order.quantity + ' ' + order.name + (pendingOrderChange ? ' to <span class="success">' + order.quantity_pending + ' ' + order.name + '</span>' : '') + '</p>';
					invoiceData += '<p class="no-margin-bottom">' + order.price.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + order.currency + ' for ' + interval + (pendingOrderChange ? ' to <span class="success">' + order.price_pending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + order.currency + ' for ' + order.interval_value_pending + ' ' + order.interval_type_pending + (order.interval_value_pending !== 1 ? 's' : '') + '</span>' : '') + '</p>';

					if (pendingOrderChange) {
						invoiceData += '<label class="label">Pending Order ' + capitalizeString(pendingChangeType) + '</label><a class="cancel-pending" href="javascript:void(0);" order_id="' + order.id + '">Cancel</a>';
					}

					invoiceData += '<div class="item-link-container"><a class="item-link" href="/orders/' + order.id + '"></a></div>';
					invoiceData += '</div>';
				});
			} else {
				invoiceData += '<h2>Invoice Order</h2>';
				invoiceData += '<div class="item-container item-button"><p><strong>Add to Account Balance</strong></p><p class="no-margin-bottom">' + parseFloat(response.data.invoice.subtotal) + ' ' + response.data.invoice.currency + '</p>';
				invoiceData += '<div class="item-link-container"></div>';
				invoiceData += '</div>';
			}

			var hasBalance = (
				response.user !== false &&
				response.user.balance > 0
			);
			invoiceData += '<h2>Invoice Pricing Details</h2>';
			invoiceData += '<p><strong>Subtotal</strong><br>' + parseFloat(response.data.invoice.subtotal).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</p>';
			invoiceData += '<p><strong>Shipping</strong><br>' + parseFloat(response.data.invoice.shipping).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</p>';
			invoiceData += '<p><strong>Tax</strong><br>' + parseFloat(response.data.invoice.tax).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</p>';
			invoiceData += '<p><strong>Total</strong><br>' + parseFloat(response.data.invoice.total).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</p>';

			if (response.data.invoice.status === 'unpaid') {
				invoiceData += '<p class="message">Additional fees for shipping and/or tax may apply before submitting final payment.</p>';
			}

			invoiceData += '<h2>Invoice Transactions</h2>';
			invoiceData += '<div class="invoice-section-container transactions"><label class="label">Invoice created.</label><div class="transaction"><p><strong>' + response.data.invoice.created + '</strong></p></div>';

			if (response.data.transactions.length) {
				response.data.transactions.map(function(transaction) {
					invoiceData += (transaction.payment_status_message ? '<label class="label ' + (typeof transaction.payment_amount === 'number' ? (Math.sign(transaction.payment_amount) >= 0 ? 'payment' : 'refund') : '') + '">' + capitalizeString(transaction.payment_status_message) + '</label>' : '');
					invoiceData += '<div class="transaction">';
					invoiceData += '<p>';
					invoiceData += '<strong>' + transaction.transaction_date + '</strong><br>';
					invoiceData += (transaction.payment_amount ? 'Amount: ' + transaction.payment_amount.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + transaction.payment_currency + '<br>' : '');
					invoiceData += (transaction.payment_method ? 'Payment Method: ' + transaction.payment_method + '<br>' : '');
					invoiceData += (transaction.payment_transaction_id ? 'Transaction ID: ' + transaction.payment_transaction_id + '<br>' : '')
					invoiceData += (transaction.billing_name ? '<strong>' + transaction.billing_name + '</strong><br>' : '');
					invoiceData += (transaction.billing_address_1 ? ' ' + transaction.billing_address_1 + '<br>' : '');
					invoiceData += (transaction.billing_address_2 ? ' ' + transaction.billing_address_2 + '<br>' : '');
					invoiceData += (transaction.billing_city ? ' ' + transaction.billing_city : '');
					invoiceData += (transaction.billing_region ? ' ' + transaction.billing_region : '');
					invoiceData += (transaction.billing_zip ? ' ' + transaction.billing_zip : '');
					invoiceData += (transaction.billing_country_code ? ' ' + transaction.billing_country_code : '');

					if (transaction.details) {
						invoiceData += transaction.details;
					}

					invoiceData += '</p>';
					invoiceData += '</div>';
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
						billingAmountField.value = Math.min(amountDue, response.user.balance).toLocaleString(false, {minimumFractionDigits: 2});
						elements.addClass('.recurring-checkbox-container', 'hidden');
						elements.addClass('.recurring-message', 'hidden');
					} else {
						billingAmountField.value = amountDue.toLocaleString(false, {minimumFractionDigits: 2});
						elements.removeClass('.recurring-checkbox-container', 'hidden');
						elements.removeClass('.recurring-message', 'hidden');
					}
				});
			});
			var paymentMessage = function(element) {
				var intitialPaymentAmount = parseFloat(element.value ? element.value : element.target.value).toLocaleString(false, {minimumFractionDigits: 2});
				document.querySelector('.recurring-message').innerHTML = '<p class="message">This <span class="recurring-message-item">first </span>payment will be ' + intitialPaymentAmount + ' ' + response.data.invoice.currency + '<span class="recurring-message-item"> and the recurring payments will be ' + (intitialPaymentAmount >= amountDue ? (response.data.invoice.total_pending ? response.data.invoice.total_pending : response.data.invoice.total) : intitialPaymentAmount).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + ' every ' + interval + '</span>.</p>';
			};
			billingAmountField.addEventListener('change', paymentMessage);
			billingAmountField.addEventListener('keyup', paymentMessage);
			paymentMessage(billingAmountField);
			processLoginVerification(response);

			if (
				hasBalance &&
				response.data.orders.length
			) {
				elements.removeClass('.payment-methods label[for="balance"]', 'hidden');
				elements.html('.payment-method.balance .message ', 'You have an available account balance of ' + response.user.balance.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency);
			}

			if (!response.data.orders.length) {
				elements.addClass('.recurring-checkbox-container', 'hidden');
			}

			if (!amountDue) {
				elements.addClass('.button[window="payment"]', 'hidden');
			}
		}

		invoiceContainer.innerHTML = invoiceData;
		var cancelPendingButton = document.querySelector('.item-button .cancel-pending');
		var cancelPending = function(orderId) {
			requestParameters.action = 'cancel';
			requestParameters.data.order_id = orderId;
			sendRequest(function(response) {
				// ..
			});
		};

		if (cancelPendingButton) {
			cancelPendingButton.removeEventListener('click', cancelPendingButton.clickListener);
			cancelPendingButton.clickListener = function() {
				cancelPending(cancelPendingButton.getAttribute('order_id'));
			};
			cancelPendingButton.addEventListener('click', cancelPendingButton.clickListener);
		}
	});
};
var processInvoices = function() {
	requestParameters.action = 'find';
	requestParameters.conditions = {
		merged_invoice_id: null,
		payable: true
	};
	requestParameters.sort = {
		field: 'created',
		order: 'DESC'
	};
	requestParameters.table = 'invoices';
	requestParameters.url = '/api/invoices';
	var invoiceData = '';
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			if (response.user === false) {
				elements.addClass('nav .user', 'hidden');
				elements.removeClass('nav .guest', 'hidden');
				response.message = {
					status: 'error',
					text: 'You\'re currently not logged in, please <a href="' + requestParameters.settings.base_url + '?#login">log in</a> or <a href="' + requestParameters.settings.base_url + '?#register">register an account</a>.'
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
				invoiceData += '<div class="item-container item-button">';
				invoiceData += '<div class="item">';
				invoiceData += '<div class="item-body">';
				invoiceData += '<p><strong>Invoice #' + item.id + '</strong></p>';
				invoiceData += '<label class="label ' + item.status + '">' + capitalizeString(item.status) + '</label>' + (item.remainder_pending && item.quantity_pending !== item.quantity ? '<label class="label">Pending Order Change</label>' : '');
				invoiceData += '</div>';
				invoiceData += '</div>';
				invoiceData += '<div class="item-link-container"><a class="item-link" href="/invoices/' + item.id + '"></a></div>';
				invoiceData += '</div>';
			});
			document.querySelector('.invoices-container').innerHTML = invoiceData;
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
	requestParameters.action = 'payment';
	requestParameters.data.invoice_id = document.querySelector('input[name="invoice_id"]').value;
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
