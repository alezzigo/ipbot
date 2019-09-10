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
 * Process balance payments
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processBalance($parameters) {
		$response = array();
		// ..
		return $response;
	}

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
		$response = false;

		if (
			!empty($parameters['transaction_method']) &&
			strlen($parameters['transaction_method']) > 1 &&
			method_exists($this, ($method = '_processTransaction' . $parameters['transaction_method']))
		) {
			$this->$method($parameters);
			$response = true;
		}

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
				'interval_type',
				'interval_value',
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
				'subscription_id',
				'transaction_charset',
				'transaction_date',
				'transaction_method',
				'transaction_processed',
				'transaction_processing',
				'transaction_raw',
				'transaction_token',
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
 * @return void
 */
	protected function _processTransactionMiscellaneous($parameters) {
		// ..
		return;
	}

/**
 * Process payment completed transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionPaymentCompleted($parameters) {
		if (!empty($parameters['subscription_id'])) {
			$existingSubscription = $this->find('subscriptions', array(
				'conditions' => array(
					'id' => $parameters['subscription_id']
				),
				'fields' => array(
					'payment_attempts'
				)
			));

			if (!empty($existingSubscription['count'])) {
				$subscription = array(
					'id' => $parameters['subscription_id'],
					'payment_attempts' => 0
				);
				$this->save('subscriptions', array(
					$subscription
				));
			}
		}

		if (!empty($parameters['invoice_id'])) {
			$invoice = $this->invoice('invoices', array(
				'conditions' => array(
					'id' => $parameters['invoice_id']
				)
			));

			if (!empty($invoice['data'])) {
				$invoiceData = array(
					'id' => $parameters['invoice_id'],
					'amount_paid' => $invoice['data']['invoice']['amount_paid'] + $parameters['payment_amount']
				);

				if (
					!empty($invoice['data']['invoice']['status']) &&
					$invoice['data']['invoice']['status'] === 'unpaid' &&
					$invoiceData['amount_paid'] >= $invoice['data']['invoice']['total']
				) {
					foreach ($invoice['data']['orders'] as $order) {
						if ($order['status'] !== 'active') {
							$processingNodes = $this->find('nodes', array(
								'conditions' => array(
									'AND' => array(
										'allocated' => false,
										'OR' => array(
											'modified <' => date('Y-m-d H:i:s', strtotime('-1 minute')),
											'processing' => false
										)
									)
								),
								'fields' => array(
									'asn',
									'city',
									'country_code',
									'country_name',
									'id',
									'ip',
									'isp',
									'region'
								),
								'limit' => $order['quantity'],
								'sort' => array(
									'field' => 'id',
									'order' => 'ASC'
								)
							));

							if (
								!empty($processingNodes['count']) &&
								count($processingNodes['data']) === $order['quantity']
							) {
								$newItemData = array(
									'order_id' => $order['id'],
									'status' => 'online',
									'user_id' => $order['user_id']
								);
								$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, $order['quantity'], array(
									'processing' => true
								)));

								if ($this->save('nodes', $processingNodes['data'])) {
									$orderData = array(
										'id' => $order['id'],
										'status' => 'active'
									);

									foreach ($processingNodes['data'] as $key => $row) {
										$allocatedNodes[] = array(
											'allocated' => true,
											'id' => ($processingNodes['data'][$key]['node_id'] = $row['id']),
											'processing' => false
										);
										$processingNodes['data'][$key] += $newItemData;
										unset($processingNodes['data'][$key]['id']);
										unset($processingNodes['data'][$key]['processing']);
									}

									if (
										$this->save('nodes', $allocatedNodes) &&
										$this->save('orders', array(
											$orderData
										)) &&
										$this->save('proxies', $processingNodes['data'])
									) {
										// Send order activation email
									}
								}
							}
						}
					}

					$invoiceData['status'] = 'paid';
				}

				if (
					!empty($invoice['data']['invoice']['user_id']) &&
					$amountToApplyToBalance = max(0, min($parameters['payment_amount'], round(($invoiceData['amount_paid'] - $invoice['data']['invoice']['total']) * 100) / 100 ))
				) {
					$user = $this->find('users', array(
						'conditions' => array(
							'id' => $invoice['data']['invoice']['user_id']
						),
						'fields' => array(
							'balance'
						)
					));

					if (!empty($user['count'])) {
						$userData = array(
							'id' => $invoice['data']['invoice']['user_id'],
							'balance' => ($user['data'][0] + $amountToApplyToBalance)
						);
						$this->save('users', array(
							$userData
						));
					}
				}

				$this->save('invoices', array(
					$invoiceData
				));
			}
		}

		return;
	}

/**
 * Process payment failed transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionPaymentFailed($parameters) {
		// ..
		return;
	}

/**
 * Process payment pending transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionPaymentPending($parameters) {
		// ..
		return;
	}

/**
 * Process payment refund transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionPaymentRefunded($parameters) {
		if (!empty($parameters['invoice_id'])) {
			$invoice = $this->invoice('invoices', array(
				'conditions' => array(
					'id' => $parameters['invoice_id']
				)
			));

			if (!empty($invoice['data'])) {
				$invoiceData = array(
					'id' => $parameters['invoice_id'],
					// ..
				);
				// ..
			}
		}

		return;
	}

/**
 * Process payment reversal cancellation transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionPaymentReversalCanceled($parameters) {
		// ..
		return;
	}

/**
 * Process payment reversal transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionPaymentReversed($parameters) {
		// ..
		return;
	}

/**
 * Process subscription canceled transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionSubscriptionCanceled($parameters) {
		$subscription = array(
			'id' => $parameters['subscription_id'],
			'status' => 'canceled'
		);

		if (
			$this->save('subscriptions', array(
				$subscription
			))
		) {
			// ..
		}

		return;
	}

/**
 * Process subscription created transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionSubscriptionCreated($parameters) {
		$subscription = array(
			'created' => $parameters['transaction_date'],
			'id' => $parameters['subscription_id'],
			'invoice_id' => $parameters['invoice_id'],
			'interval_type' => $parameters['interval_type'],
			'interval_value' => $parameters['interval_value'],
			'plan_id' => $parameters['plan_id'],
			'price' => $parameters['payment_amount'],
			'status' => 'active'
		);

		if (
			count($subscription) === count(array_filter($subscription)) &&
			$this->save('subscriptions', array(
				$subscription
			))
		) {
			// ..
		}

		return;
	}

/**
 * Process subscription expired transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionSubscriptionExpired($parameters) {
		// ..
		return;
	}

/**
 * Process subscription modified transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionSubscriptionModified($parameters) {
		$subscription = array(
			'id' => $parameters['subscription_id'],
			'invoice_id' => $parameters['invoice_id'],
			'interval_type' => $parameters['interval_type'],
			'interval_value' => $parameters['interval_value'],
			'plan_id' => $parameters['plan_id'],
			'price' => $parameters['payment_amount']
		);

		if (
			count($subscription) === count(array_filter($subscription)) &&
			$this->save('subscriptions', array(
				$subscription
			))
		) {
			// ..
		}

		return;
	}

/**
 * Process subscription failed transaction
 *
 * @param array $parameters
 *
 * @return void
 */
	protected function _processTransactionSubscriptionFailed($parameters) {
		if (!empty($parameters['subscription_id'])) {
			$existingSubscription = $this->find('subscriptions', array(
				'conditions' => array(
					'id' => $parameters['subscription_id']
				),
				'fields' => array(
					'payment_attempts'
				)
			));

			if (!empty($existingSubscription['count'])) {
				$subscription = array(
					'id' => $parameters['subscription_id'],
					'payment_attempts' => ($existingSubscription['data'][0] + 1)
				);

				if (
					$this->save('subscriptions', array(
						$subscription
					))
				) {
					// ..
				}
			}
		}

		return;
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
				'payment_amount' => (!empty($parameters['mc_gross']) ? $parameters['mc_gross'] : $parameters['amount3']),
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
				'subscription_id' => (!empty($parameters['subscr_id']) ? $parameters['subscr_id'] : null),
				'transaction_charset' => $this->settings['database']['charset'],
				'transaction_date' => date('Y-m-d h:i:s', strtotime((!empty($parameters['subscr_date']) ? $parameters['subscr_date'] : $parameters['payment_date']))),
				'transaction_processed' => 0,
				'transaction_raw' => json_encode($parameters),
				'transaction_token' => $parameters['verify_sign'],
				'user_id' => (!empty($itemNumberIds[2]) && is_numeric($itemNumberIds[2]) ? $itemNumberIds[2] : 0)
			);

			if (!empty($parameters['pending_reason'])) {
				$transaction['payment_status_code'] = $parameters['pending_reason'];
			}

			if (!empty($parameters['period3'])) {
				$subscriptionPeriod = explode(' ', $parameters['period3']);

				if (
					!empty($subscriptionPeriod[0]) &&
					is_numeric($subscriptionPeriod[0]) &&
					!empty($subscriptionPeriod[1]) &&
					is_string($subscriptionPeriod[1]) &&
					strlen($subscriptionPeriod[1]) === 1
				) {
					switch ($subscriptionPeriod[1]) {
						case 'D':
							$transaction['interval_type'] = 'day';
							break;
						case 'M':
							$transaction['interval_type'] = 'month';
							break;
						case 'W':
							$transaction['interval_type'] = 'week';
							break;
						case 'Y':
							$transaction['interval_type'] = 'year';
							break;
					}

					if (!empty($transaction['interval_type'])) {
						$transaction['interval_value'] = $subscriptionPeriod[0];
					}
				}
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

		if (!empty($parameters['txn_type'])) {
			switch ($parameters['txn_type']) {
				case 'subscr_cancel':
					$response = array(
						'payment_status_message' => 'Subscription canceled.',
						'transaction_method' => 'SubscriptionCanceled'
					);
					break;
				case 'subscr_eot':
				case 'recurring_payment_expired':
				case 'recurring_payment_suspended':
				case 'recurring_payment_suspended_due_to_max_failed_payment':
					$response = array(
						'payment_status_message' => 'Subscription term expired.',
						'transaction_method' => 'SubscriptionExpired'
					);
					break;
				case 'subscr_failed':
				case 'recurring_payment_skipped':
				case 'recurring_payment_failed':
					$response = array(
						'payment_status_message' => 'Subscription payment failed.',
						'transaction_method' => 'SubscriptionFailed'
					);
					break;
				case 'subscr_modify':
					$response = array(
						'payment_status_message' => 'Subscription modified.',
						'transaction_method' => 'SubscriptionModified'
					);
					break;
				case 'subscr_payment':
				case 'web_accept':
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
						case 'Blocked':
						case 'Denied':
						case 'Expired':
						case 'Failed':
						case 'Voided':
							$response = array(
								'payment_status_message' => 'Payment ' . strtolower($parameters['payment_status']) . '.',
								'transaction_method' => 'PaymentFailed'
							);
							break;
					}

					break;
				case 'subscr_signup':
					$response = array(
						'payment_status_message' => 'Subscription created.',
						'transaction_method' => 'SubscriptionCreated'
					);
					break;
				default:
					switch ($parameters['payment_status']) {
						case 'Reversed':
							$response = array(
								'payment_status_message' => 'Payment reversal pending' . (!empty($parameters['reason_code']) && $parameters['reason_code'] !== 'other' ? ' from ' . str_replace('_', ' ', $parameters['reason_code']) : '') . '.',
								'transaction_method' => 'PaymentReversed'
							);
							break;
						case 'Canceled_Reversal':
							$response = array(
								'payment_status_message' => 'Payment reversal canceled.',
								'transaction_method' => 'PaymentReversalCanceled'
							);
							break;
						case 'Refunded':
							$response = array(
								'payment_status_message' => 'Payment refunded.',
								'transaction_method' => 'PaymentRefunded'
							);
							break;
					}
					break;
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
				'text' => ($defaultMessage = 'Error processing your payment request, please try again.')
			)
		);

		if (
			!empty($parameters['data']['payment_method']) &&
			(
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
			)
		) {
			$parameters['user'] = empty($parameters['user']) ? $response['user'] : $parameters['user'];
			$response['message'] = $defaultResponse['message'];
			unset($response['redirect']);

			if (
				$parameters['data']['payment_method'] === 'balance' ||
				(
					!isset($parameters['data']['recurring']) ||
					!is_bool($parameters['data']['recurring'])
				)
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
				$method = '_process' . str_replace(' ', '', ucwords(str_replace('_', ' ', $parameters['data']['payment_method'])));

				if (method_exists($this, $method)) {
					if (
						$parameters['data']['payment_method'] === 'balance' &&
						$parameters['data']['billing_amount'] > $parameters['user']['balance']
					) {
						$response['message']['text'] = 'Payment amount exceeds your account balance, please enter an amount less than or equal to ' . $this->settings['billing']['currency_symbol'] . $parameters['user']['balance'] . ' ' . $this->settings['billing']['currency_name'] . '.';
					} else {
						$response['message']['text'] = 'Invalid invoice ID, please try again.';
						$invoice = $this->invoice('invoices', array(
							'conditions' => array(
								'id' => $parameters['data']['invoice_id']
							)
						));

						if (
							!empty($invoice['data']) &&
							$parameters['user']['id'] === $invoice['data']['invoice']['user_id']
						) {
							$response['message']['text'] = $defaultMessage;
							$parameters['data'] = array_merge($parameters['data'], $invoice['data']);
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
