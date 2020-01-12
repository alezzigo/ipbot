<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class CartsModel extends AppModel {

	/**
	 * Retrieve cart from session
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _retrieveCart($parameters) {
			$response = false;

			if (!empty($parameters['session'])) {
				$cartParameters = array(
					'conditions' => array(
						'user_id' => ($parameters['user']['id'] ? $parameters['user']['id'] : $parameters['session'])
					),
					'fields' => array(
						'created',
						'id',
						'modified',
						'user_id'
					),
					'limit' => 1,
					'sort' => array(
						'field' => 'modified',
						'order' => 'DESC'
					)
				);
				$cart = $this->fetch('carts', $cartParameters);

				if (empty($cart['count'])) {
					$cartData = array(
						$cartParameters['conditions']
					);
					$this->save('carts', $cartData);
					$cart = $this->fetch('carts', $cartParameters);
				}

				if (!empty($cart['count'])) {
					$response = $cart['data'][0];
				}
			}

			return $response;
		}

	/**
	 * Retrieve items for cart
	 *
	 * @param array $cartData
	 * @param array $cartProducts
	 *
	 * @return array $response Response data
	 */
		protected function _retrieveCartItems($cartData, $cartProducts) {
			$response = false;
			$cartItems = $this->fetch('cart_items', array(
				'conditions' => array(
					'cart_id' => $cartData['id']
				),
				'fields' => array(
					'cart_id',
					'id',
					'interval_type',
					'interval_value',
					'product_id',
					'quantity',
					'user_id'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			));

			if (!empty($cartItems['count'])) {
				foreach ($cartItems['data'] as $key => $cartItem) {
					if (!empty($cartProducts[$cartItem['product_id']])) {
						$cartItem = array_merge($cartProducts[$cartItem['product_id']], $cartItem);

						if ($cartItem['quantity'] === 1) {
							$cartItem['name'] = $this->_formatPluralToSingular($cartItem['name']);
						}

						$cartItem['price'] = $this->_calculateItemPrice($cartItem);
						$cartItems[$cartItem['id']] = $cartItem;
					}
				}

				unset($cartItems['count']);
				unset($cartItems['data']);
				$response = $cartItems;
			}

			return $response;
		}

	/**
	 * Retrieve products for cart
	 *
	 * @param array $cartData
	 *
	 * @return array $response
	 */
		protected function _retrieveProducts($cartData) {
			$response = false;
			$cartItemProductIds = $this->fetch('cart_items', array(
				'conditions' => array(
					'cart_id' => $cartData['id']
				),
				'fields' => array(
					'product_id'
				)
			));

			if (!empty($cartItemProductIds['count'])) {
				$cartProductParameters = array(
					'conditions' => array(
						'id' => array_unique(array_filter($cartItemProductIds['data']))
					),
					'fields' => array(
						'created',
						'has_shipping',
						'has_tax',
						'ip_version',
						'name',
						'maximum_quantity',
						'minimum_quantity',
						'modified',
						'price_per',
						'type',
						'uri'
					),
					'sort' => array(
						'field' => 'created',
						'order' => 'DESC'
					)
				);
				$cartProducts = $this->fetch('products', $cartProductParameters);
				$cartProductParameters['fields'] = array(
					'id'
				);
				$cartProductIds = $this->fetch('products', $cartProductParameters);

				if (
					!empty($cartProducts['count']) &&
					!empty($cartProductIds['count']) &&
					$cartProductIds['count'] === $cartProducts['count']
				) {
					$response = array_combine($cartProductIds['data'], $cartProducts['data']);
				}
			}

			return $response;
		}

	/**
	 * Process cart requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function cart($table, $parameters) {
			$response = array(
				'data' => array(),
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error processing your cart request, please try again.')
				)
			);

			if ($cartData = $this->_retrieveCart($parameters)) {
				$response['message']['text'] = 'There are no items in your cart.';

				if (!empty($cartData)) {
					$cartProducts = $this->_retrieveProducts($cartData);
					$cartItems = $this->_retrieveCartItems($cartData, $cartProducts);

					if (
						$cartProducts &&
						$cartItems
					) {
						$response['message']['text'] = '';
					}

					$cartItemData = array(
						array_intersect_key($parameters['data'], array(
							'id' => true,
							'interval_type' => true,
							'interval_value' => true,
							'product_id' => true,
							'quantity' => true
						))
					);

					if (!empty($cartItemData[0])) {
						$response['message'] = array(
							'status' => 'error',
							'text' => $defaultMessage
						);

						if (empty($cartItemData[0]['id'])) {
							$response['message']['text'] = 'Error adding item to your cart, please try again.';

							if (count($cartItemData[0]) === 4) {
								$response['message']['text'] = 'Invalid cart item parameters, please try again.';

								if (
									!empty($cartItemData[0]['interval_type']) &&
									in_array($cartItemData[0]['interval_type'], array('month', 'year')) &&
									!empty($cartItemData[0]['interval_value']) &&
									is_numeric($cartItemData[0]['interval_value']) &&
									in_array($cartItemData[0]['interval_value'], range(1, 12)) &&
									!empty($cartItemData[0]['product_id']) &&
									is_numeric($cartItemData[0]['product_id']) &&
									!empty($cartItemData[0]['quantity']) &&
									is_numeric($cartItemData[0]['quantity'])
								) {
									$response['message']['text'] = 'Invalid product ID, please try again.';
									$cartProduct = $this->fetch('products', array(
										'conditions' => array(
											'id' => $cartItemData[0]['product_id']
										),
										'fields' => array(
											'created',
											'has_shipping',
											'has_tax',
											'ip_version',
											'name',
											'maximum_quantity',
											'minimum_quantity',
											'modified',
											'price_per',
											'type',
											'uri'
										),
										'sort' => array(
											'field' => 'modified',
											'modified' => 'DESC'
										)
									));

									if (!empty($cartProduct['data'][0])) {
										$response['message']['text'] = 'Invalid product quantity, please try again.';
										$cartProduct = $cartProduct['data'][0];

										if (
											$cartItemData[0]['quantity'] <= $cartProduct['maximum_quantity'] &&
											$cartItemData[0]['quantity'] >= $cartProduct['minimum_quantity']
										) {
											$cartItemData = array(
												array_merge($cartItemData[0], array(
													'cart_id' => $cartData['id'],
													'user_id' => $cartData['user_id']
												))
											);

											if ($this->save('cart_items', $cartItemData)) {
												$response = array(
													'message' => array(
														'status' => 'success',
														'text' => 'Cart item added successfully.'
													),
													'redirect' => $this->settings['base_url'] . 'cart'
												);
											}
										}
									}
								}
							}
						} else {
							if (
								count($cartItemData[0]) === 1 &&
								$cartItemIds = array_values($cartItemData[0]['id'])
							) {
								$cartItemIds = $this->fetch('cart_items', array(
									'fields' => array(
										'id'
									),
									'conditions' => array(
										'cart_id' => $cartData['id'],
										'id' => $cartItemIds
									)
								));
								$response['message']['text'] = 'Error deleting cart items, please try again.';

								if (
									!empty($cartItemIds['count']) &&
									$this->delete('cart_items', array(
										'id' => $cartItemIds['data']
									))
								) {
									$cartItems = array_diff_key($cartItems, array_combine($cartItemIds['data'], array_fill(1, count($cartItemIds['data']), true)));
									$response['message'] = array(
										'status' => 'success',
										'text' => 'Cart items deleted successfully.'
									);
								}
							} elseif (
								!empty($cartItems[$cartItemData[0]['id']]) &&
								count($cartItemData[0]) === 4
							) {
								$cartItem = $cartItems[$cartItemData[0]['id']];

								if (
									!empty($cartItemData[0]['interval_type']) &&
									in_array($cartItemData[0]['interval_type'], array('month', 'year')) &&
									!empty($cartItemData[0]['interval_value']) &&
									is_numeric($cartItemData[0]['interval_value']) &&
									!empty($cartItemData[0]['quantity']) &&
									is_numeric($cartItemData[0]['quantity']) &&
									$cartItemData[0]['quantity'] <= $cartItem['maximum_quantity'] &&
									$cartItemData[0]['quantity'] >= $cartItem['minimum_quantity'] &&
									$this->save('cart_items', $cartItemData)
								) {
									$cartItems[$cartItemData[0]['id']] = $cartItem = array_merge($cartItem, $cartItemData[0]);
									$cartItems[$cartItemData[0]['id']]['price'] = $this->_calculateItemPrice($cartItem);
									$response['message']['text'] = '';
								}
							}
						}
					}

					$response = array_merge($response, array(
						'count' => count($cartItems),
						'data' => $cartItems
					));
				}
			}

			return $response;
		}

	/**
	 * Checkout
	 *
	 * @return array
	 */
		public function checkout() {
			return array();
		}

	/**
	 * Confirm order
	 *
	 * @return array
	 */
		public function confirm() {
			return array();
		}

	/**
	 * Complete order
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function complete($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Error processing your order completion request, please try again.'
				),
				'redirect' => $this->settings['base_url'] . 'cart'
			);

			if (
				($cart = $this->_retrieveCart($parameters)) &&
				($cartProducts = $this->_retrieveProducts($cart)) &&
				($cartItems = $this->_retrieveCartItems($cart, $cartProducts))
			) {
				$invoices = $orders = array();
				$invoiceConditions = $orderConditions = array(
					'currency' => $this->settings['billing']['currency'],
					'user_id' => $parameters['user']['id'] ? $parameters['user']['id'] : $parameters['session']
				);
				$invoiceConditions = array_merge($invoiceConditions, array(
					'payable' => true,
					'status' => 'unpaid'
				));
				$orderConditions['status'] = 'pending';
				$parameters['user'] = $this->_authenticate('users', $parameters);
				$total = 0;

				if (!empty($parameters['user']['id'])) {
					$invoiceConditions['user_id'] = $orderConditions['user_id'] = $parameters['user']['id'];
				}

				foreach ($cartItems as $cartItem) {
					if (empty($cartProducts[$cartItem['product_id']])) {
						continue;
					}

					$cartProduct = $cartProducts[$cartItem['product_id']];
					$invoices[$cartItem['interval_value'] . '_' . $cartItem['interval_type']][] = $cartItem['id'];
					$order = array_merge($orderConditions, array(
						'cart_item_id' => $cartItem['id'],
						'interval_type' => $cartItem['interval_type'],
						'interval_value' => $cartItem['interval_value'],
						'name' => $cartItem['name'],
						'price' => $cartItem['price'],
						'product_id' => $cartItem['product_id'],
						'quantity' => $cartItem['quantity'],
						'type' => $cartItem['type']
					));
					$item = array_merge($order, $cartProduct);
					$order['shipping'] = $this->_calculateItemShippingPrice($item);
					$order['tax'] = $this->_calculateItemTaxPrice($item);
					$orders[] = $order;
					$orderConditions['cart_item_id'][] = $cartItem['id'];
					$total += $cartItem['price'];
				}

				$total = number_format(round($total * 100) / 100, 2, '.', '');

				if (
					!empty($orders) &&
					$this->save('orders', $orders)
				) {
					foreach ($invoices as $interval => $cartItemIds) {
						$interval = explode('_', $interval);
						$intervalType = $interval[0];
						$intervalValue = $interval[1];
						$invoiceConditions['cart_items'] = sha1(json_encode($cartItemIds));
						$invoiceData = array(
							$invoiceConditions
						);
						$invoiceOrders = array();

						if ($this->save('invoices', $invoiceData)) {
							$invoice = $this->fetch('invoices', array(
								'conditions' => $invoiceConditions,
								'fields' => array(
									'created',
									'due',
									'id',
									'initial_invoice_id',
									'modified',
									'status',
									'user_id'
								),
								'limit' => 1,
								'sort' => array(
									'field' => 'created',
									'order' => 'DESC'
								)
							));
							$orderIds = $this->fetch('orders', array(
								'conditions' => array_merge($orderConditions, array(
									'cart_item_id' => $cartItemIds
								)),
								'fields' => array(
									'id'
								)
							));

							if (
								!empty($invoice['count']) &&
								!empty($orderIds['count'])
							) {
								foreach ($orderIds['data'] as $orderId) {
									$invoiceOrders[] = array(
										'invoice_id' => $invoice['data'][0]['id'],
										'order_id' => $orderId
									);
								}

								if ($this->save('invoice_orders', $invoiceOrders)) {
									$invoiceId = $invoice['data'][0]['id'];
									$response = array(
										'redirect' => $this->settings['base_url'] . 'invoices'
									);
								}
							}
						}
					}

					$this->delete('carts', array(
						'id' => $cart['id']
					));
					$this->delete('cart_items', array(
						'cart_id' => $cart['id']
					));

					if (
						count($invoices) === 1 &&
						!empty($invoiceId)
					) {
						$response['redirect'] .= '/' . $invoiceId . '#payment';
					}
				}
			}

			return $response;
		}

	/**
	 * View cart
	 *
	 * @return array
	 */
		public function view() {
			return array();
		}

	}
?>
