<?php

if (!empty($config->settings['base_path'])) {
	require_once($config->settings['base_path'] . '/models/app.php');
}

class OrdersModel extends AppModel {

/**
 * Retrieve available server node details
 *
 * @param array $orderData
 *
 * @return array $response
 */
	protected function _retrieveAvailableServerNodeDetails($orderData) {
		$response = array();
		$servers = $this->fetch('servers', array(
			'conditions' => array(
				'server_configuration_type' => $orderData['type'],
				'status' => 'online'
			),
			'fields' => array(
				'city',
				'country_code',
				'country_name',
				'id',
				'ip',
				'isp',
				'region',
				'server_configuration_type',
				'status'
			)
		));

		if (!empty($servers['count'])) {
			foreach ($servers['data'] as $server) {
				$availableServerNodeLocation = $this->fetch('nodes', array(
					'conditions' => array(
						'allocated' => false,
						'processing' => false,
						'server_id' => $server['id']
					),
					'fields' => array(
						'id'
					),
					'limit' => 1
				));

				if ($availableServerNodeLocation['count']) {
					$key = strtolower($server['city'] . $server['region'] . $server['country_code'] . $server['country_name']);
					$response['nodeLocations'][$key] = array(
						'city' => $server['city'],
						'count' => (!empty($response[$key]) ? $response[$key] : 0) + $availableServerNodeLocation['count'],
						'country_code' => $server['country_code'],
						'country_name' => $server['country_name'],
						'region' => $server['region'],
					);
				}

				$response['nodeLocations'] = array_values($response['nodeLocations']);
				$response['nodeSubnets'] = array(); // TODO
			}
		}

		return $response;
	}

/**
 * Retrieve order IDs
 *
 * @param array $orderIds
 *
 * @return array $response
 */
	protected function _retrieveOrderIds($orderIds) {
		$orderIds = $response = array_unique(array_filter($orderIds));
		$orderParameters = array(
			'conditions' => array(
				'OR' => array(
					'id' => $orderIds,
					'merged_order_id' => $orderIds
				)
			),
			'fields' => array(
				'id',
				'merged_order_id'
			)
		);

		$orders = $this->fetch('orders', $orderParameters);

		if (!empty($orders['count'])) {
			foreach ($orders['data'] as $order) {
				$orderIds = array_merge($orderIds, array_values($order));
			}
		}

		$orderIds = array_unique(array_filter($orderIds));

		if (count($orderIds) > count($response)) {
			$response = $this->_retrieveOrderIds($orderIds);
		}

		return $response;
	}

/**
 * Process API endpoint settings
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function endpoint($table, $parameters = array()) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your API endpoint settings request, please try again.')
			)
		);

		if (!empty($orderId = $parameters['data']['order_id'])) {
			$order = $this->fetch('orders', array(
				'conditions' => array(
					'id' => $orderId,
					'user_id' => $parameters['user']['id']
				),
				'fields' => array(
					'id',
					'endpoint_enable',
					'endpoint_password',
					'endpoint_require_authentication',
					'endpoint_require_match',
					'endpoint_username',
					'endpoint_whitelisted_ips'
				),
				'limit' => 1
			));

			if (!empty($order['count'])) {
				$response = array(
					'data' => $order['data'][0],
					'message'=> array(
						'status' => 'success',
						'text' => ''
					)
				);

				if (isset($parameters['data']['endpoint_enable'])) {
					if (
						(
							!empty($parameters['data']['endpoint_username']) ||
							!empty($parameters['data']['endpoint_password'])
						) &&
						(
							empty($parameters['data']['endpoint_username']) ||
							empty($parameters['data']['endpoint_password'])
						)
					) {
						$response['message'] = array(
							'status' => 'error',
							'text' => 'Both username and password must be either set or empty.'
						);
					} else {
						$orderData = array(
							array(
								'id' => $response['data']['id'],
								'endpoint_enable' => $parameters['data']['endpoint_enable'],
								'endpoint_password' => $parameters['data']['endpoint_password'],
								'endpoint_require_authentication' => $parameters['data']['endpoint_require_authentication'],
								'endpoint_require_match' => $parameters['data']['endpoint_require_match'],
								'endpoint_username' => $parameters['data']['endpoint_username'],
								'endpoint_whitelisted_ips' => $parameters['data']['endpoint_whitelisted_ips']
							)
						);

						if ($this->save('orders', $orderData)) {
							$response['message'] = array(
								'status' => 'success',
								'text' => 'Order API endpoint settings applied successfully.'
							);
						} else {
							$response['message'] = array(
								'status' => 'error',
								'text' => $defaultMessage
							);
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * List orders
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
 * Retrieve most recent order invoice data
 *
 * @param array $orderData
 *
 * @return array $response
 */
	public function retrieveMostRecentOrderInvoice($orderData) {
		$response = array();
		$mostRecentOrderInvoice = $this->fetch('invoice_orders', array(
			'conditions' => array(
				'order_id' => $orderData['id']
			),
			'fields' => array(
				'invoice_id'
			),
			'limit' => 1
		));

		if (!empty($mostRecentOrderInvoice['count'])) {
			$invoiceIds = $this->_call('invoices', array(
				'methodName' => 'retrieveInvoiceIds',
				'methodParameters' => array(
					$mostRecentOrderInvoice['data']
				)
			));
			$invoice = $this->fetch('invoices', array(
				'conditions' => array(
					'merged_invoice_id' => null,
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
					'user_id',
					'warning_level'
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
 * Shell method for processing new orders
 *
 * @param string $table
 *
 * @return array $response
 */
	public function shellProcessNewOrders($table) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'Error processing new orders, please try again.'
			)
		);

		// ..

		return $response;
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

		if (!empty($parameters['data']['orders'])) {
			$orderIds = array_values($parameters['data']['orders']);
			$orders = $this->fetch('orders', array(
				'conditions' => array(
					'id' => $orderIds,
					'user_id' => $parameters['user']['id']
				),
				'fields' => array(
					'created',
					'currency',
					'id',
					'interval_type',
					'interval_type_pending',
					'interval_value',
					'interval_value_pending',
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
				$groupedOrders = $groupedOrderMerges = $invoiceIds = $pendingAmountMergedTransactions = $pendingInvoices = $pendingInvoiceIds = $pendingInvoiceOrders = $pendingOrders = $pendingOrderMerges = $pendingOrderIds = $pendingProxies = $pendingProxyGroups = $pendingTransactions = $processedInvoices = $productIds = $selectedOrders = array();
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
						'invoice' => $this->retrieveMostRecentOrderInvoice($order),
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
				$mergedData['invoice']['amount_paid'] = $mergedData['order']['price_active'] = $mergedData['order']['quantity'] = $mergedData['order']['quantity_active'] = 0;

				foreach ($selectedOrders as $key => $selectedOrder) {
					$invoiceIds[] = $invoiceId = $selectedOrder['invoice']['id'];
					$pendingInvoice = !empty($pendingInvoices[$invoiceId]) ? $pendingInvoices[$invoiceId] : array();
					$selectedOrders[$key] = array_merge_recursive($selectedOrder, array(
						'order' => array(
							'total' => (($selectedOrder['order']['price_pending'] ? $selectedOrder['order']['price_pending'] : $selectedOrder['order']['price']) + ($selectedOrder['order']['shipping_pending'] ? $selectedOrder['order']['shipping_pending'] : $selectedOrder['order']['shipping']) + ($selectedOrder['order']['tax_pending'] ? $selectedOrder['order']['tax_pending'] : $selectedOrder['order']['tax']))
						)
					));
					$pendingInvoices[$invoiceId] = array_merge(array(
						'amount_paid' => $selectedOrder['invoice']['amount_paid'],
						'id' => ($pendingInvoiceIds[$invoiceId] = $invoiceId),
						'initial_invoice_id' => $selectedOrder['invoice']['initial_invoice_id']
					), $pendingInvoice);
					$pendingOrders[$selectedOrder['order']['id']] = array_merge($mergedInterval, array(
						'id' => $pendingOrderIds[] = $selectedOrder['order']['id'],
						'quantity_active' => 0,
						'status' => 'merged'
					));

					if (!empty($pendingInvoices[$invoiceId]['amount_paid'])) {
						$amountPaid = min($selectedOrders[$key]['order']['total'], $pendingInvoices[$invoiceId]['amount_paid']);
						$amountToApplyToBalance += $amountPaid;
						$mergedData['invoice']['amount_paid'] += $amountPaid;
						$pendingInvoices[$invoiceId]['amount_paid'] = max(0, round(($pendingInvoices[$invoiceId]['amount_paid'] - $amountPaid) * 100) / 100);
					}

					$mergedData['order']['price_active'] += $selectedOrder['order']['price_active'];
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
					$product = $this->fetch('products', array(
						'conditions' => array(
							'id' => $productId
						),
						'fields' => array(
							'id',
							'maximum_quantity',
							'minimum_quantity',
							'name',
							'price_per',
							'type'
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
							'quantity' => $mergedData['order']['quantity']
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
						$mergedData = array_replace_recursive($mergedData, $this->_call('invoices', array(
							'methodName' => 'calculateInvoicePaymentDetails',
							'methodParameters' => array(
								$mergedData,
								false
							)
						)));
						$mergedData['invoice']['remainder_pending'] = $mergedData['invoice']['total_pending'];
						$orderMergeParameters = array(
							'conditions' => array(
								'amount_merged >' => 0,
								'OR' => array(
									'initial_invoice_id' => $invoiceIds,
									'invoice_id' => $invoiceIds
								)
							),
							'fields' => array(
								'amount_merged',
								'due',
								'initial_invoice_id',
								'initial_order_id',
								'interval_type',
								'interval_value',
								'invoice_id',
								'order_id'
							),
							'sort' => array(
								'field' => 'created',
								'order' => 'DESC'
							)
						);
						$orderMerges = $this->fetch('order_merges', $orderMergeParameters);

						if (!empty($orderMerges['count'])) {
							foreach ($orderMerges['data'] as $orderMerge) {
								$groupedOrderMerges[$orderMerge['interval_value'] . $orderMerge['interval_type'] . $orderMerge['order_id'] . '_' . $orderMerge['invoice_id']] = $orderMerge;
							}
						}

						foreach ($selectedOrders as $key => $selectedOrder) {
							$previouslyPaidInvoices = $this->_call('invoices', array(
								'methodName' => 'retrievePreviouslyPaidInvoices',
								'methodParameters' => array(
									$selectedOrder['invoice']
								)
							));

							foreach ($previouslyPaidInvoices as $previouslyPaidInvoice) {
								$orderMerge = array(
									'due' => $selectedOrder['invoice']['due'],
									'initial_invoice_id' => $previouslyPaidInvoice['id'],
									'initial_order_id' => $selectedOrder['order']['id'],
									'interval_type' => $selectedOrder['order']['interval_type'],
									'interval_value' => $selectedOrder['order']['interval_value'],
									'invoice_id' => $selectedOrder['invoice']['id'],
									'order_id' => $mergedData['order']['id']
								);
								$pendingInvoices[$previouslyPaidInvoice['id']] = array_merge(!empty($pendingInvoices[$previouslyPaidInvoice['id']]) ? $pendingInvoices[$previouslyPaidInvoice['id']] : array(), array(
									'id' => $previouslyPaidInvoice['id'],
									'merged_invoice_id' => null
								));
								$previouslyPaidInvoiceIds = array_filter(array(
									$previouslyPaidInvoice['id'],
									$previouslyPaidInvoice['initial_invoice_id']
								));
								$orderMergeParameters['conditions'] = array(
									'amount_merged >' => 0,
									'initial_invoice_id' => $previouslyPaidInvoiceIds,
									'order_id' => $this->_retrieveOrderIds(array(
										$selectedOrder['order']['id']
									))
								);
								$previousOrderMerges = $this->fetch('order_merges', $orderMergeParameters);
								$amountAvailableToMerge = $selectedOrder['order']['total'];

								if (!empty($previousOrderMerges['count'])) {
									$amountAvailableToMerge = 0;

									foreach ($previousOrderMerges['data'] as $previousOrderMerge) {
										$amountAvailableToMerge += $previousOrderMerge['amount_merged'];
									}
								}

								$amountAvailableToMerge = min($amountAvailableToMerge, $previouslyPaidInvoice['amount_paid']);
								$intervalTime = strtotime($orderMerge['due'] . ' +' . $orderMerge['interval_value'] . ' ' . $orderMerge['interval_type']) - strtotime($orderMerge['due']);
								$paidTime = max(strtotime($orderMerge['due']), time()) - strtotime($orderMerge['due']);
								$remainderPercentage = 0;

								if ($paidTime < $intervalTime) {
									$remainderPercentage = (round((1 - (max(0, $paidTime / $intervalTime))) * 100) / 100);
								}

								$amountMerged = $orderMerge['amount_merged'] = ($remainderPercentage * $amountAvailableToMerge);
								$orderMergeKey = $selectedOrder['order']['interval_value'] . $selectedOrder['order']['interval_type'] . $selectedOrder['order']['id'] . '_' . $previouslyPaidInvoice['id'];
								$mergedData['invoice']['remainder_pending'] -= $amountMerged;
								$pendingAmountMergedTransactions[] = array(
									'customer_email' => $parameters['user']['email'],
									'id' => uniqid() . time(),
									'initial_invoice_id' => $previouslyPaidInvoice['id'],
									'invoice_id' => $previouslyPaidInvoice['id'],
									'payment_amount' => $amountMerged,
									'payment_currency' => $this->settings['billing']['currency'],
									'payment_status' => 'completed',
									'transaction_charset' => $this->settings['database']['charset'],
									'transaction_date' => date('Y-m-d H:i:s', time()),
									'transaction_method' => 'Miscellaneous',
									'transaction_processed' => true,
									'user_id' => $parameters['user']['id']
								);

								if (
									empty($groupedOrderMerges[$orderMergeKey]) &&
									empty($previousOrderMerges['count'])
								) {
									$pendingOrderMerges[] = $groupedOrderMerges[$orderMergeKey] = $orderMerge;
								}
							}
						}

						$mergedData['invoice'] = array_merge($mergedData['invoice'], array(
							'currency' => $this->settings['billing']['currency'], // TODO: Calculate payment amounts from pendingInvoices to match config currency
							'remainder_pending' => max(0, round($mergedData['invoice']['remainder_pending'] * 100) / 100)
						));
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
								'merged_invoice_id' => 0,
								'payable' => true
							));
							$mergedInvoiceData = array(
								array_diff_key($mergedData['invoice'], array(
									'amount_due' => true,
									'amount_due_pending' => true,
									'created' => true,
									'due' => true,
									'id' => true,
									'initial_invoice_id' => true,
									'modified' => true
								))
							);

							if ($this->save('invoices', $mergedInvoiceData)) {
								$mergedInvoice = $this->fetch('invoices', array(
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
										if (
											!array_key_exists('merged_invoice_id', $pendingInvoice) &&
											$pendingInvoice['initial_invoice_id'] != $mergedInvoiceId
										) {
											$pendingInvoices[$invoiceId] = array_merge($pendingInvoices[$invoiceId], array(
												'merged_invoice_id' => $mergedInvoiceId
											));
										}
									}

									foreach ($pendingOrderMerges as $key => $pendingOrderMerge) {
										$pendingOrderMerges[$key]['invoice_id'] = $mergedInvoiceId;
									}

									foreach ($selectedOrders as $key => $selectedOrder) {
										$additionalOrders = $this->_call('invoices', array(
											'methodName' => 'retrieveInvoiceOrders',
											'methodParameters' => array(
												$selectedOrder['invoice']
											)
										));

										if (!empty($additionalOrders)) {
											foreach ($additionalOrders as $additionalOrder) {
												if (!in_array($additionalOrder['id'], $pendingOrderIds)) {
													$pendingInvoices[$selectedOrder['invoice']['id']]['merged_invoice_id'] = null;
													unset($pendingInvoices[$selectedOrder['invoice']['id']]['warning_level']);
												}
											}

											if (!$pendingInvoices[$selectedOrder['invoice']['id']]['merged_invoice_id']) {
												$pendingTransactions[] = array(
													'customer_email' => $parameters['user']['email'],
													'details' => 'Order <a href="' . $this->settings['base_url'] . 'orders/' . $selectedOrder['order']['id'] . '">#' . $selectedOrder['order']['id'] . '</a> merged to invoice <a href="' . $this->settings['base_url'] . 'invoices/' . $mergedInvoiceId . '">#' . $mergedInvoiceId . '</a><br> ' . $selectedOrder['order']['quantity'] . ' ' . $selectedOrder['order']['name'] . '<br> ' . $selectedOrder['order']['total'] . ' ' . $this->settings['billing']['currency'] . ' for ' . $selectedOrder['order']['interval_value'] . ' ' . $selectedOrder['order']['interval_type'] . ($selectedOrder['order']['interval_value'] !== 1 ? 's' : ''),
													'id' => uniqid() . time(),
													'initial_invoice_id' => $selectedOrder['invoice']['id'],
													'invoice_id' => $selectedOrder['invoice']['id'],
													'payment_amount' => null,
													'payment_currency' => $this->settings['billing']['currency'],
													'payment_status' => 'completed',
													'payment_status_message' => 'Order merged to new invoice.',
													'transaction_charset' => $this->settings['database']['charset'],
													'transaction_date' => date('Y-m-d H:i:s', time()),
													'transaction_method' => 'Miscellaneous',
													'transaction_processed' => true,
													'user_id' => $parameters['user']['id']
												);
											}
										}
									}

									$action = ($response['data']['upgrade_quantity'] ? 'upgrade' : 'merge');
									$mergeDetails = ' order <a href="' . $this->settings['base_url'] . 'orders/' . $mergedData['order']['id'] . '">#' . $mergedData['order']['id'] . '</a>.<br>' . $mergedData['order']['quantity'] . ' ' . $mergedData['order']['name'] . '<br>' . $mergedData['order']['price'] . ' ' . $mergedData['order']['currency'] . ' for ' . $mergedData['order']['interval_value'] . ' ' . $mergedData['order']['interval_type'] . ($mergedData['order']['interval_value'] !== 1 ? 's' : '');
									$pendingOrderMergeDetails = 'order' . (count($pendingOrders) !== 1 ? 's' : '') . ' ';
									$upgradeDetails = ' order <a href="' . $this->settings['base_url'] . 'orders/' . $mergedData['order']['id'] . '">#' . $mergedData['order']['id'] . '</a>.<br>' . $mergedData['order']['quantity'] . ' ' . $mergedData['order']['name'] . ' to ' . $mergedData['order']['quantity_pending'] . ' ' . $mergedData['order']['name'] . '<br>' . $mergedData['order']['price'] . ' ' . $mergedData['order']['currency'] . ' for ' . $mergedData['order']['interval_value'] . ' ' . $mergedData['order']['interval_type'] . ($mergedData['order']['interval_value'] !== 1 ? 's' : '') . ' to ' . $mergedData['order']['price_pending'] . ' ' . $mergedData['order']['currency'] . ' for ' . $mergedData['order']['interval_value_pending'] . ' ' . $mergedData['order']['interval_type_pending'] . ($mergedData['order']['interval_value_pending'] !== 1 ? 's' : '');

									foreach ($pendingOrders as $orderId => $pendingOrder) {
										$pendingOrders[$orderId]['merged_order_id'] = $mergedData['order']['id'];
										$pendingOrderMergeDetails .= '<a anchor_order_id="' . $orderId . '" href="' . $this->settings['base_url'] . 'orders/' . $orderId . '">#' . $orderId . '</a>, ';
									}

									$pendingInvoices[$mergedData['invoice']['id'] . '_merged'] = array(
										'amount_paid' => 0,
										'id' => $mergedInvoiceId,
										'merged_invoice_id' => null
									);
									$mergedInvoiceOrder = $this->fetch('invoice_orders', array(
										'conditions' => array(
											'order_id' => $mergedData['order']['id']
										),
										'fields' => array(
											'id',
											'initial_invoice_id',
											'invoice_id',
											'order_id'
										),
										'limit' => 1
									));

									if (!empty($mergedInvoiceOrder['count'])) {
										$pendingInvoiceOrder = array_merge($mergedInvoiceOrder['data'][0], array(
											'invoice_id' => $mergedInvoiceId
										));

										if (empty($mergedInvoiceOrder['data'][0]['initial_invoice_id'])) {
											$pendingInvoiceOrder['initial_invoice_id'] = $mergedInvoiceOrder['data'][0]['invoice_id'];
										}

										$pendingInvoiceOrders[] = $pendingInvoiceOrder;
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
									$proxies = $this->fetch('proxies', $proxyParameters);

									if (!empty($proxies['count'])) {
										foreach ($proxies['data'] as $key => $proxy) {
											$proxies['data'][$key]['order_id'] = $mergedData['order']['id'];
										}

										$pendingProxies = array_values($proxies['data']);
									}

									$proxyGroups = $this->fetch('proxy_groups', $proxyParameters);

									if (!empty($proxyGroups['count'])) {
										foreach ($proxyGroups['data'] as $key => $proxyGroup) {
											$proxyGroups['data'][$key]['order_id'] = $mergedData['order']['id'];
										}

										$pendingProxyGroups = array_values($proxyGroups['data']);
									}

									$transactions = $this->fetch('transactions', array(
										'conditions' => array(
											'invoice_id' => array_values($pendingInvoiceIds)
										),
										'fields' => array(
											'id',
											'invoice_id'
										)
									));

									if (!empty($pendingAmountMergedTransactions)) {
										$pendingTransactions = array_merge($pendingTransactions, array_values(array_replace_recursive($pendingAmountMergedTransactions, array_fill(0, count($pendingAmountMergedTransactions), array(
											'invoice_id' => $mergedInvoiceId
										)))));
									}

									if (!empty($transactions['count'])) {
										$pendingTransactions = array_merge($pendingTransactions, array_values(array_replace_recursive($transactions['data'], array_fill(0, $transactions['count'], array(
											'invoice_id' => $mergedInvoiceId
										)))));
									}

									$amountToApplyToBalanceTransaction = '';
									$pendingOrderMergeDetails = str_replace(', <a anchor_order_id="' . $orderId, ' and <a anchor_order_id="' . $orderId, rtrim(trim($pendingOrderMergeDetails), ','));
									$pendingTransaction = array(
										'customer_email' => $parameters['user']['email'],
										'initial_invoice_id' => $mergedInvoiceId,
										'invoice_id' => $mergedInvoiceId,
										'payment_amount' => null,
										'payment_currency' => $this->settings['billing']['currency'],
										'payment_status' => 'completed',
										'transaction_charset' => $this->settings['database']['charset'],
										'transaction_date' => date('Y-m-d H:i:s', time()),
										'transaction_method' => 'Miscellaneous',
										'transaction_processed' => true,
										'user_id' => $parameters['user']['id']
									);
									$userData = array(
										array(
											'balance' => $parameters['user']['balance'],
											'id' => $parameters['user']['id']
										)
									);

									if (!empty($amountToApplyToBalance)) {
										$amountToApplyToBalanceTransaction .= '<br>' . number_format($amountToApplyToBalance, 2, '.', '') . ' ' . $mergedData['invoice']['currency'] . ' overpayment added to account balance.';
										$pendingInvoices[] = array(
											'amount_paid' => $amountToApplyToBalance,
											'cart_items' => ($balanceTransferInvoiceIdentifier = sha1(uniqid() . $mergedData['invoice']['cart_items'])),
											'currency' => $mergedData['invoice']['currency'],
											'due' => null,
											'payable' => true,
											'session_id' => $parameters['session'],
											'status' => 'paid',
											'subtotal' => $amountToApplyToBalance,
											'total' => $amountToApplyToBalance,
											'user_id' => $parameters['user']['id'],
											'warning_level' => 5
										);
										$userData[0]['balance'] += $amountToApplyToBalance;
									}

									if (count($pendingOrders) !== 1) {
										$details = ($mergeDetails = 'Merge requested from ' . $pendingOrderMergeDetails . ' to ' . $mergeDetails) . $amountToApplyToBalanceTransaction;
										$amountToApplyToBalanceTransaction = '';
										$pendingTransactions[] = array_merge(array(
											'details' => $details,
											'id' => uniqid() . time(),
											'payment_status_message' => 'Order merge requested.'
										), $pendingTransaction);
									}

									if ($action === 'upgrade') {
										$details = ($upgradeDetails = 'Order upgrade requested for ' . $upgradeDetails) . $amountToApplyToBalanceTransaction;
										$pendingTransactions[] = array_merge($pendingTransaction, array(
											'details' => $details,
											'id' => uniqid() . time(),
											'payment_status_message' => 'Order upgrade requested.',
											'transaction_date' => date('Y-m-d H:i:s', strtotime('+1 second')),
										));
									}

									if ($mergedData['invoice']['remainder_pending'] === 0) {
										$pendingTransactions[] = $transactionToProcess = array_merge($pendingTransaction, array(
											'details' => str_replace('requested', 'successful', ($action === 'upgrade' ? $upgradeDetails : $mergeDetails)),
											'id' => uniqid() . time(),
											'payment_amount' => 0,
											'payment_status_message' => 'Order ' . $action . ' successful.',
											'transaction_date' => date('Y-m-d H:i:s', strtotime('+2 seconds')),
											'transaction_method' => 'PaymentCompleted'
										));
									}

									if (
										$this->save('invoices', array_values($pendingInvoices)) &&
										$this->save('invoice_orders', $pendingInvoiceOrders) &&
										$this->save('orders', array_values($pendingOrders)) &&
										$this->save('order_merges', $pendingOrderMerges) &&
										$this->save('proxies', $pendingProxies) &&
										$this->save('proxy_groups', $pendingProxyGroups) &&
										$this->save('transactions', $pendingTransactions) &&
										$this->save('users', $userData)
									) {
										$response['message'] = array(
											'status' => 'success',
											'text' => 'Redirecting to merged invoice for payment, please wait.'
										);
										$response['redirect'] = $this->settings['base_url'] . 'invoices/' . $mergedInvoiceId;

										foreach ($pendingInvoices as $pendingInvoice) {
											if (!empty($pendingInvoice['id'])) {
												$invoice = $this->_call('invoices', array(
													'methodName' => 'invoice',
													'methodParameters' => array(
														'invoices',
														array(
															'conditions' => array(
																'id' => $pendingInvoice['id']
															)
														)
													)
												));
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
												$mergeDetails = array_shift(explode('<br>', $mergeDetails));
												$balanceTransferTransactions = array(
													array(
														'customer_email' => $parameters['user']['email'],
														'details' => number_format($amountToApplyToBalance, 2, '.', '') . ' ' . $mergedData['invoice']['currency'] . ' overpayment added to account balance.<br> ' . ucwords($action) . 'd' . $mergeDetails,
														'id' => uniqid() . time(),
														'initial_invoice_id' => $balanceTransferInvoiceId,
														'invoice_id' => $balanceTransferInvoiceId,
														'payment_amount' => 0,
														'payment_currency' => $this->settings['billing']['currency'],
														'payment_status' => 'completed',
														'payment_status_message' => 'Amount added to account balance.',
														'transaction_charset' => $this->settings['database']['charset'],
														'transaction_date' => date('Y-m-d H:i:s', strtotime('+1 second')),
														'transaction_method' => 'Miscellaneous',
														'transaction_processed' => true,
														'user_id' => $parameters['user']['id']
													)
												);
												$this->save('transactions', $balanceTransferTransactions);
											}
										}

										if (!empty($transactionToProcess)) {
											$this->_call('transactions', array(
												'methodName' => 'processTransaction',
												'methodParameters' => array(
													$transactionToProcess
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
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function view($table, $parameters = array()) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'Error processing your order request, please try again.'
			)
		);

		if (
			empty($parameters['id']) ||
			!is_numeric($parameters['id'])
		) {
			if (
				empty($parameters['conditions']) ||
				empty($parameters['user'])
			) {
				$response['redirect'] = $this->settings['base_url'] . 'orders';
			}

			$order = $this->fetch('orders', array(
				'conditions' => $parameters['conditions'],
				'fields' => array(
					'id',
					'name',
					'quantity',
					'quantity_active',
					'type'
				),
				'limit' => 1
			));

			if (!empty($order['count'])) {
				$response = array(
					'data' => array_merge(array(
						'invoice' => ($invoice = $this->retrieveMostRecentOrderInvoice($order['data'][0])),
						'order' => $order['data'][0]
					), $this->_retrieveAvailableServerNodeDetails($order['data'][0])),
					'message' => array(
						'status' => 'success',
						'text' => ''
					)
				);

				if ($invoice['warning_level'] >= 2) {
					$response['message'] = array(
						'status' => 'error',
						'text' => 'Please pay the <a href="' . $this->settings['base_url'] . 'invoices/' . $invoice['id'] . '">past-due invoice</a> to prevent order deactivation (past-due since ' . date('M d, Y', strtotime($invoice['due'])) . ').'
					);
				}
			}
		} else {
			$order = $this->fetch('orders', array(
				'conditions' => array(
					'id' => $parameters['id']
				),
				'fields' => array(
					'id',
					'merged_order_id'
				)
			));

			if (
				!empty($order['count']) &&
				!empty($order['data'][0]['merged_order_id'])
			) {
				$this->redirect($this->settings['base_url'] . 'orders/' . $order['data'][0]['merged_order_id']);
			}

			$response = array(
				'order_id' => $parameters['id'],
				'results_per_page' => 50
			);
		}

		return $response;
	}

}

?>
