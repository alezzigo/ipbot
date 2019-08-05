'use_strict';

var defaultTable = 'users',
	previousAction = 'register';
var processUsers = (windowName, windowSelector) => {
	requestParameters.action = windowName;
	requestParameters.table = 'users';
	sendRequest((response) => {
		var messageContainer = document.querySelector('.' + windowName + ' .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (response.message ? '<p class="message">' + response.message + '</p>' : '');
		}

		if (response.code !== 200) {
			return;
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
requestParameters.url = '/src/views/users/api.php';
onLoad(() => {
	// ...
});
