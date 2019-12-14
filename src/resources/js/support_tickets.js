var defaultTable = 'support_tickets';
var previousAction = 'fetch';
var processSupportTicket = function() {
	requestParameters.action = 'view';
	requestParameters.table = 'support_tickets';
	requestParameters.url = requestParameters.settings.base_url + 'api/support_tickets';
	var supportTicketContainer = document.querySelector('.support-ticket-container');
	var supportTicketData = '';
	var supportTicketId = document.querySelector('input[name="support_ticket_id"]').value;
	requestParameters.conditions = {
		id: supportTicketId
	};
	sendRequest(function(response) {
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
	requestParameters.action = 'list';
	requestParameters.table = 'support_tickets';
	requestParameters.url = requestParameters.settings.base_url + 'api/support_tickets';
	var supportTicketData = '';
	sendRequest(function(response) {
		var messageContainer = document.querySelector('main .message-container');

		if (messageContainer) {
			messageContainer.innerHTML = (typeof response.message !== 'undefined' && response.message.text ? '<p class="message' + (response.message.status ? ' ' + response.message.status : '') + '">' + response.message.text + '</p>' : '');
		}

		// ..
	});
};
