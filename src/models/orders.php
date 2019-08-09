<?php
/**
 * Orders Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class OrdersModel extends AppModel {

/**
 * List orders
 *
 * @return array Orders data
 */
	public function list() {
		return array();
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
			empty($orderId = $parameters['id']) ||
			!is_numeric($orderId)
		) {
			$this->redirect($this->settings['base_url'] . 'orders');
		}

		return array(
			'order_id' => $parameters['id'],
			'results_per_page' => 50
		);
	}

}
