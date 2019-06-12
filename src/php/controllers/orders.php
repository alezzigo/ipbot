<?php
/**
 * Orders Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once('../../models/orders.php');

class OrdersController extends OrdersModel {

/**
 * API for orders
 *
 * @return array Orders data, status code
 */
	public function api() {
		// ...
	}

/**
 * List orders
 *
 * @return array Orders data
 */
	public function index() {
		return $this->getOrders();
	}

/**
 * View order
 * @todo Move data processing and retrieval to API
 *
 * @return array Order data
 */
	public function view() {
		$orderId = $this->validateId(!empty($_GET['id']) ? $_GET['id'] : '', 'orders') ? $_GET['id'] : $this->redirect($this->config['base_url']);

		if (
			!empty($_POST['configuration_action']) &&
			strtolower($_SERVER['REQUEST_METHOD']) == 'post'
		) {
			// Process request based on configuration action
		}

		return $this->getOrder($orderId);
	}

}

$controller = new OrdersController();
$data = $controller->route();
