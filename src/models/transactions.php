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
 * Process PayPal payments
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processPaypal($parameters) {
		$parameters['request'] = array(
			'business' => $this->settings['billing']['merchant_ids']['paypal'],
			'cancel_return' => $_SERVER['HTTP_REFERER'] . '#payment',
			'cmd' => '_xclick',
			'item_name' => $this->settings['site_name'] . ' Plan #' . $parameters['data']['plan']['id'],
			'item_number' => $parameters['data']['invoice']['id'] . '_' . $parameters['data']['plan']['id'] . '_' . $parameters['data']['invoice']['user_id'],
			'notify_url' => $_SERVER['HTTP_REFERER'] . $_SERVER['REDIRECT_URL'],
			'return' => $_SERVER['HTTP_REFERER'],
			'src' => '1'
		);

		if (!empty($parameters['data']['billing_recurring'])) {
			$parameters['request'] = array_merge($parameters['request'], array(
				'a3' => $parameters['data']['billing_amount'],
				'cmd' => $parameters['request']['cmd'] . '-subscriptions',
				'p3' => $parameters['data']['orders'][0]['interval_value'],
				't3' => ucwords(substr($parameters['data']['orders'][0]['interval_type'], 0, 1))
			));
		} else {
			$parameters['request'] = array_merge($parameters['request'], array(
				'amount' => $parameters['data']['billing_amount']
			));
		}

		$response = array(
			'message' => array(
				'status' => 'success',
				'text' => 'Redirecting to PayPal for payment, please wait.'
			),
			'redirect' => 'https://www.paypal.com/cgi-bin/webscr?' . http_build_query($parameters['request'])
		);
		return $response;
	}

/**
 * Save transaction notification
 *
 * @param mixed [string/array] $parameters
 *
 * @return array $response
 */
	protected function _saveTransaction($parameters) {
		$response = array();

		if (
			!empty($parameters) &&
			is_string($parameters)
		) {
			$parameters = json_decode($parameters, true);

			if (array_key_exists('verify_sign', $parameters)) {
				$this->_savePaypalNotification($parameters);
			}
		}

		if (
			is_array($parameters) &&
			empty($parameters['json'])
		) {
			// ..
		}

		return $response;
	}

