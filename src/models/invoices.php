<?php
/**
 * Invoices Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class InvoicesModel extends AppModel {

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
								'has_handling',
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
					$response['invoice']['handling'] += $this->_calculateInvoiceOrderHandlingPrice($response['invoice'], $invoiceOrder, $invoiceOrderProduct);
					$response['invoice']['shipping'] += $this->_calculateInvoiceOrderShippingPrice($response['invoice'], $invoiceOrder, $invoiceOrderProduct);
					$response['invoice']['subtotal'] += $invoiceOrder['price'];
					$response['invoice']['tax'] += $this->_calculateInvoiceOrderTaxPrice($response['invoice'], $invoiceOrder, $invoiceOrderProduct);
					$response['invoice']['total'] += $invoiceOrder['handling'] + $invoiceOrder['shipping'] + $invoiceOrder['tax'];
				}
			}

			$response['invoice']['total'] += $response['invoice']['subtotal'];
			unset($response['invoice']['billing']);
			unset($response['invoice']['created']);
			unset($response['invoice']['initial_invoice_id']);
			unset($response['invoice']['modified']);

			if (!$this->save('invoices', array(
				$response['invoice']
			))) {
				return $invoiceData;
			}
		}

		$response['invoice']['amount_applied_to_balance'] = max(0, $invoiceData['amount_paid'] - $invoiceData['amount_applied']);
		return $response;
	}

/**
 * Calculate invoice order handling price
 *
 * @param array $invoiceData
 * @param array $orderData
 * @param array $productData
 *
 * @return float $response
 */
	protected function _calculateInvoiceOrderHandlingPrice($invoiceData, $orderData, $productData) {
		$response = 0.00;
		// ..
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
				'invoice_id' => $invoiceData['id']
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
					'handling',
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
				'invoice_id' => $invoiceData['id']
			),
			'fields' => array(
				'billing_address_1',
				'billing_address_2',
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
				'invoice_id',
				'modified',
				'payment_amount',
				'payment_currency',
				'payment_external_fee',
				'payment_handling_amount',
				'payment_method_id',
				'payment_shipping_amount',
				'payment_status',
				'payment_tax_amount',
				'provider_country_code',
				'provider_email',
				'provider_id',
				'sandbox',
				'transaction_date',
				'transaction_charset',
				'transaction_raw',
				'transaction_token',
				'transaction_type'
			),
			'sort' => array(
				'field' => 'transaction_date',
				'order' => 'ASC'
			)
		));

		if (!empty($invoiceTransactions['count'])) {
			foreach ($invoiceTransactions['data'] as $key => $invoiceTransaction) {
				$transactionTime = strtotime($invoiceTransaction['transaction_date']);
				$invoiceTransactions['data'][$key]['transaction_date'] = date('M d Y', $transactionTime) . ' at ' . date('g:ia', $transactionTime) . ' ' . $this->settings['timezone'];
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
			'message' => ($defaultMessage = 'Error processing your invoice request, please try again.')
		);
		$invoiceData = $this->find($table, array(
			'conditions' => $parameters['conditions'],
			'fields' => array(
				'amount_applied',
				'amount_paid',
				'amount_refunded',
				'created',
				'handling',
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

				$invoiceData['created'] = date('M d Y', strtotime($invoiceData['created'])) . ' at ' . date('g:ia', strtotime($invoiceData['created'])) . ' ' . $this->settings['timezone'];
				$response = array(
					'data' => array(
						'invoice' => $invoiceData,
						'orders' => $invoiceOrders,
						'subscriptions' => $invoiceSubscriptions,
						'transactions' => $invoiceTransactions
					),
					'message' => ''
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
