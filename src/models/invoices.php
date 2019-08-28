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
 * Retrieve invoice order data
 *
 * @param array $invoiceData
 *
 * @return array $response
 */
	protected function _retrieveInvoiceOrders($invoiceData) {
		$response = array();
		$invoiceOrders = $this->find('invoice_orders', array(
			'fields' => array(
				'order_id'
			),
			'conditions' => array(
				'invoice_id' => $invoiceData['id']
			)
		));

		if (!empty($invoiceOrders['count'])) {
			$orders = $this->find('orders', array(
				'conditions' => array(
					'id' => $invoiceOrders['data']
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
		// ..
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
		// ..
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
			'conditions' => $parameters['conditions']
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
				$response = array(
					'data' => array(
						'invoice' => $invoiceData,
						'orders' => $invoiceOrders,
						'subscriptions' => $invoiceSubscriptions,
						'transactions' => $invoiceTransactions
					),
					'message' => ''
				);
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
