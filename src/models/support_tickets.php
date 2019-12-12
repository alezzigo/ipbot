<?php
/**
 * Support Tickets Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */

if (!empty($config->settings['base_path'])) {
	require_once($config->settings['base_path'] . '/models/app.php');
}

class SupportTicketsModel extends AppModel {

/**
 * List support tickets
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array
 */
	public function list($table, $parameters = array()) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing support tickets request, please try again.')
			)
		);

		if (!empty($parameters)) {
			$supportTicketData = array();
			$supportTicketParameters = array(
				'conditions' => array(
					'session_id' => $parameters['session'],
					'user_id' => null
				),
				'fields' => array(
					'created',
					'id',
					'message',
					'modified',
					'session_id',
					'subject',
					'user_id'
				),
				'sort' => array(
					'field' => 'modified',
					'order' => 'DESC'
				)
			);
			$supportTickets = $this->fetch('support_tickets', $supportTicketParameters);

			if (
				!empty($supportTickets['count']) &&
				!empty($parameters['user'])
			) {
				$supportTicketData = array_replace_recursive($supportTickets['data'], array(
					'user_id' => $parameters['user']['id']
				));
			}

			if ($this->save('support_tickets', $supportTicketData)) {
				$supportTicketParameters['conditions'] = array(
					'OR' => array_merge(
						array(
							'user_id' => $parameters['user']['id']
						),
						$supportTicketParameters['conditions']
					)
				);
				$response = $this->fetch('support_tickets', $supportTicketParameters);

				if (empty($response['user'])) {
					$response['message']['text'] .= ' You\'re currently not logged in, please <a href="' . $this->settings['base_url'] . '?#login">log in</a> or <a href="' . $this->settings['base_url'] . '?#register">register an account</a>.';
				}

				if ($response['message']['status'] === 'success') {
					unset($response['message']['text']);
				}
			}
		}

		return $response;
	}

/**
 * View support ticket
 *
 * @param array $parameters
 *
 * @return array $response
 */
	public function view($parameters) {
		if (
			empty($supportTicketId = $parameters['id']) ||
			!is_numeric($supportTicketId)
		) {
			$this->redirect($this->settings['base_url'] . 'support');
		}

		$response = array(
			'support_ticket_id' => $parameters['id']
		);
		return $response;
	}

}
