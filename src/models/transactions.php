<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class TransactionsModel extends AppModel {

	/**
	 * Format credit card transaction notification
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _formatCreditCardNotification($parameters) {
			$response = array();
			// ..
			return $response;
		}

	/**
	 * Format PayPal transaction notification
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _formatPaypalNotification($parameters) {
			$parameters = $response = array();
			$rawParameters = $parameters['input'] = file_get_contents('php://input');
			$splitRawParameters = explode('&', $rawParameters);

			foreach ($splitRawParameters as $rawParameter) {
				$splitRawParameter = explode('=', $rawParameter);

				if (!empty($splitRawParameter[1])) {
					$parameters[$splitRawParameter[0]] = rawurldecode(utf8_encode($splitRawParameter[1]));
				}
			}

			if ($this->_validatePaypalNotification($parameters)) {
				if (
					!empty($parameters['charset']) &&
					$parameters['charset'] !== 'UTF-8'
				) {
					$parameterKeys = array_keys($parameters);
					$parameterValues = mb_convert_encoding(implode('&', $parameters), 'UTF-8', $parameters['charset']);
					$parameters = array_combine($parameterKeys, explode('&', $parameterValues));
				}

				$foreignIds = explode('_', $parameters['item_number']);
				$transactionData = array(
					array(
						'billing_address1' => $parameters['address_street'],
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
						'id' => uniqid() . time(),
						'initial_invoice_id' => ($invoiceId = (!empty($foreignIds[0]) && is_numeric($foreignIds[0]) ? $foreignIds[0] : 0)),
						'invoice_id' => $invoiceId,
						'parent_transaction_id' => (!empty($parameters['parent_txn_id']) ? $parameters['parent_txn_id'] : null),
						'payment_amount' => (!empty($parameters['mc_gross']) ? $parameters['mc_gross'] : $parameters['amount3']),
						'payment_currency' => $parameters['mc_currency'],
						'payment_external_fee' => $parameters['mc_fee'],
						'payment_method_id' => 'paypal',
						'payment_shipping_amount' => $parameters['shipping'],
						'payment_status' => strtolower($parameters['payment_status']),
						'payment_tax_amount' => $parameters['tax'],
						'payment_transaction_id' => (!empty($parameters['txn_id']) ? $parameters['txn_id'] : uniqid() . time()),
						'plan_id' => (!empty($foreignIds[1]) && is_numeric($foreignIds[1]) ? $foreignIds[1] : 0),
						'provider_country_code' => $parameters['residence_country'],
						'provider_email' => $parameters['receiver_email'],
						'provider_id' => $parameters['receiver_id'],
						'sandbox' => (!empty($parameters['test_ipn']) ? true : false),
						'subscription_id' => (!empty($parameters['subscr_id']) ? $parameters['subscr_id'] : null),
						'transaction_charset' => (!empty($parameters['charset']) ? $parameters['charset'] : $this->settings['database']['charset']),
						'transaction_date' => date('Y-m-d H:i:s', strtotime((!empty($parameters['subscr_date']) ? $parameters['subscr_date'] : $parameters['payment_date']))),
						'transaction_raw' => $rawParameters,
						'transaction_token' => $parameters['verify_sign'],
						'user_id' => (!empty($foreignIds[2]) && is_numeric($foreignIds[2]) ? $foreignIds[2] : 0)
					)
				);

				if (!empty($parameters['pending_reason'])) {
					$transactionData[0]['payment_status_code'] = $parameters['pending_reason'];
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
								$transactionData[0]['interval_type'] = 'day';
								break;
							case 'M':
								$transactionData[0]['interval_type'] = 'month';
								break;
							case 'W':
								$transactionData[0]['interval_type'] = 'week';
								break;
							case 'Y':
								$transactionData[0]['interval_type'] = 'year';
								break;
						}

						if (!empty($transactionData[0]['interval_type'])) {
							$transactionData[0]['interval_value'] = $subscriptionPeriod[0];
						}
					}
				}

				if (!empty($parameters['reason_code'])) {
					$transactionData[0]['payment_status_code'] = $parameters['reason_code'];
				}

				$transactionData[0] = array_merge($transactionData[0], $this->_retrievePayPalTransactionMethod($parameters));
				$transactionData[0]['invoice_id'] = $this->_retrieveTransactionInvoiceId($transactionData[0]);
				$response = $transactionData;
			}

			return $response;
		}

	/**
	 * Process balance payments
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _processBalance($parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Error processing payment from account balance, please try again.'
				)
			);
			$transactionData = array(
				array(
					'customer_email' => $parameters['user']['email'],
					'id' => uniqid() . time(),
					'initial_invoice_id' => $parameters['data']['invoice']['id'],
					'invoice_id' => $parameters['data']['invoice']['id'],
					'payment_amount' => $parameters['data']['billing_amount'],
					'payment_currency' => $this->settings['billing']['currency'],
					'payment_method_id' => 'balance',
					'payment_status' => 'completed',
					'payment_status_message' => 'Payment successful.',
					'payment_transaction_id' => uniqid() . time(),
					'plan_id' => $parameters['data']['plan']['id'],
					'processed' => true,
					'transaction_charset' => $this->settings['database']['charset'],
					'transaction_date' => date('Y-m-d H:i:s', time()),
					'transaction_method' => 'PaymentCompleted',
					'user_id' => $parameters['user']['id']
				)
			);

			if ($this->save('transactions', $transactionData)) {
				$user = $this->fetch('users', array(
					'conditions' => array(
						'id' => $transactionData[0]['user_id']
					),
					'fields' => array(
						'balance'
					)
				));

				if (!empty($user['count'])) {
					$userData = array(
						array(
							'id' => $transactionData[0]['user_id'],
							'balance' => (round(($user['data'][0] - $parameters['data']['billing_amount']) * 100) / 100)
						)
					);

					if ($this->save('users', $userData)) {
						$this->processTransaction($transactionData[0]);
						$response = array(
							'data' => $transactionData[0],
							'message' => array(
								'status' => 'success',
								'text' => 'Payment from account balance successful for <a href="' . $this->settings['base_url'] . 'invoices/' . $transactionData[0]['invoice_id'] . '">invoice #' . $transactionData[0]['invoice_id'] . '</a>.'
							)
						);
					}
				}
			}

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
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Error processing credit card transaction, please check your details and try again.'
				)
			);

			if (!empty($parameters['data'])) {
				if (
					empty($parameters['data']['billing_cc_name']) ||
					!is_string($parameters['data']['billing_cc_name'])
				) {
					$response['data']['errors']['billing_cc_name'] = 'Invalid credit card name.';
				}

				if (
					empty($parameters['data']['billing_cc_number']) ||
					!is_numeric($parameters['data']['billing_cc_number'])
				) {
					$response['data']['errors']['billing_cc_number'] = 'Invalid credit card number.';
				}

				if (
					empty($parameters['data']['billing_cc_month']) ||
					!in_array($parameters['data']['billing_cc_month'], range(1, 12))
				) {
					$response['data']['errors']['billing_cc_security'] = 'Invalid credit card month.';
				}

				if (
					empty($parameters['data']['billing_cc_year']) ||
					!is_numeric($parameters['data']['billing_cc_year']) ||
					strlen($parameters['data']['billing_cc_year']) !== 4
				) {
					$response['data']['errors']['billing_cc_security'] = 'Invalid credit card year.';
				}

				if (
					!empty($parameters['data']['billing_cc_month']) &&
					!empty($parameters['data']['billing_cc_year']) &&
					($parameters['data']['billing_cc_year'] . $parameters['data']['billing_cc_month']) < date('Ym', time())
				) {
					$response['data']['errors']['billing_cc_security'] = 'Credit card is expired.';
				}

				if (
					empty($parameters['data']['billing_name']) ||
					!is_string($parameters['data']['billing_name'])
				) {
					$response['data']['errors']['billing_name'] = 'Invalid billing name.';
				}

				if (
					empty($parameters['data']['billing_address1']) ||
					!is_string($parameters['data']['billing_address1'])
				) {
					$response['data']['errors']['billing_address1'] = 'Invalid billing address.';
				}

				if (
					empty($parameters['data']['state']) ||
					!is_string($parameters['data']['state'])
				) {
					$response['data']['errors']['state'] = 'Invalid billing state/region.';
				}

				if (
					empty($parameters['data']['city']) ||
					!is_string($parameters['data']['city'])
				) {
					$response['data']['errors']['city'] = 'Invalid billing city.';
				}

				if (
					empty($parameters['data']['zip']) ||
					!is_string($parameters['data']['zip'])
				) {
					$response['data']['errors']['zip'] = 'Invalid billing zip code.';
				}

				if (
					empty($parameters['data']['country']) ||
					!is_string($parameters['data']['country'])
				) {
					$response['data']['errors']['country'] = 'Invalid billing country.';
				}

				if (empty($response['data']['errors'])) {
					// ..
				}
			}

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
				'cancel_return' => $_SERVER['HTTP_ORIGIN'] . '#payment',
				'cmd' => '_xclick',
				'item_name' => $this->settings['site_name'] . ' Plan #' . $parameters['data']['plan']['id'],
				'item_number' => $parameters['data']['invoice']['id'] . '_' . $parameters['data']['plan']['id'] . '_' . $parameters['data']['invoice']['user_id'],
				'notify_url' => $_SERVER['HTTP_ORIGIN'] . $_SERVER['REDIRECT_URL'],
				'return' => $_SERVER['HTTP_ORIGIN'],
				'src' => '1'
			);

			if (
				!empty($parameters['data']['billing_recurring']) &&
				($order = $parameters['data']['orders'][0])
			) {
				$parameters['request'] = array_merge($parameters['request'], array(
					'a3' => $parameters['data']['billing_amount'],
					'cmd' => $parameters['request']['cmd'] . '-subscriptions',
					'p3' => $parameters['data']['orders'][0]['interval_value'],
					't3' => ucwords(substr($order['interval_type'], 0, 1))
				));

				if (
					(
						!empty($parameters['data']['invoice']['amount_due_pending']) &&
						$parameters['data']['billing_amount'] == $parameters['data']['invoice']['amount_due_pending'] &&
						$parameters['data']['billing_amount'] != ($total = $parameters['data']['invoice']['total_pending'])
					) ||
					(
						!empty($parameters['data']['invoice']['amount_due']) &&
						empty($parameters['data']['invoice']['amount_due_pending']) &&
						$parameters['data']['billing_amount'] == $parameters['data']['invoice']['amount_due'] &&
						$parameters['data']['billing_amount'] != ($total = $parameters['data']['invoice']['total'])
					)
				) {
					$parameters['request'] = array_merge($parameters['request'], array(
						'a1' => $parameters['request']['a3'],
						'a3' => $total,
						'p1' => $parameters['request']['p3'],
						't1' => $parameters['request']['t3']
					));
				}
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
	 * Process transactions
	 *
	 * @return array $response
	 */
		protected function _processTransactions() {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'There aren\'t any new transactions to process, please try again later.'
				)
			);
			$transactionsToProcess = $this->fetch('transactions', array(
				'conditions' => array(
					'AND' => array(
						'processed' => false,
						'OR' => array(
							'modified <' => date('Y-m-d H:i:s', strtotime('-1 minute')),
							'processing' => false
						)
					)
				),
				'fields' => array(
					'billing_address1',
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
					'initial_invoice_id',
					'interval_type',
					'interval_value',
					'invoice_id',
					'parent_transaction_id',
					'payment_amount',
					'payment_currency',
					'payment_external_fee',
					'payment_method_id',
					'payment_shipping_amount',
					'payment_status',
					'payment_status_code',
					'payment_status_message',
					'payment_tax_amount',
					'payment_transaction_id',
					'plan_id',
					'processed',
					'processing',
					'provider_country_code',
					'provider_email',
					'provider_id',
					'subscription_id',
					'transaction_charset',
					'transaction_date',
					'transaction_method',
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
						'processing' => true
					);
				}

				if ($this->save('transactions', $transactions)) {
					$processedTransactions = array();

					foreach($transactionsToProcess['data'] as $transaction) {
						$processed = $this->processTransaction($transaction);
						$processedTransactions[] = array(
							'processed' => $processed,
							'processing' => !$processed
						);
					}

					$transactions = array_replace_recursive($transactions, $processedTransactions);

					if ($this->save('transactions', $transactions)) {
						$response = array(
							'data' => $transactionsToProcess,
							'message' => array(
								'status' => 'success',
								'text' => $transactionsToProcess['count'] . ' transaction' . ($transactionsToProcess['count'] !== 1 ? 's' : '') . ' processed successfully.'
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
	 * @return void
	 */
		protected function _processTransactionMiscellaneous() {
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
			$invoiceItemData = $invoiceOrderData = $pendingTransactions = array();
			$invoiceTotalPaid = false;

			if (!empty($parameters['subscription_id'])) {
				$existingSubscription = $this->fetch('subscriptions', array(
					'conditions' => array(
						'id' => $parameters['subscription_id']
					),
					'fields' => array(
						'payment_attempts'
					)
				));

				if (!empty($existingSubscription['count'])) {
					$subscriptionData = array(
						array(
							'id' => $parameters['subscription_id'],
							'payment_attempts' => 0
						)
					);
					$this->save('subscriptions', $subscriptionData);
				}
			}

			if (!empty($parameters['invoice_id'])) {
				$invoice = $this->_call('invoices', array(
					'methodName' => 'invoice',
					'methodParameters' => array(
						'invoices',
						array(
							'conditions' => array(
								'id' => $parameters['invoice_id'],
								'payable' => true
							)
						),
						true
					)
				));
				$invoiceWarningLevel = $invoice['data']['invoice']['warning_level'];

				if (!empty($invoice['data'])) {
					$invoiceData = array(
						array(
							'amount_paid' => $invoice['data']['invoice']['amount_paid'] + $parameters['payment_amount'],
							'id' => $invoice['data']['invoice']['id']
						)
					);
					$total = !empty($invoice['data']['invoice']['total_pending']) ? $invoice['data']['invoice']['total_pending'] : $invoice['data']['invoice']['total'];

					if (is_numeric($invoice['data']['invoice']['remainder_pending'])) {
						$invoiceData[0]['remainder_pending'] = max(0, round(($invoice['data']['invoice']['remainder_pending'] - $parameters['payment_amount']) * 100) / 100);
					}

					if (
						!empty($invoice['data']['invoice']['user_id']) &&
						!empty($parameters['user']) &&
						$amountToApplyToBalance = max(0, min($parameters['payment_amount'], round(($invoiceData[0]['amount_paid'] - $total) * 100) / 100))
					) {
						if (empty($invoice['data']['orders'])) {
							$amountToApplyToBalance = $parameters['payment_amount'];
						}

						$userData = array(
							array(
								'id' => $parameters['user']['id'],
								'balance' => ($parameters['user']['balance'] + $amountToApplyToBalance)
							)
						);
						$this->save('users', $userData);
					}

					if (
						(
							$invoiceData[0]['amount_paid'] >= $total ||
							(
								isset($invoiceData[0]['remainder_pending']) &&
								$invoiceData[0]['remainder_pending'] === 0
							)
						) &&
						$this->delete('invoice_items', array(
							'invoice_id' => $invoiceData[0]['id']
						))
					) {
						$invoiceData = array(
							array_merge($invoiceData[0], array(
								'remainder_pending' => null,
								'shipping' => (!empty($invoice['data']['invoice']['shipping_pending']) ? $invoice['data']['invoice']['shipping_pending'] : $invoice['data']['invoice']['shipping']),
								'shipping_pending' => null,
								'status' => 'paid',
								'subtotal' => (!empty($invoice['data']['invoice']['subtotal_pending']) ? $invoice['data']['invoice']['subtotal_pending'] : $invoice['data']['invoice']['subtotal']),
								'subtotal_pending' => null,
								'tax' => (!empty($invoice['data']['invoice']['tax_pending']) ? $invoice['data']['invoice']['tax_pending'] : $invoice['data']['invoice']['tax']),
								'tax_pending' => null,
								'total' => (!empty($invoice['data']['invoice']['total_pending']) ? $invoice['data']['invoice']['total_pending'] : $invoice['data']['invoice']['total']),
								'total_pending' => null,
								'warning_level' => 0
							))
						);

						foreach ($invoice['data']['orders'] as $orderKey => $order) {
							if (
								(
									is_numeric($order['quantity_pending']) &&
									$order['quantity_pending'] > $order['quantity_active'] &&
									($quantity = ($order['quantity_pending'] - $order['quantity_active']))
								) ||
								(
									$order['status'] !== 'active' &&
									($quantity = $order['quantity'])
								)
							) {
								$quantity = (!empty($order['quantity_pending']) ? $order['quantity_pending'] : $order['quantity']);
								$orderData = array(
									array(
										'currency' => $order['currency'],
										'id' => $order['id'],
										'interval_type' => (!empty($order['interval_type_pending']) ? $order['interval_type_pending'] : $order['interval_type']),
										'interval_type_pending' => null,
										'interval_value' => (!empty($order['interval_value_pending']) ? $order['interval_value_pending'] : $order['interval_value']),
										'interval_value_pending' => null,
										'ip_version' => $order['ip_version'],
										'previous_action' => null,
										'price' => ($price = (!empty($order['price_pending']) ? $order['price_pending'] : $order['price'])),
										'price_active' => min($order['price_active'] + $parameters['payment_amount'], $price),
										'price_pending' => null,
										'quantity' => $quantity,
										'quantity_active' => $order['quantity_active'],
										'quantity_allocated' => $order['quantity_allocated'],
										'quantity_pending' => null,
										'shipping' => (!empty($order['shipping_pending']) ? $order['shipping_pending'] : $order['shipping']),
										'shipping_pending' => null,
										'status' => 'active',
										'tax' => (!empty($order['tax_pending']) ? $order['tax_pending'] : $order['tax']),
										'tax_pending' => null,
										'user_id' => $order['user_id']
									)
								);
								$invoice['data']['orders'][$orderKey] = array_merge($invoice['data']['orders'][$orderKey], $orderData[0]);
								$invoiceItemData[] = array_merge(array_intersect_key($orderData[0], array(
									'currency' => true,
									'interval_type' => true,
									'interval_value' => true,
									'price' => true,
									'quantity' => true
								)), array(
									'invoice_id' => $invoiceData[0]['id'],
									'order_id' => $order['id'],
									'name' => $order['name']
								));

								if (is_numeric($order['quantity_pending'])) {
									$action = $orderData[0]['previous_action'] = ($order['quantity_pending'] > $order['quantity_active'] ? 'upgrade' : 'downgrade');
									$pendingTransactions[] = array(
										'customer_email' => $parameters['user']['email'],
										'details' => 'Order ' . $action . ' successful for order <a href="' . $this->settings['base_url'] . 'orders/' . $order['id'] . '">#' . $order['id'] . '</a>.<br>' . $order['quantity'] . ' ' . $order['name'] . ' to ' . $order['quantity_pending'] . ' ' . $order['name'] . '<br>' . number_format($order['price'], 2, '.', '') . ' ' . $order['currency'] . ' for ' . $order['interval_value'] . ' ' . $order['interval_type'] . ($order['interval_value'] !== 1 ? 's' : '') . ' to ' . number_format($order['price_pending'], 2, '.', '') . ' ' . $order['currency'] . ' for ' . $order['interval_value_pending'] . ' ' . $order['interval_type_pending'] . ($order['interval_value_pending'] !== 1 ? 's' : ''),
										'id' => uniqid() . time(),
										'initial_invoice_id' => $invoiceData[0]['id'],
										'invoice_id' => $invoiceData[0]['id'],
										'payment_amount' => 0,
										'payment_currency' => $this->settings['billing']['currency'],
										'payment_status' => 'completed',
										'payment_status_message' => 'Order ' . $action . ' successful.',
										'processed' => true,
										'transaction_charset' => $this->settings['database']['charset'],
										'transaction_date' => date('Y-m-d H:i:s', strtotime('+1 second')),
										'transaction_method' => 'PaymentCompleted',
										'user_id' => $parameters['user']['id']
									);
								}

								if (
									$this->save('orders', $orderData) &&
									$this->save('transactions', $pendingTransactions)
								) {
									$actionData = array(
										array(
											'chunks' => ($chunks = ceil(($itemCount = (abs($order['quantity'] - (integer) $order['quantity_pending']))) / 10000)),
											'encoded_parameters' => json_encode(array(
												'action' => 'allocate',
												'data' => array(
													'order' => $orderData[0]
												),
												'item_count' => $itemCount,
												'table' => ($itemCount === 1 ? 'proxy' : 'proxies')
											)),
											'foreign_key' => 'order_id',
											'foreign_value' => $order['id'],
											'processed' => ($processOrder = ($chunks == 1)),
											'progress' => ($processOrder ? 100 : 0),
											'user_id' => $parameters['user']['id']
										)
									);

									if ($processOrder) {
										$this->_call('proxies', array(
											'methodName' => 'allocate',
											'methodParameters' => array(
												'proxies',
												array(
													'data' => array(
														'order' => $orderData[0]
													)
												)
											)
										));
									}

									$this->save('actions', $actionData);
								}
							}
						}

						$invoiceOrders = $this->fetch('invoice_orders', array(
							'conditions' => array(
								'invoice_id' => array_unique(array_filter(array(
									$invoice['data']['invoice']['id'],
									$invoice['data']['invoice']['initial_invoice_id'],
									$invoice['data']['invoice']['merged_invoice_id']
								)))
							),
							'fields' => array(
								'id',
								'initial_invoice_id',
								'invoice_id',
								'order_id'
							)
						));

						if (!empty($invoiceOrders['count'])) {
							$invoiceOrderData = array_replace_recursive($invoiceOrders['data'], array_fill(0, $invoiceOrders['count'], array(
								'initial_invoice_id' => null
							)));
						}

						$invoiceTotalPaid = true;
					}

					if (
						$invoiceTotalPaid &&
						empty($invoiceItemData) &&
						count($invoice['data']['orders']) === 1 &&
						($order = $invoice['data']['orders'][0]) &&
						$order['quantity_active'] === $order['quantity_pending']
					) {
						$invoiceItemData[] = array_merge(array_intersect_key($order, array(
							'interval_type' => true,
							'interval_value' => true,
							'price' => true,
							'quantity' => true
						)), array(
							'invoice_id' => $invoiceData[0]['id'],
							'order_id' => $order['id'],
							'name' => $order['name']
						));
					}

					if (
						$this->save('invoices', $invoiceData) &&
						$this->save('invoice_items', $invoiceItemData) &&
						$this->save('invoice_orders', $invoiceOrderData)
					) {
						$invoice['data']['invoice'] = array_merge($invoice['data']['invoice'], $invoiceData[0]);
						$invoiceData = array();

						if ($invoiceTotalPaid) {
							$additionalDueInvoices = $this->_call('invoices', array(
								'methodName' => 'retrieveDueInvoices',
								'methodParameters' => array(
									$invoice['data']['invoice']
								)
							));
							$intervalType = $invoice['data']['orders'][0]['interval_type'];

							if (!empty($additionalDueInvoices)) {
								$invoiceData = array_replace_recursive($additionalDueInvoices, array_fill(0, count($additionalDueInvoices), array(
									'due' => null,
									'warning_level' => 5
								)));
							}

							if (
								in_array($intervalType, array(
									'day',
									'month',
									'week',
									'year'
								)) &&
								!empty($intervalValue = $invoice['data']['orders'][0]['interval_value'])
							) {
								if (
									empty($additionalDueInvoices) ||
									(
										!empty($additionalDueInvoices[0]['warning_level']) &&
										$additionalDueInvoices[0]['warning_level'] === 5
									)
								) {
									if (
										$invoiceWarningLevel === 5 ||
										empty($invoice['data']['invoice']['initial_invoice_id'])
									) {
										$invoice['data']['invoice']['due'] = null;
									}

									$invoiceData[] = array(
										'cart_items' => $invoice['data']['invoice']['cart_items'],
										'currency' => $invoice['data']['invoice']['currency'],
										'due' => date('Y-m-d H:i:s', strtotime($invoice['data']['invoice']['due'] . ' +' . $intervalValue . ' ' . $intervalType)),
										'initial_invoice_id' => !empty($invoice['data']['invoice']['initial_invoice_id']) ? $invoice['data']['invoice']['initial_invoice_id'] : $invoice['data']['invoice']['id'],
										'session_id' => $invoice['data']['invoice']['session_id'],
										'shipping' => $invoice['data']['invoice']['shipping'],
										'status' => 'unpaid',
										'subtotal' => $invoice['data']['invoice']['subtotal'],
										'tax' => $invoice['data']['invoice']['tax'],
										'total' => $invoice['data']['invoice']['total'],
										'user_id' => $invoice['data']['invoice']['user_id'],
										'warning_level' => 0
									);
								} else {
									unset($invoiceData[0]);
								}
							}

							if (!empty($invoiceData)) {
								$this->save('invoices', $invoiceData);
							}
						}

						$invoice['data']['transactions'][] = $transaction = array_merge($parameters, array(
							'payment_method' => $this->_retrieveTransactionPaymentMethod($parameters['payment_method_id'])
						));
						$invoice['data'] = $this->_call('invoices', array(
							'methodName' => 'calculateInvoicePaymentDetails',
							'methodParameters' => array(
								$invoice['data']
							)
						));

						if ($parameters['payment_amount'] > 0) {
							$mailParameters = array(
								'from' => $this->settings['from_email'],
								'subject' => 'Invoice #' . $invoice['data']['invoice']['id'] . ' payment confirmation',
								'template' => array(
									'name' => 'payment_successful',
									'parameters' => array(
										'invoice' => $invoice['data']['invoice'],
										'transaction' => array_merge($transaction, array(
											'amount_applied_to_balance' => $amountToApplyToBalance
										)),
										'transactions' => $invoice['data']['transactions'],
										'user' => $parameters['user']
									)
								),
								'to' => $parameters['user']['email']
							);
							$this->_sendMail($mailParameters);
						}
					}
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
			if (
				!empty($parameters['invoice_id']) &&
				!empty($parameters['user'])
			) {
				$invoice = $this->_call('invoices', array(
					'methodName' => 'invoice',
					'methodParameters' => array(
						'invoices',
						array(
							'conditions' => array(
								'id' => $parameters['invoice_id']
							)
						),
						true
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Invoice #' . $invoice['data']['invoice']['id'] . ' payment failed',
						'template' => array(
							'name' => 'payment_failed',
							'parameters' => array(
								'invoice' => $invoice['data']['invoice'],
								'transaction' => array_merge($parameters, array(
									'payment_method' => $this->_retrieveTransactionPaymentMethod($parameters['payment_method_id'])
								)),
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
			}

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
			if (
				!empty($parameters['invoice_id']) &&
				!empty($parameters['user'])
			) {
				$invoice = $this->_call('invoices', array(
					'methodName' => 'invoice',
					'methodParameters' => array(
						'invoices',
						array(
							'conditions' => array(
								'id' => $parameters['invoice_id']
							)
						),
						true
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Invoice #' . $invoice['data']['invoice']['id'] . ' payment pending',
						'template' => array(
							'name' => 'payment_pending',
							'parameters' => array(
								'invoice' => $invoice['data']['invoice'],
								'transaction' => array_merge($parameters, array(
									'payment_method' => $this->_retrieveTransactionPaymentMethod($parameters['payment_method_id'])
								)),
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
			}

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
			$invoices = $invoiceData = $invoiceDeductions = $orderMergeData = $processedInvoiceIds = $pendingInvoiceOrders = $transactionData = $unpaidInvoiceIds = $userData = array();

			if (
				!empty($parameters['invoice_id']) &&
				!empty($parameters['user'])
			) {
				$invoice = $this->_call('invoices', array(
					'methodName' => 'invoice',
					'methodParameters' => array(
						'invoices',
						array(
							'conditions' => array(
								'id' => $parameters['invoice_id']
							)
						)
					)
				));

				if (!empty($invoice['data'])) {
					$invoiceDeductions = $this->_call('invoices', array(
						'methodName' => 'calculateDeductionsFromInvoice',
						'methodParameters' => array(
							$invoice['data']['invoice'],
							$parameters['payment_amount']
						)
					));
					$amountToDeductFromBalance = min(0, $invoiceDeductions['remainder']);

					foreach ($invoiceDeductions as $key => $invoiceDeduction) {
						$processedInvoiceIds[] = $invoiceDeduction['id'];
					}

					if ($amountToDeductFromBalance < 0) {
						$invoiceDeductions['balance'] = array(
							'amount_deducted' => ($amountDeductedFromBalance = round(max(($parameters['user']['balance'] * -1), $amountToDeductFromBalance) * 100) / 100),
						);
						$invoiceDeductions['remainder'] = $amountToDeductFromBalance - $amountDeductedFromBalance;
						$userData = array(
							array(
								'id' => $parameters['user']['id'],
								'balance' => max(0, $amountToRefundExceedingBalance = round(($parameters['user']['balance'] + $amountToDeductFromBalance) * 100) / 100)
							)
						);

						if ($amountToRefundExceedingBalance < 0) {
							$balanceTransactions = $this->fetch('transactions', array(
								'conditions' => array(
									'payment_method_id' => 'balance',
									'processed' => true,
									'processing' => false,
									'transaction_method' => 'PaymentCompleted',
									'user_id' => $parameters['user']['id'],
									'NOT' => array(
										'invoice_id' => $processedInvoiceIds
									)
								),
								'fields' => array(
									'id',
									'invoice_id',
									'payment_amount',
									'payment_transaction_id',
									'plan_id'
								),
								'sort' => array(
									'field' => 'created',
									'order' => 'DESC'
								)
							));

							if (!empty($balanceTransactions['count'])) {
								foreach ($balanceTransactions['data'] as $balanceTransaction) {
									if (!empty($balanceTransaction['invoice_id'])) {
										$invoice = $this->_call('invoices', array(
											'methodName' => 'invoice',
											'methodParameters' => array(
												'invoices',
												array(
													'conditions' => array(
														'id' => $balanceTransaction['invoice_id']
													)
												)
											)
										));

										if (
											!empty($invoice['data']) &&
											$amountToRefundExceedingBalance < 0
										) {
											$invoiceDeductions = $this->_call('invoices', array(
												'methodName' => 'calculateDeductionsFromInvoice',
												'methodParameters' => array(
													$invoice['data']['invoice'],
													max(min(($balanceTransaction['payment_amount'] * -1), $amountToRefundExceedingBalance), $amountToRefundExceedingBalance),
													$invoiceDeductions
												)
											));
											$amountToRefundExceedingBalance = min(0, $invoiceDeductions['remainder']);
										}
									}
								}
							}
						}
					}

					foreach ($invoiceDeductions as $key => $invoiceDeduction) {
						if (
							!isset($invoiceDeduction['amount_deducted']) ||
							$invoiceDeduction['amount_deducted'] >= 0
						) {
							unset($invoiceDeductions[$key]);
						} else {
							if (!empty($invoiceDeduction['id'])) {
								$invoiceDeductionData = array(
									'amount_paid' => round(($invoiceDeduction['amount_paid'] + $invoiceDeduction['amount_deducted']) * 100) / 100,
									'id' => $invoiceDeduction['id'],
									'payable' => true,
									'remainder_pending' => $invoiceDeduction['remainder_pending']
								);

								if (!empty($invoiceDeduction['status'])) {
									$invoiceDeductionData['status'] = $invoiceDeduction['status'];
								}

								$invoiceData[$invoiceDeduction['id']] = $invoiceDeductionData;
								$orderMergeParameters = array(
									'conditions' => array(
										'amount_merged >' => 0,
										'initial_invoice_id' => $invoiceDeduction['id']
									),
									'fields' => array(
										'amount_merged',
										'id',
										'initial_invoice_id'
									),
									'sort' => array(
										'field' => 'created',
										'order' => 'ASC'
									)
								);
								$orderMerges = $this->fetch('order_merges', $orderMergeParameters);

								if (!empty($orderMerges['count'])) {
									$amountDeducted = $invoiceDeduction['amount_deducted'];

									foreach ($orderMerges['data'] as $orderMerge) {
										if ($amountDeducted === 0) {
											break;
										}

										$amountDeductedFromAmountMerged = $amountDeducted + $orderMerge['amount_merged'];
										$amountDeducted = min(0, $amountDeductedFromAmountMerged);
										$orderMergeData[] = array(
											'amount_merged' => max(0, $amountDeductedFromAmountMerged),
											'id' => $orderMerge['id']
										);
									}
								}
							}

							$transactionData[] = array(
								'customer_email' => $parameters['user']['email'],
								'id' => uniqid() . time(),
								'invoice_id' => $invoiceDeduction['id'],
								'parent_transaction_id' => $parameters['parent_transaction_id'],
								'payment_amount' => $invoiceDeduction['amount_deducted'],
								'payment_currency' => $this->settings['billing']['currency'],
								'payment_method_id' => $parameters['payment_method_id'],
								'payment_status' => 'completed',
								'payment_status_message' => 'Payment refunded.',
								'payment_transaction_id' => $parameters['payment_transaction_id'],
								'plan_id' => $parameters['plan_id'],
								'processed' => true,
								'transaction_charset' => $this->settings['database']['charset'],
								'transaction_date' => date('Y-m-d H:i:s', time()),
								'transaction_method' => 'PaymentRefundProcessed',
								'user_id' => $parameters['user']['id']
							);
						}

						if (!empty($invoiceDeduction['id'])) {
							$unpaidInvoiceIds[] = $invoiceDeduction['id'];
							$invoiceData[$invoiceDeduction['id']]['warning_level'] = 5;
							$invoiceOrders = $this->_call('invoices', array(
								'methodName' => 'retrieveInvoiceOrders',
								'methodParameters' => array(
									$invoiceDeduction
								)
							));

							foreach ($invoiceOrders as $invoiceOrder) {
								if (empty($pendingInvoiceOrders[$invoiceOrder['id']])) {
									$pendingInvoiceOrders[$invoiceOrder['id']] = $invoiceOrder;
								}

								if ($invoiceDeduction['amount_deducted'] < 0) {
									$priceActive = $pendingInvoiceOrders[$invoiceOrder['id']]['price_active'];
									$pendingInvoiceOrders[$invoiceOrder['id']]['price_active'] = max(0, round(($pendingInvoiceOrders[$invoiceOrder['id']]['price_active'] + $invoiceDeduction['amount_deducted']) * 100) / 100);
									$invoiceDeduction['amount_deducted'] = min(0, $invoiceDeduction['amount_deducted'] + $priceActive);

									if (
										$pendingInvoiceOrders[$invoiceOrder['id']]['price_active'] === 0 &&
										$pendingInvoiceOrders[$invoiceOrder['id']]['status'] == 'active'
									) {
										$pendingInvoiceOrders[$invoiceOrder['id']]['quantity_active'] = 0;
										$pendingInvoiceOrders[$invoiceOrder['id']]['status'] = 'pending';
										$actionData = array(
											array(
												'chunks' => ($chunks = ceil($invoiceOrder['quantity_active'] / 10000)),
												'encoded_parameters' => json_encode(array(
													'action' => 'remove',
													'data' => array(
														'order' => $invoiceOrder
													),
													'item_count' => ($itemCount = $invoiceOrder['quantity_active']),
													'table' => 'proxies'
												)),
												'foreign_key' => 'order_id',
												'foreign_value' => $invoiceOrder['id'],
												'processed' => ($processOrder = ($chunks == 1)),
												'progress' => ($processOrder ? 100 : 0),
												'user_id' => $parameters['user']['id']
											)
										);

										if ($processOrder) {
											$this->_call('proxies', array(
												'methodName' => 'remove',
												'methodParameters' => array(
													'proxies',
													array(
														'data' => array(
															'order' => $invoiceOrder
														)
													)
												)
											));
										}

										$this->save('actions', $actionData);
									}
								}
							}
						}
					}

					if (
						(
							empty($unpaidInvoiceIds) ||
							$this->delete('invoice_items', array(
								'invoice_id' => $unpaidInvoiceIds
							))
						) &&
						$this->save('invoices', array_values($invoiceData)) &&
						$this->save('orders', array_values($pendingInvoiceOrders)) &&
						$this->save('order_merges', $orderMergeData) &&
						$this->save('transactions', $transactionData) &&
						$this->save('users', $userData)
					) {
						$mailParameters = array(
							'from' => $this->settings['from_email'],
							'subject' => 'Transaction #' . $parameters['payment_transaction_id'] . ' refund confirmation',
							'template' => array(
								'name' => 'payment_refunded',
								'parameters' => array(
									'deductions' => $transactionData,
									'transaction' => array_merge($parameters, array(
										'payment_method' => $this->_retrieveTransactionPaymentMethod($parameters['payment_method_id'])
									)),
									'user' => $parameters['user']
								)
							),
							'to' => $parameters['user']['email']
						);
						$this->_sendMail($mailParameters);

						foreach ($pendingInvoiceOrders as $pendingInvoiceOrder) {
							if ($pendingInvoiceOrder['quantity_active'] === 0) {
								$mailParameters = array(
									'from' => $this->settings['from_email'],
									'subject' => 'Order #' . $pendingInvoiceOrder['id'] . ' is deactivated',
									'template' => array(
										'name' => 'order_deactivated',
										'parameters' => array(
											'order' => $pendingInvoiceOrder,
											'user' => $parameters['user']
										)
									),
									'to' => $parameters['user']['email']
								);
								$this->_sendMail($mailParameters);
							}
						}
					}
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
			if (
				!empty($parameters['invoice_id']) &&
				!empty($parameters['user'])
			) {
				$invoice = $this->_call('invoices', array(
					'methodName' => 'invoice',
					'methodParameters' => array(
						'invoices',
						array(
							'conditions' => array(
								'id' => $parameters['invoice_id']
							)
						),
						true
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Invoice #' . $invoice['data']['invoice']['id'] . ' payment reversal canceled',
						'template' => array(
							'name' => 'payment_reversal_canceled',
							'parameters' => array(
								'invoice' => $invoice['data']['invoice'],
								'transaction' => array_merge($parameters, array(
									'payment_method' => $this->_retrieveTransactionPaymentMethod($parameters['payment_method_id'])
								)),
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
			}

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
			if (
				!empty($parameters['invoice_id']) &&
				!empty($parameters['user'])
			) {
				$invoice = $this->_call('invoices', array(
					'methodName' => 'invoice',
					'methodParameters' => array(
						'invoices',
						array(
							'conditions' => array(
								'id' => $parameters['invoice_id']
							)
						),
						true
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Invoice #' . $invoice['data']['invoice']['id'] . ' payment pending reversal',
						'template' => array(
							'name' => 'payment_reversal',
							'parameters' => array(
								'invoice' => $invoice['data']['invoice'],
								'transaction' => array_merge($parameters, array(
									'payment_method' => $this->_retrieveTransactionPaymentMethod($parameters['payment_method_id'])
								)),
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
			}

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
			$subscriptionData = array(
				array(
					'created' => $parameters['transaction_date'],
					'id' => $parameters['subscription_id'],
					'invoice_id' => $parameters['invoice_id'],
					'interval_type' => $parameters['interval_type'],
					'interval_value' => $parameters['interval_value'],
					'payment_method_id' => $parameters['payment_method_id'],
					'plan_id' => $parameters['plan_id'],
					'price' => $parameters['payment_amount'],
					'status' => 'canceled',
					'user_id' => $parameters['user_id']
				)
			);

			if (
				!empty($parameters['user']) &&
				$this->save('subscriptions', $subscriptionData)
			) {
				$paymentMethod = $this->fetch('payment_methods', array(
					'conditions' => array(
						'id' => $subscriptionData[0]['payment_method_id']
					),
					'fields' => array(
						'name'
					)
				));

				if (!empty($paymentMethod['count'])) {
					$subscriptionData[0]['payment_method_name'] = $paymentMethod['data'][0];
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Subscription #' . $subscriptionData[0]['id'] . ' canceled',
						'template' => array(
							'name' => 'subscription_canceled',
							'parameters' => array(
								'subscription' => $subscriptionData[0],
								'transaction' => $parameters,
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
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
			$subscriptionData = array(
				array(
					'created' => $parameters['transaction_date'],
					'id' => $parameters['subscription_id'],
					'invoice_id' => $parameters['invoice_id'],
					'interval_type' => $parameters['interval_type'],
					'interval_value' => $parameters['interval_value'],
					'payment_method_id' => $parameters['payment_method_id'],
					'plan_id' => $parameters['plan_id'],
					'price' => $parameters['payment_amount'],
					'status' => 'active',
					'user_id' => $parameters['user_id']
				)
			);

			if (
				!empty($parameters['user']) &&
				$this->save('subscriptions', $subscriptionData)
			) {
				$paymentMethod = $this->fetch('payment_methods', array(
					'conditions' => array(
						'id' => $subscriptionData[0]['payment_method_id']
					),
					'fields' => array(
						'name'
					)
				));

				if (!empty($paymentMethod['count'])) {
					$subscriptionData[0]['payment_method_name'] = $paymentMethod['data'][0];
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'New subscription #' . $subscriptionData[0]['id'] . ' created',
						'template' => array(
							'name' => 'subscription_created',
							'parameters' => array(
								'subscription' => $subscriptionData[0],
								'transaction' => $parameters,
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
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
			$subscriptionData = array(
				array(
					'created' => $parameters['transaction_date'],
					'id' => $parameters['subscription_id'],
					'invoice_id' => $parameters['invoice_id'],
					'interval_type' => $parameters['interval_type'],
					'interval_value' => $parameters['interval_value'],
					'payment_method_id' => $parameters['payment_method_id'],
					'plan_id' => $parameters['plan_id'],
					'price' => $parameters['payment_amount'],
					'status' => 'expired',
					'user_id' => $parameters['user_id']
				)
			);

			if (
				!empty($parameters['user']) &&
				$this->save('subscriptions', $subscriptionData)
			) {
				$paymentMethod = $this->fetch('payment_methods', array(
					'conditions' => array(
						'id' => $subscriptionData[0]['payment_method_id']
					),
					'fields' => array(
						'name'
					)
				));

				if (!empty($paymentMethod['count'])) {
					$subscriptionData[0]['payment_method_name'] = $paymentMethod['data'][0];
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Subscription #' . $subscriptionData[0]['id'] . ' expired',
						'template' => array(
							'name' => 'subscription_expired',
							'parameters' => array(
								'subscription' => $subscriptionData[0],
								'transaction' => $parameters,
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
			}

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
			$subscriptionData = array(
				array(
					'id' => $parameters['subscription_id'],
					'invoice_id' => $parameters['invoice_id'],
					'interval_type' => $parameters['interval_type'],
					'interval_value' => $parameters['interval_value'],
					'payment_method_id' => $parameters['payment_method_id'],
					'plan_id' => $parameters['plan_id'],
					'price' => $parameters['payment_amount'],
					'user_id' => $parameters['user_id']
				)
			);
			$subscriptionStatus = $this->fetch('subscriptions', array(
				'conditions' => array(
					'id' => $parameters['subscription_id']
				),
				'fields' => array(
					'status'
				)
			));

			if (
				!empty($parameters['user']) &&
				!empty($subscriptionStatus['count']) &&
				$this->save('subscriptions', $subscriptionData)
			) {
				$paymentMethod = $this->fetch('payment_methods', array(
					'conditions' => array(
						'id' => $subscriptionData[0]['payment_method_id']
					),
					'fields' => array(
						'name'
					)
				));

				if (!empty($paymentMethod['count'])) {
					$subscriptionData[0]['payment_method_name'] = $paymentMethod['data'][0];
					$subscriptionData[0]['status'] = $subscriptionStatus['data'][0];
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Subscription #' . $subscriptionData[0]['id'] . ' modified',
						'template' => array(
							'name' => 'subscription_modified',
							'parameters' => array(
								'subscription' => $subscriptionData[0],
								'transaction' => $parameters,
								'user' => $parameters['user']
							)
						),
						'to' => $parameters['user']['email']
					);
					$this->_sendMail($mailParameters);
				}
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
			$subscriptionData = array(
				array(
					'created' => $parameters['transaction_date'],
					'id' => $parameters['subscription_id'],
					'invoice_id' => $parameters['invoice_id'],
					'interval_type' => $parameters['interval_type'],
					'interval_value' => $parameters['interval_value'],
					'payment_attempts' => ($subscriptionPaymentAttempts['data'][0] + 1),
					'payment_method_id' => $parameters['payment_method_id'],
					'plan_id' => $parameters['plan_id'],
					'price' => $parameters['payment_amount'],
					'user_id' => $parameters['user_id']
				)
			);
			$subscriptionPaymentAttempts = $this->fetch('subscriptions', array(
				'conditions' => array(
					'id' => $parameters['subscription_id']
				),
				'fields' => array(
					'payment_attempts'
				)
			));

			if (
				!empty($parameters['user']) &&
				!empty($subscriptionPaymentAttempts['count'])
			) {
				$subscriptionData[0]['payment_attempts'] = ($subscriptionPaymentAttempts['data'][0] + 1);

				if ($this->save('subscriptions', $subscriptionData)) {
					$paymentMethod = $this->fetch('payment_methods', array(
						'conditions' => array(
							'id' => $subscriptionData[0]['payment_method_id']
						),
						'fields' => array(
							'name'
						)
					));

					if (!empty($paymentMethod['count'])) {
						$subscriptionData[0]['payment_method_name'] = $paymentMethod['data'][0];
						$mailParameters = array(
							'from' => $this->settings['from_email'],
							'subject' => 'Subscription #' . $subscriptionData[0]['id'] . ' payment failed',
							'template' => array(
								'name' => 'subscription_failed',
								'parameters' => array(
									'subscription' => $subscriptionData[0],
									'transaction' => $parameters,
									'user' => $parameters['user']
								)
							),
							'to' => $parameters['user']['email']
						);
						$this->_sendMail($mailParameters);
					}
				}
			}

			return;
		}

	/**
	 * Retrieve transaction method and custom status message
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _retrievePayPalTransactionMethod($parameters) {
			$response = array(
				'payment_status_message' => 'Transaction processed.',
				'transaction_method' => 'Miscellaneous'
			);
			$subscriptionId = (!empty($parameters['subscr_id']) ? ' #' . $parameters['subscr_id'] : '');

			if (!empty($parameters['txn_type'])) {
				switch ($parameters['txn_type']) {
					case 'subscr_cancel':
						$response = array(
							'payment_status_message' => 'Subscription' . $subscriptionId . ' canceled.',
							'transaction_method' => 'SubscriptionCanceled'
						);
						break;
					case 'subscr_eot':
					case 'recurring_payment_expired':
					case 'recurring_payment_suspended':
					case 'recurring_payment_suspended_due_to_max_failed_payment':
						$response = array(
							'payment_status_message' => 'Subscription' . $subscriptionId . ' term expired.',
							'transaction_method' => 'SubscriptionExpired'
						);
						break;
					case 'subscr_failed':
					case 'recurring_payment_skipped':
					case 'recurring_payment_failed':
						$response = array(
							'payment_status_message' => 'Subscription' . $subscriptionId . ' payment failed.',
							'transaction_method' => 'SubscriptionFailed'
						);
						break;
					case 'subscr_modify':
						$response = array(
							'payment_status_message' => 'Subscription' . $subscriptionId . ' modified.',
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
							'payment_status_message' => 'Subscription' . $subscriptionId . ' created.',
							'transaction_method' => 'SubscriptionCreated'
						);
						break;
				}
			}

			if (!empty($parameters['payment_status'])) {
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
			}

			return $response;
		}

	/**
	 * Retrieve transaction invoice ID
	 *
	 * @param array $parameters
	 *
	 * @return string $response
	 */
		protected function _retrieveTransactionInvoiceId($parameters) {
			$response = $parameters['invoice_id'];
			$latestInvoiceId = $this->fetch('invoices', array(
				'conditions' => array(
					'OR' => array(
						'id' => $parameters['invoice_id'],
						'initial_invoice_id' => $parameters['invoice_id']
					)
				),
				'fields' => array(
					'id'
				),
				'limit' => 1,
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			));

			if (!empty($latestInvoiceId['count'])) {
				$response = $latestInvoiceId['data'][0];
			}

			if (!empty($parameters['parent_transaction_id'])) {
				$transactionParameters = array(
					'conditions' => array(
						'payment_transaction_id' => $parameters['parent_transaction_id']
					),
					'fields' => array(
						'invoice_id'
					),
					'limit' => 1,
					'sort' => array(
						'field' => 'created',
						'order' => 'DESC'
					)
				);
				$transaction = $this->fetch('transactions', $transactionParameters);

				if (!empty($transaction['count'])) {
					$response = $transaction['data'][0];
				}
			}

			return $response;
		}

	/**
	 * Retrieve transaction payment method
	 *
	 * @param string $paymentMethodId
	 *
	 * @return string $response
	 */
		protected function _retrieveTransactionPaymentMethod($paymentMethodId) {
			$response = '';
			$paymentMethod = $this->fetch('payment_methods', array(
				'conditions' => array(
					'id' => $paymentMethodId
				),
				'fields' => array(
					'id',
					'name'
				)
			));

			if (!empty($paymentMethod['count'])) {
				$response = $paymentMethod['data'][0]['name'];
			}

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
			$response = $transactionData = array();

			if (
				is_array($parameters) &&
				empty($parameters['json'])
			) {
				if (!empty($parameters['verify_sign'])) {
					$transactionData = $this->_formatPaypalNotification($parameters);
				}

				if (!empty($transactionData)) {
					$existingTransaction = $this->fetch('transactions', array(
						'conditions' => array(
							'payment_transaction_id' => $transactionData[0]['payment_transaction_id']
						),
						'fields' => array(
							'id'
						)
					));

					if (empty($existingTransaction['count'])) {
						$response = $this->save('transactions', $transactionData);
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
			$verifyUrl = 'https://ipnpb' . ($parameters['test_ipn'] ? '.sandbox' : '') . '.paypal.com/cgi-bin/webscr?cmd=_notify-validate&' . $parameters['input'];
			exec('curl "' . str_replace('"', 0, $verifyUrl) . '" 2>&1', $verifyResponse);
			$response = (strcasecmp(end($verifyResponse), 'verified') === 0);
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
			$response = array();

			if (!empty($parameters['data']['payment_method'])) {
				$response = $defaultResponse = array(
					'message' => array(
						'status' => 'error',
						'text' => ($defaultMessage = 'Error processing your payment request, please try again.')
					)
				);

				if (
					!empty($parameters['user']) ||
					(
						(
							($response = $this->_call('users', array(
								'methodName' => 'register',
								'methodParameters' => array(
									'users',
									$parameters
								)
							))) &&
							!empty($response['message']['status']) &&
							$response['message']['status'] === 'success'
						) ||
						(
							($response = $this->_call('users', array(
								'methodName' => 'login',
								'methodParameters' => array(
									'users',
									$parameters
								)
							))) &&
							!empty($response['message']['status']) &&
							$response['message']['status'] === 'success'
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

					if (number_format($parameters['data']['billing_amount'], 2, '.', '') == $parameters['data']['billing_amount']) {
						$response['message']['text'] = 'Invalid payment method, please try again.';
						$method = '_process' . str_replace(' ', '', ucwords(str_replace('_', ' ', $parameters['data']['payment_method'])));

						if (method_exists($this, $method)) {
							if (
								$parameters['data']['payment_method'] === 'balance' &&
								$parameters['data']['billing_amount'] > $parameters['user']['balance']
							) {
								$response['message']['text'] = 'Payment amount from your account balance exceeds your account balance, please enter an amount less than or equal to ' . $parameters['user']['balance'] . ' ' . $this->settings['billing']['currency'] . '.';
							} else {
								$response['message']['text'] = 'Invalid invoice ID, please try again.';
								$invoice = $this->_call('invoices', array(
									'methodName' => 'invoice',
									'methodParameters' => array(
										'invoices',
										array(
											'conditions' => array(
												'id' => $parameters['data']['invoice_id']
											)
										)
									)
								));
								$amountDue = isset($invoice['data']['invoice']['amount_due_pending']) ? $invoice['data']['invoice']['amount_due_pending'] : $invoice['data']['invoice']['amount_due'];

								if (
									$parameters['data']['payment_method'] === 'balance' &&
									$parameters['data']['billing_amount'] > $amountDue
								) {
									$response['message']['text'] = 'Payment amount from your account balance exceeds the amount due' . ($amountDue ? ', please enter an amount less than or equal to ' . $amountDue . ' ' . $this->settings['billing']['currency'] : '') . '.';
								} else {
									if (
										!empty($invoice['data']) &&
										$parameters['user']['id'] === $invoice['data']['invoice']['user_id']
									) {
										if (empty($invoice['data']['invoice']['payable'])) {
											$response['message']['text'] = 'Invoice is currently not payable, please try again later.';
										} else {
											$response['message']['text'] = $defaultMessage;
											$parameters['data'] = array_merge($parameters['data'], $invoice['data']);
											$planData = array(
												array(
													'cart_items' => $parameters['data']['invoice']['cart_items'],
													'id' => uniqid(),
													'invoice_id' => $parameters['data']['invoice']['id'],
													'price' => $parameters['data']['billing_amount']
												)
											);

											if ($this->save('plans', $planData)) {
												$plan = $this->fetch('plans', array(
													'conditions' => array(
														'id' => $planData[0]['id']
													),
													'fields' => array(
														'cart_items',
														'created',
														'id',
														'invoice_id',
														'modified',
														'price'
													),
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
					}
				}
			}

			return $response;
		}

	/**
	 * Process transaction
	 *
	 * @param array $parameters
	 *
	 * @return boolean $response
	 */
		public function processTransaction($parameters) {
			$response = false;

			if (
				!empty($parameters['transaction_method']) &&
				is_string($parameters['transaction_method']) &&
				method_exists($this, ($method = '_processTransaction' . $parameters['transaction_method']))
			) {
				$user = $this->fetch('users', array(
					'conditions' => array(
						'id' => $parameters['user_id']
					),
					'fields' => array(
						'balance',
						'email',
						'id'
					)
				));

				if (!empty($user['count'])) {
					$parameters['user'] = $user['data'][0];
				}

				$this->$method($parameters);
				$response = true;
			}

			return $response;
		}

	/**
	 * Shell method for processing transactions
	 *
	 * @return array $response
	 */
		public function shellProcessTransactions() {
			$response = $this->_processTransactions();
			return $response;
		}

	}
?>
