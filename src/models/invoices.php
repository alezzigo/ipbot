<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class InvoicesModel extends AppModel {

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
			$invoices = $this->fetch('invoices', array(
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
						$invoiceData = array(
							array(
								'id' => $invoiceId,
								'warning_level' => 3
							)
						);
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
							$this->save('invoices', $invoiceData)
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
			$invoices = $this->fetch('invoices', array(
				'conditions' => array(
					'due <' => date('Y-m-d H:i:s', strtotime('+5 days')),
					'merged_invoice_id' => null,
					'status' => 'unpaid',
					'warning_level' => 0,
					'NOT' => array(
						'initial_invoice_id' => null
					)
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
						$invoiceData = array(
							array(
								'id' => $invoiceId,
								'warning_level' => 1
							)
						);
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
							$this->save('invoices', $invoiceData)
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
			$invoices = $this->fetch('invoices', array(
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
			$invoices = $this->fetch('invoices', array(
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
					$invoiceData = array(
						array(
							'id' => $invoiceId,
							'warning_level' => 5
						)
					);

					if (!empty($invoice['data'])) {
						if ($this->save('invoices', $invoiceData)) {
							$response += 1;
						}

						if (
							!empty($invoice['data']['orders']) &&
							!empty($invoice['data']['user'])
						) {
							foreach ($invoice['data']['orders'] as $order) {
								$orderData = array(
									array(
										'id' => $order['id'],
										'status' => 'pending'
									)
								);

								if ($this->save('orders', $orderData)) {
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
			$invoices = $this->fetch('invoices', array(
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
						$invoiceData = array(
							array(
								'id' => $invoiceId,
								'warning_level' => 4
							)
						);
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
							$this->save('invoices', $invoiceData)
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
			$invoices = $this->fetch('invoices', array(
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
					$invoiceData = array(
						array(
							'id' => $invoiceId,
							'warning_level' => 2
						)
					);

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
							$this->save('invoices', $invoiceData)
						) {
							$response += 1;
						}
					}
				}
			}

			return $response;
		}

	/**
	 * Retrieve invoice item data
	 *
	 * @param array $invoiceData
	 *
	 * @return array $response
	 */
		protected function _retrieveInvoiceItems($invoiceData) {
			$response = array();

			if ($invoiceData['status'] === 'paid') {
				$invoiceItems = $this->fetch('invoice_items', array(
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
	 * Retrieve invoice subscription data
	 *
	 * @param array $invoiceData
	 *
	 * @return array $response
	 */
		protected function _retrieveInvoiceSubscriptions($invoiceData) {
			$response = array();
			$invoiceSubscriptions = $this->fetch('subscriptions', array(
				'conditions' => array(
					'invoice_id' => (!empty($invoiceData['initial_invoice_id']) ? $invoiceData['initial_invoice_id'] : $invoiceData['id'])
				),
				'fields' => array(
					'created',
					'id',
					'invoice_id',
					'modified',
					'plan_id',
					'user_id'
				)
			));

			if (!empty($invoiceSubscriptions['count'])) {
				$response = $invoiceSubscriptions['data'];
			}

			return $response;
		}

	/**
	 * Calculate deduction amounts from merged and additional invoices
	 *
	 * @param array $invoiceData
	 * @param integer $amount
	 * @param array $invoiceDeductions
	 *
	 * @return array $response
	 */
		public function calculateDeductionsFromInvoice($invoiceData, $amount, $invoiceDeductions = array()) {
			if (!empty($invoiceData)) {
				$remainder = min(0, round(($invoiceData['amount_paid'] + $amount) * 100) / 100);

				if (!empty($invoiceDeductions[$invoiceData['id']])) {
					$remainder = min(0, round(($invoiceData['amount_paid'] + $invoiceDeductions[$invoiceData['id']]['amount_deducted'] + $amount) * 100) / 100);
				}

				$amountDeducted = max(($invoiceData['amount_paid'] * -1), round(($amount - $remainder) * 100) / 100);
				$invoiceDeduction = array(
					'amount_paid' => $invoiceData['amount_paid'],
					'amount_deducted' => $amountDeducted,
					'id' => $invoiceData['id'],
					'remainder_pending' => $invoiceData['remainder_pending'] + ($amountDeducted * -1)
				);

				if ($amountDeducted < 0) {
					$invoiceDeduction['status'] = 'unpaid';
				}

				$invoiceDeductions[$invoiceData['id']] = $invoiceDeduction;
				$invoiceDeductions['remainder'] = $remainder;

				if ($remainder < 0) {
					$additionalInvoice = $this->fetch('invoices', array(
						'conditions' => array(
							'created >' => date('Y-m-d H:i:s', strtotime($invoiceData['created'])),
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
						$invoiceDeductions = $this->calculateDeductionsFromInvoice($additionalInvoice['data'][0], $invoiceDeductions['remainder'], $invoiceDeductions);
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
		public function calculateInvoicePaymentDetails($invoiceData, $saveCalculations = true) {
			$response = $invoiceData;

			if (
				!empty($response['orders']) &&
				!empty($response['invoice']) &&
				$response['invoice']['status'] !== 'paid'
			) {
				$response['invoice']['total'] = $response['invoice']['total_pending'] = $response['invoice']['shipping'] = $response['invoice']['shipping_pending'] = $response['invoice']['subtotal'] = $response['invoice']['subtotal_pending'] = 0;

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
			$additionalDueInvoice = $initialInvoiceIds = $invoiceOrders = $pendingInvoices = $pendingTransactions = $userData = array();

			if (!empty($parameters['conditions'])) {
				$invoice = $this->invoice('invoices', array(
					'conditions' => $parameters['conditions']
				));

				if (!empty($invoice['data']['orders'])) {
					foreach ($invoice['data']['orders'] as $order) {
						$invoiceOrders[$order['id']] = $order;
					}

					$invoice['data']['orders'] = $invoiceOrders;

					if (!empty($invoice['data']['orders'][$order['id']])) {
						$order = $orderData = $invoice['data']['orders'][$order['id']];

						if (
							!empty($orderData['price_active']) &&
							!empty($orderData['quantity_active'])
						) {
							$orderData['price'] = $orderData['price_active'];
							$orderData['quantity'] = $orderData['quantity_active'];
						}

						$invoiceOrders = $this->fetch('invoice_orders', array(
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
							array_merge(array_intersect_key($orderData, array(
								'id' => true,
								'interval_type' => true,
								'interval_value' => true,
								'ip_version' => true,
								'price' => true,
								'quantity' => true,
								'quantity_active' => true,
								'status' => true
							)), array(
								'interval_type_pending' => null,
								'interval_value_pending' => null,
								'price_pending' => null,
								'quantity_pending' => null,
								'shipping_pending' => null,
								'tax_pending' => null
							))
						);

						if (!empty($invoiceOrders['count'])) {
							$additionalDueInvoiceIdentifier = 0;
							$amountToApplyToBalance = $invoice['data']['invoice']['amount_paid'];
							$invoiceIds = $this->retrieveInvoiceIds(array(
								$invoice['data']['invoice']['id']
							));
							$invoiceOrders['data'][0] = array_merge($invoiceOrders['data'][0], array(
								'initial_invoice_id' => null,
								'invoice_id' => ($invoiceId = $invoiceOrders['data'][0]['initial_invoice_id'])
							));
							$mostRecentPayableInvoice = $this->retrieveMostRecentPayableInvoice($invoiceId);

							if (
								$orderData[0]['quantity_active'] === 0 &&
								empty($mostRecentPayableInvoice)
							) {
								$mostRecentPayableInvoice = $pendingInvoices[$invoice['data']['invoice']['id']] = array(
									'amount_paid' => 0,
									'id' => $invoice['data']['invoice']['id'],
									'remainder_pending' => null,
									'subtotal_pending' => null,
									'tax_pending' => null,
									'total_pending' => null
								);
							} elseif (!empty($mostRecentPayableInvoice)) {
								$cancelledInvoiceIds = array_diff($invoiceIds, array(
									$invoice['data']['invoice']['id']
								));
								$invoiceParameters = array(
									'conditions' => array(
										'id' => $cancelledInvoiceIds
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
								);
								$cancelledInvoices = $this->fetch('invoices', $invoiceParameters);
								$invoiceParameters['conditions']['id'] = $mostRecentPayableInvoice['id'];
								$revertedInvoice = $this->fetch('invoices', $invoiceParameters);

								if (!empty($cancelledInvoices['count'])) {
									foreach ($cancelledInvoices['data'] as $cancelledInvoice) {
										if (
											$cancelledInvoice['initial_invoice_id'] == $invoiceId &&
											$cancelledInvoice['status'] == 'unpaid'
										) {
											$pendingInvoices['reverted'] = array_diff_key($cancelledInvoice, array(
												'created' => true,
												'due' => true,
												'id' => true,
												'merged_invoice_id' => true,
												'modified' => true
											));
										}
									}
								}

								if (!empty($revertedInvoice['count'])) {
									$additionalDueInvoices = $this->retrieveDueInvoices($revertedInvoice['data'][0]);
									$upgradeDifference = max(0, (round(($invoice['data']['invoice']['total_pending'] - $revertedInvoice['data'][0]['invoice']['total']) * 100) / 100));
									$pendingInvoices[$invoice['data']['invoice']['id']] = array(
										'amount_paid' => 0,
										'id' => $invoice['data']['invoice']['id'],
										'merged_invoice_id' => $invoiceId,
										'remainder_pending' => max(0, round(($invoice['data']['invoice']['remainder_pending'] - $upgradeDifference) * 100) / 100)
									);
									$pendingInvoices[$mostRecentPayableInvoice['id']] = array(
										'id' => $mostRecentPayableInvoice['id'],
										'merged_invoice_id' => null
									);

									if (!empty($additionalDueInvoices)) {
										$additionalDueInvoice = array_diff_key($additionalDueInvoices[0], array(
											'created' => true,
											'due' => true,
											'modified' => true
										));
									}

									$pendingInvoices['reverted'] = array_merge(
										array(
											'due' => date('Y-m-d H:i:s', strtotime($revertedInvoice['data'][0]['due'] . ' +' . $orderData[0]['interval_value'] . ' ' . $orderData[0]['interval_type'])),
											'initial_invoice_id' => (!empty($revertedInvoice['data'][0]['initial_invoice_id']) ? $revertedInvoice['data'][0]['initial_invoice_id'] : $revertedInvoice['data'][0]['id'])
										),
										$additionalDueInvoice,
										!empty($pendingInvoices['reverted']) ? $pendingInvoices['reverted'] : array(),
										array(
											'user_id' => $parameters['user']['id']
										)
									);

									if (empty($pendingInvoices['reverted']['cart_items'])) {
										$pendingInvoices['reverted']['cart_items'] = sha1(uniqid() . time());
									}

									$additionalDueInvoiceIdentifier = $pendingInvoices['reverted']['cart_items'];
								}
							}

							if (!empty($pendingInvoices)) {
								$transactionParameters = array(
									'conditions' => array(
										'invoice_id' => $invoiceIds,
										'payment_amount >' => 0,
										'transaction_method' => 'Miscellaneous'
									),
									'fields' => array(
										'id',
										'initial_invoice_id',
										'invoice_id',
										'payment_amount'
									)
								);
								$amountMergedTransactions = $this->fetch('transactions', $transactionParameters);
								$transactionParameters['conditions'] = array(
									'initial_invoice_id' => $invoiceIds,
									'OR' => array(
										'payment_amount' => null,
										'NOT' => array(
											'transaction_method' => 'Miscellaneous'
										)
									)
								);
								$upgradeTransactions = $this->fetch('transactions', $transactionParameters);

								if (!empty($amountMergedTransactions['count'])) {
									foreach ($amountMergedTransactions['data'] as $amountMergedTransaction) {
										$initialInvoiceIds[$amountMergedTransaction['initial_invoice_id']] = $amountMergedTransaction['initial_invoice_id'];
									}
								}

								if (!empty($upgradeTransactions['count'])) {
									foreach ($upgradeTransactions['data'] as $upgradeTransaction) {
										if (!in_array($upgradeTransaction['invoice_id'], $initialInvoiceIds)) {
											$pendingTransactions[] = array(
												'id' => $upgradeTransaction['id'],
												'initial_invoice_id' => null,
												'invoice_id' => $mostRecentPayableInvoice['id']
											);
										}
									}
								}

								$upgradeCancellationTransaction = array(
									'customer_email' => $parameters['user']['email'],
									'details' => 'Order upgrade request cancelled for order <a href="' . $this->settings['base_url'] . 'orders/' . $order['id'] . '">#' . $order['id'] . '</a>.<br>' . $order['quantity_pending'] . ' ' . $order['name'] . ' reverted to ' . $orderData[0]['quantity'] . ' ' . $order['name'] . '<br>' . $order['price_pending'] . ' ' . $order['currency'] . ' for ' . $order['interval_value_pending'] . ' ' . $order['interval_type_pending'] . ($order['interval_value_pending'] !== 1 ? 's' : '') . ' reverted to ' . $orderData[0]['price'] . ' ' . $order['currency'] . ' for ' . $order['interval_value'] . ' ' . $order['interval_type'] . ($order['interval_value'] !== 1 ? 's' : ''),
									'id' => ($upgradeCancellationTransactionId = uniqid() . time()),
									'initial_invoice_id' => $invoiceId,
									'invoice_id' => $mostRecentPayableInvoice['id'],
									'payment_amount' => null,
									'payment_currency' => $this->settings['billing']['currency'],
									'payment_status' => 'completed',
									'payment_status_message' => 'Order upgrade request cancelled.',
									'processed' => true,
									'transaction_charset' => $this->settings['database']['charset'],
									'transaction_date' => date('Y-m-d H:i:s', time()),
									'transaction_method' => 'Miscellaneous',
									'user_id' => $parameters['user']['id']
								);

								if ($amountToApplyToBalance > 0) {
									$pendingInvoices[] = array(
										'amount_paid' => $amountToApplyToBalance,
										'cart_items' => ($balanceTransferInvoiceIdentifier = sha1($upgradeCancellationTransactionId)),
										'currency' => $invoice['data']['invoice']['currency'],
										'due' => null,
										'payable' => true,
										'status' => 'paid',
										'subtotal' => $amountToApplyToBalance,
										'total' => $amountToApplyToBalance,
										'user_id' => $parameters['user']['id'],
										'warning_level' => 5
									);
									$upgradeCancellationTransaction['details'] .= '<br>' . number_format($amountToApplyToBalance, 2, '.', '') . ' ' . $order['currency'] . ' overpayment added to account balance.';
									$userData = array(
										array(
											'balance' => $parameters['user']['balance'] + $amountToApplyToBalance,
											'id' => $parameters['user']['id']
										)
									);
								}

								$pendingTransactions[] = $upgradeCancellationTransaction;

								if (
									$this->save('invoices', array_values(array_filter($pendingInvoices))) &&
									$this->save('invoice_orders', $invoiceOrders['data']) &&
									$this->save('orders', $orderData) &&
									$this->save('transactions', $pendingTransactions) &&
									$this->save('users', $userData)
								) {
									if ($additionalDueInvoiceIdentifier) {
										$initialInvoiceIds = $this->retrieveInvoiceIds(array(
											$invoiceId
										));
										$invoiceParameters['conditions'] = array(
											'cart_items' => $additionalDueInvoiceIdentifier
										);
										$additionalDueInvoice = $this->fetch('invoices', $invoiceParameters);

										if (!empty($additionalDueInvoice['count'])) {
											$initialInvoiceIds[] = $additionalDueInvoice['data'][0]['id'];
										}

										if (!empty($initialInvoiceIds)) {
											foreach ($initialInvoiceIds as $initialInvoiceId) {
												$this->invoice('invoices', array(
													'conditions' => array(
														'id' => $initialInvoiceId
													)
												));
											}
										}
									}

									if (!empty($balanceTransferInvoiceIdentifier)) {
										$balanceTransferInvoice = $this->fetch('invoices', array(
											'conditions' => array(
												'cart_items' => $balanceTransferInvoiceIdentifier
											),
											'fields' => array(
												'id'
											)
										));

										if (!empty($balanceTransferInvoiceId = $balanceTransferInvoice['data'][0])) {
											$balanceTransferTransactions = array(
												array(
													'customer_email' => $parameters['user']['email'],
													'details' => $upgradeCancellationTransaction['details'],
													'id' => uniqid() . time(),
													'initial_invoice_id' => $balanceTransferInvoiceId,
													'invoice_id' => $balanceTransferInvoiceId,
													'payment_amount' => 0,
													'payment_currency' => $this->settings['billing']['currency'],
													'payment_status' => 'completed',
													'payment_status_message' => 'Amount added to account balance.',
													'processed' => true,
													'transaction_charset' => $this->settings['database']['charset'],
													'transaction_date' => date('Y-m-d H:i:s', strtotime('+1 second')),
													'transaction_method' => 'Miscellaneous',
													'user_id' => $parameters['user']['id']
												)
											);
											$this->save('transactions', $balanceTransferTransactions);
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
				$invoiceParameters['conditions']['id'] = $this->retrieveInvoiceIds((array) $invoiceParameters['conditions']['id']);
				$invoiceParameters['sort'] = array(
					'field' => 'created',
					'order' => 'DESC'
				);
			}

			$invoiceData = $this->fetch($table, $invoiceParameters);

			if (!empty($invoiceData['count'])) {
				$invoiceData = $invoiceData['data'][0];

				if (is_numeric($invoiceData['merged_invoice_id'])) {
					$response['redirect'] = $this->settings['base_url'] . 'invoices/' . (!empty($invoiceData['merged_invoice_id']) ? $invoiceData['merged_invoice_id'] : null);
				} else {
					$invoiceData['created'] = date('M d Y', strtotime($invoiceData['created'])) . ' at ' . date('g:ia', strtotime($invoiceData['created'])) . ' ' . $this->settings['timezone']['display'];
					$invoiceItems = $this->_retrieveInvoiceItems($invoiceData);
					$invoiceOrders = $this->retrieveInvoiceOrders($invoiceData);
					$invoiceSubscriptions = $this->_retrieveInvoiceSubscriptions($invoiceData);
					$invoiceTransactions = $this->retrieveInvoiceTransactions($invoiceData);
					$invoiceUser = $this->_call('users', array(
						'methodName' => 'retrieveUser',
						'methodParameters' => array(
							$invoiceData
						)
					));

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
						$response['data'] = array_replace_recursive($response['data'], $this->calculateInvoicePaymentDetails($response['data']));
					}
				}
			}

			return $response;
		}

	/**
	 * List invoices
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array
	 */
		public function list($table, $parameters = array()) {
			return array();
		}

	/**
	 * Retrieve additional due invoice data
	 *
	 * @param array $invoiceData
	 *
	 * @return array $response
	 */
		public function retrieveDueInvoices($invoiceData) {
			$response = array();
			$dueInvoices = $this->fetch('invoices', array(
				'conditions' => array(
					'due >' => date('Y-m-d H:i:s', strtotime($invoiceData['due'])),
					'initial_invoice_id' => array_filter(array(
						$invoiceData['id'],
						$invoiceData['initial_invoice_id']
					)),
					'merged_invoice_id' => null,
					'status' => 'unpaid',
					'user_id' => $invoiceData['user_id'],
					'NOT' => array(
						'id' => $invoiceData['id']
					)
				),
				'fields' => array(
					'due',
					'id',
					'warning_level'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			));

			if (!empty($dueInvoices['count'])) {
				$response = $dueInvoices['data'];
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
		public function retrieveInvoiceIds($invoiceIds) {
			$invoiceIds = $response = array_unique(array_filter($invoiceIds));
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

			$invoices = $this->fetch('invoices', $invoiceParameters);

			if (!empty($invoices['count'])) {
				foreach ($invoices['data'] as $invoice) {
					$invoiceIds = array_merge($invoiceIds, array_values($invoice));
				}
			}

			$invoiceIds = array_unique(array_filter($invoiceIds));

			if (count($invoiceIds) > count($response)) {
				$response = $this->retrieveInvoiceIds($invoiceIds);
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
		public function retrieveInvoiceOrders($invoiceData) {
			$response = array();
			$invoiceIds = $this->retrieveInvoiceIds(array(
				$invoiceData['id'],
				$invoiceData['initial_invoice_id'],
				$invoiceData['merged_invoice_id']
			));
			$invoiceOrders = $this->fetch('invoice_orders', array(
				'conditions' => array(
					'invoice_id' => $invoiceIds
				),
				'fields' => array(
					'order_id'
				)
			));

			if (!empty($invoiceOrders['count'])) {
				$orders = $this->fetch('orders', array(
					'conditions' => array(
						'id' => $invoiceOrders['data'],
						'NOT' => array(
							'status' => 'merged'
						)
					),
					'fields' => array(
						'created',
						'currency',
						'id',
						'interval_type',
						'interval_type_pending',
						'interval_value',
						'interval_value_pending',
						'ip_version',
						'modified',
						'name',
						'price',
						'price_active',
						'price_pending',
						'product_id',
						'quantity',
						'quantity_active',
						'quantity_allocated',
						'quantity_pending',
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
	 * Retrieve invoice transaction data
	 *
	 * @param array $invoiceData
	 *
	 * @return array $response
	 */
		public function retrieveInvoiceTransactions($invoiceData) {
			$response = array();
			$invoiceTransactions = $this->fetch('transactions', array(
				'conditions' => array(
					'invoice_id' => $invoiceData['id'],
					'processed' => true,
					'processing' => false,
					'NOT' => array(
						'transaction_method' => 'PaymentRefunded'
					)
				),
				'fields' => array(
					'billing_address1',
					'billing_address2',
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
					'processed',
					'processing',
					'provider_country_code',
					'provider_email',
					'provider_id',
					'sandbox',
					'transaction_charset',
					'transaction_date',
					'transaction_method',
					'transaction_raw',
					'transaction_token',
					'user_id'
				),
				'sort' => array(
					'field' => 'transaction_date',
					'order' => 'ASC'
				)
			));
			$paymentMethods = $this->fetch('payment_methods', array(
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

					if (!empty($paymentMethods['data'][$invoiceTransaction['payment_method_id']])) {
						$invoiceTransactions['data'][$key]['payment_method'] = $paymentMethods['data'][$invoiceTransaction['payment_method_id']];
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
		public function retrieveMostRecentPayableInvoice($invoiceId) {
			$response = array();
			$invoiceIds = $this->retrieveInvoiceIds(array(
				$invoiceId
			));
			$invoice = $this->fetch('invoices', array(
				'conditions' => array(
					'merged_invoice_id' => null,
					'payable' => true,
					'remainder_pending' => null,
					'OR' => array(
						'id' => $invoiceIds,
						'initial_invoice_id' => $invoiceIds
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
	 * Retrieve previously-paid invoice data
	 *
	 * @param array $invoiceData
	 *
	 * @return array $response
	 */
		public function retrievePreviouslyPaidInvoices($invoiceData) {
			$response = array();
			$invoiceIds = $this->retrieveInvoiceIds(array(
				$invoiceData['id']
			));
			$orderMerges = $this->fetch('order_merges', array(
				'conditions' => array(
					'amount_merged >' => 0,
					'invoice_id' => $invoiceIds,
					'NOT' => array(
						'initial_invoice_id' => $invoiceIds
					)
				),
				'fields' => array(
					'initial_invoice_id'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			));

			if (!empty($orderMerges['count'])) {
				$invoiceIds = array_unique(array_merge($invoiceIds, $orderMerges['data']));
			}

			$previousInvoiceParameters = array(
				'conditions' => array(
					'id' => $invoiceIds,
					'created <' => date('Y-m-d H:i:s', strtotime($invoiceData['created'])),
					'due <' => date('Y-m-d H:i:s', strtotime($invoiceData['due'])),
					'OR' => array(
						'amount_paid >' => 0,
						'status' => 'paid'
					),
					'NOT' => array(
						'id' => $invoiceData['id']
					)
				),
				'fields' => array(
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
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			);
			$previousInvoices = $this->fetch('invoices', $previousInvoiceParameters);

			if (!empty($previousInvoices['count'])) {
				$response = $previousInvoices['data'];
			}

			return $response;
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
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function view($table, $parameters = array()) {
			if (
				empty($parameters['id']) ||
				!is_numeric($parameters['id'])
			) {
				$this->redirect($this->settings['base_url'] . 'invoices');
			}

			$response = array(
				'invoice_id' => $parameters['id']
			);
			return $response;
		}

	}
?>
