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
 * @param array $parameters Parameters
 *
 * @return array Orders data
 */
	public function getOrders($parameters) {
		$orders = $this->find('orders', array(
			'conditions' => array(
				'user_id' => !empty($parameters['user']['id']) ? $parameters['user']['id'] : false
			)
		));
		return array(
			'orders' => !empty($orders['count']) ? $orders['data'] : array(),
		);
	}

/**
 * Get order data
 *
 * @param array $parameters Parameters
 *
 * @return array Order data
 */
	public function getOrder($parameters) {
		$order = $this->find('orders', array(
			'conditions' => array(
				'id' => $parameters['order_id'],
				'user_id' => !empty($parameters['user']['id']) ? $parameters['user']['id'] : false
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
