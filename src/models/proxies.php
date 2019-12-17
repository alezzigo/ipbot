<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ProxiesModel extends AppModel {

	/**
	 * Generate random proxy username:password authentication
	 *
	 * @param array $proxyData
	 *
	 * @return array $response
	 */
		protected function _generateRandomAuthentication($proxyData) {
			$characters = 'abcdefghjklmnopqrstuvwxyzbcdfghjklmnpqrstvwxyz01234567890123456789012345678901234567890123456789012345678901234567890123456789';

			for ($i = 0; $i < mt_rand(10, 15); $i++) {
				$proxyData['username'] = substr($proxyData['username'] . $characters[mt_rand(0, strlen($characters) - 1)], 0, 20);
			}

			for ($i = 0; $i < mt_rand(10, 15); $i++) {
				$proxyData['password'] = substr($proxyData['password'] . $characters[mt_rand(0, strlen($characters) - 1)], 0, 20);
			}

			$response = $proxyData;
			return $response;
		}

	/**
	 * Allocate proxies to an order
	 *
	 * @param array $orderData
	 *
	 * @return integer $response
	 */
		public function allocateProxies($orderData) {
			$response = 0;
			$quantity = min(10000, max(0, $orderData['quantity'] - $orderData['quantity_allocated']));

			if ($quantity) {
				$orderProcessingData = array(
					array(
						'id' => $orderData['id'],
						'order_processing' => true
					)
				);

				if ($this->save('orders', $orderProcessingData)) {
					$processingNodes = $this->fetch('nodes', array(
						'conditions' => array(
							'AND' => array(
								'allocated' => false,
								'ip_version' => $orderData['ip_version'],
								'NOT' => array(
									'ip' => null
								),
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
						'limit' => $quantity,
						'sort' => 'random'
					));

					if ($processingNodesCount = count($processingNodes['data'])) {
						$newItemData = array(
							'order_id' => $orderData['id'],
							'status' => 'online',
							'user_id' => $orderData['user_id']
						);
						$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, $processingNodesCount, array(
							'processing' => true
						)));

						if ($this->save('nodes', $processingNodes['data'])) {
							$allocatedNodes = array();

							foreach ($processingNodes['data'] as $processingNodeKey => $row) {
								$allocatedNodes[] = array(
									'allocated' => true,
									'id' => ($processingNodes['data'][$processingNodeKey]['node_id'] = $row['id']),
									'processing' => false
								);
								$processingNodes['data'][$processingNodeKey] += $newItemData;
								unset($processingNodes['data'][$processingNodeKey]['id']);
								unset($processingNodes['data'][$processingNodeKey]['processing']);
							}

							if (
								$this->save('nodes', $allocatedNodes) &&
								$this->save('proxies', $processingNodes['data'])
							) {
								$orderProgressData = array(
									array(
										'id' => $orderData['id'],
										'order_processing' => false,
										'quantity_active' => $orderData['quantity_active'] + $processingNodesCount,
										'quantity_allocated' => ($quantityAllocated = $orderData['quantity_allocated'] + $processingNodesCount),
										'quantity_allocated_progress' => ceil(($quantityAllocated / $orderData['quantity']) * 100)
									)
								);

								if ($this->save('orders', $orderProgressData)) {
									$orderData = array_merge($orderData, $orderProgressData[0]);

									if ($orderData['quantity_allocated_progress'] == 100) {
										$mailParameters = array(
											'from' => $this->settings['from_email'],
											'subject' => 'Order #' . $orderData['id'] . ' is activated',
											'template' => array(
												'name' => 'order_activated',
												'parameters' => array(
													'order' => $orderData,
													'user' => $this->_call('users', array(
														'methodName' => 'retrieveUser',
														'methodParameters' => array(
															$orderData
														)
													))
												)
											),
											'to' => $parameters['user']['email']
										);

										if ($action = $orderData['previous_action']) {
											$mailParameters = array_replace_recursive($mailParameters, array(
												'subject' => 'Order #' . $orderData['id'] . ' is ' . $action . 'd',
												'template' => array(
													'name' => 'order_changed'
												)
											));
										}

										$this->_sendMail($mailParameters);
									}

									$response = $quantityAllocated;
								}
							}
						}
					}

					$orderProcessingData[0]['order_processing'] = false;
					$this->save('orders', $orderProcessingData);
				}
			}

			return $response;
		}

	/**
	 * Process authenticate requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function authenticate($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error authenticating ' . $table . ', please try again.')
				)
			);

			if (
				!is_array($parameters['items'][$table]['data']) ||
				empty($parameters['items'][$table]['data'])
			) {
				$response['message']['text'] = 'There are no ' . $table . ' selected to ' . $parameters['action'] . '.';

				if (
					!empty($parameters['data']['items']) &&
					!empty($parameters['data']['order_id'])
				) {
					$response = $this->_authenticateEndpoint('orders', $parameters, array(
						'id' => $parameters['data']['order_id']
					));

					if ($response['message']['status'] === 'success') {
						$response = $this->_processEndpointRequest($table, $parameters, array(
							'id' => $parameters['data']['items'],
							'order_id' => $parameters['data']['order_id']
						));

						if ($response['message']['status'] === 'success') {
							$response['message'] = array(
								'status' => 'error',
								'text' => 'Invalid API endpoint request action "' . $action . '", please try again.'
							);

							if (method_exists($this, ($action = $parameters['action']))) {
								$parameters = array_merge($parameters, array(
									'conditions' => $response['conditions'],
									'items' => array(
										$table => array(
											'count' => count($response['items']),
											'data' => $response['items']
										)
									)
								));
								$response = $this->$action($table, $parameters);
							}
						}
					}
				}
			} else {
				$proxies = $parameters['items'][$table]['data'];

				if (
					empty($parameters['data']['generate_unique']) &&
					(
						!empty($parameters['data']['username']) ||
						!empty($parameters['data']['password'])
					) &&
					(
						empty($parameters['data']['username']) ||
						empty($parameters['data']['password'])
					)
				) {
					$response['message']['text'] = 'Both username and password must be either set or empty.';
				} else {
					if (
						empty($parameters['data']['generate_unique']) &&
						(
							(
								!empty($parameters['data']['username']) &&
								(
									strlen($parameters['data']['username']) < 4 ||
									strlen($parameters['data']['username']) > 15
								)
							) ||
							(
								!empty($parameters['data']['password']) &&
								(
									strlen($parameters['data']['password']) < 4 ||
									strlen($parameters['data']['password']) > 15
								)
							)
						)
					) {
						$response['message']['text'] = 'Both username and password must be between 4 and 15 characters.';
					} else {
						$usernames = array();

						if (!empty($parameters['data']['username'])) {
							$existingUsernames = $this->fetch('proxies', array(
								'conditions' => array(
									'NOT' => array(
										'username' => ''
									)
								),
								'fields' => array(
									'username'
								)
							));

							if (!empty($existingUsernames['count'])) {
								$usernames = array_unique($existingUsernames['data']);
							}
						}

						$whitelistedIps = implode("\n", (!empty($parameters['data']['whitelisted_ips']) ? $this->_parseIps($parameters['data']['whitelisted_ips']) : array()));

						foreach ($proxies as $key => $proxy) {
							$proxy = array(
								'disable_http' => (isset($parameters['data']['disable_http']) && $parameters['data']['disable_http']),
								'id' => $proxy,
								'username' => $parameters['data']['username'],
								'password' => $parameters['data']['password'],
								'whitelisted_ips' => $whitelistedIps
							);

							if (!empty($parameters['data']['generate_unique'])) {
								$proxy = $this->_generateRandomAuthentication($proxy);
							}

							$proxies[$key] = $proxy;
						}

						if (
							!empty($parameters['data']['username']) &&
							in_array($parameters['data']['username'], $usernames)
						) {
							$response['message']['text'] = 'Username [' . $parameters['data']['username'] . '] is already in use, please try a different username.';
						} else {
							$response['message']['text'] = $defaultMessage;

							if ($this->save('proxies', $proxies)) {
								$response['message'] = array(
									'status' => 'success',
									'text' => 'Authentication saved successfully.'
								);
							}
						}
					}
				}

				$response['items'][$table] = array();
			}

			$response = array_merge($this->fetch($table, $parameters), $response);
			return $response;
		}

	/**
	 * Process copy requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function copy($table, $parameters) {
			$items = array();
			$response = $this->fetch($table, array(
				'conditions' => array(
					'id' => $parameters['items'][$table]['data']
				),
				'fields' => $this->permissions[$table]['copy']['fields']
			));

			if (
				!empty($parameters['data']) &&
				is_array($parameters['data']) &&
				!empty($parameters['data']['proxy_list_type'])
			) {
				foreach ($parameters['data'] as $columnField => $columnValue) {
					if ($columnValue == 'port') {
						$parameters['data'][$columnField] = $parameters['data']['proxy_list_type'] . '_port';
					}
				}
			}

			if (!empty($response['data'])) {
				$delimiters = array(
					!empty($parameters['data']['ipv4_delimiter_1']) ? $parameters['data']['ipv4_delimiter_1'] : '',
					!empty($parameters['data']['ipv4_delimiter_2']) ? $parameters['data']['ipv4_delimiter_2'] : '',
					!empty($parameters['data']['ipv4_delimiter_3']) ? $parameters['data']['ipv4_delimiter_3'] : '',
					''
				);
				$delimiterMask = implode('', array_unique(array_filter($delimiters)));
				$separators = array(
					'comma' => ',',
					'new_line' => "\n",
					'semicolon' => ';',
					'space' => ' ',
					'underscore' => '_'
				);

				if (
					empty($parameters['data']['separated_by']) ||
					empty($separator = $separators[$parameters['data']['separated_by']])
				) {
					$separator = "\n";
				}

				foreach ($response['data'] as $key => $data) {
					$items[$key] = '';

					for ($i = 1; $i < 5; $i++) {
						$items[$key] .= !empty($column = $response['data'][$key][$parameters['data']['ipv4_column_' . $i]]) ? $column . $delimiters[($i - 1)] : '';
					}

					$items[$key] = rtrim($items[$key], $delimiterMask);
				}
			}

			$response = array(
				'count' => count($items),
				'data' => implode($separator, $items)
			);
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
			$response = array(
				'data' => array(),
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error processing your order downgrade request for the selected ' . $table . ', please try again.')
				)
			);

			if (
				!empty($parameters['items'][$table]['count']) &&
				!empty($parameters['conditions']['order_id'])
			) {
				$downgradeQuantity = $parameters['items'][$table]['count'];
				$itemIds = array_values($parameters['items'][$table]['data']);
				$orderId = $parameters['conditions']['order_id'];
				$order = $this->fetch('orders', array(
					'conditions' => array(
						'id' => $orderId,
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
					),
					'limit' => 1
				));

				if (
					!empty($order['count']) &&
					!empty($order['data'][0]['product_id'])
				) {
					$productId = $order['data'][0]['product_id'];

					if ($order['data'][0]['quantity_active'] <= $downgradeQuantity) {
						$response['message']['text'] = 'Error processing your order downgrade request, please select less than ' . $order['data'][0]['quantity_active'] . ' active ' . $table . ' and try again.';
					} else {
						$pendingInvoices = $pendingInvoiceOrders = $pendingTransactions = array();
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
							$mergedData['order']['quantity_pending'] = $mergedData['order']['quantity'] + $response['data']['upgrade_quantity'];
							$mostRecentOrderInvoice = $this->_call('orders', array(
								'methodName' => 'retrieveMostRecentOrderInvoice',
								'methodParameters' => array(
									array(
										'id' => $orderId
									)
								)
							));
							$invoice = $this->_call('invoices', array(
								'methodName' => 'invoice',
								'methodParameters' => array(
									'invoices',
									array(
										'conditions' => array(
											'id' => $mostRecentOrderInvoice['id']
										)
									)
								)
							));
							$invoiceIds = $this->_call('invoices', array(
								'methodName' => 'retrieveInvoiceIds',
								'methodParameters' => array(
									array(
										$invoice['data']['invoice']['id']
									)
								)
							));

							if (!empty($invoice['data']['invoice'])) {
								if (!empty($invoice['data']['invoice']['remainder_pending'])) {
									$response['message']['text'] = 'Error processing your order downgrade request, there\'s already a pending order upgrade request for invoice <a href="' . $this->settings['base_url'] . 'invoices/' . $invoice['data']['invoice']['id'] . '">#' . $invoice['data']['invoice']['id'] . '</a>.';
								} else {
									$downgradedProxiesToRemove = $this->fetch($table, array(
										'conditions' => array(
											'id' => $itemIds,
											'NOT' => array(
												'status' => 'replaced'
											)
										),
										'fields' => array(
											'id'
										)
									));
									$downgradeQuantity = $downgradedProxiesToRemove['count'];

									if (empty($downgradeQuantity)) {
										$response['message']['text'] = 'The selected ' . $table . ' are already replaced and pending removal from a previous order downgrade, please try again.';
									} else {
										$itemIds = $downgradedProxiesToRemove['data'];
										$downgradedData = array(
											'order' => array_merge($order['data'][0], array(
												'quantity_pending' => $downgradeQuantity
											)),
											'invoice' => array_merge($invoice['data']['invoice'], array(
												'cart_items' => sha1(uniqid() . $invoice['data']['invoice']['cart_items']),
												'merged_invoice_id' => 0,
												'payable' => false
											))
										);
										$downgradedData['order']['price'] = $this->_calculateItemPrice($order = array(
											'interval_type' => $downgradedData['order']['interval_type'],
											'interval_value' => $downgradedData['order']['interval_value'],
											'price_per' => $response['data']['product']['price_per'],
											'quantity' => $downgradedData['order']['quantity']
										));
										$downgradedData['order']['price_pending'] = $this->_calculateItemPrice(array_merge($order, array(
											'quantity' => $downgradedData['order']['quantity_pending']
										)));
										$pendingItem = array_merge(array(
											'price' => $downgradedData['order']['price_pending'],
											'quantity' => $downgradedData['order']['quantity_pending']
										), $response['data']['product']);
										$downgradedData['order'] = array_merge($downgradedData['order'], array(
											'shipping_pending' => $this->_calculateItemShippingPrice($pendingItem),
											'tax_pending' => $this->_calculateItemTaxPrice($pendingItem)
										));
										$downgradedData['orders'][] = $downgradedData['order'];
										$downgradedData = array_replace_recursive($downgradedData, $this->_call('invoices', array(
											'methodName' => 'calculateInvoicePaymentDetails',
											'methodParameters' => array(
												$downgradedData,
												false
											)
										)));
										$response['data']['downgraded'] = $downgradedData;
										$response['message'] = array(
											'status' => 'success',
											'text' => ''
										);

										if (!empty($parameters['data']['confirm_downgrade'])) {
											$downgradedInvoiceData = array(
												array_diff_key($downgradedData['invoice'], array(
													'amount_due' => true,
													'amount_due_pending' => true,
													'billing' => true,
													'created' => true,
													'due' => true,
													'id' => true,
													'initial_invoice_id' => true,
													'modified' => true
												))
											);
											$downgradedProxyData = array();
											$downgradedProxyParameters = array(
												'conditions' => array(
													'id' => $itemIds,
													'order_id' => $orderId
												),
												'fields' => array(
													'id',
													'ip'
												)
											);
											$downgradedProxiesToKeep = $this->fetch('proxies', $downgradedProxyParameters);
											$downgradedProxyParameters['conditions'] = array(
												'order_id' => $orderId,
												'NOT' => array(
													'id' => $itemIds,
													'status' => 'replaced'
												)
											);
											$downgradedProxiesToRemove = $this->fetch('proxies', $downgradedProxyParameters);

											if (empty($downgradedProxiesToRemove['count'])) {
												$response['message'] = array(
													'status' => 'error',
													'text' => $defaultMessage
												);
											} else {
												$downgradedProxyData = array_replace_recursive($downgradedProxiesToRemove['data'], array_fill(0, $downgradedProxiesToRemove['count'], array(
													'replacement_removal_date' => date('Y-m-d H:i:s', strtotime('+2 hours')),
													'status' => 'replaced'
												)));

												if ($this->save('invoices', $downgradedInvoiceData)) {
													$downgradedInvoice = $this->fetch('invoices', array(
														'conditions' => array(
															'cart_items' => $downgradedData['invoice']['cart_items'],
															'user_id' => $downgradedData['invoice']['user_id']
														),
														'fields' => array_merge(array_keys($downgradedInvoiceData[0]), array(
															'id'
														)),
														'limit' => 1,
														'sort' => array(
															'field' => 'created',
															'order' => 'DESC'
														)
													));
													$downgradedInvoiceOrders = $this->fetch('invoice_orders', array(
														'conditions' => array(
															'invoice_id' => $invoiceIds
														),
														'fields' => array(
															'id',
															'initial_invoice_id',
															'invoice_id',
															'order_id'
														)
													));

													if (
														!empty($downgradedInvoice['count']) &&
														!empty($downgradedInvoiceOrders['count'])
													) {
														$downgradedInvoiceId = $downgradedInvoice['data'][0]['id'];
														$mostRecentPayableInvoice = $this->_call('invoices', array(
															'methodName' => 'retrieveMostRecentPayableInvoice',
															'methodParameters' => array(
																$invoice['data']['invoice']['id']
															)
														));
														$mostRecentPayableInvoiceId = $mostRecentPayableInvoice['id'];

														foreach ($downgradedInvoiceOrders['data'] as $downgradedInvoiceOrder) {
															$pendingInvoiceOrders[$downgradedInvoiceOrder['order_id']] = $downgradedInvoiceOrder;
														}

														if ($downgradedInvoiceOrders['count'] == 1) {
															$transactions = $this->_call('invoices', array(
																'methodName' => 'retrieveInvoiceTransactions',
																'methodParameters' => array(
																	$invoice['data']['invoice']
																)
															));

															if (!empty($transactions)) {
																$pendingTransactions[] = array_replace_recursive($transactions, array_fill(0, count($transactions), array(
																	'invoice_id' => $downgradedInvoiceId
																)));
															}

															$pendingInvoices[] = array(
																'id' => $invoice['data']['invoice']['id'],
																'merged_invoice_id' => $downgradedInvoiceId
															);
														}

														$downgradedData['orders'][0] = array_merge($downgradedData['order'], array(
															'price' => $downgradedData['order']['price_pending'],
															'price_active' => $downgradedData['order']['price_pending'],
															'price_pending' => null,
															'quantity' => $downgradedData['order']['quantity_pending'],
															'quantity_active' => $downgradedData['order']['quantity_pending'],
															'quantity_pending' => null,
															'shipping' => $downgradedData['order']['shipping_pending'],
															'shipping_pending' => null,
															'tax' => $downgradedData['order']['tax_pending'],
															'tax_pending' => null
														));
														$downgradedInvoiceOrderData = array(
															array(
																'id' => $downgradedInvoiceOrder['data'][0]['id'],
																'invoice_id' => $downgradedInvoiceId
															)
														);
														$amountToApplyToBalanceTransaction = '';
														$downgradedInvoice = array_merge($downgradedInvoiceData[0], array(
															'due' => date('Y-m-d H:i:s', strtotime($invoice['data']['invoice']['due'])),
															'id' => $downgradedInvoiceId,
															'merged_invoice_id' => null,
															'payable' => $invoice['data']['invoice']['payable'],
															'shipping' => $downgradedInvoiceData[0]['shipping_pending'],
															'shipping_pending' => null,
															'subtotal' => $downgradedInvoiceData[0]['subtotal_pending'],
															'subtotal_pending' => null,
															'tax' => $downgradedInvoiceData[0]['tax_pending'],
															'tax_pending' => null,
															'total' => $downgradedInvoiceData[0]['total_pending'],
															'total_pending' => null
														));
														$intervalDetails = $downgradedData['order']['interval_value'] . ' ' . $downgradedData['order']['interval_type'] . ($downgradedData['order']['interval_value'] !== 1 ? 's' : '');
														$pendingDowngradeTransaction = array(
															'customer_email' => $parameters['user']['email'],
															'details' => 'Order downgrade requested for order <a href="' . $this->settings['base_url'] . 'orders/' . $orderId . '">#' . $orderId . '</a><br>' . $downgradedData['order']['quantity_active'] . ' ' . $downgradedData['order']['name'] . ' to ' . $downgradedData['order']['quantity_pending'] . ' ' . $downgradedData['order']['name'] . '<br>' . $downgradedData['order']['price'] . ' for ' . $intervalDetails . ' to ' . $downgradedData['order']['price_pending'] . ' for ' . $intervalDetails,
															'id' => uniqid() . time(),
															'initial_invoice_id' => $mostRecentPayableInvoiceId,
															'invoice_id' => $mostRecentPayableInvoiceId,
															'payment_amount' => null,
															'payment_currency' => $this->settings['billing']['currency'],
															'payment_status' => 'completed',
															'payment_status_message' => 'Order downgrade requested.',
															'transaction_charset' => $this->settings['database']['charset'],
															'transaction_date' => date('Y-m-d H:i:s', strtotime('+1 second')),
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

														if (
															$mostRecentPayableInvoice['status'] !== 'paid' &&
															!empty($mostRecentPayableInvoice['amount_paid'])
														) {
															$amountToApplyToBalanceTransaction = '<br>' . number_format($mostRecentPayableInvoice['amount_paid'], 2, '.', '') . ' ' . $downgradedData['invoice']['currency'] . ' overpayment added to account balance.';
															$downgradedInvoice['amount_paid'] = 0;
															$userData[0]['balance'] += $mostRecentPayableInvoice['amount_paid'];
															$pendingDowngradeTransaction = array_merge($pendingDowngradeTransaction, array(
																'initial_invoice_id' => $downgradedInvoiceId,
																'invoice_id' => $downgradedInvoiceId
															));
														}

														$pendingInvoices[] = $downgradedInvoice;
														$pendingTransactions[] = $pendingDowngradeTransaction;
														$pendingDowngradeTransaction = array_merge($pendingDowngradeTransaction, array(
															'details' => str_replace('requested', 'successful', $pendingDowngradeTransaction['details']) . $amountToApplyToBalanceTransaction,
															'id' => uniqid() . time(),
															'payment_amount' => 0,
															'payment_status_message' => 'Order downgrade successful.',
															'transaction_date' => date('Y-m-d H:i:s', strtotime('+2 seconds')),
														));
														$pendingTransactions[] = $pendingDowngradeTransaction;

														if (
															$this->save('invoices', $pendingInvoices) &&
															$this->save('invoice_orders', $downgradedInvoiceOrderData) &&
															$this->save('orders', $downgradedData['orders']) &&
															$this->save('proxies', $downgradedProxyData) &&
															$this->save('transactions', $pendingTransactions) &&
															$this->save('users', $userData)
														) {
															$mailParameters = array(
																'from' => $this->settings['from_email'],
																'subject' => 'Order #' . $downgradedData['order']['id'] . ' downgraded to ' . $downgradedData['order']['quantity_pending'] . ' ' . strtolower($downgradedData['order']['name']),
																'template' => array(
																	'name' => 'order_downgraded',
																	'parameters' => array(
																		'invoice' => $downgradedData['invoice'],
																		'items_to_keep' => $downgradedProxiesToKeep['data'],
																		'items_to_remove' => $downgradedProxiesToRemove['data'],
																		'link' => 'https://' . $this->settings['base_domain'] . '/orders/' . $orderId,
																		'order' => $downgradedData['order'],
																		'table' => 'proxies'
																	)
																),
																'to' => $parameters['user']['email']
															);
															$this->_sendMail($mailParameters);
															$response['message'] = array(
																'status' => 'success',
																'text' => 'Order downgrade requested successfully.'
															);
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
				}
			}

			return $response;
		}

	/**
	 * Process group requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function group($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Error processing your group request, please try again.'
				)
			);

			if (
				$table === 'proxy_groups' &&
				!empty($parameters['data'])
			) {
				$groupData = array(
					array_intersect_key($parameters['data'], array(
						'id' => true,
						'name' => true,
						'order_id' => true
					))
				);

				if (!empty($groupData[0])) {
					if (!empty($parameters['conditions'])) {
						$groupData = array(
							array_merge($groupData[0], $parameters['conditions'])
						);
					}

					$groupParameters = array(
						'conditions' => $groupData[0],
						'fields' => array(
							'created',
							'id',
							'modified',
							'name',
							'order_id',
							'user_id'
						),
						'limit' => 1
					);

					if (
						!empty($groupData[0]['name']) &&
						!empty($groupData[0]['order_id'])
					) {
						$response['message']['text'] = 'Group "' . $groupData[0]['name'] . '" already exists for this order.';
						unset($groupParameters['conditions']['id']);
						$existingGroup = $this->fetch('proxy_groups', $groupParameters);

						if (empty($existingGroup['count'])) {
							$response['message']['text'] = 'Error creating new group, please try again.';

							if ($this->save('proxy_groups', $groupData)) {
								$response['message'] = array(
									'status' => 'success',
									'text' => 'Group "' . $groupData[0]['name'] . '" saved successfully.'
								);
							}
						}
					}

					if (
						!empty($groupData[0]['id']) &&
						!isset($groupData[0]['name'])
					) {
						$response['message']['text'] = 'Error deleting group, please try again.';
						$existingGroup = $this->fetch('proxy_groups', $groupParameters);

						if (
							!empty($existingGroup['count']) &&
							$this->delete('proxy_groups', $groupData[0]) &&
							$this->delete('proxy_group_proxies', array(
								'proxy_group_id' => $groupData[0]['id']
							))
						) {
							$response['message'] = array(
								'status' => 'success',
								'text' => 'Group deleted successfully.'
							);
						}
					}
				}
			}

			if ($table === 'proxies') {
				if (
					!empty($parameters['items']['proxies']['count']) &&
					!empty($parameters['items']['proxy_groups']['count'])
				) {
					$groups = $proxyIds = array();
					$existingProxyGroupProxies = $this->fetch('proxy_group_proxies', array(
						'conditions' => array(
							'proxy_id' => $parameters['items']['proxies']['data'],
							'proxy_group_id' => array_values($parameters['items']['proxy_groups']['data'])
						),
						'fields' => array(
							'id',
							'proxy_group_id',
							'proxy_id'
						)
					));

					foreach ($parameters['items']['proxies']['data'] as $key => $proxyId) {
						foreach ($parameters['items']['proxy_groups']['data'] as $key => $proxyGroupId) {
							$groups[$proxyGroupId . '_' . $proxyId] = array(
								'proxy_group_id' => $proxyGroupId,
								'proxy_id' => $proxyId
							);
						}
					}

					if (!empty($existingProxyGroupProxies['count'])) {
						foreach ($existingProxyGroupProxies['data'] as $existingProxyGroupProxy) {
							if (!empty($groups[$key = $existingProxyGroupProxy['proxy_group_id'] . '_' . $existingProxyGroupProxy['proxy_id']])) {
								$groups[$key]['id'] = $existingProxyGroupProxy['id'];
							}
						}
					}

					$response['message']['text'] = 'Error adding selected items to groups.';

					if ($this->save('proxy_group_proxies', array_values($groups))) {
						$response['message'] = array(
							'status' => 'success',
							'text' => 'Items added to selected groups successfully.'
						);
					}
				}

				$response['items'][$table] = array();
			} else {
				unset($parameters['limit']);
				unset($parameters['offset']);
			}

			$parameters['fields'] = $this->permissions[$table]['fetch']['fields'];
			$response = array_merge($this->fetch($table, $parameters), $response);
			return $response;
		}

	/**
	 * Process list requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function list($table, $parameters = array()) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error processing your API request, please try again.')
				)
			);

			if (!empty($orderId = $parameters['data']['order_id'])) {
				$response = $this->_authenticateEndpoint('orders', $parameters, array(
					'id' => $orderId
				));

				if ($response['message']['status'] === 'success') {
					$response['message'] = array(
						'status' => 'error',
						'text' => 'There aren\'t any ' . $table . ' available to ' . $parameters['action'] . ', please log in and check your order at ' . $this->settings['base_domain'] . $this->settings['base_url'] . 'orders/' . $orderId . '.'
					);
					$proxies = $this->fetch('proxies', array(
						'conditions' => array(
							'order_id' => $orderId
						),
						'fields' => array(
							'asn',
							'automatic_replacement_interval_type',
							'automatic_replacement_interval_value',
							'city',
							'country_code',
							'country_name',
							'disable_http',
							'http_port',
							'id',
							'ip',
							'isp',
							'last_replacement_date',
							'next_replacement_available',
							'node_id',
							'order_id',
							'password',
							'region',
							'replacement_removal_date',
							'require_authentication',
							'status',
							'transfer_authentication',
							'user_id',
							'username',
							'whitelisted_ips'
						)
					));

					if (!empty($proxies['count'])) {
						$response = array(
							'data' => array(
								$table => $proxies
							),
							'message' => array(
								'status' => 'success',
								'text' => $proxies['count'] . ' ' . $table  . ' retrieved successfully.'
							)
						);
					}
				}
			}

			return $response;
		}

	/**
	 * Process replace requests
	 *
	 * @param string $table
	 * @param array $parameters
	 * @param boolean $endpoint
	 *
	 * @return array $response
	 */
		public function replace($table, $parameters, $endpoint = false) {
			$response = array(
				'message' => array(
					'status' => 'error'
				)
			);

			if (
				!is_array($parameters['items'][$table]['data']) ||
				empty($parameters['items'][$table]['data'])
			) {
				$response['message']['text'] = 'There are no ' . $table . ' selected to ' . $parameters['action'] . '.';

				if (
					!empty($parameters['data']['order_id']) &&
					!empty($parameters['data']['items'])
				) {
					$response = $this->_authenticateEndpoint('orders', $parameters, array(
						'id' => ($orderId = $parameters['data']['order_id'])
					));

					if ($response['message']['status'] === 'success') {
						$response = $this->_processEndpointRequest($table, $parameters, array(
							'id' => $parameters['data']['items'],
							'order_id' => $orderId
						));

						if ($response['message']['status'] === 'success') {
							$response['message'] = array(
								'status' => 'error',
								'text' => 'Invalid API endpoint request action "' . $action . '", please try again.'
							);

							if (method_exists($this, ($action = $parameters['action']))) {
								$parameters = array_merge($parameters, array(
									'conditions' => $response['conditions'],
									'items' => array(
										$table => array(
											'count' => count($response['items']),
											'data' => $response['items']
										)
									)
								));
								$response = $this->$action($table, $parameters, true);
							}
						}
					}
				}
			} else {
				$response['message']['text'] = 'Error processing your replacement request, please try again.';
				$newItemData = $oldItemData = array(
					'automatic_replacement_interval_value' => 0,
					'transfer_authentication' => !empty($parameters['data']['transfer_authentication']) ? true : false
				);

				if (!empty($parameters['data']['instant_replacement'])) {
					$oldItemData += array(
						'replacement_removal_date' => date('Y-m-d H:i:s', strtotime('+24 hours')),
						'status' => 'replaced'
					);
				}

				if (
					(
						!empty($parameters['data']['automatic_replacement_interval_value']) &&
						is_numeric($parameters['data']['automatic_replacement_interval_value'])
					) &&
					(
						!empty($parameters['data']['automatic_replacement_interval_type']) &&
						in_array($automaticReplacementIntervalType = strtolower($parameters['data']['automatic_replacement_interval_type']), array('month', 'week'))
					) &&
					!empty($parameters['data']['enable_automatic_replacements'])
				) {
					$intervalData = array(
						'automatic_replacement_interval_type' => $automaticReplacementIntervalType,
						'automatic_replacement_interval_value' => $parameters['data']['automatic_replacement_interval_value'],
						'last_replacement_date' => date('Y-m-d H:i:s', time())
					);
					$newItemData = $oldItemData = array_merge($newItemData, $intervalData);
				}

				if (!empty($parameters['data']['replace_with_specific_node_locations'])) {
					$oldItemData += ($location = array(
						'replacement_city' => $parameters['data']['replacement_city'] ? $parameters['data']['replacement_city'] : null,
						'replacement_country_code' => $parameters['data']['replacement_country_code'] ? $parameters['data']['replacement_country_code'] : null,
						'replacement_region' => $parameters['data']['replacement_region'] ? $parameters['data']['replacement_region'] : null
					));
				}

				if (($orderId = !empty($parameters['conditions']['order_id']) ? $parameters['conditions']['order_id'] : 0)) {
					$oldItemData = array_fill(0, $parameters['items'][$table]['count'], $oldItemData);

					foreach ($parameters['items'][$table]['data'] as $key => $itemId) {
						$oldItemData[$key]['id'] = $itemId;
					}

					if (empty($parameters['data']['instant_replacement'])) {
						if ($this->save($table, $oldItemData)) {
							$response['message'] = array(
								'status' => 'success',
								'text' => 'Replacement settings applied to ' . $parameters['items'][$table]['count'] . ' of your selected ' . $table . ' successfully.'
							);
						}
					} else {
						$newItemData += array(
							'last_replacement_date' => date('Y-m-d H:i:s', time()),
							'next_replacement_available' => date('Y-m-d H:i:s', strtotime('+1 week')),
							'order_id' => $orderId,
							'status' => 'online',
							'user_id' => $parameters['user']['id']
						);
						$processingNodeParameters = array(
							'conditions' => array(
								'AND' => array(
									'allocated' => false,
									'NOT' => array(
										'ip' => null
									),
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
							'limit' => $parameters['items'][$table]['count'],
							'sort' => 'random'
						);

						if (!empty($location)) {
							$processingNodeParameters['conditions']['AND'] += array_filter(array_combine(array(
								'city',
								'country_code',
								'region'
							), $location));
						}

						$processingNodes = $this->fetch('nodes', $processingNodeParameters);

						if (count($processingNodes['data']) !== $parameters['items'][$table]['count']) {
							$response['message']['text'] = 'There aren\'t enough ' . $table . ' available to replace your ' . $parameters['items'][$table]['count'] . ' selected ' . $table . ', please try again in a few minutes.';
						} else {
							$allocatedNodes = array();
							$oldItems = $this->fetch($table, array(
								'conditions' => array(
									'id' => $parameters['items'][$table]['data']
								),
								'fields' => array(
									'id',
									'ip',
									'node_id'
								)
							));
							$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, count($processingNodes['data']), array(
								'processing' => true
							)));

							if (
								!empty($oldItems['count']) &&
								$this->save('nodes', $processingNodes['data'])
							) {
								foreach ($processingNodes['data'] as $key => $row) {
									$allocatedNodes[] = array(
										'allocated' => true,
										'id' => ($processingNodes['data'][$key]['node_id'] = $processingNodes['data'][$key]['id']),
										'processing' => false
									);
									$processingNodes['data'][$key] += $newItemData;
									$processingNodes['data'][$key]['previous_node_id'] = $oldItems['data'][$key]['node_id'];
									unset($processingNodes['data'][$key]['id']);
									unset($processingNodes['data'][$key]['processing']);
								}

								if (
									$endpoint ||
									$parameters['tokens'][$table] === $this->_getToken($table, $parameters, 'order_id', $orderId)
								) {
									if (!empty($parameters['data']['transfer_authentication'])) {
										$oldItemAuthentication = $this->fetch($table, array(
											'conditions' => array(
												'id' => $parameters['items'][$table]['data']
											),
											'fields' => array(
												'disable_http',
												'password',
												'require_authentication',
												'username',
												'whitelisted_ips'
											)
										));

										if (
											!empty($oldItemAuthentication['count']) &&
											count($oldItemAuthentication['data']) === count($parameters['items'][$table]['data'])
										) {
											$processingNodes['data'] = array_replace_recursive($processingNodes['data'], $oldItemAuthentication['data']);
										}
									}

									if (
										$this->save('nodes', $allocatedNodes) &&
										$this->save($table, $oldItemData) &&
										$this->save($table, $processingNodes['data']) &&
										!empty($oldItems['count'])
									) {
										$response['items'][$table] = array();
										$response['message'] = array(
											'status' => 'success',
											'text' => $parameters['items'][$table]['count'] . ' of your selected ' . $table . ' replaced successfully.'
										);
										$mailParameters = array(
											'from' => $this->settings['from_email'],
											'subject' => count($processingNodes['data']) . ' ' . $table . ' replaced successfully',
											'template' => array(
												'name' => 'items_replaced',
												'parameters' => array(
													'link' => 'https://' . $this->settings['base_domain'] . '/orders/' . $orderId,
													'new_items' => $processingNodes['data'],
													'old_items' => $oldItems['data'],
													'table' => str_replace('_', ' ', $table),
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
				}
			}

			if (($response['tokens'][$table] = $this->_getToken($table, $parameters, 'order_id', $orderId)) !== $parameters['tokens'][$table]) {
				$response['items'][$table] = array();
			}

			$response = array_merge($this->fetch($table, $parameters), $response);
			return $response;
		}

	/**
	 * Process search requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function search($table, $parameters) {
			$conditions = array();

			if (
				$broadSearchFields = $this->permissions[$table]['search']['fields'] &&
				!empty($parameters['data']['broad_search'])
			) {
				$conditions = array_map(function($broadSearchTerm) use ($broadSearchFields) {
					return array(
						'OR' => array_combine(explode('-', implode(' LIKE' . '-', $broadSearchFields) . ' LIKE'), array_fill(1, count($broadSearchFields), '%' . $broadSearchTerm . '%'))
					);
				}, array_filter(explode(' ', $parameters['data']['broad_search'])));
			}

			if (
				!empty($parameters['data']['granular_search']) &&
				($conditions['ip LIKE'] = $this->_parseIps($parameters['data']['granular_search'], true))
			) {
				array_walk($conditions['ip LIKE'], function(&$value, $key) {
					$value .= '%';
				});
			}

			if (!empty($conditions)) {
				$conditions = array(
					($parameters['data']['match_all_search'] ? 'AND' : 'OR') => $conditions
				);
			}

			if (!empty($parameters['data']['exclude_search'])) {
				$conditions = array(
					'NOT' => $conditions
				);
			}

			unset($parameters['conditions']['id']);

			if (!empty($parameters['data']['groups'])) {
				$conditions['id'] = false;
				$groupProxies = $this->fetch('proxy_group_proxies', array(
					'conditions' => array(
						'proxy_group_id' => array_values($parameters['data']['groups'])
					),
					'fields' => array(
						'proxy_id'
					)
				));

				if (
					!empty($groupProxies['count']) &&
					!empty($groupProxies['data'])
				) {
					$conditions['id'] = array_unique($groupProxies['data']);
				}
			}

			$parameters['conditions'] = array_merge($conditions, $parameters['conditions']);
			$response = array_merge($response = $this->fetch($table, $parameters), array(
				'message' => array(
					'status' => 'success',
					'text' => $response['count'] . ' search result' . ($response['count'] !== 1 ? 's' : '')  . ' found. <a class="clear" href="javascript:void(0);">Clear search filter</a>.'
				)
			));
			return $response;
		}

	/**
	 * Shell method for allocating proxies
	 *
	 * @param string $table
	 *
	 * @return array $response
	 */
		public function shellAllocateProxies($table) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'There aren\'t any new ' . $table . ' to allocate, please try again later.'
				)
			);
			$orders = $this->fetch('orders', array(
				'conditions' => array(
					'order_processing' => false,
					'quantity_allocated_progress <' => 100
				),
				'fields' => array(
					'id',
					'interval_type',
					'interval_value',
					'ip_version',
					'name',
					'previous_action',
					'price',
					'price_pending',
					'quantity',
					'quantity_active',
					'quantity_allocated',
					'quantity_allocated_progress',
					'quantity_pending',
					'user_id'
				)
			));
			$proxyCount = 0;

			if (!empty($orders['count'])) {
				foreach ($orders['data'] as $order) {
					$proxyCount += $this->allocateProxies($order);
				}

				$response = array(
					'message' => array(
						'status' => 'success',
						'text' => $proxyCount . ' new ' . $table . ' allocated successfully for ' . $orders['count'] . ' orders.'
					)
				);
			}
		}

	/**
	 * Shell method for processing replaced proxy removal
	 *
	 * @param string $table
	 *
	 * @return array $response
	 */
		public function shellProcessRemoveReplacedProxies($table) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'There aren\'t any new replaced ' . $table . ' to remove, please try again later.'
				)
			);
			$proxies = $this->fetch('proxies', array(
				'conditions' => array(
					'replacement_removal_date <' => date('Y-m-d H:i:s', time()),
					'status' => 'replaced'
				),
				'fields' => array(
					'id',
					'node_id',
					'replacement_removal_date',
					'status'
				),
				'limit' => 100000
			));

			if (!empty($proxies['count'])) {
				$nodeData = $proxyIds = array();

				foreach ($proxies['data'] as $proxy) {
					$nodeData[] = array(
						'allocated' => false,
						'id' => $proxy['node_id'],
						'processing' => false
					);
					$proxyIds[] = $proxy['id'];
				}

				if (
					$this->delete('proxies', array(
						'id' => $proxyIds
					)) &&
					$this->save('nodes', $nodeData)
				) {
					$response = array(
						'message' => array(
							'status' => 'success',
							'text' => $proxies['count'] . ' replaced proxies removed successfully.'
						)
					);
				}
			}

			return $response;
		}

	/**
	 * Shell method for processing scheduled proxy replacements
	 *
	 * @param string $table
	 *
	 * @return array $response
	 */
		public function shellProcessScheduledProxyReplacements($table) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'There aren\'t any new scheduled ' . $table . ' to replace, please try again later.'
				)
			);
			$intervalTypes = array(
				'month',
				'week'
			);
			$intervalValues = array_keys(array_fill(1, 24, true));
			$proxyParameters = array(
				'conditions' => array(
					'next_replacement_available <' => date('Y-m-d H:i:s', time()),
					'NOT' => array(
						'status' => 'replaced'
					)
				),
				'fields' => array(
					'automatic_replacement_interval_type',
					'automatic_replacement_interval_value',
					'disable_http',
					'id',
					'ip',
					'node_id',
					'password',
					'replacement_city',
					'replacement_country_code',
					'replacement_region',
					'require_authentication',
					'status',
					'transfer_authentication',
					'user_id',
					'username',
					'whitelisted_ips'
				),
				'limit' => 100000
			);

			foreach ($intervalTypes as $intervalType) {
				foreach ($intervalValues as $intervalValue) {
					$proxyParameters['conditions']['OR'][] = array(
						'AND' => array(
							'automatic_replacement_interval_type' => $intervalType,
							'automatic_replacement_interval_value' => $intervalValue,
							'last_replacement_date <' => date('Y-m-d H:i:s', strtotime('-' . $intervalValue . ' ' . $intervalType))
						)
					);
				}
			}

			$proxies = $this->fetch('proxies', $proxyParameters);

			if (!empty($proxies['count'])) {
				$users = array();
				$proxyData = array(
					'replacement_removal_date' => date('Y-m-d H:i:s', strtotime('+24 hours')),
					'status' => 'replaced'
				);

				foreach ($proxies['data'] as $key => $proxy) {
					$key = !empty($proxy['replacement_country_code']) ? ($proxy['replacement_city'] . '_' . $proxy['replacement_region'] . '_' . $proxy['replacement_country_code']) : 0;
					$users[$proxy['user_id']][$key][] = array_merge($proxy, $proxyData);
				}

				foreach ($users as $userId => $userProxyGroups) {
					foreach ($userProxyGroups as $location => $userProxies) {
						$userEmail = $this->fetch('users', array(
							'conditions' => array(
								'id' => $userId
							),
							'fields' => array(
								'email'
							)
						));

						if (!empty($userEmail['count'])) {
							$userEmail = $userEmail['data'][0];
							$defaultProcessingNodeParameters = $processingNodeParameters = array(
								'conditions' => array(
									'AND' => array(
										'allocated' => false,
										'NOT' => array(
											'ip' => null
										),
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
								'limit' => count($userProxies),
								'sort' => 'random'
							);

							if (!empty($location)) {
								$location = explode('_', $location);
								$processingNodeParameters['conditions']['AND'] += ($location = array(
									'city' => $location[0],
									'country_code' => $location[2],
									'region' => $location[1]
								));
							}

							$processingNodes = $this->fetch('nodes', $processingNodeParameters);
							$replacementNodeCount = count($processingNodes['data']);
							$userProxyCount = count($userProxies);

							if (
								!empty($location) &&
								$replacementNodeCount !== $userProxyCount
							) {
								$processingNodeParameters = $defaultProcessingNodeParameters;
								$processingNodeParameters['AND']['NOT'] = $location;
								$processingNodeParameters['limit'] = $userProxyCount - $replacementNodeCount;
								$defaultProcessingNodes = $this->fetch('nodes', $processingNodeParameters);

								if (!empty($defaultProcessingNodes['data'])) {
									$replacementNodeCount += count($defaultProcessingNodes['data']);
									$processingNodes['data'] += $defaultProcessingNodes['data'];
								}
							}

							if ($replacementNodeCount === $userProxyCount) {
								$allocatedNodes = array();
								$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, count($processingNodes['data']), array(
									'processing' => true
								)));

								if ($this->save('nodes', $processingNodes['data'])) {
									foreach ($processingNodes['data'] as $key => $row) {
										$allocatedNodes[] = array(
											'allocated' => true,
											'id' => ($processingNodes['data'][$key]['node_id'] = $processingNodes['data'][$key]['id']),
											'processing' => false
										);
										$processingNodes['data'][$key] += array(
											'automatic_replacement_interval_type' => $userProxies[$key]['automatic_replacement_interval_type'],
											'automatic_replacement_interval_value' => $userProxies[$key]['automatic_replacement_interval_value'],
											'last_replacement_date' => date('Y-m-d H:i:s', time()),
											'next_replacement_available' => date('Y-m-d H:i:s', strtotime('+1 week')),
											'order_id' => $orderId,
											'status' => 'online',
											'user_id' => $userId
										);
										$processingNodes['data'][$key]['previous_node_id'] = $userProxies[$key]['node_id'];

										if (!empty($location)) {
											$processingNodes['data'][$key] += array_combine(array(
												'replacement_city',
												'replacement_region',
												'replacement_country_code'
											), $location);
										}

										if (!empty($userProxies[$key]['transfer_authentication'])) {
											$processingNodes['data'][$key] += array(
												'disable_http' => $userProxies[$key]['disable_http'],
												'password' => $userProxies[$key]['password'],
												'transfer_authentication' => true,
												'username' => $userProxies[$key]['username'],
												'whitelisted_ips' => $userProxies[$key]['whitelisted_ips']
											);
										}

										unset($processingNodes['data'][$key]['id']);
										unset($processingNodes['data'][$key]['processing']);
									}

									if (
										$this->save('nodes', $allocatedNodes) &&
										$this->save('proxies', $userProxies) &&
										$this->save('proxies', $processingNodes['data'])
									) {
										$mailParameters = array(
											'from' => $this->settings['from_email'],
											'subject' => $replacementNodeCount . ' scheduled proxies replaced successfully',
											'template' => array(
												'name' => 'items_replaced',
												'parameters' => array(
													'link' => 'https://' . $this->settings['base_domain'] . '/orders/' . $orderId,
													'new_items' => $processingNodes['data'],
													'old_items' => $userProxies,
													'table' => 'proxies'
												)
											),
											'to' => $userEmail
										);
										$this->_sendMail($mailParameters);
									}
								}
							}
						}
					}
				}

				$response = array(
					'message' => array(
						'status' => 'success',
						'text' => $proxies['count'] . ' scheduled proxies replaced successfully.'
					)
				);
			}

			return $response;
		}

	}
?>
