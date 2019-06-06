<?php
/**
 * Orders Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once('../../models/app.php');

class OrdersModel extends App {

/**
 * Get orders data
 * *
 * @return array Orders data
 */
	public function getOrders() {
		return array(
			'orders' => $this->find('orders')
		);
	}

/**
 * Get order data
 *
 * @param string $id Order ID
 *
 * @return array Order data
 */
	public function getOrder($id) {
		$order = $this->find('orders', array(
			'id' => $id
		));
		$proxies = $this->find('proxies', array(
			'order_id' => $id
		), 'ip DESC');
		$nodes = $this->find('nodes', array(
			'id' => $this->_extract($proxies, 'node_id')
		), 'ip DESC');
		$servers = $this->find('servers', array(
			'id' => array_unique($this->_extract($nodes, 'server_id'))
		));

		foreach ($servers as $index => $server) {
			$servers[$server['id']] = $server;
			unset($servers[$index]);
		}

		foreach ($nodes as $index => $node) {
			$proxies[$proxies[$index]['id']] = array_merge($servers[$node['server_id']], $proxies[$index], array(
				'type' => $order['type']
			));
			unset($proxies[$index]);
		}

		return array(
			'order' => $order[0],
			'proxies' => $proxies,
			'servers' => $servers
		);
	}

}
