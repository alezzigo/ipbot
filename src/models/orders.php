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
 * Get orders data
 *
 * @return array Orders data
 */
	public function getOrders() {
		$orders = $this->find('orders');
		return array(
			'orders' => !empty($orders['count']) ? $orders['data'] : array(),
		);
	}

/**
 * Get order data
 * @todo Format timer countdowns with Javascript on front end
 *
 * @param string $id Order ID
 *
 * @return array Order data
 */
	public function getOrder($id) {
		$order = $this->find('orders', array(
			'conditions' => array(
				'id' => $id
			),
			'fields' => array(
				'id',
				'name',
				'status'
			)
		));

		$pagination = array(
			'results_per_page' => 50
		);

		return array(
			'order' => !empty($order['count']) ? $order['data'][0] : array(),
			'pagination' => $pagination
		);
	}

}
