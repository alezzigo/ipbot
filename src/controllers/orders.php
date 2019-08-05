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
 * @param array $parameters Parameters
 *
 * @return array Order data
 */
	public function view($parameters) {
		if (
			empty($_GET['id']) ||
			!is_numeric($_GET['id'])
		) {
			$this->redirect($this->settings['base_url']);
		}

		$parameters['order_id'] = $_GET['id'];
		return $this->getOrder($parameters);
	}

}

$appController = new AppController();
$ordersController = new OrdersController();
$parameters = $appController->authenticate('orders');
$data = $ordersController->route($parameters);
