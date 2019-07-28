'use_strict';

var defaultTable = 'users';
requestParameters.action = 'register';
requestParameters.table = 'users';
requestParameters.url = '/src/views/users/api.php';
onLoad(() => {
	sendRequest((response) => {
		// ...
	});
});
