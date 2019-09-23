'use_strict';

var defaultTable = 'users';
var previousAction = 'register';
var processEmail = function() {
	processUser();
	var hash = replaceCharacter(window.location.search, 0, '');

	if (hash) {
		requestParameters.data['token'] = hash;
	}

	if (
		requestParameters.data['token'] ||
		requestParameters.data['email']
	) {
		requestParameters.action = 'email';
		requestParameters.table = 'users';
		sendRequest(function(response) {
			var messageContainer = document.querySelector('.change-email .message-container');

			if (messageContainer) {
				if (
					!hash &&
					response.user === false
				) {
					elements.addClass('nav .user', 'hidden');
					elements.addClass('.change-email-form-item', 'hidden');
					elements.removeClass('nav .guest', 'hidden');
					elements.setAttribute('.change-email input.email', 'disabled', 'disabled');
					response.message = {
						status: 'error',
						text: 'You\'re currently not logged in, please <a href="' + requestParameters.settings.base_url + '?#login">log in</a> or <a href="' + requestParameters.settings.base_url + '?#register">register an account</a>.'
					};
				}

				if (
					response.message.status === 'success' &&
					typeof response.data !== 'undefined'
				) {
					elements.addClass('.change-email-form-item', 'hidden');
					document.querySelector('.change-email input.email').value = response.data.new_email;
					elements.setAttribute('.change-email input.email', 'disabled', 'disabled');
					processUser();
				}

				messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
			}
		});
	}
};
var processReset = function() {
	var hash = replaceCharacter(window.location.search, 0, '');
	requestParameters.action = 'reset';
	requestParameters.table = 'users';
	requestParameters.data['token'] = hash;
	sendRequest(function(response) {
		var messageContainer = document.querySelector('.reset .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		if (
			typeof response.data !== 'undefined' &&
			response.data.user.email
		) {
			document.querySelector('.reset .email').value = response.data.user.email;
		}
	});
};
var processUser = function() {
	requestParameters.action = 'view';
	requestParameters.table = 'users';
	var userContainer = document.querySelector('.user-container');
	var userData = '';
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

		if (response.user !== false) {
			userData += '<h2>Account Details</h2>';
			userData += '<p><strong>User ID</strong><br>' + response.user.id + '</p>';
			userData += '<p><strong>Email Address</strong><br>' + response.user.email + '<br><a class="email" href="' + requestParameters.settings.base_url + 'account/?#email">Change email address</a></p>';
			userData += '<p><strong>Password</strong><br>********<br>Last changed: ' + response.user.password_modified + '</p>';
			userData += '<h2>Account Balance</h2>';
			userData += '<p><strong>Current Balance</strong><br>' + requestParameters.settings.billing_currency_symbol + response.user.balance + ' ' + requestParameters.settings.billing_currency_name + '</p>';
			userData += '<div class="balance-message-container"></div>';
			userData += '<p class="no-margin-bottom"><strong>Add to Account Balance</strong></p>';
			userData += '<div class="clear"></div>';
			userData += '<div class="align-left item-container"><div class="field-group no-margin-top"><span class="balance-currency-symbol">' + requestParameters.settings.billing_currency_symbol + '</span><input class="balance-amount billing-amount" id="balance-amount" max="10000" min="20" name="balance_amount" step="0.01" type="number" value="100.00"><span class="balance-currency-name">' + requestParameters.settings.billing_currency_name + '</span><a class="add add-to-balance button" disabled href="javascript:void(0);">Add</a></div></div>';
		}

		userContainer.innerHTML = userData;
		userAddBalanceButton = userContainer.querySelector('.button.add-to-balance');

		if (userAddBalanceButton) {
			userAddBalanceButton.removeEventListener('click', userAddBalanceButton.clickListener);
			userAddBalanceButton.clickListener = function() {
				requestParameters.data['balance'] = userContainer.querySelector('.balance-amount').value;
				requestParameters.action = 'balance';
				sendRequest(function(response) {
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
	});
};
var processUsers = function(windowName, windowSelector) {
	requestParameters.action = windowName;
	requestParameters.table = 'users';
	sendRequest(function(response) {
		var messageContainer = document.querySelector('.' + windowName + ' .message-container');

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
requestParameters.url = '/api/users';
