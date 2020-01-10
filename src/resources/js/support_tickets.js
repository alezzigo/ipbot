var processSupportTicket = function() {
	var supportTicketContainer = document.querySelector('.support-ticket-container');
	var supportTicketData = '';
	var supportTicketId = document.querySelector('input[name="support_ticket_id"]').value;
	api.setRequestParameters({
		action: 'view',
		conditions: {
			id: supportTicketId
		},
		table: 'support_tickets',
		url: apiRequestParameters.current.settings.baseUrl + 'api/support_tickets'
	});
	api.sendRequest(function(response) {
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

		// ..
	});
};
var processSupportTickets = function() {
	var supportTicketData = '';
	api.setRequestParameters({
		action: 'list',
		table: 'support_tickets',
		url: apiRequestParameters.current.settings.baseUrl + 'api/support_tickets'
	});
	api.sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		// ..
	});
};
