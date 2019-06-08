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
 * List orders
 *
 * @return array Orders data
 */
	public function index() {
		return $this->getOrders();
	}

/**
 * View order
 *
 * @return array Order data
 */
	public function view() {
		$orderId = $this->validateId(!empty($_GET['id']) ? $_GET['id'] : '', 'orders') ? $_GET['id'] : $this->redirect($this->config['base_url']);
		return $this->getOrder($orderId);
	}

}

$controller = new OrdersController();
$data = $controller->route();
