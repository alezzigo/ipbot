<?php
/**
 * Orders Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/orders.php');

class OrdersController extends OrdersModel {

/**
 * Orders API
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$ordersController = new OrdersController();
$data = $ordersController->route();
