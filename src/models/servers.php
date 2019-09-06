<?php
/**
 * Servers Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class ServersModel extends AppModel {

/**
 * Retrieve server data
 *
 * @return array $response
 */
	protected function _retrieveServerDetails() {
		$response = $defaultResponse = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Access denied from ' . ($serverIp = $_SERVER['REMOTE_ADDR']) . ', please try again.')
			)
		);

		$server = $this->find('servers', array(
			'conditions' => array(
				'ip' => $serverIp,
				'status' => 'online'
			),
			'fields' => array(
				'id'
			)
		));

		if (!empty($server['count'])) {
			$response['message']['status'] = 'Duplicate server IPs, please check database.';

			if ($server['count'] === 1) {
				$response['message']['status'] = 'No active nodes available on gateway server.';
				$nodeIds = $this->find('nodes', array(
					'conditions' => array(
						'allocated' => true,
						'server_id' => $server['data'][0]
					),
					'fields' => array(
						'id'
					)
				));

				if (!empty($nodeIds['count'])) {
					$response['message']['status'] = 'No active proxies available on gateway server.';
					$proxies = $this->find('proxies', array(
						'conditions' => array(
							'node_id' => $nodeIds['data'],
							'status' => 'online'
						),
						'fields' => array(
							'asn',
							'city',
							'country_name',
							'country_code',
							'disable_http',
							'http_port',
							'id',
							'ip',
							'isp',
							'node_id',
							'password',
							'region',
							'require_authentication',
							'status',
							'username',
							'whitelisted_ips'
						),
						'sort' => array(
							'field' => 'modified',
							'order' => 'DESC'
						)
					));

					if (!empty($proxies['count'])) {
						// Format proxy server configuration
					}
				}
			}
		}

		return $response;
	}

}
