<?php
/**
 * Transactions Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/invoices.php');

class TransactionsModel extends InvoicesModel {

/**
 * Process transaction notification
 *
 * @param array $transactionData
 *
 * @return array $response
 */
	protected function _processTransaction($transactionData) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process payment requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function payment($table, $parameters) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your payment request, please try again')
			)
		);

		if (
			!empty($parameters['user']) ||
			(
				($response = $this->register('users', $parameters)) &&
				!empty($response['message']['status']) &&
				$response['message']['status'] === 'success'
			)
		) {
			// ..
		}

		return $response;
	}

}
