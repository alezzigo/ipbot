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
		return;
	}

/**
 * View order
 *
 * @return array Order data
 */
	public function view() {
		if (
			empty($_GET['id']) ||
			!is_numeric($_GET['id'])
		) {
			$this->redirect($this->settings['base_url'] . '/views/orders/list.php');
		}

		return array(
			'order_id' => $_GET['id'],
			'results_per_page' => 50
		);
	}

}
