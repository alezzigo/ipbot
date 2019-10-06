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
			$invoice = $this->find('invoices', array(
				'conditions' => array(
					'OR' => array(
						'id' => $latestOrderInvoice['data'],
						'initial_invoice_id' => $latestOrderInvoice['data']
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
					'prorate_pending',
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
					$intervalKey = $order['interval_value'] . '_' . $order['interval_type'];
					$productIds[$order['product_id']] = $order['product_id'];
					$sortInterval = array_search($order['interval_type'], $sortIntervals) . '__';
					$groupedOrders[$sortInterval . $intervalKey][] = $selectedOrders[] = array(
						'invoice' => $this->_retrieveLatestOrderInvoice($order),
						'order' => $order
					);
					$response['data']['quantity'] += $order['quantity'];
					unset($orders['data'][$key]);
				}

				$invoices = array();
				$sortIntervalKeys = array_keys($groupedOrders);
				natsort($sortIntervalKeys);
				$largestInterval = explode('_', end(explode('__', ($largestIntervalKey = end($sortIntervalKeys)))));
				$mergedData = $groupedOrders[$largestIntervalKey][0];
				$mergedInterval = array(
					'interval_type_pending' => $largestInterval[1],
					'interval_value_pending' => (integer) $largestInterval[0]
				);
				$mergedData['order'] = array_merge($mergedData['order'], $mergedInterval);
				$mergedData['invoice']['amount_paid'] = $mergedData['order']['quantity'] = 0;

				foreach ($selectedOrders as $key => $selectedOrder) {
					$selectedOrders[$key] = array_merge($selectedOrder, array(
						'invoice_pending' => $pendingInvoices[$selectedOrder['invoice']['id']] = array(
							'id' => $pendingInvoiceIds[$selectedOrder['invoice']['id']] = $selectedOrder['invoice']['id'],
							'merged_invoice_id' => ($selectedOrder['invoice']['id'] !== $mergedData['invoice']['id'] ? $mergedData['invoice']['id'] : null)
						),
						'order_pending' => $pendingOrders[$selectedOrder['order']['id']] = array_merge($mergedInterval, array(
							'id' => $pendingOrderIds[] = $selectedOrder['order']['id'],
							'status' => 'merged'
						))
					));

					if (
						!in_array($selectedOrder['invoice']['id'], $invoices) &&
						!empty($selectedOrder['invoice']['amount_paid'])
					) {
						$invoices[$selectedOrder['invoice']['id']] = $selectedOrder['invoice']['id'];
						$mergedData['invoice']['amount_paid'] += $selectedOrder['invoice']['amount_paid'];
					}

					$mergedData['order']['quantity'] += $selectedOrder['order']['quantity'];
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
						$response['data']['upgrade_quantity'] = min($product['data'][0]['maximum_quantity'], max(0, $parameters['data']['upgrade_quantity']));
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
						$mergedData['order']['shipping_pending'] = $this->_calculateItemShippingPrice($pendingItem);
						$mergedData['order']['tax_pending'] = $this->_calculateItemTaxPrice($pendingItem);
						$mergedData['orders'][] = $mergedData['order'];
						$mergedData = array_replace_recursive($mergedData, $this->_calculateInvoicePaymentDetails($mergedData));
						$mergedData['invoice']['prorate_pending'] = $mergedData['invoice']['total_pending'];

						foreach ($selectedOrders as $key => $selectedOrder) {
							if (empty($processedInvoices[$selectedOrder['invoice']['id']])) {
								$mergedData['invoice']['prorate_pending'] -= min($selectedOrder['invoice']['total'], $selectedOrder['invoice']['amount_paid']);
							}

							if (!empty($selectedOrder['invoice']['initial_invoice_id'])) {
								$previousInvoice = $this->find('invoices', array(
									'conditions' => array(
										'due >' => date('Y-m-d H:i:s', strtotime($selectedOrder['invoice']['due'] . ' -' . $selectedOrder['order']['interval_value'] . ' ' . $selectedOrder['order']['interval_type'] . ' -1 day')),
										'id' => $selectedOrder['invoice']['initial_invoice_id'],
										'id !=' => $selectedOrder['invoice']['id']
									),
									'fields' => array(
										'amount_paid',
										'due',
										'id',
										'status',
										'total'
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
									($previousInvoiceData = $previousInvoice['data'][0]) &&
									empty($processedInvoices[$previousInvoiceData['id']])
								) {
									if (
										$previousInvoiceData['amount_paid'] < $previousInvoiceData['total'] &&
										$previousInvoiceData['status'] === 'unpaid'
									) {
										$mergedData['invoice']['prorate_pending'] -= $previousInvoiceData['amount_paid'];
									} else {
										$amountPaid = min($previousInvoiceData['total'], $previousInvoiceData['amount_paid']);
										$paidTime = max(1, time() - strtotime($previousInvoiceData['due']));
										$intervalTime = max(1, strtotime($selectedOrder['invoice']['due']) - strtotime($previousInvoiceData['due']));
										$proratePercentage = 1;

										if ($paidTime < $intervalTime) {
											$proratePercentage = 1 - (max(0, $paidTime / $intervalTime));
										}

										$mergedData['invoice']['prorate_pending'] -= ($proratePercentage * $amountPaid);
									}

									$processedInvoices[$previousInvoiceData['id']] = true;
								}
							}

							$processedInvoices[$selectedOrder['invoice']['id']] = true;
						}

						$mergedData['invoice']['prorate_pending'] = max(0, round($mergedData['invoice']['prorate_pending'] * 100) / 100);
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
							$pendingInvoices[$mergedData['invoice']['id']] = array_diff_key($mergedData['invoice'], array(
								'amount_due' => true,
								'amount_due_pending' => true,
								'due' => true,
								'modified' => true,
								'payment_currency_name' => true,
								'payment_currency_symbol' => true
							));
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
									'invoice_id' => $mergedData['invoice']['id']
								))));
							}

							if ($mergedData['invoice']['prorate_pending'] === 0) {
								$pendingTransactions[] = array(
									'customer_email' => $parameters['user']['email'],
									'id' => uniqid() . time(),
									'invoice_id' => $mergedData['invoice']['id'],
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
								$response['redirect'] = $this->settings['base_url'] . 'invoices/' . $mergedData['invoice']['id'];
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
