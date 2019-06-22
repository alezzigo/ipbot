<?php
/**
 * Orders Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once('../../models/app.php');

class OrdersModel extends AppModel {

/**
 * Process replace requests
 *
 * @param array $data Request data
 * @param integer $orderId Order ID
 *
 * @return array $response Response data
 */
	protected function _processReplace($data, $orderId) {
		if (empty($data['proxies'])) {
			return false;
		}

		$processingNodesDirectory = $_SERVER['DOCUMENT_ROOT'] . $this->config['base_url'] . 'temp/nodes/';
		$proxies = $this->find('proxies', array(
			'fields' => array(
				'id',
				'node_id'
			)
		));
		$response = array(
			'message' => ''
		);
		$proxiesToReplace = explode(',', $data['proxies']);

		if (!is_dir($processingNodesDirectory)) {
			mkdir($processingNodesDirectory, 0777, true);
		}

		$processingNodes = explode(',', implode(',', array_map(function($fileName) use ($processingNodesDirectory) {
			$file = $processingNodesDirectory . $fileName;
			$nodes = file_get_contents($file);
			return time() - filemtime($file) > 10 ? (unlink($file) && (true === false)) : $nodes;
		}, array_diff(scandir($processingNodesDirectory), array('.', '..', '.DS_Store')))));
		$nodes = $this->find('nodes', array(
			'conditions' => array(
				'NOT' => array(
					'id' => array_merge($this->_extract($proxies, 'node_id', true), array_filter(array_unique($processingNodes)))
				)
			),
			'fields' => array(
				'id',
				'ip',
				'asn',
				'isp',
				'city',
				'region',
				'country_name',
				'country_code'
			),
			'limit' => count($proxiesToReplace),
			'order' => 'RAND()'
		));

		file_put_contents($processingNodesDirectory . $orderId, implode(',', $this->_extract($nodes, 'id', true)));

		if (!empty($nodes)) {
			foreach ($proxiesToReplace as $proxy) {
				// ...
			}
		}

		unlink($processingNodesDirectory . $orderId);
		return $response;
	}

/**
 * Process search requests
 *
 * @param array $data Request data
 *
 * @return array $response Response data
 */
	protected function _processSearch($data) {
		$broadSearchConditions = array();
		$broadSearchFields = array('ip', 'asn', 'isp', 'city', 'region', 'country_name', 'country_code', 'timezone', 'status', 'whitelisted_ips', 'username', 'password', 'group_name');
		$conditions = array();
		$response = array(
			'message' => ''
		);

		if (!empty($broadSearchTerms = array_filter(explode(' ', $data['broad_search'])))) {
			$conditions = array_map(function($broadSearchTerm) use ($broadSearchFields, $data) {
				return array(
					'OR' => array_fill_keys($broadSearchFields, '%' . $broadSearchTerm . '%')
				);
			}, $broadSearchTerms);
		}

		if (
			!empty($data['granular_search']) &&
			($conditions['ip'] = $this->_parseIps($data['granular_search'], true))
		) {
			array_walk($conditions['ip'], function(&$value, $key) use ($data) {
				$value .= '%'; // Add trailing wildcard for A/B/C class subnet search
			});
		}

		if (!empty($conditions)) {
			$conditions = array(
				($data['match_all_search'] ? 'AND' : 'OR') => $conditions
			);
		}

		if (!empty($data['exclude_search'])) {
			$conditions = array(
				'NOT' => $conditions
			);
		}

		$response['results'] = $this->_extract($this->find('proxies', array(
			'conditions' => $conditions,
			'fields' => array(
				'id'
			)
		)), 'id');

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
 * @todo Retrieve pagination results with javascript, format timer countdowns with Javascript on front end
 *
 * @param string $id Order ID
 * @param array $proxyIds Proxy IDs
 *
 * @return array Order data
 */
	public function getOrder($id, $proxyIds) {
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
			'current_page' => 1,
			'results_per_page' => 50
		);

		return array(
			'order' => $order[0],
			'pagination' => $pagination
		);
	}

/**
 * Process order configuration requests
 *
 * @param array $data Request data
 * @param integer $orderId Order ID
 *
 * @return array $response Response data
 */
	public function processConfiguration($data, $orderId) {
		if (!method_exists($this, $configurationActionMethod = '_process' . preg_replace('/\s+/', '', ucwords(str_replace('_', ' ', $data['configuration_action']))))) {
			return false;
		}

		return $this->$configurationActionMethod($data, $orderId);
	}

}
