var processSupportTicket = function() {
	let supportTicketData = '';
	const supportTicketId = elements.get('input[name="support_ticket_id"]').value;
	api.setRequestParameters({
		action: 'view',
		conditions: {
			id: supportTicketId
		},
		table: 'support_tickets',
		url: apiRequestParameters.current.settings.baseUrl + 'api/support_tickets'
	});
	api.sendRequest(function(response) {
		elements.html('main .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));

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
	let supportTicketData = '';
	api.setRequestParameters({
		action: 'list',
		table: 'support_tickets',
		url: apiRequestParameters.current.settings.baseUrl + 'api/support_tickets'
	});
	api.sendRequest(function(response) {
		elements.html('main .message-container', (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : ''));
		// ..
	});
};
