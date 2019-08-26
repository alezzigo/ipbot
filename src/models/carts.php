<?php
/**
 * Carts Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class CartsModel extends AppModel {

/**
 * Calculate price for cart item
 *
 * @param array $cartItem
 *
 * @return integer $response
 */
	protected function _calculateCartItemPrice($cartItem) {
		$interval = $cartItem['interval_value'] * ($cartItem['interval_type'] == 'year' ? 12 : 1);
		$response = number_format(($cartItem['price_per'] * $cartItem['quantity'] * $interval) - (($cartItem['price_per'] * $cartItem['quantity']) * (($cartItem['quantity'] / $cartItem['volume_discount_divisor']) * $cartItem['volume_discount_multiple'] * $cartItem['interval_value'])), 2, '.', '');
		return $response;
	}

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
					'id' => $parameters['session']
				),
				'limit' => 1,
				'sort' => array(
					'field' => 'modified',
					'order' => 'DESC'
				)
			);
			$this->save('carts', array(
				$cartParameters['conditions']
			));
			$cartData = $this->find('carts', $cartParameters);

			if (!empty($cartData['count'])) {
				$response = $cartData['data'][0];
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
		$cartItemParameters = array(
			'conditions' => array(
				'cart_id' => $cartData['id']
			),
			'sort' => array(
				'field' => 'created',
				'order' => 'DESC'
			)
		);
		$cartItems = $this->find('cart_items', $cartItemParameters);

		if (!empty($cartItems['count'])) {
			foreach ($cartItems['data'] as $key => $cartItem) {
				if (!empty($cartProductDetails = $cartProducts[$cartItem['product_id']])) {
					$cartItem = array_merge($cartProductDetails, $cartItem);
					$cartItem['price'] = $this->_calculateCartItemPrice($cartItem);
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
		$cartItemProductIds = $this->find('cart_items', array(
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
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			);
			$cartProducts = $this->find('products', $cartProductParameters);
			$cartProductParameters['fields'] = array(
				'id'
			);
			$cartProductIds = $this->find('products', $cartProductParameters);

			if (
				!empty($cartProductIds['count']) &&
				!empty($cartProducts['count']) &&
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
			'message' => ($defaultMessage = 'Error processing your cart request, please try again.')
		);

		if ($cartData = $this->_retrieveCart($parameters)) {
			$response['message'] = 'There are no items in your cart.';

			if (!empty($cartData)) {
				$cartProducts = $this->_retrieveProducts($cartData);
				$cartItems = $this->_retrieveCartItems($cartData, $cartProducts);

				if (
					$cartProducts &&
					$cartItems
				) {
					$response['message'] = '';
				}

				if (!empty($cartItemData = array_intersect_key($parameters['data'], array(
					'id' => true,
					'interval_type' => true,
					'interval_value' => true,
					'product_id' => true,
					'quantity' => true
				)))) {
					$response['message'] = $defaultMessage;

					if (empty($cartItemData['id'])) {
						$response['message'] = 'Error adding item to your cart, please try again.';

						if (count($cartItemData) === 4) {
							$response['message'] = 'Invalid cart item parameters, please try again.';

							if (
								!empty($cartItemData['interval_type']) &&
								in_array($cartItemData['interval_type'], array('month', 'year')) &&
								!empty($cartItemData['interval_value']) &&
								is_numeric($cartItemData['interval_value']) &&
								in_array($cartItemData['interval_value'], range(1, 12)) &&
								!empty($cartItemData['product_id']) &&
								is_numeric($cartItemData['product_id']) &&
								!empty($cartItemData['quantity']) &&
								is_numeric($cartItemData['quantity'])
							) {
								$response['message'] = 'Invalid product ID, please try again.';
								$cartProduct = $this->find('products', array(
									'conditions' => array(
										'id' => $cartItemData['product_id']
									),
									'sort' => array(
										'field' => 'modified',
										'modified' => 'DESC'
									)
								));

								if (!empty($cartProduct = $cartProduct['data'][0])) {
									$response['message'] = 'Invalid product quantity, please try again.';

									if (
										$cartItemData['quantity'] <= $cartProduct['maximum_quantity'] &&
										$cartItemData['quantity'] >= $cartProduct['minimum_quantity']
									) {
										$cartItemData['cart_id'] = $parameters['session'];

										if ($this->save('cart_items', array(
											$cartItemData
										))) {
											$response = array(
												'message' => 'Cart item added successfully.',
												'redirect' => $this->settings['base_url'] . 'cart'
											);
										}
									}
								}
							}
						}
					} else {
						if (
							count($cartItemData) === 1 &&
							$cartItemIds = array_values($cartItemData['id'])
						) {
							$cartItemIds = $this->find('cart_items', array(
								'conditions' => array(
									'id' => $cartItemIds,
									'cart_id' => $parameters['session']
								),
								'fields' => array(
									'id'
								)
							));
							$response['message'] = 'Error deleting cart items, please try again.';

							if (
								!empty($cartItemIds['count']) &&
								$this->delete('cart_items', array(
									'id' => $cartItemIds['data']
								))
							) {
								$response['message'] = 'Cart items deleted successfully.';
								$cartItems = array_diff_key($cartItems, array_combine($cartItemIds['data'], array_fill(1, count($cartItemIds['data']), true)));
							}
						} elseif (
							!empty($cartItem = $cartItems[$cartItemData['id']]) &&
							count($cartItemData) === 4
						) {
							if (
								!empty($cartItemData['interval_type']) &&
								in_array($cartItemData['interval_type'], array('month', 'year')) &&
								!empty($cartItemData['interval_value']) &&
								is_numeric($cartItemData['interval_value']) &&
								!empty($cartItemData['quantity']) &&
								is_numeric($cartItemData['quantity']) &&
								$cartItemData['quantity'] <= $cartItem['maximum_quantity'] &&
								$cartItemData['quantity'] >= $cartItem['minimum_quantity'] &&
								$this->save('cart_items', array(
									$cartItemData
								))
							) {
								$cartItems[$cartItemData['id']] = $cartItem = array_merge($cartItem, $cartItemData);
								$cartItems[$cartItemData['id']]['price'] = $this->_calculateCartItemPrice($cartItem);
								$response['message'] = '';
							}
						}
					}
				}

				$response['data'] = $cartItems;
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
			'message' => 'Error processing your order completion request, please try again.',
			'redirect' => $this->settings['base_url'] . 'cart'
		);

		if (
			($cart = $this->_retrieveCart($parameters)) &&
			($cartProducts = $this->_retrieveProducts($cart)) &&
			($cartItems = $this->_retrieveCartItems($cart, $cartProducts))
		) {
			$conditions = array(
				'session_id' => $cart['id'],
				'status' => 'pending'
			);
			$orders = $invoiceOrders = array();
			$total = 0;

			if ($cart['user_id']) {
				$conditions['user_id'] = $cartItem['user_id'];
			}

			foreach ($cartItems as $cartItem) {
				$orders[] = array_merge($conditions, array(
					'interval_type' => $cartItem['interval_type'],
					'interval_value' => $cartItem['interval_value'],
					'name' => $cartItem['name'],
					'price' => $cartItem['price'],
					'product_id' => $cartItem['product_id'],
					'quantity' => $cartItem['quantity'],
					'type' => $cartItem['type']
				));
				$total += $cartItem['price'];
			}

			$total = number_format(round($total * 100) / 100, 2, '.', '');

			if (
				$this->save('invoices', array(
					$conditions
				)) &&
				$this->save('orders', $orders)
			) {
				$invoice = $this->find('invoices', array(
					'conditions' => $conditions,
					'limit' => 1,
					'sort' => array(
						'field' => 'created',
						'order' => 'DESC'
					)
				));
				$orderIds = $this->find('orders', array(
					'conditions' => $conditions,
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

					if (
						$this->save('invoice_orders', $invoiceOrders) &&
						$this->delete('carts', array(
							'id' => $cart['id']
						)) &&
						$this->delete('cart_items', array(
							'cart_id' => $cart['id']
						))
					) {
						$response = array(
							'redirect' => $this->settings['base_url'] . 'invoices/' . $invoice['data'][0]['id']
						);
					}
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
