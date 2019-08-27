<?php
/**
 * Invoices Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class InvoicesModel extends AppModel {

/**
 * Process invoice requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function invoice($table, $parameters) {
		$response = array(
			'data' => array(),
			'message' => ($defaultMessage = 'Error processing your invoice request, please try again.')
		);

		$invoice = $this->find($table, array(
			'conditions' => $parameters['conditions']
		));

		if (!empty($invoice['count'])) {
			$response = array(
				'data' => $invoice['data'],
				'message' => ''
			);
		}

		return $response;
	}

/**
 * List invoices
 *
 * @return array
 */
	public function list() {
		return array();
	}

/**
 * View invoice
 *
 * @param array $parameters
 *
 * @return array $response
 */
	public function view($parameters) {
		if (
			empty($invoiceId = $parameters['id']) ||
			!is_numeric($invoiceId)
		) {
			$this->redirect($this->settings['base_url'] . 'invoices');
		}

		$response = array(
			'invoice_id' => $parameters['id']
		);
		return $response;
	}

}
