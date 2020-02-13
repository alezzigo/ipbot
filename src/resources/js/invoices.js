var processInvoice = function() {
	const invoiceId = elements.get('input[name="invoice_id"]').value;
	api.setRequestParameters({
		action: 'invoice',
		conditions: {
			id: invoiceId
		},
		table: 'invoices',
		url: apiRequestParameters.current.settings.baseUrl + 'api/invoices'
	});
	let invoiceData = '';
	api.sendRequest(function(response) {
		elements.html('main .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.data.invoice) {
			let amountDue = response.data.invoice.amountDue;
			let billingAmountField = elements.get('.billing-amount');
			let billingViewDetails = elements.get('.billing-view-details');
			let interval = '';
			let pendingChange = (typeof response.data.invoice.amountDuePending === 'number');

			if (pendingChange) {
				amountDue = response.data.invoice.amountDuePending;
				response.data.invoice.shipping = response.data.invoice.shippingPending;
				response.data.invoice.subtotal = response.data.invoice.subtotalPending;
				response.data.invoice.tax = response.data.invoice.taxPending;
				response.data.invoice.total = response.data.invoice.totalPending;
			}

			billingAmountField.value = amountDue.toLocaleString(false, {minimumFractionDigits: 2}).replace(',', '');
			elements.html('.invoice-name', '<label class="label ' + response.data.invoice.status + '">' + response.data.invoice.status + '</label> Invoice #' + response.data.invoice.id);
			elements.html('.billing-currency', response.data.invoice.currency);
			billingViewDetails.removeEventListener('click', billingViewDetails.clickListener);
			billingViewDetails.clickListener = function() {
				closeFrames();
			};
			billingViewDetails.addEventListener('click', billingViewDetails.clickListener);

			if (response.data.items.length) {
				response.data.orders = response.data.items;
			}

			invoiceData += '<h2>Invoice Payment Details</h2>';
			invoiceData += '<p><strong>Amount Paid to Invoice</strong><br><span' + (response.data.invoice.amountPaid ? ' class="paid"' : '') + '>' + response.data.invoice.amountPaid.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</span></p>';
			invoiceData += '<p><strong>Remaining Amount Due</strong><br>' + amountDue.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '</p>';

			if (
				response.data.invoice.due &&
				response.data.orders.length
			) {
				invoiceData += '<p><strong>Due Date</strong><br>' + response.data.invoice.due + '</p>';
			}

			if (response.data.orders.length) {
				interval = response.data.orders[0].intervalValue + ' ' + response.data.orders[0].intervalType + (response.data.orders[0].intervalValue !== 1 ? 's' : '');
				invoiceData += '<h2>Invoice Order' + (response.data.orders.length !== 1 ? 's' : '') + '</h2>';

				for (let orderKey in response.data.orders) {
					let order = response.data.orders[orderKey];
					const pendingOrderChange = (
						pendingChange &&
						order.quantityPending &&
						order.quantityPending !== order.quantity
					);

					if (pendingOrderChange) {
						var pendingChangeType = (order.quantityPending > order.quantity ? 'upgrade' : 'downgrade');
					}

					invoiceData += '<div class="item-container item-button">';
					invoiceData += '<div class="item">';
					invoiceData += '<p><strong>Order #' + order.id + '</strong></p>';
					invoiceData += '<p>' + order.quantity + ' ' + order.name + (pendingOrderChange ? ' to <span class="success">' + order.quantityPending + ' ' + order.name + '</span>' : '') + '</p>';
					invoiceData += '<p' + (!pendingOrderChange ? ' class="no-margin-bottom"' : '' ) + '>' + order.price.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + order.currency + ' for ' + interval + (pendingOrderChange ? ' to <span class="success">' + order.pricePending.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + order.currency + ' for ' + order.intervalValuePending + ' ' + order.intervalTypePending + (order.intervalValuePending !== 1 ? 's' : '') + '</span>' : '') + '</p>';

					if (pendingOrderChange) {
						invoiceData += '<label class="label">Pending Order ' + pendingChangeType + '</label><a class="cancel cancel-pending" href="javascript:void(0);" order_id="' + order.id + '">Cancel</a>';
					}

					invoiceData += '<div class="item-link-container"><a class="item-link" href="/orders/' + order.id + '"></a></div>';
					invoiceData += '</div>';
					invoiceData += '</div>';
				};
			} else {
				invoiceData += '<h2>Invoice Order</h2>';
				invoiceData += '<div class="item-container item-button"><p><strong>Add to Account Balance</strong></p><p class="no-margin-bottom">' + parseFloat(response.data.invoice.subtotal) + ' ' + response.data.invoice.currency + '</p>';
				invoiceData += '<div class="item-link-container"></div>';
				invoiceData += '</div>';
			}

			const hasBalance = (
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
				for (let transactionKey in response.data.transactions) {
					let transaction = response.data.transactions[transactionKey];

					if (transaction.paymentStatusMessage) {
						invoiceData += '<label class="label ' + (typeof transaction.paymentAmount === 'number' ? (Math.sign(transaction.paymentAmount) >= 0 ? 'payment' : 'refund') : '') + '">' + transaction.paymentStatusMessage + '</label>';
						invoiceData += '<div class="transaction">';
						invoiceData += '<p>';
						invoiceData += '<strong>' + transaction.transactionDate + '</strong><br>';
						invoiceData += (transaction.paymentAmount ? 'Amount: ' + transaction.paymentAmount.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + transaction.paymentCurrency + '<br>' : '');
						invoiceData += (transaction.paymentMethod ? 'Payment Method: ' + transaction.paymentMethod + '<br>' : '');
						invoiceData += (transaction.paymentTransactionId ? 'Transaction ID: ' + transaction.paymentTransactionId + '<br>' : '')
						invoiceData += (transaction.billingName ? '<strong>' + transaction.billingName + '</strong><br>' : '');
						invoiceData += (transaction.billingAddress1 ? ' ' + transaction.billingAddress1 + '<br>' : '');
						invoiceData += (transaction.billingAddress2 ? ' ' + transaction.billingAddress2 + '<br>' : '');
						invoiceData += (transaction.billingCity ? ' ' + transaction.billingCity : '');
						invoiceData += (transaction.billingRegion ? ' ' + transaction.billingRegion : '');
						invoiceData += (transaction.billingZip ? ' ' + transaction.billingZip : '');
						invoiceData += (transaction.billingCountryCode ? ' ' + transaction.billingCountryCode : '');

						if (transaction.details) {
							invoiceData += transaction.details;
						}

						invoiceData += '</p>';
						invoiceData += '</div>';
					}
				};
			}

			if (
				response.data.invoice.billing &&
				response.data.invoice.billing.address1 &&
				response.data.invoice.billing.company &&
				response.data.invoice.billing.countryCode &&
				response.data.invoice.billing.zip
			) {
				invoiceData += '<h2>Invoiced From</h2>';
				invoiceData += '<p><strong>' + response.data.invoice.billing.company + '</strong><br>' + response.data.invoice.billing.address1 + '<br>' + response.data.invoice.billing.address2 + '<br>' + response.data.invoice.billing.city + ', ' + response.data.invoice.billing.region + ' ' + response.data.invoice.billing.zip + ' ' + response.data.invoice.billing.countryCode + '</p>';
			}

			invoiceData	+= '</div>';
			elements.removeClass('.item-configuration .item-controls', 'hidden');
			selectAllElements('.payment-methods input', function(selectedElementKey, selectedElement) {
				selectedElement.removeEventListener('change', selectedElement.changeListener);
				selectedElement.changeListener = function() {
					let paymentMethod = selectedElement.getAttribute('id');
					elements.addClass('.payment-method', 'hidden');
					elements.removeClass('.payment-method.' + paymentMethod, 'hidden');

					if (
						hasBalance &&
						paymentMethod == 'balance'
					) {
						billingAmountField.value = Math.min(amountDue, response.user.balance).toLocaleString(false, {minimumFractionDigits: 2}).replace(',', '');
						elements.addClass('.recurring-checkbox-container', 'hidden');
						elements.addClass('.recurring-message', 'hidden');
					} else {
						billingAmountField.value = amountDue.toLocaleString(false, {minimumFractionDigits: 2}).replace(',', '');
						elements.removeClass('.recurring-checkbox-container', 'hidden');
						elements.removeClass('.recurring-message', 'hidden');
					}
				};
				selectedElement.addEventListener('change', selectedElement.changeListener);
			});
			const paymentMessage = function(element) {
				let intitialPaymentAmount = parseFloat(element.value ? element.value : element.target.value).toLocaleString(false, {minimumFractionDigits: 2});
				elements.html('.recurring-message', '<p class="message">This <span class="recurring-message-item">first </span>payment will be ' + intitialPaymentAmount + ' ' + response.data.invoice.currency + '<span class="recurring-message-item"> and the recurring payments will be ' + (intitialPaymentAmount >= amountDue ? (response.data.invoice.totalPending ? response.data.invoice.totalPending : response.data.invoice.total) : intitialPaymentAmount).toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + ' every ' + interval + '</span>.</p>');
			};
			billingAmountField.addEventListener('change', paymentMessage);
			billingAmountField.addEventListener('keyup', paymentMessage);
			paymentMessage(billingAmountField);
			processLoginVerification(response);

			if (
				hasBalance &&
				response.data.orders.length
			) {
				let balanceMessage = 'You have an available account balance of ' + response.user.balance.toLocaleString(false, {minimumFractionDigits: 2}) + ' ' + response.data.invoice.currency + '.';

				if (
					typeof response.user.testAccount !== 'undefined' &&
					response.user.testAccount
				) {
					balanceMessage += ' <strong>Account balance is for testing purposes only<strong>.';
				}

				elements.removeClass('.payment-methods label[for="balance"]', 'hidden');
				elements.html('.payment-method.balance .message ', balanceMessage);
			}

			if (!response.data.orders.length) {
				elements.addClass('.recurring-checkbox-container', 'hidden');
			}

			if (!amountDue) {
				elements.addClass('.button[frame="payment"]', 'hidden');
			}
		}

		elements.html('.invoice-container', invoiceData);
		const cancelPendingButton = elements.get('.item-button .cancel-pending');
		const cancelPending = function(orderId) {
			api.setRequestParameters({
				action: 'cancel',
				data: {
					orderId: orderId
				}
			}, true);
			api.sendRequest(function(response) {
				if (
					typeof response.redirect === 'string' &&
					response.redirect
				) {
					window.location.href = response.redirect;
					return false;
				}
			});
		};

		if (cancelPendingButton) {
			cancelPendingButton.clickListener = function() {
				cancelPendingButton.removeEventListener('click', cancelPendingButton.clickListener);
				cancelPending(cancelPendingButton.getAttribute('order_id'));
			};
			cancelPendingButton.addEventListener('click', cancelPendingButton.clickListener);
		}

		elements.addScrollable('.item-controls-container.scrollable', function(element) {
			if (element.details.width) {
				element.parentNode.querySelector('.item-body').setAttribute('style', 'padding-top: ' + (element.parentNode.querySelector('.item-header').clientHeight + 2) + 'px');
				element.setAttribute('style', 'width: ' + element.details.width + 'px;');
			}
		});
	});
};
var processInvoices = function() {
	api.setRequestParameters({
		action: 'fetch',
		conditions: {
			mergedInvoiceId: null,
			payable: true
		},
		sort: {
			field: 'created',
			order: 'DESC'
		},
		url: apiRequestParameters.current.settings.baseUrl + 'api/invoices'
	});
	let invoiceData = '';
	api.sendRequest(function(response) {
		if (response.user === false) {
			elements.addClass('nav .user', 'hidden');
			elements.removeClass('nav .guest', 'hidden');
			response.message = {
				status: 'error',
				text: 'You\'re currently not logged in, please <a href="' + apiRequestParameters.current.settings.baseUrl + '?#login">log in</a> or <a href="' + apiRequestParameters.current.settings.baseUrl + '?#register">register an account</a>.'
			};
		}

		elements.html('main .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.data.length) {
			for (let itemKey in response.data) {
				let item = response.data[itemKey];
				invoiceData += '<div class="item-container item-button">';
				invoiceData += '<div class="item">';
				invoiceData += '<div class="item-body">';
				invoiceData += '<p><strong>Invoice #' + item.id + '</strong></p>';
				invoiceData += '<label class="label ' + item.status + '">' + item.status + '</label>' + (item.remainderPending && item.quantityPending !== item.quantity ? '<label class="label">Pending Order Change</label>' : '');
				invoiceData += '</div>';
				invoiceData += '</div>';
				invoiceData += '<div class="item-link-container"><a class="item-link" href="/invoices/' + item.id + '"></a></div>';
				invoiceData += '</div>';
			};
			elements.html('.invoices-container', invoiceData);
		}
	});
};
var processLoginVerification = function(response) {
	if (
		response.user !== false &&
		response.user.email
	) {
		elements.html('.account-details', '<p class="message">You\'re currently logged in as ' + response.user.email + '.</p>');
	}
};
var processPayment = function(frameName, frameSelector) {
	const invoiceId = elements.get('input[name="invoice_id"]').value;
	delete apiRequestParameters.current.conditions;
	api.setRequestParameters({
		action: 'payment',
		data: {
			invoiceId: invoiceId
		},
		table: 'transactions',
		url: apiRequestParameters.current.settings.baseUrl + 'api/transactions'
	}, true);
	api.sendRequest(function(response) {
		elements.html(frameSelector + ' .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message payment-message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));
		processInvoice();
		processLoginVerification(response);
		window.scroll(0, 0);

		if (
			response.message &&
			response.message.status === 'success'
		) {
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
api.setRequestParameters({
	defaults: {
		action: 'fetch',
		table: 'invoices'
	},
	table: 'invoices'
});
