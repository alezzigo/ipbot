var processEmail = function() {
	processUser();
	let hash = window.location.search.substr(1);

	if (hash) {
		api.setRequestParameters({
			data: {
				token: hash
			}
		}, true);
	}

	if (
		apiRequestParameters.current.data['email'] ||
		apiRequestParameters.current.data['token']
	) {
		api.setRequestParameters({
			action: 'email',
			url: apiRequestParameters.current.settings.baseUrl + 'api/users'
		});
		api.sendRequest(function(response) {
			if (elements.get('.change-email .message-container')) {
				if (
					!hash &&
					response.user === false
				) {
					elements.addClass('nav .user', 'hidden');
					elements.addClass('.change-email form-item', 'hidden');
					elements.removeClass('nav .guest', 'hidden');
					elements.setAttribute('.change-email input.email', 'disabled', 'disabled');
					response.message = {
						status: 'error',
						text: 'You\'re currently not logged in, please <a href="' + apiRequestParameters.current.settings.baseUrl + '?#login">log in</a> or <a href="' + apiRequestParameters.current.settings.baseUrl + '?#register">register an account</a>.'
					};
				}

				if (
					response.message.status === 'success' &&
					typeof response.data !== 'undefined'
				) {
					elements.get('.change-email input.email').value = response.data.newEmail;
					elements.setAttribute('.change-email input.email', 'disabled', 'disabled');
					processUser();
					elements.addClass('.change-email form-item', 'hidden');
				}

				elements.html('.change-email .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));
			}
		});
	}
};
var processRemove = function() {
	api.setRequestParameters({
		action: 'remove',
		url: apiRequestParameters.current.settings.baseUrl + 'api/users'
	});
	api.sendRequest(function(response) {
		if (elements.get('.request-removal .message-container')) {
			elements.html('.request-removal .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));
		}

		processUser();

		if (response.message.status === 'success') {
			elements.addClass('.request-removal .form-item', 'hidden');
		}
	});
};
var processReset = function() {
	let hash = window.location.search.substr(1);
	api.setRequestParameters({
		action: 'reset',
		data: {
			token: hash
		},
		url: apiRequestParameters.current.settings.baseUrl + 'api/users'
	}, true);
	api.sendRequest(function(response) {
		if (elements.get('.reset .message-container')) {
			elements.html('.reset .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));
		}

		if (typeof response.user !== 'undefined') {
			if (response.user.email) {
				elements.get('.reset .email').value = response.user.email;
				elements.removeClass('.reset .submit', 'hidden');
			}

			if (
				response.message.status === 'success' &&
				apiRequestParameters.current.data.email
			) {
				elements.addClass('.reset .submit', 'hidden');
			}
		}

		processUser();
	});
};
var processUser = function() {
	let userData = '';
	elements.removeClass('.form-item', 'hidden');
	api.setRequestParameters({
		action: 'view',
		url: apiRequestParameters.current.settings.baseUrl + 'api/users'
	});
	api.sendRequest(function(response) {
		if (elements.get('main .message-container')) {
			if (response.user === false) {
				elements.addClass('nav .user', 'hidden');
				elements.removeClass('nav .guest', 'hidden');
				response.message = {
					status: 'error',
					text: 'You\'re currently not logged in, please <a href="' + apiRequestParameters.current.settings.baseUrl + '?#login">log in</a> or <a href="' + apiRequestParameters.current.settings.baseUrl + '?#register">register an account</a>.'
				};
			}

			elements.html('main .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));
		}

		if (elements.get('.user-container')) {
			if (response.user !== false) {
				userData += '<h2>Account Details</h2>';
				userData += '<p><strong>User ID</strong><br>' + response.user.id + '</p>';
				userData += '<p><strong>Email Address</strong><br>' + response.user.email + '<br><a class="email" href="' + apiRequestParameters.current.settings.baseUrl + 'account/?#email">Change email address</a></p>';
				userData += '<p><strong>Password</strong><br>********<br>Last changed: ' + response.user.passwordModified + '<br><a class="password" href="' + apiRequestParameters.current.settings.baseUrl + 'account/?#reset">Change password</a></p>';
				userData += '<h2>Account Balance</h2>';

				if (
					typeof response.user.testAccount !== 'undefined' &&
					response.user.testAccount
				) {
					userData += '<p class="message error">Invoices, payments and account balance are for testing purposes only.</p>';
				}

				userData += '<p><strong>Current Balance</strong><br>' + response.user.balance + ' ' + apiRequestParameters.current.settings.billingCurrency + '</p>';
				userData += '<div class="balance-message-container"></div>';
				userData += '<p class="no-margin-bottom"><strong>Add to Account Balance</strong></p>';
				userData += '<div class="clear"></div>';
				userData += '<div class="align-left item-container no-margin-bottom"><div class="field-group no-margin"><input class="balance-amount billing-amount" id="balance-amount" max="10000" min="20" name="balance_amount" step="0.01" type="number" value="100.00"><span class="balance-currency-name">' + apiRequestParameters.current.settings.billingCurrency + '</span><a class="add add-to-balance button" disabled href="javascript:void(0);">Add</a></div></div>';
				userData += '<div class="clear"></div>';

				if (response.user.subscriptions) {
					userData += '<h2>Account Subscriptions</h2>';

					for (let subscriptionDataKey in response.user.subscriptions) {
						let subscription = response.user.subscriptions[subscriptionDataKey];
						userData += '<div class="item-container item-button" subscription_id="' + subscription.id + '">';
						userData += '<div class="item">';
						userData += '<div class="item-body">';
						userData += '<p><strong>Subscription #' + subscription.id + '</strong></p>';
						userData += '<p>' + subscription.price + ' ' + apiRequestParameters.current.settings.billingCurrency + ' per ' + subscription.intervalValue + ' ' + subscription.intervalType + (subscription.intervalValue !== 1 ? 's' : '') + '</p>';
						userData += '<span class="label-container">';
						userData += '<label class="label ' + subscription.status + '">' + subscription.status.replace('_', ' ') + '</label>';
						userData += '</span>';
						userData += (subscription.status.indexOf('cancel') < 0 ? '<a class="cancel cancel-subscription" href="javascript:void(0);" subscription_id="' + subscription.id + '">Request Cancellation</a>' : '');
						userData += '<div class="hidden message-container no-margin-bottom"></div>';
						userData += '</div>';
						userData += '</div>';
						userData += '</div>';
					}
				}

				userData += '<h2>Remove Account</h2>';

				if (response.user.removed) {
					userData += '<p class="error message">Your account will be removed shortly as requested.</p>';
				} else {
					userData += '<a class="remove" href="' + apiRequestParameters.current.settings.baseUrl + 'account/?#request-removal">Request account removal</a>';
				}
			}

			elements.html('.user-container', userData);

			if (response.user.subscriptions) {
				for (let subscriptionDataKey in response.user.subscriptions) {
					let subscription = response.user.subscriptions[subscriptionDataKey];
					let cancelSubscriptionButton = elements.get('.item-button[subscription_id="' + subscription.id + '"] .cancel-subscription');
					let cancelSubscription = function(subscriptionId) {
						api.setRequestParameters({
							action: 'cancel',
							data: {
								subscriptionId: subscriptionId
							}
						}, true);
						api.sendRequest(function(response) {
							if (typeof response.message.text !== 'undefined') {
								const subscriptionContainerSelector = '.item-button[subscription_id="' + subscriptionId + '"]';
								elements.addClass('.item-button[subscription_id] .message-container', 'hidden');
								elements.html('.item-button[subscription_id] .message-container', '');
								elements.html(subscriptionContainerSelector + ' .message-container', '<p class="message ' + response.message.status + ' no-margin-bottom">' + response.message.text + '</p>');
								elements.removeClass(subscriptionContainerSelector + ' .message-container', 'hidden');

								if (response.message.status === 'success') {
									elements.addClass(subscriptionContainerSelector + ' .cancel-subscription', 'hidden');
									elements.html(subscriptionContainerSelector + ' .label-container', '<label class="label">Pending Cancellation</label>');
								}
							}
						});
					};

					if (cancelSubscriptionButton) {
						cancelSubscriptionButton.clickListener = function() {
							cancelSubscriptionButton.removeEventListener('click', cancelSubscriptionButton.clickListener);
							cancelSubscription(cancelSubscriptionButton.getAttribute('subscription_id'));
						};
						cancelSubscriptionButton.addEventListener('click', cancelSubscriptionButton.clickListener);
					}
				}
			}

			let userAddBalanceButton = elements.get('.user-container .button.add-to-balance');

			if (userAddBalanceButton) {
				userAddBalanceButton.removeEventListener('click', userAddBalanceButton.clickListener);
				userAddBalanceButton.clickListener = function() {
					api.setRequestParameters({
						action: 'balance',
						data: {
							balance: elements.get('.user-container .balance-amount').value
						}
					}, true);
					api.sendRequest(function(response) {
						elements.html('.balance-message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));

						if (
							typeof response.redirect === 'string' &&
							response.redirect
						) {
							window.location.href = response.redirect;
							return false;
						}
					});
				};
				elements.removeAttribute('.button.add-to-balance', 'disabled');
				userAddBalanceButton.addEventListener('click', userAddBalanceButton.clickListener);
			}
		}
	});
};
var processUsers = function(frameName, frameSelector) {
	api.setRequestParameters({
		action: frameName,
		url: apiRequestParameters.current.settings.baseUrl + 'api/users'
	});
	api.sendRequest(function(response) {
		elements.html('[frame="' + frameName + '"] .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));

		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}

		if (response.code !== 200) {
			return false;
		}
	});
};
api.setRequestParameters({
	defaults: {
		action: 'register',
		table: 'users'
	},
	table: 'users'
});
