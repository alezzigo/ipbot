<?php
/**
 * Orders Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/models/orders.php');

class OrdersController extends OrdersModel {

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
 * @todo Move data processing and retrieval to API, set/remove search results with _session, add success / error messages
 *
 * @return array Order data
 */
	public function view() {
		$orderId = $this->validateId(!empty($_GET['id']) ? $_GET['id'] : '', 'orders') ? $_GET['id'] : $this->redirect($this->config['base_url']);
		$proxyIds = null;

		if (
			!empty($_POST['configuration_action']) &&
			strtolower($_SERVER['REQUEST_METHOD']) == 'post'
		) {
			$response = $this->processConfiguration($_POST, $orderId);
			$proxyIds = !empty($response['results']) ? $response['results'] : array();
		}

		return $this->getOrder($orderId, $proxyIds);
	}

}

$controller = new OrdersController();
$data = $controller->route();
