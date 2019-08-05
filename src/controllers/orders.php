<?php
/**
 * Orders Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/controllers/app.php');
require_once($config->settings['base_path'] . '/models/orders.php');

class OrdersController extends OrdersModel {

/**
 * List orders
 *
 * @param array $parameters Parameters
 *
 * @return array Orders data
 */
	public function index($parameters) {
		return $this->getOrders($parameters);
	}

/**
 * View order
 *
 * @return array Order data
 */
	public function view() {
		$orderId = $this->validateId(!empty($_GET['id']) ? $_GET['id'] : '', 'orders') ? $_GET['id'] : $this->redirect($this->settings['base_url']);
		return $this->getOrder($orderId);
	}

}

$appController = new AppController();
$ordersController = new OrdersController();
$parameters = $appController->authenticate('orders');
$data = $ordersController->route($parameters);
