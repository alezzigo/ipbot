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
							'status !=' => 'offline'
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
						$response = array(
							'message' => array(
								'status' => 'success',
								'text' => 'Proxies retrieved for server ' . $serverIp . ' successfully.'
							)
						);
						$formattedAcls = $formattedFiles = $formattedProxies = $formattedUsers = $proxyAuthenticationAcls = $proxyIpAcls = $proxyWhitelistAcls = $proxyIps = array();
						$userIndex = 0;

						foreach ($proxies['data'] as $key => $proxy) {
							if (
								!empty($proxy['whitelisted_ips']) &&
								!empty($proxy['require_authentication'])
							) {
								$sources = json_encode(array_filter(explode("\n", $proxy['whitelisted_ips'])));

								if (
									empty($formattedProxies['whitelist'][$sources]) ||
									!in_array($proxy['ip'], $formattedProxies['whitelist'][$sources])
								) {
									$formattedProxies['whitelist'][$sources][] = $proxy['ip'];
								}
							}

							if (
								!empty($proxy['username']) &&
								!empty($proxy['password']) &&
								!empty($proxy['require_authentication'])
							) {
								if (
									empty($formattedProxies['authentication'][$proxy['username'] . $this->keys['start'] . $proxy['password']]) ||
									!in_array($proxy['ip'], $formattedProxies['authentication'][$proxy['username'] . $this->keys['start'] . $proxy['password']])
								) {
									$formattedProxies['authentication'][$proxy['username'] . $this->keys['start'] . $proxy['password']][] = $proxy['ip'];
								}
							}

							if (empty($proxy['require_authentication'])) {
								$formattedProxies['public'][] = $proxy['ip'];
							}

							if (!empty($proxy['disable_http'])) {
								$disabledProxies[$proxy['ip']]['disable_http'] = true;
							}

							if (!in_array(($proxyIp = $proxy['ip']), $proxyIps)) {
								$proxyIpAcls[] = 'acl ip' . $key . ' localip ' . $proxyIp;
								$proxyIpAcls[] = 'tcp_outgoing_address ' . $proxyIp . ' ip' . $key;
								$proxyIps[] = $proxyIp;
							}
						}

						if (!empty($formattedProxies['authentication'])) {
							foreach ($formattedProxies['authentication'] as $credentials => $destinations) {
								$splitAuthentication = explode($this->keys['start'], $credentials);
								$formattedAcls[] = 'acl user' . $userIndex . ' proxy_auth ' . $splitAuthentication[0];
								$formattedFiles[] = array(
									'path' => '/etc/squid3/users/' . $userIndex . '/d.txt',
									'contents' => implode("\n", $destinations)
								);
								$formattedUsers[$splitAuthentication[0]] = $splitAuthentication[1];
								$proxyAuthenticationAcls[] = 'acl d' . $userIndex . ' localip "/etc/squid3/users/' . $userIndex . '/d.txt"';
								$proxyAuthenticationAcls[] = 'http_access allow d' . $userIndex . ' user' . $userIndex;
								$userIndex++;
							}
						}

						$formattedAcls = array_merge($formattedAcls, $proxyIpAcls);

						if (!empty($formattedProxies['whitelist'])) {
							foreach ($formattedProxies['whitelist'] as $sources => $destinations) {
								$sources = json_decode($sources, true);
								$splitSources = array_chunk($sources, '500');

								foreach ($splitSources as $sourceChunk) {
									$formattedFiles[] = array(
										'path' => '/etc/squid3/users/' . $userIndex . '/d.txt',
										'contents' => implode("\n", $destinations)
									);
									$formattedFiles[] = array(
										'path' => '/etc/squid3/users/' . $userIndex . '/s.txt',
										'contents' => implode("\n", $sourceChunk)
									);
									$proxyWhitelistAcls[] = 'acl d' . $userIndex . ' localip "/etc/squid3/users/' . $userIndex . '/d.txt"';
									$proxyWhitelistAcls[] = 'acl s' . $userIndex . ' src "/etc/squid3/users/' . $userIndex . '/s.txt"';
									$proxyWhitelistAcls[] = 'http_access allow s' . $userIndex . ' d' . $userIndex;
									$userIndex++;
								}
							}
						}

						$formattedAcls = array_merge($formattedAcls, $proxyWhitelistAcls, $proxyAuthenticationAcls);

						if (!empty($formattedProxies['public'])) {
							$formattedFiles[] = array(
								'path' => '/etc/squid3/users/' . $userIndex . '/d.txt',
								'contents' => implode("\n", $formattedProxies['public'])
							);
							$formattedAcls[] = 'acl d' . $userIndex . ' localip "/etc/squid3/users/' . $userIndex . '/d.txt"';
							$formattedAcls[] = 'http_access allow d' . $userIndex . ' all';
						}

						$formattedAcls[] = 'http_access deny all';
						$response['data'] = array(
							'acls' => $formattedAcls,
							'files' => $formattedFiles,
							'users' => $formattedUsers
						);
					}
				}
			}
		}

		return $response;
	}

}
