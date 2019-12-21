var processEmail = function() {
	processUser();
	var hash = window.location.search.substr(1);

	if (hash) {
		api.setRequestParameters({
			data: {
				token: hash
			}
		}, true);
	}

	if (
		apiRequestParameters.current.data['token'] ||
		apiRequestParameters.current.data['email']
	) {
		api.setRequestParameters({
			action: 'email',
			url: apiRequestParameters.current.settings.base_url + 'api/users'
		});
		api.sendRequest(function(response) {
			var messageContainer = document.querySelector('.change-email .message-container');

			if (messageContainer) {
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
						text: 'You\'re currently not logged in, please <a href="' + apiRequestParameters.current.settings.base_url + '?#login">log in</a> or <a href="' + apiRequestParameters.current.settings.base_url + '?#register">register an account</a>.'
					};
				}

				if (
					response.message.status === 'success' &&
					typeof response.data !== 'undefined'
				) {
					document.querySelector('.change-email input.email').value = response.data.new_email;
					elements.setAttribute('.change-email input.email', 'disabled', 'disabled');
					processUser();
					elements.addClass('.change-email form-item', 'hidden');
				}

				messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
			}
		});
	}
};
var processRemove = function() {
	api.setRequestParameters({
		action: 'remove',
		url: apiRequestParameters.current.settings.base_url + 'api/users'
	});
	api.sendRequest(function(response) {
		var messageContainer = document.querySelector('.request-removal .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		processUser();

		if (response.message.status === 'success') {
			elements.addClass('.request-removal .form-item', 'hidden');
		}
	});
};
var processReset = function() {
	var hash = window.location.search.substr(1);
	api.setRequestParameters({
		action: 'reset',
		data: {
			token: hash
		},
		url: apiRequestParameters.current.settings.base_url + 'api/users'
	}, true);
	api.sendRequest(function(response) {
		var messageContainer = document.querySelector('.reset .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (typeof response.user !== 'undefined') {
			if (response.user.email) {
				document.querySelector('.reset .email').value = response.user.email;
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
	var userContainer = document.querySelector('.user-container');
	var userData = '';
	elements.removeClass('.form-item', 'hidden');
	api.setRequestParameters({
		action: 'view',
		url: apiRequestParameters.current.settings.base_url + 'api/users'
	});
	api.sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			if (response.user === false) {
				elements.addClass('nav .user', 'hidden');
				elements.removeClass('nav .guest', 'hidden');
				response.message = {
					status: 'error',
					text: 'You\'re currently not logged in, please <a href="' + apiRequestParameters.current.settings.base_url + '?#login">log in</a> or <a href="' + apiRequestParameters.current.settings.base_url + '?#register">register an account</a>.'
				};
			}

			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (userContainer) {
			if (response.user !== false) {
				userData += '<h2>Account Details</h2>';
				userData += '<p><strong>User ID</strong><br>' + response.user.id + '</p>';
				userData += '<p><strong>Email Address</strong><br>' + response.user.email + '<br><a class="email" href="' + apiRequestParameters.current.settings.base_url + 'account/?#email">Change email address</a></p>';
				userData += '<p><strong>Password</strong><br>********<br>Last changed: ' + response.user.password_modified + '<br><a class="password" href="' + apiRequestParameters.current.settings.base_url + 'account/?#reset">Change password</a></p>';
				userData += '<h2>Account Balance</h2>';

				if (
					typeof response.user.test_account !== 'undefined' &&
					response.user.test_account
				) {
					userData += '<p class="message error">Invoices, payments and account balance are for testing purposes only.</p>';
				}

				userData += '<p><strong>Current Balance</strong><br>' + response.user.balance + ' ' + apiRequestParameters.current.settings.billing_currency + '</p>';
				userData += '<div class="balance-message-container"></div>';
				userData += '<p class="no-margin-bottom"><strong>Add to Account Balance</strong></p>';
				userData += '<div class="clear"></div>';
				userData += '<div class="align-left item-container no-margin-bottom"><div class="field-group no-margin"><input class="balance-amount billing-amount" id="balance-amount" max="10000" min="20" name="balance_amount" step="0.01" type="number" value="100.00"><span class="balance-currency-name">' + apiRequestParameters.current.settings.billing_currency + '</span><a class="add add-to-balance button" disabled href="javascript:void(0);">Add</a></div></div>';
				userData += '<div class="clear"></div>';

				if (response.user.subscriptions) {
					userData += '<h2>Account Subscriptions</h2>';
					response.user.subscriptions.map(function(item, index) {
						userData += '<div class="item-container item-button" subscription_id="' + item.id + '">';
						userData += '<div class="item">';
						userData += '<div class="item-body">';
						userData += '<p><strong>Subscription #' + item.id + '</strong></p>';
						userData += '<p>' + item.price + ' ' + apiRequestParameters.current.settings.billing_currency + ' per ' + item.interval_value + ' ' + item.interval_type + (item.interval_value !== 1 ? 's' : '') + '</p>';
						userData += '<span class="label-container">'
						userData += '<label class="label ' + item.status + '">' + item.status.replace('_', ' ') + '</label>';
						userData += '</span>';
						userData += (item.status.indexOf('cancel') < 0 ? '<a class="cancel cancel-subscription" href="javascript:void(0);" subscription_id="' + item.id + '">Request Cancellation</a>' : '');
						userData += '<div class="hidden message-container no-margin-bottom"></div>';
						userData += '</div>';
						userData += '</div>';
						userData += '</div>';
					});
				}

				userData += '<h2>Remove Account</h2>';

				if (response.user.removed) {
					userData += '<p class="error message">Your account will be removed shortly as requested.</p>';
				} else {
					userData += '<a class="remove" href="' + apiRequestParameters.current.settings.base_url + 'account/?#request-removal">Request account removal</a>';
				}
			}

			userContainer.innerHTML = userData;

			if (response.user.subscriptions) {
				response.user.subscriptions.map(function(item, index) {
					var cancelSubscriptionButton = document.querySelector('.item-button[subscription_id="' + item.id + '"] .cancel-subscription');
					var cancelSubscription = function(subscriptionId) {
						api.setRequestParameters({
							action: 'cancel',
							data: {
								subscription_id: subscriptionId
							}
						}, true);
						api.sendRequest(function(response) {
							if (typeof response.message.text !== 'undefined') {
								var subscriptionContainerSelector = '.item-button[subscription_id="' + subscriptionId + '"]';
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
				});
			}

			userAddBalanceButton = userContainer.querySelector('.button.add-to-balance');

			if (userAddBalanceButton) {
				userAddBalanceButton.removeEventListener('click', userAddBalanceButton.clickListener);
				userAddBalanceButton.clickListener = function() {
					api.setRequestParameters({
						action: 'balance',
						data: {
							balance: userContainer.querySelector('.balance-amount').value
						}
					}, true);
					api.sendRequest(function(response) {
						var messageContainer = document.querySelector('.balance-message-container');

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
					});
				};
				userAddBalanceButton.addEventListener('click', userAddBalanceButton.clickListener);
				elements.removeAttribute('.button.add-to-balance', 'disabled');
			}
		}
	});
};
var processUsers = function(frameName, frameSelector) {
	api.setRequestParameters({
		action: frameName,
		url: apiRequestParameters.current.settings.base_url + 'api/users'
	});
	api.sendRequest(function(response) {
		var messageContainer = document.querySelector('.' + frameName + ' .message-container');

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
