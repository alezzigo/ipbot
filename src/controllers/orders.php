<?php
/**
 * Orders Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/controllers/app.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/models/orders.php');

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

$appController = new AppController();
$ordersController = new OrdersController();
$data = $ordersController->route();
