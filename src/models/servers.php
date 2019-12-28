<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ServersModel extends AppModel {

	/**
	 * Format Squid access controls for list of IPs
	 *
	 * @param array $ipData
	 *
	 * @return array $response
	 */
		protected function _formatSquidAccessControls($ipData) {
			// TODO: Implement gateway proxy IPs with cache_peer for automatic IP rotation with custom rotation frequencies
			// http_access allow IP_ACL USER_ACL
			// always_direct deny USER_ACL
			// never_direct allow USER_ACL
			// cache_peer PROXY_IP parent 80 4827 htcp=no-clr allow-miss no-query no-digest no-tproxy proxy-only no-netdb-exchange round-robin connect-timeout=8 connect-fail-limit=88888 name=ORDER_ID-INDEX;
			// cache_peer_access ORDER_ID-INDEX allow IP_ACL;
			$disabledProxies = $formattedFiles = $formattedProxies = $formattedUsers = $proxyAuthenticationAcls = $proxyIpAcls = $proxyWhitelistAcls = $proxyIps = array();
			$formattedAcls = array(
				'auth_param basic program /usr/lib/squid3/basic_ncsa_auth /etc/squid3/passwords',
				'auth_param basic children 88888',
				'auth_param basic realm ' . $this->settings['site_name'],
				'auth_param basic credentialsttl 88888 days',
				'auth_param basic casesensitive on'
			);
			$userIndex = 0;

			foreach ($ipData['proxies'] as $key => $proxy) {
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
					$disabledProxies[$proxy['ip']] = $proxy['ip'];
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
			$response = array(
				'acls' => implode("\n", $formattedAcls),
				'configuration' => implode("\n", $this->proxyConfigurations['http']['static']['squid']['configuration']),
				'files' => $formattedFiles,
				'users' => $formattedUsers
			);

			if (strpos($response['configuration'], '[dns_ips]') !== false) {
				$response['configuration'] = str_replace('[dns_ips]', '127.0.0.1 ' . implode(' ', $ipData['dns_ips']), $response['configuration']);
			}

			if (
				!empty($this->proxyConfigurations['http']['static']['squid']['ports']) &&
				!empty($disabledProxies)
			) {
				$splitDisabledPorts = array_chunk($this->proxyConfigurations['http']['static']['squid']['ports'], '10');
				$splitDisabledProxies = array_chunk($disabledProxies, '10');

				foreach ($splitDisabledProxies as $proxies) {
					foreach ($splitDisabledPorts as $ports) {
						$response['firewall_filter'][] = '-A INPUT -p tcp ! -i lo -d ' . implode(',', $proxies) . ' -m multiport --dports ' . implode(',', $ports) . ' -j DROP';
					}
				}
			}

			return $response;
		}

	/**
	 * Retrieve DNS IPs
	 *
	 * @param array $nodeIds
	 *
	 * @return array $response
	 */
		protected function _retrieveDnsIps($nodeIds) {
			$response = array();
			$dnsIps = $this->fetch('dns_ips', array(
				'conditions' => array(
					'node_id' => $nodeIds
				),
				'fields' => array(
					'ip'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'ASC'
				)
			));

			if (!empty($dnsIps['count'])) {
				$response = array_unique($dnsIps['data']);
			}

			return $response;
		}

	/**
	 * Retrieve proxy data
	 *
	 * @param array $nodeIds
	 *
	 * @return array $response
	 */
		protected function _retrieveProxyData($nodeIds) {
			$response = array();
			$proxyParameters = array(
				'conditions' => array(
					'node_id' => $nodeIds,
					'type' => 'gateway',
					'NOT' => array(
						'status' => 'offline'
					)
				),
				'fields' => array(
					'allow_direct',
					'disable_http',
					'http_port',
					'id',
					'ip',
					'isp',
					'node_id',
					'password',
					'previous_rotation_date',
					'require_authentication',
					'rotation_frequency',
					'rotation_node_id',
					'status',
					'type',
					'username',
					'whitelisted_ips'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'ASC'
				)
			);
			$gatewayProxies = $this->fetch('proxies', $proxyParameters);

			if (!empty($gatewayProxies['count'])) {
				$response['gateway_proxies'] = $gatewayProxies['data'];

				foreach ($response['gateway_proxies'] as $gatewayProxyKey => $gatewayProxy) {
					$gatewayProxyIdParameters = array(
						'conditions' => array(
							'gateway_proxy_id' => $gatewayProxy['id']
						),
						'fields' => array(
							'proxy_id'
						)
					);

					if (
						empty($gatewayProxy['rotation_frequency']) &&
						!is_numeric($gatewayProxy['rotation_frequency'])
					) {
						$gatewayProxyForwardingProxyIds = $this->fetch('proxy_forwarding_proxies', $gatewayProxyIdParameters);

						if (!empty($gatewayProxyForwardingProxyIds['count'])) {
							$proxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
								'id' => $gatewayProxyForwardingProxyIds['data'],
								'type' => 'forwarding'
							));
							$forwardingProxies = $this->fetch('proxies', $proxyParameters);

							if (!empty($forwardingProxies['count'])) {
								$response['gateway_proxies'][$gatewayProxyKey]['forwarding_proxies'] = $forwardingProxies['data'];
							}

							// ..
						}
					}

					$gatewayProxyStaticProxyIds = $this->fetch('proxy_static_proxies', $gatewayProxyIdParameters);

					if (!empty($gatewayProxyStaticProxyIds['count'])) {
						$proxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
							'id' => $gatewayProxyStaticProxyIds['data'],
							'type' => 'static'
						));
						$staticProxies = $this->fetch('proxies', $proxyParameters);

						if (!empty($staticProxies['count'])) {
							$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = $staticProxies['data'];
						}

						// ..
					}
				}
			}

			unset($proxyParameters['conditions']['id']);
			$proxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
				'allow_direct' => true,
				'type' => 'static'
			));
			$staticProxies = $this->fetch('proxies', $proxyParameters);

			if (!empty($staticProxies['count'])) {
				$response['static_proxies'] = $staticProxies['data'];
			}

			unset($proxyParameters['conditions']['type']);
			$proxyParameters['fields'] = array(
				'ip'
			);
			$proxyIps = $this->fetch('proxies', $proxyParameters);

			if (!empty($proxyIps['count'])) {
				$response['proxy_ips'] = array_unique($proxyIps['data']);
			}

			return $response;
		}

	/**
	 * Retrieve server data
	 *
	 * @return array $response
	 */
		protected function _retrieveServerData() {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Access denied from ' . ($serverIp = $_SERVER['REMOTE_ADDR']) . ', please try again.'
				)
			);
			$server = $this->fetch('servers', array(
				'conditions' => array(
					'ip' => $serverIp,
					'status' => 'online'
				),
				'fields' => array(
					'http_proxy_configuration',
					'id',
					'ip',
					'server_configuration',
					'server_configuration_type'
				)
			));
			$proxyConfiguration = $serverConfiguration = array();

			if (!empty($server['count'])) {
				$response['message']['text'] = 'Duplicate server IPs, please check server options in database.';

				if ($server['count'] === 1) {
					$response['message']['text'] = 'No active nodes available on gateway server.';
					$nodeIds = $this->fetch('nodes', array(
						'conditions' => array(
							'allocated' => true,
							'server_id' => $server['data'][0]['id']
						),
						'fields' => array(
							'id'
						)
					));

					if (!empty($nodeIds['count'])) {
						$response['message']['text'] = 'No active proxies available on server.';
						$dnsIps = $this->_retrieveDnsIps($nodeIds['data']);
						$proxies = $this->fetch('proxies', array(
							'conditions' => array(
								'node_id' => $nodeIds['data'],
								'NOT' => array(
									'status' => 'offline'
								)
							),
							'fields' => array(
								'disable_http',
								'http_port',
								'id',
								'ip',
								'isp',
								'node_id',
								'password',
								'previous_rotation_date',
								'require_authentication',
								'rotation_frequency',
								'status',
								'type',
								'username',
								'whitelisted_ips'
							),
							'sort' => array(
								'field' => 'modified',
								'order' => 'DESC'
							)
						));
						// ..

						if (
							!empty($dnsIps) &&
							!empty($proxies['count'])
						) {
							$response['message']['status'] = 'Invalid server configuration type, please check your configuration file and server options in database.';

							if (
								!empty($this->serverConfigurations) &&
								!empty($server['data'][0]['server_configuration']) &&
								!empty($server['data'][0]['server_configuration_type']) &&
								!empty($this->serverConfigurations[$server['data'][0]['server_configuration']][$server['data'][0]['server_configuration_type']])
							) {
								$response['message']['status'] = 'Invalid proxy configuration settings, please check your configuration file and server options in database.';
								$serverConfiguration = $this->serverConfigurations[$server['data'][0]['server_configuration']][$server['data'][0]['server_configuration_type']];

								if (
									!empty($this->proxyConfigurations) &&
									is_array($this->proxyConfigurations)
								) {
									foreach ($proxies['data'] as $proxy) {
										$proxyIps[$proxy['node_id']] = $proxy['ip'];
									}

									if (!empty($this->settings['open_unallocated_proxies'])) {
										$unallocatedNodes = $this->fetch('nodes', array(
											'conditions' => array(
												'allocated' => false,
												'server_id' => $server['data'][0]['id']
											),
											'fields' => array(
												'id',
												'ip'
											)
										));

										if (!empty($unallocatedNodes['count'])) {
											foreach ($unallocatedNodes['data'] as $unallocatedNode) {
												$proxies['data'][] = array(
													'ip' => $unallocatedNode['ip'],
													'require_authentication' => false
												);
												$proxyIps[$unallocatedNode['id']] = $unallocatedNode['ip'];
											}
										}
									}

									$response = array(
										'data' => array(
											'dns_ips' => $dnsIps,
											'proxies' => $proxies['data'],
											'proxy_ips' => array_unique($proxyIps),
											'server' => $serverConfiguration
										),
										'message' => array(
											'status' => 'success',
											'text' => 'Proxies retrieved for server ' . $serverIp . ' successfully.'
										)
									);

									foreach ($this->proxyConfigurations as $proxyProtocol => $proxyConfiguration) {
										// ..

										if (
											!empty($proxyConfiguration) &&
											!empty($proxyConfiguration[$server['data'][0]['server_configuration_type']][$proxyConfigurationType = $server['data'][0][$proxyProtocol . '_proxy_configuration']]) &&
											method_exists($this, ($method = '_format' . ucwords($proxyConfigurationType) . 'AccessControls')) &&
											($formattedAcls = $this->$method($response['data']))
										) {
											$response['data'][$proxyProtocol] = $formattedAcls;
										}
									}

									if (!empty($response['data'])) {
										$response['message'] = array(
											'status' => 'success',
											'text' => 'Proxies retrieved for server ' . $serverIp . ' successfully.'
										);
									}
								}
							}
						}
					}
				}
			}

			return $response;
		}

	}
?>
