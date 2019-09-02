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
 * Process credit card payments
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processCreditCard($parameters) {
		$response = array();
		// ..
		return $response;
	}

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
 * Process PayPal payments
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processPaypal($parameters) {
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
		$response = $defaultResponse = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your payment request, please try again')
			)
		);

		if (
			!empty($parameters['user']) ||
			(
				(
					($response = $this->register('users', $parameters)) &&
					!empty($response['message']['status']) &&
					$response['message']['status'] === 'success'
				) ||
				(
					($response = $this->login('users', $parameters)) &&
					!empty($response['message']['status']) &&
					$response['message']['status'] === 'success'
				)
			)
		) {
			$parameters['user'] = empty($parameters['user']) ? $response['user'] : $parameters['user'];
			$response['message'] = $defaultResponse['message'];
			unset($response['redirect']);

			if (
				!isset($parameters['data']['recurring']) ||
				!is_bool($parameters['data']['recurring'])
			) {
				$parameters['data']['recurring'] = false;
			}

			$response['message']['text'] = 'Invalid payment amount, please try again.';

			if (
				!empty($amount = $parameters['data']['billing_amount']) &&
				is_numeric($amount) &&
				number_format($amount, 2, '.', '') == $amount
			) {
				$response['message']['text'] = 'Invalid payment method, please try again.';

				if (!empty($parameters['data']['payment_method'])) {
					$method = '_process' . str_replace(' ', '', ucwords(str_replace('_', ' ', $parameters['data']['payment_method'])));

					if (method_exists($this, $method)) {
						$response['message']['text'] = 'Invalid invoice ID, please try again.';
						$invoice = $this->invoice('invoices', array_merge($parameters, array(
							'conditions' => array(
								'id' => $parameters['data']['invoice_id']
							)
						)));

						if ($invoice['message']['status'] === 'success') {
							$parameters['data'] = array_merge($parameters['data'], $invoice['data']);
							$response = $this->$method($parameters);
						}
					}
				}
			}
		}

		return $response;
	}

}
