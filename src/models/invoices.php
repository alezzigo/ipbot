<?php
/**
 * Invoices Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/users.php');

class InvoicesModel extends UsersModel {

/**
 * Calculate deduction amounts from merged and additional invoices
 *
 * @param array $invoiceData
 * @param integer $amount
 * @param array $invoiceDeductions
 *
 * @return array $response
 */
	protected function _calculateDeductionsFromInvoice($invoiceData, $amount, $invoiceDeductions = array()) {
		if (!empty($invoiceData)) {
			$remainder = min(0, round(($invoiceData['amount_paid'] + $amount) * 100) / 100);

			if (!empty($invoiceDeductions[$invoiceData['id']])) {
				$remainder = min(0, round(($invoiceData['amount_paid'] + $invoiceDeductions[$invoiceData['id']]['amount_deducted'] + $amount) * 100) / 100);
			}

			$amountDeducted = max(($invoiceData['amount_paid'] * -1), round(($amount - $remainder) * 100) / 100);
			$invoiceDeduction = array(
				'amount_paid' => $invoiceData['amount_paid'],
				'amount_deducted' => $amountDeducted,
				'id' => $invoiceData['id']
			);

			if (
				$amountDeducted < 0 &&
				($amountDeducted * -1) === $invoiceDeduction['amount_paid']
			) {
				$invoiceDeduction['status'] = 'unpaid';
			}

			$invoiceDeductions[$invoiceData['id']] = $invoiceDeduction;
			$invoiceDeductions['remainder'] = $remainder;

			if ($remainder < 0) {
				$additionalInvoice = $this->find('invoices', array(
					'conditions' => array(
						'created >' => date('Y-m-d h:i:s', strtotime($invoiceData['created'])),
						'OR' => array(
							'initial_invoice_id' => $invoiceData['id'],
							'id' => $invoiceData['merged_invoice_id']
						)
					),
					'fields' => array(
						'amount_paid',
						'created',
						'id',
						'initial_invoice_id',
						'merged_invoice_id',
						'status'
					),
					'limit' => 1,
					'sort' => array(
						'field' => 'created',
						'order' => 'ASC'
					)
				));

				if (!empty($additionalInvoice['count'])) {
					$invoiceDeductions = $this->_calculateDeductionsFromInvoice($additionalInvoice['data'][0], $invoiceDeductions['remainder'], $invoiceDeductions);
				}
			}
		}

		$response = $invoiceDeductions;
		return $response;
	}

/**
 * Calculate invoice payment details
 *
 * @param array $invoiceData
 * @param boolean $saveCalculations
 *
 * @return array $response
 */
	protected function _calculateInvoicePaymentDetails($invoiceData, $saveCalculations = true) {
		$response = $invoiceData;

		if (
			!empty($response['orders']) &&
			!empty($response['invoice']) &&
			$response['invoice']['status'] !== 'paid'
		) {
			$response['invoice']['total'] = $response['invoice']['total_pending'] = $response['invoice']['subtotal'] = $response['invoice']['subtotal_pending'] = 0;

			foreach ($response['orders'] as $key => $invoiceOrder) {
				$response['invoice']['shipping'] += $invoiceOrder['shipping'];
				$response['invoice']['shipping_pending'] += $invoiceOrder['shipping_pending'];
				$response['invoice']['subtotal'] += $invoiceOrder['price'];
				$response['invoice']['subtotal_pending'] += $invoiceOrder['price_pending'];
				$response['invoice']['tax'] += $invoiceOrder['tax'];
				$response['invoice']['tax_pending'] += $invoiceOrder['tax_pending'];
				$response['invoice']['total'] += $invoiceOrder['shipping'] + $invoiceOrder['tax'];
				$response['invoice']['total_pending'] += $invoiceOrder['shipping_pending'] + $invoiceOrder['tax_pending'];
			}

			$response['invoice']['total'] += $response['invoice']['subtotal'];
			$response['invoice']['total_pending'] += $response['invoice']['subtotal_pending'];

			foreach ($response['invoice'] as $invoiceKey => $invoiceValue) {
				if (is_numeric($invoiceValue)) {
					$response['invoice'][$invoiceKey] = (integer) round($invoiceValue * 100) / 100;
				}
			}
		}

		$invoiceCalculationData = $response['invoice'];
		$pendingOrderChange = (
			isset($response['invoice']['remainder_pending']) &&
			is_numeric($response['invoice']['remainder_pending'])
		);
		unset($invoiceCalculationData['amount_paid']);
		unset($invoiceCalculationData['billing']);
		unset($invoiceCalculationData['created']);
		unset($invoiceCalculationData['initial_invoice_id']);
		unset($invoiceCalculationData['modified']);
		unset($invoiceCalculationData['user_id']);

		if (
			empty($invoiceCalculationData) ||
			(
				$invoiceCalculationData['status'] !== 'paid' &&
				(
					!$pendingOrderChange &&
					(
						$saveCalculations &&
						!$this->save('invoices', array(
							$invoiceCalculationData
						))
					)
				)
			)
		) {
			$response = $invoiceData;
		}

		if (!empty($response['invoice']['due'])) {
			$dueDate = strtotime($response['invoice']['due']);
			$response['invoice']['due'] = date('M d, Y', $dueDate) . ' ' . date('g:ia', $dueDate) . ' ' . $this->settings['timezone']['display'];
		}

		$response['invoice']['amount_due'] = max(0, round(($response['invoice']['total'] - $response['invoice']['amount_paid']) * 100) / 100);

		if ($pendingOrderChange) {
			$response['invoice']['amount_due_pending'] = $response['invoice']['remainder_pending'];
		} elseif ($response['invoice']['status'] === 'paid') {
			$response['invoice']['amount_due'] = 0;
		}

		return $response;
	}

