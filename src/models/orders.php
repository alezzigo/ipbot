<?php
/**
 * Orders Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/invoices.php');

class OrdersModel extends InvoicesModel {

/**
 * Retrieve latest order invoice data
 *
 * @param array $orderData
 *
 * @return array $response
 */
	protected function _retrieveLatestOrderInvoice($orderData) {
		$response = array();
		$latestOrderInvoice = $this->find('invoice_orders', array(
			'conditions' => array(
				'order_id' => $orderData['id']
			),
			'fields' => array(
				'invoice_id'
			),
			'limit' => 1
		));

		if (!empty($latestOrderInvoice['count'])) {
			$invoiceIds = $this->_retrieveInvoiceIds($latestOrderInvoice['data']);
			$invoice = $this->find('invoices', array(
				'conditions' => array(
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
					'due',
					'id',
					'initial_invoice_id',
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
					'field' => 'due',
					'order' => 'DESC'
				)
			));

			if (!empty($invoice['count'])) {
				$response = $invoice['data'][0];
			}
		}

		return $response;
	}

/**
 * Process order downgrade requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function downgrade($table, $parameters) {
		$response = array();
		// ..
		return $response;
	}

/**
 * List orders
 *
 * @return array
 */
	public function list() {
		return array();
	}

/**
 * Process order upgrade requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function upgrade($table, $parameters) {
		$response = array(
			'data' => array(),
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your order upgrade request, please try again.')
			)
		);

		if (!empty($orderIds = array_values($parameters['data']['orders']))) {
			$orders = $this->find('orders', array(
				'conditions' => array(
					'id' => $orderIds,
					'user_id' => $parameters['user']['id']
				),
				'fields' => array(
					'created',
					'id',
					'interval_type',
					'interval_type_pending',
					'interval_value',
					'interval_value_pending',
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
				$groupedOrders = $pendingInvoices = $pendingInvoiceIds = $pendingOrders = $pendingOrderIds = $pendingProxies = $pendingProxyGroups = $pendingTransactions = $processedInvoices = $productIds = $selectedOrders = array();
				$sortIntervals = array(
					'day',
					'week',
					'month',
					'year'
				);

				foreach ($orders['data'] as $key => $order) {
					$intervalType = $order['interval_type_pending'] ? $order['interval_type_pending'] : $order['interval_type'];
					$intervalValue = $order['interval_value_pending'] ? $order['interval_value_pending'] : $order['interval_value'];
					$intervalKey = $intervalValue . '_' . $intervalType;
					$productIds[$order['product_id']] = $order['product_id'];
					$sortInterval = array_search($intervalType, $sortIntervals) . '__';
					$groupedOrders[$sortInterval . $intervalKey][] = $selectedOrders[] = array(
						'invoice' => $this->_retrieveLatestOrderInvoice($order),
						'order' => $order
					);
				}

				$sortIntervalKeys = array_keys($groupedOrders);
				natsort($sortIntervalKeys);
				$largestInterval = explode('_', end(explode('__', ($largestIntervalKey = end($sortIntervalKeys)))));
				$mergedData = $groupedOrders[$largestIntervalKey][0];
				$mergedInterval = array(
					'interval_type_pending' => $largestInterval[1],
					'interval_value_pending' => (integer) $largestInterval[0]
				);
				$mergedData['order'] = array_merge($mergedData['order'], $mergedInterval);
				$mergedData['invoice']['amount_paid'] = $mergedData['order']['quantity'] = $mergedData['order']['quantity_active'] = 0;

				foreach ($selectedOrders as $key => $selectedOrder) {
					$invoiceId = $selectedOrder['invoice']['id'];
					$pendingInvoice = !empty($pendingInvoices[$invoiceId]) ? $pendingInvoices[$invoiceId] : array();
					$selectedOrders[$key] = array_merge($selectedOrder, array(
						'invoice_pending' => $pendingInvoices[$invoiceId] = array_merge(array(
							'amount_paid' => $selectedOrder['invoice']['amount_paid'],
							'id' => $pendingInvoiceIds[$invoiceId] = $invoiceId
						), $pendingInvoice),
						'order_pending' => $pendingOrders[$selectedOrder['order']['id']] = array_merge($mergedInterval, array(
							'id' => $pendingOrderIds[] = $selectedOrder['order']['id'],
							'quantity_active' => 0,
							'status' => 'merged'
						))
					));

					if (!empty($pendingInvoices[$invoiceId]['amount_paid'])) {
						$selectedOrders[$key]['total'] = $selectedOrder['order']['price'] + $selectedOrder['order']['shipping'] + $selectedOrder['order']['tax'];
						$amountPaid = min($selectedOrders[$key]['total'], $pendingInvoices[$invoiceId]['amount_paid']);
						$mergedData['invoice']['amount_paid'] += $amountPaid;
						$pendingInvoices[$invoiceId]['amount_paid'] = max(0, ceil(($pendingInvoices[$invoiceId]['amount_paid'] - $amountPaid) * 100) / 100);
					}

					$mergedData['order']['quantity'] += (!empty($selectedOrder['order']['quantity_pending']) ? $selectedOrder['order']['quantity_pending'] : $selectedOrder['order']['quantity']);
					$mergedData['order']['quantity_active'] += $selectedOrder['order']['quantity_active'];
				}

				foreach ($pendingInvoices as $invoiceId => $pendingInvoice) {
					if (!empty($pendingInvoice['amount_paid'])) {
						unset($pendingInvoiceIds[$invoiceId]);
					}
				}

				if (
					!empty($productIds) &&
					count($productIds) === 1 &&
					($productId = key($productIds))
				) {
					$product = $this->find('products', array(
						'conditions' => array(
							'id' => $productId
						),
						'fields' => array(
							'id',
							'maximum_quantity',
							'minimum_quantity',
							'name',
							'price_per',
							'type',
							'volume_discount_divisor',
							'volume_discount_multiple'
						)
					));

					if (!empty($product['count'])) {
						$response['data']['product'] = $product['data'][0];
						$response['data']['upgrade_quantity'] = min($product['data'][0]['maximum_quantity'], max((count($selectedOrders) === 1 ? 1 : 0), $parameters['data']['upgrade_quantity']));
						$mergedData['order']['quantity_pending'] = $mergedData['order']['quantity'] + $response['data']['upgrade_quantity'];
						$mergedData['order']['price'] = $this->_calculateItemPrice($order = array(
							'interval_type' => $mergedData['order']['interval_type'],
							'interval_value' => $mergedData['order']['interval_value'],
							'price_per' => $response['data']['product']['price_per'],
							'quantity' => $mergedData['order']['quantity'],
							'volume_discount_divisor' => $response['data']['product']['volume_discount_divisor'],
							'volume_discount_multiple' => $response['data']['product']['volume_discount_multiple']
						));
						$mergedData['order']['price_pending'] = $this->_calculateItemPrice(array_merge($order, array(
							'interval_type' => $mergedInterval['interval_type_pending'],
							'interval_value' => $mergedInterval['interval_value_pending'],
							'quantity' => $mergedData['order']['quantity_pending']
						)));
						$pendingItem = array_merge(array(
							'price' => $mergedData['order']['price_pending'],
							'quantity' => $mergedData['order']['quantity_pending']
						), $response['data']['product']);
						$mergedData['order'] = array_merge($mergedData['order'], array(
							'shipping_pending' => $this->_calculateItemShippingPrice($pendingItem),
							'tax_pending' => $this->_calculateItemTaxPrice($pendingItem)
						));
						$mergedData['orders'][] = $mergedData['order'];
						$mergedData = array_replace_recursive($mergedData, $this->_calculateInvoicePaymentDetails($mergedData));
						$mergedData['invoice']['remainder_pending'] = $mergedData['invoice']['total_pending'];

						foreach ($selectedOrders as $key => $selectedOrder) {
							$mergedData['invoice']['remainder_pending'] -= min($selectedOrder['total'], $selectedOrder['invoice']['amount_paid']);

							if (!empty($selectedOrder['invoice']['initial_invoice_id'])) {
								$previousInvoice = $this->find('invoices', array(
									'conditions' => array(
										'id !=' => $selectedOrder['invoice']['id'],
										'OR' => array(
											'id' => $selectedOrder['invoice']['initial_invoice_id'],
											'initial_invoice_id' => $selectedOrder['invoice']['initial_invoice_id']
										)
									),
									'fields' => array(
										'amount_paid',
										'due',
										'id',
										'status',
										'total',
										'total_pending'
									),
									'limit' => 1,
									'sort' => array(
										'field' => 'due',
										'order' => 'DESC'
									)
								));

								if (
									!empty($previousInvoice['count']) &&
									$previousInvoice['data'][0]['amount_paid'] > 0 &&
									($previousInvoiceData = $previousInvoice['data'][0])
								) {
									// ..

									if (
										$previousInvoiceData['amount_paid'] < $previousInvoiceData['total'] &&
										$previousInvoiceData['status'] === 'unpaid'
									) {
										$mergedData['invoice']['remainder_pending'] -= min($selectedOrder['order']['price'], $previousInvoiceData['amount_paid']);
									} else {
										$amountPaid = min($selectedOrder['order']['price'], $previousInvoiceData['amount_paid']);
										$paidTime = max(1, time() - strtotime($previousInvoiceData['due']));
										$intervalTime = max(1, strtotime($selectedOrder['invoice']['due']) - strtotime($previousInvoiceData['due']));
										$proratePercentage = 1;

										if ($paidTime < $intervalTime) {
											$proratePercentage = 1 - (max(0, $paidTime / $intervalTime));
										}

										$mergedData['invoice']['remainder_pending'] -= ($proratePercentage * $amountPaid);
									}

									$pendingInvoiceIds[$previousInvoiceData['id']] = $previousInvoiceData['id'];
								}
							}
						}

						$mergedData['invoice']['remainder_pending'] = max(0, ceil($mergedData['invoice']['remainder_pending'] * 100) / 100);
						$response['data']['merged'] = $mergedData;
						$response['message'] = array(
							'status' => 'success',
							'text' => ''
						);

						if (!empty($parameters['data']['confirm_upgrade'])) {
							$response['message'] = array(
								'status' => 'error',
								'text' => $defaultMessage
							);
							$mergedData['invoice'] = array_merge($mergedData['invoice'], array(
								'cart_items' => sha1(uniqid() . $mergedData['invoice']['cart_items']),
								'payable' => true
							));
							$mergedInvoiceData = array(
								array_diff_key($mergedData['invoice'], array(
									'amount_due' => true,
									'amount_due_pending' => true,
									'created' => true,
									'due' => true,
									'id' => true,
									'modified' => true,
									'payment_currency_name' => true,
									'payment_currency_symbol' => true
								))
							);

							if ($this->save('invoices', $mergedInvoiceData)) {
								$mergedInvoice = $this->find('invoices', array(
									'conditions' => array(
										'cart_items' => $mergedData['invoice']['cart_items'],
										'user_id' => $mergedData['invoice']['user_id']
									),
									'fields' => array_merge(array_keys($mergedInvoiceData[0]), array(
										'id'
									)),
									'limit' => 1,
									'sort' => array(
										'field' => 'created',
										'order' => 'DESC'
									)
								));

								if (!empty($mergedInvoice['count'])) {
									$mergedInvoiceId = $mergedInvoice['data'][0]['id'];

									foreach ($pendingInvoices as $invoiceId => $pendingInvoice) {
										$pendingInvoices[$invoiceId]['merged_invoice_id'] = $mergedInvoiceId;
									}

									$pendingInvoices[$mergedData['invoice']['id'] . '_merged'] = array(
										'id' => $mergedInvoiceId
									);
								}

								$pendingOrders[$mergedData['order']['id']] = $mergedData['order'];
								$proxyParameters = array(
									'conditions' => array(
										'order_id' => $pendingOrderIds
									),
									'fields' => array(
										'id',
										'order_id'
									)
								);
								$proxies = $this->find('proxies', $proxyParameters);

								if (!empty($proxies['count'])) {
									foreach ($proxies['data'] as $key => $proxy) {
										$proxies['data'][$key]['order_id'] = $mergedData['order']['id'];
									}

									$pendingProxies = array_values($proxies['data']);
								}

								$proxyGroups = $this->find('proxy_groups', $proxyParameters);

								if (!empty($proxyGroups['count'])) {
									foreach ($proxyGroups['data'] as $key => $proxyGroup) {
										$proxyGroups['data'][$key]['order_id'] = $mergedData['order']['id'];
									}

									$pendingProxyGroups = array_values($proxyGroups['data']);
								}

								$transactions = $this->find('transactions', array(
									'conditions' => array(
										'invoice_id' => array_values($pendingInvoiceIds)
									),
									'fields' => array(
										'id',
										'invoice_id'
									)
								));

								if (!empty($transactions['count'])) {
									$pendingTransactions = array_values(array_replace_recursive($transactions['data'], array_fill(0, $transactions['count'], array(
										'invoice_id' => $mergedInvoiceId
									))));
								}

								if ($mergedData['invoice']['remainder_pending'] === 0) {
									$pendingTransactions[] = array(
										'customer_email' => $parameters['user']['email'],
										'id' => uniqid() . time(),
										'invoice_id' => $mergedInvoiceId,
										'payment_amount' => 0,
										'payment_currency' => $this->settings['billing']['currency_name'],
										'payment_method_id' => 'balance',
										'payment_status' => 'completed',
										'payment_status_message' => ($response['data']['upgrade_quantity'] ? 'Upgrade' : 'Merge') . ' successful.',
										'transaction_charset' => $this->settings['database']['charset'],
										'transaction_date' => date('Y-m-d h:i:s', time()),
										'transaction_method' => 'PaymentCompleted',
										'user_id' => $parameters['user']['id']
									);
								}

								if (
									$this->save('invoices', array_values($pendingInvoices)) &&
									$this->save('orders', array_values($pendingOrders)) &&
									$this->save('proxies', $pendingProxies) &&
									$this->save('proxy_groups', $pendingProxyGroups) &&
									$this->save('transactions', $pendingTransactions)
								) {
									$response['message'] = array(
										'status' => 'success',
										'text' => 'Redirecting to merged invoice for payment, please wait.'
									);
									$response['redirect'] = $this->settings['base_url'] . 'invoices/' . $mergedInvoiceId;

									foreach ($pendingInvoices as $invoiceId => $pendingInvoice) {
										if (!empty($invoiceId)) {
											$this->invoice('invoices', array(
												'conditions' => array(
													'id' => $invoiceId
												)
											));
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
 * View order
 *
 * @param array $parameters
 *
 * @return array $response
 */
	public function view($parameters) {
		if (
			empty($orderId = $parameters['id']) ||
			!is_numeric($orderId)
		) {
			$this->redirect($this->settings['base_url'] . 'orders');
		}

		$response = array(
			'order_id' => $parameters['id'],
			'results_per_page' => 50
		);
		return $response;
	}

}
