'use_strict';

var defaultTable = 'users';
var previousAction = 'register';
var processReset = function() {
	var hash =  replaceCharacter(window.location.search, 0, '');
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
			userData += '<p><strong>Email Address</strong><br>' + response.user.email + '</p>';
			userData += '<h2>Account Balance</h2>';
			userData += '<p><strong>Current Balance</strong><br>' + requestParameters.settings.billing_currency_symbol + response.user.balance + ' ' + requestParameters.settings.billing_currency_name + '</p>';
		}

		userContainer.innerHTML = userData;
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
