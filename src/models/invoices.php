<?php
/**
 * Invoices Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/users.php');

class InvoicesModel extends UsersModel {

/**
 * Calculate invoice payment details
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _calculateInvoicePaymentDetails($invoiceData) {
		$response = $invoiceData;

		if (
			$response['invoice']['status'] === 'unpaid' ||
			$response['invoice']['amount_paid'] < $response['invoice']['total']
		) {
			$response['invoice']['total'] = $response['invoice']['subtotal'] = 0;

			if (!empty($response['orders'])) {
				$invoiceOrderProducts = array();

				foreach ($response['orders'] as $key => $invoiceOrder) {
					if (empty($invoiceOrderProducts[$invoiceOrder['product_id']])) {
						$invoiceOrderProduct = $this->find('products', array(
							'conditions' => array(
								'id' => $invoiceOrder['product_id']
							),
							'fields' => array(
								'created',
								'has_shipping',
								'has_tax',
								'name',
								'maximum_quantity',
								'minimum_quantity',
								'modified',
								'price_per',
								'type',
								'uri',
								'volume_discount_divisor',
								'volume_discount_multiple'
							)
						));

						if (empty($invoiceOrderProduct['count'])) {
							$this->delete('orders', array(
								'id' => $invoiceOrder['id']
							));
							continue;
						}

						$invoiceOrderProducts[$invoiceOrder['product_id']] = $invoiceOrderProduct['data'][0];
					}

					$invoiceOrderProduct = $invoiceOrderProducts[$invoiceOrder['product_id']];
					$response['invoice']['shipping'] += $this->_calculateInvoiceOrderShippingPrice($response['invoice'], $invoiceOrder, $invoiceOrderProduct);
					$response['invoice']['subtotal'] += $invoiceOrder['price'];
					$response['invoice']['tax'] += $this->_calculateInvoiceOrderTaxPrice($response['invoice'], $invoiceOrder, $invoiceOrderProduct);
					$response['invoice']['total'] += $invoiceOrder['shipping'] + $invoiceOrder['tax'];
				}
			}

			$response['invoice']['total'] += $response['invoice']['subtotal'];
			$invoiceCalculationData = $response['invoice'];
			unset($invoiceCalculationData['billing']);
			unset($invoiceCalculationData['created']);
			unset($invoiceCalculationData['initial_invoice_id']);
			unset($invoiceCalculationData['modified']);
			unset($invoiceCalculationData['user_id']);

			if (!$this->save('invoices', array(
				$invoiceCalculationData
			))) {
				$response = $invoiceData;
			}
		}

		$response['invoice']['amount_due'] = max(0, round(($response['invoice']['total'] - $response['invoice']['amount_paid']) * 100) / 100);
		$response['invoice']['payment_currency_name'] = $this->settings['billing']['currency_name'];
		$response['invoice']['payment_currency_symbol'] = $this->settings['billing']['currency_symbol'];
		return $response;
	}

/**
 * Calculate invoice order shipping price
 *
 * @param array $invoiceData
 * @param array $orderData
 * @param array $productData
 *
 * @return float $response
 */
	protected function _calculateInvoiceOrderShippingPrice($invoiceData, $orderData, $productData) {
		$response = 0.00;
		// ..
		return $response;
	}

/**
 * Calculate invoice order tax price
 *
 * @param array $invoiceData
 * @param array $orderData
 * @param array $productData
 *
 * @return float $response
 */
	protected function _calculateInvoiceOrderTaxPrice($invoiceData, $orderData, $productData) {
		$response = 0.00;
		// ..
		return $response;
	}