/**
 * Save credit card transaction notifications
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _saveCreditCardNotification($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Save PayPal transaction notification
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _savePaypalNotification($parameters) {
		$response = $transaction = array();

		if ($this->_validatePaypalNotification($parameters) || true) {
			$itemNumberIds = explode('_', $parameters['item_number']);
			$transaction = array(
				'billing_address_1' => $parameters['address_street'],
				'billing_address_status' => $parameters['address_status'],
				'billing_city' => $parameters['address_city'],
				'billing_country_code' => $parameters['address_country_code'],
				'billing_name' => $parameters['address_country_code'],
				'billing_region' => $parameters['address_state'],
				'billing_zip' => $parameters['address_zip'],
				'customer_email' => $parameters['payer_email'],
				'customer_first_name' => $parameters['first_name'],
				'customer_id' => $parameters['payer_id'],
				'customer_last_name' => $parameters['last_name'],
				'customer_status' => $parameters['payer_status'],
				'id' => $parameters['txn_id'],
				'invoice_id' => (!empty($itemNumberIds[0]) && is_numeric($itemNumberIds[0]) ? $itemNumberIds[0] : 0),
				'payment_amount' => $parameters['mc_gross'],
				'payment_currency' => $parameters['mc_currency'],
				'payment_external_fee' => $parameters['mc_fee'],
				'payment_shipping_amount' => $parameters['shipping'],
				'payment_status' => $parameters['payment_status'],
				'payment_tax_amount' => $parameters['tax'],
				'plan_id' => (!empty($itemNumberIds[1]) && is_numeric($itemNumberIds[1]) ? $itemNumberIds[1] : 0),
				'provider_country_code' => $parameters['residence_country'],
				'provider_email' => $parameters['receiver_email'],
				'provider_id' => $parameters['receiver_id'],
				'sandbox' => (!empty($parameters['test_ipn']) ? true : false),
				'transaction_charset' => $this->settings['database']['charset'],
				'transaction_date' => date('Y-m-d h:i:s', strtotime($parameters['payment_date'])),
				'transaction_processed' => 0,
				'transaction_raw' => json_encode($parameters),
				'transaction_token' => $parameters['verify_sign'],
				'transaction_type' => $parameters['txn_type'],
				'user_id' => (!empty($itemNumberIds[2]) && is_numeric($itemNumberIds[2]) ? $itemNumberIds[2] : 0)
			);
			$existingTransaction = $this->find('transactions', array(
				'conditions' => array(
					'id' => $parameters['txn_id']
				),
				'fields' => array(
					'id'
				),
				'limit' => 1
			));

			if (
				empty($existingTransaction['count']) &&
				$this->save('transactions', array(
					$transaction
				))
			) {
				$response = $transaction;
			}
		}

		return $response;
	}

/**
 * Validate PayPal notification
 *
 * @param array $parameters
 *
 * @return boolean $response
 */
	protected function _validatePaypalNotification($parameters) {
		$response = false;
		$urlParameters = $parameters;
		array_walk($urlParameters, function(&$value, $key) {
			$value = $key . '=' . $value;
		});
		$validNotification = (
			strtolower(file_get_contents('https://ipnpb.paypal.com/cgi-bin/webscr?cmd=_notify-validate&' . implode('&', $urlParameters))) === 'verified' &&
			(
				!empty($parameters['address_city']) &&
				is_string($parameters['address_city'])
			) &&
			(
				!empty($parameters['address_country']) &&
				is_string($parameters['address_country'])
			) &&
			(
				!empty($parameters['address_country_code']) &&
				is_string($parameters['address_country_code'])
			) &&
			(
				!empty($parameters['address_name']) &&
				is_string($parameters['address_name'])
			) &&
			(
				!empty($parameters['address_state']) &&
				is_string($parameters['address_state'])
			) &&
			(
				!empty($parameters['address_status']) &&
				is_string($parameters['address_status'])
			) &&
			(
				!empty($parameters['address_street']) &&
				is_string($parameters['address_street'])
			) &&
			(
				!empty($parameters['address_zip']) &&
				is_string($parameters['address_zip'])
			) &&
			(
				!empty($parameters['business']) &&
				is_string($parameters['business'])
			) &&
			(
				!empty($parameters['first_name']) &&
				is_string($parameters['first_name'])
			) &&
			(
				!empty($parameters['item_name']) &&
				is_string($parameters['item_name'])
			) &&
			(
				!empty($parameters['item_number']) &&
				is_string($parameters['item_number'])
			) &&
			(
				!empty($parameters['last_name']) &&
				is_string($parameters['last_name'])
			) &&
			(
				!empty($parameters['mc_currency']) &&
				is_string($parameters['mc_currency'])
			) &&
			(
				!empty($parameters['mc_fee']) &&
				is_numeric($parameters['mc_fee'])
			) &&
			(
				!empty($parameters['mc_gross']) &&
				is_numeric($parameters['mc_gross'])
			) &&
			(
				!empty($parameters['notify_version']) &&
				is_string($parameters['notify_version'])
			) &&
			(
				!empty($parameters['payer_email']) &&
				is_string($parameters['payer_email']) &&
				$this->_validateEmailFormat($parameters['payer_email'])
			) &&
			(
				!empty($parameters['payer_id']) &&
				is_string($parameters['payer_id'])
			) &&
			(
				!empty($parameters['payer_status']) &&
				is_string($parameters['payer_status'])
			) &&
			(
				!empty($parameters['payment_date']) &&
				is_string($parameters['payment_date'])
			) &&
			(
				!empty($parameters['payment_status']) &&
				is_string($parameters['payment_status'])
			) &&
			(
				!empty($parameters['payment_type']) &&
				is_string($parameters['payment_type'])
			) &&
			(
				!empty($parameters['quantity']) &&
				is_numeric($parameters['quantity'])
			) &&
			(
				!empty($parameters['receiver_email']) &&
				is_string($parameters['receiver_email']) &&
				$this->_validateEmailFormat($parameters['receiver_email'])
			) &&
			(
				!empty($parameters['receiver_id']) &&
				is_string($parameters['receiver_id'])
			) &&
			(
				!empty($parameters['residence_country']) &&
				is_string($parameters['residence_country'])
			) &&
			(
				!empty($parameters['shipping']) &&
				is_numeric($parameters['shipping'])
			) &&
			(
				!empty($parameters['tax']) &&
				is_numeric($parameters['tax'])
			) &&
			(
				!empty($parameters['txn_id']) &&
				is_string($parameters['txn_id'])
			) &&
			(
				!empty($parameters['txn_type']) &&
				is_string($parameters['txn_type'])
			) &&
			(
				!empty($parameters['verify_sign']) &&
				is_string($parameters['verify_sign'])
			)
		);

		if ($validNotification) {
			$response = true;
		}

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
						$parameters['data'] = array_merge($parameters['data'], $invoice['data']);

						if (
							$invoice['message']['status'] === 'success' &&
							$parameters['user']['id'] === $parameters['data']['invoice']['user_id']
						) {
							$response['message']['text'] = $defaultMessage;
							$planData = array(
								'cart_items' => $parameters['data']['invoice']['cart_items'],
								'invoice_id' => $parameters['data']['invoice']['id'],
								'price' => $parameters['data']['billing_amount']
							);
							$existingPlan = $this->find('plans', array(
								'conditions' => $planData,
								'fields' => array(
									'id'
								),
								'limit' => 1
							));

							if (
								!empty($existingPlan['count']) ||
								$this->save('plans', array(
									$planData
								))
							) {
								$plan = $this->find('plans', array(
									'conditions' => $planData,
									'fields' => array(
										'cart_items',
										'created',
										'id',
										'invoice_id',
										'modified',
										'price'
									),
									'limit' => 1,
									'sort' => array(
										'field' => 'created',
										'order' => 'DESC'
									)
								));

								if (!empty($plan['count'])) {
									$parameters['data']['plan'] = $plan['data'][0];
									$response = $this->$method($parameters);
								}
							}
						}
					}
				}
			}
		}

		return $response;
	}

}
