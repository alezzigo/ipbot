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
	// ..
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
