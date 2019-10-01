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
					'modified',
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
				$groupedOrders = $pendingInvoices = $pendingOrders = $pendingOrderIds = $productIds = $selectedOrders = array();
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

				$sortIntervalKeys = array_keys($groupedOrders);
				natsort($sortIntervalKeys);
				$largestInterval = explode('_', end(explode('__', ($largestIntervalKey = end($sortIntervalKeys)))));
				$mergedData = $groupedOrders[$largestIntervalKey][0];
				$invoices = array();

				foreach ($selectedOrders as $key => $selectedOrder) {
					$selectedOrders[$key] = array_merge($selectedOrder, array(
						'invoice_pending' => $pendingInvoices[$selectedOrder['invoice']['id']] = array(
							'id' => $selectedOrder['invoice']['id'],
							'merged_invoice_id' => ($selectedOrder['invoice']['id'] !== $mergedData['invoice']['id'] ? $mergedData['invoice']['id'] : null)
						),
						'order_pending' => $pendingOrders[$selectedOrder['order']['id']] = array(
							'id' => $pendingOrderIds[] = $selectedOrder['order']['id'],
							'interval_type_pending' => $largestInterval[1],
							'interval_value_pending' => $largestInterval[0]
						)
					));

					if ($selectedOrder['invoice']['id'] !== $mergedData['invoice']['id']) {
						if (
							!in_array($selectedOrder['invoice']['id'], $invoices) &&
							!empty($selectedOrder['invoice']['amount_paid'])
						) {
							$invoices[$selectedOrder['invoice']['id']] = $selectedOrder['invoice']['id'];
							$mergedData['invoice']['amount_paid'] += $selectedOrder['invoice']['amount_paid'];
						}

						$mergedData['order']['quantity'] += $selectedOrder['order']['quantity'];
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
						$response['data']['upgrade_quantity'] = min($product['data'][0]['maximum_quantity'], max(1, $parameters['data']['upgrade_quantity']));
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
							'interval_type' => $mergedData['order']['interval_type_pending'],
							'interval_value' => $mergedData['order']['interval_value_pending'],
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
						$response['data']['merged'] = $mergedData;
						$response['message'] = $successMessage = array(
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
								'due' => true,
								'payment_currency_name' => true,
								'payment_currency_symbol' => true
							));
							$pendingOrders[$mergedData['order']['id']] = $mergedData['order'];

							if (
								$this->save('invoices', array_values($pendingInvoices)) &&
								$this->save('orders', array_values($pendingOrders))
							) {
								$proxies = $this->find('proxies', array(
									'conditions' => array(
										'order_id' => $pendingOrderIds
									),
									'fields' => array(
										'id',
										'order_id'
									)
								));

								if (!empty($proxies['count'])) {
									$response['message'] = array(
										'status' => 'error',
										'text' => $defaultMessage
									);

									foreach ($proxies['data'] as $key => $proxy) {
										$proxies['data'][$key]['order_id'] = $mergedData['order']['id'];
									}

									if ($this->save('proxies', $proxies['data'])) {
										$response['message'] = $successMessage;
									}
								}

								$response['message'] = $successMessage;
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
