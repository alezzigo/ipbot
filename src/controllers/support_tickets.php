<?php
	require_once($config->settings['base_path'] . '/models/support_tickets.php');

	class SupportTicketsController extends SupportTicketsModel {

	/**
	 * Support Tickets API
	 *
	 * @return array Response
	 */
		public function api() {
			return $this->_request($_POST);
		}

	}

	$supportTicketsController = new SupportTicketsController();
	$data = $supportTicketsController->route($config->parameters);
?>
