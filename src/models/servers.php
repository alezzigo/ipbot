<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ServersModel extends AppModel {

	/**
	 * Format Squid access controls for list of IPs
	 *
	 * @param array $serverData
	 *
	 * @return array $response
	 */
		protected function _formatSquidAccessControls($serverData) {
			// TODO: Add process for continuous IP rotation with city selection, IPv6 support
			$disabledProxies = $formattedFiles = $formattedProxies = $formattedUsers = $gatewayAcls = $proxyAuthenticationAcls = $proxyIpAcls = $proxyWhitelistAcls = array();
			$formattedAcls = array(
				'auth_param basic program /usr/lib/squid3/basic_ncsa_auth /etc/squid3/passwords',
				'auth_param basic children 88888',
				'auth_param basic realm ' . $this->settings['site_name'],
				'auth_param basic credentialsttl 88888 days',
				'auth_param basic casesensitive on'
			);
			$userIndex = 0;

			if (!empty($serverData['proxy_ips'])) {
				foreach ($serverData['proxy_ips'] as $proxyIp => $proxyIndex) {
					$proxyIpAcls[] = 'acl ip' . $proxyIndex . ' localip ' . $proxyIp;
					$proxyIpAcls[] = 'tcp_outgoing_address ' . $proxyIp . ' ip' . $proxyIndex;
				}
			}

			$proxyData = array_intersect_key($serverData, array(
				'gateway_proxies' => true,
				'static_proxies' => true
			));

			foreach ($proxyData as $proxyType => $proxies) {
				foreach ($proxies as $proxy) {
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

					if (
						$proxyType === 'gateway_proxies' &&
						empty($proxy['allow_direct'])
					) {
						$gatewayAcls[] = 'always_direct deny ip' . $serverData['proxy_ips'][$proxy['ip']];
						$gatewayAcls[] = 'never_direct allow ip' . $serverData['proxy_ips'][$proxy['ip']];
					}

					$forwardingSources = array(
						$proxy['ip']
					);

					if (!empty($proxy['local_forwarding_proxies'])) {
						foreach ($proxy['local_forwarding_proxies'] as $localForwardingProxy) {
							$gatewayAcls[] = 'cache_peer ' . $localForwardingProxy['ip'] . ' parent ' . $localForwardingProxy['http_rotation_port'] . ' 4827 allow-miss connect-timeout=5 htcp=no-clr name=' . $localForwardingProxy['id'] . ' no-digest no-netdb-exchange no-query proxy-only round-robin';
							$gatewayAcls[] = 'cache_peer_access ' . $localForwardingProxy['id'] . ' allow ip' . $serverData['proxy_ips'][$proxy['ip']];
							$formattedProxies['whitelist'][json_encode($forwardingSources)] = $localForwardingProxy['ip'];
						}
					}

					if (!empty($proxy['static_proxies'])) {
						foreach ($proxy['static_proxies'] as $staticProxyChunkKey => $staticProxies) {
							$gatewayIp = $proxy['ip'];
							$staticProxyIps = array();

							if (!empty($proxy['local_forwarding_proxies'])) {
								$forwardingSources[] = $gatewayIp = $proxy['local_forwarding_proxies'][$staticProxyChunkKey]['ip'];

								if (empty($proxy['local_forwarding_proxies'][$staticProxyChunkKey]['allow_direct'])) {
									$gatewayAcls[] = 'always_direct deny ip' . $serverData['proxy_ips'][$gatewayIp];
									$gatewayAcls[] = 'never_direct allow ip' . $serverData['proxy_ips'][$gatewayIp];
								}
							}

							$loadBalanceMethod = empty($staticProxies[1]) ? 'default' : 'round-robin';

							foreach ($staticProxies as $staticProxy) {
								$gatewayAcls[] = 'cache_peer ' . $staticProxy['ip'] . ' parent ' . $staticProxy['http_rotation_port'] . ' 4827 allow-miss connect-timeout=5 htcp=no-clr name=' . $staticProxy['id'] . ' no-digest no-netdb-exchange no-query proxy-only ' . $loadBalanceMethod;
								$gatewayAcls[] = 'cache_peer_access ' . $staticProxy['id'] . ' allow ip' . $serverData['proxy_ips'][$gatewayIp];
								$formattedProxies['whitelist'][json_encode($forwardingSources)][] = $staticProxy['ip'];
							}
						}
					}

					if (empty($proxy['require_authentication'])) {
						$formattedProxies['public'][] = $proxy['ip'];
					}

					if (!empty($proxy['disable_http'])) {
						$disabledProxies[$proxy['ip']] = $proxy['ip'];
					}
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

			$formattedAcls = array_merge($formattedAcls, $gatewayAcls);
			$formattedAcls[] = 'http_access deny all';
			$response = array(
				'acls' => implode("\n", $formattedAcls),
				'configuration' => implode("\n", $this->proxyConfigurations['http']['static']['squid']['configuration']),
				'files' => $formattedFiles,
				'users' => $formattedUsers
			);

			if (strpos($response['configuration'], '[dns_ips]') !== false) {
				$response['configuration'] = str_replace('[dns_ips]', '127.0.0.1 ' . implode(' ', $serverData['dns_ips']), $response['configuration']);
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
					'http_rotation_port',
					'id',
					'ip',
					'isp',
					'next_rotation_date',
					'node_id',
					'password',
					'previous_rotation_proxy_id',
					'previous_rotation_proxy_ip',
					'require_authentication',
					'rotation_frequency',
					'rotation_proxy_id',
					'rotation_proxy_ip',
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
						$rotateOnEveryRequest = (
							empty($gatewayProxy['rotation_frequency']) &&
							!is_numeric($gatewayProxy['rotation_frequency'])
						)
					) {
						$gatewayProxyForwardingProxyIds = $this->fetch('proxy_forwarding_proxies', $gatewayProxyIdParameters);
						$globalForwardingProxyParameters = $localForwardingProxyParameters = $proxyParameters;

						if (!empty($gatewayProxyForwardingProxyIds['count'])) {
							$globalForwardingProxyParameters['conditions'] = $localForwardingProxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
								'id' => $gatewayProxyForwardingProxyIds['data'],
								'type' => 'forwarding'
							));
							unset($globalForwardingProxyParameters['conditions']['node_id']);
							$globalForwardingProxies = $this->fetch('proxies', $globalForwardingProxyParameters);
							$localForwardingProxies = $this->fetch('proxies', $localForwardingProxyParameters);

							if (!empty($globalForwardingProxies['count'])) {
								$response['gateway_proxies'][$gatewayProxyKey]['global_forwarding_proxies'] = $globalForwardingProxies['data'];
							}

							if (!empty($localForwardingProxies['count'])) {
								$response['gateway_proxies'][$gatewayProxyKey]['local_forwarding_proxies'] = $localForwardingProxies['data'];
							}
						}
					}

					$gatewayProxyStaticProxyIds = $this->fetch('proxy_static_proxies', $gatewayProxyIdParameters);

					if (
						$rotateOnEveryRequest &&
						!empty($gatewayProxyStaticProxyIds['count'])
					) {
						$staticProxyParameters = $proxyParameters;
						$staticProxyParameters['conditions'] = array_merge($staticProxyParameters['conditions'], array(
							'id' => $gatewayProxyStaticProxyIds['data'],
							'type' => 'static'
						));
						unset($staticProxyParameters['conditions']['node_id']);
						$staticProxies = $this->fetch('proxies', $staticProxyParameters);

						if (!empty($staticProxies['count'])) {
							$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array(
								$staticProxies['data']
							);
						}

						if (
							!empty($response['gateway_proxies'][$gatewayProxyKey]['static_proxies']) &&
							!empty($response['gateway_proxies'][$gatewayProxyKey]['global_forwarding_proxies']) &&
							($gatewayGlobalForwardingProxies = $response['gateway_proxies'][$gatewayProxyKey]['global_forwarding_proxies']) &&
							($gatewayStaticProxies = $response['gateway_proxies'][$gatewayProxyKey]['static_proxies'])
						) {
							$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array_chunk($gatewayStaticProxies[0], ceil(count($gatewayStaticProxies[0]) / count($gatewayGlobalForwardingProxies)));
						}
					} elseif (
						!empty($gatewayProxy['rotation_proxy_id']) &&
						!empty($gatewayProxy['rotation_proxy_ip'])
					) {
						$rotationIntervalProxyParameters = $proxyParameters;
						$rotationIntervalProxyParameters['conditions'] = array(
							'id' => $gatewayProxy['rotation_proxy_id']
						);
						$rotationIntervalProxy = $this->fetch('proxies', $rotationIntervalProxyParameters);

						if (!empty($rotationIntervalProxy['count'])) {
							$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array(
								$rotationIntervalProxy['data']
							);
						}
					}
				}
			}

			$staticProxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
				'allow_direct' => true,
				'type' => 'static'
			));
			$staticProxies = $this->fetch('proxies', $staticProxyParameters);

			if (!empty($staticProxies['count'])) {
				$response['static_proxies'] = $staticProxies['data'];
			}

			unset($proxyParameters['conditions']['type']);
			$proxyParameters['fields'] = array(
				'ip'
			);
			$proxyIps = $this->fetch('proxies', $proxyParameters);

			if (!empty($proxyIps['count'])) {
				$proxyIps['data'] = array_unique($proxyIps['data']);
				$response['proxy_ips'] = array_combine($proxyIps['data'], range(0, count($proxyIps['data']) - 1));
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
						$proxyData = $this->_retrieveProxyData($nodeIds['data']);

						if (!empty($proxyData)) {
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
												$proxyData['static_proxies'][] = array(
													'ip' => $unallocatedNode['ip'],
													'require_authentication' => false
												);
												$proxyData['proxy_ips'][$unallocatedNode['id']] = $unallocatedNode['ip'];
											}
										}
									}

									$response = array(
										'data' => array_merge($proxyData, array(
											'dns_ips' => $this->_retrieveDnsIps($nodeIds['data']),
											'server' => $serverConfiguration
										)),
										'message' => array(
											'status' => 'success',
											'text' => 'Proxies retrieved for server ' . $serverIp . ' successfully.'
										)
									);

									if (!empty($response['data']['dns_ips'])) {
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
