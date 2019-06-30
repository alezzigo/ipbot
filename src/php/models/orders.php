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
 * Get orders data
 *
 * @return array Orders data
 */
	public function getOrders() {
		$orders = $this->find('orders');
		return array(
			'orders' => $orders['data']
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
			'order' => $order['data'][0],
			'pagination' => $pagination
		);
	}

}
