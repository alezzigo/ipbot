<?php
/**
 * Orders Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once('../../models/app.php');

class Orders extends App {

/**
 * List orders
 *
 * @return array Orders data
 */
	public function index() {
		return array(
			'orders' => $this->find('orders')
		);
	}

/**
 * View order
 *
 * @return array Order data
 */
	public function view() {
		$orderId = $this->validateId(!empty($_GET['id']) ? $_GET['id'] : '', 'orders') ? $_GET['id'] : $this->redirect($this->config['base_url']);
		return array(
			'orders' => $this->find('orders', array(
				'id' => $orderId
			))
		);
	}

}

$controller = new Orders();
$data = $controller->route();
