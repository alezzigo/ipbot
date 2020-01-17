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
						'id',
						'subtotal',
						'total',
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
	 * Retrieve cart item IDs
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _retrieveCartItemIds($parameters) {
			$response = array();
			$cartItemParameters = array_merge($parameters, array(
				'conditions' => array(
					'cart_id' => $parameters['cart_id']
				),
				'fields' => array(
					'id'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			));
			$cartItemIds = $this->fetch('cart_items', $cartItemParameters);

			if (!empty($cartItemIds['count'])) {
				$response = $cartItemIds;
			}

			return $response;
		}

	/**
	 * Retrieve cart items
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _retrieveCartItems($parameters) {
			$response = array();
			$cartItems = $this->fetch('cart_items', array(
				'conditions' => array(
					'id' => $parameters['cart_item_ids']['data']
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
					if (!empty($parameters['cart_products'][$cartItem['product_id']])) {
						$cartItem = array_merge($parameters['cart_products'][$cartItem['product_id']], $cartItem);

						if ($cartItem['quantity'] === 1) {
							$cartItem['name'] = $this->_formatPluralToSingular($cartItem['name']);
						}

						$cartItem['price'] = $this->_calculateItemPrice($cartItem);
						$response[$cartItem['id']] = $cartItem;
					}
				}
			}

			return $response;
		}

	/**
	 * Retrieve cart products
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _retrieveCartProducts($parameters) {
			$response = array();
			$cartItemProductIds = $this->fetch('cart_items', array(
				'conditions' => array(
					'id' => $parameters['cart_item_ids']['data']
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
						'id',
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

				if (!empty($cartData['id'])) {
					$parameters['cart_id'] = $cartData['id'];
					$parameters['cart_item_ids'] = $this->_retrieveCartItemIds($parameters);
					$parameters['cart_products'] = $this->_retrieveCartProducts($parameters);
					$parameters['cart_items'] = $this->_retrieveCartItems($parameters);

					if (
						$parameters['cart_items'] &&
						$parameters['cart_products']
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
											'id',
											'maximum_quantity',
											'minimum_quantity',
											'price_per'
										)
									));

									if (!empty($cartProduct['count'])) {
										$response['message']['text'] = 'Invalid product quantity, please try again.';

										if (
											$cartItemData[0]['quantity'] <= $cartProduct['data'][0]['maximum_quantity'] &&
											$cartItemData[0]['quantity'] >= $cartProduct['data'][0]['minimum_quantity']
										) {
											$cartItem = array_merge($cartItemData[0], $cartProduct['data'][0]);
											$cartItemPrice = $this->_calculateItemPrice($cartItem);
											// ..
											$cartData = array(
												array_merge($cartData, array(
													'subtotal' => $cartData['subtotal'] + $cartItemPrice,
													'total' => $cartData['total'] + $cartItemPrice
												))
											);
											$cartItemData = array(
												array_merge($cartItemData[0], array(
													'cart_id' => $cartData[0]['id'],
													'user_id' => $cartData[0]['user_id']
												))
											);

											if (
												$this->save('carts', $cartData) &&
												$this->save('cart_items', $cartItemData)
											) {
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
						} elseif (
							!empty($parameters['cart_items'][$cartItemData[0]['id']]) &&
							count($cartItemData[0]) === 4
						) {
							$cartItem = $parameters['cart_items'][$cartItemData[0]['id']];

							if (
								!empty($cartItemData[0]['interval_type']) &&
								in_array($cartItemData[0]['interval_type'], array('month', 'year')) &&
								!empty($cartItemData[0]['interval_value']) &&
								is_numeric($cartItemData[0]['interval_value']) &&
								!empty($cartItemData[0]['quantity']) &&
								is_numeric($cartItemData[0]['quantity']) &&
								$cartItemData[0]['quantity'] <= $cartItem['maximum_quantity'] &&
								$cartItemData[0]['quantity'] >= $cartItem['minimum_quantity']
							) {
								$cartItemPriceDifference = $this->_calculateItemPrice(array_merge($cartItem, $cartItemData[0])) - $cartItem['price'];
								$cartData = array(
									array_merge($cartData, array(
										'subtotal' => $cartData['subtotal'] + $cartItemPriceDifference,
										'total' => $cartData['total'] + $cartItemPriceDifference
									))
								);

								if (
									$this->save('carts', $cartData) &&
									$this->save('cart_items', $cartItemData)
								) {
									$response = array(
										'message' => array(
											'status' => 'success',
											'text' => 'Cart item updated successfully.'
										)
									);
									$cartData = $cartData[0];
								}
							}
						}
					}

					$response = array_merge($response, array(
						'count' => $parameters['cart_item_ids']['count'],
						'data' => array(
							'cart' => $cartData,
							'cart_items' => array_values($parameters['cart_items'])
						)
					));
				}
			}

			return $response;
		}

	/**
	 * Process cart item checkout
	 *
	 * @return array
	 */
		public function checkout() {
			return array();
		}

	/**
	 * Process cart item confirmation
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function confirm($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Error processing your request, please <a href="' . $this->settings['base_url'] . 'cart">review your shopping cart</a> and try again.'
				)
			);

			if ($cartData = $this->_retrieveCart($parameters)) {
				$parameters['cart_id'] = $cartData['id'];
				$parameters['cart_item_ids'] = $this->_retrieveCartItemIds($parameters);
				$parameters['cart_products'] = $this->_retrieveCartProducts($parameters);
				$parameters['cart_items'] = $this->_retrieveCartItems($parameters);
				$invoices = $orders = array();
				$invoiceConditions = $orderConditions = array(
					'currency' => $this->settings['billing']['currency'],
					'user_id' => $cartData['user_id']
				);
				$invoiceConditions = array_merge($invoiceConditions, array(
					'payable' => true,
					'status' => 'unpaid'
				));
				$orderConditions['status'] = 'pending';
				$parameters['user'] = $this->_authenticate('users', $parameters);

				if (!empty($parameters['user']['id'])) {
					$invoiceConditions['user_id'] = $orderConditions['user_id'] = $parameters['user']['id'];
				}

				foreach ($parameters['cart_items'] as $cartItem) {
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
					$order['shipping'] = $this->_calculateItemShippingPrice($order);
					$order['tax'] = $this->_calculateItemTaxPrice($order);
					$orders[] = $order;
				}

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
						'id' => $parameters['cart_id']
					));
					$this->delete('cart_items', array(
						'cart_id' => $parameters['cart_id']
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
	 * Remove cart items from cart
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function remove($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Error removing selectec cart items, please try again.'
				)
			);

			if ($cartData = $this->_retrieveCart($parameters)) {
				$response['message']['text'] = 'There are no items in your cart to remove.';

				if (
					!empty($cartData['id']) &&
					!empty($parameters['items']['carts']['count'])
				) {
					$parameters['cart_item_ids'] = $cartItemIds = $this->fetch('cart_items', array(
						'fields' => array(
							'id'
						),
						'conditions' => array(
							'cart_id' => $cartData['id'],
							'id' => $parameters['items']['carts']['data']
						)
					));
					$parameters['cart_products'] = $this->_retrieveCartProducts($parameters);
					$parameters['cart_items'] = $this->_retrieveCartItems($parameters);
					$cartData = array(
						$cartData
					);

					foreach ($parameters['cart_items'] as $cartItem) {
						$cartData[0]['subtotal'] -= $cartItem['price'];
						$cartData[0]['total'] -= $cartItem['price'];
					}

					if (
						$this->delete('cart_items', array(
							'id' => $cartItemIds['data']
						)) &&
						$this->save('carts', $cartData)
					) {
						$response['message'] = array(
							'status' => 'success',
							'text' => 'Selected cart items removed successfully.'
						);
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
