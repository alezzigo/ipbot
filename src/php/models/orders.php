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
 * Process search requests
 * @todo Format [match all] terms and filter, parse and format granular IP / subnet search, process query
 *
 * @param array $data Request data
 *
 * @return array $response Response data
 */
	protected function _processSearch($data, $response = array()) {
		$broadSearchConditions = array();
		$broadSearchFields = array('ip', 'asn', 'isp', 'city', 'region', 'country_name', 'country_code', 'timezone', 'status', 'whitelisted_ips', 'username', 'password', 'group_name');

		if (!empty($broadSearchTerms = array_filter(explode(' ', $data['broad_search'])))) {
			$broadSearchConditions = array_map(function($broadSearchTerm) use ($broadSearchFields, $data) {
				$broadSearchCondition = array_map(function($broadSearchField) {
					return $broadSearchField;
				}, $broadSearchFields);
				array_walk($broadSearchCondition, function(&$value, $key) use ($broadSearchTerm, $data) {
					$value = $value . ($data['exclude_search'] ? ' NOT' : null) . ' LIKE %' . $broadSearchTerm . '%';
				}, $broadSearchTerm);
				return $broadSearchCondition;
			}, $broadSearchTerms);
		}

		// ... $broadSearchConditions is populated for [match_all]

		if (!empty($data['granular_search'])) {
			// ... parse IPs / subnets
		}

		// ... find()
		return $response;
	}

/**
* Format timestamps to custom countdown timer format ([days]d [minutes]m, [hours]h)
*
* @param string $timestamp Timestamp
*
* @return string $countdown Countdown format, boolean false if current time exceeds timestamp
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
			'conditions' => array(
				'id' => $id
			),
			'fields' => array(
				'id',
				'name',
				'status'
			)
		));
		$proxies = $this->find('proxies', array(
			'conditions' => array(
				'order_id' => $id
			),
			'fields' => array(
				'id',
				'user_id',
				'order_id',
				'node_id',
				'ip',
				'http_port',
				'whitelisted_ips',
				'username',
				'password',
				'disable_http',
				'require_authentication',
				'group_name',
				'next_replacement_available',
				'replacement_removal_date',
				'last_replacement_date',
				'auto_replacement_interval_type',
				'auto_replacement_interval_value',
				'status',
				'created'
			),
			'order' => 'ip DESC'
		));
		$nodes = $this->find('nodes', array(
			'conditions' => array(
				'id' => $this->_extract($proxies, 'node_id')
			),
			'fields' => array(
				'id',
				'server_id',
				'ip'
			),
			'order' => 'ip DESC'
		));
		$servers = $this->find('servers', array(
			'conditions' => array(
				'id' => array_unique($this->_extract($nodes, 'server_id'))
			),
			'fields' => array(
				'id',
				'ip',
				'asn',
				'isp',
				'city',
				'region',
				'country_name',
				'country_code',
				'timezone',
				'status',
				'created'
			)
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

/**
 * Process order configuration requests
 *
 * @param array $data Request data
 *
 * @return array $response Response data
 */
	public function processConfiguration($data) {
		if (!method_exists($this, $configurationActionMethod = '_process' . preg_replace('/\s+/', '', ucwords(str_replace('_', ' ', $data['configuration_action']))))) {
			return false;
		}

		$response = $this->$configurationActionMethod($data);
	}

}