/**
 * Retrieve invoice order data
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _retrieveInvoiceOrders($invoiceData) {
		$response = array();
		$invoiceOrders = $this->find('invoice_orders', array(
			'conditions' => array(
				'invoice_id' => (!empty($invoiceData['initial_invoice_id']) ? $invoiceData['initial_invoice_id'] : $invoiceData['id'])
			),
			'fields' => array(
				'order_id'
			)
		));

		if (!empty($invoiceOrders['count'])) {
			$orders = $this->find('orders', array(
				'conditions' => array(
					'id' => $invoiceOrders['data']
				),
				'fields' => array(
					'created',
					'id',
					'interval_type',
					'interval_value',
					'modified',
					'name',
					'price',
					'product_id',
					'quantity',
					'session_id',
					'shipping',
					'status',
					'tax',
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
 * Retrieve invoice subscription data
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _retrieveInvoiceSubscriptions($invoiceData) {
		$response = array();
		$invoiceSubscriptions = $this->find('subscriptions', array(
			'conditions' => array(
				'invoice_id' => $invoiceData['id']
			),
			'fields' => array(
				'created',
				'id',
				'invoice_id',
				'modified',
				'plan_id'
			)
		));

		if (!empty($invoiceSubscriptions['count'])) {
			$response = $invoiceSubscriptions['data'];
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
	protected function _retrieveInvoiceTransactions($invoiceData) {
		$response = array();
		$invoiceTransactions = $this->find('transactions', array(
			'conditions' => array(
				'invoice_id' => $invoiceData['id'],
				'transaction_processed' => true,
				'transaction_processing' => false
			),
			'fields' => array(
				'billing_address_1',
				'billing_address_2',
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
				'id',
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
				'plan_id',
				'provider_country_code',
				'provider_email',
				'provider_id',
				'sandbox',
				'transaction_charset',
				'transaction_date',
				'transaction_method',
				'transaction_processed',
				'transaction_processing',
				'transaction_raw',
				'transaction_token',
				'user_id'
			),
			'sort' => array(
				'field' => 'transaction_date',
				'order' => 'ASC'
			)
		));
		$paymentMethods = $this->find('payment_methods', array(
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
				$invoiceTransactions['data'][$key]['transaction_date'] = date('M d Y', $transactionTime) . ' at ' . date('g:ia', $transactionTime) . ' ' . $this->settings['timezone'];

				if (!empty($paymentMethod = $paymentMethods['data'][$invoiceTransaction['payment_method_id']])) {
					$invoiceTransactions['data'][$key]['payment_method'] = $paymentMethod;
				}
			}

			$response = $invoiceTransactions['data'];
		}

		return $response;
	}

/**
 * Process invoice requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function invoice($table, $parameters) {
		$response = array(
			'data' => array(),
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your invoice request, please try again.')
			)
		);
		$invoiceData = $this->find($table, array(
			'conditions' => $parameters['conditions'],
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
				'status',
				'subtotal',
				'tax',
				'total',
				'user_id'
			)
		));

		if (!empty($invoiceData['count'])) {
			$invoiceData = $invoiceData['data'][0];
			$invoiceData['created'] = date('M d Y', strtotime($invoiceData['created'])) . ' at ' . date('g:ia', strtotime($invoiceData['created'])) . ' ' . $this->settings['timezone'];
			$invoiceOrders = $this->_retrieveInvoiceOrders($invoiceData);
			$invoiceSubscriptions = $this->_retrieveInvoiceSubscriptions($invoiceData);
			$invoiceTransactions = $this->_retrieveInvoiceTransactions($invoiceData);

			if (
				!empty($invoiceData) &&
				!empty($invoiceOrders)
			) {
				if (!empty($this->settings['billing'])) {
					$invoiceData['billing'] = $this->settings['billing'];
				}

				$response = array(
					'data' => array(
						'invoice' => $invoiceData,
						'orders' => $invoiceOrders,
						'subscriptions' => $invoiceSubscriptions,
						'transactions' => $invoiceTransactions
					),
					'message' => array(
						'status' => 'success',
						'text' => ''
					)
				);
				$response['data'] = array_replace_recursive($response['data'], $this->_calculateInvoicePaymentDetails($response['data']));
			}
		}

		return $response;
	}

/**
 * List invoices
 *
 * @return array
 */
	public function list() {
		return array();
	}

/**
 * View invoice
 *
 * @param array $parameters
 *
 * @return array $response
 */
	public function view($parameters) {
		if (
			empty($invoiceId = $parameters['id']) ||
			!is_numeric($invoiceId)
		) {
			$this->redirect($this->settings['base_url'] . 'invoices');
		}

		$response = array(
			'invoice_id' => $parameters['id']
		);
		return $response;
	}

}
