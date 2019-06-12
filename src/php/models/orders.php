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
* Format timestamps to custom countdown timer format ([days]d [minutes]m, [hours]h)
*
* @return string Countdown format, boolean false if current time exceeds timestamp
*/
	public function formatTimestampToCountdown($timestamp) {
		$countdown = (strtotime($timestamp) - time());

		if ($countdown <= 0) {
			return false;
		}

		$countdown = str_replace(';', 'd ', str_replace('!', 'm', str_replace(':', 'h ', gmdate('d;H:i!', $countdown))));
		$splitCountdown = explode(' ', $countdown);
		$countdown = '';

		if (!empty($splitCountdown[0])) {
			$day = (integer) str_replace('d', '', $splitCountdown[0]) - 1;

			if (!empty($day)) {
				$countdown .= $day . 'd ';
			}
		}

		if (!empty($splitCountdown[1])) {
			$hour = (integer) str_replace('h', '', $splitCountdown[1]);

			if (
				!empty($day) ||
				!empty($hour)
			) {
				$countdown .= $hour . 'h ';
			}
		}

		if (!empty($splitCountdown[2])) {
			$minute = (integer) str_replace('m', '', $splitCountdown[2]);
			$countdown .= $minute . 'm';
		}

		return $countdown;
	}

/**
 * Get orders data
 *
 * @return array Orders data
 */
	public function getOrders() {
		return array(
			'orders' => $this->find('orders')
		);
	}

/**
 * Get order data
 * @todo Pagination, format timer countdowns with Javascript on front end
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

		$proxyData = array(
			'current_page' => 1,
			'pagination_index' => 0,
			'results_per_page' => 100
		);

		foreach ($nodes as $index => $node) {
			if ($proxyData['pagination_index'] >= $proxyData['results_per_page']) {
				$proxyData['pagination_index'] = 0;
				$proxyData['current_page']++;
			}

			$proxyData['pagination_index']++;
			$proxyData['next_replacement_available_formatted'] = 'Available' . (!empty($proxies[$index]['next_replacement_available']) ? ' in ' . $this->formatTimestampToCountdown($proxies[$index]['next_replacement_available']) : '');
			$proxyData['replacement_removal_date_formatted'] = $proxies[$index]['status'] == 'replaced' ? 'Removal in ' . $this->formatTimestampToCountdown($proxies[$index]['replacement_removal_date']) : '';
			$proxies[$index] = array_merge($servers[$node['server_id']], $proxies[$index], $proxyData);
		}

		return array(
			'order' => $order[0],
			'proxies' => $proxies,
			'results_per_page' => $proxyData['results_per_page'],
			'servers' => $servers
		);
	}

}