/**
 * Process invoices
 *
 * @return array $response
 */
	protected function _processInvoices() {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'There aren\'t any new invoices to process, please try again later.'
			)
		);
		$processedInvoices = 0;
		$processedInvoices += $this->_processInvoicesFirstPastDueWarning();
		$processedInvoices += $this->_processInvoicesFirstUpcomingPaymentDueWarning();
		$processedInvoices += $this->_processInvoicesPayable();
		$processedInvoices += $this->_processInvoicesOverduePayment();
		$processedInvoices += $this->_processInvoicesSecondUpcomingPaymentDueWarning();
		$processedInvoices += $this->_processInvoicesSecondPastDueWarning();

		if (!empty($processedInvoices)) {
			$response = array(
				'message' => array(
					'status' => 'success',
					'text' => $processedInvoices . ' invoice' . ($processedInvoices !== 1 ? 's' : '') . ' processed successfully.'
				)
			);
		}

		return $response;
	}

/**
 * Process invoices with first past due warning
 *
 * @return boolean $response Count invoices processed
 */
	protected function _processInvoicesFirstPastDueWarning() {
		$response = 0;
		$invoices = $this->find('invoices', array(
			'conditions' => array(
				'due <' => date('Y-m-d H:i:s', strtotime('-1 day')),
				'merged_invoice_id' => null,
				'status' => 'unpaid',
				'warning_level' => 2
			),
			'fields' => array(
				'id'
			)
		));

		if (!empty($invoices['count'])) {
			foreach ($invoices['data'] as $invoiceId) {
				$invoice = $this->invoice('invoices', array(
					'conditions' => array(
						'id' => $invoiceId
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Payment past-due for invoice #' . $invoiceId,
						'template' => array(
							'name' => 'invoice_past_due',
							'parameters' => $invoice['data']
						),
						'to' => $invoice['data']['user']['email']
					);

					if (
						$this->_sendMail($mailParameters) &&
						$this->save('invoices', array(
							array(
								'id' => $invoiceId,
								'warning_level' => 3
							)
						))
					) {
						$response += 1;
					}
				}
			}
		}

		return $response;
	}

/**
 * Process invoices with first upcoming payment due warning
 *
 * @return boolean $response Count invoices processed
 */
	protected function _processInvoicesFirstUpcomingPaymentDueWarning() {
		$response = 0;
		$invoices = $this->find('invoices', array(
			'conditions' => array(
				'due <' => date('Y-m-d H:i:s', strtotime('+5 days')),
				'initial_invoice_id !=' => null,
				'merged_invoice_id' => null,
				'status' => 'unpaid',
				'warning_level' => 0
			),
			'fields' => array(
				'id'
			)
		));

		if (!empty($invoices['count'])) {
			foreach ($invoices['data'] as $invoiceId) {
				$invoice = $this->invoice('invoices', array(
					'conditions' => array(
						'id' => $invoiceId
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Upcoming payment' . (count($invoice['data']['subscriptions']) > 1 ? 's' : '') . ' ' . (!empty($invoice['data']['subscriptions']) ? 'scheduled' : 'due') . ' for invoice #' . $invoiceId,
						'template' => array(
							'name' => 'invoice_upcoming_payment',
							'parameters' => $invoice['data']
						),
						'to' => $invoice['data']['user']['email']
					);

					if (
						$this->_sendMail($mailParameters) &&
						$this->save('invoices', array(
							array(
								'id' => $invoiceId,
								'warning_level' => 1
							)
						))
					) {
						$response += 1;
					}
				}
			}
		}

		return $response;
	}

/**
 * Process invoices which are within a payable date range
 *
 * @return boolean $response Count invoices processed
 */
	protected function _processInvoicesPayable() {
		$response = 0;
		$invoices = $this->find('invoices', array(
			'conditions' => array(
				'due <' => date('Y-m-d H:i:s', strtotime('+10 days')),
				'merged_invoice_id' => null,
				'payable' => false,
				'status' => 'unpaid',
				'warning_level' => 0
			),
			'fields' => array(
				'id',
				'payable'
			)
		));

		if (!empty($invoices['count'])) {
			$invoiceData = array_values(array_replace_recursive($invoices['data'], array_fill(0, $invoices['count'], array(
				'payable' => true
			))));

			if ($this->save('invoices', $invoiceData)) {
				$response += $invoices['count'];
			}
		}

		return $response;
	}

/**
 * Process invoices with overdue payment
 *
 * @return boolean $response Count invoices processed
 */
	protected function _processInvoicesOverduePayment() {
		$response = 0;
		$invoices = $this->find('invoices', array(
			'conditions' => array(
				'due <' => date('Y-m-d H:i:s', strtotime('-6 days')),
				'merged_invoice_id' => null,
				'status' => 'unpaid',
				'warning_level' => 4
			),
			'fields' => array(
				'id'
			)
		));

		if (!empty($invoices['count'])) {
			foreach ($invoices['data'] as $invoiceId) {
				$invoice = $this->invoice('invoices', array(
					'conditions' => array(
						'id' => $invoiceId
					)
				));

				if (!empty($invoice['data'])) {
					if ($this->save('invoices', array(
						array(
							'id' => $invoiceId,
							'warning_level' => 5
						)
					))) {
						$response += 1;
					}

					if (
						!empty($invoice['data']['orders']) &&
						!empty($invoice['data']['user'])
					) {
						foreach ($invoice['data']['orders'] as $order) {
							$nodeData = $orderData = array();
							$orderData[] = array(
								'id' => $order['id'],
								'status' => 'pending'
							);

							if ($this->save('orders', $orderData)) {
								$proxyParameters = array(
									'conditions' => array(
										'order_id' => $order['id']
									),
									'fields' => array(
										'node_id'
									)
								);
								$nodeIds = $this->find('proxies', $proxyParameters);
								$proxyParameters['fields'] = array(
									'id'
								);
								$proxyIds = $this->find('proxies', $proxyParameters);

								if (
									!empty($nodeIds['count']) &&
									!empty($proxyIds['count'])
								) {
									foreach ($nodeIds['data'] as $nodeId) {
										$nodeData[$nodeId] = array(
											'allocated' => false,
											'id' => $nodeId,
											'processing' => false
										);
									}

									$this->save('nodes', array_values($nodeData));
									$this->delete('proxies', array(
										'id' => $proxyIds['data']
									));
								}

								$mailParameters = array(
									'from' => $this->settings['from_email'],
									'subject' => 'Order #' . $order['id'] . ' is deactivated',
									'template' => array(
										'name' => 'order_deactivated',
										'parameters' => array(
											'invoice' => $invoice['data']['invoice'],
											'order' => array_merge($order, $orderData),
											'user' => $invoice['data']['user']
										)
									),
									'to' => $invoice['data']['user']['email']
								);
								$this->_sendMail($mailParameters);
							}
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * Process invoices with second past due warning
 *
 * @return boolean $response Count invoices processed
 */
	protected function _processInvoicesSecondPastDueWarning() {
		$response = 0;
		$invoices = $this->find('invoices', array(
			'conditions' => array(
				'due <' => date('Y-m-d H:i:s', strtotime('-1 day')),
				'merged_invoice_id' => null,
				'status' => 'unpaid',
				'warning_level' => 3
			),
			'fields' => array(
				'id'
			)
		));

		if (!empty($invoices['count'])) {
			foreach ($invoices['data'] as $invoiceId) {
				$invoice = $this->invoice('invoices', array(
					'conditions' => array(
						'id' => $invoiceId
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Payment past-due for invoice #' . $invoiceId,
						'template' => array(
							'name' => 'invoice_past_due',
							'parameters' => $invoice['data']
						),
						'to' => $invoice['data']['user']['email']
					);

					if (
						$this->_sendMail($mailParameters) &&
						$this->save('invoices', array(
							array(
								'id' => $invoiceId,
								'warning_level' => 4
							)
						))
					) {
						$response += 1;
					}
				}
			}
		}

		return $response;
	}

/**
 * Process invoices with second upcoming payment due warning
 *
 * @return boolean $response Count invoices processed
 */
	protected function _processInvoicesSecondUpcomingPaymentDueWarning() {
		$response = 0;
		$invoices = $this->find('invoices', array(
			'conditions' => array(
				'due <' => date('Y-m-d H:i:s', strtotime('+1 day')),
				'merged_invoice_id' => null,
				'status' => 'unpaid',
				'warning_level' => 1
			),
			'fields' => array(
				'id'
			)
		));

		if (!empty($invoices['count'])) {
			foreach ($invoices['data'] as $invoiceId) {
				$invoice = $this->invoice('invoices', array(
					'conditions' => array(
						'id' => $invoiceId
					)
				));

				if (!empty($invoice['data'])) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
						'subject' => 'Upcoming payment' . (count($invoice['data']['subscriptions']) > 1 ? 's' : '') . ' ' . (!empty($invoice['data']['subscriptions']) ? 'scheduled' : 'due') . ' for invoice #' . $invoiceId,
						'template' => array(
							'name' => 'invoice_upcoming_payment',
							'parameters' => $invoice['data']
						),
						'to' => $invoice['data']['user']['email']
					);

					if (
						$this->_sendMail($mailParameters) &&
						$this->save('invoices', array(
							array(
								'id' => $invoiceId,
								'warning_level' => 2
							)
						))
					) {
						$response += 1;
					}
				}
			}
		}

		return $response;
	}

/**
 * Retrieve invoice IDs
 *
 * @param array $invoiceIds
 *
 * @return array $response
 */
	protected function _retrieveInvoiceIds($invoiceIds) {
		$response = $invoiceIds;
		$invoiceParameters = array(
			'conditions' => array(
				'OR' => array(
					'id' => $invoiceIds,
					'initial_invoice_id' => $invoiceIds,
					'merged_invoice_id' => $invoiceIds
				)
			),
			'fields' => array(
				'id',
				'initial_invoice_id',
				'merged_invoice_id'
			)
		);

		$invoices = $this->find('invoices', $invoiceParameters);

		if (!empty($invoices['count'])) {
			foreach ($invoices['data'] as $invoice) {
				$invoiceIds = array_merge($invoiceIds, array_values($invoice));
			}
		}

		$invoiceIds = array_unique(array_filter($invoiceIds));

		if (count($invoiceIds) > count($response)) {
			$response = $this->_retrieveInvoiceIds($invoiceIds);
		}

		return $response;
	}

/**
 * Retrieve invoice items data
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _retrieveInvoiceItems($invoiceData) {
		$response = array();

		if ($invoiceData['status'] === 'paid') {
			$invoiceItems = $this->find('invoice_items', array(
				'conditions' => array(
					'invoice_id' => $invoiceData['id']
				),
				'fields' => array(
					'currency',
					'interval_type',
					'interval_value',
					'invoice_id',
					'name',
					'order_id',
					'price',
					'quantity'
				)
			));

			if (!empty($invoiceItems['count'])) {
				foreach ($invoiceItems['data'] as $invoiceItemKey => $invoiceItem) {
					$invoiceItems['data'][$invoiceItemKey]['id'] = $invoiceItem['order_id'];
				}

				$response = $invoiceItems['data'];
			}
		}

		return $response;
	}

/**
 * Retrieve invoice order data
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _retrieveInvoiceOrders($invoiceData) {
		$response = array();
		$invoiceIds = $this->_retrieveInvoiceIds(array_unique(array_filter(array(
			$invoiceData['id'],
			$invoiceData['initial_invoice_id'],
			$invoiceData['merged_invoice_id']
		))));
		$invoiceOrders = $this->find('invoice_orders', array(
			'conditions' => array(
				'invoice_id' => $invoiceIds
			),
			'fields' => array(
				'order_id'
			)
		));

		if (!empty($invoiceOrders['count'])) {
			$orders = $this->find('orders', array(
				'conditions' => array(
					'id' => $invoiceOrders['data'],
					'status !=' => 'merged'
				),
				'fields' => array(
					'created',
					'currency',
					'id',
					'interval_type',
					'interval_type_pending',
					'interval_value',
					'interval_value_pending',
					'modified',
					'name',
					'price',
					'price_active',
					'price_pending',
					'product_id',
					'quantity',
					'quantity_active',
					'quantity_pending',
					'session_id',
					'shipping',
					'shipping_pending',
					'status',
					'tax',
					'tax_pending',
					'type',
					'user_id'
				)
			));

			if (!empty($orders['count'])) {
				$response = $orders['data'];
			}
		}

		return $response;
	}

/**
 * Retrieve invoice subscription data
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _retrieveInvoiceSubscriptions($invoiceData) {
		$response = array();
		$invoiceSubscriptions = $this->find('subscriptions', array(
			'conditions' => array(
				'invoice_id' => (!empty($invoiceData['initial_invoice_id']) ? $invoiceData['initial_invoice_id'] : $invoiceData['id'])
			),
			'fields' => array(
				'created',
				'id',
				'invoice_id',
				'modified',
				'plan_id'
			)
		));

		if (!empty($invoiceSubscriptions['count'])) {
			$response = $invoiceSubscriptions['data'];
		}

		return $response;
	}

/**
 * Retrieve invoice transaction data
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _retrieveInvoiceTransactions($invoiceData) {
		$response = array();
		$invoiceTransactions = $this->find('transactions', array(
			'conditions' => array(
				'transaction_method !=' => 'PaymentRefunded',
				'invoice_id' => $invoiceData['id'],
				'transaction_processed' => true,
				'transaction_processing' => false
			),
			'fields' => array(
				'billing_address_1',
				'billing_address_2',
				'billing_address_status',
				'billing_city',
				'billing_country_code',
				'billing_name',
				'billing_region',
				'billing_zip',
				'created',
				'customer_email',
				'customer_first_name',
				'customer_id',
				'customer_last_name',
				'customer_status',
				'details',
				'id',
				'initial_invoice_id',
				'interval_type',
				'interval_value',
				'invoice_id',
				'modified',
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
				'provider_country_code',
				'provider_email',
				'provider_id',
				'sandbox',
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
				'field' => 'transaction_date',
				'order' => 'ASC'
			)
		));
		$paymentMethods = $this->find('payment_methods', array(
			'fields' => array(
				'id',
				'name'
			)
		));

		if (
			!empty($invoiceTransactions['count']) &&
			!empty($paymentMethods['count'])
		) {
			foreach ($paymentMethods['data'] as $key => $paymentMethod) {
				$paymentMethods['data'][$paymentMethod['id']] = $paymentMethod['name'];
				unset($paymentMethods['data'][$key]);
			}

			foreach ($invoiceTransactions['data'] as $key => $invoiceTransaction) {
				$transactionTime = strtotime($invoiceTransaction['transaction_date']);
				$invoiceTransactions['data'][$key]['transaction_date'] = date('M d Y', $transactionTime) . ' at ' . date('g:ia', $transactionTime) . ' ' . $this->settings['timezone']['display'];

				if (!empty($paymentMethod = $paymentMethods['data'][$invoiceTransaction['payment_method_id']])) {
					$invoiceTransactions['data'][$key]['payment_method'] = $paymentMethod;
				}
			}

			$response = $invoiceTransactions['data'];
		}

		return $response;
	}

/**
 * Retrieve most recent payable invoice data
 *
 * @param integer $invoiceId
 *
 * @return array $response
 */
	protected function _retrieveMostRecentPayableInvoice($invoiceId) {
		$response = array();
		$invoiceIds = $this->_retrieveInvoiceIds(array(
			$invoiceId
		));
		$invoice = $this->find('invoices', array(
			'conditions' => array(
				'merged_invoice_id' => null,
				'payable' => true,
				'remainder_pending' => null,
				'OR' => array(
					'id' => $invoiceIds,
					'initial_invoice_id' => $invoiceIds,
					'merged_invoice_id' => $invoiceIds
				)
			),
			'fields' => array(
				'amount_paid',
				'cart_items',
				'created',
				'currency',
				'due',
				'id',
				'initial_invoice_id',
				'merged_invoice_id',
				'modified',
				'payable',
				'remainder_pending',
				'session_id',
				'shipping',
				'shipping_pending',
				'status',
				'subtotal',
				'subtotal_pending',
				'tax',
				'tax_pending',
				'total',
				'total_pending',
				'user_id'
			),
			'limit' => 1,
			'sort' => array(
				'field' => 'created',
				'order' => 'DESC'
			)
		));

		if (!empty($invoice['count'])) {
			$response = $invoice['data'][0];
		}

		return $response;
	}

/**
 * Retrieve previous invoice data
 *
 * @param array $invoiceData
 * @param array $previousInvoices
 *
 * @return array $response
 */
	protected function _retrievePreviousInvoices($invoiceData, $previousInvoices = array()) {
		$response = $previousInvoices;
		$previousInvoiceParameters = array(
			'conditions' => array(
				'created <=' => date('Y-m-d h:i:s', strtotime($invoiceData['created'])),
				'id !=' => $invoiceData['id'],
				'OR' => array(
					'id' => $invoiceData['initial_invoice_id'],
					'merged_invoice_id' => $invoiceData['id']
				)
			),
			'fields' => array(
				'amount_merged',
				'amount_paid',
				'created',
				'due',
				'id',
				'initial_invoice_id',
				'remainder_pending',
				'status',
				'total',
				'total_pending'
			),
			'limit' => 1,
			'sort' => array(
				'field' => 'created',
				'order' => 'DESC'
			)
		);
		$previousInvoice = $this->find('invoices', $previousInvoiceParameters);

		if (!empty($previousInvoice['count'])) {
			$previousInvoices[] = $previousInvoice['data'][0];
			$response = $this->_retrievePreviousInvoices($previousInvoice['data'][0], $previousInvoices);
		}

		return $response;
	}

/**
 * Cancel pending invoice order requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function cancel($table, $parameters) {
		$response = array(
			'data' => array(),
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your pending invoice order cancellation request, please try again.')
			)
		);
		$initialInvoiceIds = $invoiceOrders = $pendingInvoices = $pendingInvoiceOrders = $pendingTransactions = $userData = array();

		if (!empty($parameters['conditions'])) {
			$invoice = $this->invoice('invoices', array(
				'conditions' => $parameters['conditions']
			));

			if (!empty($invoice['data']['orders'])) {
				foreach ($invoice['data']['orders'] as $order) {
					$invoiceOrders[$order['id']] = $order;
				}

				$invoice['data']['orders'] = $invoiceOrders;

				if (!empty($order = $orderData = $invoice['data']['orders'][$order['id']])) {
					if (
						!empty($orderData['price_active']) &&
						!empty($orderData['quantity_active'])
					) {
						$orderData['price'] = $orderData['price_active'];
						$orderData['quantity'] = $orderData['quantity_active'];
					}

					$invoiceOrders = $this->find('invoice_orders', array(
						'conditions' => array(
							'order_id' => $orderData['id']
						),
						'fields' => array(
							'id',
							'initial_invoice_id',
							'invoice_id',
							'order_id'
						),
						'limit' => 1
					));
					$orderData = array(
						array_merge($orderData, array(
							'interval_type_pending' => null,
							'interval_value_pending' => null,
							'price_pending' => null,
							'quantity_pending' => null,
							'shipping_pending' => null,
							'tax_pending' => null
						))
					);

					if (!empty($invoiceOrders['count'])) {
						$invoiceOrders['data'][0] = array_merge($invoiceOrders['data'][0], array(
							'initial_invoice_id' => null,
							'invoice_id' => ($invoiceId = $invoiceOrders['data'][0]['initial_invoice_id'])
						));
						$mostRecentPayableInvoice = $this->_retrieveMostRecentPayableInvoice($invoiceId);

						if (!empty($mostRecentPayableInvoice)) {
							$amountPaidForUpgrade = 0;
							$invoiceIds = $this->_retrieveInvoiceIds(array(
								$invoice['data']['invoice']['id']
							));
							$transactionParameters = array(
								'conditions' => array(
									'invoice_id' => $invoiceIds,
									'transaction_method' => 'Miscellaneous'
								),
								'fields' => array(
									'id',
									'initial_invoice_id',
									'invoice_id',
									'payment_amount'
								)
							);
							$amountMergedTransactions = $this->find('transactions', $transactionParameters);
							$transactionParameters['conditions'] = array(
								'initial_invoice_id' => $invoiceIds
							);
							$upgradeTransactions = $this->find('transactions', $transactionParameters);
							$cancelledInvoiceIds = array_diff($invoiceIds, array(
								$invoice['data']['invoice']['id']
							));

							foreach ($cancelledInvoiceIds as $cancelledInvoiceId) {
								$pendingInvoices[$cancelledInvoiceId] = array(
									'id' => $cancelledInvoiceId,
									'merged_invoice_id' => $invoiceId
								);
							}

							if (!empty($amountMergedTransactions['count'])) {
								foreach ($amountMergedTransactions['data'] as $amountMergedTransaction) {
									$initialInvoiceIds[$amountMergedTransaction['initial_invoice_id']] = $amountMergedTransaction['initial_invoice_id'];
								}

								$initialInvoices = $this->find('invoices', array(
									'conditions' => array(
										'id' => array_values($initialInvoiceIds)
									),
									'fields' => array(
										'amount_merged',
										'id'
									)
								));

								if (!empty($initialInvoices['count'])) {
									foreach ($initialInvoices['data'] as $initialInvoice) {
										$pendingInvoices[$initialInvoice['id']] = $initialInvoice;
									}
								}

								foreach ($amountMergedTransactions['data'] as $amountMergedTransaction) {
									$pendingInvoices[$amountMergedTransaction['initial_invoice_id']]['amount_merged'] = max(0, round(($pendingInvoices[$amountMergedTransaction['initial_invoice_id']]['amount_merged'] - $amountMergedTransaction['payment_amount'] * 100)) / 100);
								}
							}

							if (!empty($upgradeTransactions['count'])) {
								foreach ($upgradeTransactions['data'] as $upgradeTransaction) {
									if (
										is_numeric($upgradeTransaction['payment_amount']) &&
										$upgradeTransaction['transaction_method'] != 'Miscellaneous'
									) {
										$amountPaidForUpgrade += $upgradeTransaction['payment_amount'];
									}

									$pendingTransactions[] = array(
										'id' => $upgradeTransaction['id'],
										'initial_invoice_id' => null,
										'invoice_id' => $mostRecentPayableInvoice['id']
									);
								}
							}

							$upgradeDifference = max(0, (round(($invoice['data']['invoice']['total_pending'] - $revertedInvoice['invoice']['total']) * 100) / 100));
							$pendingInvoices[$invoice['data']['invoice']['id']] = array(
								'amount_paid' => ($amountPaid = max(0, round(($invoice['data']['invoice']['amount_paid'] - $amountPaidForUpgrade) * 100) / 100)),
								'id' => $invoice['data']['invoice']['id'],
								'merged_invoice_id' => $invoiceId,
								'remainder_pending' => max(0, round(($invoice['data']['invoice']['remainder_pending'] - $upgradeDifference) * 100) / 100)
							);
							$upgradeCancellationTransaction = array(
								'customer_email' => $parameters['user']['email'],
								'details' => 'Order upgrade request cancelled for order <a href="' . $this->settings['base_url'] . 'orders/' . $order['id'] . '">#' . $order['id'] . '</a>.<br>' . $order['quantity_pending'] . ' ' . $order['name'] . ' reverted to ' . $orderData[0]['quantity'] . ' ' . $order['name'] . '<br>' . $order['price_pending'] . ' ' . $order['currency'] . ' for ' . $order['interval_value_pending'] . ' ' . $order['interval_type_pending'] . ($order['interval_value_pending'] !== 1 ? 's' : '') . ' reverted to ' . $orderData[0]['price'] . ' ' . $order['currency'] . ' for ' . $order['interval_value'] . ' ' . $order['interval_type'] . ($order['interval_value'] !== 1 ? 's' : ''),
								'id' => uniqid() . time(),
								'initial_invoice_id' => $invoiceId,
								'invoice_id' => $invoiceId,
								'payment_amount' => null,
								'payment_currency' => $this->settings['billing']['currency'],
								'payment_status' => 'completed',
								'payment_status_message' => 'Order upgrade request cancelled.',
								'transaction_charset' => $this->settings['database']['charset'],
								'transaction_date' => date('Y-m-d h:i:s', time()),
								'transaction_method' => 'Miscellaneous',
								'transaction_processed' => true,
								'user_id' => $parameters['user']['id']
							);

							if ($amountPaidForUpgrade > 0) {
								$upgradeCancellationTransaction['details'] .= '<br>' . number_format($amountPaidForUpgrade, 2, '.', '') . ' ' . $order['currency'] . ' overpayment added to account balance.';
								$userData = array(
									array(
										'balance' => $parameters['user']['balance'] + $amountPaidForUpgrade,
										'id' => $parameters['user']['id']
									)
								);
							}

							$pendingTransactions[] = $upgradeCancellationTransaction;

							if (
								$this->save('invoices', array_values($pendingInvoices)) &&
								$this->save('invoice_orders', $invoiceOrders['data']) &&
								$this->save('orders', $orderData) &&
								$this->save('transactions', $pendingTransactions) &&
								$this->save('users', $userData)
							) {
								$initialInvoiceIds = $this->_retrieveInvoiceIds(array(
									$invoiceId
								));

								if (!empty($initialInvoiceIds)) {
									foreach ($initialInvoiceIds as $initialInvoiceId) {
										$this->invoice('invoices', array(
											'conditions' => array(
												'id' => $initialInvoiceId
											)
										));
									}
								}

								$response['message'] = array(
									'status' => 'success',
									'text' => 'Invoice order canceled successfully for order #' . $order['id'] . '.'
								);
								$response['redirect'] = $this->settings['base_url'] . 'invoices/' . $mostRecentPayableInvoice['id'];
							}
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * Process invoice requests
 *
 * @param string $table
 * @param array $parameters
 * @param boolean $mostRecent
 *
 * @return array $response
 */
	public function invoice($table, $parameters, $mostRecent = false) {
		$response = array(
			'data' => array(),
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your invoice request, please try again.')
			)
		);
		$invoiceParameters = array(
			'conditions' => $parameters['conditions'],
			'fields' => array(
				'amount_paid',
				'cart_items',
				'created',
				'currency',
				'due',
				'id',
				'initial_invoice_id',
				'merged_invoice_id',
				'modified',
				'payable',
				'remainder_pending',
				'session_id',
				'shipping',
				'shipping_pending',
				'status',
				'subtotal',
				'subtotal_pending',
				'tax',
				'tax_pending',
				'total',
				'total_pending',
				'user_id'
			),
			'limit' => 1
		);

		if (
			$mostRecent === true &&
			!empty($invoiceParameters['conditions']['id'])
		) {
			$invoiceParameters['conditions']['id'] = $this->_retrieveInvoiceIds((array) $invoiceParameters['conditions']['id']);
			$invoiceParameters['sort'] = array(
				'field' => 'created',
				'order' => 'DESC'
			);
		}

		$invoiceData = $this->find($table, $invoiceParameters);

		if (!empty($invoiceData['count'])) {
			$invoiceData = $invoiceData['data'][0];

			if (!empty($invoiceData['merged_invoice_id'])) {
				$response['redirect'] = $this->settings['base_url'] . 'invoices/' . $invoiceData['merged_invoice_id'];
			} else {
				$invoiceData['created'] = date('M d Y', strtotime($invoiceData['created'])) . ' at ' . date('g:ia', strtotime($invoiceData['created'])) . ' ' . $this->settings['timezone']['display'];
				$invoiceItems = $this->_retrieveInvoiceItems($invoiceData);
				$invoiceOrders = $this->_retrieveInvoiceOrders($invoiceData);
				$invoiceSubscriptions = $this->_retrieveInvoiceSubscriptions($invoiceData);
				$invoiceTransactions = $this->_retrieveInvoiceTransactions($invoiceData);
				$invoiceUser = $this->_retrieveUser($invoiceData);

				if (!empty($invoiceData)) {
					if (!empty($this->settings['billing'])) {
						$invoiceData['billing'] = $this->settings['billing'];
					}

					$response = array(
						'data' => array(
							'invoice' => $invoiceData,
							'items' => $invoiceItems,
							'orders' => $invoiceOrders,
							'subscriptions' => $invoiceSubscriptions,
							'transactions' => $invoiceTransactions,
							'user' => $invoiceUser
						),
						'message' => array(
							'status' => 'success',
							'text' => ''
						)
					);
					$response['data'] = array_replace_recursive($response['data'], $this->_calculateInvoicePaymentDetails($response['data']));
				}
			}
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
 * Shell method for processing invoices
 *
 * @return array $response
 */
	public function shellProcessInvoices() {
		$response = $this->_processInvoices();
		return $response;
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
