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
 * @param array $cartItem Cart item
 *
 * @return array $response Response data
 */
	public function _calculateCartItemPrice($cartItem) {
		$response = false;

		if (
			!empty($cartItem['price_per']) &&
			!empty($cartItem['quantity']) &&
			!empty($cartItem['volume_discount_divisor']) &&
			!empty($cartItem['volume_discount_multiple'])
		) {
			$response = number_format(round(($cartItem['price_per'] * $cartItem['quantity']) - (($cartItem['price_per'] * $cartItem['quantity']) * (($cartItem['quantity'] / $cartItem['volume_discount_divisor']) * $cartItem['volume_discount_multiple'])), 2), 2);
		}

		return $response;
	}

/**
 * Retrieve cart from session
 *
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	public function _retrieveCart($parameters) {
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
			$cart = $this->find('carts', $cartParameters);

			if (!empty($cart['count'])) {
				$response = $cart['data'][0];
			}
		}

		return $response;
	}

/**
 * Retrieve items for cart
 *
 * @param array $cart Cart data
 * @param array $cartProducts Cart products
 *
 * @return array $response Response data
 */
	public function _retrieveCartItems($cart, $cartProducts) {
		$response = false;
		$cartItemParameters = array(
			'conditions' => array(
				'cart_id' => $cart['id']
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
 * @param array $cart Cart data
 *
 * @return array $response Response data
 */
	public function _retrieveProducts($cart) {
		$response = false;
		$cartItemProductIds = $this->find('cart_items', array(
			'conditions' => array(
				'cart_id' => $cart['id']
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
 * @param string $table Table name
 * @param array $parameters Group query parameters
 *
 * @return array $response Response data
 */
	public function cart($table, $parameters) {
		$defaultMessage = 'Error processing your cart request, please try again.';
		$response = array(
			'data' => array(),
			'message' => $defaultMessage
		);

		if ($cart = $this->_retrieveCart($parameters)) {
			$response['message'] = 'There are no items in your cart.';

			if (
				!empty($cart) &&
				$cartProducts = $this->_retrieveProducts($cart)
			) {
				if ($cartItems = $this->_retrieveCartItems($cart, $cartProducts)) {
					$response['message'] = '';

					if (!empty($cartItemData = array_intersect_key($parameters['data'], array(
						'id' => true,
						'interval_type' => true,
						'interval_value' => true,
						'quantity' => true
					)))) {
						$response['message'] = 'Invalid cart item, please try again.';

						if (!empty($cartItem = $cartItems[$cartItemData['id']])) {
							$response['message'] = $defaultMessage;
							unset($cartItem['modified']);

							if (
								$cartItemData['quantity'] <= $cartItem['maximum_quantity'] &&
								$cartItemData['quantity'] >= $cartItem['minimum_quantity'] &&
								$this->save('cart_items', array(
									$cartItemData
								))
							) {
								$cartItems[$cartItemData['id']] = array_merge($cartItems[$cartItemData['id']], $cartItemData);
								$cartItems[$cartItemData['id']]['price'] = $this->_calculateCartItemPrice($cartItems[$cartItemData['id']]);
								$response['message'] = '';
							}
						}
					}

					$response['data'] = $cartItems;
				}
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
 * View cart
 *
 * @return array
 */
	public function view() {
		return array();
	}

}
