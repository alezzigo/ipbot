<?php
/**
 * Transactions Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
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
 * Process transaction
 *
 * @param array $parameters
 *
 * @return boolean $response
 */
	protected function _processTransaction($parameters) {
		$response = true;
		// ..
		return $response;
	}

/**
 * Process transactions
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactions($parameters) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'There aren\'t any new transactions to process, please try again.'
			)
		);
		$transactionsToProcess = $this->find('transactions', array(
			'conditions' => array(
				'AND' => array(
					'transaction_processed' => false,
					'OR' => array(
						'modified <' => date('Y-m-d H:i:s', strtotime('-1 minute')),
						'transaction_processing' => false
					)
				)
			),
			'fields' => array(
				'billing_address_1',
				'billing_address_status',
				'billing_city',
				'billing_country_code',
				'billing_name',
				'billing_region',
				'billing_zip',
				'customer_email',
				'customer_first_name',
				'customer_id',
				'customer_last_name',
				'customer_status',
				'id',
				'invoice_id',
				'payment_amount',
				'payment_currency',
				'payment_external_fee',
				'payment_method_id',
				'payment_shipping_amount',
				'payment_status',
				'payment_status_code',
				'payment_status_message',
				'payment_tax_amount',
				'plan_id',
				'provider_country_code',
				'provider_email',
				'provider_id',
				'sandbox',
				'transaction_charset',
				'transaction_date',
				'transaction_processed',
				'transaction_processing',
				'transaction_raw',
				'transaction_token',
				'transaction_type',
				'user_id'
			),
			'sort' => array(
				'field' => 'created',
				'order' => 'DESC'
			)
		));

		if (!empty($transactionsToProcess['count'])) {
			$response['message']['text'] = ($defaultMessage = 'Error processing transactions, please try again.');
			$transactions = array();

			foreach ($transactionsToProcess['data'] as $transaction) {
				$transactions[] = array(
					'id' => $transaction['id'],
					'transaction_processing' => true
				);
			}

			if ($this->save('transactions', $transactions)) {
				$processedTransactions = array();

				foreach($transactionsToProcess['data'] as $transaction) {
					$processed = $this->_processTransaction($transaction);
					$processedTransactions[] = array(
						'transaction_processed' => $processed,
						'transaction_processing' => !$processed
					);
				}

				$transactions = array_replace_recursive($transactions, $processedTransactions);

				if ($this->save('transactions', $transactions)) {
					$response = array(
						'data' => $transactionsToProcess,
						'message' => array(
							'status' => 'success',
							'text' => 'Transactions processed successfully.'
						)
					);
				}
			}
		}

		return $response;
	}

/**
 * Process miscellaneous transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionMiscellaneous() {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process payment completed transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionPaymentCompleted($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process payment failed transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionPaymentFailed($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process payment pending transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionPaymentPending($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process payment refund transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionPaymentRefunded($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process payment reversal cancellation transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionPaymentReversalCanceled($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process payment reversal transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionPaymentReversed($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process subscription canceled transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionSubscriptionCanceled($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process subscription created transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionSubscriptionCreated($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process subscription expired transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionSubscriptionExpired($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process subscription modified transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionSubscriptionModified($parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * Process subscription failed transaction
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processTransactionSubscriptionFailed($parameters) {
		$response = array();
		// ..
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

		if ($this->_validatePaypalNotification($parameters)) {
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
				'payment_method_id' => 'paypal',
				'payment_shipping_amount' => $parameters['shipping'],
				'payment_status' => strtolower($parameters['payment_status']),
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

			if (!empty($parameters['pending_reason'])) {
				$transaction['payment_status_code'] = $parameters['pending_reason'];
			}

			if (!empty($parameters['reason_code'])) {
				$transaction['payment_status_code'] = $parameters['reason_code'];
			}

			$transaction = array_merge($transaction, $this->_savePayPalTransactionMethod($parameters, $transaction));
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
 * Save transaction method and custom status message
 *
 * @param array $parameters
 * @param array $transactionData
 *
 * @return array $response
 */
	protected function _savePayPalTransactionMethod($parameters, $transactionData) {
		$response = array(
			'payment_status_message' => 'Transaction processed.',
			'transaction_method' => 'Miscellaneous'
		);

		if (in_array($parameters['txn_type'], array(
			'subscr_payment',
			'web_accept'
		))) {
			if (in_array($parameters['payment_type'], array(
				'echeck',
				'instant'
			))) {
				switch ($parameters['payment_status']) {
					case 'Completed':
					case 'Created':
					case 'Processed':
						$response = array(
							'payment_status_message' => 'Payment successful.',
							'transaction_method' => 'PaymentCompleted'
						);
						break;
					case 'Pending':
						$response = array(
							'payment_status_message' => 'Payment pending.',
							'transaction_method' => 'PaymentPending'
						);

						if (!empty($parameters['pending_reason'])) {
							switch ($parameters['pending_reason']) {
								case 'address':
									$response['payment_status_message'] = 'Unconfirmed shipping address requires manual confirmation.';
									break;
								case 'delayed_disbursement':
									$response['payment_status_message'] = 'Payment is authorized but awaiting bank funding.';
									break;
								case 'echeck':
									$response['payment_status_message'] = 'eCheck payment has not yet cleared.';
									break;
								case 'intl':
									$response['payment_status_message'] = 'International payment requires manual approval.';
									break;
								case 'multi_currency':
									$response['payment_status_message'] = 'Currency conversion requires manual approval.';
									break;
								case 'paymentreview':
								case 'regulatory_review':
									$response['payment_status_message'] = 'Payment is awaiting review by payment processor.';
									break;
								case 'unilateral':
									$response['payment_status_message'] = 'Unconfirmed account payment is awaiting review by payment processor.';
									break;
								case 'authorization':
								case 'order':
								case 'upgrade':
								case 'verify':
									$response['payment_status_message'] = 'Payment is authorized but not cleared.';
									break;
							}
						}

						break;
				}
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
