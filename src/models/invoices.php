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
			$response['invoice']['due'] = date('M d, Y', $dueDate) . ' ' . date('g:ia', $dueDate) . ' ' . $this->settings['timezone'];
		}

		$response['invoice']['amount_due'] = max(0, round(($response['invoice']['total'] - $response['invoice']['amount_paid']) * 100) / 100);
		$response['invoice']['payment_currency_name'] = $this->settings['billing']['currency_name'];
		$response['invoice']['payment_currency_symbol'] = $this->settings['billing']['currency_symbol'];

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
					'id',
					'interval_type',
					'interval_type_pending',
					'interval_value',
					'interval_value_pending',
					'modified',
					'name',
					'price',
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
				$invoiceTransactions['data'][$key]['transaction_date'] = date('M d Y', $transactionTime) . ' at ' . date('g:ia', $transactionTime) . ' ' . $this->settings['timezone'];

				if (!empty($paymentMethod = $paymentMethods['data'][$invoiceTransaction['payment_method_id']])) {
					$invoiceTransactions['data'][$key]['payment_method'] = $paymentMethod;
				}
			}

			$response = $invoiceTransactions['data'];
		}

		return $response;
	}

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
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your invoice request, please try again.')
			)
		);
		$invoiceData = $this->find($table, array(
			'conditions' => $parameters['conditions'],
			'fields' => array(
				'amount_paid',
				'cart_items',
				'created',
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
			)
		));

		if (!empty($invoiceData['count'])) {
			$invoiceData = $invoiceData['data'][0];

			if (!empty($invoiceData['merged_invoice_id'])) {
				$response['redirect'] = $this->settings['base_url'] . 'invoices/' . $invoiceData['merged_invoice_id'];
			} else {
				$invoiceData['created'] = date('M d Y', strtotime($invoiceData['created'])) . ' at ' . date('g:ia', strtotime($invoiceData['created'])) . ' ' . $this->settings['timezone'];
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
