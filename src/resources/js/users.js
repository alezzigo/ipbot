'use_strict';

var defaultTable = 'users';
var previousAction = 'register';
var processRemove = function() {
	requestParameters.action = 'remove';
	requestParameters.table = 'users';
	sendRequest(function(response) {
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
					elements.addClass('.change-email form-item', 'hidden');
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

		if (typeof response.user !== 'undefined') {
			if (response.user.email) {
				document.querySelector('.reset .email').value = response.user.email;
				elements.removeClass('.reset .submit', 'hidden');
			}

			if (
				response.message.status === 'success' &&
				requestParameters.data.email
			) {
				elements.addClass('.reset .submit', 'hidden');
			}
		}

		processUser();
	});
};
var processUser = function() {
	elements.removeClass('.form-item', 'hidden');
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

		if (userContainer) {
			if (response.user !== false) {
				userData += '<h2>Account Details</h2>';
				userData += '<p><strong>User ID</strong><br>' + response.user.id + '</p>';
				userData += '<p><strong>Email Address</strong><br>' + response.user.email + '<br><a class="email" href="' + requestParameters.settings.base_url + 'account/?#email">Change email address</a></p>';
				userData += '<p><strong>Password</strong><br>********<br>Last changed: ' + response.user.password_modified + '<br><a class="password" href="' + requestParameters.settings.base_url + 'account/?#reset">Change password</a></p>';
				userData += '<h2>Account Balance</h2>';
				userData += '<p><strong>Current Balance</strong><br>' + response.user.balance + ' ' + requestParameters.settings.billing_currency + '</p>';
				userData += '<div class="balance-message-container"></div>';
				userData += '<p class="no-margin-bottom"><strong>Add to Account Balance</strong></p>';
				userData += '<div class="clear"></div>';
				userData += '<div class="align-left item-container no-margin-bottom"><div class="field-group no-margin"><input class="balance-amount billing-amount" id="balance-amount" max="10000" min="20" name="balance_amount" step="0.01" type="number" value="100.00"><span class="balance-currency-name">' + requestParameters.settings.billing_currency + '</span><a class="add add-to-balance button" disabled href="javascript:void(0);">Add</a></div></div>';
				userData += '<div class="clear"></div>';
				userData += '<h2>Remove Account</h2>';

				if (response.user.removed) {
					userData += '<p class="error message">Your account will be removed shortly as requested.</p>';
				} else {
					userData += '<a class="remove" href="' + requestParameters.settings.base_url + 'account/?#request-removal">Request account removal</a>';
				}
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
